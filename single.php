<?php
/**
 * Single Post Template
 *
 * Full-bleed featured-image hero, followed by the article body in a standard
 * Bootstrap container. The post title is the <h1> in the body, directly under
 * the review notice — not overlaid on the hero.
 */

get_header();

while ( have_posts() ) : the_post();
	?>
	<?php if ( has_post_thumbnail() ) : ?>
		<section class="blog-hero position-relative overflow-hidden">
			<?php the_post_thumbnail('full', [
				'class'    => 'd-block w-100 blog-hero-img',
				'loading'  => 'eager',
				'decoding' => 'sync',
			]); ?>
		</section>
	<?php endif; ?>

	<div class="container">
		<section class="lean-section">
			<article>
				<?php echo do_shortcode('[blog_review_notice]'); ?>
				<h1 class="mb-4"><?php the_title(); ?></h1>
				<?php the_content(); ?>
				<?php echo do_shortcode('[blog_post_interlink]'); ?>
			</article>
		</section>
	</div>
<?php
endwhile;

get_footer();
