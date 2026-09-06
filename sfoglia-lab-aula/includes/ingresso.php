<?php
/**
 * ingresso.php — l'ingresso dello studente: codice classe + nickname, senza
 * password né dati personali (vedi docs/02-specifica-classi-virtuali.md,
 * vincoli V1 e V2).
 *
 * La sessione non usa i sistemi di login di WordPress: uno studente non è
 * un utente WordPress. È un cookie che contiene l'ID del posto (post
 * sla_studente) e un token casuale salvato come meta dello stesso posto;
 * il cookie vale 30 giorni.
 *
 * Semplificazione dichiarata di questa prima versione: se il docente non
 * libera il posto dal cruscotto, un nickname già occupato non si può
 * riprendere da un altro dispositivo (non essendoci una password non c'è
 * modo di distinguere lo studente vero da un compagno che indovina il
 * nickname). "Libera il posto" in classi.php/cruscotto.php è la funzione
 * pensata apposta per il cambio di dispositivo, non solo per l'errore di
 * persona.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SLA_COOKIE = 'sla_sessione';
const SLA_COOKIE_GIORNI = 30;

/**
 * I posti ancora liberi di una classe (per il menu a tendina del
 * nickname nella pagina di ingresso).
 */
function sla_studenti_liberi( $classe_id ) {
	return get_posts( array(
		'post_type'      => 'sla_studente',
		'post_status'    => 'publish',
		'post_parent'    => (int) $classe_id,
		'posts_per_page' => -1,
		'orderby'        => 'ID',
		'order'          => 'ASC',
		'meta_query'     => array(
			array(
				'key'     => 'sla_occupato',
				'value'   => '1',
				'compare' => '!=',
			),
		),
	) );
}

/**
 * Tenta l'ingresso: codice classe + ID del posto scelto. Occupa il posto,
 * genera un token e imposta il cookie. Ritorna l'ID della classe in caso di
 * successo, o un WP_Error.
 */
function sla_studente_entra( $codice, $studente_id ) {
	$classe_id = sla_trova_classe_da_codice( $codice );
	if ( ! $classe_id ) {
		return new WP_Error( 'sla_codice_non_valido', 'Codice classe non riconosciuto.' );
	}

	$studente_id = (int) $studente_id;
	$studente    = get_post( $studente_id );
	if ( ! $studente || 'sla_studente' !== $studente->post_type || (int) $studente->post_parent !== $classe_id ) {
		return new WP_Error( 'sla_posto_non_valido', 'Posto non riconosciuto in questa classe.' );
	}

	if ( '1' === get_post_meta( $studente_id, 'sla_occupato', true ) ) {
		return new WP_Error( 'sla_posto_occupato', 'Questo nickname è già stato scelto da qualcun altro. Chiedi al docente di liberarlo se è un errore.' );
	}

	$token = wp_generate_password( 32, false, false );
	update_post_meta( $studente_id, 'sla_occupato', '1' );
	update_post_meta( $studente_id, 'sla_token', $token );

	sla_imposta_cookie_sessione( $studente_id, $token );

	return $classe_id;
}

function sla_imposta_cookie_sessione( $studente_id, $token ) {
	$valore  = $studente_id . ':' . $token;
	$scadenza = time() + SLA_COOKIE_GIORNI * DAY_IN_SECONDS;
	setcookie( SLA_COOKIE, $valore, array(
		'expires'  => $scadenza,
		'path'     => '/',
		'secure'   => is_ssl(),
		'httponly' => true,
		'samesite' => 'Lax',
	) );
}

/**
 * Legge il cookie di sessione e ritorna i dati dello studente collegato
 * (array con studente_id, classe_id, nickname) o null se non c'è una
 * sessione valida.
 */
function sla_studente_da_sessione() {
	if ( empty( $_COOKIE[ SLA_COOKIE ] ) ) {
		return null;
	}

	$parti = explode( ':', (string) $_COOKIE[ SLA_COOKIE ], 2 );
	if ( 2 !== count( $parti ) ) {
		return null;
	}
	list( $studente_id, $token ) = $parti;
	$studente_id = (int) $studente_id;

	$studente = get_post( $studente_id );
	if ( ! $studente || 'sla_studente' !== $studente->post_type ) {
		return null;
	}

	$token_salvato = get_post_meta( $studente_id, 'sla_token', true );
	if ( '' === $token_salvato || ! hash_equals( (string) $token_salvato, (string) $token ) ) {
		return null;
	}

	return array(
		'studente_id' => $studente_id,
		'classe_id'   => (int) $studente->post_parent,
		'nickname'    => get_post_meta( $studente_id, 'sla_nickname', true ),
	);
}

/**
 * Libera un posto (azione del docente dal cruscotto): toglie l'occupazione
 * e il token, così il nickname torna scegliibile e la vecchia sessione
 * smette di funzionare.
 */
function sla_libera_posto( $studente_id ) {
	update_post_meta( $studente_id, 'sla_occupato', '' );
	delete_post_meta( $studente_id, 'sla_token' );
}

// -----------------------------------------------------------------------------
// AJAX
// -----------------------------------------------------------------------------

add_action( 'wp_ajax_nopriv_sla_entra', 'sla_ajax_entra' );
add_action( 'wp_ajax_sla_entra', 'sla_ajax_entra' );
function sla_ajax_entra() {
	check_ajax_referer( 'sla_ajax_pubblico', 'nonce' );

	$risultato = sla_studente_entra( $_POST['codice'] ?? '', $_POST['studente_id'] ?? 0 );
	if ( is_wp_error( $risultato ) ) {
		wp_send_json_error( array( 'message' => $risultato->get_error_message() ) );
	}

	wp_send_json_success( array( 'classe_id' => $risultato ) );
}

add_action( 'wp_ajax_nopriv_sla_elenco_nickname', 'sla_ajax_elenco_nickname' );
add_action( 'wp_ajax_sla_elenco_nickname', 'sla_ajax_elenco_nickname' );
function sla_ajax_elenco_nickname() {
	check_ajax_referer( 'sla_ajax_pubblico', 'nonce' );

	$classe_id = sla_trova_classe_da_codice( $_POST['codice'] ?? '' );
	if ( ! $classe_id ) {
		wp_send_json_error( array( 'message' => 'Codice classe non riconosciuto.' ) );
	}

	$liberi = array_map( function ( $post ) {
		return array( 'id' => $post->ID, 'nickname' => get_post_meta( $post->ID, 'sla_nickname', true ) );
	}, sla_studenti_liberi( $classe_id ) );

	wp_send_json_success( array( 'nickname' => $liberi ) );
}

add_action( 'wp_ajax_sla_libera_posto', 'sla_ajax_libera_posto' );
function sla_ajax_libera_posto() {
	check_ajax_referer( 'sla_ajax', 'nonce' );
	$studente_id = (int) ( $_POST['studente_id'] ?? 0 );
	$studente    = get_post( $studente_id );

	if ( ! $studente || 'sla_studente' !== $studente->post_type || ! sla_docente_possiede_classe( $studente->post_parent ) ) {
		wp_send_json_error( array( 'message' => 'Posto non riconosciuto.' ) );
	}

	sla_libera_posto( $studente_id );
	wp_send_json_success();
}
