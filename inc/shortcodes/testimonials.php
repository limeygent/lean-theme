<?php

/**
 * Testimonials Shortcode
 * Usage: [testimonials num_reviews="6"]
 *        [testimonials category="hvac"]                       (single term slug)
 *        [testimonials category="hvac,plumbing"]              (any of several)
 *        [testimonials category="maintenance" num_reviews="6"] (tops up from "generic")
 *        [testimonials category="maintenance" fallback="reviews"] (custom top-up)
 *        [testimonials category="maintenance" fallback=""]    (no top-up, show what exists)
 *        [testimonials category="heating" fallback="brand" fallback_exclusive="0"]
 *                                                             (allow off-topic top-ups)
 *        [testimonials location="south-dallas"]               (one business location)
 *        [testimonials location="south-dallas" category="fast-release"]
 *                                                             (both: that topic, that branch)
 *        [testimonials text_only="1"]                         (skip star-only reviews)
 *        [testimonials max_chars="180"]                       (truncate long quotes)
 *
 *        [gbp_review_count]    → 153   every Google review, rating-only included
 *        [gbp_review_average]  → 5.0   mean star rating over the same reviews
 *
 * Filters by the `testimonial_category` taxonomy when `category` is given, newest
 * first. If the primary category has fewer than num_reviews, the remaining slots
 * are filled at random from the `fallback` category (default "generic"), with no
 * duplicates. Set fallback="" to disable top-up.
 *
 * The top-up pool is *exclusive* by default: a review only qualifies if it carries
 * no testimonial_category outside the fallback (and primary) set. So a review
 * tagged "brand + ac-repair" is an AC review, and never tops up a heating page —
 * only reviews tagged "brand" alone do. Set fallback_exclusive="0" to go back to
 * "any review carrying the fallback term", which is what you want if every review
 * also carries a utility term (e.g. "featured") that would otherwise disqualify
 * the whole pool.
 *
 * location filters on the `review_location` meta the agency dashboard writes on
 * each testimonial — the slug of the business location the review was left for.
 * It applies to the WHOLE row, top-ups included: a Kaufman page that runs short
 * tops up with other Kaufman reviews, never with another branch's. Comma-separate
 * to accept several. Testimonials with no location recorded (entered by hand, or
 * pushed before the field existed) match no location filter. Needs NerdPress SEO
 * to register the field; without it nothing carries a location and a filtered
 * row is empty.
 *
 * text_only="1" skips reviews with no written body (a star-only Google review),
 * and keeps looking so the row still fills to num_reviews. max_chars="180" cuts
 * long quotes at a whole word and appends an ellipsis, for even card heights;
 * the untruncated review still ships as the schema reviewBody.
 *
 * The review total can't come from counting testimonial posts — star-only and
 * uncategorised reviews are never pushed, so the posts are a subset. The agency
 * dashboard writes the real totals to a draft `gbp-stats` post on every push
 * (see lean_gbp_review_stats()), which [gbp_review_count] and [gbp_review_average]
 * read. Both print nothing on a site without that post.
 *
 * Output: a Bootstrap `.row.g-4` of `.col-md-6.col-lg-4` cells, each holding a
 * `.review-card` (styled in css/lean-pages.css) with stars, the quote, and a
 * name / source / date footer. Schema.org Review microdata is carried on
 * invisible <meta> tags inside each card.
 * WCAG AA Compliant testimonials display
 */

// Register the shortcode
add_shortcode('testimonials', 'display_testimonials_shortcode');

function display_testimonials_shortcode($atts) {
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'num_reviews' => 4,         // Number of reviews to show
        'category'    => '',        // Primary testimonial_category slug(s), comma-separated
        'fallback'    => 'generic', // Top-up category when the primary is short; '' disables
        'fallback_exclusive' => true, // Top-ups must carry no category outside the fallback
        'location'    => '',        // review_location slug(s), comma-separated; '' = any location
        'text_only'   => false,     // Skip star-only reviews (no written body)
        'max_chars'   => 0,         // Truncate the quote past this many characters; 0 = never
    ), $atts, 'testimonials');

    // Sanitize the number of reviews
    $num_reviews = absint($atts['num_reviews']);
    if ($num_reviews < 1) {
        $num_reviews = 6;
    }

    $text_only = filter_var($atts['text_only'], FILTER_VALIDATE_BOOLEAN);
    $exclusive = filter_var($atts['fallback_exclusive'], FILTER_VALIDATE_BOOLEAN);
    $max_chars = absint($atts['max_chars']);

    $primary_slugs  = array_filter(array_map('sanitize_title', explode(',', $atts['category'])));
    $fallback_slugs = array_filter(array_map('sanitize_title', explode(',', $atts['fallback'])));
    // A fallback that duplicates the primary would just re-return the same posts.
    $fallback_slugs = array_diff($fallback_slugs, $primary_slugs);

    // Location narrows every query below, so it is built once and merged in.
    $location_slugs = array_values(array_filter(array_map('sanitize_title', explode(',', $atts['location']))));
    $location_args  = empty($location_slugs) ? array() : array(
        'meta_query' => array(array(
            'key'     => 'review_location',
            'value'   => $location_slugs,
            'compare' => 'IN',
        )),
    );

    // Collect the post IDs to render, in display order.
    if (empty($primary_slugs)) {
        // No category filter — newest N across all testimonials (original behavior).
        $collected_ids = lean_testimonials_collect_ids(array_merge(array(
            'orderby' => 'date',
            'order'   => 'DESC',
        ), $location_args), $num_reviews, $text_only);
    } else {
        // 1. Primary category, newest first.
        $collected_ids = lean_testimonials_collect_ids(array_merge(array(
            'orderby'   => 'date',
            'order'     => 'DESC',
            'tax_query' => array(array(
                'taxonomy' => 'testimonial_category',
                'field'    => 'slug',
                'terms'    => $primary_slugs,
            )),
        ), $location_args), $num_reviews, $text_only);

        // 2. If short, top up the remainder randomly from the fallback category,
        //    excluding anything already collected so nothing repeats.
        $needed = $num_reviews - count($collected_ids);
        if ($needed > 0 && !empty($fallback_slugs)) {
            $tax_query = array(array(
                'taxonomy' => 'testimonial_category',
                'field'    => 'slug',
                'terms'    => $fallback_slugs,
            ));

            // Exclusive top-up: a review also tagged with some *other* service is a
            // review about that service, and reads as off-topic here. Disqualify it
            // by requiring the review to carry none of the taxonomy's other terms.
            $off_topic = $exclusive
                ? lean_testimonials_other_term_slugs(array_merge($fallback_slugs, $primary_slugs))
                : array();
            if (!empty($off_topic)) {
                $tax_query['relation'] = 'AND';
                $tax_query[] = array(
                    'taxonomy' => 'testimonial_category',
                    'field'    => 'slug',
                    'terms'    => $off_topic,
                    'operator' => 'NOT IN',
                );
            }

            $collected_ids = array_merge($collected_ids, lean_testimonials_collect_ids(array_merge(array(
                'orderby'      => 'rand',
                'post__not_in' => $collected_ids,
                'tax_query'    => $tax_query,
            ), $location_args), $needed, $text_only));
        }
    }

    if (empty($collected_ids)) {
        return '<p>No testimonials found.</p>';
    }

    // Render query: fetch the collected posts, preserving the order above.
    $testimonials_query = new WP_Query(array(
        'post_type'      => 'testimonials',
        'post_status'    => 'publish',
        'post__in'       => $collected_ids,
        'orderby'        => 'post__in',
        'posts_per_page' => count($collected_ids),
    ));
    
    if (!$testimonials_query->have_posts()) {
        return '<p>No testimonials found.</p>';
    }
    
    // Business info for the itemReviewed schema — identical for every card, so
    // read the options once rather than per iteration.
    $biz_name = get_option('business_name', get_bloginfo('name'));
    $biz_url = get_option('business_url', home_url());
    $biz_phone = get_option('business_phone', '');
    $biz_address = get_option('business_address', '');
    $biz_city = get_option('business_city', '');
    $biz_state = get_option('business_state', '');
    $biz_zip = get_option('business_zip', '');
    $biz_cid = get_option('google_maps_cid', '');

    // Format phone for schema (+1-XXX-XXX-XXXX)
    $phone_digits = preg_replace('/\D/', '', $biz_phone);
    $phone_formatted = $phone_digits ? '+1-' . substr($phone_digits, 0, 3) . '-' . substr($phone_digits, 3, 3) . '-' . substr($phone_digits, 6) : '';

    // Start building output
    ob_start();

    echo '<div class="row g-4 mt-1" role="region" aria-label="Customer testimonials">';
    while ($testimonials_query->have_posts()) {
        $testimonials_query->the_post();
        $post_id = get_the_ID();
        $reviewer_name = get_the_title();
        $review_content = get_the_content();
        $review_link = get_post_meta($post_id, 'review_link', true);
        $review_rating = get_post_meta($post_id, 'review_rating', true) ?: 5; // Default to 5 if unset
        $review_rating = max(1, min(5, (int) $review_rating));
        $date_published = get_the_date('c');    // ISO 8601, for schema
        $date_display = get_the_date('F Y');    // e.g. "July 2026", for the card
        $source_label = lean_testimonial_source_label($review_link);

        echo '<div class="col-md-6 col-lg-4">';
        echo '<div class="review-card h-100" itemscope itemtype="https://schema.org/Review">';

        // ── Schema-only markup (no visual output) ───────────────────────────
        // Review source URL (if available)
        if (!empty($review_link)) {
            echo '<meta itemprop="url" content="' . esc_url($review_link) . '">';
        }

        // Date published
        echo '<meta itemprop="datePublished" content="' . esc_attr($date_published) . '">';

        // itemReviewed: LocalBusiness
        echo '<div itemprop="itemReviewed" itemscope itemtype="https://schema.org/LocalBusiness">';
        echo '<meta itemprop="name" content="' . esc_attr($biz_name) . '">';
        echo '<meta itemprop="url" content="' . esc_url($biz_url) . '">';
        if ($phone_formatted) {
            echo '<meta itemprop="telephone" content="' . esc_attr($phone_formatted) . '">';
        }
        if ($biz_address || $biz_city) {
            echo '<div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
            if ($biz_address) echo '<meta itemprop="streetAddress" content="' . esc_attr($biz_address) . '">';
            if ($biz_city) echo '<meta itemprop="addressLocality" content="' . esc_attr($biz_city) . '">';
            if ($biz_state) echo '<meta itemprop="addressRegion" content="' . esc_attr($biz_state) . '">';
            if ($biz_zip) echo '<meta itemprop="postalCode" content="' . esc_attr($biz_zip) . '">';
            echo '</div>';
        }
        if ($biz_cid) {
            echo '<meta itemprop="sameAs" content="https://www.google.com/maps?cid=' . esc_attr($biz_cid) . '">';
        }
        echo '</div>';

        // Review rating (from the testimonial's review_rating meta, 1–5)
        echo '<div itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">';
        echo '<meta itemprop="ratingValue" content="' . esc_attr($review_rating) . '">';
        echo '<meta itemprop="bestRating" content="5">';
        echo '<meta itemprop="worstRating" content="1">';
        echo '</div>';

        // ── Visible card ────────────────────────────────────────────────────
        // Stars: filled for the rating, hollow for the remainder. role/aria-label
        // give the glyphs a text alternative (a bare ★ string reads as junk).
        echo '<div class="stars" role="img" aria-label="Rated ' . esc_attr($review_rating) . ' out of 5 stars">';
        echo str_repeat('&#9733;', $review_rating) . str_repeat('&#9734;', 5 - $review_rating);
        echo '</div>';

        // Review body, in quotes. Testimonials are plain quoted speech, so the
        // stored markup is flattened to text and re-wrapped in <p> here.
        echo lean_testimonial_body_html($review_content, $max_chars);

        // Author attribution: name, then source · date.
        echo '<p class="text-muted small mb-0">';
        echo '<span itemprop="author" itemscope itemtype="https://schema.org/Person">';
        if (!empty($review_link)) {
            echo '<strong><a href="' . esc_url($review_link) . '" target="_blank" rel="noopener noreferrer" aria-label="Read full review by ' . esc_attr($reviewer_name) . ' (opens in new tab)">';
            echo '<span itemprop="name">' . esc_html($reviewer_name) . '</span>';
            echo '</a></strong>';
        } else {
            echo '<strong itemprop="name">' . esc_html($reviewer_name) . '</strong>';
        }
        echo '</span>';

        // Source is only stated when the review link proves it — never guessed.
        $attribution = array_filter(array($source_label, $date_display));
        if (!empty($attribution)) {
            echo '<br>' . implode(' &middot; ', array_map('esc_html', $attribution));
        }
        echo '</p>';

        echo '</div>'; // .review-card
        echo '</div>'; // .col
    }

	wp_reset_postdata();

    echo '</div>'; // Close .row

    return ob_get_clean();
}

/**
 * Collect testimonial IDs for one slice of the display list.
 *
 * `$base_args` carries only what varies (ordering, tax_query, exclusions); the
 * post type / status are fixed here. When `$text_only` is on, the body has to be
 * inspected to judge it, so the query is run unlimited and filtered down to the
 * first `$limit` reviews that actually have written text — over-fetching is what
 * keeps a page of star-only reviews from silently returning a short row.
 */
function lean_testimonials_collect_ids($base_args, $limit, $text_only = false) {
    if ($limit < 1) {
        return array();
    }

    $args = array_merge(array(
        'post_type'     => 'testimonials',
        'post_status'   => 'publish',
        'no_found_rows' => true, // never paginated — skip the row count
    ), $base_args);

    if (!$text_only) {
        $args['posts_per_page'] = $limit;
        $args['fields']         = 'ids';
        $query = new WP_Query($args);

        return $query->posts;
    }

    // 'fields' => 'ids' would mean a separate fetch per post to read the body,
    // so pull the posts themselves and filter in one pass.
    $args['posts_per_page'] = -1;
    unset($args['fields']);
    $query = new WP_Query($args);

    $ids = array();
    foreach ($query->posts as $post) {
        if ('' === lean_testimonial_plain_text($post->post_content)) {
            continue; // star-only review — nothing to quote
        }
        $ids[] = $post->ID;
        if (count($ids) >= $limit) {
            break;
        }
    }

    return $ids;
}

/**
 * Every testimonial_category slug except the ones passed in.
 *
 * Used to build the NOT IN half of an exclusive top-up: "tagged brand, and tagged
 * nothing else". Returns array() if the taxonomy isn't registered or has no other
 * terms — the caller then runs the plain IN query, since with nothing to exclude
 * the two are the same thing.
 */
function lean_testimonials_other_term_slugs($keep_slugs) {
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

/**
 * Flatten a testimonial's stored content to the plain quotable text.
 *
 * Shortcodes, tags and block comments go; entities are decoded so `&deg;` reads
 * as a degree sign rather than markup. Returns '' for a star-only review, which
 * is what `text_only` filters on.
 */
function lean_testimonial_plain_text($content) {
    $text = wp_strip_all_tags(strip_shortcodes((string) $content));
    $text = html_entity_decode($text, ENT_QUOTES, get_bloginfo('charset') ?: 'UTF-8');
    $text = preg_replace('/[ \t]{2,}/', ' ', $text);        // gaps left by stripped markup
    $text = preg_replace('/\x{00A0}/u', ' ', (string) $text); // pasted reviews carry nbsp

    return trim($text);
}

/**
 * Render a testimonial's stored content as quoted paragraph(s).
 *
 * The editor content is flattened to plain text and re-wrapped, so a testimonial
 * pasted from Google — or written in Gutenberg — always renders as clean quoted
 * speech inside the card. Blank lines are preserved as separate paragraphs; the
 * opening quote goes on the first and the closing quote on the last.
 *
 * `$max_chars` > 0 shortens the quote to a whole word at or under that length
 * and appends an ellipsis. A truncated quote collapses to a single paragraph,
 * and the FULL text still goes out as the schema reviewBody — the card is
 * shortened for layout, the structured data must stay faithful to the review.
 */
function lean_testimonial_body_html($content, $max_chars = 0) {
    $text = lean_testimonial_plain_text($content);

    if ('' === $text) {
        return '';
    }

    $paragraphs = preg_split('/\R\s*\R/', $text);
    $paragraphs = array_values(array_filter(array_map('trim', $paragraphs), 'strlen'));

    $truncated = '';
    if ($max_chars > 0) {
        // Truncating across a paragraph break reads as a mistake, so a shortened
        // quote is one run of text.
        $truncated = lean_testimonial_truncate(implode(' ', $paragraphs), $max_chars);
    }

    if ('' !== $truncated) {
        return '<meta itemprop="reviewBody" content="' . esc_attr($text) . '">'
             . '<p>"' . esc_html($truncated) . '&hellip;"</p>';
    }

    $last = count($paragraphs) - 1;

    $html = '';
    foreach ($paragraphs as $i => $paragraph) {
        $open  = (0 === $i) ? '"' : '';
        $close = ($last === $i) ? '"' : '';
        // itemprop on the wrapper so a multi-paragraph quote is one reviewBody.
        $attr  = (0 === $i && 0 === $last) ? ' itemprop="reviewBody"' : '';
        $html .= '<p' . $attr . '>' . $open . esc_html($paragraph) . $close . '</p>';
    }

    // Multi-paragraph: wrap so reviewBody covers the whole quote, not just ¶1.
    if ($last > 0) {
        $html = '<div itemprop="reviewBody">' . $html . '</div>';
    }

    return $html;
}

/**
 * Cut text to a whole word at or under $max_chars.
 *
 * Returns '' when the text already fits, which is the caller's signal that
 * nothing was truncated (and so no ellipsis belongs). Trailing punctuation is
 * stripped from the cut so the join with the ellipsis doesn't read as ",…".
 */
function lean_testimonial_truncate($text, $max_chars) {
    if ($max_chars < 1 || mb_strlen($text) <= $max_chars) {
        return '';
    }

    $cut = mb_substr($text, 0, $max_chars);

    // A space is single-byte in UTF-8, so this byte offset is always a character
    // boundary. Only back up to it if it leaves a usable amount of the quote.
    $space = strrpos($cut, ' ');
    if (false !== $space && $space >= (int) ($max_chars / 2)) {
        $cut = substr($cut, 0, $space);
    }

    // Regex, not rtrim(): rtrim() strips bytes, so the dashes in its list would eat
    // the tail of any multibyte character sharing those bytes (e.g. an emoji), and
    // esc_html() blanks a string left as invalid UTF-8.
    return (string) preg_replace('/[\s.,;:!?\-–—"\']+$/u', '', $cut);
}

/**
 * Human label for where a review came from, derived from its review_link.
 *
 * Returns '' when there is no link — an unsourced testimonial shows only its
 * date rather than claiming a platform we can't evidence.
 */
function lean_testimonial_source_label($review_link) {
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

    // Unknown platform: name it after the registrable domain label, so
    // reviews.example.com reads "Example Review".
    $parts = explode('.', $host);
    $name  = count($parts) > 1 ? $parts[count($parts) - 2] : $parts[0];

    return $name ? ucfirst($name) . ' Review' : '';
}

/**
 * Review totals written by the agency dashboard on every testimonials push.
 *
 * Stored as JSON in the post_content of ONE draft `testimonials` post, slug
 * `gbp-stats` (title "GBP review stats (generated — do not edit)"). Draft keeps
 * it out of every listing query in this file, since they all ask for publish;
 * get_page_by_path() reads it by slug whatever its status.
 *
 * Only `total.count` and `total.average` are used here. The payload also carries
 * withText / published / googleCount and a per-location breakdown — spec lives
 * with the writer, in agency-dashboard/docs/testimonials-push.md. Returns array()
 * when the post is missing or doesn't parse, and the shortcodes print nothing.
 */
function lean_gbp_review_stats() {
    static $stats = null;
    if (null !== $stats) {
        return $stats;
    }

    $post    = get_page_by_path('gbp-stats', OBJECT, 'testimonials');
    $decoded = $post ? json_decode($post->post_content, true) : null;
    $stats   = is_array($decoded) ? $decoded : array();

    return $stats;
}

// Inline review figures for page copy, e.g.
// "Rated [gbp_review_average] from [gbp_review_count] Google reviews".

/** [gbp_review_count] → "153": total.count, every active review, rating-only included. */
add_shortcode('gbp_review_count', function () {
    $stats = lean_gbp_review_stats();
    $count = isset($stats['total']['count']) ? $stats['total']['count'] : null;

    return is_numeric($count) ? number_format_i18n((int) $count) : '';
});

/** [gbp_review_average] → "5.0": total.average, always one decimal as Google shows it. */
add_shortcode('gbp_review_average', function () {
    $stats   = lean_gbp_review_stats();
    $average = isset($stats['total']['average']) ? $stats['total']['average'] : null;

    return is_numeric($average) ? number_format_i18n((float) $average, 1) : '';
});

// Optional: Add to admin for easy reference
add_action('admin_footer', function() {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'testimonials') {
        echo '<div class="notice notice-info"><p><strong>Testimonials Shortcode:</strong> Use <code>[testimonials num_reviews="6"]</code> to display testimonials, <code>[testimonials category="slug"]</code> to show only one category, or <code>[testimonials location="slug"]</code> to show only one location.</p></div>';
    }
});
