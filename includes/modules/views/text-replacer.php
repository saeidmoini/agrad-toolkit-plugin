<?php
/**
 * Text replacer admin view.
 *
 * @package AgradToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Global Text Replacer', 'agrad-toolkit' ); ?></h1>

	<?php if ( isset( $_GET['message'] ) && 'success' === $_GET['message'] ) : ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'عملیات جایگزینی انجام شد.', 'agrad-toolkit' ); ?></p></div>
	<?php elseif ( isset( $_GET['message'] ) && 'error' === $_GET['message'] ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'داده ارسالی معتبر نبود.', 'agrad-toolkit' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'agrad_text_replace', 'agrad_text_nonce' ); ?>
		<input type="hidden" name="action" value="agrad_replace_text" />

		<table class="form-table">
			<tr>
				<th scope="row"><label for="search_text"><?php esc_html_e( 'متن مورد جستجو', 'agrad-toolkit' ); ?></label></th>
				<td><input type="text" name="search_text" id="search_text" class="regular-text" required /></td>
			</tr>
			<tr>
				<th scope="row"><label for="replace_text"><?php esc_html_e( 'متن جایگزین', 'agrad-toolkit' ); ?></label></th>
				<td><input type="text" name="replace_text" id="replace_text" class="regular-text" required /></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'محدوده تغییر', 'agrad-toolkit' ); ?></th>
				<td>
					<label><input type="radio" name="scope" value="global" checked /> <?php esc_html_e( 'کل سایت', 'agrad-toolkit' ); ?></label><br />
					<label><input type="radio" name="scope" value="page" /> <?php esc_html_e( 'فقط یک صفحه', 'agrad-toolkit' ); ?></label>
				</td>
			</tr>
			<tr id="agrad_page_url_row" style="display:none;">
				<th scope="row"><label for="page_url"><?php esc_html_e( 'آدرس صفحه', 'agrad-toolkit' ); ?></label></th>
				<td><input type="url" name="page_url" id="page_url" class="regular-text" placeholder="https://example.com/sample-page" /></td>
			</tr>
		</table>

		<?php submit_button( __( 'جایگزینی متن', 'agrad-toolkit' ) ); ?>
	</form>

	<hr />
	<h2><?php esc_html_e( 'گزارش تغییرات', 'agrad-toolkit' ); ?></h2>
	<?php if ( ! empty( $logs ) ) : ?>
		<table class="widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'تاریخ', 'agrad-toolkit' ); ?></th>
					<th><?php esc_html_e( 'متن جستجو', 'agrad-toolkit' ); ?></th>
					<th><?php esc_html_e( 'متن جایگزین', 'agrad-toolkit' ); ?></th>
					<th><?php esc_html_e( 'تعداد تغییرات', 'agrad-toolkit' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $logs as $log ) : ?>
					<tr>
						<td><?php echo esc_html( $log['date'] ); ?></td>
						<td><?php echo esc_html( $log['search'] ); ?></td>
						<td><?php echo esc_html( $log['replace'] ); ?></td>
						<td><?php echo esc_html( $log['count'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p><?php esc_html_e( 'هنوز لاگی ثبت نشده است.', 'agrad-toolkit' ); ?></p>
	<?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const radios = document.querySelectorAll('input[name="scope"]');
	const row = document.getElementById('agrad_page_url_row');
	const toggle = () => {
		const checked = document.querySelector('input[name="scope"]:checked').value;
		row.style.display = (checked === 'page') ? 'table-row' : 'none';
	};
	radios.forEach((radio) => radio.addEventListener('change', toggle));
	toggle();
});
</script>
