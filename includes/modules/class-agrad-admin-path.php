<?php
/**
 * Custom admin login path handler.
 *
 * @package AgradToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agrad_Module_Admin_Path {

	const SLUG = 'agrad-admin';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'intercept_login_request' ), 1 );
		add_filter( 'login_url', array( __CLASS__, 'filter_login_url' ), 10, 3 );
		add_filter( 'site_url', array( __CLASS__, 'filter_site_url' ), 10, 4 );
	}

	/**
	 * Serve wp-login.php when visiting /agrad-admin.
	 */
	public static function intercept_login_request() {
		if ( self::is_digits_active() ) {
			return;
		}

		if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
			return;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path        = trim( strtok( $request_uri, '?' ), '/' );

		if ( self::SLUG === $path ) {
			require_once ABSPATH . 'wp-login.php';
			exit;
		}

		if ( 0 === strpos( $path, 'wp-admin' ) && ! is_user_logged_in() && ! self::is_ajax_request() ) {
			if ( self::is_login_asset_request( $path ) ) {
				return;
			}

			// Keep wp-admin hidden: serve 404 for direct hits while letting whitelisted assets through.
			status_header( 404 );
			nocache_headers();
			exit;
		}

		if ( false !== strpos( $path, 'wp-login.php' ) ) {
			$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
			if ( 'logout' === $action ) {
				return;
			}

			wp_safe_redirect( home_url( '/' . self::SLUG . '/' ) );
			exit;
		}
	}

	/**
	 * Filter login_url() helper.
	 */
	public static function filter_login_url( $login_url, $redirect, $force_reauth ) {
		if ( self::is_digits_active() ) {
			return $login_url;
		}

		$url = home_url( '/' . self::SLUG . '/' );
		if ( $redirect ) {
			$url = add_query_arg( 'redirect_to', urlencode( $redirect ), $url );
		}
		if ( $force_reauth ) {
			$url = add_query_arg( 'reauth', '1', $url );
		}

		return $url;
	}

	/**
	 * Filter site_url() usages for wp-login.php references.
	 */
	public static function filter_site_url( $url, $path, $scheme, $blog_id ) {
		if ( self::is_digits_active() ) {
			return $url;
		}

		if ( 'wp-login.php' === $path ) {
			return home_url( '/' . self::SLUG . '/' );
		}
		return $url;
	}

	/**
	 * Determine whether current request is admin-ajax (allowed).
	 *
	 * @return bool
	 */
	protected static function is_ajax_request() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return true;
		}

		$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) ) : '';
		return 'admin-ajax.php' === $script;
	}

	/**
	 * Allow login assets (styles/scripts/images) for unauthenticated users.
	 *
	 * @param string $path Request path.
	 * @return bool
	 */
	protected static function is_login_asset_request( $path ) {
		$path = ltrim( $path, '/' );

		$whitelist_prefixes = array(
			'wp-admin/load-styles.php',
			'wp-admin/load-scripts.php',
			'wp-admin/css/',
			'wp-admin/js/',
			'wp-admin/images/',
			'wp-admin/fonts/',
			'wp-includes/js/',
			'wp-includes/css/',
			'wp-includes/images/',
			'wp-includes/fonts/',
		);

		foreach ( $whitelist_prefixes as $prefix ) {
			if ( 0 === strpos( $path, $prefix ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Detect Digits plugin to avoid clashing with its login overrides.
	 *
	 * @return bool
	 */
	protected static function is_digits_active() {
		return class_exists( 'Digits' ) || defined( 'DIGITS_VERSION' );
	}
}
