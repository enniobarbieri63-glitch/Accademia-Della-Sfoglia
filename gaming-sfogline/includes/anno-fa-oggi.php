<?php
/**
 * anno-fa-oggi.php — Un Anno Fa Oggi.
 *
 * Flashback comunitario e pubblico: le ricette del Ricettario delle Famiglie
 * approvate esattamente un anno fa, nello stesso giorno di oggi. Nessun dato
 * nuovo da gestire: è solo una lettura diversa di gs_ricetta, già esistente.
 * (Diverso dal "Diario a Doppio Senso": quello è privato, guarda il proprio
 * diario personale; questo è pubblico, guarda il Ricettario di tutte.)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Ricette approvate pubblicate esattamente un anno fa oggi (stesso giorno e mese). */
function gs_ricette_anno_fa_oggi() {
	if ( ! function_exists( 'gs_ricette_approvate' ) ) { return array(); }
	$oggi = getdate( current_time( 'timestamp' ) );
	$out  = array();
	foreach ( gs_ricette_approvate() as $r ) {
		$data = getdate( strtotime( $r->post_date ) );
		if ( $data['mon'] === $oggi['mon'] && $data['mday'] === $oggi['mday'] && $data['year'] < $oggi['year'] ) {
			$out[] = $r;
		}
	}
	return $out;
}

add_shortcode( 'gs_anno_fa_oggi', 'gs_sc_anno_fa_oggi' );
function gs_sc_anno_fa_oggi() {
	if ( $g = gs_gate_riservato() ) { return $g; }
	if ( $g = gs_blackout_gate() ) { return $g; }

	$out = gs_box_open( '🕰️ Un Anno Fa Oggi' );
	$out .= gs_sezione_aiuto( 'Ogni giorno questa pagina mostra le ricette del Ricettario delle Famiglie che sono state approvate esattamente un anno fa, in questo stesso giorno. È un piccolo promemoria comunitario di quello che l\'Accademia condivideva un anno fa — cambia da solo ogni giorno, non c\'è nulla da configurare.' );

	$ricette = gs_ricette_anno_fa_oggi();
	if ( ! $ricette ) {
		$out .= '<p class="gs-hint">Oggi non c\'è nessuna ricetta pubblicata esattamente un anno fa. Torna domani!</p>';
		$out .= gs_box_close();
		return $out;
	}

	$out .= '<div class="gs-gallery">';
	foreach ( $ricette as $r ) {
		$c  = gs_ricetta_get( $r->ID );
		$au = get_userdata( $c['autore'] );
		$out .= '<div class="gs-card"><div class="gs-card-body">';
		$out .= '<p class="gs-card-author">📅 ' . esc_html( date_i18n( 'd F Y', strtotime( $r->post_date ) ) ) . '</p>';
		$out .= '<h4>' . esc_html( $c['titolo'] ) . '</h4>';
		if ( $au ) { $out .= '<p class="gs-hint">di ' . esc_html( $au->display_name ) . '</p>'; }
		$out .= '<details class="gs-corso-descr"><summary>📖 Leggi la ricetta completa</summary>';
		if ( $c['ingredienti'] ) { $out .= '<p class="gs-hint" style="margin-top:6px"><strong>Ingredienti:</strong><br>' . nl2br( esc_html( $c['ingredienti'] ) ) . '</p>'; }
		if ( $c['procedimento'] ) { $out .= '<p class="gs-hint"><strong>Procedimento:</strong><br>' . nl2br( esc_html( $c['procedimento'] ) ) . '</p>'; }
		if ( $c['racconto'] ) { $out .= '<p class="gs-hint"><strong>La storia:</strong><br>' . nl2br( esc_html( $c['racconto'] ) ) . '</p>'; }
		$out .= '</details>';
		$out .= '</div></div>';
	}
	$out .= '</div>';
	$out .= gs_box_close();
	return $out;
}
