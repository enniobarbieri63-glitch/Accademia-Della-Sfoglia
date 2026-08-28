<?php
/**
 * token.php — Credito a token per le consulenze private con "L'Esperto
 * Risponde" (Rina Poletti Risponde, Bruno Cingolani Risponde e ogni altro
 * canale futuro). Nessun incasso online (vedi CLAUDE.md): il credito si
 * accredita a mano dopo aver verificato un bonifico con causale
 * "CONTRIBUTO A SOSTEGNO DELL'ASSOCIAZIONE ACCADEMIA DELLA SFOGLIA" — stesso schema manuale della quota associativa
 * di registration.php, non un pagamento a listino.
 *
 * Saldo in meta gs_token_credito, storico in meta gs_token_log — stesso
 * schema di gs_add_points()/gs_log_points() in points.php.
 *
 * Nel linguaggio verso le sfogline si evitano le parole "costo"/"prezzo":
 * si parla di contributi associativi (richiesto da Ennio il 2026-07-30).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Saldo e storico
// -----------------------------------------------------------------------------
function gs_token_saldo( $uid ) {
	return (int) get_user_meta( (int) $uid, 'gs_token_credito', true );
}

/**
 * Muove il saldo token di una sfoglina e scrive lo storico.
 *
 * @param int    $uid    ID utente.
 * @param int    $delta  Variazione: positiva per accredito/rimborso, negativa per consumo.
 * @param string $tipo   'accredito' | 'consumo' | 'rimborso'.
 * @param string $motivo Testo leggibile registrato nello storico.
 * @return int Nuovo saldo.
 *
 * ATTENZIONE sullo storico (gs_token_log, non sul saldo): è un
 * leggi-modifica-scrivi su un solo meta, quindi due movimenti simultanei
 * sulla stessa sfoglina possono far perdere una delle due righe — il saldo
 * non si perde (max(0, saldo+delta) è scritto a parte), lo storico sì. La
 * rete di sicurezza di gs_ajax_token_accredita() legge l'ultima voce di
 * questo storico per riconoscere un doppio invio senza identificativo: se
 * proprio quella voce va persa, la rete non vede il doppio invio (trovato
 * il 25/08/2026 — stesso limite in gs_log_points() e
 * gs_cal_pagamento_log_aggiungi()).
 */
function gs_token_movimento( $uid, $delta, $tipo, $motivo = '' ) {
	$uid   = (int) $uid;
	$delta = (int) $delta;
	if ( ! $uid || 0 === $delta ) {
		return gs_token_saldo( $uid );
	}
	$saldo_prima = gs_token_saldo( $uid );
	$nuovo = max( 0, $saldo_prima + $delta );

	// Se il taglio a zero è intervenuto, il movimento chiesto era più grande
	// di quanto il saldo permettesse: va scritto nello storico, altrimenti
	// la contabilità dei token mente in silenzio — con 1 token e due consumi
	// da 1 il saldo finisce a 0 come se fosse tutto regolare, e lo storico
	// da solo non dice che uno dei due non ci stava (trovato il 25/08/2026).
	// Non si toglie il max(0,...): un saldo negativo confonderebbe la
	// sfoglina, si registra solo che è successo.
	if ( $nuovo !== $saldo_prima + $delta ) {
		$motivo .= ' (saldo insufficiente: richiesti ' . abs( $delta ) . ', tolti ' . $saldo_prima . ')';
	}

	update_user_meta( $uid, 'gs_token_credito', $nuovo );

	$log = get_user_meta( $uid, 'gs_token_log', true );
	if ( ! is_array( $log ) ) {
		$log = array();
	}
	array_unshift( $log, array(
		'time'   => current_time( 'mysql' ),
		'tipo'   => sanitize_key( $tipo ),
		'delta'  => $delta,
		'motivo' => sanitize_text_field( $motivo ),
		'saldo'  => $nuovo,
	) );
	$log = array_slice( $log, 0, 100 );
	update_user_meta( $uid, 'gs_token_log', $log );

	return $nuovo;
}

function gs_token_log( $uid, $limit = 20 ) {
	$log = get_user_meta( (int) $uid, 'gs_token_log', true );
	if ( ! is_array( $log ) ) {
		return array();
	}
	return array_slice( $log, 0, (int) $limit );
}

// -----------------------------------------------------------------------------
// Impostazioni: valore di un token in euro (per il calcolo suggerito
// nell'accredito) e giorni prima del rimborso automatico
// -----------------------------------------------------------------------------
function gs_token_valore_euro() {
	$s = gs_settings();
	$v = isset( $s['token']['valore_euro'] ) ? (float) $s['token']['valore_euro'] : 5;
	return $v > 0 ? $v : 5;
}
function gs_token_giorni_rimborso() {
	$s = gs_settings();
	$g = isset( $s['token']['giorni_rimborso'] ) ? (int) $s['token']['giorni_rimborso'] : 7;
	return $g > 0 ? $g : 14;
}

/**
 * Costo in token dell'attivazione della Vetrina pubblica di una sfoglina
 * (funzionale, consumato davvero dal saldo — vedi gs_vetrina_attiva_con_token()
 * in shortcodes.php). Impostabile qui, separato dal costo delle consulenze
 * private con L'Esperto Risponde (quello si decide per canale).
 */
function gs_token_costo_vetrina() {
	$s = gs_settings();
	$v = isset( $s['token']['costo_vetrina'] ) ? (int) $s['token']['costo_vetrina'] : 5;
	return $v > 0 ? $v : 5;
}

/**
 * Importi di riferimento per l'abbonamento annuale di Artigiani della Pasta
 * e Scuole di Cucina — non consumano token: sono solo un promemoria del
 * bonifico atteso, richiesto esplicitamente distinto per le due categorie
 * (Ennio, 2026-08-11). L'importo davvero registrato per ogni partner resta
 * quello scritto a mano nel modulo "Registra un bonifico" di ciascuna vetrina.
 */
function gs_token_riferimento_artigiani() {
	$s = gs_settings();
	return isset( $s['token']['rif_artigiani'] ) && '' !== $s['token']['rif_artigiani'] ? $s['token']['rif_artigiani'] : '150,00 €/anno';
}
function gs_token_riferimento_scuole() {
	$s = gs_settings();
	return isset( $s['token']['rif_scuole'] ) && '' !== $s['token']['rif_scuole'] ? $s['token']['rif_scuole'] : '150,00 €/anno';
}

add_action( 'wp_ajax_gs_token_salva_impostazioni', 'gs_ajax_token_salva_impostazioni' );
function gs_ajax_token_salva_impostazioni() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Permesso negato.' ) );
	}
	$s = gs_settings();
	if ( ! isset( $s['token'] ) || ! is_array( $s['token'] ) ) {
		$s['token'] = array();
	}
	$s['token']['valore_euro']     = max( 0.5, (float) ( $_POST['valore_euro'] ?? 5 ) );
	$s['token']['giorni_rimborso'] = max( 1, (int) ( $_POST['giorni_rimborso'] ?? 7 ) );
	$s['token']['costo_vetrina']   = max( 1, (int) ( $_POST['costo_vetrina'] ?? 5 ) );
	$s['token']['rif_artigiani']   = sanitize_text_field( wp_unslash( $_POST['rif_artigiani'] ?? '' ) );
	$s['token']['rif_scuole']      = sanitize_text_field( wp_unslash( $_POST['rif_scuole'] ?? '' ) );
	update_option( GS_OPTION, $s );
	wp_send_json_success( array( 'message' => 'Impostazioni salvate.' ) );
}

// -----------------------------------------------------------------------------
// Accredito manuale dopo un contributo associativo (bonifico, causale
// "CONTRIBUTO A SOSTEGNO DELL'ASSOCIAZIONE ACCADEMIA DELLA SFOGLIA")
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_token_accredita', 'gs_ajax_token_accredita' );
function gs_ajax_token_accredita() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Permesso negato.' ) );
	}
	$uid       = isset( $_POST['uid'] ) ? (int) $_POST['uid'] : 0;
	$token_n   = isset( $_POST['token'] ) ? (int) $_POST['token'] : 0;
	$importo   = isset( $_POST['importo'] ) ? (float) $_POST['importo'] : 0;
	$motivo_in = isset( $_POST['motivo'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['motivo'] ) ) ) : '';
	$rif       = isset( $_POST['rif'] ) ? sanitize_key( wp_unslash( $_POST['rif'] ) ) : '';
	if ( ! $uid || ! get_user_by( 'id', $uid ) ) {
		wp_send_json_error( array( 'message' => 'Sfoglina non trovata.' ) );
	}
	if ( 0 === $token_n ) {
		wp_send_json_error( array( 'message' => 'Indica quanti token assegnare.' ) );
	}
	// Un numero negativo corregge un accredito sbagliato (es. un doppio
	// clic registrato prima di questa protezione): solo il titolare, stesso
	// principio già applicato ai pagamenti del Calendario Corsi — non si usa
	// un max(0,...) qui, l'errore deve restare visibile nello storico.
	if ( $token_n < 0 && ! gs_is_titolare() ) {
		wp_send_json_error( array( 'message' => 'Solo il titolare può registrare una correzione (numero negativo).' ) );
	}

	// Blocca il doppio invio, stesso schema di gs_ajax_cal_pagamento: un
	// identificativo generato all'apertura della scheda e controllato prima
	// di sommare, con una rete di sicurezza sull'ultimo movimento per quando
	// non arriva (browser con JavaScript vecchio in cache — trovato il
	// 25/08/2026, stesso difetto già corretto sui pagamenti del calendario).
	$visti = get_user_meta( $uid, 'gs_token_rif', true );
	$visti = is_array( $visti ) ? $visti : array();
	if ( $rif && in_array( $rif, $visti, true ) ) {
		wp_send_json_error( array( 'message' => 'Questo accredito risulta già registrato.' ) );
	}
	if ( $rif ) {
		$visti[] = $rif;
		$visti = array_slice( $visti, -50 ); // mai più di 50 in memoria: a differenza del calendario (un elenco per prenotazione) questo è per sfoglina, e si accumulerebbe per sempre
		update_user_meta( $uid, 'gs_token_rif', $visti );
	} else {
		$ultima = gs_token_log( $uid, 1 );
		$ultima = $ultima ? $ultima[0] : null;
		if ( $ultima
			&& (int) $ultima['delta'] === $token_n
			&& ( current_time( 'timestamp' ) - strtotime( $ultima['time'] ) ) < 15 ) {
			wp_send_json_error( array( 'message' => 'Un accredito identico è stato registrato pochi secondi fa: se è davvero un secondo accredito, riprova fra un minuto.' ) );
		}
	}

	// Con un importo: assegnazione legata a un contributo associativo
	// (bonifico), con o senza motivo aggiuntivo. Senza importo: assegnazione
	// libera (regalo, premio, correzione…) — richiede un motivo, per lasciare
	// sempre traccia leggibile di un movimento non legato a un bonifico.
	if ( $importo > 0 ) {
		$motivo = 'Contributo associativo ricevuto (' . number_format( $importo, 2, ',', '.' ) . ' €)' . ( $motivo_in ? ' — ' . $motivo_in : '' );
		$oggetto_email = 'Contributo associativo ricevuto';
		$corpo_email   = "abbiamo ricevuto il tuo contributo associativo e ti abbiamo accreditato";
	} else {
		if ( '' === $motivo_in ) {
			wp_send_json_error( array( 'message' => 'Senza un importo, scrivi il motivo dell\'assegnazione.' ) );
		}
		$motivo = $motivo_in;
		$oggetto_email = 'Token assegnati';
		$corpo_email   = "ti abbiamo assegnato";
	}
	$nuovo = gs_token_movimento( $uid, $token_n, $token_n < 0 ? 'correzione' : 'accredito', $motivo );

	// Email e aeroplanino solo per un accredito vero: una correzione negativa
	// sistema un errore di sistema (es. un doppio clic), la sfoglina non ha
	// motivo di sapere che è successo, e "hai ricevuto -5 token" confonderebbe
	// e basta.
	if ( $token_n > 0 ) {
		$user = get_user_by( 'id', $uid );
		if ( $user && function_exists( 'gs_mail_progetto' ) ) {
			gs_mail_progetto(
				$uid,
				'messaggi',
				$oggetto_email,
				"Ciao " . $user->display_name . ",\n\n" . $corpo_email . " "
				. $token_n . " token per le consulenze private con i maestri.\n\nSaldo attuale: " . $nuovo . " token.\n\nGrazie!\n\n— Accademia della Sfoglia"
			);
		}

		// Avviso Aeroplanino, come per gli altri eventi che riguardano la
		// sfoglina (punto 11, Ennio 21/08/2026: era uno dei "buchi" trovati).
		if ( function_exists( 'gs_accoda_volo' ) ) {
			gs_accoda_volo( $uid, 'HAI RICEVUTO ' . $token_n . ' TOKEN! 🎟️', function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_dashboard' ) : '' );
		}
	}

	wp_send_json_success( array(
		'message' => $token_n < 0 ? 'Correzione registrata. Saldo: ' . $nuovo . '.' : 'Assegnati ' . $token_n . ' token. Saldo: ' . $nuovo . '.',
		'saldo'   => $nuovo,
	) );
}

// -----------------------------------------------------------------------------
// Pannello gestore — sezione Pagamenti
// -----------------------------------------------------------------------------
function gs_pannello_token() {
	if ( ! ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) ) {
		return;
	}
	$valore = gs_token_valore_euro();
	$giorni = gs_token_giorni_rimborso();

	echo gs_box_open( '🎫 Token per le consulenze private', '', 'gs-box-token' );
	echo gs_sezione_aiuto(
		'Le consulenze private con i maestri (Rina Poletti Risponde, Bruno Cingolani Risponde e altri canali futuri) si pagano a token: ogni domanda ne consuma uno, o il numero che decidi per quel singolo maestro dal pannello «L\'Esperto Risponde». '
		. 'Le sfogline comprano credito con un contributo associativo versato con bonifico — causale «CONTRIBUTO A SOSTEGNO DELL\'ASSOCIAZIONE ACCADEMIA DELLA SFOGLIA» — e tu lo accrediti qui sotto dopo aver verificato l\'arrivo del bonifico. '
		. 'Se una domanda resta senza risposta, il token torna alla sfoglina: in automatico dopo i giorni che imposti qui sotto, oppure subito a mano dalla conversazione privata.'
	);

	echo '<div class="gs-token-info"><strong>Cosa sono i contributi associativi?</strong>'
		. '<p>Sono versamenti volontari che le sfogline possono decidere di fare oltre alla quota associativa, per finanziare le consulenze private con i maestri o altre attività non coperte dal bilancio ordinario. Non sono un pagamento a listino: restano un contributo libero all\'Accademia, che si traduce in credito da usare per le domande private.</p></div>';

	// Impostazioni.
	echo '<form class="gs-form gs-form-token-imp" onsubmit="return false">';
	echo '<p><label>Valore di un token (euro)<br><input type="number" step="0.5" min="0.5" name="valore_euro" value="' . esc_attr( $valore ) . '" style="width:100px"></label> ';
	echo '<label>Giorni prima del rimborso automatico se non c\'è risposta<br><input type="number" step="1" min="1" name="giorni_rimborso" value="' . esc_attr( $giorni ) . '" style="width:100px"></label></p>';
	echo '<p><label>Token per attivare la Vetrina pubblica di una sfoglina<br><input type="number" step="1" min="1" name="costo_vetrina" value="' . esc_attr( gs_token_costo_vetrina() ) . '" style="width:100px"></label></p>';
	echo '<div class="gs-todo-riquadro"><p class="gs-hint">I due importi qui sotto sono solo un riferimento per te: non si scalano da nessun saldo, servono a ricordarti quanto chiedere quando registri il bonifico di un Artigiano della Pasta o di una Scuola di Cucina — costi tenuti apposta distinti tra le due categorie.</p>';
	echo '<p><label>Riferimento abbonamento annuale — Artigiani della Pasta<br><input type="text" name="rif_artigiani" value="' . esc_attr( gs_token_riferimento_artigiani() ) . '" style="width:160px"></label> ';
	echo '<label>Riferimento abbonamento annuale — Scuole di Cucina<br><input type="text" name="rif_scuole" value="' . esc_attr( gs_token_riferimento_scuole() ) . '" style="width:160px"></label></p></div>';
	echo '<p><button class="gs-btn gs-btn-sm gs-token-imp-salva">Salva impostazioni</button> <span class="gs-token-imp-msg gs-richiesta-esito"></span></p>';
	echo '</form><hr>';

	// Assegna token: dopo un contributo associativo, oppure senza — a tua discrezione.
	echo '<h4>Assegna token a una sfoglina</h4>';
	echo '<p class="gs-hint">Di solito dopo un contributo associativo ricevuto, ma puoi assegnare token anche senza bonifico (es. un regalo, un premio, una correzione) — lascia vuoto l\'importo e scrivi il motivo.</p>';
	echo '<form class="gs-form gs-form-token-accredita" onsubmit="return false" data-valore-token="' . esc_attr( $valore ) . '">';
	echo '<p><label>Sfoglina<br><select name="uid" style="min-width:220px"><option value="">— scegli —</option>';
	foreach ( gs_sez_sfogline() as $u ) {
		echo '<option value="' . (int) $u->ID . '">' . esc_html( $u->display_name ) . ' (' . gs_token_saldo( $u->ID ) . ' token)</option>';
	}
	echo '</select></label></p>';
	echo '<p><label>Importo del contributo ricevuto (€) — facoltativo<br><input type="number" step="0.01" min="0" class="gs-token-importo" style="width:120px"></label> ';
	echo '<label>Token da assegnare<br><input type="number" step="1" name="token" class="gs-token-quanti" value="1" style="width:90px"></label></p>';
	echo '<p class="gs-hint">Un numero negativo corregge un accredito sbagliato (solo il titolare).</p>';
	echo '<p><label>Motivo (facoltativo — obbligatorio di fatto se non c\'è un importo)<br><input type="text" name="motivo" autocomplete="off" style="width:100%" placeholder="es. regalo di benvenuto, correzione, premio…"></label></p>';
	echo '<p class="gs-hint">Con un importo, il numero di token si calcola da solo in base al valore di un token impostato sopra (resta comunque modificabile a mano). Senza importo, decidi tu direttamente quanti token assegnare.</p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-token-accredita">Assegna</button> <span class="gs-token-accredita-msg gs-richiesta-esito"></span></p>';
	echo '</form><hr>';

	// Saldo e storico di ogni sfoglina.
	echo '<h4>Saldo e storico di ogni sfoglina</h4>';
	$sfogline = gs_sez_sfogline();
	if ( ! $sfogline ) {
		echo '<p class="gs-hint">Nessuna sfoglina registrata.</p>';
	} else {
		echo '<div class="gs-todo-riquadro"><div class="gs-token-lista gs-paginate" data-per-page="10">';
		foreach ( $sfogline as $u ) {
			$saldo = gs_token_saldo( $u->ID );
			$log   = gs_token_log( $u->ID, 30 );
			echo '<details class="gs-inbox-item" data-uid="' . (int) $u->ID . '">';
			echo '<summary class="gs-inbox-oggetto">' . esc_html( $u->display_name ) . '<span class="gs-msg-data">' . $saldo . ' token disponibili</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			if ( ! $log ) {
				echo '<p class="gs-hint">Nessun movimento ancora.</p>';
			} else {
				echo '<ul class="gs-todo-list">';
				foreach ( $log as $voce ) {
					$segno = $voce['delta'] > 0 ? '+' : '';
					echo '<li class="gs-todo-item"><span>' . esc_html( date_i18n( 'j/m/Y H:i', strtotime( $voce['time'] ) ) )
						. ' — ' . esc_html( $segno . (int) $voce['delta'] ) . ' token — ' . esc_html( $voce['motivo'] )
						. ' (saldo: ' . (int) $voce['saldo'] . ')</span></li>';
				}
				echo '</ul>';
			}
			echo '</div></details>';
		}
		echo '</div></div>';
	}

	echo gs_box_close();
}

// -----------------------------------------------------------------------------
// Rimborso automatico: domande private senza risposta da troppi giorni
// -----------------------------------------------------------------------------
add_action( 'gs_daily_cron', 'gs_token_controlla_rimborsi' );
function gs_token_controlla_rimborsi() {
	if ( ! function_exists( 'gs_conv_msgs' ) ) {
		return;
	}
	$giorni = gs_token_giorni_rimborso();
	$limite = time() - ( $giorni * DAY_IN_SECONDS );

	$convs = get_posts( array(
		'post_type'      => 'gs_conversazione',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	) );
	global $wpdb;
	foreach ( $convs as $c ) {
		// Stesso lucchetto del rimborso a mano (gs_conv_rimborsa_token_uid in
		// conversazioni.php): se il cron gira nello stesso istante di un clic
		// a mano sulla stessa domanda, senza lucchetto condiviso il token
		// veniva restituito due volte (trovato 26/08/2026).
		$lock = 'gs_conv_rimborso_' . (int) $c->ID;
		if ( '1' !== $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) ) ) {
			continue; // occupata in questo momento, ci pensa il prossimo giro di cron
		}

		try {
			// get_posts() qui sopra ha già precaricato in cache i meta di
			// questo post PRIMA di prendere il lucchetto: senza svuotarla,
			// la lettura che segue vede ancora il valore di prima, anche se
			// nel frattempo il rimborso a mano ha già scritto il nuovo
			// 'rimborsato' — il lucchetto da solo non basta se si legge
			// dalla cache invece che dal database (trovato 26/08/2026,
			// stesso genere del limite già noto su gs_points).
			wp_cache_delete( $c->ID, 'post_meta' );
			$msgs = gs_conv_msgs( $c->ID );
			foreach ( $msgs as $i => $m ) {
				if ( empty( $m['consulenza'] ) || ! empty( $m['rimborsato'] ) || empty( $m['token_costo'] ) ) {
					continue;
				}
				if ( (int) $m['time'] > $limite ) {
					continue; // non ancora scaduto il termine
				}
				// C'è una risposta dell'esperto DOPO questa domanda? "Dopo" per
				// posizione nell'elenco, non per timestamp (due messaggi nello
				// stesso secondo avrebbero lo stesso tempo).
				$risposta_trovata = false;
				foreach ( $msgs as $j => $succ ) {
					if ( $j > $i && ! empty( $succ['is_esperto'] ) ) {
						$risposta_trovata = true;
						break;
					}
				}
				if ( $risposta_trovata ) {
					continue;
				}

				$sfoglina = (int) $m['from'];

				// Contrassegno scritto e SALVATO per ogni singolo messaggio,
				// prima del movimento e prima dell'email — non più una volta
				// sola a fine ciclo. Il lucchetto impedisce a due rimborsi di
				// partire insieme, ma non impedisce al ciclo di morire a
				// metà (tempo massimo di esecuzione, limite orario di posta
				// dell'hosting — la stessa causa già descritta in
				// buono-sfoglia.php). Con la scrittura solo a fine ciclo, una
				// conversazione con cinque domande scadute restituiva cinque
				// token e mandava cinque email prima di scrivere un solo
				// contrassegno: se il ciclo si fermava alla terza, il giorno
				// dopo uscivano di nuovo tutti e cinque (trovato 26/08/2026).
				// Costa una scrittura per rimborso invece di una per
				// conversazione — i rimborsi sono rari.
				$msgs[ $i ]['rimborsato'] = true;
				update_post_meta( $c->ID, 'gs_msgs', $msgs );
				gs_token_movimento( $sfoglina, (int) $m['token_costo'], 'rimborso', 'Rimborso automatico: nessuna risposta entro ' . $giorni . ' giorni' );

				$user = get_user_by( 'id', $sfoglina );
				if ( $user && function_exists( 'gs_mail_progetto' ) ) {
					gs_mail_progetto(
						$sfoglina,
						'messaggi',
						'Token restituito',
						"Ciao " . $user->display_name . ",\n\nla tua domanda privata non ha ancora ricevuto risposta dopo " . $giorni . " giorni, quindi ti abbiamo restituito il token.\n\nPuoi riprovare quando vuoi.\n\n— Accademia della Sfoglia"
					);
				}
			}
		} finally {
			$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
		}
	}
}
