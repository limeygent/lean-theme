<?php
/**
 * Lean SEO Output - Frontend Meta Tags
 *
 * Code Snippet: Run everywhere (frontend)
 *
 * Outputs SEO meta tags in two ways:
 * 1. For lean templates: lean-head.php calls lean_output_seo_meta_tags() directly
 * 2. For legacy templates: Hooks into wp_head
 *
 * Outputs:
 * - <title>
 * - meta description
 * - meta keywords (for Bing)
 * - meta robots
 * - canonical URL
 * - Open Graph tags
 * - Twitter Card tags
 * - Dublin Core tags
 *
 * Reads from:
 * - _lean_meta_title
 * - _lean_meta_description
 * - _lean_meta_keywords
 * - _lean_meta_noindex
 * - _lean_meta_nofollow
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// ──────────────────────────────────────────────────────────────────────────────
// CLEANUP: Remove WP default SEO tags (we handle these ourselves)
// ──────────────────────────────────────────────────────────────────────────────

add_action('after_setup_theme', function() {
	// Remove default title tag (we output our own)
	remove_theme_support('title-tag');
	remove_action('wp_head', '_wp_render_title_tag', 1);

	// Remove oEmbed discovery links
	remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);

	// Remove REST API link
	remove_action('wp_head', 'rest_output_link_wp_head', 10);

	// Remove WP shortlink
	remove_action('wp_head', 'wp_shortlink_wp_head', 10);

	// Remove EditURI (RSD) link
	remove_action('wp_head', 'rsd_link', 10);

	// Remove default canonical (we output our own)
	remove_action('wp_head', 'rel_canonical', 10);
});

// Remove default robots meta
add_filter('wp_robots', '__return_empty_array');

// ──────────────────────────────────────────────────────────────────────────────
// OUTPUT FUNCTION: Called by lean-head.php or hooked to wp_head
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Output SEO meta tags
 * Called directly by lean-head.php for lean templates
 */
function lean_output_seo_meta_tags() {
	if (!is_singular()) {
		return;
	}

	global $post;

	// Retrieve custom meta fields
	$meta_title       = get_post_meta($post->ID, '_lean_meta_title', true);
	$meta_description = get_post_meta($post->ID, '_lean_meta_description', true);
	$meta_keywords    = get_post_meta($post->ID, '_lean_meta_keywords', true);
	$meta_noindex     = get_post_meta($post->ID, '_lean_meta_noindex', true);
	$meta_nofollow    = get_post_meta($post->ID, '_lean_meta_nofollow', true);

	// Featured image
	$meta_image = '';
	$meta_image_alt = '';
	if (has_post_thumbnail($post->ID)) {
		$meta_image = get_the_post_thumbnail_url($post->ID, 'full');
		$thumb_id = get_post_thumbnail_id($post->ID);
		$meta_image_alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
	}
	if (empty($meta_image_alt)) {
		$meta_image_alt = $meta_description;
	}

	// Fallbacks
	if (empty($meta_title)) {
		$meta_title = get_the_title($post->ID);
	} else {
		$meta_title = do_shortcode($meta_title);
	}
	if (empty($meta_description)) {
		$meta_description = wp_trim_words(wp_strip_all_tags($post->post_content), 30, '...');
	}

	// Canonical URL
	$canonical_url = get_permalink($post->ID);
	$modified_time = get_the_modified_time('c', $post->ID);

	// Build robots directives
	$robots = [];
	$robots[] = $meta_noindex ? 'noindex' : 'index';
	if (!$meta_noindex) {
		$robots[] = 'max-image-preview:large';
		$robots[] = 'max-snippet:-1';
	}
	$robots[] = $meta_nofollow ? 'nofollow' : 'follow';
	$robots_content = implode(', ', $robots);

	// Site name
	$site_name = get_option('business_name', get_bloginfo('name'));

	// Output
	echo PHP_EOL . '<!-- Lean SEO Meta Tags -->' . PHP_EOL;
	echo '<title>' . esc_html($meta_title) . '</title>' . PHP_EOL;
	echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . PHP_EOL;
	if (!empty($meta_keywords) && trim($meta_keywords)) {
		echo '<meta name="keywords" content="' . esc_attr(trim($meta_keywords)) . '">' . PHP_EOL;
	}
	echo '<meta name="robots" content="' . esc_attr($robots_content) . '">' . PHP_EOL;
	echo '<link rel="canonical" href="' . esc_url($canonical_url) . '">' . PHP_EOL;
	echo '<meta property="article:modified_time" content="' . esc_attr($modified_time) . '">' . PHP_EOL;

	// Dublin Core
	echo PHP_EOL . '<!-- Dublin Core -->' . PHP_EOL;
	echo '<meta name="dc.title" content="' . esc_attr($meta_title) . '">' . PHP_EOL;
	echo '<meta name="dc.description" content="' . esc_attr($meta_description) . '">' . PHP_EOL;
	echo '<meta name="dc.language" content="en_US">' . PHP_EOL;
	if (!empty($meta_keywords) && trim($meta_keywords)) {
		echo '<meta name="dc.keywords" content="' . esc_attr(trim($meta_keywords)) . '">' . PHP_EOL;
	}

	// Open Graph
	echo PHP_EOL . '<!-- Open Graph -->' . PHP_EOL;
	echo '<meta property="og:title" content="' . esc_attr($meta_title) . '">' . PHP_EOL;
	echo '<meta property="og:description" content="' . esc_attr($meta_description) . '">' . PHP_EOL;
	echo '<meta property="og:locale" content="en_US">' . PHP_EOL;
	echo '<meta property="og:type" content="article">' . PHP_EOL;
	echo '<meta property="og:url" content="' . esc_url($canonical_url) . '">' . PHP_EOL;
	if (!empty($meta_image)) {
		echo '<meta property="og:image" content="' . esc_url($meta_image) . '">' . PHP_EOL;
		echo '<meta property="og:image:alt" content="' . esc_attr($meta_image_alt) . '">' . PHP_EOL;
	}
	echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . PHP_EOL;

	// Twitter Card
	echo PHP_EOL . '<!-- Twitter Card -->' . PHP_EOL;
	echo '<meta name="twitter:card" content="summary_large_image">' . PHP_EOL;
	echo '<meta name="twitter:title" content="' . esc_attr($meta_title) . '">' . PHP_EOL;
	echo '<meta name="twitter:description" content="' . esc_attr($meta_description) . '">' . PHP_EOL;
	if (!empty($meta_image)) {
		echo '<meta name="twitter:image" content="' . esc_url($meta_image) . '">' . PHP_EOL;
	}
	echo '<!-- End Lean SEO -->' . PHP_EOL . PHP_EOL;
}

// ──────────────────────────────────────────────────────────────────────────────
// WP_HEAD HOOK: For legacy (non-lean) templates
// ──────────────────────────────────────────────────────────────────────────────

add_action('wp_head', 'lean_output_seo_meta_tags_wphead', 1);

function lean_output_seo_meta_tags_wphead() {
	// Only output if we're on a singular page/post
	if (!is_singular()) {
		return;
	}

	// Check if Yoast is active - if so, let Yoast handle it
	// Remove this check once Yoast is fully deactivated
	if (defined('WPSEO_VERSION')) {
		return;
	}

	lean_output_seo_meta_tags();
}
