<?php
/**
 * Filename: cpts.php
 * Purpose: Register custom post types (Testimonials)
 *
 *
 * Usage: Include in functions.php or require directly
 *   require_once get_template_directory() . '/inc/cpts.php';
 */

add_action( 'init', 'lean_register_custom_post_types' );

function lean_register_custom_post_types() {

	/**
	 * Post Type: Testimonials
	 * Private (admin only), not queryable on frontend
	 */
	register_post_type( 'testimonials', [
		'label'               => 'Testimonials',
		'labels'              => [
			'name'          => 'Testimonials',
			'singular_name' => 'Testimonial',
		],
		'public'              => false,
		'publicly_queryable'  => false,
		'show_ui'             => true,
		'show_in_rest'        => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => true,
		'has_archive'         => false,
		'exclude_from_search' => false,
		'capability_type'     => 'post',
		'map_meta_cap'        => true,
		'hierarchical'        => false,
		'rewrite'             => false,
		'query_var'           => true,
		'menu_icon'           => 'dashicons-format-quote',
		'supports'            => [ 'title', 'editor' ],
	]);
}
