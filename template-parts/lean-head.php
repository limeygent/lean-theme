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
<?php if (function_exists('lean_webmcp_origin_trial_meta')) lean_webmcp_origin_trial_meta(); // WebMCP declarative API enablement (no-op when no token set) ?>

<?php if ($gtm_id): ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo esc_js($gtm_id); ?>');</script>
<!-- End Google Tag Manager -->
<?php endif; ?>

<!-- Performance hints — only emit when the upstream is actually used -->
<?php if ($gtm_id): ?>
<link rel="preconnect" href="https://www.googletagmanager.com">
<?php endif; ?>
<?php if ($clarity_id): ?>
<link rel="preconnect" href="https://www.clarity.ms" crossorigin>
<?php endif; ?>

<!-- Preload the body font (Roboto) so font-display:optional finds it within its ~100ms
     window and actually USES it (otherwise the webfont times out -> fallback locked in,
     "webfont not used"). No ?ver: the href must match the @font-face src exactly, and
     crossorigin is required for font fetches or the browser double-downloads. -->
<link rel="preload" href="<?php echo $theme_rel; ?>/assets/fonts/roboto/roboto-v49-latin-regular.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?php echo $theme_rel; ?>/assets/fonts/roboto/roboto-v49-latin-700.woff2" as="font" type="font/woff2" crossorigin>

<!-- Favicon -->
<link rel="shortcut icon" href="/favicon.ico" />
<?php if (file_exists(ABSPATH . 'site.webmanifest')): ?>
<link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32" />
<link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16" />
<link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
<link rel="manifest" href="/site.webmanifest" />
<?php endif; ?>

<?php
// SEO <head> tags — title, description, canonical, robots, hreflang, Open Graph,
// Twitter, JSON-LD — are owned by the NerdPress SEO plugin and emitted via the
// lean_head action (do_action('lean_head') below). The plugin's hreflang is keyed
// to the canonical permalink and configurable in NerdPress SEO settings, so the
// theme no longer emits its own alternate links (those were duplicate and pointed
// at the non-canonical request URL). If the plugin is absent, lean-loader.php's
// lean_head fallback supplies a basic title/description/canonical/robots block.
?>

<?php
// Standalone emit point for the LCP hero preload. wp_head() is bypassed here, so this is
// where the preload must live in standalone mode (it lands early — before the stylesheets
// below — which is exactly where the preload scanner wants it). Prefer the ACF/query-var
// hero passed to this template; otherwise fall back to in-content detection, which is
// owned by lean_hero_image_url() in inc/performance.php (single source of truth, shared
// with the integration-mode wp_head path). lean_emit_hero_preload() de-dupes per request.
$lean_hero_url = ($hero_image && !empty($hero_image['url'])) ? $hero_image['url'] : '';
if (!$lean_hero_url && function_exists('lean_hero_image_url')) {
	$lean_hero_url = lean_hero_image_url(get_queried_object());
}
if (function_exists('lean_emit_hero_preload')) {
	echo '<!-- Preload hero image (LCP element) -->' . "\n";
	lean_emit_hero_preload($lean_hero_url);
} elseif ($lean_hero_url) {
	// Defensive fallback if performance.php didn't load for some reason.
	echo '<link rel="preload" href="' . esc_url($lean_hero_url) . '" as="image" fetchpriority="high">' . "\n";
}
?>

<!-- NB: CSS <link rel="preload"> hints intentionally removed. When Perfmatters
     "Remove Unused CSS" is active it inlines the used CSS and delays the full
     stylesheets, so preloading them just triggers "preloaded but not used" console
     warnings and wastes bandwidth. The blocking <link rel="stylesheet"> tags below
     are what Perfmatters processes; without Perfmatters they still load fine. -->

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

<?php if ($hero_image && !empty($hero_image['url'])): ?>
<style>
.hero-bg {
	background-image: url('<?php echo esc_url($hero_image['url']); ?>');
	background-size: cover;
	background-position: center;
	filter: saturate(1.05) brightness(.95);
	z-index: 0;
}
</style>
<?php endif; ?>

<?php
// Admin bar styles — only loaded when the WP admin bar is showing (logged-in users).
// We bypass wp_head(), so the styles WordPress would normally enqueue have to be added by hand.
if (is_admin_bar_showing()):
	$wp_ver = $GLOBALS['wp_version'];
?>
<link rel="stylesheet" id="dashicons-css" href="<?php echo esc_url(includes_url('css/dashicons.min.css') . '?ver=' . $wp_ver); ?>">
<link rel="stylesheet" id="admin-bar-css" href="<?php echo esc_url(includes_url('css/admin-bar.min.css') . '?ver=' . $wp_ver); ?>">
<style id="admin-bar-inline-css">
html { margin-top: 32px !important; }
@media screen and (max-width: 782px) {
	html { margin-top: 46px !important; }
}
</style>
<?php endif; ?>

<?php do_action('lean_head'); ?>