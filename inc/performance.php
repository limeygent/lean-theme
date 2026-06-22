<?php
/**
 * Performance: LCP hints for the hero image.
 *
 * The hero image is hand-coded as <img class="hero-bg"> inside page content,
 * so WordPress's automatic LCP fetchpriority handling (which targets attachment
 * images) doesn't see it. This filter stamps fetchpriority="high" on the first
 * .hero-bg image in the content and strips loading="lazy" if anything added it,
 * so PageSpeed Insights can discover the LCP candidate immediately.
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Preload the hero's CSS background image (the LCP candidate on pages whose hero is a
 * <div class="hero-bg" style="background:url(...)"> rather than an <img>). A CSS
 * background is discovered late (only after CSS parses), which shows up in PSI as LCP
 * "resource load delay" + "should be discoverable / fetchpriority=high". This reads the
 * hero URL straight from the current page's content and emits a high-priority preload in
 * <head>, so it is fetched immediately. URL is auto-detected (not hardcoded), so it keeps
 * working if the hero image changes.
 */
add_action('wp_head', 'lean_hero_bg_preload', 1);
function lean_hero_bg_preload() {
	if (!is_singular()) {
		return;
	}
	$post = get_queried_object();
	if (!($post instanceof WP_Post) || strpos($post->post_content, 'hero-bg') === false) {
		return;
	}
	// Grab the hero-bg element's opening tag, then the url() inside its inline style.
	if (!preg_match('/<(?:div|section)\b[^>]*\bhero-bg\b[^>]*>/i', $post->post_content, $tag)) {
		return;
	}
	if (!preg_match('/url\(\s*(?:&quot;|&#0?34;|["\']?)\s*([^)"\'\s]+\.(?:webp|avif|jpe?g|png))/i', $tag[0], $m)) {
		return;
	}
	$url = html_entity_decode($m[1]);
	echo '<link rel="preload" as="image" href="' . esc_url($url) . '" fetchpriority="high">' . "\n";
}

add_filter('the_content', 'lean_hero_bg_lcp_hints', 20);
function lean_hero_bg_lcp_hints($content) {
	if (is_feed() || strpos($content, 'hero-bg') === false) {
		return $content;
	}

	$count = 0;
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
		1,
		$count
	);

	return $content;
}
