<?php
/**
 * Blog Posts Index (Blog Roll)
 *
 * Used by WordPress for the blog index — either as the front page (when
 * Settings → Reading is "Latest posts") or as the dedicated posts page.
 *
 * Grid: 4 columns on desktop (≥992px), 3 on tablet/laptop (≥768px), 1 on mobile.
 */

get_header();
?>
<section class="py-5">
	<div class="container">
		<?php
		// Heading: title of the Posts Page, or fall back to "News"
		$page_for_posts = (int) get_option('page_for_posts');
		$heading = $page_for_posts ? get_the_title($page_for_posts) : 'News';
		?>
		<h1 class="mb-4"><?php echo esc_html($heading); ?></h1>

		<?php if ( have_posts() ) : ?>
			<div class="row g-4">
				<?php while ( have_posts() ) : the_post(); ?>
					<article class="col-12 col-md-4 col-lg-3">
						<a href="<?php the_permalink(); ?>" class="d-block text-decoration-none text-body">
							<?php if ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail('medium_large', [
									'class'   => 'img-fluid rounded mb-3 w-100',
									'loading' => 'lazy',
								]); ?>
							<?php else : ?>
								<div class="bg-light rounded mb-3" style="aspect-ratio: 4/3;"></div>
							<?php endif; ?>

							<h2 class="h4 fw-bold mb-0"><?php the_title(); ?></h2>
						</a>
					</article>
				<?php endwhile; ?>
			</div>

			<div class="mt-5">
				<?php the_posts_pagination([
					'mid_size'  => 1,
					'prev_text' => '&laquo; Previous',
					'next_text' => 'Next &raquo;',
				]); ?>
			</div>
		<?php else : ?>
			<p>No posts yet.</p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
