<?php
/**
 * Lean Header Template Part
 *
 * The optimized header markup with CSS-only mobile menu.
 * Includes: Google Analytics, skip link, and full header.
 *
 * Usage in page templates (after <body> tag):
 *   <?php get_template_part('templates/header'); ?>
 */

$ga4_id = get_option('ga4_measurement_id', '');
$logo_url = get_option('business_logo_url', '');
$business_name = get_option('business_name', get_bloginfo('name'));
?>

<?php if ($ga4_id): ?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($ga4_id); ?>"></script>
<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date());
	gtag('config', '<?php echo esc_js($ga4_id); ?>');
</script>
<?php endif; ?>

<?php wp_body_open(); ?>
<a class="lean-skip" href="#lean-main">Skip to content</a>
<div id="lean-root" class="lean-root">

	<header id="lean-header" class="lean-header header">
		<!-- Desktop – Top header: header text left, phone right -->
		<div class="header-top d-none d-lg-block">
			<div class="container">
				<div class="row">
					<div class="col-lg-6 align-self-center">
						Worry-free Pool Service for Busy People
					</div>
					<div class="col-lg-6 text-right align-self-center">
						<?php echo do_shortcode('[business_phone_link]'); ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Main header: logo + nav (responsive) -->
		<div class="header-main">
			<div class="container">
				<div class="d-flex align-items-center justify-content-between py-2">
					<!-- Logo - fixed left -->
					<div class="header-logo">
						<a href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr($business_name); ?>">
							<?php if ($logo_url): ?>
							<img src="<?php echo esc_url($logo_url); ?>"
								 alt="<?php echo esc_attr($business_name); ?>" width="220" height="55" loading="eager">
							<?php else: ?>
							<span class="site-title"><?php echo esc_html($business_name); ?></span>
							<?php endif; ?>
						</a>
					</div>

					<!-- Mobile: Phone button - centered (hidden on desktop) -->
					<div class="d-flex d-lg-none align-items-center">
						<?php echo do_shortcode('[business_phone_button class="btn btn-warning"]'); ?>
					</div>

					<!-- Mobile: Hamburger - fixed right (hidden on desktop) -->
					<div class="d-flex d-lg-none align-items-center">
						<input type="checkbox" id="nav-toggle" class="nav-toggle">
						<label for="nav-toggle" class="nav-toggle-open mb-0" aria-label="Toggle navigation">
							<span></span>
						</label>
					</div>

					<!-- Navigation (single menu for all breakpoints) -->
					<nav class="header-nav">
						<!-- Close button for mobile (inside nav) -->
						<label for="nav-toggle" class="nav-close d-lg-none" aria-label="Close navigation">×</label>
						<?php
						wp_nav_menu([
							'theme_location' => 'primary',
							'container'      => false,
							'menu_class'     => 'header-menu list-unstyled mb-0',
							'fallback_cb'    => false,
						]);
						?>
					</nav>
				</div>
			</div>
		</div>
	</header>