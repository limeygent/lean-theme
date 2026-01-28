<?php
/**
 * c20 functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package c20
 */

if ( ! function_exists( 'c20_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function c20_setup() {
		/*
		 * Make theme available for translation.a
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on c20, use a find and replace
		 * to change 'c20' to the name of your theme in all the template files.
		 */
		load_theme_textdomain( 'c20', get_template_directory() . '/languages' );

		// Add default posts and comments RSS feed links to head.
		//add_theme_support( 'automatic-feed-links' );
		remove_theme_support( 'widgets-block-editor' );


		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		// Simon removed 1/3/25
		// add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		// This theme uses wp_nav_menu() in one location.
		register_nav_menus( array(
			'menu-1' => esc_html__( 'Primary', 'c20' ),
			'menu-2' => esc_html__( 'Secondary', 'c20' ),
			'menu-3' => esc_html__( 'Footer', 'c20' ),
		) );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		) );

		// Set up the WordPress core custom background feature.
		add_theme_support( 'custom-background', apply_filters( 'c20_custom_background_args', array(
			'default-color' => 'ffffff',
			'default-image' => '',
		) ) );

		// Add theme support for selective refresh for widgets.
		add_theme_support( 'customize-selective-refresh-widgets' );

		/**
		 * Add support for core custom logo.
		 *
		 * @link https://codex.wordpress.org/Theme_Logo
		 */
		add_theme_support( 'custom-logo', array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		) );

		add_image_size( 'page-thumbnail', 630);
		add_image_size( 'gallery-thumbnail', 240);
		add_image_size( 'post-thumbnail', 630);
		
		// add_option( 'c20_version' , false ); 
	}
endif;
add_action( 'after_setup_theme', 'c20_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function c20_content_width() {
	// This variable is intended to be overruled from themes.
	// Open WPCS issue: {@link https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/issues/1043}.
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$GLOBALS['content_width'] = apply_filters( 'c20_content_width', 640 );
}
add_action( 'after_setup_theme', 'c20_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function c20_widgets_init() {

	register_sidebar( array(
		'name'          => esc_html__( 'Blog Sidebar', 'c20' ),
		'id'            => 'sidebar-0',
		'description'   => esc_html__( 'Add widgets here.', 'c20' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h4 class="widget-title">',
		'after_title'   => '</h4>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Primary Sidebar', 'c20' ),
		'id'            => 'sidebar-1',
		'description'   => esc_html__( 'Add widgets here.', 'c20' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h4 class="widget-title">',
		'after_title'   => '</h4>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Right Sidebar 2', 'c20' ),
		'id'            => 'sidebar-2',
		'description'   => esc_html__( 'Add widgets here.', 'c20' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h4 class="widget-title">',
		'after_title'   => '</h4>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Right Sidebar 3', 'c20' ),
		'id'            => 'sidebar-3',
		'description'   => esc_html__( 'Add widgets here.', 'c20' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h4 class="widget-title">',
		'after_title'   => '</h4>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Right Sidebar 4', 'c20' ),
		'id'            => 'sidebar-4',
		'description'   => esc_html__( 'Add widgets here.', 'c20' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h4 class="widget-title">',
		'after_title'   => '</h4>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Right Sidebar 5', 'c20' ),
		'id'            => 'sidebar-5',
		'description'   => esc_html__( 'Add widgets here.', 'c20' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h4 class="widget-title">',
		'after_title'   => '</h4>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Right Sidebar 6', 'c20' ),
		'id'            => 'sidebar-6',
		'description'   => esc_html__( 'Add widgets here.', 'c20' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h4 class="widget-title">',
		'after_title'   => '</h4>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Right Sidebar 7', 'c20' ),
		'id'            => 'sidebar-7',
		'description'   => esc_html__( 'Add widgets here.', 'c20' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h4 class="widget-title">',
		'after_title'   => '</h4>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Right Sidebar 8', 'c20' ),
		'id'            => 'sidebar-8',
		'description'   => esc_html__( 'Add widgets here.', 'c20' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h4 class="widget-title">',
		'after_title'   => '</h4>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Footer 1', 'c20' ),
		'id'            => 'footer-1',
		'description'   => esc_html__( 'Add widgets here.', 'c20' ),
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h6 class="widget-title">',
		'after_title'   => '</h6>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Footer 2', 'c20' ),
		'id'            => 'footer-2',
		'description'   => esc_html__( 'Add widgets here.', 'c20' ),
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h6 class="widget-title">',
		'after_title'   => '</h6>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Footer 3', 'c20' ),
		'id'            => 'footer-3',
		'description'   => esc_html__( 'Add widgets here.', 'c20' ),
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h6 class="widget-title">',
		'after_title'   => '</h6>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Footer 4', 'c20' ),
		'id'            => 'footer-4',
		'description'   => esc_html__( 'Add widgets here.', 'c20' ),
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h6 class="widget-title">',
		'after_title'   => '</h6>',
	) );
}

add_action( 'widgets_init', 'c20_widgets_init' );

function c20_register_scripts() {

	$has_animation 	= c20_cmb2_get_general('general_is_animation');
	$is_dev 		= c20_cmb2_get_general('general_on_dev'); 
	$option_version = (float) c20_cmb2_get_general('general_version');

	// $version = time() + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
	$version = '1.779';

	$temp_dir 	= get_template_directory_uri();
	$assets_dir = $temp_dir.'/assets';
	$css_dir 	= $assets_dir.'/css';
	$libs_dir 	= $assets_dir.'/libs';


	wp_register_style( 'c20_bs', $libs_dir.'/bootstrap/css/bootstrap-custom-min.css', array(), null, 'all' );

	wp_register_style( 'slick-main', $libs_dir.'/slick/slick-main.css', array(), null, 'all' );

	wp_register_style( 'aos', $libs_dir.'/aos/aos.css', array(), null, 'all' );
	
	wp_register_style( 'c20_settings', $css_dir.'/settings.css', array(), '10.1.5', 'all' );
	wp_register_style( 'c20_header', $css_dir.'/header.css', array(), '10.1.3', 'all' );
	wp_register_style( 'c20_main', $css_dir.'/main.css', array(), '10.1.3', 'all' );
    
    wp_register_style( 'c20_footer', $css_dir.'/footer.css', array(), $version, 'all' );

    // wp_register_style( 'c20_all', $css_dir.'/all.min.css', array(), $version, 'all' );

    wp_register_style( 'c20_page', $css_dir.'/page.css', array(), $version, 'all' );

    if(empty($has_animation)) {
		wp_enqueue_style( 'aos');
    }

	wp_enqueue_style( 'c20_bs');

	wp_enqueue_style( 'slick-main');	

	wp_enqueue_style( 'c20_settings');

	wp_enqueue_style( 'c20_header');

	wp_enqueue_style( 'c20_main');


	if(!is_front_page() ) {
		wp_enqueue_style( 'c20_page');
	}

	wp_enqueue_style( 'c20_footer');

	wp_enqueue_style( 'c20-style', get_stylesheet_uri(), array(), $version);


	/*Register Scripts*/
	wp_register_script( 'aos_js', $libs_dir.'/aos/aos.js', array(), null, true );
	// wp_register_script( 'yt_js', $assets_dir.'/js/youtube-background.js', array(), null, true );
	wp_register_script( 'bs_js', $libs_dir.'/bootstrap/js/bootstrap.js', array('jquery'), null, true );
	wp_register_script( 'slick', $libs_dir.'/slick/slick.min.js', array('jquery'), null, true );
	wp_register_script( 'c20_main', $assets_dir.'/js/common.js', array('jquery'), null, true );
	wp_register_script( 'c20_scripts', $assets_dir.'/js/main.js', array('jquery'), '', true );

	if(empty($has_animation)) {
		wp_enqueue_script('aos_js');
		wp_localize_script( 'c20_scripts','c20Animate', 'yes');
	}

	wp_enqueue_script('bs_js');
	wp_enqueue_script('slick');

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	wp_enqueue_script('c20_scripts');

}
add_action( 'wp_enqueue_scripts', 'c20_register_scripts' );


add_action( 'wp_enqueue_scripts', 'c20_register_scripts_final', 11 );

function c20_register_scripts_final() {

	if( !is_front_page() ) {
		// wp_deregister_style('nk-awb');
		// wp_deregister_script( 'nk-awb' );
		// wp_deregister_script( 'jarallax-video' );
		// wp_deregister_script( 'jarallax' );
	}

}


function c20_admin_script(){

	$css_dir = get_template_directory_uri().'/assets/css';

	wp_register_style( 'c20_cmb2', $css_dir.'/c20-cmb2.css',array('cmb2-styles'), '1.1' , 'all' );

	if(is_admin()) {
		wp_enqueue_style( 'c20_cmb2' );
	}
}
add_action( 'admin_enqueue_scripts','c20_admin_script' );



if ( file_exists( get_template_directory() . '/inc/meta-fields/init.php' ) ) {
	require_once get_template_directory() . '/inc/meta-fields/init.php';
	require_once get_template_directory() . '/inc/meta-fields.php';

}


/**
 * Implement the Custom Header feature.
 */
// require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
// require get_template_directory() . '/inc/customizer.php';

// require get_template_directory() . '/inc/gutenburg/gutenburg.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}

function c20_search_form( $form ) { 
     $form = '<form role="search" method="get" class="search-form" action="' . esc_url( home_url( '/' ) ) . '">
                <label>
                    <span class="screen-reader-text">' . _x( 'Search for:', 'label' ) . '</span>
                    <input type="search" class="search-field" placeholder="' . esc_attr_x( 'Search The Blog', 'placeholder' ) . '" value="' . get_search_query() . '" name="s" />
                </label>
                <input type="submit" class="search-submit" value="Search" />
            </form>';
     return $form;
}
 
add_filter( 'get_search_form', 'c20_search_form' );

function c20_get_alt($url){
	$alt = '';
	if($url) {
		$get_image_id = attachment_url_to_postid($url);
		$alt = get_post_meta($get_image_id, '_wp_attachment_image_alt', true );
		return  $alt;
	}
	return;
}


add_filter( 'walker_nav_menu_start_el', 'c20_add_arrow_to_dorpdown_menu',10,4);
function c20_add_arrow_to_dorpdown_menu( $item_output, $item, $depth, $args ){

    if( ( 'menu-1' == $args->theme_location || 'menu-2' == $args->theme_location ) && $depth == 0 ){

    	$item_output = '<div class="c20-nav-item-wrap">'.$item_output;

		if (in_array('menu-item-has-children', $item->classes)) {
        	$item_output .= '<span class="nav-arrow"></span>';
		}

    	$item_output .= '</div>';

    }

    return $item_output;
}

add_filter('widget_text', 'do_shortcode');





add_shortcode( 'c20_social', 'c20_social_profiles' );

function c20_social_profiles($atts){

    extract( shortcode_atts( array(
        'class'   	=> '',
    ),  $atts));

	$img_dir = get_template_directory_uri().'/assets/img/social-icons';

	$facebook 	= c20_cmb2_get_social('social_facebook_url'); 
	$twitter 	= c20_cmb2_get_social('social_twitter_url'); 
	$yelp 		= c20_cmb2_get_social('social_yelp_Url'); 
	$gmb 		= c20_cmb2_get_social('social_gmb_url'); 
	$instag 	= c20_cmb2_get_social('social_ig_url'); 

	$data = '';

	ob_start(); ?>
	<?php if($facebook || $twitter || $yelp || $gmb || $instag) : ?>

	<ul class="c20_social <?php echo $class; ?>">

		<?php if($facebook): ?>
			<li class="icon-fb">
				<a href="<?php echo esc_url( $facebook ); ?>" target="_blank">
					<img src="<?php echo $img_dir; ?>/icon-fb.svg" alt="Facebook">
				</a>
			</li>
		<?php endif; ?>

		<?php if($twitter): ?>
			<li class="icon-fb">
				<a href="<?php echo esc_url( $twitter ); ?>" target="_blank">
					<img src="<?php echo $img_dir; ?>/icon-tw.svg" alt="Twitter">
				</a>
			</li>
		<?php endif; ?>

		<?php if($yelp): ?>
			<li class="icon-yelp">
				<a href="<?php echo esc_url( $yelp); ?>" target="_blank">
					<img src="<?php echo $img_dir; ?>/icon-yelp.svg" alt="Yelp">
				</a>
			</li>
		<?php endif; ?>

		<?php if($gmb): ?>
			<li class="icon-map">
				<a href="<?php echo esc_url( $gmb ); ?>" target="_blank">
					<!--<img src="<?php echo $img_dir; ?>/icon-gmb.svg" alt="GMB">-->
					 <img src="<?php echo $img_dir; ?>/google.svg" alt="GMB"> 
				</a>
			</li>
		<?php endif; ?>
		
		<?php if($instag): ?>
			<li class="icon-ig">
				<a href="<?php echo esc_url( $instag ); ?>" target="_blank">
					 <img src="<?php echo $img_dir; ?>/instagram.svg" alt="instagram"> 
				</a>
			</li>
		<?php endif; ?>

	</ul>

	<?php endif;

	$output = ob_get_contents(); $data .= $output; ob_get_clean();
	return $data;
}



// function wpb_imagelink_setup() {
//     $image_set = get_option( 'image_default_link_type' );
     
//     if ($image_set !== 'none') {
//         update_option('image_default_link_type', 'none');
//     }
// }
// add_action('admin_init', 'wpb_imagelink_setup', 10);


add_shortcode( 'c20_slogan', 'c20_slogan_module' );
function c20_slogan_module(){
	
	$img_dir = get_template_directory_uri().'/assets';

	$slogan_image 	= c20_cmb2_get_components('components_slogan_image'); 
	$slogan_1_title = c20_cmb2_get_components('components_slogan_1_title'); 
	$slogan_1_btn 	= c20_cmb2_get_components('components_slogan_1_btn'); 
	$slogan_1_url 	= c20_cmb2_get_components('components_slogan_1_url'); 
	$slogan_2_title = c20_cmb2_get_components('components_slogan_2_title'); 
	$slogan_2_btn 	= c20_cmb2_get_components('components_slogan_2_btn'); 
	$slogan_2_url 	= c20_cmb2_get_components('components_slogan_2_url'); 

	$data = '';

	ob_start(); ?>

		<div class="slogan-section">
			<div class="bg-white p-3 slogan-section__header">

				<?php if($slogan_image) : ?>
					<div class="mb-3">
						<img class="w-auto" src="<?php echo $slogan_image; ?>" alt="<?php echo $slogan_1_title; ?>" data-aos-offset="50" data-aos="fade-down" data-aos-duration="600" data-aos-delay="100">
					</div>	
				<?php endif; ?>

				<?php if($slogan_1_title):  ?>

				<h2 class="h3 m-0 mb-3 font-700 text-primary" data-aos-offset="50" data-aos="fade-up" data-aos-duration="600" data-aos-delay="100"><?php echo $slogan_1_title; ?></h2>
				<?php endif; ?>

				<?php if($slogan_1_btn) : ?>
					<a href="<?php echo (!empty($slogan_1_url)) ? $slogan_1_url : '#'; ?>" class="btn btn-default" data-aos-offset="50" data-aos="fade-up" data-aos-duration="600" data-aos-delay="100"><?php echo $slogan_1_btn; ?></a>
				<?php endif; ?>

			</div>

			<div class="bg-primary py-5 px-4 slogan-section__footer" data-aos-offset="50" data-aos="fade-up" data-aos-duration="600" data-aos-delay="100">
				<?php if($slogan_2_title): ?>
				<h2 class="h4 lh-16 font-500 mb-4 text-white"><?php echo $slogan_2_title; ?></h2>
				<?php endif; ?>

				<?php if($slogan_2_btn): ?>
					<a href="<?php echo (!empty($slogan_2_url)) ? $slogan_2_url : '#'; ?>" class="btn btn-white"><?php echo $slogan_2_btn; ?></a>
				<?php endif; ?>
				
			</div>
		</div>
	<?php 

	$output = ob_get_contents(); $data .= $output; ob_get_clean();
	return $data;
}



add_shortcode( 'c20_collapse', 'c20_register_accordion' );
function c20_register_accordion($atts){

    extract( shortcode_atts( array(
        'menu_id'   => '',
    ),  $atts));

	$data = '';

	ob_start();


	$menu_ids = explode(',', $menu_id); 

	$i = 0;

	if(!empty($menu_ids)) {

	echo '<div class="c20_collapse_group">';
		foreach ($menu_ids as $nav_id) { 
			$i++;
			$menu_obj = wp_get_nav_menu_object($nav_id);

			echo ($i==1) ? '<div class="c20_collapse c20_collapse_open">' : '<div class="c20_collapse">';
				echo '<div class="c20_collapse_header"><span class="c20_collapse_title">'.$menu_obj->name.'</span><span class="collapse_status"></span></div>';
				
					wp_nav_menu( array(
						'menu'            => $nav_id,
						'container'       => 'div',
						'container_class' => 'c20_collapse_nav',
					) );
				 
			echo '</div>';
		}
		echo '</div>';
	}



	$output = ob_get_contents(); $data .= $output; ob_get_clean();
	return $data;
}


add_shortcode( 'c20_review', 'c20_register_review_module' );
function c20_register_review_module($atts){

	$data = '';
	$img_dir = get_template_directory_uri().'/assets/img/review';

	$review_title 		= c20_cmb2_get_review('review_title'); 
	$review_text 		= c20_cmb2_get_review('review_text'); 
	$review_author 		= c20_cmb2_get_review('review_author'); 
	$bbb_url 			= c20_cmb2_get_social('social_bbb_url'); 
	
	$review_page_link 	= c20_cmb2_get_general('general_review_page');
	$contact_page 		= c20_cmb2_get_general('general_contact_page'); 

	ob_start();

	if($review_title || $review_text) : ?>

		<div class="page-review" data-aos="fade-up" data-aos-duration="700" data-aos-delay="300">
			<?php if($review_title): ?>
<!-- 			<div class="review-header">
				<h2 class="m-0 font-700"><?php echo $review_title; ?></h2>
			</div> -->
			<?php endif; ?>
		
			<?php if($review_text): ?>

				<div class="review-body">
				<!--	<div class="review-star-count">
 						<img src="<?php //echo $img_dir; ?>/star.png" data-aos="fade-left" data-aos-duration="700" data-aos-delay="100" alt="Rating Star">
						<img src="<?php //echo $img_dir; ?>/star.png" data-aos="fade-left" data-aos-duration="700" data-aos-delay="200" alt="Rating Star">
						<img src="<?php //echo $img_dir; ?>/star.png" data-aos="fade-left" data-aos-duration="700" data-aos-delay="300" alt="Rating Star">
						<img src="<?php //echo $img_dir; ?>/star.png" data-aos="fade-left" data-aos-duration="700" data-aos-delay="400" alt="Rating Star">
						<img src="<?php //echo $img_dir; ?>/star.png" data-aos="fade-left" data-aos-duration="700" data-aos-delay="500" alt="Rating Star">
					</div> -->
						
<script async defer id="pulsem-embed-widget-review" src="https://static.speetra.com/embed-pulsemweb-review.js" data-id="f1861f47d3244b4e69e598ca8b200cee95e0e7aae0dbcede7ce64cafb1465f71"></script>
		
					
					<!-- <div class="review-text"><?php echo $review_text; ?></div>

					<?php if($review_author): ?>
						<div class="review-author"><?php echo $review_author; ?></div>
					<?php endif; ?>

				</div>-->
			<?php endif; ?>

			<?php if($bbb_url || $review_page_link || $contact_page) : ?>

				<div class="review-footer px-0">

					<div class="review-footer__left mb-3 mb-xl-0">
						
						<a class="me-2" href="https://www.google.com/maps?cid=3876601864266800055" target="_blank">
							<img src="/wp-content/uploads/2023/08/google-guaranteed-130.png" alt="Google Guaranteed">
						</a>
						
						<?php if($bbb_url): ?>
							<a href="<?php echo esc_url( $bbb_url ); ?>" target="_blank">
								<img src="<?php echo $img_dir; ?>/bbb.png" alt="BBB Logo">
							</a>
						<?php endif; ?>
					</div>

					<div class="review-footer__right">
						<?php if($review_page_link): ?>
							<span  data-aos="fade-left" data-aos-duration="1200" data-aos-delay="300">
								<a href="<?php echo esc_url( $review_page_link ); ?>" class="btn btn-white mx-2 mb-3 mb-xl-0">Read More Reviews</a>
							</span>
						<?php endif; ?>

						<?php if($contact_page): ?>
							<span data-aos="fade-left" data-aos-duration="1200" data-aos-delay="400">
								<a href="<?php echo esc_url( $contact_page ); ?>" class="btn mx-2 mb-3 mb-xl-0">Request Service</a>
							</span>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>

	<?php endif;

	$output = ob_get_contents(); $data .= $output; ob_get_clean();
	return $data;
}





add_shortcode( 'c20_card', 'c20_text_highlight_cb' );

function c20_text_highlight_cb($atts, $content = null){

    extract( shortcode_atts( array(
        'align'		=> 'left',
        'text'		=> '',
    ),  $atts));


    $img_dir = get_template_directory_uri().'/assets/img/control';


    $alignment = '';

    switch ($align) {

        case 'right':
            $alignment = 'text-right';
            break;

        case 'center':
            $alignment = 'text-center';
            break;

        default:
            $alignment = 'text-left';
            break;
    }

    $data = '';

    ob_start();

    if($text || $content) : ?>

        <div class="c20-quote font-500 <?php echo $alignment; ?>">
        	<div>
            	<?php echo !empty($content) ? do_shortcode($content) : $text; ?>
        	</div>
        </div>

    <?php endif;

    $output = ob_get_contents(); $data .= $output; ob_get_clean();
    return $data;
}

add_shortcode('future_date', 'future_date_shortcode');
// [future_date days="14" format="m/d/Y"]
function future_date_shortcode($atts) {
    // Extract and set default attributes
    $atts = shortcode_atts(
        array(
            'days' => 7,         // Default to 7 days
            'format' => 'F j, Y', // Default format: Month Day, Year (e.g., April 17, 2025)
        ), 
        $atts, 
        'future_date'
    );
    
    // Calculate the future date
    $future_date = date($atts['format'], strtotime('+' . intval($atts['days']) . ' days'));
    
    // Return the formatted date
    return $future_date;
}

add_shortcode( 'coupon', 'c20_register_coupon' );
function c20_register_coupon($atts){
	$data = '';
	$img_dir = get_template_directory_uri().'/assets/img';
	
	// Calculate expiration date - 7 days from now
	$expiration_date = date('F j, Y', strtotime('+7 days'));
	
	extract( shortcode_atts( array(
		'title'     		=> '',
		'description'     	=> '',
		'tag'     			=> 'h2',
		'disclaimer'  		=> 'Cannot be combined with any other offer. Limit one per customer. Must be presented at time of service.'
	),  $atts));

	// [coupon title="" suffix="" subtitle="" tag="" discount="yes" description="" disclaimer=""]

	ob_start(); ?>
	
	<div class="c20-coupon-wrap print-coupon">
		<div class="c20-coupon">
			<img src="<?php echo $img_dir; ?>/coupon-bg.png" alt="logo">

			<div class="coupon-inner">
				<?php if($title) : ?>
					<div class="coupon-title coupon-title-<?php echo $tag; ?>"><?php echo $title; ?></div>
				<?php endif; ?>

				<?php if($description) : ?>
					<p class="coupon-description"><?php echo $description; ?></p>
				<?php endif; ?>
				
				<p class="coupon-expiration"><strong>Expires</strong> <?php echo $expiration_date; ?></p>

			
			</div>
			<a class="btn nprint desktop mb-4" href="/schedule-now/">Request Estimate</a>
			<?php $callnow = c20_cmb2_get_general('general_phone'); ?>
			<a class="btn nprint mobile mb-4" href="tel:<?php echo $callnow; ?>">Call Now</a>
			
			<?php if($disclaimer) : ?>
				<div class="coupon-disclaimer"><?php echo $disclaimer; ?></div>
			<?php endif; ?>	
		</div>
	</div>
		
	
		
	<?php  

	$output = ob_get_contents(); $data .= $output; ob_get_clean();
	return $data;
}




add_filter( 'excerpt_length', 'c20_custom_excerpt_length', 999 );
function c20_custom_excerpt_length( $length ) {

	if(is_home() && !is_front_page()){
		return 45;

	} else {
		
		return 18;
	}
}


add_filter('excerpt_more', 'c20_custom_excerpt_more');
function c20_custom_excerpt_more( $more ) {
    return '...';
}




function c20_plain_phone_no($phone){

	$phone_format = '';

	if(empty($phone)){
		return;
	}

	return $phone_format = preg_replace('/\D+/', '', $phone);
}

add_shortcode( 'c20_nap', 'c20_register_nap' );
function c20_register_nap($atts){

    extract( shortcode_atts( array(
        'phone'   	=> '',
        'fax'   	=> '',
        'address'   => '',
    ),  $atts));

    $img_dir = get_template_directory_uri().'/assets/img/nap';

	$data = '';

	ob_start(); ?>

		<?php if( $address || $fax || $phone) : ?>

			<div class="c20-nap-items">
				<?php if(!empty($address)) : ?>
					<div style="background-image: url(<?php echo $img_dir; ?>/nap-marker.png);" class="mb-3 c20-nap-item c20-nap-address"><?php echo $address; ?></div>
				<?php endif; ?>

				<?php if(!empty($phone)) : ?>
					<div style="background-image: url(<?php echo $img_dir; ?>/nap-phone.png);" class="mb-3 c20-nap-item c20-nap-phone">Tel: <a href="tel:<?php echo c20_plain_phone_no($phone); ?>"><?php echo $phone; ?></a></div>
				<?php endif; ?>

				<?php if(!empty($fax)) : ?>
					<div style="background-image: url(<?php echo $img_dir; ?>/nap-fax.png);" class="c20-nap-item c20-nap-fax">Fax: <a href="fax:<?php echo c20_plain_phone_no($phone); ?>"><?php echo $phone; ?></a></div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	<?php
	$output = ob_get_contents(); $data .= $output; ob_get_clean();
	return $data;
}

// add_shortcode( 'nap_email', 'c20_nap_email' );
function c20_nap_phone($atts){

    extract( shortcode_atts( array(
        'email'   => '',
    ),  $atts));

	$data = '';

	if(empty($no)){
		return;
	}

	ob_start(); ?>
		<div class="c20-nap-item c20-nap-phone"><a href="tel:<?php echo c20_plain_phone_no($no); ?>"><?php echo $no; ?></a></div>
	<?php
	$output = ob_get_contents(); $data .= $output; ob_get_clean();
	return $data;
}


add_shortcode( 'phone', 'c20_common_phone_no' );
function c20_common_phone_no($atts){

	$phone_raw = c20_cmb2_get_general('general_phone'); 

    extract( shortcode_atts( array(
        'ga'     => false,
        'btn'  	=> '',
        // 'label' => '',
        'class'  => 'c20-phone',
    ),  $atts));


	if(empty($phone_raw)) {
		return;
	}

	$phone_txt = $phone_raw;

	

	if($btn) {
		$phone_txt = $btn;
	}

	$phone = c20_plain_phone_no($phone_raw);

	if($ga) {
    	return "<a href=\"tel:{$phone}\" class=\"{$class}\" onclick=\"ga('send',' event', 'phone', 'call');\">{$phone_txt}</a>";

	} else {

    	return "<a href=\"tel:{$phone}\" class=\"{$class}\">{$phone_txt}</a>";
	}
}



add_shortcode( 'c20_year', 'c20_show_date' );
function c20_show_date(){
    return date('Y');
}



function c20_sanitize_text_callback( $value, $field_args, $field ) {
    $value = strip_tags( $value, '<p><a><br><br/>' );
    return $value;
}


add_shortcode( 'tips', 'c20_expert_tips' );
function c20_expert_tips($atts){
	$title 	= c20_cmb2_get_tips('tips_title'); 
	$expert = c20_cmb2_get_tips('tips_name'); 
	$image 	= c20_cmb2_get_tips('tips_thumb'); 
	$text 	= c20_cmb2_get_tips('tips_text'); 

	extract( shortcode_atts( array(
		'title' 	=> $title,
		'text' 		=> $text,
		'image' 	=> $image,
		'expert' 	=> $expert,
	),  $atts));
	
	$data = '';
	ob_start();  
	$temp_dir = get_template_directory_uri();  

	if($text): ?>

		<div class="c20-tips-module" data-aos="fade-up" data-aos-duration="700" data-aos-delay="300">
			<div class="tips-header"><?php echo $title ? esc_html($title) : 'Exert Tips'; ?></div>
			<div class="tips-body">
				<?php if($image && ($image !== 'no') ): ?>
				<div class="tips-thumb">
					<img src="<?php echo esc_url($image); ?>" alt="<?php echo $expert ? esc_attr($expert) : esc_attr($title); ?>">
				</div>
				<?php endif; ?>
				
				<div class="tips-box">
					<div class="tips-text"><?php echo $text; ?></div>
					<?php if($expert): ?>
						<div class="tips-author"><?php echo esc_html($expert); ?></div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	<?php endif;
	
	$output = ob_get_contents(); $data .= $output; ob_get_clean();
	return $data;
}


function c20_cmb2_url_data($url) {
	if(!$url) {
		return;
	}
	$url = wp_parse_args( $url, array(
		'post_id'		=> '',
		'custom_url'	=> '',
	));
	return ($url['custom_url']) ? $url['custom_url'] : get_permalink($url['post_id']);
}

function c20_get_bg_image($url, $lazy = null){
	if(empty($url)) {
		return;
	}
	if($lazy) {
		return 'data-background-image="'.$url.'"';
	} else {
		return 'style="background-image: url('.$url.');"';
	}
}

add_shortcode( 'testimonial', 'c20_testimonial_cb' );
function c20_testimonial_cb($atts){

	extract( shortcode_atts( array(
		'text' => '',
		'name' => '',
	),  $atts));
	
	$data = '';
	ob_start();

	$temp_dir = get_template_directory_uri();  

	if($text): ?>

	<blockquote class="c20-testimonial">
		“<?php echo $text; ?>”
		<?php if($name) : ?>
			<span class="mb-0 pt-4 h5 d-block text-light">- <?php echo $name; ?></span>
		<?php endif; ?>
	</blockquote>

	<?php endif;
	
	$output = ob_get_contents(); $data .= $output; ob_get_clean();
	return $data;
}





function c20_keyword_lists_for_block() {

	return array( 'outsource', 'Madam', 'SEO', 'long term relationship', 'seo', 'SEO', 'Seo', 'backlink', 'ranking', '404', 'designer', 'keyword', 'presence', 'marketing', 'sex', 'xxx', 'porn', 'ppc', 'leads', 'pay par click', 'sponsored', 'plugin', 'spam', 'explainer video', 'Virtual Assistants', 'Virtual Assistant', 'Live Chat Agents', 'Live Chat Agent', 'visitors', 'traffic', 'visitor');
}


function c20_check_keyworkds($haystack, $needle) {
	if(!is_array($needle)) $needle = array($needle);
	foreach($needle as $what) {
		if(($pos = stripos($haystack, $what))!==false) return true;
	}
	return false;
}


add_action('gform_pre_submission_4', 'c20_gform_block_spam_keywords_form_4'); 
function c20_gform_block_spam_keywords_form_4($validation_result){
	
	$blocked_keyword = c20_keyword_lists_for_block();

	$stop_id = array();

	if(isset($_POST) && !empty($_POST)) {
		foreach($_POST as $key => $value) {
			if(c20_check_keyworkds($value, $blocked_keyword)) {
				$stop_id[] = $key;
			}
		}
	}

	if(sizeof($stop_id) > 0) {
		$validation_result['is_valid'] = false;
		$_POST['input_12'] = "No";
	}
}


add_action('gform_pre_submission_2', 'c20_gform_block_spam_keywords_form_2'); 
function c20_gform_block_spam_keywords_form_2($validation_result){
	
	$blocked_keyword = c20_keyword_lists_for_block();

	$stop_id = array();

	if(isset($_POST) && !empty($_POST)) {
		foreach($_POST as $key => $value) {
			if(c20_check_keyworkds($value, $blocked_keyword)) {
				$stop_id[] = $key;
			}
		}
	}

	if(sizeof($stop_id) > 0) {
		$validation_result['is_valid'] = false;
		$_POST['input_8'] = "No";
	}
}

function cc_mime_types($mimes) {
  $mimes['svg'] = 'image/svg+xml';
  return $mimes;
}

add_filter('upload_mimes', 'cc_mime_types');


// added by Simon Cornelius December 2024
// Add a meta box for entering a city
function staggs_add_city_meta_box() {
    add_meta_box(
        'staggs_city_meta_box', // ID of the meta box
        'City', // Title of the meta box
        'staggs_city_meta_box_callback', // Callback function to display the meta box
        'page', // Post type where the meta box appears
        'side', // Context: 'side', 'normal', or 'advanced'
        'high' // Priority
    );
}
add_action('add_meta_boxes', 'staggs_add_city_meta_box');

// Callback function to display the meta box
function staggs_city_meta_box_callback($post) {
    // Retrieve the current value of the city meta field
    $city = get_post_meta($post->ID, '_staggs_city', true);
    
    echo '<label for="staggs_city_field">Enter City:</label>';
    echo '<input type="text" name="staggs_city_field" id="staggs_city_field" value="' . esc_attr($city) . '" style="width: 100%;" />';
}

// Save the city meta field data
function staggs_save_city_meta_box($post_id) {
    // Check if the 'staggs_city_field' is set
    if (isset($_POST['staggs_city_field'])) {
        update_post_meta($post_id, '_staggs_city', sanitize_text_field($_POST['staggs_city_field']));
    }
}
add_action('save_post', 'staggs_save_city_meta_box');

// Shortcode to display the city
function staggs_city_shortcode($atts) {
    global $post;
    
    // Retrieve the city meta field value
    $city = get_post_meta($post->ID, '_staggs_city', true);
    
    if (!empty($city)) {
        return esc_html($city); // Return the city if it exists
    }
    
    return ''; // Return nothing if no city is set
}
add_shortcode('staggs_city', 'staggs_city_shortcode');
