<?php
/**
 * Filename: blog.php
 * Purpose: Blog-related shortcodes and functionality
 *
 * Shortcodes:
 * - [blog_featured_image] - Display featured image on blog posts
 * - [blog_review_notice] - Display "reviewed by" notice with date
 * - [blog_post_interlink] - Prev/next post links within same category
 * - [latest_blog_post] - Link to most recent post
 *
 * Also forces blog posts to use the lean template
 */

// ──────────────────────────────────────────────────────────────────────────────
// TEMPLATE: Force blog posts to use lean template
// ──────────────────────────────────────────────────────────────────────────────

add_filter('template_include', 'lean_blog_use_lean_template');

function lean_blog_use_lean_template($template) {
	if (is_single() && get_post_type() === 'post') {
		$lean_template = locate_template('templates/page-lean.php');
		if ($lean_template) {
			return $lean_template;
		}
	}
	return $template;
}

// ──────────────────────────────────────────────────────────────────────────────
// SHORTCODE: [blog_featured_image]
// ──────────────────────────────────────────────────────────────────────────────

add_shortcode('blog_featured_image', 'lean_blog_featured_image_shortcode');

function lean_blog_featured_image_shortcode() {
	global $post;

	// Only run on single blog posts with featured image
	if (!is_single() || get_post_type() !== 'post' || !has_post_thumbnail()) {
		return '';
	}

	$thumb_id = get_post_thumbnail_id();
	$img = wp_get_attachment_image_src($thumb_id, 'full');
	$ratio = ($img && !empty($img[1])) ? ($img[2] / $img[1]) : null;

	$attrs = [
		'class'    => 'mb-4',
		'loading'  => 'eager',
		'decoding' => 'async',
	];

	if ($ratio) {
		$attrs['data-crop-image-ratio'] = $ratio;
	}

	return get_the_post_thumbnail(null, 'full', $attrs);
}

// ──────────────────────────────────────────────────────────────────────────────
// SHORTCODE: [blog_review_notice]
// ──────────────────────────────────────────────────────────────────────────────

add_shortcode('blog_review_notice', 'lean_blog_review_notice_shortcode');

function lean_blog_review_notice_shortcode() {
	global $post;

	// Only run on single blog posts
	if (!is_single() || get_post_type() !== 'post') {
		return '';
	}

	$business_name = get_option('business_name', '');
	$publish_date = new DateTime($post->post_date);
	$current_date = new DateTime();
	$this_month = new DateTime('third wednesday of this month');

	if ($this_month >= $current_date) {
		$review_date = new DateTime('third wednesday of last month');
	} else {
		$review_date = $this_month;
	}

	$output = '<div style="display: inline-block; background-color: rgb(0, 83, 149, 0.3); padding: 10px; border: 1px solid #005395; margin-top: 0px; margin-bottom: 10px; font-size: 0.8em;">';
	$output .= 'This article was written & reviewed by the ' . esc_html($business_name) . ' staff. ';
	if ($review_date >= $publish_date) {
		$formatted_date = $review_date->format('F j, Y');
		$output .= 'Last reviewed: ' . $formatted_date . '. ';
	}
	$output .= '</div>';

	return $output;
}

// ──────────────────────────────────────────────────────────────────────────────
// SHORTCODE: [blog_post_interlink]
// ──────────────────────────────────────────────────────────────────────────────

add_shortcode('blog_post_interlink', 'lean_blog_post_interlink_shortcode');

function lean_blog_post_interlink_shortcode() {
	global $post;

	// Only run on standard posts
	if (get_post_type($post) !== 'post') {
		return '';
	}

	$postCats = get_the_category();
	$postCatIDs = array();
	$postCategory = '';

	foreach ($postCats as $postCat) {
		$postCategory = $postCat->cat_name;
		array_push($postCatIDs, $postCat->cat_ID);
	}

	$str = '<div class="mb-4">';
	$str .= '<hr>If you enjoyed this article, check out these other articles regarding ' . esc_html($postCategory) . ':';

	// Get ALL posts in category INCLUDING current post
	$args = array(
		'posts_per_page' => -1,
		'category__in'   => $postCatIDs,
		'post_status'    => array('publish')
	);

	$allPosts = get_posts($args);

	// Sort by date (oldest first)
	usort($allPosts, function($a, $b) {
		return strtotime($a->post_date) - strtotime($b->post_date);
	});

	// Find current post's position
	$currentIndex = -1;
	for ($i = 0; $i < count($allPosts); $i++) {
		if ($allPosts[$i]->ID == $post->ID) {
			$currentIndex = $i;
			break;
		}
	}

	// Previous Post (chronologically previous = older post)
	$prevIndex = $currentIndex - 1;
	if ($prevIndex < 0) {
		$prevIndex = count($allPosts) - 1; // Wrap to newest
	}

	if ($prevIndex != $currentIndex && isset($allPosts[$prevIndex])) {
		$linkedPost = $allPosts[$prevIndex];
		$str .= '<br><a href="' . get_permalink($linkedPost->ID) . '">' . esc_html($linkedPost->post_title) . '</a>';
	}

	// Next Post (chronologically next = newer post)
	$nextIndex = $currentIndex + 1;
	if ($nextIndex >= count($allPosts)) {
		$nextIndex = 0; // Wrap to oldest
	}

	if ($nextIndex != $currentIndex && isset($allPosts[$nextIndex])) {
		$linkedPost = $allPosts[$nextIndex];
		$str .= '<br><a href="' . get_permalink($linkedPost->ID) . '">' . esc_html($linkedPost->post_title) . '</a>';
	}

	$str .= '</div>';

	return $str;
}

// ──────────────────────────────────────────────────────────────────────────────
// SHORTCODE: [latest_blog_post]
// ──────────────────────────────────────────────────────────────────────────────

add_shortcode('latest_blog_post', 'lean_latest_blog_post_shortcode');

function lean_latest_blog_post_shortcode($atts) {
	$atts = shortcode_atts(array(
		'category' => '', // Optional category slug
	), $atts);

	$args = array(
		'posts_per_page' => 1,
		'orderby'        => 'date',
		'order'          => 'DESC'
	);

	if (!empty($atts['category'])) {
		$args['category_name'] = $atts['category'];
	}

	$query = new WP_Query($args);

	if ($query->have_posts()) {
		$query->the_post();
		$link = '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
		wp_reset_postdata();
		return $link;
	}

	return 'No posts found.';
}
