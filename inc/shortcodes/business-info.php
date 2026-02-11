<?php
/**
 * Filename: business-info.php
 * Purpose: Business information shortcodes
 *
 * Shortcodes:
 * - [business_name]         - Business name
 * - [business_address]      - Street address
 * - [business_city]         - City
 * - [business_state]        - State abbreviation
 * - [business_zip]          - ZIP code
 * - [business_full_address] - Full formatted address
 * - [business_url]          - Website URL
 * - [business_logo_url]     - Logo image URL
 * - [business_phone]        - Phone number (plain text)
 * - [business_phone_url]    - Phone tel: URL only
 * - [business_phone_link]   - Clickable phone link
 * - [business_phone_button]      - Phone link with custom text/class
 * - [lean_business_phone_button] - Phone button with icon/variant (migration shortcode)
 * - [business_booking]            - Booking button/link/custom (type, class, text attrs)
 * - [google_maps_cid]       - Google Maps CID
 * - [google_kgid]           - Google Knowledge Graph ID
 * - [google_gmb_image_url]  - GMB Image URL
 *
 * All values come from Lean Theme Settings (Appearance > Lean Theme)
 */
// ──────────────────────────────────────────────────────────────────────────────
// BASIC BUSINESS INFO
// ──────────────────────────────────────────────────────────────────────────────

add_shortcode('business_name', function() {
	return esc_html(get_option('business_name', ''));
});

add_shortcode('business_address', function() {
	return esc_html(get_option('business_address', ''));
});

add_shortcode('business_city', function() {
	return esc_html(get_option('business_city', ''));
});

add_shortcode('business_state', function() {
	return esc_html(get_option('business_state', ''));
});

add_shortcode('business_zip', function() {
	return esc_html(get_option('business_zip', ''));
});

add_shortcode('business_url', function() {
	return esc_url(get_option('business_url', home_url()));
});

add_shortcode('business_logo_url', function() {
	return esc_url(get_option('business_logo_url', ''));
});

// Full formatted address
add_shortcode('business_full_address', function() {
	$address = get_option('business_address', '');
	$city = get_option('business_city', '');
	$state = get_option('business_state', '');
	$zip = get_option('business_zip', '');

	$parts = array_filter([$address, $city, trim($state . ' ' . $zip)]);
	return esc_html(implode(', ', $parts));
});

// ──────────────────────────────────────────────────────────────────────────────
// PHONE SHORTCODES
// ──────────────────────────────────────────────────────────────────────────────

// Plain text phone number
add_shortcode('business_phone', function() {
	return esc_html(get_option('business_phone', ''));
});

// Phone URL only (tel:+1XXXXXXXXXX)
add_shortcode('business_phone_url', function() {
	$phone = get_option('business_phone', '');
	$href_phone = preg_replace('/\D/', '', $phone);
	return 'tel:+1' . esc_attr($href_phone);
});

// Clickable phone link
add_shortcode('business_phone_link', function() {
	$phone = get_option('business_phone', '');
	$href_phone = preg_replace('/\D/', '', $phone);
	return '<a href="tel:+1' . esc_attr($href_phone) . '">' . esc_html($phone) . '</a>';
});

// Phone button with custom text and class
// Usage: [business_phone_button text="Call Now" class="btn btn-primary"]
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

// Phone button with icon and variant support (lean_ prefix avoids conflict with parent theme)
// Usage: [lean_business_phone_button text="Call Now" class="btn btn-primary btn-lg px-5" icon="bi-telephone"]
//        [lean_business_phone_button variant="orange"]
function lean_business_phone_button_shortcode( $atts ) {
	$business_phone = get_option('business_phone', '');
	$href_phone = preg_replace('/\D/', '', $business_phone);
	$atts = shortcode_atts( array(
		'text'    => $business_phone,
		'class'   => 'btn btn-outline-light btn-lg px-5',
		'icon'    => 'bi-telephone',
		'variant' => '',
	), $atts );

	if ( $atts['variant'] === 'orange' ) {
		$atts['class'] = 'btn btn-sp-orange btn-lg px-5';
	}

	return '<a href="tel:+1' . $href_phone . '" class="' . esc_attr( $atts['class'] ) . '">'
		 . '<i class="bi ' . esc_attr( $atts['icon'] ) . ' me-2"></i>'
		 . esc_html( $atts['text'] )
		 . '</a>';
}
add_shortcode('lean_business_phone_button', 'lean_business_phone_button_shortcode');

// ──────────────────────────────────────────────────────────────────────────────
// BOOKING SHORTCODE
// ──────────────────────────────────────────────────────────────────────────────

// Booking button, link, or custom widget
// Usage: [business_booking]
//        [business_booking class="btn btn-sp-orange btn-lg fw-semibold" text="Book Today"]
//        [business_booking type="link" text="Schedule Online"]
//        [business_booking type="custom"]
add_shortcode('business_booking', function($atts) {
	$booking_code = get_option('booking_code', '');
	if (empty($booking_code)) {
		return '';
	}

	$atts = shortcode_atts(array(
		'type'  => 'button',
		'text'  => 'Book Now',
		'class' => 'btn btn-primary',
	), $atts);

	$type = $atts['type'];

	// Custom: output HTML, replacing {text} placeholder with text attribute
	if ($type === 'custom') {
		return str_replace('{text}', esc_html($atts['text']), $booking_code);
	}

	// Button and link: treat booking_code as a URL
	$url = esc_url($booking_code);
	if (empty($url)) {
		return '';
	}

	$class_attr = $atts['class'] ? ' class="' . esc_attr($atts['class']) . '"' : '';
	$role_attr = ($type === 'button') ? ' role="button"' : '';

	return '<a href="' . $url . '"' . $class_attr . $role_attr . '>' . esc_html($atts['text']) . '</a>';
});

// ──────────────────────────────────────────────────────────────────────────────
// GOOGLE SHORTCODES
// ──────────────────────────────────────────────────────────────────────────────

add_shortcode('google_maps_cid', function() {
	return esc_html(get_option('google_maps_cid', ''));
});

add_shortcode('google_kgid', function() {
	return esc_html(get_option('google_kgid', ''));
});

add_shortcode('google_gmb_image_url', function() {
	return esc_url(get_option('google_gmb_image_url', ''));
});
