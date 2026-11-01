<?php
/**
 * Custom Reviews (testimonials) — CUSTOM USE ONLY, FOR NON-LEAN-THEME SITES
 * ---------------------------------------------------------------------------
 * DO NOT install this on a site running the Lean theme, in standalone or
 * embedded mode. Lean + NerdPress SEO already provide everything here. If Lean
 * is detected, this snippet disables itself and shows an admin notice.
 *
 * Not loaded by the theme, and not part of any release. It is a hand-copied
 * port of inc/shortcodes/testimonials.php plus the NerdPress testimonial files,
 * so fixes made there do NOT reach sites running this copy.
 *
 * INSTALL
 *   Code Snippets plugin: paste as a PHP snippet, "Run everywhere", without the
 *   opening <?php tag. Or save as wp-content/mu-plugins/custom-reviews.php.
 *   Then fill in nlr_business() below (used for the review schema), and check
 *   nlr_site_terms(): it holds ONE CLIENT'S terms and creates them on this site.
 *
 * WHAT IT ADDS
 *   - `testimonials` post type, used as an internal database: no URLs, no archive,
 *     not in search or sitemaps, and its REST routes need a logged-in editor
 *   - `testimonial_category` taxonomy (the service)
 *   - `review_attribute` taxonomy (situation, what staff did, manner, outcome).
 *     This one exists only in this snippet, not in Lean or NerdPress yet.
 *   - Review Link, Rating and Location fields on the testimonial editor
 *   - The shortcodes below, with their own CSS (no Bootstrap needed)
 *   Post type, taxonomy and meta keys match Lean/NerdPress, so the agency
 *   dashboard's push (agency-dashboard/docs/testimonials-push.md) works
 *   unchanged. Terms in nlr_site_terms() are created once, on first load.
 *
 * SHORTCODES
 *   [testimonials num_reviews="6"]
 *   [testimonials category="hvac,plumbing"]      any of several term slugs
 *   [testimonials category="hvac" fallback="reviews"]  top up from this term
 *                                                (default "brand"; "" = none)
 *   [testimonials category="hvac" fallback_exclusive="0"]  allow top-ups that
 *                                                also carry other categories
 *   [testimonials location="oak-cliff"]       one location, top-ups included
 *   [testimonials attribute="expedited,first-time"]   any of these attributes
 *   [testimonials attribute="expedited,first-time" match="all"]  all of them
 *                                                An attribute row never tops up:
 *                                                a brand review would not back up
 *                                                the claim. A short row stays short,
 *                                                and an empty one prints nothing.
 *   [testimonials text_only="1"]                 skip star-only reviews
 *   [testimonials max_chars="180"]               shorten long quotes on the card
 *   [gbp_review_count]    → 153   total from the dashboard's `gbp-stats` post
 *   [gbp_review_average]  → 5.0   prints nothing without that post
 *
 * BEFORE INSTALLING, CHECK THE SITE'S THEME
 *   - An existing [testimonials] shortcode is REPLACED by this one.
 *   - An existing `testimonials` post type is kept, and gains the fields and
 *     taxonomy. Any query of its own that includes drafts will show the
 *     `gbp-stats` post as a review.
 *
 * CSS is printed in <head> on every page (under 1 KB). To style reviews from
 * the site's theme instead, delete the wp_head line in nlr_bootstrap().
 * ---------------------------------------------------------------------------
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * EDIT PER SITE: the business named as `itemReviewed` in the review schema.
 * Leave a value '' to omit it. Non-US phone numbers are output as typed.
 */
function nlr_business() {
	return array(
		'name'    => get_bloginfo('name'),
		'url'     => home_url('/'),
		'phone'   => '',
		'address' => '',
		'city'    => '',
		'state'   => '',
		'zip'     => '',
		'cid'     => '', // Google Maps CID
	);
}

/**
 * EDIT PER SITE: the terms to create. Currently SOUTHERN BAIL BONDS.
 *
 * THIS IS THE ONLY LIST. The agency dashboard reads these terms from the site and
 * files reviews by them, so a change here changes how reviews get tagged.
 *
 * After changing this list, raise NLR_TERMS_VERSION. That adds missing terms and
 * overwrites the DESCRIPTIONS of the listed attributes with the tests below. It
 * never renames, moves or deletes a term.
 */
define('NLR_TERMS_VERSION', '3');

function nlr_site_terms() {
	return array(
		'testimonial_category' => array(
			'brand' => 'Brand',
		),
		// axis slug => array(axis name, array(value slug => array(name, test)))
		// The test goes in the term's description, and the dashboard reads it as
		// the question it asks about each review.
		'review_attribute' => array(
			'situations' => array('Situations', array(
				'first-time' => array('First time', 'The reviewer, or the person they helped, had never posted a bond or been through this before. Phrases like \'my first time\' or \'someone who has never done this\' count.'),
				'for-family-member' => array('For family', 'The reviewer was acting for someone else in jail: a son, daughter, spouse, parent, sibling, partner, friend or other loved one. \'Bailing someone out\' counts.'),
				'for-self' => array('For self', 'The reviewer is the person who was arrested and bailed out, and the review shows it, such as \'I was out\' or \'my bond\'. If it mentions a relative or friend in jail, or does not make this clear, the answer is no.'),
				'remote-caller' => array('Remote caller', 'The reviewer says they handled it from home, or from another city or state, without coming in.'),
				'after-hours' => array('After hours', 'The arrest or the help happened at night, on a weekend, or on a holiday. A holiday or night event counts even if 24/7 is never said.'),
				'others-said-no' => array('Others said no', 'The review mentions a different bail company by saying that company turned the reviewer away, said no, charged them without helping, or gave them the runaround first. Merely praising Southern Bail Bonds or recommending it over others is no.'),
				'money-tight' => array('Money tight', 'The reviewer states money was tight, they could not afford the full amount, or they were worried about the cost.'),
				'distressed' => array('Distressed', 'The reviewer states they were stressed, scared, nervous, emotional, or in a hard or difficult time, or says this about the situation.'),
			)),
			'actions' => array('Actions', array(
				'explained' => array('Explained', 'Staff explained the process, steps, costs, fees or paperwork, or guided the reviewer through it.'),
				'kept-informed' => array('Kept informed', 'Staff gave updates during the wait: callbacks, check-ins, status calls, or communicating often.'),
				'answered-promptly' => array('Answered promptly', 'Staff answered the phone or responded right away, or called back quickly.'),
				'remote-process' => array('Online / phone', 'The bond was handled by phone, online, or remote paperwork. Any mention of doing it over the phone, by email or online counts.'),
				'found-a-way' => array('Found a way', 'Staff solved a hard case, worked with an unusual situation, or went above and beyond, including helping outside normal hours.'),
				'expedited' => array('Fast', 'The process, the staff member\'s work or the release was fast. Any speed word counts: fast, quick, moved quickly, prompt, same day, within an hour, no waiting.'),
			)),
			'manner' => array('Manner', array(
				'kind-patient' => array('Kind / patient', 'Staff were kind, patient, friendly, caring, understanding, or helpful in tone.'),
				'professional' => array('Professional / respectful', 'Staff were professional, knowledgeable, efficient, respectful, polite, courteous or non-judgmental, knew the process, or treated the reviewer with dignity.'),
			)),
			'outcomes' => array('Outcomes', array(
				'home' => array('Got home', 'The person got out of jail or came home.'),
				'stress-relieved' => array('Stress relieved', 'The reviewer felt calmer, relieved or at ease, or describes the process as easy, smooth or painless.'),
				'recommend' => array('Recommends', 'The reviewer explicitly recommends the business, would refer others, or would use them again.'),
			)),
		),
	);
}

// Waits for after_setup_theme because plugins and snippets load BEFORE the theme:
// LEAN_THEME_DIR isn't defined yet when this file runs.
add_action('after_setup_theme', 'nlr_bootstrap', 0);

function nlr_bootstrap() {
	if (defined('LEAN_THEME_DIR')) {
		add_action('admin_notices', 'nlr_lean_detected_notice');
		return;
	}

	// Late enough to see a type registered by the theme or by NerdPress SEO, and
	// to replace a theme's own [testimonials].
	add_action('init', 'nlr_register_content', 20);
	add_action('init', 'nlr_seed_terms', 21);
	// Filters, so the rules also hold for a type or taxonomy registered elsewhere.
	add_filter('register_post_type_args', 'nlr_private_post_type_args', 20, 2);
	add_filter('register_taxonomy_args', 'nlr_private_taxonomy_args', 20, 2);
	add_filter('rest_pre_dispatch', 'nlr_guard_rest', 10, 3);
	add_filter('wpseo_sitemap_exclude_post_type', 'nlr_yoast_exclude_type', 10, 2);
	add_filter('wpseo_sitemap_exclude_taxonomy', 'nlr_yoast_exclude_taxonomy', 10, 2);
	add_filter('wp_robots', 'nlr_noindex');
	add_action('init', 'nlr_register_shortcodes', 20);
	add_action('wp_head', 'nlr_print_styles', 20);
	add_action('admin_notices', 'nlr_usage_notice');

	// NerdPress SEO on a non-Lean site already provides the editor fields.
	if (!function_exists('nerdpress_testimonial_meta_box')) {
		add_action('add_meta_boxes', 'nlr_add_meta_box');
		add_action('save_post_testimonials', 'nlr_save_meta');
	}
}

function nlr_lean_detected_notice() {
	if (current_user_can('manage_options')) {
		echo '<div class="notice notice-error"><p><strong>Custom Reviews snippet is disabled:</strong> it is for non-Lean-theme sites only, and this site runs the Lean theme. Remove the snippet.</p></div>';
	}
}

function nlr_usage_notice() {
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if ($screen && 'testimonials' === $screen->post_type) {
		echo '<div class="notice notice-info"><p><strong>Testimonials Shortcode:</strong> Use <code>[testimonials num_reviews="6"]</code> to display testimonials, <code>[testimonials category="slug"]</code> to show only one category, or <code>[testimonials location="slug"]</code> to show only one location.</p></div>';
	}
}

/* ---- Keeping it private -------------------------------------------------- */

function nlr_private_post_type_args($args, $post_type) {
	if ('testimonials' !== $post_type) {
		return $args;
	}

	return array_merge($args, array(
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'has_archive'         => false,
		'rewrite'             => false,
		'query_var'           => false,
		'show_in_nav_menus'   => false,
		'show_ui'             => true,
		'show_in_rest'        => true, // the dashboard writes through REST; nlr_guard_rest locks it
	));
}

function nlr_private_taxonomy_args($args, $taxonomy) {
	if (!in_array($taxonomy, array('testimonial_category', 'review_attribute'), true)) {
		return $args;
	}

	return array_merge($args, array(
		'public'             => false,
		'publicly_queryable' => false,
		'rewrite'            => false,
		'query_var'          => false,
		'show_in_nav_menus'  => false,
		'show_tagcloud'      => false,
		'show_ui'            => true,
		'show_in_rest'       => true,
	));
}

/**
 * REST routes are readable by anyone by default, even on a non-public type, which
 * would publish the whole review table as JSON. Shortcodes don't use REST, so
 * pages are unaffected.
 */
function nlr_guard_rest($result, $server, $request) {
	if (null !== $result) {
		return $result;
	}
	if (preg_match('#^/wp/v2/(testimonials|testimonial_category|review_attribute)(/|$)#', $request->get_route())
		&& !current_user_can('edit_posts')) {
		return new WP_Error('nlr_private', 'Not available.', array('status' => 401));
	}

	return $result;
}

function nlr_yoast_exclude_type($excluded, $post_type) {
	return 'testimonials' === $post_type ? true : $excluded;
}

function nlr_yoast_exclude_taxonomy($excluded, $taxonomy) {
	return in_array($taxonomy, array('testimonial_category', 'review_attribute'), true) ? true : $excluded;
}

/** Backstop only: with no URLs, nothing should ever reach this. */
function nlr_noindex($robots) {
	if (is_singular('testimonials')) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}

	return $robots;
}

/* ---- Post type, taxonomy, meta ------------------------------------------ */

function nlr_register_content() {
	if (post_type_exists('testimonials')) {
		// The push can't write meta over REST without this support.
		add_post_type_support('testimonials', 'custom-fields');
	} else {
		register_post_type('testimonials', array(
			'labels'       => array(
				'name'          => 'Testimonials',
				'singular_name' => 'Testimonial',
				'add_new_item'  => 'Add New Testimonial',
				'edit_item'     => 'Edit Testimonial',
				'search_items'  => 'Search Testimonials',
				'not_found'     => 'No testimonials found',
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => true,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-format-quote',
			'supports'     => array('title', 'editor', 'custom-fields'),
			'rewrite'      => false,
		));
	}

	if (!taxonomy_exists('testimonial_category')) {
		register_taxonomy('testimonial_category', array('testimonials'), array(
			'labels'            => array(
				'name'          => 'Testimonial Categories',
				'singular_name' => 'Testimonial Category',
				'menu_name'     => 'Categories',
				'all_items'     => 'All Categories',
				'edit_item'     => 'Edit Category',
				'update_item'   => 'Update Category',
				'add_new_item'  => 'Add New Category',
				'new_item_name' => 'New Category Name',
				'search_items'  => 'Search Categories',
				'not_found'     => 'No categories found',
			),
			'public'            => false,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'hierarchical'      => true,
			'rewrite'           => false,
		));
	}

	// Separate from testimonial_category on purpose: the top-up pool only takes
	// reviews with no other category, and nearly every review has attributes.
	if (!taxonomy_exists('review_attribute')) {
		register_taxonomy('review_attribute', array('testimonials'), array(
			'labels'            => array(
				'name'          => 'Review Attributes',
				'singular_name' => 'Review Attribute',
				'menu_name'     => 'Attributes',
				'all_items'     => 'All Attributes',
				'edit_item'     => 'Edit Attribute',
				'update_item'   => 'Update Attribute',
				'add_new_item'  => 'Add New Attribute',
				'new_item_name' => 'New Attribute Name',
				'parent_item'   => 'Parent Attribute',
				'search_items'  => 'Search Attributes',
				'not_found'     => 'No attributes found',
			),
			'public'            => false,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'hierarchical'      => true,
			'rewrite'           => false,
		));
	}

	if (function_exists('nerdpress_register_testimonial_meta')) {
		return; // NerdPress SEO already registered the same keys.
	}

	$can_edit = function () {
		return current_user_can('edit_posts');
	};

	register_post_meta('testimonials', 'review_rating', array(
		'single'            => true,
		'type'              => 'string', // stored as '1'..'5'
		'show_in_rest'      => true,
		'sanitize_callback' => function ($value) {
			return (string) max(1, min(5, (int) $value));
		},
		'auth_callback'     => $can_edit,
	));
	register_post_meta('testimonials', 'review_link', array(
		'single'            => true,
		'type'              => 'string',
		'show_in_rest'      => true,
		'sanitize_callback' => 'esc_url_raw',
		'auth_callback'     => $can_edit,
	));
	register_post_meta('testimonials', 'review_location', array(
		'single'            => true,
		'type'              => 'string',
		'show_in_rest'      => true,
		'sanitize_callback' => 'sanitize_title',
		'auth_callback'     => $can_edit,
	));
}

/** Create what nlr_site_terms() lists. Marked done only if everything succeeded. */
function nlr_seed_terms() {
	if (NLR_TERMS_VERSION === get_option('nlr_terms_version')) {
		return;
	}

	$ok    = true;
	$terms = nlr_site_terms();

	foreach ($terms['testimonial_category'] as $slug => $name) {
		$ok = (null !== nlr_ensure_term('testimonial_category', $slug, $name, 0)) && $ok;
	}
	foreach ($terms['review_attribute'] as $parent_slug => $group) {
		$parent_id = nlr_ensure_term('review_attribute', $parent_slug, $group[0], 0);
		if (null === $parent_id) {
			$ok = false;
			continue;
		}
		foreach ($group[1] as $slug => $term) {
			$ok = (null !== nlr_ensure_term('review_attribute', $slug, $term[0], $parent_id, $term[1])) && $ok;
		}
	}

	if ($ok) {
		update_option('nlr_terms_version', NLR_TERMS_VERSION);
	}
}

/**
 * The term's id, creating it if no term has this slug. Null if that failed. An
 * existing term only ever has its description brought into line, and only when a
 * description is given.
 */
function nlr_ensure_term($taxonomy, $slug, $name, $parent_id, $description = null) {
	$existing = get_term_by('slug', $slug, $taxonomy);
	if ($existing) {
		if (null !== $description && $existing->description !== $description) {
			$updated = wp_update_term($existing->term_id, $taxonomy, array('description' => $description));
			if (is_wp_error($updated)) {
				return null;
			}
		}

		return (int) $existing->term_id;
	}

	$created = wp_insert_term($name, $taxonomy, array(
		'slug'        => $slug,
		'parent'      => $parent_id,
		'description' => null === $description ? '' : $description,
	));

	return is_wp_error($created) ? null : (int) $created['term_id'];
}

function nlr_add_meta_box() {
	add_meta_box('nlr_testimonial_details', 'Testimonial Details', 'nlr_render_meta_box', 'testimonials', 'normal', 'high');
}

function nlr_render_meta_box($post) {
	wp_nonce_field('nlr_save_testimonial', 'nlr_testimonial_nonce');

	$link     = get_post_meta($post->ID, 'review_link', true);
	$rating   = get_post_meta($post->ID, 'review_rating', true);
	$location = get_post_meta($post->ID, 'review_location', true);
	if ('' === $rating) {
		$rating = '5';
	}
	?>
	<p>
		<label for="nlr_review_link"><strong>Review Link</strong></label><br>
		<input type="url" id="nlr_review_link" name="nlr_review_link"
			   value="<?php echo esc_attr($link); ?>" class="widefat" placeholder="https://...">
		<span class="description">Optional — link to the full review (e.g. Google). Used as the review source and author link.</span>
	</p>
	<p>
		<label for="nlr_review_rating"><strong>Rating</strong></label><br>
		<select id="nlr_review_rating" name="nlr_review_rating">
			<?php for ($i = 5; $i >= 1; $i--) : ?>
				<option value="<?php echo esc_attr($i); ?>" <?php selected((string) $rating, (string) $i); ?>>
					<?php echo esc_html($i); ?> star<?php echo 1 === $i ? '' : 's'; ?>
				</option>
			<?php endfor; ?>
		</select>
	</p>
	<p>
		<label for="nlr_review_location"><strong>Location</strong></label><br>
		<input type="text" id="nlr_review_location" name="nlr_review_location"
			   value="<?php echo esc_attr($location); ?>" class="regular-text" placeholder="oak-cliff">
		<span class="description">Optional — the location slug this review belongs to. Filter by it with <code>[testimonials location="oak-cliff"]</code>.</span>
	</p>
	<?php
}

function nlr_save_meta($post_id) {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	if (wp_is_post_revision($post_id)) {
		return;
	}
	if (!isset($_POST['nlr_testimonial_nonce']) || !wp_verify_nonce($_POST['nlr_testimonial_nonce'], 'nlr_save_testimonial')) {
		return;
	}
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	if (isset($_POST['nlr_review_link'])) {
		update_post_meta($post_id, 'review_link', esc_url_raw(wp_unslash($_POST['nlr_review_link'])));
	}
	if (isset($_POST['nlr_review_rating'])) {
		update_post_meta($post_id, 'review_rating', (string) max(1, min(5, (int) $_POST['nlr_review_rating'])));
	}
	if (isset($_POST['nlr_review_location'])) {
		$location = sanitize_title(wp_unslash($_POST['nlr_review_location']));
		if ('' === $location) {
			// Blank means no location, not a location called "".
			delete_post_meta($post_id, 'review_location');
		} else {
			update_post_meta($post_id, 'review_location', $location);
		}
	}
}

/* ---- Styles -------------------------------------------------------------- */

function nlr_print_styles() {
	echo '<style id="nlr-reviews-css">'
		. '.nlr-reviews{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(280px,100%),1fr));gap:1.5rem;margin:1.5rem 0}'
		. '.nlr-card{background:#fff;color:#333;border:1px solid rgba(0,0,0,.1);border-radius:12px;padding:1.5rem;box-shadow:0 2px 10px rgba(0,0,0,.06)}'
		. '.nlr-stars{color:#a85a00;font-size:1.05rem;letter-spacing:.12em;margin-bottom:.5rem}'
		. '.nlr-card p{margin:0 0 1rem;line-height:1.6}'
		. '.nlr-card .nlr-attribution{margin:0;color:#595959;font-size:.875rem}'
		. '.nlr-attribution a{color:inherit;text-decoration:underline}'
		. '</style>';
}

/* ---- Shortcodes ---------------------------------------------------------- */

function nlr_register_shortcodes() {
	add_shortcode('testimonials', 'nlr_testimonials_shortcode');

	add_shortcode('gbp_review_count', function () {
		$stats = nlr_gbp_review_stats();
		$count = isset($stats['total']['count']) ? $stats['total']['count'] : null;

		return is_numeric($count) ? number_format_i18n((int) $count) : '';
	});

	add_shortcode('gbp_review_average', function () {
		$stats   = nlr_gbp_review_stats();
		$average = isset($stats['total']['average']) ? $stats['total']['average'] : null;

		return is_numeric($average) ? number_format_i18n((float) $average, 1) : '';
	});
}

function nlr_testimonials_shortcode($atts) {
	$atts = shortcode_atts(array(
		'num_reviews'        => 4,
		'category'           => '',
		'fallback'           => 'brand',
		'fallback_exclusive' => true,
		'location'           => '',
		'attribute'          => '',
		'match'              => 'any',
		'text_only'          => false,
		'max_chars'          => 0,
	), $atts, 'testimonials');

	$num_reviews = absint($atts['num_reviews']);
	if ($num_reviews < 1) {
		$num_reviews = 4;
	}

	$text_only = filter_var($atts['text_only'], FILTER_VALIDATE_BOOLEAN);
	$exclusive = filter_var($atts['fallback_exclusive'], FILTER_VALIDATE_BOOLEAN);
	$max_chars = absint($atts['max_chars']);

	$primary_slugs  = array_filter(array_map('sanitize_title', explode(',', $atts['category'])));
	$fallback_slugs = array_filter(array_map('sanitize_title', explode(',', $atts['fallback'])));
	$fallback_slugs = array_diff($fallback_slugs, $primary_slugs);

	// Location narrows every query, top-ups included.
	$location_slugs = array_values(array_filter(array_map('sanitize_title', explode(',', $atts['location']))));
	$location_args  = empty($location_slugs) ? array() : array(
		'meta_query' => array(array(
			'key'     => 'review_location',
			'value'   => $location_slugs,
			'compare' => 'IN',
		)),
	);

	$attribute_slugs  = array_values(array_filter(array_map('sanitize_title', explode(',', $atts['attribute']))));
	$attribute_clause = empty($attribute_slugs) ? null : array(
		'taxonomy'         => 'review_attribute',
		'field'            => 'slug',
		'terms'            => $attribute_slugs,
		'operator'         => 'all' === strtolower(trim($atts['match'])) ? 'AND' : 'IN',
		'include_children' => false,
	);

	if (empty($primary_slugs)) {
		$args = array(
			'orderby' => 'date',
			'order'   => 'DESC',
		);
		if ($attribute_clause) {
			$args['tax_query'] = array($attribute_clause);
		}
		$ids = nlr_collect_ids(array_merge($args, $location_args), $num_reviews, $text_only);
	} else {
		// Primary category, newest first.
		$primary_query = array(array(
			'taxonomy' => 'testimonial_category',
			'field'    => 'slug',
			'terms'    => $primary_slugs,
		));
		if ($attribute_clause) {
			$primary_query['relation'] = 'AND';
			$primary_query[] = $attribute_clause;
		}
		$ids = nlr_collect_ids(array_merge(array(
			'orderby'   => 'date',
			'order'     => 'DESC',
			'tax_query' => $primary_query,
		), $location_args), $num_reviews, $text_only);

		// If short, top up at random from the fallback category.
		$needed = $num_reviews - count($ids);
		if ($needed > 0 && !empty($fallback_slugs) && !$attribute_clause) {
			$tax_query = array(array(
				'taxonomy' => 'testimonial_category',
				'field'    => 'slug',
				'terms'    => $fallback_slugs,
			));

			// Exclusive: a review also tagged with another service is about that
			// service, and reads as off-topic here.
			$off_topic = $exclusive ? nlr_other_term_slugs(array_merge($fallback_slugs, $primary_slugs)) : array();
			if (!empty($off_topic)) {
				$tax_query['relation'] = 'AND';
				$tax_query[] = array(
					'taxonomy' => 'testimonial_category',
					'field'    => 'slug',
					'terms'    => $off_topic,
					'operator' => 'NOT IN',
				);
			}

			$ids = array_merge($ids, nlr_collect_ids(array_merge(array(
				'orderby'      => 'rand',
				'post__not_in' => $ids,
				'tax_query'    => $tax_query,
			), $location_args), $needed, $text_only));
		}
	}

	if (empty($ids)) {
		return $attribute_clause ? '' : '<p>No testimonials found.</p>';
	}

	$query = new WP_Query(array(
		'post_type'      => 'testimonials',
		'post_status'    => 'publish',
		'post__in'       => $ids,
		'orderby'        => 'post__in',
		'posts_per_page' => count($ids),
	));

	if (!$query->have_posts()) {
		return '<p>No testimonials found.</p>';
	}

	$item_reviewed = nlr_item_reviewed_html();

	ob_start();

	echo '<div class="nlr-reviews" role="region" aria-label="Customer testimonials">';
	while ($query->have_posts()) {
		$query->the_post();
		$post_id     = get_the_ID();
		$name        = get_the_title();
		$link        = get_post_meta($post_id, 'review_link', true);
		$rating      = get_post_meta($post_id, 'review_rating', true) ?: 5;
		$rating      = max(1, min(5, (int) $rating));
		$attribution = array_filter(array(nlr_source_label($link), get_the_date('F Y')));

		echo '<div class="nlr-card" itemscope itemtype="https://schema.org/Review">';

		// Schema only, no visual output.
		if (!empty($link)) {
			echo '<meta itemprop="url" content="' . esc_url($link) . '">';
		}
		echo '<meta itemprop="datePublished" content="' . esc_attr(get_the_date('c')) . '">';
		echo $item_reviewed;
		echo '<div itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">';
		echo '<meta itemprop="ratingValue" content="' . esc_attr($rating) . '">';
		echo '<meta itemprop="bestRating" content="5">';
		echo '<meta itemprop="worstRating" content="1">';
		echo '</div>';

		// Visible card.
		echo '<div class="nlr-stars" role="img" aria-label="Rated ' . esc_attr($rating) . ' out of 5 stars">';
		echo str_repeat('&#9733;', $rating) . str_repeat('&#9734;', 5 - $rating);
		echo '</div>';

		echo nlr_body_html(get_the_content(), $max_chars);

		echo '<p class="nlr-attribution">';
		echo '<span itemprop="author" itemscope itemtype="https://schema.org/Person">';
		if (!empty($link)) {
			echo '<strong><a href="' . esc_url($link) . '" target="_blank" rel="noopener noreferrer" aria-label="Read full review by ' . esc_attr($name) . ' (opens in new tab)">';
			echo '<span itemprop="name">' . esc_html($name) . '</span>';
			echo '</a></strong>';
		} else {
			echo '<strong itemprop="name">' . esc_html($name) . '</strong>';
		}
		echo '</span>';

		// The source is only stated when the review link proves it, never guessed.
		if (!empty($attribution)) {
			echo '<br>' . implode(' &middot; ', array_map('esc_html', $attribution));
		}
		echo '</p>';

		echo '</div>';
	}
	echo '</div>';

	wp_reset_postdata();

	return ob_get_clean();
}

/** The itemReviewed block, identical on every card. */
function nlr_item_reviewed_html() {
	$biz = array_merge(array(
		'name' => '', 'url' => '', 'phone' => '', 'address' => '',
		'city' => '', 'state' => '', 'zip' => '', 'cid' => '',
	), nlr_business());

	$html  = '<div itemprop="itemReviewed" itemscope itemtype="https://schema.org/LocalBusiness">';
	$html .= '<meta itemprop="name" content="' . esc_attr($biz['name']) . '">';
	$html .= '<meta itemprop="url" content="' . esc_url($biz['url']) . '">';

	$phone = nlr_schema_phone($biz['phone']);
	if ('' !== $phone) {
		$html .= '<meta itemprop="telephone" content="' . esc_attr($phone) . '">';
	}

	if ($biz['address'] || $biz['city']) {
		$html .= '<div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
		$parts = array(
			'streetAddress'   => $biz['address'],
			'addressLocality' => $biz['city'],
			'addressRegion'   => $biz['state'],
			'postalCode'      => $biz['zip'],
		);
		foreach ($parts as $prop => $value) {
			if ($value) {
				$html .= '<meta itemprop="' . $prop . '" content="' . esc_attr($value) . '">';
			}
		}
		$html .= '</div>';
	}

	if ($biz['cid']) {
		$html .= '<meta itemprop="sameAs" content="https://www.google.com/maps?cid=' . esc_attr($biz['cid']) . '">';
	}

	return $html . '</div>';
}

/** +1-XXX-XXX-XXXX for a US number; anything else is output as typed. */
function nlr_schema_phone($phone) {
	$phone  = trim((string) $phone);
	$digits = preg_replace('/\D/', '', $phone);
	$is_us  = (10 === strlen($digits) && '+' !== substr($phone, 0, 1))
		|| (11 === strlen($digits) && '1' === $digits[0]);

	if (!$is_us) {
		return $phone;
	}

	$digits = substr($digits, -10);

	return '+1-' . substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6);
}

/**
 * Collect testimonial IDs for one slice of the display list. With $text_only the
 * body has to be inspected, so it over-fetches and filters to keep the row full.
 */
function nlr_collect_ids($base_args, $limit, $text_only = false) {
	if ($limit < 1) {
		return array();
	}

	$args = array_merge(array(
		'post_type'     => 'testimonials',
		'post_status'   => 'publish',
		'no_found_rows' => true,
	), $base_args);

	if (!$text_only) {
		$args['posts_per_page'] = $limit;
		$args['fields']         = 'ids';
		$query = new WP_Query($args);

		return $query->posts;
	}

	$args['posts_per_page'] = -1;
	$query = new WP_Query($args);

	$ids = array();
	foreach ($query->posts as $post) {
		if ('' === nlr_plain_text($post->post_content)) {
			continue;
		}
		$ids[] = $post->ID;
		if (count($ids) >= $limit) {
			break;
		}
	}

	return $ids;
}

/** Every testimonial_category slug except the ones passed in. */
function nlr_other_term_slugs($keep_slugs) {
	$all = get_terms(array(
		'taxonomy'   => 'testimonial_category',
		'fields'     => 'slugs',
		'hide_empty' => false,
	));

	if (is_wp_error($all) || empty($all)) {
		return array();
	}

	return array_values(array_diff($all, $keep_slugs));
}

/** Flatten stored content to plain text. Returns '' for a star-only review. */
function nlr_plain_text($content) {
	$text = wp_strip_all_tags(strip_shortcodes((string) $content));
	$text = html_entity_decode($text, ENT_QUOTES, get_bloginfo('charset') ?: 'UTF-8');
	$text = preg_replace('/[ \t]{2,}/', ' ', $text);
	$text = preg_replace('/\x{00A0}/u', ' ', (string) $text);

	return trim($text);
}

/**
 * Render content as quoted paragraph(s). A shortened quote collapses to one
 * paragraph, and the FULL text still goes out as the schema reviewBody.
 */
function nlr_body_html($content, $max_chars = 0) {
	$text = nlr_plain_text($content);
	if ('' === $text) {
		return '';
	}

	$paragraphs = preg_split('/\R\s*\R/', $text);
	$paragraphs = array_values(array_filter(array_map('trim', $paragraphs), 'strlen'));

	$truncated = $max_chars > 0 ? nlr_truncate(implode(' ', $paragraphs), $max_chars) : '';
	if ('' !== $truncated) {
		return '<meta itemprop="reviewBody" content="' . esc_attr($text) . '">'
			. '<p>"' . esc_html($truncated) . '&hellip;"</p>';
	}

	$last = count($paragraphs) - 1;
	$html = '';
	foreach ($paragraphs as $i => $paragraph) {
		$open  = (0 === $i) ? '"' : '';
		$close = ($last === $i) ? '"' : '';
		$attr  = (0 === $i && 0 === $last) ? ' itemprop="reviewBody"' : '';
		$html .= '<p' . $attr . '>' . $open . esc_html($paragraph) . $close . '</p>';
	}

	return $last > 0 ? '<div itemprop="reviewBody">' . $html . '</div>' : $html;
}

/** Cut to a whole word at or under $max_chars. Returns '' when the text fits. */
function nlr_truncate($text, $max_chars) {
	if ($max_chars < 1 || mb_strlen($text) <= $max_chars) {
		return '';
	}

	$cut   = mb_substr($text, 0, $max_chars);
	$space = strrpos($cut, ' ');
	if (false !== $space && $space >= (int) ($max_chars / 2)) {
		$cut = substr($cut, 0, $space);
	}

	// Regex, not rtrim(): rtrim() strips bytes, which corrupts multibyte characters.
	return (string) preg_replace('/[\s.,;:!?\-–—"\']+$/u', '', $cut);
}

/** "Google Review" etc., from the review link. '' when there is no link. */
function nlr_source_label($review_link) {
	$host = $review_link ? wp_parse_url($review_link, PHP_URL_HOST) : '';
	if (empty($host)) {
		return '';
	}

	$host = preg_replace('/^www\./', '', strtolower($host));

	$platforms = array(
		'google'      => 'Google Review',
		'goo.gl'      => 'Google Review',
		'g.page'      => 'Google Review',
		'facebook'    => 'Facebook Review',
		'fb.com'      => 'Facebook Review',
		'yelp'        => 'Yelp Review',
		'bbb.org'     => 'BBB Review',
		'angi'        => 'Angi Review',
		'angieslist'  => 'Angi Review',
		'homeadvisor' => 'HomeAdvisor Review',
		'thumbtack'   => 'Thumbtack Review',
		'nextdoor'    => 'Nextdoor Review',
		'trustpilot'  => 'Trustpilot Review',
		'houzz'       => 'Houzz Review',
		'porch.com'   => 'Porch Review',
	);

	foreach ($platforms as $needle => $label) {
		if (false !== strpos($host, $needle)) {
			return $label;
		}
	}

	$parts = explode('.', $host);
	$name  = count($parts) > 1 ? $parts[count($parts) - 2] : $parts[0];

	return $name ? ucfirst($name) . ' Review' : '';
}

/** Totals the dashboard writes as JSON to one draft post, slug `gbp-stats`. */
function nlr_gbp_review_stats() {
	static $stats = null;
	if (null !== $stats) {
		return $stats;
	}

	$post    = get_page_by_path('gbp-stats', OBJECT, 'testimonials');
	$decoded = $post ? json_decode($post->post_content, true) : null;
	$stats   = is_array($decoded) ? $decoded : array();

	return $stats;
}
