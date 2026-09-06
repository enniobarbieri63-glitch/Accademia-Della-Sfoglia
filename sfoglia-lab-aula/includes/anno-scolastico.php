<?php
/**
 * anno-scolastico.php — passaggio da un anno scolastico all'altro
 * (criterio di collaudo C9 della specifica): il docente ritrova le classi
 * dell'anno prima, raggruppate, e le duplica per l'anno corrente senza
 * doverle ricreare da zero. La duplicazione porta nome, materia e docente:
 * non porta gli studenti (posti vuoti, nickname da aggiungere di nuovo) né
 * le assegnazioni già fatte, che restano storia dell'anno precedente.
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'SLA_TEST' ) ) {
	exit;
}

/**
 * L'anno scolastico "corrente" calcolato dalla data di oggi: da settembre
 * in poi è l'anno che inizia ora, da gennaio ad agosto è quello iniziato
 * l'autunno precedente. Isolata da WordPress (usa solo date PHP) per
 * essere provata da sola.
 *
 * @param int|null $timestamp Timestamp da usare al posto di "adesso" (per i test).
 */
function sla_anno_da_data( $timestamp = null ) {
	$timestamp = $timestamp ?? time();
	$anno      = (int) gmdate( 'Y', $timestamp );
	$mese      = (int) gmdate( 'n', $timestamp );
	$inizio    = $mese >= 9 ? $anno : $anno - 1;
	return $inizio . '/' . ( $inizio + 1 );
}

if ( defined( 'SLA_TEST' ) ) {
	return; // il resto del file usa funzioni di WordPress.
}

/**
 * L'anno scolastico corrente: quello impostato a mano nelle opzioni del
 * plugin, se c'è, altrimenti quello calcolato dalla data di oggi.
 */
function sla_anno_corrente() {
	$impostazioni = get_option( SLA_OPTION, array() );
	if ( ! empty( $impostazioni['anno_corrente'] ) ) {
		return $impostazioni['anno_corrente'];
	}
	return sla_anno_da_data();
}

/**
 * Imposta a mano l'anno scolastico corrente (facoltativo: usato solo se il
 * calcolo automatico da settembre non basta, per esempio a fine anno
 * mentre si preparano già le classi per quello nuovo).
 */
function sla_imposta_anno_corrente( $anno ) {
	$impostazioni                  = get_option( SLA_OPTION, array() );
	$impostazioni['anno_corrente'] = sla_clean( $anno );
	update_option( SLA_OPTION, $impostazioni );
}

/**
 * Le classi del docente, raggruppate per anno scolastico (le classi senza
 * anno indicato finiscono sotto l'etichetta "Senza anno indicato"). I
 * gruppi sono ordinati dal più recente al più vecchio.
 */
function sla_classi_per_anno( $docente_id ) {
	$gruppi = array();
	foreach ( sla_classi_del_docente( $docente_id ) as $classe ) {
		$anno = get_post_meta( $classe->ID, 'sla_anno', true );
		$anno = '' !== $anno ? $anno : 'Senza anno indicato';
		if ( ! isset( $gruppi[ $anno ] ) ) {
			$gruppi[ $anno ] = array();
		}
		$gruppi[ $anno ][] = $classe;
	}
	krsort( $gruppi );
	return $gruppi;
}

/**
 * Duplica una classe per l'anno scolastico corrente: stesso nome (con
 * l'anno aggiornato, se il nome lo conteneva) e stessa materia, nuovo
 * codice di ingresso, nessuno studente e nessuna assegnazione — quelli si
 * ricreano per la nuova classe. Ritorna l'ID della nuova classe o un
 * WP_Error.
 */
function sla_duplica_classe( $classe_id ) {
	$originale = get_post( $classe_id );
	if ( ! $originale || 'sla_classe' !== $originale->post_type ) {
		return new WP_Error( 'sla_classe_non_trovata', 'Classe non trovata.' );
	}

	$materia = get_post_meta( $classe_id, 'sla_materia', true );
	$anno    = sla_anno_corrente();

	$nuovo_id = sla_crea_classe( (int) $originale->post_author, $originale->post_title, $materia, $anno );
	if ( is_wp_error( $nuovo_id ) ) {
		return $nuovo_id;
	}

	return $nuovo_id;
}

// -----------------------------------------------------------------------------
// AJAX
// -----------------------------------------------------------------------------

add_action( 'wp_ajax_sla_duplica_classe', 'sla_ajax_duplica_classe' );
function sla_ajax_duplica_classe() {
	check_ajax_referer( 'sla_ajax', 'nonce' );
	$classe_id = (int) ( $_POST['classe_id'] ?? 0 );

	if ( ! sla_can_manage() || ! sla_docente_possiede_classe( $classe_id ) ) {
		wp_send_json_error( array( 'message' => 'Non hai accesso a questa classe.' ) );
	}

	$nuovo_id = sla_duplica_classe( $classe_id );
	if ( is_wp_error( $nuovo_id ) ) {
		wp_send_json_error( array( 'message' => $nuovo_id->get_error_message() ) );
	}

	wp_send_json_success( array( 'classe_id' => $nuovo_id ) );
}
