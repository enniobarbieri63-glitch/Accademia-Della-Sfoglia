<?php
/**
 * cassaforte-sapere.php — La Cassaforte del Sapere.
 *
 * Contenuto esclusivo che si sblocca solo quando un numero sufficiente di
 * sfogline raggiunge, tutte insieme, una soglia collettiva (es. "quando 50
 * sfogline arrivano almeno al Livello Intermedio"). Non individuale: conta
 * la comunità, non la singola sfoglina. Una volta raggiunta, resta sbloccata
 * per sempre (non si "richiude" se qualche sfoglina scende sotto la soglia).
 *
 * CPT gs_cassaforte → meta:
 *  - gs_cassaforte_soglia   (int, quante sfogline servono)
 *  - gs_cassaforte_livello  (int, indice del livello minimo richiesto — 0-based,
 *                            come gs_settings()['levels'])
 * Il nome della cassaforte sta in post_title, il contenuto da sbloccare in
 * post_content.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_cassaforte_register_cpt' );
function gs_cassaforte_register_cpt() {
	register_post_type( 'gs_cassaforte', array(
		'labels'       => array( 'name' => 'La Cassaforte del Sapere' ),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'title', 'editor' ),
	) );
}

function gs_cassaforte_get( $id ) {
	return array(
		'id'         => $id,
		'titolo'     => get_the_title( $id ),
		'contenuto'  => get_post( $id ) ? get_post( $id )->post_content : '',
		'soglia'     => (int) get_post_meta( $id, 'gs_cassaforte_soglia', true ),
		'livello'    => (int) get_post_meta( $id, 'gs_cassaforte_livello', true ),
	);
}

function gs_cassaforti_tutte() {
	return gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_cassaforte',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'ASC',
		'suppress_filters' => true,
	) ), 'gs_cassaforte' );
}

/** Quante sfogline hanno raggiunto almeno il livello richiesto (indice 0-based), in questo momento. */
function gs_cassaforte_conteggio( $livello_richiesto ) {
	$n = 0;
	foreach ( get_users( array( 'fields' => 'ID' ) ) as $uid ) {
		if ( ! gs_e_sfoglina_vera( (int) $uid ) ) { continue; }
		$totale = (int) get_user_meta( $uid, 'gs_points', true );
		if ( gs_level_index( $totale ) >= (int) $livello_richiesto ) { $n++; }
	}
	return $n;
}

/** True se questa cassaforte è sbloccata ora (soglia raggiunta o superata). */
function gs_cassaforte_sbloccata( $id ) {
	$c = gs_cassaforte_get( $id );
	if ( $c['soglia'] <= 0 ) { return false; }
	return gs_cassaforte_conteggio( $c['livello'] ) >= $c['soglia'];
}

/**
 * Avviso Aeroplanino allo sblocco (punto 11, Ennio 21/08/2026: era uno dei
 * "buchi" trovati) — a tutta la community, non alla singola sfoglina: lo
 * sblocco è collettivo, non un traguardo personale. Agganciato a
 * gs_level_up perché è l'UNICO evento che può far salire il conteggio di
 * gs_cassaforte_conteggio() (sfogline che raggiungono il livello richiesto):
 * dopo ogni salita di livello di chiunque, controlla se qualche cassaforte
 * non ancora notificata è appena passata da chiusa a sbloccata. Il meta
 * gs_cassaforte_notificata evita di avvisare di nuovo a ogni livello
 * successivo, una volta già sbloccata.
 */
add_action( 'gs_level_up', 'gs_cassaforte_controlla_sblocco', 25 );
function gs_cassaforte_controlla_sblocco() {
	if ( ! function_exists( 'gs_cassaforti_tutte' ) ) { return; }
	foreach ( gs_cassaforti_tutte() as $cf ) {
		if ( get_post_meta( $cf->ID, 'gs_cassaforte_notificata', true ) ) { continue; }
		if ( ! gs_cassaforte_sbloccata( $cf->ID ) ) { continue; }
		update_post_meta( $cf->ID, 'gs_cassaforte_notificata', 1 );
		if ( function_exists( 'gs_sez_sfogline' ) && function_exists( 'gs_accoda_volo' ) ) {
			$c    = gs_cassaforte_get( $cf->ID );
			$link = function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_cassaforte' ) : '';
			foreach ( gs_sez_sfogline() as $u ) {
				gs_accoda_volo( $u->ID, '🔓 LA CASSAFORTE «' . mb_strtoupper( $c['titolo'] ) . '» SI È SBLOCCATA!', $link );
			}
		}
	}
}

// -----------------------------------------------------------------------------
// [gs_cassaforte_sapere] — lato sfoglina
// -----------------------------------------------------------------------------
add_shortcode( 'gs_cassaforte_sapere', 'gs_sc_cassaforte_sapere' );
function gs_sc_cassaforte_sapere() {
	if ( $g = gs_gate_riservato() ) { return $g; }
	if ( $g = gs_blackout_gate() ) { return $g; }

	$out = gs_box_open( '🔐 La Cassaforte del Sapere' );
	$out .= gs_sezione_aiuto( 'Ogni cassaforte si apre solo quando un numero sufficiente di sfogline raggiunge insieme un livello minimo — non basta il tuo livello personale, conta quante siete tutte insieme. Finché non si apre vedi solo quante ne mancano; una volta aperta, il contenuto resta visibile a tutte per sempre.' );

	$cassaforti = gs_cassaforti_tutte();
	if ( ! $cassaforti ) {
		$out .= '<p>Nessuna cassaforte al momento.</p>' . gs_box_close();
		return $out;
	}

	$livelli = gs_settings()['levels'];
	foreach ( $cassaforti as $cf ) {
		$c        = gs_cassaforte_get( $cf->ID );
		$attuale  = gs_cassaforte_conteggio( $c['livello'] );
		$sbloccata = gs_cassaforte_sbloccata( $cf->ID );
		$nome_livello = $livelli[ $c['livello'] ]['titolo'] ?? '—';

		$out .= '<div class="gs-todo-riquadro" style="margin-bottom:12px">';
		$out .= '<p class="gs-hint" style="font-size:16px"><strong>' . ( $sbloccata ? '🔓 ' : '🔒 ' ) . esc_html( $c['titolo'] ) . '</strong></p>';
		if ( $sbloccata ) {
			$out .= '<p class="gs-hint">Sbloccata! ' . (int) $attuale . ' sfogline hanno raggiunto almeno il livello «' . esc_html( $nome_livello ) . '».</p>';
			$out .= '<div class="gs-inbox-testo">' . nl2br( esc_html( $c['contenuto'] ) ) . '</div>';
		} else {
			$out .= '<p class="gs-hint">Ancora chiusa: servono <strong>' . (int) $c['soglia'] . '</strong> sfogline al livello «' . esc_html( $nome_livello ) . '» o superiore. Per ora ce ne sono <strong>' . (int) $attuale . '</strong>.</p>';
		}
		$out .= '</div>';
	}
	$out .= gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// PANNELLO — gestione casseforti (titolare)
// -----------------------------------------------------------------------------
function gs_pannello_cassaforte_sapere() {
	if ( ! gs_can_manage() ) { return; }

	$livelli = gs_settings()['levels'];

	echo gs_box_open( 'La Cassaforte del Sapere', '', 'gs-box-cassaforte' );
	echo '<p class="gs-hint">Crea un contenuto che si sblocca solo quando abbastanza sfogline, tutte insieme, raggiungono un certo livello. Una volta sbloccata resta visibile a tutte, anche se in seguito il conteggio scende sotto la soglia.</p>';

	echo '<form class="gs-form gs-form-cassaforte-crea" onsubmit="return false">';
	echo '<p><label>Nome della cassaforte<br><input type="text" name="titolo" style="width:100%" required></label></p>';
	echo '<p><label>Contenuto da sbloccare<br><textarea name="contenuto" rows="4" style="width:100%"></textarea></label></p>';
	echo '<p style="display:flex;gap:12px;flex-wrap:wrap"><label>Quante sfogline servono<br><input type="number" name="soglia" min="1" style="width:120px" required></label>';
	echo '<label>Livello minimo richiesto<br><select name="livello">';
	foreach ( $livelli as $i => $l ) { echo '<option value="' . (int) $i . '">' . esc_html( $l['simbolo'] . ' ' . $l['titolo'] ) . '</option>'; }
	echo '</select></label></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-cassaforte-crea">Crea cassaforte</button> <span class="gs-cassaforte-crea-msg gs-richiesta-esito"></span></p>';
	echo '</form>';

	$tutte = gs_cassaforti_tutte();
	echo '<h4 style="margin-top:14px">Casseforti create (' . count( $tutte ) . ')</h4>';
	if ( ! $tutte ) {
		echo '<p class="gs-hint">Nessuna cassaforte ancora creata.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="8">';
		foreach ( $tutte as $cf ) {
			$c        = gs_cassaforte_get( $cf->ID );
			$attuale  = gs_cassaforte_conteggio( $c['livello'] );
			$sbloccata = gs_cassaforte_sbloccata( $cf->ID );
			echo '<details class="gs-inbox-item" data-cassaforte="' . (int) $cf->ID . '">';
			echo '<summary class="gs-inbox-oggetto">' . ( $sbloccata ? '🔓 ' : '🔒 ' ) . esc_html( $c['titolo'] ) . ' <span class="gs-msg-data">' . (int) $attuale . ' / ' . (int) $c['soglia'] . '</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			echo '<form class="gs-form gs-form-cassaforte-modifica" data-cassaforte="' . (int) $cf->ID . '" onsubmit="return false">';
			echo '<p><label>Nome della cassaforte<br><input type="text" name="titolo" value="' . esc_attr( $c['titolo'] ) . '" style="width:100%" required></label></p>';
			echo '<p><label>Contenuto da sbloccare<br><textarea name="contenuto" rows="4" style="width:100%">' . esc_textarea( $c['contenuto'] ) . '</textarea></label></p>';
			echo '<p style="display:flex;gap:12px;flex-wrap:wrap"><label>Quante sfogline servono<br><input type="number" name="soglia" min="1" value="' . (int) $c['soglia'] . '" style="width:120px" required></label>';
			echo '<label>Livello minimo richiesto<br><select name="livello">';
			foreach ( $livelli as $i => $l ) { echo '<option value="' . (int) $i . '" ' . selected( $c['livello'], $i, false ) . '>' . esc_html( $l['simbolo'] . ' ' . $l['titolo'] ) . '</option>'; }
			echo '</select></label></p>';
			echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-cassaforte-salva">Salva modifiche</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-cassaforte-elimina" data-cassaforte="' . (int) $cf->ID . '">Elimina</button> <span class="gs-cassaforte-mod-msg gs-richiesta-esito"></span></p>';
			echo '</form>';
			echo '</div></details>';
		}
		echo '</div>';
	}

	$trash = get_posts( array( 'post_type' => 'gs_cassaforte', 'post_status' => 'trash', 'posts_per_page' => 50, 'suppress_filters' => true ) );
	echo '<div class="gs-sezione-cestino">';
	echo '<h4 class="gs-titolo-cestino">🗑️ Cestino</h4>';
	if ( ! $trash ) {
		echo '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		echo '<table class="gs-table gs-paginate" data-per-page="8"><thead><tr>' . gs_cestino_th_checkbox() . '<th>Cassaforte</th><th></th></tr></thead><tbody>';
		foreach ( $trash as $cf ) {
			echo '<tr data-cassaforte="' . (int) $cf->ID . '">' . gs_cestino_td_checkbox( $cf->ID ) . '<td>' . esc_html( get_the_title( $cf ) ) . '</td>';
			echo '<td><button class="gs-btn gs-btn-sm gs-btn-verde gs-cassaforte-ripristina" data-cassaforte="' . (int) $cf->ID . '">Ripristina</button> <span class="gs-cassaforte-trow-msg gs-richiesta-esito"></span></td></tr>';
		}
		echo '</tbody></table>';
		echo gs_cestino_azioni_bulk( 'gs_cassaforte' );
	}
	echo '</div>';

	echo gs_box_close();
}

add_action( 'wp_ajax_gs_cassaforte_crea', 'gs_ajax_cassaforte_crea' );
function gs_ajax_cassaforte_crea() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	if ( '' === trim( $titolo ) ) { wp_send_json_error( array( 'message' => 'Scrivi il nome della cassaforte.' ) ); }
	$contenuto = isset( $_POST['contenuto'] ) ? sanitize_textarea_field( wp_unslash( $_POST['contenuto'] ) ) : '';
	$soglia    = isset( $_POST['soglia'] ) ? max( 1, (int) $_POST['soglia'] ) : 1;
	$livello   = isset( $_POST['livello'] ) ? max( 0, (int) $_POST['livello'] ) : 0;

	$cid = wp_insert_post( array(
		'post_type'    => 'gs_cassaforte',
		'post_status'  => 'publish',
		'post_title'   => $titolo,
		'post_content' => $contenuto,
	) );
	if ( is_wp_error( $cid ) || ! $cid ) { wp_send_json_error( array( 'message' => 'Errore nella creazione.' ) ); }

	update_post_meta( $cid, 'gs_cassaforte_soglia', $soglia );
	update_post_meta( $cid, 'gs_cassaforte_livello', $livello );

	wp_send_json_success( array( 'message' => 'Cassaforte creata.' ) );
}

add_action( 'wp_ajax_gs_cassaforte_modifica', 'gs_ajax_cassaforte_modifica' );
function gs_ajax_cassaforte_modifica() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$cid = isset( $_POST['cassaforte'] ) ? (int) $_POST['cassaforte'] : 0;
	if ( 'gs_cassaforte' !== get_post_type( $cid ) ) { wp_send_json_error( array( 'message' => 'Cassaforte non valida.' ) ); }

	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	if ( '' === trim( $titolo ) ) { wp_send_json_error( array( 'message' => 'Scrivi il nome della cassaforte.' ) ); }
	$contenuto = isset( $_POST['contenuto'] ) ? sanitize_textarea_field( wp_unslash( $_POST['contenuto'] ) ) : '';
	$soglia    = isset( $_POST['soglia'] ) ? max( 1, (int) $_POST['soglia'] ) : 1;
	$livello   = isset( $_POST['livello'] ) ? max( 0, (int) $_POST['livello'] ) : 0;

	wp_update_post( array( 'ID' => $cid, 'post_title' => $titolo, 'post_content' => $contenuto ) );
	update_post_meta( $cid, 'gs_cassaforte_soglia', $soglia );
	update_post_meta( $cid, 'gs_cassaforte_livello', $livello );

	wp_send_json_success( array( 'message' => 'Cassaforte aggiornata.' ) );
}

add_action( 'wp_ajax_gs_cassaforte_elimina', 'gs_ajax_cassaforte_elimina' );
function gs_ajax_cassaforte_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$cid = isset( $_POST['cassaforte'] ) ? (int) $_POST['cassaforte'] : 0;
	if ( 'gs_cassaforte' !== get_post_type( $cid ) ) { wp_send_json_error( array( 'message' => 'Cassaforte non valida.' ) ); }
	wp_trash_post( $cid );
	wp_send_json_success( array( 'message' => 'Spostata nel cestino.' ) );
}

add_action( 'wp_ajax_gs_cassaforte_ripristina', 'gs_ajax_cassaforte_ripristina' );
function gs_ajax_cassaforte_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$cid = isset( $_POST['cassaforte'] ) ? (int) $_POST['cassaforte'] : 0;
	if ( 'gs_cassaforte' !== get_post_type( $cid ) ) { wp_send_json_error( array( 'message' => 'Cassaforte non valida.' ) ); }
	wp_untrash_post( $cid );
	wp_send_json_success( array( 'message' => 'Cassaforte ripristinata.' ) );
}
