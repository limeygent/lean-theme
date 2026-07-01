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

// Custom Post Types (Testimonials) are registered by the required NerdPress SEO
// plugin, which ships `testimonials` as a built-in default. The [testimonials]
// shortcode (inc/shortcodes/testimonials.php) queries it.

// SEO (meta box, admin columns, front-end <head>) and XML sitemaps are provided
// by the REQUIRED NerdPress SEO plugin — the theme no longer loads its legacy
// inc/seo.php or inc/sitemaps.php. Warn if the plugin is missing.
add_action('admin_notices', 'lean_require_nerdpress_notice');
function lean_require_nerdpress_notice() {
	if ( defined('NERDPRESS_VERSION') ) {
		return;
	}
	echo '<div class="notice notice-error"><p><strong>Lean Theme:</strong> the <strong>NerdPress SEO</strong> plugin is required for SEO meta tags and XML sitemaps — please install and activate it.</p></div>';
}

// Declarative WebMCP helpers (must load before forms.php — the form renderer calls these)
require_once LEAN_THEME_DIR . '/inc/lean-webmcp.php';

// Contact form system (shortcode, handler, admin viewer)
require_once LEAN_THEME_DIR . '/inc/forms.php';

// Business settings & shortcodes (name, phone, address, colors)
require_once LEAN_THEME_DIR . '/inc/settings.php';

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
 * Front-end <head> SEO fallback for when the NerdPress SEO plugin is missing,
 * inactive, or only half-loaded. Lean templates bypass wp_head(), so the plugin
 * is the ONLY source of <title>/description/canonical/robots on those pages —
 * without this, a plugin-less site would ship pages with an empty <head>.
 *
 * Keyed on the plugin's own output function rather than a version constant, so a
 * plugin that failed to fully load (missing include) still degrades gracefully.
 * When the plugin is present it owns the head block via its own lean_head hook
 * and this stays silent, so there are never duplicate tags.
 */
add_action('lean_head', 'lean_fallback_head_seo', 1);
function lean_fallback_head_seo() {
	if (function_exists('nerdpress_output_lean_head')) {
		return; // plugin present and functional — it owns the head
	}

	echo '<title>' . esc_html(wp_get_document_title()) . '</title>' . "\n";

	if (is_singular()) {
		$queried = get_queried_object();
		if ($queried instanceof WP_Post) {
			$desc = has_excerpt($queried)
				? get_the_excerpt($queried)
				: wp_trim_words(wp_strip_all_tags($queried->post_content), 30, '…');
			if ($desc !== '') {
				echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
			}
			echo '<link rel="canonical" href="' . esc_url(get_permalink($queried)) . '">' . "\n";
		}
	}

	echo '<meta name="robots" content="' . (get_option('blog_public') ? 'index, follow' : 'noindex, nofollow') . '">' . "\n";
}

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
