# Migrate Legacy CSS to Tailwind - Brainstorm

**Date:** 2026-01-26
**Type:** Refactoring / Technical Improvement
**Status:** Brainstormed, ready for planning

## What We're Building

Migrate legacy CSS files (utility.css, utility-md.css, utility-lg.css) and block-specific CSS to pure Tailwind CSS. This will consolidate all styling into the Tailwind build system and remove technical debt from the WordPress theme.

**Current State:**
- Theme uses modern Tailwind CSS v3.4.18 with CLI build
- Legacy utility files (3 files) still loaded in block editor for backward compatibility
- 6 blocks have individual style.css files with custom CSS
- Build process: `src/tailwind.css` → Tailwind CLI → `assets/css/tailwind.css`

**Target State:**
- All utility classes migrated to Tailwind (config or @layer utilities)
- Block-specific CSS converted to Tailwind classes or @layer components
- Legacy files kept temporarily for safety, marked as deprecated
- Single source of truth: Tailwind CSS

## Why This Approach

**Selected: Approach 3 - Hybrid (Utilities First, Then Blocks)**

**Phase 1: Migrate Utilities**
- Analyze utility.css, utility-md.css, utility-lg.css
- Add missing utilities to tailwind.config.js or @layer utilities
- Update references in PHP templates
- Test in block editor

**Phase 2: Migrate Block CSS**
- Convert block-specific style.css to Tailwind classes in templates
- Move complex styles to @layer components if needed
- Update block registrations to stop loading individual style.css

**Rationale:**
- Utilities are foundation - blocks depend on them
- Two clear phases make progress trackable
- Lower risk than big-bang migration
- Utilities can be used during block migration
- Structured workflow easier to follow

**Backward Compatibility Strategy:**
- Keep legacy files after migration (don't delete immediately)
- Add deprecation comments in files
- Monitor for 2-4 weeks before removal
- Easy rollback if issues discovered

## Key Decisions

1. **Migration Strategy:** Hybrid approach (utilities first, then blocks)
2. **Scope:** Migrate utility.css files + block-specific CSS, skip editor/admin CSS for now
3. **Backward Compatibility:** Keep legacy files temporarily with deprecation warnings
4. **Tailwind Layers:** Use @layer utilities for simple utilities, @layer components for complex block styles
5. **Testing:** Test each phase in block editor before proceeding to next
6. **Build Process:** No changes to build process (already using Tailwind CLI)

## Technical Context

**Current Build Setup:**
- **Tool:** Tailwind CSS CLI v3.4.18 (standalone, no bundler)
- **Source:** `src/tailwind.css`
- **Output:** `assets/css/tailwind.css` (minified, gitignored)
- **Scripts:** `npm run dev` (watch), `npm run build:css` (production)
- **Content Paths:** Scans `*.php`, `blocks/**/*.php`, `assets/**/*.js`, `assets/components/**/*.php`

**Legacy Files to Migrate:**
- `/assets/css/utility.css` - Base utility classes
- `/assets/css/utility-md.css` - Responsive utilities (md breakpoint)
- `/assets/css/utility-lg.css` - Responsive utilities (lg breakpoint)

**Blocks with style.css:**
1. slider-block
2. text-scroller
3. (4 other blocks identified in research)

**Tailwind Config Already Has:**
- Custom colors (red, dark-blue, light-blue, neutrals)
- Custom breakpoints (md: 640px, lg: 960px)
- Custom utilities (max-w-420, h-124, gap-40, etc.)
- Custom fonts (sohne-buch, sohne-mono)

## Open Questions

1. **Utility Audit:** Which specific utility classes are in the legacy files? Need to scan them.
2. **Usage Analysis:** How many templates/blocks use each legacy class? Need to grep.
3. **Tailwind Equivalents:** Do all legacy utilities have Tailwind equivalents, or do we need custom utilities?
4. **Block CSS Complexity:** How complex is the block-specific CSS? Can it all be converted to Tailwind classes?
5. **Testing Strategy:** What's the best way to test all 26 blocks in editor after migration?
6. **Deprecation Timeline:** Exact timeline for removing legacy files (2 weeks? 4 weeks?)?

## Success Criteria

**Phase 1 (Utilities) Complete When:**
- [ ] All utility.css classes have Tailwind equivalents (config or @layer)
- [ ] All PHP templates updated to use Tailwind classes
- [ ] Block editor tested with legacy files disabled
- [ ] No visual regressions detected
- [ ] Legacy utility files marked as deprecated

**Phase 2 (Blocks) Complete When:**
- [ ] All block style.css content migrated to Tailwind
- [ ] Block templates updated with Tailwind classes
- [ ] Block registrations no longer load individual style.css
- [ ] All 26 blocks tested in editor
- [ ] No visual regressions detected

**Project Complete When:**
- [ ] All success criteria from both phases met
- [ ] Documentation updated (README, comments)
- [ ] Deprecation warnings added to legacy files
- [ ] Team notified of changes
- [ ] Monitoring period complete (2-4 weeks)
- [ ] Legacy files safely removed

## Next Steps

1. Run `/workflows:plan` to create detailed implementation plan
2. Plan will include:
   - Utility class audit and mapping
   - Usage analysis (grep for legacy classes)
   - Step-by-step migration tasks
   - Testing checklist
   - Rollback procedures

## References

**Repository Research:**
- agentId: ae5b957 (repo-research-analyst with full CSS build analysis)
- Current build working well, no changes needed to build process
- Theme location: `/themes/acrylicon-2024`
- 26 custom ACF blocks
- Norwegian project by Andreas Harstad

**Related Files:**
- `tailwind.config.js` - Tailwind configuration
- `src/tailwind.css` - Source CSS with Tailwind directives
- `functions.php` - Asset enqueuing (lines for legacy CSS to remove later)
- `package.json` - Build scripts
