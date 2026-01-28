# Lean Theme Session Log

## Session: 2026-01-27

### Summary
Major restructure to support dual-mode usage: standalone theme OR integration into existing themes. Added lean-loader.php as single entry point. Integrated with staggsplumbing site as test case. Fixed PageSpeed issues. Merged comprehensive CSS from Executive Blue Pools project.

### Architecture Changes

**New Dual-Mode Structure:**
```
existing-theme/
├── functions.php        ← Add: require_once .../lean/lean-loader.php
└── lean/                ← Copy lean theme files here
    ├── lean-loader.php  ← Main entry point
    ├── inc/
    ├── template-parts/
    └── css/
```

**Key Constants (defined in lean-loader.php):**
- `LEAN_THEME_DIR` - Absolute path to lean directory
- `LEAN_THEME_URL` - URL to lean directory
- `LEAN_IS_STANDALONE` - true if active theme, false if embedded

### Files Modified

| File | Changes |
|------|---------|
| `lean-loader.php` | NEW - Main entry point, template registration, path detection |
| `functions.php` | Simplified to just include lean-loader.php |
| `template-parts/page-lean.php` | Updated to use lean_get_template_part() |
| `template-parts/lean-head.php` | Fixed asset paths to use LEAN constants, dynamic CSS versioning, async Font Awesome |
| `template-parts/lean-header.php` | Menu location now configurable via settings |
| `template-parts/lean-footer.php` | No changes |
| `inc/settings.php` | Moved to top-level "Theme Settings" menu, added Menu Location dropdown |
| `css/bootstrap.css` | Removed sourcemap reference (was causing 404) |
| `css/lean-pages.css` | Major merge from EBP project + theme variable support |
| `bad-ass-optimized.php` | DELETED - Duplicate of page-lean.php |

### Settings Page Changes
- Moved from **Appearance > Lean Theme** to top-level **Theme Settings** (position 2.1, after Dashboard)
- Added **Menu Location** dropdown in Appearance tab - lists all registered nav menu locations

### CSS Enhancements

**Merged from Executive Blue Pools:**
- Brand CSS variables (:root)
- ADA skip link (.lean-skip)
- Button styles (.btn-primary, .btn-warning)
- Full header/nav system (hamburger, dropdowns)
- Hero section styles
- Footer styles
- Section card effect (.lean-section)
- Callout blocks

**Theme Variable Support:**
| CSS Variable | Theme Setting | Used For |
|--------------|---------------|----------|
| `--nav-color` | Nav Text Color | Desktop menu links, hamburger |
| `--dropdown-bg` | Dropdown Background | Submenu background |
| `--dropdown-text` | Dropdown Text Color | Submenu links |

**Mobile Nav Fix:**
- All links: #666 (not theme variable - white bg needs dark text)
- No hover effect on mobile
- Close button: #666

### PageSpeed Fixes
- Font Awesome loaded async (preload + onload pattern) - saves ~440ms
- Removed bootstrap.css sourcemap reference (was 404)
- CSS versions now dynamic based on file modification time

### Integration Steps (for existing themes)
1. Copy lean folder into theme: `wp-content/themes/your-theme/lean/`
2. Add to theme's functions.php: `require_once get_template_directory() . '/lean/lean-loader.php';`
3. Configure in **Theme Settings** (appears after Dashboard)
4. Select Menu Location in Appearance tab
5. Edit pages → change Template to "Lean Page"

### Test Site
- Site: staggsplumbing.co
- Theme: staggsplumbing (c20-based custom theme)
- Integration: lean/ subfolder with loader

### GitHub Status
- Repo: https://github.com/limeygent/lean-theme
- Branch: main
- Files need to be committed after this session

---

## Session: 2026-01-27 (continued)

### Summary
PageSpeed optimizations: created minimal Font Awesome CSS subset. Added Google Tag Manager support as alternative to direct GA4.

### Files Modified

| File | Changes |
|------|---------|
| `css/fontawesome-minimal.css` | NEW - Minimal FA subset (~4KB vs 18KB full) |
| `template-parts/lean-head.php` | Uses minimal FA file, added GTM head script, preconnect |
| `template-parts/lean-header.php` | Added GTM noscript after body, GA4 only loads if GTM not set |
| `inc/settings.php` | Added GTM Container ID field to Analytics tab |

### Font Awesome Optimization
- Created minimal subset with ~120 commonly used icons
- Includes: navigation, contact, time, status, ratings, business, trade/plumbing, social media
- Reduced CSS from 18.3 KB to ~4 KB (78% reduction)
- Font files still load from Cloudflare CDN

### GTM Support
- New setting: **GTM Container ID** (GTM-XXXXXXX)
- If GTM is set, GA4 direct code is skipped (configure GA4 inside GTM instead)
- GTM head script in `<head>`, noscript iframe after `<body>`

### Analytics Logic
| GTM Set? | GA4 Set? | Result |
|----------|----------|--------|
| Yes | (ignored) | GTM loads |
| No | Yes | GA4 gtag.js loads |
| No | No | No Google analytics |

---

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

## Next Steps
1. Commit changes to GitHub
2. Continue testing on staggsplumbing site
3. Run PageSpeed Insights again to verify improvements
4. Create minimal Bootstrap CSS (next PageSpeed optimization)

---

*Last Updated: 2026-01-27*
