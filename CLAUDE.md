# Lean Theme

## Repository
- **GitHub:** https://github.com/limeygent/lean-theme
- **Branch:** main

## Overview
A lightweight WordPress theme/module with built-in SEO functionality, Bootstrap CSS, custom shortcodes, and migration tools from Yoast SEO.

## Usage Modes
- **Standalone:** Use as the active WordPress theme (functions.php loads lean-loader.php)
- **Integration:** Copy into existing theme as `/lean/` subfolder, add to functions.php:
  ```php
  require_once get_template_directory() . '/lean/lean-loader.php';
  ```

## Key Constants (defined in lean-loader.php)
- `LEAN_THEME_DIR` - Absolute path to lean-theme directory
- `LEAN_THEME_URL` - URL to lean-theme directory
- `LEAN_IS_STANDALONE` - true if Lean is the active theme, false if embedded

## Project Structure
- `lean-loader.php` - Main entry point for all functionality
- `functions.php` - Standalone theme bootstrap (just includes loader)
- `/inc/` - PHP includes (SEO, settings, shortcodes, forms, lead attribution)
- `/css/` - Stylesheets (Bootstrap, custom styles)
- `/template-parts/` - Theme template partials
- `/code-snippets/` - Standalone code snippets for SEO functionality
- `/assets/` - Fonts and other assets

## Future Feature: ADA / WCAG Contrast Auditor

### Context
Brand colors entered in **Lean Settings → Primary Color / Accent Color** can produce text/background combinations that fail WCAG 2.x contrast minimums (4.5:1 normal text, 3:1 large text, 3:1 UI components). Common failures: pale yellow accent with white text, mid-blue brand with dark text on hover states, light gray text on white backgrounds.

### What's already in place
Partial coverage exists in `inc/settings.php`:
- `lean_contrast_color($hex)` — picks `#fff` or `#212529` for foreground based on relative luminance (WCAG 2.x formula, lines 841–859).
- `lean_hex_to_rgb($hex)` — hex parser, lines 815–828.
- The result is exposed as CSS vars `--brand-fg` and `--accent-fg`, so primary/accent buttons auto-flip their text color (lines 793–803).

This solves buttons. It does *not* solve: hero overlay text on `--brand`, link colors against page backgrounds, badge/pill text on accent, header-nav text against `header_main_bg`, footer text against the footer background, or anything an editor hand-codes in page content.

### Proposed: interactive ADA audit
Add an admin tool that scans the active color palette and flags failing pairs.

**Scope (MVP — palette-level, not page-level):**
1. New file: `inc/ada-audit.php`, loaded from `lean-loader.php`.
2. New admin submenu under "Lean Settings" → "ADA Audit".
3. Pull every configured color: `primary_color`, `secondary_color`, `header_main_bg`, `header_nav_text`, `dropdown_bg`, `dropdown_text`, footer bg/text, plus the implicit `--brand-fg` / `--accent-fg` that `lean_contrast_color()` computed.
4. Compute pairwise contrast ratios for the meaningful pairs (not the cartesian product — only pairs that actually meet on the page, e.g. `header_nav_text` on `header_main_bg`).
5. Render a table: pair, ratio, pass/fail at AA-normal/AA-large/AAA, with a suggested fix.

**Suggested-fix logic:**
- Move `lean_contrast_color()` into a shared helper file (e.g. `inc/color-utils.php`) so audit + settings both pull from it.
- Add `lean_contrast_ratio($hex_a, $hex_b)` returning the numeric ratio. (We already compute luminance; just generalize.)
- Add `lean_suggest_passing_color($bg, $target_ratio = 4.5)` that nudges lightness up/down in HSL space until the ratio passes, returning the closest passing variant of the user's chosen color rather than just flipping to white/black.

**Optional v2 — content scanning:**
- Cron or on-demand crawl of published pages; parse rendered HTML; for each visible text node, resolve its computed CSS color + nearest background color; flag anything below 4.5:1. Heavy; only worth it if v1 surfaces enough real issues to justify.

**Optional v3 — auto-remediation toggle:**
- Setting: "Auto-correct brand color to nearest WCAG-passing variant when used as background." When enabled, the value written to `--brand` is shifted by `lean_suggest_passing_color()` rather than used raw. Risky because it silently changes what the user typed — keep it opt-in and show a notice in the color field's description.

### Lesson learned
Auto-picking foreground per background (what we did for buttons) is the right *primitive*, but it isn't enough on its own — most contrast failures in real client sites come from places where the foreground is fixed by markup or content (hero overlay text, hand-coded headings, third-party block output). An audit step that surfaces failures is more useful than another auto-flip, because it tells the operator *which surface* is broken and lets them choose to recolor the brand vs. recolor the text.
