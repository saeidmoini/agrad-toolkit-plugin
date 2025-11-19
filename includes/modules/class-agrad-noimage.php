<?php
/**
 * Products without featured image report.
 *
 * @package AgradToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agrad_Module_Noimage {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_page' ) );
		add_action( 'admin_post_agrad_no_image_csv', array( __CLASS__, 'download_csv' ) );
	}

	/**
	 * Register submenu.
	 */
	public static function register_page() {
		add_submenu_page(
			'agrad-toolkit',
			__( 'محصولات بدون تصویر شاخص', 'agrad-toolkit' ),
			__( 'بدون تصویر شاخص', 'agrad-toolkit' ),
			'manage_woocommerce',
			'agrad-no-image',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Render admin page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$products = self::query_products();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'محصولات بدون تصویر شاخص', 'agrad-toolkit' ); ?></h1>
			<?php if ( empty( $products ) ) : ?>
				<p><?php esc_html_e( 'همه محصولات تصویر شاخص دارند.', 'agrad-toolkit' ); ?></p>
			<?php else : ?>
				<table class="widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'شناسه', 'agrad-toolkit' ); ?></th>
							<th><?php esc_html_e( 'عنوان', 'agrad-toolkit' ); ?></th>
							<th><?php esc_html_e( 'لینک', 'agrad-toolkit' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $products as $product ) : ?>
							<tr>
								<td><?php echo esc_html( $product->ID ); ?></td>
								<td><?php echo esc_html( get_the_title( $product ) ); ?></td>
								<td><a href="<?php echo esc_url( get_edit_post_link( $product ) ); ?>"><?php esc_html_e( 'ویرایش محصول', 'agrad-toolkit' ); ?></a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="agrad_no_image_csv" />
					<?php wp_nonce_field( 'agrad_no_image_csv', 'agrad_no_image_nonce' ); ?>
					<?php submit_button( __( 'دانلود CSV', 'agrad-toolkit' ) ); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * CSV download.
	 */
	public static function download_csv() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( __( 'Access denied.', 'agrad-toolkit' ) );
		}

		check_admin_referer( 'agrad_no_image_csv', 'agrad_no_image_nonce' );

		$products = self::query_products();

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=products_without_featured_image.csv' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'ID', 'Title', 'Edit URL' ) );

		foreach ( $products as $product ) {
			fputcsv(
				$output,
				array(
					$product->ID,
					get_the_title( $product ),
					get_edit_post_link( $product ),
				)
			);
		}

		fclose( $output );
		exit;
	}

	/**
	 * Query helper.
	 *
	 * @return array
	 */
	protected static function query_products() {
		return get_posts(
			array(
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'meta_query'     => array(
					array(
						'key'     => '_thumbnail_id',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);
	}
}
