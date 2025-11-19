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
		'remove_wp_version'       => 1,
		'disable_gutenberg'       => 1,
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

	return wp_parse_args( $options, agrad_default_settings() );
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

	return $sanitized;
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
