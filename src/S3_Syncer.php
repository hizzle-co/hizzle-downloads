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
		add_action(
			'admin_init',
			function () {
				// For testing: Manually trigger sync.
				do_action( 'hizzle_download_download_created', hizzle_get_download( 83 ) );
			}
		);
	}

	/**
	 * Syncs a download to S3.
	 *
	 * @param Download $download The download object.
	 */
	public static function sync_download( $download ) {

		// Ensure we have a Download object.
		if ( ! $download instanceof Download ) {
			return;
		}

		// Parse the file path.
		$parsed = $download->parse_file_path();

		// Only sync local files.
		if ( ! empty( $parsed['remote_file'] ) ) {
			return;
		}

		$file_path = $parsed['file_path'];

		// Ensure the file exists.
		if ( ! file_exists( $file_path ) ) {
			return;
		}

		// Get the upload directory.
		$upload_dir = wp_upload_dir();
		$base_dir   = wp_normalize_path( trailingslashit( $upload_dir['basedir'] ) . 'hizzle_uploads/' );

		// Check if the file is in the hizzle_uploads directory.
		if ( 0 !== strpos( $file_path, $base_dir ) ) {
			return;
		}

		// Remove the base directory from the file path.
		$new_file_name = str_replace( $base_dir, '', $file_path );

		// Get the hostname.
		$host_name = wp_parse_url( home_url(), PHP_URL_HOST );

		// Validate hostname.
		if ( empty( $host_name ) ) {
			return hizzle_downloads()->logger->error( 'Failed to parse hostname from home_url()', 'hizzle_downloads' );
		}

		// Prepare the S3 key.
		$s3_key = wp_normalize_path( $host_name . '/' . $new_file_name );

		// Upload to S3.
		self::upload_to_s3( $file_path, $s3_key, $download->get_downloaded_file_name() );
	}

	/**
	 * Uploads a file to S3.
	 *
	 * @param string $file_path The local file path.
	 * @param string $s3_key    The S3 key (path).
	 * @param string $downloaded_file_name
	 */
	public static function upload_to_s3( $file_path, $s3_key, $downloaded_file_name ) {

		// Get S3 credentials from constants.
		$access_key = defined( 'HIZZLE_DOWNLOADS_S3_ACCESS_KEY' ) ? HIZZLE_DOWNLOADS_S3_ACCESS_KEY : '';
		$secret_key = defined( 'HIZZLE_DOWNLOADS_S3_SECRET_KEY' ) ? HIZZLE_DOWNLOADS_S3_SECRET_KEY : '';
		$bucket     = defined( 'HIZZLE_DOWNLOADS_S3_BUCKET' ) ? HIZZLE_DOWNLOADS_S3_BUCKET : '';
		$region     = defined( 'HIZZLE_DOWNLOADS_S3_REGION' ) ? HIZZLE_DOWNLOADS_S3_REGION : 'us-east-1';

		// Validate credentials.
		if ( empty( $access_key ) || empty( $secret_key ) || empty( $bucket ) ) {
			return hizzle_downloads()->logger->error(
				'S3 credentials are not configured.',
				'hizzle-downloads'
			);
		}

		// Read the file.
		$file_content = file_get_contents( $file_path );
		if ( false === $file_content ) {
			return hizzle_downloads()->logger->error(
				'Failed to read file: ' . $file_path,
				'hizzle-downloads'
			);
		}

		// Get the file mime type.
		$file_type = wp_check_filetype( $file_path );
		$mime_type = ! empty( $file_type['type'] ) ? $file_type['type'] : 'application/octet-stream';

		// Prepare AWS Signature Version 4.
		$service    = 's3';
		$endpoint   = trailingslashit( HIZZLE_DOWNLOADS_S3_ENDPOINT ) . ltrim( $s3_key, '/' );
		$host       = parse_url( $endpoint, PHP_URL_HOST );
		$timestamp  = gmdate( 'Ymd\THis\Z' );
		$date_stamp = gmdate( 'Ymd' );

		// Create canonical request.
		$method          = 'PUT';
		$canonical_uri   = '/' . ltrim( $s3_key, '/' );
		$canonical_query = '';
		$payload_hash    = hash( 'sha256', $file_content );

		$canonical_headers  = "content-type:$mime_type\n";
		$canonical_headers .= "host:$host\n";
		$canonical_headers .= "x-amz-content-sha256:$payload_hash\n";
		$canonical_headers .= "x-amz-date:$timestamp\n";

		$signed_headers = 'content-type;host;x-amz-content-sha256;x-amz-date';

		$canonical_request = "$method\n$canonical_uri\n$canonical_query\n$canonical_headers\n$signed_headers\n$payload_hash";

		// Create string to sign.
		$algorithm        = 'AWS4-HMAC-SHA256';
		$credential_scope = "$date_stamp/$region/$service/aws4_request";
		$string_to_sign   = "$algorithm\n$timestamp\n$credential_scope\n" . hash( 'sha256', $canonical_request );

		// Calculate signature.
		$k_date    = hash_hmac( 'sha256', $date_stamp, 'AWS4' . $secret_key, true );
		$k_region  = hash_hmac( 'sha256', $region, $k_date, true );
		$k_service = hash_hmac( 'sha256', $service, $k_region, true );
		$k_signing = hash_hmac( 'sha256', 'aws4_request', $k_service, true );
		$signature = hash_hmac( 'sha256', $string_to_sign, $k_signing );

		// Create authorization header.
		$authorization = "$algorithm Credential=$access_key/$credential_scope, SignedHeaders=$signed_headers, Signature=$signature";

		// Make the request.
		$response = wp_remote_request(
			$endpoint,
			array(
				'method'  => 'PUT',
				'headers' => array(
					'Content-Type'         => $mime_type,
					'Content-Disposition'  => 'attachment; filename="' . sanitize_file_name( $downloaded_file_name ) . '"',
					'Content-Language'     => get_bloginfo( 'language' ),
					'Authorization'        => $authorization,
					'x-amz-content-sha256' => $payload_hash,
					'x-amz-date'           => $timestamp,
				),
				'body'    => $file_content,
				'timeout' => 60,
				//'blocking' => false,
			)
		);

		echo '<pre>';
		var_dump( array( wp_remote_retrieve_body( $response ), $authorization, $timestamp ) );
		exit;
	}

	/**
	 * Generates the signing key for AWS Signature Version 4.
	 *
	 * @param string $secret_key The secret access key.
	 * @param string $date_stamp The date stamp (YYYYMMDD).
	 * @param string $region     The AWS region.
	 * @param string $service    The AWS service name.
	 * @return string The signing key.
	 */
	private static function get_signature_key( $secret_key, $date_stamp, $region, $service ) {
		$k_date    = hash_hmac( 'sha256', $date_stamp, 'AWS4' . $secret_key, true );
		$k_region  = hash_hmac( 'sha256', $region, $k_date, true );
		$k_service = hash_hmac( 'sha256', $service, $k_region, true );
		$k_signing = hash_hmac( 'sha256', 'aws4_request', $k_service, true );
		return $k_signing;
	}
}
