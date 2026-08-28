<?php
/**
 * stato-generale.php — pagina di riepilogo con TUTTI i servizi/sezioni del
 * plugin, attivi o spenti in un colpo d'occhio, con la possibilità di
 * accenderli/spegnerli direttamente da qui (un clic, salvataggio immediato,
 * niente pulsante "Salva" in fondo a una tabella lunghissima) — richiesto da
 * Ennio il 16/08/2026: "una pagina intera con tutto lo stato generale di
 * tutti i servizi se attivati o non attivati, con la possibilità di
 * attivarli e disattivarli direttamente da lì".
 *
 * Riusa i dati già esistenti (gs_sez_registry() in sezioni.php per le
 * sezioni con pagina/pannello, le impostazioni dei Caroselli per il Nastro,
 * gs_blackout_attivo() per il Gaming) — non introduce un nuovo sistema di
 * permessi, solo un altro modo, più rapido, di vedere e cambiare quello che
 * già c'è.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gs_pannello_stato_generale() {
	if ( ! gs_can_manage() ) { return; }

	echo gs_box_open( '🗂️ Stato Generale — tutti i servizi in un colpo d\'occhio', '', 'gs-box-stato-generale' );
	echo '<p class="gs-hint">Ogni interruttore qui sotto agisce SUBITO, senza bisogno di un pulsante "Salva": un clic e il servizio si accende o si spegne davvero. È lo stesso identico interruttore che trovi nel pannello dedicato di ognuno — qui sono solo tutti insieme, per un controllo veloce.</p>';

	// --- Interruttori principali: gli unici due che non vivono nel registro
	// delle sezioni (sezioni.php), perché non hanno una pagina pubblica ma
	// solo un'impostazione dedicata già usata altrove nel plugin.
	$cfg_nastro = function_exists( 'gs_nastro_impostazioni' ) ? gs_nastro_impostazioni() : array( 'nastro_attivo' => false );
	$blackout   = function_exists( 'gs_blackout_attivo' ) && gs_blackout_attivo();

	// Sfogline online adesso (punto 14, Ennio 21/08/2026): solo per chi
	// gestisce, mai per le sfogline — in cima, ben visibile.
	$online = function_exists( 'gs_conta_online' ) ? gs_conta_online() : 0;
	echo '<div class="gs-stato-principali">';
	echo '<p class="gs-stato-riga gs-stato-riga-grande" style="opacity:.85"><span class="gs-stato-nome">🟢 ' . (int) $online . ' sfogline online adesso (ultimi 5 minuti)</span></p>';

	// Fotografia della comunità (richiesta da Ennio, 22/08/2026). Nasce da una
	// domanda rimasta senza risposta per due giri di correzioni: "quante
	// sfogline vere ci sono davvero?". Quattro numeri invece di uno perché
	// ognuno risponde a una domanda diversa già emersa: quante sono, quante
	// compaiono nel Nastro, quante sono ancora vive, e quante stanno per
	// vincere il Buono alla prossima chiusura.
	//
	// NON è messo in cache, di proposito: è un numero che si guarda per
	// decidere se agire — stessa regola della Torre di controllo — e il
	// pannello lo apre solo chi gestisce. Il costo è comunque quasi nullo:
	// gs_sez_sfogline() ha una cache per richiesta ed è già stata chiamata da
	// control-panel.php nella stessa pagina, e la get_users() che c'è dentro
	// ha già caricato in memoria i meta di tutte le sfogline — le letture qui
	// sotto non tornano sul database.
	$sfogline_tutte = function_exists( 'gs_sez_sfogline' ) ? gs_sez_sfogline() : array();
	$soglia_buono   = 2500;
	$limite_vive    = current_time( 'timestamp' ) - ( 30 * DAY_IN_SECONDS );
	$n_vetrina = 0;
	$n_vive    = 0;
	$n_soglia  = 0;

	foreach ( $sfogline_tutte as $u ) {
		if ( function_exists( 'gs_vetrina_disponibile' ) && gs_vetrina_disponibile( $u->ID ) ) {
			$n_vetrina++;
		}
		$ultimo = get_user_meta( $u->ID, 'gs_ultimo_accesso', true );
		if ( $ultimo && strtotime( $ultimo ) >= $limite_vive ) {
			$n_vive++;
		}
		if ( function_exists( 'gs_get_month_points' ) && gs_get_month_points( $u->ID ) >= $soglia_buono ) {
			$n_soglia++;
		}
	}

	echo '<p class="gs-stato-riga gs-stato-riga-grande" style="opacity:.85"><span class="gs-stato-nome">👭 ' . count( $sfogline_tutte ) . ' sfogline attive in tutto</span></p>';
	echo '<p class="gs-stato-riga gs-stato-riga-grande" style="opacity:.85"><span class="gs-stato-nome">🎗️ ' . (int) $n_vetrina . ' con la Vetrina accesa — sono quelle che compaiono nel Nastro</span></p>';
	echo '<p class="gs-stato-riga gs-stato-riga-grande" style="opacity:.85"><span class="gs-stato-nome">📅 ' . (int) $n_vive . ' si sono collegate negli ultimi 30 giorni</span></p>';
	echo '<p class="gs-stato-riga gs-stato-riga-grande" style="opacity:.85"><span class="gs-stato-nome">🎁 ' . (int) $n_soglia . ' sopra i ' . (int) $soglia_buono . ' punti questo mese — vincono il Buono Sfoglia alla chiusura</span></p>';

	// Diagnostica Buono Sfoglia (Ennio, 23/08/2026, dopo l'emergenza della
	// chiusura di luglio): a colpo d'occhio, se e quale mese risulta già
	// chiuso e quante sfogline sono state toccate — utile per controllare
	// che tutto sia andato come previsto senza dover leggere il database
	// a mano.
	$mese_chiuso = (string) get_option( 'gs_buono_sfoglia_mese_chiuso', '' );
	if ( '' === $mese_chiuso ) {
		echo '<p class="gs-stato-riga gs-stato-riga-grande" style="opacity:.85"><span class="gs-stato-nome">🎁 Buono Sfoglia: nessun mese ancora chiuso</span></p>';
	} else {
		$n_toccate = 0;
		foreach ( get_users( array( 'fields' => 'ID' ) ) as $uid_check ) {
			if ( get_user_meta( (int) $uid_check, 'gs_buono_mese_' . $mese_chiuso, true ) ) {
				$n_toccate++;
			}
		}
		$etichetta_mese = function_exists( 'gs_buono_sfoglia_mese_label' ) ? gs_buono_sfoglia_mese_label( $mese_chiuso ) : $mese_chiuso;
		echo '<p class="gs-stato-riga gs-stato-riga-grande" style="opacity:.85"><span class="gs-stato-nome">🎁 Buono Sfoglia: ultimo mese chiuso — ' . esc_html( $etichetta_mese ) . ' (' . (int) $n_toccate . ' sfogline toccate)</span></p>';
		if ( $n_toccate > 0 ) {
			echo '<p class="gs-stato-riga gs-stato-riga-grande" style="opacity:.85">'
				. '<button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-buono-pulisci-messaggi-btn" data-mese="' . esc_attr( $mese_chiuso ) . '">🧹 Rimuovi dalle caselle i messaggi "Il tuo resoconto" di questo mese</button> '
				. '<span class="gs-buono-pulisci-messaggi-msg gs-richiesta-esito"></span>'
				. '</p>';
		}
	}
	echo '<label class="gs-stato-riga gs-stato-riga-grande"><input type="checkbox" class="gs-stato-nastro-toggle" ' . checked( ! empty( $cfg_nastro['nastro_attivo'] ), true, false ) . '> <span class="gs-stato-nome">🎗️ Nastro fisso sotto il menu</span></label>';
	echo '<label class="gs-stato-riga gs-stato-riga-grande"><input type="checkbox" class="gs-stato-blackout-toggle" ' . checked( ! $blackout, true, false ) . '> <span class="gs-stato-nome">🌙 Gaming attivo (spento = tutto oscurato per le sfogline)</span></label>';
	$percorso_mensile = function_exists( 'gs_percorso_mensile_attivo' ) && gs_percorso_mensile_attivo();
	echo '<label class="gs-stato-riga gs-stato-riga-grande"><input type="checkbox" class="gs-stato-percorso-mensile-toggle" ' . checked( $percorso_mensile, true, false ) . '> <span class="gs-stato-nome">🏁 Gara mensile del Buono Sfoglia in corso (spenta = nessun resoconto di fine mese)</span></label>';
	echo '<p class="gs-hint" style="margin:-4px 0 10px 26px">Accendila solo quando la gara parte davvero. Finché è spenta nessuna sfoglina riceve il resoconto di fine mese, e il primo resoconto vero sarà quello del primo mese giocato per intero.</p>';
	// Voce informativa, senza interruttore: la Regia è uno strumento SOLO per
	// chi gestisce, non ha una pagina pubblica da nascondere/mostrare alle
	// sfogline come le altre righe qui sopra — un interruttore sarebbe finto.
	// Serve solo a trovarla subito da qui (richiesto da Ennio, 18/08/2026).
	echo '<p class="gs-stato-riga gs-stato-riga-grande" style="opacity:.85"><span class="gs-stato-nome">🎯 Lista degli Iscritti ai Corsi — solo per chi gestisce, nessuna pagina pubblica</span></p>';
	echo '</div>';

	// --- Tutte le sezioni del registro, raggruppate per "zona" del pannello
	// (stesso identico registro usato da "Visibilità delle sezioni e
	// permessi" — un solo interruttore vale per entrambe le pagine).
	$reg    = function_exists( 'gs_sez_registry' ) ? gs_sez_registry() : array();
	$hidden = function_exists( 'gs_sez_hidden_map' ) ? gs_sez_hidden_map() : array();
	$n_tot  = count( $reg );
	$n_vis  = 0;
	foreach ( $reg as $key => $s ) { if ( empty( $hidden[ $key ] ) ) { $n_vis++; } }

	echo '<p style="margin-top:18px"><strong>' . (int) $n_vis . ' su ' . (int) $n_tot . ' sezioni accese.</strong></p>';
	echo '<input type="text" class="gs-cerca-input" data-target=".gs-stato-griglia" placeholder="🔍 Cerca un servizio…" style="width:100%;max-width:320px;margin-bottom:10px">';
	echo '<div class="gs-stato-griglia">';
	foreach ( $reg as $key => $s ) {
		$is_vis = empty( $hidden[ $key ] );
		echo '<label class="gs-stato-riga" data-nome="' . esc_attr( mb_strtolower( $s['label'] ) ) . '"><input type="checkbox" class="gs-stato-sez-toggle" data-key="' . esc_attr( $key ) . '" ' . checked( $is_vis, true, false ) . '> <span class="gs-stato-nome">' . esc_html( $s['label'] ) . '</span></label>';
	}
	echo '</div>';
	echo '<span class="gs-stato-msg gs-richiesta-esito"></span>';

	echo gs_box_close();
}

/** Accende/spegne UNA sola sezione all'istante, senza toccare collaboratori o "nascondi a" già salvati per le altre. */
add_action( 'wp_ajax_gs_stato_sez_toggle', 'gs_ajax_stato_sez_toggle' );
function gs_ajax_stato_sez_toggle() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( array( 'message' => 'Solo il titolare può cambiare questi permessi.' ) ); }
	$key = isset( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : '';
	$reg = function_exists( 'gs_sez_registry' ) ? gs_sez_registry() : array();
	if ( ! isset( $reg[ $key ] ) ) { wp_send_json_error( array( 'message' => 'Sezione non valida.' ) ); }

	$attivo = ! empty( $_POST['attivo'] );
	$s      = gs_settings();
	$hidden = isset( $s['sez_hidden'] ) && is_array( $s['sez_hidden'] ) ? $s['sez_hidden'] : array();
	if ( $attivo ) {
		unset( $hidden[ $key ] );
	} else {
		$hidden[ $key ] = true;
	}
	$s['sez_hidden'] = $hidden;
	update_option( GS_OPTION, $s );

	wp_send_json_success( array( 'message' => $attivo ? 'Acceso.' : 'Spento.' ) );
}

/**
 * Accende/spegne la gara mensile del Buono Sfoglia all'istante.
 *
 * Deve poterlo fare anche Rina Poletti, non solo il titolare (Ennio,
 * 23/08/2026: "lo decido io se parte o la Rina Poletti") — quindi
 * gs_can_manage(), come il Nastro, e non manage_options.
 */
add_action( 'wp_ajax_gs_stato_percorso_mensile_toggle', 'gs_ajax_stato_percorso_mensile_toggle' );
function gs_ajax_stato_percorso_mensile_toggle() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$attivo = ! empty( $_POST['attivo'] );
	$s = gs_settings();
	if ( ! isset( $s['features'] ) || ! is_array( $s['features'] ) ) { $s['features'] = array(); }
	$s['features']['percorso_mensile'] = $attivo ? 1 : 0;
	update_option( GS_OPTION, $s );

	// All'accensione, allinea SUBITO il segnaposto al mese appena finito.
	// Senza questa riga c'è una finestra vera: accendendo il 1° del mese
	// prima che il cron di quel giorno sia girato, il primo giro troverebbe
	// il segnaposto fermo al mese prima e chiuderebbe un mese in cui la gara
	// non c'era ancora — mandando a tutte il resoconto sbagliato, esattamente
	// com'è successo con luglio 2026. Trovato provando l'interruttore su
	// guru2 il 23/08/2026.
	$mese_appena_finito = date( 'Y-m', strtotime( 'first day of last month', current_time( 'timestamp' ) ) );
	if ( $attivo ) {
		update_option( 'gs_buono_sfoglia_mese_chiuso', $mese_appena_finito );
	}

	wp_send_json_success( array(
		'message' => $attivo
			? 'Gara mensile accesa. Il primo resoconto arriverà alla fine del primo mese giocato per intero.'
			: 'Gara mensile spenta: nessun resoconto di fine mese.',
	) );
}

/** Accende/spegne il Nastro fisso all'istante, senza toccare le altre impostazioni dei Caroselli. */
add_action( 'wp_ajax_gs_stato_nastro_toggle', 'gs_ajax_stato_nastro_toggle' );
function gs_ajax_stato_nastro_toggle() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$attivo = ! empty( $_POST['attivo'] );
	$s = gs_settings();
	if ( ! isset( $s['caroselli'] ) || ! is_array( $s['caroselli'] ) ) { $s['caroselli'] = array(); }
	$s['caroselli']['nastro_attivo'] = $attivo ? 1 : 0;
	update_option( GS_OPTION, $s );
	wp_send_json_success( array( 'message' => $attivo ? 'Nastro acceso.' : 'Nastro spento.' ) );
}
