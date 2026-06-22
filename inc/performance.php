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
