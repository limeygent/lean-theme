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
		// Heading: title of the Posts Page, or fall back to "News".
		//
		// page_for_posts only means anything when a static front page is configured.
		// WordPress keeps the old value when Reading is switched back to "Your latest
		// posts" — that is how the dropdown remembers your choice — so reading it
		// blindly puts a stale heading on the roll, or an empty <h1> if that page has
		// since been trashed.
		$page_for_posts = ('page' === get_option('show_on_front')) ? (int) get_option('page_for_posts') : 0;
		$heading = $page_for_posts ? get_the_title($page_for_posts) : 'News';
		if ('' === trim((string) $heading)) {
			$heading = 'News';
		}
		?>
		<h1 class="mb-4"><?php echo esc_html($heading); ?></h1>

		<?php if ( have_posts() ) : ?>
			<div class="row g-4" id="lean-post-grid">
				<?php while ( have_posts() ) : the_post(); ?>
					<?php lean_get_template_part('template-parts/post-card'); ?>
				<?php endwhile; ?>
			</div>

			<?php
			global $wp_query;
			$lean_max   = (int) $wp_query->max_num_pages;
			$lean_paged = max(1, (int) get_query_var('paged'));
			?>

			<?php if ( $lean_paged < $lean_max ) : ?>
				<?php
				// Infinite scroll. The sentinel auto-loads the next batch as it nears
				// the viewport, so no /page/2/ link is ever rendered.
				//
				// It is a real <button>, not a bare <div>: scroll position is not an
				// input a keyboard user has, and an unfocusable sentinel would strand
				// them at post 20. Activating it loads the next batch directly.
				?>
				<?php
				// The live region sits OUTSIDE the sentinel on purpose. The script removes
				// the sentinel once the last batch lands; a live region destroyed in the
				// same task never gets announced, so "All posts loaded." would be silent.
				?>
				<p class="visually-hidden" role="status" id="lean-load-more-status"></p>

				<div class="text-center mt-5" id="lean-load-more-sentinel">
					<button type="button"
					        class="btn btn-primary btn-lg"
					        id="lean-load-more"
					        data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
					        data-paged="<?php echo esc_attr($lean_paged); ?>">
						Load more posts
					</button>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<p>No posts yet.</p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
