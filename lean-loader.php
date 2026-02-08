<?php
/**
 * Lean Theme Loader
 *
 * Include this file from any theme's functions.php to add Lean functionality.
 *
 * Usage in existing theme:
 *   require_once get_template_directory() . '/lean/lean-loader.php';
 *
 * Usage as standalone theme:
 *   Already included by functions.php
 *
 * The folder can be named anything (lean, lean-theme, etc.) - paths are detected automatically.
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

// Define the path to the lean theme directory (works from any location)
define('LEAN_THEME_DIR', __DIR__);

// Check if we're the main theme or included in another theme
define('LEAN_IS_STANDALONE', realpath(get_template_directory()) === realpath(LEAN_THEME_DIR));

// URL path depends on whether we're standalone or integrated
if (LEAN_IS_STANDALONE) {
    define('LEAN_THEME_URL', get_template_directory_uri());
} else {
    // Calculate relative path from theme root to lean-theme directory
    $relative_path = str_replace(realpath(get_template_directory()), '', realpath(LEAN_THEME_DIR));
    $relative_path = ltrim(str_replace('\\', '/', $relative_path), '/');
    define('LEAN_THEME_URL', get_template_directory_uri() . '/' . $relative_path);
}

// ──────────────────────────────────────────────────────────────────────────────
// CORE FUNCTIONALITY
// ──────────────────────────────────────────────────────────────────────────────

// Custom Post Types (Testimonials)
require_once LEAN_THEME_DIR . '/inc/cpts.php';

// SEO: Meta box, admin columns, frontend output
require_once LEAN_THEME_DIR . '/inc/seo.php';

// Contact form system (shortcode, handler, admin viewer)
require_once LEAN_THEME_DIR . '/inc/forms.php';

// Business settings & shortcodes (name, phone, address, colors)
require_once LEAN_THEME_DIR . '/inc/settings.php';

// Custom XML sitemaps
require_once LEAN_THEME_DIR . '/inc/sitemaps.php';

// Disable features (feeds, search)
require_once LEAN_THEME_DIR . '/inc/disable-features.php';

// ──────────────────────────────────────────────────────────────────────────────
// MIGRATIONS (remove after use)
// ──────────────────────────────────────────────────────────────────────────────

// Yoast SEO → Lean SEO migration tool (Tools > Yoast → Lean SEO)
// Comment out or delete after migration is complete
require_once LEAN_THEME_DIR . '/inc/migrations/yoast-to-lean-seo.php';

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

/**
 * Output Gutenberg block CSS on lean pages via lean_head hook
 */
add_action('lean_head', 'lean_output_block_styles');
function lean_output_block_styles() {
	$block_css_path = ABSPATH . WPINC . '/css/dist/block-library/style.min.css';
	if (file_exists($block_css_path)) {
		$ver = filemtime($block_css_path);
		echo '<link rel="stylesheet" href="' . esc_url(includes_url('css/dist/block-library/style.min.css') . '?ver=' . $ver) . '" id="wp-block-library-css">' . "\n";
	}
}

// ──────────────────────────────────────────────────────────────────────────────
// THEME SETUP (only when running as standalone theme)
// ──────────────────────────────────────────────────────────────────────────────

if (LEAN_IS_STANDALONE) {
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
}

// ──────────────────────────────────────────────────────────────────────────────
// TEMPLATE REGISTRATION (works in both standalone and integrated modes)
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Helper function to include template parts from lean-theme directory
 * Use this instead of get_template_part() in lean templates
 */
function lean_get_template_part($slug) {
    $file = LEAN_THEME_DIR . '/' . $slug . '.php';
    if (file_exists($file)) {
        include $file;
    }
}

/**
 * Get the folder name for template keys (dynamic based on actual folder name)
 */
function lean_get_folder_name() {
    static $folder = null;
    if ($folder === null) {
        $folder = LEAN_IS_STANDALONE ? '' : basename(LEAN_THEME_DIR);
    }
    return $folder;
}

/**
 * Register Lean page templates in the page editor dropdown
 */
add_filter('theme_page_templates', 'lean_register_page_templates');
function lean_register_page_templates($templates) {
    $folder = lean_get_folder_name();
    $prefix = $folder ? $folder . '/' : '';

    $templates[$prefix . 'template-parts/page-lean.php'] = 'Lean Page';
    return $templates;
}

/**
 * Map template keys to actual file paths
 */
function lean_get_template_map() {
    $folder = lean_get_folder_name();
    $prefix = $folder ? $folder . '/' : '';

    return array(
        $prefix . 'template-parts/page-lean.php' => LEAN_THEME_DIR . '/template-parts/page-lean.php',
    );
}

/**
 * Load the Lean template from the correct location
 */
add_filter('template_include', 'lean_template_include');
function lean_template_include($template) {
    global $post;

    if (!$post) {
        return $template;
    }

    $page_template = get_post_meta($post->ID, '_wp_page_template', true);
    $template_map = lean_get_template_map();

    // Check if a Lean template is selected
    if (isset($template_map[$page_template])) {
        $lean_template = $template_map[$page_template];
        if (file_exists($lean_template)) {
            return $lean_template;
        }
    }

    return $template;
}
