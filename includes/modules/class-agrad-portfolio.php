<?php
/**
 * Portfolio CPT module.
 *
 * @package AgradToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agrad_Module_Portfolio {

	/**
	 * Register portfolio CPT.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
	}

	/**
	 * CPT registration borrowed from legacy plugin.
	 */
	public static function register_post_type() {
		$labels = array(
			'name'               => _x( 'Portfolio', 'Post Type General Name', 'agrad-toolkit' ),
			'singular_name'      => _x( 'Portfolio', 'Post Type Singular Name', 'agrad-toolkit' ),
			'menu_name'          => __( 'Portfolio', 'agrad-toolkit' ),
			'parent_item_colon'  => __( 'Parent Portfolio', 'agrad-toolkit' ),
			'all_items'          => __( 'All Portfolios', 'agrad-toolkit' ),
			'view_item'          => __( 'View Portfolio', 'agrad-toolkit' ),
			'add_new_item'       => __( 'Add New Portfolio', 'agrad-toolkit' ),
			'add_new'            => __( 'Add New', 'agrad-toolkit' ),
			'edit_item'          => __( 'Edit Portfolio', 'agrad-toolkit' ),
			'update_item'        => __( 'Update Portfolio', 'agrad-toolkit' ),
			'search_items'       => __( 'Search Portfolio', 'agrad-toolkit' ),
			'not_found'          => __( 'Not Found', 'agrad-toolkit' ),
			'not_found_in_trash' => __( 'Not found in Trash', 'agrad-toolkit' ),
		);

		$args = array(
			'label'               => __( 'Portfolio', 'agrad-toolkit' ),
			'description'         => __( 'Portfolio items', 'agrad-toolkit' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields' ),
			'taxonomies'          => array( 'genres' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 5,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
			'show_in_rest'        => true,
		);

		register_post_type( 'portfolio', $args );
	}
}
