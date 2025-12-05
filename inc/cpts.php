<?php
/**
 * Filename: register-cpts.php
 * Purpose: Register custom post types (FAQ, Testimonials, Locations)
 *
 * Usage: Include in functions.php or require directly
 *   require_once get_template_directory() . '/register-cpts.php';
 */

add_action( 'init', 'lean_register_custom_post_types' );

function lean_register_custom_post_types() {

	/**
	 * Post Type: FAQs
	 * Public, queryable, no archive
	 */
	register_post_type( 'faq', [
		'label'               => 'FAQs',
		'labels'              => [
			'name'          => 'FAQs',
			'singular_name' => 'FAQ',
		],
		'public'              => true,
		'publicly_queryable'  => true,
		'show_ui'             => true,
		'show_in_rest'        => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => true,
		'has_archive'         => false,
		'exclude_from_search' => false,
		'capability_type'     => 'post',
		'map_meta_cap'        => true,
		'hierarchical'        => false,
		'rewrite'             => [ 'slug' => 'faq', 'with_front' => true ],
		'query_var'           => true,
		'menu_icon'           => 'dashicons-format-status',
		'supports'            => [ 'title', 'editor' ],
	]);

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

	/**
	 * Post Type: Locations
	 * Private but queryable (for shortcodes/templates)
	 */
	register_post_type( 'locations', [
		'label'               => 'Locations',
		'labels'              => [
			'name'          => 'Locations',
			'singular_name' => 'Location',
		],
		'public'              => false,
		'publicly_queryable'  => true,
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
		'menu_icon'           => 'dashicons-location',
		'supports'            => [ 'title', 'editor', 'thumbnail', 'custom-fields' ],
	]);

	/**
	 * Post Type: Jobs
	 * Work showcase - techs submit completed job photos/descriptions
	 * for use as portfolio examples on service pages.
	 * Frontend submission via [job_submit_form] shortcode.
	 */
	register_post_type( 'job', [
		'label'               => 'Jobs',
		'labels'              => [
			'name'               => 'Jobs',
			'singular_name'      => 'Job',
			'menu_name'          => 'Jobs',
			'all_items'          => 'All Jobs',
			'add_new'            => 'Add Job',
			'add_new_item'       => 'Add New Job',
			'edit_item'          => 'Edit Job',
			'new_item'           => 'New Job',
			'view_item'          => 'View Job',
			'view_items'         => 'View Jobs',
			'search_items'       => 'Search Jobs',
			'not_found'          => 'No jobs found',
			'not_found_in_trash' => 'No jobs found in Trash',
		],
		'public'              => true,
		'publicly_queryable'  => true,
		'show_ui'             => true,
		'show_in_rest'        => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => true,
		'has_archive'         => true,
		'exclude_from_search' => false,
		'capability_type'     => 'post',
		'map_meta_cap'        => true,
		'hierarchical'        => false,
		'rewrite'             => [ 'slug' => 'jobs', 'with_front' => false ],
		'query_var'           => true,
		'menu_icon'           => 'dashicons-hammer',
		'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ],
	]);
}
