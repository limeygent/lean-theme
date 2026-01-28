<?php
/**
 * Lean Header Template Part
 *
 * The optimized header markup with CSS-only mobile menu.
 * Includes: Google Analytics, skip link, and full header.
 *
 * Usage in page templates (after <body> tag):
 *   <?php get_template_part('template-parts/lean-header'); ?>
 *
 * Settings used (Appearance > Lean Theme):
 *   - ga4_measurement_id
 *   - header_tagline
 *   - business_logo_url
 *   - header_top_mode (tagline, items, none)
 *   - header_top_items (array of custom items)
 *   - header_top_bg, header_top_text (colors)
 *   - header_main_bg, header_nav_text (colors)
 *   - dropdown_bg, dropdown_text (colors)
 */

// Get configurable values
$ga4_id = get_option('ga4_measurement_id', '');
$logo_url = get_option('business_logo_url', '');
$business_name = get_option('business_name', get_bloginfo('name'));
$header_tagline = get_option('header_tagline', '');

// Header top bar settings
$header_top_mode = get_option('header_top_mode', 'tagline');
$header_top_items = get_option('header_top_items', []);
if (!is_array($header_top_items)) $header_top_items = [];

// Colors
$header_top_bg = get_option('header_top_bg', '#f8f9fa');
$header_top_text = get_option('header_top_text', '#333333');
$header_main_bg = get_option('header_main_bg', '#ffffff');
$header_nav_text = get_option('header_nav_text', '#333333');
$dropdown_bg = get_option('dropdown_bg', '#ffffff');
$dropdown_text = get_option('dropdown_text', '#333333');
?>

<?php
// Get GTM ID to determine which analytics to use
$gtm_id = get_option('gtm_container_id', '');
?>

<?php if (!$gtm_id && $ga4_id): ?>
<!-- Google tag (gtag.js) - only if GTM not set -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($ga4_id); ?>"></script>
<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date());
	gtag('config', '<?php echo esc_js($ga4_id); ?>');
</script>
<?php endif; ?>

<?php wp_body_open(); ?>

<?php if ($gtm_id): ?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr($gtm_id); ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<?php endif; ?>

<a class="lean-skip" href="#lean-main">Skip to content</a>
<div id="lean-root" class="lean-root">

	<header id="lean-header" class="lean-header header">
		<?php if ($header_top_mode !== 'none'): ?>
		<!-- Desktop – Top header bar -->
		<div class="header-top d-none d-lg-block" style="background-color: <?php echo esc_attr($header_top_bg); ?>; color: <?php echo esc_attr($header_top_text); ?>;">
			<div class="container">
				<?php if ($header_top_mode === 'tagline'): ?>
				<!-- Simple tagline mode -->
				<div class="row">
					<div class="col-lg-6 align-self-center">
						<?php echo esc_html($header_tagline); ?>
					</div>
					<div class="col-lg-6 text-right align-self-center">
						<?php echo do_shortcode('[business_phone_link]'); ?>
					</div>
				</div>
				<?php elseif ($header_top_mode === 'items'): ?>
				<!-- Custom items mode -->
				<div class="header-top-items d-flex justify-content-between align-items-center py-2">
					<?php
					$phone = get_option('business_phone', '');
					$phone_clean = preg_replace('/[^0-9]/', '', $phone);

					foreach ($header_top_items as $item):
						if (empty($item['type'])) continue;

						$link_start = '';
						$link_end = '';
						if (!empty($item['link'])) {
							$link_start = '<a href="' . esc_url($item['link']) . '" class="header-item-link">';
							$link_end = '</a>';
						}

						switch ($item['type']):
							case 'badge':
								if (!empty($item['image'])):
									echo $link_start;
									echo '<div class="header-item header-badge">';
									echo '<img src="' . esc_url($item['image']) . '" alt="' . esc_attr($item['text']) . '" class="header-badge-img">';
									echo '</div>';
									echo $link_end;
								endif;
								break;

							case 'icon-box':
								echo $link_start;
								echo '<div class="header-item header-icon-box">';
								if (!empty($item['icon'])):
									echo '<span class="header-icon"><i class="fas ' . esc_attr($item['icon']) . '"></i></span>';
								endif;
								echo '<span class="header-text">';
								echo esc_html($item['text']);
								if (!empty($item['subtext'])):
									echo '<small class="d-block">' . esc_html($item['subtext']) . '</small>';
								endif;
								echo '</span>';
								echo '</div>';
								echo $link_end;
								break;

							case 'text':
								echo $link_start;
								echo '<div class="header-item header-text-only">';
								echo esc_html($item['text']);
								if (!empty($item['subtext'])):
									echo '<small class="d-block">' . esc_html($item['subtext']) . '</small>';
								endif;
								echo '</div>';
								echo $link_end;
								break;

							case 'phone-button':
								if (!empty($phone)):
									echo '<div class="header-item header-phone-btn">';
									if (!empty($item['subtext'])):
										echo '<span class="phone-subtext">' . esc_html($item['subtext']) . '</span>';
									endif;
									echo '<a href="tel:+1' . esc_attr($phone_clean) . '" class="btn btn-danger">' . esc_html($phone) . '</a>';
									echo '</div>';
								endif;
								break;
						endswitch;
					endforeach;
					?>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?php endif; ?>

		<!-- Main header: logo + nav (responsive) -->
		<div class="header-main" style="background-color: <?php echo esc_attr($header_main_bg); ?>; --nav-color: <?php echo esc_attr($header_nav_text); ?>; --dropdown-bg: <?php echo esc_attr($dropdown_bg); ?>; --dropdown-text: <?php echo esc_attr($dropdown_text); ?>;">
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
						$menu_location = get_option('lean_menu_location', 'primary');
						if ($menu_location) {
							wp_nav_menu([
								'theme_location' => $menu_location,
								'container'      => false,
								'menu_class'     => 'header-menu list-unstyled mb-0',
								'fallback_cb'    => false,
							]);
						}
						?>
					</nav>
				</div>
			</div>
		</div>
	</header>
