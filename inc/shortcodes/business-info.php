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

add_shortcode('business_email', function() {
	$email = sanitize_email(get_option('business_email', ''));
	return $email ? esc_html($email) : '';
});

add_shortcode('business_email_link', function() {
	$email = sanitize_email(get_option('business_email', ''));
	if (!$email) return '';
	return '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
});

/**
 * [business_email_obfuscated] — human-readable, scraper-resistant email.
 * Renders e.g. "info (at) example.com" instead of "info@example.com".
 * Not a link. Use this in legal pages or any public-facing page where
 * email harvesters are a concern.
 */
add_shortcode('business_email_obfuscated', function() {
	$email = sanitize_email(get_option('business_email', ''));
	if (!$email) return '';
	return esc_html(str_replace('@', ' (at) ', $email));
});

add_shortcode('business_county', function() {
	return esc_html(get_option('business_county', ''));
});

/**
 * Map a 2-letter US state code to the full state name. If the input
 * is already the full name (or any other value), returns it unchanged.
 */
function lean_us_state_name($value) {
	$value = trim((string) $value);
	if ($value === '') return '';
	$states = [
		'AL'=>'Alabama','AK'=>'Alaska','AZ'=>'Arizona','AR'=>'Arkansas','CA'=>'California',
		'CO'=>'Colorado','CT'=>'Connecticut','DE'=>'Delaware','FL'=>'Florida','GA'=>'Georgia',
		'HI'=>'Hawaii','ID'=>'Idaho','IL'=>'Illinois','IN'=>'Indiana','IA'=>'Iowa',
		'KS'=>'Kansas','KY'=>'Kentucky','LA'=>'Louisiana','ME'=>'Maine','MD'=>'Maryland',
		'MA'=>'Massachusetts','MI'=>'Michigan','MN'=>'Minnesota','MS'=>'Mississippi','MO'=>'Missouri',
		'MT'=>'Montana','NE'=>'Nebraska','NV'=>'Nevada','NH'=>'New Hampshire','NJ'=>'New Jersey',
		'NM'=>'New Mexico','NY'=>'New York','NC'=>'North Carolina','ND'=>'North Dakota','OH'=>'Ohio',
		'OK'=>'Oklahoma','OR'=>'Oregon','PA'=>'Pennsylvania','RI'=>'Rhode Island','SC'=>'South Carolina',
		'SD'=>'South Dakota','TN'=>'Tennessee','TX'=>'Texas','UT'=>'Utah','VT'=>'Vermont',
		'VA'=>'Virginia','WA'=>'Washington','WV'=>'West Virginia','WI'=>'Wisconsin','WY'=>'Wyoming',
		'DC'=>'District of Columbia',
	];
	$upper = strtoupper($value);
	if (strlen($upper) === 2 && isset($states[$upper])) {
		return $states[$upper];
	}
	return $value;
}

/**
 * [business_state_name] — full state name for legal prose.
 * Expands 2-letter codes ("TX" → "Texas"); leaves full names as-is.
 */
add_shortcode('business_state_name', function() {
	return esc_html(lean_us_state_name(get_option('business_state', '')));
});

/**
 * Formatted jurisdiction phrase for legal pages.
 * - With county + state: "[County] County, State of [State]"
 * - State only:          "State of [State]"
 * - Neither:             "the United States"
 */
add_shortcode('business_jurisdiction', function() {
	$county = trim(get_option('business_county', ''));
	$state  = lean_us_state_name(get_option('business_state', ''));
	if ($county && $state) {
		return esc_html($county) . ' County, State of ' . esc_html($state);
	}
	if ($state) {
		return 'State of ' . esc_html($state);
	}
	return 'the United States';
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

// ──────────────────────────────────────────────────────────────────────────────
// BUSINESS HOURS
// ──────────────────────────────────────────────────────────────────────────────

/**
 * [business_hours]
 *
 * Renders the business hours grid (Mon → Sun) plus a live "Open Now / Opens at"
 * status pill. Today's row is bolded. Up to 2 sets of hours per day supported
 * (for businesses closed over lunch). Hours and timezone are configured under
 * Appearance → Lean Theme Settings → Hours.
 */
add_shortcode('business_hours', function() {
	$hours = get_option('business_hours', []);
	$tz_str = function_exists('wp_timezone_string') ? wp_timezone_string() : (get_option('timezone_string') ?: 'UTC');

	$days = [
		'mon' => 'Monday',
		'tue' => 'Tuesday',
		'wed' => 'Wednesday',
		'thu' => 'Thursday',
		'fri' => 'Friday',
		'sat' => 'Saturday',
		'sun' => 'Sunday',
	];

	// Format HH:MM (24h) -> e.g. "8:00 AM"
	$fmt = function($t) {
		if (!$t || !preg_match('/^(\d{1,2}):(\d{2})$/', $t, $m)) return '';
		$h = (int) $m[1];
		$min = $m[2];
		$ampm = $h < 12 ? 'AM' : 'PM';
		$h12 = $h % 12;
		if ($h12 === 0) $h12 = 12;
		return $h12 . ':' . $min . ' ' . $ampm;
	};

	// Determine today's key (in business timezone)
	try {
		$now = new DateTime('now', new DateTimeZone($tz_str));
	} catch (Exception $e) {
		$now = new DateTime('now', new DateTimeZone('UTC'));
	}
	$today_key = strtolower(substr($now->format('D'), 0, 3)); // mon, tue, ...

	ob_start();
	?>
	<div class="lean-hours">
		<div class="lean-hours-label fw-bold text-uppercase mb-3" style="letter-spacing:.05em;">
			<i class="bi bi-clock-fill me-2" aria-hidden="true"></i>Hours
		</div>
		<div class="hours-grid mb-3">
			<?php foreach ($days as $key => $label):
				$row = isset($hours[$key]) ? $hours[$key] : ['closed' => false, 'sets' => []];
				$is_today = ($key === $today_key);
				$closed = !empty($row['closed']) || empty($row['sets']);
				?>
				<div class="hours-row<?php echo $is_today ? ' is-today' : ''; ?>" data-day="<?php echo esc_attr($key); ?>">
					<span class="day"><?php echo esc_html($label); ?></span>
					<span class="time<?php echo $closed ? ' closed' : ''; ?>">
						<?php
						if ($closed) {
							echo 'Closed';
						} else {
							$parts = [];
							foreach ($row['sets'] as $set) {
								$parts[] = esc_html($fmt($set['open']) . ' – ' . $fmt($set['close']));
							}
							echo implode('<br>', $parts);
						}
						?>
					</span>
				</div>
			<?php endforeach; ?>
		</div>
		<span class="hours-status" aria-live="polite">&nbsp;</span>
	</div>
	<script>
	(function(){
		if (window.__leanHoursInit) return;
		window.__leanHoursInit = true;

		var TZ = <?php echo wp_json_encode($tz_str); ?>;
		var HOURS = <?php echo wp_json_encode($hours); ?>;
		var DAY_KEYS = ['sun','mon','tue','wed','thu','fri','sat'];
		var DAY_NAMES = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

		function toMin(t) {
			if (!t) return null;
			var p = t.split(':');
			return parseInt(p[0],10) * 60 + parseInt(p[1],10);
		}
		function fmt(t) {
			var m = toMin(t); if (m === null) return '';
			var h = Math.floor(m/60), mn = m%60, ampm = h<12 ? 'AM' : 'PM';
			var h12 = h%12; if (h12===0) h12 = 12;
			return h12 + ':' + (mn<10?'0':'') + mn + ' ' + ampm;
		}
		function nowParts() {
			var parts = new Intl.DateTimeFormat('en-US', {
				timeZone: TZ, weekday:'short', hour:'2-digit', minute:'2-digit', hour12:false
			}).formatToParts(new Date());
			var m = {};
			parts.forEach(function(p){ m[p.type] = p.value; });
			var wkday = (m.weekday || '').toLowerCase().substr(0,3); // sun, mon ...
			var dayIdx = DAY_KEYS.indexOf(wkday); if (dayIdx < 0) dayIdx = 0;
			var hour = parseInt(m.hour, 10); if (hour === 24) hour = 0;
			var minute = parseInt(m.minute, 10) || 0;
			return { dayIdx: dayIdx, minutes: hour*60 + minute };
		}
		function compute() {
			var t = nowParts();
			var today = HOURS[DAY_KEYS[t.dayIdx]];
			if (today && !today.closed && today.sets && today.sets.length) {
				for (var i = 0; i < today.sets.length; i++) {
					var s = today.sets[i];
					if (t.minutes >= toMin(s.open) && t.minutes < toMin(s.close)) {
						return { open: true, text: 'Open Now' };
					}
				}
				for (var j = 0; j < today.sets.length; j++) {
					var s2 = today.sets[j];
					if (t.minutes < toMin(s2.open)) {
						return { open: false, text: 'Opens today at ' + fmt(s2.open) };
					}
				}
			}
			for (var d = 1; d <= 7; d++) {
				var nextIdx = (t.dayIdx + d) % 7;
				var next = HOURS[DAY_KEYS[nextIdx]];
				if (next && !next.closed && next.sets && next.sets.length) {
					return { open: false, text: 'Opens ' + DAY_NAMES[nextIdx] + ' at ' + fmt(next.sets[0].open) };
				}
			}
			return { open: false, text: 'Closed' };
		}
		function render() {
			var nodes = document.querySelectorAll('.hours-status');
			if (!nodes.length) return;
			var s = compute();
			nodes.forEach(function(el){
				el.textContent = s.text;
				el.classList.toggle('is-open', s.open);
			});
		}
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', function(){ render(); setInterval(render, 60000); });
		} else {
			render();
			setInterval(render, 60000);
		}
	})();
	</script>
	<?php
	return ob_get_clean();
});
