<?php
/**
 * email.php — i messaggi che partono da soli: la conferma all'iscritto con
 * le coordinate per l'acconto, l'avviso al gestore che è arrivata
 * un'iscrizione, e la conferma quando il bonifico risulta ricevuto.
 *
 * Fino a ieri il modulo pubblico prometteva "riceverai le coordinate per il
 * bonifico via email" e non partiva niente: era la promessa dell'interfaccia
 * che il programma non manteneva.
 *
 * Se l'invio è spento (vedi impostazioni.php) queste funzioni non fanno
 * nulla e non danno errore: il resto dell'iscrizione funziona lo stesso, e
 * il pannello avvisa il gestore che deve scrivere lui.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Il blocco con le coordinate del bonifico, uguale in tutti i messaggi.
 */
function slp_blocco_coordinate( $acconto_centesimi, $causale ) {
	$impostazioni = slp_impostazioni();

	$righe = array( 'Coordinate per il bonifico:' );
	if ( '' !== $impostazioni['intestatario'] ) {
		$righe[] = 'Intestatario: ' . $impostazioni['intestatario'];
	}
	$righe[] = 'IBAN: ' . $impostazioni['iban'];
	if ( '' !== $impostazioni['banca'] ) {
		$righe[] = 'Banca: ' . $impostazioni['banca'];
	}
	$righe[] = 'Importo dell\'acconto: ' . slp_euro( $acconto_centesimi );
	$righe[] = 'Causale: ' . $causale;

	return implode( "\n", $righe );
}

/**
 * Alla persona che si è appena iscritta: cosa ha prenotato, quanto versare
 * e dove. È il messaggio che il modulo pubblico promette.
 */
function slp_email_conferma_iscrizione( $iscrizione_id ) {
	if ( ! slp_email_attive() ) {
		return false;
	}

	$iscrizione = get_post( $iscrizione_id );
	$sessione   = $iscrizione ? get_post( $iscrizione->post_parent ) : null;
	if ( ! $sessione ) {
		return false;
	}

	$corso = slp_get_corso( get_post_meta( $sessione->ID, 'slp_corso_codice', true ) );
	if ( ! $corso ) {
		return false;
	}

	$nome    = get_post_meta( $iscrizione_id, 'slp_nome', true );
	$email   = get_post_meta( $iscrizione_id, 'slp_email', true );
	$data    = get_post_meta( $sessione->ID, 'slp_data', true );
	$acconto = slp_calcola_acconto( $corso['quota'] );
	$causale = $corso['codice'] . ' ' . $data . ' — ' . $nome;

	$corpo = "Ciao " . $nome . ",\n\n"
		. "abbiamo ricevuto la tua iscrizione a:\n"
		. $corso['titolo'] . ' (' . $corso['codice'] . ")\n"
		. 'Data: ' . $data . "\n"
		. 'Durata: ' . $corso['durata'] . "\n"
		. 'Quota: ' . slp_euro( $corso['quota'] ) . "\n\n"
		. "Il posto è tenuto per te quando arriva l'acconto, che non è rimborsabile.\n\n"
		. slp_blocco_coordinate( $acconto, $causale ) . "\n\n"
		. "Appena registriamo il bonifico ti arriva la conferma.\n"
		. "Se qualcosa non torna, rispondi a questo messaggio.\n";

	return wp_mail(
		$email,
		'Iscrizione ricevuta — ' . $corso['titolo'],
		$corpo,
		slp_intestazioni_email()
	);
}

/**
 * Al gestore: è arrivata un'iscrizione, con i contatti per richiamare.
 */
function slp_email_avviso_gestore( $iscrizione_id ) {
	if ( ! slp_email_attive() ) {
		return false;
	}

	$destinatario = slp_impostazione( 'email_gestore' );
	if ( ! is_email( $destinatario ) ) {
		return false;
	}

	$iscrizione = get_post( $iscrizione_id );
	$sessione   = $iscrizione ? get_post( $iscrizione->post_parent ) : null;
	if ( ! $sessione ) {
		return false;
	}

	$corso  = slp_get_corso( get_post_meta( $sessione->ID, 'slp_corso_codice', true ) );
	$liberi = slp_posti_liberi( $sessione->ID );

	$corpo = "Nuova iscrizione.\n\n"
		. 'Corso: ' . ( $corso ? $corso['codice'] . ' — ' . $corso['titolo'] : '(sconosciuto)' ) . "\n"
		. 'Data: ' . get_post_meta( $sessione->ID, 'slp_data', true ) . "\n\n"
		. 'Nome: ' . get_post_meta( $iscrizione_id, 'slp_nome', true ) . "\n"
		. 'Email: ' . get_post_meta( $iscrizione_id, 'slp_email', true ) . "\n"
		. 'Telefono: ' . get_post_meta( $iscrizione_id, 'slp_telefono', true ) . "\n\n"
		. 'Posti ancora liberi: ' . ( null === $liberi ? 'nessun limite' : $liberi ) . "\n";

	return wp_mail( $destinatario, 'Nuova iscrizione ai corsi', $corpo, slp_intestazioni_email() );
}

/**
 * All'iscritto, quando il versamento raggiunge l'acconto: il posto è suo.
 */
function slp_email_conferma_pagamento( $iscrizione_id, $totale_versato, $quota ) {
	if ( ! slp_email_attive() ) {
		return false;
	}

	$iscrizione = get_post( $iscrizione_id );
	$sessione   = $iscrizione ? get_post( $iscrizione->post_parent ) : null;
	if ( ! $sessione ) {
		return false;
	}

	$corso = slp_get_corso( get_post_meta( $sessione->ID, 'slp_corso_codice', true ) );
	$email = get_post_meta( $iscrizione_id, 'slp_email', true );
	if ( ! $corso || ! is_email( $email ) ) {
		return false;
	}

	$residuo = max( 0, $quota - $totale_versato );

	$corpo = 'Ciao ' . get_post_meta( $iscrizione_id, 'slp_nome', true ) . ",\n\n"
		. "abbiamo registrato il tuo versamento: il posto è confermato.\n\n"
		. 'Corso: ' . $corso['titolo'] . ' (' . $corso['codice'] . ")\n"
		. 'Data: ' . get_post_meta( $sessione->ID, 'slp_data', true ) . "\n"
		. 'Versato finora: ' . slp_euro( $totale_versato ) . ' su ' . slp_euro( $quota ) . "\n";

	if ( $residuo > 0 ) {
		$corpo .= 'Da saldare: ' . slp_euro( $residuo ) . " (il giorno del corso o con un secondo bonifico).\n";
	} else {
		$corpo .= "Quota saldata per intero.\n";
	}

	$corpo .= "\nCi vediamo in laboratorio.\n";

	return wp_mail( $email, 'Iscrizione confermata — ' . $corso['titolo'], $corpo, slp_intestazioni_email() );
}

/**
 * Le email partono dall'indirizzo del sito, ma le risposte devono arrivare
 * a chi gestisce i corsi, non finire in una casella che nessuno apre.
 */
function slp_intestazioni_email() {
	$gestore = slp_impostazione( 'email_gestore' );
	return is_email( $gestore ) ? array( 'Reply-To: ' . $gestore ) : array();
}
