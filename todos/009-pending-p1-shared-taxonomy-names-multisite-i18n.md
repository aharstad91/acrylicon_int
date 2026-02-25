---
status: pending
priority: p1
issue_id: "009"
tags: [multisite, i18n, taxonomy, seo, shared-taxonomies]
dependencies: []
---

# Shared Taxonomy Term Names — Multisite i18n Strategy

## Problem Statement

`acrylicon-shared-taxonomies.php` (mu-plugin) forces all blogs to share taxonomy tables from main site. This means taxonomy term names are identical on both NO and EN blogs.

**Current state:**
- Product CPT titles use Norwegian suffixes: "Dekor System – Gulv", "Flake System – Gulv"
- Taxonomy terms use English suffixes: "Decor System – Floor", "Flake System – Floor"
- These terms appear in meta descriptions, reference filters, and potentially schema markup

**Why it matters:**
- Norwegian meta descriptions show English term names ("referanseprosjekt med Flake System – Floor")
- When EN blog gets more content, English pages will also use these same terms — but some are mixed language
- Any taxonomy-based display (filters, widgets, schema) inherits this inconsistency
- Fixing it later becomes harder as more features depend on taxonomy terms

## Affected Areas

1. `inc/meta-descriptions.php` — referanser meta descriptions show term names
2. Reference filtering (client-side JS pills built from term names)
3. Future JSON-LD structured data that references product terms
4. Admin UI (Yoast, ACF, taxonomy columns)

## Options to Consider

1. **Rename terms to Norwegian:** Simple, but affects EN blog too (shared tables). Product names are already in mixed language on both blogs.
2. **Mapping layer in PHP:** Array that translates term names per blog_id. More code, but accurate per language.
3. **Separate taxonomy tables per blog:** Remove shared-taxonomies plugin, maintain terms per blog. Biggest change, most correct for i18n.
4. **Use product CPT titles instead of term names:** Query the matching product post and use its title (which differs per blog via multisite-sync). Most semantic, but requires reliable product↔term mapping.

## Recommendation

Needs discussion — this is an architectural decision that affects the entire multisite i18n strategy, not just meta descriptions. Should be resolved before adding more taxonomy-dependent features (schema markup, product filters, reference generators).
