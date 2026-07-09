<?php
/**
 * Blog Roll
 *
 * Powers home.php: batch size, the "Load more" endpoint that appends the next
 * batch of cards, and the script that drives it.
 *
 * Lean templates never call wp_footer(), so wp_enqueue_script() and
 * wp_localize_script() emit nothing on the front end. The script is printed on
 * the lean_footer action instead, and configured via data-* attributes.
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * How many posts load initially and per "Load more" click.
 * Overrides Settings → Reading for the blog roll only.
 */
function lean_posts_per_load() {
	$per_load = (int) apply_filters('lean_posts_per_load', 20);
	return $per_load > 0 ? $per_load : 20;
}

/**
 * Query args shared by the main blog-roll query and the AJAX endpoint, so both
 * paginate over an identical result set.
 *
 * Sticky posts are ignored on purpose. WordPress hoists them onto page 1 but
 * still returns them in their natural date position on a later page, so a
 * sticky post would render twice once a visitor loads the batch containing it.
 */
function lean_blog_roll_query_args($paged) {
	return array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => lean_posts_per_load(),
		'paged'               => max(1, (int) $paged),
		'ignore_sticky_posts' => true,
	);
}

/**
 * Apply the batch size (and the sticky rule above) to the main blog-roll query.
 *
 * Feeds are excluded: is_home() is also true for /feed/, and the batch size is a
 * layout concern that has no business overriding the posts_per_rss option.
 */
add_action('pre_get_posts', 'lean_blog_roll_pre_get_posts');
function lean_blog_roll_pre_get_posts($query) {
	if (is_admin() || !$query->is_main_query() || !$query->is_home() || $query->is_feed()) {
		return;
	}
	$query->set('posts_per_page', lean_posts_per_load());
	$query->set('ignore_sticky_posts', true);
}

/**
 * Whether the current request is a paginated blog-roll URL that must not be indexed.
 * Page 1 stays indexable.
 */
function lean_blog_roll_is_noindex() {
	return is_home() && is_paged();
}

/**
 * The robots directives for a paginated blog-roll URL.
 *
 * This must handle "Discourage search engines" itself rather than defer to
 * lean_fallback_head_seo(): that fallback goes silent whenever the plugin is
 * active, and the plugin emits nothing on non-singular views — so on a
 * discouraged staging site with the (required) plugin installed, deferring would
 * leave these URLs with no robots tag at all.
 */
function lean_blog_roll_robots_content() {
	return get_option('blog_public') ? 'noindex, follow' : 'noindex, nofollow';
}

/**
 * Keep /page/2/ and beyond out of the search index.
 *
 * Nothing else emits robots for the blog roll: the plugin's head output returns
 * early on !is_singular() (meta-output.php:172) and the theme's fallback stays
 * silent whenever the plugin is active (lean-loader.php:91) — so these URLs were
 * shipping as indexable.
 *
 * `follow` is deliberate. Crawlers still walk through to the posts, and post
 * discovery does not depend on this path anyway: `post` is in the plugin's XML
 * sitemap (cpt-registry.php:27-29).
 */
add_action('lean_head', 'lean_blog_roll_noindex_paged', 2);
function lean_blog_roll_noindex_paged() {
	if (!lean_blog_roll_is_noindex()) {
		return;
	}
	echo '<meta name="robots" content="' . esc_attr(lean_blog_roll_robots_content()) . '">' . "\n";
}

/**
 * Return the next batch of rendered post cards.
 *
 * No nonce, deliberately. The endpoint only reads already-published posts, so
 * there is nothing to forge; meanwhile the blog roll is page-cached (see
 * docs/PERFORMANCE.md), and a nonce baked into cached HTML outlives its 12-hour
 * window and would break "Load more" for real visitors.
 */
add_action('wp_ajax_lean_load_more_posts', 'lean_load_more_posts');
add_action('wp_ajax_nopriv_lean_load_more_posts', 'lean_load_more_posts');
function lean_load_more_posts() {
	$paged = isset($_POST['paged']) ? absint($_POST['paged']) : 0;
	if ($paged < 2) {
		wp_send_json_error(array('message' => 'Invalid page.'), 400);
	}

	$query = new WP_Query(lean_blog_roll_query_args($paged));
	$max   = (int) $query->max_num_pages;

	if ($paged > $max) {
		wp_reset_postdata();
		wp_send_json_success(array('html' => '', 'has_more' => false));
	}

	ob_start();
	while ($query->have_posts()) {
		$query->the_post();
		lean_get_template_part('template-parts/post-card');
	}
	wp_reset_postdata();

	wp_send_json_success(array(
		'html'     => ob_get_clean(),
		'has_more' => $paged < $max,
	));
}

/** Print the infinite-scroll script on the blog roll, and only when it has work to do. */
add_action('lean_footer', 'lean_blog_roll_script');
function lean_blog_roll_script() {
	if (!is_home() || !defined('LEAN_THEME_DIR') || !defined('LEAN_THEME_URL')) {
		return;
	}

	// home.php renders the sentinel only while a further page exists. Without one
	// the script has nothing to observe and bails immediately, so a single-page
	// blog should not pay for the request at all.
	global $wp_query;
	$paged = max(1, (int) get_query_var('paged'));
	if (!isset($wp_query) || $paged >= (int) $wp_query->max_num_pages) {
		return;
	}

	$path = LEAN_THEME_DIR . '/js/lean-load-more.js';
	if (!file_exists($path)) {
		return;
	}

	$url = LEAN_THEME_URL . '/js/lean-load-more.js?ver=' . filemtime($path);
	echo '<script src="' . esc_url($url) . '" defer></script>' . "\n";
}
