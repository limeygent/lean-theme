<?php
/**
 * Main Template File
 *
 * WordPress requires this file to recognize the theme.
 * Uses the same lean template parts as page-lean.php.
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
	<head>
		<?php lean_get_template_part('template-parts/lean-head'); ?>
	</head>

	<body class="lean-body">
		<?php lean_get_template_part('template-parts/lean-header'); ?>

		<main id="lean-main" class="lean-main" tabindex="-1">
			<?php
			if ( ! is_single() || get_post_type() !== 'post' ) {
				remove_filter('the_content', 'wpautop');
				remove_filter('the_content', 'shortcode_unautop');
			}

			if ( have_posts() ) :
				while ( have_posts() ) : the_post();

				if ( is_single() && get_post_type() === 'post' ) {
					echo do_shortcode('[blog_featured_image]');
					echo do_shortcode('[blog_review_notice]');
				}

				the_content();

				if ( is_single() && get_post_type() === 'post' ) {
					echo do_shortcode('[blog_post_interlink]');
				}

				endwhile;
			else :
				echo '<p>No content found.</p>';
			endif;
			?>
		</main>

		<?php lean_get_template_part('template-parts/lean-footer'); ?>
	</body>
</html>
