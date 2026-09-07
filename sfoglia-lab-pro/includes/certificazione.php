<?php
/**
 * certificazione.php — il percorso a livelli (docs/01 §1.6): Praticante,
 * Addetto, Specialista, Maestro. Numero progressivo dell'attestato,
 * scadenza a tre anni, elenco pubblico consultabile — quello che, secondo
 * il documento di progetto, i ristoranti dovrebbero un giorno cercare per
 * trovare personale già formato.
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'SLP_TEST' ) ) {
	exit;
}

/** I quattro livelli, in ordine. Il livello 0 non appare nell'elenco pubblico. */
function slp_livelli() {
	return array(
		0 => 'Praticante',
		1 => 'Addetto',
		2 => 'Specialista',
		3 => 'Maestro',
	);
}

/**
 * Il numero progressivo di un attestato, nel formato ANNO-SEQUENZA a
 * quattro cifre (es. "2026-0007"). Pura funzione di formattazione, senza
 * bisogno del database per essere provata.
 */
function slp_formatta_numero_certificato( $anno, $sequenza ) {
	return sprintf( '%d-%04d', (int) $anno, max( 1, (int) $sequenza ) );
}

/**
 * True se un attestato rilasciato in una certa data è scaduto rispetto a
 * "oggi" (validità di tre anni, doc 01 § 1.6). I parametri sono timestamp,
 * non stringhe, per restare indipendente da WordPress e testabile da sola.
 */
function slp_certificato_scaduto( $data_rilascio_ts, $oggi_ts ) {
	$scadenza_ts = strtotime( '+3 years', $data_rilascio_ts );
	return $oggi_ts > $scadenza_ts;
}

if ( defined( 'SLP_TEST' ) ) {
	return; // il resto del file usa funzioni di WordPress.
}

/**
 * Crea un nuovo certificato. La sequenza è calcolata contando quanti
 * certificati esistono già per l'anno indicato: non elegantissimo con
 * grandi volumi, ma per un numero di attestati nell'ordine delle decine
 * o centinaia all'anno (l'ordine di grandezza reale di questo progetto)
 * è affidabile e non ha bisogno di un contatore separato da mantenere
 * sincronizzato.
 */
/**
 * Il valore predefinito di $pubblico è false apposta: pubblicare nome e
 * cognome di una persona su una pagina aperta a chiunque è una scelta che
 * deve fare lei, non il programma per distrazione di chi lo usa. Chi
 * rilascia l'attestato passa true solo dopo che la persona ha detto di sì.
 */
function slp_crea_certificato( $persona_nome, $livello, $anno, $pubblico = false ) {
	$livelli = slp_livelli();
	if ( ! isset( $livelli[ $livello ] ) ) {
		return new WP_Error( 'slp_livello_sconosciuto', 'Livello non riconosciuto.' );
	}

	$esistenti = get_posts( array(
		'post_type'      => 'slp_certificato',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array( 'key' => 'slp_anno', 'value' => (int) $anno ),
		),
	) );
	$sequenza = count( $esistenti ) + 1;
	$numero   = slp_formatta_numero_certificato( $anno, $sequenza );

	$certificato_id = wp_insert_post( array(
		'post_type'   => 'slp_certificato',
		'post_title'  => $numero . ' — ' . slp_clean( $persona_nome ),
		'post_status' => 'publish',
		'meta_input'  => array(
			'slp_persona_nome'  => slp_clean( $persona_nome ),
			'slp_livello'       => (int) $livello,
			'slp_anno'          => (int) $anno,
			'slp_numero'        => $numero,
			'slp_data_rilascio' => current_time( 'mysql' ),
			'slp_pubblico'      => $pubblico ? '1' : '',
		),
	), true );

	return $certificato_id;
}

/**
 * L'elenco pubblico: solo i certificati marcati pubblici, di livello 1 o
 * superiore (il livello 0, Praticante, non ci va — vedi doc 01 § 1.6: "non
 * appare nell'elenco pubblico"), non ancora scaduti.
 */
function slp_elenco_pubblico_certificati() {
	$tutti = get_posts( array(
		'post_type'      => 'slp_certificato',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_query'     => array(
			array( 'key' => 'slp_pubblico', 'value' => '1' ),
			array( 'key' => 'slp_livello', 'value' => 0, 'compare' => '>' ),
		),
	) );

	$oggi   = time();
	$validi = array();
	foreach ( $tutti as $certificato ) {
		$rilascio_ts = strtotime( (string) get_post_meta( $certificato->ID, 'slp_data_rilascio', true ) );
		if ( ! slp_certificato_scaduto( $rilascio_ts, $oggi ) ) {
			$validi[] = $certificato;
		}
	}
	return $validi;
}
