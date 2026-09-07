<?php
/**
 * sessioni.php — le date programmate di un corso del catalogo, con i
 * posti ancora disponibili.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * I posti ancora liberi di una sessione: il massimo (della sessione, o
 * del catalogo se la sessione non lo sovrascrive) meno le iscrizioni non
 * annullate. Un corso "AZ-1" con posti_max a 0 (la brigata, non un numero
 * fisso — vedi catalogo.php) non ha un limite: ritorna sempre disponibile.
 */
function slp_posti_liberi( $sessione_id ) {
	$corso_codice = get_post_meta( $sessione_id, 'slp_corso_codice', true );
	$corso        = slp_get_corso( $corso_codice );
	if ( ! $corso ) {
		return 0;
	}

	$posti_max = (int) get_post_meta( $sessione_id, 'slp_posti_max', true );
	if ( 0 === $posti_max ) {
		$posti_max = (int) $corso['posti_max'];
	}
	if ( 0 === $posti_max ) {
		return null; // nessun limite (es. formazione in azienda sulla brigata)
	}

	$iscritti = get_posts( array(
		'post_type'      => 'slp_iscrizione',
		'post_status'    => 'publish',
		'post_parent'    => $sessione_id,
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array( 'key' => 'slp_stato', 'value' => 'annullata', 'compare' => '!=' ),
		),
	) );

	return max( 0, $posti_max - count( $iscritti ) );
}

/**
 * Crea una nuova sessione (una data) per un corso del catalogo.
 */
function slp_crea_sessione( $corso_codice, $data, $posti_max = 0 ) {
	$corso = slp_get_corso( $corso_codice );
	if ( ! $corso ) {
		return new WP_Error( 'slp_corso_sconosciuto', 'Corso non riconosciuto.' );
	}

	$sessione_id = wp_insert_post( array(
		'post_type'   => 'slp_sessione',
		'post_title'  => $corso_codice . ' — ' . slp_clean( $data ),
		'post_status' => 'publish',
		'meta_input'  => array(
			'slp_corso_codice' => sanitize_key( $corso_codice ),
			'slp_data'         => slp_clean( $data ),
			'slp_posti_max'    => max( 0, (int) $posti_max ),
			'slp_stato'        => 'aperta',
		),
	), true );

	return $sessione_id;
}

/**
 * Le sessioni aperte, dalla più vicina alla più lontana nel tempo.
 *
 * L'ordinamento per data fa riferimento esplicitamente alla clausola
 * "per_data" del meta_query (invece di mescolare il vecchio stile
 * meta_key/orderby=meta_value con un meta_query separato): è il modo che
 * WordPress documenta come affidabile per filtrare su un meta e ordinare
 * su un altro nella stessa interrogazione.
 */
function slp_sessioni_aperte() {
	return get_posts( array(
		'post_type'      => 'slp_sessione',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => array( 'per_data' => 'ASC' ),
		'meta_query'     => array(
			'per_data' => array( 'key' => 'slp_data', 'type' => 'DATE' ),
			array( 'key' => 'slp_stato', 'value' => 'aperta' ),
		),
	) );
}

/**
 * TUTTE le sessioni esistenti, qualunque sia il loro stato — a differenza
 * di slp_sessioni_aperte() non filtra nulla. Serve solo al pannello
 * diagnostico qui sotto, per distinguere "non è stata salvata nel
 * database" da "è stata salvata ma non supera il filtro sullo stato".
 */
function slp_sessioni_diagnostica() {
	$sessioni = get_posts( array(
		'post_type'      => 'slp_sessione',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );

	return array_map( function ( $sessione ) {
		return array(
			'id'     => $sessione->ID,
			'titolo' => $sessione->post_title,
			'stato_post'  => $sessione->post_status,
			'corso_codice' => get_post_meta( $sessione->ID, 'slp_corso_codice', true ),
			'data'         => get_post_meta( $sessione->ID, 'slp_data', true ),
			'stato_sessione' => get_post_meta( $sessione->ID, 'slp_stato', true ),
		);
	}, $sessioni );
}

// -----------------------------------------------------------------------------
// AJAX
// -----------------------------------------------------------------------------

add_action( 'wp_ajax_slp_crea_sessione', 'slp_ajax_crea_sessione' );
function slp_ajax_crea_sessione() {
	check_ajax_referer( 'slp_ajax', 'nonce' );
	if ( ! slp_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Non hai i permessi per programmare una sessione.' ) );
	}

	$sessione_id = slp_crea_sessione(
		$_POST['corso_codice'] ?? '',
		$_POST['data'] ?? '',
		$_POST['posti_max'] ?? 0
	);

	if ( is_wp_error( $sessione_id ) ) {
		wp_send_json_error( array( 'message' => $sessione_id->get_error_message() ) );
	}

	wp_send_json_success( array( 'sessione_id' => $sessione_id ) );
}
