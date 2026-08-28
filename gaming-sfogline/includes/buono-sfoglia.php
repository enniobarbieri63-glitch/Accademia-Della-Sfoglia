<?php
/**
 * buono-sfoglia.php — Sistema mensile di gioco: Buono Sfoglia.
 *
 * Richiesta completa di Ennio (19-20/08/2026). I punti mensili (vedi
 * gs_get_month_points() in points.php, alimentati dallo stesso
 * gs_add_points() di sempre) sono un secondo conteggio parallelo, azzerato
 * ogni mese, separato dal totale vita natural durante (gs_points, quello
 * dei livelli — non si tocca). Deliberatamente NON integrato nel sistema
 * sconti già esistente (sconto-corsi.php): quello ha regole sue (livello
 * corso progressivo, reset al 24 dicembre, alimentato dai Premi per
 * Traguardo) — il Buono Sfoglia ha le sue, diverse, e mescolarli avrebbe
 * confuso due contabilità che rispondono a domande diverse.
 *
 * Regole del Buono Sfoglia:
 *  - Chi raggiunge 2500 punti in un mese vince, a fine mese, un Buono
 *    Sfoglia: 2,5% di sconto, cumulabile mese dopo mese (raggiungere di
 *    nuovo la soglia in un mese successivo aggiunge un altro 2,5%).
 *  - Va speso entro l'anno in corso, su un Corso Base o Avanzato che si
 *    svolge a gennaio dell'anno successivo. Chi non riesce a parteciparvi,
 *    per qualsiasi motivo, perde la possibilità di riutilizzarlo — nessuna
 *    nuova occasione futura (qui semplificato con una scadenza fissa al
 *    31 gennaio: l'aggancio a UNA prenotazione precisa del calendario, per
 *    sapere con certezza se ha partecipato o no, resta da costruire insieme
 *    a calendario.php quando serve).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Lettura del saldo e dello storico
// -----------------------------------------------------------------------------

/** Percentuale di Buono Sfoglia accumulata (0-100), a prescindere dal sistema sconti generico. */
function gs_buono_sfoglia_pct( $uid ) {
	$pct = (float) get_user_meta( (int) $uid, 'gs_buono_sfoglia_pct', true );
	return (float) max( 0, min( 100, $pct ) );
}

function gs_buono_sfoglia_log_aggiungi( $uid, $voce ) {
	$log          = get_user_meta( (int) $uid, 'gs_buono_sfoglia_log', true );
	$log          = is_array( $log ) ? $log : array();
	$voce['data'] = current_time( 'mysql' );
	$log[]        = $voce;
	if ( count( $log ) > 100 ) {
		$log = array_slice( $log, -100 );
	}
	update_user_meta( (int) $uid, 'gs_buono_sfoglia_log', $log );
}

function gs_buono_sfoglia_log_get( $uid ) {
	$log = get_user_meta( (int) $uid, 'gs_buono_sfoglia_log', true );
	return is_array( $log ) ? array_reverse( $log ) : array();
}

// -----------------------------------------------------------------------------
// Chiusura del mese: assegna il Buono Sfoglia a chi ha raggiunto la soglia
// -----------------------------------------------------------------------------

/**
 * Esegue la chiusura del mese indicato (default: il mese appena finito) per
 * tutte le sfogline vere: assegna il Buono Sfoglia a chi ha raggiunto la
 * soglia, manda il resoconto a tutte. Idempotente per mese (gs_option
 * gs_buono_sfoglia_mese_chiuso): se richiamata più volte per lo stesso mese
 * non assegna due volte.
 *
 * @param string $ym "AAAA-MM" del mese da chiudere. Default: il mese scorso.
 */
function gs_buono_sfoglia_chiudi_mese( $ym = null ) {
	if ( ! $ym ) {
		// "first day of last month" invece di "-1 month": il 31 marzo,
		// "-1 month" darebbe il 3 marzo (l'overflow di PHP quando il mese di
		// destinazione ha meno giorni), quindi il mese calcolato sarebbe
		// sbagliato. Con la chiusura che ora gira ogni giorno (non più solo
		// il giorno 1, vedi gs_buono_sfoglia_controlla_chiusura_mese) questo
		// caso può capitare davvero.
		$ym = date( 'Y-m', strtotime( 'first day of last month', current_time( 'timestamp' ) ) );
	}
	if ( get_option( 'gs_buono_sfoglia_mese_chiuso' ) === $ym ) {
		return 0; // già fatto per questo mese
	}

	$soglia    = 2500;
	$assegnati = 0;
	$mese_label = gs_buono_sfoglia_mese_label( $ym );

	foreach ( get_users( array( 'fields' => 'ID' ) ) as $uid ) {
		$uid = (int) $uid;
		if ( ! gs_e_sfoglina_vera( $uid ) ) {
			continue;
		}

		// Marcatore per singola sfoglina, scritto PRIMA di mandare messaggi e
		// assegnare la percentuale: se il ciclo muore a metà (troppe sfogline
		// per il tempo massimo di esecuzione, o il limite orario di posta
		// dell'hosting), la ripresa riparte da dove si era fermata invece che
		// da capo. Prima di questa riga, una ripresa rimandava le email già
		// mandate e sommava un secondo 2,5% a chi era già stato elaborato —
		// la protezione a fine funzione (gs_buono_sfoglia_mese_chiuso) non
		// bastava, perché veniva scritta solo se il ciclo arrivava in fondo.
		$chiave_fatto = 'gs_buono_mese_' . $ym;
		if ( get_user_meta( $uid, $chiave_fatto, true ) ) {
			continue; // già elaborata per questo mese
		}
		update_user_meta( $uid, $chiave_fatto, 1 );

		$punti_mese = gs_get_month_points( $uid, $ym );

		// Resoconto di fine mese a TUTTE, non solo a chi vince il Buono
		// (Ennio: "ogni sfoglina va avvisata" — su email/interno secondo le
		// sue preferenze, categoria "livelli", più il messaggio privato
		// dedicato sotto; il terzo canale, "dentro La Mia Sfoglia", è il
		// riquadro sempre visibile di gs_buono_sfoglia_box_html()).
		$vinto = $punti_mese >= $soglia;
		$oggetto_resoconto = 'Il tuo resoconto di ' . $mese_label . ' — Accademia della Sfoglia';
		$corpo_resoconto = "Ciao,\n\nA " . $mese_label . " hai totalizzato " . $punti_mese . " punti nel percorso del mese.\n\n"
			. ( $vinto
				? "Hai raggiunto la soglia di $soglia punti: hai vinto un Buono Sfoglia, 2,5% di sconto su un Corso Base o Avanzato di gennaio (si somma a quelli già accumulati)."
				: "Non hai raggiunto la soglia di $soglia punti per il Buono Sfoglia questo mese: mancavano " . max( 0, $soglia - $punti_mese ) . " punti. Il conteggio è ripartito da zero con il nuovo mese." )
			. "\n\nContinua così!\n\n— Accademia della Sfoglia";
		if ( function_exists( 'gs_mail_progetto' ) ) {
			gs_mail_progetto( $uid, 'livelli', $oggetto_resoconto, $corpo_resoconto );
		}
		// Messaggio privato dedicato, sempre (non passa dalle preferenze
		// email/interno: è il canale "messaggio privato sul sito" richiesto
		// esplicitamente da Ennio come terzo canale, distinto da quello
		// facoltativo di gs_mail_progetto sopra).
		if ( function_exists( 'gs_invia_messaggio' ) ) {
			gs_invia_messaggio( $uid, $oggetto_resoconto, $corpo_resoconto );
		}

		if ( ! $vinto ) {
			continue;
		}

		// --- Assegna il Buono Sfoglia --------------------------------------
		gs_buono_sfoglia_aggiungi( $uid, 2.5, $ym );
		$assegnati++;
	}

	update_option( 'gs_buono_sfoglia_mese_chiuso', $ym );
	return $assegnati;
}
add_action( 'gs_daily_cron', 'gs_buono_sfoglia_controlla_chiusura_mese' );
function gs_buono_sfoglia_controlla_chiusura_mese() {
	// Non più "solo il giorno 1": il cron di WordPress non è un vero cron
	// (gira solo quando qualcuno visita il sito) e può saltare quella
	// finestra — in quel caso il mese non si sarebbe più chiuso. Ogni giorno
	// si chiede: c'è un mese finito e non ancora chiuso?
	$mese_scorso = date( 'Y-m', strtotime( 'first day of last month', current_time( 'timestamp' ) ) );

	// Interruttore del percorso mensile (Ennio, 23/08/2026: "il gaming parte
	// il 1 ottobre"). Finché è spento il segnaposto viene tenuto aggiornato
	// al mese appena finito, ma non viene toccata nessuna sfoglina: così
	// all'accensione la prima chiusura vera è quella del primo mese davvero
	// giocato, e non arriva a nessuno un resoconto dei mesi in cui la gara
	// non c'era. È il motivo per cui esiste questo interruttore: era già
	// successo con luglio 2026, e senza questa riga si sarebbe ripetuto il
	// 1° settembre con agosto.
	if ( function_exists( 'gs_percorso_mensile_attivo' ) && ! gs_percorso_mensile_attivo() ) {
		update_option( 'gs_buono_sfoglia_mese_chiuso', $mese_scorso );
		return;
	}

	// Primo passaggio in assoluto: l'opzione non è mai stata scritta, perché
	// finora la chiusura girava solo il giorno 1 e dalla nascita del Buono
	// Sfoglia (19-20/08/2026) non è ancora passato nessun primo del mese.
	// Senza questa protezione la chiusura — ora quotidiana — proverebbe
	// SUBITO a chiudere il mese precedente, mandando a ogni sfoglina due
	// messaggi sul resoconto di un mese in cui il gioco mensile non
	// esisteva ancora. Stessa protezione già usata sulla scadenza annuale
	// in gs_buono_sfoglia_controlla_scadenza(): segna il mese scorso come
	// "già gestito" senza toccare nessuna sfoglina, così la prima chiusura
	// vera sarà quella del mese in cui il gioco è davvero esistito.
	if ( '' === (string) get_option( 'gs_buono_sfoglia_mese_chiuso', '' ) ) {
		update_option( 'gs_buono_sfoglia_mese_chiuso', $mese_scorso );
		return;
	}

	if ( get_option( 'gs_buono_sfoglia_mese_chiuso' ) === $mese_scorso ) {
		return;
	}
	gs_buono_sfoglia_chiudi_mese( $mese_scorso );
}

/** Etichetta leggibile di un "AAAA-MM", es. "2026-08" → "agosto 2026". */
function gs_buono_sfoglia_mese_label( $ym ) {
	$ts = strtotime( $ym . '-01' );
	return date_i18n( 'F Y', $ts );
}

/**
 * Aggiunge il Buono Sfoglia (percentuale + badge del mese + Aeroplanino) a
 * una sfoglina per il mese indicato. Separata da gs_buono_sfoglia_chiudi_mese()
 * così può essere richiamata anche a mano dal pannello, per correzioni.
 */
function gs_buono_sfoglia_aggiungi( $uid, $pct, $ym ) {
	$uid = (int) $uid;
	if ( ! $uid || $pct <= 0 ) {
		return false;
	}
	$nuovo = (float) min( 100, gs_buono_sfoglia_pct( $uid ) + $pct );
	update_user_meta( $uid, 'gs_buono_sfoglia_pct', $nuovo );
	gs_buono_sfoglia_log_aggiungi( $uid, array(
		'tipo'        => 'guadagno',
		'percentuale' => $pct,
		'mese'        => $ym,
	) );

	// Badge del mese (chiave unica per mese, così un Buono vinto più volte
	// in mesi diversi lascia più voci distinte nello storico badge — stesso
	// schema già usato per "Corso con Rina Poletti AAAA" in year-prize.php).
	if ( function_exists( 'gs_get_user_badges' ) ) {
		$badges    = gs_get_user_badges( $uid );
		$badge_key = 'buono_sfoglia_' . $ym;
		if ( ! in_array( $badge_key, $badges, true ) ) {
			$badges[] = $badge_key;
			update_user_meta( $uid, 'gs_badges', $badges );
			update_user_meta( $uid, 'gs_badge_label_' . $badge_key, '🎁 Buono Sfoglia — ' . gs_buono_sfoglia_mese_label( $ym ) );
		}
	}

	// Avviso Aeroplanino, come per ogni badge/Buono vinto (Ennio: "ogni
	// volta che una sfoglina vince un badge O un Buono Sfoglia").
	if ( function_exists( 'gs_accoda_volo' ) ) {
		gs_accoda_volo( $uid, '🎁 HAI VINTO UN BUONO SFOGLIA (+2,5%)!', function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_dashboard' ) : '' );
	}
	return true;
}

// -----------------------------------------------------------------------------
// Uso e scadenza
// -----------------------------------------------------------------------------

/** Il gestore conferma che la sfoglina ha davvero partecipato al corso di gennaio: consuma il Buono. */
function gs_buono_sfoglia_applica( $uid, $pren_id = 0 ) {
	$uid = (int) $uid;
	if ( ! $uid ) {
		return false;
	}
	$pct_usata = gs_buono_sfoglia_pct( $uid );
	update_user_meta( $uid, 'gs_buono_sfoglia_pct', 0 );
	gs_buono_sfoglia_log_aggiungi( $uid, array(
		'tipo'         => 'consumo',
		'percentuale'  => $pct_usata,
		'prenotazione' => (int) $pren_id,
	) );
	return true;
}
add_action( 'wp_ajax_gs_buono_sfoglia_applica', 'gs_ajax_buono_sfoglia_applica' );
function gs_ajax_buono_sfoglia_applica() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Permesso negato.' ) );
	}
	$uid = isset( $_POST['uid'] ) ? (int) $_POST['uid'] : 0;
	if ( ! $uid || ! get_userdata( $uid ) ) {
		wp_send_json_error( array( 'message' => 'Sfoglina non trovata.' ) );
	}
	$pren = isset( $_POST['pren'] ) ? (int) $_POST['pren'] : 0;
	gs_buono_sfoglia_applica( $uid, $pren );
	wp_send_json_success( array( 'message' => 'Buono Sfoglia segnato come usato.' ) );
}

/**
 * Scadenza: il Buono non usato entro gennaio si perde (Ennio: "chi non
 * riesce a partecipare... perde la possibilità di riutilizzarlo"). Qui
 * semplificato a una data fissa (1° febbraio) finché non è agganciato a
 * una prenotazione precisa del Calendario Corsi.
 */
add_action( 'gs_daily_cron', 'gs_buono_sfoglia_controlla_scadenza' );
function gs_buono_sfoglia_controlla_scadenza() {
	$oggi = current_time( 'timestamp' );
	$anno = date( 'Y', $oggi );

	// Primo passaggio dopo questo aggiornamento: l'opzione
	// gs_buono_sfoglia_scaduto_anno non è mai stata scritta (il Buono
	// Sfoglia è nato ad agosto 2026, dopo il 1° febbraio). Senza questa
	// protezione, il controllo sotto — non più limitato al solo 1° febbraio —
	// farebbe scadere SUBITO tutti i Buoni Sfoglia esistenti, a metà anno.
	// Segna l'anno in corso come già gestito: la prima scadenza vera sarà il
	// 1° febbraio prossimo.
	if ( '' === (string) get_option( 'gs_buono_sfoglia_scaduto_anno', '' ) && date( 'm-d', $oggi ) >= '02-01' ) {
		update_option( 'gs_buono_sfoglia_scaduto_anno', $anno );
		return;
	}

	// Non più "solo il 1° febbraio": da lì in poi, finché non è stato fatto
	// per quest'anno — così una giornata di cron saltata non salta la
	// scadenza per sempre (stesso motivo della chiusura del mese sopra).
	if ( date( 'm-d', $oggi ) < '02-01' ) {
		return;
	}
	if ( get_option( 'gs_buono_sfoglia_scaduto_anno' ) === $anno ) {
		return; // già fatto quest'anno
	}
	foreach ( get_users( array( 'fields' => 'ID' ) ) as $uid ) {
		$uid = (int) $uid;
		$pct = gs_buono_sfoglia_pct( $uid );
		if ( $pct > 0 ) {
			update_user_meta( $uid, 'gs_buono_sfoglia_pct', 0 );
			gs_buono_sfoglia_log_aggiungi( $uid, array(
				'tipo'        => 'scaduto',
				'percentuale' => $pct,
			) );
		}
	}
	update_option( 'gs_buono_sfoglia_scaduto_anno', $anno );
}

// -----------------------------------------------------------------------------
// Riquadro per "La Mia Sfoglia": saldo del Buono + punti del mese in corso
// -----------------------------------------------------------------------------
function gs_buono_sfoglia_box_html( $uid ) {
	$uid         = (int) $uid;
	$pct         = gs_buono_sfoglia_pct( $uid );
	$punti_mese  = gs_get_month_points( $uid );
	$soglia      = 2500;
	$mese_label  = gs_buono_sfoglia_mese_label( date( 'Y-m', current_time( 'timestamp' ) ) );

	$out  = gs_box_open( '🎁 Buono Sfoglia' );
	$out .= gs_sezione_aiuto( 'Raggiungi 2500 punti in un mese e vinci il Buono Sfoglia: 2,5% di sconto su un Corso Base o Avanzato di gennaio, cumulabile ogni mese in cui raggiungi di nuovo la soglia. Va speso entro l\'anno in corso: se non riesci a partecipare al corso di gennaio stabilito, per qualsiasi motivo, il Buono non è più riutilizzabile.' );

	$out .= '<p class="gs-hint">Punti di ' . esc_html( $mese_label ) . ': <strong>' . (int) $punti_mese . ' / ' . (int) $soglia . '</strong>' . ( $punti_mese >= $soglia ? ' — soglia raggiunta! 🎉' : '' ) . '</p>';

	if ( $pct > 0 ) {
		$out .= '<p class="gs-sconto-evidenza">🎁 Buono Sfoglia accumulato: <strong>' . number_format( $pct, 1 ) . '%</strong></p>';
		$out .= '<p class="gs-hint">Iscriviti a un Corso Base o Avanzato di gennaio e segnalo alla segreteria per usarlo.</p>';
	} else {
		$out .= '<p class="gs-hint">Nessun Buono Sfoglia accumulato al momento.</p>';
	}

	$log = gs_buono_sfoglia_log_get( $uid );
	if ( $log ) {
		$out .= '<details class="gs-sezione-aiuto"><summary>Storico Buono Sfoglia</summary><ul class="gs-todo-list">';
		foreach ( array_slice( $log, 0, 15 ) as $voce ) {
			$etichette = array( 'guadagno' => '➕ Guadagnato', 'consumo' => '✔ Usato', 'scaduto' => '⌛ Scaduto' );
			$tipo = isset( $etichette[ $voce['tipo'] ] ) ? $etichette[ $voce['tipo'] ] : $voce['tipo'];
			$out .= '<li class="gs-todo-item"><span>' . esc_html( $tipo ) . ': ' . number_format( (float) $voce['percentuale'], 1 ) . '% — ' . esc_html( date_i18n( 'j/n/Y', strtotime( $voce['data'] ) ) ) . '</span></li>';
		}
		$out .= '</ul></details>';
	}

	$out .= gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// Pulizia messaggi di un mese chiuso per errore
// -----------------------------------------------------------------------------

/**
 * Sposta nel cestino (mai cancellazione definitiva) i messaggi interni "Il
 * tuo resoconto di [mese]" mandati alle sfogline toccate dalla chiusura del
 * mese indicato. Serve per il caso — successo davvero il 23/08/2026 con
 * "luglio 2026", prima che la protezione del primo avvio (vedi
 * gs_buono_sfoglia_controlla_chiusura_mese) fosse installata — in cui un
 * mese viene chiuso per errore prima che il gioco mensile fosse davvero
 * iniziato: qui si dà a chi gestisce un modo per ripulire le caselle senza
 * dover intervenire a mano sul database. Non tocca le email già partite
 * (non richiamabili) né la percentuale del Buono Sfoglia (che qui non è mai
 * stata assegnata, essendo i punti del mese sbagliato sempre a zero).
 */
function gs_buono_sfoglia_pulisci_messaggi_mese( $ym ) {
	$ym = sanitize_text_field( $ym );
	if ( ! $ym ) {
		return 0;
	}
	$etichetta_mese = gs_buono_sfoglia_mese_label( $ym );
	$titolo_atteso  = 'Il tuo resoconto di ' . $etichetta_mese . ' — Accademia della Sfoglia';
	$rimossi        = 0;

	foreach ( get_users( array( 'fields' => 'ID' ) ) as $uid ) {
		$uid = (int) $uid;
		if ( ! get_user_meta( $uid, 'gs_buono_mese_' . $ym, true ) ) {
			continue; // questa sfoglina non è stata toccata da quella chiusura
		}
		$messaggi = get_posts( array(
			'post_type'   => 'gs_messaggio',
			'post_status' => 'publish',
			'meta_key'    => 'gs_dest',
			'meta_value'  => $uid,
			'title'       => $titolo_atteso,
			'numberposts' => -1,
		) );
		foreach ( $messaggi as $m ) {
			wp_trash_post( $m->ID );
			$rimossi++;
		}
	}
	return $rimossi;
}

add_action( 'wp_ajax_gs_buono_sfoglia_pulisci_messaggi', 'gs_ajax_buono_sfoglia_pulisci_messaggi' );
function gs_ajax_buono_sfoglia_pulisci_messaggi() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Permesso negato.' ) );
	}
	$ym = isset( $_POST['mese'] ) ? sanitize_text_field( wp_unslash( $_POST['mese'] ) ) : '';
	if ( ! $ym ) {
		wp_send_json_error( array( 'message' => 'Mese mancante.' ) );
	}
	$rimossi = gs_buono_sfoglia_pulisci_messaggi_mese( $ym );
	wp_send_json_success( array(
		'message' => $rimossi > 0
			? $rimossi . ' messaggio/i spostato/i nel cestino.'
			: 'Nessun messaggio trovato da rimuovere (forse già fatto, o le sfogline li avevano già eliminati).',
	) );
}
