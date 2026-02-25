# Custom SEO-modul — Erstatt Yoast SEO

**Dato:** 2026-02-25
**Status:** Besluttet

---

## Hva vi bygger

En komplett custom SEO mu-plugin ("AcryliCon SEO") som erstatter Yoast SEO med full kontroll over all SEO-output. Siden er ikke live ennå, så vi kan fjerne Yoast umiddelbart uten migrasjonsrisiko.

### Moduler

1. **Meta Titles** — Auto-genererte titler med maler per CPT, multisite-aware
2. **Meta Descriptions** — Allerede bygget (`inc/meta-descriptions.php`), flyttes til mu-plugin
3. **JSON-LD Schema** — Organization, Product, LocalBusiness, Service, Article
4. **Open Graph / Social** — OG tags + Twitter Card meta for alle sider
5. **Canonical URLs** — Selvhenvisende canonical, multisite-aware
6. **Robots Meta** — noindex/nofollow kontroll per side
7. **Admin Metabox** — Google snippet preview, "Regenerer SEO"-knapp, visuell feedback til redaktører
8. **XML Sitemaps** — Bruker WordPress core (`wp-sitemap.xml`), ingen custom

### Utenfor scope

- Keyword-analyse / readability score (Yoast-feature vi aldri brukte)
- Redirect-manager (kan legges til senere)
- Social media preview (kun meta tags, ikke visuell preview i admin)
- Avanserte sitemaps (bildekart, nyhetskart)

---

## Hvorfor denne tilnærmingen

### Motivasjon
- **Full kontroll:** Nøyaktig kontroll over hva som outputtes i HTML — ingen black box
- **Ytelse:** Yoast er tungt (mange DB-queries, admin JS/CSS bloat). Lett mu-plugin gir raskere sider
- **Multisite-klar:** Bygget for vår spesifikke multisite-struktur (blog 1 EN, blog 3 NO)

### Arkitekturvalg: mu-plugin
- Must-use plugin — alltid aktiv, kan ikke deaktiveres ved uhell
- Uavhengig av tema — overlever temabytte
- Passer for SEO som site-wide infrastruktur
- Struktur: `mu-plugins/acrylicon-seo/` med autoloader

### Migrasjonsstrategi: Direkte erstatning
- Siden er ikke live ennå — ingen SEO-equity å miste
- Fjern Yoast, aktiver custom modul, verifiser output
- Eksisterende `inc/meta-descriptions.php` flyttes til mu-plugin

---

## Nøkkelbeslutninger

| Beslutning | Valg | Begrunnelse |
|-----------|------|-------------|
| Plassering | mu-plugin | Alltid aktiv, temauavhengig |
| Migrering | Big bang (fjern Yoast) | Siden ikke live, ingen risiko |
| Sitemaps | WordPress core | wp-sitemap.xml dekker behovet |
| Admin UI | Google snippet preview + regenerer-knapp | Redaktører trenger visuell feedback |
| SEO-verdier | Auto-generert fra kode, lagret i postmeta | Kan overstyre manuelt, caches for ytelse |
| Keyword-analyse | Utelatt | Aldri brukt, unødvendig kompleksitet |

---

## Admin UI — Konsept

Metabox i WordPress editor ("AcryliCon SEO"):

```
┌─────────────────────────────────────────────────┐
│  AcryliCon SEO                                  │
│                                                 │
│  Google Preview:                                │
│  ┌─────────────────────────────────────────────┐│
│  │ AcryliCon Flake System – Gulv | AcryliCon   ││
│  │ acrylicon.no › produkter › flake-system-gulv ││
│  │ Dekorativ overflate med sklisikring og...    ││
│  └─────────────────────────────────────────────┘│
│                                                 │
│  Meta Title:    [auto-generert]     [✏️ Endre]  │
│  Description:   [auto-generert]     [✏️ Endre]  │
│  Canonical:     [auto]                          │
│  Robots:        [index, follow ▼]               │
│                                                 │
│  [🔄 Regenerer SEO]    Status: ✅ OK            │
│                                                 │
│  Schema: Organization, Product                  │
│  OG Image: featured image (1200x630)            │
└─────────────────────────────────────────────────┘
```

- Auto-genererte verdier vises, med mulighet for manuell overstyring
- "Regenerer SEO"-knapp tvinger ny generering fra innholdsdata
- Statusindikator: OK / Mangler description / For lang tittel
- Viser hvilke schema-typer som outputtes

---

## Tekniske hensyn

### Multisite
- `get_current_blog_id()` for språkdeteksjon (3 = NO, 1 = EN)
- CPT-slugs via `acrylicon_get_cpt_slugs()`
- hreflang allerede custom i `inc/language-switcher.php` — beholdes
- Taxonomy ID er fast (`referanser-produkter`), uavhengig av blog

### Yoast-avhengigheter å erstatte
1. `wpseo_metadesc` filter → egen output i `wp_head`
2. Yoast title tag → WordPress `title-tag` support + `document_title_parts` filter
3. Yoast canonical → egen `<link rel="canonical">` i `wp_head`
4. Yoast robots → egen `<meta name="robots">` i `wp_head`
5. Yoast schema → egen JSON-LD output i `wp_head` eller `wp_footer`
6. Yoast OG → egne `<meta property="og:*">` i `wp_head`

### Postmeta-strategi
- `_acrylicon_seo_title` — manuell overstyring av tittel
- `_acrylicon_seo_description` — manuell overstyring av description
- `_acrylicon_seo_robots` — noindex/nofollow per side
- `_acrylicon_seo_canonical` — custom canonical URL
- Alle felter valgfrie — auto-generering er default

---

## Åpne spørsmål

1. **OG-bilde:** Skal vi alltid bruke featured image, eller ha mulighet for eget OG-bilde per side?
2. **Schema-dybde:** Hvor detaljert skal Product-schema være? (pris, tilgjengelighet, eller bare navn/beskrivelse?)
3. **Innholdsendrings-deteksjon:** Skal "regenerer SEO" trigges automatisk ved save_post, eller bare manuelt?

---

## Relasjon til eksisterende arbeid

- **SEO-007 (meta descriptions):** Allerede implementert i `inc/meta-descriptions.php` — flyttes til mu-plugin
- **Ralph SEO-roadmap:** SEO-001 til SEO-008 i `ralph/prd.json` — schema/OG-stories absorberes av denne modulen
- **Todo 009 (taxonomy term names):** Påvirker schema og meta — bør løses parallelt
- **hreflang (language-switcher.php):** Beholdes i temaet — er UI-koblet, ikke ren SEO
