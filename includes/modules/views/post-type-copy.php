<?php
/**
 * Post type copy view.
 *
 * @package AgradToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'کپی پست‌ها بین انواع مختلف', 'agrad-toolkit' ); ?></h1>
	<form method="post">
		<?php wp_nonce_field( 'agrad_ptc_action', 'agrad_ptc_nonce' ); ?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'نوع پست مبدا', 'agrad-toolkit' ); ?></th>
				<td>
					<select name="source_type" required>
						<?php foreach ( $post_types as $type ) : ?>
							<option value="<?php echo esc_attr( $type->name ); ?>"><?php echo esc_html( $type->labels->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'نوع پست مقصد', 'agrad-toolkit' ); ?></th>
				<td>
					<select name="target_type" required>
						<?php foreach ( $post_types as $type ) : ?>
							<option value="<?php echo esc_attr( $type->name ); ?>"><?php echo esc_html( $type->labels->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'کپی پست‌ها', 'agrad-toolkit' ), 'primary', 'agrad_ptc_submit' ); ?>
	</form>
</div>
