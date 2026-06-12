<?php
/**
 * Acrylicon statusside — token-beskyttet fremdriftsoversikt.
 *
 * URL: /wp-content/status/?t=<token>
 * Token ligger i status-token.php (gitignored, deployes manuelt per miljø).
 * Fail-closed: mangler token-fil eller feil token → 404 uten innhold.
 *
 * Layout følger statusside-skillets standardiserte spec (templates/LAYOUT.md):
 * nøytral smal kolonne, kollapsede journey-kort, slide-in detalj-panel.
 * Prinsipper: progress = andel done (partial=0); grønn = alle done +
 * godkjentAvTeam (settes kun av mennesker); data i status-data.php via git.
 */

$token_file = __DIR__ . '/status-token.php';
$expected   = is_readable( $token_file ) ? (string) include $token_file : '';
$given      = isset( $_GET['t'] ) ? (string) $_GET['t'] : '';

if ( $expected === '' || $given === '' || ! hash_equals( $expected, $given ) ) {
	http_response_code( 404 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'X-Robots-Tag: noindex, nofollow' );
	exit( 'Not Found' );
}

header( 'X-Robots-Tag: noindex, nofollow' );
header( 'Cache-Control: no-store, max-age=0' );
header( 'Content-Type: text/html; charset=utf-8' );

$journeys = include __DIR__ . '/status-data.php';

/* ---------- Derivasjon (speiler templates/lib/mvp-status.ts) ---------- */

function journey_farge( array $j ): string {
	$statuses = array_column( $j['kriterier'], 'status' );
	if ( ! $statuses ) {
		return 'red';
	}
	$done_alle = ! array_diff( $statuses, [ 'done' ] );
	if ( $done_alle && ! empty( $j['godkjentAvTeam'] ) ) {
		return 'green';
	}
	if ( ! array_diff( $statuses, [ 'missing' ] ) ) {
		return 'red';
	}
	return 'yellow';
}

function journey_done( array $j ): int {
	return count( array_filter( $j['kriterier'], fn( $k ) => $k['status'] === 'done' ) );
}

function total_progress( array $journeys ): int {
	$alle = array_merge( ...array_column( $journeys, 'kriterier' ) );
	if ( ! $alle ) {
		return 0;
	}
	$done = count( array_filter( $alle, fn( $k ) => $k['status'] === 'done' ) );
	return (int) round( $done / count( $alle ) * 100 );
}

function farge_fordeling( array $journeys ): array {
	$f = [ 'green' => 0, 'yellow' => 0, 'red' => 0 ];
	foreach ( $journeys as $j ) {
		$f[ journey_farge( $j ) ]++;
	}
	return $f;
}

function nyeste_verifisert( array $journeys ): ?string {
	$datoer = [];
	foreach ( $journeys as $j ) {
		foreach ( $j['kriterier'] as $k ) {
			if ( ! empty( $k['verifisert'] ) ) {
				$datoer[] = $k['verifisert'];
			}
		}
	}
	return $datoer ? max( $datoer ) : null;
}

function format_dato( ?string $iso ): string {
	if ( ! $iso ) {
		return 'ennå ikke verifisert';
	}
	$ts = strtotime( $iso );
	if ( ! $ts ) {
		return $iso;
	}
	$mnd = [ 1 => 'januar', 'februar', 'mars', 'april', 'mai', 'juni', 'juli', 'august', 'september', 'oktober', 'november', 'desember' ];
	return (int) date( 'j', $ts ) . '. ' . $mnd[ (int) date( 'n', $ts ) ] . ' ' . date( 'Y', $ts );
}

function e( ?string $s ): string {
	return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}

/* ---------- /goal-generering (broen til agent-arbeidsflyten) ---------- */

const KVALITETSRUTINE = "Kvalitetsrutine:\n"
	. "- Rører endringen auth, betaling eller datamutasjoner, eller er diffen stor (50+ linjer): kjør /ce-code-review på diffen og fiks funnene før ship.\n"
	. "- Gjelder kriteriet en brukerflyt: verifiser i kjørende app (/verify eller nettleser mot localhost:8888/acrylicon) — ikke bare kode-lesing.\n"
	. "- Trengs design/avveininger først (flere rimelige løsninger): start med /ce-plan.\n"
	. "- Løste du noe ikke-opplagt underveis: fang læringen med /ce-compound.\n"
	. 'Modellstrategi: kjør hovedløkka (orkestrering, kodeverifisering, statusoppdatering i status-data.php) selv på Fable. Avgrensede implementasjonsoppgaver delegeres til subagenter på Opus (Agent-verktøyet med model: "opus") — én subagent per oppgave, seriellt med mindre oppgavene er genuint uavhengige.';

const VERIFISERING = 'php -l på endrede PHP-filer er grønn og berørte sider svarer 200 på prod';

$status_goal_label = [ 'done' => 'ferdig (kodeverifisert)', 'partial' => 'delvis', 'missing' => 'mangler' ];

function bygg_goal( array $j, array $k ): string {
	global $status_goal_label;
	$betingelse = $k['status'] === 'done'
		? 'Kriterium ' . $k['id'] . ' i wp-content/status/status-data.php er revalidert mot koden: verifisert-datoen er oppdatert hvis det fortsatt stemmer, ellers er status nedgradert med notat om hva som har driftet — og ' . VERIFISERING . '.'
		: 'Kriterium ' . $k['id'] . ' i wp-content/status/status-data.php står som done med oppdatert bevis (fil:linje) og verifisert-dato — eller er eksplisitt markert blokkert på menneske i notatet — og ' . VERIFISERING . '.';
	$linjer = [
		'/goal ' . $betingelse,
		'',
		'Kontekst fra Acrylicon-statussiden (WordPress multisite):',
		'Journey ' . $j['nr'] . ': ' . $j['tittel'],
		'Kriterium ' . $k['id'] . ': ' . $k['tekst'],
		'Status nå: ' . $status_goal_label[ $k['status'] ],
	];
	if ( ! empty( $k['notat'] ) ) {
		$linjer[] = 'Notat: ' . $k['notat'];
	}
	if ( ! empty( $k['bevis'] ) ) {
		$linjer[] = 'Bevis/referanse: ' . $k['bevis'];
	}
	$linjer[] = '';
	$linjer[] = $k['status'] === 'done'
		? 'Revalider kriteriet mot koden. Stemmer det fortsatt, oppdater verifisert-datoen; hvis ikke, nedgrader status med notat om hva som har driftet.'
		: 'Finn ut hva som mangler og implementer det. Når det er kodeverifisert: oppdater status, bevis (fil:linje/commit) og verifisert-dato i wp-content/status/status-data.php i samme commit som fiksen. Aldri done uten bevis; tvil = partial. Deploy endrede filer til prod (rsync/scp per CLAUDE.md) og statusdata med.';
	$linjer[] = '';
	$linjer[] = KVALITETSRUTINE;
	return implode( "\n", $linjer );
}

function bygg_journey_goal( array $j ): string {
	global $status_goal_label;
	$linjer = [
		'/goal Alle kriterier i journey ' . $j['nr'] . ' («' . $j['tittel'] . '») i wp-content/status/status-data.php står som done med bevis (fil:linje) og oppdatert verifisert-dato — eller er eksplisitt markert blokkert på menneske i notatet — og hver endring er shippet med ' . VERIFISERING . '.',
		'',
		'Kontekst fra Acrylicon-statussiden (WordPress multisite):',
		'Journey ' . $j['nr'] . ': ' . $j['tittel'] . ' (aktør: ' . $j['aktor'] . ')',
		'Hvorfor: ' . $j['hvorfor'],
		'',
		'Kriterier nå:',
	];
	foreach ( $j['kriterier'] as $k ) {
		$linjer[] = '- ' . $k['id'] . ' [' . $status_goal_label[ $k['status'] ] . ']: ' . $k['tekst']
			. ( ! empty( $k['notat'] ) ? ' (' . $k['notat'] . ')' : '' );
	}
	$linjer[] = '';
	$linjer[] = 'Ta kriteriene i fornuftig rekkefølge (avhengigheter først). For hvert: implementer, verifiser mot koden, og oppdater status, bevis og verifisert-dato i wp-content/status/status-data.php i samme commit som fiksen. Aldri done uten bevis; tvil = partial. Det som krever menneskelig handling (review, kontoer, DNS, innhold fra Monika, juridiske beslutninger): marker tydelig i notatet og gå videre. Deploy endrede filer til prod (rsync/scp per CLAUDE.md) og statusdata med.';
	$linjer[] = '';
	$linjer[] = KVALITETSRUTINE;
	return implode( "\n", $linjer );
}

/* ---------- Avledede verdier ---------- */

$total     = total_progress( $journeys );
$fordeling = farge_fordeling( $journeys );
$sist      = nyeste_verifisert( $journeys );

$farge_meta = [
	'green'  => [ 'label' => 'Godkjent',     'badge' => 'badge-green',  'bar' => 'bar-green' ],
	'yellow' => [ 'label' => 'Underveis',    'badge' => 'badge-amber',  'bar' => 'bar-amber' ],
	'red'    => [ 'label' => 'Ikke startet', 'badge' => 'badge-red',    'bar' => 'bar-red' ],
];
$krit_ikon = [ 'done' => '✓', 'partial' => '◌', 'missing' => '✕' ];
?>
<!DOCTYPE html>
<html lang="nb">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Status — AcryliCon digital roadmap 2026</title>
<style>
	:root {
		--bg:#fafafa; --card:#fff; --border:#e5e7eb; --ink:#111827; --muted:#6b7280;
		--green:#16a34a; --green-bg:#dcfce7; --green-ink:#166534;
		--amber:#d97706; --amber-bg:#fef3c7; --amber-ink:#92400e;
		--red:#dc2626; --red-bg:#fee2e2; --red-ink:#991b1b;
		--track:#e5e7eb;
	}
	* { box-sizing:border-box; }
	body { margin:0; font:15px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; background:var(--bg); color:var(--ink); }
	main { max-width:672px; margin:0 auto; padding:40px 16px; display:flex; flex-direction:column; gap:24px; }

	/* Header */
	.head-row { display:flex; align-items:flex-end; justify-content:space-between; gap:16px; }
	h1 { margin:0; font-size:24px; font-weight:700; letter-spacing:-.02em; }
	.sub { margin:2px 0 0; font-size:13px; color:var(--muted); }
	.bigpct { font-size:48px; font-weight:700; font-variant-numeric:tabular-nums; letter-spacing:-.02em; line-height:1; }
	.progress { height:10px; border-radius:99px; background:var(--track); overflow:hidden; margin-top:16px; }
	.progress > div { height:100%; border-radius:99px; background:var(--ink); }
	.pills { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-top:14px; font-size:12px; font-weight:500; }
	.pill { border-radius:99px; padding:4px 10px; }
	.pill.green { background:var(--green-bg); color:var(--green-ink); }
	.pill.amber { background:var(--amber-bg); color:var(--amber-ink); }
	.pill.red { background:var(--red-bg); color:var(--red-ink); }
	.deps { margin-left:auto; color:var(--muted); font-weight:400; }

	/* Journey-kort (kollapsede) */
	.cards { display:flex; flex-direction:column; gap:12px; }
	.card { display:block; width:100%; text-align:left; border:1px solid var(--border); border-radius:12px; background:var(--card); padding:16px 20px; cursor:pointer; font:inherit; color:inherit; box-shadow:0 1px 2px rgba(0,0,0,.04); transition:box-shadow .15s; }
	.card:hover { box-shadow:0 4px 10px rgba(0,0,0,.08); }
	.card-top { display:flex; align-items:center; justify-content:space-between; gap:8px; }
	.jlabel { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); }
	.badge { display:inline-block; border-radius:99px; padding:2px 9px; font-size:11px; font-weight:600; }
	.badge-green { background:var(--green-bg); color:var(--green-ink); }
	.badge-amber { background:var(--amber-bg); color:var(--amber-ink); }
	.badge-red { background:var(--red-bg); color:var(--red-ink); }
	.chev { color:var(--muted); font-size:14px; margin-left:6px; }
	.card-title { margin:8px 0 0; font-weight:600; line-height:1.35; }
	.card-bar { display:flex; align-items:center; gap:8px; margin-top:10px; }
	.minibar { flex:1; height:6px; border-radius:99px; background:var(--track); overflow:hidden; }
	.minibar > div { height:100%; border-radius:99px; }
	.bar-green { background:var(--green); }
	.bar-amber { background:var(--amber); }
	.bar-red { background:var(--red); }
	.count { font-size:12px; font-weight:500; color:var(--muted); font-variant-numeric:tabular-nums; white-space:nowrap; }

	/* Slide-in panel */
	.overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); opacity:0; pointer-events:none; transition:opacity .25s; z-index:40; }
	.overlay.open { opacity:1; pointer-events:auto; }
	.panel { position:fixed; top:0; right:0; bottom:0; width:100%; max-width:576px; background:var(--card); border-left:1px solid var(--border); box-shadow:-8px 0 24px rgba(0,0,0,.12); transform:translateX(100%); transition:transform .25s ease; z-index:50; display:flex; flex-direction:column; }
	.panel.open { transform:translateX(0); }
	.panel-head { flex-shrink:0; border-bottom:1px solid var(--border); padding:20px 24px 16px; position:relative; }
	.panel-title { margin:8px 0 0; font-size:20px; font-weight:700; letter-spacing:-.01em; }
	.panel-hvorfor { margin:6px 0 0; font-size:14px; line-height:1.6; }
	.panel-aktor { margin:4px 0 0; font-size:12px; color:var(--muted); }
	.close { position:absolute; top:14px; right:14px; border:0; background:none; font-size:16px; color:var(--muted); cursor:pointer; border-radius:6px; padding:4px 8px; }
	.close:hover { background:var(--track); color:var(--ink); }
	.jgoal { margin-top:12px; display:inline-flex; align-items:center; gap:6px; border:1px solid var(--border); background:var(--card); border-radius:8px; padding:6px 11px; font-size:12px; font-weight:500; color:var(--muted); cursor:pointer; }
	.jgoal:hover { background:var(--bg); color:var(--ink); }
	.jgoal.ok { border-color:var(--green); color:var(--green-ink); background:var(--green-bg); }
	.panel-body { overflow-y:auto; padding:16px 24px 24px; }
	.sect { margin:0; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); }
	.krit { list-style:none; margin:8px 0 0; padding:0; display:flex; flex-direction:column; gap:8px; }
	.krit li { display:flex; align-items:flex-start; gap:12px; border:1px solid var(--border); border-radius:10px; background:var(--card); padding:12px 14px; box-shadow:0 1px 2px rgba(0,0,0,.03); }
	.kikon { flex-shrink:0; font-size:14px; font-weight:700; margin-top:1px; }
	.kikon.done { color:var(--green); }
	.kikon.partial { color:var(--amber); }
	.kikon.missing { color:var(--red); }
	.kbody { flex:1; min-width:0; }
	.ktekst { margin:0; font-size:14px; font-weight:500; line-height:1.45; }
	.knotat { margin:4px 0 0; font-size:12px; line-height:1.5; color:var(--muted); }
	.kbevis { display:inline-block; margin-top:6px; max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; border-radius:5px; background:var(--bg); border:1px solid var(--border); padding:2px 7px; font:11px/1.6 ui-monospace,SFMono-Regular,Menlo,monospace; color:var(--muted); }
	.kcopy { flex-shrink:0; border:0; background:none; cursor:pointer; font-size:13px; color:var(--muted); border-radius:6px; padding:4px 6px; margin-top:-2px; }
	.kcopy:hover { background:var(--bg); color:var(--ink); }
	.kcopy.ok { color:var(--green); }
	.godkjent-boks { margin-top:16px; border-radius:8px; background:var(--bg); padding:10px 12px; font-size:12px; }
	.godkjent-boks .ja { color:var(--green-ink); font-weight:500; }
	.godkjent-boks .nei { color:var(--muted); }

	footer { border-top:1px solid var(--border); padding-top:20px; text-align:center; font-size:12px; color:var(--muted); }
	footer code { font:11px ui-monospace,SFMono-Regular,Menlo,monospace; }
</style>
</head>
<body>
<main>
	<header>
		<div class="head-row">
			<div>
				<h1>Digital roadmap 2026</h1>
				<p class="sub">Verifisert mot kode <?php echo e( format_dato( $sist ) ); ?></p>
			</div>
			<span class="bigpct"><?php echo $total; ?>%</span>
		</div>
		<div class="progress"><div style="width:<?php echo $total; ?>%"></div></div>
		<div class="pills">
			<span class="pill green"><?php echo $fordeling['green']; ?> godkjent</span>
			<span class="pill amber"><?php echo $fordeling['yellow']; ?> underveis</span>
			<span class="pill red"><?php echo $fordeling['red']; ?> ikke startet</span>
			<span class="deps">Avhengigheter: 1 (cutover) låser opp full effekt av 2</span>
		</div>
	</header>

	<div class="cards">
		<?php foreach ( $journeys as $j ) :
			$f      = $farge_meta[ journey_farge( $j ) ];
			$done   = journey_done( $j );
			$antall = count( $j['kriterier'] );
			$pct    = $antall ? $done / $antall * 100 : 0;
		?>
		<button type="button" class="card" data-panel="panel-<?php echo e( $j['id'] ); ?>">
			<div class="card-top">
				<span class="jlabel">Journey <?php echo $j['nr']; ?></span>
				<span><span class="badge <?php echo $f['badge']; ?>"><?php echo $f['label']; ?></span><span class="chev">›</span></span>
			</div>
			<p class="card-title"><?php echo e( $j['tittel'] ); ?></p>
			<div class="card-bar">
				<div class="minibar"><div class="<?php echo $f['bar']; ?>" style="width:<?php echo $pct; ?>%"></div></div>
				<span class="count"><?php echo $done; ?>/<?php echo $antall; ?></span>
			</div>
		</button>
		<?php endforeach; ?>
	</div>

	<footer>Oppdateres via <code>wp-content/status/status-data.php</code></footer>
</main>

<div class="overlay" id="overlay"></div>

<?php foreach ( $journeys as $j ) :
	$f      = $farge_meta[ journey_farge( $j ) ];
	$done   = journey_done( $j );
	$antall = count( $j['kriterier'] );
	$pct    = $antall ? $done / $antall * 100 : 0;
?>
<aside class="panel" id="panel-<?php echo e( $j['id'] ); ?>" role="dialog" aria-modal="true" aria-label="Journey <?php echo $j['nr']; ?>">
	<div class="panel-head">
		<span class="jlabel">Journey <?php echo $j['nr']; ?></span>
		<span class="badge <?php echo $f['badge']; ?>"><?php echo $f['label']; ?></span>
		<h2 class="panel-title"><?php echo e( $j['tittel'] ); ?></h2>
		<p class="panel-hvorfor"><?php echo e( $j['hvorfor'] ); ?></p>
		<p class="panel-aktor">Aktør: <?php echo e( $j['aktor'] ); ?></p>
		<div class="card-bar">
			<div class="minibar"><div class="<?php echo $f['bar']; ?>" style="width:<?php echo $pct; ?>%"></div></div>
			<span class="count"><?php echo $done; ?>/<?php echo $antall; ?> kriterier</span>
		</div>
		<button type="button" class="jgoal" data-prompt="<?php echo e( bygg_journey_goal( $j ) ); ?>">📋 Kopier /goal for hele journeyen</button>
		<button type="button" class="close" aria-label="Lukk">✕</button>
	</div>
	<div class="panel-body">
		<h3 class="sect">Akseptkriterier</h3>
		<ul class="krit">
			<?php foreach ( $j['kriterier'] as $k ) : ?>
			<li>
				<span class="kikon <?php echo e( $k['status'] ); ?>"><?php echo $krit_ikon[ $k['status'] ]; ?></span>
				<div class="kbody">
					<p class="ktekst"><?php echo e( $k['tekst'] ); ?></p>
					<?php if ( ! empty( $k['notat'] ) ) : ?><p class="knotat"><?php echo e( $k['notat'] ); ?></p><?php endif; ?>
					<?php if ( ! empty( $k['bevis'] ) ) : ?><span class="kbevis" title="<?php echo e( $k['bevis'] ); ?>"><?php echo e( $k['bevis'] ); ?><?php if ( ! empty( $k['verifisert'] ) ) : ?> · <?php echo e( $k['verifisert'] ); ?><?php endif; ?></span><?php endif; ?>
				</div>
				<button type="button" class="kcopy" title="Kopier /goal — lim inn i Claude Code, så jobber den til punktet er i mål" data-prompt="<?php echo e( bygg_goal( $j, $k ) ); ?>">📋</button>
			</li>
			<?php endforeach; ?>
		</ul>
		<p class="godkjent-boks">
			<?php if ( ! empty( $j['godkjentAvTeam'] ) ) : $g = $j['godkjentAvTeam']; ?>
				<span class="ja">Godkjent av teamet <?php echo e( format_dato( $g['dato'] ) ); ?> (<?php echo e( $g['av'] ); ?>)<?php if ( ! empty( $g['notat'] ) ) : ?> — <?php echo e( $g['notat'] ); ?><?php endif; ?></span>
			<?php else : ?>
				<span class="nei">Ikke godkjent av teamet ennå</span>
			<?php endif; ?>
		</p>
	</div>
</aside>
<?php endforeach; ?>

<script>
(function () {
	var overlay = document.getElementById('overlay');
	var apen = null;

	function lukk() {
		if (apen) { apen.classList.remove('open'); apen = null; }
		overlay.classList.remove('open');
	}
	function apne(id) {
		lukk();
		var p = document.getElementById(id);
		if (p) { p.classList.add('open'); overlay.classList.add('open'); apen = p; }
	}

	document.querySelectorAll('.card').forEach(function (c) {
		c.addEventListener('click', function () { apne(c.dataset.panel); });
	});
	overlay.addEventListener('click', lukk);
	document.querySelectorAll('.panel .close').forEach(function (b) {
		b.addEventListener('click', lukk);
	});
	document.addEventListener('keydown', function (ev) {
		if (ev.key === 'Escape') lukk();
	});

	document.querySelectorAll('button[data-prompt]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var original = btn.textContent;
			navigator.clipboard.writeText(btn.dataset.prompt).then(function () {
				btn.classList.add('ok');
				btn.textContent = btn.classList.contains('jgoal') ? '✓ /goal kopiert' : '✓';
				setTimeout(function () { btn.classList.remove('ok'); btn.textContent = original; }, 1800);
			});
		});
	});
})();
</script>
</body>
</html>
