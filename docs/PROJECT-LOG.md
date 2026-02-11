# PROJECT-LOG – AcryliCon

> Strategisk prosjektlogg. Oppdateres etter hver /full-sesjon.

---

## 2026-02-11 – Tailwind deploy + HTTPS

### Beslutninger
- **Tailwind deployet til prod:** Rsync av hele temaet fra branch `refactor/migrate-legacy-css-to-tailwind` (6 commits). 6 legacy block style.css-filer slettet, stiler migrert til tailwind.css. functions.php oppdatert med ny enqueue-logikk.
- **Database URL-fiks:** 10 327 `localhost:8888`-referanser erstattet med prod-URL via `wp search-replace`. Forsiden hadde bl.a. en video med localhost-src som ikke lastet.
- **HTTPS aktivert:** 10 649 `http://` → `https://` erstatninger. Servebolt hadde allerede SSL — bare WordPress-konfig manglet. HTTP redirecter nå til HTTPS (301).
- **DB-passord resatt:** Nytt passord for phpMyAdmin-tilgang. wp-config.php oppdatert. Credentials lagret i `docs/security/credentials.md` (gitignored).
- **Deploy-prosess dokumentert:** Rsync-kommando og cache-flush lagt til i CLAUDE.md.

### Løste issues fra forrige sesjon
- ~~SSL/HTTPS på produksjon~~ — Fikset
- ~~Tailwind-migrering ikke deployet~~ — Fikset
- ~~Deploy-strategi udokumentert~~ — Rsync-prosess dokumentert

### Parkert / Åpne spørsmål
- **11GB uploads-mappe:** Fortsatt uløst. Trenger CDN/R2-løsning.
- **Multisite content sync plugin:** Fortsatt ikke deployet. 7 P1 todos bør fikses først.
- **PageSpeed:** Nå som Tailwind er deployet, bør vi måle og optimalisere.
- **Lokal DB ut av sync:** Lokal DB har fortsatt `localhost`-URLer. Funker for lokal utvikling, men vær obs ved fremtidig DB-sync.

### Retning
Første runde deploy er gjennomført — temaet og HTTPS er på plass. Neste naturlige steg:
1. **PageSpeed-måling og optimalisering** (kritisk for AI Search-synlighet)
2. **Fiks P1 todos** i sync-pluginen (spesielt sikkerhet: credential exposure, file upload validation)
3. **Gravity Forms + Zapier → SuperOffice** (erstatte iFrames)

---

## 2026-02-11 – Internasjonal brief mottatt

### Beslutninger
- Lagret briefen i `docs/strategy/international-brief.md` for fremtidig referanse
- Briefen definerer Fase 2 av prosjektet: acrylicon.com (internasjonal side)

### Parkert / Åpne spørsmål
- **Fase 2 vs. nåværende acrylicon.no:** Skal arbeid på .no fullføres først (Tailwind-deploy, PageSpeed, SSL)?
- **Multisite vs. separate installasjoner:** Briefen sier "separate eller multisite" – vi har allerede multisite-infrastruktur. Beslutning trengs.
- **150k-estimat:** Hva er scoped inn, hva er ute? Trenger nedbrytning i konkrete leveranser.
- **Gravity Forms:** Lisens kjøpt? Zapier-konto satt opp?
- **Hotjar:** Konto opprettet?
- **CDN-valg:** Servebolt har innebygd CDN, men R2 kan være bedre for 11GB uploads

### Retning
To parallelle spor:
1. **Kort sikt:** Ferdigstille acrylicon.no (deploy Tailwind, PageSpeed, SSL)
2. **Medium sikt:** Bygge acrylicon.com (Fase 2 fra briefen)

Anbefaler å få .no i god stand først – det er lettere å bygge .com på et solid fundament enn å starte noe nytt mens det gamle halter.

### Observasjoner
- Briefen er godt strukturert men mangler prioritering – alt er like viktig, og det er det aldri
- 150k er et realistisk budsjett for dette scopet, men trenger tydelige faser/milepæler
- Mobile-first med 65,9% mobiltrafikk er riktig prioritering
- Referansesystemet (filtre, søk, lazy load) kan bli det mest komplekse enkeltelementet

---

## 2026-02-11 – Miljø-setup og dokumentasjonsbase

### Beslutninger
- **Servebolt SSH/WP-CLI:** Satt opp nøkkelautentisering og WP-CLI-tilgang til produksjon, lik bimverdi-v2-oppsettet. Gir direkte databasetilgang fra begge miljøer.
- **Produktsync:** 12 produkter synkronisert fra prod til lokal via WP export/import. Databasene er nå identiske.
- **Docs-struktur:** Valgte flat tematisk struktur med 3 mapper (context/, strategy/, architecture/) fremfor alt-i-én eller dyp hierarkisk struktur. 11 nye markdown-filer basert på ACRYLICON_CONTEXT.md.
- **AUTH_COOKIE fix:** Løste Yoast SEO multisite-bug med eksplisitte cookie-definisjoner i wp-config.php på begge miljøer.
- **WP Fastest Cache:** Skippes i WP-CLI for å unngå HTML-dump.

### Parkert / Åpne spørsmål
- **SSL/HTTPS på produksjon:** Siteurl er fortsatt http://. Bør settes opp.
- **11GB uploads-mappe:** Trenger CDN/R2-løsning (plan finnes).
- **Deploy-strategi:** Manuell deploy. Trenger en prosess for å flytte tema/plugins fra lokal til prod.
- **Multisite content sync plugin:** Utviklet men ikke deployet. Når skal den deployes?
- **7 åpne P1 todos:** Ikke tatt tak i denne sesjonen.
- **Tailwind-migrering:** 6 commits foran master, ikke deployet.

### Retning
Prosjektet er i en **stabiliseringsfase** – miljøet er nå satt opp med full tilgang og dokumentasjon. Neste naturlige steg:
1. Deploy Tailwind-migreringen (allerede ferdig, bare trenger push)
2. PageSpeed-forbedring (kritisk for AI Search-synlighet)
3. Graviy Forms + Zapier → SuperOffice (erstatte iFrames)

Dokumentasjonsbasen gir et solid fundament for å jobbe effektivt fremover. Hver Claude Code-sesjon har nå full kontekst uten å måtte lese 401-linjers kontekstfilen.

### Observasjoner
- **Effektivitetsgevinst:** WP-CLI på begge miljøer + synkroniserte databaser gjør innholdsarbeid mye raskere
- **Docs vs. CLAUDE.md:** God arbeidsdeling – CLAUDE.md har quick reference, docs/ har dybde
- **Teknisk gjeld:** Mye er planlagt men lite deployet (multisite-sync, Tailwind, mu-plugins). Bør prioritere å få ting ut i prod fremfor flere nye features
- **PageSpeed er kritisk:** 57–68 vs. konkurrenter på 90+ betyr at AcryliCon er usynlig i AI-søk. Dette bør være topp-prioritet etter deploy
