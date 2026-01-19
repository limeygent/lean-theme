<?php
/**
 * Filename: faqs.php
 * Purpose: FAQ shortcode for displaying FAQ custom post type
 *
 * Shortcodes:
 * - [faq_list]                    - Display all FAQs
 * - [faq_list posts_per_page="10"] - Limit number of FAQs shown
 *
 * Requires: 'faq' custom post type to be registered
 * Uses <details>/<summary> for accessible accordion behavior
 */

add_shortcode('faq_list', 'lean_faq_list_shortcode');

function lean_faq_list_shortcode($atts) {
	$atts = shortcode_atts(array(
		'posts_per_page' => 30,
	), $atts, 'faq_list');

	$args = array(
		'post_type'      => 'faq',
		'posts_per_page' => intval($atts['posts_per_page']),
		'orderby'        => 'menu_order',
		'order'          => 'ASC'
	);

	$faq = new WP_Query($args);

	if (!$faq->have_posts()) {
		return '';
	}

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

	return ob_get_clean();
}
