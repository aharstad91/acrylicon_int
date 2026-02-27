---
module: WordPress Multisite Taxonomies
date: 2026-02-26
problem_type: database_issue
component: database
symptoms:
  - "get_the_terms() returns stale/wrong taxonomy data after direct SQL updates"
  - "wp term create fails with 'En term med dette navnet finnes allerede'"
  - "Terms assigned via wp_3_term_relationships not visible in WordPress queries"
root_cause: config_error
resolution_type: code_fix
severity: critical
tags: [wordpress-multisite, shared-taxonomies, mu-plugin, taxonomy-tables, object-cache]
---

# Troubleshooting: WordPress Multisite Shared Taxonomy Tables — Wrong Table Prefix

## Problem
In the Acrylicon WordPress Multisite, direct SQL operations on blog-prefixed taxonomy tables (`wp_3_terms`, `wp_3_term_relationships`, `wp_3_term_taxonomy`) silently fail because the `acrylicon-shared-taxonomies.php` mu-plugin forces ALL blogs to use the main site's tables (`wp_terms`, `wp_term_relationships`, `wp_term_taxonomy`).

## Environment
- Module: WordPress Multisite Taxonomies
- WordPress: 6.8.3
- PHP: 8.4 (prod), 8.1 (local)
- Affected Component: `mu-plugins/acrylicon-shared-taxonomies.php`, all taxonomy operations on Blog 3 (NO)
- Date: 2026-02-26

## Symptoms
- `get_the_terms()` returns stale/wrong taxonomy data after inserting rows into `wp_3_term_relationships`
- `wp term create referanser-type "case-study" --url=.../no/` fails with "En term med dette navnet finnes allerede" — the term exists in `wp_terms` but not in `wp_3_terms`
- Direct SQL `INSERT INTO wp_3_term_relationships` has no effect on WordPress queries
- Term counts in `wp_3_term_taxonomy` don't match what WordPress reports
- WP object cache returns old data even after `wp_cache_flush()`

## What Didn't Work

**Attempted Solution 1:** Insert term relationships into `wp_3_term_relationships` (blog-prefixed table)
- **Why it failed:** The mu-plugin overrides `$wpdb->terms`, `$wpdb->term_taxonomy`, and `$wpdb->term_relationships` for ALL blogs to point to the main site tables. WordPress never reads from `wp_3_*` taxonomy tables.

**Attempted Solution 2:** Create term via `wp term create` on Blog 3
- **Why it failed:** The term already existed in the shared `wp_terms` table (main site). WP-CLI correctly reported it already exists, but checking `wp_3_terms` showed it missing — because `wp_3_terms` is not used.

**Attempted Solution 3:** Flush WP object cache after direct SQL updates to wrong tables
- **Why it failed:** Flushing cache doesn't help when data was written to the wrong table entirely. `clean_object_term_cache()`, `clean_term_cache()`, and `wp_cache_flush()` all returned clean results but the data simply wasn't in the table WordPress was reading from.

## Solution

All taxonomy operations must use the **main site tables** (no blog prefix):

```sql
-- WRONG: Blog-prefixed tables (these are IGNORED by WordPress)
INSERT INTO wp_3_term_relationships (object_id, term_taxonomy_id) VALUES (1790, 94);
SELECT * FROM wp_3_terms WHERE slug = 'case-study';

-- CORRECT: Main site tables (shared across all blogs)
INSERT INTO wp_term_relationships (object_id, term_taxonomy_id) VALUES (1790, 94);
SELECT * FROM wp_terms WHERE slug = 'case-study';
```

For the specific fix, we moved 10 reference posts from `new-reference` (term_taxonomy_id=93) to `case-study` (term_taxonomy_id=94):

```sql
-- Remove old assignment
DELETE FROM wp_term_relationships
WHERE object_id IN (1790,2039,2063,2145,2382,2434,2464,3115,3173,5697)
  AND term_taxonomy_id = 93;

-- Add new assignment
INSERT INTO wp_term_relationships (object_id, term_taxonomy_id, term_order)
SELECT id, 94, 0 FROM (
  SELECT 1790 AS id UNION ALL SELECT 2039 ...
) AS t
WHERE NOT EXISTS (
  SELECT 1 FROM wp_term_relationships
  WHERE object_id = t.id AND term_taxonomy_id = 94
);

-- Update term counts
UPDATE wp_term_taxonomy SET count = (
  SELECT COUNT(*) FROM wp_term_relationships WHERE term_taxonomy_id = 94
) WHERE term_taxonomy_id = 94;
```

Also cleaned up the erroneously created entries in `wp_3_*` tables.

## Why This Works

1. **ROOT CAUSE:** The `acrylicon-shared-taxonomies.php` mu-plugin hooks into WordPress early and overrides `$wpdb->terms`, `$wpdb->term_taxonomy`, and `$wpdb->term_relationships` for every blog in the multisite. This means Blog 3's taxonomy data lives in the same tables as Blog 1's — the `wp_3_*` prefixed tables are never read by WordPress.

2. **Why the solution works:** By operating on `wp_term_relationships` (no prefix), we write to the actual table WordPress reads from. The term `case-study` already existed in `wp_terms` with the correct `term_taxonomy_id` in `wp_term_taxonomy`.

3. **Underlying architecture:** The shared taxonomy mu-plugin exists so that taxonomies like `referanser-type`, `referanser-kategorier`, etc. are consistent across all blogs. A reference post on Blog 3 and its English counterpart on Blog 1 share the same taxonomy terms. This is intentional but creates a trap for anyone doing direct SQL operations.

## Prevention

- **ALWAYS check which tables WordPress is actually using** before direct SQL: run `wp db query "SHOW TABLES LIKE 'wp%term%'"` and cross-reference with `$wpdb->term_relationships` in PHP
- **Use WP-CLI or WordPress API functions** (`wp_set_object_terms()`, `wp term create`) instead of direct SQL when possible — they respect table overrides
- **When direct SQL is necessary**, verify the table name by checking the mu-plugin: `mu-plugins/acrylicon-shared-taxonomies.php`
- **Rule of thumb for this project:** All taxonomy tables are shared (no blog prefix). Post tables ARE blog-prefixed (`wp_3_posts`, `wp_3_postmeta`). This asymmetry is the core trap.
- **After direct SQL taxonomy changes**, always flush caches: `wp cache flush --url=...` and verify with `wp term list <taxonomy> --url=...`

## Related Issues

No related issues documented yet.
