<?php
/**
 * Security & performance hardening.
 *
 * @package AgradToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agrad_Module_Security {

	/**
	 * Plugin settings snapshot.
	 *
	 * @var array
	 */
	protected static $settings = array();

	/**
	 * Bootstrap module.
	 *
	 * @param array $settings Settings array.
	 */
	public static function init( $settings ) {
		self::$settings = $settings;

		if ( ! empty( $settings['custom_font_swap'] ) ) {
			add_filter(
				'elementor_pro/custom_fonts/font_display',
				array( __CLASS__, 'force_font_swap' ),
				10,
				3
			);
		}

		if ( ! empty( $settings['disable_xmlrpc'] ) ) {
			add_filter( 'xmlrpc_enabled', '__return_false' );
		}

		if ( ! empty( $settings['allow_svg'] ) ) {
			add_filter( 'upload_mimes', array( __CLASS__, 'allow_svg_uploads' ) );
			add_filter( 'wp_check_filetype_and_ext', array( __CLASS__, 'verify_svg_uploads' ), 10, 5 );
		}

		if ( ! empty( $settings['disable_rest_api'] ) ) {
			add_filter( 'rest_pre_dispatch', array( __CLASS__, 'maybe_block_rest_api' ), 10, 3 );
		}

		if ( ! empty( $settings['remove_wp_version'] ) ) {
			add_filter( 'the_generator', '__return_empty_string' );
			add_filter( 'style_loader_src', array( __CLASS__, 'strip_version_from_assets' ), 10, 1 );
			add_filter( 'script_loader_src', array( __CLASS__, 'strip_version_from_assets' ), 10, 1 );
		}

		if ( ! empty( $settings['disable_gutenberg'] ) ) {
			add_filter( 'gutenberg_can_edit_post', '__return_false', 5 );
			add_filter( 'use_block_editor_for_post_type', '__return_false', 10 );
		}

		if ( ! empty( $settings['disable_updates_cache'] ) ) {
			add_filter( 'auto_update_plugin', '__return_false' );
			add_filter( 'auto_update_theme', '__return_false' );
			remove_action( 'load-update-core.php', 'wp_update_plugins' );
			remove_action( 'load-update-core.php', 'wp_update_themes' );
			remove_action( 'load-plugins.php', 'wp_update_plugins' );
			remove_action( 'load-themes.php', 'wp_update_themes' );
			add_filter( 'pre_site_transient_update_plugins', array( __CLASS__, 'fake_plugin_update_transient' ), 5 );
			add_filter( 'pre_site_transient_update_themes', array( __CLASS__, 'fake_theme_update_transient' ), 5 );
			add_action( 'init', array( __CLASS__, 'clear_plugin_update_messages' ) );
		}

		if ( ! empty( $settings['remove_dashboard_meta'] ) ) {
			add_action( 'wp_dashboard_setup', array( __CLASS__, 'clean_dashboard_widgets' ) );
		}
	}

	/**
	 * Force custom fonts to use swap to avoid layout shifts.
	 *
	 * @param string $current_value Current display option.
	 * @return string
	 */
	public static function force_font_swap( $current_value ) {
		return 'swap';
	}

	/**
	 * Add svg mime support for admins.
	 *
	 * @param array $mimes Mime types.
	 * @return array
	 */
	public static function allow_svg_uploads( $mimes ) {
		if ( current_user_can( 'administrator' ) ) {
			$mimes['svg']  = 'image/svg+xml';
			$mimes['svgz'] = 'image/svg+xml';
		}
		return $mimes;
	}

	/**
	 * Verify SVG uploads.
	 */
	public static function verify_svg_uploads( $checked, $file, $filename, $mimes, $real_mime ) {
		if ( $checked['type'] ) {
			return $checked;
		}

		$filetype = wp_check_filetype( $filename, $mimes );
		if ( 0 === strpos( (string) $filetype['type'], 'image/' ) && 'svg' !== $filetype['ext'] ) {
			$filetype['ext']  = false;
			$filetype['type'] = false;
		}

		return array(
			'ext'             => $filetype['ext'],
			'type'            => $filetype['type'],
			'proper_filename' => $filename,
		);
	}

	/**
	 * Remove version from assets.
	 */
	public static function strip_version_from_assets( $src ) {
		if ( empty( $src ) ) {
			return $src;
		}
		$parts = explode( '?', $src );
		return $parts[0];
	}

	/**
	 * Block REST requests for visitors except health endpoint.
	 */
	public static function maybe_block_rest_api( $result, $server, $request ) {
		$route = $request->get_route();

		// Allow the health endpoint even for visitors.
		if ( 0 === strpos( $route, '/wp-health-check/' ) ) {
			return $result;
		}

		if ( current_user_can( 'manage_options' ) || is_user_logged_in() ) {
			return $result;
		}

		return new WP_Error(
			'rest_disabled',
			__( 'The REST API is disabled for visitors.', 'agrad-toolkit' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Return stubbed plugin updates to avoid remote calls.
	 */
	public static function fake_plugin_update_transient( $transient ) {
		if ( ! is_admin() ) {
			return $transient;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();
		$stub    = new stdClass();

		$stub->last_checked    = time();
		$stub->version_checked = get_bloginfo( 'version' );
		$stub->checked         = array();
		$stub->response        = array();
		$stub->translations    = array();
		$stub->no_update       = array();

		foreach ( $plugins as $plugin_file => $plugin_data ) {
			$stub->checked[ $plugin_file ] = $plugin_data['Version'];
		}

		return $stub;
	}

	/**
	 * Fake theme update transient.
	 */
	public static function fake_theme_update_transient( $transient ) {
		if ( ! is_admin() ) {
			return $transient;
		}

		$themes = wp_get_themes();
		$stub   = new stdClass();

		$stub->last_checked    = time();
		$stub->version_checked = get_bloginfo( 'version' );
		$stub->checked         = array();
		$stub->response        = array();
		$stub->translations    = array();
		$stub->no_update       = array();

		foreach ( $themes as $stylesheet => $theme ) {
			$stub->checked[ $stylesheet ] = $theme->get( 'Version' );
		}

		return $stub;
	}

	/**
	 * Remove plugin update notices.
	 */
	public static function clear_plugin_update_messages() {
		remove_all_actions( 'in_plugin_update_message' );
	}

	/**
	 * Clean dashboard noise.
	 */
	public static function clean_dashboard_widgets() {
		global $wp_meta_boxes;
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity'] );
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now'] );
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_site_health'] );
		unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press'] );
		unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_primary'] );
		unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary'] );
	}
}
