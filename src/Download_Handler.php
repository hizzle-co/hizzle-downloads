<?php

namespace Hizzle\Downloads;

/**
 * Handles the actual download.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Download handler class.
 */
class Download_Handler {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		if ( isset( $_GET['hizzle_download_file'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			add_action( 'wp_loaded', array( $this, 'download_file' ) );
		}

		add_action( 'hizzle_download_file_redirect', array( $this, 'download_file_redirect' ) );
		add_action( 'hizzle_download_file_xsendfile', array( $this, 'download_file_xsendfile' ) );
		add_action( 'hizzle_download_file_force', array( $this, 'download_file_force' ) );
	}

	/**
	 * Downloads a file.
	 */
	public function download_file() {
		$file_id = rawurldecode( $_GET['hizzle_download_file'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Either retrieve the file via an ID or a name.
		if ( is_numeric( $file_id ) ) {
			$file = hizzle_get_download( absint( $file_id ) );
		} else {
			$file = hizzle_get_download_by_file_name( sanitize_text_field( $file_id ) );
		}

		// Abort if the file doesn't exist.
		if ( is_wp_error( $file ) ) {
			return $this->download_error( $file );
		}

		// Abort if the file is not downloadable.
		if ( ! $file->is_downloadable() ) {
			return $this->download_error( new \WP_Error( 'hizzle_downloads_file_not_downloadable', __( 'This file is not downloadable.', 'hizzle-downloads' ) ) );
		}

		// Check if the current user can download the file.
		if ( ! $file->current_user_can_download() ) {
			return $this->download_error( new \WP_Error( 'hizzle_downloads_user_cannot_download', __( 'You do not have permission to download this file.', 'hizzle-downloads' ) ) );
		}

		// Maybe request a password.
		if ( $file->is_password_protected() ) {
			$this->maybe_request_password( $file );
		}

		// Download the file.
		$parsed_file_path = $file->parse_file_path();

		try {
			$download_range   = self::get_download_range( @filesize( $parsed_file_path['file_path'] ) );  // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

			// Track the download.
			if ( ! $download_range['is_range_request'] ) {
				$current_user_id = get_current_user_id();
				$current_user_id = apply_filters( 'hizzle_downloads_current_user_id', $current_user_id );
				$ip_address      = hizzle_downloads_get_ip_address();

				$file->track_download( $current_user_id > 0 ? $current_user_id : null, ! empty( $ip_address ) ? $ip_address : null );
			}

			// Handle the download.
			do_action( 'hizzle_download_file_' . hizzle_download_method(), $file );
		} catch ( \Exception $e ) {
			$this->log( $e->getMessage(), 'error', $parsed_file_path );
			$this->download_error( new \WP_Error( 'hizzle_downloads_error', $e->getMessage() ) );
		}

		exit;
	}

	/**
	 * Requests a password if the file is password protected.
	 *
	 * @param \Hizzle\Downloads\Download $file The file object.
	 */
	protected function maybe_request_password( $file ) {

		// Show a password input form.
		if ( ! isset( $_POST['hizzle_downloads_file_password'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			require plugin_dir_path( __FILE__ ) . 'html-password-form.php';
			exit;
		}

		// Check the password.
		if ( ! hash_equals( wp_unslash( $_POST['hizzle_downloads_file_password'] ), $file->get_password() ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$this->download_error( new \WP_Error( 'hizzle_downloads_password_incorrect', __( 'The password you entered is incorrect. Please try again.', 'hizzle-downloads' ) ), $file );
		}

	}

	/**
	 * Redirect to a file to start the download.
	 *
	 * @param Download $file The file to download.
	 */
	public function download_file_redirect( $file ) {
		header( 'Location: ' . $file->get_file_url() );
		exit;
	}

	/**
	 * Download a file using X-Sendfile, X-Lighttpd-Sendfile, or X-Accel-Redirect if available.
	 *
	 * @param Download $file The file to download.
	 */
	public function download_file_xsendfile( $file ) {
		$parsed_file_path = $file->parse_file_path();

		/**
		 * Fallback on force download method for remote files. This is because:
		 * 1. xsendfile needs proxy configuration to work for remote files, which cannot be assumed to be available on most hosts.
		 * 2. Force download method is more secure than redirect method if `allow_url_fopen` is enabled in `php.ini`.
		 */
		if ( $parsed_file_path['remote_file'] && ! apply_filters( 'hizzle_use_xsendfile_for_remote_download', false ) ) {
			do_action( 'hizzle_download_file_force', $file );
			return;
		}

		if ( function_exists( 'apache_get_modules' ) && in_array( 'mod_xsendfile', apache_get_modules(), true ) ) {
			self::download_headers( $parsed_file_path['file_path'], $file->get_downloaded_file_name() );
			$filepath = apply_filters( 'hizzle_download_file_xsendfile_file_path', $parsed_file_path['file_path'], $file, $parsed_file_path );
			header( 'X-Sendfile: ' . $filepath );
			exit;
		} elseif ( stristr( getenv( 'SERVER_SOFTWARE' ), 'lighttpd' ) ) {
			self::download_headers( $parsed_file_path['file_path'], $file->get_downloaded_file_name() );
			$filepath = apply_filters( 'hizzle_download_file_xsendfile_lighttpd_file_path', $parsed_file_path['file_path'], $file, $parsed_file_path );
			header( 'X-Lighttpd-Sendfile: ' . $filepath );
			exit;
		} elseif ( stristr( getenv( 'SERVER_SOFTWARE' ), 'nginx' ) || stristr( getenv( 'SERVER_SOFTWARE' ), 'cherokee' ) ) {
			self::download_headers( $parsed_file_path['file_path'], $file->get_downloaded_file_name() );
			$xsendfile_path = trim( preg_replace( '`^' . str_replace( '\\', '/', getcwd() ) . '`', '', $parsed_file_path['file_path'] ), '/' );
			$xsendfile_path = apply_filters( 'hizzle_download_file_xsendfile_x_accel_redirect_file_path', $xsendfile_path, $file, $parsed_file_path );
			header( "X-Accel-Redirect: /$xsendfile_path" );
			exit;
		}

		// Notify the site admin.
		$this->log(
			sprintf(
				/* translators: %1$s contains the filepath of the digital asset. */
				__( '%1$s could not be served using the X-Accel-Redirect/X-Sendfile method. A Force Download will be used instead.', 'hizzle-downloads' ),
				$file->get_file_name()
			),
			'warning',
			$parsed_file_path
		);

		// Do not use X-sendfile for future downloads.
		update_option( 'hizzle_downloads_xsendfile_missing', 1 );

		// Force download.
		do_action( 'hizzle_download_file_force', $file );
	}

	/**
	 * Force download - this is the default method.
	 *
	 * @param Download $file The file to download.
	 */
	public function download_file_force( $file ) {
		$parsed_file_path = $file->parse_file_path();
		$download_range   = self::get_download_range( @filesize( $parsed_file_path['file_path'] ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		self::download_headers( $parsed_file_path['file_path'], $file->get_downloaded_file_name(), $download_range );

		$start  = isset( $download_range['start'] ) ? $download_range['start'] : 0;
		$length = isset( $download_range['length'] ) ? $download_range['length'] : 0;
		if ( ! self::readfile_chunked( $parsed_file_path['file_path'], $start, $length ) ) {
			if ( $parsed_file_path['remote_file'] && apply_filters( 'hizzle_downloads_redirect_fallback_allowed', true, $file ) ) {
				$this->log(
					sprintf(
						/* translators: %1$s contains the filepath of the digital asset. */
						__( '%1$s could not be served using the Force Download method. A redirect will be used instead.', 'hizzle-downloads' ),
						$file->get_file_name()
					),
					'warning',
					$parsed_file_path
				);
				do_action( 'hizzle_download_file_redirect', $file );
			} else {
				self::download_error( __( 'File not found', 'hizzle-downloads' ) );
			}
		}

		exit;
	}

	/**
	 * Parse the HTTP_RANGE request from iOS devices.
	 * Does not support multi-range requests.
	 *
	 * @param int $file_size Size of file in bytes.
	 * @return array {
	 *     Information about range download request: beginning and length of
	 *     file chunk, whether the range is valid/supported and whether the request is a range request.
	 *
	 *     @type int  $start            Byte offset of the beginning of the range. Default 0.
	 *     @type int  $length           Length of the requested file chunk in bytes. Optional.
	 *     @type bool $is_range_valid   Whether the requested range is a valid and supported range.
	 *     @type bool $is_range_request Whether the request is a range request.
	 * }
	 */
	protected function get_download_range( $file_size ) {
		$start          = 0;
		$download_range = array(
			'start'            => $start,
			'is_range_valid'   => false,
			'is_range_request' => false,
		);

		if ( ! $file_size ) {
			return $download_range;
		}

		$end                      = $file_size - 1;
		$download_range['length'] = $file_size;

		if ( isset( $_SERVER['HTTP_RANGE'] ) ) { // @codingStandardsIgnoreLine.
			$http_range                         = sanitize_text_field( wp_unslash( $_SERVER['HTTP_RANGE'] ) ); // WPCS: input var ok.
			$download_range['is_range_request'] = true;

			$c_start = $start;
			$c_end   = $end;
			// Extract the range string.
			list( , $range ) = explode( '=', $http_range, 2 );
			// Make sure the client hasn't sent us a multibyte range.
			if ( strpos( $range, ',' ) !== false ) {
				return $download_range;
			}

			/*
			 * If the range starts with an '-' we start from the beginning.
			 * If not, we forward the file pointer
			 * and make sure to get the end byte if specified.
			 */
			if ( '-' === $range[0] ) {
				// The n-number of the last bytes is requested.
				$c_start = $file_size - substr( $range, 1 );
			} else {
				$range   = explode( '-', $range );
				$c_start = ( isset( $range[0] ) && is_numeric( $range[0] ) ) ? (int) $range[0] : 0;
				$c_end   = ( isset( $range[1] ) && is_numeric( $range[1] ) ) ? (int) $range[1] : $file_size;
			}

			/*
			 * Check the range and make sure it's treated according to the specs: http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html.
			 * End bytes can not be larger than $end.
			 */
			$c_end = ( $c_end > $end ) ? $end : $c_end;
			// Validate the requested range and return an error if it's not correct.
			if ( $c_start > $c_end || $c_start > $file_size - 1 || $c_end >= $file_size ) {
				return $download_range;
			}
			$start  = $c_start;
			$end    = $c_end;
			$length = $end - $start + 1;

			$download_range['start']          = $start;
			$download_range['length']         = $length;
			$download_range['is_range_valid'] = true;
		}
		return $download_range;
	}

	/**
	 * Set headers for the download.
	 *
	 * @param string $file_path      File path.
	 * @param string $filename       File name.
	 * @param array  $download_range Array containing info about range download request (see {@see get_download_range} for structure).
	 */
	private function download_headers( $file_path, $filename, $download_range = array() ) {
		$this->check_server_config();
		$this->clean_buffers();
		nocache_headers();

		header( 'X-Robots-Tag: noindex, nofollow', true );
		header( 'Content-Type: ' . $this->get_download_content_type( $file_path ) );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
		header( 'Content-Transfer-Encoding: binary' );

		$file_size = @filesize( $file_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! $file_size ) {
			return;
		}

		if ( isset( $download_range['is_range_request'] ) && true === $download_range['is_range_request'] ) {
			if ( false === $download_range['is_range_valid'] ) {
				header( 'HTTP/1.1 416 Requested Range Not Satisfiable' );
				header( 'Content-Range: bytes 0-' . ( $file_size - 1 ) . '/' . $file_size );
				exit;
			}

			$start  = $download_range['start'];
			$end    = $download_range['start'] + $download_range['length'] - 1;
			$length = $download_range['length'];

			header( 'HTTP/1.1 206 Partial Content' );
			header( "Accept-Ranges: 0-$file_size" );
			header( "Content-Range: bytes $start-$end/$file_size" );
			header( "Content-Length: $length" );
		} else {
			header( 'Content-Length: ' . $file_size );
		}
	}

	/**
	 * Check and set certain server config variables to ensure downloads work as intended.
	 */
	private function check_server_config() {
		$this->set_time_limit( 0 );
		if ( function_exists( 'apache_setenv' ) ) {
			@apache_setenv( 'no-gzip', 1 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_apache_setenv
		}
		@ini_set( 'zlib.output_compression', 'Off' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.IniSet.Risky
		@session_write_close(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.VIP.SessionFunctionsUsage.session_session_write_close
	}

	/**
	 * Clean all output buffers.
	 *
	 * Can prevent errors, for example: transfer closed with 3 bytes remaining to read.
	 */
	private function clean_buffers() {
		if ( ob_get_level() ) {
			$levels = ob_get_level();
			for ( $i = 0; $i < $levels; $i++ ) {
				@ob_end_clean(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}
		} else {
			@ob_end_clean(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
	}

	/**
	 * Wrapper for set_time_limit to see if it is enabled.
	 *
	 * @since 1.0.0
	 * @param int $limit Time limit.
	 */
	private function set_time_limit( $limit = 0 ) {
		if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) { // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
			@set_time_limit( $limit ); // @codingStandardsIgnoreLine
		}
	}

	/**
	 * Get content type of a download.
	 *
	 * @param  string $file_path File path.
	 * @return string
	 */
	private function get_download_content_type( $file_path ) {
		$file_extension = strtolower( substr( strrchr( $file_path, '.' ), 1 ) );
		$ctype          = 'application/force-download';

		foreach ( get_allowed_mime_types() as $mime => $type ) {
			$mimes = explode( '|', $mime );
			if ( in_array( $file_extension, $mimes, true ) ) {
				$ctype = $type;
				break;
			}
		}

		return $ctype;
	}

	/**
	 * Read file chunked.
	 *
	 * Reads file in chunks so big downloads are possible without changing PHP.INI - http://codeigniter.com/wiki/Download_helper_for_large_files/.
	 *
	 * @param  string $file   File.
	 * @param  int    $start  Byte offset/position of the beginning from which to read from the file.
	 * @param  int    $length Length of the chunk to be read from the file in bytes, 0 means full file.
	 * @return bool Success or fail
	 */
	public static function readfile_chunked( $file, $start = 0, $length = 0 ) {
		if ( ! defined( 'HIZZLE_DOWNLOADS_CHUNK_SIZE' ) ) {
			define( 'HIZZLE_DOWNLOADS_CHUNK_SIZE', MB_IN_BYTES );
		}

		$handle = @fopen( $file, 'r' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fopen

		if ( false === $handle ) {
			return false;
		}

		if ( ! $length ) {
			$length = @filesize( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		$read_length = (int) HIZZLE_DOWNLOADS_CHUNK_SIZE;

		if ( $length ) {
			$end = $start + $length - 1;

			@fseek( $handle, $start ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			$p = @ftell( $handle ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

			while ( ! @feof( $handle ) && $p <= $end ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				// Don't run past the end of file.
				if ( $p + $read_length > $end ) {
					$read_length = $end - $p + 1;
				}

				echo @fread( $handle, $read_length ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.XSS.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions.file_system_read_fread
				$p = @ftell( $handle ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

				if ( ob_get_length() ) {
					ob_flush();
					flush();
				}
			}
		} else {
			while ( ! @feof( $handle ) ) { // @codingStandardsIgnoreLine.
				echo @fread( $handle, $read_length ); // @codingStandardsIgnoreLine.
				if ( ob_get_length() ) {
					ob_flush();
					flush();
				}
			}
		}

		return @fclose( $handle ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fclose
	}

	/**
	 * Die with an error message if the download fails.
	 *
	 * @param \WP_Error $error The error to display.
	 */
	private function download_error( $error ) {

		/*
		 * Since we will now render a message instead of serving a download, we should unwind some of the previously set
		 * headers.
		 */
		if ( headers_sent() ) {
			$this->log( __( 'Headers already sent when generating download error message.', 'hizzle-downloads' ), 'warning', $error->get_error_data() );
		} else {
			header( 'Content-Type: ' . get_option( 'html_type' ) . '; charset=' . get_option( 'blog_charset' ) );
			header_remove( 'Content-Description;' );
			header_remove( 'Content-Disposition' );
			header_remove( 'Content-Transfer-Encoding' );
		}

		wp_die( $error, 400 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Logs an error message.
	 *
	 * @param string $message The error message to log.
	 * @param string $type    The type of error.
	 * @param array  $data    Optional. Data to log in the error message.
	 */
	private function log( $message, $type = 'error', $data = array() ) {
		$context = array_merge(
			array( 'source' => 'hizzle_download_file' ),
			$data
		);

		hizzle_downloads()->logger->log( $type, $message, $context );
	}

}
