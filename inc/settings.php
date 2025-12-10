<?php
/**
 * Filename: settings.php
 * Purpose: Lean Theme settings page and shortcodes
 *
 * Creates: Appearance > Lean Theme Settings
 * Includes all business info, analytics, form settings, and shortcodes
 */

// ──────────────────────────────────────────────────────────────────────────────
// ADMIN MENU & PAGE
// ──────────────────────────────────────────────────────────────────────────────

add_action('admin_menu', 'lean_theme_add_settings_page');

function lean_theme_add_settings_page() {
	add_theme_page(
		'Lean Theme Settings',
		'Lean Theme',
		'manage_options',
		'lean-theme-settings',
		'lean_theme_settings_page'
	);
}

function lean_theme_settings_page() {
	if (!current_user_can('manage_options')) {
		return;
	}

	// Save settings if form submitted
	if (isset($_POST['lean_theme_nonce']) && wp_verify_nonce($_POST['lean_theme_nonce'], 'lean_theme_save_settings')) {
		lean_theme_save_settings();
		echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
	}

	// Get current tab
	$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'business';
	?>
	<div class="wrap">
		<h1>Lean Theme Settings</h1>

		<nav class="nav-tab-wrapper">
			<a href="?page=lean-theme-settings&tab=business" class="nav-tab <?php echo $active_tab === 'business' ? 'nav-tab-active' : ''; ?>">Business Info</a>
			<a href="?page=lean-theme-settings&tab=appearance" class="nav-tab <?php echo $active_tab === 'appearance' ? 'nav-tab-active' : ''; ?>">Appearance</a>
			<a href="?page=lean-theme-settings&tab=analytics" class="nav-tab <?php echo $active_tab === 'analytics' ? 'nav-tab-active' : ''; ?>">Analytics</a>
			<a href="?page=lean-theme-settings&tab=forms" class="nav-tab <?php echo $active_tab === 'forms' ? 'nav-tab-active' : ''; ?>">Contact Form</a>
			<a href="?page=lean-theme-settings&tab=shortcodes" class="nav-tab <?php echo $active_tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>">Shortcodes</a>
		</nav>

		<form method="post" action="">
			<?php wp_nonce_field('lean_theme_save_settings', 'lean_theme_nonce'); ?>
			<input type="hidden" name="active_tab" value="<?php echo esc_attr($active_tab); ?>">

			<table class="form-table" role="presentation">
				<?php
				switch ($active_tab) {
					case 'business':
						lean_theme_business_fields();
						break;
					case 'appearance':
						lean_theme_appearance_fields();
						break;
					case 'analytics':
						lean_theme_analytics_fields();
						break;
					case 'forms':
						lean_theme_form_fields();
						break;
					case 'shortcodes':
						lean_theme_shortcodes_reference();
						break;
				}
				?>
			</table>

			<?php if ($active_tab !== 'shortcodes'): ?>
			<?php submit_button('Save Settings'); ?>
			<?php endif; ?>
		</form>
	</div>
	<?php
}

// ──────────────────────────────────────────────────────────────────────────────
// SETTINGS FIELDS BY TAB
// ──────────────────────────────────────────────────────────────────────────────

function lean_theme_business_fields() {
	?>
	<tr>
		<th scope="row"><label for="business_name">Business Name</label></th>
		<td><input type="text" name="business_name" id="business_name" value="<?php echo esc_attr(get_option('business_name', '')); ?>" class="regular-text"></td>
	</tr>
	<tr>
		<th scope="row"><label for="business_url">Website URL</label></th>
		<td><input type="url" name="business_url" id="business_url" value="<?php echo esc_attr(get_option('business_url', home_url())); ?>" class="regular-text"></td>
	</tr>
	<tr>
		<th scope="row"><label for="business_phone">Phone Number</label></th>
		<td><input type="text" name="business_phone" id="business_phone" value="<?php echo esc_attr(get_option('business_phone', '')); ?>" class="regular-text" placeholder="(555) 555-5555"></td>
	</tr>
	<tr>
		<th scope="row"><label for="business_address">Street Address</label></th>
		<td><input type="text" name="business_address" id="business_address" value="<?php echo esc_attr(get_option('business_address', '')); ?>" class="regular-text"></td>
	</tr>
	<tr>
		<th scope="row"><label for="business_city">City</label></th>
		<td><input type="text" name="business_city" id="business_city" value="<?php echo esc_attr(get_option('business_city', '')); ?>" class="regular-text"></td>
	</tr>
	<tr>
		<th scope="row"><label for="business_state">State</label></th>
		<td><input type="text" name="business_state" id="business_state" value="<?php echo esc_attr(get_option('business_state', '')); ?>" class="small-text" maxlength="2" placeholder="TX"></td>
	</tr>
	<tr>
		<th scope="row"><label for="business_zip">ZIP Code</label></th>
		<td><input type="text" name="business_zip" id="business_zip" value="<?php echo esc_attr(get_option('business_zip', '')); ?>" class="small-text" maxlength="10"></td>
	</tr>
	<tr>
		<th scope="row"><label for="google_maps_cid">Google Maps CID</label></th>
		<td>
			<input type="text" name="google_maps_cid" id="google_maps_cid" value="<?php echo esc_attr(get_option('google_maps_cid', '')); ?>" class="regular-text">
			<p class="description">Your Google Business Profile CID (for map embed)</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="google_kgid">Google Knowledge Graph ID</label></th>
		<td><input type="text" name="google_kgid" id="google_kgid" value="<?php echo esc_attr(get_option('google_kgid', '')); ?>" class="regular-text"></td>
	</tr>
	<tr>
		<th scope="row"><label for="google_gmb_image_url">GMB Image URL</label></th>
		<td>
			<input type="url" name="google_gmb_image_url" id="google_gmb_image_url" value="<?php echo esc_attr(get_option('google_gmb_image_url', '')); ?>" class="large-text" placeholder="https://...">
			<p class="description">Google My Business profile image URL (for map overlay)</p>
		</td>
	</tr>
	<?php
}

function lean_theme_appearance_fields() {
	?>
	<tr>
		<th scope="row"><label for="business_logo_url">Logo URL</label></th>
		<td>
			<input type="url" name="business_logo_url" id="business_logo_url" value="<?php echo esc_attr(get_option('business_logo_url', '')); ?>" class="large-text" placeholder="https://...">
			<p class="description">Full URL to your logo image (displayed in header and footer)</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="primary_color">Primary Color</label></th>
		<td><input type="text" name="primary_color" id="primary_color" value="<?php echo esc_attr(get_option('primary_color', '')); ?>" class="regular-text" placeholder="#0073aa"></td>
	</tr>
	<tr>
		<th scope="row"><label for="secondary_color">Secondary Color</label></th>
		<td><input type="text" name="secondary_color" id="secondary_color" value="<?php echo esc_attr(get_option('secondary_color', '')); ?>" class="regular-text" placeholder="#23282d"></td>
	</tr>
	<tr>
		<th scope="row"><label for="footer_color">Footer Color</label></th>
		<td><input type="text" name="footer_color" id="footer_color" value="<?php echo esc_attr(get_option('footer_color', '')); ?>" class="regular-text" placeholder="#1d2327"></td>
	</tr>
	<?php
}

function lean_theme_analytics_fields() {
	?>
	<tr>
		<th scope="row"><label for="ga4_measurement_id">GA4 Measurement ID</label></th>
		<td>
			<input type="text" name="ga4_measurement_id" id="ga4_measurement_id" value="<?php echo esc_attr(get_option('ga4_measurement_id', '')); ?>" class="regular-text" placeholder="G-XXXXXXXXXX">
			<p class="description">Your Google Analytics 4 Measurement ID (starts with G-)</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="clarity_project_id">MS Clarity Project ID</label></th>
		<td>
			<input type="text" name="clarity_project_id" id="clarity_project_id" value="<?php echo esc_attr(get_option('clarity_project_id', '')); ?>" class="regular-text">
			<p class="description">Your Microsoft Clarity Project ID</p>
		</td>
	</tr>
	<?php
}

function lean_theme_form_fields() {
	?>
	<tr>
		<th scope="row"><label for="form_recipient_email">Recipient Email(s)</label></th>
		<td>
			<input type="text" name="form_recipient_email" id="form_recipient_email" value="<?php echo esc_attr(get_option('form_recipient_email', '')); ?>" class="regular-text">
			<p class="description">Comma-separated for multiple recipients</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="form_from_email">"From" Email</label></th>
		<td>
			<input type="email" name="form_from_email" id="form_from_email" value="<?php echo esc_attr(get_option('form_from_email', '')); ?>" class="regular-text">
			<p class="description">The "From" address for form notification emails</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="form_success_message">Success Message</label></th>
		<td><input type="text" name="form_success_message" id="form_success_message" value="<?php echo esc_attr(get_option('form_success_message', "Message sent. We'll contact you within 24 hours.")); ?>" class="large-text"></td>
	</tr>
	<tr>
		<th scope="row"><label for="form_error_message">Error Message</label></th>
		<td><input type="text" name="form_error_message" id="form_error_message" value="<?php echo esc_attr(get_option('form_error_message', 'Error sending message. Please call us directly.')); ?>" class="large-text"></td>
	</tr>
	<tr>
		<th scope="row">Confirmation Email</th>
		<td>
			<label>
				<input type="checkbox" name="form_send_confirmation" value="1" <?php checked(get_option('form_send_confirmation'), '1'); ?>>
				Send confirmation email to the person who submitted the form
			</label>
		</td>
	</tr>
	<?php
}

function lean_theme_shortcodes_reference() {
	?>
	<tr>
		<td colspan="2">
			<h2>Available Shortcodes</h2>
			<p>Use these shortcodes in your pages and posts:</p>

			<h3>Business Information</h3>
			<table class="widefat striped" style="max-width:600px;">
				<thead><tr><th>Shortcode</th><th>Output</th></tr></thead>
				<tbody>
					<tr><td><code>[business_name]</code></td><td><?php echo esc_html(get_option('business_name', '(not set)')); ?></td></tr>
					<tr><td><code>[business_phone]</code></td><td><?php echo esc_html(get_option('business_phone', '(not set)')); ?></td></tr>
					<tr><td><code>[business_phone_link]</code></td><td>Clickable phone link</td></tr>
					<tr><td><code>[business_phone_button class="btn btn-primary"]</code></td><td>Phone link with custom class</td></tr>
					<tr><td><code>[business_address]</code></td><td><?php echo esc_html(get_option('business_address', '(not set)')); ?></td></tr>
					<tr><td><code>[business_city]</code></td><td><?php echo esc_html(get_option('business_city', '(not set)')); ?></td></tr>
					<tr><td><code>[business_state]</code></td><td><?php echo esc_html(get_option('business_state', '(not set)')); ?></td></tr>
					<tr><td><code>[business_zip]</code></td><td><?php echo esc_html(get_option('business_zip', '(not set)')); ?></td></tr>
					<tr><td><code>[business_full_address]</code></td><td>Full formatted address</td></tr>
					<tr><td><code>[business_url]</code></td><td><?php echo esc_html(get_option('business_url', home_url())); ?></td></tr>
					<tr><td><code>[business_logo_url]</code></td><td>Logo image URL</td></tr>
				</tbody>
			</table>

			<h3 style="margin-top:20px;">Google</h3>
			<table class="widefat striped" style="max-width:600px;">
				<thead><tr><th>Shortcode</th><th>Description</th></tr></thead>
				<tbody>
					<tr><td><code>[google_maps_cid]</code></td><td>Google Maps CID</td></tr>
					<tr><td><code>[google_kgid]</code></td><td>Knowledge Graph ID</td></tr>
					<tr><td><code>[google_gmb_image_url]</code></td><td>GMB Image URL</td></tr>
					<tr><td><code>[map_embed]</code></td><td>Google Map with GMB overlay</td></tr>
				</tbody>
			</table>

			<h3 style="margin-top:20px;">Content</h3>
			<table class="widefat striped" style="max-width:600px;">
				<thead><tr><th>Shortcode</th><th>Description</th></tr></thead>
				<tbody>
					<tr><td><code>[lean_form]</code></td><td>Contact form</td></tr>
					<tr><td><code>[testimonials num_reviews="6"]</code></td><td>Display testimonials</td></tr>
					<tr><td><code>[latest_blog_post]</code></td><td>Link to latest post</td></tr>
				</tbody>
			</table>
		</td>
	</tr>
	<?php
}

// ──────────────────────────────────────────────────────────────────────────────
// SAVE SETTINGS
// ──────────────────────────────────────────────────────────────────────────────

function lean_theme_save_settings() {
	// Business info
	$text_fields = array(
		'business_name', 'business_phone', 'business_address',
		'business_city', 'business_state', 'business_zip',
		'google_maps_cid', 'google_kgid', 'ga4_measurement_id', 'clarity_project_id',
		'primary_color', 'secondary_color', 'footer_color',
		'form_success_message', 'form_error_message'
	);

	foreach ($text_fields as $field) {
		if (isset($_POST[$field])) {
			update_option($field, sanitize_text_field($_POST[$field]));
		}
	}

	// URL fields
	$url_fields = array('business_url', 'business_logo_url', 'google_gmb_image_url');
	foreach ($url_fields as $field) {
		if (isset($_POST[$field])) {
			update_option($field, esc_url_raw($_POST[$field]));
		}
	}

	// Email fields
	if (isset($_POST['form_recipient_email'])) {
		update_option('form_recipient_email', sanitize_text_field($_POST['form_recipient_email']));
	}
	if (isset($_POST['form_from_email'])) {
		update_option('form_from_email', sanitize_email($_POST['form_from_email']));
	}

	// Checkbox
	update_option('form_send_confirmation', isset($_POST['form_send_confirmation']) ? '1' : '');
}

// ──────────────────────────────────────────────────────────────────────────────
// INJECT CUSTOM COLORS
// ──────────────────────────────────────────────────────────────────────────────

function lean_theme_inject_custom_colors() {
	$footer_color = get_option('footer_color');
	$primary_color = get_option('primary_color');
	$secondary_color = get_option('secondary_color');

	if (!$footer_color && !$primary_color && !$secondary_color) {
		return;
	}

	echo '<style>';
	if ($footer_color) {
		echo '.footer_color { background-color: ' . esc_attr($footer_color) . ' !important; }';
	}
	if ($primary_color) {
		echo '.primary_color { background-color: ' . esc_attr($primary_color) . ' !important; }';
	}
	if ($secondary_color) {
		echo '.secondary_color { background-color: ' . esc_attr($secondary_color) . ' !important; }';
	}
	echo '</style>';
}
add_action('wp_head', 'lean_theme_inject_custom_colors');

// ──────────────────────────────────────────────────────────────────────────────
// SHORTCODES
// ──────────────────────────────────────────────────────────────────────────────

// Business name
add_shortcode('business_name', function() {
	return esc_html(get_option('business_name', ''));
});

// Business address
add_shortcode('business_address', function() {
	return esc_html(get_option('business_address', ''));
});

// Business city
add_shortcode('business_city', function() {
	return esc_html(get_option('business_city', ''));
});

// Business state
add_shortcode('business_state', function() {
	return esc_html(get_option('business_state', ''));
});

// Business zip
add_shortcode('business_zip', function() {
	return esc_html(get_option('business_zip', ''));
});

// Business URL
add_shortcode('business_url', function() {
	return esc_url(get_option('business_url', home_url()));
});

// Business logo URL
add_shortcode('business_logo_url', function() {
	return esc_url(get_option('business_logo_url', ''));
});

// Full address
add_shortcode('business_full_address', function() {
	$address = get_option('business_address', '');
	$city = get_option('business_city', '');
	$state = get_option('business_state', '');
	$zip = get_option('business_zip', '');

	$parts = array_filter([$address, $city, trim($state . ' ' . $zip)]);
	return esc_html(implode(', ', $parts));
});

// Phone (plain text)
add_shortcode('business_phone', function() {
	return esc_html(get_option('business_phone', ''));
});

// Phone link
add_shortcode('business_phone_link', function() {
	$phone = get_option('business_phone', '');
	$href_phone = preg_replace('/\D/', '', $phone);
	return '<a href="tel:+1' . esc_attr($href_phone) . '">' . esc_html($phone) . '</a>';
});

// Phone button with custom text/class
add_shortcode('business_phone_button', function($atts) {
	$phone = get_option('business_phone', '');
	$href_phone = preg_replace('/\D/', '', $phone);

	$atts = shortcode_atts(array(
		'text' => $phone,
		'class' => '',
	), $atts);

	$class_attr = $atts['class'] ? ' class="' . esc_attr($atts['class']) . '"' : '';
	return '<a href="tel:+1' . esc_attr($href_phone) . '"' . $class_attr . '>' . esc_html($atts['text']) . '</a>';
});

// Google Maps CID
add_shortcode('google_maps_cid', function() {
	return esc_html(get_option('google_maps_cid', ''));
});

// Google Knowledge Graph ID
add_shortcode('google_kgid', function() {
	return esc_html(get_option('google_kgid', ''));
});

// Google GMB Image URL
add_shortcode('google_gmb_image_url', function() {
	return esc_url(get_option('google_gmb_image_url', ''));
});
