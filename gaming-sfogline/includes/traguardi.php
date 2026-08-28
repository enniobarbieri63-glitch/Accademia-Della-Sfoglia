<?php
/**
 * traguardi.php — "Ultimi Traguardi": feed pubblico dei risultati più
 * recenti di tutta l'Accademia (diplomi assegnati da Rina Poletti e badge
 * sbloccati), i più recenti in cima. Diverso dalla Vetrina (gs_sc_vetrina
 * in shortcodes.php), che è la pagina pubblica di UNA sola sfoglina: qui
 * invece si vede tutta l'Accademia insieme.
 *
 * Non introduce dati nuovi per i diplomi: riusa gs_registro_allievi() già
 * presente in registro.php. Per i badge legge gs_badges_log, il registro
 * delle date di sblocco salvato da gs_unlock_badge() in badges.php.
 *
 * Pagina pubblica, senza login richiesto, come la Vetrina e il Registro
 * Ufficiale: non passa dal cancello del blackout.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gli ultimi $limit traguardi (diplomi + badge) di tutta l'Accademia, più
 * recenti prima.
 */
function gs_traguardi_recenti( $limit = 12 ) {
	$eventi = array();

	// Diplomi assegnati da Rina Poletti.
	if ( function_exists( 'gs_registro_allievi' ) ) {
		foreach ( gs_registro_allievi() as $r ) {
			if ( ! $r['data'] ) {
				continue; // diploma senza data non entra nel feed (ordine non affidabile)
			}
			$eventi[] = array(
				'icona' => '🎓',
				'nome'  => $r['nome'],
				'label' => 'ha conseguito la Laurea in Sfoglia',
				'ts'    => strtotime( $r['data'] . ' 00:00:00' ),
				'data'  => $r['data'],
			);
		}
	}

	// Badge sbloccati, letti dal log di ogni sfoglina.
	if ( function_exists( 'gs_get_badges_definitions' ) && function_exists( 'gs_sez_sfogline' ) ) {
		$defs = gs_get_badges_definitions();
		foreach ( gs_sez_sfogline() as $u ) {
			$log = get_user_meta( $u->ID, 'gs_badges_log', true );
			if ( ! is_array( $log ) ) {
				continue;
			}
			foreach ( $log as $key => $ts ) {
				if ( ! $ts ) {
					continue;
				}
				if ( isset( $defs[ $key ] ) ) {
					$icona = $defs[ $key ]['icon'];
					$label = $defs[ $key ]['label'];
				} else {
					// Badge dinamico (chiave non fissa): es. "Percorso completato".
					$label = get_user_meta( $u->ID, 'gs_badge_label_' . $key, true );
					if ( ! $label ) { continue; }
					$icona = '🏅';
				}
				$eventi[] = array(
					'icona' => $icona,
					'nome'  => $u->display_name,
					'label' => 'ha sbloccato il badge «' . $label . '»',
					'ts'    => (int) $ts,
					'data'  => date( 'Y-m-d', (int) $ts ),
				);
			}
		}
	}

	usort( $eventi, function ( $a, $b ) { return $b['ts'] <=> $a['ts']; } );

	return array_slice( $eventi, 0, $limit );
}

add_shortcode( 'gs_ultimi_traguardi', 'gs_sc_ultimi_traguardi' );
function gs_sc_ultimi_traguardi() {
	$out  = gs_box_open( '🏅 Ultimi Traguardi' );
	$out .= gs_sezione_aiuto( 'Qui vedi gli ultimi risultati raggiunti dalle sfogline di tutta l\'Accademia: diplomi conseguiti e badge sbloccati, i più recenti in cima. Pagina pubblica, come la Vetrina e il Registro Ufficiale.' );

	$eventi = gs_traguardi_recenti( 15 );
	if ( ! $eventi ) {
		$out .= '<p class="gs-hint">Ancora nessun traguardo da mostrare.</p>';
	} else {
		$out .= '<div class="gs-todo-riquadro gs-riquadro-vetrina"><ul class="gs-traguardi-lista">';
		foreach ( $eventi as $e ) {
			$data_lbl = date_i18n( 'j F Y', $e['ts'] );
			$out .= '<li><span class="gs-traguardo-ico">' . $e['icona'] . '</span> <strong>' . esc_html( $e['nome'] ) . '</strong> '
				. esc_html( $e['label'] ) . ' <span class="gs-msg-data">' . esc_html( $data_lbl ) . '</span></li>';
		}
		$out .= '</ul></div>';
	}

	$out .= gs_box_close();
	return $out;
}
