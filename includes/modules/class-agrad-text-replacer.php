<?php
/**
 * Global text replacer module.
 *
 * @package AgradToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agrad_Module_Text_Replacer {

	/**
	 * Option key for logs.
	 *
	 * @var string
	 */
	protected static $log_option = 'agrad_text_replacer_logs';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_page' ) );
		add_action( 'admin_post_agrad_replace_text', array( __CLASS__, 'handle_replace' ) );
	}

	/**
	 * Submenu.
	 */
	public static function register_page() {
		add_submenu_page(
			'agrad-toolkit',
			__( 'Global Text Replacer', 'agrad-toolkit' ),
			__( 'Text Replacer', 'agrad-toolkit' ),
			'manage_options',
			'agrad-text-replacer',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Render admin page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$logs = get_option( self::$log_option, array() );
		include __DIR__ . '/views/text-replacer.php';
	}

	/**
	 * Handle submissions.
	 */
	public static function handle_replace() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Access denied.', 'agrad-toolkit' ) );
		}

		check_admin_referer( 'agrad_text_replace', 'agrad_text_nonce' );

		$search_text  = isset( $_POST['search_text'] ) ? sanitize_text_field( wp_unslash( $_POST['search_text'] ) ) : '';
		$replace_text = isset( $_POST['replace_text'] ) ? sanitize_text_field( wp_unslash( $_POST['replace_text'] ) ) : '';
		$scope        = isset( $_POST['scope'] ) ? sanitize_text_field( wp_unslash( $_POST['scope'] ) ) : 'global';

		if ( empty( $search_text ) ) {
			wp_safe_redirect(
				add_query_arg(
					'message',
					'error',
					admin_url( 'admin.php?page=agrad-text-replacer' )
				)
			);
			exit;
		}

		$pattern = '/' . str_replace( '\ ', '\s*', preg_quote( $search_text, '/' ) ) . '/u';
		$modified_count = 0;
		$posts          = array();

		if ( 'page' === $scope ) {
			$page_url = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';
			if ( empty( $page_url ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=agrad-text-replacer&message=error' ) );
				exit;
			}

			$parsed = wp_parse_url( $page_url );
			$slug   = isset( $parsed['path'] ) ? trim( $parsed['path'], '/' ) : '';
			$post   = get_page_by_path( $slug, OBJECT, array( 'post', 'page' ) );

			if ( ! $post ) {
				wp_safe_redirect( admin_url( 'admin.php?page=agrad-text-replacer&message=error' ) );
				exit;
			}

			$posts = array( $post );
		} else {
			$posts = get_posts(
				array(
					'post_type'      => 'any',
					'posts_per_page' => -1,
					'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private', 'inherit' ),
				)
			);
		}

		foreach ( $posts as $post ) {
			$updated = false;

			if ( preg_match( $pattern, $post->post_content ) ) {
				wp_update_post(
					array(
						'ID'           => $post->ID,
						'post_content' => preg_replace( $pattern, $replace_text, $post->post_content ),
					)
				);
				$updated = true;
				$modified_count++;
			}

			$meta_keys = get_post_custom_keys( $post->ID );
			if ( ! empty( $meta_keys ) ) {
				foreach ( $meta_keys as $meta_key ) {
					$meta_value = get_post_meta( $post->ID, $meta_key, true );
					if ( is_string( $meta_value ) && preg_match( $pattern, $meta_value ) ) {
						update_post_meta( $post->ID, $meta_key, preg_replace( $pattern, $replace_text, $meta_value ) );
						$updated = true;
						$modified_count++;
					}
				}
			}

			if ( class_exists( '\Elementor\Plugin' ) ) {
				$document = \Elementor\Plugin::instance()->documents->get( $post->ID );
				if ( $document && preg_match( $pattern, $document->get_json_meta( '_elementor_data' ) ) ) {
					$old_data = $document->get_json_meta( '_elementor_data' );
					$new_data = preg_replace( $pattern, $replace_text, $old_data );
					$document->save( array(), array( 'meta_input' => array( '_elementor_data' => $new_data ) ) );
					$updated = true;
					$modified_count++;
				}
			}

			if ( $updated ) {
				clean_post_cache( $post->ID );
			}
		}

		self::log_change(
			array(
				'date'    => current_time( 'mysql' ),
				'search'  => $search_text,
				'replace' => $replace_text,
				'count'   => $modified_count,
			)
		);

		wp_safe_redirect(
			add_query_arg(
				'message',
				'success',
				admin_url( 'admin.php?page=agrad-text-replacer' )
			)
		);
		exit;
	}

	/**
	 * Save logs.
	 *
	 * @param array $entry Entry.
	 */
	protected static function log_change( $entry ) {
		$logs   = get_option( self::$log_option, array() );
		$logs[] = $entry;
		update_option( self::$log_option, array_slice( $logs, -20 ) ); // Keep last 20 entries.
	}
}
