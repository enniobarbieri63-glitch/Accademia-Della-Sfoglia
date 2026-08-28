<?php
/**
 * diario-doppio-senso.php — Il Diario a Doppio Senso.
 *
 * Nessun dato nuovo: è solo una lettura diversa del Diario dell'Impasto
 * (gs_diario, già esistente in cpt.php/forms.php). Mostra alla sfoglina,
 * in modo privato (solo a lei, mai pubblico), cosa aveva scritto esattamente
 * un anno fa oggi. Diverso da "Un Anno Fa Oggi": quello è un flashback
 * pubblico del Ricettario, questo è privato e riguarda solo il proprio
 * diario personale.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Voci di diario della sfoglina pubblicate esattamente un anno fa oggi (stesso giorno e mese). */
function gs_diario_anno_fa( $uid ) {
	if ( ! function_exists( 'gs_get_diario' ) ) { return array(); }
	$oggi = getdate( current_time( 'timestamp' ) );
	$out  = array();
	foreach ( gs_get_diario( $uid ) as $v ) {
		$data = getdate( strtotime( $v->post_date ) );
		if ( $data['mon'] === $oggi['mon'] && $data['mday'] === $oggi['mday'] && $data['year'] < $oggi['year'] ) {
			$out[] = $v;
		}
	}
	return $out;
}

/** Blocchetto HTML privato da inserire in cima al Diario, se c'è qualcosa da mostrare. */
function gs_diario_doppio_senso_html( $uid ) {
	$voci = gs_diario_anno_fa( $uid );
	if ( ! $voci ) { return ''; }

	$out = gs_box_open( '🔁 Un anno fa scrivevi…' );
	$out .= '<p class="gs-hint">Solo tu vedi questo: è quello che avevi scritto nel tuo diario esattamente un anno fa, in questo stesso giorno.</p>';
	foreach ( $voci as $v ) {
		$out .= '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">' . esc_html( get_the_title( $v ) ) . ' <span class="gs-msg-data">' . esc_html( get_the_date( '', $v ) ) . '</span></summary>';
		$out .= '<div class="gs-inbox-corpo"><div class="gs-inbox-testo">' . nl2br( esc_html( $v->post_content ) ) . '</div></div></details>';
	}
	$out .= gs_box_close();
	return $out;
}
