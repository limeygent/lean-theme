<?php
/**
 * Main Template File
 *
 * WordPress requires this file to recognize the theme.
 * For optimized pages, use templates/page-lean.php instead.
 */

get_header();
?>

<main id="lean-main" class="lean-main">
	<div class="container py-5">
		<?php if (have_posts()): ?>
			<?php while (have_posts()): the_post(); ?>
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<header>
						<h1><?php the_title(); ?></h1>
					</header>
					<div class="entry-content">
						<?php the_content(); ?>
					</div>
				</article>
			<?php endwhile; ?>
		<?php else: ?>
			<p>No content found.</p>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
