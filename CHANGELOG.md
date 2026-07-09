# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/), and this
project adheres to [Semantic Versioning](https://semver.org/). The canonical version
is the `Version:` header in `style.css`; `bin/check-version.sh` (run automatically on
`git push`) refuses a push that ships code without bumping it.

## [Unreleased]

## [1.2.0] - 2026-07-09
### Added
- **Blog roll with meta descriptions and infinite scroll.** `home.php` renders a 4/3/1
  column grid of featured image, title, and the post's meta description. A sentinel
  below the grid auto-loads the next 20 posts as it nears the viewport, so no
  `/page/N/` URL is ever linked; WordPress still serves those URLs, so the theme
  noindexes them (`inc/blog-roll.php`). New `lean_post_summary()` resolves the card
  text — NerdPress SEO meta description, then manual excerpt, then the trimmed body,
  capped at the 160-character meta budget by `lean_trim_chars()`. The body fallback
  drops headings and de-duplicates a title repeated in the opening line.
  - New `inc/blog-roll.php`, `template-parts/post-card.php`, `js/lean-load-more.js`.
  - The sentinel is a real `<button>`: scroll position is not an input a keyboard user
    has. Auto-load never moves focus; an explicit click does. A failed request disables
    auto-load so a dead network cannot drive a retry storm.
  - Password-protected posts never surface their body: `get_post_field()` reads the
    gated content raw, unlike core's `get_the_content()` / `get_the_excerpt()`.
- **`[testimonials]` category filtering.** New `category` (one or more
  `testimonial_category` slugs) and `fallback` (default `generic`) attributes. When the
  primary category is short of `num_reviews`, the remainder is filled at random from the
  fallback category with no duplicates; `fallback=""` disables the top-up.
- **Declarative WebMCP support for the contact form** (2026-06-22) — `[lean_form]` now
  renders Chrome Declarative WebMCP annotations (`toolname`, `tooldescription`, per-field
  `toolparamdescription`) so in-browser AI agents can fill the form. Off by default and
  gated behind an explicit per-origin Origin Trial token.
  - New `inc/lean-webmcp.php` helper: token-gated `lean_webmcp_enabled()`, attribute
    builders, tool presets (`request_service_estimate`, `submit_contact_request`,
    `request_callback`), and the origin-trial `<meta>` output.
  - New **WebMCP Origin Trial Token** field on the Lean Settings → Forms tab. No WebMCP
    attributes are emitted until a token is saved.
  - Shortcode attributes: `webmcp`, `webmcp_tool`, `webmcp_description`.
  - Agent-readiness only — not a performance or SEO signal; the related Lighthouse audits
    are informational.

### Changed
- `[lean_form]` now uses per-instance unique element IDs (`lean-form-1`, …) and a stable
  `lean-form` class, so multiple forms on one page work correctly. Inline CSS switched from
  ID-based to class-based selectors.
  - **Note for integrations:** external references to the old `#lean-form` / `#lean-name`
    IDs (e.g. GTM triggers, custom CSS) should target the `.lean-form` class instead.
- The spam honeypot renders off-schema (no `name` attribute, value attached via JS) when
  WebMCP is active, so an agent cannot fill the trap and silently discard a real lead; the
  classic named honeypot is retained when WebMCP is inactive.

### Fixed
- **Empty `<head>` when NerdPress SEO is inactive** (2026-07-01) — Lean templates bypass
  `wp_head()`, so with the plugin missing, deactivated, or half-loaded a page shipped no
  `<title>`, description, canonical, or robots at all (`lean-head.php` has no `<title>` of its
  own). New `lean_fallback_head_seo()` on the `lean_head` hook emits a basic
  title/description/canonical/robots block. Keyed on `nerdpress_output_lean_head()` existing,
  so it stays silent — no duplicate tags — whenever the plugin is active.
- **Duplicate `hreflang` alternate links** (2026-07-01) — `template-parts/lean-head.php`
  emitted its own `rel="alternate"` hreflang on every page, duplicating NerdPress SEO's on
  singular views and pointing at the non-canonical request URL. The plugin now solely owns
  hreflang, keyed to the canonical permalink and configurable in its settings. Non-singular
  Lean pages no longer emit hreflang — expected for single-language sites.

### Removed
- The theme's hardcoded `hreflang` alternate-link block and the dead
  `lean_output_seo_meta_tags()` call from `template-parts/lean-head.php` (retired theme SEO
  function; head SEO is owned by the NerdPress SEO plugin, which now lives in its own repo at
  https://github.com/limeygent/nerdpress-seo).
- `TODO.md` and `CLAUDE.md` are no longer packaged into `lean-theme.zip`. Both are
  development files, and every install exposed them under `/wp-content/themes/lean-theme/`.

### Assets
- Expanded the curated Bootstrap Icons subset (CSS selectors **and** the subsetted font
  files — adding an icon needs both, or it renders as a blank box).

[1.2.0]: https://github.com/limeygent/lean-theme/releases/tag/v1.2.0
