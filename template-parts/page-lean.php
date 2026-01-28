<?php
/**
 * Template Name: Lean Theme
 * Description: Optimized single-file template - no bloat.
 *
 * Uses template parts from /template-parts/:
 *   - template-parts/lean-head.php
 *   - template-parts/lean-header.php
 *   - template-parts/lean-footer.php
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
	<head>
		<?php lean_get_template_part('template-parts/lean-head'); ?>
	</head>

	<body class="lean-body">
		<?php lean_get_template_part('template-parts/lean-header'); ?>

		<!--  Content  -->
		<main id="lean-main" class="lean-main container" tabindex="-1">

			<?php
			// Only remove wpautop for pages (which use raw HTML)
			// Posts need it to convert line breaks to paragraphs
			if ( ! is_single() || get_post_type() !== 'post' ) {
				remove_filter('the_content', 'wpautop');
				remove_filter('the_content', 'shortcode_unautop');
			}

			while ( have_posts() ) : the_post();

			// Blog post specific elements (only on single posts)
			if ( is_single() && get_post_type() === 'post' ) {
				echo do_shortcode('[blog_featured_image]');
				echo do_shortcode('[blog_review_notice]');
			}

			the_content();

			// Blog post interlinking at the end (only on single posts)
			if ( is_single() && get_post_type() === 'post' ) {
				echo do_shortcode('[blog_post_interlink]');
			}

			endwhile;
			?>
		</main>

		<?php lean_get_template_part('template-parts/lean-footer'); ?>
	</body>
</html>
