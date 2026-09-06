<?php
/**
 * classi.php — creazione e gestione delle classi da parte del docente:
 * creare una classe, incollare l'elenco dei nickname, assegnare un
 * esercizio. Il rendering del pannello e le chiamate AJAX che lo
 * alimentano stanno tutte qui.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Operazioni sui dati
// -----------------------------------------------------------------------------

/**
 * Crea una nuova classe per il docente indicato. Ritorna l'ID del post o
 * un WP_Error se il nome è vuoto.
 */
function sla_crea_classe( $docente_id, $nome, $materia, $anno ) {
	$nome = sla_clean( $nome );
	if ( '' === $nome ) {
		return new WP_Error( 'sla_nome_vuoto', 'Il nome della classe non può essere vuoto.' );
	}

	$classe_id = wp_insert_post( array(
		'post_type'   => 'sla_classe',
		'post_title'  => $nome,
		'post_author' => (int) $docente_id,
		'post_status' => 'publish',
	), true );

	if ( is_wp_error( $classe_id ) ) {
		return $classe_id;
	}

	update_post_meta( $classe_id, 'sla_codice', sla_genera_codice() );
	update_post_meta( $classe_id, 'sla_materia', sla_clean( $materia ) );
	update_post_meta( $classe_id, 'sla_anno', sla_clean( $anno ) );

	return $classe_id;
}

/**
 * Le classi create dal docente indicato, più recenti prima.
 */
function sla_classi_del_docente( $docente_id ) {
	return get_posts( array(
		'post_type'      => 'sla_classe',
		'post_status'    => 'publish',
		'author'         => (int) $docente_id,
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
}

/**
 * Aggiunge posti-studente a una classe da un elenco di nickname (uno per
 * riga). Righe vuote ignorate, nickname duplicati nello stesso invio
 * ignorati (il docente probabilmente ha incollato due volte per errore).
 * Ritorna quanti posti sono stati creati davvero.
 */
function sla_aggiungi_studenti( $classe_id, $elenco_testo ) {
	$righe  = preg_split( '/\r\n|\r|\n/', (string) $elenco_testo );
	$visti  = array();
	$creati = 0;

	foreach ( $righe as $riga ) {
		$nickname = sla_clean( $riga );
		if ( '' === $nickname || isset( $visti[ $nickname ] ) ) {
			continue;
		}
		$visti[ $nickname ] = true;

		wp_insert_post( array(
			'post_type'   => 'sla_studente',
			'post_title'  => $nickname,
			'post_parent' => (int) $classe_id,
			'post_status' => 'publish',
			'meta_input'  => array(
				'sla_nickname' => $nickname,
				'sla_occupato' => '',
			),
		) );
		$creati++;
	}

	return $creati;
}

/**
 * Gli studenti (posti) di una classe, in ordine di creazione.
 */
function sla_studenti_della_classe( $classe_id ) {
	return get_posts( array(
		'post_type'      => 'sla_studente',
		'post_status'    => 'publish',
		'post_parent'    => (int) $classe_id,
		'posts_per_page' => -1,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	) );
}

/**
 * Rigenera il codice di ingresso di una classe. I vecchi accessi (i cookie
 * di sessione già distribuiti) restano validi finché non scadono da soli:
 * il token di sessione non dipende dal codice, solo l'ingresso di un nuovo
 * studente lo richiede.
 */
function sla_rigenera_codice( $classe_id ) {
	$codice = sla_genera_codice();
	update_post_meta( $classe_id, 'sla_codice', $codice );
	return $codice;
}

/**
 * Assegna un esercizio del catalogo a una classe.
 */
function sla_assegna_esercizio( $classe_id, $slug, $scadenza, $tentativi, $vale_voto ) {
	if ( ! sla_get_esercizio( $slug ) ) {
		return new WP_Error( 'sla_esercizio_sconosciuto', 'Esercizio non riconosciuto.' );
	}

	$assegnazione_id = wp_insert_post( array(
		'post_type'   => 'sla_assegnazione',
		'post_title'  => $slug,
		'post_parent' => (int) $classe_id,
		'post_status' => 'publish',
	), true );

	if ( is_wp_error( $assegnazione_id ) ) {
		return $assegnazione_id;
	}

	update_post_meta( $assegnazione_id, 'sla_esercizio', sanitize_key( $slug ) );
	update_post_meta( $assegnazione_id, 'sla_apertura', current_time( 'mysql' ) );
	update_post_meta( $assegnazione_id, 'sla_scadenza', sla_clean( $scadenza ) );
	update_post_meta( $assegnazione_id, 'sla_tentativi', max( 0, (int) $tentativi ) );
	update_post_meta( $assegnazione_id, 'sla_vale_voto', $vale_voto ? '1' : '' );

	return $assegnazione_id;
}

/**
 * Le assegnazioni di una classe, più recente prima.
 */
function sla_assegnazioni_della_classe( $classe_id ) {
	return get_posts( array(
		'post_type'      => 'sla_assegnazione',
		'post_status'    => 'publish',
		'post_parent'    => (int) $classe_id,
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
}

// -----------------------------------------------------------------------------
// AJAX
// -----------------------------------------------------------------------------

add_action( 'wp_ajax_sla_crea_classe', 'sla_ajax_crea_classe' );
function sla_ajax_crea_classe() {
	check_ajax_referer( 'sla_ajax', 'nonce' );
	if ( ! sla_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Non hai i permessi per creare una classe.' ) );
	}

	$classe_id = sla_crea_classe(
		get_current_user_id(),
		$_POST['nome'] ?? '',
		$_POST['materia'] ?? '',
		$_POST['anno'] ?? ''
	);

	if ( is_wp_error( $classe_id ) ) {
		wp_send_json_error( array( 'message' => $classe_id->get_error_message() ) );
	}

	wp_send_json_success( array(
		'classe_id' => $classe_id,
		'codice'    => get_post_meta( $classe_id, 'sla_codice', true ),
	) );
}

add_action( 'wp_ajax_sla_aggiungi_studenti', 'sla_ajax_aggiungi_studenti' );
function sla_ajax_aggiungi_studenti() {
	check_ajax_referer( 'sla_ajax', 'nonce' );
	$classe_id = (int) ( $_POST['classe_id'] ?? 0 );

	if ( ! sla_can_manage() || ! sla_docente_possiede_classe( $classe_id ) ) {
		wp_send_json_error( array( 'message' => 'Non hai accesso a questa classe.' ) );
	}

	$creati = sla_aggiungi_studenti( $classe_id, $_POST['elenco'] ?? '' );
	wp_send_json_success( array( 'creati' => $creati ) );
}

add_action( 'wp_ajax_sla_rigenera_codice', 'sla_ajax_rigenera_codice' );
function sla_ajax_rigenera_codice() {
	check_ajax_referer( 'sla_ajax', 'nonce' );
	$classe_id = (int) ( $_POST['classe_id'] ?? 0 );

	if ( ! sla_can_manage() || ! sla_docente_possiede_classe( $classe_id ) ) {
		wp_send_json_error( array( 'message' => 'Non hai accesso a questa classe.' ) );
	}

	wp_send_json_success( array( 'codice' => sla_rigenera_codice( $classe_id ) ) );
}

add_action( 'wp_ajax_sla_assegna_esercizio', 'sla_ajax_assegna_esercizio' );
function sla_ajax_assegna_esercizio() {
	check_ajax_referer( 'sla_ajax', 'nonce' );
	$classe_id = (int) ( $_POST['classe_id'] ?? 0 );

	if ( ! sla_can_manage() || ! sla_docente_possiede_classe( $classe_id ) ) {
		wp_send_json_error( array( 'message' => 'Non hai accesso a questa classe.' ) );
	}

	$assegnazione_id = sla_assegna_esercizio(
		$classe_id,
		$_POST['esercizio'] ?? '',
		$_POST['scadenza'] ?? '',
		$_POST['tentativi'] ?? 3,
		! empty( $_POST['vale_voto'] )
	);

	if ( is_wp_error( $assegnazione_id ) ) {
		wp_send_json_error( array( 'message' => $assegnazione_id->get_error_message() ) );
	}

	wp_send_json_success( array( 'assegnazione_id' => $assegnazione_id ) );
}

/**
 * True se la classe esiste ed è del docente collegato (o se chi guarda è
 * amministratore: gli serve per assistenza/collaudo).
 */
function sla_docente_possiede_classe( $classe_id ) {
	$classe = get_post( $classe_id );
	if ( ! $classe || 'sla_classe' !== $classe->post_type ) {
		return false;
	}
	return current_user_can( 'manage_options' ) || (int) $classe->post_author === get_current_user_id();
}
