<?php
/*
Plugin Name: Agrad Toolkit
Version: 2.0.6
Description: Unified performance, security and WooCommerce utilities for Agrad sites.
Author: Agrad Team
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AGRAD_PLUGIN_VERSION', '2.0.6' );
define( 'AGRAD_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'AGRAD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once AGRAD_PLUGIN_PATH . 'includes/options.php';
require_once AGRAD_PLUGIN_PATH . 'includes/settings-page.php';

/**
 * Activation hook.
 */
function agrad_toolkit_activate() {
	$settings = get_option( 'agrad_settings', array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	update_option( 'agrad_settings', wp_parse_args( $settings, agrad_default_settings() ) );
}
register_activation_hook( __FILE__, 'agrad_toolkit_activate' );

/**
 * Load modules once plugins are loaded.
 */
function agrad_toolkit_bootstrap() {
	$settings = agrad_get_settings();

	require_once AGRAD_PLUGIN_PATH . 'includes/modules/class-agrad-security.php';
	Agrad_Module_Security::init( $settings );

	if ( ! empty( $settings['custom_comments'] ) ) {
		require_once AGRAD_PLUGIN_PATH . 'includes/modules/class-agrad-comments.php';
		Agrad_Module_Comments::init();
	}

	if ( ! empty( $settings['portfolio_cpt'] ) ) {
		require_once AGRAD_PLUGIN_PATH . 'includes/modules/class-agrad-portfolio.php';
		Agrad_Module_Portfolio::init();
	}

	if ( ! empty( $settings['healthcheck_endpoint'] ) ) {
		require_once AGRAD_PLUGIN_PATH . 'includes/modules/class-agrad-healthcheck.php';
		Agrad_Module_Healthcheck::init();
	}

	if ( ! empty( $settings['custom_admin_path'] ) ) {
		require_once AGRAD_PLUGIN_PATH . 'includes/modules/class-agrad-admin-path.php';
		Agrad_Module_Admin_Path::init();
	}

	if ( ! empty( $settings['http_access_control'] ) ) {
		require_once AGRAD_PLUGIN_PATH . 'includes/modules/class-agrad-http-access.php';
		Agrad_Module_Http_Access::init( $settings );
	}

	// Optional toolset modules.
	if ( ! empty( $settings['woo_term_transfer'] ) ) {
		require_once AGRAD_PLUGIN_PATH . 'includes/modules/class-agrad-term-transfer.php';
		Agrad_Module_Term_Transfer::init();
	}

	if ( ! empty( $settings['woo_text_replacer'] ) ) {
		require_once AGRAD_PLUGIN_PATH . 'includes/modules/class-agrad-text-replacer.php';
		Agrad_Module_Text_Replacer::init();
	}

	if ( ! empty( $settings['woo_remove_discounts'] ) ) {
		require_once AGRAD_PLUGIN_PATH . 'includes/modules/class-agrad-remove-discounts.php';
		Agrad_Module_Remove_Discounts::init();
	}

	if ( ! empty( $settings['woo_product_status'] ) ) {
		require_once AGRAD_PLUGIN_PATH . 'includes/modules/class-agrad-product-status.php';
		Agrad_Module_Product_Status::init();
	}

	if ( ! empty( $settings['woo_no_image_report'] ) ) {
		require_once AGRAD_PLUGIN_PATH . 'includes/modules/class-agrad-noimage.php';
		Agrad_Module_Noimage::init();
	}

	if ( ! empty( $settings['post_type_copy'] ) ) {
		require_once AGRAD_PLUGIN_PATH . 'includes/modules/class-agrad-post-type-copy.php';
		Agrad_Module_Post_Type_Copy::init();
	}

	if ( ! empty( $settings['woo_custom_sorting'] ) ) {
		require_once AGRAD_PLUGIN_PATH . 'includes/modules/class-agrad-custom-sorting.php';
		Agrad_Module_Custom_Sorting::init();
	}
}
add_action( 'plugins_loaded', 'agrad_toolkit_bootstrap', 20 );
