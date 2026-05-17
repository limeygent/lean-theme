# Lean Theme Session Log

## Session: 2026-05-11

### Summary
Added three reusable component classes and one CSS variable to lean-pages.css to support a real-world page conversion (https://southernbailbonds.com/waco → standalone HTML at `~/Desktop/codeprojects/clients/entirelykids/waco.html`). All added with the user's explicit approval before edit.

### Files Modified

| File | Changes |
|------|---------|
| `css/lean-pages.css` | Added `--brand-rgb: 0, 83, 149;` to `:root`; added `.step-number` (48px brand-bg numbered circle), `.icon-circle` (56px tinted-brand icon wrapper), `.sticky-cta-mobile` (fixed bottom bar, hidden ≥992px) under new "REUSABLE COMPONENTS" section above FOOTER |

### Why these classes are reusable, not page-specific
- `.step-number` — every step-by-step service page (How It Works grids, What to Expect lists) needs a numbered brand circle. Recurs across niches.
- `.icon-circle` — same pattern for any "icon + heading + body" 3-column row. Tinted brand bg uses the new `--brand-rgb`.
- `.sticky-cta-mobile` — universal mobile-only fixed phone-CTA bar pattern.

### Icon subset gaps surfaced this session
The 65-icon subset doesn't include several icons that are commonly used in service-page layouts:
- `bi-check-lg` — use `bi-check2` instead (in subset)
- `bi-chevron-*` (any chevron) — no equivalent; inline SVG or `bi-arrow-right` rotation
- `bi-door-open-fill` — closest is `bi-arrow-right`
- `bi-quote` — use CSS `::before { content: '"' }`
- `bi-shop`, `bi-bank` — `bi-building` is the closest

If a future page genuinely needs these, expand the subset deliberately (the pattern is documented in this log's 2026-02-11 entry).

### Decisions made
- Page-specific styling (brand color variables, one-off layout classes, breakpoint-conditional positioning) belongs in the page's `<head>` `<style>` block, NOT in lean-pages.css.
- The shared theme stays generic; clients override `--brand`/`--accent` at the page level.
- Memory written to `~/.claude/projects/-Users-nomis/memory/lean-theme-cheatsheet.md` + `feedback-lean-theme-page-discipline.md` capturing the rules and the icon subset list for future sessions.

---

## Session: 2026-02-11

### Summary
Added configurable booking system (settings tab + shortcode) and expanded Bootstrap Icons subset.

### Features Added

**Booking System**
- New "Booking" settings tab with two fields:
  - Booking Code — textarea for URL or custom HTML (e.g., HouseCall Pro button)
  - Booking Widget Script — textarea for 3rd-party JS (auto-injected into footer)
- Single shortcode `[business_booking]` with `type` attribute (`button`, `link`, `custom`)
  - `button` (default): wraps URL in BS5-styled anchor with `role="button"`
  - `link`: wraps URL in plain anchor
  - `custom`: outputs raw HTML with `{text}` placeholder support
  - Supports `class` and `text` attributes
- Replaced hardcoded HouseCall Pro script in footer with dynamic `booking_widget_script` output

**Migration Shortcode**
- Added `[lean_business_phone_button]` with `lean_` prefix to avoid conflict with parent theme
- Supports `icon`, `variant`, `text`, `class` attributes
- Bootstrap Icons support (bi-telephone default)

**Bootstrap Icons Subset**
- Added `bi-droplet-fill` (`\F30B`), `bi-snow2` (`\f56E`) to subset
- Now 44 icons in `css/bootstrap-icons.min.css`

### Files Modified

| File | Changes |
|------|---------|
| `inc/settings.php` | Added Booking tab, `lean_theme_booking_fields()`, save handlers for `booking_code`/`booking_widget_script`, shortcode reference entries |
| `inc/shortcodes/business-info.php` | Added `[business_booking]` shortcode, added `[lean_business_phone_button]` migration shortcode, updated file header |
| `template-parts/lean-footer.php` | Replaced hardcoded HouseCall Pro script with dynamic `booking_widget_script` output |
| `inc/shortcodes.php` | Updated loader comment to mention `[business_booking]` |
| `css/bootstrap-icons.min.css` | Added `bi-droplet-fill`, `bi-snow2` icons (44 total) |

### Key Decisions
- Single booking shortcode with `type` attribute (not separate shortcodes per mode)
- `{text}` placeholder in custom HTML for dynamic text override
- Booking fields saved with `wp_unslash()` (not `wp_kses_post`) to allow `<script>` tags — safe because settings page gated by `manage_options`
- Bootstrap Icons codepoints must be looked up at https://icons.getbootstrap.com/ (don't guess)

---

## Session: 2026-02-08

### Summary
Implemented customizable footer widget system, added Bootstrap Icons support, merged external changes, and performed settings cleanup for improved flexibility and UX.

### Features Added

**Footer Widget System**
- Up to 4 customizable HTML textareas in Appearance tab
- Smart column width calculation: 4 widgets=25%, 3=33%, 2=50%, 1=100%
- Supports HTML, shortcodes, and PHP code
- Auto-populates with sensible defaults (Logo/Address, Map, Hours) on first activation
- Dynamic default function `lean_theme_set_default_footer_widgets()`
- No forced text alignment (user-configurable)

**Bootstrap Icons Support**
- Added minimal Bootstrap Icons CSS (~2.6KB vs 86KB full)
- New file: `css/bootstrap-icons.min.css` with custom Twitter X icon
- Preloaded in head for performance
- File versioning with cache busting

**Other Enhancements**
- House Call Pro booking script integrated into footer
- Mobile phone button updated: `btn btn-warning btn-lg w-100 d-block m-0 rounded-0`
- Header top bar items styled as buttons: `btn btn-outline-light btn-lg px-5`
- Theme-color meta tag now dynamic (uses `header_top_bg` setting)

### Settings Cleanup
- Removed Business Hours field (no longer needed)
- Removed Service Area field
- Removed Service Area Link field
- Kept address fields (street, city, state, zip) - users continue using shortcodes

### Files Modified

| File | Changes |
|------|---------|
| `inc/settings.php` | Added footer widget textareas, default widget function, removed business_hours/service_area fields |
| `template-parts/lean-footer.php` | Replaced hardcoded 3-column footer with dynamic widget rendering, added booking script |
| `template-parts/lean-head.php` | Added Bootstrap Icons CSS support, made theme-color meta tag dynamic |
| `template-parts/lean-header.php` | Updated header item links with button styling |
| `css/bootstrap-icons.min.css` | NEW - Minimal Bootstrap Icons subset with Twitter X icon |
| `.claude-context/session-log.md` | Session documentation |

### Merged External Changes
- Integrated changes from live site (`_changed-files/`)
- Kept footer widget system (Option A approach)
- Applied House Call Pro script, Bootstrap Icons, and header styling improvements
- Deleted temporary `_changed-files/` folder

### Default Footer Widgets
On first activation, users get:
1. **Widget 1:** Logo, business name, address, phone
2. **Widget 2:** Google Map embed (auto-configurable)
3. **Widget 3:** Empty (for user customization)
4. **Widget 4:** Empty (for user customization)

### Technical Details
- Footer widgets use `eval()` to process PHP code (admin-controlled)
- Output sanitized with `wp_kses_post()` for security
- Shortcodes processed after PHP evaluation
- Fallback message if no widgets configured

### GitHub Commit
- **Commit:** 5cc8d1e
- **Message:** "Add footer widgets, Bootstrap Icons, and UI improvements"
- **Branch:** main
- **Pushed:** February 8, 2026

---

## Session: 2026-01-28

### Summary
Added custom hooks (`lean_head`, `lean_footer`) for code snippet injection on lean pages. Fixed font preload warnings. Added Gutenberg block CSS and WP Customizer CSS support to lean pages.

### Files Modified

| File | Changes |
|------|---------|
| `template-parts/lean-head.php` | Added `do_action('lean_head')` hook, removed duplicate Roboto @font-face, removed font preload hints |
| `template-parts/lean-footer.php` | Added `do_action('lean_footer')` hook |
| `lean-loader.php` | Added Customizer CSS output on lean_head, added Gutenberg block CSS output on lean_head |

### Custom Hooks Added
Two custom action hooks for lean pages (since `wp_head`/`wp_footer` are not called):

| Hook | Location | Purpose |
|------|----------|---------|
| `lean_head` | End of `<head>` in lean-head.php | Inject scripts/styles into head |
| `lean_footer` | Before closing scripts in lean-footer.php | Inject scripts into footer |

**Usage in code snippets (to run on both legacy and lean pages):**
```php
add_action('wp_head', 'my_function');
add_action('lean_head', 'my_function');
```

### Auto-injected via lean_head
These are hooked automatically in `lean-loader.php`:
- **WP Customizer CSS** — Appearance > Customize > Additional CSS now works on lean pages
- **Gutenberg block CSS** — `wp-block-library/style.min.css` loaded so Gutenberg blocks (columns, images, etc.) render correctly

### Font Preload Fix
- Removed Roboto preload hints from lean-head.php (were causing "preloaded but not used" console warnings)
- Removed duplicate inline @font-face declarations (already in lean-pages.css)
- `font-display: swap` in lean-pages.css handles font loading efficiently without preloads

---

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

## Session: 2026-05-16

### Summary
CSS variable plumbing for brand/accent colors, mega-menu support for the primary nav, and a round of WCAG/accessibility fixes driven by Lighthouse findings on the Entirely Kids Pediatrics build.

### Files Created
| File | Purpose |
|------|---------|
| `inc/mega-menu-walker.php` | `Lean_Mega_Menu_Walker` — custom `Walker_Nav_Menu` that renders a 4-column dropdown when a top-level menu item has the `mega-menu` CSS class. Children grouped by their Description field (= column heading). Defers to default walker for non-mega items. |

### Files Modified
| File | Changes |
|------|---------|
| `css/lean-pages.css` | Added `.btn-cta` (CTA button, themed via `--accent`, text color via `--accent-fg`). `.btn-primary` now uses `var(--brand-fg, #fff)` for auto-contrast text. `.hero-overlay` gradient switched from hardcoded `rgba(0,83,149,…)` to `rgba(var(--brand-rgb),…)` so it tracks the configured brand color. Removed unused `--hero-color-1/2`. Removed the `:root { --brand…/--accent }` defaults block (moved to PHP — single source of truth, prevents FOUC). Added `list-style: none !important` to desktop `.sub-menu`. Appended MEGA MENU CSS block (scoped under `.lean-header .header-menu .mega-menu`) with a `::before` hover-bridge to prevent the panel closing in the parent→panel gap. |
| `inc/settings.php` | `lean_theme_inject_custom_colors()` now always emits the inline `:root` block (uses option or hardcoded default), and emits `--brand-fg` + `--accent-fg`. Added `lean_contrast_color($hex)` helper (WCAG relative-luminance formula → picks `#fff` or `#212529` for max contrast). Hooked into both `wp_head` and `lean_head` (lean templates bypass `wp_head()`). Constants `LEAN_DEFAULT_PRIMARY_COLOR='#005395'` / `LEAN_DEFAULT_ACCENT_COLOR='#daa520'`. Primary/Accent settings inputs no longer preload Bootstrap defaults; placeholders + descriptions advertise the real CSS defaults and the auto-contrast behavior. |
| `lean-loader.php` | `require_once` for `inc/mega-menu-walker.php` alongside other inc/ includes. |
| `template-parts/lean-header.php` | Passed `'walker' => new Lean_Mega_Menu_Walker()` to the existing `wp_nav_menu()` call. |
| `inc/mega-menu-walker.php` | Swapped `<h6 class="mega-col-title">` → `<div class="mega-col-title">` (no `<h>` tag pollution in nav). Removed `role="menu"`, `role="menuitem"`, `role="none"` per a11y patch — those ARIA roles are for app menus, not nav dropdowns, and Lighthouse flagged the parent/child role contract. |

### New Functions
- `lean_contrast_color($hex)` — returns `#fff` or `#212529`, whichever has higher WCAG contrast against the background. Used to compute `--brand-fg` and `--accent-fg`.

### Key Architectural Decisions
- **Single source of truth for brand CSS vars** — defaults live in PHP (`LEAN_DEFAULT_*` constants) and are emitted inline in `<head>`. `lean-pages.css` no longer declares them, which eliminates a FOUC where the file's defaults briefly painted before the inline override.
- **Mega menu = pure CSS** — no JS dependency. `:hover` + `:focus-within` only. Pseudo-element hover bridge solves the parent-to-panel gap caused by `position: static` on the parent `<li>`.
- **Auto-contrast button text** — any accent/brand the admin picks gets the contrast-correct foreground without per-color CSS edits. Verified with spot-checks against Bootstrap defaults (#0d6efd→white, #ffc107→dark, #198754→white, #dc3545→white) and the EKP coral (#ff7a59→dark).

### Theme Zip
Rebuilt `~/Desktop/lean-theme.zip` mid-session via `zip -r` from the parent dir with exclusions for `.git`, `.claude*`, `.DS_Store`, `CLAUDE.md`, and the loose scratch HTML mockups.

### PageSpeed (post-session, on live EKP site)
- FCP ~2.0s. Render-blocking CSS (`bootstrap.css` 35 KB, `lean-pages.css` 17 KB, FA, BI) is the main FCP killer.
- Bootstrap JS (`bootstrap.bundle.min.js`, 26 KB) is loaded deferred in footer; grep confirmed zero `data-bs-*` usage anywhere in theme. FAQ shortcode uses native `<details>/<summary>` — no Bootstrap JS dependency in theme code. Pending decision: whether to drop the script outright (would break any runtime content authored with Bootstrap-JS components).

### Next Steps
1. Re-run Lighthouse to confirm the four flagged a11y items (3 ARIA, 1 color-contrast) all clear.
2. Decide on Bootstrap JS removal (depends on content audit).
3. FCP path: trim `bootstrap.css` via custom build or PurgeCSS pass — biggest remaining win.
4. Consider minified `lean-pages.css` shipped alongside source (small FCP nudge).

---

*Last Updated: 2026-05-16*
