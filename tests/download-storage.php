<?php
/**
 * Standalone storage regressions: php tests/download-storage.php
 * Exercises production methods with isolated WordPress/record dependencies.
 */

namespace Hizzle\Store {

	class Record {}
}

namespace {

	define( 'ABSPATH', __DIR__ . '/' );
	$method = 'force';
	$download = null;

	function apply_filters( $hook, $value ) { return $value; }
	function hizzle_download_method() { return $GLOBALS['method']; }
	function trailingslashit( $path ) { return rtrim( $path, '/\\' ) . '/'; }
	function wp_mkdir_p( $path ) { return is_dir( $path ) || mkdir( $path, 0777, true ); }
	function wp_generate_password( $length, $special, $extra ) { return substr( bin2hex( random_bytes( $length ) ), 0, $length ); }
	function wp_check_filetype( $name ) { return array( 'ext' => 'zip' === strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ? 'zip' : false ); }
	function is_wp_error( $value ) { return false; }
	function wp_remote_retrieve_body( $response ) { return $response; }
	function get_bloginfo( $name ) { return 'Test site'; }
	function home_url() { return 'https://example.test'; }
	function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
	function wp_parse_list( $value ) { return explode( ',', $value ); }
	function rest_ensure_response( $value ) { return $value; }
	function hizzle_get_download( $id ) { return $GLOBALS['download']; }
	function hizzle_get_download_by_file_name( $name ) { return $GLOBALS['download']; }
	function hizzle_get_download_by_git_url( $url ) { return $GLOBALS['download']; }
	function absint( $value ) { return abs( (int) $value ); }

	require __DIR__ . '/../src/Installer.php';
	require __DIR__ . '/../src/GitHub_Updater.php';
	require __DIR__ . '/../src/Download.php';
	require __DIR__ . '/../src/REST_Versions.php';

	class Storage_Test_Updater extends \Hizzle\Downloads\GitHub_Updater {
		public $body = 'release contents';
		public function __construct() {}
		public function get( $url, $args = array() ) { return $this->body; }
		public function asset( $asset, $dir ) { return $this->process_release_asset( $asset, 'https://example.test/uploads/hizzle_uploads/owner', $dir ); }
		public function zipball( $release, $dir ) { return $this->download_zipball( $release, 'https://example.test/uploads/hizzle_uploads/owner', $dir ); }
	}

	class Storage_Test_Download extends \Hizzle\Downloads\Download {
		public $allowed = false;
		public function exists() { return true; }
		public function get_id() { return 7; }
		public function get_file_name( $context = 'view' ) { return 'plugin'; }
		public function get_version() { return '1.2.3'; }
		public function get_meta( $key, $default = '' ) { return $default; }
		public function has_conditional_logic() { return true; }
		public function current_user_can_download() { return $this->allowed; }
	}

	class Storage_Test_Versions extends \Hizzle\Downloads\REST_Versions {
		public function __construct() {}
	}

	function check( $condition, $message ) {
		if ( ! $condition ) {
			throw new \RuntimeException( $message );
		}
	}

	$dir = sys_get_temp_dir() . '/hizzle-storage-test-' . bin2hex( random_bytes( 8 ) );
	mkdir( $dir );

	try {
		\Hizzle\Downloads\Installer::prepare_upload_dir( $dir );
		check( 'deny from all' === file_get_contents( $dir . '/.htaccess' ), 'Apache access denied' );
		$xml = simplexml_load_file( $dir . '/web.config' );
		check( 'Deny' === (string) $xml->{'system.webServer'}->security->authorization->add['accessType'], 'IIS access denied' );
		check( '*' === (string) $xml->{'system.webServer'}->security->authorization->add['users'], 'IIS denies every user' );
		file_put_contents( $dir . '/.htaccess', 'custom Apache config' );
		file_put_contents( $dir . '/web.config', 'custom IIS config' );
		\Hizzle\Downloads\Installer::prepare_upload_dir( $dir );
		check( 'custom Apache config' === file_get_contents( $dir . '/.htaccess' ), 'Preserve existing Apache config' );
		check( 'custom IIS config' === file_get_contents( $dir . '/web.config' ), 'Preserve existing IIS config' );
		unlink( $dir . '/.htaccess' );
		unlink( $dir . '/web.config' );
		$method = 'redirect';
		\Hizzle\Downloads\Installer::prepare_upload_dir( $dir );
		check( 'Options -Indexes' === file_get_contents( $dir . '/.htaccess' ), 'Redirect mode remains compatible' );
		check( ! file_exists( $dir . '/web.config' ), 'Redirect mode does not create an IIS deny rule' );

		$updater = new Storage_Test_Updater();
		$asset = (object) array( 'name' => 'Plugin.ZIP', 'url' => 'https://api.github.test/asset', 'browser_download_url' => 'https://github.test/plugin.zip' );
		$release = array( 'repo' => array( 'full_name' => 'owner/plugin', 'name' => 'plugin' ), 'tag_name' => 'v1.2.3' );
		$seen = array();
		foreach ( array( $updater->asset( $asset, $dir ), $updater->asset( $asset, $dir ), $updater->zipball( $release, $dir ), $updater->zipball( $release, $dir ) ) as $result ) {
			$name = basename( $result['file_path'] );
			check( 1 === preg_match( '/^[a-zA-Z0-9]{32}\.zip$/', $name ), 'Unpredictable ZIP storage name' );
			check( ! isset( $seen[$name] ), 'Repeated releases have distinct paths' );
			check( $name === basename( $result['file_url'] ), 'URL and disk path match' );
			check( $updater->body === file_get_contents( $result['file_path'] ), 'Release data remains intact' );
			check( $result['is_zip'], 'ZIP handling remains enabled' );
			$seen[$name] = true;
		}
		check( ! file_exists( $dir . '/plugin.zip' ), 'Predictable repository path is not written' );

		$download = new Storage_Test_Download();
		$versions = new Storage_Test_Versions();
		foreach ( array( false, true ) as $allowed ) {
			$download->allowed = $allowed;
			$results = $versions->get_versions( array( 'downloads' => '7,owner/plugin,plugin' ) );
			foreach ( $results as $info ) {
				check( ! array_key_exists( 'path', $info['metadata'] ), 'Version info never discloses storage paths' );
				check( 7 === $info['metadata']['id'] && '1.2.3' === $info['version'], 'Version metadata remains available' );
				check( $allowed ? false !== strpos( $info['download_link'], 'hizzle_download_file=7' ) : '' === $info['download_link'], 'Version link respects permissions' );
			}
		}

		echo "Storage regressions passed.\n";
	} finally {
		// Only files created inside this randomly named test directory are removed.
		foreach ( array_merge( glob( $dir . '/*' ), glob( $dir . '/.htaccess' ) ) as $file ) {
			unlink( $file );
		}
		rmdir( $dir );
	}
}
