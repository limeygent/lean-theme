<?php
/**
 * Lean Head Template Part
 *
 * Replaces wp_head() for optimized page templates.
 * Place everything that goes INSIDE <head></head>
 *
 * Usage in page templates:
 *   <head>
 *   <?php get_template_part('template-parts/head'); ?>
 *   </head>
 *
 * Optional: Pass hero image via set_query_var before calling:
 *   set_query_var('lean_hero_image', $hero_image_array);
 *   get_template_part('template-parts/head');
 */

// Get hero image - check if passed via query var first, then try ACF
$hero_image = get_query_var('lean_hero_image', null);
if (!$hero_image && function_exists('get_field')) {
	$hero_image = get_field('hero_background_image');
}

// Theme paths
$theme_uri = get_template_directory_uri();
$theme_rel = wp_make_link_relative($theme_uri);
$theme_dir = get_template_directory();

// CSS version for cache busting
$lean_css_path = $theme_dir . '/css/lean-pages.css';
$lean_css_ver = file_exists($lean_css_path) ? filemtime($lean_css_path) : time();

// Analytics IDs
$clarity_id = get_option('clarity_project_id', '');
?>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="theme-color" content="#025592" />

<?php if ($clarity_id): ?>
<!-- Performance hints -->
<link rel="dns-prefetch" href="https://www.clarity.ms">
<?php endif; ?>


<?php
// Favicon
$site_icon_id = get_option('site_icon');
if ($site_icon_id) {
	$site_icon_url = wp_get_attachment_image_url($site_icon_id, 'full');
	if ($site_icon_url) {
		echo '<link rel="icon" href="' . esc_url($site_icon_url) . '" sizes="32x32" />' . "\n";
		echo '<link rel="icon" href="' . esc_url($site_icon_url) . '" sizes="192x192" />' . "\n";
		echo '<link rel="apple-touch-icon" href="' . esc_url($site_icon_url) . '" />' . "\n";
		echo '<meta name="msapplication-TileImage" content="' . esc_url($site_icon_url) . '" />' . "\n";
	}
}
?>

<?php
// Canonical and hreflang
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$currentUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
?>
<!-- Alternate Language Links -->
<link rel="alternate" href="<?php echo esc_url($currentUrl); ?>" hreflang="en">
<link rel="alternate" href="<?php echo esc_url($currentUrl); ?>" hreflang="x-default">

<?php if (function_exists('pageone_output_seo_meta_tags')) pageone_output_seo_meta_tags(); ?>

<!-- Preload local Roboto -->
<link rel="preload" href="<?php echo $theme_uri; ?>/assets/fonts/roboto/roboto-v49-latin-regular.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?php echo $theme_uri; ?>/assets/fonts/roboto/roboto-v49-latin-700.woff2" as="font" type="font/woff2" crossorigin>

<?php if ($hero_image && !empty($hero_image['url'])): ?>
<!-- Preload hero image (LCP element) -->
<link rel="preload" href="<?php echo esc_url($hero_image['url']); ?>" as="image" fetchpriority="high">
<?php endif; ?>

<!-- Preload CSS files for parallel download -->
<link rel="preload" href="<?php echo $theme_rel; ?>/css/bootstrap.css?ver=1.1" as="style">
<link rel="preload" href="<?php echo $theme_rel; ?>/style.css?ver=1.667" as="style">
<link rel="preload" href="<?php echo $theme_rel; ?>/css/lean-pages.css?ver=<?php echo $lean_css_ver; ?>" as="style">

<!-- Apply stylesheets (blocking for FOUC prevention) -->
<link rel="stylesheet" href="<?php echo $theme_rel; ?>/css/bootstrap.css?ver=1.1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="stylesheet" href="<?php echo $theme_rel; ?>/style.css?ver=1.667">
<link rel="stylesheet" href="<?php echo $theme_rel; ?>/css/lean-pages.css?ver=<?php echo $lean_css_ver; ?>">

<?php if ($clarity_id): ?>
<!-- MS Clarity -->
<script type="text/javascript">
	(function(c,l,a,r,i,t,y){
		c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
		t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
		y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
	})(window, document, "clarity", "script", "<?php echo esc_js($clarity_id); ?>");
</script>
<?php endif; ?>

<style>
	<?php if ($hero_image && !empty($hero_image['url'])): ?>
	.hero-bg {
		background-image: url('<?php echo esc_url($hero_image['url']); ?>');
		background-size: cover;
		background-position: center;
		filter: saturate(1.05) brightness(.95);
		z-index: 0;
	}
	<?php endif; ?>

	/* Local Roboto (WOFF2 only) */
	@font-face {
		font-family: "Roboto";
		src: url("<?php echo $theme_uri; ?>/assets/fonts/roboto/roboto-v49-latin-regular.woff2") format("woff2");
		font-weight: 400; font-style: normal; font-display: swap;
	}
	@font-face {
		font-family: "Roboto";
		src: url("<?php echo $theme_uri; ?>/assets/fonts/roboto/roboto-v49-latin-700.woff2") format("woff2");
		font-weight: 700; font-style: normal; font-display: swap;
	}
</style>