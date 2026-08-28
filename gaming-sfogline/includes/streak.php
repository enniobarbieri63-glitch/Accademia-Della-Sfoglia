<?php
/**
 * streak.php — Streak del Matterello.
 * +1 per ogni settimana solare (lun-dom) in cui si pubblica almeno una sfoglia.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alla pubblicazione di una sfoglia, aggiorna la streak.
 */
add_action( 'gs_sfoglia_pubblicata', 'gs_update_streak', 10, 1 );

function gs_update_streak( $user_id ) {
	$this_week = gs_current_week();
	$last_week = get_user_meta( $user_id, 'gs_streak_last_week', true );

	if ( $last_week === $this_week ) {
		return; // già conteggiata questa settimana
	}

	$streak = (int) get_user_meta( $user_id, 'gs_streak', true );

	if ( $last_week && gs_is_previous_week( $last_week, $this_week ) ) {
		$streak++;
	} else {
		$streak = 1; // ricomincia (prima volta o settimana saltata)
	}

	update_user_meta( $user_id, 'gs_streak', $streak );
	update_user_meta( $user_id, 'gs_streak_last_week', $this_week );

	// Punti streak dalla seconda settimana consecutiva in poi.
	if ( $streak > 1 ) {
		gs_add_points( $user_id, gs_get_points_value( 'streak_settimana', 10 ), 'Streak mantenuta (' . $streak . ' settimane)' );
	}

	gs_streak_scudo_controlla_traguardo( $user_id, $streak );
}

// -----------------------------------------------------------------------------
// Scudo salva-streak (v3.115.0): ogni 4 settimane consecutive si guadagna
// uno scudo, spendibile una volta per coprire UNA settimana saltata senza
// azzerare la streak — pensato per non far abbandonare del tutto chi perde
// un turno per una volta, mantenendo comunque il valore della costanza.
// -----------------------------------------------------------------------------

/** Ogni quante settimane consecutive si guadagna un nuovo scudo. */
function gs_streak_scudo_ogni_settimane() {
	return 4;
}

/** Quanti scudi ha attualmente disponibili una sfoglina. */
function gs_streak_scudi( $uid ) {
	return (int) get_user_meta( $uid, 'gs_streak_scudi', true );
}

/** Se la streak ha appena raggiunto un multiplo della soglia, assegna un nuovo scudo (badge dedicato, una tantum per traguardo). */
function gs_streak_scudo_controlla_traguardo( $uid, $streak ) {
	$soglia = gs_streak_scudo_ogni_settimane();
	if ( $streak < $soglia || 0 !== $streak % $soglia ) { return false; }

	$badge_key = 'streak_scudo_' . $streak;
	$owned     = gs_get_user_badges( $uid );
	if ( in_array( $badge_key, $owned, true ) ) { return false; } // già assegnato per questo traguardo

	$owned[] = $badge_key;
	update_user_meta( $uid, 'gs_badges', $owned );
	update_user_meta( $uid, 'gs_badge_label_' . $badge_key, '🛡️ Scudo salva-streak (' . $streak . ' settimane)' );

	$log = get_user_meta( $uid, 'gs_badges_log', true );
	$log = is_array( $log ) ? $log : array();
	$log[ $badge_key ] = current_time( 'timestamp' );
	update_user_meta( $uid, 'gs_badges_log', $log );

	update_user_meta( $uid, 'gs_streak_scudi', gs_streak_scudi( $uid ) + 1 );

	if ( function_exists( 'gs_accoda_volo' ) ) {
		gs_accoda_volo( $uid, 'HAI GUADAGNATO UNO SCUDO SALVA-STREAK! 🛡️ (' . $streak . ' SETTIMANE)', gs_pagina_url( 'gs_page_dashboard' ) );
	}
	return true;
}

/** La settimana (formato "aaaa-WNN") immediatamente successiva a quella data. */
function gs_settimana_successiva( $week ) {
	$p = sscanf( $week, '%d-W%d' );
	if ( ! $p || count( $p ) < 2 || null === $p[0] || null === $p[1] ) { return $week; }
	list( $y, $w ) = $p;
	$d = new DateTime();
	$d->setISODate( (int) $y, (int) $w, 1 ); // lunedì di quella settimana
	$d->modify( '+7 days' );
	return gs_current_week( $d->getTimestamp() );
}

/**
 * Verifica se $last è la settimana immediatamente precedente a $current.
 */
function gs_is_previous_week( $last, $current ) {
	// $last e $current sono nel formato "2026-W27".
	$lp = sscanf( $last, '%d-W%d' );
	$cp = sscanf( $current, '%d-W%d' );
	if ( ! $lp || ! $cp ) {
		return false;
	}
	list( $ly, $lw ) = $lp;
	list( $cy, $cw ) = $cp;

	if ( $ly === $cy ) {
		return ( $cw - $lw ) === 1;
	}
	// Cambio anno: ultima settimana dell'anno precedente → prima del nuovo.
	if ( $cy - $ly === 1 && 1 === (int) $cw && $lw >= 52 ) {
		return true;
	}
	return false;
}

/**
 * Controllo giornaliero: azzera le streak di chi ha saltato una settimana
 * intera — a meno che non abbia uno scudo salva-streak disponibile, nel
 * qual caso lo consuma e la streak resta viva (la settimana saltata viene
 * "coperta": gs_streak_last_week avanza di una settimana, così il giorno
 * dopo il controllo non scatta di nuovo per la stessa settimana).
 */
add_action( 'gs_daily_cron', 'gs_check_streaks' );

function gs_check_streaks() {
	$users = get_users( array(
		'meta_key'     => 'gs_streak',
		'meta_compare' => 'EXISTS',
		'number'       => -1,
	) );

	$this_week = gs_current_week();

	foreach ( $users as $user ) {
		$last   = get_user_meta( $user->ID, 'gs_streak_last_week', true );
		$streak = (int) get_user_meta( $user->ID, 'gs_streak', true );
		if ( ! $streak ) {
			continue;
		}
		// Se l'ultima settimana attiva non è né questa né la precedente → streak a rischio.
		if ( $last !== $this_week && ! gs_is_previous_week( $last, $this_week ) ) {
			$scudi = gs_streak_scudi( $user->ID );
			if ( $scudi > 0 ) {
				update_user_meta( $user->ID, 'gs_streak_scudi', $scudi - 1 );
				update_user_meta( $user->ID, 'gs_streak_last_week', gs_settimana_successiva( $last ) );
				if ( function_exists( 'gs_accoda_volo' ) ) {
					gs_accoda_volo( $user->ID, 'IL TUO SCUDO HA SALVATO LA STREAK! 🛡️ (' . $streak . ' SETTIMANE INTATTE)', gs_pagina_url( 'gs_page_dashboard' ) );
				}
				continue;
			}
			update_user_meta( $user->ID, 'gs_streak', 0 );
		}
	}
}

/**
 * Streak corrente di un utente.
 */
function gs_get_streak( $user_id ) {
	return (int) get_user_meta( $user_id, 'gs_streak', true );
}
