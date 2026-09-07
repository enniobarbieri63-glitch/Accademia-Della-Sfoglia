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
	$posti_max = slp_posti_max_effettivi( $sessione_id );
	if ( null === $posti_max ) {
		return null; // nessun limite (es. formazione in azienda sulla brigata)
	}
	if ( 0 === $posti_max ) {
		return 0; // corso non riconosciuto: nessun posto, per sicurezza
	}

	return max( 0, $posti_max - slp_conta_iscritti( $sessione_id ) );
}

/**
 * I posti massimi che valgono davvero per questa sessione: quelli scritti
 * sulla sessione, o in mancanza quelli del catalogo. Ritorna null quando
 * non c'è nessun limite, 0 quando il corso non è riconosciuto.
 */
function slp_posti_max_effettivi( $sessione_id ) {
	$corso = slp_get_corso( get_post_meta( $sessione_id, 'slp_corso_codice', true ) );
	if ( ! $corso ) {
		return 0;
	}

	$posti_max = (int) get_post_meta( $sessione_id, 'slp_posti_max', true );
	if ( 0 === $posti_max ) {
		$posti_max = (int) $corso['posti_max'];
	}

	return 0 === $posti_max ? null : $posti_max;
}

/**
 * Quante iscrizioni non annullate ha questa sessione.
 */
function slp_conta_iscritti( $sessione_id ) {
	$iscritti = get_posts( array(
		'post_type'      => 'slp_iscrizione',
		'post_status'    => 'publish',
		'post_parent'    => (int) $sessione_id,
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array( 'key' => 'slp_stato', 'value' => 'annullata', 'compare' => '!=' ),
		),
	) );

	return count( $iscritti );
}

/**
 * Crea una nuova sessione (una data) per un corso del catalogo.
 */
function slp_crea_sessione( $corso_codice, $data, $posti_max = 0 ) {
	$corso = slp_get_corso( $corso_codice );
	if ( ! $corso ) {
		return new WP_Error( 'slp_corso_sconosciuto', 'Corso non riconosciuto.' );
	}

	// $corso['codice'] (non il parametro grezzo passato dal modulo) è il
	// codice canonico così come appare nel catalogo, es. "PRO-1": va
	// salvato esattamente in quella forma, perché ogni lettura successiva
	// (slp_get_corso() qui sopra) cerca la chiave nel catalogo in modo
	// sensibile alle maiuscole. sanitize_key() lo forzava in minuscolo
	// ("pro-1"), rompendo la corrispondenza — le sessioni si salvavano lo
	// stesso, ma sparivano dall'elenco perché $corso risultava sempre
	// vuoto in fase di visualizzazione (trovato con la diagnostica).
	$sessione_id = wp_insert_post( array(
		'post_type'   => 'slp_sessione',
		'post_title'  => $corso['codice'] . ' — ' . slp_clean( $data ),
		'post_status' => 'publish',
		'meta_input'  => array(
			'slp_corso_codice' => $corso['codice'],
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

/**
 * Gli stati che una sessione può avere. "chiusa" serve quando le
 * iscrizioni si fermano ma il corso si fa lo stesso (per esempio si è al
 * completo, o si è vicini alla data); "annullata" quando il corso non si
 * fa. In entrambi i casi sparisce dal catalogo pubblico e non accetta
 * nuove iscrizioni, ma resta nel pannello con i suoi iscritti.
 */
function slp_stati_sessione() {
	return array(
		'aperta'    => 'Iscrizioni aperte',
		'chiusa'    => 'Iscrizioni chiuse',
		'annullata' => 'Sessione annullata',
	);
}

function slp_cambia_stato_sessione( $sessione_id, $stato ) {
	$sessione = get_post( $sessione_id );
	if ( ! $sessione || 'slp_sessione' !== $sessione->post_type ) {
		return new WP_Error( 'slp_sessione_sconosciuta', 'Sessione non trovata.' );
	}

	if ( ! isset( slp_stati_sessione()[ $stato ] ) ) {
		return new WP_Error( 'slp_stato_sconosciuto', 'Stato non riconosciuto.' );
	}

	update_post_meta( $sessione_id, 'slp_stato', $stato );
	return true;
}

/**
 * Le sessioni che il gestore deve vedere nel pannello: tutte quelle non
 * cestinate, aperte o no. Nel catalogo pubblico compaiono solo le aperte
 * (slp_sessioni_aperte()), ma chi gestisce deve continuare a vedere anche
 * quelle chiuse, altrimenti perde di vista gli iscritti di un corso che
 * ha appena chiuso le iscrizioni.
 */
function slp_sessioni_del_gestore() {
	return get_posts( array(
		'post_type'      => 'slp_sessione',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => array( 'per_data' => 'ASC' ),
		'meta_query'     => array(
			'per_data' => array( 'key' => 'slp_data', 'type' => 'DATE' ),
		),
	) );
}

/**
 * Elimina (cestina) una sessione solo se non ha nessun iscritto: serve a
 * ripulire dalla Diagnostica le sessioni "orfane" salvate col vecchio bug
 * del codice minuscolo (mai visibili nell'elenco normale, e quindi mai
 * raggiungibili da nessuno per iscriversi). Non tocca mai una sessione
 * con anche un solo iscritto, per non perdere dati veri per errore.
 */
function slp_elimina_sessione_diagnostica( $sessione_id ) {
	$sessione = get_post( $sessione_id );
	if ( ! $sessione || 'slp_sessione' !== $sessione->post_type ) {
		return new WP_Error( 'slp_sessione_sconosciuta', 'Sessione non trovata.' );
	}

	$iscritti = get_posts( array(
		'post_type'      => 'slp_iscrizione',
		'post_status'    => 'any',
		'post_parent'    => $sessione_id,
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );
	if ( ! empty( $iscritti ) ) {
		return new WP_Error( 'slp_sessione_con_iscritti', 'Questa sessione ha almeno un iscritto: non viene eliminata automaticamente.' );
	}

	wp_trash_post( $sessione_id );
	return true;
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

add_action( 'wp_ajax_slp_cambia_stato_sessione', 'slp_ajax_cambia_stato_sessione' );
function slp_ajax_cambia_stato_sessione() {
	check_ajax_referer( 'slp_ajax', 'nonce' );
	if ( ! slp_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Non hai i permessi per modificare una sessione.' ) );
	}

	$esito = slp_cambia_stato_sessione(
		(int) ( $_POST['sessione_id'] ?? 0 ),
		sanitize_key( $_POST['stato'] ?? '' )
	);

	if ( is_wp_error( $esito ) ) {
		wp_send_json_error( array( 'message' => $esito->get_error_message() ) );
	}

	wp_send_json_success();
}

add_action( 'wp_ajax_slp_elimina_sessione_diagnostica', 'slp_ajax_elimina_sessione_diagnostica' );
function slp_ajax_elimina_sessione_diagnostica() {
	check_ajax_referer( 'slp_ajax', 'nonce' );
	if ( ! slp_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Non hai i permessi per eliminare una sessione.' ) );
	}

	$esito = slp_elimina_sessione_diagnostica( (int) ( $_POST['sessione_id'] ?? 0 ) );

	if ( is_wp_error( $esito ) ) {
		wp_send_json_error( array( 'message' => $esito->get_error_message() ) );
	}

	wp_send_json_success();
}
