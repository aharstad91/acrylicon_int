<?php
/**
 * Acrylicon statusside — token-beskyttet fremdriftsoversikt.
 *
 * URL: /wp-content/status/?t=<token>
 * Token ligger i status-token.php (gitignored, deployes manuelt per miljø).
 * Fail-closed: mangler token-fil eller feil token → 404 uten innhold.
 *
 * Prinsipper (statusside-skillet):
 * - progress = andel done; partial teller som 0
 * - grønn journey = alle done OG godkjentAvTeam satt (kun av mennesker)
 * - data i status-data.php, oppdateres via vanlige commits
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

/** Grønn = alle done + team-godkjent; rød = alt missing; ellers gul. */
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

function journey_progress( array $j ): int {
	$n = count( $j['kriterier'] );
	if ( ! $n ) {
		return 0;
	}
	$done = count( array_filter( $j['kriterier'], fn( $k ) => $k['status'] === 'done' ) );
	return (int) round( $done / $n * 100 );
}

function total_progress( array $journeys ): int {
	$alle = array_merge( ...array_column( $journeys, 'kriterier' ) );
	if ( ! $alle ) {
		return 0;
	}
	$done = count( array_filter( $alle, fn( $k ) => $k['status'] === 'done' ) );
	return (int) round( $done / count( $alle ) * 100 );
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

function e( ?string $s ): string {
	return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}

/** Agent-prompt for «kopier prompt»-knappen. */
function kriterie_prompt( array $j, array $k ): string {
	$linjer = [
		'Prosjekt: Acrylicon (WordPress multisite). Statusside-kriterium å jobbe med:',
		'Journey ' . $j['nr'] . ': ' . $j['tittel'] . ' (aktør: ' . $j['aktor'] . ')',
		'Kriterium ' . $k['id'] . ': ' . $k['tekst'],
		'Status nå: ' . $k['status'],
	];
	if ( ! empty( $k['notat'] ) ) {
		$linjer[] = 'Notat: ' . $k['notat'];
	}
	if ( ! empty( $k['bevis'] ) ) {
		$linjer[] = 'Bevis: ' . $k['bevis'];
	}
	$linjer[] = '';
	$linjer[] = 'Oppgave: implementer/fullfør dette kriteriet, verifiser mot kode og prod, '
		. 'og oppdater deretter wp-content/status/status-data.php med ny status, bevis (fil:linje '
		. 'eller commit) og verifisert-dato. Aldri done uten bevis; tvil = partial med notat. '
		. 'Deploy statusdata til prod etterpå (scp wp-content/status/status-data.php).';
	return implode( "\n", $linjer );
}

$total       = total_progress( $journeys );
$alle_krit   = array_merge( ...array_column( $journeys, 'kriterier' ) );
$ant_done    = count( array_filter( $alle_krit, fn( $k ) => $k['status'] === 'done' ) );
$ant_partial = count( array_filter( $alle_krit, fn( $k ) => $k['status'] === 'partial' ) );
$ant_missing = count( $alle_krit ) - $ant_done - $ant_partial;
$sist        = nyeste_verifisert( $journeys );

$status_label = [ 'done' => 'Ferdig', 'partial' => 'Delvis', 'missing' => 'Ikke startet' ];
?>
<!DOCTYPE html>
<html lang="nb">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>AcryliCon — Digital roadmap: status</title>
<style>
	:root { --blue:#253761; --red:#E2241C; --lblue:#D5EDF7; --bg:#f7f6f2; --ink:#1c1c1c; --muted:#6b7280; --green:#15803d; --yellow:#b45309; }
	* { box-sizing:border-box; }
	body { margin:0; font:16px/1.55 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; background:var(--bg); color:var(--ink); }
	.wrap { max-width:880px; margin:0 auto; padding:32px 20px 80px; }
	header.top { background:var(--blue); color:#fff; border-radius:12px; padding:28px 28px 24px; }
	header.top h1 { margin:0 0 4px; font-size:26px; }
	header.top p { margin:0; opacity:.8; font-size:14px; }
	.bar { background:rgba(255,255,255,.18); border-radius:99px; height:14px; margin-top:18px; overflow:hidden; }
	.bar > div { background:#fff; height:100%; border-radius:99px; transition:width .4s; }
	.bigpct { font-size:40px; font-weight:700; margin-top:14px; }
	.counts { display:flex; gap:18px; margin-top:6px; font-size:14px; opacity:.9; flex-wrap:wrap; }
	.journey { background:#fff; border-radius:12px; margin-top:22px; padding:22px 24px; border-left:6px solid #ccc; box-shadow:0 1px 3px rgba(0,0,0,.06); }
	.journey.green { border-left-color:var(--green); }
	.journey.yellow { border-left-color:#f59e0b; }
	.journey.red { border-left-color:var(--red); }
	.jhead { display:flex; justify-content:space-between; gap:12px; align-items:baseline; flex-wrap:wrap; }
	.jhead h2 { margin:0; font-size:19px; color:var(--blue); }
	.jpct { font-weight:700; color:var(--blue); white-space:nowrap; }
	.aktor { font-size:13px; color:var(--muted); margin:2px 0 0; }
	.hvorfor { font-size:14px; margin:10px 0 0; }
	.steg { margin:10px 0 0; padding:0; list-style:none; display:flex; flex-wrap:wrap; gap:6px; font-size:12.5px; color:var(--blue); }
	.steg li { background:var(--lblue); border-radius:99px; padding:3px 11px; }
	.steg li + li::before { content:""; }
	.jbar { background:#eee; border-radius:99px; height:8px; margin-top:14px; overflow:hidden; }
	.jbar > div { background:var(--blue); height:100%; }
	table.krit { width:100%; border-collapse:collapse; margin-top:14px; font-size:14px; }
	table.krit td { padding:9px 8px; border-top:1px solid #eee; vertical-align:top; }
	td.st { white-space:nowrap; width:110px; }
	.badge { display:inline-block; border-radius:99px; padding:2px 10px; font-size:12px; font-weight:600; }
	.badge.done { background:#dcfce7; color:var(--green); }
	.badge.partial { background:#fef3c7; color:var(--yellow); }
	.badge.missing { background:#fee2e2; color:var(--red); }
	.bevis { color:var(--muted); font-size:12.5px; display:block; margin-top:3px; }
	.notat { font-size:12.5px; display:block; margin-top:3px; }
	td.cp { width:46px; text-align:right; }
	button.copy { border:1px solid #d1d5db; background:#fff; border-radius:8px; padding:4px 8px; cursor:pointer; font-size:12px; color:var(--blue); }
	button.copy:hover { background:var(--lblue); }
	button.copy.ok { background:#dcfce7; border-color:var(--green); color:var(--green); }
	.godkjent { margin-top:12px; font-size:13px; color:var(--green); }
	.ikkegodkjent { margin-top:12px; font-size:12.5px; color:var(--muted); }
	footer { margin-top:28px; font-size:12.5px; color:var(--muted); }
</style>
</head>
<body>
<div class="wrap">
	<header class="top">
		<h1>AcryliCon — Digital roadmap 2026</h1>
		<p>Felles statusside · scope godkjent av Monika 2026-06-12 · kun done teller — partial = 0</p>
		<div class="bigpct"><?php echo $total; ?>%</div>
		<div class="bar"><div style="width:<?php echo $total; ?>%"></div></div>
		<div class="counts">
			<span>✔ <?php echo $ant_done; ?> ferdig</span>
			<span>◐ <?php echo $ant_partial; ?> delvis</span>
			<span>○ <?php echo $ant_missing; ?> ikke startet</span>
			<?php if ( $sist ) : ?><span>Sist verifisert mot kode: <?php echo e( $sist ); ?></span><?php endif; ?>
		</div>
	</header>

	<?php foreach ( $journeys as $j ) : $farge = journey_farge( $j ); $pct = journey_progress( $j ); ?>
	<section class="journey <?php echo $farge; ?>">
		<div class="jhead">
			<h2><?php echo $j['nr'] . '. ' . e( $j['tittel'] ); ?></h2>
			<span class="jpct"><?php echo $pct; ?>%</span>
		</div>
		<p class="aktor">Aktør: <?php echo e( $j['aktor'] ); ?></p>
		<p class="hvorfor"><?php echo e( $j['hvorfor'] ); ?></p>
		<ul class="steg"><?php foreach ( $j['steg'] as $i => $s ) : ?><li><?php echo ( $i + 1 ) . '. ' . e( $s ); ?></li><?php endforeach; ?></ul>
		<div class="jbar"><div style="width:<?php echo $pct; ?>%"></div></div>
		<table class="krit">
			<?php foreach ( $j['kriterier'] as $k ) : ?>
			<tr>
				<td class="st"><span class="badge <?php echo e( $k['status'] ); ?>"><?php echo $status_label[ $k['status'] ]; ?></span></td>
				<td>
					<?php echo e( $k['tekst'] ); ?>
					<?php if ( ! empty( $k['bevis'] ) ) : ?><span class="bevis">Bevis: <?php echo e( $k['bevis'] ); ?><?php if ( ! empty( $k['verifisert'] ) ) : ?> · verifisert <?php echo e( $k['verifisert'] ); ?><?php endif; ?></span><?php endif; ?>
					<?php if ( ! empty( $k['notat'] ) ) : ?><span class="notat"><?php echo e( $k['notat'] ); ?></span><?php endif; ?>
				</td>
				<td class="cp"><button class="copy" title="Kopier agent-prompt" data-prompt="<?php echo e( kriterie_prompt( $j, $k ) ); ?>">📋</button></td>
			</tr>
			<?php endforeach; ?>
		</table>
		<?php if ( ! empty( $j['godkjentAvTeam'] ) ) : $g = $j['godkjentAvTeam']; ?>
			<p class="godkjent">✔ Godkjent av teamet <?php echo e( $g['dato'] ); ?> (<?php echo e( $g['av'] ); ?>)<?php if ( ! empty( $g['notat'] ) ) : ?> — <?php echo e( $g['notat'] ); ?><?php endif; ?></p>
		<?php else : ?>
			<p class="ikkegodkjent">Ikke team-godkjent ennå — grønn krever alle kriterier ferdig + eksplisitt beslutning.</p>
		<?php endif; ?>
	</section>
	<?php endforeach; ?>

	<footer>
		Data: <code>wp-content/status/status-data.php</code> (git-historikken er endringsloggen).
		Kilde for scope: <code>docs/strategy/digital-roadmap-2026.html</code>.
		Baren kan gå ned når nye hull oppdages — ærlig er viktigere enn pen.
	</footer>
</div>
<script>
document.querySelectorAll('button.copy').forEach(function (btn) {
	btn.addEventListener('click', function () {
		navigator.clipboard.writeText(btn.dataset.prompt).then(function () {
			btn.classList.add('ok'); btn.textContent = '✓';
			setTimeout(function () { btn.classList.remove('ok'); btn.textContent = '📋'; }, 1600);
		});
	});
});
</script>
</body>
</html>
