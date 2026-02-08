<?php
/**
 * Lean Footer Template Part
 *
 * The optimized footer with customizable widgets configured in Appearance > Lean Theme.
 *
 * Usage in page templates:
 *   <?php get_template_part('template-parts/lean-footer'); ?>
 *
 * Settings used (Appearance > Lean Theme > Footer Widgets):
 *   - footer_widget_1 through footer_widget_4 (HTML)
 *   - footer_bg, footer_text (colors)
 */

// Get footer colors
$footer_bg = get_option('footer_bg', '#212529');
$footer_text = get_option('footer_text', '#ffffff');
$business_name = get_option('business_name', get_bloginfo('name'));

// Get footer widgets and filter out empty ones
$widgets = [];
for ($i = 1; $i <= 4; $i++) {
	$widget_content = get_option('footer_widget_' . $i, '');
	if (!empty(trim($widget_content))) {
		$widgets[] = $widget_content;
	}
}

// Calculate Bootstrap column class based on widget count
$widget_count = count($widgets);
$col_class = 'col-12'; // Default for mobile
if ($widget_count === 4) {
	$col_class .= ' col-lg-3';
} elseif ($widget_count === 3) {
	$col_class .= ' col-lg-4';
} elseif ($widget_count === 2) {
	$col_class .= ' col-lg-6';
} elseif ($widget_count === 1) {
	$col_class .= ' col-lg-12';
}
?>
<script async src="https://online-booking.housecallpro.com/script.js?token=6b7fc522d39f48b6a21ef6e73d6ad96c&orgName=Staggs-Plumbing"></script>

<!--  Footer  -->
<footer id="lean-footer" class="lean-footer" role="contentinfo" style="background-color: <?php echo esc_attr($footer_bg); ?>; color: <?php echo esc_attr($footer_text); ?>;">
	<div class="container">
		<div class="row">
			<?php if (!empty($widgets)): ?>
				<?php foreach ($widgets as $widget): ?>
				<div class="<?php echo esc_attr($col_class); ?> mb-4">
					<?php
					// Process PHP code if present
					ob_start();
					eval('?>' . $widget);
					$processed_widget = ob_get_clean();
					// Then process shortcodes
					echo do_shortcode($processed_widget);
					?>
				</div>
				<?php endforeach; ?>
			<?php else: ?>
				<!-- Fallback if no widgets configured -->
				<div class="col-12 text-center">
					<p>Configure footer widgets in <strong>Appearance > Lean Theme > Footer Widgets</strong></p>
				</div>
			<?php endif; ?>
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
	<?php echo do_shortcode('[business_phone_button class="btn btn-warning btn-lg w-100 d-block m-0 rounded-0"]'); ?>
</div>

<?php do_action('lean_footer'); ?>

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
