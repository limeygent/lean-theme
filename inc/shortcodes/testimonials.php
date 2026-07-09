<?php

/**
 * Testimonials Shortcode
 * Usage: [testimonials num_reviews="6"]
 *        [testimonials category="hvac"]                       (single term slug)
 *        [testimonials category="hvac,plumbing"]              (any of several)
 *        [testimonials category="maintenance" num_reviews="6"] (tops up from "generic")
 *        [testimonials category="maintenance" fallback="reviews"] (custom top-up)
 *        [testimonials category="maintenance" fallback=""]    (no top-up, show what exists)
 *
 * Filters by the `testimonial_category` taxonomy when `category` is given, newest
 * first. If the primary category has fewer than num_reviews, the remaining slots
 * are filled at random from the `fallback` category (default "generic"), with no
 * duplicates. Set fallback="" to disable top-up.
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
    ), $atts, 'testimonials');

    // Sanitize the number of reviews
    $num_reviews = absint($atts['num_reviews']);
    if ($num_reviews < 1) {
        $num_reviews = 6;
    }

    $primary_slugs  = array_filter(array_map('sanitize_title', explode(',', $atts['category'])));
    $fallback_slugs = array_filter(array_map('sanitize_title', explode(',', $atts['fallback'])));
    // A fallback that duplicates the primary would just re-return the same posts.
    $fallback_slugs = array_diff($fallback_slugs, $primary_slugs);

    // Collect the post IDs to render, in display order.
    if (empty($primary_slugs)) {
        // No category filter — newest N across all testimonials (original behavior).
        $ids_query = new WP_Query(array(
            'post_type'      => 'testimonials',
            'post_status'    => 'publish',
            'posts_per_page' => $num_reviews,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'fields'         => 'ids',
        ));
        $collected_ids = $ids_query->posts;
    } else {
        // 1. Primary category, newest first.
        $primary_query = new WP_Query(array(
            'post_type'      => 'testimonials',
            'post_status'    => 'publish',
            'posts_per_page' => $num_reviews,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'fields'         => 'ids',
            'tax_query'      => array(array(
                'taxonomy' => 'testimonial_category',
                'field'    => 'slug',
                'terms'    => $primary_slugs,
            )),
        ));
        $collected_ids = $primary_query->posts;

        // 2. If short, top up the remainder randomly from the fallback category,
        //    excluding anything already collected so nothing repeats.
        $needed = $num_reviews - count($collected_ids);
        if ($needed > 0 && !empty($fallback_slugs)) {
            $fallback_query = new WP_Query(array(
                'post_type'      => 'testimonials',
                'post_status'    => 'publish',
                'posts_per_page' => $needed,
                'orderby'        => 'rand',
                'fields'         => 'ids',
                'post__not_in'   => $collected_ids,
                'tax_query'      => array(array(
                    'taxonomy' => 'testimonial_category',
                    'field'    => 'slug',
                    'terms'    => $fallback_slugs,
                )),
            ));
            $collected_ids = array_merge($collected_ids, $fallback_query->posts);
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
    
    // Start building output
    ob_start();
    
echo '<div class="items-grid" role="region" aria-label="Customer testimonials">';
    while ($testimonials_query->have_posts()) {
        $testimonials_query->the_post();
        $post_id = get_the_ID();
        $reviewer_name = get_the_title();
        $review_content = get_the_content();
        $review_link = get_post_meta($post_id, 'review_link', true);
        $review_rating = get_post_meta($post_id, 'review_rating', true) ?: 5; // Default to 5 if unset
        $review_rating = max(1, min(5, (int) $review_rating));
        $date_published = get_the_date('c'); // ISO 8601 format

        echo '<div class="items-card" itemscope itemtype="https://schema.org/Review">';

        // Review source URL (if available)
        if (!empty($review_link)) {
            echo '<meta itemprop="url" content="' . esc_url($review_link) . '">';
        }

        // Date published
        echo '<meta itemprop="datePublished" content="' . esc_attr($date_published) . '">';

        // Get business info from settings
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

        // Review body
        echo '<blockquote itemprop="reviewBody">';
        echo wp_kses_post($review_content);
        echo '</blockquote>';

        // Author attribution
        echo '<footer class="testimonial-author" itemprop="author" itemscope itemtype="https://schema.org/Person">';
        if (!empty($review_link)) {
            echo '<cite itemprop="name">';
            echo '<a href="' . esc_url($review_link) . '" target="_blank" rel="noopener noreferrer" aria-label="Read full review by ' . esc_attr($reviewer_name) . ' (opens in new tab)">';
            echo '&mdash; ' . esc_html($reviewer_name);
            echo '<span class="screen-reader-text"> (opens in new tab)</span>';
            echo '</a>';
            echo '</cite>';
        } else {
            echo '<cite itemprop="name">&mdash; ' . esc_html($reviewer_name) . '</cite>';
        }
        echo '</footer>';

        echo '</div>';
    }

	wp_reset_postdata();
    
    echo '</div>'; // Close items-grid
    
    return ob_get_clean();
}

// Optional: Add to admin for easy reference
add_action('admin_footer', function() {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'testimonials') {
        echo '<div class="notice notice-info"><p><strong>Testimonials Shortcode:</strong> Use <code>[testimonials num_reviews="6"]</code> to display testimonials, or <code>[testimonials category="slug"]</code> to show only one category.</p></div>';
    }
});
