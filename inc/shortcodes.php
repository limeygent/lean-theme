<?php
/**
 * Filename: shortcodes.php
 * Purpose: Modular shortcode loader
 *
 * Comment out any line below to disable that shortcode group.
 * Each file is self-contained with its own shortcode registrations.
 */

// Business info: [business_name], [business_phone], [business_phone_link], etc.
require_once __DIR__ . '/shortcodes/business-info.php';

// Blog: [blog_featured_image], [blog_review_notice], [blog_post_interlink], [latest_blog_post]
require_once __DIR__ . '/shortcodes/blog.php';

// FAQs: [faq_list]
require_once __DIR__ . '/shortcodes/faqs.php';

// Testimonials: [testimonials]
require_once __DIR__ . '/shortcodes/testimonials.php';

// ──────────────────────────────────────────────────────────────────────────────
// DISABLED SHORTCODES (uncomment to enable)
// ──────────────────────────────────────────────────────────────────────────────

// Maps: [map_embed]
// require_once __DIR__ . '/shortcodes/maps.php';
