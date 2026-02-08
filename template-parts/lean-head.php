<?php
/**
 * Lean Head Template Part
 *
 * Replaces wp_head() for optimized page templates.
 * Place everything that goes INSIDE <head></head>
 *
 * Usage in page templates:
 *   <head>
 *   <?php get_template_part('template-parts/lean-head'); ?>
 *   </head>
 *
 * Optional: Pass hero image via set_query_var before calling:
 *   set_query_var('lean_hero_image', $hero_image_array);
 *   get_template_part('template-parts/lean-head');
 */

// Get hero image - check if passed via query var first, then try ACF
$hero_image = get_query_var('lean_hero_image', null);
if (!$hero_image && function_exists('get_field')) {
	$hero_image = get_field('hero_background_image');
}

// Theme paths - use LEAN constants for correct paths in both standalone and integrated modes
$theme_uri = defined('LEAN_THEME_URL') ? LEAN_THEME_URL : get_template_directory_uri();
$theme_rel = wp_make_link_relative($theme_uri);
$theme_dir = defined('LEAN_THEME_DIR') ? LEAN_THEME_DIR : get_template_directory();

// CSS version for cache busting (uses file modification time)
$bootstrap_css_path = $theme_dir . '/css/bootstrap.css';
$bootstrap_css_ver = file_exists($bootstrap_css_path) ? filemtime($bootstrap_css_path) : time();

$lean_css_path = $theme_dir . '/css/lean-pages.css';
$lean_css_ver = file_exists($lean_css_path) ? filemtime($lean_css_path) : time();

$fa_css_path = $theme_dir . '/css/fontawesome-minimal.css';
$fa_css_ver = file_exists($fa_css_path) ? filemtime($fa_css_path) : time();

$bi_css_path = $theme_dir . '/css/bootstrap-icons.min.css';
$bi_css_ver = file_exists($bi_css_path) ? filemtime($bi_css_path) : time();

// Analytics IDs
$gtm_id = get_option('gtm_container_id', '');
$clarity_id = get_option('clarity_project_id', '');
?>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="theme-color" content="<?php echo esc_attr(get_option('header_top_bg', '#f8f9fa')); ?>" />

<?php if ($gtm_id): ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo esc_js($gtm_id); ?>');</script>
<!-- End Google Tag Manager -->
<?php endif; ?>

<!-- Performance hints -->
<?php if ($gtm_id): ?>
<link rel="preconnect" href="https://www.googletagmanager.com">
<?php endif; ?>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<?php if ($clarity_id): ?>
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

<?php if (function_exists('lean_output_seo_meta_tags')) lean_output_seo_meta_tags(); ?>

<?php if ($hero_image && !empty($hero_image['url'])): ?>
<!-- Preload hero image (LCP element) -->
<link rel="preload" href="<?php echo esc_url($hero_image['url']); ?>" as="image" fetchpriority="high">
<?php endif; ?>

<!-- Preload CSS files for parallel download -->
<link rel="preload" href="<?php echo $theme_rel; ?>/css/bootstrap.css?ver=<?php echo $bootstrap_css_ver; ?>" as="style">
<link rel="preload" href="<?php echo $theme_rel; ?>/css/lean-pages.css?ver=<?php echo $lean_css_ver; ?>" as="style">
<link rel="preload" href="<?php echo $theme_rel; ?>/css/fontawesome-minimal.css?ver=<?php echo $fa_css_ver; ?>" as="style">
<link rel="preload" href="<?php echo $theme_rel; ?>/css/bootstrap-icons.min.css?ver=<?php echo $bi_css_ver; ?>" as="style">

<!-- Apply stylesheets (blocking for FOUC prevention) -->
<link rel="stylesheet" href="<?php echo $theme_rel; ?>/css/bootstrap.css?ver=<?php echo $bootstrap_css_ver; ?>">
<link rel="stylesheet" href="<?php echo $theme_rel; ?>/css/lean-pages.css?ver=<?php echo $lean_css_ver; ?>">

<!-- Font Awesome (minimal subset - ~4KB vs 18KB) -->
<link rel="stylesheet" href="<?php echo $theme_rel; ?>/css/fontawesome-minimal.css?ver=<?php echo $fa_css_ver; ?>">

<!-- Bootstrap Icons (subset - ~2.6KB vs 86KB) -->
<link rel="stylesheet" href="<?php echo $theme_rel; ?>/css/bootstrap-icons.min.css?ver=<?php echo $bi_css_ver; ?>">

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
</style>

<?php do_action('lean_head'); ?>