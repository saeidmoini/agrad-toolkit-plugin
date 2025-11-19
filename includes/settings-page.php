<?php
/**
 * Admin settings UI.
 *
 * @package AgradToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agrad_Settings_Page {

	/**
	 * Hook everything.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Register our settings.
	 */
	public static function register_settings() {
		register_setting(
			'agrad_settings_group',
			'agrad_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => 'agrad_sanitize_settings',
				'default'           => agrad_default_settings(),
			)
		);
	}

	/**
	 * Register the Settings menu.
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'Agrad Toolkit', 'agrad-toolkit' ),
			__( 'Agrad Toolkit', 'agrad-toolkit' ),
			'manage_options',
			'agrad-toolkit',
			array( __CLASS__, 'render_page' ),
			'dashicons-shield-alt',
			58
		);
	}

	/**
	 * Render the admin page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = agrad_get_settings();

		$groups = array(
			'security' => array(
				'title'   => __( 'Security & Performance', 'agrad-toolkit' ),
				'options' => array(
					'disable_xmlrpc'        => __( 'Disable XML-RPC', 'agrad-toolkit' ),
					'allow_svg'             => __( 'Allow SVG uploads (admins only)', 'agrad-toolkit' ),
					'disable_rest_api'      => __( 'Disable REST API for visitors (except health endpoint)', 'agrad-toolkit' ),
					'remove_wp_version'     => __( 'Hide WordPress version', 'agrad-toolkit' ),
					'disable_gutenberg'     => __( 'Disable Gutenberg editor', 'agrad-toolkit' ),
					'disable_updates_cache' => __( 'Stop core update checks and cache empty responses', 'agrad-toolkit' ),
					'remove_dashboard_meta' => __( 'Clean dashboard widgets', 'agrad-toolkit' ),
					'custom_font_swap'      => __( 'Force Elementor Pro custom fonts to use font-display swap', 'agrad-toolkit' ),
				),
			),
			'core'     => array(
				'title'   => __( 'Core Utilities', 'agrad-toolkit' ),
				'options' => array(
					'custom_comments'      => __( 'Customise comment form', 'agrad-toolkit' ),
					'portfolio_cpt'        => __( 'Portfolio custom post type', 'agrad-toolkit' ),
					'healthcheck_endpoint' => __( 'Expose health-check REST endpoint', 'agrad-toolkit' ),
					'custom_admin_path'    => __( 'Rewrite login URL to /agrad-admin', 'agrad-toolkit' ),
					'http_access_control'  => __( 'HTTP access control (allow/block hosts)', 'agrad-toolkit' ),
				),
			),
			'modules'  => array(
				'title'   => __( 'Tools & Integrations', 'agrad-toolkit' ),
				'options' => array(
					'woo_term_transfer'    => __( 'WooCommerce term transfer tool', 'agrad-toolkit' ),
					'woo_text_replacer'    => __( 'Global text replacer', 'agrad-toolkit' ),
					'woo_remove_discounts' => __( 'Remove all product discounts', 'agrad-toolkit' ),
					'woo_product_status'   => __( 'Product maintenance mode (catalog only)', 'agrad-toolkit' ),
					'woo_no_image_report'  => __( 'Products without featured image report', 'agrad-toolkit' ),
					'post_type_copy'       => __( 'Copy between post types', 'agrad-toolkit' ),
					'woo_custom_sorting'   => __( 'Custom product sorting options', 'agrad-toolkit' ),
				),
			),
		);
		?>
		<div class="wrap agrad-toolkit-settings">
			<h1><?php esc_html_e( 'Agrad Toolkit Settings', 'agrad-toolkit' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'agrad_settings_group' );
				?>
				<?php foreach ( $groups as $group ) : ?>
					<div class="agrad-settings-card">
						<h2><?php echo esc_html( $group['title'] ); ?></h2>
						<table class="form-table">
							<?php foreach ( $group['options'] as $key => $label ) : ?>
								<tr>
									<th scope="row"><?php echo esc_html( $label ); ?></th>
									<td>
										<label>
											<input type="checkbox" name="agrad_settings[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $options[ $key ] ) ); ?> />
											<?php esc_html_e( 'Enabled', 'agrad-toolkit' ); ?>
										</label>
									</td>
								</tr>
							<?php endforeach; ?>
						</table>
					</div>
				<?php endforeach; ?>

				<div class="agrad-settings-card">
					<h2><?php esc_html_e( 'HTTP Access Control', 'agrad-toolkit' ); ?></h2>
					<p><?php esc_html_e( 'One host per line. Hosts listed below are granted when HTTP blocking is enabled by wp-config. Blocked hosts always fail, even if whitelisted elsewhere.', 'agrad-toolkit' ); ?></p>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Allowed hosts', 'agrad-toolkit' ); ?></th>
							<td>
								<textarea name="agrad_settings[http_allowed_hosts]" rows="5" cols="50"><?php echo esc_textarea( implode( "\n", (array) $options['http_allowed_hosts'] ) ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Blocked hosts', 'agrad-toolkit' ); ?></th>
							<td>
								<textarea name="agrad_settings[http_blocked_hosts]" rows="5" cols="50"><?php echo esc_textarea( implode( "\n", (array) $options['http_blocked_hosts'] ) ); ?></textarea>
							</td>
						</tr>
					</table>
				</div>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}

Agrad_Settings_Page::init();
