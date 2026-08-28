<?php
/**
 * promemoria.php — Promemoria giornaliero opt-in.
 *
 * Estensione del modulo notifiche (notifiche-pref.php, categoria
 * "promemoria"): a differenza delle altre categorie — decise dal titolare
 * per ogni sfoglina — questa è la sfoglina stessa a decidere se vuole un
 * avviso, e a che ora. Se lo attiva, ogni ora il cron controlla se è
 * arrivata la sua ora scelta e se non si è ancora collegata oggi: solo in
 * quel caso le manda il promemoria, con lo stesso canale email/interno
 * scelto dal titolare per la categoria "Promemoria giornaliero".
 *
 * "Collegata oggi" = ha usato il sito da loggata almeno una volta oggi
 * (non solo il modulo di login: anche restando connessa con un cookie
 * "ricordami" conta), registrato in gs_ultimo_accesso a ogni richiesta.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Traccia l'ultimo giorno di attività (una sola scrittura al giorno per utente)
// -----------------------------------------------------------------------------
add_action( 'init', 'gs_promemoria_registra_attivita', 20 );
function gs_promemoria_registra_attivita() {
	if ( ! is_user_logged_in() ) { return; }
	$uid  = get_current_user_id();
	$oggi = date( 'Y-m-d', current_time( 'timestamp' ) );
	if ( get_user_meta( $uid, 'gs_ultimo_accesso', true ) !== $oggi ) {
		update_user_meta( $uid, 'gs_ultimo_accesso', $oggi );
	}

	// Timestamp preciso per il contatore "online adesso" del punto 14
	// (Ennio, 21/08/2026) — a differenza di gs_ultimo_accesso sopra (una
	// scrittura al giorno, non basta per sapere chi è online ORA), ma con
	// lo stesso spirito: throttlato a una scrittura ogni 2 minuti per
	// utente, non a ogni richiesta di pagina.
	$adesso  = time();
	$ultimo  = (int) get_user_meta( $uid, 'gs_ultimo_accesso_timestamp', true );
	if ( ( $adesso - $ultimo ) > 120 ) {
		update_user_meta( $uid, 'gs_ultimo_accesso_timestamp', $adesso );
	}
}

/** True se la sfoglina ha già usato il sito oggi. */
function gs_si_e_collegata_oggi( $uid ) {
	$oggi = date( 'Y-m-d', current_time( 'timestamp' ) );
	return get_user_meta( $uid, 'gs_ultimo_accesso', true ) === $oggi;
}

/**
 * Numero di sfogline "online adesso" (attive negli ultimi 5 minuti) — solo
 * per chi gestisce, vedi gs_pannello_stato_generale(). Query mirata sul
 * meta invece di caricare tutti gli utenti in PHP.
 */
function gs_conta_online() {
	$soglia = time() - 5 * MINUTE_IN_SECONDS;
	$q = new WP_User_Query( array(
		'meta_query'  => array(
			array(
				'key'     => 'gs_ultimo_accesso_timestamp',
				'value'   => $soglia,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			),
		),
		'fields'      => 'ID',
		'count_total' => true,
		'number'      => 1,
	) );
	return (int) $q->get_total();
}

// -----------------------------------------------------------------------------
// Preferenza della sfoglina: ora scelta (0-23), o '' se disattivato
// -----------------------------------------------------------------------------
function gs_promemoria_ora( $uid ) {
	$ora = get_user_meta( $uid, 'gs_promemoria_ora', true );
	return ( '' !== $ora && is_numeric( $ora ) && $ora >= 0 && $ora <= 23 ) ? (int) $ora : null;
}

add_action( 'wp_ajax_gs_promemoria_salva', 'gs_ajax_promemoria_salva' );
function gs_ajax_promemoria_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }

	$ora = isset( $_POST['ora'] ) ? sanitize_text_field( wp_unslash( $_POST['ora'] ) ) : '';
	if ( '' === $ora ) {
		update_user_meta( $uid, 'gs_promemoria_ora', '' );
		wp_send_json_success( array( 'message' => 'Promemoria disattivato.' ) );
	}
	if ( ! is_numeric( $ora ) || $ora < 0 || $ora > 23 ) { wp_send_json_error( array( 'message' => 'Ora non valida.' ) ); }
	update_user_meta( $uid, 'gs_promemoria_ora', (int) $ora );
	wp_send_json_success( array( 'message' => 'Promemoria attivato: ti avviseremo alle ' . (int) $ora . ':00 se non ti sei ancora collegata.' ) );
}

// -----------------------------------------------------------------------------
// [gs_promemoria] — piccolo modulo di preferenza, lato sfoglina
// -----------------------------------------------------------------------------
add_shortcode( 'gs_promemoria', 'gs_sc_promemoria' );
function gs_sc_promemoria() {
	if ( $g = gs_gate_riservato() ) { return $g; }

	$out = gs_box_open( '⏰ Promemoria giornaliero' );
	$out .= gs_sezione_aiuto( 'Se lo attivi, quando arriva l\'ora che scegli e non ti sei ancora collegata oggi, ti mandiamo un avviso (via email o come messaggio interno, secondo le tue preferenze di notifica). Se ti sei già collegata, non arriva nulla: non è un fastidio, è solo per non dimenticarsi.' );

	$uid    = get_current_user_id();
	$attuale = gs_promemoria_ora( $uid );

	$out .= '<form class="gs-form gs-form-promemoria" onsubmit="return false">';
	$out .= '<p><label>Avvisami se non mi sono ancora collegata, alle<br><select name="ora">';
	$out .= '<option value="">— Disattivato —</option>';
	for ( $h = 0; $h < 24; $h++ ) {
		$out .= '<option value="' . $h . '" ' . selected( $attuale, $h, false ) . '>' . sprintf( '%02d:00', $h ) . '</option>';
	}
	$out .= '</select></label></p>';
	$out .= '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-promemoria-salva">Salva</button> <span class="gs-promemoria-msg gs-richiesta-esito"></span></p>';
	$out .= '</form>';

	$out .= gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// Cron orario: chi ha scelto l'ora attuale e non si è ancora collegata oggi
// -----------------------------------------------------------------------------
add_action( 'gs_hourly_cron', 'gs_promemoria_controlla_tutte' );
function gs_promemoria_controlla_tutte() {
	$ora_attuale = (int) date( 'G', current_time( 'timestamp' ) );
	$oggi        = date( 'Y-m-d', current_time( 'timestamp' ) );

	$utenti = get_users( array( 'meta_key' => 'gs_promemoria_ora', 'meta_compare' => 'EXISTS', 'number' => -1 ) );
	foreach ( $utenti as $u ) {
		$ora = gs_promemoria_ora( $u->ID );
		if ( null === $ora || $ora !== $ora_attuale ) { continue; }
		if ( gs_si_e_collegata_oggi( $u->ID ) ) { continue; }
		if ( get_user_meta( $u->ID, 'gs_promemoria_inviato', true ) === $oggi ) { continue; } // già avvisata oggi (difesa da doppie esecuzioni del cron)

		gs_promemoria_invia( $u->ID );
		update_user_meta( $u->ID, 'gs_promemoria_inviato', $oggi );
	}
}

/** Manda il promemoria a una sfoglina: rispetta il canale email/interno scelto dal titolare per questa categoria. */
function gs_promemoria_invia( $uid ) {
	$u = get_userdata( $uid );
	if ( ! $u ) { return; }
	$oggetto = 'Non hai ancora fatto la tua mossa di oggi — Accademia della Sfoglia';
	$corpo   = "Ciao " . $u->display_name . ",\n\noggi non ti sei ancora collegata all'Accademia della Sfoglia: una lezione, una domanda del giorno, una ricetta, una parola sul Tavolo di Lavoro… quello che preferisci.\n\nSe non vuoi più ricevere questo avviso, puoi disattivarlo da \"Promemoria giornaliero\".\n\nAccademia della Sfoglia";
	if ( function_exists( 'gs_mail_progetto' ) ) {
		gs_mail_progetto( $uid, 'promemoria', $oggetto, $corpo );
	} elseif ( $u->user_email ) {
		wp_mail( $u->user_email, $oggetto, $corpo );
	}
}
