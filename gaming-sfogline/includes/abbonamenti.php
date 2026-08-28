<?php
/**
 * abbonamenti.php — Stato abbonamento delle sfogline.
 *
 * Se l'abbonamento è "scaduto", la sfoglina accede solo al gaming pubblico di
 * primo livello (sezioni 'base') e NON alle aree private di livello superiore
 * (Calendario Corsi, Area Professionale, Messaggi, Esperto, Conversazioni).
 * Lo stato si imposta dal pannello di controllo.
 *
 * Meta utente:
 *  - gs_abbonamento          = 'attivo' (default) | 'scaduto'
 *  - gs_abbonamento_scadenza = data Y-m-d opzionale, solo promemoria via email
 *    (non cambia da sola lo stato: resta sempre il gestore a impostare
 *    "scaduto" a mano, la data serve solo per avvisare prima che succeda)
 *  - gs_abbonamento_avviso_per = ultima data di scadenza per cui è già stato
 *    inviato l'avviso (evita di reinviarlo ogni giorno per la stessa data)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** True se l'abbonamento della sfoglina è scaduto. */
function gs_abbonamento_scaduto( $uid ) {
	return 'scaduto' === get_user_meta( (int) $uid, 'gs_abbonamento', true );
}

/**
 * True se questa sfoglina è CONGELATA: fuori dall'area riservata.
 *
 * Due strade, una sola risposta:
 *  • lo stato messo a mano dal pannello ('scaduto');
 *  • la data gs_abbonamento_scadenza passata — che dal 26/08/2026 non è più
 *    solo un promemoria ma la scadenza vera (Ennio: "certo scadenza
 *    automatica, noi di manuale controlliamo solo il bonifico").
 *
 * Nessuna data e stato "attivo" = accesso aperto. È una scelta deliberata,
 * non una dimenticanza: gli account di Ennio, dei collaboratori, degli amici
 * e dei giornalisti non hanno una scadenza e non devono averla. Il prezzo è
 * che una data cancellata per sbaglio riapre l'accesso invece di chiuderlo —
 * accettato, perché l'errore opposto (chiudere fuori chi ha pagato) è molto
 * peggio.
 */
function gs_sfoglina_congelata( $uid ) {
	$uid = (int) $uid;
	if ( ! $uid ) { return false; }
	if ( user_can( $uid, 'gs_manage_gaming' ) || user_can( $uid, 'manage_options' ) ) {
		return false; // i gestori non si congelano mai
	}
	if ( 'scaduto' === get_user_meta( $uid, 'gs_abbonamento', true ) ) {
		return true;
	}
	$scadenza = get_user_meta( $uid, 'gs_abbonamento_scadenza', true );
	if ( ! $scadenza || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $scadenza ) ) {
		return false;
	}
	// Due date attraverso la stessa funzione, mai un timestamp contro una
	// mezzanotte: è l'errore di P3 (25/08/2026), trovato tre volte.
	return strtotime( current_time( 'Y-m-d' ) ) > strtotime( $scadenza );
}

/** Quante sfogline hanno l'abbonamento scaduto (per la Bacheca di riepilogo). */
function gs_abbonamento_totale_scaduti() {
	$sfogline = function_exists( 'gs_sez_sfogline' ) ? gs_sez_sfogline() : array();
	$n = 0;
	foreach ( $sfogline as $u ) {
		if ( gs_abbonamento_scaduto( $u->ID ) ) { $n++; }
	}
	return $n;
}

/**
 * Il riquadro che una sfoglina congelata vede al posto di ogni sezione
 * riservata, «La Mia Sfoglia» compresa (Ennio, 26/08/2026: si chiude anche
 * quella). Non rimanda più alla dashboard per le istruzioni del bonifico —
 * è chiusa anch'essa — quindi le istruzioni complete stanno solo nell'email
 * di scadenza (vedi gs_abbonamento_controlla_scadenze()); qui c'è solo il
 * promemoria che quell'email esiste.
 */
function gs_congelata_avviso() {
	$importo = gs_settings()['registration']['importo_quota'] ?? '29,00';
	return '<div class="gs-box gs-notice gs-box-congelata">'
		. '<h3>Il tuo mese di prova è finito</h3>'
		. '<p><strong>Non hai perso niente.</strong> I tuoi punti, i tuoi badge, il tuo percorso, '
		. 'le tue ricette, le tue foto e tutto quello che hai scritto sono salvati esattamente come li hai lasciati. '
		. 'Sono congelati, non cancellati: il giorno in cui rientri, ritrovi tutto al suo posto.</p>'
		. '<p>Per riaprire questa parte del sito serve un contributo di <strong>' . esc_html( $importo ) . ' €</strong> '
		. 'a sostegno dell\'Accademia. Ti abbiamo mandato un\'email con tutte le istruzioni — controlla anche la posta indesiderata.</p>'
		. '<p>Nel frattempo le pagine aperte del sito restano tue: la Galleria, il Registro, '
		. 'la Classifica, le Sfogline, le Letture e la tua Vetrina.</p>'
		. '</div>';
}

// -----------------------------------------------------------------------------
// PANNELLO — gestione stato abbonamento
// -----------------------------------------------------------------------------
function gs_pannello_abbonamenti() {
	if ( ! gs_can_manage() ) { return; }
	$sfogline = function_exists( 'gs_sez_sfogline' ) ? gs_sez_sfogline() : array();
	echo gs_box_open( 'Abbonamenti delle sfogline', '', 'gs-box-abbonamenti' );
	echo '<p class="gs-hint">Imposta lo stato dell\'abbonamento di ogni sfoglina. Con l\'abbonamento «scaduto», o con la data di scadenza passata, l\'accesso a tutta la parte riservata (gaming e aree private) si chiude: resta aperto solo il sito in chiaro. Nulla va perso: è congelato, non cancellato, e riapre appena sposti la data o metti «Attivo» — anche dopo mesi.</p>';
	echo '<div class="gs-token-info"><strong>Cosa sono i contributi associativi?</strong>'
		. '<p>Sono versamenti volontari che le sfogline possono decidere di fare oltre alla quota associativa, ad esempio per le consulenze private con i maestri o altre attività non coperte dal bilancio ordinario. Causale da usare per il bonifico: «CONTRIBUTO A SOSTEGNO DELL\'ASSOCIAZIONE ACCADEMIA DELLA SFOGLIA». Il credito che ne deriva (in token) si accredita dal pannello «Pagamenti → Token».</p></div>';
	if ( ! $sfogline ) {
		echo '<p class="gs-hint">Nessuna sfoglina registrata.</p>' . gs_box_close();
		return;
	}
	echo '<p class="gs-hint">La data di scadenza è la data in cui l\'accesso all\'area riservata si chiude da solo. Alla registrazione viene messa in automatico a 30 giorni dall\'approvazione: è la prova in regalo. Quando arriva il bonifico di 29 euro, sposta la data in avanti — nient\'altro. Se togli la data, l\'accesso resta aperto senza scadenza: usalo per i collaboratori, per gli amici e per i giornalisti. Lo stato "Scaduto" chiude subito, senza aspettare la data. Le righe scadute lampeggiano in rosso, quelle attive sono verdi.</p>';
	echo '<form class="gs-form gs-form-abbonamenti" onsubmit="return false"><table class="gs-table gs-paginate" data-per-page="15"><thead><tr><th>Sfoglina</th><th>Stato abbonamento</th><th>Scadenza (facoltativa)</th><th>Giorni rimasti</th></tr></thead><tbody>';
	foreach ( $sfogline as $u ) {
		$scaduto  = gs_abbonamento_scaduto( $u->ID );
		$scadenza = get_user_meta( $u->ID, 'gs_abbonamento_scadenza', true );
		$congelata = function_exists( 'gs_sfoglina_congelata' ) && gs_sfoglina_congelata( $u->ID );
		$cls      = $congelata ? 'gs-abb-scaduto gs-lampeggia-rosso' : 'gs-abb-attivo';
		echo '<tr class="' . esc_attr( $cls ) . '" data-uid="' . (int) $u->ID . '"><td>' . esc_html( $u->display_name ) . '</td><td>';
		echo '<select class="gs-abb-stato">';
		echo '<option value="attivo" ' . ( $scaduto ? '' : 'selected' ) . '>Attivo</option>';
		echo '<option value="scaduto" ' . ( $scaduto ? 'selected' : '' ) . '>Scaduto</option>';
		echo '</select></td><td><input type="date" class="gs-abb-scadenza" value="' . esc_attr( $scadenza ) . '"></td><td>';
		if ( $scadenza && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $scadenza ) ) {
			$giorni = (int) floor( ( strtotime( $scadenza ) - strtotime( current_time( 'Y-m-d' ) ) ) / DAY_IN_SECONDS );
			echo $giorni >= 0 ? esc_html( $giorni ) . ( 1 === $giorni ? ' giorno' : ' giorni' ) : 'congelata';
		} else {
			echo '—';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table><p><button class="gs-btn gs-btn-sm gs-abb-salva">Salva stati abbonamento</button> <span class="gs-abb-msg gs-richiesta-esito"></span></p></form>';
	echo gs_box_close();
}

add_action( 'wp_ajax_gs_abbonamento_salva', 'gs_ajax_abbonamento_salva' );
function gs_ajax_abbonamento_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$stati    = isset( $_POST['stati'] ) && is_array( $_POST['stati'] ) ? $_POST['stati'] : array();
	$scadenze = isset( $_POST['scadenze'] ) && is_array( $_POST['scadenze'] ) ? $_POST['scadenze'] : array();
	$n = 0;
	foreach ( $stati as $uid => $stato ) {
		$uid   = (int) $uid;
		$stato = ( 'scaduto' === $stato ) ? 'scaduto' : 'attivo';
		if ( $uid <= 0 ) { continue; }
		update_user_meta( $uid, 'gs_abbonamento', $stato );

		$scadenza = isset( $scadenze[ $uid ] ) ? sanitize_text_field( wp_unslash( $scadenze[ $uid ] ) ) : '';
		if ( $scadenza && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $scadenza ) ) {
			$vecchia = get_user_meta( $uid, 'gs_abbonamento_scadenza', true );
			update_user_meta( $uid, 'gs_abbonamento_scadenza', $scadenza );
			if ( $vecchia !== $scadenza ) {
				delete_user_meta( $uid, 'gs_abbonamento_avviso_per' ); // data cambiata: si può riavvisare
			}
		} else {
			delete_user_meta( $uid, 'gs_abbonamento_scadenza' );
			delete_user_meta( $uid, 'gs_abbonamento_avviso_per' );
		}
		$n++;
	}
	wp_send_json_success( array( 'message' => 'Aggiornati ' . $n . ' abbonamenti.' ) );
}

// -----------------------------------------------------------------------------
// Avviso automatico di scadenza in arrivo (via email, un promemoria solo).
// Gira sul cron giornaliero già esistente del plugin (gs_daily_cron).
// -----------------------------------------------------------------------------
add_action( 'gs_daily_cron', 'gs_abbonamento_controlla_scadenze' );
function gs_abbonamento_controlla_scadenze() {
	$giorni_preavviso = 7;
	$sfogline = function_exists( 'gs_sez_sfogline' ) ? gs_sez_sfogline() : array();
	foreach ( $sfogline as $u ) {
		if ( gs_abbonamento_scaduto( $u->ID ) ) { continue; } // già scaduto A MANO, nessun avviso da mandare
		$scadenza = get_user_meta( $u->ID, 'gs_abbonamento_scadenza', true );
		if ( ! $scadenza || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $scadenza ) ) { continue; }

		// Due DATE attraverso la stessa funzione, mai un momento (con l'ora)
		// contro una mezzanotte: current_time('timestamp') porta con sé
		// l'ora, quindi il giorno stesso della scadenza, dopo mezzanotte,
		// mancherebbero "-1 giorni" e partirebbe la mail "la prova è
		// finita" mentre gs_sfoglina_congelata() — che confronta due
		// mezzanotte — la considera ancora dentro: la mail e il cancello si
		// contraddicono esattamente il giorno che conta di più. È lo stesso
		// difetto (P3) già trovato tre volte su questo progetto, stavolta
		// nella funzione che decide quale delle tre mail parte (trovato
		// 27/08/2026). round() e non floor(): con l'ora legale un giorno
		// dura 23 o 25 ore, non sempre 24.
		$giorni_mancanti = (int) round( ( strtotime( $scadenza ) - strtotime( current_time( 'Y-m-d' ) ) ) / DAY_IN_SECONDS );
		$ts = strtotime( $scadenza ); // solo per la data leggibile più sotto (avviso interno)

		// Dal 27/08/2026 (documento TRENTA-GIORNI-IL-CANCELLO.md) la
		// scadenza non è più solo un promemoria: gs_sfoglina_congelata()
		// la rende vincolante da sola, senza bisogno che il gestore imposti
		// "Scaduto" a mano. Per questo la fase "scaduto" qui sotto scrive
		// anche alla sfoglina, non solo a Ennio (vedi più sotto).
		if ( $giorni_mancanti > $giorni_preavviso || $giorni_mancanti < -3 ) { continue; }
		if ( $giorni_mancanti >= 0 && $giorni_mancanti <= 1 ) { $fase = 'ultimo'; }
		elseif ( $giorni_mancanti < 0 ) { $fase = 'scaduto'; }
		else { $fase = 'preavviso'; }

		// Il marcatore tiene scadenza + fase, così ogni fase parte una volta
		// sola. Sulle sfogline già avvisate col codice precedente,
		// gs_abbonamento_avviso_per vale solo la data: non corrisponde più a
		// nessun marcatore nuovo, quindi il preavviso riparte una volta — un
		// avviso in più, non uno in meno, accettabile e da sapere prima di
		// installare.
		$marcatore = $scadenza . '|' . $fase;
		if ( get_user_meta( $u->ID, 'gs_abbonamento_avviso_per', true ) === $marcatore ) { continue; }
		// Scritto PRIMA di mandare, non dopo: la regola della chiusura del mese.
		update_user_meta( $u->ID, 'gs_abbonamento_avviso_per', $marcatore );

		// Le tre fasi vivono nel registro (gs_mail_template_registro() in
		// mail-area-riservata.php): 'scadenza_preavviso', 'scadenza_ultimo',
		// 'scadenza_scaduto' — documento IL-PANNELLO-DELLE-MAIL.md,
		// 26/08/2026. Dal 27/08/2026 (documento TRENTA-GIORNI-IL-CANCELLO.md)
		// la fase "scaduto" scrive ANCHE alla sfoglina, non più solo a Ennio:
		// con la scadenza automatica ora vera ("è tutto salvato, si riapre
		// così"), non solo un promemoria di un aggiornamento mancato.
		if ( function_exists( 'gs_invia_mail_template' ) ) {
			gs_invia_mail_template( 'scadenza_' . $fase, $u->ID );
		}

		if ( 'scaduto' === $fase && function_exists( 'gs_inbox_crea' ) ) {
			gs_inbox_crea(
				'Congelata: ' . $u->display_name,
				$u->display_name . ' è appena uscita dal mese di prova (scadenza ' . date_i18n( 'j/m/Y', $ts ) . '). Se arriva il bonifico, riattivala dal pannello «Abbonamenti delle sfogline».',
				array( 'author' => (int) $u->ID )
			);
		}
	}
}
