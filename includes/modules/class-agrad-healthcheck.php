<?php
/**
 * Health-check REST endpoint.
 *
 * @package AgradToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agrad_Module_Healthcheck {

	const API_KEY = '1}~Im%O)[PO?v1dRTl$]Xj0wbG(K[:Jsi/cc{m{5(y13gYP)';

	/**
	 * Register endpoint.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_route' ) );
	}

	/**
	 * Register /wp-health-check/v1/status.
	 */
	public static function register_route() {
		register_rest_route(
			'wp-health-check/v1',
			'/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'status' ),
				'permission_callback' => array( __CLASS__, 'permission_check' ),
			)
		);
	}

	/**
	 * Validate the shared API key.
	 */
	public static function permission_check( WP_REST_Request $request ) {
		$key = $request->get_param( 'api_key' );
		if ( ! hash_equals( self::API_KEY, (string) $key ) ) {
			return new WP_Error( 'rest_forbidden', __( 'Invalid API key.', 'agrad-toolkit' ), array( 'status' => 401 ) );
		}
		return true;
	}

	/**
	 * Provide runtime status.
	 */
	public static function status() {
		$response = array(
			'status'  => 'ok',
			'message' => __( 'WordPress site is responsive.', 'agrad-toolkit' ),
		);

		$last_error = error_get_last();
		if ( $last_error && in_array( $last_error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ), true ) ) {
			$response['status']        = 'error';
			$response['message']       = sprintf(
				'Fatal PHP error: %1$s in %2$s on line %3$d',
				$last_error['message'],
				$last_error['file'],
				$last_error['line']
			);
			$response['error_details'] = $last_error;
		}

		return new WP_REST_Response( $response, 200 );
	}
}
