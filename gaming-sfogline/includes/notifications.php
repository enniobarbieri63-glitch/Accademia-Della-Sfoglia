<?php
/**
 * notifications.php — Email automatiche (badge/livello) e push OneSignal.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Email: salita di livello
// -----------------------------------------------------------------------------
add_action( 'gs_level_up', 'gs_email_level_up', 10, 3 );

function gs_email_level_up( $user_id, $new_level, $old_level ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return;
	}
	$levels = gs_settings()['levels'];
	$level  = isset( $levels[ $new_level ] ) ? $levels[ $new_level ] : null;
	if ( ! $level ) {
		return;
	}

	$oggetto = sprintf( 'Nuovo livello raggiunto: %s %s!', $level['simbolo'], $level['titolo'] );
	$corpo   = sprintf(
		"Complimenti %s!\n\nHai raggiunto un nuovo traguardo: %s %s.\n\nContinua così, la prossima insegna ti aspetta!\n\n— Accademia della Sfoglia",
		$user->display_name, $level['simbolo'], $level['titolo']
	);
	if ( function_exists( 'gs_mail_progetto' ) ) {
		gs_mail_progetto( $user_id, 'livelli', $oggetto, $corpo );
	} elseif ( $user->user_email ) {
		wp_mail( $user->user_email, $oggetto, $corpo );
	}
}

// -----------------------------------------------------------------------------
// Email: badge sbloccato
// -----------------------------------------------------------------------------
add_action( 'gs_badge_unlocked', 'gs_email_badge', 10, 2 );

function gs_email_badge( $user_id, $badge_key ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return;
	}
	$badges = gs_get_badges_definitions();
	if ( ! isset( $badges[ $badge_key ] ) ) {
		return;
	}
	$badge = $badges[ $badge_key ];

	$oggetto = sprintf( 'Nuovo badge sbloccato: %s %s', $badge['icon'], $badge['label'] );
	$corpo   = sprintf(
		"Complimenti %s!\n\nHai sbloccato il badge \"%s %s\": %s\n\nLo trovi ora nel tuo profilo, per sempre.\n\n— Accademia della Sfoglia",
		$user->display_name, $badge['icon'], $badge['label'], $badge['desc']
	);
	if ( function_exists( 'gs_mail_progetto' ) ) {
		gs_mail_progetto( $user_id, 'livelli', $oggetto, $corpo );
	} elseif ( $user->user_email ) {
		wp_mail( $user->user_email, $oggetto, $corpo );
	}
}

// -----------------------------------------------------------------------------
// Push OneSignal: reveal dell'Ingrediente Segreto
// -----------------------------------------------------------------------------

/**
 * Verifica se OneSignal è configurato.
 */
function gs_onesignal_ready() {
	$os = gs_settings()['onesignal'];
	return ! empty( $os['app_id'] ) && ! empty( $os['rest_api_key'] );
}

/**
 * Invia una notifica push a tutti gli iscritti. Silenzioso se non configurato.
 *
 * @param string $titolo   Titolo della notifica.
 * @param string $messaggio Corpo.
 * @param string $url      URL di destinazione al click.
 */
function gs_send_push( $titolo, $messaggio, $url = '' ) {
	if ( ! gs_onesignal_ready() ) {
		return false; // disattivato, nessun errore
	}
	$os = gs_settings()['onesignal'];

	$payload = array(
		'app_id'            => $os['app_id'],
		'included_segments' => array( 'Subscribed Users' ),
		'headings'          => array( 'en' => $titolo, 'it' => $titolo ),
		'contents'          => array( 'en' => $messaggio, 'it' => $messaggio ),
	);
	if ( $url ) {
		$payload['url'] = $url;
	}

	$response = wp_remote_post( 'https://onesignal.com/api/v1/notifications', array(
		'timeout' => 15,
		'headers' => array(
			'Content-Type'  => 'application/json; charset=utf-8',
			'Authorization' => 'Basic ' . $os['rest_api_key'],
		),
		'body'    => wp_json_encode( $payload ),
	) );

	if ( is_wp_error( $response ) ) {
		return false;
	}
	return true;
}

// -----------------------------------------------------------------------------
// Digest settimanale: riepilogo via email a tutte le sfogline (Ricettario,
// Lezioni Video, Corsi in arrivo, e "sei quasi arrivata" se manca una sola
// lezione per completare il Percorso Guidato attuale). Gira sul cron
// settimanale del plugin (gs_weekly_cron, programmato/rimosso in
// gaming-sfogline.php). Se per una sfoglina non c'è nessuna novità né un
// percorso quasi finito, non le manda nulla.
// -----------------------------------------------------------------------------
add_action( 'gs_weekly_cron', 'gs_digest_settimanale' );
function gs_digest_settimanale() {
	$da = current_time( 'timestamp' ) - WEEK_IN_SECONDS;

	$ricette_nuove = array();
	if ( function_exists( 'gs_ricette_approvate' ) ) {
		foreach ( gs_ricette_approvate() as $r ) {
			if ( strtotime( $r->post_date ) >= $da ) { $ricette_nuove[] = get_the_title( $r ); }
		}
	}

	$lezioni_nuove = array();
	if ( function_exists( 'gs_lezioni_tutte' ) ) {
		foreach ( gs_lezioni_tutte() as $l ) {
			if ( strtotime( $l->post_date ) >= $da ) { $lezioni_nuove[] = get_the_title( $l ); }
		}
	}

	$corsi_in_arrivo = array();
	if ( function_exists( 'gs_cal_corsi' ) ) {
		$tra_14 = current_time( 'timestamp' ) + ( 14 * DAY_IN_SECONDS );
		foreach ( gs_cal_corsi( true ) as $c ) {
			$data = get_post_meta( $c->ID, 'gs_data', true );
			$ts   = $data ? strtotime( $data ) : false;
			if ( $ts && $ts <= $tra_14 ) {
				$corsi_in_arrivo[] = get_the_title( $c ) . ' (' . date_i18n( 'j F', $ts ) . ')';
			}
		}
	}

	$ha_novita_generali = $ricette_nuove || $lezioni_nuove || $corsi_in_arrivo;

	$blocchi_generali = '';
	if ( $ricette_nuove ) {
		$blocchi_generali .= "📖 NUOVE RICETTE NEL RICETTARIO\n";
		foreach ( $ricette_nuove as $t ) { $blocchi_generali .= "• " . $t . "\n"; }
		$blocchi_generali .= "\n";
	}
	if ( $lezioni_nuove ) {
		$blocchi_generali .= "🎬 NUOVE LEZIONI VIDEO\n";
		foreach ( $lezioni_nuove as $t ) { $blocchi_generali .= "• " . $t . "\n"; }
		$blocchi_generali .= "\n";
	}
	if ( $corsi_in_arrivo ) {
		$blocchi_generali .= "📅 CORSI IN ARRIVO\n";
		foreach ( $corsi_in_arrivo as $t ) { $blocchi_generali .= "• " . $t . "\n"; }
		$blocchi_generali .= "\n";
	}

	$destinatarie = function_exists( 'gs_sez_sfogline' ) ? gs_sez_sfogline() : array();
	foreach ( $destinatarie as $u ) {
		// Una sfoglina con l'abbonamento scaduto non può aprire niente di
		// quello che il digest annuncia (ricette, lezioni, corsi): senza
		// questo controllo continuava a riceverlo ogni lunedì e per sempre,
		// un elenco di cose che non può usare (segnalato 26/08/2026).
		if ( function_exists( 'gs_abbonamento_scaduto' ) && gs_abbonamento_scaduto( $u->ID ) ) {
			continue;
		}
		$quasi = function_exists( 'gs_percorso_quasi_completo_per' ) ? gs_percorso_quasi_completo_per( $u->ID ) : false;
		if ( ! $ha_novita_generali && ! $quasi ) {
			continue; // niente di nuovo per questa sfoglina, niente email
		}

		$corpo = sprintf( "Ciao %s,\n\necco le novità della settimana all'Accademia della Sfoglia:\n\n", $u->display_name );
		$corpo .= $blocchi_generali;
		if ( $quasi ) {
			$corpo .= "🗺️ CI SEI QUASI!\n";
			$corpo .= "Ti manca una sola lezione per completare il percorso «" . $quasi['percorso'] . "»: \"" . $quasi['lezione'] . "\". Guardala per sbloccare il percorso successivo!\n\n";
		}
		$corpo .= "A presto,\nAccademia della Sfoglia";

		$oggetto = 'Le novità della settimana — Accademia della Sfoglia';
		if ( function_exists( 'gs_mail_progetto' ) ) {
			gs_mail_progetto( $u->ID, 'digest', $oggetto, $corpo );
		} elseif ( $u->user_email ) {
			wp_mail( $u->user_email, $oggetto, $corpo );
		}
	}
}

/**
 * Quando un Ingrediente Segreto viene pubblicato, invia la push.
 */
add_action( 'transition_post_status', 'gs_push_on_ingrediente', 10, 3 );

function gs_push_on_ingrediente( $new_status, $old_status, $post ) {
	if ( 'gs_ingrediente' !== $post->post_type ) {
		return;
	}
	if ( 'publish' === $new_status && 'publish' !== $old_status ) {
		$page_id = (int) get_option( 'gs_page_sfida' );
		$url     = $page_id ? get_permalink( $page_id ) : home_url();
		gs_send_push(
			'🔔 Ingrediente Segreto svelato!',
			sprintf( 'L\'ingrediente della settimana è: %s', get_the_title( $post ) ),
			$url
		);
	}
}
