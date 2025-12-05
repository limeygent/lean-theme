<?php
/**
 * Locations URL System
 *
 * Uses the "locations" CPT + ACF fields to drive:
 *
 * 1) City service pages:
 *    /{city-slug}-pool-cleaning-service/
 *    - Backed by a "locations" post:
 *      - post_type = locations
 *      - post_name = {city-slug}
 *      - location_type = "city"
 *
 * 2) Neighborhood service pages:
 *    /{city-slug}/{neighborhood-slug}-pool-cleaning-service/
 *    - Backed by a "locations" post:
 *      - post_type = locations
 *      - post_name = {neighborhood-slug}
 *      - location_type = "neighborhood"
 *      - parent_city = {city-slug} (ACF field)
 *
 * 3) City landing pages (hub pages):
 *    /{city-slug}/
 *    - Only used when NO core/page/post rewrite matches first.
 *    - Backed by same "locations" post as the city.
 *    - Renders via single-locations-city.php (city hub template).
 *
 * If a URL doesn't map to a real locations post (e.g. /gotham/ or
 * /gotham-pool-cleaning-service/), the query returns no posts and falls
 * through to the normal 404. No soft-200s.
 */

add_action('wp_head', function() {
    if ( is_user_logged_in() ) {
        echo '<!-- DEBUG: ';
        echo 'city_landing: ' . get_query_var('city_landing') . ' | ';
        echo 'is_singular(locations): ' . (is_singular('locations') ? 'yes' : 'no') . ' | ';
        echo 'post_type: ' . get_post_type() . ' | ';
        echo 'template: ' . basename(get_page_template());
        echo ' -->';
    }
});

/**
 * Build canonical permalinks for locations CPT.
 * Ensures get_permalink() + admin "View" use our pretty patterns.
 */
function ebp_locations_permalink($post_link, $post, $leavename) {
    if ($post->post_type !== 'locations') {
        // Only touch locations CPT; all other post types use default behavior.
        return $post_link;
    }

    $type = strtolower(get_field('location_type', $post->ID));
    $slug = $post->post_name;

    // City: /{city-slug}-pool-cleaning-service/
    if ($type === 'city') {
        return home_url('/' . $slug . '-pool-cleaning-service/');
    }

    // Neighborhood: /{parent-city}/{neighborhood-slug}-pool-cleaning-service/
    if ($type === 'neighborhood') {
        // ACF field "parent_city" should store the city slug or city label.
        $parent_city = get_field('parent_city', $post->ID);
        if ($parent_city) {
            $city_slug = sanitize_title($parent_city);
            return home_url('/' . $city_slug . '/' . $slug . '-pool-cleaning-service/');
        }
    }

    // If ACF data is missing or unexpected, fall back to default permalink
    // so we don't generate broken URLs.
    return $post_link;
}
add_filter('post_type_link', 'ebp_locations_permalink', 10, 3);

/**
 * Preview links.
 * Makes the "Preview" and "View" buttons in admin match the live URL structure.
 */
function ebp_locations_preview_link($preview_link, $post) {
    if ($post->post_type !== 'locations') {
        return $preview_link;
    }

    $type = strtolower(get_field('location_type', $post->ID));
    $slug = $post->post_name;

    if ($type === 'city') {
        // Preview for city = city service URL
        return home_url('/' . $slug . '-pool-cleaning-service/');
    }

    if ($type === 'neighborhood') {
        // Preview for neighborhood = neighborhood service URL
        $parent_city = get_field('parent_city', $post->ID);
        if ($parent_city) {
            $city_slug = sanitize_title($parent_city);
            return home_url('/' . $city_slug . '/' . $slug . '-pool-cleaning-service/');
        }
    }

    // Fallback to default preview if data incomplete.
    return $preview_link;
}
add_filter('preview_post_link', 'ebp_locations_preview_link', 10, 2);

/**
 * Rewrites for locations CPT
 *
 * IMPORTANT ORDER NOTES:
 * - City + neighborhood service rules use 'top' so they take priority.
 * - City landing rule uses 'bottom' as a fallback ONLY IF:
 *      - No Page/Post/Category/etc already owns that slug.
 *      - This prevents collisions with /contact/, /about/, etc.
 */
function ebp_locations_rewrites() {

    // 1) City service:
    //    /{city-slug}-pool-cleaning-service/
    //    Maps to locations post with slug = {city-slug}
    add_rewrite_rule(
        '^([^/]+)-pool-cleaning-service/?$',
        'index.php?post_type=locations&name=$matches[1]',
        'top'
    );

    // 2) Neighborhood service:
    //    /{city-slug}/{neighborhood-slug}-pool-cleaning-service/
    //    Maps to locations post with slug = {neighborhood-slug}
    //    city_context is passed for template logic (parent city awareness).
    add_rewrite_rule(
        '^([^/]+)/([^/]+)-pool-cleaning-service/?$',
        'index.php?post_type=locations&name=$matches[2]&city_context=$matches[1]',
        'top'
    );

    // 3) City landing:
    //    /{city-slug}/
    //
    //    - Declared with 'bottom' so:
    //        - If a real Page/CPT/taxonomy exists for that slug, it wins.
    //        - If nothing else matches, we *attempt* to treat it as a
    //          locations city record.
    //
    //    - If no locations post exists, WP query returns 0 results => 404.
    //      This is desired: unknown slugs become normal 404s.
    add_rewrite_rule(
        '^([^/]+)/?$',
        'index.php?post_type=locations&name=$matches[1]&city_landing=1',
        'bottom'
    );
}
add_action('init', 'ebp_locations_rewrites');

/**
 * Register public query vars used by our rewrites.
 *
 * - city_context: carries parent city slug into neighborhood templates.
 * - city_landing: flag to indicate "/{city}/" was matched as a potential city hub.
 */
function ebp_locations_query_vars($vars) {
    $vars[] = 'city_context';
    $vars[] = 'city_landing';
    return $vars;
}
add_filter('query_vars', 'ebp_locations_query_vars');

/**
 * Normalize main query for locations after rewrites.
 *
 * Ensures that when we hit a locations URL, WP treats it as a locations CPT
 * single and doesn't get confused by other query vars.
 */
function ebp_locations_pre_get_posts($q) {
    if (is_admin() || !$q->is_main_query()) {
        return;
    }

    // If a rewrite set post_type=locations and name={slug}, make sure it's locked in.
    if ($q->get('post_type') === 'locations' && $q->get('name')) {
        $q->set('post_type', 'locations');
    }
}
add_action('pre_get_posts', 'ebp_locations_pre_get_posts');

/**
 * Template routing for city landing pages.
 *
 * Logic:
 * - Only triggers when:
 *      - city_landing=1 (from our /{city}/ rewrite), AND
 *      - We successfully resolved a singular 'locations' post.
 * - Loads single-locations-city.php for city hub output.
 *
 * If no locations post exists for that slug:
 * - Core query will have no posts.
 * - WP will render the normal 404 template.
 */
function ebp_locations_template_include( $template ) {
    // city_landing is only set by our /{city}/ rewrite rule.
    if ( get_query_var( 'city_landing' ) && is_singular( 'locations' ) ) {
        $city_template = locate_template( 'single-locations-city.php' );
        if ( $city_template ) {
            return $city_template;
        }
        // If city_landing is set but the template is missing, we fall back
        // to the default single template so we don't hard-break the site.
    }

    return $template;
}
add_filter( 'template_include', 'ebp_locations_template_include', 99 );

/**
 * Prevent canonical redirect for city landing pages
 * This checks the actual URL pattern and post type, not query vars
 */
/**
 * Prevent canonical redirect for city landing pages
 * This checks the actual URL pattern and post type, not query vars
 */
function ebp_prevent_city_landing_redirect( $redirect_url, $requested_url ) {
    // Parse the requested path
    $path = parse_url( $requested_url, PHP_URL_PATH );
    $path = trim( $path, '/' );
    
    // Check if this looks like a city landing page:
    // - Single segment path (no slashes in the middle)
    // - NOT ending with -pool-cleaning-service
    if ( ! empty( $path ) 
         && strpos( $path, '/' ) === false 
         && strpos( $path, '-pool-cleaning-service' ) === false 
         && strpos( $path, '.' ) === false ) {
        
        // Check if this slug matches a locations post
        $location_post = get_page_by_path( $path, OBJECT, 'locations' );
        
        if ( $location_post ) {
            // Check if it's a city location
            $location_type = get_field( 'location_type', $location_post->ID );
            
            if ( strtolower( $location_type ) === 'city' ) {
                // This IS a city landing page - prevent redirect!
                return false; // Returning false prevents the redirect
            }
        }
    }
    
    return $redirect_url;
}

// Add with priority 1 to run early - BEFORE the debug interceptor
add_filter( 'redirect_canonical', 'ebp_prevent_city_landing_redirect', 1, 2 );

/**
 * Set the proper <link rel="canonical"> for city landing pages.
 *
 * If your theme (or core) calls rel_canonical() on singulars, this ensures
 * the hub URL (/city/) is treated as canonical for city_landing views,
 * instead of the pool-cleaning service URL.
 */
function ebp_locations_city_landing_canonical( $canonical ) {
    if ( get_query_var( 'city_landing' ) && is_singular( 'locations' ) ) {
        $slug = get_post_field( 'post_name' );
        if ( $slug ) {
            return home_url( '/' . $slug . '/' );
        }
    }

    return $canonical;
}
add_filter( 'rel_canonical', 'ebp_locations_city_landing_canonical' );


/**
 * Fix the query vars for city landing pages
 * This runs early and forces the correct post_type
 */
function ebp_fix_city_landing_query( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }
    
    // Get the request path
    $path = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
    
    // Check if this is a single-segment path (potential city)
    if ( ! empty( $path ) 
         && strpos( $path, '/' ) === false 
         && strpos( $path, '-pool-cleaning-service' ) === false
         && strpos( $path, '.' ) === false
         && strpos( $path, 'wp-' ) === false ) {
        
        // Check if a locations post exists with this slug
        global $wpdb;
        $location_exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'locations' AND post_status = 'publish'",
            $path
        ));
        
        if ( $location_exists ) {
            // Force the query to look for locations post type
            $query->set( 'post_type', 'locations' );
            $query->set( 'name', $path );
            $query->set( 'city_landing', 1 );
            
            // Make sure it's treated as a single post query
            $query->is_single = true;
            $query->is_singular = true;
            $query->is_404 = false;
        }
    }
}
// Run this VERY early, before other plugins can interfere
add_action( 'pre_get_posts', 'ebp_fix_city_landing_query', 1 );

/**
 * Alternative approach - filter the query vars directly
 */
function ebp_fix_city_query_vars( $query_vars ) {
    $path = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
    
    // If we have a name but no post_type, and it matches our pattern
    if ( isset( $query_vars['name'] ) 
         && ! isset( $query_vars['post_type'] )
         && ! empty( $path ) 
         && strpos( $path, '/' ) === false 
         && strpos( $path, '-pool-cleaning-service' ) === false ) {
        
        // Check if this is a locations post
        global $wpdb;
        $is_location = $wpdb->get_var( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'locations' AND post_status = 'publish'",
            $query_vars['name']
        ));
        
        if ( $is_location ) {
            $query_vars['post_type'] = 'locations';
            $query_vars['city_landing'] = 1;
        }
    }
    
    return $query_vars;
}
add_filter( 'request', 'ebp_fix_city_query_vars', 1 );