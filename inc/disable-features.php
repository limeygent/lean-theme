<?php
/**
 * Filename: disable-features.php
 * Purpose: Disable WordPress features not needed for lean theme sites
 *
 * Features disabled:
 * - RSS/Atom feeds (410 Gone)
 * - Site search (410 Gone)
 *
 * TODO: Add admin toggles in theme options to enable/disable each feature
 */

// ──────────────────────────────────────────────────────────────────────────────
// DISABLE RSS/ATOM FEEDS
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Return 410 Gone for any RSS/Atom feed request
 */
function lean_disable_feeds_410() {
	if (is_feed()) {
		status_header(410);
		nocache_headers();
		header('Content-Type: text/plain; charset=' . get_option('blog_charset'));
		echo 'This site does not provide RSS/Atom feeds.';
		exit;
	}
}
add_action('template_redirect', 'lean_disable_feeds_410', 0);

/**
 * Short-circuit core feed handlers
 */
function lean_kill_feed_endpoint() {
	status_header(410);
	nocache_headers();
	header('Content-Type: text/plain; charset=' . get_option('blog_charset'));
	echo 'Feed endpoint is gone.';
	exit;
}

$feed_hooks = array(
	'do_feed', 'do_feed_rdf', 'do_feed_rss', 'do_feed_rss2', 'do_feed_atom',
	'do_feed_rss2_comments', 'do_feed_atom_comments'
);
foreach ($feed_hooks as $hook) {
	add_action($hook, 'lean_kill_feed_endpoint', 0);
}

/**
 * Remove feed discovery links from <head>
 * Note: Only relevant if wp_head() is ever called
 */
add_action('after_setup_theme', function() {
	remove_theme_support('automatic-feed-links');
}, 11);

add_action('init', function() {
	remove_action('wp_head', 'feed_links', 2);
	remove_action('wp_head', 'feed_links_extra', 3);
	add_filter('feed_links_show_posts_feed', '__return_false');
	add_filter('feed_links_show_comments_feed', '__return_false');
});

// ──────────────────────────────────────────────────────────────────────────────
// DISABLE SITE SEARCH
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Intercept search queries and flag as 404
 */
add_action('parse_query', function($query) {
	if (is_search() && !is_admin()) {
		$query->is_search = false;
		$query->query_vars['s'] = false;
		$query->query['s'] = false;
		$query->is_404 = true;
	}
}, 15);

/**
 * Return 410 Gone for search requests
 */
add_action('template_redirect', function() {
	if (is_404() && isset($_GET['s'])) {
		status_header(410);
		nocache_headers();
		header('Content-Type: text/plain; charset=' . get_option('blog_charset'));
		echo 'Site search is not available.';
		exit;
	}
});
