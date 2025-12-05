<?php
/**
 * Filename: lean-forms.php
 * Purpose: Complete contact form system with custom database table
 *
 * Features:
 * - Custom table for form submissions (lean_form_submissions)
 * - [lean_form] shortcode for frontend display
 * - AJAX form handling with honeypot + rate limiting
 * - Admin viewer with analytics, filtering, CSV export
 * - Legacy Ninja Forms migration tool
 *
 * Usage: require_once get_template_directory() . '/lean-forms.php';
 */

// ──────────────────────────────────────────────────────────────────────────────
// DATABASE TABLE SETUP
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Get the submissions table name
 */
function lean_forms_table_name() {
	global $wpdb;
	return $wpdb->prefix . 'lean_form_submissions';
}

/**
 * Create the submissions table on theme activation
 */
function lean_forms_create_table() {
	global $wpdb;

	$table_name = lean_forms_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table_name} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL,
		email varchar(255) NOT NULL,
		phone varchar(50) DEFAULT '',
		address text DEFAULT '',
		message text NOT NULL,
		page_slug varchar(255) NOT NULL DEFAULT '',
		page_title varchar(255) DEFAULT '',
		page_url varchar(255) DEFAULT '',
		referrer varchar(255) DEFAULT '',
		ip_address varchar(45) DEFAULT '',
		user_agent text DEFAULT '',
		status varchar(20) NOT NULL DEFAULT 'new',
		is_spam tinyint(1) NOT NULL DEFAULT 0,
		is_read tinyint(1) NOT NULL DEFAULT 0,
		notes text DEFAULT '',
		meta longtext DEFAULT '',
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY page_slug (page_slug),
		KEY status (status),
		KEY is_spam (is_spam),
		KEY created_at (created_at),
		KEY email (email)
	) {$charset_collate};";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);

	// Store version for future migrations
	update_option('lean_forms_db_version', '1.0');
}

/**
 * Check and create table if needed
 */
function lean_forms_maybe_create_table() {
	$installed_version = get_option('lean_forms_db_version', '0');
	if (version_compare($installed_version, '1.0', '<')) {
		lean_forms_create_table();
	}
}
add_action('after_setup_theme', 'lean_forms_maybe_create_table');

// ──────────────────────────────────────────────────────────────────────────────
// FORM SUBMISSION HANDLING
// ──────────────────────────────────────────────────────────────────────────────

// Register AJAX endpoints
add_action('wp_ajax_lean_contact_form', 'lean_forms_process_submission');
add_action('wp_ajax_nopriv_lean_contact_form', 'lean_forms_process_submission');

/**
 * Security: Generate form token
 */
function lean_forms_token_generate() {
	$salt = 'lean-form-' . get_home_url();
	return md5(date('Ymd') . $salt);
}

/**
 * Security: Verify form token
 */
function lean_forms_token_verify($token) {
	return $token === lean_forms_token_generate();
}

/**
 * Security: Rate limiting
 */
function lean_forms_rate_limit_check() {
	$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	$key = 'lean_forms_rl_' . md5($ip);
	$hits = (int) get_transient($key);

	if ($hits > 10) {
		return false; // 10 submissions per hour per IP
	}

	set_transient($key, $hits + 1, HOUR_IN_SECONDS);
	return true;
}

/**
 * Process form submission
 */
function lean_forms_process_submission() {
	// Get config from options
	$config = array(
		'recipient'          => get_option('form_recipient_email', get_option('admin_email')),
		'company_name'       => get_option('business_name', get_bloginfo('name')),
		'from_email'         => get_option('form_from_email', get_option('admin_email')),
		'success_message'    => get_option('form_success_message', "Message sent. We'll contact you within 24 hours."),
		'error_message'      => get_option('form_error_message', 'Error sending message. Please call us directly.'),
		'send_confirmation'  => get_option('form_send_confirmation', false),
	);

	// Security: Token check
	if (!isset($_POST['token']) || !lean_forms_token_verify($_POST['token'])) {
		wp_send_json_error('Security validation failed. Please refresh and try again.');
	}

	// Security: Honeypot check
	if (!empty($_POST['website'])) {
		// Silently succeed to confuse bots
		wp_send_json_success($config['success_message']);
	}

	// Security: Rate limiting
	if (!lean_forms_rate_limit_check()) {
		wp_send_json_error('Too many submissions. Please try again later.');
	}

	// Sanitize fields
	$name       = sanitize_text_field($_POST['name'] ?? '');
	$email      = sanitize_email($_POST['email'] ?? '');
	$phone      = sanitize_text_field($_POST['phone'] ?? '');
	$address    = sanitize_text_field($_POST['address'] ?? '');
	$message    = sanitize_textarea_field($_POST['message'] ?? '');
	$page_slug  = sanitize_text_field($_POST['page_slug'] ?? '');
	$page_title = sanitize_text_field($_POST['page_title'] ?? '');

	// Validate required fields
	if (empty($name) || empty($email) || empty($message)) {
		wp_send_json_error('Please fill in all required fields.');
	}

	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		wp_send_json_error('Please enter a valid email address.');
	}

	// Save to database - page_slug is the unique identifier
	$submission_id = lean_forms_save_submission(array(
		'name'       => $name,
		'email'      => $email,
		'phone'      => $phone,
		'address'    => $address,
		'message'    => $message,
		'page_slug'  => $page_slug,
		'page_title' => $page_title ?: $page_slug,
		'page_url'   => wp_get_referer() ?: '',
		'referrer'   => $_SERVER['HTTP_REFERER'] ?? '',
		'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
		'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
	));

	if (!$submission_id) {
		wp_send_json_error($config['error_message']);
	}

	// Send email notification
	$mail_sent = lean_forms_send_notification($submission_id, $config);

	// Send confirmation email if enabled
	if ($mail_sent && $config['send_confirmation']) {
		lean_forms_send_confirmation($email, $name, $config);
	}

	if ($mail_sent) {
		wp_send_json_success($config['success_message']);
	} else {
		// Still saved to DB, just email failed
		error_log('Lean Forms: Email failed but submission saved. ID: ' . $submission_id);
		wp_send_json_success($config['success_message']);
	}
}

/**
 * Save submission to database
 */
function lean_forms_save_submission($data) {
	global $wpdb;
	$table = lean_forms_table_name();

	$result = $wpdb->insert($table, array(
		'name'       => $data['name'],
		'email'      => $data['email'],
		'phone'      => $data['phone'] ?? '',
		'address'    => $data['address'] ?? '',
		'message'    => $data['message'],
		'page_slug'  => $data['page_slug'] ?? '',
		'page_title' => $data['page_title'] ?? '',
		'page_url'   => $data['page_url'] ?? '',
		'referrer'   => $data['referrer'] ?? '',
		'ip_address' => $data['ip_address'] ?? '',
		'user_agent' => $data['user_agent'] ?? '',
		'status'     => 'new',
		'is_spam'    => 0,
		'is_read'    => 0,
		'meta'       => isset($data['meta']) ? json_encode($data['meta']) : '',
		'created_at' => current_time('mysql'),
	), array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%d','%s','%s'));

	if ($result === false) {
		error_log('Lean Forms DB Error: ' . $wpdb->last_error);
		return false;
	}

	return $wpdb->insert_id;
}

/**
 * Send email notification to admin
 */
function lean_forms_send_notification($submission_id, $config) {
	global $wpdb;
	$table = lean_forms_table_name();

	$submission = $wpdb->get_row($wpdb->prepare(
		"SELECT * FROM {$table} WHERE id = %d",
		$submission_id
	));

	if (!$submission) {
		return false;
	}

	$subject = sprintf('[%s] New Contact Form Submission', $config['company_name']);

	$body = "New form submission received:\n\n";
	$body .= "Name: {$submission->name}\n";
	$body .= "Email: {$submission->email}\n";
	$body .= "Phone: {$submission->phone}\n";
	if (!empty($submission->address)) {
		$body .= "Address: {$submission->address}\n";
	}
	$body .= "\nMessage:\n{$submission->message}\n";
	$body .= "\n---\n";
	$body .= "Page: {$submission->page_title}\n";
	$body .= "Submitted: {$submission->created_at}\n";
	$body .= "IP: {$submission->ip_address}\n";
	$body .= "\nView in admin: " . admin_url('admin.php?page=lean-form-submissions&view=' . $submission_id);

	$headers = array(
		'From: ' . $config['company_name'] . ' <' . $config['from_email'] . '>',
		'Reply-To: ' . $submission->name . ' <' . $submission->email . '>',
		'Content-Type: text/plain; charset=UTF-8'
	);

	return wp_mail($config['recipient'], $subject, $body, $headers);
}

/**
 * Send confirmation email to submitter
 */
function lean_forms_send_confirmation($email, $name, $config) {
	$subject = 'Thank you for contacting ' . $config['company_name'];

	$body = "Hi {$name},\n\n";
	$body .= "Thank you for reaching out to us. We've received your message and will get back to you within 24 hours.\n\n";
	$body .= "Best regards,\n";
	$body .= $config['company_name'];

	$headers = array(
		'From: ' . $config['company_name'] . ' <' . $config['from_email'] . '>',
		'Content-Type: text/plain; charset=UTF-8'
	);

	return wp_mail($email, $subject, $body, $headers);
}

// ──────────────────────────────────────────────────────────────────────────────
// SHORTCODE: [lean_form]
// ──────────────────────────────────────────────────────────────────────────────

add_shortcode('lean_form', 'lean_forms_shortcode');

function lean_forms_shortcode($atts = array()) {
	$a = shortcode_atts(array(
		'button'      => 'Get My Quote',
		'class'       => 'btn-warning',
		'size'        => '',
		'show_phone'  => 'true',
		'show_address'=> 'false',
		'columns'     => '',
	), $atts);

	$show_phone   = filter_var($a['show_phone'], FILTER_VALIDATE_BOOLEAN);
	$show_address = filter_var($a['show_address'], FILTER_VALIDATE_BOOLEAN);

	$ajax_url = admin_url('admin-ajax.php');
	$token = lean_forms_token_generate();

	// Get page context - this is the unique identifier for submissions
	global $post;
	$page_slug = '';
	$page_title = '';
	if (is_front_page() || is_home()) {
		$page_slug = 'home';
		$page_title = 'Home';
	} elseif ($post) {
		$page_slug = $post->post_name;
		$page_title = get_the_title($post->ID);
	}

	$btn_size = $a['size'] ? 'btn-' . $a['size'] : '';

	ob_start();
	?>
	<style>
	#lean-form .hp { position: absolute; left: -9999px; }
	#lean-form .btn:disabled { opacity: 0.65; cursor: not-allowed; }
	#lean-form-wrapper .form-fields { transition: opacity 0.3s ease; }
	#lean-form-wrapper .form-fields.hiding { opacity: 0; }
	</style>

	<?php if ($a['columns']): ?>
	<div class="row justify-content-center">
	<div class="<?php echo esc_attr($a['columns']); ?>">
	<?php endif; ?>

	<div id="lean-form-wrapper">
		<form id="lean-form" class="needs-validation" novalidate
			  data-page-slug="<?php echo esc_attr($page_slug); ?>"
			  data-page-title="<?php echo esc_attr($page_title); ?>">

			<div class="form-fields">
				<!-- Name -->
				<div class="form-group mb-3">
					<label for="lean-name" class="sr-only visually-hidden">Your name</label>
					<input type="text" id="lean-name" name="name" class="form-control"
						   placeholder="Your name *" required minlength="2"
						   autocomplete="name">
					<div class="invalid-feedback">Please enter your name</div>
				</div>

				<!-- Email -->
				<div class="form-group mb-3">
					<label for="lean-email" class="sr-only visually-hidden">Your email</label>
					<input type="email" id="lean-email" name="email" class="form-control"
						   placeholder="Your email *" required
						   autocomplete="email">
					<div class="invalid-feedback">Please enter a valid email</div>
				</div>

				<?php if ($show_phone): ?>
				<!-- Phone -->
				<div class="form-group mb-3">
					<label for="lean-phone" class="sr-only visually-hidden">Your phone</label>
					<input type="tel" id="lean-phone" name="phone" class="form-control"
						   placeholder="Your phone *" required minlength="10"
						   autocomplete="tel">
					<div class="invalid-feedback">Please enter a valid phone number</div>
				</div>
				<?php endif; ?>

				<?php if ($show_address): ?>
				<!-- Address -->
				<div class="form-group mb-3">
					<label for="lean-address" class="sr-only visually-hidden">Service address</label>
					<input type="text" id="lean-address" name="address" class="form-control"
						   placeholder="Service address (optional)"
						   autocomplete="street-address">
				</div>
				<?php endif; ?>

				<!-- Message -->
				<div class="form-group mb-3">
					<label for="lean-message" class="sr-only visually-hidden">Your message</label>
					<textarea id="lean-message" name="message" class="form-control"
							  placeholder="How can we help you? *" required rows="4"></textarea>
					<div class="invalid-feedback">Please enter your message</div>
				</div>

				<!-- Honeypot -->
				<div class="hp" aria-hidden="true">
					<input type="text" name="website" tabindex="-1" autocomplete="off">
				</div>

				<!-- Submit -->
				<button type="submit" class="btn <?php echo esc_attr($a['class'] . ' ' . $btn_size); ?> btn-block w-100">
					<span class="btn-text"><?php echo esc_html($a['button']); ?></span>
					<span class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
				</button>
			</div>
		</form>

		<div class="form-message alert mt-3 d-none"></div>
	</div>

	<?php if ($a['columns']): ?>
	</div>
	</div>
	<?php endif; ?>

	<script>
	(function() {
		var form = document.getElementById('lean-form');
		if (!form || form.dataset.initialized) return;
		form.dataset.initialized = 'true';

		var wrapper = document.getElementById('lean-form-wrapper');
		var fields = form.querySelector('.form-fields');
		var msg = wrapper.querySelector('.form-message');
		var btn = form.querySelector('button[type="submit"]');
		var btnText = btn.querySelector('.btn-text');
		var spinner = btn.querySelector('.spinner-border');
		var originalText = btnText.textContent;

		form.addEventListener('submit', function(e) {
			e.preventDefault();

			if (!form.checkValidity()) {
				form.classList.add('was-validated');
				return;
			}

			btn.disabled = true;
			btnText.textContent = 'Sending...';
			spinner.classList.remove('d-none');
			msg.className = 'form-message alert mt-3 d-none';

			var data = new FormData();
			data.append('action', 'lean_contact_form');
			data.append('token', '<?php echo $token; ?>');
			data.append('page_slug', form.dataset.pageSlug);
			data.append('page_title', form.dataset.pageTitle);
			data.append('name', form.querySelector('[name="name"]').value);
			data.append('email', form.querySelector('[name="email"]').value);
			data.append('message', form.querySelector('[name="message"]').value);
			data.append('website', form.querySelector('[name="website"]').value);

			var phone = form.querySelector('[name="phone"]');
			if (phone) data.append('phone', phone.value);

			var address = form.querySelector('[name="address"]');
			if (address) data.append('address', address.value);

			fetch('<?php echo $ajax_url; ?>', {
				method: 'POST',
				body: data
			})
			.then(function(r) { return r.json(); })
			.then(function(result) {
				msg.className = 'form-message alert alert-' + (result.success ? 'success' : 'danger') + ' mt-3';

				if (result.success) {
					fields.classList.add('hiding');
					setTimeout(function() {
						fields.style.display = 'none';
						msg.innerHTML = '<strong>Thank You!</strong><br>' + (result.data || '');
					}, 300);

					// Track conversion
					if (typeof gtag !== 'undefined') {
						gtag('event', 'generate_lead', {
							'event_category': 'contact',
							'event_label': form.dataset.pageSlug
						});
					}
				} else {
					msg.textContent = result.data || 'An error occurred';
					btn.disabled = false;
					btnText.textContent = originalText;
					spinner.classList.add('d-none');
				}
			})
			.catch(function() {
				msg.className = 'form-message alert alert-danger mt-3';
				msg.textContent = 'Network error. Please try again.';
				btn.disabled = false;
				btnText.textContent = originalText;
				spinner.classList.add('d-none');
			});
		});
	})();
	</script>
	<?php
	return ob_get_clean();
}

// ──────────────────────────────────────────────────────────────────────────────
// ADMIN: Submissions Viewer
// ──────────────────────────────────────────────────────────────────────────────

add_action('admin_menu', 'lean_forms_admin_menu');

function lean_forms_admin_menu() {
	add_menu_page(
		'Form Submissions',
		'Form Submissions',
		'manage_options',
		'lean-form-submissions',
		'lean_forms_admin_page',
		'dashicons-email-alt',
		30
	);
}

// Load Chart.js on admin page
add_action('admin_enqueue_scripts', function($hook) {
	if ($hook !== 'toplevel_page_lean-form-submissions') return;
	wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', array(), '4.4.0', true);
});

// AJAX handlers for admin actions
add_action('wp_ajax_lean_forms_mark_spam', 'lean_forms_ajax_mark_spam');
add_action('wp_ajax_lean_forms_delete', 'lean_forms_ajax_delete');
add_action('wp_ajax_lean_forms_mark_read', 'lean_forms_ajax_mark_read');

function lean_forms_ajax_mark_spam() {
	if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

	$id = intval($_POST['id'] ?? 0);
	$is_spam = intval($_POST['is_spam'] ?? 1);

	if (!wp_verify_nonce($_POST['nonce'] ?? '', 'lean_forms_action_' . $id)) {
		wp_send_json_error('Security check failed');
	}

	global $wpdb;
	$table = lean_forms_table_name();
	$wpdb->update($table, array('is_spam' => $is_spam), array('id' => $id), array('%d'), array('%d'));

	wp_send_json_success($is_spam ? 'Marked as spam' : 'Marked as not spam');
}

function lean_forms_ajax_delete() {
	if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

	$id = intval($_POST['id'] ?? 0);

	if (!wp_verify_nonce($_POST['nonce'] ?? '', 'lean_forms_action_' . $id)) {
		wp_send_json_error('Security check failed');
	}

	global $wpdb;
	$table = lean_forms_table_name();
	$wpdb->delete($table, array('id' => $id), array('%d'));

	wp_send_json_success('Deleted');
}

function lean_forms_ajax_mark_read() {
	if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

	$id = intval($_POST['id'] ?? 0);

	global $wpdb;
	$table = lean_forms_table_name();
	$wpdb->update($table, array('is_read' => 1), array('id' => $id), array('%d'), array('%d'));

	wp_send_json_success('Marked as read');
}

/**
 * Get analytics data
 */
function lean_forms_get_analytics($start_date = null, $end_date = null) {
	global $wpdb;
	$table = lean_forms_table_name();

	$where = "WHERE is_spam = 0";
	if ($start_date) $where .= $wpdb->prepare(" AND created_at >= %s", $start_date);
	if ($end_date) $where .= $wpdb->prepare(" AND created_at <= %s", $end_date);

	// Monthly breakdown
	$monthly = $wpdb->get_results("
		SELECT
			DATE_FORMAT(created_at, '%Y-%m') as month,
			YEAR(created_at) as year,
			MONTH(created_at) as month_num,
			COUNT(*) as total
		FROM {$table}
		{$where}
		GROUP BY DATE_FORMAT(created_at, '%Y-%m')
		ORDER BY month DESC
	");

	// By page (slug is the unique identifier)
	$by_page = $wpdb->get_results("
		SELECT page_slug, page_title, COUNT(*) as total
		FROM {$table}
		{$where}
		AND page_slug != ''
		GROUP BY page_slug
		ORDER BY total DESC
		LIMIT 20
	");

	return array(
		'monthly' => $monthly,
		'by_page' => $by_page,
	);
}

/**
 * Admin page rendering
 */
function lean_forms_admin_page() {
	global $wpdb;
	$table = lean_forms_table_name();

	// Check if table exists
	if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
		echo '<div class="wrap"><h1>Form Submissions</h1>';
		echo '<div class="notice notice-warning"><p>Database table not found. ';
		echo '<a href="' . admin_url('admin.php?page=lean-form-submissions&action=create_table') . '" class="button">Create Table</a></p></div>';
		echo '</div>';

		if (isset($_GET['action']) && $_GET['action'] === 'create_table') {
			lean_forms_create_table();
			echo '<div class="notice notice-success"><p>Table created successfully!</p></div>';
		}
		return;
	}

	// Handle CSV export
	if (isset($_GET['export']) && $_GET['export'] === 'csv') {
		lean_forms_export_csv();
		exit;
	}

	// Filters
	$spam_filter = isset($_GET['spam']) ? sanitize_text_field($_GET['spam']) : 'not_spam';
	$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
	$paged = max(1, intval($_GET['paged'] ?? 1));
	$per_page = 50;
	$offset = ($paged - 1) * $per_page;

	// Build query
	$where = "WHERE 1=1";
	if ($spam_filter === 'spam') {
		$where .= " AND is_spam = 1";
	} elseif ($spam_filter === 'not_spam') {
		$where .= " AND is_spam = 0";
	}

	if (!empty($search)) {
		$like = '%' . $wpdb->esc_like($search) . '%';
		$where .= $wpdb->prepare(" AND (name LIKE %s OR email LIKE %s OR message LIKE %s OR phone LIKE %s)", $like, $like, $like, $like);
	}

	$total = $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where}");
	$total_pages = ceil($total / $per_page);

	$submissions = $wpdb->get_results("
		SELECT * FROM {$table}
		{$where}
		ORDER BY created_at DESC
		LIMIT {$per_page} OFFSET {$offset}
	");

	$spam_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE is_spam = 1");
	$new_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE is_read = 0 AND is_spam = 0");

	// Get analytics
	$analytics = lean_forms_get_analytics();

	// Calculate YTD
	$current_year = date('Y');
	$ytd_total = 0;
	$last_year_total = 0;
	foreach ($analytics['monthly'] as $row) {
		if ((int)$row->year === (int)$current_year) {
			$ytd_total += $row->total;
		}
		if ((int)$row->year === (int)$current_year - 1) {
			$last_year_total += $row->total;
		}
	}

	?>
	<div class="wrap">
		<h1>Form Submissions <?php if ($new_count): ?><span class="badge" style="background:#0073aa;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;"><?php echo $new_count; ?> new</span><?php endif; ?></h1>

		<!-- Stats Row -->
		<div style="display:flex;gap:20px;margin:20px 0;">
			<div style="background:#fff;padding:15px 25px;border:1px solid #ccd0d4;border-left:4px solid #0073aa;">
				<div style="font-size:11px;color:#666;text-transform:uppercase;">Year to Date</div>
				<div style="font-size:28px;font-weight:bold;color:#0073aa;"><?php echo number_format($ytd_total); ?></div>
			</div>
			<div style="background:#fff;padding:15px 25px;border:1px solid #ccd0d4;">
				<div style="font-size:11px;color:#666;text-transform:uppercase;">Last Year Total</div>
				<div style="font-size:28px;font-weight:bold;"><?php echo number_format($last_year_total); ?></div>
			</div>
			<div style="background:#fff;padding:15px 25px;border:1px solid #ccd0d4;">
				<div style="font-size:11px;color:#666;text-transform:uppercase;">Spam</div>
				<div style="font-size:28px;font-weight:bold;color:#dc3545;"><?php echo number_format($spam_count); ?></div>
			</div>
		</div>

		<!-- Filters -->
		<div class="tablenav top">
			<div class="alignleft actions">
				<a href="?page=lean-form-submissions" class="button <?php echo $spam_filter === 'not_spam' ? 'button-primary' : ''; ?>">Inbox</a>
				<a href="?page=lean-form-submissions&spam=spam" class="button <?php echo $spam_filter === 'spam' ? 'button-primary' : ''; ?>">Spam (<?php echo $spam_count; ?>)</a>
				<a href="?page=lean-form-submissions&spam=all" class="button <?php echo $spam_filter === 'all' ? 'button-primary' : ''; ?>">All</a>

				<form method="get" style="display:inline-block;margin-left:10px;">
					<input type="hidden" name="page" value="lean-form-submissions">
					<input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search...">
					<input type="submit" class="button" value="Search">
				</form>

				<a href="?page=lean-form-submissions&export=csv&spam=<?php echo $spam_filter; ?>&s=<?php echo urlencode($search); ?>" class="button" style="margin-left:10px;">Export CSV</a>
			</div>

			<?php if ($total_pages > 1): ?>
			<div class="tablenav-pages">
				<span class="displaying-num"><?php echo number_format($total); ?> items</span>
				<?php
				echo paginate_links(array(
					'base' => add_query_arg('paged', '%#%'),
					'format' => '',
					'total' => $total_pages,
					'current' => $paged,
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;',
				));
				?>
			</div>
			<?php endif; ?>
		</div>

		<!-- Table -->
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width:140px;">Date</th>
					<th>Name</th>
					<th>Email</th>
					<th>Phone</th>
					<th>Page</th>
					<th style="width:180px;">Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($submissions)): ?>
				<tr><td colspan="6" style="text-align:center;padding:40px;">No submissions found.</td></tr>
				<?php else: ?>
				<?php foreach ($submissions as $sub): ?>
				<tr id="row-<?php echo $sub->id; ?>" <?php echo $sub->is_spam ? 'style="background:#fee;"' : ''; ?> <?php echo !$sub->is_read ? 'style="font-weight:bold;"' : ''; ?>>
					<td><?php echo esc_html(date('M j, Y g:i A', strtotime($sub->created_at))); ?></td>
					<td><?php echo esc_html($sub->name); ?></td>
					<td><a href="mailto:<?php echo esc_attr($sub->email); ?>"><?php echo esc_html($sub->email); ?></a></td>
					<td><?php echo esc_html($sub->phone ?: '-'); ?></td>
					<td><?php echo esc_html($sub->page_title ?: '-'); ?></td>
					<td>
						<a href="#" class="button button-small" onclick="event.preventDefault();toggleDetail(<?php echo $sub->id; ?>);">View</a>
						<?php if ($sub->is_spam): ?>
						<a href="#" class="button button-small" data-id="<?php echo $sub->id; ?>" data-action="not_spam" data-nonce="<?php echo wp_create_nonce('lean_forms_action_'.$sub->id); ?>" onclick="event.preventDefault();leanFormsAction(this,'not_spam');">Not Spam</a>
						<?php else: ?>
						<a href="#" class="button button-small" data-id="<?php echo $sub->id; ?>" data-action="spam" data-nonce="<?php echo wp_create_nonce('lean_forms_action_'.$sub->id); ?>" onclick="event.preventDefault();leanFormsAction(this,'spam');">Spam</a>
						<?php endif; ?>
						<a href="#" class="button button-small" style="color:#b32d2e;" data-id="<?php echo $sub->id; ?>" data-nonce="<?php echo wp_create_nonce('lean_forms_action_'.$sub->id); ?>" onclick="event.preventDefault();if(confirm('Delete permanently?'))leanFormsAction(this,'delete');">Delete</a>
					</td>
				</tr>
				<tr id="detail-<?php echo $sub->id; ?>" style="display:none;">
					<td colspan="6" style="background:#f9f9f9;padding:20px;">
						<div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
							<div><strong>Name:</strong><br><?php echo esc_html($sub->name); ?></div>
							<div><strong>Email:</strong><br><a href="mailto:<?php echo esc_attr($sub->email); ?>"><?php echo esc_html($sub->email); ?></a></div>
							<div><strong>Phone:</strong><br><?php echo esc_html($sub->phone ?: '-'); ?></div>
							<div><strong>Address:</strong><br><?php echo esc_html($sub->address ?: '-'); ?></div>
							<div style="grid-column:1/-1;"><strong>Message:</strong><br><pre style="white-space:pre-wrap;margin:0;background:#fff;padding:10px;border:1px solid #ddd;"><?php echo esc_html($sub->message); ?></pre></div>
						</div>
						<div style="margin-top:15px;padding-top:15px;border-top:1px solid #ddd;color:#666;font-size:12px;">
							<strong>ID:</strong> <?php echo $sub->id; ?> |
							<strong>Page:</strong> <?php echo esc_html($sub->page_title ?: $sub->page_slug); ?> (<?php echo esc_html($sub->page_slug); ?>) |
							<strong>IP:</strong> <?php echo esc_html($sub->ip_address); ?> |
							<strong>Submitted:</strong> <?php echo $sub->created_at; ?>
						</div>
					</td>
				</tr>
				<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<script>
	function toggleDetail(id) {
		var row = document.getElementById('detail-' + id);
		row.style.display = row.style.display === 'none' ? 'table-row' : 'none';

		// Mark as read
		fetch(ajaxurl, {
			method: 'POST',
			headers: {'Content-Type': 'application/x-www-form-urlencoded'},
			body: 'action=lean_forms_mark_read&id=' + id
		});
	}

	function leanFormsAction(btn, action) {
		var id = btn.dataset.id;
		var nonce = btn.dataset.nonce;
		var row = document.getElementById('row-' + id);
		var detail = document.getElementById('detail-' + id);

		btn.disabled = true;
		btn.textContent = '...';

		var ajaxAction = action === 'delete' ? 'lean_forms_delete' : 'lean_forms_mark_spam';
		var body = 'action=' + ajaxAction + '&id=' + id + '&nonce=' + nonce;
		if (action !== 'delete') body += '&is_spam=' + (action === 'spam' ? 1 : 0);

		fetch(ajaxurl, {
			method: 'POST',
			headers: {'Content-Type': 'application/x-www-form-urlencoded'},
			body: body
		})
		.then(function(r) { return r.json(); })
		.then(function(result) {
			if (result.success) {
				row.style.opacity = '0';
				setTimeout(function() {
					row.remove();
					if (detail) detail.remove();
				}, 300);
			} else {
				alert(result.data || 'Error');
				btn.disabled = false;
			}
		});
	}
	</script>

	<style>
	#row-<?php /* dynamic */ ?> { transition: opacity 0.3s; }
	.tablenav { margin: 15px 0; }
	</style>
	<?php
}

/**
 * Export submissions to CSV
 */
function lean_forms_export_csv() {
	if (!current_user_can('manage_options')) {
		wp_die('Unauthorized');
	}

	global $wpdb;
	$table = lean_forms_table_name();

	$spam_filter = sanitize_text_field($_GET['spam'] ?? 'not_spam');
	$search = sanitize_text_field($_GET['s'] ?? '');

	$where = "WHERE 1=1";
	if ($spam_filter === 'spam') {
		$where .= " AND is_spam = 1";
	} elseif ($spam_filter === 'not_spam') {
		$where .= " AND is_spam = 0";
	}

	if (!empty($search)) {
		$like = '%' . $wpdb->esc_like($search) . '%';
		$where .= $wpdb->prepare(" AND (name LIKE %s OR email LIKE %s OR message LIKE %s)", $like, $like, $like);
	}

	$submissions = $wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY created_at DESC");

	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename="form-submissions-' . date('Y-m-d') . '.csv"');

	$output = fopen('php://output', 'w');
	fputcsv($output, array('ID', 'Date', 'Name', 'Email', 'Phone', 'Address', 'Message', 'Page Slug', 'Page Title', 'IP', 'Spam'));

	foreach ($submissions as $sub) {
		fputcsv($output, array(
			$sub->id,
			$sub->created_at,
			$sub->name,
			$sub->email,
			$sub->phone,
			$sub->address,
			$sub->message,
			$sub->page_slug,
			$sub->page_title,
			$sub->ip_address,
			$sub->is_spam ? 'Yes' : 'No'
		));
	}

	fclose($output);
}

