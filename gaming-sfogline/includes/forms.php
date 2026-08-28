<?php
/**
 * forms.php — Invio Diario dell'Impasto e Consigli della Community.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Diario dell'Impasto (privato)
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_aggiungi_diario', 'gs_ajax_aggiungi_diario' );

function gs_ajax_aggiungi_diario() {
	check_ajax_referer( 'gs_ajax', 'nonce' );

	$user_id = get_current_user_id();
	// gs_puo_partecipare() copre anche la congelata, non solo la non
	// approvata: era rimasto il controllo vecchio, trovato il 26/08/2026
	// insieme ad altri dieci nella stessa condizione.
	if ( ! $user_id || ! gs_puo_partecipare( $user_id ) ) {
		wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) );
	}

	$check = gs_antispam_check( $_POST, 'diario' );
	if ( is_wp_error( $check ) ) {
		wp_send_json_error( array( 'message' => $check->get_error_message() ) );
	}

	$titolo  = gs_clean( $_POST['titolo'] ?? '' );
	$testo   = sanitize_textarea_field( wp_unslash( $_POST['testo'] ?? '' ) );
	$famiglia = ! empty( $_POST['ricetta_famiglia'] );

	if ( ! $testo ) {
		wp_send_json_error( array( 'message' => 'Scrivi qualcosa nel diario.' ) );
	}
	if ( ! $titolo ) {
		$titolo = 'Voce del ' . date_i18n( 'j F Y' );
	}

	$post_id = wp_insert_post( array(
		'post_type'    => 'gs_diario',
		'post_status'  => 'private',
		'post_title'   => $titolo,
		'post_content' => $testo,
		'post_author'  => $user_id,
	) );

	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( array( 'message' => 'Errore nel salvataggio.' ) );
	}

	if ( $famiglia ) {
		update_post_meta( $post_id, 'gs_ricetta_famiglia', 1 );
		do_action( 'gs_ricetta_famiglia', $user_id );
	}

	// I punti del Diario si prendono UNA VOLTA AL GIORNO — la stessa regola
	// già scelta da Ennio per le foto del Tavolo di Lavoro ("il numero di
	// foto caricato non influisce sul punteggio"). Scrivere resta libero: la
	// voce si salva sempre, cambia solo se paga. Senza questo, Diario e
	// Consigli insieme valevano 350 punti l'ora — i 2.500 del Buono Sfoglia
	// in 14 minuti al giorno, senza mai impastare (misurato 26/08/2026).
	$oggi_diario   = current_time( 'Y-m-d' );
	$paga_oggi     = get_user_meta( $user_id, 'gs_diario_punti_il', true ) !== $oggi_diario;
	if ( $paga_oggi ) {
		update_user_meta( $user_id, 'gs_diario_punti_il', $oggi_diario );   // contrassegno PRIMA
		gs_add_points( $user_id, gs_get_points_value( 'voce_diario', 15 ), 'Voce di diario aggiunta' );
	}
	gs_detect_evo( $user_id, $titolo . ' ' . $testo );
	do_action( 'gs_diario_aggiunto', $user_id, $post_id );

	$messaggio = $paga_oggi
		? 'Voce salvata nel tuo diario. +' . gs_get_points_value( 'voce_diario', 15 ) . ' punti!'
		: 'Voce salvata. I punti del Diario li hai già presi oggi — torna domani.';
	wp_send_json_success( array( 'message' => $messaggio ) );
}

/**
 * Voci di diario di un utente (private).
 */
function gs_get_diario( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) { return array(); } // mai autore 0: eviterebbe di pescare tutto il sito
	return gs_get_posts_by_author( array(
		'post_type'      => 'gs_diario',
		'post_status'    => 'private',
		'author'         => $user_id,
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
}

// -----------------------------------------------------------------------------
// Consigli della Community (pubblici)
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_aggiungi_consiglio', 'gs_ajax_aggiungi_consiglio' );

function gs_ajax_aggiungi_consiglio() {
	check_ajax_referer( 'gs_ajax', 'nonce' );

	$user_id = get_current_user_id();
	// Stessa cautela del diario qui sopra.
	if ( ! $user_id || ! gs_puo_partecipare( $user_id ) ) {
		wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) );
	}

	$check = gs_antispam_check( $_POST, 'consiglio' );
	if ( is_wp_error( $check ) ) {
		wp_send_json_error( array( 'message' => $check->get_error_message() ) );
	}

	$titolo   = gs_clean( $_POST['titolo'] ?? '' );
	$testo    = sanitize_textarea_field( wp_unslash( $_POST['testo'] ?? '' ) );
	$famiglia = ! empty( $_POST['ricetta_famiglia'] );

	if ( ! $testo ) {
		wp_send_json_error( array( 'message' => 'Scrivi il tuo consiglio.' ) );
	}
	if ( ! $titolo ) {
		$titolo = 'Consiglio di ' . wp_get_current_user()->display_name;
	}

	$post_id = wp_insert_post( array(
		'post_type'    => 'gs_consiglio',
		'post_status'  => 'publish',
		'post_title'   => $titolo,
		'post_content' => $testo,
		'post_author'  => $user_id,
	) );

	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( array( 'message' => 'Errore nel salvataggio.' ) );
	}

	if ( $famiglia ) {
		update_post_meta( $post_id, 'gs_ricetta_famiglia', 1 );
		do_action( 'gs_ricetta_famiglia', $user_id );
	}

	// Stessa regola del Diario, qui sopra: punti una volta al giorno,
	// scrivere resta libero (26/08/2026).
	$oggi_consiglio = current_time( 'Y-m-d' );
	$paga_oggi      = get_user_meta( $user_id, 'gs_consiglio_punti_il', true ) !== $oggi_consiglio;
	if ( $paga_oggi ) {
		update_user_meta( $user_id, 'gs_consiglio_punti_il', $oggi_consiglio );   // contrassegno PRIMA
		gs_add_points( $user_id, gs_get_points_value( 'consiglio', 20 ), 'Consiglio condiviso' );
	}
	gs_detect_evo( $user_id, $titolo . ' ' . $testo );
	do_action( 'gs_consiglio_aggiunto', $user_id, $post_id );

	$messaggio = $paga_oggi
		? 'Grazie per il consiglio! +' . gs_get_points_value( 'consiglio', 20 ) . ' punti.'
		: 'Grazie per il consiglio! I punti dei Consigli li hai già presi oggi — torna domani.';
	wp_send_json_success( array( 'message' => $messaggio ) );
}

/**
 * Consigli pubblici (tutti).
 */
function gs_get_consigli( $limit = 30 ) {
	return get_posts( array(
		'post_type'      => 'gs_consiglio',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
}

// -----------------------------------------------------------------------------
// Modifica / eliminazione delle proprie voci (diario e consigli)
// -----------------------------------------------------------------------------
function gs_voce_tipo_cpt( $tipo ) {
	return ( 'consiglio' === $tipo ) ? 'gs_consiglio' : 'gs_diario';
}
/** Può modificare questa voce? (autrice o gestore) */
function gs_voce_puo_modificare( $post_id ) {
	$p = get_post( $post_id );
	if ( ! $p ) { return false; }
	$uid = get_current_user_id();
	if ( ! $uid ) { return false; }
	if ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) { return true; }
	return (int) $p->post_author === (int) $uid;
}

add_action( 'wp_ajax_gs_voce_salva', 'gs_ajax_voce_salva' );
function gs_ajax_voce_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$id    = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$tipo  = isset( $_POST['tipo'] ) ? sanitize_key( wp_unslash( $_POST['tipo'] ) ) : 'diario';
	$cpt   = gs_voce_tipo_cpt( $tipo );
	if ( get_post_type( $id ) !== $cpt ) { wp_send_json_error( array( 'message' => 'Voce non valida.' ) ); }
	if ( ! gs_voce_puo_modificare( $id ) ) { wp_send_json_error( array( 'message' => 'Puoi modificare solo i tuoi contenuti.' ) ); }
	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	$testo  = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';
	if ( function_exists( 'gs_msg_clean' ) ) { $testo = gs_msg_clean( $testo ); }
	if ( '' === trim( $testo ) ) { wp_send_json_error( array( 'message' => 'Il testo non può essere vuoto.' ) ); }
	wp_update_post( array(
		'ID'           => $id,
		'post_title'   => $titolo ? $titolo : get_the_title( $id ),
		'post_content' => $testo,
	) );
	wp_send_json_success( array( 'message' => 'Modifiche salvate.' ) );
}

add_action( 'wp_ajax_gs_voce_elimina', 'gs_ajax_voce_elimina' );
function gs_ajax_voce_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$id   = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$tipo = isset( $_POST['tipo'] ) ? sanitize_key( wp_unslash( $_POST['tipo'] ) ) : 'diario';
	if ( get_post_type( $id ) !== gs_voce_tipo_cpt( $tipo ) ) { wp_send_json_error( array( 'message' => 'Voce non valida.' ) ); }
	if ( ! gs_voce_puo_modificare( $id ) ) { wp_send_json_error( array( 'message' => 'Puoi eliminare solo i tuoi contenuti.' ) ); }
	wp_trash_post( $id ); // va nel cestino personale, recuperabile
	wp_send_json_success( array( 'message' => 'Spostato nel cestino.' ) );
}
