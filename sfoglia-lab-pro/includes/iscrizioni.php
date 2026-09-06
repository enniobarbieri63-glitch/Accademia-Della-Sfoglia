<?php
/**
 * iscrizioni.php — iscrizione pubblica a una sessione, con lo storico dei
 * pagamenti. Il gestore registra ogni bonifico ricevuto; lo storico non si
 * sovrascrive mai, si aggiunge soltanto (stesso principio già collaudato
 * in gaming-sfogline per gli Artigiani/Scuole partner).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Iscrive una persona a una sessione, se ci sono posti. Ritorna l'ID
 * dell'iscrizione o un WP_Error.
 */
function slp_iscrivi( $sessione_id, $nome, $email, $telefono ) {
	$sessione = get_post( $sessione_id );
	if ( ! $sessione || 'slp_sessione' !== $sessione->post_type ) {
		return new WP_Error( 'slp_sessione_non_trovata', 'Sessione non trovata.' );
	}

	$liberi = slp_posti_liberi( $sessione_id );
	if ( null !== $liberi && $liberi <= 0 ) {
		return new WP_Error( 'slp_posti_esauriti', 'Non ci sono più posti disponibili per questa data.' );
	}

	$nome = slp_clean( $nome );
	if ( '' === $nome ) {
		return new WP_Error( 'slp_nome_vuoto', 'Il nome non può essere vuoto.' );
	}
	$email = sanitize_email( wp_unslash( $email ) );
	if ( ! is_email( $email ) ) {
		return new WP_Error( 'slp_email_non_valida', 'Indirizzo email non valido.' );
	}

	$iscrizione_id = wp_insert_post( array(
		'post_type'   => 'slp_iscrizione',
		'post_title'  => $nome . ' — ' . get_the_title( $sessione ),
		'post_parent' => (int) $sessione_id,
		'post_status' => 'publish',
		'meta_input'  => array(
			'slp_nome'      => $nome,
			'slp_email'     => $email,
			'slp_telefono'  => slp_clean( $telefono ),
			'slp_stato'     => 'in_attesa',
			'slp_pagamenti' => array(),
		),
	), true );

	return $iscrizione_id;
}

/**
 * Registra un pagamento ricevuto (bonifico) su un'iscrizione. Aggiunge
 * allo storico, non sovrascrive mai le voci precedenti. Se il totale
 * versato raggiunge almeno l'acconto dovuto, l'iscrizione passa a
 * "confermata".
 */
function slp_registra_pagamento( $iscrizione_id, $importo_centesimi, $nota = '' ) {
	$iscrizione = get_post( $iscrizione_id );
	if ( ! $iscrizione || 'slp_iscrizione' !== $iscrizione->post_type ) {
		return new WP_Error( 'slp_iscrizione_non_trovata', 'Iscrizione non trovata.' );
	}

	$pagamenti   = get_post_meta( $iscrizione_id, 'slp_pagamenti', true );
	$pagamenti   = is_array( $pagamenti ) ? $pagamenti : array();
	$pagamenti[] = array(
		'data'              => current_time( 'mysql' ),
		'importo_centesimi' => max( 0, (int) $importo_centesimi ),
		'nota'              => slp_clean( $nota ),
	);
	update_post_meta( $iscrizione_id, 'slp_pagamenti', $pagamenti );

	$corso_codice = get_post_meta( $iscrizione->post_parent, 'slp_corso_codice', true );
	$corso        = slp_get_corso( $corso_codice );
	if ( $corso ) {
		$acconto = slp_calcola_acconto( $corso['quota'] );
		$totale  = slp_totale_pagato( $pagamenti );
		$stato   = slp_stato_pagamento( $corso['quota'], $acconto, $totale );
		if ( in_array( $stato, array( 'acconto_versato', 'saldo_versato' ), true ) ) {
			update_post_meta( $iscrizione_id, 'slp_stato', 'confermata' );
		}
	}

	return true;
}

/**
 * Le iscrizioni di una sessione, più recenti prima.
 */
function slp_iscrizioni_della_sessione( $sessione_id ) {
	return get_posts( array(
		'post_type'      => 'slp_iscrizione',
		'post_status'    => 'publish',
		'post_parent'    => (int) $sessione_id,
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
}

// -----------------------------------------------------------------------------
// AJAX
// -----------------------------------------------------------------------------

add_action( 'wp_ajax_nopriv_slp_iscrivi', 'slp_ajax_iscrivi' );
add_action( 'wp_ajax_slp_iscrivi', 'slp_ajax_iscrivi' );
function slp_ajax_iscrivi() {
	check_ajax_referer( 'slp_ajax_pubblico', 'nonce' );

	$iscrizione_id = slp_iscrivi(
		$_POST['sessione_id'] ?? 0,
		$_POST['nome'] ?? '',
		$_POST['email'] ?? '',
		$_POST['telefono'] ?? ''
	);

	if ( is_wp_error( $iscrizione_id ) ) {
		wp_send_json_error( array( 'message' => $iscrizione_id->get_error_message() ) );
	}

	wp_send_json_success( array( 'iscrizione_id' => $iscrizione_id ) );
}

add_action( 'wp_ajax_slp_registra_pagamento', 'slp_ajax_registra_pagamento' );
function slp_ajax_registra_pagamento() {
	check_ajax_referer( 'slp_ajax', 'nonce' );
	if ( ! slp_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Non hai i permessi per registrare un pagamento.' ) );
	}

	$importo_euro = (float) str_replace( ',', '.', (string) ( $_POST['importo'] ?? 0 ) );
	$risultato    = slp_registra_pagamento(
		(int) ( $_POST['iscrizione_id'] ?? 0 ),
		(int) round( $importo_euro * 100 ),
		$_POST['nota'] ?? ''
	);

	if ( is_wp_error( $risultato ) ) {
		wp_send_json_error( array( 'message' => $risultato->get_error_message() ) );
	}

	wp_send_json_success();
}
