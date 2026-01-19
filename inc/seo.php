<?php
/**
 * Filename: seo.php
 * Purpose: SEO meta fields admin UI + frontend output for lean templates
 *
 * Includes:
 * - Meta box for title, description, keywords, noindex/nofollow
 * - Admin columns + Quick Edit support
 * - Frontend output function: lean_output_seo_meta_tags()
 * - Cleanup of WP default SEO cruft
 *
 * Note: Keywords meta tag retained for Bing compatibility
 */

// ──────────────────────────────────────────────────────────────────────────────
// CLEANUP: Remove WP default SEO tags (we handle these ourselves)
// ──────────────────────────────────────────────────────────────────────────────
add_action('after_setup_theme', function() {
	// Turn off WP's title-tag support so no <title> is auto-generated
	remove_theme_support('title-tag');

	// In case the theme/hooks still hooked the render function at priority 1
	remove_action('wp_head', '_wp_render_title_tag', 1);

	// Remove oEmbed discovery links
	remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);

	// Remove the REST API link
	remove_action('wp_head', 'rest_output_link_wp_head', 10);

	// Remove oEmbed-specific JavaScript from the front-end
	remove_action('wp_head', 'wp_oembed_add_host_js', 10);

	// Disable oEmbed auto-discovery
	add_filter('embed_oembed_discover', '__return_false', 10);

	// Remove the WP shortlink from the <head>
	remove_action('wp_head', 'wp_shortlink_wp_head', 10);

	// Remove EditURI (RSD) link
	remove_action('wp_head', 'rsd_link', 10);

	// Prevent default rel=canonical link in <head>
	remove_action('wp_head', 'rel_canonical', 10);
});

// Prevent default robots meta in <head>
add_filter('wp_robots', function($robots) {
	return []; // Remove all robots meta tags
});

// ──────────────────────────────────────────────────────────────────────────────
// POST TYPES: Define which post types get SEO fields
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Get post types that support SEO fields
 * Use filter 'lean_seo_post_types' to add custom post types
 *
 * Example: add_filter('lean_seo_post_types', function($types) {
 *     $types[] = 'service';
 *     return $types;
 * });
 */
function lean_seo_post_types() {
	$default_types = ['post', 'page'];
	return apply_filters('lean_seo_post_types', $default_types);
}

// ──────────────────────────────────────────────────────────────────────────────
// FRONTEND OUTPUT: SEO Meta Tags (called directly in head.php)
// ──────────────────────────────────────────────────────────────────────────────
function lean_output_seo_meta_tags() {
	if (!is_singular()) {
		return;
	}

	global $post;

	// Retrieve custom meta fields
	$meta_title       = get_post_meta($post->ID, '_lean_meta_title', true);
	$meta_description = get_post_meta($post->ID, '_lean_meta_description', true);
	$meta_noindex     = get_post_meta($post->ID, '_lean_meta_noindex', true);
	$meta_nofollow    = get_post_meta($post->ID, '_lean_meta_nofollow', true);
	$meta_keywords    = get_post_meta($post->ID, '_lean_meta_keywords', true);

	// Featured image and alt
	$meta_image     = has_post_thumbnail($post->ID) ? get_the_post_thumbnail_url($post->ID, 'full') : '';
	$meta_image_alt = '';
	if (has_post_thumbnail($post->ID)) {
		$thumb_id       = get_post_thumbnail_id($post->ID);
		$meta_image_alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
	}
	if (empty($meta_image_alt)) {
		$meta_image_alt = $meta_description;
	}

	// Fallbacks
	if (empty($meta_title)) {
		$meta_title = get_the_title($post->ID);
	} else {
		$meta_title = do_shortcode($meta_title);
	}
	if (empty($meta_description)) {
		$meta_description = wp_trim_words(wp_strip_all_tags($post->post_content), 30, '...');
	}

	// Canonical URL
	$canonical_url = get_permalink($post->ID);

	$modified_time = get_the_modified_time('c', $post->ID);

	// Build robots directives
	$robots = [];
	$robots[] = $meta_noindex ? 'noindex' : 'index';
	if (!$meta_noindex) {
		$robots[] = 'max-image-preview:large';
		$robots[] = 'max-snippet:-1';
	}
	$robots[] = $meta_nofollow ? 'nofollow' : 'follow';
	$robots_content = implode(', ', $robots);

	// Get site name from options
	$site_name = get_option('business_name', get_bloginfo('name'));

	// Output SEO meta tags
	echo PHP_EOL . '<!-- SEO Meta Tags -->' . PHP_EOL;
	echo '<title>' . esc_html($meta_title) . '</title>' . PHP_EOL;
	echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . PHP_EOL;
	if (!empty($meta_keywords) && trim($meta_keywords)) {
		echo '<meta name="keywords" content="' . esc_attr(trim($meta_keywords)) . '">' . PHP_EOL;
	}
	echo '<meta name="robots" content="' . esc_attr($robots_content) . '">' . PHP_EOL;
	echo '<link rel="canonical" href="' . esc_url($canonical_url) . '">' . PHP_EOL;
	echo '<meta property="article:modified_time" content="' . esc_attr($modified_time) . '">' . PHP_EOL;

	// Dublin Core
	echo PHP_EOL . '<!-- Dublin Core Meta Tags -->' . PHP_EOL;
	echo '<meta name="dc.title" content="' . esc_attr($meta_title) . '">' . PHP_EOL;
	echo '<meta name="dc.description" content="' . esc_attr($meta_description) . '">' . PHP_EOL;
	echo '<meta name="dc.language" content="en_US">' . PHP_EOL;
	if (!empty($meta_keywords) && trim($meta_keywords)) {
		echo '<meta name="dc.keywords" content="' . esc_attr(trim($meta_keywords)) . '">' . PHP_EOL;
	}

	// Open Graph
	echo PHP_EOL . '<!-- Open Graph Meta Tags -->' . PHP_EOL;
	echo '<meta property="og:title" content="' . esc_attr($meta_title) . '">' . PHP_EOL;
	echo '<meta property="og:description" content="' . esc_attr($meta_description) . '">' . PHP_EOL;
	echo '<meta property="og:locale" content="en_US">' . PHP_EOL;
	echo '<meta property="og:type" content="article">' . PHP_EOL;
	echo '<meta property="og:url" content="' . esc_url($canonical_url) . '">' . PHP_EOL;
	if (!empty($meta_image)) {
		echo '<meta property="og:image" content="' . esc_url($meta_image) . '">' . PHP_EOL;
		echo '<meta property="og:image:alt" content="' . esc_attr($meta_image_alt) . '">' . PHP_EOL;
	}
	echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . PHP_EOL;

	// Twitter Card
	echo PHP_EOL . '<!-- Twitter Card Meta Tags -->' . PHP_EOL;
	echo '<meta name="twitter:card" content="summary_large_image">' . PHP_EOL;
	echo '<meta name="twitter:title" content="' . esc_attr($meta_title) . '">' . PHP_EOL;
	echo '<meta name="twitter:description" content="' . esc_attr($meta_description) . '">' . PHP_EOL;
	if (!empty($meta_image)) {
		echo '<meta name="twitter:image" content="' . esc_url($meta_image) . '">' . PHP_EOL;
	}
	echo '<!-- End SEO Meta Tags -->' . PHP_EOL . PHP_EOL;
}

// ──────────────────────────────────────────────────────────────────────────────
// ADMIN: Meta Box for SEO Fields
// ──────────────────────────────────────────────────────────────────────────────

function lean_add_meta_seo_fields($post) {
	wp_nonce_field('lean_save_meta_seo_fields', 'lean_seo_nonce');

	$meta_title       = get_post_meta($post->ID, '_lean_meta_title', true);
	$meta_description = get_post_meta($post->ID, '_lean_meta_description', true);
	$meta_noindex     = get_post_meta($post->ID, '_lean_meta_noindex', true);
	$meta_nofollow    = get_post_meta($post->ID, '_lean_meta_nofollow', true);
	$meta_keywords    = get_post_meta($post->ID, '_lean_meta_keywords', true);
	?>
	<div class="postbox">
		<h3>SEO Meta Settings</h3>
		<div style="padding:10px;">
			<label for="lean_meta_title"><strong>Title</strong></label>
			<input type="text" id="lean_meta_title" name="lean_meta_title"
				   value="<?php echo esc_attr($meta_title); ?>"
				   style="width:100%;margin:0.5em 0;">

			<label for="lean_meta_description"><strong>Description</strong> (max 160 chars)</label>
			<textarea id="lean_meta_description" name="lean_meta_description"
					  rows="4" maxlength="160"
					  style="width:100%;margin:0.5em 0;"><?php echo esc_textarea($meta_description); ?></textarea>

			<label for="lean_meta_keywords"><strong>Keywords</strong> (comma-separated, used by Bing)</label>
			<input type="text" id="lean_meta_keywords" name="lean_meta_keywords"
				   value="<?php echo esc_attr($meta_keywords); ?>"
				   style="width:100%;margin:0.5em 0;">

			<div style="margin-top:1em;">
				<label>
					<input type="checkbox" name="lean_meta_noindex" value="1" <?php checked($meta_noindex, '1'); ?>>
					Noindex
				</label><br>
				<label>
					<input type="checkbox" name="lean_meta_nofollow" value="1" <?php checked($meta_nofollow, '1'); ?>>
					Nofollow
				</label>
			</div>
		</div>
	</div>
	<?php
}

function lean_register_seo_meta_box() {
	add_meta_box(
		'lean_seo_meta_fields',
		'SEO Settings',
		'lean_add_meta_seo_fields',
		lean_seo_post_types(),
		'normal',
		'default'
	);
}
add_action('add_meta_boxes', 'lean_register_seo_meta_box');

/**
 * Save SEO fields on both normal save and Quick Edit.
 */
add_action('save_post', 'lean_save_seo_fields', 10, 3);
function lean_save_seo_fields($post_id, $post, $update) {
	// 1) Bail on autosave/revision
	if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) return;

	// 2) Only for our post types
	if (!in_array($post->post_type, lean_seo_post_types(), true)) return;

	// 3) Capability
	if (!current_user_can('edit_post', $post_id)) return;

	// 4) Accept either: meta box nonce OR Quick Edit nonce
	$has_metabox_nonce = isset($_POST['lean_seo_nonce']) && wp_verify_nonce($_POST['lean_seo_nonce'], 'lean_save_meta_seo_fields');
	$has_quickedit_nonce = isset($_POST['_inline_edit']) && wp_verify_nonce($_POST['_inline_edit'], 'inlineeditnonce');

	if (!$has_metabox_nonce && !$has_quickedit_nonce) return;

	// 5) Save fields
	if (isset($_POST['lean_meta_title'])) {
		update_post_meta($post_id, '_lean_meta_title', sanitize_text_field(wp_unslash($_POST['lean_meta_title'])));
	}

	if (isset($_POST['lean_meta_description'])) {
		update_post_meta($post_id, '_lean_meta_description', sanitize_textarea_field(wp_unslash($_POST['lean_meta_description'])));
	}

	if (isset($_POST['lean_meta_keywords'])) {
		$kw  = (string) wp_unslash($_POST['lean_meta_keywords']);
		$arr = array_filter(array_map('sanitize_text_field', array_map('trim', explode(',', $kw))));
		update_post_meta($post_id, '_lean_meta_keywords', implode(', ', $arr));
	}

	update_post_meta($post_id, '_lean_meta_noindex', isset($_POST['lean_meta_noindex']) ? '1' : '');
	update_post_meta($post_id, '_lean_meta_nofollow', isset($_POST['lean_meta_nofollow']) ? '1' : '');
}

// ──────────────────────────────────────────────────────────────────────────────
// ADMIN LIST SCREEN COLUMNS
// ──────────────────────────────────────────────────────────────────────────────
function lean_add_seo_columns($cols) {
	$new = [];
	foreach ($cols as $key => $label) {
		$new[$key] = $label;
		if ($key === 'title') {
			$new['seo_title']       = 'SEO Title';
			$new['seo_description'] = 'SEO Description';
			$new['seo_keywords']    = 'Keywords';
			$new['seo_robots']      = 'SEO Robots';
		}
	}
	return $new;
}

// Register columns for all SEO-enabled post types
add_action('admin_init', function() {
	foreach (lean_seo_post_types() as $post_type) {
		if ($post_type === 'page') {
			add_filter('manage_pages_columns', 'lean_add_seo_columns');
			add_action('manage_pages_custom_column', 'lean_populate_seo_columns', 10, 2);
			add_filter('manage_edit-page_sortable_columns', 'lean_make_seo_columns_sortable');
		} else {
			add_filter("manage_{$post_type}_posts_columns", 'lean_add_seo_columns');
			add_action("manage_{$post_type}_posts_custom_column", 'lean_populate_seo_columns', 10, 2);
			add_filter("manage_edit-{$post_type}_sortable_columns", 'lean_make_seo_columns_sortable');
		}
	}
});

function lean_populate_seo_columns($column, $post_id) {
	switch ($column) {
		case 'seo_title':
			$val = get_post_meta($post_id, '_lean_meta_title', true);
			echo '<span data-full-value="' . esc_attr($val) . '">' . ($val ? esc_html($val) : '<em>Not set</em>') . '</span>';
			break;
		case 'seo_description':
			$val = get_post_meta($post_id, '_lean_meta_description', true);
			$out = $val
				? (strlen($val) > 50 ? esc_html(substr($val, 0, 50)) . '...' : esc_html($val))
				: '<em>Not set</em>';
			echo '<span data-full-value="' . esc_attr($val) . '">' . $out . '</span>';
			break;
		case 'seo_keywords':
			$val = get_post_meta($post_id, '_lean_meta_keywords', true);
			echo '<span data-full-value="' . esc_attr($val) . '">' . ($val ? esc_html($val) : '<em>Not set</em>') . '</span>';
			break;
		case 'seo_robots':
			$noindex  = get_post_meta($post_id, '_lean_meta_noindex', true);
			$nofollow = get_post_meta($post_id, '_lean_meta_nofollow', true);
			$robots   = [];
			if ($noindex) $robots[] = 'noindex';
			if ($nofollow) $robots[] = 'nofollow';
			echo '<span data-noindex="' . ($noindex ? '1' : '0') . '" data-nofollow="' . ($nofollow ? '1' : '0') . '">'
				. ($robots ? esc_html(implode(', ', $robots)) : 'index, follow')
				. '</span>';
			break;
	}
}

function lean_make_seo_columns_sortable($cols) {
	$cols['seo_title']       = 'seo_title';
	$cols['seo_description'] = 'seo_description';
	$cols['seo_keywords']    = 'seo_keywords';
	$cols['seo_robots']      = 'seo_robots';
	return $cols;
}

function lean_handle_seo_column_sorting($query) {
	if (is_admin() && $query->is_main_query()) {
		switch ($query->get('orderby')) {
			case 'seo_title':
				$query->set('meta_key', '_lean_meta_title');
				$query->set('orderby', 'meta_value');
				break;
			case 'seo_description':
				$query->set('meta_key', '_lean_meta_description');
				$query->set('orderby', 'meta_value');
				break;
			case 'seo_keywords':
				$query->set('meta_key', '_lean_meta_keywords');
				$query->set('orderby', 'meta_value');
				break;
			case 'seo_robots':
				$query->set('meta_key', '_lean_meta_noindex');
				$query->set('orderby', 'meta_value');
				break;
		}
	}
}
add_action('pre_get_posts', 'lean_handle_seo_column_sorting');

// ──────────────────────────────────────────────────────────────────────────────
// QUICK EDIT SUPPORT
// ──────────────────────────────────────────────────────────────────────────────
function lean_add_quick_edit_fields($column_name, $post_type) {
	if (!in_array($post_type, lean_seo_post_types(), true)) return;
	static $done = false;
	if ($done) return;
	$done = true;
	?>
	<fieldset class="inline-edit-col-right">
		<div class="inline-edit-col">
			<h4>SEO Settings</h4>
			<label><span class="title">Title</span>
				<span class="input-text-wrap">
					<input type="text" name="lean_meta_title" class="lean_meta_title">
				</span>
			</label>
			<label><span class="title">Description</span>
				<span class="input-text-wrap">
					<textarea name="lean_meta_description" class="lean_meta_description" rows="3"></textarea>
				</span>
			</label>
			<label><span class="title">Keywords</span>
				<span class="input-text-wrap">
					<input type="text" name="lean_meta_keywords" class="lean_meta_keywords" placeholder="Comma-separated">
				</span>
			</label>
			<div class="inline-edit-group">
				<label class="alignleft">
					<input type="checkbox" name="lean_meta_noindex" class="lean_meta_noindex" value="1">
					<span class="checkbox-title">Noindex</span>
				</label>
				<label class="alignleft">
					<input type="checkbox" name="lean_meta_nofollow" class="lean_meta_nofollow" value="1">
					<span class="checkbox-title">Nofollow</span>
				</label>
			</div>
		</div>
	</fieldset>
	<?php
}
add_action('quick_edit_custom_box', 'lean_add_quick_edit_fields', 10, 2);

function lean_admin_footer_script() {
	$screen = get_current_screen();
	$seo_screens = array_map(function($type) {
		return $type === 'page' ? 'edit-page' : "edit-{$type}";
	}, lean_seo_post_types());

	if (!in_array($screen->id, $seo_screens, true)) return;

	?>
	<script type="text/javascript">
	jQuery(function($){
		$('body').on('click', '.editinline', function(){
			var id = $(this).closest('tr').attr('id').replace('post-', '');
			if (!id || isNaN(id)) return;
			var row = $('#post-' + id);
			var title = row.find('.column-seo_title span').attr('data-full-value') || '';
			var desc  = row.find('.column-seo_description span').attr('data-full-value') || '';
			var keys  = row.find('.column-seo_keywords span').attr('data-full-value') || '';
			var rspan = row.find('.column-seo_robots span');
			var noindex  = (rspan.attr('data-noindex') === '1');
			var nofollow = (rspan.attr('data-nofollow') === '1');
			$('.lean_meta_title').val(title);
			$('.lean_meta_description').val(desc);
			$('.lean_meta_keywords').val(keys);
			$('.lean_meta_noindex').prop('checked', noindex);
			$('.lean_meta_nofollow').prop('checked', nofollow);
		});
	});
	</script>
	<?php
}
add_action('admin_footer', 'lean_admin_footer_script');

// ──────────────────────────────────────────────────────────────────────────────
// ADMIN CSS
// ──────────────────────────────────────────────────────────────────────────────
function lean_seo_admin_css() {
	$screen = get_current_screen();
	$seo_screens = array_map(function($type) {
		return $type === 'page' ? 'edit-page' : "edit-{$type}";
	}, lean_seo_post_types());

	if (!in_array($screen->id, $seo_screens, true)) return;

	?>
	<style>
	.column-seo_title,
	.column-seo_description,
	.column-seo_keywords { width: 15%; }
	.column-seo_robots { width: 10%; }
	.column-seo_description { max-width: 200px; word-wrap: break-word; }
	.inline-edit-col h4 { margin: 0.2em 0; }
	.inline-edit-group .alignleft { margin-right: 15px; }
	</style>
	<?php
}
add_action('admin_head', 'lean_seo_admin_css');
