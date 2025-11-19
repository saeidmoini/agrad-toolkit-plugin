<?php
/**
 * Product maintenance mode module.
 *
 * @package AgradToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agrad_Module_Product_Status {

	const OPTION_MODE = 'agrad_product_status_mode';
	const OPTION_LOGS = 'agrad_product_status_logs';

	/**
	 * Setup hooks.
	 */
	public static function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'missing_woo_notice' ) );
			return;
		}

		add_action( 'admin_menu', array( __CLASS__, 'register_page' ) );
		add_action( 'admin_post_agrad_toggle_product_status', array( __CLASS__, 'handle_toggle' ) );
		add_action( 'wp_ajax_agrad_clear_product_status_logs', array( __CLASS__, 'clear_logs' ) );

		if ( get_option( self::OPTION_MODE ) ) {
			add_filter( 'woocommerce_is_purchasable', '__return_false' );
			add_filter( 'woocommerce_get_availability', array( __CLASS__, 'override_availability' ), 10, 2 );
			add_filter( 'woocommerce_before_cart', array( __CLASS__, 'catalog_only_notice' ) );
		}
	}

	/**
	 * Admin notice when WooCommerce is missing.
	 */
	public static function missing_woo_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>' . esc_html__( 'فعال‌سازی ماژول وضعیت محصولات نیازمند فعال بودن ووکامرس است.', 'agrad-toolkit' ) . '</p></div>';
	}

	/**
	 * Submenu page.
	 */
	public static function register_page() {
		add_submenu_page(
			'agrad-toolkit',
			__( 'مدیریت وضعیت محصولات', 'agrad-toolkit' ),
			__( 'وضعیت محصولات', 'agrad-toolkit' ),
			'manage_woocommerce',
			'agrad-product-status',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Render the management page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$mode = (bool) get_option( self::OPTION_MODE );
		$logs = get_option( self::OPTION_LOGS, array() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'مدیریت وضعیت محصولات', 'agrad-toolkit' ); ?></h1>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'agrad_toggle_product_status', 'agrad_toggle_nonce' ); ?>
				<input type="hidden" name="action" value="agrad_toggle_product_status" />
				<p>
					<?php esc_html_e( 'فعال‌سازی حالت کاتالوگ موجب می‌شود تمام محصولات فقط جهت مشاهده باشند و امکان خرید وجود نداشته باشد.', 'agrad-toolkit' ); ?>
				</p>
				<p>
					<button type="submit" name="mode" value="<?php echo $mode ? '0' : '1'; ?>" class="button button-primary">
						<?php echo $mode ? esc_html__( 'غیرفعال‌سازی حالت کاتالوگ', 'agrad-toolkit' ) : esc_html__( 'فعال‌سازی حالت کاتالوگ', 'agrad-toolkit' ); ?>
					</button>
				</p>
			</form>

			<h2><?php esc_html_e( 'لاگ تغییرات', 'agrad-toolkit' ); ?></h2>
			<p>
				<button id="agrad-clear-product-status-logs" class="button"><?php esc_html_e( 'پاک کردن لاگ‌ها', 'agrad-toolkit' ); ?></button>
			</p>
			<table class="widefat stripped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'تاریخ', 'agrad-toolkit' ); ?></th>
						<th><?php esc_html_e( 'کاربر', 'agrad-toolkit' ); ?></th>
						<th><?php esc_html_e( 'وضعیت', 'agrad-toolkit' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $logs ) ) : ?>
						<tr><td colspan="3"><?php esc_html_e( 'لاگی وجود ندارد.', 'agrad-toolkit' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( array_reverse( $logs ) as $log ) : ?>
							<tr>
								<td><?php echo esc_html( $log['time'] ); ?></td>
								<td><?php echo esc_html( $log['user'] ); ?></td>
								<td><?php echo esc_html( $log['status'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<script>
		jQuery(function($){
			$('#agrad-clear-product-status-logs').on('click', function(e){
				e.preventDefault();
				if (!confirm('<?php echo esc_js( __( 'آیا مطمئن هستید؟', 'agrad-toolkit' ) ); ?>')) {
					return;
				}
				$.post(ajaxurl, { action: 'agrad_clear_product_status_logs', nonce: '<?php echo esc_js( wp_create_nonce( 'agrad_clear_product_status_logs' ) ); ?>' })
				.done(function(){ location.reload(); });
			});
		});
		</script>
		<?php
	}

	/**
	 * Toggle handler.
	 */
	public static function handle_toggle() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( __( 'Access denied.', 'agrad-toolkit' ) );
		}

		check_admin_referer( 'agrad_toggle_product_status', 'agrad_toggle_nonce' );

		$mode = isset( $_POST['mode'] ) ? absint( $_POST['mode'] ) : 0;
		update_option( self::OPTION_MODE, $mode );

		self::log(
			sprintf(
				'%s',
				$mode ? __( 'حالت کاتالوگ فعال شد', 'agrad-toolkit' ) : __( 'حالت کاتالوگ غیرفعال شد', 'agrad-toolkit' )
			)
		);

		wp_safe_redirect( admin_url( 'admin.php?page=agrad-product-status' ) );
		exit;
	}

	/**
	 * Clear logs.
	 */
	public static function clear_logs() {
		check_ajax_referer( 'agrad_clear_product_status_logs', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error();
		}

		delete_option( self::OPTION_LOGS );
		wp_send_json_success();
	}

	/**
	 * Add entry to log.
	 *
	 * @param string $message Message.
	 */
	protected static function log( $message ) {
		$logs   = get_option( self::OPTION_LOGS, array() );
		$user   = wp_get_current_user();
		$logs[] = array(
			'time'   => current_time( 'mysql' ),
			'user'   => $user->exists() ? $user->display_name : __( 'سیستم', 'agrad-toolkit' ),
			'status' => $message,
		);

		update_option( self::OPTION_LOGS, array_slice( $logs, -50 ) );
	}

	/**
	 * Cart notice.
	 */
	public static function catalog_only_notice() {
		wc_print_notice( __( 'سفارش‌گیری موقتاً غیرفعال است و محصولات فقط جهت مشاهده هستند.', 'agrad-toolkit' ), 'notice' );
	}

	/**
	 * Update availability text.
	 *
	 * @param array      $availability Availability data.
	 * @param WC_Product $product Product.
	 * @return array
	 */
	public static function override_availability( $availability, $product ) {
		$availability['availability'] = __( 'نمایش کاتالوگ', 'agrad-toolkit' );
		return $availability;
	}
}
