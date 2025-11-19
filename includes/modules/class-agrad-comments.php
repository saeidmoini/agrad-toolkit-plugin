<?php
/**
 * Comment customisations.
 *
 * @package AgradToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agrad_Module_Comments {

	/**
	 * Bootstrap module.
	 */
	public static function init() {
		add_filter( 'comment_form_defaults', array( __CLASS__, 'override_defaults' ) );
		add_filter( 'comment_form_default_fields', array( __CLASS__, 'custom_fields' ) );
		add_filter( 'preprocess_comment', array( __CLASS__, 'require_author_name' ) );
		add_filter( 'get_comment_author', array( __CLASS__, 'maybe_use_display_name' ) );
		add_filter( 'comment_form_logged_in', '__return_empty_string' );
	}

	/**
	 * Customise default comment form labels.
	 *
	 * @param array $defaults Defaults.
	 * @return array
	 */
	public static function override_defaults( $defaults ) {
		$defaults['title_reply'] = __( 'پرسش و پاسخ', 'agrad-toolkit' );
		return $defaults;
	}

	/**
	 * Override form fields.
	 *
	 * @param array $fields Fields.
	 * @return array
	 */
	public static function custom_fields( $fields ) {
		$commenter = wp_get_current_commenter();

		$fields['author'] = sprintf(
			'<p class="comment-form-author"><label for="author">%1$s</label><span class="required"> *</span><input id="author" name="author" placeholder="%2$s" type="text" value="%3$s" size="30" required /></p>',
			esc_html__( 'Name', 'agrad-toolkit' ),
			esc_attr__( 'فارسی و الزامی', 'agrad-toolkit' ),
			esc_attr( $commenter['comment_author'] )
		);

		$fields['email'] = sprintf(
			'<p class="comment-form-email"><label for="email">%1$s</label><input id="email" name="email" placeholder="%2$s" type="text" value="%3$s" size="30" /></p>',
			esc_html__( 'Email', 'agrad-toolkit' ),
			esc_attr__( 'جهت اطلاع رسانی', 'agrad-toolkit' ),
			esc_attr( $commenter['comment_author_email'] )
		);

		if ( isset( $fields['url'] ) ) {
			unset( $fields['url'] );
		}

		return $fields;
	}

	/**
	 * Require an author name.
	 *
	 * @param array $commentdata Data.
	 * @return array
	 */
	public static function require_author_name( $commentdata ) {
		if ( empty( $commentdata['comment_author'] ) ) {
			wp_die(
				sprintf(
					'<p>%s</p><hr><a href="#" onclick="window.history.back();return false;" class="button">%s</a>',
					esc_html__( 'لطفا نام خود را وارد کنید', 'agrad-toolkit' ),
					esc_html__( 'بازگشت', 'agrad-toolkit' )
				)
			);
		}

		return $commentdata;
	}

	/**
	 * Use display name for registered users.
	 *
	 * @param string $author Author.
	 * @return string
	 */
	public static function maybe_use_display_name( $author ) {
		global $comment;

		if ( ! empty( $comment->user_id ) ) {
			$user = get_userdata( $comment->user_id );
			if ( $user ) {
				$author = $user->display_name;
			}
		}

		return $author;
	}
}
