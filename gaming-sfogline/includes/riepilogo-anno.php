<?php
/**
 * riepilogo-anno.php — "Il Tuo Anno in Accademia": riepilogo narrativo
 * personale per anno solare (punti, badge sbloccati, opere pubblicate,
 * ricette inviate, posizione in classifica). Diverso da cronologia.php
 * (che è una cronologia di TUTTA la vita natural durante, non per anno) e
 * da year-prize.php (che è una classifica, non un racconto personale).
 * Nessun dato nuovo da salvare: legge solo quello che gli altri moduli
 * scrivono già nei meta.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dati del riepilogo annuale di un utente per l'anno indicato.
 *
 * @param int         $user_id
 * @param string|null $year "2026", default: anno corrente.
 */
function gs_riepilogo_anno_dati( $user_id, $year = null ) {
	$user_id = (int) $user_id;
	$year    = $year ? (string) $year : date( 'Y', current_time( 'timestamp' ) );

	$punti_anno      = function_exists( 'gs_get_year_points' ) ? gs_get_year_points( $user_id, $year ) : 0;
	$punti_anno_prec = function_exists( 'gs_get_year_points' ) ? gs_get_year_points( $user_id, (string) ( (int) $year - 1 ) ) : 0;

	// Badge sbloccati durante l'anno (dal log con data, non solo dai posseduti oggi).
	$log  = get_user_meta( $user_id, 'gs_badges_log', true );
	$log  = is_array( $log ) ? $log : array();
	$defs = function_exists( 'gs_get_badges_definitions' ) ? gs_get_badges_definitions() : array();
	$badge_anno = array();
	foreach ( $log as $key => $ts ) {
		if ( (string) date( 'Y', (int) $ts ) === $year ) {
			$badge_anno[] = isset( $defs[ $key ] ) ? $defs[ $key ] : array( 'icon' => '🏅', 'label' => $key );
		}
	}

	// Opere pubblicate nell'anno.
	$opere_anno = 0;
	if ( function_exists( 'gs_get_user_works' ) ) {
		foreach ( gs_get_user_works( $user_id ) as $p ) {
			if ( (string) date( 'Y', strtotime( $p->post_date ) ) === $year ) {
				$opere_anno++;
			}
		}
	}

	// Ricette di famiglia inviate nell'anno (qualunque stato: l'invio è già un traguardo).
	$ricette_anno = 0;
	if ( function_exists( 'gs_ricette_utente' ) ) {
		foreach ( gs_ricette_utente( $user_id ) as $p ) {
			if ( (string) date( 'Y', strtotime( $p->post_date ) ) === $year ) {
				$ricette_anno++;
			}
		}
	}

	// Posizione in classifica dell'anno.
	$posizione = null;
	if ( function_exists( 'gs_year_leaderboard' ) ) {
		foreach ( gs_year_leaderboard( $year, 1000 ) as $i => $riga ) {
			if ( (int) $riga['user']->ID === $user_id ) {
				$posizione = $i + 1;
				break;
			}
		}
	}

	return array(
		'anno'            => $year,
		'punti_anno'      => $punti_anno,
		'punti_anno_prec' => $punti_anno_prec,
		'badge_anno'      => $badge_anno,
		'opere_anno'      => $opere_anno,
		'ricette_anno'    => $ricette_anno,
		'posizione'       => $posizione,
		'streak_attuale'  => function_exists( 'gs_get_streak' ) ? gs_get_streak( $user_id ) : 0,
		'livello'         => function_exists( 'gs_get_level' ) ? gs_get_level( $user_id ) : null,
	);
}

/** true se c'è qualcosa da raccontare (evita un riepilogo vuoto per chi non ha ancora fatto nulla quest'anno). */
function gs_riepilogo_anno_ha_contenuto( $dati ) {
	return $dati['punti_anno'] > 0 || $dati['opere_anno'] > 0 || $dati['ricette_anno'] > 0 || count( $dati['badge_anno'] ) > 0;
}

// -----------------------------------------------------------------------------
// [gs_il_mio_anno] — lato sfoglina
// -----------------------------------------------------------------------------
add_shortcode( 'gs_il_mio_anno', 'gs_sc_il_mio_anno' );
function gs_sc_il_mio_anno() {
	if ( $g = gs_gate_riservato() ) { return $g; }
	if ( $g = gs_blackout_gate() ) { return $g; }

	$uid  = get_current_user_id();
	$dati = gs_riepilogo_anno_dati( $uid );

	$out  = gs_box_open( '🎊 Il Tuo Anno in Accademia — ' . esc_html( $dati['anno'] ) );
	$out .= gs_sezione_aiuto( 'Un racconto (non una classifica) di quello che hai fatto quest\'anno: punti guadagnati, badge sbloccati, ricette inviate, opere pubblicate e la tua posizione nella classifica dell\'anno. Si aggiorna via via che l\'anno prosegue.' );

	if ( ! gs_riepilogo_anno_ha_contenuto( $dati ) ) {
		$out .= '<p class="gs-hint">Non hai ancora attività registrata per il ' . esc_html( $dati['anno'] ) . '. Torna a trovarci quando avrai pubblicato qualcosa!</p>';
		$out .= gs_box_close();
		return $out;
	}

	$out .= '<div class="gs-todo-riquadro">';

	$out .= '<p class="gs-hint" style="font-size:16px"><strong>' . (int) $dati['punti_anno'] . ' punti</strong> guadagnati nel ' . esc_html( $dati['anno'] );
	if ( $dati['punti_anno_prec'] > 0 ) {
		$diff = $dati['punti_anno'] - $dati['punti_anno_prec'];
		if ( $diff > 0 ) {
			$out .= '<br>' . $diff . ' in più rispetto al ' . ( (int) $dati['anno'] - 1 );
		} elseif ( $diff < 0 ) {
			$out .= '<br>' . abs( $diff ) . ' in meno rispetto al ' . ( (int) $dati['anno'] - 1 );
		} else {
			$out .= '<br>come nel ' . ( (int) $dati['anno'] - 1 );
		}
	}
	$out .= '</p>';

	if ( $dati['livello'] ) {
		$out .= '<p class="gs-hint">Hai raggiunto <strong>' . esc_html( $dati['livello']['simbolo'] . ' ' . $dati['livello']['titolo'] ) . '</strong> (livello ' . (int) $dati['livello']['numero'] . ')</p>';
	}
	if ( $dati['posizione'] ) {
		$out .= '<p class="gs-hint">Sei <strong>' . (int) $dati['posizione'] . 'ª</strong> nella classifica del ' . esc_html( $dati['anno'] ) . '</p>';
	}
	if ( $dati['opere_anno'] ) {
		$out .= '<p class="gs-hint"><strong>' . (int) $dati['opere_anno'] . '</strong> ' . ( 1 === $dati['opere_anno'] ? 'opera pubblicata' : 'opere pubblicate' ) . '</p>';
	}
	if ( $dati['ricette_anno'] ) {
		$out .= '<p class="gs-hint"><strong>' . (int) $dati['ricette_anno'] . '</strong> ' . ( 1 === $dati['ricette_anno'] ? 'ricetta di famiglia inviata' : 'ricette di famiglia inviate' ) . ' al Ricettario</p>';
	}
	if ( $dati['streak_attuale'] > 1 ) {
		$out .= '<p class="gs-hint">🔥 Striscia attuale: <strong>' . (int) $dati['streak_attuale'] . ' settimane</strong></p>';
	}
	if ( $dati['badge_anno'] ) {
		$out .= '<p class="gs-hint"><strong>' . count( $dati['badge_anno'] ) . ' ' . ( 1 === count( $dati['badge_anno'] ) ? 'badge sbloccato' : 'badge sbloccati' ) . ' quest\'anno</strong><br>';
		$pezzi = array();
		foreach ( $dati['badge_anno'] as $b ) {
			$pezzi[] = ( $b['icon'] ?? '🏅' ) . ' ' . ( $b['label'] ?? '' );
		}
		$out .= esc_html( implode( '   ', $pezzi ) );
		$out .= '</p>';
	}

	$out .= '</div>';
	$out .= gs_box_close();
	return $out;
}
