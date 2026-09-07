<?php
/**
 * impostazioni.php — le poche cose che cambiano da un'installazione
 * all'altra e che non stanno bene scritte nel codice: le coordinate per il
 * bonifico e l'indirizzo a cui arrivano gli avvisi delle nuove iscrizioni.
 *
 * L'invio delle email è spento finché non lo si accende apposta. È voluto:
 * un sito di prova che manda email vere a persone vere, con un IBAN non
 * ancora compilato, fa un danno che non si recupera con una scusa.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function slp_impostazioni() {
	$salvate = get_option( SLP_OPTION, array() );
	return wp_parse_args( is_array( $salvate ) ? $salvate : array(), array(
		'intestatario'  => '',
		'iban'          => '',
		'banca'         => '',
		'email_gestore' => get_option( 'admin_email' ),
		'invia_email'   => '',
	) );
}

function slp_impostazione( $chiave ) {
	$impostazioni = slp_impostazioni();
	return $impostazioni[ $chiave ] ?? '';
}

/**
 * True se il plugin può mandare email: l'interruttore è acceso e c'è
 * almeno un IBAN da scrivere nel messaggio. Senza IBAN il messaggio
 * sarebbe una conferma che non dice come pagare, cioè peggio di niente.
 */
function slp_email_attive() {
	$impostazioni = slp_impostazioni();
	return '1' === $impostazioni['invia_email'] && '' !== trim( $impostazioni['iban'] );
}

function slp_salva_impostazioni( $nuove ) {
	$impostazioni = array(
		'intestatario'  => slp_clean( $nuove['intestatario'] ?? '' ),
		'iban'          => strtoupper( preg_replace( '/\s+/', '', slp_clean( $nuove['iban'] ?? '' ) ) ),
		'banca'         => slp_clean( $nuove['banca'] ?? '' ),
		'email_gestore' => sanitize_email( wp_unslash( $nuove['email_gestore'] ?? '' ) ),
		'invia_email'   => ! empty( $nuove['invia_email'] ) ? '1' : '',
	);

	if ( '' !== $impostazioni['email_gestore'] && ! is_email( $impostazioni['email_gestore'] ) ) {
		return new WP_Error( 'slp_email_gestore_non_valida', 'L\'indirizzo per gli avvisi non è valido.' );
	}

	update_option( SLP_OPTION, $impostazioni );
	return true;
}

// -----------------------------------------------------------------------------
// AJAX
// -----------------------------------------------------------------------------

add_action( 'wp_ajax_slp_salva_impostazioni', 'slp_ajax_salva_impostazioni' );
function slp_ajax_salva_impostazioni() {
	check_ajax_referer( 'slp_ajax', 'nonce' );
	if ( ! slp_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Non hai i permessi per cambiare le impostazioni.' ) );
	}

	// $_POST grezzo, non wp_unslash()ato qui: slp_salva_impostazioni() toglie
	// già le barre campo per campo, e farlo due volte mangerebbe le barre
	// scritte davvero da chi compila.
	$esito = slp_salva_impostazioni( $_POST );
	if ( is_wp_error( $esito ) ) {
		wp_send_json_error( array( 'message' => $esito->get_error_message() ) );
	}

	wp_send_json_success();
}
