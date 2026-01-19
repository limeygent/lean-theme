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
		<th scope="row"><label for="header_tagline">Header Tagline</label></th>
		<td>
			<input type="text" name="header_tagline" id="header_tagline" value="<?php echo esc_attr(get_option('header_tagline', '')); ?>" class="large-text">
			<p class="description">Displayed in the top header bar on desktop (when mode is set to "Tagline")</p>
		</td>
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
		<th scope="row"><label for="business_hours">Business Hours</label></th>
		<td>
			<textarea name="business_hours" id="business_hours" rows="5" class="large-text"><?php echo esc_textarea(get_option('business_hours', '')); ?></textarea>
			<p class="description">HTML allowed. Example: <code>&lt;p class="hours"&gt;Mon-Fri: 8am-5pm&lt;/p&gt;</code></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="service_area">Service Area</label></th>
		<td>
			<input type="text" name="service_area" id="service_area" value="<?php echo esc_attr(get_option('service_area', '')); ?>" class="regular-text" placeholder="Dallas-Fort Worth Metroplex">
			<p class="description">Displayed in footer</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="service_area_url">Service Area Link</label></th>
		<td>
			<input type="text" name="service_area_url" id="service_area_url" value="<?php echo esc_attr(get_option('service_area_url', '/service-areas/')); ?>" class="regular-text">
			<p class="description">URL for service area link</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="google_maps_cid">Google Maps CID</label></th>
		<td>
			<input type="text" name="google_maps_cid" id="google_maps_cid" value="<?php echo esc_attr(get_option('google_maps_cid', '')); ?>" class="regular-text">
			<p class="description">Your Google Business Profile CID (used if embed URL not set)</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="google_maps_embed_url">Google Maps Embed URL</label></th>
		<td>
			<input type="url" name="google_maps_embed_url" id="google_maps_embed_url" value="<?php echo esc_attr(get_option('google_maps_embed_url', '')); ?>" class="large-text">
			<p class="description">Full embed URL from Google Maps (takes priority over CID)</p>
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
	$header_top_mode = get_option('header_top_mode', 'tagline');
	$header_top_items = get_option('header_top_items', []);
	if (!is_array($header_top_items)) $header_top_items = [];
	?>

	<tr><td colspan="2"><h2>Logo</h2></td></tr>
	<tr>
		<th scope="row"><label for="business_logo_url">Logo URL</label></th>
		<td>
			<input type="url" name="business_logo_url" id="business_logo_url" value="<?php echo esc_attr(get_option('business_logo_url', '')); ?>" class="large-text" placeholder="https://...">
			<p class="description">Full URL to your logo image (displayed in header and footer)</p>
		</td>
	</tr>

	<tr><td colspan="2"><h2>Header Top Bar</h2></td></tr>
	<tr>
		<th scope="row"><label for="header_top_mode">Top Bar Mode</label></th>
		<td>
			<select name="header_top_mode" id="header_top_mode">
				<option value="none" <?php selected($header_top_mode, 'none'); ?>>None (hidden)</option>
				<option value="tagline" <?php selected($header_top_mode, 'tagline'); ?>>Simple Tagline + Phone</option>
				<option value="items" <?php selected($header_top_mode, 'items'); ?>>Custom Items</option>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="header_top_bg">Top Bar Background</label></th>
		<td><input type="text" name="header_top_bg" id="header_top_bg" value="<?php echo esc_attr(get_option('header_top_bg', '#f8f9fa')); ?>" class="regular-text" placeholder="#f8f9fa"></td>
	</tr>
	<tr>
		<th scope="row"><label for="header_top_text">Top Bar Text</label></th>
		<td><input type="text" name="header_top_text" id="header_top_text" value="<?php echo esc_attr(get_option('header_top_text', '#333333')); ?>" class="regular-text" placeholder="#333333"></td>
	</tr>

	<!-- Custom Header Items (shown when mode = items) -->
	<tr class="header-items-row">
		<th scope="row">Header Top Items</th>
		<td>
			<p class="description" style="margin-bottom:15px;">Configure up to 4 items for the top header bar. Types: badge (image), icon-box (icon + text), text, phone-button.</p>
			<?php for ($i = 0; $i < 4; $i++):
				$item = isset($header_top_items[$i]) ? $header_top_items[$i] : [];
			?>
			<fieldset style="border:1px solid #ccc; padding:15px; margin-bottom:15px; background:#fafafa;">
				<legend style="font-weight:bold;">Item <?php echo $i + 1; ?></legend>
				<table class="form-table" style="margin:0;">
					<tr>
						<th><label>Type</label></th>
						<td>
							<select name="header_top_items[<?php echo $i; ?>][type]">
								<option value="">-- None --</option>
								<option value="badge" <?php selected($item['type'] ?? '', 'badge'); ?>>Badge (Image)</option>
								<option value="icon-box" <?php selected($item['type'] ?? '', 'icon-box'); ?>>Icon Box</option>
								<option value="text" <?php selected($item['type'] ?? '', 'text'); ?>>Text Only</option>
								<option value="phone-button" <?php selected($item['type'] ?? '', 'phone-button'); ?>>Phone Button</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label>Icon (FA class)</label></th>
						<td><input type="text" name="header_top_items[<?php echo $i; ?>][icon]" value="<?php echo esc_attr($item['icon'] ?? ''); ?>" class="regular-text" placeholder="fa-star"></td>
					</tr>
					<tr>
						<th><label>Text</label></th>
						<td><input type="text" name="header_top_items[<?php echo $i; ?>][text]" value="<?php echo esc_attr($item['text'] ?? ''); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label>Subtext</label></th>
						<td><input type="text" name="header_top_items[<?php echo $i; ?>][subtext]" value="<?php echo esc_attr($item['subtext'] ?? ''); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label>Link URL</label></th>
						<td><input type="url" name="header_top_items[<?php echo $i; ?>][link]" value="<?php echo esc_attr($item['link'] ?? ''); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label>Image URL (badges)</label></th>
						<td><input type="url" name="header_top_items[<?php echo $i; ?>][image]" value="<?php echo esc_attr($item['image'] ?? ''); ?>" class="large-text"></td>
					</tr>
				</table>
			</fieldset>
			<?php endfor; ?>
		</td>
	</tr>

	<tr><td colspan="2"><h2>Header Navigation</h2></td></tr>
	<tr>
		<th scope="row"><label for="header_main_bg">Header Background</label></th>
		<td><input type="text" name="header_main_bg" id="header_main_bg" value="<?php echo esc_attr(get_option('header_main_bg', '#ffffff')); ?>" class="regular-text" placeholder="#ffffff"></td>
	</tr>
	<tr>
		<th scope="row"><label for="header_nav_text">Nav Text Color</label></th>
		<td><input type="text" name="header_nav_text" id="header_nav_text" value="<?php echo esc_attr(get_option('header_nav_text', '#333333')); ?>" class="regular-text" placeholder="#333333"></td>
	</tr>
	<tr>
		<th scope="row"><label for="dropdown_bg">Dropdown Background</label></th>
		<td><input type="text" name="dropdown_bg" id="dropdown_bg" value="<?php echo esc_attr(get_option('dropdown_bg', '#ffffff')); ?>" class="regular-text" placeholder="#ffffff"></td>
	</tr>
	<tr>
		<th scope="row"><label for="dropdown_text">Dropdown Text Color</label></th>
		<td><input type="text" name="dropdown_text" id="dropdown_text" value="<?php echo esc_attr(get_option('dropdown_text', '#333333')); ?>" class="regular-text" placeholder="#333333"></td>
	</tr>

	<tr><td colspan="2"><h2>Brand Colors</h2></td></tr>
	<tr>
		<th scope="row"><label for="primary_color">Primary Color</label></th>
		<td>
			<input type="text" name="primary_color" id="primary_color" value="<?php echo esc_attr(get_option('primary_color', '#0d6efd')); ?>" class="regular-text" placeholder="#0d6efd">
			<p class="description">Used for buttons, links, hero overlay (sets --brand CSS variable)</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="secondary_color">Accent Color</label></th>
		<td>
			<input type="text" name="secondary_color" id="secondary_color" value="<?php echo esc_attr(get_option('secondary_color', '#ffc107')); ?>" class="regular-text" placeholder="#ffc107">
			<p class="description">Used for callouts, highlights (sets --accent CSS variable)</p>
		</td>
	</tr>

	<tr><td colspan="2"><h2>Footer</h2></td></tr>
	<tr>
		<th scope="row"><label for="footer_bg">Footer Background</label></th>
		<td><input type="text" name="footer_bg" id="footer_bg" value="<?php echo esc_attr(get_option('footer_bg', '#212529')); ?>" class="regular-text" placeholder="#212529"></td>
	</tr>
	<tr>
		<th scope="row"><label for="footer_text">Footer Text Color</label></th>
		<td><input type="text" name="footer_text" id="footer_text" value="<?php echo esc_attr(get_option('footer_text', '#ffffff')); ?>" class="regular-text" placeholder="#ffffff"></td>
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
					<tr><td><code>[business_phone_url]</code></td><td>tel: URL only</td></tr>
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
	// Text fields
	$text_fields = array(
		'business_name', 'header_tagline', 'business_phone', 'business_address',
		'business_city', 'business_state', 'business_zip', 'service_area', 'service_area_url',
		'google_maps_cid', 'google_kgid', 'ga4_measurement_id', 'clarity_project_id',
		'primary_color', 'secondary_color',
		'header_top_mode', 'header_top_bg', 'header_top_text',
		'header_main_bg', 'header_nav_text', 'dropdown_bg', 'dropdown_text',
		'footer_bg', 'footer_text',
		'form_success_message', 'form_error_message'
	);

	foreach ($text_fields as $field) {
		if (isset($_POST[$field])) {
			update_option($field, sanitize_text_field($_POST[$field]));
		}
	}

	// URL fields
	$url_fields = array('business_url', 'business_logo_url', 'google_gmb_image_url', 'google_maps_embed_url');
	foreach ($url_fields as $field) {
		if (isset($_POST[$field])) {
			update_option($field, esc_url_raw($_POST[$field]));
		}
	}

	// HTML fields (allow safe HTML)
	if (isset($_POST['business_hours'])) {
		update_option('business_hours', wp_kses_post($_POST['business_hours']));
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

	// Header top items (array)
	if (isset($_POST['header_top_items']) && is_array($_POST['header_top_items'])) {
		$sanitized_items = [];
		foreach ($_POST['header_top_items'] as $item) {
			$sanitized_items[] = [
				'type'    => sanitize_text_field($item['type'] ?? ''),
				'icon'    => sanitize_text_field($item['icon'] ?? ''),
				'text'    => sanitize_text_field($item['text'] ?? ''),
				'subtext' => sanitize_text_field($item['subtext'] ?? ''),
				'link'    => esc_url_raw($item['link'] ?? ''),
				'image'   => esc_url_raw($item['image'] ?? ''),
			];
		}
		update_option('header_top_items', $sanitized_items);
	}
}

// ──────────────────────────────────────────────────────────────────────────────
// INJECT CUSTOM COLORS AS CSS VARIABLES
// ──────────────────────────────────────────────────────────────────────────────

function lean_theme_inject_custom_colors() {
	$primary_color = get_option('primary_color');
	$secondary_color = get_option('secondary_color');

	if (!$primary_color && !$secondary_color) {
		return;
	}

	echo '<style>:root{';

	if ($primary_color) {
		// Convert hex to RGB for rgba() usage
		$rgb = lean_hex_to_rgb($primary_color);
		echo '--brand:' . esc_attr($primary_color) . ';';
		echo '--brand-dark:' . esc_attr(lean_adjust_brightness($primary_color, -15)) . ';';
		echo '--brand-darker:' . esc_attr(lean_adjust_brightness($primary_color, -25)) . ';';
		if ($rgb) {
			echo '--brand-rgb:' . esc_attr($rgb['r'] . ',' . $rgb['g'] . ',' . $rgb['b']) . ';';
		}
	}

	if ($secondary_color) {
		echo '--accent:' . esc_attr($secondary_color) . ';';
	}

	echo '}</style>';
}
add_action('wp_head', 'lean_theme_inject_custom_colors');

/**
 * Convert hex color to RGB array
 */
function lean_hex_to_rgb($hex) {
	$hex = ltrim($hex, '#');
	if (strlen($hex) === 3) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if (strlen($hex) !== 6) {
		return false;
	}
	return [
		'r' => hexdec(substr($hex, 0, 2)),
		'g' => hexdec(substr($hex, 2, 2)),
		'b' => hexdec(substr($hex, 4, 2)),
	];
}

/**
 * Adjust hex color brightness
 * @param string $hex Hex color
 * @param int $percent Positive = lighter, Negative = darker
 */
function lean_adjust_brightness($hex, $percent) {
	$hex = ltrim($hex, '#');
	if (strlen($hex) === 3) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	$rgb = [
		hexdec(substr($hex, 0, 2)),
		hexdec(substr($hex, 2, 2)),
		hexdec(substr($hex, 4, 2)),
	];

	foreach ($rgb as &$color) {
		$color = max(0, min(255, $color + ($color * $percent / 100)));
		$color = round($color);
	}

	return sprintf('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
}

// ──────────────────────────────────────────────────────────────────────────────
// SHORTCODES
// ──────────────────────────────────────────────────────────────────────────────
// Business shortcodes have been moved to: inc/shortcodes/business-info.php
// Load via: inc/shortcodes.php
