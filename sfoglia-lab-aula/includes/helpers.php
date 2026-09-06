<?php
/**
 * helpers.php — utilità condivise da tutto il plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pulisce un testo arrivato da un form: rimuove i tag e gli spazi ai bordi.
 * Non tocca l'HTML legittimo perché qui non ne serve mai: ogni campo del
 * plugin è testo semplice (nomi di classe, nickname, materie).
 */
function sla_clean( $valore ) {
	return sanitize_text_field( wp_unslash( (string) $valore ) );
}

/**
 * Genera un codice di ingresso a 6 caratteri, maiuscolo, senza caratteri
 * ambigui (niente 0/O, 1/I/L) per evitare errori di trascrizione quando il
 * docente lo detta o lo scrive alla lavagna.
 */
function sla_genera_codice() {
	$alfabeto = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
	$max      = strlen( $alfabeto ) - 1;
	do {
		$codice = '';
		for ( $i = 0; $i < 6; $i++ ) {
			$codice .= $alfabeto[ random_int( 0, $max ) ];
		}
		$esiste = sla_trova_classe_da_codice( $codice );
	} while ( $esiste );
	return $codice;
}

/**
 * Trova la classe (post sla_classe) dal suo codice di ingresso, o null se
 * il codice non corrisponde a nessuna classe.
 */
function sla_trova_classe_da_codice( $codice ) {
	$codice = strtoupper( sla_clean( $codice ) );
	if ( '' === $codice ) {
		return null;
	}
	$trovati = get_posts( array(
		'post_type'      => 'sla_classe',
		'post_status'    => 'publish',
		'meta_key'       => 'sla_codice',
		'meta_value'     => $codice,
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	return $trovati ? (int) $trovati[0] : null;
}

/**
 * True se l'utente collegato può gestire le classi (è un "docente" nel
 * senso di questo plugin). Vedi la nota in sfoglia-lab-aula.php su
 * sla_activate(): oggi è una capacità WordPress, domani sarà legata
 * all'accesso SSO dell'istituto.
 */
function sla_can_manage() {
	return current_user_can( 'sla_gestisci_classi' ) || current_user_can( 'manage_options' );
}

/**
 * Formatta un numero decimale con la virgola italiana, per la stampa a
 * video (non per i calcoli, che restano sempre in punto decimale).
 */
function sla_num_it( $valore, $decimali = 1 ) {
	return number_format( (float) $valore, $decimali, ',', '.' );
}
