<?php
/**
 * svolgimento.php — lo studente svolge l'esercizio assegnato: legge
 * l'enunciato, inserisce il valore, riceve il punteggio e il messaggio del
 * maestro corrispondente alla fascia (verde/giallo/rosso).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * L'assegnazione più recente e ancora aperta di una classe, o null se non
 * c'è nulla da svolgere. "Aperta" = scadenza vuota, oppure scadenza non
 * ancora passata.
 */
function sla_assegnazione_corrente( $classe_id ) {
	$assegnazioni = sla_assegnazioni_della_classe( $classe_id );
	foreach ( $assegnazioni as $assegnazione ) {
		$scadenza = get_post_meta( $assegnazione->ID, 'sla_scadenza', true );
		if ( '' === $scadenza || strtotime( $scadenza ) >= time() ) {
			return $assegnazione;
		}
	}
	return null;
}

/**
 * I tentativi già fatti da uno studente su un'assegnazione, dal più
 * vecchio al più recente.
 */
function sla_tentativi_dello_studente( $assegnazione_id, $studente_id ) {
	return get_posts( array(
		'post_type'      => 'sla_tentativo',
		'post_status'    => 'publish',
		'post_parent'    => (int) $assegnazione_id,
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'ASC',
		'meta_query'     => array(
			array( 'key' => 'sla_studente_id', 'value' => (int) $studente_id ),
		),
	) );
}

/**
 * True se lo studente può ancora tentare questa assegnazione (non ha
 * esaurito i tentativi consentiti; 0 = illimitati).
 */
function sla_puo_tentare( $assegnazione_id, $studente_id ) {
	$max = (int) get_post_meta( $assegnazione_id, 'sla_tentativi', true );
	if ( 0 === $max ) {
		return true;
	}
	return count( sla_tentativi_dello_studente( $assegnazione_id, $studente_id ) ) < $max;
}

/**
 * Registra un tentativo su un esercizio numerico, calcola il punteggio e lo
 * ritorna insieme al messaggio del maestro per la fascia ottenuta. Non
 * verifica i permessi: chi chiama (l'handler AJAX qui sotto) deve aver già
 * controllato la sessione dello studente.
 */
function sla_registra_tentativo_esercizio( $assegnazione_id, $studente_id, $valore ) {
	$slug      = get_post_meta( $assegnazione_id, 'sla_esercizio', true );
	$esercizio = sla_get_esercizio( $slug );
	if ( ! $esercizio ) {
		return new WP_Error( 'sla_esercizio_sconosciuto', 'Esercizio non riconosciuto.' );
	}

	$valore    = (float) $valore;
	$punteggio = sla_calcola_punteggio( $valore, $esercizio['corretto'], $esercizio['verde'], $esercizio['giallo'] );
	$fascia    = sla_fascia_punteggio( $punteggio );

	$tentativo_id = wp_insert_post( array(
		'post_type'   => 'sla_tentativo',
		'post_title'  => sprintf( 'Tentativo studente #%d', $studente_id ),
		'post_parent' => (int) $assegnazione_id,
		'post_status' => 'publish',
		'meta_input'  => array(
			'sla_studente_id' => (int) $studente_id,
			'sla_valore'      => $valore,
			'sla_punteggio'   => $punteggio,
			'sla_stato'       => 'consegnato',
			'sla_data'        => current_time( 'mysql' ),
		),
	), true );

	if ( is_wp_error( $tentativo_id ) ) {
		return $tentativo_id;
	}

	return array(
		'tentativo_id' => $tentativo_id,
		'punteggio'    => $punteggio,
		'fascia'       => $fascia,
		'messaggio'    => $esercizio['messaggi'][ $fascia ],
	);
}

/**
 * Registra un tentativo su un quiz: valuta le risposte con
 * sla_valuta_quiz() e salva sia il punteggio complessivo sia il dettaglio
 * domanda per domanda (serve alla Vista 2 del cruscotto, per capire su
 * quale argomento la classe sbaglia di più).
 */
function sla_registra_tentativo_quiz( $assegnazione_id, $studente_id, $risposte ) {
	$slug = get_post_meta( $assegnazione_id, 'sla_esercizio', true );
	$quiz = sla_get_quiz( $slug );
	if ( ! $quiz ) {
		return new WP_Error( 'sla_quiz_sconosciuto', 'Quiz non riconosciuto.' );
	}

	$valutazione = sla_valuta_quiz( is_array( $risposte ) ? $risposte : array(), $quiz['domande'] );
	$fascia      = sla_fascia_punteggio( $valutazione['punteggio'] );

	$tentativo_id = wp_insert_post( array(
		'post_type'   => 'sla_tentativo',
		'post_title'  => sprintf( 'Tentativo studente #%d', $studente_id ),
		'post_parent' => (int) $assegnazione_id,
		'post_status' => 'publish',
		'meta_input'  => array(
			'sla_studente_id' => (int) $studente_id,
			'sla_punteggio'   => $valutazione['punteggio'],
			'sla_dettaglio'   => wp_json_encode( $valutazione['dettaglio'] ),
			'sla_stato'       => 'consegnato',
			'sla_data'        => current_time( 'mysql' ),
		),
	), true );

	if ( is_wp_error( $tentativo_id ) ) {
		return $tentativo_id;
	}

	return array(
		'tentativo_id' => $tentativo_id,
		'punteggio'    => $valutazione['punteggio'],
		'fascia'       => $fascia,
		'dettaglio'    => $valutazione['dettaglio'],
		'domande'      => $quiz['domande'],
	);
}

// -----------------------------------------------------------------------------
// AJAX
// -----------------------------------------------------------------------------

add_action( 'wp_ajax_nopriv_sla_consegna', 'sla_ajax_consegna' );
add_action( 'wp_ajax_sla_consegna', 'sla_ajax_consegna' );
function sla_ajax_consegna() {
	check_ajax_referer( 'sla_ajax_pubblico', 'nonce' );

	$sessione = sla_studente_da_sessione();
	if ( ! $sessione ) {
		wp_send_json_error( array( 'message' => 'Sessione scaduta: torna alla pagina di ingresso e inserisci di nuovo codice e nickname.' ) );
	}

	$assegnazione_id = (int) ( $_POST['assegnazione_id'] ?? 0 );
	$assegnazione    = get_post( $assegnazione_id );
	if ( ! $assegnazione || 'sla_assegnazione' !== $assegnazione->post_type
		|| (int) $assegnazione->post_parent !== $sessione['classe_id'] ) {
		wp_send_json_error( array( 'message' => 'Assegnazione non riconosciuta.' ) );
	}

	if ( ! sla_puo_tentare( $assegnazione_id, $sessione['studente_id'] ) ) {
		wp_send_json_error( array( 'message' => 'Hai già usato tutti i tentativi disponibili per questo esercizio.' ) );
	}

	$tipo = sla_tipo_assegnazione( $assegnazione_id );

	if ( 'quiz' === $tipo ) {
		$risposte_grezze = wp_unslash( $_POST['risposte'] ?? array() );
		$risposte        = array();
		if ( is_array( $risposte_grezze ) ) {
			foreach ( $risposte_grezze as $id_domanda => $valore ) {
				$id_domanda = sanitize_key( $id_domanda );
				$risposte[ $id_domanda ] = is_array( $valore )
					? array_map( 'sanitize_text_field', $valore )
					: sanitize_text_field( $valore );
			}
		}
		$risultato = sla_registra_tentativo_quiz( $assegnazione_id, $sessione['studente_id'], $risposte );
	} else {
		$risultato = sla_registra_tentativo_esercizio( $assegnazione_id, $sessione['studente_id'], $_POST['valore'] ?? 0 );
	}

	if ( is_wp_error( $risultato ) ) {
		wp_send_json_error( array( 'message' => $risultato->get_error_message() ) );
	}

	wp_send_json_success( $risultato );
}
