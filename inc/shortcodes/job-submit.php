<?php
// === Disable WordPress auto-scaling of large images ===
add_filter('big_image_size_threshold', '__return_false');

// === Prevent WordPress from rotating images and adding "-rotated" suffix ===
add_filter('wp_image_maybe_exif_rotate', function ($orientation, $file) {
    return 1; // pretend orientation is normal; skip rotation & the "-rotated" suffix
}, 10, 2);

/**
 * Fix: After entering a password on a protected page, always redirect back
 * to that exact page—even if Referer is missing or platform rules intervene.
 *
 * Works with default WP password forms and on WP Engine.
 */

/**
 * 1) Inject a hidden "redirect_to" (and post_id) into the password form.
 */
add_filter('the_password_form', function ($form) {
    if (!is_singular()) {
        return $form;
    }

    $permalink = get_permalink();
    if (!$permalink) {
        return $form;
    }

    // Hidden fields we’ll use after cookie is set.
    $hidden  = '<input type="hidden" name="redirect_to" value="' . esc_url($permalink) . '">';
    $hidden .= '<input type="hidden" name="post_id" value="' . intval(get_queried_object_id()) . '">';

    // Inject right before the closing </form>.
    return str_ireplace('</form>', $hidden . '</form>', $form);
});

/**
 * 2) On the FINAL redirect, override Location specifically for postpass flows.
 * Use high priority so we win over earlier redirects (e.g., platform/plugins).
 */
add_filter('wp_redirect', function ($location, $status) {
    // Only run for the post-password handler
    if (!isset($_REQUEST['action']) || $_REQUEST['action'] !== 'postpass') {
        return $location;
    }

    // Prefer explicit redirect_to we injected into the form
    if (!empty($_POST['redirect_to'])) {
        $target = wp_validate_redirect($_POST['redirect_to'], home_url('/'));
        if (!empty($target)) {
            return $target;
        }
    }

    // Fallback: rebuild from post_id if present
    if (!empty($_POST['post_id'])) {
        $permalink = get_permalink((int) $_POST['post_id']);
        if ($permalink) {
            $target = wp_validate_redirect($permalink, home_url('/'));
            if (!empty($target)) {
                return $target;
            }
        }
    }

    // Last resort: original location
    return $location;
}, 999, 2);

// === Security helpers ===
function ebp_form_token_issue(): array {
    $ts   = time();
    $rand = wp_generate_password(8, false);
    $sig  = hash_hmac('sha256', $ts . '|' . $rand, SECURE_AUTH_KEY);
    return compact('ts','rand','sig');
}

function ebp_form_token_verify($ts, $rand, $sig): bool {
    if (abs(time() - (int)$ts) > 1800) return false; // 30 min window
    $calc = hash_hmac('sha256', $ts . '|' . $rand, SECURE_AUTH_KEY);
    return hash_equals($calc, (string)$sig);
}

function ebp_rate_limit_ok(string $bucket = 'job_submit'): bool {
    $ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key  = 'ebp_rl_' . md5($bucket . '|' . $ip);
    $hits = (int) get_transient($key);
    if ($hits > 20) return false;             // 20 submits/hour/ip
    set_transient($key, $hits + 1, HOUR_IN_SECONDS);
    return true;
}

// === Shortcode: [job_submit_form] ===
add_shortcode('job_submit_form', function () {
    // Alerts
    $alerts = '';
    $errmap = [
        'spam'   => 'Spam detected. Please try again.',
        'rate'   => 'Too many submissions from this device. Try again soon.',
        'token'  => 'Your form expired. Please reload and submit again.',
        'fields' => 'Please complete all required fields.',
        'images' => 'Please upload 1-2 images only.',
        'server' => 'Unexpected error. Please try again.',
    ];
    
    if (isset($_GET['submitted'])) {
        $alerts .= '<div class="alert alert-success mb-3" role="alert">Thanks! Your job report was received and is awaiting review.</div>';
    } elseif (isset($_GET['err']) && isset($errmap[$_GET['err']])) {
        $alerts .= '<div class="alert alert-danger mb-3" role="alert">'.esc_html($errmap[$_GET['err']]).'</div>';
    }

    $tok = ebp_form_token_issue();
    $redirect_to = get_permalink();

    ob_start(); ?>
<?= $alerts ?>
<form method="post" enctype="multipart/form-data" class="ebp-field-report needs-validation" novalidate>
    <input type="hidden" name="ebp_action" value="ebp_submit_job">
    <input type="hidden" name="ts"   value="<?= esc_attr($tok['ts']) ?>">
    <input type="hidden" name="rand" value="<?= esc_attr($tok['rand']) ?>">
    <input type="hidden" name="sig"  value="<?= esc_attr($tok['sig']) ?>">
    <input type="hidden" name="redirect_to" value="<?= esc_url($redirect_to) ?>">

    <!-- Honeypot -->
    <div style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden" aria-hidden="true">
        <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
    </div>

    <div class="mb-3">
        <label class="form-label">Job Description</label>
        <textarea name="description" class="form-control" rows="5" 
                  placeholder="Brief description of what was going on for this job..." 
                  required></textarea>
        <div class="invalid-feedback">Please provide a description.</div>
    </div>

    <div class="mb-3">
        <label class="form-label">Photos (1-2 images)</label>
        <input type="file" name="photos[]" class="form-control" multiple required>
        <div class="form-text">Upload 1-2 photos from your phone.</div>
        <div class="invalid-feedback">Please upload at least one photo.</div>
    </div>

    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="consent" name="consent" required>
        <label class="form-check-label" for="consent">I confirm photo consent & no faces/addresses are visible.</label>
        <div class="invalid-feedback">Consent is required.</div>
    </div>

    <div id="formStatus" class="visually-hidden" aria-live="polite"></div>

    <button type="submit" class="btn btn-primary btn-lg w-100" id="submitBtn">
        <span class="btn-text">Submit Job</span>
        <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
    </button>
</form>

<!-- Progress overlay -->
<div id="ebpOverlay" class="ebp-over">
    <div class="d-flex h-100 align-items-center justify-content-center">
        <div class="text-center">
            <div class="spinner-border" role="status" aria-hidden="true"></div>
            <div class="mt-2 fw-semibold">Uploading… please wait</div>
        </div>
    </div>
</div>

<script>
    (function () {
        const form = document.querySelector('.ebp-field-report');
        if (!form) return;

        let submitting = false;

        form.addEventListener('submit', function (e) {
            // HTML5 validation
            if (!form.checkValidity()) {
                e.preventDefault(); 
                e.stopPropagation();
                form.classList.add('was-validated');
                return;
            }

            // Validate 1-2 images only
            const fileInput = form.querySelector('input[name="photos[]"]');
            const files = fileInput.files;
            const fileCount = files.length;
            
            if (fileCount < 1 || fileCount > 2) {
                e.preventDefault();
                alert('Please upload 1-2 images only.');
                return;
            }

            // Validate file types (images only)
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/heic', 'image/heif'];
            for (let i = 0; i < files.length; i++) {
                if (!allowedTypes.includes(files[i].type)) {
                    e.preventDefault();
                    alert('Please upload image files only (JPG, PNG, GIF, WebP, HEIC).');
                    return;
                }
            }

            if (submitting) {
                e.preventDefault();
                return;
            }
            submitting = true;

            // Show spinner + overlay
            const btn  = document.getElementById('submitBtn');
            const spin = btn?.querySelector('.spinner-border');
            const text = btn?.querySelector('.btn-text');
            const over = document.getElementById('ebpOverlay');
            const live = document.getElementById('formStatus');

            if (btn) btn.disabled = true;
            if (spin) spin.classList.remove('d-none');
            if (text) text.textContent = 'Submitting…';
            form.setAttribute('aria-busy', 'true');
            if (live) { 
                live.classList.remove('visually-hidden'); 
                live.textContent = 'Submitting…'; 
            }
            if (over) over.classList.add('show');
        });

        // Clean URL after success
        if (new URLSearchParams(location.search).has('submitted')) {
            const clean = location.pathname + location.hash;
            history.replaceState(null, '', clean);
        }
    })();
</script>

<?php
    return ob_get_clean();
});

// Helper: safe redirect with query params
function ebp_redirect_with($url, $args) {
    wp_safe_redirect(add_query_arg($args, $url)); 
    exit;
}

// === Form submission handler ===
add_action('init', function () {
    if (empty($_POST['ebp_action']) || $_POST['ebp_action'] !== 'ebp_submit_job') return;

    $redirect = isset($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : home_url('/');

    // Honeypot + rate + token
    if (!empty($_POST['website']))         ebp_redirect_with($redirect, ['err'=>'spam']);
    if (!ebp_rate_limit_ok())              ebp_redirect_with($redirect, ['err'=>'rate']);
    if (!ebp_form_token_verify($_POST['ts'] ?? 0, $_POST['rand'] ?? '', $_POST['sig'] ?? '')) {
        ebp_redirect_with($redirect, ['err'=>'token']);
    }

    // Validate form fields
    $description = sanitize_textarea_field($_POST['description'] ?? '');
    $consent     = !empty($_POST['consent']);
    
    if (!$description || !$consent) {
        ebp_redirect_with($redirect, ['err'=>'fields']);
    }

    // Validate image count (1-2 only)
    if (empty($_FILES['photos']['name'][0]) || count($_FILES['photos']['name']) > 2) {
        ebp_redirect_with($redirect, ['err'=>'images']);
    }

    // Upload images (resize to 800px, preserve EXIF)
    $photo_ids = ebp_handle_simple_uploads('photos');

    if (empty($photo_ids)) {
        ebp_redirect_with($redirect, ['err'=>'images']);
    }

    // Create post title (simple timestamp-based)
    $post_title = 'Job Report - ' . date('M j, Y g:i A');
    $post_slug  = 'job-' . date('Y-m-d-His');

    // Content
    $content = wpautop(esc_html($description));
    
    // Add images to content
    if ($photo_ids) {
        $content .= "\n<h3>Photos</h3>\n<div class='ebp-gallery' style='display:flex;gap:10px;flex-wrap:wrap'>";
        foreach ($photo_ids as $id) {
            $content .= wp_get_attachment_image($id, 'large', false, ['style'=>'max-width:400px;height:auto;border-radius:8px']);
        }
        $content .= "</div>";
    }

    // Choose default author
    $default_author = (int) get_option('ebp_default_author_id', 1);

    // Insert job as Pending
    $post_id = wp_insert_post([
        'post_type'    => 'job',
        'post_title'   => $post_title,
        'post_name'    => $post_slug,
        'post_status'  => 'pending',
        'post_content' => $content,
        'post_author'  => $default_author,
    ], true);
    
    if (is_wp_error($post_id)) {
        wp_die('Could not create job: ' . $post_id->get_error_message());
    }

    // Attach media & set Featured Image
    $featured = $photo_ids[0] ?? 0;
    foreach ($photo_ids as $aid) {
        wp_update_post(['ID' => $aid, 'post_parent' => $post_id]);
    }
    if ($featured) {
        set_post_thumbnail($post_id, $featured);
    }

    // Save photo IDs as meta
    update_post_meta($post_id, 'job_photos', $photo_ids);

    // Notify admin
    wp_mail(
        get_option('admin_email'), 
        'New Job Pending Review', 
        'A new job report has been submitted and is awaiting review.\n\nView: ' . get_edit_post_link($post_id)
    );

    // Redirect to success
    wp_safe_redirect(add_query_arg('submitted', '1', $redirect));
    exit;
});

// === Upload helper (resize to 800px but preserve EXIF) ===
function ebp_handle_simple_uploads($field) {
    if (empty($_FILES[$field]['name'])) return [];

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $ids   = [];
    $files = $_FILES[$field];
    $count = is_array($files['name']) ? count($files['name']) : 0;

    // Allowed mime types
    $allowed_mimes = [
        'jpg|jpeg' => 'image/jpeg',
        'png'      => 'image/png',
        'webp'     => 'image/webp',
        'gif'      => 'image/gif',
        'heic|heif'=> 'image/heic',
    ];

    // Maximum dimension for longest side
    $MAX_DIM = 800; // Resize to max 800px
    
    // Check if Imagick is available
    $has_imagick = class_exists('Imagick');
    if (!$has_imagick) {
        error_log('WARNING: Imagick not available. Images will not be resized. Install php-imagick to enable resizing with EXIF preservation.');
    }

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

        $file = [
            'name'     => $files['name'][$i],
            'type'     => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error'    => $files['error'][$i],
            'size'     => $files['size'][$i],
        ];

        // Basic validation
        if ($file['size'] > 20 * 1024 * 1024) continue; // 20MB cap

        // === Resize with Imagick to preserve EXIF ===
        if ($has_imagick && in_array($file['type'], ['image/jpeg', 'image/png', 'image/webp'])) {
            try {
                $im = new Imagick($file['tmp_name']);

                // Auto-orient based on EXIF (fixes rotation issues)
                if (method_exists($im, 'autoOrientImage')) {
                    $im->autoOrientImage();
                    // Set orientation to normal after rotation
                    $im->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
                }

                // Resize if larger than MAX_DIM
                $w = $im->getImageWidth();
                $h = $im->getImageHeight();
                if ($w > $MAX_DIM || $h > $MAX_DIM) {
                    // Lanczos filter for best quality, preserve aspect ratio
                    $im->resizeImage($MAX_DIM, $MAX_DIM, Imagick::FILTER_LANCZOS, 1, true);
                }

                // Compress with good quality
                if ($file['type'] === 'image/jpeg') {
                    $im->setImageCompression(Imagick::COMPRESSION_JPEG);
                    $im->setImageCompressionQuality(85);
                    $im->setInterlaceScheme(Imagick::INTERLACE_JPEG);
                } elseif ($file['type'] === 'image/webp') {
                    $im->setImageCompressionQuality(85);
                } elseif ($file['type'] === 'image/png') {
                    $im->setImageCompressionQuality(85);
                }

                // CRITICAL: DO NOT call stripImage() - preserves all EXIF/GPS data
                $im->writeImage($file['tmp_name']);
                $im->clear();
                $im->destroy();

                // Update file size
                $file['size'] = filesize($file['tmp_name']);
                
                error_log("Successfully resized image: {$file['name']} to max {$MAX_DIM}px");
            } catch (Throwable $e) {
                // If Imagick fails, continue with original file
                error_log('Imagick processing failed for ' . $file['name'] . ': ' . $e->getMessage());
            }
        }
        // === End resize block ===

        // Upload as-is with original filename
        $overrides = [
            'test_form' => false,
            'mimes'     => $allowed_mimes,
        ];
        
        $move = wp_handle_sideload($file, $overrides);
        
        if (!empty($move['error'])) continue;

        // Create attachment
        $aid = wp_insert_attachment([
            'guid'           => $move['url'],
            'post_mime_type' => $move['type'],
            'post_title'     => sanitize_file_name(pathinfo($move['file'], PATHINFO_FILENAME)),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ], $move['file']);
        
        if (is_wp_error($aid)) continue;

        // Generate attachment metadata (WordPress will create thumbnails automatically)
        $meta = wp_generate_attachment_metadata($aid, $move['file']);
        wp_update_attachment_metadata($aid, $meta);

        $ids[] = $aid;
    }

    return $ids;
}