<?php
/**
 * Yoast SEO → Lean SEO Migration
 *
 * Code Snippet: Run on Admin only
 * ONE-TIME USE: Disable after migration is complete
 *
 * Access: Tools > Yoast → Lean SEO
 *
 * Migrates:
 * - _yoast_wpseo_title        → _lean_meta_title
 * - _yoast_wpseo_metadesc     → _lean_meta_description
 * - _yoast_wpseo_focuskw      → _lean_meta_keywords
 * - _yoast_wpseo_meta-robots-noindex  → _lean_meta_noindex
 * - _yoast_wpseo_meta-robots-nofollow → _lean_meta_nofollow
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// ──────────────────────────────────────────────────────────────────────────────
// ADMIN PAGE
// ──────────────────────────────────────────────────────────────────────────────

add_action('admin_menu', function() {
	add_management_page(
		'Yoast → Lean SEO Migration',
		'Yoast → Lean SEO',
		'manage_options',
		'lean-seo-migrate',
		'lean_seo_migration_page'
	);
});

function lean_seo_migration_page() {
	if (!current_user_can('manage_options')) {
		wp_die('Unauthorized');
	}

	$action = isset($_POST['lean_migrate_action']) ? $_POST['lean_migrate_action'] : '';
	$nonce_valid = isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'lean_seo_migrate');

	echo '<div class="wrap">';
	echo '<h1>Yoast SEO → Lean SEO Migration</h1>';

	if ($action === 'preview' && $nonce_valid) {
		lean_seo_migration_preview();
	} elseif ($action === 'migrate' && $nonce_valid) {
		lean_seo_migration_execute();
	} else {
		lean_seo_migration_form();
	}

	echo '</div>';
}

// ──────────────────────────────────────────────────────────────────────────────
// FORM
// ──────────────────────────────────────────────────────────────────────────────

function lean_seo_migration_form() {
	global $wpdb;

	// Stats
	$yoast_title = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_yoast_wpseo_title' AND meta_value != ''");
	$yoast_desc = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_yoast_wpseo_metadesc' AND meta_value != ''");
	$yoast_kw = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_yoast_wpseo_focuskw' AND meta_value != ''");
	$yoast_noindex = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_yoast_wpseo_meta-robots-noindex' AND meta_value = '1'");
	$lean_exists = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_lean_meta_title' AND meta_value != ''");

	?>
	<div class="card" style="max-width: 700px; padding: 20px;">
		<h2>Yoast Data Found</h2>
		<table class="widefat striped" style="max-width: 300px;">
			<tr><td>SEO Titles</td><td><strong><?php echo intval($yoast_title); ?></strong></td></tr>
			<tr><td>Meta Descriptions</td><td><strong><?php echo intval($yoast_desc); ?></strong></td></tr>
			<tr><td>Focus Keywords</td><td><strong><?php echo intval($yoast_kw); ?></strong></td></tr>
			<tr><td>Noindex Pages</td><td><strong><?php echo intval($yoast_noindex); ?></strong></td></tr>
		</table>

		<?php if ($lean_exists > 0): ?>
		<div class="notice notice-warning" style="margin: 15px 0;">
			<p><strong>Note:</strong> <?php echo intval($lean_exists); ?> posts already have Lean SEO data. These will be <strong>skipped</strong>.</p>
		</div>
		<?php endif; ?>

		<h2 style="margin-top: 25px;">Migration Mapping</h2>
		<table class="widefat striped" style="max-width: 500px;">
			<tr><td><code>_yoast_wpseo_title</code></td><td>→</td><td><code>_lean_meta_title</code></td></tr>
			<tr><td><code>_yoast_wpseo_metadesc</code></td><td>→</td><td><code>_lean_meta_description</code></td></tr>
			<tr><td><code>_yoast_wpseo_focuskw</code></td><td>→</td><td><code>_lean_meta_keywords</code></td></tr>
			<tr><td><code>_yoast_wpseo_meta-robots-noindex</code></td><td>→</td><td><code>_lean_meta_noindex</code></td></tr>
			<tr><td><code>_yoast_wpseo_meta-robots-nofollow</code></td><td>→</td><td><code>_lean_meta_nofollow</code></td></tr>
		</table>

		<h2 style="margin-top: 25px;">Actions</h2>
		<form method="post" style="display: inline-block; margin-right: 10px;">
			<?php wp_nonce_field('lean_seo_migrate'); ?>
			<input type="hidden" name="lean_migrate_action" value="preview">
			<button type="submit" class="button">Preview Migration</button>
		</form>
		<form method="post" style="display: inline-block;">
			<?php wp_nonce_field('lean_seo_migrate'); ?>
			<input type="hidden" name="lean_migrate_action" value="migrate">
			<button type="submit" class="button button-primary" onclick="return confirm('Run migration? This copies Yoast data to Lean SEO fields.');">Run Migration</button>
		</form>

		<p class="description" style="margin-top: 15px;">
			Migration <strong>copies</strong> data (doesn't delete Yoast data). Safe to run.
		</p>
	</div>
	<?php
}

// ──────────────────────────────────────────────────────────────────────────────
// PREVIEW
// ──────────────────────────────────────────────────────────────────────────────

function lean_seo_migration_preview() {
	$posts = lean_get_yoast_posts();

	if (empty($posts)) {
		echo '<div class="notice notice-warning"><p>No Yoast data found to migrate.</p></div>';
		echo '<p><a href="' . admin_url('tools.php?page=lean-seo-migrate') . '" class="button">← Back</a></p>';
		return;
	}

	?>
	<div class="card" style="max-width: 1100px; padding: 20px;">
		<h2>Preview: <?php echo count($posts); ?> posts to migrate</h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th>ID</th>
					<th>Title</th>
					<th>Type</th>
					<th>Yoast Title</th>
					<th>Description</th>
					<th>Status</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($posts as $p): ?>
				<tr>
					<td><?php echo $p['id']; ?></td>
					<td><a href="<?php echo get_edit_post_link($p['id']); ?>" target="_blank"><?php echo esc_html(substr($p['post_title'], 0, 30)); ?></a></td>
					<td><?php echo $p['post_type']; ?></td>
					<td><?php echo esc_html(substr($p['yoast_title'], 0, 35)); ?><?php echo strlen($p['yoast_title']) > 35 ? '...' : ''; ?></td>
					<td><?php echo esc_html(substr($p['yoast_desc'], 0, 40)); ?><?php echo strlen($p['yoast_desc']) > 40 ? '...' : ''; ?></td>
					<td>
						<?php if ($p['has_lean']): ?>
							<span style="color:orange;">Skip (has Lean data)</span>
						<?php else: ?>
							<span style="color:green;">Will migrate</span>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p style="margin-top: 15px;">
			<a href="<?php echo admin_url('tools.php?page=lean-seo-migrate'); ?>" class="button">← Back</a>
			<form method="post" style="display: inline-block; margin-left: 10px;">
				<?php wp_nonce_field('lean_seo_migrate'); ?>
				<input type="hidden" name="lean_migrate_action" value="migrate">
				<button type="submit" class="button button-primary">Run Migration</button>
			</form>
		</p>
	</div>
	<?php
}

// ──────────────────────────────────────────────────────────────────────────────
// EXECUTE
// ──────────────────────────────────────────────────────────────────────────────

function lean_seo_migration_execute() {
	$posts = lean_get_yoast_posts();

	if (empty($posts)) {
		echo '<div class="notice notice-warning"><p>No Yoast data found.</p></div>';
		return;
	}

	$migrated = 0;
	$skipped = 0;

	foreach ($posts as $p) {
		if ($p['has_lean']) {
			$skipped++;
			continue;
		}

		$post_id = $p['id'];

		// Migrate title (clean Yoast variables)
		if (!empty($p['yoast_title'])) {
			$clean_title = lean_clean_yoast_title($p['yoast_title'], $post_id);
			update_post_meta($post_id, '_lean_meta_title', $clean_title);
		}

		// Migrate description
		if (!empty($p['yoast_desc'])) {
			update_post_meta($post_id, '_lean_meta_description', $p['yoast_desc']);
		}

		// Migrate keywords
		if (!empty($p['yoast_kw'])) {
			update_post_meta($post_id, '_lean_meta_keywords', $p['yoast_kw']);
		}

		// Migrate noindex/nofollow
		if ($p['yoast_noindex']) {
			update_post_meta($post_id, '_lean_meta_noindex', '1');
		}
		if ($p['yoast_nofollow']) {
			update_post_meta($post_id, '_lean_meta_nofollow', '1');
		}

		$migrated++;
	}

	?>
	<div class="notice notice-success">
		<p><strong>Migration complete!</strong> Migrated: <?php echo $migrated; ?> | Skipped: <?php echo $skipped; ?></p>
	</div>

	<div class="card" style="max-width: 600px; padding: 20px;">
		<h2>Next Steps</h2>
		<ol>
			<li>Verify SEO data on a few pages</li>
			<li>Deactivate Yoast SEO plugin</li>
			<li><strong>Disable this Code Snippet</strong> (one-time use)</li>
		</ol>
		<p><a href="<?php echo admin_url('tools.php?page=lean-seo-migrate'); ?>" class="button">← Back</a></p>
	</div>
	<?php
}

// ──────────────────────────────────────────────────────────────────────────────
// HELPERS
// ──────────────────────────────────────────────────────────────────────────────

function lean_get_yoast_posts() {
	global $wpdb;

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
		$id = $post->ID;

		$yoast_title = get_post_meta($id, '_yoast_wpseo_title', true);
		$yoast_desc = get_post_meta($id, '_yoast_wpseo_metadesc', true);
		$yoast_kw = get_post_meta($id, '_yoast_wpseo_focuskw', true);
		$yoast_noindex = get_post_meta($id, '_yoast_wpseo_meta-robots-noindex', true) === '1';
		$yoast_nofollow = get_post_meta($id, '_yoast_wpseo_meta-robots-nofollow', true) === '1';

		// Check for existing Lean data
		$lean_title = get_post_meta($id, '_lean_meta_title', true);
		$lean_desc = get_post_meta($id, '_lean_meta_description', true);
		$has_lean = !empty($lean_title) || !empty($lean_desc);

		// Skip if no meaningful Yoast data
		if (empty($yoast_title) && empty($yoast_desc) && empty($yoast_kw) && !$yoast_noindex && !$yoast_nofollow) {
			continue;
		}

		$result[] = [
			'id' => $id,
			'post_title' => $post->post_title,
			'post_type' => $post->post_type,
			'yoast_title' => $yoast_title,
			'yoast_desc' => $yoast_desc,
			'yoast_kw' => $yoast_kw,
			'yoast_noindex' => $yoast_noindex,
			'yoast_nofollow' => $yoast_nofollow,
			'has_lean' => $has_lean,
		];
	}

	return $result;
}

function lean_clean_yoast_title($title, $post_id) {
	$post = get_post($post_id);
	$site_name = get_option('business_name', get_bloginfo('name'));

	$replacements = [
		'%%title%%' => $post ? $post->post_title : '',
		'%%post_title%%' => $post ? $post->post_title : '',
		'%%sitename%%' => $site_name,
		'%%site_title%%' => $site_name,
		'%%sep%%' => '-',
		'%%separator%%' => '-',
		'%%page%%' => '',
		'%%primary_category%%' => '',
	];

	$clean = str_replace(array_keys($replacements), array_values($replacements), $title);
	$clean = preg_replace('/\s+/', ' ', $clean);
	$clean = preg_replace('/\s*-\s*-\s*/', ' - ', $clean);
	$clean = trim($clean, ' -');

	return $clean;
}
