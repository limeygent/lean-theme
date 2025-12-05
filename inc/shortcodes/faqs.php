<?php
/**
 * FAQ Shortcode
 * Usage: [faq_list] or [faq_list posts_per_page="10"]
 */
function display_faq_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'posts_per_page' => 30,
    ), $atts, 'faq_list');
    
    // Build query args
    $args = array(
        'post_type' => 'faq',
        'posts_per_page' => intval($atts['posts_per_page']),
        'orderby' => 'menu_order',
        'order' => 'ASC'
    );
    
    $faq = new WP_Query($args);
    
    if (!$faq->have_posts()) {
        return '';
    }
    
    // Start output buffering
    ob_start();
    
    while ($faq->have_posts()) : $faq->the_post();
        $question = get_the_title();
        $answer = get_the_content();
        $faq_id = get_the_ID();
        ?>
        <details class="faq-item border rounded mb-3 p-3">
            <summary class="mb-2" id="faq-question-<?php echo $faq_id; ?>" aria-controls="faq-answer-<?php echo $faq_id; ?>">
                <?php echo esc_html($question); ?>
            </summary>
            <div class="faq-body pt-2" id="faq-answer-<?php echo $faq_id; ?>" aria-labelledby="faq-question-<?php echo $faq_id; ?>">
                <h3><?php echo esc_html($question); ?></h3>
                <?php echo apply_filters('the_content', $answer); ?>
            </div>
        </details>
        <?php
    endwhile;
    
    wp_reset_postdata();
    
    // Return the buffered content
    return ob_get_clean();
}
add_shortcode('faq_list', 'display_faq_shortcode');