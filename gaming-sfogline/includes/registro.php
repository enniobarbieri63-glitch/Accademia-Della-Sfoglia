<?php
/**
 * registro.php — Il Registro Ufficiale delle Sfogline e Sfoglini.
 *
 * Diviso in due rami, secondo "I Corsi di Formazione" della pagina pubblica
 * Diventa Supporter (contenuto reale del sito, confermato da Ennio il
 * 2026-08-08 — non è un criterio legato al gaming/punti/badge, che è tutto
 * un sistema separato: "quelli vengono oltre il gaming"):
 *
 *  - Registro degli AMATORI: chi ha ricevuto l'Attestato di Corso Base o di
 *    Corso Intermedio (Calendario Corsi, meta gs_cal_attestato sulla
 *    prenotazione — vedi calendario.php, gs_cal_tipo_ha_attestato()).
 *  - Registro dei PROFESSIONISTI: due percorsi che portano allo stesso
 *    registro —
 *      1) l'Attestato di Corso Professionale (Calendario Corsi, esame di
 *         valutazione diretto dalla Maestra Rina Poletti);
 *      2) il percorso privato di Area Professionale ("Corso Privato"), fino
 *         alla Laurea in Sfoglia (meta gs_diploma_rina sul CPT gs_corso) —
 *         era l'unico registro prima di questa versione. gs_registro_allievi()
 *         resta con lo stesso nome e lo stesso significato (solo Laurea in
 *         Sfoglia) per non rompere gli altri moduli che la riusano (seo.php
 *         per lo schema.org, traguardi.php per "Ultimi Traguardi").
 *
 * È un elenco diverso da "Le Sfogline" (shortcode [gs_sfogline]), che invece
 * mostra tutte le iscritte al sito. Pagina pubblica, senza login richiesto,
 * come la Vetrina — non passa dal cancello del blackout.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Registro dei Professionisti — percorso 2: Laurea in Sfoglia (Area Professionale), più recenti prima. */
function gs_registro_allievi() {
	$corsi = get_posts( array(
		'post_type'      => 'gs_corso',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_key'       => 'gs_diploma_rina',
		'meta_value'     => '1',
	) );

	$righe = array();
	foreach ( $corsi as $c ) {
		$uid = (int) get_post_meta( $c->ID, 'gs_corso_utente', true );
		$u   = $uid ? get_user_by( 'id', $uid ) : null;
		if ( ! $u ) {
			continue; // sfoglina eliminata nel frattempo
		}
		$righe[] = array(
			'nome'    => $u->display_name,
			'livello' => gs_get_level( $uid ),
			'data'    => get_post_meta( $c->ID, 'gs_diploma_data', true ),
			'corso_titolo' => 'Corso Privato — Laurea in Sfoglia',
		);
	}

	usort( $righe, function ( $a, $b ) {
		return strcmp( (string) $b['data'], (string) $a['data'] );
	} );

	return $righe;
}

/**
 * Righe del Registro a partire dagli Attestati del Calendario Corsi, filtrate
 * per uno o più tipi di corso (base, intermedio, professionale — vedi
 * gs_cal_tipi_appuntamento() in calendario.php). Funzione unica riusata sia
 * per gli Amatori (base+intermedio) sia per il Corso Professionale dei
 * Professionisti, così i due elenchi restano sempre coerenti tra loro.
 */
function gs_registro_da_attestati_calendario( $tipi ) {
	if ( ! function_exists( 'gs_cal_corso_get' ) ) {
		return array();
	}
	$tipi = (array) $tipi;
	$prenotazioni = get_posts( array(
		'post_type'      => 'gs_prenotazione',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_key'       => 'gs_cal_attestato',
		'meta_value'     => '1',
	) );

	$righe = array();
	foreach ( $prenotazioni as $p ) {
		$corso_id = (int) get_post_meta( $p->ID, 'gs_corso', true );
		$corso    = gs_cal_corso_get( $corso_id );
		if ( ! in_array( $corso['tipo'], $tipi, true ) ) {
			continue;
		}
		$uid = (int) get_post_meta( $p->ID, 'gs_cliente', true );
		$u   = $uid ? get_user_by( 'id', $uid ) : null;
		if ( ! $u ) {
			continue; // cliente eliminato nel frattempo
		}
		$righe[] = array(
			'nome'         => $u->display_name,
			'livello'      => function_exists( 'gs_get_level' ) ? gs_get_level( $uid ) : array( 'simbolo' => '', 'titolo' => '' ),
			'data'         => get_post_meta( $p->ID, 'gs_cal_attestato_data', true ) ?: $corso['data'],
			'corso_titolo' => $corso['titolo'] ? $corso['titolo'] : ( function_exists( 'gs_cal_attestato_titolo' ) ? gs_cal_attestato_titolo( $corso['tipo'] ) : '' ),
		);
	}

	usort( $righe, function ( $a, $b ) {
		return strcmp( (string) $b['data'], (string) $a['data'] );
	} );

	return $righe;
}

/** Registro degli Amatori: Attestato di Corso Base o Corso Intermedio, più recenti prima. */
function gs_registro_amatori() {
	return gs_registro_da_attestati_calendario( array( 'base', 'intermedio' ) );
}

/** Registro dei Professionisti — percorso 1: Attestato di Corso Professionale (Calendario Corsi), più recenti prima. */
function gs_registro_professionale_calendario() {
	return gs_registro_da_attestati_calendario( array( 'professionale' ) );
}

/** Unisce e riordina per data i due percorsi che portano al Registro dei Professionisti. */
function gs_registro_professionisti() {
	$righe = array_merge( gs_registro_professionale_calendario(), gs_registro_allievi() );
	usort( $righe, function ( $a, $b ) {
		return strcmp( (string) $b['data'], (string) $a['data'] );
	} );
	return $righe;
}

/** Tabella HTML riusabile per una lista di righe del registro (nome, livello, data, corso). */
function gs_registro_tabella_html( $righe, $id_html ) {
	if ( ! $righe ) {
		return '<p class="gs-hint">Nessun nome ancora in questo registro.</p>';
	}
	$out  = '<input type="text" class="gs-cerca-input" data-target="#' . esc_attr( $id_html ) . '" placeholder="🔍 Cerca un\'allieva o un allievo…" style="width:100%;max-width:360px;margin-bottom:12px">';
	$out .= '<table class="gs-table gs-paginate" data-per-page="15" id="' . esc_attr( $id_html ) . '">';
	$out .= '<thead><tr><th>Nome</th><th>Livello raggiunto</th><th>Corso</th><th>Data</th></tr></thead><tbody>';
	foreach ( $righe as $r ) {
		$data_lbl = $r['data'] ? date_i18n( 'j F Y', strtotime( $r['data'] ) ) : '—';
		$out .= '<tr data-nome="' . esc_attr( strtolower( $r['nome'] ) ) . '">';
		$out .= '<td>' . esc_html( $r['nome'] ) . '</td>';
		$out .= '<td>' . esc_html( trim( $r['livello']['simbolo'] . ' ' . $r['livello']['titolo'] ) ) . '</td>';
		$out .= '<td>' . esc_html( $r['corso_titolo'] ?? '' ) . '</td>';
		$out .= '<td>' . esc_html( $data_lbl ) . '</td>';
		$out .= '</tr>';
	}
	$out .= '</tbody></table>';
	return $out;
}

add_shortcode( 'gs_registro_ufficiale', 'gs_sc_registro_ufficiale' );
function gs_sc_registro_ufficiale() {
	if ( function_exists( 'gs_sez_collab_pagina_ok' ) && ! gs_sez_collab_pagina_ok( 'registro' ) ) { return '<div class="gs-box"><p>Questa sezione non è disponibile al momento.</p></div>'; }

	$professionisti = gs_registro_professionisti();
	$amatori        = gs_registro_amatori();

	$out  = gs_box_open( "📜 Il Registro Ufficiale dell'Accademia della Sfoglia" );
	$out .= gs_sezione_aiuto( 'Il Registro si divide in due rami, con criteri diversi: il Registro degli Amatori (Attestato di Corso Base o Corso Intermedio dal Calendario Corsi, oppure Attestato di Frequenza Intermedia da Rina Online) e il Registro dei Professionisti (Attestato di Corso Professionale, oppure il percorso privato dei Corsi Online fino alla Laurea in Sfoglia). Tutte le iscritte al sito, invece, sono nella sezione «Le Sfogline» — un elenco ancora diverso.' );
	$out .= '<p class="gs-hint">L\'Accademia della Sfoglia di Rina Poletti ha istituito il Registro Ufficiale delle Sfogline e Sfoglini, diviso in due rami: il Registro degli Amatori dell\'Accademia della Sfoglia e il Registro dei Professionisti dell\'Accademia della Sfoglia.</p>';

	$out .= '<h3 class="gs-box-title">🌿 Registro degli Amatori dell\'Accademia della Sfoglia</h3>';
	$out .= '<p class="gs-hint">Chi ha ricevuto l\'Attestato di Corso Base o di Corso Intermedio (in presenza, dal Calendario Corsi, o a distanza con Rina Online).</p>';
	$out .= gs_registro_tabella_html( $amatori, 'gs-registro-amatori' );

	$out .= '<h3 class="gs-box-title" style="margin-top:26px">🎓 Registro dei Professionisti dell\'Accademia della Sfoglia</h3>';
	$out .= '<p class="gs-hint">Chi ha superato l\'esame del Corso Professionale, o completato l\'intero percorso privato dei Corsi Online fino alla Laurea in Sfoglia.</p>';
	$out .= gs_registro_tabella_html( $professionisti, 'gs-registro-professionisti' );

	$out .= gs_box_close();
	return $out;
}
