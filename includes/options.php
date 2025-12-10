<?php
/**
 * Option helpers for the Agrad Toolkit plugin.
 *
 * @package AgradToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default settings for the plugin.
 *
 * @return array
 */
function agrad_default_settings() {
	return array(
		'disable_xmlrpc'          => 1,
		'allow_svg'               => 1,
		'disable_rest_api'        => 1,
		'rest_allowed_prefixes'   => array(),
		'remove_wp_version'       => 1,
		'disable_gutenberg'       => 0,
		'disable_updates_cache'   => 1,
		'remove_dashboard_meta'   => 1,
		'custom_font_swap'        => 1,
		'custom_comments'         => 1,
		'portfolio_cpt'           => 0,
		'healthcheck_endpoint'    => 1,
		'custom_admin_path'       => 1,
		'http_access_control'     => 1,
		'http_allowed_hosts'      => array( 'api.crocoblock.com' ),
		'http_blocked_hosts'      => array(),
		'woo_term_transfer'       => 0,
		'woo_text_replacer'       => 0,
		'woo_remove_discounts'    => 0,
		'woo_product_status'      => 0,
		'woo_no_image_report'     => 0,
		'post_type_copy'          => 0,
		'woo_custom_sorting'      => 0,
	);
}

/**
 * Fetch settings merged with defaults.
 *
 * @return array
 */
function agrad_get_settings() {
	$options = get_option( 'agrad_settings', array() );

	if ( ! is_array( $options ) ) {
		$options = array();
	}

	$settings = wp_parse_args( $options, agrad_default_settings() );
	return agrad_normalize_settings( agrad_merge_global_config( $settings ) );
}

/**
 * Sanitize settings before persisting.
 *
 * @param array $input Raw input.
 * @return array
 */
function agrad_sanitize_settings( $input ) {
	$defaults = agrad_default_settings();
	$sanitized = array();

	if ( ! is_array( $input ) ) {
		$input = array();
	}

	foreach ( $defaults as $key => $default ) {
		if ( is_array( $default ) ) {
			$field = isset( $input[ $key ] ) ? $input[ $key ] : array();
			if ( ! is_array( $field ) ) {
				$field = explode( "\n", (string) $field );
			}
			$field = array_filter(
				array_map(
					function( $value ) {
						return sanitize_text_field( trim( $value ) );
					},
					$field
				)
			);
			$sanitized[ $key ] = array_values( array_unique( $field ) );
		} else {
			$sanitized[ $key ] = isset( $input[ $key ] ) ? 1 : 0;
		}
	}

	return agrad_normalize_settings( $sanitized );
}

/**
 * Merge global config lists into settings.
 *
 * @param array $settings Settings array.
 * @return array
 */
function agrad_merge_global_config( $settings ) {
	$global = agrad_load_global_config();

	foreach ( array( 'rest_allowed_prefixes', 'http_allowed_hosts', 'http_blocked_hosts' ) as $key ) {
		if ( empty( $global[ $key ] ) || ! is_array( $global[ $key ] ) ) {
			continue;
		}

		if ( ! isset( $settings[ $key ] ) || ! is_array( $settings[ $key ] ) ) {
			$settings[ $key ] = array();
		}

		$settings[ $key ] = array_values(
			array_unique(
				array_merge( $settings[ $key ], $global[ $key ] )
			)
		);
	}

	return $settings;
}

/**
 * Load global config overrides from config/global-config.json.
 *
 * @return array
 */
function agrad_load_global_config() {
	$path = trailingslashit( AGRAD_PLUGIN_PATH ) . 'config/global-config.json';

	if ( ! file_exists( $path ) ) {
		return array();
	}

	$contents = file_get_contents( $path );

	if ( false === $contents ) {
		return array();
	}

	$data = json_decode( $contents, true );

	if ( ! is_array( $data ) ) {
		return array();
	}

	$allowed_keys = array( 'rest_allowed_prefixes', 'http_allowed_hosts', 'http_blocked_hosts' );
	$sanitized    = array();

	foreach ( $allowed_keys as $key ) {
		if ( empty( $data[ $key ] ) || ! is_array( $data[ $key ] ) ) {
			continue;
		}

		$values = array_map(
			function( $value ) {
				return sanitize_text_field( trim( $value ) );
			},
			$data[ $key ]
		);

		$sanitized[ $key ] = array_values( array_filter( array_unique( $values ) ) );
	}

	return $sanitized;
}

/**
 * Normalise settings values to booleans/arrays.
 *
 * @param array $settings Settings array.
 * @return array
 */
function agrad_normalize_settings( $settings ) {
	$defaults = agrad_default_settings();

	foreach ( $defaults as $key => $default ) {
		if ( is_array( $default ) ) {
			$field = isset( $settings[ $key ] ) && is_array( $settings[ $key ] ) ? $settings[ $key ] : array();
			$field = array_filter(
				array_map(
					function( $value ) {
						return sanitize_text_field( trim( $value ) );
					},
					$field
				)
			);
			$settings[ $key ] = array_values( array_unique( $field ) );
			continue;
		}

		$value = isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
		$settings[ $key ] = filter_var( $value, FILTER_VALIDATE_BOOLEAN ) ? 1 : 0;
	}

	return $settings;
}

/**
 * Helper to decide if a feature is enabled.
 *
 * @param string $key Option key.
 * @return bool
 */
function agrad_is_enabled( $key ) {
	$options = agrad_get_settings();
	return ! empty( $options[ $key ] );
}
