<?php

namespace Hizzle\Downloads;

/**
 * Syncs files to S3-compatible storage.
 *
 * This class automatically uploads files from the hizzle_uploads directory to S3-compatible
 * storage services like Amazon S3 and Cloudflare R2.
 *
 * Note: This implementation uses AWS Signature Version 2 for compatibility with various
 * S3-compatible services. For very large files, consider implementing streaming uploads
 * to avoid memory issues.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Syncs files to S3-compatible storage.
 */
class S3_Syncer {

	/**
	 * Loads the class.
	 */
	public function __construct() {

		// Listen to download created and updated hooks.
		add_action( 'hizzle_download_download_created', array( $this, 'sync_download' ), 10, 1 );
		add_action( 'hizzle_download_download_updated', array( $this, 'sync_download' ), 10, 2 );
	}

	/**
	 * Syncs a download to S3.
	 *
	 * @param Download $download The download object.
	 * @param array    $changes  The changes made to the download (only for updated hook).
	 */
	public function sync_download( $download, $changes = array() ) {

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
		$base_dir   = trailingslashit( $upload_dir['basedir'] ) . 'hizzle_uploads/';

		// Check if the file is in the hizzle_uploads directory.
		if ( 0 !== strpos( $file_path, $base_dir ) ) {
			return;
		}

		// Remove the base directory from the file path.
		$new_file_name = str_replace( $base_dir, '', $file_path );

		// Get the hostname.
		$host_name = parse_url( home_url(), PHP_URL_HOST );

		// Validate hostname.
		if ( empty( $host_name ) ) {
			hizzle_downloads()->logger->error( 'Failed to parse hostname from home_url()', 'hizzle_downloads' );
			return;
		}

		// Prepare the S3 key.
		$s3_key = $host_name . '/' . $new_file_name;

		// Upload to S3.
		$this->upload_to_s3( $file_path, $s3_key );
	}

	/**
	 * Uploads a file to S3.
	 *
	 * @param string $file_path The local file path.
	 * @param string $s3_key    The S3 key (path).
	 */
	protected function upload_to_s3( $file_path, $s3_key ) {

		// Get S3 credentials from constants.
		$access_key = defined( 'HIZZLE_DOWNLOADS_S3_ACCESS_KEY' ) ? HIZZLE_DOWNLOADS_S3_ACCESS_KEY : '';
		$secret_key = defined( 'HIZZLE_DOWNLOADS_S3_SECRET_KEY' ) ? HIZZLE_DOWNLOADS_S3_SECRET_KEY : '';
		$bucket     = defined( 'HIZZLE_DOWNLOADS_S3_BUCKET' ) ? HIZZLE_DOWNLOADS_S3_BUCKET : '';
		$region     = defined( 'HIZZLE_DOWNLOADS_S3_REGION' ) ? HIZZLE_DOWNLOADS_S3_REGION : 'us-east-1';
		$endpoint   = defined( 'HIZZLE_DOWNLOADS_S3_ENDPOINT' ) ? HIZZLE_DOWNLOADS_S3_ENDPOINT : '';

		// Validate credentials.
		if ( empty( $access_key ) || empty( $secret_key ) || empty( $bucket ) ) {
			hizzle_downloads()->logger->error( 'S3 credentials are not configured.', 'hizzle_downloads' );
			return false;
		}

		// Read the file.
		$file_content = file_get_contents( $file_path );
		if ( false === $file_content ) {
			hizzle_downloads()->logger->error( 'Failed to read file: ' . $file_path, 'hizzle_downloads' );
			return false;
		}

		// Get the file mime type.
		$file_type = wp_check_filetype( $file_path );
		$mime_type = ! empty( $file_type['type'] ) ? $file_type['type'] : 'application/octet-stream';

		// Determine the S3 endpoint.
		if ( empty( $endpoint ) ) {
			$endpoint = "https://{$bucket}.s3.{$region}.amazonaws.com";
		}

		// Prepare the URL.
		$url = trailingslashit( $endpoint ) . ltrim( $s3_key, '/' );

		// Prepare the request.
		$date      = gmdate( 'D, d M Y H:i:s T' );
		$string_to_sign = "PUT\n\n{$mime_type}\n{$date}\n/{$bucket}/" . ltrim( $s3_key, '/' );
		$signature = base64_encode( hash_hmac( 'sha1', $string_to_sign, $secret_key, true ) );

		// Make the request.
		$response = wp_remote_request(
			$url,
			array(
				'method'  => 'PUT',
				'headers' => array(
					'Date'          => $date,
					'Content-Type'  => $mime_type,
					'Authorization' => "AWS {$access_key}:{$signature}",
				),
				'body'    => $file_content,
				'timeout' => 60,
			)
		);

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			hizzle_downloads()->logger->error( 'S3 upload failed: ' . $response->get_error_message(), 'hizzle_downloads' );
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code < 200 || $response_code >= 300 ) {
			$body = wp_remote_retrieve_body( $response );
			hizzle_downloads()->logger->error( "S3 upload failed with status {$response_code}: {$body}", 'hizzle_downloads' );
			return false;
		}

		// Log success.
		hizzle_downloads()->logger->info( "Successfully uploaded {$s3_key} to S3.", 'hizzle_downloads' );
		return true;
	}
}
