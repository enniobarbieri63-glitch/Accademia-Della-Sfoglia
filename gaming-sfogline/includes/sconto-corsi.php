<?php
/**
 * sconto-corsi.php — Sconti sui corsi di Rina Poletti, guadagnati vincendo
 * badge/traguardi (estende "Premi per Traguardo", premi-traguardi.php, con
 * un terzo tipo di premio: "sconto", una percentuale invece di un video o
 * un messaggio).
 *
 * Come funziona:
 *  - Ogni sfoglina ha un "livello corso" corrente: base → avanzato →
 *    professionale (meta utente gs_sconto_livello, parte da 'base').
 *  - Ogni volta che sblocca un badge collegato a un premio di tipo
 *    "sconto" (impostato in Premi per Traguardo), la percentuale di quel
 *    premio si somma all'accumulo per il SUO livello corrente (meta
 *    gs_sconto_pct, tetto 100%).
 *  - Quando si iscrive davvero a un corso di quel livello e lo fa, il
 *    gestore lo conferma dal pannello Iscrizioni (Calendario Corsi): lo
 *    sconto si azzera e il livello corrente avanza a quello successivo.
 *    Oltre "professionale" non c'è livello successivo: l'accumulo
 *    continua sul professionale (tetto sempre 100%).
 *  - Quanto accumulato ma non speso entro il 24 dicembre dell'anno solare
 *    si perde (reset automatico, gs_daily_cron).
 *
 * Nessun pagamento reale gestito qui: solo la percentuale, da applicare a
 * mano nel calcolo dell'importo (come tutti i pagamenti dei corsi).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Livelli corso e stato per sfoglina
// -----------------------------------------------------------------------------

function gs_sconto_livelli() {
	return array(
		'base'          => 'Base',
		'avanzato'      => 'Avanzato',
		'professionale' => 'Professionale',
	);
}

function gs_sconto_livello_label( $liv ) {
	$l = gs_sconto_livelli();
	return isset( $l[ $liv ] ) ? $l[ $liv ] : $l['base'];
}

/** Livello corso su cui la sfoglina sta accumulando sconto in questo momento. */
function gs_sconto_livello_corrente( $uid ) {
	$liv     = get_user_meta( (int) $uid, 'gs_sconto_livello', true );
	$livelli = gs_sconto_livelli();
	return isset( $livelli[ $liv ] ) ? $liv : 'base';
}

/** Percentuale accumulata (0-100) per il livello corrente. */
function gs_sconto_percentuale( $uid ) {
	$pct = (float) get_user_meta( (int) $uid, 'gs_sconto_pct', true );
	// (float) esplicito: max()/min() con un intero (0, 100) e un float
	// possono restituire un intero quando il valore combacia esattamente,
	// rompendo i confronti stretti (0.0 === 0 è falso in PHP).
	return (float) max( 0, min( 100, $pct ) );
}

/** Livello successivo nella scala base→avanzato→professionale, o null se già al top. */
function gs_sconto_prossimo_livello( $liv ) {
	$ordine = array_keys( gs_sconto_livelli() );
	$i      = array_search( $liv, $ordine, true );
	if ( false === $i || $i >= count( $ordine ) - 1 ) {
		return null;
	}
	return $ordine[ $i + 1 ];
}

function gs_sconto_log_aggiungi( $uid, $voce ) {
	$log         = get_user_meta( (int) $uid, 'gs_sconto_log', true );
	$log         = is_array( $log ) ? $log : array();
	$voce['data'] = current_time( 'mysql' );
	$log[]       = $voce;
	if ( count( $log ) > 100 ) {
		$log = array_slice( $log, -100 );
	}
	update_user_meta( (int) $uid, 'gs_sconto_log', $log );
}

function gs_sconto_log_get( $uid ) {
	$log = get_user_meta( (int) $uid, 'gs_sconto_log', true );
	return is_array( $log ) ? array_reverse( $log ) : array();
}

/** Aggiunge una percentuale all'accumulo del livello corrente (chiamata da premi-traguardi.php). */
function gs_sconto_aggiungi( $uid, $pct, $motivo = '' ) {
	$uid = (int) $uid;
	$pct = (float) $pct;
	if ( ! $uid || $pct <= 0 ) {
		return false;
	}
	$nuovo = (float) min( 100, gs_sconto_percentuale( $uid ) + $pct );
	update_user_meta( $uid, 'gs_sconto_pct', $nuovo );
	gs_sconto_log_aggiungi( $uid, array(
		'tipo'        => 'guadagno',
		'percentuale' => $pct,
		'livello'     => gs_sconto_livello_corrente( $uid ),
		'motivo'      => $motivo,
	) );
	return true;
}

/**
 * Il gestore conferma che la sfoglina ha davvero fatto il corso: consuma
 * lo sconto accumulato e la fa avanzare al livello corso successivo.
 */
function gs_sconto_applica( $uid, $pren_id = 0 ) {
	$uid = (int) $uid;
	if ( ! $uid ) {
		return false;
	}
	$livello_usato = gs_sconto_livello_corrente( $uid );
	$pct_usata     = gs_sconto_percentuale( $uid );
	update_user_meta( $uid, 'gs_sconto_pct', 0 );
	$prossimo = gs_sconto_prossimo_livello( $livello_usato );
	if ( $prossimo ) {
		update_user_meta( $uid, 'gs_sconto_livello', $prossimo );
	}
	gs_sconto_log_aggiungi( $uid, array(
		'tipo'         => 'consumo',
		'percentuale'  => $pct_usata,
		'livello'      => $livello_usato,
		'prenotazione' => (int) $pren_id,
	) );
	return true;
}

// -----------------------------------------------------------------------------
// Reset annuale: sconto non speso entro il 24 dicembre si azzera
// -----------------------------------------------------------------------------
add_action( 'gs_daily_cron', 'gs_sconto_reset_annuale' );
function gs_sconto_reset_annuale() {
	$oggi = current_time( 'timestamp' );
	if ( date( 'm-d', $oggi ) < '12-24' ) {
		return;
	}
	$anno = date( 'Y', $oggi );
	if ( get_option( 'gs_sconto_reset_anno' ) === $anno ) {
		return; // già fatto quest'anno
	}
	foreach ( get_users( array( 'fields' => 'ID' ) ) as $uid ) {
		$uid = (int) $uid;
		$pct = gs_sconto_percentuale( $uid );
		if ( $pct > 0 ) {
			update_user_meta( $uid, 'gs_sconto_pct', 0 );
			gs_sconto_log_aggiungi( $uid, array(
				'tipo'        => 'scaduto',
				'percentuale' => $pct,
				'livello'     => gs_sconto_livello_corrente( $uid ),
			) );
		}
	}
	update_option( 'gs_sconto_reset_anno', $anno );
}

// -----------------------------------------------------------------------------
// AJAX: il gestore segna lo sconto come applicato (corso fatto davvero)
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_sconto_applica', 'gs_ajax_sconto_applica' );
function gs_ajax_sconto_applica() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Permesso negato.' ) );
	}
	$pren = isset( $_POST['pren'] ) ? (int) $_POST['pren'] : 0;
	if ( 'gs_prenotazione' !== get_post_type( $pren ) ) {
		wp_send_json_error( array( 'message' => 'Prenotazione non valida.' ) );
	}
	$uid = (int) get_post_meta( $pren, 'gs_cliente', true );
	if ( ! $uid ) {
		wp_send_json_error( array( 'message' => 'Sfoglina non trovata.' ) );
	}
	gs_sconto_applica( $uid, $pren );
	wp_send_json_success( array( 'message' => 'Sconto applicato: livello aggiornato.' ) );
}

// -----------------------------------------------------------------------------
// Box per la dashboard della sfoglina: sconto accumulato + storico premi
// -----------------------------------------------------------------------------
function gs_sconto_box_html( $uid ) {
	$uid    = (int) $uid;
	$liv    = gs_sconto_livello_corrente( $uid );
	$pct    = gs_sconto_percentuale( $uid );
	$out    = gs_box_open( '🎁 Premi e sconti sui corsi' );
	$out   .= gs_sezione_aiuto( 'Vincendo badge speciali, guadagni sconti sui corsi di Rina Poletti: si accumulano finché non ti iscrivi davvero a un corso di quel livello. Si parte dal corso Base; una volta usato lo sconto su un corso, quello successivo che guadagni si riferisce al corso Avanzato, poi al Professionale. Attenzione: lo sconto accumulato durante l\'anno va speso entro il 24 dicembre, altrimenti si azzera.' );

	if ( $pct > 0 ) {
		$out .= '<p class="gs-sconto-evidenza">🎓 Sconto accumulato: <strong>' . number_format( $pct, 0 ) . '%</strong> sul corso <strong>' . esc_html( gs_sconto_livello_label( $liv ) ) . '</strong></p>';
		$out .= '<p class="gs-hint">Iscriviti a un corso ' . esc_html( gs_sconto_livello_label( $liv ) ) . ' e segnalo alla segreteria: lo sconto verrà applicato.</p>';
	} else {
		$out .= '<p class="gs-hint">Nessuno sconto accumulato al momento. Vinci badge per guadagnarne uno sul corso ' . esc_html( gs_sconto_livello_label( $liv ) ) . '.</p>';
	}

	if ( function_exists( 'gs_premio_storico_html' ) ) {
		$out .= gs_premio_storico_html( $uid );
	}

	$out .= gs_box_close();
	return $out;
}
