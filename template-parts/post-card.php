<?php
/**
 * Post Card
 *
 * One post in a listing grid: featured image, title, meta description.
 * Must be called from inside the loop.
 *
 * Uses template tags rather than the $post global — lean_get_template_part()
 * includes this from function scope, where that global is not in scope.
 *
 * The title carries .stretched-link so the whole card is clickable while the
 * accessible link name stays just the title. Wrapping image + title + summary
 * in one <a> would make screen readers announce the lot as a single link.
 */

$lean_summary = lean_post_summary();

// Loading strategy is left to core on the initial render: it withholds lazy from
// the first image(s), which on the blog roll is the likely LCP element. Hard-coding
// loading="lazy" would lazy-load the top-left card and cost us the metric.
//
// AJAX-appended cards are always below the fold, but core's per-request counter
// restarts on the admin-ajax request, so the first card of every batch would look
// like an above-the-fold image to it. Force lazy there.
$lean_thumb_attrs = ['class' => 'img-fluid rounded mb-3 w-100'];
if ( wp_doing_ajax() ) {
	$lean_thumb_attrs['loading'] = 'lazy';
}
?>
<article class="col-12 col-md-4 col-lg-3">
	<div class="position-relative h-100">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail('medium_large', $lean_thumb_attrs); ?>
		<?php else : ?>
			<div class="bg-light rounded mb-3" style="aspect-ratio: 4/3;"></div>
		<?php endif; ?>

		<h2 class="h4 fw-bold mb-2">
			<a href="<?php the_permalink(); ?>" class="stretched-link text-decoration-none text-body"><?php the_title(); ?></a>
		</h2>

		<?php if ( $lean_summary !== '' ) : ?>
			<p class="text-body-secondary mb-0"><?php echo esc_html($lean_summary); ?></p>
		<?php endif; ?>
	</div>
</article>
