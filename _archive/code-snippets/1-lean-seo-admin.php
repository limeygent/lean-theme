<?php
/**
 * Lean SEO Admin - Meta Box for Pages & Posts
 *
 * Code Snippet: Run on Admin only
 *
 * Adds SEO fields to the page/post editor:
 * - Title
 * - Description (max 160 chars)
 * - Keywords (for Bing)
 * - Noindex / Nofollow checkboxes
 *
 * Stores data in:
 * - _lean_meta_title
 * - _lean_meta_description
 * - _lean_meta_keywords
 * - _lean_meta_noindex
 * - _lean_meta_nofollow
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// ──────────────────────────────────────────────────────────────────────────────
// POST TYPES: Define which post types get SEO fields
// ──────────────────────────────────────────────────────────────────────────────

function lean_seo_post_types() {
	$default_types = ['post', 'page'];
	return apply_filters('lean_seo_post_types', $default_types);
}

// ──────────────────────────────────────────────────────────────────────────────
// META BOX: Register and render
// ──────────────────────────────────────────────────────────────────────────────

add_action('add_meta_boxes', 'lean_register_seo_meta_box');

function lean_register_seo_meta_box() {
	add_meta_box(
		'lean_seo_meta_fields',
		'SEO Settings',
		'lean_render_seo_meta_box',
		lean_seo_post_types(),
		'normal',
		'high'
	);
}

function lean_render_seo_meta_box($post) {
	wp_nonce_field('lean_save_seo_fields', 'lean_seo_nonce');

	$meta_title       = get_post_meta($post->ID, '_lean_meta_title', true);
	$meta_description = get_post_meta($post->ID, '_lean_meta_description', true);
	$meta_keywords    = get_post_meta($post->ID, '_lean_meta_keywords', true);
	$meta_noindex     = get_post_meta($post->ID, '_lean_meta_noindex', true);
	$meta_nofollow    = get_post_meta($post->ID, '_lean_meta_nofollow', true);
	?>
	<table class="form-table">
		<tr>
			<th><label for="lean_meta_title">SEO Title</label></th>
			<td>
				<input type="text" id="lean_meta_title" name="lean_meta_title"
					   value="<?php echo esc_attr($meta_title); ?>" class="large-text">
				<p class="description">Leave blank to use the page title</p>
			</td>
		</tr>
		<tr>
			<th><label for="lean_meta_description">Meta Description</label></th>
			<td>
				<textarea id="lean_meta_description" name="lean_meta_description"
						  rows="3" class="large-text" maxlength="160"><?php echo esc_textarea($meta_description); ?></textarea>
				<p class="description">Max 160 characters. <span id="lean-desc-count"><?php echo strlen($meta_description); ?></span>/160</p>
			</td>
		</tr>
		<tr>
			<th><label for="lean_meta_keywords">Keywords</label></th>
			<td>
				<input type="text" id="lean_meta_keywords" name="lean_meta_keywords"
					   value="<?php echo esc_attr($meta_keywords); ?>" class="large-text">
				<p class="description">Comma-separated (used by Bing)</p>
			</td>
		</tr>
		<tr>
			<th>Robots</th>
			<td>
				<label style="margin-right: 20px;">
					<input type="checkbox" name="lean_meta_noindex" value="1" <?php checked($meta_noindex, '1'); ?>>
					Noindex (hide from search engines)
				</label>
				<label>
					<input type="checkbox" name="lean_meta_nofollow" value="1" <?php checked($meta_nofollow, '1'); ?>>
					Nofollow (don't follow links)
				</label>
			</td>
		</tr>
	</table>

	<script>
	jQuery(function($) {
		$('#lean_meta_description').on('input', function() {
			$('#lean-desc-count').text($(this).val().length);
		});
	});
	</script>
	<?php
}

// ──────────────────────────────────────────────────────────────────────────────
// SAVE: Handle form submission
// ──────────────────────────────────────────────────────────────────────────────

add_action('save_post', 'lean_save_seo_fields', 10, 3);

function lean_save_seo_fields($post_id, $post, $update) {
	// Bail on autosave/revision
	if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) return;

	// Only for our post types
	if (!in_array($post->post_type, lean_seo_post_types(), true)) return;

	// Capability check
	if (!current_user_can('edit_post', $post_id)) return;

	// Verify nonce
	if (!isset($_POST['lean_seo_nonce']) || !wp_verify_nonce($_POST['lean_seo_nonce'], 'lean_save_seo_fields')) return;

	// Save fields
	if (isset($_POST['lean_meta_title'])) {
		update_post_meta($post_id, '_lean_meta_title', sanitize_text_field(wp_unslash($_POST['lean_meta_title'])));
	}

	if (isset($_POST['lean_meta_description'])) {
		update_post_meta($post_id, '_lean_meta_description', sanitize_textarea_field(wp_unslash($_POST['lean_meta_description'])));
	}

	if (isset($_POST['lean_meta_keywords'])) {
		$kw = (string) wp_unslash($_POST['lean_meta_keywords']);
		$arr = array_filter(array_map('sanitize_text_field', array_map('trim', explode(',', $kw))));
		update_post_meta($post_id, '_lean_meta_keywords', implode(', ', $arr));
	}

	update_post_meta($post_id, '_lean_meta_noindex', isset($_POST['lean_meta_noindex']) ? '1' : '');
	update_post_meta($post_id, '_lean_meta_nofollow', isset($_POST['lean_meta_nofollow']) ? '1' : '');
}

// ──────────────────────────────────────────────────────────────────────────────
// ADMIN COLUMNS: Show SEO status in post list
// ──────────────────────────────────────────────────────────────────────────────

add_action('admin_init', function() {
	foreach (lean_seo_post_types() as $post_type) {
		if ($post_type === 'page') {
			add_filter('manage_pages_columns', 'lean_add_seo_columns');
			add_action('manage_pages_custom_column', 'lean_populate_seo_columns', 10, 2);
		} else {
			add_filter("manage_{$post_type}_posts_columns", 'lean_add_seo_columns');
			add_action("manage_{$post_type}_posts_custom_column", 'lean_populate_seo_columns', 10, 2);
		}
	}
});

function lean_add_seo_columns($cols) {
	$new = [];
	foreach ($cols as $key => $label) {
		$new[$key] = $label;
		if ($key === 'title') {
			$new['lean_seo_status'] = 'SEO';
		}
	}
	return $new;
}

function lean_populate_seo_columns($column, $post_id) {
	if ($column !== 'lean_seo_status') return;

	$title = get_post_meta($post_id, '_lean_meta_title', true);
	$desc = get_post_meta($post_id, '_lean_meta_description', true);
	$noindex = get_post_meta($post_id, '_lean_meta_noindex', true);

	$status = [];
	if ($title) $status[] = '<span style="color:green;" title="Has SEO title">T</span>';
	if ($desc) $status[] = '<span style="color:green;" title="Has meta description">D</span>';
	if ($noindex) $status[] = '<span style="color:red;" title="Noindex">N</span>';

	echo $status ? implode(' ', $status) : '<span style="color:#999;">—</span>';
}

// Add column width
add_action('admin_head', function() {
	echo '<style>.column-lean_seo_status { width: 60px; text-align: center; }</style>';
});
