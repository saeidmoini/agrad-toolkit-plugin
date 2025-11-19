<?php
/**
 * Custom WooCommerce sorting module.
 *
 * @package AgradToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agrad_Module_Custom_Sorting {

	/**
	 * Singleton.
	 *
	 * @var self|null
	 */
	protected static $instance = null;

	/**
	 * Option name.
	 *
	 * @var string
	 */
	protected $option_name = 'agrad_custom_sort_options';

	/**
	 * Bootstrap.
	 */
	public static function init() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_custom_sort_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_custom_sort_fields' ) );
		add_shortcode( 'agrad_custom_sort', array( $this, 'render_sort_dropdown' ) );
		add_filter( 'woocommerce_get_catalog_ordering_args', array( $this, 'customize_ordering_args' ), 20 );
		add_filter( 'woocommerce_catalog_orderby', array( $this, 'add_sorting_to_default_dropdown' ) );
		add_action( 'wp_ajax_agrad_save_custom_sort', array( $this, 'ajax_save_custom_sort' ) );
		add_action( 'wp_ajax_agrad_delete_custom_sort', array( $this, 'ajax_delete_custom_sort' ) );
		add_action( 'wp_ajax_agrad_clear_custom_sort', array( $this, 'ajax_clear_sort_values' ) );
		add_action( 'wp_head', array( $this, 'add_custom_styles' ) );
	}

	/**
	 * Admin page registration.
	 */
	public function register_page() {
		add_submenu_page(
			'agrad-toolkit',
			__( 'Custom Sort Options', 'agrad-toolkit' ),
			__( 'Custom Sort', 'agrad-toolkit' ),
			'manage_woocommerce',
			'agrad-custom-sorting',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Admin page view.
	 */
	public function render_admin_page() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'WooCommerce must be active to use this tool.', 'agrad-toolkit' ) . '</p></div>';
			return;
		}

		$saved_sorts = get_option( $this->option_name, array() );
		$nonce       = wp_create_nonce( 'agrad_custom_sort' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'مدیریت گزینه‌های مرتب‌سازی', 'agrad-toolkit' ); ?></h1>
			<table class="form-table">
				<tr>
					<th><label for="agrad-sort-key"><?php esc_html_e( 'کلید مرتب‌سازی', 'agrad-toolkit' ); ?></label></th>
					<td><input type="text" id="agrad-sort-key" class="regular-text" /></td>
				</tr>
				<tr>
					<th><label for="agrad-sort-label"><?php esc_html_e( 'عنوان نمایشی', 'agrad-toolkit' ); ?></label></th>
					<td><input type="text" id="agrad-sort-label" class="regular-text" /></td>
				</tr>
			</table>
			<p><button class="button button-primary" id="agrad-add-sort"><?php esc_html_e( 'افزودن گزینه', 'agrad-toolkit' ); ?></button></p>

			<h2><?php esc_html_e( 'گزینه‌های موجود', 'agrad-toolkit' ); ?></h2>
			<table class="widefat striped">
				<thead>
				<tr>
					<th><?php esc_html_e( 'کلید', 'agrad-toolkit' ); ?></th>
					<th><?php esc_html_e( 'عنوان', 'agrad-toolkit' ); ?></th>
					<th><?php esc_html_e( 'عملیات', 'agrad-toolkit' ); ?></th>
				</tr>
				</thead>
				<tbody id="agrad-sort-list">
				<?php foreach ( $saved_sorts as $key => $label ) : ?>
					<tr data-key="<?php echo esc_attr( $key ); ?>">
						<td><?php echo esc_html( $key ); ?></td>
						<td><?php echo esc_html( $label ); ?></td>
						<td>
							<button class="button agrad-delete-sort"><?php esc_html_e( 'حذف', 'agrad-toolkit' ); ?></button>
							<button class="button agrad-clear-sort"><?php esc_html_e( 'پاک کردن مقادیر', 'agrad-toolkit' ); ?></button>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<script>
		jQuery(function($){
			const nonce = '<?php echo esc_js( $nonce ); ?>';
			$('#agrad-add-sort').on('click', function(e){
				e.preventDefault();
				$.post(ajaxurl, {
					action: 'agrad_save_custom_sort',
					sort_key: $('#agrad-sort-key').val(),
					sort_label: $('#agrad-sort-label').val(),
					nonce: nonce
				}).done(function(){ location.reload(); });
			});

			$('.agrad-delete-sort').on('click', function(e){
				e.preventDefault();
				const row = $(this).closest('tr');
				$.post(ajaxurl, {
					action: 'agrad_delete_custom_sort',
					sort_key: row.data('key'),
					nonce: nonce
				}).done(function(){ row.remove(); });
			});

			$('.agrad-clear-sort').on('click', function(e){
				e.preventDefault();
				const button = $(this);
				const row = button.closest('tr');
				button.prop('disabled', true).text('<?php echo esc_js( __( 'در حال پاکسازی...', 'agrad-toolkit' ) ); ?>');
				$.post(ajaxurl, {
					action: 'agrad_clear_custom_sort',
					sort_key: row.data('key'),
					nonce: nonce
				}).always(function(){
					button.prop('disabled', false).text('<?php echo esc_js( __( 'پاک کردن مقادیر', 'agrad-toolkit' ) ); ?>');
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Add fields on product edit page.
	 */
	public function add_custom_sort_fields() {
		$saved_sorts = get_option( $this->option_name, array() );
		global $post;

		foreach ( $saved_sorts as $key => $label ) {
			$value = get_post_meta( $post->ID, '_' . $key, true );
			woocommerce_wp_text_input(
				array(
					'id'                => $key,
					'label'             => $label,
					'desc_tip'          => true,
					'description'       => sprintf( __( 'Enter sort value for %s', 'agrad-toolkit' ), $label ),
					'type'              => 'number',
					'custom_attributes' => array(
						'step' => 'any',
						'min'  => '0',
					),
					'value'             => $value,
				)
			);
		}
	}

	/**
	 * Save meta values.
	 */
	public function save_custom_sort_fields( $post_id ) {
		$saved_sorts = get_option( $this->option_name, array() );

		foreach ( $saved_sorts as $key => $label ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, '_' . $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}
	}

	/**
	 * Shortcode renderer.
	 */
	public function render_sort_dropdown() {
		$saved_sorts  = get_option( $this->option_name, array() );
		$current_sort = isset( $_GET['agrad_sort'] ) ? sanitize_text_field( wp_unslash( $_GET['agrad_sort'] ) ) : '';
		$current_url  = remove_query_arg( array( 'agrad_sort', 'paged' ) );

		ob_start();
		?>
		<form method="get" action="<?php echo esc_url( $current_url ); ?>" class="woocommerce-ordering custom-sort-form">
			<?php foreach ( $_GET as $key => $value ) : ?>
				<?php if ( in_array( $key, array( 'agrad_sort', 'paged' ), true ) ) { continue; } ?>
				<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" />
			<?php endforeach; ?>
			<select name="agrad_sort" class="custom-sort-dropdown" onchange="this.form.submit()">
				<option value=""><?php esc_html_e( 'مرتب‌سازی پیش‌فرض', 'agrad-toolkit' ); ?></option>
				<?php foreach ( $saved_sorts as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_sort, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Sorting args.
	 */
	public function customize_ordering_args( $args ) {
		if ( empty( $_GET['agrad_sort'] ) ) {
			return $args;
		}

		$custom_sort  = sanitize_text_field( wp_unslash( $_GET['agrad_sort'] ) );
		$saved_sorts  = get_option( $this->option_name, array() );
		if ( ! array_key_exists( $custom_sort, $saved_sorts ) ) {
			return $args;
		}

		$order = isset( $_GET['order'] ) ? strtoupper( sanitize_key( wp_unslash( $_GET['order'] ) ) ) : 'DESC';
		$order = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';

		$args['meta_key'] = '_' . $custom_sort;
		$args['orderby']  = 'meta_value_num';
		$args['order']    = $order;

		return $args;
	}

	/**
	 * Add custom options to default dropdown.
	 */
	public function add_sorting_to_default_dropdown( $sortby ) {
		$saved_sorts = get_option( $this->option_name, array() );
		foreach ( $saved_sorts as $key => $label ) {
			$sortby[ $key ] = $label;
		}
		return $sortby;
	}

	/**
	 * Add frontend styles.
	 */
	public function add_custom_styles() {
		?>
		<style>
			.custom-sort-dropdown {
				padding: 8px 12px;
				border: 1px solid #ddd;
				border-radius: 4px;
				background-color: #fff;
				font-size: 14px;
				margin: 10px 0;
			}
		</style>
		<?php
	}

	/**
	 * Save custom sort via AJAX.
	 */
	public function ajax_save_custom_sort() {
		check_ajax_referer( 'agrad_custom_sort', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error();
		}

		$saved_sorts = get_option( $this->option_name, array() );
		$key         = sanitize_key( wp_unslash( $_POST['sort_key'] ) );
		$label       = sanitize_text_field( wp_unslash( $_POST['sort_label'] ) );

		$saved_sorts[ $key ] = $label;
		update_option( $this->option_name, $saved_sorts );

		wp_send_json_success();
	}

	/**
	 * Delete option.
	 */
	public function ajax_delete_custom_sort() {
		check_ajax_referer( 'agrad_custom_sort', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error();
		}

		$key         = sanitize_key( wp_unslash( $_POST['sort_key'] ) );
		$saved_sorts = get_option( $this->option_name, array() );

		if ( isset( $saved_sorts[ $key ] ) ) {
			unset( $saved_sorts[ $key ] );
			update_option( $this->option_name, $saved_sorts );
			global $wpdb;
			$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_' . $key ) );
		}

		wp_send_json_success();
	}

	/**
	 * Remove all stored meta values for a key.
	 */
	public function ajax_clear_sort_values() {
		check_ajax_referer( 'agrad_custom_sort', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error();
		}

		$key = sanitize_key( wp_unslash( $_POST['sort_key'] ) );
		global $wpdb;
		$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_' . $key ) );

		wp_send_json_success();
	}
}
