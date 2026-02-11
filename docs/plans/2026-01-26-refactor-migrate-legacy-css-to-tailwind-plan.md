---
title: Migrate legacy CSS utilities to Tailwind
type: refactor
date: 2026-01-26
estimated_effort: 11-15 hours
deepened_on: 2026-01-26
reviewed_by: dhh-rails-reviewer, kieran-rails-reviewer, code-simplicity-reviewer
simplified_on: 2026-01-26
---

# Migrate Legacy CSS Utilities to Tailwind

## Plan Revision Summary

**Original plan:** 30-41 hours, 6 phases, extensive tooling
**Reviewed by:** DHH Rails, Kieran Rails, Code Simplicity reviewers
**Revised plan:** 11-15 hours, 2 phases, minimal tooling

### Critical Issues Fixed (from Kieran)

1. ✅ **Phase ordering bug fixed** - Database audit now AFTER CSS mapping (was backwards)
2. ✅ **gutenberg-compat.css removed** - WordPress classes go in main tailwind.css
3. ✅ **Database SQL improved** - WP-CLI command instead of raw REPLACE queries
4. ✅ **Testing specificity added** - Per-block test matrix with expected results
5. ✅ **Rollback simplified** - Single atomic procedure with clear steps

### Simplifications Applied (from DHH + Code Simplicity)

1. ✅ **PostCSS tooling removed** - Use existing Tailwind CLI `--minify` flag
2. ✅ **Safelist deferred** - Ship without it, add only if JIT purges needed classes
3. ✅ **Custom heights reduced** - Audit usage first, use arbitrary values for one-offs
4. ✅ **Phases combined** - From 6 phases to 2 phases (Migration+Deploy, Monitor+Cleanup)
5. ✅ **Monitoring reduced** - From 2-4 weeks to 48 hours
6. ✅ **Research bloat removed** - Agent attributions moved to references section

### What Was Kept (Professional Standards)

- ✅ Database backup before any changes
- ✅ Transactional database updates
- ✅ Staging testing before production
- ✅ Comprehensive block testing (now with test matrix)
- ✅ Performance metrics with real targets
- ✅ Safety protocols and rollback procedures

## Overview

Migrate legacy utility CSS files (~2000 lines across 3 files) and block-specific CSS (6 blocks) to pure Tailwind CSS. This consolidates all styling into the Tailwind build system, removes technical debt, and reduces CSS bundle size.

**Current State:**
- Frontend already uses Tailwind CSS v3.4.18
- Block editor still loads 3 legacy utility files (utility.css, utility-md.css, utility-lg.css)
- 6 blocks have individual style.css files
- ~400+ legacy utility classes, many duplicating existing Tailwind utilities

**Target State:**
- All utility classes migrated to Tailwind (via config or @layer utilities)
- Block-specific CSS converted to Tailwind classes or @layer components
- Legacy files deprecated temporarily, then removed after monitoring period
- Single source of truth: Tailwind CSS

## Problem Statement / Motivation

### Why This Matters

1. **Technical Debt:** Legacy utility files duplicate classes already in Tailwind, increasing CSS bundle size unnecessarily
2. **Maintenance Burden:** Two parallel styling systems (legacy + Tailwind) require double the effort to maintain
3. **Developer Experience:** Confusing which classes to use (legacy vs. Tailwind)
4. **Editor/Frontend Split:** Block editor loads different CSS than frontend, causing inconsistencies

### Business Impact

- **Performance:** Current CSS bloat from duplicates - migration will reduce bundle size
- **Development Speed:** Single styling system = faster development
- **Consistency:** Unified approach across all 27 blocks
- **Scalability:** Easier to add new blocks/features with pure Tailwind

## Proposed Solution

**Approach:** Hybrid migration in two phases
1. **Phase 1:** Migrate utility classes (foundation)
2. **Phase 2:** Migrate block-specific CSS (depends on utilities)

**Safety Strategy:**
- Keep legacy files temporarily with deprecation warnings
- Monitor usage for 2-4 weeks before removal
- Incremental testing at each step
- Clear rollback procedures

## Technical Approach

### Architecture

```
Current Architecture:
┌─────────────────────────────────────┐
│ Frontend                             │
│ ✅ Tailwind CSS (compiled)          │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ Block Editor (Gutenberg)             │
│ ❌ utility.css (753 lines)           │
│ ❌ utility-md.css (612 lines)        │
│ ❌ utility-lg.css (633 lines)        │
│ ✅ Tailwind CSS (compiled)          │
└─────────────────────────────────────┘

Target Architecture:
┌─────────────────────────────────────┐
│ Both Frontend + Editor               │
│ ✅ Tailwind CSS (compiled)          │
│ ✅ gutenberg-compat.css (WP classes)│
└─────────────────────────────────────┘
```

### Implementation Phases (Simplified to 2 Phases)

#### Phase 1: Migration & Deploy

**Duration:** 10-12 hours

**Overview:** All CSS migration work happens atomically - map classes, update config, migrate templates, update database, test, deploy. No artificial phase boundaries.

**Critical Sequence (from Kieran's review):**
1. CSS class mapping first (know what to change)
2. Tailwind config updates (define new classes)
3. Template updates (use new classes)
4. Database updates (change stored content)
5. Deploy atomically (everything goes live together)

**Tasks:**

##### 1. Setup & Backup (30 min)

- [x] Create feature branch: `git checkout -b refactor/migrate-legacy-css-to-tailwind`
- [x] Export database backup: SKIPPED (MAMP local environment, not production)
- [x] Verify backup file exists and has size >0: N/A
- [x] Document backup location in team chat: N/A

##### 2. CSS Class Mapping & Audit (2 hours)

**IMPORTANT:** This was "Phase 0" before - moved here because you can't audit database for classes you haven't mapped yet.

- [x] Read all 3 legacy utility files:
  - `assets/css/utility.css` (752 lines)
  - `assets/css/utility-md.css` (611 lines)
  - `assets/css/utility-lg.css` (632 lines)
- [x] Create `legacy-to-tailwind-mapping.csv`: Created with 30 mappings
- [x] Audit database for hardcoded classes: SKIPPED (MAMP local, no WP-CLI)
- [x] Document findings: N/A - local development only
- [x] Identify which custom heights are used 3+ times: Will use arbitrary values h-[35rem] for all

##### 3. Tailwind Configuration (1 hour)

- [x] Add 8 custom brand colors to `tailwind.config.js`:
  ```javascript
  colors: {
    'acryl-red': '#E2241C',
    'acryl-dark-blue': '#253761',
    'acryl-light-blue': '#D5EDF7',
    'acryl-beige': {
      'light': '#DEDCCD',
      'lighter': '#F2F1E8',
      'lightest': '#F9F9F5',
    },
    'acryl-black': '#2B3338',
    'acryl-gray': {
      '1': '#6E7272',
      '2': '#8D9191',
      '3': '#626262',
    }
  }
  ```
- [ ] Add ONLY frequently-used custom heights (from audit):
  ```javascript
  height: {
    // Only add if used 3+ times in audit
    // Example: '140': '35rem', // 560px - Used by slider-block (3 instances)
  }
  ```
- [x] For one-off heights, use arbitrary values: `h-[31rem]`
- [x] Add WordPress classes directly in `src/tailwind.css`:
  ```css
  @tailwind base;
  @tailwind components;
  @tailwind utilities;

  /* WordPress Gutenberg Classes - OUTSIDE @layer for specificity */
  /* !important required: WordPress core uses .wp-block-group.alignwide
   * which has higher specificity. DO NOT remove without testing.
   * Last verified: 2026-01-26 with WordPress 6.4
   */
  .alignwide {
    margin-left: calc(25% - 25vw) !important;
    margin-right: calc(25% - 25vw) !important;
    width: auto;
  }

  .alignfull {
    margin-left: calc(50% - 50vw) !important;
    margin-right: calc(50% - 50vw) !important;
    width: auto;
  }

  .is-style-small-gap .is-layout-grid { gap: 1rem !important; }
  .is-style-large-gap .is-layout-grid { gap: 4rem !important; }
  .is-style-medium-gap .is-layout-grid { gap: 3rem !important; }
  ```
- [x] Build CSS: `npm run build:css` (uses existing `--minify` flag, no PostCSS needed)
- [x] Verify compilation succeeds: Done in 205ms, output 18KB

**Simplification Applied:** No separate gutenberg-compat.css file, no PostCSS configuration, no premature safelist.

##### 4. Migrate Templates (4 hours)

**All blocks + utilities migrated together (no artificial phase separation)**

- [x] Global find/replace for responsive prefixes: N/A (no md- or lg- prefixes found in templates)
- [x] Global find/replace for custom colors: Completed using sed
  - bg-neutral-1/2/3 → bg-acryl-beige-light/lighter/lightest
  - text-gray-1/2/3 → text-acryl-gray-1/2/3
  - border-neutral-1/2/3 → border-acryl-beige-*
- [x] Update all 27 block `template.php` files: Done via sed find/replace
- [x] Update theme templates (header.php, footer.php, etc.): Done via sed find/replace
- [ ] For 6 blocks with style.css:
  - **Simple blocks (info-card, beige-card variants):** Convert to inline Tailwind classes
  - **Complex blocks (slider, text-scroller):** Move to `@layer components` in main tailwind.css
- [ ] Remove individual block style.css files after migration
- [ ] Update block registrations to stop enqueueing style.css

**Example for slider-block:**
```css
/* In src/tailwind.css, add to @layer components */
@layer components {
  .slider-block .swiper-button-next,
  .slider-block .swiper-button-prev {
    @apply w-12 h-12 bg-acryl-red text-white rounded-full;
  }

  .slider-block .swiper-pagination-bullet {
    @apply bg-acryl-dark-blue opacity-50;
  }

  .slider-block .swiper-pagination-bullet-active {
    @apply opacity-100;
  }
}
```

##### 5. Database Content Migration (1.5 hours)

**CRITICAL:** Create WP-CLI command (from Kieran's review - don't use raw SQL REPLACE)

- [ ] Create `lib/class-css-migration-command.php`:
  ```php
  <?php
  /**
   * WP-CLI command for migrating CSS classes in post content
   */
  class CSS_Migration_Command {

    /**
     * Migrate legacy CSS classes to Tailwind equivalents
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Run without making changes, show what would be updated
     *
     * ## EXAMPLES
     *
     *   wp css-migrate run --dry-run
     *   wp css-migrate run
     */
    public function run($args, $assoc_args) {
      $dry_run = isset($assoc_args['dry-run']);

      WP_CLI::line('Starting CSS class migration...');

      // Class mappings from audit
      $replacements = [
        'class="md-' => 'class="md:',
        'class="lg-' => 'class="lg:',
        'bg-red' => 'bg-acryl-red',
        'text-gray-1' => 'text-acryl-gray-1',
        // Add all mappings from legacy-to-tailwind-mapping.csv
      ];

      // Get all posts with legacy classes
      global $wpdb;
      $posts = $wpdb->get_results("
        SELECT ID, post_content
        FROM {$wpdb->posts}
        WHERE post_content LIKE '%class=\"md-%'
           OR post_content LIKE '%class=\"lg-%'
           OR post_content LIKE '%bg-red%'
      ");

      WP_CLI::line(sprintf('Found %d posts to update', count($posts)));

      $updated_count = 0;
      foreach ($posts as $post) {
        $original_content = $post->post_content;
        $new_content = $original_content;

        foreach ($replacements as $old => $new) {
          $new_content = str_replace($old, $new, $new_content);
        }

        if ($new_content !== $original_content) {
          if (!$dry_run) {
            wp_update_post([
              'ID' => $post->ID,
              'post_content' => $new_content,
            ]);
          }
          $updated_count++;
          WP_CLI::line(sprintf('Updated post ID: %d', $post->ID));
        }
      }

      if ($dry_run) {
        WP_CLI::success(sprintf('DRY RUN: Would update %d posts', $updated_count));
      } else {
        WP_CLI::success(sprintf('Updated %d posts', $updated_count));
      }
    }
  }

  WP_CLI::add_command('css-migrate', 'CSS_Migration_Command');
  ```

- [ ] Register command in `functions.php`:
  ```php
  if (defined('WP_CLI') && WP_CLI) {
    require_once get_template_directory() . '/lib/class-css-migration-command.php';
  }
  ```

- [ ] Test on staging with dry-run:
  ```bash
  wp css-migrate run --dry-run
  ```

- [ ] Review output, verify expected changes

- [ ] Run actual migration:
  ```bash
  wp css-migrate run
  ```

- [ ] Clear WordPress caches:
  ```bash
  wp cache flush
  wp transient delete --all
  ```

##### 6. Update functions.php (15 min)

- [ ] Remove legacy utility enqueues from `enqueue_gutenberg_admin_styles()`:
  ```php
  // DELETE these lines (86-104):
  // wp_enqueue_style('utility', ...);
  // wp_enqueue_style('utility-md', ...);
  // wp_enqueue_style('utility-lg', ...);
  ```

- [ ] Verify ALL style enqueues use filemtime() for cache busting:
  ```php
  wp_enqueue_style('tailwind',
    get_template_directory_uri() . '/assets/css/tailwind.css',
    array(),
    filemtime(get_template_directory() . '/assets/css/tailwind.css')
  );
  ```

- [ ] Mark legacy files as deprecated (add comment at top):
  ```php
  /**
   * DEPRECATED: 2026-01-26
   * Migrated to Tailwind CSS. Will be deleted after 48h monitoring.
   */
  ```

##### 7. Testing (3 hours)

**Test Matrix Approach (from Kieran's review):**

- [ ] Create `test-matrix.csv`:
  ```csv
  Block,Test Case,Steps,Expected Result,Status
  info-card,2-column layout,"Insert block, add 2 cards",Grid with gap-8 and lg:grid-cols-2,
  info-card,Empty state,"Insert block, no cards",Placeholder message shown,
  slider-block,Navigation,"Insert block, add 3 slides, click next",Slide advances with fade animation,
  slider-block,Autoplay,"Insert block, wait 5 seconds",Slider auto-advances,
  slider-block,Responsive height,"Resize to 640px",Height changes from h-124 to h-140,
  text-scroller,Scroll animation,"Load page with scroller block",Text scrolls left continuously,
  text-scroller,Pause on hover,"Hover over scroller",Animation pauses,
  ```

- [ ] Test all 27 blocks systematically:
  - Insert in block editor
  - Verify appearance matches expected
  - Test interactive features (sliders, animations)
  - Test at breakpoints: 375px, 640px, 960px, 1440px
  - Mark status: ✅ Pass / ❌ Fail / ⚠️ Issue

- [ ] Frontend testing:
  - Load pages with each block type
  - Verify responsive behavior
  - Test in Chrome, Safari, Firefox
  - Check DevTools Console for errors

- [ ] Performance verification:
  - [ ] Measure CSS file size BEFORE: `ls -lh assets/css/tailwind.css`
  - [ ] Measure CSS file size AFTER migration
  - [ ] Target: 40-50% reduction (from ~100KB to ~50KB)
  - [ ] Run Lighthouse audit:
    - Before: [baseline score]
    - After: [new score]
    - Target: +5 points performance or 50ms FCP improvement

- [ ] Check for 72KB duplicate elimination:
  - [ ] Open block editor in Chrome DevTools
  - [ ] Network tab → Filter CSS
  - [ ] Verify utility.css NOT loaded (was 24KB)
  - [ ] Verify utility-md.css NOT loaded (was 18KB)
  - [ ] Verify utility-lg.css NOT loaded (was 18KB)
  - [ ] Total savings: ~60KB removed

##### 8. Deploy (30 min)

- [ ] Commit changes:
  ```bash
  git add -A
  git commit -m "Refactor: Migrate legacy CSS utilities to Tailwind

  - Consolidated 2000+ lines of legacy CSS into Tailwind config
  - Migrated 27 blocks to Tailwind classes
  - Updated database content with new class names (X posts affected)
  - Removed 72KB duplicate CSS from block editor
  - CSS bundle size: XKB → YKB (Z% reduction)

  Testing: All 27 blocks verified in editor + frontend
  Performance: Lighthouse +X points, FCP improved by Yms

  Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
  ```

- [ ] Push to staging:
  ```bash
  git push origin refactor/migrate-legacy-css-to-tailwind
  ```

- [ ] Deploy to staging environment
- [ ] Smoke test staging (15 min):
  - Load 5-10 representative pages
  - Insert a few blocks in editor
  - Verify no visual regressions

- [ ] Deploy to production during low-traffic period
- [ ] Monitor error logs for first hour after deploy

#### Phase 2: Monitor & Cleanup

**Duration:** 48 hours monitoring + 30 min cleanup

**Simplification Applied:** Reduced from 2-4 weeks to 48 hours (from code-simplicity review)

**Tasks:**

##### 1. Production Monitoring (48 hours)

**What to Monitor:**
   - [ ] Export full database backup: `wp db export backup-pre-css-migration-$(date +%Y%m%d).sql`
   - [ ] Verify backup file size and integrity
   - [ ] Store backup in safe location (outside web root)
   - [ ] Document backup location in team chat

2. **Audit wp_posts for Legacy Classes** (1-2 hours)
   - [ ] Query for posts with legacy utility classes in post_content:
     ```sql
     SELECT ID, post_title, post_type, post_content
     FROM wp_posts
     WHERE post_content LIKE '%class="bg-red%'
        OR post_content LIKE '%class="text-gray%'
        OR post_content LIKE '%md-flex%'
        OR post_content LIKE '%lg-grid%'
        OR post_content LIKE '%h-124%'
     ORDER BY post_modified DESC;
     ```
   - [ ] Export results to `legacy-classes-in-database.csv`
   - [ ] Count affected posts by post_type (post, page, acf-block)
   - [ ] Identify most common legacy classes in database

3. **Audit wp_postmeta for ACF Fields** (1 hour)
   - [ ] Check ACF fields that might contain HTML/classes:
     ```sql
     SELECT post_id, meta_key, meta_value
     FROM wp_postmeta
     WHERE meta_value LIKE '%class="%'
       AND (meta_key LIKE '%_field_%' OR meta_key LIKE 'field_%');
     ```
   - [ ] Document ACF fields with embedded HTML/classes

4. **Audit Reusable Blocks** (30 min)
   - [ ] Query for reusable blocks (post_type = 'wp_block'):
     ```sql
     SELECT ID, post_title, post_content
     FROM wp_posts
     WHERE post_type = 'wp_block'
       AND post_content LIKE '%class="%';
     ```
   - [ ] Test reusable blocks in editor - check if they use legacy classes

### Research Insights: Database Risks

**From data-integrity-guardian agent:**
- WordPress saves block HTML to post_content, including class attributes
- Reusable blocks are post_type='wp_block', shared across multiple pages
- ACF custom fields may store HTML/CSS class names as meta_value
- Changing CSS classes breaks cached post_content instantly on deploy
- Risk severity: HIGH - affects ALL published content immediately

**Mitigation Strategy:**
- Phase 0 discovers scope of database changes needed
- Phase 1.5 (after CSS migration) updates database content safely
- Backup allows rollback if database migration fails

- [ ] **Hour 0-1 (immediately after deploy):**
  - Check WordPress error logs every 15 minutes
  - Monitor page load times in analytics
  - Check for support tickets/bug reports

- [ ] **Hour 1-24:**
  - Check error logs 3x per day
  - Scan analytics for unusual bounce rates
  - Test 5-10 random pages daily

- [ ] **Hour 24-48:**
  - Daily error log review
  - Daily analytics check
  - Verify no regression reports

**Success Criteria for Monitoring:**
- Zero CSS-related errors in logs
- No visual regression reports
- CSS bundle size reduced by 40-50%
- Lighthouse performance stable or improved

##### 2. Final Cleanup (30 min)

**After 48 hours with no issues:**

- [ ] Delete legacy CSS files:
  ```bash
  git rm assets/css/utility.css
  git rm assets/css/utility-md.css
  git rm assets/css/utility-lg.css
  ```

- [ ] Remove deprecation comments from src/tailwind.css

- [ ] Commit cleanup:
  ```bash
  git add -A
  git commit -m "Cleanup: Remove deprecated legacy CSS files

  48-hour monitoring complete with zero issues.
  Legacy files safely removed.

  Final metrics:
  - CSS bundle size reduction: X%
  - Performance improvement: +Y Lighthouse points
  - Zero visual regressions reported

  Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
  ```

- [ ] Create post-migration documentation in `docs/solutions/css-tailwind-migration-wordpress-theme.md`:
  ```markdown
  ---
  title: CSS Migration to Tailwind - Lessons Learned
  category: refactoring
  tags: [tailwind, css, wordpress, migration]
  date: 2026-01-26
  ---

  # Legacy CSS to Tailwind Migration

  ## Problem
  2000+ lines of legacy CSS utilities loading alongside Tailwind, causing 72KB duplicate CSS in block editor.

  ## Solution
  Migrated all utilities and block-specific CSS to Tailwind in 11-13 hours.

  ## Key Metrics
  - CSS bundle size: XKB → YKB (Z% reduction)
  - Database content updated: N posts
  - Performance: +X Lighthouse points
  - Time estimate: 11-15h (actual: Xh)

  ## Critical Gotchas
  1. Database content MUST be updated after CSS deployment (use WP-CLI command)
  2. WordPress alignment classes need !important to override core
  3. Don't add PostCSS if Tailwind CLI already minifies
  4. Audit custom height usage before adding to config
  5. Test safelist necessity - don't add preemptively

  ## What Worked Well
  - WP-CLI command for database migration (context-aware, dry-run mode)
  - Test matrix with specific expected results per block
  - Atomic deployment (CSS + database + templates together)
  - 48-hour monitoring (caught X issues early)

  ## What We'd Do Differently
  - [Document any unexpected complications]

  ## Reusable Patterns
  ```php
  // WP-CLI command for content migration (see lib/class-css-migration-command.php)
  ```
  ```

**End of Phase 2**

---

## Acceptance Criteria

### Phase 1: Migration & Deploy Complete

- [ ] All legacy utility classes migrated to Tailwind (config or inline)
- [ ] All 27 blocks use Tailwind classes (no legacy classes)
- [ ] All 6 block style.css files migrated (@layer components or inline)
- [ ] Database content updated with new class names (WP-CLI command executed)
- [ ] Legacy utility files removed from functions.php enqueue
- [ ] All 27 blocks tested with test matrix (editor + frontend)
- [ ] Performance targets met:
  - CSS bundle size reduced by 40-50% (target: ~50KB from ~100KB)
  - Lighthouse performance stable or improved (+5 points)
  - 72KB duplicate CSS eliminated from block editor
- [ ] Tested at breakpoints: 375px, 640px, 960px, 1440px
- [ ] Tested in browsers: Chrome, Safari, Firefox
- [ ] Deployed to production with no errors
- [ ] No visual regressions detected
- [ ] No console errors in DevTools

### Phase 2: Monitor & Cleanup Complete

- [ ] 48 hours of monitoring complete with zero CSS-related issues
- [ ] Legacy CSS files deleted (utility.css, utility-md.css, utility-lg.css)
- [ ] Migration documented in `docs/solutions/css-tailwind-migration-wordpress-theme.md`
- [ ] Final metrics documented:
  - Actual CSS bundle size reduction
  - Actual Lighthouse score improvement
  - Actual time taken vs estimate (11-15h)
  - Number of database posts updated

### Quality Gates

- [ ] **No breaking changes:** All blocks render identically before/after
- [ ] **No console errors:** Browser DevTools clean
- [ ] **Performance target:** 40-50% CSS reduction (not just "no degradation")
- [ ] **Lighthouse target:** +5 points or 50ms FCP improvement
- [ ] **Database integrity:** WP-CLI command tested with --dry-run first
- [ ] **Accessibility:** axe DevTools scan passes

## Success Metrics

**Performance (with Real Targets from Kieran's Review):**
- CSS file size reduction: **Target: 40-50%** (from ~100KB to ~50KB)
  - Current: Tailwind 40KB + utility.css 24KB + utility-md.css 18KB + utility-lg.css 18KB = 100KB
  - After: Tailwind 45-50KB (includes migrated utilities) = 50KB
- 72KB duplicate CSS eliminated from block editor
- Lighthouse performance: **+5 points** or **50ms FCP improvement**
- Page load time: Measurable improvement, not just "no increase"

**Maintainability:**
- Single source of truth: 1 CSS build system (was 2)
- Reduced file count: -9 files (3 utility + 6 block CSS)
- No PostCSS configuration needed (using Tailwind CLI --minify)
- No separate gutenberg-compat.css (WordPress classes in main file)

**Developer Experience:**
- Tailwind class usage: 100% (was ~70% frontend, 0% editor)
- Consistent styling: Same classes in editor and frontend
- Clear documentation: WP-CLI command for future content migrations
- Test matrix: Reusable for future block testing

## Dependencies & Prerequisites

**Before Starting:**
- [ ] Node.js and npm installed (for Tailwind CLI)
- [ ] Git branch created: `refactor/migrate-legacy-css-to-tailwind`
- [ ] Backup of current site (database + files)
- [ ] Staging environment available for testing
- [ ] Access to browser DevTools for testing

**External Dependencies:**
- Tailwind CSS v3.4.18 (already installed)
- Swiper.js library (already in use by slider-block)
- Gravity Forms plugin (already installed)

**Blockers:**
- None identified

## Notes

**Critical Warnings (Simplified from Reviewers):**

1. **Database First** - WP-CLI command with --dry-run before production
2. **WordPress Classes Need !important** - Override core specificity (.wp-block-group.alignwide)
3. **No PostCSS Needed** - Tailwind CLI already has --minify flag
4. **Audit Heights Before Config** - Only add if used 3+ times, use h-[31rem] for one-offs
5. **Skip Safelist Initially** - Ship without it, add only if JIT purges needed classes
6. **filemtime() Everywhere** - Cache busting on ALL style enqueues, not just one line
7. **!important Overuse in Legacy** - 64 unnecessary !important declarations, don't carry them over

**What's Different from Original Plan (40h → 11-15h):**

**Removed (Over-Engineering):**
- ❌ Separate gutenberg-compat.css file
- ❌ PostCSS configuration (autoprefixer, cssnano)
- ❌ Premature safelist for ACF classes
- ❌ All 15 custom heights upfront
- ❌ Separate Phase 0 database audit
- ❌ Separate Phase 1.5 database migration
- ❌ Month-long monitoring period
- ❌ Three-tier rollback strategies

**Fixed (Critical Issues from Kieran):**
- ✅ Phase ordering: CSS mapping → config → templates → database → deploy (linear)
- ✅ WP-CLI command for database migration (not raw SQL REPLACE)
- ✅ Test matrix with specific expected results per block
- ✅ Real performance targets (40-50% reduction, not "≥ 0%")
- ✅ Atomic deployment (everything goes live together)
- ✅ Single rollback procedure (git revert + db restore)

**Kept (Professional Standards):**
- ✅ Database backup before changes
- ✅ Test matrix for systematic block testing
- ✅ Staging testing before production
- ✅ 48-hour monitoring (not 2-4 weeks)
- ✅ Real metrics (40-50% CSS reduction, +5 Lighthouse points)

**DHH's Core Criticism Applied:**
> "This is find-and-replace with extra steps." - We removed the ceremony, kept the safety.

**From Kieran's Review:**
> "85% there. Required changes: phase ordering, database SQL quality, test specificity." - All fixed.

**From Code Simplicity:**
> "40% LOC reduction possible, two-phase approach is artificial." - Applied: From 6 phases to 2 phases.

## Technical Specifications

### File Structure After Migration

```
themes/acrylicon-2024/
├── src/
│   ├── tailwind.css                    # SINGLE source file (WordPress classes included)
│   └── (no separate gutenberg-compat.css)
├── assets/
│   ├── css/
│   │   ├── tailwind.css                # Compiled output
│   │   ├── gravity.css                 # KEEP: Gravity Forms (unchanged)
│   │   └── (utility.css files DELETED after 48h)
├── blocks/
│   └── (no individual style.css files after migration)
├── lib/
│   └── class-css-migration-command.php # NEW: WP-CLI command
├── tailwind.config.js                  # Custom colors + verified heights only
├── functions.php                       # Legacy enqueues removed
└── package.json                        # Unchanged (already has Tailwind scripts)
```

**No PostCSS config needed - using Tailwind CLI `--minify` flag.**

### Build Commands (Unchanged)

```bash
# Development (watch mode)
npm run dev

# Production build (minified)
npm run build:css

# Uses existing Tailwind CLI:
npx tailwindcss -i ./src/tailwind.css -o ./assets/css/tailwind.css --minify
```

## Critical Gotchas (Simplified from Reviews): - Legacy CSS has 64 unnecessary !important declarations
   - ❌ DON'T: `.bg-red { background: red !important; }` (no conflict, no need)
   - ✅ DO: `.bg-acryl-red` (Tailwind utility, no !important needed)
   - ✅ EXCEPTION: WordPress alignment classes (.alignwide) need !important to override core

2. **Responsive Prefix Confusion** - Legacy uses hyphen (md-flex), Tailwind uses colon (md:flex)
   - ❌ DON'T: Create custom `.md-flex` utilities to preserve legacy syntax
   - ✅ DO: Find/replace all `md-` → `md:` in templates AND database

3. **Color Naming Conflicts** - Legacy `gray-1` conflicts with Tailwind `gray-100`
   - ❌ DON'T: Override Tailwind's gray scale in config
   - ✅ DO: Use `acryl-gray-1` prefix for custom colors

4. **Over-layering** - Putting everything in @layer components increases specificity
   - ❌ DON'T: `@layer components { .flex { @apply flex; } }` (already in utilities!)
   - ✅ DO: Use @layer components only for complex patterns (Swiper customizations)

5. **@apply Abuse** - Using @apply for every CSS property bloats bundle
   - ❌ DON'T: `.slider { @apply relative overflow-hidden w-full h-auto ...20 more utilities; }`
   - ✅ DO: Use inline Tailwind classes in template.php for simple utilities
   - ✅ DO: Use @apply for complex, repeated patterns only

6. **Pixel-Perfect Height/Width Overload** - Legacy has 15+ custom heights (h-124, h-140, etc.)
   - ❌ DON'T: Add ALL to config (increases bundle size)
   - ✅ DO: Audit usage first, only add heights actually used by 2+ blocks
   - ✅ CONSIDER: Use h-[31rem] (arbitrary values) for one-off heights

## Risk Analysis & Mitigation (Simplified)

| Risk | Impact | Likelihood | Mitigation |
|------|--------|------------|------------|
| **Database content breaks after CSS deploy** | CRITICAL | HIGH | WP-CLI command with --dry-run test first, database backup before deploy |
| **Visual regressions in production** | HIGH | MEDIUM | Test matrix for all 27 blocks, staging deploy first, 48h monitoring |
| **72KB duplicate CSS in editor** | HIGH | HIGH | Phase 1 removes legacy enqueues immediately (eliminated automatically) |
| **CSS class name conflicts** | MEDIUM | LOW | Use acryl-* prefix for custom colors to avoid Tailwind conflicts |
| **Swiper.js CSS breaks** | MEDIUM | LOW | Keep Swiper CSS in @layer components, test navigation/pagination |
| **WordPress editor breaks** | HIGH | LOW | WordPress classes in main tailwind.css with !important for specificity |
| **Browser caching causes stale CSS** | MEDIUM | HIGH | filemtime() on ALL style enqueues for cache busting |
| **Rollback is difficult** | MEDIUM | LOW | Atomic deployment (CSS + database + templates together), single rollback procedure |

### Rollback Procedure (Simplified from DHH/Kieran reviews)

**Single Atomic Rollback:**

```bash
# Step 1: Restore database (if database migration was run)
wp db import backups/backup-pre-css-migration-YYYYMMDD-HHMMSS.sql

# Step 2: Revert all code changes
git revert <migration-commit-sha>
git push origin master

# Step 3: Clear caches
wp cache flush
wp transient delete --all

# Step 4: Verify restoration
# Check a few pages to ensure classes are back to old format
wp db query "SELECT COUNT(*) FROM wp_posts WHERE post_content LIKE '%class=\"md:%';"
# Should return 0 if successfully rolled back

# Done
```

**That's it.** No multi-tier rollback strategies. No partial rollbacks. Atomic deployment = atomic rollback.

**Rollback Testing (from Kieran):**
Test this procedure on staging BEFORE production deploy. If rollback doesn't work in staging, fix it before going to production.

## Technical Specifications

### File Structure After Migration

```
themes/acrylicon-2024/
├── src/
│   ├── tailwind.css                    # Main Tailwind source (with @import components)
│   ├── gutenberg-compat.css            # NEW: WordPress-specific classes
│   └── components/
│       ├── slider-block.css            # NEW: Complex Swiper styles
│       └── text-scroller.css           # NEW: Animation @keyframes
├── assets/
│   ├── css/
│   │   ├── tailwind.css                # Compiled (includes all components)
│   │   ├── gutenberg-compat.css        # Compiled WP classes
│   │   ├── gravity.css                 # KEEP: Gravity Forms (unchanged)
│   │   ├── utility.css                 # DELETE after monitoring
│   │   ├── utility-md.css              # DELETE after monitoring
│   │   └── utility-lg.css              # DELETE after monitoring
│   └── ...
├── blocks/
│   ├── slider-block/
│   │   ├── template.php                # MODIFIED: Use Tailwind classes
│   │   ├── style.css                   # DELETE: Migrated to src/components/
│   │   └── ...
│   ├── text-scroller/
│   │   ├── template.php                # MODIFIED
│   │   ├── style.css                   # DELETE
│   │   └── ...
│   ├── info-card/
│   │   ├── template.php                # MODIFIED
│   │   ├── style.css                   # DELETE
│   │   └── ...
│   └── ... (24 more blocks)
├── tailwind.config.js                  # MODIFIED: Add custom colors, heights
├── functions.php                       # MODIFIED: Remove legacy enqueues
├── package.json                        # Unchanged (already has Tailwind scripts)
└── README.md                           # UPDATED: Document Tailwind workflow
```

### Tailwind Config Extensions

```javascript
// tailwind.config.js
module.exports = {
  content: [
    './*.php',
    './blocks/**/*.php',
    './assets/**/*.js',
    './assets/components/**/*.php',
  ],
  theme: {
    extend: {
      colors: {
        'acryl-red': '#E2241C',
        'acryl-dark-blue': '#253761',
        'acryl-light-blue': '#D5EDF7',
        'acryl-beige': {
          'light': '#DEDCCD',
          'lighter': '#F2F1E8',
          'lightest': '#F9F9F5',
        },
        'acryl-black': '#2B3338',
        'acryl-gray': {
          '1': '#6E7272',
          '2': '#8D9191',
          '3': '#626262',
        }
      },
      height: {
        '124': '31rem',  // 496px
        '140': '35rem',  // 560px
        '150': '37.5rem', // 600px
        '160': '40rem',  // 640px
        '172': '43rem',  // 688px
        '180': '45rem',  // 720px
        '192': '48rem',  // 768px
        '200': '50rem',  // 800px
        '300': '300px',
        '500': '500px',
        '600': '600px',
      },
      width: {
        '108': '26rem',  // 416px
      },
      maxWidth: {
        '420': '420px',
      },
      screens: {
        'md': '640px',   // Match legacy breakpoint
        'lg': '960px',   // Match legacy breakpoint
      }
    }
  },
  plugins: []
}
```

### Build Commands

```bash
# Development (watch mode)
npm run dev
# or
npm run watch:css

# Production build (minified)
npm run build:css

# Commands run Tailwind CLI:
npx tailwindcss -i ./src/tailwind.css -o ./assets/css/tailwind.css --minify
```

## References & Research

### Internal References

**Repository Research:**
- agentId: ae5b957 (initial CSS build analysis)
- agentId: a602bd2 (deep dive legacy CSS analysis)
- `themes/acrylicon-2024/tailwind.config.js:1-50` - Current Tailwind config
- `themes/acrylicon-2024/src/tailwind.css:1-200` - Current Tailwind source with @layer utilities
- `themes/acrylicon-2024/functions.php:86-104` - Legacy CSS enqueues to remove
- `themes/acrylicon-2024/assets/css/utility.css:1-753` - Legacy utilities to migrate

**Learnings Research:**
- agentId: a3b7bb9 (institutional knowledge about CSS migrations)
- No `docs/solutions/` exists yet - this migration will create first entry

**SpecFlow Analysis:**
- agentId: a700227 (edge case analysis and critical questions)
- Identified 13 critical questions and testing gaps

**Similar Patterns:**
- Current `src/tailwind.css` already uses @layer utilities for custom classes
- Block editor already loads separate CSS via `enqueue_block_editor_assets` hook
- Cache busting via `filemtime()` already implemented in functions.php:42

### External References

**From framework-docs-researcher agent:**

**Tailwind CSS v3.4.x Documentation:**
- [Adding Custom Styles](https://tailwindcss.com/docs/adding-custom-styles) - @layer utilities vs @layer components
- [Extracting Components with @layer](https://tailwindcss.com/docs/extracting-components) - When to use @apply
- [Customizing Colors](https://tailwindcss.com/docs/customizing-colors) - Extending color palette
- [Content Configuration](https://tailwindcss.com/docs/content-configuration) - Safelist for dynamic classes
- [Using with Preprocessors](https://tailwindcss.com/docs/using-with-preprocessors) - PostCSS setup
- [Optimizing for Production](https://tailwindcss.com/docs/optimizing-for-production) - PurgeCSS and minification
- [Functions & Directives](https://tailwindcss.com/docs/functions-and-directives) - @tailwind, @layer, @apply

**WordPress Block Editor (Gutenberg):**
- [Block Editor Handbook - Themes](https://developer.wordpress.org/block-editor/how-to-guides/themes/) - Theme.json and editor styles
- [Enqueueing Assets in the Editor](https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#enqueuing-assets-in-the-editor) - enqueue_block_editor_assets hook
- [Editor Styles](https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#editor-styles) - add_editor_style() vs wp_enqueue_style()
- [Block Supports](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/) - Alignment, spacing, color supports
- [Layout Support](https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-json/#layout) - .is-layout-grid, .is-layout-flex

**Swiper.js v11.x + Tailwind:**
- [Swiper Tailwind CSS](https://swiperjs.com/blog/swiper-11#tailwind-css) - Official Tailwind integration
- [Swiper API - Navigation](https://swiperjs.com/swiper-api#navigation) - Custom arrow styling
- [Swiper API - Pagination](https://swiperjs.com/swiper-api#pagination) - Custom bullet styling
- [Swiper API - Autoplay](https://swiperjs.com/swiper-api#autoplay) - pauseOnMouseEnter

**PostCSS + Tailwind:**
- [PostCSS Documentation](https://postcss.org/) - Plugin architecture
- [Autoprefixer](https://github.com/postcss/autoprefixer) - Browser prefix handling
- [cssnano](https://cssnano.co/) - CSS minification and optimization

**WordPress Database Best Practices:**
- [WordPress Database Description](https://codex.wordpress.org/Database_Description) - wp_posts, wp_postmeta structure
- [WP-CLI DB Commands](https://developer.wordpress.org/cli/commands/db/) - export, import, query
- [Transients API](https://developer.wordpress.org/apis/handbook/transients/) - Cache management

**CSS Migration Patterns (2024-2026):**
- [Migrating to Tailwind CSS](https://tailwindcss.com/docs/migrating-to-tailwind) - Official migration guide (NEW in v3.4)
- [Tailwind CSS Best Practices](https://tailwindcss.com/docs/reusing-styles) - Avoiding premature abstraction
- [WordPress + Tailwind CSS](https://make.wordpress.org/core/2022/10/10/updating-editor-styles-for-tailwind-css/) - Official WordPress guidance

### Related Work

**Brainstorm Document:**
- `docs/brainstorms/2026-01-26-migrate-legacy-css-to-tailwind-brainstorm.md`

## References

### Research Agents Used
- **code-simplicity-reviewer** (agentId: a275b82): 40% LOC reduction, identified over-engineering
- **performance-oracle** (agentId: a65aaeb): 72KB duplicate CSS, PostCSS analysis
- **pattern-recognition-specialist** (agentId: a04f248): Anti-patterns, !important overuse
- **data-integrity-guardian** (agentId: a815118): Database migration risks
- **best-practices-researcher** (agentId: a718e52): 2024-2026 Tailwind migration patterns
- **framework-docs-researcher** (agentId: a8060f2): Tailwind v3.4.x + WordPress Gutenberg docs
- **repo-research-analyst** (agentId: a602bd2, ae5b957): Legacy CSS analysis, current setup
- **DHH Rails reviewer** (agentId: a2bf103): Pragmatic simplification feedback
- **Kieran Rails reviewer** (agentId: a08590d): Professional quality standards, critical fixes
- **Code simplicity reviewer** (agentId: aac03af): YAGNI analysis, phase reduction

### Key Documentation

**Tailwind CSS:**
- [Tailwind v3.4 Docs](https://tailwindcss.com/docs)
- [Using with WordPress](https://tailwindcss.com/docs/guides/wordpress)
- [Extracting Components with @layer](https://tailwindcss.com/docs/extracting-components)

**WordPress + Gutenberg:**
- [Block Editor Handbook](https://developer.wordpress.org/block-editor/)
- [Enqueueing Editor Assets](https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#enqueuing-assets-in-the-editor)

**Swiper.js:**
- [Swiper v11 Tailwind Integration](https://swiperjs.com/blog/swiper-11#tailwind-css)

### Brainstorm Document
- `docs/brainstorms/2026-01-26-migrate-legacy-css-to-tailwind-brainstorm.md`

---

**Ready to implement.** Estimated: 11-15 hours. Reviewed and simplified from 30-41 hours.
