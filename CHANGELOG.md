# Changelog
All notable changes to this project will be documented in this file.

## [Unreleased]
### Added
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

### Removed
