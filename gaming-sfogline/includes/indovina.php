<?php
/**
 * indovina.php — Indovina la Sfoglia: il quiz-lampo del giorno.
 *
 * Una sola domanda al giorno, la stessa per tutte le sfogline, scelta in
 * automatico dalla lista di domande scritte dal titolare (gs_settings()
 * ['indovina_domande'], array di {id, testo, risposta_esatta} — stessa
 * logica di correzione automatica delle domande di verifica delle Lezioni
 * Video, vedi gs_risposta_corretta() in lezioni-video.php). Cambia da sola
 * a mezzanotte, si risponde una volta sola al giorno: un gesto veloce
 * pensato per far tornare le sfogline ogni giorno, non un compito.
 *
 * Stato della risposta di oggi: meta utente gs_indovina = {data, domanda_id,
 * risposta, corretta} — stesso schema "confronta la data, azzera se è
 * cambiato il giorno" già usato in missions.php per le missioni giornaliere.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Helper
// -----------------------------------------------------------------------------

/** La lista di domande scritte dal titolare. */
function gs_indovina_domande() {
	$s = gs_settings();
	return isset( $s['indovina_domande'] ) && is_array( $s['indovina_domande'] ) ? $s['indovina_domande'] : array();
}

/** Cestino delle domande eliminate: sono nelle impostazioni, non un CPT, niente wp_trash_post(). */
function gs_indovina_domande_cestino() {
	$s = gs_settings();
	return isset( $s['indovina_domande_cestino'] ) && is_array( $s['indovina_domande_cestino'] ) ? $s['indovina_domande_cestino'] : array();
}
/**
 * Sposta una domanda dalle attive al cestino. Un'UNICA lettura/scrittura
 * delle impostazioni: due update_option() separati (uno per le domande
 * attive, uno per il cestino) si pesterebbero i piedi, perché gs_settings()
 * ha una cache statica che non si aggiorna da sola tra due chiamate nello
 * stesso processo — il secondo salvataggio riscriverebbe sopra dati vecchi.
 */
function gs_indovina_domanda_sposta_nel_cestino( $id ) {
	$s       = gs_settings();
	$domande = isset( $s['indovina_domande'] ) && is_array( $s['indovina_domande'] ) ? $s['indovina_domande'] : array();
	$cestino = isset( $s['indovina_domande_cestino'] ) && is_array( $s['indovina_domande_cestino'] ) ? $s['indovina_domande_cestino'] : array();
	foreach ( $domande as $d ) {
		if ( $d['id'] === $id ) { $d['ts'] = time(); $cestino[] = $d; }
	}
	$domande = array_values( array_filter( $domande, function ( $d ) use ( $id ) { return $d['id'] !== $id; } ) );
	if ( count( $cestino ) > 30 ) { $cestino = array_slice( $cestino, -30 ); }
	$s['indovina_domande']         = $domande;
	$s['indovina_domande_cestino'] = array_values( $cestino );
	update_option( GS_OPTION, $s );
}
/** Riporta una domanda dal cestino alle attive (stessa unica lettura/scrittura). */
function gs_indovina_domanda_ripristina_dal_cestino( $id ) {
	$s       = gs_settings();
	$cestino = isset( $s['indovina_domande_cestino'] ) && is_array( $s['indovina_domande_cestino'] ) ? $s['indovina_domande_cestino'] : array();
	$domande = isset( $s['indovina_domande'] ) && is_array( $s['indovina_domande'] ) ? $s['indovina_domande'] : array();
	foreach ( $cestino as $d ) {
		if ( $d['id'] === $id ) { unset( $d['ts'] ); $domande[] = $d; }
	}
	$cestino = array_values( array_filter( $cestino, function ( $d ) use ( $id ) { return $d['id'] !== $id; } ) );
	$s['indovina_domande']         = $domande;
	$s['indovina_domande_cestino'] = $cestino;
	update_option( GS_OPTION, $s );
}

/**
 * La domanda di oggi: stessa per tutte, scelta in automatico dalla lista in
 * base al giorno (nessuna scelta manuale quotidiana richiesta al titolare).
 * Null se la lista è vuota.
 */
function gs_indovina_di_oggi() {
	$domande = gs_indovina_domande();
	if ( ! $domande ) { return null; }
	$giorno  = (int) floor( current_time( 'timestamp' ) / DAY_IN_SECONDS );
	$indice  = $giorno % count( $domande );
	return $domande[ $indice ];
}

/** Lo stato della risposta di oggi per una sfoglina, o null se non ha ancora risposto oggi. */
function gs_indovina_stato_oggi( $uid ) {
	$oggi   = date( 'Y-m-d', current_time( 'timestamp' ) );
	$stato  = get_user_meta( $uid, 'gs_indovina', true );
	if ( ! is_array( $stato ) || ( $stato['data'] ?? '' ) !== $oggi ) {
		return null;
	}
	return $stato;
}

// -----------------------------------------------------------------------------
// [gs_indovina] — lato sfoglina
// -----------------------------------------------------------------------------
add_shortcode( 'gs_indovina', 'gs_sc_indovina' );
function gs_sc_indovina() {
	if ( $g = gs_gate_riservato() ) { return $g; }
	if ( $g = gs_blackout_gate() ) { return $g; }

	$out = gs_box_open( '🔮 Indovina la Sfoglia' );
	$out .= gs_sezione_aiuto( 'Una domanda-lampo al giorno, uguale per tutte: rispondi con le tue parole, il sistema ti dice subito se è esatta e in quel caso ti dà qualche punto. Una sola risposta al giorno; la domanda cambia da sola a mezzanotte — torna domani per la prossima.' );

	$domanda = gs_indovina_di_oggi();
	if ( ! $domanda ) {
		$out .= '<p class="gs-hint">Il quiz del giorno è in preparazione: torna presto.</p>';
		$out .= gs_box_close();
		return $out;
	}

	$uid   = get_current_user_id();
	$stato = gs_indovina_stato_oggi( $uid );

	if ( $stato ) {
		$out .= '<div class="gs-todo-riquadro">';
		$out .= '<p class="gs-hint" style="font-size:16px"><strong>' . esc_html( $domanda['testo'] ) . '</strong></p>';
		$out .= '<p class="gs-hint">' . ( $stato['corretta'] ? '✅ Hai risposto esatto!' : '❌ Non era esatta.' ) . ' La risposta era: <strong>' . esc_html( $domanda['risposta_esatta'] ) . '</strong><br>Hai risposto: «' . esc_html( $stato['risposta'] ) . '»</p>';
		$out .= '<p class="gs-hint">Torna domani per la prossima domanda.</p>';
		$out .= '</div>';
	} else {
		$out .= '<div class="gs-todo-riquadro">';
		$out .= '<p class="gs-hint" style="font-size:16px"><strong>' . esc_html( $domanda['testo'] ) . '</strong></p>';
		$out .= '<form class="gs-form gs-form-indovina" data-domanda="' . esc_attr( $domanda['id'] ) . '" onsubmit="return false">';
		$out .= '<p><input type="text" name="risposta" autocomplete="off" style="width:100%" placeholder="La tua risposta…"></p>';
		$out .= '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-indovina-invia">Rispondi</button> <span class="gs-indovina-msg gs-richiesta-esito"></span></p>';
		$out .= '</form>';
		$out .= '</div>';
	}

	$out .= gs_box_close();
	return $out;
}

add_action( 'wp_ajax_gs_indovina_rispondi', 'gs_ajax_indovina_rispondi' );
function gs_ajax_indovina_rispondi() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }

	$domanda = gs_indovina_di_oggi();
	if ( ! $domanda ) { wp_send_json_error( array( 'message' => 'Nessuna domanda disponibile oggi.' ) ); }
	if ( gs_indovina_stato_oggi( $uid ) ) { wp_send_json_error( array( 'message' => 'Hai già risposto alla domanda di oggi. Torna domani per la prossima.' ) ); }

	$risposta = isset( $_POST['risposta'] ) ? sanitize_text_field( wp_unslash( $_POST['risposta'] ) ) : '';
	if ( '' === trim( $risposta ) ) { wp_send_json_error( array( 'message' => 'Scrivi una risposta.' ) ); }

	$corretta = gs_risposta_corretta( $risposta, $domanda['risposta_esatta'] );

	update_user_meta( $uid, 'gs_indovina', array(
		'data'       => date( 'Y-m-d', current_time( 'timestamp' ) ),
		'domanda_id' => $domanda['id'],
		'risposta'   => $risposta,
		'corretta'   => $corretta,
	) );

	if ( $corretta ) {
		gs_add_points( $uid, gs_get_points_value( 'indovina_corretta', 5 ), 'Indovina la Sfoglia: risposta esatta' );
	}

	wp_send_json_success( array(
		'corretta'        => $corretta,
		'risposta_esatta' => $domanda['risposta_esatta'],
		'message'         => $corretta ? '✅ Esatto!' : '❌ Non era questa. La risposta era: ' . $domanda['risposta_esatta'],
	) );
}

// -----------------------------------------------------------------------------
// PANNELLO — gestione della lista di domande (titolare)
// -----------------------------------------------------------------------------
function gs_pannello_indovina() {
	if ( ! gs_can_manage() ) { return; }

	echo gs_box_open( '🔮 Indovina la Sfoglia', '', 'gs-box-indovina' );
	echo '<p class="gs-hint">Scrivi qui la lista di domande del quiz-lampo giornaliero: ogni giorno il sistema ne sceglie una da solo (sempre la stessa per tutte le sfogline, cambia a mezzanotte) — non serve sceglierla giorno per giorno. Più domande scrivi, più tempo passa prima che si ripetano. Scrivi anche la risposta esatta: il sistema corregge da solo, come per le domande delle Lezioni Video. Usa risposte brevi e univoche (una parola, un numero).</p>';

	$domanda_oggi = gs_indovina_di_oggi();
	if ( $domanda_oggi ) {
		echo '<p class="gs-hint"><strong>Oggi tocca a:</strong> ' . esc_html( $domanda_oggi['testo'] ) . '</p>';
	}

	$domande = gs_indovina_domande();
	echo '<ul class="gs-todo-list gs-indovina-domande-list">';
	foreach ( $domande as $d ) {
		$risp_esatta = isset( $d['risposta_esatta'] ) ? $d['risposta_esatta'] : '';
		echo '<li class="gs-todo-item" data-domanda="' . esc_attr( $d['id'] ) . '" style="display:block;padding:8px 0">';
		echo '<div style="display:flex;justify-content:space-between;align-items:center;gap:8px">'
			. '<input type="text" class="gs-indovina-domanda-testo" value="' . esc_attr( $d['testo'] ) . '" style="flex:1">'
			. '<button class="gs-todo-del gs-indovina-domanda-elimina" data-domanda="' . esc_attr( $d['id'] ) . '" title="Elimina">✕</button></div>';
		echo '<div style="display:flex;gap:8px;margin-top:4px">';
		echo '<input type="text" class="gs-indovina-domanda-risposta-esatta" value="' . esc_attr( $risp_esatta ) . '" placeholder="Risposta esatta…" style="flex:1">';
		echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-indovina-domanda-risposta-salva" data-domanda="' . esc_attr( $d['id'] ) . '">✎ Salva modifiche</button>';
		echo '</div>';
		echo '<span class="gs-indovina-domanda-riga-msg gs-richiesta-esito"></span>';
		if ( '' === trim( $risp_esatta ) ) { echo '<p class="gs-hint" style="margin:4px 0 0">⚠️ Senza risposta esatta, questa domanda non assegna punti in automatico.</p>'; }
		echo '</li>';
	}
	echo '</ul>';
	if ( ! $domande ) { echo '<p class="gs-hint gs-indovina-domande-vuoto">Nessuna domanda ancora: le sfogline vedranno "in preparazione" finché non ne scrivi almeno una.</p>'; }

	$domande_cestino = gs_indovina_domande_cestino();
	echo '<details class="gs-todo-cestino"><summary>🗑️ Domande eliminate (' . count( $domande_cestino ) . ')</summary>';
	if ( ! $domande_cestino ) {
		echo '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		echo '<ul class="gs-todo-list gs-todo-list-cestino">';
		foreach ( array_reverse( $domande_cestino ) as $d ) {
			echo '<li class="gs-todo-item" data-id="' . esc_attr( $d['id'] ) . '">'
				. '<span>' . esc_html( $d['testo'] ) . '</span>'
				. '<button class="gs-todo-ripristina gs-indovina-domanda-ripristina" data-domanda="' . esc_attr( $d['id'] ) . '" title="Ripristina">↺ Ripristina</button></li>';
		}
		echo '</ul>';
	}
	echo '</details>';

	echo '<div style="display:flex;gap:8px;margin-top:6px;flex-wrap:wrap">';
	echo '<input type="text" class="gs-indovina-domanda-input" placeholder="Nuova domanda…" style="flex:2;min-width:160px" autocomplete="off">';
	echo '<input type="text" class="gs-indovina-domanda-risposta-input" placeholder="Risposta esatta…" style="flex:1;min-width:120px" autocomplete="off">';
	echo '<button class="gs-btn gs-btn-sm gs-indovina-domanda-add">Aggiungi</button>';
	echo '</div><span class="gs-indovina-domanda-msg gs-richiesta-esito"></span>';

	echo gs_box_close();
}

// -----------------------------------------------------------------------------
// AJAX — gestione lista di domande
// -----------------------------------------------------------------------------

add_action( 'wp_ajax_gs_indovina_domanda_aggiungi', 'gs_ajax_indovina_domanda_aggiungi' );
function gs_ajax_indovina_domanda_aggiungi() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$testo = isset( $_POST['testo'] ) ? sanitize_text_field( wp_unslash( $_POST['testo'] ) ) : '';
	if ( '' === trim( $testo ) ) { wp_send_json_error( array( 'message' => 'Scrivi il testo della domanda.' ) ); }
	$risposta_esatta = isset( $_POST['risposta_esatta'] ) ? sanitize_text_field( wp_unslash( $_POST['risposta_esatta'] ) ) : '';

	$domande   = gs_indovina_domande();
	$domande[] = array( 'id' => uniqid( 'q' ), 'testo' => $testo, 'risposta_esatta' => $risposta_esatta );

	$s = gs_settings();
	$s['indovina_domande'] = $domande;
	update_option( GS_OPTION, $s );

	wp_send_json_success( array( 'message' => 'Domanda aggiunta.' ) );
}

/**
 * Modifica una domanda esistente: risposta esatta sempre, testo solo se
 * passato (null = non toccarlo). Restituisce true se trovata e salvata.
 */
function gs_indovina_domanda_modifica( $id, $risposta_esatta, $testo = null ) {
	$domande = gs_indovina_domande();
	$trovata = false;
	foreach ( $domande as &$d ) {
		if ( $d['id'] === $id ) {
			$d['risposta_esatta'] = $risposta_esatta;
			if ( null !== $testo ) { $d['testo'] = $testo; }
			$trovata = true;
			break;
		}
	}
	unset( $d );
	if ( ! $trovata ) { return false; }

	$s = gs_settings();
	$s['indovina_domande'] = $domande;
	update_option( GS_OPTION, $s );
	return true;
}

add_action( 'wp_ajax_gs_indovina_domanda_risposta_salva', 'gs_ajax_indovina_domanda_risposta_salva' );
function gs_ajax_indovina_domanda_risposta_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id              = isset( $_POST['domanda'] ) ? sanitize_text_field( wp_unslash( $_POST['domanda'] ) ) : '';
	$risposta_esatta = isset( $_POST['risposta_esatta'] ) ? sanitize_text_field( wp_unslash( $_POST['risposta_esatta'] ) ) : '';
	$testo           = isset( $_POST['testo'] ) ? sanitize_text_field( wp_unslash( $_POST['testo'] ) ) : null;
	if ( null !== $testo && '' === trim( $testo ) ) { wp_send_json_error( array( 'message' => 'La domanda non può essere vuota.' ) ); }
	if ( ! gs_indovina_domanda_modifica( $id, $risposta_esatta, $testo ) ) { wp_send_json_error( array( 'message' => 'Domanda non trovata.' ) ); }
	wp_send_json_success( array( 'message' => 'Modifiche salvate.' ) );
}

add_action( 'wp_ajax_gs_indovina_domanda_elimina', 'gs_ajax_indovina_domanda_elimina' );
function gs_ajax_indovina_domanda_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['domanda'] ) ? sanitize_text_field( wp_unslash( $_POST['domanda'] ) ) : '';
	gs_indovina_domanda_sposta_nel_cestino( $id );
	wp_send_json_success( array( 'message' => 'Domanda spostata nel cestino.' ) );
}

add_action( 'wp_ajax_gs_indovina_domanda_ripristina', 'gs_ajax_indovina_domanda_ripristina' );
function gs_ajax_indovina_domanda_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['domanda'] ) ? sanitize_text_field( wp_unslash( $_POST['domanda'] ) ) : '';
	gs_indovina_domanda_ripristina_dal_cestino( $id );
	wp_send_json_success( array( 'message' => 'Domanda ripristinata.' ) );
}
