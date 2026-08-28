<?php
/**
 * volo-notifiche.php — Coda di avvisi "aeroplanino" per la sfoglina.
 *
 * L'animazione dell'aeroplanino (assets/js/gaming.js) mostra uno striscione che
 * attraversa lo schermo quando succede qualcosa che riguarda la sfoglina, anche
 * mentre sta navigando altrove sul sito. I messaggi (messaggi.php) e le
 * conversazioni (conversazioni.php) hanno un proprio conteggio "non letti" e
 * quindi un proprio confronto in tempo reale; tutti gli altri eventi (badge,
 * livello, aiuto gestito, prenotazione confermata, risposta dell'esperto)
 * passano invece da questa coda: un evento del server aggiunge un testo,
 * il front-end la interroga ogni 15 secondi e la svuota mostrando l'aeroplanino.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GS_VOLI_META', 'gs_voli_pendenti' );

/**
 * Accoda un avviso "aeroplanino" per una sfoglina. Silenzioso se manca l'utente
 * o il testo: nessun errore da sollevare, è solo un avviso extra.
 *
 * $link (facoltativo): se impostato, cliccando sullo striscione mentre vola
 * si viene portati direttamente lì (es. la domanda a cui l'esperto ha
 * risposto, la lezione nuova, la prenotazione confermata).
 *
 * $aereo_id (facoltativo): id del messaggio "Aeroplanino della redazione" nello
 * storico (gs_aeroplanino_log()). Se impostato, lo striscione diventa comunque
 * cliccabile (anche senza $link) e il clic viene contato — vedi
 * gs_ajax_aeroplanino_click() — per sapere quante sfogline lo hanno notato.
 */
function gs_accoda_volo( $user_id, $testo, $link = '', $aereo_id = '', $sponsor = null ) {
	$user_id = (int) $user_id;
	$testo   = trim( (string) $testo );
	if ( ! $user_id || '' === $testo ) {
		return;
	}
	$coda = get_user_meta( $user_id, GS_VOLI_META, true );
	$coda = is_array( $coda ) ? $coda : array();
	$coda[] = array( 'testo' => $testo, 'ts' => time(), 'link' => (string) $link, 'aereo_id' => (string) $aereo_id, 'sponsor' => $sponsor ? $sponsor : null );
	// Non deve accumularsi all'infinito se la sfoglina non si collega per giorni.
	if ( count( $coda ) > 20 ) {
		$coda = array_slice( $coda, -20 );
	}
	update_user_meta( $user_id, GS_VOLI_META, $coda );
}

/**
 * AJAX: preleva e svuota la coda di avvisi della sfoglina corrente.
 *
 * Interrogato ogni 15 secondi da OGNI utente collegato (sfogline comprese),
 * su qualunque pagina del sito — è il polling più universale che il plugin
 * abbia già: per questo, dal 18/08/2026, è anche il punto in cui si
 * controllano gli invii programmati di Aeroplanino e Palloncini dovuti al
 * minuto (vedi gs_programma_esegui_dovuti() più sotto in questo file). Non
 * è un nuovo giro di richieste al server: si aggancia a uno che già gira.
 */
add_action( 'wp_ajax_gs_voli_preleva', 'gs_ajax_voli_preleva' );
function gs_ajax_voli_preleva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );

	if ( function_exists( 'gs_programma_esegui_dovuti' ) ) {
		gs_programma_esegui_dovuti( 'aeroplanino' );
		gs_programma_esegui_dovuti( 'palloncini' );
	}

	$uid = get_current_user_id();
	if ( ! $uid ) {
		wp_send_json_success( array( 'voli' => array() ) );
	}
	$coda = get_user_meta( $uid, GS_VOLI_META, true );
	$coda = is_array( $coda ) ? $coda : array();
	delete_user_meta( $uid, GS_VOLI_META );
	wp_send_json_success( array( 'voli' => $coda ) );
}

// -----------------------------------------------------------------------------
// PANNELLO — "In diretta": Aeroplanino, Palloncini e Palloncino Gigante
// riuniti in una sola zona a linguette (richiesto da Ennio il 18/08/2026,
// prima erano tre zone separate). Non tocca le tre funzioni originali —
// restano quelle di sempre, ognuna nel proprio riquadro — aggiunge solo la
// barra delle linguette sopra. Ogni linguetta rispetta ancora la propria
// voce in "Visibilità sezioni e permessi": se una è disattivata, la sua
// linguetta sparisce del tutto invece di restare vuota; se ne resta una
// sola, la barra delle linguette non compare nemmeno.
// -----------------------------------------------------------------------------
function gs_pannello_in_diretta() {
	if ( ! gs_can_manage() ) { return; }

	$possibili = array(
		'aeroplanino' => array( 'ico' => '🛩️', 'lbl' => 'Aeroplanino',         'sez' => 'aeroplanino',         'cb' => 'gs_pannello_aeroplanino' ),
		'palloncini'  => array( 'ico' => '🎈', 'lbl' => 'Palloncini',          'sez' => 'palloncini',          'cb' => 'gs_pannello_palloncini' ),
		'gigante'     => array( 'ico' => '🎈', 'lbl' => 'Palloncino Gigante',  'sez' => 'palloncino_gigante',  'cb' => 'gs_pannello_palloncino_gigante' ),
	);
	$schede = array();
	foreach ( $possibili as $key => $s ) {
		if ( function_exists( $s['cb'] ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( $s['sez'] ) ) ) {
			$schede[ $key ] = $s;
		}
	}
	if ( ! $schede ) { return; }

	echo '<div class="gs-diretta">';
	echo '<p class="gs-hint">Tre modi per far apparire qualcosa in diretta sullo schermo di chi è collegato in questo momento: un messaggio che attraversa lo schermo, una pioggia di palloncini, o un palloncino che si gonfia fino a coprire tutto.</p>';

	if ( count( $schede ) > 1 ) {
		echo '<div class="gs-diretta-tabs">';
		$i = 0;
		foreach ( $schede as $key => $s ) {
			echo '<button type="button" class="gs-diretta-tab' . ( 0 === $i ? ' on' : '' ) . '" data-tab="' . esc_attr( $key ) . '">' . $s['ico'] . ' ' . esc_html( $s['lbl'] ) . '</button>';
			$i++;
		}
		echo '</div>';
	}

	// Ogni pannello mantiene il proprio id "gs-zona-…" di sempre — servono
	// alle voci del menu di WordPress e a qualunque link salvato che punti
	// direttamente lì (es. admin.php?page=gs-generale#gs-zona-palloncini):
	// gaming.js attiva la linguetta giusta prima di scorrere fin lì.
	$id_zona = array( 'aeroplanino' => 'gs-zona-aeroplanino', 'palloncini' => 'gs-zona-palloncini', 'gigante' => 'gs-zona-palloncino-gigante' );
	$i = 0;
	foreach ( $schede as $key => $s ) {
		echo '<div class="gs-diretta-pannello" data-pannello="' . esc_attr( $key ) . '" id="' . esc_attr( $id_zona[ $key ] ) . '"' . ( 0 === $i ? '' : ' style="display:none"' ) . '>';
		call_user_func( $s['cb'] );
		echo '</div>';
		$i++;
	}
	echo '</div>';
}

// -----------------------------------------------------------------------------
// PANNELLO — Aeroplanino: messaggio istantaneo della redazione a tutte le
// sfogline collegate (striscione che attraversa lo schermo, non lascia
// traccia nella posta). Riusa la stessa coda usata per badge/livello/ecc.
// -----------------------------------------------------------------------------
function gs_pannello_aeroplanino() {
	if ( ! gs_can_manage() ) { return; }
	$sponsor_ora = gs_aeroplanino_sponsor_attivo_ora();
	echo gs_box_open( '🛩️ Aeroplanino — messaggio istantaneo', '', 'gs-box-aeroplanino' );
	echo '<p class="gs-hint">Scrivi un messaggio breve e invialo: attraversa lo schermo di ogni sfoglina collegata, entro una quindicina di secondi, come uno striscione — anche se sta navigando altrove sul sito. Non lascia traccia nella posta né in nessun elenco: è pensato per avvisi rapidi del momento (es. "il sito sarà chiuso stasera per manutenzione"), non per comunicazioni da conservare. Per quelle usa "Messaggi privati alle sfogline".</p>';

	echo '<details class="gs-sotto-sezione" open><summary>✈️ Invia adesso</summary><div class="gs-sotto-sezione-corpo">';
	?>
	<p class="gs-hint">Usa "Anteprima" per vedere sul tuo schermo come apparirà lo striscione, prima di inviarlo davvero.</p>
	<form class="gs-form gs-form-aeroplanino" onsubmit="return false">
		<p><textarea name="testo" rows="2" maxlength="200" style="width:100%" placeholder="Scrivi qui il messaggio da far volare a tutte le sfogline…"></textarea></p>
		<p><label><input type="checkbox" name="con_sponsor" <?php echo $sponsor_ora ? '' : 'disabled'; ?>> Allega il logo dello sponsor attivo in questo momento<?php echo $sponsor_ora ? ' (<strong>' . esc_html( $sponsor_ora['nome'] ) . '</strong>)' : ' <span class="gs-hint">(nessuno sponsor attivo adesso — vedi «Sponsor» qui sotto)</span>'; ?></label></p>
		<script>window.GS_SPONSOR_ORA = <?php echo $sponsor_ora ? wp_json_encode( array( 'nome' => $sponsor_ora['nome'], 'foto' => $sponsor_ora['foto'], 'url' => $sponsor_ora['url'] ) ) : 'null'; ?>;</script>
		<p>
			<button type="button" class="gs-btn gs-btn-sm gs-aeroplanino-anteprima">🔍 Anteprima</button>
			<button class="gs-btn gs-btn-sm gs-btn-verde gs-aeroplanino-invia">🛩️ Invia a tutte le sfogline</button>
			<button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-aeroplanino-cancella">Cancella</button>
			<span class="gs-aeroplanino-msg gs-richiesta-esito"></span>
		</p>
	</form>
	<?php
	echo '</div></details>';

	echo '<details class="gs-sotto-sezione"><summary>🏷️ Sponsor (' . count( gs_aeroplanino_sponsors() ) . ')</summary><div class="gs-sotto-sezione-corpo">';
	echo gs_aeroplanino_sponsors_html();
	echo '</div></details>';

	echo '<details class="gs-sotto-sezione"><summary>🗓️ Programmazione (' . count( gs_aeroplanino_programmati() ) . ')</summary><div class="gs-sotto-sezione-corpo">';
	echo gs_aeroplanino_programmati_html();
	echo '</div></details>';

	echo '<details class="gs-sotto-sezione"><summary>📜 Storico (' . count( gs_aeroplanino_log() ) . ')</summary><div class="gs-sotto-sezione-corpo">';
	echo gs_aeroplanino_storico_html();
	echo gs_aeroplanino_cestino_html();
	echo '</div></details>';

	echo gs_box_close();
}

// -----------------------------------------------------------------------------
// SPONSOR — elenco di loghi, ognuno con il proprio periodo di attività
// (richiesto da Ennio, 17/08/2026: "voglio programmato con loghi differenti
// in tempi determinati differenti", dopo un primo giro con un solo sponsor
// fisso). Ogni sponsor può essere: "sempre attivo" (sponsor di riserva,
// usato solo se nessun altro col suo periodo è attivo in questo momento),
// oppure con un periodo preciso (con o senza "si ripete ogni anno", per le
// campagne stagionali tipo Natale). Se più periodi si sovrappongono, vince
// il primo della lista che risulta attivo — l'ordine si può cambiare
// eliminando e ri-aggiungendo, non c'è (ancora) un drag&drop.
// -----------------------------------------------------------------------------

function gs_aeroplanino_sponsors() {
	$s = gs_settings();
	return isset( $s['aeroplanino_sponsors'] ) && is_array( $s['aeroplanino_sponsors'] ) ? $s['aeroplanino_sponsors'] : array();
}
function gs_aeroplanino_sponsors_salva( $lista ) {
	$s = gs_settings();
	$s['aeroplanino_sponsors'] = $lista;
	update_option( GS_OPTION, $s );
}

/**
 * Risolve quale sponsor è attivo ORA dentro a una lista di sponsor con
 * periodo — stessa identica logica usata sia dall'Aeroplanino che dai
 * Palloncini (elenchi separati, stesso risolutore: un solo posto dove
 * questa regola può sbagliarsi).
 */
function gs_sponsor_attivo_ora( $lista ) {
	if ( ! $lista ) { return null; }
	$oggi = current_time( 'Y-m-d' );
	$mese_giorno_oggi = current_time( 'm-d' );
	$fallback = null;

	foreach ( $lista as $sp ) {
		if ( empty( $sp['attivo'] ) ) { continue; }
		if ( 'sempre' === $sp['tipo'] ) {
			if ( ! $fallback ) { $fallback = $sp; }
			continue;
		}
		if ( empty( $sp['data_inizio'] ) || empty( $sp['data_fine'] ) ) { continue; }
		if ( ! empty( $sp['ripeti_ogni_anno'] ) ) {
			// Confronta solo mese-giorno, gestendo anche i periodi che
			// attraversano il capodanno (es. 20/12 → 10/01).
			$ini = substr( $sp['data_inizio'], 5 );
			$fin = substr( $sp['data_fine'], 5 );
			$dentro = ( $ini <= $fin )
				? ( $mese_giorno_oggi >= $ini && $mese_giorno_oggi <= $fin )
				: ( $mese_giorno_oggi >= $ini || $mese_giorno_oggi <= $fin );
		} else {
			$dentro = ( $oggi >= $sp['data_inizio'] && $oggi <= $sp['data_fine'] );
		}
		if ( $dentro ) { return $sp; } // periodo preciso: vince subito, non aspetta il resto della lista
	}
	return $fallback;
}

/** Lo sponsor da usare ORA per l'Aeroplanino, in base ai periodi configurati — o null se nessuno è attivo. */
function gs_aeroplanino_sponsor_attivo_ora() {
	return gs_sponsor_attivo_ora( gs_aeroplanino_sponsors() );
}

function gs_aeroplanino_sponsors_html() {
	$sponsor_ora = gs_aeroplanino_sponsor_attivo_ora();
	$out = '<p class="gs-hint">Aggiungi quanti sponsor vuoi, ognuno con il proprio logo e il proprio periodo di attività: quello dello striscione dell\'Aeroplanino, quando spunti "allega sponsor", è sempre quello il cui periodo comprende il giorno di oggi. "Sempre attivo" fa da riserva, usato solo se in quel momento nessun altro sponsor col suo periodo è attivo.</p>';
	if ( $sponsor_ora ) {
		$out .= '<p><span class="gs-blackout-stato on">ATTIVO ORA: ' . esc_html( $sponsor_ora['nome'] ) . '</span></p>';
	} else {
		$out .= '<p><span class="gs-blackout-stato off">NESSUNO SPONSOR ATTIVO ORA</span></p>';
	}

	// Dimensione del logo sullo striscione — regolabile a piacere (richiesto
	// da Ennio il 18/08/2026: "che posso ingrandirlo dal pannello come voglio").
	$s_dim = gs_settings();
	$dim_attuale = isset( $s_dim['aereo_logo_dimensione'] ) ? (int) $s_dim['aereo_logo_dimensione'] : 52;
	$out .= '<p class="gs-todo-riquadro"><label>Dimensione del logo sullo striscione '
		. '<input type="range" name="aereo_logo_dimensione" min="20" max="140" step="2" value="' . (int) $dim_attuale . '" class="gs-aereo-logo-dim-range"> '
		. '<span class="gs-aereo-logo-dim-out">' . (int) $dim_attuale . 'px</span></label> '
		. '<button type="button" class="gs-btn gs-btn-sm gs-btn-verde gs-aereo-logo-dim-salva">Salva dimensione</button> '
		. '<span class="gs-aereo-logo-dim-msg gs-richiesta-esito"></span></p>';

	$out .= '<form class="gs-form gs-form-aeroplanino-sponsor" onsubmit="return false">';
	$out .= '<p><label>Nome<br><input type="text" name="nome" style="width:100%;max-width:320px" placeholder="es. Mulino Marino"></label></p>';
	$out .= '<p><label>URL del logo<br><input type="url" name="foto" class="gs-sponsor-foto-campo" style="width:100%;max-width:420px" placeholder="https://…/logo.png"></label> <button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-sponsor-foto-media">📁 Scegli dai Media di WP</button></p>';
	$out .= '<p><label>Link (dove porta il clic sul logo, facoltativo)<br><input type="url" name="url" style="width:100%;max-width:420px" placeholder="https://…"></label></p>';
	$out .= '<p><label>Quando è attivo <select name="tipo" class="gs-aeroplanino-sponsor-tipo">'
		. '<option value="periodo">In un periodo preciso</option>'
		. '<option value="sempre">Sempre (sponsor di riserva)</option>'
		. '</select></label></p>';
	$out .= '<p class="gs-aeroplanino-sponsor-campo-periodo">';
	$out .= '<label>Dal <input type="date" name="data_inizio"></label> ';
	$out .= '<label>al <input type="date" name="data_fine"></label> ';
	$out .= '<label><input type="checkbox" name="ripeti_ogni_anno"> Si ripete ogni anno (stesso giorno e mese, es. campagna di Natale)</label>';
	$out .= '</p>';
	$out .= '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-aeroplanino-sponsor-salva">🏷️ Aggiungi sponsor</button> <span class="gs-aeroplanino-sponsor-msg gs-richiesta-esito"></span></p>';
	$out .= '</form>';

	$out .= gs_aeroplanino_sponsors_lista_html();
	return $out;
}

function gs_aeroplanino_sponsors_lista_html() {
	$lista = gs_aeroplanino_sponsors();
	$sponsor_ora = gs_aeroplanino_sponsor_attivo_ora();
	$out = '<div class="gs-todo-riquadro" id="gs-aeroplanino-sponsors-lista">';
	if ( ! $lista ) {
		$out .= '<p class="gs-hint">Nessuno sponsor configurato.</p>';
	} else {
		foreach ( $lista as $sp ) {
			$e_attivo_ora = $sponsor_ora && $sponsor_ora['id'] === $sp['id'];
			$out .= '<div class="gs-prenotazione-riga' . ( $e_attivo_ora ? ' gs-prenotazione-colore' : '' ) . '"' . ( $e_attivo_ora ? ' style="border-left-color:#1f6e37"' : '' ) . ' data-id="' . esc_attr( $sp['id'] ) . '">';
			if ( ! empty( $sp['foto'] ) ) { $out .= '<img src="' . esc_url( $sp['foto'] ) . '" alt="" style="width:28px;height:28px;border-radius:50%;object-fit:contain;background:#fff;border:1px solid var(--gs-bordo,#e3d5b8);vertical-align:middle;margin-right:6px">'; }
			$out .= '<strong>' . esc_html( $sp['nome'] ) . '</strong>';
			if ( 'sempre' === $sp['tipo'] ) {
				$out .= ' — sempre attivo (riserva)';
			} else {
				$out .= ' — dal ' . esc_html( date_i18n( 'j/m/Y', strtotime( $sp['data_inizio'] ) ) ) . ' al ' . esc_html( date_i18n( 'j/m/Y', strtotime( $sp['data_fine'] ) ) );
				$out .= ! empty( $sp['ripeti_ogni_anno'] ) ? ' (ogni anno)' : '';
			}
			$out .= ( $e_attivo_ora ? ' · <span class="gs-blackout-stato on">attivo ora</span>' : '' );
			$out .= ( empty( $sp['attivo'] ) ? ' · <span class="gs-voted">in pausa</span>' : '' );
			$out .= '<br><button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-aeroplanino-sponsor-toggle" data-id="' . esc_attr( $sp['id'] ) . '">' . ( empty( $sp['attivo'] ) ? 'Riattiva' : 'Metti in pausa' ) . '</button> '
				. '<button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-aeroplanino-sponsor-elimina" data-id="' . esc_attr( $sp['id'] ) . '">Elimina</button>';
			$out .= '</div>';
		}
	}
	$out .= '</div>';
	return $out;
}

add_action( 'wp_ajax_gs_aeroplanino_sponsor_salva', 'gs_ajax_aeroplanino_sponsor_salva' );
function gs_ajax_aeroplanino_sponsor_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$nome = isset( $_POST['nome'] ) ? sanitize_text_field( wp_unslash( $_POST['nome'] ) ) : '';
	if ( '' === $nome ) { wp_send_json_error( array( 'message' => 'Scrivi il nome dello sponsor.' ) ); }
	$tipo = isset( $_POST['tipo'] ) && 'sempre' === $_POST['tipo'] ? 'sempre' : 'periodo';

	$voce = array(
		'id'    => uniqid( 'sponsor_', true ),
		'nome'  => $nome,
		'foto'  => isset( $_POST['foto'] ) ? esc_url_raw( wp_unslash( $_POST['foto'] ) ) : '',
		'url'   => isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '',
		'tipo'  => $tipo,
		'attivo' => true,
	);
	if ( 'periodo' === $tipo ) {
		$ini = isset( $_POST['data_inizio'] ) ? sanitize_text_field( wp_unslash( $_POST['data_inizio'] ) ) : '';
		$fin = isset( $_POST['data_fine'] ) ? sanitize_text_field( wp_unslash( $_POST['data_fine'] ) ) : '';
		if ( ! $ini || ! $fin || ! strtotime( $ini ) || ! strtotime( $fin ) ) { wp_send_json_error( array( 'message' => 'Scegli una data di inizio e una di fine valide.' ) ); }
		$voce['data_inizio']      = $ini;
		$voce['data_fine']        = $fin;
		$voce['ripeti_ogni_anno'] = ! empty( $_POST['ripeti_ogni_anno'] );
	}

	$lista = gs_aeroplanino_sponsors();
	$lista[] = $voce;
	gs_aeroplanino_sponsors_salva( $lista );

	wp_send_json_success( array( 'message' => 'Sponsor aggiunto.', 'html' => gs_aeroplanino_sponsors_lista_html() ) );
}

add_action( 'wp_ajax_gs_aeroplanino_sponsor_toggle', 'gs_ajax_aeroplanino_sponsor_toggle' );
function gs_ajax_aeroplanino_sponsor_toggle() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	$lista = gs_aeroplanino_sponsors();
	foreach ( $lista as &$sp ) {
		if ( $sp['id'] === $id ) { $sp['attivo'] = empty( $sp['attivo'] ); break; }
	}
	unset( $sp );
	gs_aeroplanino_sponsors_salva( $lista );
	wp_send_json_success( array( 'html' => gs_aeroplanino_sponsors_lista_html() ) );
}

add_action( 'wp_ajax_gs_aeroplanino_sponsor_elimina', 'gs_ajax_aeroplanino_sponsor_elimina' );
function gs_ajax_aeroplanino_sponsor_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	$lista = array_values( array_filter( gs_aeroplanino_sponsors(), function ( $sp ) use ( $id ) { return $sp['id'] !== $id; } ) );
	gs_aeroplanino_sponsors_salva( $lista );
	wp_send_json_success( array( 'message' => 'Sponsor eliminato.', 'html' => gs_aeroplanino_sponsors_lista_html() ) );
}

/** Dimensione (in pixel) del logo sponsor sullo striscione dell'Aeroplanino — regolabile a piacere. */
add_action( 'wp_ajax_gs_aereo_logo_dimensione_salva', 'gs_ajax_aereo_logo_dimensione_salva' );
function gs_ajax_aereo_logo_dimensione_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$dim = max( 20, min( 140, (int) ( $_POST['dimensione'] ?? 52 ) ) );
	$s = gs_settings();
	$s['aereo_logo_dimensione'] = $dim;
	update_option( GS_OPTION, $s );
	wp_send_json_success( array( 'message' => 'Dimensione salvata.', 'dimensione' => $dim ) );
}

/** Elenco paginato dei messaggi Aeroplanino già inviati, più recenti in cima. */
function gs_aeroplanino_storico_html() {
	$log = gs_aeroplanino_log();
	$out = '';
	if ( ! $log ) {
		$out .= '<p class="gs-hint">Nessun messaggio inviato finora.</p>';
		return $out;
	}
	$out .= '<div class="gs-todo-riquadro"><table class="gs-table gs-paginate" data-per-page="10" id="gs-aeroplanino-storico"><thead><tr><th>Quando</th><th>Messaggio</th><th>Sponsor</th><th>Da</th><th>Sfogline raggiunte</th><th>Click per leggerlo</th><th></th></tr></thead><tbody>';
	foreach ( $log as $voce ) {
		$sp = ! empty( $voce['sponsor']['nome'] ) ? $voce['sponsor']['nome'] : '—';
		$out .= '<tr><td>' . esc_html( date_i18n( 'j/m/Y H:i', $voce['ts'] ) ) . '</td>'
			. '<td>' . esc_html( $voce['testo'] ) . '</td>'
			. '<td>' . esc_html( $sp ) . '</td>'
			. '<td>' . esc_html( $voce['autore'] ?? '—' ) . '</td>'
			. '<td>' . (int) ( $voce['n'] ?? 0 ) . '</td>'
			. '<td>' . ( ! empty( $voce['id'] ) ? gs_aeroplanino_click_count( $voce['id'] ) : 0 ) . '</td>'
			. '<td><button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-aeroplanino-elimina" data-id="' . esc_attr( $voce['id'] ?? '' ) . '">Elimina</button></td></tr>';
	}
	$out .= '</tbody></table></div>';
	return $out;
}

/** Cestino dei messaggi Aeroplanino eliminati dallo storico — sempre recuperabili (mai una cancellazione definitiva, coerente col resto del progetto). */
function gs_aeroplanino_cestino_html() {
	$cestino = gs_aeroplanino_log_cestino();
	$out = '<div class="gs-sezione-cestino"><h4 class="gs-titolo-cestino">🗑️ Cestino messaggi Aeroplanino</h4>';
	if ( ! $cestino ) {
		$out .= '<p class="gs-hint">Il cestino è vuoto.</p></div>';
		return $out;
	}
	$out .= '<table class="gs-table gs-paginate" data-per-page="10"><thead><tr><th>Quando</th><th>Messaggio</th><th>Da</th><th></th></tr></thead><tbody>';
	foreach ( $cestino as $voce ) {
		$out .= '<tr><td>' . esc_html( date_i18n( 'j/m/Y H:i', $voce['ts'] ) ) . '</td>'
			. '<td>' . esc_html( $voce['testo'] ) . '</td>'
			. '<td>' . esc_html( $voce['autore'] ?? '—' ) . '</td>'
			. '<td><button type="button" class="gs-btn gs-btn-sm gs-btn-verde gs-aeroplanino-ripristina" data-id="' . esc_attr( $voce['id'] ?? '' ) . '">Ripristina</button></td></tr>';
	}
	$out .= '</tbody></table></div>';
	return $out;
}

// -----------------------------------------------------------------------------
// PROGRAMMAZIONE — motore condiviso da Aeroplanino e Palloncini (richiesto
// da Ennio il 18/08/2026: prima solo all'ora, ora al minuto). Il vero
// controllo gira agganciato a gs_ajax_voli_preleva() — la coda personale
// interrogata ogni 15 secondi da OGNI utente collegato (sfogline comprese),
// vedi più sotto in questo file — così un invio parte entro 15-60 secondi
// dal minuto giusto quando c'è gente collegata. Il cron 'gs_hourly_cron'
// resta come rete di sicurezza (precisione "entro l'ora", come prima):
// garantisce che l'invio parta comunque anche se, per pura sfortuna,
// nessuno interroga il server esattamente nel minuto giusto. Se non c'è
// NESSUNO collegato in quel momento, semplicemente non parte nulla finché
// qualcuno non si ricollega (entro l'ora, dal cron) — comportamento giusto:
// questi avvisi sono pensati per chi è lì a vederli, non un invio a futura
// memoria.
// -----------------------------------------------------------------------------

/**
 * Vero se una voce di programmazione (stessa struttura per Aeroplanino e
 * Palloncini: tipo/data/giorno/mese/giorno_settimana/ora_min/ultimo_invio)
 * deve partire ORA. $modo = 'minuto' per il controllo in tempo reale
 * (preciso al minuto), $modo = 'ora' per la rete di sicurezza del cron
 * (basta l'ora giusta, come il comportamento originale). Il tipo
 * 'a_ripetizione' (ogni N minuti per una finestra di tempo, per un evento
 * dal vivo) ha senso solo in tempo reale — la rete di sicurezza oraria non
 * lo controlla, non avrebbe modo di ripetersi ogni pochi minuti comunque.
 *
 * Ritorna array( bool $deve_partire, string $chiave_periodo ): chi chiama,
 * se $deve_partire è vero, deve salvare $chiave_periodo in 'ultimo_invio'
 * per non rimandarlo due volte nello stesso periodo.
 */
function gs_programma_dovuto( $p, $modo = 'minuto' ) {
	if ( empty( $p['attivo'] ) ) { return array( false, '' ); }

	if ( 'a_ripetizione' === $p['tipo'] ) {
		if ( 'minuto' !== $modo ) { return array( false, '' ); }
		$ogni   = max( 1, (int) ( $p['ogni_minuti'] ?? 5 ) );
		$durata = max( 1, (int) ( $p['durata_minuti'] ?? 60 ) );
		$inizio = (int) ( $p['creato_ts'] ?? 0 );
		if ( ! $inizio ) { return array( false, '' ); }
		$trascorsi_min = ( time() - $inizio ) / 60;
		if ( $trascorsi_min < 0 || $trascorsi_min > $durata ) { return array( false, '' ); }
		$giro   = (int) floor( $trascorsi_min / $ogni );
		$chiave = 'giro_' . $giro;
		return array( $p['ultimo_invio'] !== $chiave, $chiave );
	}

	$oggi             = current_time( 'Y-m-d' );
	$giorno_num       = (int) current_time( 'j' );
	$mese_num         = (int) current_time( 'n' );
	$settimana_num    = (int) current_time( 'w' ); // 0 = domenica
	$chiave_anno      = current_time( 'Y' );
	$chiave_mese      = current_time( 'Y-m' );
	$chiave_settimana = current_time( 'Y-\WW' ); // anno + numero settimana ISO

	$periodo_giusto = false;
	$chiave_periodo = '';
	if ( 'una_volta' === $p['tipo'] ) {
		$periodo_giusto = ( $p['data'] === $oggi );
		$chiave_periodo = $oggi;
	} elseif ( 'ogni_anno' === $p['tipo'] ) {
		$periodo_giusto = ( (int) $p['giorno'] === $giorno_num && (int) $p['mese'] === $mese_num );
		$chiave_periodo = $chiave_anno;
	} elseif ( 'ogni_mese' === $p['tipo'] ) {
		$periodo_giusto = ( (int) $p['giorno'] === $giorno_num );
		$chiave_periodo = $chiave_mese;
	} elseif ( 'ogni_settimana' === $p['tipo'] ) {
		$periodo_giusto = ( (int) $p['giorno_settimana'] === $settimana_num );
		$chiave_periodo = $chiave_settimana;
	} elseif ( 'ogni_giorno' === $p['tipo'] ) {
		$periodo_giusto = true;
		$chiave_periodo = $oggi;
	}
	if ( ! $periodo_giusto || $p['ultimo_invio'] === $chiave_periodo ) { return array( false, '' ); }

	$ora_min = ! empty( $p['ora_min'] ) ? $p['ora_min'] : '09:00';
	if ( 'minuto' === $modo ) {
		if ( $ora_min !== current_time( 'H:i' ) ) { return array( false, '' ); }
	} else {
		if ( (int) substr( $ora_min, 0, 2 ) !== (int) current_time( 'G' ) ) { return array( false, '' ); }
	}
	return array( true, $chiave_periodo );
}

/**
 * Controlla ed esegue gli invii programmati dovuti ORA, per una delle due
 * code ('aeroplanino' o 'palloncini') — chiamata dal polling ogni 15
 * secondi (vedi gs_ajax_voli_preleva). Un lucchetto MySQL per voce (stesso
 * meccanismo già usato per l'overbooking del Calendario Corsi e per il
 * blocco anti-bruteforce del login) evita che due richieste arrivate nello
 * stesso istante — da due sfogline diverse collegate insieme — lo mandino
 * due volte. Rilegge e risalva SOLO la voce appena eseguita, non l'intera
 * lista in un colpo solo: con più invii dovuti nello stesso istante,
 * salvare tutta la lista in blocco rischierebbe di perdere l'aggiornamento
 * di uno mentre si salva quello di un altro (stesso tipo di bug già trovato
 * e corretto altrove nel plugin sui meta condivisi, 14/08/2026).
 */
function gs_programma_esegui_dovuti( $coda ) {
	$get   = 'aeroplanino' === $coda ? 'gs_aeroplanino_programmati' : 'gs_palloncini_programmati';
	$salva = 'aeroplanino' === $coda ? 'gs_aeroplanino_programmati_salva' : 'gs_palloncini_programmati_salva';

	$lista = call_user_func( $get );
	if ( ! $lista ) { return; }

	global $wpdb;
	foreach ( $lista as $p ) {
		list( $dovuto, $chiave ) = gs_programma_dovuto( $p, 'minuto' );
		if ( ! $dovuto ) { continue; }

		$nome_lock = 'gs_programma_' . $coda . '_' . $p['id'];
		$preso = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', $nome_lock ) );
		if ( ! $preso ) { continue; } // un'altra richiesta lo sta già eseguendo in questo istante

		// Ricontrolla con dati freschi: mentre aspettavamo il lucchetto,
		// un'altra richiesta potrebbe averlo già inviato.
		$fresca = call_user_func( $get );
		foreach ( $fresca as &$pf ) {
			if ( $pf['id'] !== $p['id'] ) { continue; }
			if ( $pf['ultimo_invio'] === $chiave ) { break; } // già fatto nel frattempo
			if ( 'aeroplanino' === $coda ) {
				$sponsor = ! empty( $pf['con_sponsor'] ) ? gs_aeroplanino_sponsor_attivo_ora() : null;
				gs_aeroplanino_invia_messaggio( $pf['testo'], $sponsor, 'Programmato automatico' );
			} else {
				$sponsor = ! empty( $pf['con_sponsor'] ) ? gs_palloncini_sponsor_attivo_ora() : null;
				gs_palloncini_lancia_messaggio( $pf['motivo'], $sponsor, isset( $pf['distribuzione'] ) ? $pf['distribuzione'] : 'uno' );
			}
			$pf['ultimo_invio'] = $chiave;
			call_user_func( $salva, $fresca );
			break;
		}
		unset( $pf );

		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $nome_lock ) );
	}
}

/** Elenco degli invii programmati, salvati in GS_OPTION. */
function gs_aeroplanino_programmati() {
	$s = gs_settings();
	return isset( $s['aeroplanino_programmati'] ) && is_array( $s['aeroplanino_programmati'] ) ? $s['aeroplanino_programmati'] : array();
}

function gs_aeroplanino_programmati_salva( $lista ) {
	$s = gs_settings();
	$s['aeroplanino_programmati'] = $lista;
	update_option( GS_OPTION, $s );
}

/** Pannello: form per aggiungerne uno nuovo + elenco di quelli già programmati. */
function gs_aeroplanino_programmati_html() {
	$lista = gs_aeroplanino_programmati();
	$out = '<p class="gs-hint">Il messaggio parte da solo, con lo sponsor del momento se lo spunti — al minuto preciso, se c\'è almeno una sfoglina collegata in quel momento (il controllo è agganciato al loro aggiornamento automatico ogni 15 secondi). Se proprio nessuno è collegato nel minuto esatto, parte comunque entro l\'ora, come rete di sicurezza.</p>';
	$out .= '<form class="gs-form gs-form-aeroplanino-programma" onsubmit="return false">';
	$out .= '<p><textarea name="testo" rows="2" maxlength="200" style="width:100%" placeholder="Messaggio da inviare automaticamente…"></textarea></p>';
	$out .= '<p><label><input type="checkbox" name="con_sponsor"> Allega il logo dello sponsor del momento</label></p>';
	$out .= '<p><label>Frequenza <select name="tipo" class="gs-aeroplanino-programma-tipo">'
		. '<option value="una_volta">Una volta sola, a una data precisa</option>'
		. '<option value="ogni_anno">Ogni anno, lo stesso giorno</option>'
		. '<option value="ogni_mese">Ogni mese, lo stesso giorno del mese</option>'
		. '<option value="ogni_settimana">Ogni settimana, lo stesso giorno</option>'
		. '<option value="ogni_giorno">Ogni giorno</option>'
		. '<option value="a_ripetizione">A ripetizione (es. ogni 5 minuti, per un evento dal vivo)</option>'
		. '</select></label></p>';
	$out .= '<p class="gs-aeroplanino-campo-data"><label>Data <input type="date" name="data"></label></p>';
	$out .= '<p class="gs-aeroplanino-campo-mese-giorno" style="display:none"><label>Giorno e mese (ogni anno) <input type="text" name="giorno_mese" placeholder="es. 25/12" maxlength="5" style="width:80px"></label></p>';
	$out .= '<p class="gs-aeroplanino-campo-giorno-mese" style="display:none"><label>Giorno del mese <input type="number" name="giorno" min="1" max="31" style="width:70px"></label></p>';
	$out .= '<p class="gs-aeroplanino-campo-giorno-settimana" style="display:none"><label>Giorno della settimana <select name="giorno_settimana">'
		. '<option value="1">Lunedì</option><option value="2">Martedì</option><option value="3">Mercoledì</option>'
		. '<option value="4">Giovedì</option><option value="5">Venerdì</option><option value="6">Sabato</option><option value="0">Domenica</option>'
		. '</select></label></p>';
	$out .= '<p class="gs-aeroplanino-campo-ora-min"><label>A che ora <input type="time" name="ora_min" value="09:00"></label></p>';
	$out .= '<p class="gs-aeroplanino-campo-ripetizione" style="display:none">'
		. '<label>Ogni <input type="number" name="ogni_minuti" min="1" max="180" value="5" style="width:60px"> minuti</label> '
		. '<label style="margin-left:12px">per <input type="number" name="durata_minuti" min="1" max="1440" value="60" style="width:70px"> minuti in tutto</label>'
		. '<br><span class="gs-hint">Parte dal momento in cui premi "Programma" — pensato per un evento dal vivo (es. una diretta), non per una data futura.</span></p>';
	$out .= '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-aeroplanino-programma-salva">📅 Programma</button> <span class="gs-aeroplanino-programma-msg gs-richiesta-esito"></span></p>';
	$out .= '</form>';
	$out .= gs_aeroplanino_programmati_lista_html();
	return $out;
}

/**
 * Solo la lista (senza il form sopra): usata sia per il primo caricamento
 * della pagina (dentro gs_aeroplanino_programmati_html) sia come pezzo da
 * sostituire via AJAX dopo salva/pausa/elimina, senza duplicare il form.
 */
function gs_aeroplanino_programmati_lista_html() {
	return gs_programmati_lista_html_render( gs_aeroplanino_programmati(), 'gs-aeroplanino-programmati-lista', 'gs-aeroplanino-programma' );
}

/**
 * Reso generico per essere usato sia dall'Aeroplanino che dai Palloncini
 * (stessa struttura di voce, stessi campi) — solo l'id del contenitore e il
 * prefisso delle classi dei pulsanti cambiano tra i due.
 */
function gs_programmati_lista_html_render( $lista, $id_contenitore, $prefisso_classe ) {
	$etichette_tipo = array(
		'una_volta'      => 'Una volta',
		'ogni_anno'      => 'Ogni anno',
		'ogni_mese'      => 'Ogni mese',
		'ogni_settimana' => 'Ogni settimana',
		'ogni_giorno'    => 'Ogni giorno',
		'a_ripetizione'  => 'A ripetizione',
	);
	$giorni_settimana = array( 'Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato' );

	$out = '<div class="gs-todo-riquadro" id="' . esc_attr( $id_contenitore ) . '">';
	if ( ! $lista ) {
		$out .= '<p class="gs-hint">Nessun invio programmato al momento.</p>';
	} else {
		foreach ( $lista as $p ) {
			$out .= '<div class="gs-prenotazione-riga" data-id="' . esc_attr( $p['id'] ) . '">';
			$out .= '<strong>' . esc_html( $etichette_tipo[ $p['tipo'] ] ?? $p['tipo'] ) . '</strong> — ';
			if ( 'a_ripetizione' === $p['tipo'] ) {
				$fine = ( (int) ( $p['creato_ts'] ?? 0 ) ) + ( (int) ( $p['durata_minuti'] ?? 0 ) ) * 60;
				$in_corso = time() < $fine;
				$out .= esc_html( 'ogni ' . (int) ( $p['ogni_minuti'] ?? 0 ) . ' minuti, per ' . (int) ( $p['durata_minuti'] ?? 0 ) . ' minuti in tutto' );
				$out .= $in_corso ? ' · <span class="gs-blackout-stato on">in corso</span>' : ' · <span class="gs-hint">finita</span>';
			} else {
				$quando = '';
				if ( 'una_volta' === $p['tipo'] ) {
					$quando = ! empty( $p['data'] ) ? date_i18n( 'j/m/Y', strtotime( $p['data'] ) ) : '—';
				} elseif ( 'ogni_anno' === $p['tipo'] ) {
					$quando = sprintf( '%02d/%02d ogni anno', (int) $p['giorno'], (int) $p['mese'] );
				} elseif ( 'ogni_mese' === $p['tipo'] ) {
					$quando = 'giorno ' . (int) $p['giorno'] . ' di ogni mese';
				} elseif ( 'ogni_settimana' === $p['tipo'] ) {
					$quando = 'ogni ' . ( $giorni_settimana[ (int) $p['giorno_settimana'] ] ?? '?' );
				} elseif ( 'ogni_giorno' === $p['tipo'] ) {
					$quando = 'ogni giorno';
				}
				$ora_min = ! empty( $p['ora_min'] ) ? $p['ora_min'] : sprintf( '%02d:00', (int) ( $p['ora'] ?? 9 ) );
				$quando .= ', alle ' . $ora_min;
				$out .= esc_html( $quando );
			}
			$out .= ( ! empty( $p['con_sponsor'] ) ? ' · 🏷️ con sponsor' : '' );
			$out .= ( empty( $p['attivo'] ) ? ' · <span class="gs-voted">in pausa</span>' : '' );
			$out .= '<br>' . esc_html( isset( $p['testo'] ) ? $p['testo'] : ( isset( $p['motivo'] ) ? ucfirst( $p['motivo'] ) : '' ) );
			if ( ! empty( $p['ultimo_invio'] ) ) { $out .= '<br><span class="gs-hint">Ultimo invio: ' . esc_html( $p['ultimo_invio'] ) . '</span>'; }
			$out .= '<br><button type="button" class="gs-btn gs-btn-sm gs-btn-ghost ' . esc_attr( $prefisso_classe ) . '-toggle" data-id="' . esc_attr( $p['id'] ) . '">' . ( empty( $p['attivo'] ) ? 'Riattiva' : 'Metti in pausa' ) . '</button> '
				. '<button type="button" class="gs-btn gs-btn-sm gs-btn-ghost ' . esc_attr( $prefisso_classe ) . '-elimina" data-id="' . esc_attr( $p['id'] ) . '">Elimina</button>';
			$out .= '</div>';
		}
	}
	$out .= '</div>';
	return $out;
}

add_action( 'wp_ajax_gs_aeroplanino_programma_salva', 'gs_ajax_aeroplanino_programma_salva' );
function gs_ajax_aeroplanino_programma_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$testo = isset( $_POST['testo'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['testo'] ) ) ) : '';
	if ( mb_strlen( $testo ) < 2 ) { wp_send_json_error( array( 'message' => 'Scrivi un messaggio prima di programmarlo.' ) ); }
	$tipo = isset( $_POST['tipo'] ) ? sanitize_key( wp_unslash( $_POST['tipo'] ) ) : '';
	$voce = gs_programma_valida_e_costruisci( $tipo );
	if ( is_wp_error( $voce ) ) { wp_send_json_error( array( 'message' => $voce->get_error_message() ) ); }
	$voce['testo']       = $testo;
	$voce['con_sponsor'] = ! empty( $_POST['con_sponsor'] );

	$lista = gs_aeroplanino_programmati();
	$lista[] = $voce;
	gs_aeroplanino_programmati_salva( $lista );

	wp_send_json_success( array( 'message' => 'Invio programmato.', 'html' => gs_aeroplanino_programmati_lista_html() ) );
}

/**
 * Legge dal $_POST e valida i campi comuni di una voce di programmazione
 * (tipo/data/giorno/mese/giorno_settimana/ora_min/a_ripetizione) — condiviso
 * da Aeroplanino e Palloncini, che aggiungono poi i propri campi (testo o
 * motivo) sopra al risultato. Ritorna la voce pronta, o un WP_Error col
 * messaggio da mostrare.
 */
function gs_programma_valida_e_costruisci( $tipo ) {
	$tipi_validi = array( 'una_volta', 'ogni_anno', 'ogni_mese', 'ogni_settimana', 'ogni_giorno', 'a_ripetizione' );
	if ( ! in_array( $tipo, $tipi_validi, true ) ) { return new WP_Error( 'gs_programma', 'Frequenza non valida.' ); }

	$voce = array(
		'id'           => uniqid( 'programma_', true ),
		'tipo'         => $tipo,
		'attivo'       => true,
		'ultimo_invio' => '',
		'creato_ts'    => time(),
	);

	if ( 'a_ripetizione' === $tipo ) {
		$ogni   = max( 1, min( 180, (int) ( $_POST['ogni_minuti'] ?? 5 ) ) );
		$durata = max( 1, min( 1440, (int) ( $_POST['durata_minuti'] ?? 60 ) ) );
		$voce['ogni_minuti']   = $ogni;
		$voce['durata_minuti'] = $durata;
		return $voce;
	}

	$ora_min = isset( $_POST['ora_min'] ) ? sanitize_text_field( wp_unslash( $_POST['ora_min'] ) ) : '';
	if ( ! preg_match( '/^([01]\d|2[0-3]):([0-5]\d)$/', $ora_min ) ) { $ora_min = '09:00'; }
	$voce['ora_min'] = $ora_min;

	if ( 'una_volta' === $tipo ) {
		$data = isset( $_POST['data'] ) ? sanitize_text_field( wp_unslash( $_POST['data'] ) ) : '';
		if ( ! $data || ! strtotime( $data ) ) { return new WP_Error( 'gs_programma', 'Scegli una data valida.' ); }
		$voce['data'] = $data;
	} elseif ( 'ogni_anno' === $tipo ) {
		$gm = isset( $_POST['giorno_mese'] ) ? sanitize_text_field( wp_unslash( $_POST['giorno_mese'] ) ) : '';
		if ( ! preg_match( '/^(\d{1,2})\/(\d{1,2})$/', $gm, $m ) ) { return new WP_Error( 'gs_programma', 'Scrivi giorno e mese come "25/12".' ); }
		$giorno = (int) $m[1]; $mese = (int) $m[2];
		if ( $giorno < 1 || $giorno > 31 || $mese < 1 || $mese > 12 ) { return new WP_Error( 'gs_programma', 'Giorno o mese non validi.' ); }
		$voce['giorno'] = $giorno; $voce['mese'] = $mese;
	} elseif ( 'ogni_mese' === $tipo ) {
		$giorno = max( 1, min( 31, (int) ( $_POST['giorno'] ?? 0 ) ) );
		if ( ! $giorno ) { return new WP_Error( 'gs_programma', 'Scegli il giorno del mese.' ); }
		$voce['giorno'] = $giorno;
	} elseif ( 'ogni_settimana' === $tipo ) {
		$voce['giorno_settimana'] = max( 0, min( 6, (int) ( $_POST['giorno_settimana'] ?? 1 ) ) );
	}
	// 'ogni_giorno' non ha campi in più.

	return $voce;
}

add_action( 'wp_ajax_gs_aeroplanino_programma_toggle', 'gs_ajax_aeroplanino_programma_toggle' );
function gs_ajax_aeroplanino_programma_toggle() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	$lista = gs_aeroplanino_programmati();
	foreach ( $lista as &$p ) {
		if ( $p['id'] === $id ) { $p['attivo'] = empty( $p['attivo'] ); break; }
	}
	unset( $p );
	gs_aeroplanino_programmati_salva( $lista );
	wp_send_json_success( array( 'html' => gs_aeroplanino_programmati_lista_html() ) );
}

add_action( 'wp_ajax_gs_aeroplanino_programma_elimina', 'gs_ajax_aeroplanino_programma_elimina' );
function gs_ajax_aeroplanino_programma_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	$lista = array_values( array_filter( gs_aeroplanino_programmati(), function ( $p ) use ( $id ) { return $p['id'] !== $id; } ) );
	gs_aeroplanino_programmati_salva( $lista );
	wp_send_json_success( array( 'message' => 'Programmazione eliminata.', 'html' => gs_aeroplanino_programmati_lista_html() ) );
}

/**
 * Girato una volta all'ora dal cron già esistente 'gs_hourly_cron': controlla
 * ogni invio programmato attivo e lo manda se è il momento giusto, evitando
 * di rimandarlo due volte nello stesso periodo (anno/mese/settimana/giorno)
 * confrontando 'ultimo_invio' con la chiave del periodo corrente.
 */
/**
 * Rete di sicurezza oraria per una delle due code — "entro l'ora", come il
 * comportamento originale, per garantire che un invio parta comunque anche
 * se nessuno è mai stato collegato esattamente nel minuto giusto (vedi
 * gs_programma_esegui_dovuti() per il controllo preciso al minuto, quello
 * vero, agganciato al polling).
 */
function gs_programma_cron_orario( $coda ) {
	$get   = 'aeroplanino' === $coda ? 'gs_aeroplanino_programmati' : 'gs_palloncini_programmati';
	$salva = 'aeroplanino' === $coda ? 'gs_aeroplanino_programmati_salva' : 'gs_palloncini_programmati_salva';
	$lista = call_user_func( $get );
	if ( ! $lista ) { return; }

	$cambiato = false;
	foreach ( $lista as &$p ) {
		list( $dovuto, $chiave ) = gs_programma_dovuto( $p, 'ora' );
		if ( ! $dovuto ) { continue; }
		if ( 'aeroplanino' === $coda ) {
			$sponsor = ! empty( $p['con_sponsor'] ) ? gs_aeroplanino_sponsor_attivo_ora() : null;
			gs_aeroplanino_invia_messaggio( $p['testo'], $sponsor, 'Programmato automatico' );
		} else {
			$sponsor = ! empty( $p['con_sponsor'] ) ? gs_palloncini_sponsor_attivo_ora() : null;
			gs_palloncini_lancia_messaggio( $p['motivo'], $sponsor, isset( $p['distribuzione'] ) ? $p['distribuzione'] : 'uno' );
		}
		$p['ultimo_invio'] = $chiave;
		$cambiato = true;
	}
	unset( $p );

	if ( $cambiato ) { call_user_func( $salva, $lista ); }
}
add_action( 'gs_hourly_cron', 'gs_aeroplanino_cron_esegui' );
function gs_aeroplanino_cron_esegui() {
	gs_programma_cron_orario( 'aeroplanino' );
	gs_programma_cron_orario( 'palloncini' );
}

/**
 * AJAX: una sfoglina ha cliccato sullo striscione dell'Aeroplanino della
 * redazione per leggerlo — incrementa il contatore del messaggio
 * corrispondente. Nessun controllo gs_can_manage(): la chiama chi RICEVE il
 * messaggio, non chi lo gestisce.
 *
 * Il contatore NON vive dentro GS_OPTION (a differenza di tutte le altre
 * impostazioni del plugin): con centinaia di sfogline che cliccano nello
 * stesso istante, un leggi-poi-scrivi su un'unica opzione condivisa perde
 * sistematicamente aggiornamenti (due richieste leggono lo stesso valore
 * prima che la prima abbia salvato, la seconda sovrascrive la prima) — testato
 * il 2026-08-14 con un carico simulato: a 50 click simultanei ne risultavano
 * contati solo 25, sempre circa la metà persa a ogni livello di concorrenza.
 * Ogni messaggio ha invece una sua opzione dedicata ('gs_aereo_click_{id}'),
 * incrementata con un UPDATE SQL diretto — atomico per costruzione, MySQL
 * stesso serializza gli accessi concorrenti alla stessa riga, senza bisogno
 * di leggere il valore in PHP prima di scriverlo.
 */
add_action( 'wp_ajax_gs_aeroplanino_click', 'gs_ajax_aeroplanino_click' );
function gs_ajax_aeroplanino_click() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	if ( '' !== $id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->options} SET option_value = option_value + 1 WHERE option_name = %s",
			'gs_aereo_click_' . $id
		) );
	}
	wp_send_json_success();
}

/** Quante sfogline hanno cliccato per leggere un dato messaggio Aeroplanino. */
function gs_aeroplanino_click_count( $log_id ) {
	return (int) get_option( 'gs_aereo_click_' . $log_id, 0 );
}

/** Storico di tutti i messaggi Aeroplanino inviati, più recente in cima. */
function gs_aeroplanino_log() {
	$s = gs_settings();
	$log = isset( $s['aeroplanino_log'] ) && is_array( $s['aeroplanino_log'] ) ? $s['aeroplanino_log'] : array();
	return $log;
}

/** Cestino dello storico Aeroplanino: voci eliminate dallo storico, mai perse per sempre. */
function gs_aeroplanino_log_cestino() {
	$s = gs_settings();
	return isset( $s['aeroplanino_log_cestino'] ) && is_array( $s['aeroplanino_log_cestino'] ) ? $s['aeroplanino_log_cestino'] : array();
}

/**
 * Sposta una voce dello storico Aeroplanino nel suo cestino — reversibile,
 * mai una cancellazione vera (Ennio, 16/08/2026: "crea un cestino con
 * messaggi recuperabili"). Diverso dal Cestino di "Messaggi alle sfogline"
 * (messaggi.php, basato su wp_trash_post): qui lo storico non è un CPT ma un
 * array dentro GS_OPTION, quindi si sposta a mano tra due array.
 */
add_action( 'wp_ajax_gs_aeroplanino_elimina', 'gs_ajax_aeroplanino_elimina' );
function gs_ajax_aeroplanino_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	if ( '' === $id ) { wp_send_json_error( array( 'message' => 'Messaggio non valido.' ) ); }

	$s   = gs_settings();
	$log = isset( $s['aeroplanino_log'] ) && is_array( $s['aeroplanino_log'] ) ? $s['aeroplanino_log'] : array();
	$cestino = isset( $s['aeroplanino_log_cestino'] ) && is_array( $s['aeroplanino_log_cestino'] ) ? $s['aeroplanino_log_cestino'] : array();

	$trovato = null;
	foreach ( $log as $k => $voce ) {
		if ( isset( $voce['id'] ) && $voce['id'] === $id ) { $trovato = $voce; unset( $log[ $k ] ); break; }
	}
	if ( null === $trovato ) { wp_send_json_error( array( 'message' => 'Messaggio non trovato.' ) ); }

	array_unshift( $cestino, $trovato );
	$s['aeroplanino_log']         = array_values( $log );
	$s['aeroplanino_log_cestino'] = $cestino;
	update_option( GS_OPTION, $s );

	wp_send_json_success( array( 'message' => 'Spostato nel cestino.' ) );
}

/** Ripristina una voce dal cestino dello storico Aeroplanino, rimettendola in cima allo storico. */
add_action( 'wp_ajax_gs_aeroplanino_ripristina', 'gs_ajax_aeroplanino_ripristina' );
function gs_ajax_aeroplanino_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	if ( '' === $id ) { wp_send_json_error( array( 'message' => 'Messaggio non valido.' ) ); }

	$s   = gs_settings();
	$log = isset( $s['aeroplanino_log'] ) && is_array( $s['aeroplanino_log'] ) ? $s['aeroplanino_log'] : array();
	$cestino = isset( $s['aeroplanino_log_cestino'] ) && is_array( $s['aeroplanino_log_cestino'] ) ? $s['aeroplanino_log_cestino'] : array();

	$trovato = null;
	foreach ( $cestino as $k => $voce ) {
		if ( isset( $voce['id'] ) && $voce['id'] === $id ) { $trovato = $voce; unset( $cestino[ $k ] ); break; }
	}
	if ( null === $trovato ) { wp_send_json_error( array( 'message' => 'Messaggio non trovato nel cestino.' ) ); }

	array_unshift( $log, $trovato );
	$s['aeroplanino_log']         = $log;
	$s['aeroplanino_log_cestino'] = array_values( $cestino );
	update_option( GS_OPTION, $s );

	wp_send_json_success( array( 'message' => 'Messaggio ripristinato.' ) );
}

/**
 * Nucleo vero dell'invio (richiesto da Ennio il 17/08/2026, estratto da
 * gs_ajax_aeroplanino_invia per poterlo richiamare anche dal cron della
 * programmazione automatica, non solo dal pulsante manuale). $sponsor è
 * l'array nome/foto/url da gs_aeroplanino_sponsor_attivo_ora(), o null per
 * nessuno sponsor. Restituisce n, log_id, ts, autore.
 */
function gs_aeroplanino_invia_messaggio( $testo, $sponsor = null, $autore_nome = '' ) {
	$testo = trim( (string) $testo );
	if ( '' === $testo || ! function_exists( 'gs_sez_sfogline' ) ) {
		return array( 'n' => 0, 'log_id' => '', 'ts' => time(), 'autore' => $autore_nome );
	}

	// Id univoco di questo invio: usato per contare quante sfogline cliccano
	// sullo striscione per "vederlo" — vedi gs_ajax_aeroplanino_click().
	$log_id = uniqid( 'aereo_', true );

	$n = 0;
	foreach ( gs_sez_sfogline() as $u ) {
		gs_accoda_volo( $u->ID, $testo, '', $log_id, $sponsor );
		$n++;
	}

	// Salvato a parte (non nella coda per-utente, che si svuota al primo
	// dispositivo che la legge): chi gestisce il portale deve vederlo su
	// TUTTI i suoi dispositivi collegati in quel momento, computer e
	// telefono insieme — vedi gs_ajax_aeroplanino_ultimo().
	$s = gs_settings();
	$s['aeroplanino_ultimo'] = array( 'testo' => $testo, 'ts' => time(), 'sponsor' => $sponsor );

	// Storico permanente (richiesto da Ennio il 2026-08-11): a differenza
	// della coda per-sfoglina e di 'aeroplanino_ultimo' — pensati apposta per
	// essere effimeri — questo elenco resta, per poter ritrovare cosa e
	// quando è stato mandato. Tetto a 200 voci per non crescere all'infinito.
	if ( ! isset( $s['aeroplanino_log'] ) || ! is_array( $s['aeroplanino_log'] ) ) {
		$s['aeroplanino_log'] = array();
	}
	if ( ! $autore_nome ) {
		$mittente = wp_get_current_user();
		$autore_nome = $mittente && $mittente->exists() ? $mittente->display_name : '—';
	}
	array_unshift( $s['aeroplanino_log'], array(
		'id'      => $log_id,
		'testo'   => $testo,
		'ts'      => time(),
		'autore'  => $autore_nome,
		'n'       => $n,
		'sponsor' => $sponsor,
	) );
	// Contatore dei click "l'ho letto" (richiesto da Ennio il 2026-08-14):
	// creato subito a 0 in una sua opzione dedicata — vedi
	// gs_ajax_aeroplanino_click() sul perché non vive dentro $s.
	add_option( 'gs_aereo_click_' . $log_id, 0, '', false );
	if ( count( $s['aeroplanino_log'] ) > 200 ) {
		$tolte = array_splice( $s['aeroplanino_log'], 200 );
		foreach ( $tolte as $voce_tolta ) {
			if ( ! empty( $voce_tolta['id'] ) ) {
				delete_option( 'gs_aereo_click_' . $voce_tolta['id'] );
			}
		}
	}

	update_option( GS_OPTION, $s );

	return array( 'n' => $n, 'log_id' => $log_id, 'ts' => time(), 'autore' => $autore_nome );
}

/** Manda un avviso "aeroplanino" a tutte le sfogline approvate (non ai gestori). */
add_action( 'wp_ajax_gs_aeroplanino_invia', 'gs_ajax_aeroplanino_invia' );
function gs_ajax_aeroplanino_invia() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$testo = isset( $_POST['testo'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['testo'] ) ) ) : '';
	if ( mb_strlen( $testo ) < 2 ) { wp_send_json_error( array( 'message' => 'Scrivi un messaggio prima di inviarlo.' ) ); }
	if ( ! function_exists( 'gs_sez_sfogline' ) ) { wp_send_json_error( array( 'message' => 'Funzione non disponibile.' ) ); }

	$sponsor = ! empty( $_POST['con_sponsor'] ) ? gs_aeroplanino_sponsor_attivo_ora() : null;
	$r = gs_aeroplanino_invia_messaggio( $testo, $sponsor );

	wp_send_json_success( array(
		'ts'      => $r['ts'],
		'n'       => $r['n'],
		'autore'  => $r['autore'],
		'quando'  => date_i18n( 'j/m/Y H:i', time() ),
		'message' => $r['n']
			? 'Messaggio inviato: arriverà entro una quindicina di secondi a ' . $r['n'] . ' sfoglin' . ( 1 === $r['n'] ? 'a' : 'e' ) . ' collegate.'
			: 'Nessuna sfoglina registrata al momento.',
	) );
}

/**
 * Interrogato ogni 15 secondi da chi gestisce il portale (titolare o
 * collaboratore), su ogni dispositivo/sessione: restituisce l'ultimo
 * messaggio "aeroplanino" inviato dalla redazione, con il suo timestamp.
 * Non si "consuma": ogni dispositivo confronta il timestamp con l'ultimo
 * che ha già mostrato (salvato nel proprio localStorage) e decide da solo
 * se farlo volare — così compare su tutti insieme, non solo sul primo che
 * lo legge.
 */
add_action( 'wp_ajax_gs_aeroplanino_ultimo', 'gs_ajax_aeroplanino_ultimo' );
function gs_ajax_aeroplanino_ultimo() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$s      = gs_settings();
	$ultimo = isset( $s['aeroplanino_ultimo'] ) && is_array( $s['aeroplanino_ultimo'] ) ? $s['aeroplanino_ultimo'] : array();
	wp_send_json_success( array(
		'testo'   => isset( $ultimo['testo'] ) ? $ultimo['testo'] : '',
		'ts'      => isset( $ultimo['ts'] ) ? (int) $ultimo['ts'] : 0,
		'sponsor' => isset( $ultimo['sponsor'] ) ? $ultimo['sponsor'] : null,
	) );
}

// -----------------------------------------------------------------------------
// PANNELLO — Palloncini: festeggiamenti in diretta (compleanno/diploma/festa),
// stesso posto dell'Aeroplanino qui sopra, su richiesta di Ennio (2026-08-07).
// Nati come demo standalone (2026-08-05_demo-effetto-palloncini.html),
// integrati qui nel pannello vero. A differenza dell'aeroplanino della
// redazione (solo chi gestisce, coda personale per ogni sfoglina), i
// palloncini li vede OGNI utente collegato in quel momento: stesso
// meccanismo "ultimo inviato + timestamp" già usato per l'aeroplanino della
// redazione (gs_ajax_aeroplanino_ultimo), ma senza il filtro gs_can_manage()
// sulla lettura — sono pensati per festeggiare insieme, non un avviso di
// servizio riservato a chi gestisce.
// -----------------------------------------------------------------------------
function gs_pannello_palloncini() {
	if ( ! gs_can_manage() ) { return; }
	$sponsor_ora = gs_palloncini_sponsor_attivo_ora();
	echo gs_box_open( '🎈 Palloncini — festeggiamenti in diretta', '', 'gs-box-palloncini' );
	echo '<p class="gs-hint">Un clic e i palloncini si gonfiano, salgono ondeggiando e scoppiano — sullo schermo di <strong>ogni sfoglina collegata</strong> in quel momento, ovunque stia navigando sul sito, con il suono dello scoppio. Come l\'Aeroplanino qui sopra, non lascia traccia da nessuna parte: è pensato per festeggiare insieme un momento (un compleanno, un diploma, una bella notizia), non per un annuncio da conservare.</p>';

	echo '<details class="gs-sotto-sezione" open><summary>🎈 Lancia adesso</summary><div class="gs-sotto-sezione-corpo">';
	?>
	<p>
		<button type="button" class="gs-btn gs-btn-sm gs-palloncini-lancia" data-motivo="compleanno">🎂 Compleanno</button>
		<button type="button" class="gs-btn gs-btn-sm gs-palloncini-lancia" data-motivo="diploma">🎓 Diploma</button>
		<button type="button" class="gs-btn gs-btn-sm gs-palloncini-lancia" data-motivo="festa">🎉 Festeggia</button>
	</p>
	<p><label><input type="checkbox" class="gs-palloncini-con-sponsor" <?php echo $sponsor_ora ? '' : 'disabled'; ?>> Metti il logo dello sponsor attivo in questo momento<?php echo $sponsor_ora ? ' (<strong>' . esc_html( $sponsor_ora['nome'] ) . '</strong>)' : ' <span class="gs-hint">(nessuno sponsor attivo adesso — vedi «Sponsor» qui sotto)</span>'; ?></label></p>
	<p class="gs-palloncini-distribuzione-riga" style="display:none">Il logo va su:
		<label><input type="radio" name="gs-palloncini-distribuzione" value="uno" checked> un solo palloncino</label>
		<label style="margin-left:14px"><input type="radio" name="gs-palloncini-distribuzione" value="tutti"> tutti i palloncini</label>
	</p>
	<p><span class="gs-palloncini-msg gs-richiesta-esito"></span></p>
	<?php
	echo '</div></details>';

	echo '<details class="gs-sotto-sezione"><summary>🏷️ Sponsor (' . count( gs_palloncini_sponsors() ) . ')</summary><div class="gs-sotto-sezione-corpo">';
	echo gs_palloncini_sponsors_html();
	echo '</div></details>';

	echo '<details class="gs-sotto-sezione"><summary>🗓️ Programmazione (' . count( gs_palloncini_programmati() ) . ')</summary><div class="gs-sotto-sezione-corpo">';
	echo gs_palloncini_programmati_html();
	echo '</div></details>';

	echo gs_box_close();
}

/**
 * Nucleo vero del lancio dei palloncini — riusato sia dall'invio manuale che
 * dalla programmazione automatica, stessa idea di gs_aeroplanino_invia_messaggio().
 */
function gs_palloncini_lancia_messaggio( $motivo, $sponsor = null, $distribuzione = 'uno' ) {
	$s = gs_settings();
	$s['palloncini_ultimo'] = array( 'motivo' => $motivo, 'ts' => time(), 'sponsor' => $sponsor, 'distribuzione' => $distribuzione );
	update_option( GS_OPTION, $s );
}

/** Fa partire i palloncini per tutte le sfogline (e i gestori) collegati in quel momento. */
add_action( 'wp_ajax_gs_palloncini_invia', 'gs_ajax_palloncini_invia' );
function gs_ajax_palloncini_invia() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$motivo = isset( $_POST['motivo'] ) ? sanitize_key( wp_unslash( $_POST['motivo'] ) ) : '';
	if ( ! in_array( $motivo, array( 'compleanno', 'diploma', 'festa' ), true ) ) {
		wp_send_json_error( array( 'message' => 'Motivo non valido.' ) );
	}
	$distribuzione = ( isset( $_POST['distribuzione'] ) && 'tutti' === $_POST['distribuzione'] ) ? 'tutti' : 'uno';
	$sponsor = null;
	if ( ! empty( $_POST['con_sponsor'] ) ) {
		$sp = gs_palloncini_sponsor_attivo_ora();
		if ( $sp ) { $sponsor = array( 'nome' => $sp['nome'], 'foto' => $sp['foto'], 'url' => $sp['url'] ); }
	}

	gs_palloncini_lancia_messaggio( $motivo, $sponsor, $distribuzione );

	wp_send_json_success( array( 'message' => 'Palloncini in arrivo su tutti gli schermi collegati.', 'sponsor' => $sponsor ) );
}

/**
 * Interrogato ogni 15 secondi da OGNI utente collegato (sfogline comprese —
 * a differenza di gs_ajax_aeroplanino_ultimo(), riservato ai gestori): stesso
 * confronto "timestamp dell'ultimo lancio vs. ultimo già mostrato sul proprio
 * dispositivo" salvato in localStorage, così compaiono insieme su tutti gli
 * schermi collegati invece che solo sul primo che interroga il server.
 */
add_action( 'wp_ajax_gs_palloncini_ultimo', 'gs_ajax_palloncini_ultimo' );
function gs_ajax_palloncini_ultimo() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! is_user_logged_in() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$s      = gs_settings();
	$ultimo = isset( $s['palloncini_ultimo'] ) && is_array( $s['palloncini_ultimo'] ) ? $s['palloncini_ultimo'] : array();
	wp_send_json_success( array(
		'motivo'        => isset( $ultimo['motivo'] ) ? $ultimo['motivo'] : '',
		'ts'            => isset( $ultimo['ts'] ) ? (int) $ultimo['ts'] : 0,
		'sponsor'       => isset( $ultimo['sponsor'] ) ? $ultimo['sponsor'] : null,
		'distribuzione' => isset( $ultimo['distribuzione'] ) ? $ultimo['distribuzione'] : 'uno',
	) );
}

// -----------------------------------------------------------------------------
// SPONSOR PALLONCINI — stessa identica metodologia dell'Aeroplanino qui sopra
// (elenco di loghi, ognuno col proprio periodo, risolto da gs_sponsor_attivo_ora()),
// ma un elenco SEPARATO: Ennio può avere sponsor diversi sui palloncini
// rispetto all'aeroplanino, con periodi diversi (richiesto il 18/08/2026). In
// più, qui si sceglie anche SE il logo va su un solo palloncino della
// lanciata o su tutti (vedi gs_ajax_palloncini_invia sopra).
// -----------------------------------------------------------------------------

function gs_palloncini_sponsors() {
	$s = gs_settings();
	return isset( $s['palloncini_sponsors'] ) && is_array( $s['palloncini_sponsors'] ) ? $s['palloncini_sponsors'] : array();
}
function gs_palloncini_sponsors_salva( $lista ) {
	$s = gs_settings();
	$s['palloncini_sponsors'] = $lista;
	update_option( GS_OPTION, $s );
}
function gs_palloncini_sponsor_attivo_ora() {
	return gs_sponsor_attivo_ora( gs_palloncini_sponsors() );
}

function gs_palloncini_sponsors_html() {
	$sponsor_ora = gs_palloncini_sponsor_attivo_ora();
	$out = '<p class="gs-hint">Aggiungi quanti sponsor vuoi, ognuno con il proprio logo e il proprio periodo di attività — stessa logica dell\'Aeroplanino, ma un elenco a parte: qui puoi avere sponsor diversi sui palloncini. "Sempre attivo" fa da riserva, usato solo se in quel momento nessun altro sponsor col suo periodo è attivo.</p>';
	if ( $sponsor_ora ) {
		$out .= '<p><span class="gs-blackout-stato on">ATTIVO ORA: ' . esc_html( $sponsor_ora['nome'] ) . '</span></p>';
	} else {
		$out .= '<p><span class="gs-blackout-stato off">NESSUNO SPONSOR ATTIVO ORA</span></p>';
	}

	$out .= '<form class="gs-form gs-form-palloncini-sponsor" onsubmit="return false">';
	$out .= '<p><label>Nome<br><input type="text" name="nome" style="width:100%;max-width:320px" placeholder="es. Mulino Marino"></label></p>';
	$out .= '<p><label>URL del logo<br><input type="url" name="foto" class="gs-sponsor-foto-campo" style="width:100%;max-width:420px" placeholder="https://…/logo.png"></label> <button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-sponsor-foto-media">📁 Scegli dai Media di WP</button></p>';
	$out .= '<p><label>Link (dove porta il clic sul logo, facoltativo)<br><input type="url" name="url" style="width:100%;max-width:420px" placeholder="https://…"></label></p>';
	$out .= '<p><label>Quando è attivo <select name="tipo" class="gs-palloncini-sponsor-tipo">'
		. '<option value="periodo">In un periodo preciso</option>'
		. '<option value="sempre">Sempre (sponsor di riserva)</option>'
		. '</select></label></p>';
	$out .= '<p class="gs-palloncini-sponsor-campo-periodo">';
	$out .= '<label>Dal <input type="date" name="data_inizio"></label> ';
	$out .= '<label>al <input type="date" name="data_fine"></label> ';
	$out .= '<label><input type="checkbox" name="ripeti_ogni_anno"> Si ripete ogni anno (stesso giorno e mese, es. campagna di Natale)</label>';
	$out .= '</p>';
	$out .= '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-palloncini-sponsor-salva">🏷️ Aggiungi sponsor</button> <span class="gs-palloncini-sponsor-msg gs-richiesta-esito"></span></p>';
	$out .= '</form>';

	$out .= gs_palloncini_sponsors_lista_html();
	return $out;
}

function gs_palloncini_sponsors_lista_html() {
	$lista = gs_palloncini_sponsors();
	$sponsor_ora = gs_palloncini_sponsor_attivo_ora();
	$out = '<div class="gs-todo-riquadro" id="gs-palloncini-sponsors-lista">';
	if ( ! $lista ) {
		$out .= '<p class="gs-hint">Nessuno sponsor configurato.</p>';
	} else {
		foreach ( $lista as $sp ) {
			$e_attivo_ora = $sponsor_ora && $sponsor_ora['id'] === $sp['id'];
			$out .= '<div class="gs-prenotazione-riga' . ( $e_attivo_ora ? ' gs-prenotazione-colore' : '' ) . '"' . ( $e_attivo_ora ? ' style="border-left-color:#1f6e37"' : '' ) . ' data-id="' . esc_attr( $sp['id'] ) . '">';
			if ( ! empty( $sp['foto'] ) ) { $out .= '<img src="' . esc_url( $sp['foto'] ) . '" alt="" style="width:28px;height:28px;border-radius:50%;object-fit:contain;background:#fff;border:1px solid var(--gs-bordo,#e3d5b8);vertical-align:middle;margin-right:6px">'; }
			$out .= '<strong>' . esc_html( $sp['nome'] ) . '</strong>';
			if ( 'sempre' === $sp['tipo'] ) {
				$out .= ' — sempre attivo (riserva)';
			} else {
				$out .= ' — dal ' . esc_html( date_i18n( 'j/m/Y', strtotime( $sp['data_inizio'] ) ) ) . ' al ' . esc_html( date_i18n( 'j/m/Y', strtotime( $sp['data_fine'] ) ) );
				$out .= ! empty( $sp['ripeti_ogni_anno'] ) ? ' (ogni anno)' : '';
			}
			$out .= ( $e_attivo_ora ? ' · <span class="gs-blackout-stato on">attivo ora</span>' : '' );
			$out .= ( empty( $sp['attivo'] ) ? ' · <span class="gs-voted">in pausa</span>' : '' );
			$out .= '<br><button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-palloncini-sponsor-toggle" data-id="' . esc_attr( $sp['id'] ) . '">' . ( empty( $sp['attivo'] ) ? 'Riattiva' : 'Metti in pausa' ) . '</button> '
				. '<button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-palloncini-sponsor-elimina" data-id="' . esc_attr( $sp['id'] ) . '">Elimina</button>';
			$out .= '</div>';
		}
	}
	$out .= '</div>';
	return $out;
}

add_action( 'wp_ajax_gs_palloncini_sponsor_salva', 'gs_ajax_palloncini_sponsor_salva' );
function gs_ajax_palloncini_sponsor_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$nome = isset( $_POST['nome'] ) ? sanitize_text_field( wp_unslash( $_POST['nome'] ) ) : '';
	if ( '' === $nome ) { wp_send_json_error( array( 'message' => 'Scrivi il nome dello sponsor.' ) ); }
	$tipo = isset( $_POST['tipo'] ) && 'sempre' === $_POST['tipo'] ? 'sempre' : 'periodo';

	$voce = array(
		'id'    => uniqid( 'psponsor_', true ),
		'nome'  => $nome,
		'foto'  => isset( $_POST['foto'] ) ? esc_url_raw( wp_unslash( $_POST['foto'] ) ) : '',
		'url'   => isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '',
		'tipo'  => $tipo,
		'attivo' => true,
	);
	if ( 'periodo' === $tipo ) {
		$ini = isset( $_POST['data_inizio'] ) ? sanitize_text_field( wp_unslash( $_POST['data_inizio'] ) ) : '';
		$fin = isset( $_POST['data_fine'] ) ? sanitize_text_field( wp_unslash( $_POST['data_fine'] ) ) : '';
		if ( ! $ini || ! $fin || ! strtotime( $ini ) || ! strtotime( $fin ) ) { wp_send_json_error( array( 'message' => 'Scegli una data di inizio e una di fine valide.' ) ); }
		$voce['data_inizio']      = $ini;
		$voce['data_fine']        = $fin;
		$voce['ripeti_ogni_anno'] = ! empty( $_POST['ripeti_ogni_anno'] );
	}

	$lista = gs_palloncini_sponsors();
	$lista[] = $voce;
	gs_palloncini_sponsors_salva( $lista );

	wp_send_json_success( array( 'message' => 'Sponsor aggiunto.', 'html' => gs_palloncini_sponsors_lista_html() ) );
}

add_action( 'wp_ajax_gs_palloncini_sponsor_toggle', 'gs_ajax_palloncini_sponsor_toggle' );
function gs_ajax_palloncini_sponsor_toggle() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	$lista = gs_palloncini_sponsors();
	foreach ( $lista as &$sp ) {
		if ( $sp['id'] === $id ) { $sp['attivo'] = empty( $sp['attivo'] ); break; }
	}
	unset( $sp );
	gs_palloncini_sponsors_salva( $lista );
	wp_send_json_success( array( 'html' => gs_palloncini_sponsors_lista_html() ) );
}

add_action( 'wp_ajax_gs_palloncini_sponsor_elimina', 'gs_ajax_palloncini_sponsor_elimina' );
function gs_ajax_palloncini_sponsor_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	$lista = array_values( array_filter( gs_palloncini_sponsors(), function ( $sp ) use ( $id ) { return $sp['id'] !== $id; } ) );
	gs_palloncini_sponsors_salva( $lista );
	wp_send_json_success( array( 'message' => 'Sponsor eliminato.', 'html' => gs_palloncini_sponsors_lista_html() ) );
}

// -----------------------------------------------------------------------------
// PROGRAMMAZIONE PALLONCINI — stessa identica metodologia dell'Aeroplanino
// (motore condiviso gs_programma_dovuto() / gs_programma_esegui_dovuti() più
// sopra in questo file), elenco separato: si può programmare un lancio di
// palloncini (con o senza sponsor) una volta, ogni anno/mese/settimana/
// giorno, o a ripetizione per un evento dal vivo — richiesto da Ennio il
// 18/08/2026.
// -----------------------------------------------------------------------------

function gs_palloncini_programmati() {
	$s = gs_settings();
	return isset( $s['palloncini_programmati'] ) && is_array( $s['palloncini_programmati'] ) ? $s['palloncini_programmati'] : array();
}
function gs_palloncini_programmati_salva( $lista ) {
	$s = gs_settings();
	$s['palloncini_programmati'] = $lista;
	update_option( GS_OPTION, $s );
}

function gs_palloncini_programmati_html() {
	$out = '<p class="gs-hint">I palloncini partono da soli, con lo sponsor del momento se lo spunti — al minuto preciso se c\'è almeno una sfoglina collegata (stessa precisione dell\'Aeroplanino qui sopra), altrimenti entro l\'ora come rete di sicurezza.</p>';
	$out .= '<form class="gs-form gs-form-palloncini-programma" onsubmit="return false">';
	$out .= '<p><label>Motivo <select name="motivo">'
		. '<option value="compleanno">🎂 Compleanno</option>'
		. '<option value="diploma">🎓 Diploma</option>'
		. '<option value="festa">🎉 Festeggia</option>'
		. '</select></label></p>';
	$out .= '<p><label><input type="checkbox" name="con_sponsor"> Metti il logo dello sponsor del momento</label></p>';
	$out .= '<p class="gs-palloncini-programma-campo-distribuzione">Il logo va su: '
		. '<label><input type="radio" name="distribuzione" value="uno" checked> un solo palloncino</label>'
		. '<label style="margin-left:12px"><input type="radio" name="distribuzione" value="tutti"> tutti i palloncini</label></p>';
	$out .= '<p><label>Frequenza <select name="tipo" class="gs-palloncini-programma-tipo">'
		. '<option value="una_volta">Una volta sola, a una data precisa</option>'
		. '<option value="ogni_anno">Ogni anno, lo stesso giorno</option>'
		. '<option value="ogni_mese">Ogni mese, lo stesso giorno del mese</option>'
		. '<option value="ogni_settimana">Ogni settimana, lo stesso giorno</option>'
		. '<option value="ogni_giorno">Ogni giorno</option>'
		. '<option value="a_ripetizione">A ripetizione (es. ogni 5 minuti, per un evento dal vivo)</option>'
		. '</select></label></p>';
	$out .= '<p class="gs-palloncini-campo-data"><label>Data <input type="date" name="data"></label></p>';
	$out .= '<p class="gs-palloncini-campo-mese-giorno" style="display:none"><label>Giorno e mese (ogni anno) <input type="text" name="giorno_mese" placeholder="es. 25/12" maxlength="5" style="width:80px"></label></p>';
	$out .= '<p class="gs-palloncini-campo-giorno-mese" style="display:none"><label>Giorno del mese <input type="number" name="giorno" min="1" max="31" style="width:70px"></label></p>';
	$out .= '<p class="gs-palloncini-campo-giorno-settimana" style="display:none"><label>Giorno della settimana <select name="giorno_settimana">'
		. '<option value="1">Lunedì</option><option value="2">Martedì</option><option value="3">Mercoledì</option>'
		. '<option value="4">Giovedì</option><option value="5">Venerdì</option><option value="6">Sabato</option><option value="0">Domenica</option>'
		. '</select></label></p>';
	$out .= '<p class="gs-palloncini-campo-ora-min"><label>A che ora <input type="time" name="ora_min" value="09:00"></label></p>';
	$out .= '<p class="gs-palloncini-campo-ripetizione" style="display:none">'
		. '<label>Ogni <input type="number" name="ogni_minuti" min="1" max="180" value="5" style="width:60px"> minuti</label> '
		. '<label style="margin-left:12px">per <input type="number" name="durata_minuti" min="1" max="1440" value="60" style="width:70px"> minuti in tutto</label>'
		. '<br><span class="gs-hint">Parte dal momento in cui premi "Programma" — pensato per un evento dal vivo, non per una data futura.</span></p>';
	$out .= '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-palloncini-programma-salva">📅 Programma</button> <span class="gs-palloncini-programma-msg gs-richiesta-esito"></span></p>';
	$out .= '</form>';
	$out .= gs_palloncini_programmati_lista_html();
	return $out;
}

function gs_palloncini_programmati_lista_html() {
	return gs_programmati_lista_html_render( gs_palloncini_programmati(), 'gs-palloncini-programmati-lista', 'gs-palloncini-programma' );
}

add_action( 'wp_ajax_gs_palloncini_programma_salva', 'gs_ajax_palloncini_programma_salva' );
function gs_ajax_palloncini_programma_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$motivo = isset( $_POST['motivo'] ) ? sanitize_key( wp_unslash( $_POST['motivo'] ) ) : '';
	if ( ! in_array( $motivo, array( 'compleanno', 'diploma', 'festa' ), true ) ) { wp_send_json_error( array( 'message' => 'Motivo non valido.' ) ); }
	$tipo = isset( $_POST['tipo'] ) ? sanitize_key( wp_unslash( $_POST['tipo'] ) ) : '';
	$voce = gs_programma_valida_e_costruisci( $tipo );
	if ( is_wp_error( $voce ) ) { wp_send_json_error( array( 'message' => $voce->get_error_message() ) ); }
	$voce['motivo']        = $motivo;
	$voce['con_sponsor']   = ! empty( $_POST['con_sponsor'] );
	$voce['distribuzione'] = ( isset( $_POST['distribuzione'] ) && 'tutti' === $_POST['distribuzione'] ) ? 'tutti' : 'uno';

	$lista = gs_palloncini_programmati();
	$lista[] = $voce;
	gs_palloncini_programmati_salva( $lista );

	wp_send_json_success( array( 'message' => 'Lancio programmato.', 'html' => gs_palloncini_programmati_lista_html() ) );
}

add_action( 'wp_ajax_gs_palloncini_programma_toggle', 'gs_ajax_palloncini_programma_toggle' );
function gs_ajax_palloncini_programma_toggle() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	$lista = gs_palloncini_programmati();
	foreach ( $lista as &$p ) {
		if ( $p['id'] === $id ) { $p['attivo'] = empty( $p['attivo'] ); break; }
	}
	unset( $p );
	gs_palloncini_programmati_salva( $lista );
	wp_send_json_success( array( 'html' => gs_palloncini_programmati_lista_html() ) );
}

add_action( 'wp_ajax_gs_palloncini_programma_elimina', 'gs_ajax_palloncini_programma_elimina' );
function gs_ajax_palloncini_programma_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	$lista = array_values( array_filter( gs_palloncini_programmati(), function ( $p ) use ( $id ) { return $p['id'] !== $id; } ) );
	gs_palloncini_programmati_salva( $lista );
	wp_send_json_success( array( 'message' => 'Programmazione eliminata.', 'html' => gs_palloncini_programmati_lista_html() ) );
}

// -----------------------------------------------------------------------------
// Badge sbloccato
// -----------------------------------------------------------------------------
add_action( 'gs_badge_unlocked', 'gs_volo_badge', 20, 2 );
function gs_volo_badge( $user_id, $badge_key ) {
	$badges = function_exists( 'gs_get_badges_definitions' ) ? gs_get_badges_definitions() : array();
	$icon   = isset( $badges[ $badge_key ]['icon'] ) ? $badges[ $badge_key ]['icon'] : '🏅';
	$label  = isset( $badges[ $badge_key ]['label'] ) ? $badges[ $badge_key ]['label'] : 'nuovo badge';
	gs_accoda_volo( $user_id, 'BADGE SBLOCCATO: ' . $icon . ' ' . mb_strtoupper( $label ), gs_pagina_url( 'gs_page_badge' ) );
}

// -----------------------------------------------------------------------------
// Salita di livello
// -----------------------------------------------------------------------------
add_action( 'gs_level_up', 'gs_volo_livello', 20, 3 );
function gs_volo_livello( $user_id, $new_level, $old_level ) {
	$levels  = gs_settings()['levels'];
	$level   = isset( $levels[ $new_level ] ) ? $levels[ $new_level ] : null;
	$simbolo = $level ? $level['simbolo'] : '⭐';
	$titolo  = $level ? $level['titolo'] : 'nuovo livello';
	gs_accoda_volo( $user_id, 'SEI SALITA DI LIVELLO: ' . $simbolo . ' ' . mb_strtoupper( $titolo ), gs_pagina_url( 'gs_page_dashboard' ) );
}

// -----------------------------------------------------------------------------
// Diploma di Rina Poletti assegnato (Area Professionale)
// -----------------------------------------------------------------------------
add_action( 'gs_diploma_assegnato', 'gs_volo_diploma', 20, 1 );
function gs_volo_diploma( $corso_id ) {
	$user_id = (int) get_post_meta( $corso_id, 'gs_corso_utente', true );
	if ( ! $user_id ) {
		return;
	}
	// Non un link diretto al diploma: quel link contiene un nonce legato a chi
	// lo genera (qui, la persona che ha assegnato il diploma), non valido per
	// lei. Si rimanda invece alla pagina, dove il pulsante "Vedi/stampa il tuo
	// diploma" genera il proprio link corretto nella sua sessione.
	$url = gs_pagina_url( 'gs_page_area_pro' );
	gs_accoda_volo( $user_id, 'HAI RICEVUTO IL DIPLOMA: 🎓 SFOGLINA PROFESSIONISTA', $url );

	$u = get_user_by( 'id', $user_id );
	if ( $u ) {
		$corpo = "Ciao " . $u->display_name . ",\n\n"
			. "Rina Poletti ti ha assegnato il Diploma di Sfoglina Professionista: hai completato "
			. "l'intero percorso di formazione secondo il Piano di Studio Ufficiale dell'Accademia della Sfoglia.\n\n"
			. "Vai nei tuoi Corsi Online per vederlo e stamparlo:\n" . $url . "\n\n"
			. "— Accademia della Sfoglia";
		$oggetto_diploma = 'Il tuo diploma è arrivato — Accademia della Sfoglia';
		if ( function_exists( 'gs_mail_progetto' ) ) {
			gs_mail_progetto( $u->ID, 'livelli', $oggetto_diploma, $corpo );
		} elseif ( $u->user_email ) {
			wp_mail( $u->user_email, $oggetto_diploma, $corpo );
		}
	}
}
