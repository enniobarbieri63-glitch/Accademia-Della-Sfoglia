<?php
/**
 * cruscotto.php — la vista del docente su una classe: chi ha consegnato,
 * con che punteggio, ed esportazione in CSV. In questa prima versione c'è
 * solo la vista sui punteggi (corrisponde alla Vista 1 e alla Vista 3 di
 * docs/02-specifica-classi-virtuali.md § 7); la Vista 2, "dove sbaglia la
 * classe", ha bisogno di più di un esercizio nel catalogo per avere senso
 * ed è rimandata a quando arrivano i valori dei maestri.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Una riga per ogni studente della classe, con l'ultimo tentativo
 * sull'assegnazione indicata (o nessun tentativo).
 */
function sla_dati_cruscotto( $classe_id, $assegnazione_id ) {
	$righe = array();
	foreach ( sla_studenti_della_classe( $classe_id ) as $studente ) {
		$tentativi = sla_tentativi_dello_studente( $assegnazione_id, $studente->ID );
		$ultimo    = end( $tentativi ) ?: null;

		$righe[] = array(
			'studente_id' => $studente->ID,
			'nickname'    => get_post_meta( $studente->ID, 'sla_nickname', true ),
			'occupato'    => '1' === get_post_meta( $studente->ID, 'sla_occupato', true ),
			'consegnato'  => (bool) $ultimo,
			'punteggio'   => $ultimo ? (int) get_post_meta( $ultimo->ID, 'sla_punteggio', true ) : null,
			'data'        => $ultimo ? get_post_meta( $ultimo->ID, 'sla_data', true ) : '',
		);
	}
	return $righe;
}

/**
 * Converte un punteggio 0-100 in un voto scolastico 4-10, con la stessa
 * tabella proposta in docs/02-specifica-classi-virtuali.md § 7 (Vista 3).
 * In questa versione non è ancora modificabile dal docente: è il prossimo
 * passo naturale, quando il pannello avrà un'impostazione salvata per
 * classe invece di questa funzione fissa.
 */
function sla_punteggio_a_voto( $punteggio ) {
	$soglie = array(
		39 => 4, 54 => 5, 64 => 6, 74 => 7, 84 => 8, 94 => 9, 100 => 10,
	);
	foreach ( $soglie as $massimo => $voto ) {
		if ( $punteggio <= $massimo ) {
			return $voto;
		}
	}
	return 10;
}

// -----------------------------------------------------------------------------
// Esportazione CSV
// -----------------------------------------------------------------------------

add_action( 'admin_post_sla_esporta_csv', 'sla_esporta_csv' );
function sla_esporta_csv() {
	$classe_id       = (int) ( $_GET['classe_id'] ?? 0 );
	$assegnazione_id = (int) ( $_GET['assegnazione_id'] ?? 0 );

	if ( ! check_admin_referer( 'sla_esporta_csv_' . $classe_id ) ) {
		wp_die( 'Richiesta non valida.' );
	}
	if ( ! sla_docente_possiede_classe( $classe_id ) ) {
		wp_die( 'Non hai accesso a questa classe.' );
	}

	$classe = get_post( $classe_id );
	$righe  = sla_dati_cruscotto( $classe_id, $assegnazione_id );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $classe->post_title ) . '.csv"' );

	// BOM + punto e virgola: senza, Excel in italiano apre il file tutto in
	// una colonna (vedi docs/02-specifica-classi-virtuali.md § 8, criterio
	// di collaudo C5).
	echo "\xEF\xBB\xBF";

	$flusso = fopen( 'php://output', 'w' );
	fputcsv( $flusso, array( 'Nickname', 'Consegnato', 'Punteggio', 'Voto', 'Data' ), ';' );

	foreach ( $righe as $riga ) {
		fputcsv( $flusso, array(
			$riga['nickname'],
			$riga['consegnato'] ? 'sì' : 'no',
			null === $riga['punteggio'] ? '' : $riga['punteggio'],
			null === $riga['punteggio'] ? '' : sla_punteggio_a_voto( $riga['punteggio'] ),
			$riga['data'],
		), ';' );
	}

	fclose( $flusso );
	exit;
}
