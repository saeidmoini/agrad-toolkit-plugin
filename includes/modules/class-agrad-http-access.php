<?php
/**
 * HTTP access controls for external requests.
 *
 * @package AgradToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agrad_Module_Http_Access {

	/**
	 * Allowed hosts.
	 *
	 * @var array
	 */
	protected static $allowed = array();

	/**
	 * Blocked hosts.
	 *
	 * @var array
	 */
	protected static $blocked = array();

	/**
	 * Setup filters.
	 *
	 * @param array $settings Settings.
	 */
	public static function init( $settings ) {
		self::$allowed = array_map( 'strtolower', (array) $settings['http_allowed_hosts'] );
		self::$blocked = array_map( 'strtolower', (array) $settings['http_blocked_hosts'] );

		add_filter( 'http_request_host_is_external', array( __CLASS__, 'filter_access' ), 10, 2 );
		add_filter( 'pre_http_request', array( __CLASS__, 'block_requests' ), 10, 3 );
	}

	/**
	 * Treat whitelisted hosts as internal so WP_HTTP_BLOCK_EXTERNAL does not reject them.
	 *
	 * @param bool   $is_external Flag.
	 * @param string $host Host name.
	 *
	 * @return bool
	 */
	public static function filter_access( $is_external, $host ) {
		$host = strtolower( $host );

		if ( in_array( $host, self::$blocked, true ) ) {
			return true;
		}

		if ( in_array( $host, self::$allowed, true ) ) {
			return false;
		}

		return $is_external;
	}

	/**
	 * Abort HTTP requests to explicitly blocked hosts.
	 */
	public static function block_requests( $preempt, $args, $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		$host = strtolower( (string) $host );

		if ( $host && in_array( $host, self::$blocked, true ) ) {
			return new WP_Error(
				'agrad_http_blocked',
				sprintf(
					/* translators: %s host name */
					__( 'Requests to %s are blocked by Agrad Toolkit.', 'agrad-toolkit' ),
					$host
				)
			);
		}

		return $preempt;
	}
}
