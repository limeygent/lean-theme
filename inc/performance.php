<?php
/**
 * Performance: LCP hints for the hero image.
 *
 * The hero is hand-coded as <img class="hero-bg" src="..."> (or, on some pages, a
 * <div|section class="hero-bg" style="background:url()">) inside page content, so
 * WordPress's automatic LCP fetchpriority handling (which targets attachment images)
 * never sees it. This file supplies the two LCP hints PSI wants:
 *
 *   1. <link rel="preload" as="image" fetchpriority="high"> in <head> so the LCP image
 *      is discovered immediately instead of mid-parse.
 *   2. fetchpriority="high" (and no loading="lazy") on the hero <img> itself.
 *
 * DETECTION vs EMISSION — one source of truth, two emit points.
 * `lean_hero_image_url()` is the ONE place that resolves the hero URL from content.
 * Where the preload TAG is echoed depends on how the current request renders <head>:
 *   - Standalone (Lean is the active theme): header.php -> template-parts/lean-head.php
 *     renders <head> and NEVER calls wp_head(). lean-head.php calls the helper and emits
 *     the preload inline, early — before the stylesheets, where the preload scanner wants
 *     it. The wp_head hook below never fires in this mode.
 *   - Integration (Lean embedded as /lean/ in a parent theme): the PARENT theme's
 *     header.php calls wp_head(), and lean-head.php is not used. The wp_head action below
 *     is then the emit point.
 * The modes are mutually exclusive on which head renders, and lean_emit_hero_preload()
 * carries a once-per-request guard, so the tag can never be emitted twice.
 *
 * The fetchpriority stamp (lean_hero_bg_lcp_hints) runs on the_content, so it applies in
 * BOTH modes regardless of the head mechanism.
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Resolve the LCP hero image URL from a post's saved content, for the two in-content hero
 * forms the theme supports. Returns '' when neither is present.
 *
 * ACF / query-var heroes are resolved by the caller (lean-head.php) since those are
 * template-context specific; this helper only reads post_content.
 *
 *   1. <img class="hero-bg" src="..."> .................... most page heroes
 *   2. <div|section class="hero-bg" style="...url()..."> ... CSS-background hero
 *
 * @param WP_Post|mixed $post
 * @return string Hero image URL, or '' if none found.
 */
function lean_hero_image_url($post) {
	if (!($post instanceof WP_Post) || strpos($post->post_content, 'hero-bg') === false) {
		return '';
	}
	// (1) <img class="hero-bg" src="..."> — attribute order-independent.
	if (preg_match('/<img\b[^>]*\bhero-bg\b[^>]*>/i', $post->post_content, $tag)
		&& preg_match('/\bsrc\s*=\s*["\']([^"\']+\.(?:webp|avif|jpe?g|png))/i', $tag[0], $m)) {
		return html_entity_decode($m[1]);
	}
	// (2) <div|section class="hero-bg" style="...url()...">
	if (preg_match('/<(?:div|section)\b[^>]*\bhero-bg\b[^>]*>/i', $post->post_content, $tag)
		&& preg_match('/url\(\s*(?:&quot;|&#0?34;|["\']?)\s*([^)"\'\s]+\.(?:webp|avif|jpe?g|png))/i', $tag[0], $m)) {
		return html_entity_decode($m[1]);
	}
	return '';
}

/**
 * Echo the hero preload <link> exactly once per request. Both emit points (lean-head.php
 * inline, and the wp_head action) call this; the static guard makes a double-emit
 * impossible even if a template were ever to render both heads.
 *
 * @param string $url Hero image URL ('' is a no-op).
 * @return void
 */
function lean_emit_hero_preload($url) {
	static $emitted = false;
	if ($emitted || $url === '') {
		return;
	}
	$emitted = true;
	echo '<link rel="preload" as="image" href="' . esc_url($url) . '" fetchpriority="high">' . "\n";
}

/**
 * Integration-mode emit point: preload the hero on wp_head. No-op in standalone (Lean
 * templates never call wp_head()); this fires only when a parent theme's header does.
 * Resolves an ACF hero first, then falls back to in-content detection.
 *
 * @return void
 */
add_action('wp_head', 'lean_hero_bg_preload', 1);
function lean_hero_bg_preload() {
	if (!is_singular()) {
		return;
	}
	$url = '';
	if (function_exists('get_field')) {
		$acf = get_field('hero_background_image');
		if (is_array($acf) && !empty($acf['url'])) {
			$url = $acf['url'];
		}
	}
	if ($url === '') {
		$url = lean_hero_image_url(get_queried_object());
	}
	lean_emit_hero_preload($url);
}

/**
 * Stamp fetchpriority="high" on the first <img class="hero-bg"> in content and strip any
 * loading="lazy" a filter added, so the LCP candidate is high-priority and never lazy.
 * Runs on the_content, so it applies on every template in both modes.
 *
 * @param string $content
 * @return string
 */
add_filter('the_content', 'lean_hero_bg_lcp_hints', 20);
function lean_hero_bg_lcp_hints($content) {
	if (is_feed() || strpos($content, 'hero-bg') === false) {
		return $content;
	}

	$content = preg_replace_callback(
		'/<img\b([^>]*\bclass="[^"]*\bhero-bg\b[^"]*"[^>]*)>/i',
		function ($m) {
			$attrs = $m[1];
			if (stripos($attrs, 'fetchpriority=') === false) {
				$attrs .= ' fetchpriority="high"';
			}
			$attrs = preg_replace('/\s+loading="lazy"/i', '', $attrs);
			return '<img' . $attrs . '>';
		},
		$content,
		1
	);

	return $content;
}
