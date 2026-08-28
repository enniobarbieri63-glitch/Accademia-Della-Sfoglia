<?php
/**
 * year-prize.php — Premio di Fine Anno (Corso con Rina Poletti).
 * Usa il totale punti per anno solare gestito in points.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classifica dell'anno indicato (per punti dell'anno di gioco, gennaio →
 * 13 dicembre — non l'anno solare intero: aggiornato il 22/08/2026 insieme
 * al sistema mensile di gioco, vedi gs_get_anno_gioco_points() in points.php).
 *
 * @param string $year  es. "2026". Default: anno corrente.
 * @param int    $limit
 * @return array di array( 'user' => WP_User, 'punti' => int )
 */
function gs_year_leaderboard( $year = null, $limit = 50 ) {
	$year     = $year ? $year : date( 'Y', current_time( 'timestamp' ) );
	$meta_key = 'gs_points_anno_' . $year;

	// 'number' a -1 e filtro dopo, come gs_leaderboard() in voting.php: senza,
	// il Premio di Fine Anno potrebbe risultare vinto da un account che non è
	// una sfoglina vera.
	$tutti = get_users( array(
		'meta_key' => $meta_key,
		'orderby'  => 'meta_value_num',
		'order'    => 'DESC',
		'number'   => -1,
	) );
	$users = array_slice( array_values( array_filter( $tutti, 'gs_e_sfoglina_vera' ) ), 0, $limit );

	$out = array();
	foreach ( $users as $user ) {
		$out[] = array(
			'user'  => $user,
			'punti' => (int) get_user_meta( $user->ID, $meta_key, true ),
		);
	}
	return $out;
}

/**
 * Assegna il Premio di Fine Anno alle prime N classificate.
 *
 * @param string $year
 * @return array Nomi delle vincitrici premiate.
 */
function gs_assign_year_prize( $year = null ) {
	$year     = $year ? $year : date( 'Y', current_time( 'timestamp' ) );
	$settings = gs_settings()['year_prize'];
	$n        = (int) $settings['numero_vincitrici'];
	$testo    = $settings['testo'];

	$classifica = gs_year_leaderboard( $year, $n );
	$vincitrici = array();

	foreach ( $classifica as $riga ) {
		$user = $riga['user'];

		// Badge personalizzato "Corso con Rina Poletti" (registrato al volo).
		$badges = gs_get_user_badges( $user->ID );
		$badge_key = 'corso_rina_' . $year;
		if ( ! in_array( $badge_key, $badges, true ) ) {
			$badges[] = $badge_key;
			update_user_meta( $user->ID, 'gs_badges', $badges );
			update_user_meta( $user->ID, 'gs_badge_label_' . $badge_key, '🎓 Corso con Rina Poletti ' . $year );
		}

		// Email con i dettagli del premio.
		$oggetto_premio = 'Hai vinto il Premio di Fine Anno: Corso con Rina Poletti! 🎓';
		$corpo_premio   = sprintf(
			"Complimenti %s!\n\nSei tra i primi posti della classifica dell'anno %s e hai vinto il Premio di Fine Anno dell'Accademia della Sfoglia:\n\n%s\n\nLa segreteria ti contatterà con i dettagli organizzativi.\n\nCon stima,\nl'Accademia della Sfoglia",
			$user->display_name, $year, $testo
		);
		if ( function_exists( 'gs_mail_progetto' ) ) {
			gs_mail_progetto( $user->ID, 'livelli', $oggetto_premio, $corpo_premio );
		} else {
			wp_mail( $user->user_email, $oggetto_premio, $corpo_premio );
		}

		// Avviso Aeroplanino (punto 11, Ennio 21/08/2026: era uno dei "buchi"
		// trovati, segnato per errore come "indovina.php" in quella nota —
		// il vincitore annunciato è qui, nel Premio di Fine Anno).
		if ( function_exists( 'gs_accoda_volo' ) ) {
			gs_accoda_volo( $user->ID, '🎓 HAI VINTO IL PREMIO DI FINE ANNO!', function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_classifica' ) : '' );
		}

		$vincitrici[] = $user->display_name;
	}

	// Registra l'avvenuta assegnazione.
	update_option( 'gs_year_prize_assigned_' . $year, current_time( 'mysql' ) );

	return $vincitrici;
}

/**
 * Verifica se il premio è già stato assegnato per un anno.
 */
function gs_year_prize_assigned( $year ) {
	return (bool) get_option( 'gs_year_prize_assigned_' . $year, false );
}
