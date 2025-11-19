<?php
/**
 * WooCommerce term transfer module.
 *
 * @package AgradToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agrad_Module_Term_Transfer {

	/**
	 * Hook into WP.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_page' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_ajax_agrad_term_transfer_get_terms', array( __CLASS__, 'ajax_get_terms' ) );
	}

	/**
	 * Register submenu.
	 */
	public static function register_page() {
		add_submenu_page(
			'agrad-toolkit',
			__( 'انتقال ترم محصولات', 'agrad-toolkit' ),
			__( 'انتقال ترم محصولات', 'agrad-toolkit' ),
			'manage_woocommerce',
			'agrad-term-transfer',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Enqueue jQuery (used by inline script) when needed.
	 */
	public static function enqueue_assets( $hook ) {
		if ( 'agrad-toolkit_page_agrad-term-transfer' === $hook ) {
			wp_enqueue_script( 'jquery' );
		}
	}

	/**
	 * Render admin page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'WooCommerce must be active to use this tool.', 'agrad-toolkit' ) . '</p></div>';
			return;
		}

		$taxonomies = get_object_taxonomies( 'product', 'objects' );

		if ( isset( $_POST['agrad_term_transfer_submit'] ) ) {
			check_admin_referer( 'agrad_term_transfer_action', 'agrad_term_transfer_nonce' );

			$source_tax  = sanitize_text_field( wp_unslash( $_POST['source_tax'] ) );
			$source_term = absint( $_POST['source_term'] );
			$target_tax  = sanitize_text_field( wp_unslash( $_POST['target_tax'] ) );
			$target_term = absint( $_POST['target_term'] );

			if ( $source_tax && $source_term && $target_tax && $target_term ) {
				$args     = array(
					'post_type'      => 'product',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'tax_query'      => array(
						array(
							'taxonomy' => $source_tax,
							'field'    => 'term_id',
							'terms'    => $source_term,
						),
					),
				);
				$products = get_posts( $args );
				$total    = count( $products );

				foreach ( $products as $product_id ) {
					wp_set_object_terms( $product_id, $target_term, $target_tax, true );
				}

				printf(
					'<div class="notice notice-success"><p>%s</p></div>',
					esc_html(
						sprintf(
							/* translators: %d number of products updated */
							__( '%d products updated successfully.', 'agrad-toolkit' ),
							$total
						)
					)
				);
			} else {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'All fields are required.', 'agrad-toolkit' ) . '</p></div>';
			}
		}

		include __DIR__ . '/views/term-transfer.php';
	}

	/**
	 * AJAX: fetch terms for selected taxonomy.
	 */
	public static function ajax_get_terms() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error();
		}

		$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) ) : '';
		if ( ! $taxonomy ) {
			wp_send_json_error();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			wp_send_json_error();
		}

		$response = array();
		foreach ( $terms as $term ) {
			$response[] = array(
				'term_id' => $term->term_id,
				'name'    => $term->name,
			);
		}

		wp_send_json_success( $response );
	}
}
