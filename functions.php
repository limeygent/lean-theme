<?php
/**
 * Lean Theme - Functions
 *
 * Minimal loader for theme functionality.
 * All features are organized in /inc/ directory.
 */

// ──────────────────────────────────────────────────────────────────────────────
// CORE FUNCTIONALITY
// ──────────────────────────────────────────────────────────────────────────────

// Custom Post Types (FAQ, Testimonials, Locations, Jobs)
require_once get_template_directory() . '/inc/cpts.php';

// SEO: Meta box, admin columns, frontend output
require_once get_template_directory() . '/inc/seo.php';

// Contact form system (shortcode, handler, admin viewer)
require_once get_template_directory() . '/inc/forms.php';

// Business settings & shortcodes (name, phone, address, colors)
require_once get_template_directory() . '/inc/settings.php';

// Custom XML sitemaps
require_once get_template_directory() . '/inc/sitemaps.php';

// Location CPT rewrite rules (city pages, service pages)
require_once get_template_directory() . '/inc/locations.php';

// Disable features (feeds, search)
require_once get_template_directory() . '/inc/disable-features.php';

// ──────────────────────────────────────────────────────────────────────────────
// SHORTCODES
// ──────────────────────────────────────────────────────────────────────────────

// Blog: featured image, review notice, interlinks, latest post, template
require_once get_template_directory() . '/inc/shortcodes/blog.php';

// [testimonials] - Display testimonials with Schema.org markup
require_once get_template_directory() . '/inc/shortcodes/testimonials.php';

// [faq_list] - Display FAQs using <details> elements
require_once get_template_directory() . '/inc/shortcodes/faqs.php';

// [display_jobs] - Display job portfolio cards
require_once get_template_directory() . '/inc/shortcodes/jobs.php';

// [job_submit_form] - Frontend job submission form for techs
require_once get_template_directory() . '/inc/shortcodes/job-submit.php';

// [map_embed] - Google Maps embed with GMB overlay
require_once get_template_directory() . '/inc/shortcodes/maps.php';

// ──────────────────────────────────────────────────────────────────────────────
// THEME SETUP
// ──────────────────────────────────────────────────────────────────────────────

add_action('after_setup_theme', 'lean_theme_setup');

function lean_theme_setup() {
	// Add support for post thumbnails
	add_theme_support('post-thumbnails');

	// Register navigation menu
	register_nav_menus(array(
		'primary' => 'Primary Navigation',
		'footer'  => 'Footer Navigation',
	));
}
