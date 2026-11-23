<?php
/** Standalone regressions: php tests/client-ip.php. No live site is modified. */
namespace Hizzle\Store {
	class Record {}
}
namespace {
	define( 'ABSPATH', __DIR__ . '/' );
	$trusted = array();
	$header = 'HTTP_X_FORWARDED_FOR';
	$filters = array();
	function add_filter( $name, $callback ) { $GLOBALS['filters'][$name][] = $callback; }
	function apply_filters( $name, $value, ...$args ) {
		if ( 'hizzle_downloads_trusted_proxies' === $name ) return $GLOBALS['trusted'];
		if ( 'hizzle_downloads_forwarded_ip_header' === $name ) return $GLOBALS['header'];
		foreach ( isset( $GLOBALS['filters'][$name] ) ? $GLOBALS['filters'][$name] : array() as $callback ) {
			$value = call_user_func_array( $callback, array_merge( array( $value ), $args ) );
		}
		return $value;
	}
	function rest_is_ip_address( $value ) { return filter_var( $value, FILTER_VALIDATE_IP ) ? $value : false; }
	require __DIR__ . '/../src/functions.php';
	require __DIR__ . '/../src/Download.php';

	/** Uses the production permission evaluator with an IP-only record fixture. */
	class IP_Test_Download extends \Hizzle\Downloads\Download {
		public $action = 'allow';
		public $conditional = true;
		public $legacy_password = '';
		public function get_meta( $key, $default = '' ) { return '_password' === $key ? $this->legacy_password : $default; }
		public function is_downloadable() { return true; }
		public function get_conditional_logic() {
			return array( 'enabled' => $this->conditional, 'action' => $this->action, 'type' => 'all', 'rules' => array(
				array( 'type' => 'ip_address', 'condition' => 'is', 'value' => '203.0.113.7' ),
			) );
		}
	}
	function check( $condition, $message ) {
		if ( ! $condition ) throw new \RuntimeException( $message );
	}
	$download = new IP_Test_Download();
	$_SERVER = array( 'REMOTE_ADDR' => '198.51.100.25', 'HTTP_X_REAL_IP' => '203.0.113.7', 'HTTP_X_FORWARDED_FOR' => '203.0.113.7' );
	check( '198.51.100.25' === hizzle_downloads_get_access_ip_address(), 'Untrusted forwarding headers are ignored' );
	check( ! $download->current_user_can_download(), 'Spoofing the allowlisted IP does not authorize a download' );
	check( '203.0.113.7' === hizzle_downloads_get_ip_address(), 'Logging retains forwarded addresses without granting access' );
	$_SERVER['HTTP_CF_CONNECTING_IP'] = '2001:db8::8';
	check( '2001:db8::8' === hizzle_downloads_get_ip_address(), 'CDN visitor IP remains visible in logs without proxy setup' );
	check( ! $download->current_user_can_download(), 'Telemetry headers cannot override the access resolver' );
	unset( $_SERVER['HTTP_CF_CONNECTING_IP'] );
	$_SERVER['HTTP_X_REAL_IP'] = 'invalid';
	check( '203.0.113.7' === hizzle_downloads_get_ip_address(), 'Logging rejects invalid headers and tries the next source' );
	unset( $_SERVER['HTTP_X_REAL_IP'] );
	check( ! $download->current_user_can_download(), 'X-Forwarded-For alone cannot bypass the ACL' );
	$_SERVER['REMOTE_ADDR'] = '203.0.113.7';
	$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.25';
	check( $download->current_user_can_download(), 'The real allowlisted client remains authorized' );
	$download->action = 'deny';
	check( ! $download->current_user_can_download(), 'A forbidden client cannot hide behind a forged forwarding header' );
	$download->action = 'allow';

	$trusted = array( '10.0.0.0/24' );
	$_SERVER = array( 'REMOTE_ADDR' => '10.0.0.2', 'HTTP_X_REAL_IP' => '203.0.113.7', 'HTTP_X_FORWARDED_FOR' => '203.0.113.7, 198.51.100.25' );
	check( '198.51.100.25' === hizzle_downloads_get_access_ip_address(), 'Appended chain stops at actual untrusted client' );
	check( ! $download->current_user_can_download(), 'Forged leftmost XFF and unselected X-Real-IP do not grant access' );
	$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.7, 10.0.0.1';
	check( $download->current_user_can_download(), 'Multiple explicitly trusted hops resolve the client' );
	$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.7, 198.51.100.5, 10.0.0.1';
	check( '198.51.100.5' === hizzle_downloads_get_access_ip_address(), 'An untrusted intermediary terminates the walk' );
	$_SERVER['HTTP_X_FORWARDED_FOR'] = 'invalid attacker prefix, 203.0.113.7';
	check( '203.0.113.7' === hizzle_downloads_get_access_ip_address(), 'Untrusted leftmost junk cannot mask the validated client' );
	foreach ( array( '', 'unknown', '203.0.113.7:123', '203.0.113.7,', array( '203.0.113.7' ), str_repeat( 'x', 8193 ), implode( ',', array_fill( 0, 101, '10.0.0.1' ) ) ) as $bad ) {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = $bad;
		check( '' === hizzle_downloads_get_access_ip_address(), 'Invalid/oversized required forwarding data is rejected' );
	}
	unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );
	check( '' === hizzle_downloads_get_access_ip_address(), 'A trusted proxy missing its configured header cannot supply a client IP' );
	$header = 'HTTP_X_REAL_IP';
	check( $download->current_user_can_download(), 'Explicitly configured single-IP header works behind a trusted proxy' );
	$_SERVER['HTTP_X_REAL_IP'] = '203.0.113.7, 198.51.100.25';
	check( '' === hizzle_downloads_get_access_ip_address(), 'A single-IP header cannot carry an unvalidated list' );
	$header = 'HTTP_CF_CONNECTING_IP';
	$_SERVER['HTTP_CF_CONNECTING_IP'] = '2001:db8::7';
	check( '2001:db8::7' === hizzle_downloads_get_access_ip_address(), 'Configured proxy headers accept validated IPv6' );
	$_SERVER['REMOTE_ADDR'] = '198.51.100.25';
	check( '198.51.100.25' === hizzle_downloads_get_access_ip_address(), 'CF header is also ignored on direct untrusted requests' );
	$_SERVER['REMOTE_ADDR'] = 'bad-address';
	check( '' === hizzle_downloads_get_access_ip_address(), 'Invalid socket address is rejected' );
	check( ! hizzle_download_conditional_logic_rule_met_ip_address( false, array( 'value' => '' ) ), 'An unknown address never matches an empty rule' );
	unset( $_SERVER['REMOTE_ADDR'] );
	check( '' === hizzle_downloads_get_access_ip_address(), 'No socket address means no trusted identity' );

	foreach ( array(
		array( '192.0.2.129', array( '192.0.2.128/25' ), true ),
		array( '192.0.2.127', array( '192.0.2.128/25' ), false ),
		array( '2001:db8:7fff::1', array( '2001:db8::/33' ), true ),
		array( '2001:db8:8000::1', array( '2001:db8::/33' ), false ),
		array( '2001:db8::1', array( '2001:0db8:0:0:0:0:0:1' ), true ),
		array( '192.0.2.1', array( '2001:db8::/32', '192.0.2.0/33', '192.0.2.0/no', 'bad', null ), false ),
	) as $case ) {
		check( $case[2] === hizzle_downloads_is_trusted_proxy( $case[0], $case[1] ), 'Exact/CIDR proxy matching respects address families and mask boundaries' );
	}
	$download->conditional = false;
	$download->legacy_password = 'old-secret';
	check( ! $download->current_user_can_download(), 'Removing password support does not expose legacy password-only files' );
	$_POST['hizzle_downloads_file_password'] = 'old-secret';
	check( ! $download->current_user_can_download(), 'Submitting the former password cannot grant access' );
	$download->legacy_password = '';
	check( $download->current_user_can_download(), 'Reviewed files follow their saved conditional rules' );
	check( ! method_exists( $download, 'set_password' ) && ! method_exists( $download, 'get_password' ), 'Password APIs are removed' );
	echo "Client IP and permission regressions passed.\n";
}
