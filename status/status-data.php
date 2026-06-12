<?php
/**
 * Statusside-data — Acrylicon digital roadmap 2026.
 *
 * Kilde for scope: docs/strategy/digital-roadmap-2026.html (Monika godkjente
 * hele roadmapen 2026-06-12). Hver status SKAL være verifisert mot kode/prod
 * med bevis og verifisert-dato. Tvil = 'partial' med notat. Oppdateres via
 * vanlige commits — git-historikken er endringsloggen.
 *
 * Status: 'done' | 'partial' | 'missing'
 * godkjentAvTeam settes KUN etter eksplisitt menneskelig beslutning.
 */

return [
	[
		'id'      => 'j1',
		'nr'      => 1,
		'tittel'  => 'Internasjonal kunde besøker den engelske siten',
		'aktor'   => 'Internasjonal kunde / arkitekt',
		'hvorfor' => 'Den internasjonale lanseringen er porten til alle nye markeder — alt annet i roadmapen skalerer via denne.',
		'steg'    => [ 'Søker / får lenke til acrylicon.com', 'Lander på engelsk forside', 'Ser referanser og produkter på engelsk', 'Finner riktig kontor og tar kontakt' ],
		'kriterier' => [
			[
				'id' => 'j1-k1', 'tekst' => 'Engelsk site live med samme design og kvalitet som norsk',
				'status' => 'done',
				'bevis' => 'Prod blog 1 live på Servebolt; WORKLOG 2026-06-05 «internasjonale lanseringen LIVE»',
				'verifisert' => '2026-06-12',
			],
			[
				'id' => 'j1-k2', 'tekst' => 'DNS-cutover: acrylicon.no/.com peker på Servebolt med SSL',
				'status' => 'missing',
				'bevis' => 'docs/plans/cutover/ (scripts klare, validert 2026-05-27)',
				'notat' => 'acrylicon.no peker fortsatt på gammel host (93.188.2.51, verifisert 2026-06-12). Scripts og rollback klare — venter cutover-vindu.',
			],
			[
				'id' => 'j1-k3', 'tekst' => 'hreflang + canonical korrekt på begge språk',
				'status' => 'done',
				'bevis' => 'curl-verifisert på prod: en/nb/x-default begge veier (teknisk audit 2026-06-12)',
				'verifisert' => '2026-06-12',
			],
			[
				'id' => 'j1-k4', 'tekst' => 'Engelske referanser med dybdeinnhold',
				'status' => 'partial',
				'bevis' => '3 migrert til prod: Park garage (6013), AO Arena (6018), DC Valley (6024) — WORKLOG 2026-06-05',
				'notat' => 'Flere venter på at Monika identifiserer ikke-norske referanser (todos/013 #8).',
				'verifisert' => '2026-06-12',
			],
			[
				'id' => 'j1-k5', 'tekst' => 'Engelske produkt-PDF-er på Downloads',
				'status' => 'missing',
				'notat' => 'Blocked: venter engelske Tech Sheets + METAP-avklaring fra Monika (todos/013 #7).',
			],
			[
				'id' => 'j1-k6', 'tekst' => 'Benefits-siden med engelske kundenavn',
				'status' => 'missing',
				'notat' => 'Blocked: venter kundeliste fra Monika (todos/013 #6).',
			],
		],
		'godkjentAvTeam' => null,
		'kbLenke' => 'docs/plans/2026-05-27-001-feat-multisite-domain-mapping-plan.md',
	],
	[
		'id'      => 'j2',
		'nr'      => 2,
		'tittel'  => 'Arkitekt finner AcryliCon i søk',
		'aktor'   => 'Arkitekt / innkjøper / driftsansvarlig',
		'hvorfor' => '600+ profesjonelle søk/mnd der AcryliCon er usynlig — den som blir funnet først, får samtalen.',
		'steg'    => [ 'Søker «industrigulv» / «gulvbelegg næringsmiddel»', 'Finner AcryliCon på første side / i AI-svar', 'Leser referanse eller produktside', 'Tar kontakt' ],
		'kriterier' => [
			[
				'id' => 'j2-k1', 'tekst' => 'Meta descriptions + OG-tagger på alle sider',
				'status' => 'done',
				'bevis' => 'mu-plugins/acrylicon-seo/ (auto-descriptions + metabox); OG verifisert på prod-forsiden',
				'verifisert' => '2026-06-12',
			],
			[
				'id' => 'j2-k2', 'tekst' => 'Strukturert data (schema.org) komplett',
				'status' => 'partial',
				'bevis' => 'ld+json på forsiden (curl-verifisert 2026-06-12)',
				'notat' => 'Logo-PNG for schema mangler (todos/010), title-format review åpen (todos/012).',
				'verifisert' => '2026-06-12',
			],
			[
				'id' => 'j2-k3', 'tekst' => 'Sitemap + robots indekserbart på kanonisk domene',
				'status' => 'partial',
				'bevis' => 'wp-sitemap.xml 200 på prod (2026-06-12)',
				'notat' => 'Servebolt staging-robots svarer «Disallow: /» til DNS-cutover. GSC-resubmit ligger i cutover-planen.',
				'verifisert' => '2026-06-12',
			],
			[
				'id' => 'j2-k4', 'tekst' => 'PageSpeed 90+ mobil (kritisk for AI-søk)',
				'status' => 'partial',
				'bevis' => 'Teknisk audit 2026-06-12: LCP 135ms/CLS 0 (desktop, uten throttling)',
				'notat' => 'Roadmap målte 57–68 mobil. WPFC serverer «via php» (~0,5s backend per uncachet treff) — Servebolt fullside-cache er neste steg. Mobil-måling med throttling gjenstår.',
				'verifisert' => '2026-06-12',
			],
			[
				'id' => 'j2-k5', 'tekst' => 'Mobil UX-gjennomgang (navigasjon, CTA-plassering)',
				'status' => 'missing',
				'notat' => '66% av trafikken er mobil. Estimert 20+ timer i roadmap.',
			],
			[
				'id' => 'j2-k6', 'tekst' => 'Google Business-profiler for alle 4 norske kontorer',
				'status' => 'missing',
				'notat' => '0 profiler i dag (roadmap). Estimert 8+ timer.',
			],
			[
				'id' => 'j2-k7', 'tekst' => 'Referanse-filtrering (bruksområde / produkt / kontor)',
				'status' => 'done',
				'bevis' => 'themes/acrylicon-2024/blocks/global-reference/template.php:97–137 (industri/system/kontor-filter)',
				'verifisert' => '2026-06-12',
			],
			[
				'id' => 'j2-k8', 'tekst' => '10 nye topp-referanser med dybdeinnhold',
				'status' => 'partial',
				'bevis' => '3 av 10 publisert (Park garage, AO Arena, DC Valley) — WORKLOG 2026-06-05',
				'notat' => 'Analytics-innsikt skal styre valget av de neste. 5 eksisterende dybde-referanser gir 7 279 sesjoner/år.',
				'verifisert' => '2026-06-12',
			],
		],
		'godkjentAvTeam' => null,
		'kbLenke' => 'docs/strategy/digital-roadmap-2026.html',
	],
	[
		'id'      => 'j3',
		'nr'      => 3,
		'tittel'  => 'Selger sender dokumentasjon til kunde',
		'aktor'   => 'Selger (Anders m.fl.)',
		'hvorfor' => 'Selgerverktøy bygget på data som allerede finnes i databasen — erstatter ekstern grafiker og manuelt arbeid.',
		'steg'    => [ 'Åpner produkt/referanser', 'Genererer produktblad / referanseark / spesifikasjon', 'Sender PDF til kunde samme dag' ],
		'kriterier' => [
			[
				'id' => 'j3-k1', 'tekst' => 'Produktblad-generator (alltid oppdatert, klar som PDF)',
				'status' => 'partial',
				'bevis' => 'single-produkter-sheet.php + inc/product-sheet-helpers.php (?view=sheet) — browser-verifisert 2026-06-12',
				'notat' => 'Template live med print/PDF-knapp. Gjenstår: innholdsfylling per produkt (tomt beskrivelsesfelt observert på Multi-Grip ID), utrulling alle produkter (docs/brainstorms 2026-05-13).',
				'verifisert' => '2026-06-12',
			],
			[
				'id' => 'j3-k2', 'tekst' => 'Referanseark-generator (filtrer → PDF-pakke med 3–5 prosjekter)',
				'status' => 'missing',
				'notat' => 'Etterspurt av Anders Øwre-Johnsen. Estimert 30+ timer.',
			],
			[
				'id' => 'j3-k3', 'tekst' => 'Arkitekt-spesifikasjonsverktøy (anbudstekst med tekniske data)',
				'status' => 'missing',
				'notat' => 'Estimert 40+ timer.',
			],
		],
		'godkjentAvTeam' => null,
		'kbLenke' => 'docs/brainstorms/2026-05-13-dynamic-product-sheets-rollout-brainstorm.md',
	],
	[
		'id'      => 'j4',
		'nr'      => 4,
		'tittel'  => 'Kunde vurderer totaløkonomi og tar kontakt',
		'aktor'   => 'Kunde / byggherre',
		'hvorfor' => 'Forvandler «dere er dyre» til «dere er billigst over tid» — og sørger for at henvendelsen lander hos riktig kontor.',
		'steg'    => [ 'Bruker produktvelger / TCO-kalkulator', 'Får anbefaling med referanser', 'Sender henvendelse via eget skjema', 'Riktig kontor følger opp' ],
		'kriterier' => [
			[
				'id' => 'j4-k1', 'tekst' => 'TCO-kalkulator med PDF-rapport',
				'status' => 'missing',
				'notat' => 'Levetidskost-innhold finnes som CPT (levetids-kostnader) — kalkulator gjenstår. Estimert 50+ timer.',
			],
			[
				'id' => 'j4-k2', 'tekst' => 'Produktvelger-veiviser (lokale + krav → anbefaling)',
				'status' => 'missing',
				'notat' => 'Estimert 30+ timer.',
			],
			[
				'id' => 'j4-k3', 'tekst' => 'Eget kontaktskjema med GA4-sporing (erstatter SuperOffice-iframe)',
				'status' => 'missing',
				'bevis' => 'SuperOffice fortsatt i bruk (referanse i themes/acrylicon-2024/style.css)',
				'notat' => 'Estimert 15+ timer.',
			],
			[
				'id' => 'j4-k4', 'tekst' => 'Forbedrede kontorsider (innhold, kart, lokale referanser)',
				'status' => 'partial',
				'bevis' => 'office-contact-card-blokk + e-post/ikon-fikser (commits e82d402, 66dd0c8; WORKLOG 2026-06-05)',
				'notat' => 'Kontaktinfo på plass og verifisert. Kart og lokale referanser per kontor gjenstår.',
				'verifisert' => '2026-06-12',
			],
		],
		'godkjentAvTeam' => null,
		'kbLenke' => 'docs/strategy/digital-roadmap-2026.html',
	],
	[
		'id'      => 'j5',
		'nr'      => 5,
		'tittel'  => 'Dokumentasjon, garanti og etterlevelse',
		'aktor'   => 'Arkitekt / driftsansvarlig / gulvlegger / HMS-ansvarlig',
		'hvorfor' => 'Selvbetjening og digitale bevis profesjonaliserer AcryliCon — og fjerner hindre i offentlige anbud.',
		'steg'    => [ 'Laster ned datablad/sertifikat', 'Skanner QR i gulvet → garantiside', 'Gulvlegger viser digital HMS-sertifisering' ],
		'kriterier' => [
			[
				'id' => 'j5-k1', 'tekst' => 'Sertifiserings-/dokumentportal med filtrering og nedlastingsinnsikt',
				'status' => 'partial',
				'bevis' => '/downloads/ og /no/nedlastinger/ svarer 200 (2026-06-12)',
				'notat' => 'Statisk nedlastingsside finnes. Filtrering per produkt/sertifisering og e-post ved nedlasting gjenstår. Estimert 35+ timer.',
				'verifisert' => '2026-06-12',
			],
			[
				'id' => 'j5-k2', 'tekst' => 'Digitalt garantibevis med QR-kode («skann gulvet ditt»)',
				'status' => 'missing',
				'notat' => 'Eksplisitt post-launch (todos/013). Estimert 40+ timer.',
			],
			[
				'id' => 'j5-k3', 'tekst' => 'HMS-portal for gulvleggere med utløpsvarsler',
				'status' => 'missing',
				'notat' => 'Estimert 30+ timer.',
			],
			[
				'id' => 'j5-k4', 'tekst' => 'WCAG-tilgjengelighet (offentlige anbud)',
				'status' => 'partial',
				'bevis' => 'Lighthouse a11y 95 på prod-forsiden (2026-06-12)',
				'notat' => 'Kjente funn: kort-lenker uten tilgjengelig navn, generiske «Read more»-tekster. Full audit gjenstår (20+ timer).',
				'verifisert' => '2026-06-12',
			],
			[
				'id' => 'j5-k5', 'tekst' => 'Cookie consent / GDPR (scripts kun etter samtykke)',
				'status' => 'missing',
				'bevis' => 'GTM-TJ93BLWH lastes uten samtykke — themes/acrylicon-2024/header.php:25',
				'notat' => 'Lovkrav. Gjelder GA4, GTM og Byggfakta. Estimert 8+ timer.',
			],
		],
		'godkjentAvTeam' => null,
		'kbLenke' => 'docs/strategy/digital-roadmap-2026.html',
	],
	[
		'id'      => 'j6',
		'nr'      => 6,
		'tittel'  => 'Ledelsen følger effekten og skalerer',
		'aktor'   => 'Monika / ledelsen / kontorene',
		'hvorfor' => 'Synliggjør verdien av web-investeringen og gjør nye land til et koblingspunkt, ikke et nytt prosjekt.',
		'steg'    => [ 'Åpner statusside / månedsrapport', 'Ser trafikk, leads og fremdrift', 'Kontor får varsel om varme leads', 'Beslutter neste land' ],
		'kriterier' => [
			[
				'id' => 'j6-k1', 'tekst' => 'GA4/GTM-måling aktiv',
				'status' => 'done',
				'bevis' => 'GTM-TJ93BLWH i themes/acrylicon-2024/header.php:25',
				'verifisert' => '2026-06-12',
			],
			[
				'id' => 'j6-k2', 'tekst' => 'Byggfakta Analytics installert',
				'status' => 'done',
				'bevis' => 'Byggfakta-script i themes/acrylicon-2024/header.php',
				'verifisert' => '2026-06-12',
			],
			[
				'id' => 'j6-k3', 'tekst' => 'Byggfakta regionale varsler til kontorene',
				'status' => 'missing',
				'notat' => 'Estimert 6+ timer.',
			],
			[
				'id' => 'j6-k4', 'tekst' => 'Månedlig rapport / dashboard',
				'status' => 'missing',
				'notat' => 'Estimert 10+ timer.',
			],
			[
				'id' => 'j6-k5', 'tekst' => 'Felles statusside med kodeverifisert fremdrift',
				'status' => 'done',
				'bevis' => 'wp-content/status/ (denne siden) — satt opp 2026-06-12',
				'verifisert' => '2026-06-12',
			],
			[
				'id' => 'j6-k6', 'tekst' => 'Multisite-arkitektur klar for nye land',
				'status' => 'partial',
				'bevis' => 'Multisite + plugins/acrylicon-multisite-sync/ + mu-plugins/acrylicon-shared-taxonomies.php i drift',
				'notat' => 'Arkitekturen er bevist med EN-siten. Per-land-oppsett (~20 t teknisk) gjøres ved behov.',
				'verifisert' => '2026-06-12',
			],
			[
				'id' => 'j6-k7', 'tekst' => 'Karrieresider med søknadshåndtering',
				'status' => 'missing',
				'notat' => 'Estimert 15+ timer.',
			],
		],
		'godkjentAvTeam' => null,
		'kbLenke' => 'docs/PROJECT-LOG.md',
	],
];
