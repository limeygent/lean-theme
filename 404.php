<?php
/**
 * 404 Not Found Template
 */

get_header();
?>
<section class="py-5">
	<div class="container text-center">
		<h1 class="display-4 mb-3">Page Not Found</h1>
		<p class="lead mb-4">The page you're looking for doesn't exist or has moved.</p>
		<a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">Return Home</a>
	</div>
</section>
<?php
get_footer();
