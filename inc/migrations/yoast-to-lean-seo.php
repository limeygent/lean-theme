<?php
/**
 * Yoast SEO to Lean Theme SEO Migration
 *
 * Run once via: /wp-admin/admin.php?page=lean-migrate-seo
 *
 * Migrates:
 * - _yoast_wpseo_title        → _lean_meta_title
 * - _yoast_wpseo_metadesc     → _lean_meta_description
 * - _yoast_wpseo_focuskw      → _lean_meta_keywords
 * - _yoast_wpseo_meta-robots-noindex  → _lean_meta_noindex
 * - _yoast_wpseo_meta-robots-nofollow → _lean_meta_nofollow
 *
 * After migration, you can safely deactivate Yoast SEO.
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// ──────────────────────────────────────────────────────────────────────────────
// ADMIN PAGE
// ──────────────────────────────────────────────────────────────────────────────

add_action('admin_menu', 'lean_migrate_seo_admin_menu');

function lean_migrate_seo_admin_menu() {
	add_management_page(
		'Migrate Yoast to Lean SEO',
		'Yoast → Lean SEO',
		'manage_options',
		'lean-migrate-seo',
		'lean_migrate_seo_page'
	);
}

function lean_migrate_seo_page() {
	if (!current_user_can('manage_options')) {
		wp_die('Unauthorized access');
	}

	$action = isset($_POST['lean_migrate_action']) ? $_POST['lean_migrate_action'] : '';
	$nonce_valid = isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'lean_migrate_seo');

	?>
	<div class="wrap">
		<h1>Migrate Yoast SEO → Lean Theme SEO</h1>

		<?php if ($action === 'preview' && $nonce_valid): ?>
			<?php lean_migrate_seo_preview(); ?>
		<?php elseif ($action === 'migrate' && $nonce_valid): ?>
			<?php lean_migrate_seo_execute(); ?>
		<?php else: ?>
			<?php lean_migrate_seo_form(); ?>
		<?php endif; ?>
	</div>
	<?php
}

// ──────────────────────────────────────────────────────────────────────────────
// FORM
// ──────────────────────────────────────────────────────────────────────────────

function lean_migrate_seo_form() {
	// Quick stats
	global $wpdb;

	$yoast_title_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_yoast_wpseo_title' AND meta_value != ''");
	$yoast_desc_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_yoast_wpseo_metadesc' AND meta_value != ''");
	$yoast_kw_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_yoast_wpseo_focuskw' AND meta_value != ''");
	$yoast_noindex_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_yoast_wpseo_meta-robots-noindex' AND meta_value = '1'");
	$yoast_nofollow_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_yoast_wpseo_meta-robots-nofollow' AND meta_value = '1'");

	$lean_title_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_lean_meta_title' AND meta_value != ''");

	?>
	<div class="card" style="max-width: 800px; padding: 20px;">
		<h2>Yoast SEO Data Found</h2>
		<table class="widefat striped" style="max-width: 400px;">
			<thead>
				<tr><th>Field</th><th>Count</th></tr>
			</thead>
			<tbody>
				<tr><td>SEO Titles</td><td><strong><?php echo intval($yoast_title_count); ?></strong></td></tr>
				<tr><td>Meta Descriptions</td><td><strong><?php echo intval($yoast_desc_count); ?></strong></td></tr>
				<tr><td>Focus Keywords</td><td><strong><?php echo intval($yoast_kw_count); ?></strong></td></tr>
				<tr><td>Noindex Pages</td><td><strong><?php echo intval($yoast_noindex_count); ?></strong></td></tr>
				<tr><td>Nofollow Pages</td><td><strong><?php echo intval($yoast_nofollow_count); ?></strong></td></tr>
			</tbody>
		</table>

		<?php if ($lean_title_count > 0): ?>
		<div class="notice notice-warning" style="margin: 20px 0;">
			<p><strong>Note:</strong> You already have <?php echo intval($lean_title_count); ?> posts with Lean SEO data. The migration will <strong>skip</strong> posts that already have Lean SEO data to avoid overwriting.</p>
		</div>
		<?php endif; ?>

		<h2 style="margin-top: 30px;">Migration Mapping</h2>
		<table class="widefat striped" style="max-width: 600px;">
			<thead>
				<tr><th>Yoast Field</th><th>→</th><th>Lean Field</th></tr>
			</thead>
			<tbody>
				<tr><td><code>_yoast_wpseo_title</code></td><td>→</td><td><code>_lean_meta_title</code></td></tr>
				<tr><td><code>_yoast_wpseo_metadesc</code></td><td>→</td><td><code>_lean_meta_description</code></td></tr>
				<tr><td><code>_yoast_wpseo_focuskw</code></td><td>→</td><td><code>_lean_meta_keywords</code></td></tr>
				<tr><td><code>_yoast_wpseo_meta-robots-noindex</code></td><td>→</td><td><code>_lean_meta_noindex</code></td></tr>
				<tr><td><code>_yoast_wpseo_meta-robots-nofollow</code></td><td>→</td><td><code>_lean_meta_nofollow</code></td></tr>
			</tbody>
		</table>

		<h2 style="margin-top: 30px;">Actions</h2>
		<form method="post" style="display: inline-block; margin-right: 10px;">
			<?php wp_nonce_field('lean_migrate_seo'); ?>
			<input type="hidden" name="lean_migrate_action" value="preview">
			<button type="submit" class="button button-secondary">Preview Migration</button>
		</form>

		<form method="post" style="display: inline-block;">
			<?php wp_nonce_field('lean_migrate_seo'); ?>
			<input type="hidden" name="lean_migrate_action" value="migrate">
			<button type="submit" class="button button-primary" onclick="return confirm('Are you sure? This will copy Yoast SEO data to Lean SEO fields.');">Run Migration</button>
		</form>

		<p class="description" style="margin-top: 15px;">
			<strong>Tip:</strong> Preview first to see what will be migrated. The migration copies data (doesn't delete Yoast data).
		</p>
	</div>
	<?php
}

// ──────────────────────────────────────────────────────────────────────────────
// PREVIEW
// ──────────────────────────────────────────────────────────────────────────────

function lean_migrate_seo_preview() {
	$posts = lean_get_posts_with_yoast_data();

	if (empty($posts)) {
		echo '<div class="notice notice-warning"><p>No Yoast SEO data found to migrate.</p></div>';
		echo '<p><a href="' . admin_url('tools.php?page=lean-migrate-seo') . '" class="button">← Back</a></p>';
		return;
	}

	?>
	<div class="card" style="max-width: 1200px; padding: 20px;">
		<h2>Migration Preview</h2>
		<p>Found <strong><?php echo count($posts); ?></strong> posts with Yoast SEO data to migrate:</p>

		<table class="widefat striped">
			<thead>
				<tr>
					<th>ID</th>
					<th>Title</th>
					<th>Type</th>
					<th>Yoast Title</th>
					<th>Yoast Description</th>
					<th>Keywords</th>
					<th>Robots</th>
					<th>Status</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($posts as $post): ?>
				<tr>
					<td><?php echo intval($post['id']); ?></td>
					<td><a href="<?php echo get_edit_post_link($post['id']); ?>" target="_blank"><?php echo esc_html($post['post_title']); ?></a></td>
					<td><?php echo esc_html($post['post_type']); ?></td>
					<td><?php echo esc_html(mb_substr($post['yoast_title'], 0, 40)); ?><?php echo strlen($post['yoast_title']) > 40 ? '...' : ''; ?></td>
					<td><?php echo esc_html(mb_substr($post['yoast_desc'], 0, 50)); ?><?php echo strlen($post['yoast_desc']) > 50 ? '...' : ''; ?></td>
					<td><?php echo esc_html($post['yoast_kw']); ?></td>
					<td>
						<?php if ($post['yoast_noindex']): ?><span style="color:red;">noindex</span><?php endif; ?>
						<?php if ($post['yoast_nofollow']): ?><span style="color:orange;">nofollow</span><?php endif; ?>
						<?php if (!$post['yoast_noindex'] && !$post['yoast_nofollow']): ?>index, follow<?php endif; ?>
					</td>
					<td>
						<?php if ($post['has_lean_data']): ?>
							<span style="color:orange;">⚠ Has Lean data (skip)</span>
						<?php else: ?>
							<span style="color:green;">✓ Will migrate</span>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p style="margin-top: 20px;">
			<a href="<?php echo admin_url('tools.php?page=lean-migrate-seo'); ?>" class="button">← Back</a>
			<form method="post" style="display: inline-block; margin-left: 10px;">
				<?php wp_nonce_field('lean_migrate_seo'); ?>
				<input type="hidden" name="lean_migrate_action" value="migrate">
				<button type="submit" class="button button-primary" onclick="return confirm('Are you sure? This will copy Yoast SEO data to Lean SEO fields.');">Run Migration</button>
			</form>
		</p>
	</div>
	<?php
}

// ──────────────────────────────────────────────────────────────────────────────
// EXECUTE MIGRATION
// ──────────────────────────────────────────────────────────────────────────────

function lean_migrate_seo_execute() {
	$posts = lean_get_posts_with_yoast_data();

	if (empty($posts)) {
		echo '<div class="notice notice-warning"><p>No Yoast SEO data found to migrate.</p></div>';
		echo '<p><a href="' . admin_url('tools.php?page=lean-migrate-seo') . '" class="button">← Back</a></p>';
		return;
	}

	$migrated = 0;
	$skipped = 0;
	$results = [];

	foreach ($posts as $post) {
		$post_id = $post['id'];

		// Skip if already has Lean SEO data
		if ($post['has_lean_data']) {
			$skipped++;
			$results[] = [
				'id' => $post_id,
				'title' => $post['post_title'],
				'status' => 'skipped',
				'reason' => 'Already has Lean SEO data'
			];
			continue;
		}

		// Migrate title
		if (!empty($post['yoast_title'])) {
			// Clean up Yoast variables like %%title%% %%sep%% %%sitename%%
			$clean_title = lean_clean_yoast_title($post['yoast_title'], $post_id);
			update_post_meta($post_id, '_lean_meta_title', $clean_title);
		}

		// Migrate description
		if (!empty($post['yoast_desc'])) {
			update_post_meta($post_id, '_lean_meta_description', $post['yoast_desc']);
		}

		// Migrate focus keyword as keywords
		if (!empty($post['yoast_kw'])) {
			update_post_meta($post_id, '_lean_meta_keywords', $post['yoast_kw']);
		}

		// Migrate noindex
		if ($post['yoast_noindex']) {
			update_post_meta($post_id, '_lean_meta_noindex', '1');
		}

		// Migrate nofollow
		if ($post['yoast_nofollow']) {
			update_post_meta($post_id, '_lean_meta_nofollow', '1');
		}

		$migrated++;
		$results[] = [
			'id' => $post_id,
			'title' => $post['post_title'],
			'status' => 'migrated',
			'reason' => ''
		];
	}

	?>
	<div class="card" style="max-width: 1000px; padding: 20px;">
		<h2>Migration Complete</h2>

		<div class="notice notice-success" style="margin: 20px 0;">
			<p><strong>Migrated:</strong> <?php echo intval($migrated); ?> posts</p>
			<?php if ($skipped > 0): ?>
			<p><strong>Skipped:</strong> <?php echo intval($skipped); ?> posts (already had Lean SEO data)</p>
			<?php endif; ?>
		</div>

		<h3>Results</h3>
		<table class="widefat striped">
			<thead>
				<tr>
					<th>ID</th>
					<th>Title</th>
					<th>Status</th>
					<th>Notes</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($results as $result): ?>
				<tr>
					<td><?php echo intval($result['id']); ?></td>
					<td><a href="<?php echo get_edit_post_link($result['id']); ?>" target="_blank"><?php echo esc_html($result['title']); ?></a></td>
					<td>
						<?php if ($result['status'] === 'migrated'): ?>
							<span style="color:green;">✓ Migrated</span>
						<?php else: ?>
							<span style="color:orange;">⚠ Skipped</span>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html($result['reason']); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h3 style="margin-top: 30px;">Next Steps</h3>
		<ol>
			<li>Verify a few pages to ensure SEO data migrated correctly</li>
			<li>Deactivate Yoast SEO plugin</li>
			<li>Remove this migration file from the theme (optional)</li>
		</ol>

		<p><a href="<?php echo admin_url('tools.php?page=lean-migrate-seo'); ?>" class="button">← Back to Migration Tool</a></p>
	</div>
	<?php
}

// ──────────────────────────────────────────────────────────────────────────────
// HELPERS
// ──────────────────────────────────────────────────────────────────────────────

function lean_get_posts_with_yoast_data() {
	global $wpdb;

	// Get all posts that have any Yoast SEO data
	$sql = "
		SELECT DISTINCT p.ID, p.post_title, p.post_type
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
		WHERE pm.meta_key IN (
			'_yoast_wpseo_title',
			'_yoast_wpseo_metadesc',
			'_yoast_wpseo_focuskw',
			'_yoast_wpseo_meta-robots-noindex',
			'_yoast_wpseo_meta-robots-nofollow'
		)
		AND pm.meta_value != ''
		AND p.post_status IN ('publish', 'draft', 'pending', 'private')
		ORDER BY p.post_type, p.post_title
	";

	$posts = $wpdb->get_results($sql);

	$result = [];
	foreach ($posts as $post) {
		$post_id = $post->ID;

		$yoast_title = get_post_meta($post_id, '_yoast_wpseo_title', true);
		$yoast_desc = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
		$yoast_kw = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
		$yoast_noindex = get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true);
		$yoast_nofollow = get_post_meta($post_id, '_yoast_wpseo_meta-robots-nofollow', true);

		// Check if already has Lean SEO data
		$lean_title = get_post_meta($post_id, '_lean_meta_title', true);
		$lean_desc = get_post_meta($post_id, '_lean_meta_description', true);
		$has_lean_data = !empty($lean_title) || !empty($lean_desc);

		// Only include if has meaningful Yoast data
		if (empty($yoast_title) && empty($yoast_desc) && empty($yoast_kw) && !$yoast_noindex && !$yoast_nofollow) {
			continue;
		}

		$result[] = [
			'id' => $post_id,
			'post_title' => $post->post_title,
			'post_type' => $post->post_type,
			'yoast_title' => $yoast_title,
			'yoast_desc' => $yoast_desc,
			'yoast_kw' => $yoast_kw,
			'yoast_noindex' => $yoast_noindex === '1',
			'yoast_nofollow' => $yoast_nofollow === '1',
			'has_lean_data' => $has_lean_data,
		];
	}

	return $result;
}

/**
 * Clean Yoast title variables
 * Replaces %%title%%, %%sep%%, %%sitename%% etc with actual values
 */
function lean_clean_yoast_title($title, $post_id) {
	$post = get_post($post_id);
	$site_name = get_option('business_name', get_bloginfo('name'));
	$separator = '-';

	// Common Yoast variables
	$replacements = [
		'%%title%%' => $post ? $post->post_title : '',
		'%%post_title%%' => $post ? $post->post_title : '',
		'%%sitename%%' => $site_name,
		'%%site_title%%' => $site_name,
		'%%sep%%' => $separator,
		'%%separator%%' => $separator,
		'%%page%%' => '',
		'%%primary_category%%' => '',
		'%%excerpt%%' => $post ? wp_trim_words($post->post_content, 10, '') : '',
	];

	$cleaned = str_replace(array_keys($replacements), array_values($replacements), $title);

	// Clean up multiple spaces and separators
	$cleaned = preg_replace('/\s+/', ' ', $cleaned);
	$cleaned = preg_replace('/\s*-\s*-\s*/', ' - ', $cleaned);
	$cleaned = trim($cleaned, ' -');

	return $cleaned;
}
