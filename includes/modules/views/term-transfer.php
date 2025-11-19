<?php
/**
 * Term transfer view.
 *
 * @package AgradToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'انتقال ترم محصولات', 'agrad-toolkit' ); ?></h1>
	<form method="post">
		<?php wp_nonce_field( 'agrad_term_transfer_action', 'agrad_term_transfer_nonce' ); ?>

		<h2><?php esc_html_e( 'منبع', 'agrad-toolkit' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Taxonomy منبع', 'agrad-toolkit' ); ?></th>
				<td>
					<select name="source_tax" id="agrad_source_tax">
						<option value=""><?php esc_html_e( 'انتخاب کنید', 'agrad-toolkit' ); ?></option>
						<?php foreach ( $taxonomies as $tax ) : ?>
							<option value="<?php echo esc_attr( $tax->name ); ?>"><?php echo esc_html( $tax->label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'ترم منبع', 'agrad-toolkit' ); ?></th>
				<td>
					<select name="source_term" id="agrad_source_term">
						<option value=""><?php esc_html_e( 'ابتدا taxonomy را انتخاب کنید', 'agrad-toolkit' ); ?></option>
					</select>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'مقصد', 'agrad-toolkit' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Taxonomy مقصد', 'agrad-toolkit' ); ?></th>
				<td>
					<select name="target_tax" id="agrad_target_tax">
						<option value=""><?php esc_html_e( 'انتخاب کنید', 'agrad-toolkit' ); ?></option>
						<?php foreach ( $taxonomies as $tax ) : ?>
							<option value="<?php echo esc_attr( $tax->name ); ?>"><?php echo esc_html( $tax->label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'ترم مقصد', 'agrad-toolkit' ); ?></th>
				<td>
					<select name="target_term" id="agrad_target_term">
						<option value=""><?php esc_html_e( 'ابتدا taxonomy را انتخاب کنید', 'agrad-toolkit' ); ?></option>
					</select>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'اجرا', 'agrad-toolkit' ), 'primary', 'agrad_term_transfer_submit' ); ?>
	</form>
</div>

<script type="text/javascript">
	jQuery(function($) {
		function loadTerms(taxonomy, target) {
			if (!taxonomy) {
				target.html('<option value=""><?php echo esc_js( __( 'ابتدا taxonomy را انتخاب کنید', 'agrad-toolkit' ) ); ?></option>');
				return;
			}
			target.prop('disabled', true);

			$.post(ajaxurl, {
				action: 'agrad_term_transfer_get_terms',
				taxonomy: taxonomy
			}).done(function(response) {
				target.empty();
				target.append('<option value=""><?php echo esc_js( __( 'انتخاب کنید', 'agrad-toolkit' ) ); ?></option>');
				if (response.success) {
					$.each(response.data, function(_, term) {
						target.append('<option value="' + term.term_id + '">' + term.name + '</option>');
					});
				}
			}).always(function() {
				target.prop('disabled', false);
			});
		}

		$('#agrad_source_tax').on('change', function() {
			loadTerms($(this).val(), $('#agrad_source_term'));
		});

		$('#agrad_target_tax').on('change', function() {
			loadTerms($(this).val(), $('#agrad_target_term'));
		});
	});
</script>
