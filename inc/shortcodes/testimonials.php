<?php

/**
 * Testimonials Shortcode
 * Usage: [testimonials num_reviews="6"]
 * WCAG AA Compliant testimonials display
 */

// Register the shortcode
add_shortcode('testimonials', 'display_testimonials_shortcode');

function display_testimonials_shortcode($atts) {
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'num_reviews' => 4, // Number of reviews to show
    ), $atts, 'testimonials');
    
    // Sanitize the number of reviews
    $num_reviews = absint($atts['num_reviews']);
    if ($num_reviews < 1) {
        $num_reviews = 6;
    }
    
    // Query testimonials
    $testimonials_query = new WP_Query(array(
        'post_type' => 'testimonials',
        'post_status' => 'publish',
        'posts_per_page' => $num_reviews,
        'orderby' => 'date',
        'order' => 'DESC'
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
        $review_link = get_field('review_link', $post_id);
        $review_rating = get_field('review_rating', $post_id) ?: 5; // Default to 5 if no rating field
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

        // Review rating (assumes a custom field 'review_rating' or defaults to 5)
        echo '<div itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">';
        echo '<meta itemprop="ratingValue" content="5">';
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
        echo '<div class="notice notice-info"><p><strong>Testimonials Shortcode:</strong> Use <code>[testimonials num_reviews="6"]</code> to display testimonials.</p></div>';
    }
});
