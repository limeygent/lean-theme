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

// Custom Post Types (Testimonials)
require_once get_template_directory() . '/inc/cpts.php';

// SEO: Meta box, admin columns, frontend output
require_once get_template_directory() . '/inc/seo.php';

// Contact form system (shortcode, handler, admin viewer)
require_once get_template_directory() . '/inc/forms.php';

// Business settings & shortcodes (name, phone, address, colors)
require_once get_template_directory() . '/inc/settings.php';

// Custom XML sitemaps
require_once get_template_directory() . '/inc/sitemaps.php';

// Disable features (feeds, search)
require_once get_template_directory() . '/inc/disable-features.php';

// ──────────────────────────────────────────────────────────────────────────────
// MIGRATIONS (remove after use)
// ──────────────────────────────────────────────────────────────────────────────

// Yoast SEO → Lean SEO migration tool (Tools > Yoast → Lean SEO)
// Comment out or delete after migration is complete
require_once get_template_directory() . '/inc/migrations/yoast-to-lean-seo.php';

// ──────────────────────────────────────────────────────────────────────────────
// SHORTCODES (modular loader - comment out individual files in shortcodes.php)
// ──────────────────────────────────────────────────────────────────────────────

require_once get_template_directory() . '/inc/shortcodes.php';

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
