# WordPress-arkitektur

> Sist oppdatert: 2026-02-11
> Quick reference i CLAUDE.md – dette dokumentet utdyper.

---

## Multisite-konfigurasjon

- **Type:** Subdirectory (ikke subdomain)
- **Blog 1:** `/` – Hovedsite
- **Blog 3:** `/norway/` – Norge-subsite
- AUTH_COOKIE fix i wp-config.php (Yoast SEO multisite-bug)

## Custom Post Types (utdypet)

### Med arkivside
| CPT | Slug | Hierarkisk | Supports |
|-----|------|-----------|----------|
| industrier | `industrier` | Ja | title, editor, thumbnail, revisions, excerpt |

### Uten arkivside
| CPT | Slug | URL-slug | Supports |
|-----|------|----------|----------|
| kontor | `kontor` | kontor | title, editor, thumbnail, revisions, excerpt |
| produkter | `produkter` | produkter | title, editor, thumbnail, revisions, excerpt |
| bruksomrader | `bruksomrader` | bruksomrader | title, editor, thumbnail, revisions, excerpt |
| godegrunner | `godegrunner` | gode-grunner | title, editor, thumbnail, revisions, excerpt |
| levetidskostnader | `levetidskostnader` | levetids-kostnader | title, editor, thumbnail, revisions, excerpt |
| baerekreaftig | `baerekreaftig` | baerekraft | title, editor, thumbnail, revisions, excerpt |
| referanser | `referanser` | referanser | editor, title, custom-fields, thumbnail |

**Merk:** `referanser` har en block template med fast struktur (heading, paragraph, synlig blokk ref 4419, info-card).

## Taxonomier (alle på `referanser`)

| Taxonomy | Slug | Label | Rewrite |
|----------|------|-------|---------|
| referanser-type | referanser-type | Referansetype | – |
| referanser-kategorier | referanser-kategorier | Produktområder | referanse-kategori |
| referanser-kontor | referanser-kontor | Kontor | referanse-kontor |
| referanser-produkter | referanser-produkter | Produkter | referanse-produkter |

## Viktige page-IDs

| Side | ID | Slug |
|------|----|------|
| Produkter | 80 | produkter |
| Kontor | 82 | kontor |
| Referanser | 84 | referanser |
| Om Acrylicon | 86 | om-acrylicon |
| Sertifiseringer | 324 | sertifiseringer |
| Bruksområder | 1858 | bruksomrader |
| Informasjonskapsler | 2268 | – |
| Nedlastinger | 2336 | – |
| Forside | 4540 | forside |
| Gode grunner | 4790 | gode-grunner |
| Levetidskostnader | 4793 | – |
| Bærekraftig | 4795 | – |
| Fordeler | 4798 | fordeler |
| Komponenter under utvikling | 5025 | – |
| Nyhetsbrev | 5624 | – |
| Karriere | 5717 | karriere |

## Menyer (6 stk)

- `primary-menu` – Hovedmeny
- `footer-one` – Bunn: Store bokstaver
- `footer-two` – Bunn: Små bokstaver venstre
- `footer-three` – Bunn: Midten
- `footer-four` – Bunn: Høyre
- `mobile` – Mobilmeny

## wp-config.php spesialiteter

- `DB_HOST`: Socket-basert (`localhost:/Applications/MAMP/tmp/mysql/mysql.sock`)
- AUTH_COOKIE, SECURE_AUTH_COOKIE, LOGGED_IN_COOKIE definert eksplisitt (fix for Yoast+multisite)
- WP Fastest Cache skippes i WP-CLI (`wp-cli.yml`)
