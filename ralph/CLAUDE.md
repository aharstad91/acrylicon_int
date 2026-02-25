# Ralph Agent Instructions — Acrylicon Technical SEO

You are an autonomous coding agent working on the Acrylicon WordPress multisite project.

## Project Context

- **WordPress root:** `/Applications/MAMP/htdocs/acrylicon/`
- **Git root:** `/Applications/MAMP/htdocs/acrylicon/wp-content/`
- **Theme:** `themes/acrylicon-2024/`
- **Local URL:** `http://localhost:8888/acrylicon/` (blog 1, EN) and `http://localhost:8888/acrylicon/no/` (blog 3, NO)
- **Production URL:** `https://acryli-28355.jana-osl.servebolt.cloud`
- **PHP:** 8.1 (local), 8.4 (production)
- **Plugins:** ACF Pro, Yoast SEO v26.8, WP Fastest Cache

### Multisite Structure
- **Blog 1** (`/`): International (English)
- **Blog 3** (`/no/`): Norway (Norwegian) — primary content source

### Key Files
- `themes/acrylicon-2024/functions.php` — CPTs, enqueue, hooks, ACF blocks
- `themes/acrylicon-2024/inc/language-switcher.php` — hreflang tags (already implemented)
- `themes/acrylicon-2024/header.php` — wp_head, GTM, analytics
- `themes/acrylicon-2024/footer.php` — footer content
- `themes/acrylicon-2024/blocks/` — 26 ACF blocks
- `themes/acrylicon-2024/src/tailwind.css` — Tailwind source
- `themes/acrylicon-2024/tailwind.config.js` — custom colors + breakpoints

### Custom Post Types (blog 3 / NO slugs)
- `produkter` — 12 product systems (ACF fields: product_excerpt, etc.)
- `referanser` — 100+ references (ACF fields: referance_productsystem, etc.)
- `bruksomrader` — Application areas
- `kontor` — 4 office locations (ACF fields: address, phone, etc.)
- `industrier` — Industries
- `gode-grunner` — Good reasons
- `levetids-kostnader` — Lifecycle costs
- `baerekraft` — Sustainability

### Taxonomies (on referanser)
- `referanser-type` — Reference type
- `referanse-kategori` — Product areas
- `referanse-kontor` — Office
- `referanse-produkter` — Products

## Your Task

1. Read the PRD at `ralph/prd.json` (relative to git root `wp-content/`)
2. Read the progress log at `ralph/progress.txt` (check Codebase Patterns section first)
3. Check you're on the correct branch from PRD `branchName`. If not, check it out or create from main.
4. Pick the **highest priority** user story where `passes: false`
5. Implement that single user story
6. Run quality checks (see below)
7. If checks pass, commit ALL changes with message: `feat: [Story ID] - [Story Title]`
8. Update the PRD to set `passes: true` for the completed story
9. Append your progress to `ralph/progress.txt`

## Quality Checks for This Project

After implementing each story, run these checks:

```bash
# 1. PHP syntax check on all changed files
find wp-content/themes/acrylicon-2024/ -name "*.php" -newer ralph/prd.json -exec php -l {} \;

# 2. Verify Tailwind builds without errors
cd wp-content/themes/acrylicon-2024 && npm run build:css 2>&1

# 3. Verify JSON-LD output is valid JSON (for schema stories)
# Use curl to fetch the page and extract JSON-LD, then validate with jq
curl -s "http://localhost:8888/acrylicon/no/" | grep -oP '<script type="application/ld\+json">\K[^<]+' | jq . > /dev/null 2>&1
```

If checks fail, fix the issue before committing.

## WordPress/PHP Patterns for This Project

- **Add hooks in functions.php** or create new files in `themes/acrylicon-2024/inc/` and require them from functions.php
- **JSON-LD output:** Use `wp_head` hook with appropriate priority
- **Multisite awareness:** Use `get_current_blog_id()` to detect NO (3) vs EN (1)
- **ACF data:** Use `get_field()` / `get_fields()` for custom field data
- **Yoast filter for meta descriptions:** `wpseo_metadesc`
- **Yoast filter for OG tags:** `wpseo_opengraph_*`
- **Production URL for schema:** Always use `https://acryli-28355.jana-osl.servebolt.cloud` as base URL in schema markup, not localhost

## Progress Report Format

APPEND to ralph/progress.txt (never replace):
```
## [Date/Time] - [Story ID]
- What was implemented
- Files changed
- **Learnings for future iterations:**
  - Patterns discovered
  - Gotchas encountered
  - Useful context
---
```

## Consolidate Patterns

If you discover a reusable pattern, add it to the `## Codebase Patterns` section at the TOP of ralph/progress.txt.

## Stop Condition

After completing a user story, check if ALL stories have `passes: true`.

If ALL stories are complete, reply with:
<promise>COMPLETE</promise>

If stories remain with `passes: false`, end your response normally.

## Important

- Work on ONE story per iteration
- Commit frequently to git root (wp-content/)
- Keep PHP syntax valid
- Read the Codebase Patterns section in progress.txt before starting
- Use existing code patterns from functions.php and inc/ files
- Test locally at http://localhost:8888/acrylicon/no/ for Norwegian site
