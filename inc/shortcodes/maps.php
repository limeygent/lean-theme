<?php
/**
 * Shortcode: [map_embed oq="your original query"]
 *
 * - oq               = original query (e.g. "bail bonds")
 * - business_name    = get_option('business_name')
 * - business_phone   = get_option('business_phone')
 * - google_maps_cid  = get_option('google_maps_cid')
 * - google_gmb_image_url = get_option('google_gmb_image_url')
 * - location_name    = get_field('location_name')  (ACF)
 * - page_cid         = get_field('cid')            (ACF)
 */
function map_embed_shortcode( $atts ) {
    // 1) normalize attrs
    $atts = shortcode_atts( array(
        'oq' => '', 
    ), $atts, 'map_embed' );

    // 2) pull in all the bits
    $business_name   = get_option( 'business_name' );
    $business_phone  = get_option( 'business_phone' );
    $business_cid    = get_option( 'google_maps_cid' );
    $gmb_image       = get_option( 'google_gmb_image_url' );

    $location_name   = get_field( 'location_name' );  // e.g. "Riverfront, Dallas TX"
    $page_cid        = get_field( 'cid' );            // city-page CID

    // 3) build map q / oq
    $q_raw   = urlencode( $business_name );
    $oq_full = trim( $atts['oq'] . ' ' . $location_name );
    $oq_raw  = urlencode( $oq_full );

    // 4) iframe src
    $iframe_src = sprintf(
        'https://maps.google.com/maps?q=%1$s&oq=%2$s&cid=%3$s&output=embed&t=h&z=14',
        $q_raw,
        $oq_raw,
        esc_attr( $business_cid )
    );

    // 5) alt text for the GMB image
    $alt_text = sprintf(
        '%1$s near %2$s',
        ucwords( $atts['oq'] ),
        $location_name
    );

	// 6) build the Google Search URL for the “Find the best…” link
	$search_url = sprintf(
		'https://www.google.com/search?q=%1$s&oq=%2$s&rldimm=%3$s&rlst=f#rlfi=hd:;si:%4$s',
		$q_raw,
		$oq_raw,
		esc_attr( $page_cid ),
		esc_attr( $business_cid )
	);

	// 7) link text and phone
	$link_text  = sprintf(
		'Find the best %1$s in %2$s',
		esc_html( $atts['oq'] ),
		esc_html( $location_name )
	);
	$clean_phone = preg_replace( '/\D+/', '', $business_phone );

    // 8) render
    ob_start();
    ?>
    <div class="map-embed">
        <iframe
            src="<?php echo esc_url( $iframe_src ); ?>"
            allowfullscreen="allowfullscreen"
        ></iframe>

        <div class="top-image-overlay">
            <img
                src="<?php echo esc_url( $gmb_image ); ?>"
                alt="<?php echo esc_attr( $alt_text ); ?>"
            />
            <div class="img-alt-text"><?php echo esc_html( $alt_text ); ?></div>
        </div>

		<div class="bottom-info">
			<strong>
				<a href="<?php echo esc_url( $search_url ); ?>">
					<?php echo $link_text; ?>
				</a><br>
				<?php echo esc_html( $business_phone ); ?>
			</strong>
		</div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'map_embed', 'map_embed_shortcode' );
