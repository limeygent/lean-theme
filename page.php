<?php
/**
 * Page Template
 *
 * Default template for WordPress pages.
 */

get_header();

// Pages use raw HTML — disable wpautop so paragraphs aren't auto-inserted.
remove_filter('the_content', 'wpautop');
remove_filter('the_content', 'shortcode_unautop');

while ( have_posts() ) : the_post();
	the_content();
endwhile;

get_footer();
