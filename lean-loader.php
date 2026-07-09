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

// Blog roll: batch size, "Load more" endpoint, and its script (used by home.php)
require_once LEAN_THEME_DIR . '/inc/blog-roll.php';

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

	// The paginated blog roll prints its own noindex (inc/blog-roll.php) on a later
	// priority; skip here so those URLs never carry two robots tags.
	if (!lean_blog_roll_is_noindex()) {
		echo '<meta name="robots" content="' . (get_option('blog_public') ? 'index, follow' : 'noindex, nofollow') . '">' . "\n";
	}
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
// TEMPLATE HELPERS
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Returns the BCP 47 language code for the current page, used by header.php for
 * the <html lang> attribute. Per-page meta wins; falls back to the theme's
 * `lean_default_language` option; falls back to en-US.
 *
 * Theme-owned on purpose: the SEO→NerdPress migration retired inc/seo.php (where
 * this used to live), but the <html lang> attribute is a theme-template concern,
 * not an SEO-plugin one. This reads only DB values (option + post meta) via core
 * WP functions, so it carries no dependency on the plugin.
 */
function lean_get_page_language() {
	if (is_singular()) {
		$override = get_post_meta(get_queried_object_id(), '_lean_meta_language', true);
		if (!empty($override)) {
			return $override;
		}
	}
	return get_option('lean_default_language', 'en-US');
}

/**
 * Collapse whitespace and cut to at most $chars characters on a word boundary.
 *
 * 160 is the meta-description budget, so a well-formed description passes through
 * untouched and the card shows it in full. Only the excerpt/content fallbacks are
 * long enough to actually get cut.
 *
 * Uses mb_substr/mb_strlen (WordPress shims both). strrpos on a space is safe on
 * UTF-8 because 0x20 never occurs inside a multi-byte sequence.
 */
function lean_trim_chars($text, $chars = 160, $more = '…') {
	$text = trim(preg_replace('/\s+/u', ' ', (string) $text));
	if ('' === $text || mb_strlen($text) <= $chars) {
		return $text;
	}

	$cut = mb_substr($text, 0, $chars);
	$space = strrpos($cut, ' ');
	if (false !== $space && $space > 0) {
		$cut = substr($cut, 0, $space);
	}

	return rtrim($cut, " \t\n\r\0\x0B.,;:–—-") . $more;
}

/**
 * The summary shown beneath a post's featured image in listing grids.
 *
 * Prefers the NerdPress SEO meta description, so a card and its search snippet
 * say the same thing. Asks the plugin for its own key rather than hardcoding
 * one: the prefix is operator-configurable (NerdPress SEO → Settings → Meta
 * field prefix), and sites migrated off the retired inc/seo.php still store
 * descriptions under `_lean_meta_description`.
 *
 * Keep the function_exists() guard. This runs once per card, and a deactivated
 * plugin would otherwise fatal the entire blog roll — the same failure mode as
 * the lean_get_page_language() regression above.
 *
 * Returns raw text; escape at the point of output.
 */
function lean_post_summary($post_id = null, $chars = 160) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	if (!$post_id) {
		return '';
	}

	if (function_exists('nerdpress_meta_key')) {
		$description = get_post_meta($post_id, nerdpress_meta_key('description'), true);
		if (!empty($description)) {
			// A description written to the 160-char budget is returned whole.
			return lean_trim_chars($description, $chars);
		}
	}

	// A password-protected post is still `publish`, so it appears in listings. Stop
	// before the excerpt and content fallbacks: get_post_field() reads the gated
	// body straight out of the DB with none of the protection that core's
	// get_the_content()/get_the_excerpt() apply, which would publish the first
	// $words of it to anyone. An author-written meta description is fine — it is
	// already public in the <head> — so it is resolved above this line.
	if (post_password_required($post_id)) {
		return '';
	}

	// Manual excerpts may legitimately contain markup. The card escapes with
	// esc_html(), so leaving tags in would render them as visible tag soup.
	if (has_excerpt($post_id)) {
		return lean_trim_chars(wp_strip_all_tags(get_the_excerpt($post_id)), $chars);
	}

	$content = strip_shortcodes(get_post_field('post_content', $post_id));

	// Headings make poor summary prose, and most posts open with their own title as
	// an <h1>/<h2>, which would render the card as "Title Title Body…". Drop heading
	// elements outright before flattening the rest.
	$stripped = preg_replace('#<h[1-6]\b[^>]*>.*?</h[1-6]>#is', ' ', $content);
	if (null !== $stripped) {
		$content = $stripped;
	}

	$text = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags($content)));

	// Belt and braces: catch a title repeated as plain text rather than as a heading.
	// The boundary check matters — without it the title "Furnace Repair" would eat
	// the first words of a body reading "Furnace repairs are seasonal work."
	$title = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags(get_the_title($post_id))));
	if ('' !== $title) {
		$pattern = '/^' . preg_quote($title, '/') . '(?![\p{L}\p{N}])[\s\p{P}]*/iu';
		$trimmed = preg_replace($pattern, '', $text, 1);
		if (null !== $trimmed) {
			$text = $trimmed;
		}
	}

	return lean_trim_chars($text, $chars);
}

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
