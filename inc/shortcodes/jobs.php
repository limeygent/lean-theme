<?php
/**
 * Jobs Display Shortcode
 * 
 * Usage: [display_jobs]
 * Optional: [display_jobs limit="6" city="Plano" category="Pool Repair"]
 */

add_shortcode('display_jobs', function($atts) {
    $atts = shortcode_atts([
        'limit'    => -1,
        'city'     => '',
        'category' => '',
        'orderby'  => 'date',
        'order'    => 'DESC'
    ], $atts);
    
    // Build query args
    $args = [
        'post_type'      => 'job',
        'posts_per_page' => intval($atts['limit']),
        'orderby'        => $atts['orderby'],
        'order'          => $atts['order'],
    ];
    
    // Add meta query filters if specified
    $meta_query = [];
    
    if (!empty($atts['city'])) {
        $meta_query[] = [
            'key'   => 'city',
            'value' => $atts['city']
        ];
    }
    
    if (!empty($atts['category'])) {
        $meta_query[] = [
            'key'   => 'job_category',
            'value' => $atts['category']
        ];
    }
    
    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }
    
    $jobs = new WP_Query($args);
    
    if (!$jobs->have_posts()) {
        return '<p>No jobs found.</p>';
    }
    
    ob_start();
    ?>
    <div class="container-fluid p-0">
        <div class="row">
            <?php while ($jobs->have_posts()) : $jobs->the_post(); 
                $tech_name    = get_field('tech_name');
                $description  = get_field('description');
                $city         = get_field('city');
                $job_date     = get_field('job_date');
                $job_category = get_field('job_category');
                $image        = get_field('job_photo');
                
                // Format date
                $formatted_date = '';
                if ($job_date) {
                    $formatted_date = date('F j', strtotime($job_date));
                }
            ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 border p-0">
                    <?php if ($image) : ?>
                        <img src="<?php echo esc_url($image['url']); ?>" 
                             alt="<?php echo esc_attr($city . ' ' . $job_category); ?>" 
                             class="card-img-top" 
                             style="height: 400px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <p class="mb-3"><?php echo esc_html($description); ?></p>
                        <p class="mb-0"><strong>Location: </strong><?php echo esc_html($city); ?></p>
                        <p class="mb-0"><strong>Date: </strong><?php echo esc_html($formatted_date); ?></p>
                        <p class="mb-0"><strong>Technician: </strong><?php echo esc_html($tech_name); ?></p>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
});