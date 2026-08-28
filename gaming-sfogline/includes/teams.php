<?php
/**
 * teams.php — Squadre regionali e classifica a squadre.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elenco delle squadre configurate.
 */
function gs_get_teams() {
	$teams = gs_settings()['teams'];
	return is_array( $teams ) ? $teams : array();
}

/**
 * Squadra di un utente.
 */
function gs_get_user_team( $user_id ) {
	return get_user_meta( $user_id, 'gs_team', true );
}

/** ID di tutti i membri di una squadra (per i Percorsi di Squadra). */
function gs_squadra_membri( $team ) {
	if ( ! $team ) { return array(); }
	$utenti = get_users( array( 'meta_key' => 'gs_team', 'meta_value' => $team, 'number' => -1 ) );
	$utenti = array_filter( $utenti, 'gs_e_sfoglina_vera' );
	return array_map( function ( $u ) { return (int) $u->ID; }, $utenti );
}

/**
 * Scelta della squadra (AJAX).
 */
add_action( 'wp_ajax_gs_scegli_squadra', 'gs_ajax_scegli_squadra' );

function gs_ajax_scegli_squadra() {
	check_ajax_referer( 'gs_ajax', 'nonce' );

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		wp_send_json_error( array( 'message' => 'Devi accedere.' ) );
	}

	$check = gs_antispam_check( $_POST, 'squadra' );
	if ( is_wp_error( $check ) ) {
		wp_send_json_error( array( 'message' => $check->get_error_message() ) );
	}

	$squadra = gs_clean( $_POST['squadra'] ?? '' );
	if ( ! in_array( $squadra, gs_get_teams(), true ) ) {
		wp_send_json_error( array( 'message' => 'Squadra non valida.' ) );
	}

	update_user_meta( $user_id, 'gs_team', $squadra );
	wp_send_json_success( array( 'message' => 'Ora fai parte di ' . $squadra . '! 🎽' ) );
}

/**
 * Classifica a squadre: somma dei punti totali dei membri.
 */
function gs_team_leaderboard() {
	$teams   = gs_get_teams();
	$totals  = array();
	$counts  = array();

	foreach ( $teams as $team ) {
		$totals[ $team ] = 0;
		$counts[ $team ] = 0;
	}

	$members = get_users( array(
		'meta_key'     => 'gs_team',
		'meta_compare' => 'EXISTS',
		'number'       => -1,
	) );

	foreach ( $members as $member ) {
		if ( ! gs_e_sfoglina_vera( $member ) ) { continue; }
		$team = get_user_meta( $member->ID, 'gs_team', true );
		if ( ! isset( $totals[ $team ] ) ) {
			continue;
		}
		$totals[ $team ] += (int) get_user_meta( $member->ID, 'gs_points', true );
		$counts[ $team ]++;
	}

	arsort( $totals );

	$out = array();
	foreach ( $totals as $team => $punti ) {
		$out[] = array(
			'squadra' => $team,
			'punti'   => $punti,
			'membri'  => $counts[ $team ],
		);
	}
	return $out;
}
