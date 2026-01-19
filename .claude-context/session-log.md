# Lean Theme Session Log

## Session: 2026-01-19

### Summary
Major refactor for modular architecture and Avada child theme compatibility. Created standalone Code Snippets for SEO functionality. Ran Yoast → Lean SEO migration.

### Files Modified & Pushed to GitHub (v1.2.0)

| File | Changes |
|------|---------|
| `bad-ass-optimized.php` | NEW - Page template at root level (matches Avada child) |
| `functions.php` | Added shortcodes loader, migrations include |
| `inc/shortcodes.php` | NEW - Modular shortcode loader |
| `inc/shortcodes/business-info.php` | NEW - Business info shortcodes |
| `inc/shortcodes/faqs.php` | NEW - FAQ shortcode |
| `inc/shortcodes/maps.php` | DELETED - Removed per request |
| `inc/seo.php` | Refactored: pageone_ → lean_ prefix, filterable post types |
| `inc/settings.php` | Removed shortcodes (moved to modular files) |
| `inc/migrations/yoast-to-lean-seo.php` | NEW - Theme-based Yoast migration |
| `css/lean-pages.css` | Added @font-face rules for Roboto (400, 700) |
| `template-parts/lean-head.php` | RENAMED from head.php |
| `template-parts/lean-header.php` | RENAMED from header.php |
| `template-parts/lean-footer.php` | RENAMED from footer.php |
| `template-parts/page-lean.php` | Updated to use lean-* template parts |

### Code Snippets Created
For standalone use with Avada child theme (before full theme switch):

| File | Purpose | Run Where |
|------|---------|-----------|
| `code-snippets/1-lean-seo-admin.php` | SEO meta box in page editor | Admin only |
| `code-snippets/2-lean-seo-output.php` | Output meta tags in `<head>` | Everywhere |
| `code-snippets/3-lean-seo-migration.php` | Yoast → Lean migration tool | Admin only (one-time) |

### Template Parts Renamed
Changed to `lean-*` prefix for Avada child theme compatibility:
- `head.php` → `lean-head.php`
- `header.php` → `lean-header.php`
- `footer.php` → `lean-footer.php`

### Shortcodes Now Modular
```
inc/shortcodes.php          ← Loader (comment out to disable)
inc/shortcodes/
├── business-info.php       ← [business_name], [business_phone], etc.
├── blog.php                ← [blog_featured_image], [blog_review_notice], etc.
├── faqs.php                ← [faq_list]
└── testimonials.php        ← [testimonials]
```

### SEO Refactor
- Function prefix: `pageone_` → `lean_`
- Meta keys: `_pageone_meta_*` → `_lean_meta_*`
- Post types now filterable via `lean_seo_post_types` filter
- Keywords retained for Bing compatibility

### Yoast Migration
- Ran migration via Tools > Yoast → Lean SEO
- All Yoast data copied to Lean meta fields
- Migration Code Snippet can be disabled after verification

### GitHub Status
- Repo: https://github.com/limeygent/lean-theme
- Latest commit: add4eb3
- Version: 1.2.0
- Branch: main

---

## Session: 2026-01-15

### Summary
Updated the GitHub repo (https://github.com/limeygent/lean-theme) with all enhancements from the Avada child theme work. Discussed gradual migration strategy for two sites.

### Files Modified & Pushed to GitHub
| File | Changes |
|------|---------|
| `template-parts/page-lean.php` | Updated paths from templates/ to template-parts/, added blog post conditionals |
| `template-parts/head.php` | Added Font Awesome 6.5.1 CDN |
| `template-parts/header.php` | Full color support, header top bar modes (none/tagline/items), custom items rendering |
| `template-parts/footer.php` | Configurable bg/text colors, dynamic hours and service area |
| `style.css` | v1.1.0 with CSS variables for nav/dropdown colors, header top items styling |
| `inc/settings.php` | All new settings: colors, header modes, business hours, service area, maps embed URL |

### Folder Rename
- Renamed `templates/` → `template-parts/` (WordPress coding standard)

### New Settings Available (Appearance > Lean Theme)
**Business Info Tab:**
- Business hours (HTML textarea)
- Service area + URL
- Google Maps embed URL (priority over CID)

**Appearance Tab:**
- Header Top Bar: Mode selector (none/tagline/items), bg/text colors
- Custom Items: Up to 4 items (badge, icon-box, text, phone-button with FA icons)
- Header Navigation: Main bg, nav text, dropdown bg/text colors
- Footer: Background and text colors
- Brand: Primary color, accent color

### New Shortcodes
- `[business_phone_url]` - Returns just the tel: URL

---

## Current Setup (Executive Blue Pools Site)

### Active Theme: Avada Child
```
avada-child/
├── bad-ass-optimized.php         ← Page template
├── template-parts/
│   ├── lean-head.php
│   ├── lean-header.php
│   └── lean-footer.php
├── css/
│   ├── bootstrap.css
│   └── lean-pages.css
└── assets/
    └── fonts/
        └── roboto/
            ├── roboto-v49-latin-regular.woff2
            └── roboto-v49-latin-700.woff2
```

### Code Snippets Active
1. Business Settings (Appearance > Business Information)
2. Business Shortcodes
3. Lean SEO Admin
4. Lean SEO Output
5. ~~Yoast Migration~~ (disable after use)

### SEO Status
- Yoast still active (for legacy pages)
- Lean SEO working on "Bad Ass Optimized" template pages
- Migration complete - data in `_lean_meta_*` fields

---

## Next Steps
1. Continue migrating pages to "Bad Ass Optimized" template
2. Test thoroughly
3. Deactivate Yoast when confident
4. Eventually switch to lean-theme as active theme
5. Disable duplicate Code Snippets (theme has same functionality)

---

*Last Updated: 2026-01-19*
