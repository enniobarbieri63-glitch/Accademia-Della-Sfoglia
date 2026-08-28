<?php
/**
 * export.php — Esportazione della classifica in una pagina stampabile
 * ottimizzata (l'utente sceglie "Salva come PDF" dal browser).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Intercetta la richiesta di esportazione (?gs_export=...).
 */
add_action( 'admin_init', 'gs_handle_export' );

function gs_handle_export() {
	if ( empty( $_GET['gs_export'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	check_admin_referer( 'gs_export' );

	$tipo     = sanitize_key( $_GET['gs_export'] ); // generale | squadre | sfida
	$sfida_id = isset( $_GET['sfida'] ) ? (int) $_GET['sfida'] : 0;

	gs_render_printable( $tipo, $sfida_id );
	exit;
}

/**
 * Costruisce l'URL di esportazione firmato.
 */
function gs_export_url( $tipo, $sfida_id = 0 ) {
	$args = array( 'gs_export' => $tipo );
	if ( $sfida_id ) {
		$args['sfida'] = $sfida_id;
	}
	return wp_nonce_url( add_query_arg( $args, admin_url( 'admin.php' ) ), 'gs_export' );
}

/**
 * Stampa la pagina HTML stampabile.
 */
function gs_render_printable( $tipo, $sfida_id = 0 ) {
	$title = 'Classifica';
	$rows  = array();
	$cols  = array( 'Pos.', 'Sfoglina', 'Punti' );

	if ( 'squadre' === $tipo ) {
		$title = 'Classifica a Squadre';
		$cols  = array( 'Pos.', 'Squadra', 'Membri', 'Punti' );
		$pos = 1;
		foreach ( gs_team_leaderboard() as $riga ) {
			$rows[] = array( $pos++, $riga['squadra'], $riga['membri'], $riga['punti'] );
		}
	} elseif ( 'sfida' === $tipo && $sfida_id ) {
		$title = 'Classifica sfida: ' . get_the_title( $sfida_id );
		$cols  = array( 'Pos.', 'Sfoglina', 'Media voti' );
		$pos = 1;
		foreach ( gs_challenge_leaderboard( $sfida_id ) as $sfoglia ) {
			$autrice = get_userdata( $sfoglia->post_author );
			$rows[]  = array( $pos++, $autrice ? $autrice->display_name : '—', gs_calc_media_voti( $sfoglia->ID ) );
		}
	} else {
		$title = 'Classifica Generale';
		$pos = 1;
		foreach ( gs_leaderboard( 100 ) as $user ) {
			$rows[] = array( $pos++, $user->display_name, (int) get_user_meta( $user->ID, 'gs_points', true ) );
		}
	}

	header( 'Content-Type: text/html; charset=utf-8' );
	?><!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="utf-8">
	<title><?php echo esc_html( $title ); ?></title>
	<style>
		body { font-family: Georgia, "Times New Roman", serif; color: #3a2a1a; margin: 40px; }
		h1 { color: #a8541f; border-bottom: 3px solid #e0b04a; padding-bottom: 8px; }
		.meta { color: #8a7a6a; font-size: 13px; margin-bottom: 24px; }
		table { width: 100%; border-collapse: collapse; }
		th, td { text-align: left; padding: 8px 12px; border-bottom: 1px solid #e8ddc8; }
		th { background: #f6ecd6; color: #7a4a1a; }
		tr:nth-child(1) td { font-weight: bold; }
		@media print { .noprint { display: none; } body { margin: 12mm; } }
	</style>
</head>
<body onload="window.print()">
	<h1>🌾 <?php echo esc_html( $title ); ?></h1>
	<div class="meta">Accademia della Sfoglia — generata il <?php echo esc_html( date_i18n( 'j F Y, H:i' ) ); ?></div>
	<table>
		<thead><tr><?php foreach ( $cols as $c ) { echo '<th>' . esc_html( $c ) . '</th>'; } ?></tr></thead>
		<tbody>
		<?php foreach ( $rows as $row ) : ?>
			<tr><?php foreach ( $row as $cell ) { echo '<td>' . esc_html( $cell ) . '</td>'; } ?></tr>
		<?php endforeach; ?>
		<?php if ( empty( $rows ) ) : ?>
			<tr><td colspan="<?php echo count( $cols ); ?>">Nessun dato disponibile.</td></tr>
		<?php endif; ?>
		</tbody>
	</table>
	<p class="noprint" style="margin-top:24px;">
		<button onclick="window.print()">🖨️ Stampa / Salva come PDF</button>
	</p>
</body>
</html><?php
}
