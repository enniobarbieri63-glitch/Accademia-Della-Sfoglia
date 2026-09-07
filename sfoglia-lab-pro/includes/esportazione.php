<?php
/**
 * esportazione.php — l'elenco dei partecipanti di una sessione in CSV.
 * Serve il giorno del corso: si stampa, si spunta chi arriva, e si sa
 * subito chi deve ancora saldare.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_post_slp_esporta_iscritti', 'slp_esporta_iscritti' );
function slp_esporta_iscritti() {
	$sessione_id = (int) ( $_GET['sessione_id'] ?? 0 );

	if ( ! check_admin_referer( 'slp_esporta_iscritti_' . $sessione_id ) ) {
		wp_die( 'Richiesta non valida.' );
	}
	if ( ! slp_can_manage() ) {
		wp_die( 'Non hai i permessi per esportare questo elenco.' );
	}

	$sessione = get_post( $sessione_id );
	if ( ! $sessione || 'slp_sessione' !== $sessione->post_type ) {
		wp_die( 'Sessione non trovata.' );
	}

	$corso = slp_get_corso( get_post_meta( $sessione_id, 'slp_corso_codice', true ) );
	$data  = get_post_meta( $sessione_id, 'slp_data', true );
	$quota = $corso ? (int) $corso['quota'] : 0;

	nocache_headers();
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( ( $corso ? $corso['codice'] : 'sessione' ) . '-' . $data ) . '.csv"' );

	// BOM + punto e virgola: senza, Excel in italiano apre il file tutto in
	// una colonna (stessa scelta già fatta per i cruscotti di Sfoglia Lab —
	// Aula).
	echo "\xEF\xBB\xBF";

	$flusso = fopen( 'php://output', 'w' );
	fputcsv( $flusso, array( 'Nome', 'Email', 'Telefono', 'Stato', 'Versato', 'Quota', 'Da saldare' ), ';' );

	foreach ( slp_iscrizioni_della_sessione( $sessione_id ) as $iscrizione ) {
		$pagamenti = get_post_meta( $iscrizione->ID, 'slp_pagamenti', true );
		$versato   = slp_totale_pagato( is_array( $pagamenti ) ? $pagamenti : array() );

		fputcsv( $flusso, array(
			get_post_meta( $iscrizione->ID, 'slp_nome', true ),
			get_post_meta( $iscrizione->ID, 'slp_email', true ),
			get_post_meta( $iscrizione->ID, 'slp_telefono', true ),
			str_replace( '_', ' ', (string) get_post_meta( $iscrizione->ID, 'slp_stato', true ) ),
			number_format( $versato / 100, 2, ',', '' ),
			number_format( $quota / 100, 2, ',', '' ),
			number_format( max( 0, $quota - $versato ) / 100, 2, ',', '' ),
		), ';' );
	}

	fclose( $flusso );
	exit;
}
