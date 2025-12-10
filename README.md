# Lean Theme

A lightweight, performance-optimized WordPress theme that bypasses `wp_head()` for maximum speed.

## Features

- **Zero Dependencies** - No parent theme required, just core WordPress
- **Performance First** - Bypasses wp_head() bloat, preloads critical assets
- **Custom Post Types** - Testimonials (built-in)
- **SEO Ready** - Meta box for title/description, Schema.org markup
- **Contact Forms** - Custom form system with database storage, no plugins needed
- **CSS-Only Mobile Menu** - No JavaScript required for navigation
- **Self-Hosted Fonts** - Roboto loaded locally with font-display: swap

## Installation

1. Download or clone this repository
2. Upload to `/wp-content/themes/lean-theme/`
3. Activate in **Appearance > Themes**
4. Configure settings in **Appearance > Lean Theme**

## Settings

All business information is configured in **Appearance > Lean Theme**:

| Tab | Settings |
|-----|----------|
| Business Info | Name, Phone, Address, Google Maps CID |
| Appearance | Logo URL, Brand Colors |
| Analytics | GA4 Measurement ID, MS Clarity ID |
| Contact Form | Recipient emails, Success/Error messages |
| Shortcodes | Reference of all available shortcodes |

## Shortcodes

### Business Information
```
[business_name]
[business_phone]
[business_phone_link]
[business_phone_button class="btn btn-primary"]
[business_address]
[business_full_address]
```

### Content
```
[testimonials num_reviews="6"]
[lean_form]
[map_embed]
[latest_blog_post]
```

## Page Templates

For optimized pages, use the Lean template (`templates/page-lean.php`) which:
- Skips wp_head() entirely
- Preloads hero images, fonts, and CSS
- Loads GA4 and Clarity conditionally
- Includes Schema.org structured data

## File Structure

```
lean-theme/
├── functions.php       # Loader
├── style.css           # Theme declaration + styles
├── index.php           # Fallback template
├── inc/
│   ├── cpts.php        # Custom Post Types
│   ├── seo.php         # Meta boxes & output
│   ├── forms.php       # Contact form system
│   ├── settings.php    # Admin settings page
│   ├── sitemaps.php    # Custom XML sitemaps
│   ├── disable-features.php
│   └── shortcodes/
│       ├── blog.php
│       ├── testimonials.php
│       └── maps.php
└── templates/
    ├── page-lean.php   # Optimized page template
    ├── head.php        # <head> contents
    ├── header.php      # Site header + GA4
    └── footer.php      # Footer + tracking scripts
```

## Requirements

- WordPress 6.0+
- PHP 8.0+
- ACF Pro (optional, for hero images)

## License

GPL v2 or later
