<?php
/**
 * seasonal.php — Barometro Stagionale (Guide Segrete della Stagione).
 * Sbloccate solo per chi ha partecipato a una sfida nel trimestre di competenza.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verifica se un utente ha partecipato ad almeno una sfida in un trimestre.
 *
 * @param int    $user_id
 * @param string $trimestre formato "2026-Q3"
 */
function gs_participated_in_quarter( $user_id, $trimestre ) {
	// $trimestre = "ANNO-Qn"
	$parts = explode( '-Q', $trimestre );
	if ( count( $parts ) !== 2 ) {
		return false;
	}
	$year = $parts[0];
	$qn   = 'Q' . $parts[1];

	$quarters = get_user_meta( $user_id, 'gs_quarters', true );
	if ( ! is_array( $quarters ) || ! isset( $quarters[ $year ] ) ) {
		return false;
	}
	return in_array( $qn, $quarters[ $year ], true );
}

/**
 * Restituisce le guide accessibili a un utente.
 */
function gs_get_accessible_guides( $user_id ) {
	$guides = get_posts( array(
		'post_type'      => 'gs_barometro',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	) );

	$accessible = array();
	$locked     = array();

	foreach ( $guides as $guide ) {
		$trimestre = get_post_meta( $guide->ID, 'gs_trimestre', true );
		if ( user_can( $user_id, 'manage_options' ) || gs_participated_in_quarter( $user_id, $trimestre ) ) {
			$accessible[] = $guide;
		} else {
			$locked[] = array( 'post' => $guide, 'trimestre' => $trimestre );
		}
	}

	return array( 'accessible' => $accessible, 'locked' => $locked );
}
