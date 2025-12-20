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
		add_action( 'hizzle_downloads_upload_to_s3', __CLASS__ . '::upload_to_s3', 10, 3 );
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
	 * Upload a file to S3-compatible storage using AWS Signature Version 4
	 *
	 * @param string $local_file_path Full path to the local file
	 * @param string $bucket_path     Path in the bucket (e.g., 'folder/file.pdf')
	 * @param string $downloaded_file_name The name of the downloaded file
	 */
	public static function upload_to_s3( $local_file_path, $bucket_path, $downloaded_file_name ) {

		$access_key  = defined( 'HIZZLE_DOWNLOADS_S3_ACCESS_KEY' ) ? HIZZLE_DOWNLOADS_S3_ACCESS_KEY : '';
		$secret_key  = defined( 'HIZZLE_DOWNLOADS_S3_SECRET_KEY' ) ? HIZZLE_DOWNLOADS_S3_SECRET_KEY : '';
		$bucket_name = defined( 'HIZZLE_DOWNLOADS_S3_BUCKET' ) ? HIZZLE_DOWNLOADS_S3_BUCKET : '';
		$region      = defined( 'HIZZLE_DOWNLOADS_S3_REGION' ) ? HIZZLE_DOWNLOADS_S3_REGION : 'us-east-1';

		// Check if file exists
		if ( ! file_exists( $local_file_path ) ) {
			return hizzle_downloads()->logger->error(
				'Local file does not exist: ' . $local_file_path,
				'hizzle-downloads'
			);
		}

		// Get S3 credentials from constants
		$endpoint   = defined( 'HIZZLE_DOWNLOADS_S3_ENDPOINT' ) ? HIZZLE_DOWNLOADS_S3_ENDPOINT : '';
		$access_key = defined( 'HIZZLE_DOWNLOADS_S3_ACCESS_KEY' ) ? HIZZLE_DOWNLOADS_S3_ACCESS_KEY : '';
		$secret_key = defined( 'HIZZLE_DOWNLOADS_S3_SECRET_KEY' ) ? HIZZLE_DOWNLOADS_S3_SECRET_KEY : '';

		// Validate credentials
		if ( empty( $endpoint ) || empty( $access_key ) || empty( $secret_key ) ) {
			return hizzle_downloads()->logger->error(
				'S3 credentials not configured. Please define HIZZLE_DOWNLOADS_S3_ENDPOINT, HIZZLE_DOWNLOADS_S3_ACCESS_KEY, and HIZZLE_DOWNLOADS_S3_SECRET_KEY.',
				'hizzle-downloads'
			);
		}

		// Prepare file data
		$bucket_path  = ltrim( $bucket_path, '/' );
		$file_content = file_get_contents( $local_file_path );
		$file_size    = filesize( $local_file_path );

		// Get file MIME type
		$mime_type = mime_content_type( $local_file_path );
		if ( ! $mime_type ) {
			$mime_type = 'application/octet-stream';
		}

		// Parse endpoint
		$endpoint_parts = wp_parse_url( $endpoint );
		$scheme         = isset( $endpoint_parts['scheme'] ) ? $endpoint_parts['scheme'] : 'https';
		$host           = isset( $endpoint_parts['host'] ) ? $endpoint_parts['host'] : $endpoint;
		$port           = isset( $endpoint_parts['port'] ) ? $endpoint_parts['port'] : ( 'https' === $scheme ? 443 : 80 );

		// Build the URL (path-style for S3-compatible services)
		$uri = '/' . $bucket_name . '/' . $bucket_path;
		$url = $scheme . '://' . $host;
		if ( ( 'https' === $scheme && 443 !== $port ) || ( 'http' === $scheme && 80 !== $port ) ) {
			$url .= ':' . $port;
		}
		$url .= $uri;

		// Prepare AWS Signature Version 4 headers
		$service        = 's3';
		$algorithm      = 'AWS4-HMAC-SHA256';
		$amz_date       = gmdate( 'Ymd\THis\Z' );
		$date_stamp     = gmdate( 'Ymd' );
		$content_sha256 = hash( 'sha256', $file_content );

		// Create canonical headers
		$canonical_headers = array(
			'content-type'         => $mime_type,
			'host'                 => $host,
			'x-amz-acl'            => 'private',
			'x-amz-content-sha256' => $content_sha256,
			'x-amz-date'           => $amz_date,
		);

		ksort( $canonical_headers );

		$canonical_headers_str = '';
		$signed_headers_str    = '';
		foreach ( $canonical_headers as $key => $value ) {
			$canonical_headers_str .= $key . ':' . trim( $value ) . "\n";
			$signed_headers_str    .= $key . ';';
		}
		$signed_headers_str = rtrim( $signed_headers_str, ';' );

		// Create canonical request
		$canonical_request  = "PUT\n";
		$canonical_request .= $uri . "\n";
		$canonical_request .= "\n"; // The query string is empty
		$canonical_request .= $canonical_headers_str . "\n";
		$canonical_request .= $signed_headers_str . "\n";
		$canonical_request .= $content_sha256;

		// Create string to sign
		$credential_scope = $date_stamp . '/' . $region . '/' . $service . '/aws4_request';
		$string_to_sign   = $algorithm . "\n";
		$string_to_sign  .= $amz_date . "\n";
		$string_to_sign  .= $credential_scope . "\n";
		$string_to_sign  .= hash( 'sha256', $canonical_request );

		// Calculate signing key
		$k_date    = hash_hmac( 'sha256', $date_stamp, 'AWS4' . $secret_key, true );
		$k_region  = hash_hmac( 'sha256', $region, $k_date, true );
		$k_service = hash_hmac( 'sha256', $service, $k_region, true );
		$k_signing = hash_hmac( 'sha256', 'aws4_request', $k_service, true );

		// Calculate signature
		$signature = hash_hmac( 'sha256', $string_to_sign, $k_signing );

		// Build authorization header
		$authorization  = $algorithm . ' ';
		$authorization .= 'Credential=' . $access_key . '/' . $credential_scope . ',';
		$authorization .= 'SignedHeaders=' . $signed_headers_str . ',';
		$authorization .= 'Signature=' . $signature;

		// Prepare HTTP headers for WordPress HTTP API
		$http_headers = array(
			'Content-Disposition'  => 'attachment; filename="' . sanitize_file_name( $downloaded_file_name ) . '"',
			'Content-Language'     => get_bloginfo( 'language' ),
			'Content-Type'         => $mime_type,
			'Content-Length'       => $file_size,
			'Host'                 => $host,
			'x-amz-acl'            => 'private',
			'x-amz-content-sha256' => $content_sha256,
			'x-amz-date'           => $amz_date,
			'Authorization'        => $authorization,
		);

		// Use WordPress HTTP API
		wp_remote_request(
			$url,
			array(
				'method'    => 'PUT',
				'headers'   => $http_headers,
				'body'      => $file_content,
				'timeout'   => 60,
				'sslverify' => true,
				'blocking'  => false,
			)
		);
	}
}
