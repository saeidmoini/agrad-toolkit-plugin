<?php
/**
 * Remove all WooCommerce discounts in batches.
 *
 * @package AgradToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agrad_Module_Remove_Discounts {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_page' ) );
		add_action( 'wp_ajax_agrad_remove_discounts', array( __CLASS__, 'ajax_remove_discounts' ) );
	}

	/**
	 * Submenu.
	 */
	public static function register_page() {
		add_submenu_page(
			'agrad-toolkit',
			__( 'حذف تخفیفات محصولات', 'agrad-toolkit' ),
			__( 'حذف تخفیفات', 'agrad-toolkit' ),
			'manage_woocommerce',
			'agrad-remove-discounts',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Render admin page with inline JS.
	 */
	public static function render_page() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'WooCommerce must be active to use this tool.', 'agrad-toolkit' ) . '</p></div>';
			return;
		}

		$nonce = wp_create_nonce( 'agrad_remove_discounts' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'حذف تمامی تخفیفات محصولات', 'agrad-toolkit' ); ?></h1>
			<p><?php esc_html_e( 'با کلیک روی دکمه زیر تمام قیمت‌های حراج محصولات ساده و متغیر پاک می‌شود.', 'agrad-toolkit' ); ?></p>
			<button id="agrad-remove-discounts" class="button button-primary"><?php esc_html_e( 'شروع پردازش', 'agrad-toolkit' ); ?></button>
			<div id="agrad-remove-log" style="margin-top:20px;padding:10px;background:#f9f9f9;border:1px solid #ddd;"></div>
		</div>
		<script>
		jQuery(function($){
			const button = $('#agrad-remove-discounts');
			const logBox = $('#agrad-remove-log');
			let offset = 0;

			function processBatch() {
				$.post(ajaxurl, {
					action: 'agrad_remove_discounts',
					offset: offset,
					nonce: '<?php echo esc_js( $nonce ); ?>'
				}).done(function(response) {
					if (response.success) {
						const data = response.data;
						logBox.append('<p style="color:green;">' + data.processed + ' / ' + data.total + '</p>');
						offset = data.offset;
						if (offset < data.total) {
							processBatch();
						} else {
							logBox.append('<p style="color:darkgreen;font-weight:bold;"><?php echo esc_js( __( 'پردازش کامل شد.', 'agrad-toolkit' ) ); ?></p>');
							button.prop('disabled', false);
						}
					} else {
						logBox.append('<p style="color:red;"><?php echo esc_js( __( 'خطا در پردازش.', 'agrad-toolkit' ) ); ?></p>');
						button.prop('disabled', false);
					}
				}).fail(function(){
					logBox.append('<p style="color:red;"><?php echo esc_js( __( 'ارتباط با سرور برقرار نشد.', 'agrad-toolkit' ) ); ?></p>');
					button.prop('disabled', false);
				});
			}

			button.on('click', function(){
				offset = 0;
				logBox.empty().append('<p style="color:blue;"><?php echo esc_js( __( 'در حال پردازش...', 'agrad-toolkit' ) ); ?></p>');
				button.prop('disabled', true);
				processBatch();
			});
		});
		</script>
		<?php
	}

	/**
	 * AJAX handler.
	 */
	public static function ajax_remove_discounts() {
		check_ajax_referer( 'agrad_remove_discounts', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error();
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce is not active.', 'agrad-toolkit' ) ) );
		}

		$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$limit  = 10;

		$query = new WP_Query(
			array(
				'post_type'      => 'product',
				'posts_per_page' => $limit,
				'offset'         => $offset,
				'post_status'    => 'publish',
			)
		);

		$total_products = (int) $query->found_posts;
		$processed      = 0;

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$product_id = get_the_ID();
				$product    = wc_get_product( $product_id );

				if ( ! $product ) {
					continue;
				}

				if ( $product->is_type( 'simple' ) ) {
					if ( $product->is_on_sale() ) {
						update_post_meta( $product_id, '_sale_price', '' );
						$processed++;
					}
				} elseif ( $product->is_type( 'variable' ) ) {
					$variations = $product->get_children();
					foreach ( $variations as $variation_id ) {
						if ( '' !== get_post_meta( $variation_id, '_sale_price', true ) ) {
							update_post_meta( $variation_id, '_sale_price', '' );
							$processed++;
						}
					}
				}
			}
		}
		wp_reset_postdata();

		$total_items = (int) wp_count_posts( 'product' )->publish;

		wp_send_json_success(
			array(
				'processed' => $processed,
				'total'     => $total_items,
				'offset'    => $offset + $limit,
			)
		);
	}
}
