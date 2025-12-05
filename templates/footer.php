<?php
/**
 * Lean Footer Template Part
 *
 * The optimized footer with business info, map, hours, and tracking scripts.
 *
 * Usage in page templates:
 *   <?php get_template_part('templates/footer'); ?>
 */

$logo_url = get_option('business_logo_url', '');
$business_name = get_option('business_name', get_bloginfo('name'));
$google_maps_cid = get_option('google_maps_cid', '');
?>
<!--  Footer  -->
<footer id="lean-footer" class="lean-footer" role="contentinfo">
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
			<div class="col-12 col-md-4 mb-4 d-none d-lg-block">
				<?php if ($google_maps_cid): ?>
				<div class="map-container">
					<iframe
							src="https://www.google.com/maps?cid=<?php echo esc_attr($google_maps_cid); ?>&output=embed"
							width="100%" height="350" style="border:0;" allowfullscreen=""
							loading="lazy" referrerpolicy="no-referrer-when-downgrade"
							title="Map to <?php echo esc_attr($business_name); ?>"></iframe>
				</div>
				<?php endif; ?>
			</div>

			<!-- Column 3: Hours -->
			<div class="col-12 col-lg-4 mb-4 text-center text-lg-left">
				<p style="font-size: 1.5rem;"><strong>Hours:</strong></p>
				<div class="hours"><span>Monday</span>&nbsp;&nbsp;<span>8 AM - 5 PM</span></div>
				<div class="hours"><span>Tuesday</span>&nbsp;&nbsp;<span>8 AM - 5 PM</span></div>
				<div class="hours"><span>Wednesday</span>&nbsp;&nbsp;<span>8 AM - 5 PM</span></div>
				<div class="hours"><span>Thursday</span>&nbsp;&nbsp;<span>8 AM - 5 PM</span></div>
				<div class="hours"><span>Friday</span>&nbsp;&nbsp;<span>8 AM - 5 PM</span></div>
				<div class="hours"><span>Saturday</span>&nbsp;&nbsp;<span>Closed</span></div>
				<div class="hours"><span>Sunday</span>&nbsp;&nbsp;<span>Closed</span></div>

				<p class="mt-4 mb-1"><strong>Service Area:</strong></p>
				<p><a href="/service-areas/">Collin & East Denton Counties</a></p>
			</div>

		</div>
	</div>

	<div class="footer-bottom">
		<p>© <?php echo date('Y'); ?> <?php echo esc_html($business_name); ?> |
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

<!-- Submenu toggles -->
<script>
	document.addEventListener('DOMContentLoaded', function () {
		// Mobile menu toggle (hamburger open/close)
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
		// Track form submit button
		const formButton = document.querySelector('button[type="submit"]');
		if (formButton) {
			formButton.closest('form')?.addEventListener('submit', function(e) {
			const eventData = {
			'event_category': 'engagement',
				'event_label': formButton.textContent.trim(),
					'page_path': window.location.pathname,
						'button_type': 'form_submit'
		};

		// Debug output
		console.log('=== GA Event: Form Submit ===');
		console.log('Event Name:', 'button_click');
		console.log('Event Data:', eventData);
		console.log('Button Element:', formButton);
		console.log('============================');

		gtag('event', 'button_click', eventData);
	});
	}

	// Track ALL phone button clicks
	const phoneButtons = document.querySelectorAll('a[href^="tel:"]');
	phoneButtons.forEach(function(phoneButton) {
		phoneButton.addEventListener('click', function(e) {
			const eventData = {
				'event_category': 'engagement',
				'event_label': phoneButton.textContent.trim(),
				'page_path': window.location.pathname,
				'button_type': 'phone_call'
			};

			// Debug output
			console.log('=== GA Event: Phone Click ===');
			console.log('Event Name:', 'button_click');
			console.log('Event Data:', eventData);
			console.log('Button Element:', phoneButton);
			console.log('Phone Number:', phoneButton.href);
			console.log('=============================');

			gtag('event', 'button_click', eventData);
		});
	});
	});
</script>