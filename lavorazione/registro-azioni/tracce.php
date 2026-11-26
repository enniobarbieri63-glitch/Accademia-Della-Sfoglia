<?php
/**
 * tracce.php — Il registro delle azioni del Pannello di Controllo.
 *
 * Richiesta di Ennio (16/10/2026), nata da una domanda a cui non si poteva
 * rispondere: il Blackout era acceso, lui l'ha spento, e voleva sapere chi
 * l'avesse fatto e quando. Non si poteva sapere. Le impostazioni stanno in
 * una riga sola delle opzioni di WordPress, che viene sovrascritta: il valore
 * di prima non resta, e WordPress non mette una data sulle opzioni. Da qui in
 * avanti sì.
 *
 * Come è fatto, e perché così:
 *
 *  1. NESSUNA TABELLA NUOVA. Una voce nelle opzioni, 'gs_tracce', con le
 *     ultime GS_TRACCE_MAX azioni. La più vecchia esce quando entra la nuova.
 *
 *  2. NON È AUTOLOAD. update_option() viene chiamata col terzo parametro a
 *     false, quindi questa riga NON entra nella query che WordPress fa a ogni
 *     pagina per caricare le opzioni: si legge solo quando apri il registro.
 *
 *  3. SI SCRIVE DOPO, NON PRIMA. gs_traccia() si chiama quando l'azione è
 *     riuscita. Un tentativo fallito non finisce nel registro.
 *
 *  4. NON COPRE TUTTO, E LO DICE. Il pannello ha circa 385 comandi, e la
 *     maggioranza sono cancellazioni di voci di contenuto: segnarle tutte
 *     farebbe un registro illeggibile.
 *
 *  5. IL REGISTRO NON SI PUÒ RISCRIVERE. Si può svuotare — solo un
 *     amministratore vero — e lo svuotamento è la prima voce del registro nuovo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const GS_TRACCE_OPZIONE = 'gs_tracce';
const GS_TRACCE_MAX     = 400;

// -----------------------------------------------------------------------------
// Scrivere
// -----------------------------------------------------------------------------

/**
 * Segna un'azione nel registro. Da chiamare quando l'azione è GIÀ riuscita.
 * Non torna niente e non può far fallire l'azione.
 *
 * @param string $azione    Cosa è stato fatto, leggibile: 'Blackout acceso'.
 * @param string $dettaglio Su chi o su cosa, e da quale valore a quale.
 * @param int    $su_chi    ID della sfoglina interessata, 0 se non c'entra.
 */
function gs_traccia( $azione, $dettaglio = '', $su_chi = 0 ) {
	$azione = trim( wp_strip_all_tags( (string) $azione ) );
	if ( '' === $azione ) {
		return;
	}

	$uid  = get_current_user_id();
	$nome = '';
	if ( $uid ) {
		$u    = get_userdata( $uid );
		$nome = $u ? $u->display_name : '';
	}
	// Senza utente collegato l'azione viene dal cron o dalla riga di comando.
	if ( '' === $nome ) {
		$nome = $uid ? ( 'utente ' . (int) $uid ) : 'automatico';
	}

	$righe   = gs_tracce_righe();
	$righe[] = array(
		't' => (int) current_time( 'timestamp' ),
		'u' => (int) $uid,
		'n' => $nome,
		'a' => mb_substr( $azione, 0, 120 ),
		'd' => mb_substr( trim( wp_strip_all_tags( (string) $dettaglio ) ), 0, 300 ),
		'o' => (int) $su_chi,
	);

	if ( count( $righe ) > GS_TRACCE_MAX ) {
		$righe = array_slice( $righe, - GS_TRACCE_MAX );
	}

	// Terzo parametro false: fuori dall'autoload. Vedi il punto 2 in testa.
	update_option( GS_TRACCE_OPZIONE, $righe, false );
}

/**
 * Comodità per gli interruttori: scrive «X acceso» o «X spento».
 *
 * @param string $cosa   Nome dell'interruttore, come lo legge una persona.
 * @param bool   $adesso Il valore NUOVO, quello appena salvato.
 */
function gs_traccia_interruttore( $cosa, $adesso, $dettaglio = '' ) {
	gs_traccia( $cosa . ( $adesso ? ' acceso' : ' spento' ), $dettaglio );
}

// -----------------------------------------------------------------------------
// Leggere
// -----------------------------------------------------------------------------

/** Le righe grezze, dalla più vecchia alla più recente. */
function gs_tracce_righe() {
	$r = get_option( GS_TRACCE_OPZIONE, array() );
	return is_array( $r ) ? $r : array();
}

/** Le righe dalla più recente, al massimo $quante (0 = tutte). */
function gs_tracce_elenco( $quante = 0 ) {
	$righe = array_reverse( gs_tracce_righe() );
	return $quante > 0 ? array_slice( $righe, 0, (int) $quante ) : $righe;
}

/**
 * L'ultima volta che un'azione il cui testo contiene $pezzo è stata fatta.
 *
 * @return array|null La riga, o null se di quell'azione non c'è traccia.
 */
function gs_traccia_ultima( $pezzo ) {
	foreach ( gs_tracce_elenco() as $r ) {
		if ( false !== mb_stripos( $r['a'], $pezzo ) ) {
			return $r;
		}
	}
	return null;
}

/**
 * La riga pronta da stampare sotto un interruttore:
 * «Ultima modifica: Blackout spento — Ennio, 16/10/2026 alle 11:42».
 * Stringa vuota se di quell'interruttore non c'è ancora traccia.
 */
function gs_traccia_riga_html( $pezzo ) {
	$r = gs_traccia_ultima( $pezzo );
	if ( ! $r ) {
		return '';
	}
	return '<p class="gs-hint gs-traccia-riga">🕓 Ultima modifica: <strong>' . esc_html( $r['a'] ) . '</strong> — '
		. esc_html( $r['n'] ) . ', ' . esc_html( gs_it_data( 'd/m/Y', $r['t'] ) )
		. ' alle ' . esc_html( gs_it_data( 'H:i', $r['t'] ) ) . '</p>';
}

// -----------------------------------------------------------------------------
// Le tracce che si scrivono da sole
// -----------------------------------------------------------------------------

/** Attivazione e disattivazione del plugin dal pannello dei Plugin. */
function gs_traccia_attivazione() {
	gs_traccia( 'Plugin attivato', 'Versione ' . GS_VERSION );
}
function gs_traccia_disattivazione() {
	gs_traccia( 'Plugin disattivato', 'Versione ' . GS_VERSION );
}

/**
 * Aggiornamento di versione: si confronta la versione dell'ultimo giro.
 * 'gs_tracce_versione' è una stringa corta, resta in autoload di proposito.
 */
add_action( 'init', 'gs_traccia_versione_nuova', 1 );
function gs_traccia_versione_nuova() {
	$vista = get_option( 'gs_tracce_versione', '' );
	if ( GS_VERSION === $vista ) {
		return;
	}
	update_option( 'gs_tracce_versione', GS_VERSION );
	// Al primo giro in assoluto non si scrive «aggiornato»: non si sa da cosa.
	if ( '' === $vista ) {
		gs_traccia( 'Registro delle azioni avviato', 'Versione ' . GS_VERSION );
		return;
	}
	gs_traccia( 'Plugin aggiornato', 'Dalla versione ' . $vista . ' alla ' . GS_VERSION );
}

// -----------------------------------------------------------------------------
// Il riquadro nel Pannello di Controllo
// -----------------------------------------------------------------------------

function gs_pannello_tracce() {
	if ( ! function_exists( 'gs_can_manage' ) || ! gs_can_manage() ) {
		return;
	}
	$righe = gs_tracce_elenco();

	echo gs_box_open( '🕓 Registro delle Azioni', '', 'gs-box-tracce' );

	echo '<p class="gs-hint">Chi ha fatto cosa, e quando, dal Pannello di Controllo. '
		. 'Le ultime ' . (int) GS_TRACCE_MAX . ' azioni: la più vecchia esce quando entra la nuova. '
		. 'Ci sono gli interruttori generali, i bonifici, i token, le iscritte, i permessi, '
		. 'le mail partite e il Reset — non le piccole modifiche ai contenuti.</p>';

	if ( ! $righe ) {
		echo '<p class="gs-hint"><em>Il registro è vuoto: parte da adesso. Quello che è stato '
			. 'fatto prima di questa versione non c\'è, perché non veniva segnato da nessuno.</em></p>';
		echo gs_box_close();
		return;
	}

	echo '<p><input type="search" class="gs-tracce-cerca" placeholder="Cerca un nome o un\'azione…" style="max-width:320px"> '
		. '<span class="gs-hint gs-tracce-quante">' . count( $righe ) . ' azioni</span></p>';

	echo '<div class="gs-tabella-scroll"><table class="gs-tabella gs-tracce-tab"><thead><tr>'
		. '<th>Quando</th><th>Chi</th><th>Cosa</th><th>Dettaglio</th>'
		. '</tr></thead><tbody>';

	$oggi  = gs_it_data( 'd/m/Y' );
	$ieri  = gs_it_data( 'd/m/Y', (int) current_time( 'timestamp' ) - DAY_IN_SECONDS );

	foreach ( $righe as $r ) {
		$giorno = gs_it_data( 'd/m/Y', $r['t'] );
		$quando = $giorno === $oggi ? 'oggi' : ( $giorno === $ieri ? 'ieri' : $giorno );
		echo '<tr><td style="white-space:nowrap">' . esc_html( $quando ) . ' <span class="gs-hint">'
			. esc_html( gs_it_data( 'H:i', $r['t'] ) ) . '</span></td>'
			. '<td>' . esc_html( $r['n'] ) . '</td>'
			. '<td><strong>' . esc_html( $r['a'] ) . '</strong></td>'
			. '<td>' . ( '' !== $r['d'] ? esc_html( $r['d'] ) : '<span class="gs-hint">—</span>' ) . '</td></tr>';
	}

	echo '</tbody></table></div>';

	if ( current_user_can( 'manage_options' ) ) {
		echo '<p style="margin-top:14px"><button type="button" class="gs-btn gs-btn-rosso gs-tracce-svuota">🗑️ Svuota il registro</button> '
			. '<span class="gs-hint">Lo svuotamento diventa la prima voce del registro nuovo: non si può cancellare una riga sola.</span></p>';
	}

	echo gs_box_close();
}

// -----------------------------------------------------------------------------
// Svuotamento
// -----------------------------------------------------------------------------

add_action( 'wp_ajax_gs_tracce_svuota', 'gs_ajax_tracce_svuota' );
function gs_ajax_tracce_svuota() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	// Più stretto di gs_can_manage(): un collaboratore non svuota il registro.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Solo un amministratore può svuotare il registro.' ) );
	}
	$quante = count( gs_tracce_righe() );
	update_option( GS_TRACCE_OPZIONE, array(), false );
	gs_traccia( 'Registro delle azioni svuotato', $quante . ' righe cancellate' );
	wp_send_json_success( array( 'message' => 'Registro svuotato.' ) );
}
