<?php
/**
 * Lean Theme Loader
 *
 * Loaded by functions.php when Lean Theme is the active WordPress theme.
 * Bootstraps all theme functionality: SEO, settings, shortcodes, forms,
 * sitemaps, and template parts.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Prevent double-loading
if (defined('LEAN_THEME_LOADED')) {
    return;
}
define('LEAN_THEME_LOADED', true);

// Theme paths
define('LEAN_THEME_DIR', __DIR__);
define('LEAN_THEME_URL', get_template_directory_uri());

// ──────────────────────────────────────────────────────────────────────────────
// CORE FUNCTIONALITY
// ──────────────────────────────────────────────────────────────────────────────

// Custom Post Types (Testimonials)
require_once LEAN_THEME_DIR . '/inc/cpts.php';

// SEO: Meta box, admin columns, frontend output
require_once LEAN_THEME_DIR . '/inc/seo.php';

// Declarative WebMCP helpers (must load before forms.php — the form renderer calls these)
require_once LEAN_THEME_DIR . '/inc/lean-webmcp.php';

// Contact form system (shortcode, handler, admin viewer)
require_once LEAN_THEME_DIR . '/inc/forms.php';

// Business settings & shortcodes (name, phone, address, colors)
require_once LEAN_THEME_DIR . '/inc/settings.php';

// Custom XML sitemaps
require_once LEAN_THEME_DIR . '/inc/sitemaps.php';

// Disable features (feeds, search)
require_once LEAN_THEME_DIR . '/inc/disable-features.php';

// Default menu + News page setup on theme activation
require_once LEAN_THEME_DIR . '/inc/menu-setup.php';

// Mega-menu walker for multi-column nav dropdowns
require_once LEAN_THEME_DIR . '/inc/mega-menu-walker.php';

// Performance: LCP fetchpriority on hero image
require_once LEAN_THEME_DIR . '/inc/performance.php';

// Performance: auto-import tuned Perfmatters settings on theme activation
require_once LEAN_THEME_DIR . '/inc/perfmatters-defaults.php';

// ──────────────────────────────────────────────────────────────────────────────
// SHORTCODES
// ──────────────────────────────────────────────────────────────────────────────

require_once LEAN_THEME_DIR . '/inc/shortcodes.php';

// ──────────────────────────────────────────────────────────────────────────────
// LEAN HEAD HOOKS
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Output WP Customizer "Additional CSS" on lean pages via lean_head hook
 */
add_action('lean_head', 'lean_output_customizer_css');
function lean_output_customizer_css() {
	$custom_css = wp_get_custom_css();
	if ($custom_css) {
		echo '<style id="wp-custom-css">' . strip_tags($custom_css) . '</style>' . "\n";
	}
}


// ──────────────────────────────────────────────────────────────────────────────
// THEME SETUP
// ──────────────────────────────────────────────────────────────────────────────

add_action('after_setup_theme', 'lean_theme_setup');
function lean_theme_setup() {
	// Add support for post thumbnails
	add_theme_support('post-thumbnails');

	// Let WordPress manage the <title> tag
	add_theme_support('title-tag');

	// HTML5 markup for core elements
	add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);

	// Register navigation menus
	register_nav_menus([
		'primary' => 'Primary Navigation',
		'footer'  => 'Footer Navigation',
	]);
}

// ──────────────────────────────────────────────────────────────────────────────
// TEMPLATE PART HELPER
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Include a template part by slug relative to the theme directory.
 * Equivalent to get_template_part() in standalone mode; kept as a named
 * helper so template parts can be relocated without touching call sites.
 */
function lean_get_template_part($slug) {
	$file = LEAN_THEME_DIR . '/' . $slug . '.php';
	if (file_exists($file)) {
		include $file;
	}
}
