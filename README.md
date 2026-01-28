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
- **Bootstrap 5.3.3** - Included locally, no CDN dependency

## Installation

### Option A: Standalone Theme (Greenfield Sites)

1. Download or clone this repository
2. Upload to `/wp-content/themes/lean-theme/`
3. Activate in **Appearance > Themes**
4. Configure settings in **Appearance > Lean Theme**

### Option B: Add to Existing Theme

1. Download or clone this repository
2. Copy contents into a subfolder of your theme: `/wp-content/themes/your-theme/lean/`
3. Add this line to your theme's `functions.php`:

```php
require_once get_template_directory() . '/lean/lean-loader.php';
```

4. Configure settings in **Appearance > Lean Theme**
5. Edit individual pages and select **"Lean Page"** from the Template dropdown to migrate them

**Example structure after integration:**
```
your-theme/
├── functions.php      # Add the require_once line
├── style.css          # Your existing styles
├── ... other files    # Your existing theme files
└── lean/              # Lean subfolder
    ├── lean-loader.php
    ├── inc/
    ├── template-parts/
    └── css/
```

**Note:** When used with an existing theme, Lean provides all functionality (SEO, forms, shortcodes, settings) without overriding your theme's templates or navigation menus. Migrate pages gradually by changing their template.

## Settings

All business information is configured in **Appearance > Lean Theme**:

| Tab | Settings |
|-----|----------|
| Business Info | Name, Phone, Address, Google Maps CID |
| Appearance | Logo URL, Brand Colors |
| Analytics | GA4 Measurement ID, MS Clarity ID |
| Contact Form | Recipient emails, Success/Error messages |
| Shortcodes | Reference of all available shortcodes |

## Customizing Brand Colors

The theme uses CSS variables for all brand colors. Configure them in **Appearance > Lean Theme > Appearance**:

| Setting | CSS Variables Generated | Used By |
|---------|------------------------|---------|
| Primary Color | `--brand`, `--brand-dark`, `--brand-darker`, `--brand-rgb` | Buttons, hero overlay, links, footer text |
| Secondary Color | `--accent` | Callout borders, accent elements |
| Footer Color | `--footer-bg` | Footer background |

**Example:** Setting Primary Color to `#005395` automatically generates:

```css
:root {
  --brand: #005395;
  --brand-dark: #00477e;    /* 15% darker */
  --brand-darker: #003a6b;  /* 25% darker */
  --brand-rgb: 0, 83, 149;  /* For rgba() usage */
}
```

**For additional customizations**, add CSS to `css/lean-pages.css`:

```css
/* Override or extend brand variables */
:root {
  --accent: goldenrod;
  --footer-text: #cccccc;
}

/* Site-specific component styles */
.my-custom-section {
  background: var(--brand);
  color: white;
}
```

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
├── lean-loader.php     # Main loader (include this from existing themes)
├── functions.php       # Standalone theme entry point
├── style.css           # Theme declaration + styles
├── index.php           # Fallback template (required by WP)
├── assets/
│   └── fonts/
│       └── roboto/     # Self-hosted Roboto fonts
├── css/
│   ├── bootstrap.css   # Bootstrap 5.3.3
│   └── lean-pages.css  # Custom page styles
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
- PHP 7.4+
- ACF Pro (optional, for hero images)

## License

GPL v2 or later
