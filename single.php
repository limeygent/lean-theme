<?php
/**
 * Single Post Template
 *
 * Full-bleed featured-image hero with the post title overlaid, followed by
 * the article body in a standard Bootstrap container.
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
			<div class="position-absolute bottom-0 start-0 w-100 bg-dark bg-opacity-75 py-4 py-md-5">
				<div class="container">
					<p class="fs-1 fw-bold text-white mb-0"><?php the_title(); ?></p>
				</div>
			</div>
		</section>
	<?php else : ?>
		<section class="blog-hero bg-dark py-5">
			<div class="container">
				<p class="fs-1 fw-bold text-white mb-0"><?php the_title(); ?></p>
			</div>
		</section>
	<?php endif; ?>

	<div class="container">
		<section class="lean-section">
			<article>
				<?php echo do_shortcode('[blog_review_notice]'); ?>
				<?php the_content(); ?>
				<?php echo do_shortcode('[blog_post_interlink]'); ?>
			</article>
		</section>
	</div>
<?php
endwhile;

get_footer();
