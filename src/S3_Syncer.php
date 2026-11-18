<?php

/**
 * Syncs files to S3-compatible storage.
 *
 * This class automatically uploads files from the hizzle_uploads directory to S3-compatible
 * storage services like Amazon S3 and Cloudflare R2.
 *
 * Note: This implementation uses AWS Signature Version 4 for enhanced security and
 * compatibility with modern S3-compatible services. For very large files, consider
 * implementing streaming uploads to avoid memory issues.
 *
 * @version 1.0.0
 */

namespace Hizzle\Downloads;

defined( 'ABSPATH' ) || exit;

/**
 * Syncs files to S3-compatible storage.
 */
class S3_Syncer {
	/**
	 * Loads the class.
	 */
	public static function init() {

		// Listen to download created and updated hooks.
		add_action( 'hizzle_download_download_created', __CLASS__ . '::sync_download' );
		add_action( 'hizzle_download_download_updated', __CLASS__ . '::sync_download' );

		// Register the upload to S3 action.
		add_action( 'hizzle_downloads_upload_to_s3', __CLASS__ . '::upload_to_s3', 10, 4 );
	}

	/**
	 * Syncs a download to S3.
	 *
	 * @param Download $download The download object.
	 */
	public static function sync_download( $download ) {

		// Remove the base directory from the file path.
		$new_file_name = self::s3_key( $download );

		if ( empty( $new_file_name ) ) {
			return;
		}

		$parsed    = $download->parse_file_path();
		$file_path = $parsed['file_path'];

		// Get the hostname.
		$host_name = wp_parse_url( home_url(), PHP_URL_HOST );

		// Validate hostname.
		if ( empty( $host_name ) ) {
			return hizzle_downloads()->logger->error( 'Failed to parse hostname from home_url()', 'hizzle_downloads' );
		}

		// Prepare the S3 key.
		$s3_key = wp_normalize_path( $host_name . '/' . $new_file_name );

		// Upload to S3.
		if ( function_exists( 'schedule_noptin_background_action' ) ) {
			schedule_noptin_background_action(
				time() + 30,
				'hizzle_downloads_upload_to_s3',
				$file_path,
				$s3_key,
				$download->get_downloaded_file_name()
			);
		} else {
			do_action( 'hizzle_downloads_upload_to_s3', $file_path, $s3_key, $download->get_downloaded_file_name() );
		}
	}

	/**
	 * Fetches a file's S3 key.
	 *
	 * @param Download $download The download object.
	 */
	public static function s3_key( $download ) {

		// Ensure we have a Download object.
		if ( ! $download instanceof Download ) {
			return '';
		}

		// Parse the file path.
		$parsed = $download->parse_file_path();

		// Only sync local files.
		if ( ! empty( $parsed['remote_file'] ) ) {
			return '';
		}

		$file_path = $parsed['file_path'];

		// Ensure the file exists.
		if ( ! file_exists( $file_path ) ) {
			return '';
		}

		// Get the upload directory.
		$upload_dir = wp_upload_dir();
		$base_dir   = wp_normalize_path( trailingslashit( $upload_dir['basedir'] ) );

		// Check if the file is in the hizzle_uploads directory.
		if ( 0 !== strpos( $file_path, $base_dir ) ) {
			return '';
		}

		// Remove the base directory from the file path.
		return str_replace( $base_dir, '', $file_path );
	}

	/**
	 * Uploads a private file with optional S3/R2 custom metadata.
	 *
	 * @param string $local_file_path Local file.
	 * @param string $bucket_path Object key.
	 * @param string $downloaded_file_name Attachment filename.
	 * @param array  $metadata ASCII custom metadata, without the x-amz-meta- prefix.
	 * @return array|\WP_Error HTTP response or error.
	 */
	public static function upload_to_s3( $local_file_path, $bucket_path, $downloaded_file_name, $metadata = array() ) {
		return self::request_s3( 'PUT', $bucket_path, $local_file_path, $downloaded_file_name, $metadata );
	}

	/**
	 * Removes an object when a synced download is deleted or becomes unavailable.
	 *
	 * @param string $bucket_path Object key.
	 * @return array|\WP_Error HTTP response or error.
	 */
	public static function delete_from_s3( $bucket_path ) {
		return self::request_s3( 'DELETE', $bucket_path );
	}

	/**
	 * Signs file and metadata together so policy cannot be substituted in transit.
	 *
	 * @param string $method PUT or DELETE.
	 * @param string $bucket_path Object key.
	 * @param string $local_file_path Local file for PUT.
	 * @param string $filename Attachment name.
	 * @param array  $metadata Custom metadata.
	 * @return array|\WP_Error HTTP response or error.
	 */
	private static function request_s3( $method, $bucket_path, $local_file_path = '', $filename = '', $metadata = array() ) {
		$access_key  = defined( 'HIZZLE_DOWNLOADS_S3_ACCESS_KEY' ) ? HIZZLE_DOWNLOADS_S3_ACCESS_KEY : '';
		$secret_key  = defined( 'HIZZLE_DOWNLOADS_S3_SECRET_KEY' ) ? HIZZLE_DOWNLOADS_S3_SECRET_KEY : '';
		$bucket_name = defined( 'HIZZLE_DOWNLOADS_S3_BUCKET' ) ? HIZZLE_DOWNLOADS_S3_BUCKET : '';
		$region      = defined( 'HIZZLE_DOWNLOADS_S3_REGION' ) ? HIZZLE_DOWNLOADS_S3_REGION : 'us-east-1';
		$endpoint    = defined( 'HIZZLE_DOWNLOADS_S3_ENDPOINT' ) ? HIZZLE_DOWNLOADS_S3_ENDPOINT : '';
		if ( ! $endpoint || ! $access_key || ! $secret_key || ! $bucket_name ) {
			return new \WP_Error( 's3_not_configured', 'S3 credentials are not configured.' );
		}
		if ( 'PUT' === $method && ! is_readable( $local_file_path ) ) {
			return new \WP_Error( 's3_file_missing', 'The local download file is unavailable.' );
		}
		$content = 'PUT' === $method ? file_get_contents( $local_file_path ) : '';
		if ( false === $content ) {
			return new \WP_Error( 's3_read_failed', 'The local download file could not be read.' );
		}
		$mime  = 'PUT' === $method ? mime_content_type( $local_file_path ) : 'application/octet-stream';
		$mime  = $mime ? $mime : 'application/octet-stream';
		$parts = wp_parse_url( $endpoint );
		if ( empty( $parts['host'] ) || empty( $parts['scheme'] ) ) {
			return new \WP_Error( 's3_invalid_endpoint', 'The S3 endpoint is invalid.' );
		}
		$host              = $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' );
		$uri               = '/' . rawurlencode( $bucket_name ) . '/' . implode( '/', array_map( 'rawurlencode', explode( '/', ltrim( $bucket_path, '/' ) ) ) );
		$url               = $parts['scheme'] . '://' . $host . $uri;
		$amz_date          = gmdate( 'Ymd\THis\Z' );
		$date_stamp        = substr( $amz_date, 0, 8 );
		$payload_hash      = hash( 'sha256', $content );
		$canonical_headers = array(
			'content-type'         => $mime,
			'host'                 => $host,
			'x-amz-content-sha256' => $payload_hash,
			'x-amz-date'           => $amz_date,
		);
		if ( 'PUT' === $method ) {
			$canonical_headers['x-amz-acl'] = 'private';
		}
		foreach ( $metadata as $name => $value ) {
			if ( ! preg_match( '/^[a-z0-9-]+$/D', $name ) || ! is_string( $value ) || preg_match( '/[^\x20-\x7e]/', $value ) ) {
				return new \WP_Error( 's3_invalid_metadata', 'S3 metadata must have safe ASCII names and values.' );
			}
			$canonical_headers[ 'x-amz-meta-' . $name ] = trim( $value );
		}
		ksort( $canonical_headers );
		$header_lines = '';
		foreach ( $canonical_headers as $name => $value ) {
			$header_lines .= $name . ':' . preg_replace( '/ +/', ' ', trim( $value ) ) . "\n";
		}
		$signed_headers            = implode( ';', array_keys( $canonical_headers ) );
		$canonical                 = $method . "\n" . $uri . "\n\n" . $header_lines . "\n" . $signed_headers . "\n" . $payload_hash;
		$scope                     = $date_stamp . '/' . $region . '/s3/aws4_request';
		$to_sign                   = "AWS4-HMAC-SHA256\n" . $amz_date . "\n" . $scope . "\n" . hash( 'sha256', $canonical );
		$key                       = hash_hmac( 'sha256', $date_stamp, 'AWS4' . $secret_key, true );
		$key                       = hash_hmac( 'sha256', $region, $key, true );
		$key                       = hash_hmac( 'sha256', 's3', $key, true );
		$key                       = hash_hmac( 'sha256', 'aws4_request', $key, true );
		$headers                   = $canonical_headers;
		$headers['Authorization']  = 'AWS4-HMAC-SHA256 Credential=' . $access_key . '/' . $scope
			. ', SignedHeaders=' . $signed_headers . ', Signature=' . hash_hmac( 'sha256', $to_sign, $key );
		$headers['Content-Length'] = strlen( $content );
		if ( $filename ) {
			$headers['Content-Disposition'] = 'attachment; filename="' . sanitize_file_name( $filename ) . '"';
		}
		$response = wp_remote_request(
			$url,
			array(
				'method'    => $method,
				'headers'   => $headers,
				'body'      => $content,
				'timeout'   => 60,
				'sslverify' => true,
				'blocking'  => true,
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$status = wp_remote_retrieve_response_code( $response );
		return $status >= 200 && $status < 300 ? $response : new \WP_Error( 's3_request_failed', 'S3 request failed with status ' . $status );
	}
}
