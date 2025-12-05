<?php

// Disable default WP sitemaps
add_filter( 'wp_sitemaps_enabled', '__return_false' );

// ─── Sitemap index ─────────────────────────────────────────────────────────
function pageone_generate_sitemap_index() {
    header( 'Content-Type: application/xml; charset=utf-8' );
    $base = home_url( '/' );

    echo '<?xml version="1.0" encoding="UTF-8"?>';
	// need to echo <?xml-stylesheet type="text/xsl" href="' . esc_url($xsl) 
    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        echo '<sitemap><loc>' . esc_url( $base . 'post-sitemap.xml' ) . '</loc></sitemap>';
        echo '<sitemap><loc>' . esc_url( $base . 'page-sitemap.xml' ) . '</loc></sitemap>';
    echo '</sitemapindex>';
    exit;
}
function pageone_sitemap_index_check() {
    $uri = untrailingslashit( $_SERVER['REQUEST_URI'] );
    if ( $uri === '/sitemap_index.xml' ) {
        pageone_generate_sitemap_index();
    }
}
add_action( 'init', 'pageone_sitemap_index_check' );

// ─── Posts sitemap ─────────────────────────────────────────────────────────
function pageone_generate_posts_sitemap() {
    header( 'Content-Type: application/xml; charset=utf-8' );

    $posts = get_posts( [
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ] );
    usort( $posts, function( $a, $b ) {
        return strcmp( get_permalink( $a->ID ), get_permalink( $b->ID ) );
    } );

	echo '<?xml version="1.0" encoding="UTF-8"?>';
	// need to echo <?xml-stylesheet type="text/xsl" href="' . esc_url($xsl) 
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ( $posts as $p ) {
            if ( get_post_meta( $p->ID, '_pageone_meta_noindex', true ) ) {
                continue;
            }
            echo '<url>';
                echo '<loc>'     . esc_url( get_permalink( $p->ID ) ) . '</loc>';
                echo '<lastmod>' . esc_html( get_the_modified_time( 'c', $p->ID ) ) . '</lastmod>';
            echo '</url>';
        }
    echo '</urlset>';
    exit;
}
function pageone_posts_sitemap_check() {
    $uri = untrailingslashit( $_SERVER['REQUEST_URI'] );
    if ( $uri === '/post-sitemap.xml' ) {
        pageone_generate_posts_sitemap();
    }
}
add_action( 'init', 'pageone_posts_sitemap_check' );

// ─── Pages sitemap ─────────────────────────────────────────────────────────
function pageone_generate_pages_sitemap() {
    header( 'Content-Type: application/xml; charset=utf-8' );

	// Pull both Pages and Locations (published only)
	$pages = get_posts( [
		'post_type'      => [ 'page', 'locations' ],
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	] );

	// Sort by permalink for a stable, readable order
	usort( $pages, function( $a, $b ) {
		return strcmp( get_permalink( $a->ID ), get_permalink( $b->ID ) );
	} );

	echo '<?xml version="1.0" encoding="UTF-8"?>';
	// need to echo <?xml-stylesheet type="text/xsl" href="' . esc_url($xsl) 
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ( $pages as $page ) {
            if ( get_post_meta( $page->ID, '_pageone_meta_noindex', true ) ) {
                continue;
            }
            echo '<url>';
                echo '<loc>'     . esc_url( get_permalink( $page->ID ) ) . '</loc>';
                echo '<lastmod>' . esc_html( get_the_modified_time( 'c', $page->ID ) ) . '</lastmod>';
            echo '</url>';
        }
    echo '</urlset>';
    exit;
}
function pageone_pages_sitemap_check() {
    $uri = untrailingslashit( $_SERVER['REQUEST_URI'] );
    if ( $uri === '/page-sitemap.xml' ) {
        pageone_generate_pages_sitemap();
    }
}
add_action( 'init', 'pageone_pages_sitemap_check' );