<?php
/**
 * Main Template (Fallback)
 *
 * Used by WordPress for any request not matched by a more specific template
 * (archive.php, search.php, etc.). Pages route to page.php and single posts
 * route to single.php.
 */

get_header();

// Pages use raw HTML; only keep wpautop for posts.
if ( ! is_single() || get_post_type() !== 'post' ) {
	remove_filter('the_content', 'wpautop');
	remove_filter('the_content', 'shortcode_unautop');
}

if ( have_posts() ) :
	while ( have_posts() ) : the_post();
		the_content();
	endwhile;
else :
	echo '<p>No content found.</p>';
endif;

get_footer();
