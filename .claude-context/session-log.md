# Lean Theme Session Log

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

### GitHub Status
- Repo: https://github.com/limeygent/lean-theme
- Latest commit: f894ebe
- Branch: main
- All changes pushed successfully

---

## Migration Strategy (Discussed)

### User's Situation
- Two sites to convert to lean-theme:
  1. Site with Avada theme
  2. Site with custom theme based on Twenty Eighteen
- Both have 100s of pages/posts
- Need gradual migration, not big-bang switch

### Agreed Approach
1. **Add Code Snippets** (theme-independent, stored in DB)
   - Business settings admin page
   - All shortcodes
   - These persist across theme changes

2. **Add template files to current theme:**
   ```
   current-theme/
   ├── template-parts/
   │   ├── page-lean.php
   │   ├── head.php
   │   ├── header.php
   │   └── footer.php
   └── css/
       ├── bootstrap.css
       └── lean-pages.css
   ```

3. **Migrate pages one by one:**
   - Edit page → Page Attributes → Template → "Lean Theme"
   - Test, fix issues, repeat

4. **Final switch:**
   - Appearance → Themes → Activate "Lean Theme"
   - Template assignments persist (same path: `template-parts/page-lean.php`)
   - Code snippets keep working
   - Optionally deactivate duplicate snippets since theme includes same functionality

### Key Points
- Code Snippets plugin = theme-independent (database, not files)
- Template path must match between old theme and lean-theme for seamless switch
- Settings stored as WordPress options - persist across everything

---

## Next Steps
1. Add template files + CSS to the Avada site (or child theme)
2. Add template files + CSS to the Twenty Eighteen-based site
3. Configure Code Snippets on both sites
4. Begin gradual page migration
5. Final theme switch when ready

---

## Related Files (Avada Child Theme Work)
Located in: `/Users/nomis/Desktop/executive blue pools/avada-child-theme/`
- `code-snippets/1-business-settings-admin.php`
- `code-snippets/2-business-shortcodes.php`
- Template files mirror the lean-theme structure

---

*Last Updated: 2026-01-15*
