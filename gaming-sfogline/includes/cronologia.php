<?php
/**
 * cronologia.php — "Il Tuo Percorso": cronologia personale della sfoglina.
 *
 * Mette in una sola pagina, in ordine cronologico, quello che la sfoglina ha
 * già costruito nel tempo: livello attuale, badge sbloccati, punti guadagnati
 * (riusa il log già scritto da points.php), ricette approvate nel Ricettario
 * e lezioni video guardate. Nessun dato nuovo da salvare: legge solo quello
 * che gli altri moduli scrivono già nei meta.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// [gs_percorso_personale] — lato sfoglina
// -----------------------------------------------------------------------------
add_shortcode( 'gs_percorso_personale', 'gs_sc_percorso_personale' );
function gs_sc_percorso_personale() {
	if ( $g = gs_gate_riservato() ) { return $g; }
	if ( $g = gs_blackout_gate() ) { return $g; }

	$uid = get_current_user_id();

	// --- Livello attuale ---
	$level = gs_get_level( $uid );
	$out   = gs_box_open( '🧭 Il Tuo Percorso' );
	$out  .= gs_sezione_aiuto( 'Pagina di sola consultazione, pensata per ripercorrere quello che hai già fatto: nessun modulo da compilare. Scorri i riquadri per vedere livello, badge, cronologia punti, ricette approvate e lezioni guardate. La tabella della cronologia punti è paginata, usa "Vedi tutti" per vederla tutta.' );
	$out  .= '<p class="gs-hint">Tutto quello che hai costruito finora, in ordine di tempo: livello, badge, punti, ricette approvate e lezioni guardate.</p>';
	$out  .= '<div class="gs-todo-riquadro">';
	$out  .= '<h4 style="margin-top:0">' . esc_html( $level['simbolo'] . ' ' . $level['titolo'] ) . ' — ' . (int) $level['punti'] . ' punti' . '</h4>';
	if ( $level['next'] ) {
		$out .= '<p class="gs-hint">Mancano ' . (int) $level['to_next'] . ' punti a ' . esc_html( $level['next']['simbolo'] . ' ' . $level['next']['titolo'] ) . ' (' . (int) $level['progress'] . '%).</p>';
	} else {
		$out .= '<p class="gs-hint">Hai raggiunto l\'insegna più alta.</p>';
	}
	$out .= '</div>';
	$out .= gs_box_close();

	// --- Badge sbloccati ---
	$defs  = gs_get_badges_definitions();
	$owned = gs_get_user_badges( $uid );
	$out  .= gs_box_open( '🎖️ I tuoi badge (' . count( $owned ) . ' su ' . count( $defs ) . ')' );
	if ( ! $owned ) {
		$out .= '<p class="gs-hint">Non hai ancora sbloccato nessun badge. Continua a partecipare!</p>';
	} else {
		$out .= '<div class="gs-badge-grid">';
		foreach ( $owned as $key ) {
			if ( isset( $defs[ $key ] ) ) {
				$icon   = $defs[ $key ]['icon'];
				$label  = $defs[ $key ]['label'];
				$desc   = $defs[ $key ]['desc'];
				$colore = ! empty( $defs[ $key ]['colore'] ) ? $defs[ $key ]['colore'] : gs_badge_colore_riserva();
			} else {
				// Badge dinamico (chiave non fissa): es. "Percorso completato",
				// "Corso con Rina Poletti YYYY". Etichetta salvata a parte.
				$label = get_user_meta( $uid, 'gs_badge_label_' . $key, true );
				if ( ! $label ) { continue; }
				$icon   = '🏅';
				$desc   = '';
				$colore = gs_badge_colore_riserva();
			}
			$out .= '<div class="gs-badge-card unlocked" ' . gs_badge_style_colore( $colore ) . '><div class="gs-badge-testa"><div class="gs-badge-icon">' . $icon . '</div></div>';
			$out .= '<div class="gs-badge-corpo"><h4>' . esc_html( $label ) . '</h4><p>' . esc_html( $desc ) . '</p></div></div>';
		}
		$out .= '</div>';
	}
	$out .= gs_box_close();

	// --- Cronologia punti ---
	$log = gs_get_points_log( $uid, 30 );
	$out .= gs_box_open( '📜 Cronologia punti' );
	if ( ! $log ) {
		$out .= '<p class="gs-hint">Ancora nessun punto guadagnato.</p>';
	} else {
		$out .= '<table class="gs-table gs-paginate" data-per-page="10"><thead><tr><th>Data</th><th>Punti</th><th>Motivo</th><th>Totale</th></tr></thead><tbody>';
		foreach ( $log as $entry ) {
			$segno = $entry['points'] >= 0 ? '+' : '';
			$out  .= '<tr><td>' . esc_html( $entry['time'] ) . '</td><td>' . esc_html( $segno . (int) $entry['points'] ) . '</td>';
			$out  .= '<td>' . esc_html( $entry['reason'] ) . '</td><td>' . (int) $entry['total'] . '</td></tr>';
		}
		$out .= '</tbody></table>';
	}
	$out .= gs_box_close();

	// --- Ricette approvate ---
	$ricette_approvate = array();
	if ( function_exists( 'gs_ricette_utente' ) ) {
		foreach ( gs_ricette_utente( $uid ) as $r ) {
			if ( 'approvata' === get_post_meta( $r->ID, 'gs_stato', true ) ) {
				$ricette_approvate[] = $r;
			}
		}
	}
	$out .= gs_box_open( '📖 Le tue ricette approvate (' . count( $ricette_approvate ) . ')' );
	if ( ! $ricette_approvate ) {
		$out .= '<p class="gs-hint">Nessuna ricetta ancora approvata nel Ricettario delle Famiglie.</p>';
	} else {
		$out .= '<div class="gs-todo-riquadro"><ul class="gs-missions">';
		foreach ( $ricette_approvate as $r ) {
			$out .= '<li>' . esc_html( get_the_title( $r ) ) . '</li>';
		}
		$out .= '</ul></div>';
	}
	$out .= gs_box_close();

	// --- Lezioni video guardate ---
	$viste = get_user_meta( $uid, 'gs_lezioni_viste', true );
	$viste = is_array( $viste ) ? $viste : array();
	$out  .= gs_box_open( '🎬 Lezioni video guardate (' . count( $viste ) . ')' );
	if ( ! $viste ) {
		$out .= '<p class="gs-hint">Non hai ancora aperto nessuna lezione video.</p>';
	} else {
		$out .= '<div class="gs-todo-riquadro"><ul class="gs-missions">';
		foreach ( $viste as $lid ) {
			if ( 'gs_lezione' === get_post_type( $lid ) ) {
				$out .= '<li>' . esc_html( get_the_title( $lid ) ) . '</li>';
			}
		}
		$out .= '</ul></div>';
	}
	$out .= gs_box_close();

	return $out;
}
