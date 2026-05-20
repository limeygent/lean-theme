<?php
/**
 * Default menu + News page setup on theme activation.
 *
 * On first activation: creates a "News" page, sets it as the WP Posts Page,
 * builds a "Main Menu" with Home (house icon) and News, and assigns it to
 * the 'primary' nav menu location.
 *
 * Tracked by the `lean_default_menu_created` option so it only runs once.
 */

if (!defined('ABSPATH')) exit;

add_action('after_switch_theme', 'lean_setup_default_menu');
function lean_setup_default_menu() {
	if (get_option('lean_default_menu_created')) {
		return;
	}

	// 1. News page (idempotent — reuse if it exists by slug)
	$news_page = get_page_by_path('news');
	if ($news_page) {
		$news_id = $news_page->ID;
	} else {
		$news_id = wp_insert_post([
			'post_title'   => 'News',
			'post_name'    => 'news',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		]);
	}

	if (!$news_id || is_wp_error($news_id)) {
		return;
	}

	// 2. Set as the WP "Posts Page" (only if not already configured)
	if (!get_option('page_for_posts')) {
		update_option('page_for_posts', $news_id);
	}

	// 3. Build the menu only if nothing is in the primary location yet
	$locations = get_nav_menu_locations();
	if (empty($locations['primary'])) {
		$menu_id = wp_create_nav_menu('Main Menu');

		if (!is_wp_error($menu_id)) {
			// Home — custom link with the `menu-icon-home` class (handled by the title filter below)
			wp_update_nav_menu_item($menu_id, 0, [
				'menu-item-title'   => 'Home',
				'menu-item-url'     => home_url('/'),
				'menu-item-status'  => 'publish',
				'menu-item-type'    => 'custom',
				'menu-item-classes' => 'menu-icon-home',
			]);

			// News — page link to the News page
			wp_update_nav_menu_item($menu_id, 0, [
				'menu-item-title'     => 'News',
				'menu-item-object-id' => $news_id,
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			]);

			$locations['primary'] = (int) $menu_id;
			set_theme_mod('nav_menu_locations', $locations);
		}
	}

	update_option('lean_default_menu_created', 1);
}

/**
 * Auto-create the three required legal pages (Terms of Use, Privacy Policy,
 * Accessibility). Content comes from inc/legal-templates/*.html with shortcode
 * placeholders for business name, address, email, etc. Pages are marked
 * noindex,nofollow via the theme's SEO meta keys.
 *
 * Idempotent — only creates pages whose slug doesn't already exist. Runs on
 * theme activation, so deleting any legal page and reactivating the theme
 * recreates the missing one. (Note: get_page_by_path matches trashed pages
 * too — empty Trash before reactivating if you actually want a recreate.)
 */
add_action('after_switch_theme', 'lean_setup_legal_pages');
function lean_setup_legal_pages() {
	$pages = [
		'terms-of-use' => [
			'title'    => 'Terms of Use',
			'template' => 'terms-of-use.html',
		],
		'privacy-policy' => [
			'title'    => 'Privacy Policy',
			'template' => 'privacy-policy.html',
		],
		'accessibility' => [
			'title'    => 'Accessibility Statement',
			'template' => 'accessibility.html',
		],
	];

	foreach ($pages as $slug => $config) {
		// Skip if a page already exists at this slug (any status, including trash)
		if (get_page_by_path($slug)) {
			continue;
		}

		$template_path = LEAN_THEME_DIR . '/inc/legal-templates/' . $config['template'];
		if (!file_exists($template_path)) {
			continue;
		}

		$content = file_get_contents($template_path);
		if ($content === false) {
			continue;
		}

		$page_id = wp_insert_post([
			'post_title'   => $config['title'],
			'post_name'    => $slug,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => $content,
		]);

		if (!is_wp_error($page_id) && $page_id) {
			// Mark noindex, nofollow via the theme's SEO meta keys
			update_post_meta($page_id, '_lean_meta_noindex', '1');
			update_post_meta($page_id, '_lean_meta_nofollow', '1');
		}
	}
}

/**
 * One-time content migrations for auto-created legal pages.
 *
 * Each migration is gated by its own option flag so it runs exactly once,
 * the next time wp-admin is loaded after the theme is updated. Migrations
 * here exist because the page content was already inserted into wp_posts
 * from an older template — updating the template files alone doesn't change
 * the stored post content.
 *
 * Add new migrations by incrementing the flag (lean_legal_migration_002, etc.)
 * and using the same idempotent pattern.
 */
add_action('admin_init', 'lean_legal_pages_migrations');
function lean_legal_pages_migrations() {
	// Migration 001: terms-of-use [business_state] → [business_state_name]
	// (full-name shortcode for legal prose, "TX" → "Texas")
	if (!get_option('lean_legal_migration_001')) {
		$page = get_page_by_path('terms-of-use');
		if ($page && strpos($page->post_content, '[business_state]') !== false) {
			wp_update_post([
				'ID'           => $page->ID,
				'post_content' => str_replace('[business_state]', '[business_state_name]', $page->post_content),
			]);
		}
		update_option('lean_legal_migration_001', 1);
	}

	// Migration 002: swap email shortcodes for the obfuscated version on legal pages
	// (scraper-resistant — "info (at) example.com" rather than a mailto link)
	if (!get_option('lean_legal_migration_002')) {
		foreach (['terms-of-use', 'privacy-policy', 'accessibility'] as $slug) {
			$page = get_page_by_path($slug);
			if (!$page) continue;
			$content = $page->post_content;
			$updated = str_replace(
				['[business_email_link]', '[business_email]'],
				['[business_email_obfuscated]', '[business_email_obfuscated]'],
				$content
			);
			if ($updated !== $content) {
				wp_update_post(['ID' => $page->ID, 'post_content' => $updated]);
			}
		}
		update_option('lean_legal_migration_002', 1);
	}

	// Migration 003: recreate any missing legal pages (e.g. when WP's default
	// Privacy Policy draft was deleted and our auto-create was skipped because
	// the slug already existed at the time).
	if (!get_option('lean_legal_migration_003')) {
		if (function_exists('lean_setup_legal_pages')) {
			lean_setup_legal_pages();
		}
		update_option('lean_legal_migration_003', 1);
	}

	// Migration 004: flip the outer wrapper so the .container is the outer
	// element and <section class="lean-section"> the inner one, matching the
	// theme's documented section pattern (css/lean-pages.css).
	if (!get_option('lean_legal_migration_004')) {
		foreach (['terms-of-use', 'privacy-policy', 'accessibility'] as $slug) {
			$page = get_page_by_path($slug);
			if (!$page) continue;
			$content = $page->post_content;
			$updated = str_replace(
				[
					"<section class=\"lean-section\">\n<div class=\"container\">",
					"</div>\n</section>",
				],
				[
					"<div class=\"container\">\n<section class=\"lean-section\">",
					"</section>\n</div>",
				],
				$content
			);
			if ($updated !== $content) {
				wp_update_post(['ID' => $page->ID, 'post_content' => $updated]);
			}
		}
		update_option('lean_legal_migration_004', 1);
	}
}

/**
 * Translate the `menu-icon-home` CSS class into a Bootstrap Icon
 * with screen-reader text. The title text is replaced by the icon.
 */
add_filter('nav_menu_item_title', 'lean_inject_menu_icons', 10, 4);
function lean_inject_menu_icons($title, $item, $args, $depth) {
	$classes = isset($item->classes) ? (array) $item->classes : [];
	if (in_array('menu-icon-home', $classes, true)) {
		return '<i class="bi bi-house-door" aria-hidden="true"></i><span class="visually-hidden">' . esc_html($title) . '</span>';
	}
	return $title;
}
