<?php
/**
 * Copy posts between post types.
 *
 * @package AgradToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agrad_Module_Post_Type_Copy {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_page' ) );
	}

	/**
	 * Register submenu.
	 */
	public static function register_page() {
		add_submenu_page(
			'agrad-toolkit',
			__( 'کپی پست‌ها', 'agrad-toolkit' ),
			__( 'کپی پست تایپ', 'agrad-toolkit' ),
			'manage_options',
			'agrad-post-type-copy',
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

		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		if ( isset( $_POST['agrad_ptc_submit'] ) ) {
			check_admin_referer( 'agrad_ptc_action', 'agrad_ptc_nonce' );

			$source = sanitize_text_field( wp_unslash( $_POST['source_type'] ) );
			$target = sanitize_text_field( wp_unslash( $_POST['target_type'] ) );

			if ( $source && $target ) {
				self::copy_posts( $source, $target );
				echo '<div class="notice notice-success"><p>' . esc_html__( 'پست‌ها با موفقیت کپی شدند.', 'agrad-toolkit' ) . '</p></div>';
			}
		}

		include __DIR__ . '/views/post-type-copy.php';
	}

	/**
	 * Perform the copy.
	 *
	 * @param string $source Source post type.
	 * @param string $target Target post type.
	 */
	protected static function copy_posts( $source, $target ) {
		$posts = get_posts(
			array(
				'post_type'      => $source,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			)
		);

		foreach ( $posts as $post ) {
			$new_post = array(
				'post_title'   => $post->post_title,
				'post_content' => $post->post_content,
				'post_excerpt' => $post->post_excerpt,
				'post_status'  => 'publish',
				'post_type'    => $target,
				'post_author'  => $post->post_author,
			);

			$new_post_id = wp_insert_post( $new_post );

			$meta = get_post_meta( $post->ID );
			foreach ( $meta as $key => $values ) {
				foreach ( $values as $value ) {
					add_post_meta( $new_post_id, $key, maybe_unserialize( $value ) );
				}
			}

			if ( has_post_thumbnail( $post->ID ) ) {
				set_post_thumbnail( $new_post_id, get_post_thumbnail_id( $post->ID ) );
			}

			$taxonomies = get_object_taxonomies( $source );
			foreach ( $taxonomies as $taxonomy ) {
				$terms = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );
				wp_set_object_terms( $new_post_id, $terms, $taxonomy );
			}
		}
	}
}
