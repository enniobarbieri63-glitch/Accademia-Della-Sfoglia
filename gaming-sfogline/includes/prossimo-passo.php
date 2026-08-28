<?php
/**
 * prossimo-passo.php — Un piccolo suggerimento dinamico su cosa fare dopo,
 * mostrato in cima a "La Mia Sfoglia" (dentro il profilo), oltre
 * all'onboarding iniziale già presente. Non introduce dati nuovi: legge
 * solo lo stato già tracciato da missioni, corso professionale, livelli e
 * calendario corsi.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Il prossimo passo suggerito per l'utente, o null se non c'è nulla da
 * segnalare. Controlla poche condizioni in ordine di priorità e si ferma
 * alla prima che trova.
 */
function gs_prossimo_passo_suggerimento( $user_id ) {
	// 1) Missioni di oggi non ancora completate.
	if ( function_exists( 'gs_missions_for_display' ) ) {
		$missioni = gs_missions_for_display( $user_id );
		$da_fare  = array_filter( $missioni, function ( $m ) { return empty( $m['done'] ); } );
		if ( $da_fare ) {
			return array(
				'icona' => '🎯',
				'testo' => 'Hai ancora missioni di oggi da completare: vale la pena dare un\'occhiata prima che finisca la giornata.',
			);
		}
	}

	// 2) Compiti del Corso Professionale non ancora consegnati.
	if ( function_exists( 'gs_get_corso_utente' ) ) {
		$corso = gs_get_corso_utente( $user_id );
		if ( $corso && ! gs_corso_oscurato( $corso->ID ) && 'sospeso' !== gs_corso_stato( $corso->ID ) ) {
			$compiti = gs_get_compiti( $corso->ID );
			$da_fare = array_filter( $compiti, function ( $c ) { return empty( $c['fatto'] ); } );
			if ( $da_fare ) {
				$n = count( $da_fare );
				return array(
					'icona' => '📝',
					'testo' => 1 === $n
						? 'Hai un compito da consegnare nel Corso Professionale di Sfoglia.'
						: 'Hai ' . $n . ' compiti da consegnare nel Corso Professionale di Sfoglia.',
				);
			}
		}
	}

	// 3) Vicina al prossimo livello.
	if ( function_exists( 'gs_get_level' ) ) {
		$level = gs_get_level( $user_id );
		if ( $level['next'] && $level['to_next'] > 0 && $level['to_next'] <= 20 ) {
			return array(
				'icona' => '⭐',
				'testo' => 'Ti mancano solo ' . (int) $level['to_next'] . ' punti per raggiungere il livello ' . $level['next']['titolo'] . ': pubblica una sfoglia o vota quelle della community.',
			);
		}
	}

	// 4) Nessuna prenotazione mai fatta, e c'è almeno un corso disponibile.
	if ( function_exists( 'gs_cal_corsi' ) && function_exists( 'gs_cal_prenotazioni' ) ) {
		$mie = array_filter( gs_cal_prenotazioni(), function ( $p ) use ( $user_id ) {
			return (int) get_post_meta( $p->ID, 'gs_cliente', true ) === (int) $user_id;
		} );
		if ( ! $mie ) {
			$corsi  = gs_cal_corsi( true );
			$aperti = array_filter( $corsi, function ( $p ) {
				return 'bloccato' !== get_post_meta( $p->ID, 'gs_stato', true ) && gs_cal_posti_rimasti( $p->ID ) > 0;
			} );
			if ( $aperti ) {
				return array(
					'icona' => '📅',
					'testo' => 'C\'è almeno un corso in calendario con posti liberi: potrebbe interessarti prenotare il tuo primo corso.',
				);
			}
		}
	}

	// 5) Niente di urgente: incoraggiamento generico.
	return array(
		'icona' => '🧭',
		'testo' => 'Continua così: pubblica una nuova sfoglia o lascia un consiglio alla community per guadagnare punti.',
	);
}

/** Blocco HTML del suggerimento, da inserire nel profilo (solo pagina propria, non vetrina pubblica). */
function gs_prossimo_passo_html( $user_id ) {
	$s = gs_prossimo_passo_suggerimento( $user_id );
	if ( ! $s ) { return ''; }
	$out  = '<div class="gs-todo-riquadro gs-prossimo-passo">';
	$out .= '<span class="gs-prossimo-passo-ico">' . $s['icona'] . '</span> ';
	$out .= '<strong>Prossimo passo:</strong> ' . esc_html( $s['testo'] );
	$out .= '</div>';
	return $out;
}
