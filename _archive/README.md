# _archive

Retired theme code, kept for reference only. **Nothing here is loaded** — the
theme no longer `require`s these files, and this folder is never auto-included
by WordPress.

| File | Was | Retired | Replaced by |
|------|-----|---------|-------------|
| `seo.php` | `inc/seo.php` — SEO meta box, admin columns, front-end `<head>` output (`_lean_meta_*`) | 2026-06-30 | **NerdPress SEO** plugin (`plugin/nerdpress-seo/`) |
| `sitemaps.php` | `inc/sitemaps.php` — custom XML sitemaps (`pageone_*`, `_pageone_meta_noindex`) | 2026-06-30 | **NerdPress SEO** plugin |
| `code-snippets/1-lean-seo-admin.php` | Standalone snippet — SEO meta box + admin columns (`_lean_meta_*`) | 2026-06-30 | NerdPress SEO `meta-box.php` + `admin-columns.php` |
| `code-snippets/2-lean-seo-output.php` | Standalone snippet — front-end `<head>` output | 2026-06-30 | NerdPress SEO `meta-output.php` |
| `code-snippets/3-lean-seo-migration.php` | Standalone snippet — one-time Yoast→Lean migration (already used) | 2026-06-30 | n/a (one-time tool) |
| `cpts.php` | `inc/cpts.php` — registered the non-public `testimonials` CPT | 2026-06-30 | **NerdPress SEO** plugin seeds `testimonials` as a built-in default CPT. Theme's `[testimonials]` shortcode (still in `inc/shortcodes/`) queries it. |

The `code-snippets/` files were never loaded by the theme — they were pasted
into the "Code Snippets" plugin on live sites. The NerdPress plugin supersedes
them; it reads the same `_lean_meta_*` data when its prefix is set to `lean`.

## Why

SEO + sitemaps moved out of the theme into the standalone NerdPress SEO plugin,
which is now a hard dependency of the Lean theme (`lean-loader.php` shows an
admin notice if it's inactive). The plugin reads the same `_lean_meta_*` data
when its meta-key prefix is set to `lean`, so no migration is needed.

Full history is preserved in git (`git log --follow _archive/seo.php`).
