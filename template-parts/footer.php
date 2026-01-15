<?php
/**
 * Lean Footer Template Part
 *
 * The optimized footer with business info, map, hours, and tracking scripts.
 *
 * Usage in page templates:
 *   <?php get_template_part('template-parts/footer'); ?>
 *
 * Settings used (Appearance > Lean Theme):
 *   - business_logo_url
 *   - business_hours (HTML)
 *   - google_maps_cid or google_maps_embed_url
 *   - service_area, service_area_url
 *   - footer_bg, footer_text (colors)
 */

// Get configurable values
$logo_url = get_option('business_logo_url', '');
$business_name = get_option('business_name', get_bloginfo('name'));
$google_maps_cid = get_option('google_maps_cid', '');
$google_maps_embed_url = get_option('google_maps_embed_url', '');
$business_hours = get_option('business_hours', '');
$service_area = get_option('service_area', '');
$service_area_url = get_option('service_area_url', '/service-areas/');

// Colors
$footer_bg = get_option('footer_bg', '#212529');
$footer_text = get_option('footer_text', '#ffffff');
?>
<!--  Footer  -->
<footer id="lean-footer" class="lean-footer" role="contentinfo" style="background-color: <?php echo esc_attr($footer_bg); ?>; color: <?php echo esc_attr($footer_text); ?>;">
	<div class="container">
		<div class="row">

			<!-- Column 1: Logo, Business Name, Address -->
			<div class="col-12 col-lg-4 mb-4 text-center text-lg-left">
				<?php if ($logo_url): ?>
				<img src="<?php echo esc_url($logo_url); ?>"
					 alt="<?php echo esc_attr($business_name); ?>"
					 width="300" height="169" loading="lazy" />
				<?php endif; ?>
				<div class="mt-3">
					<div class="footer-business-name" style="font-size: 1.5rem; margin-bottom: 10px;">
						<strong><?php echo esc_html($business_name); ?></strong>
					</div>
					<p style="font-size: 1.5rem; margin-bottom: 5px;"><strong>Address:</strong></p>
					<div class="footer-address" style="margin-bottom: 15px;">
						<?php echo do_shortcode('[business_full_address]'); ?>
					</div>
					<div class="phone-number">
						<p style="font-size: 1.5rem; margin-bottom: 5px;"><strong>Phone:</strong></p>
						<?php echo do_shortcode('[business_phone_link]'); ?>
					</div>
				</div>
			</div>

			<!-- Column 2: Google Map -->
			<?php
			$map_src = '';
			if ($google_maps_embed_url) {
				$map_src = $google_maps_embed_url;
			} elseif ($google_maps_cid) {
				$map_src = 'https://www.google.com/maps?cid=' . esc_attr($google_maps_cid) . '&output=embed';
			}
			if ($map_src):
			?>
			<div class="col-12 col-md-4 mb-4 d-none d-lg-block">
				<div class="map-container">
					<iframe
							src="<?php echo esc_url($map_src); ?>"
							width="100%" height="350" style="border:0;" allowfullscreen=""
							loading="lazy" referrerpolicy="no-referrer-when-downgrade"
							title="Map to <?php echo esc_attr($business_name); ?>"></iframe>
				</div>
			</div>
			<?php endif; ?>

			<!-- Column 3: Hours -->
			<div class="col-12 col-lg-4 mb-4 text-center text-lg-left">
				<?php if ($business_hours): ?>
				<p style="font-size: 1.5rem;"><strong>Hours:</strong></p>
				<?php echo wp_kses_post($business_hours); ?>
				<?php endif; ?>

				<?php if ($service_area): ?>
				<p class="mt-4 mb-1"><strong>Service Area:</strong></p>
				<p><a href="<?php echo esc_url($service_area_url); ?>"><?php echo esc_html($service_area); ?></a></p>
				<?php endif; ?>
			</div>

		</div>
	</div>

	<div class="footer-bottom">
		<p>&copy; <?php echo date('Y'); ?> <?php echo esc_html($business_name); ?> |
			<a title="terms of use link" href="<?php echo esc_url(home_url('/terms-of-use/')); ?>" target="_blank" rel="nofollow noopener">Terms of Use</a> |
			<a title="privacy policy link" href="<?php echo esc_url(home_url('/privacy-policy/')); ?>" target="_blank" rel="nofollow noopener">Privacy Policy</a> |
			<a title="accessibility link" href="<?php echo esc_url(home_url('/accessibility/')); ?>" target="_blank" rel="nofollow noopener">Accessibility</a>
		</p>
	</div>
</footer>

</div><!-- /#lean-root -->

<!-- Mobile Sticky Phone Button -->
<div class="d-md-none position-fixed w-100 shadow-lg" style="bottom: 0; left: 0; z-index: 1030;">
	<?php echo do_shortcode('[business_phone_button class="btn btn-warning btn-lg btn-block m-0 rounded-0"]'); ?>
</div>

<!-- Mobile Menu Toggle -->
<script>
	document.addEventListener('DOMContentLoaded', function () {
		var navToggle = document.getElementById('nav-toggle');
		var headerNav = document.querySelector('.header-nav');

		if (navToggle && headerNav) {
			navToggle.addEventListener('change', function() {
				headerNav.classList.toggle('is-open', this.checked);
			});
		}
	});
</script>

<!-- GA Event Tracking -->
<script>
	document.addEventListener('DOMContentLoaded', function() {
		if (typeof gtag !== 'function') return;

		// Track form submissions
		var formButton = document.querySelector('button[type="submit"]');
		if (formButton && formButton.closest('form')) {
			formButton.closest('form').addEventListener('submit', function() {
				gtag('event', 'button_click', {
					'event_category': 'engagement',
					'event_label': formButton.textContent.trim(),
					'page_path': window.location.pathname,
					'button_type': 'form_submit'
				});
			});
		}

		// Track phone clicks
		document.querySelectorAll('a[href^="tel:"]').forEach(function(btn) {
			btn.addEventListener('click', function() {
				gtag('event', 'button_click', {
					'event_category': 'engagement',
					'event_label': btn.textContent.trim(),
					'page_path': window.location.pathname,
					'button_type': 'phone_call'
				});
			});
		});
	});
</script>
