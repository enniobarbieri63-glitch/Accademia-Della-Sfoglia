<?php
/**
 * missions.php — Missioni giornaliere. Progressi che si azzerano ogni notte.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Progressi missioni del giorno per un utente (reset automatico a cambio giorno).
 */
function gs_get_mission_progress( $user_id ) {
	$today  = date( 'Y-m-d', current_time( 'timestamp' ) );
	$stored = get_user_meta( $user_id, 'gs_missions', true );

	if ( ! is_array( $stored ) || ( $stored['date'] ?? '' ) !== $today ) {
		$stored = array( 'date' => $today, 'progress' => array(), 'done' => array() );
		update_user_meta( $user_id, 'gs_missions', $stored );
	}
	return $stored;
}

/**
 * Incrementa il progresso di una missione e assegna i punti al completamento.
 *
 * @param int    $user_id
 * @param string $mission_key vota_3 | diario | consiglio | commento
 */
function gs_advance_mission( $user_id, $mission_key ) {
	$missions = gs_settings()['missions'];
	if ( ! isset( $missions[ $mission_key ] ) ) {
		return;
	}

	$state = gs_get_mission_progress( $user_id );

	// Già completata oggi?
	if ( in_array( $mission_key, $state['done'], true ) ) {
		return;
	}

	$current = (int) ( $state['progress'][ $mission_key ] ?? 0 );
	$current++;
	$state['progress'][ $mission_key ] = $current;

	$obiettivo = (int) $missions[ $mission_key ]['obiettivo'];
	if ( $current >= $obiettivo ) {
		$state['done'][] = $mission_key;
		gs_add_points( $user_id, (int) $missions[ $mission_key ]['punti'], 'Missione completata: ' . $missions[ $mission_key ]['label'] );
	}

	update_user_meta( $user_id, 'gs_missions', $state );
}

/**
 * La missione "vota_3" (richiesto da Ennio l'11/08/2026) ora richiede
 * ENTRAMBE le cose insieme, non una sola: votare 3 sfoglie E commentarne
 * almeno una. Tracciate come due contatori separati in progress (invece del
 * singolo contatore generico di gs_advance_mission) apposta per non poterla
 * completare solo votando o solo commentando.
 */
function gs_verifica_missione_vota3_completa( $user_id ) {
	$missions = gs_settings()['missions'];
	if ( ! isset( $missions['vota_3'] ) ) {
		return;
	}
	$state = gs_get_mission_progress( $user_id );
	if ( in_array( 'vota_3', $state['done'], true ) ) {
		return;
	}
	$voti      = (int) ( $state['progress']['vota_3'] ?? 0 );
	$commentato = ! empty( $state['progress']['vota_3_commento'] );
	if ( $voti >= 3 && $commentato ) {
		$state['done'][] = 'vota_3';
		gs_add_points( $user_id, (int) $missions['vota_3']['punti'], 'Missione completata: ' . $missions['vota_3']['label'] );
		update_user_meta( $user_id, 'gs_missions', $state );
	}
}

// -----------------------------------------------------------------------------
// Collegamento delle missioni alle azioni reali
// -----------------------------------------------------------------------------
add_action( 'gs_voto_dato', function ( $user_id ) {
	$state = gs_get_mission_progress( $user_id );
	if ( ! in_array( 'vota_3', $state['done'], true ) ) {
		$state['progress']['vota_3'] = (int) ( $state['progress']['vota_3'] ?? 0 ) + 1;
		update_user_meta( $user_id, 'gs_missions', $state );
	}
	gs_verifica_missione_vota3_completa( $user_id );
}, 10, 1 );

add_action( 'gs_commento_sfoglia', function ( $user_id ) {
	$state = gs_get_mission_progress( $user_id );
	if ( ! in_array( 'vota_3', $state['done'], true ) ) {
		$state['progress']['vota_3_commento'] = true;
		update_user_meta( $user_id, 'gs_missions', $state );
	}
	gs_verifica_missione_vota3_completa( $user_id );
}, 10, 1 );

add_action( 'gs_diario_aggiunto', function ( $user_id ) {
	gs_advance_mission( $user_id, 'diario' );
}, 10, 1 );

add_action( 'gs_consiglio_aggiunto', function ( $user_id ) {
	gs_advance_mission( $user_id, 'consiglio' );
}, 10, 1 );

add_action( 'gs_commento_sfida', function ( $user_id ) {
	gs_advance_mission( $user_id, 'commento' );
}, 10, 1 );

/**
 * Elenco leggibile delle missioni con stato per il front-end.
 */
function gs_missions_for_display( $user_id ) {
	$missions = gs_settings()['missions'];
	$state    = gs_get_mission_progress( $user_id );
	$out      = array();

	foreach ( $missions as $key => $m ) {
		$progress = (int) ( $state['progress'][ $key ] ?? 0 );
		$voce = array(
			'key'       => $key,
			'label'     => $m['label'],
			'punti'     => (int) $m['punti'],
			'obiettivo' => (int) $m['obiettivo'],
			'progress'  => min( $progress, (int) $m['obiettivo'] ),
			'done'      => in_array( $key, $state['done'], true ),
		);
		// "vota_3" ha una seconda condizione (un commento) oltre al contatore
		// dei voti: la aggiunge come testo extra, invece di forzarla nel
		// singolo numero progress/obiettivo pensato per le altre missioni.
		if ( 'vota_3' === $key ) {
			$voce['extra'] = ! empty( $state['progress']['vota_3_commento'] ) ? '💬 commento fatto' : '💬 manca un commento';
		}
		$out[] = $voce;
	}
	return $out;
}
