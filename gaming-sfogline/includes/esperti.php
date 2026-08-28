<?php
/**
 * esperti.php — "L'Esperto Risponde"
 * Consulenze PRIVATE a pagamento (a token) con un maestro/collaboratore
 * (Rina Poletti Risponde, Bruno Cingolani Risponde, e ogni altro canale
 * futuro) — dal 2026-07-30 non più una bacheca pubblica: ogni domanda apre
 * (o continua) una Conversazione privata 1-a-1 con l'esperto del canale
 * (vedi conversazioni.php), visibile solo alla sfoglina e a chi modera quel
 * canale. Il credito a token si gestisce in token.php.
 *
 * - Config canali in impostazioni: 'esperti' => [ slug => {nome, esperto, attivo, costo_token} ]
 * - Anti-spam su ogni domanda + limite orario per utente
 * - Una sola pagina [gs_esperto] mostra il canale indicato da ?canale=slug
 *
 * Il vecchio CPT gs_domanda (bacheca pubblica) resta definito sotto per non
 * perdere eventuali domande già pubblicate in passato, ma non riceve più
 * nulla di nuovo: il modulo non lo usa più per le domande vere.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_register_domanda_cpt' );
function gs_register_domanda_cpt() {
	register_post_type( 'gs_domanda', array(
		'labels'       => array( 'name' => 'Domande agli esperti' ),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'editor', 'author' ),
	) );
}

// -----------------------------------------------------------------------------
// Config canali
// -----------------------------------------------------------------------------
function gs_esperti_canali() {
	$s = gs_settings();
	$c = isset( $s['esperti'] ) && is_array( $s['esperti'] ) ? $s['esperti'] : array();
	// Normalizza: assicura le chiavi.
	foreach ( $c as $slug => &$ch ) {
		$ch['nome']        = isset( $ch['nome'] ) ? $ch['nome'] : 'Esperto Risponde';
		$ch['esperto']     = isset( $ch['esperto'] ) ? (int) $ch['esperto'] : 0;
		$ch['attivo']      = isset( $ch['attivo'] ) ? (bool) $ch['attivo'] : true;
		// Quanti token consuma una domanda in questo canale — uguale per
		// tutti finché non lo cambi per un singolo maestro dal pannello.
		$ch['costo_token'] = isset( $ch['costo_token'] ) && (int) $ch['costo_token'] > 0 ? (int) $ch['costo_token'] : 1;
	}
	unset( $ch );
	return $c;
}
function gs_esperti_salva( $canali ) {
	$s = gs_settings();
	$s['esperti'] = $canali;
	update_option( GS_OPTION, $s );
}
/**
 * Crea i due canali storici ("Rina Poletti Risponde", "Bruno Cingolani
 * Risponde") solo se non esiste ancora NESSUN canale — non tocca eventuali
 * canali già configurati dal gestore. L'esperto resta da assegnare a mano
 * dal pannello (non si inventa l'utente WordPress di una persona reale).
 */
function gs_esperti_seed_default() {
	$c = gs_esperti_canali();
	if ( ! empty( $c ) ) {
		return;
	}
	$c = array(
		'rina-poletti'    => array( 'nome' => 'Rina Poletti Risponde', 'esperto' => 0, 'attivo' => true ),
		'bruno-cingolani' => array( 'nome' => 'Bruno Cingolani Risponde', 'esperto' => 0, 'attivo' => true ),
	);
	gs_esperti_salva( $c );
}
function gs_esperto_canale( $slug ) {
	$c = gs_esperti_canali();
	return isset( $c[ $slug ] ) ? $c[ $slug ] : null;
}
function gs_esperto_di( $slug ) {
	$ch = gs_esperto_canale( $slug );
	return $ch ? (int) $ch['esperto'] : 0;
}
function gs_is_esperto( $uid, $slug ) {
	return $uid && (int) gs_esperto_di( $slug ) === (int) $uid;
}
/** Può moderare/rispondere in questo canale: gestore o esperto del canale. */
function gs_esperto_puo_moderare( $slug ) {
	$uid = get_current_user_id();
	return ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) || gs_is_esperto( $uid, $slug );
}
/** Genera uno slug unico da un nome. */
function gs_esperto_slug_nuovo( $nome ) {
	$base = sanitize_title( $nome );
	if ( '' === $base ) { $base = 'esperto'; }
	$c = gs_esperti_canali();
	$slug = $base; $i = 2;
	while ( isset( $c[ $slug ] ) ) { $slug = $base . '-' . $i; $i++; }
	return $slug;
}

// -----------------------------------------------------------------------------
// Domande & risposte
// -----------------------------------------------------------------------------
function gs_esperto_domande( $slug ) {
	return get_posts( array(
		'post_type'      => 'gs_domanda',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_key'       => 'gs_canale',
		'meta_value'     => $slug,
	) );
}
function gs_esperto_risposte( $domanda_id ) {
	$r = get_post_meta( $domanda_id, 'gs_risposte', true );
	return is_array( $r ) ? $r : array();
}

/** Cestino delle risposte eliminate: non sono un CPT, niente wp_trash_post(). */
function gs_esperto_risposte_cestino( $domanda_id ) {
	$r = get_post_meta( $domanda_id, 'gs_risposte_cestino', true );
	return is_array( $r ) ? $r : array();
}
function gs_esperto_salva_risposte_cestino( $domanda_id, $cestino ) {
	if ( count( $cestino ) > 30 ) { $cestino = array_slice( $cestino, -30 ); }
	update_post_meta( $domanda_id, 'gs_risposte_cestino', array_values( $cestino ) );
}
/** Sposta una risposta dalle attive al cestino. */
function gs_risposta_sposta_nel_cestino( $domanda_id, $id ) {
	$risposte = gs_esperto_risposte( $domanda_id );
	$cestino  = gs_esperto_risposte_cestino( $domanda_id );
	foreach ( $risposte as $r ) {
		if ( $r['id'] === $id ) { $r['ts'] = time(); $cestino[] = $r; }
	}
	$risposte = array_values( array_filter( $risposte, function ( $r ) use ( $id ) { return $r['id'] !== $id; } ) );
	update_post_meta( $domanda_id, 'gs_risposte', $risposte );
	gs_esperto_salva_risposte_cestino( $domanda_id, $cestino );
}
/** Modifica il testo di una risposta esistente. */
function gs_risposta_modifica_testo( $domanda_id, $id, $testo ) {
	$risposte = gs_esperto_risposte( $domanda_id );
	$trovata  = false;
	foreach ( $risposte as &$r ) {
		if ( $r['id'] === $id ) { $r['testo'] = $testo; $trovata = true; }
	}
	unset( $r );
	if ( $trovata ) { update_post_meta( $domanda_id, 'gs_risposte', $risposte ); }
	return $trovata;
}

/** Riporta una risposta dal cestino alle attive. */
function gs_risposta_ripristina_dal_cestino( $domanda_id, $id ) {
	$cestino  = gs_esperto_risposte_cestino( $domanda_id );
	$risposte = gs_esperto_risposte( $domanda_id );
	foreach ( $cestino as $r ) {
		if ( $r['id'] === $id ) { unset( $r['ts'] ); $risposte[] = $r; }
	}
	$cestino = array_values( array_filter( $cestino, function ( $r ) use ( $id ) { return $r['id'] !== $id; } ) );
	update_post_meta( $domanda_id, 'gs_risposte', $risposte );
	gs_esperto_salva_risposte_cestino( $domanda_id, $cestino );
}

/** Numero di domande ancora senza risposta in un canale (per il pallino). */
/**
 * Domande private a pagamento ancora senza risposta (per un canale, o per
 * tutti se $slug è vuoto) — sostituisce, dal passaggio al privato del
 * 2026-07-30, il vecchio conteggio sulla bacheca pubblica gs_domanda.
 * Ogni voce: { conv_id, msg } (msg = la domanda dentro gs_msgs).
 */
function gs_esperto_domande_pendenti( $slug = '' ) {
	if ( ! function_exists( 'gs_conv_msgs' ) ) { return array(); }
	$pendenti = array();
	$convs = get_posts( array(
		'post_type'      => 'gs_conversazione',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	) );
	foreach ( $convs as $c ) {
		$msgs = gs_conv_msgs( $c->ID );
		foreach ( $msgs as $i => $m ) {
			if ( empty( $m['consulenza'] ) || ! empty( $m['rimborsato'] ) ) { continue; }
			if ( $slug && ( ! isset( $m['canale'] ) || $m['canale'] !== $slug ) ) { continue; }
			// "Dopo" per POSIZIONE nell'elenco, non per timestamp: due
			// messaggi inviati nello stesso secondo avrebbero lo stesso
			// tempo, e un confronto per data li avrebbe scambiati per "non
			// ancora risposti" anche quando la risposta c'era già.
			$risposta_trovata = false;
			foreach ( $msgs as $j => $succ ) {
				if ( $j > $i && ! empty( $succ['is_esperto'] ) ) { $risposta_trovata = true; break; }
			}
			if ( $risposta_trovata ) { continue; }
			$pendenti[] = array( 'conv_id' => $c->ID, 'msg' => $m );
		}
	}
	return $pendenti;
}
function gs_esperto_senza_risposta( $slug ) {
	return count( gs_esperto_domande_pendenti( $slug ) );
}

// -----------------------------------------------------------------------------
// Limiti domande per sfoglina (configurabili dal pannello)
// -----------------------------------------------------------------------------
function gs_esperti_limiti() {
	$s = gs_settings();
	$l = isset( $s['esperti_limiti'] ) && is_array( $s['esperti_limiti'] ) ? $s['esperti_limiti'] : array();
	return wp_parse_args( $l, array( 'giorno' => 3, 'settimana' => 10, 'mese' => 30, 'cooldown' => 20 ) );
}

/**
 * Conta le domande private (consulenze a token) fatte da una sfoglina negli
 * ultimi 1, 7 e 30 giorni, in un solo giro sulla corrispondenza — prima
 * c'era una funzione a un giorno per volta (gs_domande_conteggio()),
 * chiamata tre volte da gs_domande_limiti_ok() per rifare da capo la stessa
 * lettura di tutte le conversazioni; tolta il 25/08/2026 perché non aveva
 * più nessun altro chiamante, verificato prima di toglierla.
 */
function gs_domande_conteggi( $uid ) {
	$ora = time();
	$out = array( 1 => 0, 7 => 0, 30 => 0 );
	if ( ! function_exists( 'gs_conv_di_utente' ) ) { return $out; }
	foreach ( gs_conv_di_utente( $uid ) as $c ) {
		foreach ( gs_conv_msgs( $c->ID ) as $m ) {
			if ( empty( $m['consulenza'] ) || (int) $m['from'] !== (int) $uid ) { continue; }
			$eta = $ora - (int) $m['time'];
			if ( $eta < 30 * DAY_IN_SECONDS ) { $out[30]++; }
			if ( $eta < 7 * DAY_IN_SECONDS )  { $out[7]++; }
			if ( $eta < DAY_IN_SECONDS )      { $out[1]++; }
		}
	}
	return $out;
}

/** Verifica i limiti; ritorna true|WP_Error. */
function gs_domande_limiti_ok( $uid ) {
	$l = gs_esperti_limiti();

	// Controllato SEMPRE, non solo se $l['cooldown'] è impostato: il blocco
	// che scrive questo transient ha adesso una durata minima propria
	// (max(5, ...) nei due punti di invio) indipendente dall'attesa visibile
	// che Ennio configura qui — un cooldown a 0 deve togliere solo l'attesa
	// percepita, non la protezione contro il doppio clic (25/08/2026).
	if ( get_transient( 'gs_dom_cd_' . $uid ) ) {
		return new WP_Error( 'cooldown', 'Aspetta qualche secondo prima di scrivere ancora.' );
	}
	// Un solo giro su tutta la corrispondenza invece di tre (giorno,
	// settimana, mese chiamavano ognuno gs_domande_conteggio() da capo,
	// rileggendo le stesse conversazioni tre volte mentre la sfoglina aspetta
	// che parta la sua domanda — trovato il 25/08/2026).
	$n = gs_domande_conteggi( $uid );
	if ( ! empty( $l['giorno'] ) && $n[1] >= (int) $l['giorno'] ) {
		return new WP_Error( 'giorno', 'Hai raggiunto il numero massimo di domande per oggi (' . (int) $l['giorno'] . ').' );
	}
	if ( ! empty( $l['settimana'] ) && $n[7] >= (int) $l['settimana'] ) {
		return new WP_Error( 'settimana', 'Hai raggiunto il numero massimo di domande per questa settimana (' . (int) $l['settimana'] . ').' );
	}
	if ( ! empty( $l['mese'] ) && $n[30] >= (int) $l['mese'] ) {
		return new WP_Error( 'mese', 'Hai raggiunto il numero massimo di domande per questo mese (' . (int) $l['mese'] . ').' );
	}
	return true;
}

// -----------------------------------------------------------------------------
// Shortcode [gs_esperto]  (canale via ?canale=slug o attributo)
// -----------------------------------------------------------------------------
add_shortcode( 'gs_esperto', 'gs_sc_esperto' );
function gs_sc_esperto( $atts ) {
	$atts = shortcode_atts( array( 'canale' => '' ), $atts );
	if ( $g = gs_gate_riservato() ) { return $g; }
	if ( $g = gs_blackout_gate() ) { return $g; }
	if ( ! gs_is_approved( get_current_user_id() ) ) {
		return '<div class="gs-box gs-notice">Il tuo account è in attesa di approvazione.</div>';
	}

	$slug = $atts['canale'];
	if ( ! $slug && isset( $_GET['canale'] ) ) { $slug = sanitize_key( wp_unslash( $_GET['canale'] ) ); }

	$canali = gs_esperti_canali();

	// Nessun canale indicato: mostra l'indice dei canali attivi.
	if ( ! $slug || ! isset( $canali[ $slug ] ) ) {
		$out = gs_box_open( '💬 L\'Esperto Risponde' );
		$out .= '<p class="gs-hint">Scegli un maestro: la tua domanda resta privata, la vedrete solo tu e lui.</p><ul>';
		$pid = (int) get_option( 'gs_page_esperto' );
		$base = $pid ? get_permalink( $pid ) : '';
		foreach ( $canali as $s => $ch ) {
			if ( empty( $ch['attivo'] ) ) { continue; }
			$out .= '<li><a href="' . esc_url( add_query_arg( 'canale', $s, $base ) ) . '">' . esc_html( $ch['nome'] ) . '</a></li>';
		}
		$out .= '</ul>' . gs_box_close();
		return $out;
	}

	$ch = $canali[ $slug ];
	if ( empty( $ch['attivo'] ) && ! gs_esperto_puo_moderare( $slug ) ) {
		return gs_box_open( '💬 ' . esc_html( $ch['nome'] ) ) . '<p>Questo canale è momentaneamente chiuso.</p>' . gs_box_close();
	}

	$puo_moderare = gs_esperto_puo_moderare( $slug );
	$esperto_uid  = (int) $ch['esperto'];
	$esperto_user = $esperto_uid ? get_user_by( 'id', $esperto_uid ) : null;
	$costo        = (int) $ch['costo_token'];

	$out = gs_box_open( '💬 ' . esc_html( $ch['nome'] ) );
	$out .= gs_sezione_aiuto(
		'Questa è una consulenza privata: la tua domanda e la risposta le vedrete solo tu e ' . ( $esperto_user ? esc_html( $esperto_user->display_name ) : 'il maestro' ) . ', mai le altre sfogline. '
		. 'Ogni domanda consuma ' . $costo . ' token dal tuo credito. Scrivi la domanda vera e propria nell\'Oggetto: se nel testo scrivi più di una domanda, viene considerata solo quella dell\'Oggetto. '
		. 'Se non hai token, fai un contributo associativo con bonifico (causale «CONTRIBUTO A SOSTEGNO DELL\'ASSOCIAZIONE ACCADEMIA DELLA SFOGLIA») e chiedi alla segreteria di accreditarti il credito. '
		. 'Se la domanda resta senza risposta, il token ti viene restituito.'
	);

	if ( $puo_moderare ) {
		// Chi modera questo canale non fa domande a sé stesso: qui trova solo
		// il punto d'accesso rapido alle domande in sospeso — le risposte si
		// scrivono nelle Conversazioni private (Menu → Messaggi).
		$pendenti = gs_esperto_domande_pendenti( $slug );
		if ( $pendenti ) {
			$out .= '<p class="gs-hint">🔴 ' . count( $pendenti ) . ' domande di questo canale sono ancora senza risposta.</p>';
			$out .= '<div class="gs-todo-riquadro"><ul class="gs-todo-list">';
			foreach ( $pendenti as $p ) {
				$autore = get_user_by( 'id', $p['msg']['from'] );
				$out .= '<li class="gs-todo-item"><span><strong>' . esc_html( $autore ? $autore->display_name : 'sfoglina' ) . '</strong>'
					. ( ! empty( $p['msg']['oggetto'] ) ? ' — ' . esc_html( $p['msg']['oggetto'] ) : '' ) . '</span>'
					. '<a class="gs-btn gs-btn-sm" href="' . esc_url( gs_conv_link( $p['conv_id'] ) ) . '">Apri e rispondi ↗</a></li>';
			}
			$out .= '</ul></div>';
		} else {
			$out .= '<p class="gs-hint">Nessuna domanda in sospeso in questo canale.</p>';
		}
		$out .= gs_box_close();
		return $out;
	}

	// --- Sfoglina: saldo token + modulo domanda privata ---
	$uid   = get_current_user_id();
	$saldo = function_exists( 'gs_token_saldo' ) ? gs_token_saldo( $uid ) : 0;
	$out .= '<p class="gs-hint"><strong>Hai ' . (int) $saldo . ' token disponibili</strong> per le consulenze private.</p>';
	if ( $saldo < $costo ) {
		$out .= '<div class="gs-notice">Non hai abbastanza token per fare una domanda qui (ne servono ' . $costo . '). '
			. 'Fai un contributo associativo con bonifico all\'Accademia della Sfoglia — causale «CONTRIBUTO A SOSTEGNO DELL\'ASSOCIAZIONE ACCADEMIA DELLA SFOGLIA» — e chiedi alla segreteria di accreditarti il credito.</div>';
	}

	$out .= '<div class="gs-todo-riquadro">';
	$out .= '<form class="gs-form gs-form-domanda-privata" data-canale="' . esc_attr( $slug ) . '" onsubmit="return false">';
	ob_start(); gs_antispam_fields(); $out .= ob_get_clean();
	$out .= '<p><label>Oggetto (la tua domanda, in breve)<br><input type="text" name="oggetto" maxlength="150" style="width:100%" placeholder="Scrivi qui la tua domanda…"></label></p>';
	$out .= '<p><label>Dettagli (facoltativo — solo contesto, non altre domande)<br><textarea name="testo" rows="3" style="width:100%" placeholder="Se serve, aggiungi qualche dettaglio in più…"></textarea></label></p>';
	$out .= '<p><button class="gs-btn gs-btn-sm gs-invia-domanda-privata">Invia la domanda privata (' . $costo . ' token)</button> <span class="gs-domanda-privata-msg gs-richiesta-esito"></span></p>';
	$out .= '</form>';
	$out .= '</div>';

	if ( function_exists( 'gs_pagina_url' ) ) {
		$out .= '<p class="gs-hint">Le tue domande e le risposte le trovi nelle <a href="' . esc_url( gs_pagina_url( 'gs_page_messaggi' ) ) . '">Conversazioni private</a>.</p>';
	}

	$out .= gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// AJAX — domanda (sfoglina), con anti-spam
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_esperto_domanda', 'gs_ajax_esperto_domanda' );
function gs_ajax_esperto_domanda() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	// gs_puo_partecipare() copre anche la congelata: era rimasto il
	// controllo vecchio, trovato il 26/08/2026 insieme ad altri dieci.
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Non puoi scrivere.' ) ); }

	$slug = isset( $_POST['canale'] ) ? sanitize_key( wp_unslash( $_POST['canale'] ) ) : '';
	$ch   = gs_esperto_canale( $slug );
	if ( ! $ch || empty( $ch['attivo'] ) ) { wp_send_json_error( array( 'message' => 'Canale non disponibile.' ) ); }

	// Anti-spam: honeypot + trappola tempo + limite orario.
	$check = gs_antispam_check( $_POST, 'domanda' );
	if ( is_wp_error( $check ) ) { wp_send_json_error( array( 'message' => $check->get_error_message() ) ); }

	// Limiti per sfoglina (cooldown + giorno/settimana/mese) configurabili.
	$lim = gs_domande_limiti_ok( $uid );
	if ( is_wp_error( $lim ) ) { wp_send_json_error( array( 'message' => $lim->get_error_message() ) ); }

	$testo = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';
	$testo = trim( $testo );
	if ( mb_strlen( $testo ) < 5 ) { wp_send_json_error( array( 'message' => 'Scrivi una domanda un po\' più lunga.' ) ); }
	if ( mb_strlen( $testo ) > 2000 ) { $testo = mb_substr( $testo, 0, 2000 ); }

	// Stesso principio di gs_ajax_esperto_domanda_privata() qui sotto: il
	// blocco va scritto SUBITO prima del primo effetto, non alla fine,
	// altrimenti un doppio clic passa due volte prima che una delle due
	// l'abbia scritto (trovato il 25/08/2026 sulla versione a token, stesso
	// difetto anche qui). Ma DOPO i controlli sul testo — sono solo letture,
	// non toccano niente — perché un oggetto troppo corto non è un caso
	// raro come un canale rotto: è l'errore più comune in un modulo, e non
	// deve costare un'attesa a chi lo corregge e ripreme subito (trovato il
	// 25/08/2026, seconda verifica: il primo spostamento andava troppo in
	// anticipo).
	set_transient( 'gs_dom_cd_' . $uid, 1, max( 5, (int) gs_esperti_limiti()['cooldown'] ) );

	$did = wp_insert_post( array(
		'post_type'    => 'gs_domanda',
		'post_status'  => 'publish',
		'post_author'  => $uid,
		'post_content' => $testo,
	) );
	if ( is_wp_error( $did ) || ! $did ) { wp_send_json_error( array( 'message' => 'Errore nell\'invio.' ) ); }
	update_post_meta( $did, 'gs_canale', $slug );
	update_post_meta( $did, 'gs_risposte', array() );

	// Notifica email all'esperto del canale (se assegnato).
	$esperto_uid = gs_esperto_di( $slug );
	$esperto     = $esperto_uid ? get_user_by( 'id', $esperto_uid ) : null;
	if ( $esperto && $esperto->user_email ) {
		$autore  = wp_get_current_user();
		$pid     = (int) get_option( 'gs_page_esperto' );
		$link    = $pid ? add_query_arg( 'canale', $slug, get_permalink( $pid ) ) : home_url();
		$nome_ch = $ch['nome'];
		$corpo   = "Ciao " . $esperto->display_name . ",\n\n"
			. $autore->display_name . " ha fatto una nuova domanda nel canale \"" . $nome_ch . "\":\n\n"
			. "\"" . $testo . "\"\n\n"
			. "Rispondi qui: " . $link . "\n\n— Accademia della Sfoglia";
		wp_mail( $esperto->user_email, 'Nuova domanda in "' . $nome_ch . '"', $corpo );
	}

	wp_send_json_success( array( 'message' => 'Domanda inviata!' ) );
}

// -----------------------------------------------------------------------------
// AJAX — domanda PRIVATA a token (sfoglina), dal 2026-07-30
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_esperto_domanda_privata', 'gs_ajax_esperto_domanda_privata' );
function gs_ajax_esperto_domanda_privata() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	// Qui era più grave che altrove: questa domanda spende un token, e i
	// token sono soldi versati con bonifico. Stessa cautela delle altre
	// dieci trovate insieme il 26/08/2026.
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Non puoi scrivere.' ) ); }

	$slug = isset( $_POST['canale'] ) ? sanitize_key( wp_unslash( $_POST['canale'] ) ) : '';
	$ch   = gs_esperto_canale( $slug );
	if ( ! $ch || empty( $ch['attivo'] ) ) { wp_send_json_error( array( 'message' => 'Canale non disponibile.' ) ); }

	$esperto_uid = (int) $ch['esperto'];
	if ( ! $esperto_uid ) { wp_send_json_error( array( 'message' => 'Questo canale non ha ancora un maestro assegnato.' ) ); }
	if ( $esperto_uid === $uid ) { wp_send_json_error( array( 'message' => 'Non puoi scrivere a te stessa.' ) ); }

	// Anti-spam: honeypot + trappola tempo + limite orario.
	$check = gs_antispam_check( $_POST, 'domanda' );
	if ( is_wp_error( $check ) ) { wp_send_json_error( array( 'message' => $check->get_error_message() ) ); }

	// Limiti per sfoglina (cooldown + giorno/settimana/mese) configurabili.
	$lim = gs_domande_limiti_ok( $uid );
	if ( is_wp_error( $lim ) ) { wp_send_json_error( array( 'message' => $lim->get_error_message() ) ); }

	$oggetto = isset( $_POST['oggetto'] ) ? sanitize_text_field( wp_unslash( $_POST['oggetto'] ) ) : '';
	$oggetto = trim( $oggetto );
	if ( mb_strlen( $oggetto ) < 5 ) { wp_send_json_error( array( 'message' => 'Scrivi la tua domanda nell\'Oggetto (almeno qualche parola).' ) ); }
	if ( mb_strlen( $oggetto ) > 150 ) { $oggetto = mb_substr( $oggetto, 0, 150 ); }

	$dettagli = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';
	$dettagli = trim( $dettagli );
	if ( mb_strlen( $dettagli ) > 2000 ) { $dettagli = mb_substr( $dettagli, 0, 2000 ); }

	// Il blocco va scritto SUBITO prima del primo effetto (qui: il controllo
	// del saldo, poi la creazione della conversazione e l'addebito), non
	// alla fine: stessa lezione del marcatore per sfoglina nella chiusura
	// del mese. Ma DOPO i controlli sul testo qui sopra — sono solo
	// letture — perché un oggetto troppo corto è l'errore più comune in un
	// modulo, non un caso raro come un canale rotto: non deve costare
	// un'attesa a chi lo corregge e ripreme subito (25/08/2026, seconda
	// verifica: il primo spostamento andava troppo in anticipo). Messo alla
	// fine come nella versione originale, un doppio clic o due schede
	// aperte passano entrambe il controllo di gs_domande_limiti_ok() più in
	// alto prima che una qualsiasi abbia scritto questo blocco — e allora o
	// si addebita due volte la stessa domanda (la sfoglina paga due volte),
	// o si creano due domande scalando un token solo (l'Accademia perde un
	// token). Durata minima di 5 secondi indipendentemente da come Ennio ha
	// impostato l'attesa visibile: se la configura a 0, il blocco
	// anti-doppio-clic non deve sparire con lei — sono due scopi diversi
	// con lo stesso timer.
	set_transient( 'gs_dom_cd_' . $uid, 1, max( 5, (int) gs_esperti_limiti()['cooldown'] ) );

	$costo = (int) $ch['costo_token'];
	if ( ! function_exists( 'gs_token_saldo' ) || gs_token_saldo( $uid ) < $costo ) {
		wp_send_json_error( array( 'message' => 'Non hai abbastanza token. Fai un contributo associativo con bonifico (causale «CONTRIBUTO A SOSTEGNO DELL\'ASSOCIAZIONE ACCADEMIA DELLA SFOGLIA») e chiedi alla segreteria di accreditarti il credito.' ) );
	}

	$cid = gs_conv_trova_o_crea( $uid, $esperto_uid, $slug );
	if ( ! $cid ) { wp_send_json_error( array( 'message' => 'Errore nell\'invio.' ) ); }

	gs_token_movimento( $uid, -$costo, 'consumo', 'Domanda a ' . $ch['nome'] . ': ' . $oggetto );

	$testo_completo = $dettagli ? $dettagli : $oggetto;
	gs_conv_aggiungi( $cid, $uid, $testo_completo, true, null, array(
		'consulenza'  => true,
		'canale'      => $slug,
		'oggetto'     => $oggetto,
		'token_costo' => $costo,
		'rimborsato'  => false,
	) );

	wp_send_json_success( array(
		'message' => 'Domanda inviata! ' . $costo . ' token consumati.',
		'link'    => gs_conv_link( $cid ),
	) );
}

// -----------------------------------------------------------------------------
// AJAX — risposta (esperto del canale o gestore)
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_esperto_risposta', 'gs_ajax_esperto_risposta' );
function gs_ajax_esperto_risposta() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$did  = isset( $_POST['domanda'] ) ? (int) $_POST['domanda'] : 0;
	$slug = get_post_meta( $did, 'gs_canale', true );
	if ( ! $slug || 'gs_domanda' !== get_post_type( $did ) ) { wp_send_json_error( array( 'message' => 'Domanda non valida.' ) ); }
	if ( ! gs_esperto_puo_moderare( $slug ) ) { wp_send_json_error( array( 'message' => 'Solo l\'esperto o la segreteria possono rispondere.' ) ); }

	$testo = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';
	$testo = trim( $testo );
	if ( '' === $testo ) { wp_send_json_error( array( 'message' => 'Scrivi una risposta.' ) ); }

	$uid  = get_current_user_id();
	$user = wp_get_current_user();
	$risposte = gs_esperto_risposte( $did );
	$risposte[] = array(
		'id'      => uniqid( 'r' ),
		'uid'     => $uid,
		'nome'    => $user->display_name,
		'esperto' => gs_is_esperto( $uid, $slug ),
		'testo'   => $testo,
		'time'    => time(),
	);
	update_post_meta( $did, 'gs_risposte', $risposte );
	$domanda = get_post( $did );
	if ( $domanda && (int) $domanda->post_author !== (int) $uid && function_exists( 'gs_accoda_volo' ) ) {
		gs_accoda_volo( $domanda->post_author, 'L\'ESPERTO HA RISPOSTO ALLA TUA DOMANDA', gs_pagina_url( 'gs_page_esperto' ) );
	}
	wp_send_json_success( array( 'message' => 'Risposta pubblicata.' ) );
}

// -----------------------------------------------------------------------------
// AJAX — moderazione (elimina domanda / risposta)
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_esperto_del_domanda', 'gs_ajax_esperto_del_domanda' );
function gs_ajax_esperto_del_domanda() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$did  = isset( $_POST['domanda'] ) ? (int) $_POST['domanda'] : 0;
	$slug = get_post_meta( $did, 'gs_canale', true );
	if ( ! $slug || ! gs_esperto_puo_moderare( $slug ) ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	wp_trash_post( $did );
	wp_send_json_success( array( 'message' => 'Domanda spostata nel cestino.' ) );
}

add_action( 'wp_ajax_gs_esperto_modifica_risposta', 'gs_ajax_esperto_modifica_risposta' );
function gs_ajax_esperto_modifica_risposta() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$did  = isset( $_POST['domanda'] ) ? (int) $_POST['domanda'] : 0;
	$rid  = isset( $_POST['risposta'] ) ? sanitize_text_field( wp_unslash( $_POST['risposta'] ) ) : '';
	$slug = get_post_meta( $did, 'gs_canale', true );
	if ( ! $slug || ! gs_esperto_puo_moderare( $slug ) ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$testo = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';
	if ( '' === trim( $testo ) ) { wp_send_json_error( array( 'message' => 'La risposta non può essere vuota.' ) ); }
	if ( ! gs_risposta_modifica_testo( $did, $rid, $testo ) ) { wp_send_json_error( array( 'message' => 'Risposta non trovata.' ) ); }
	wp_send_json_success( array( 'message' => 'Modifiche salvate.' ) );
}

add_action( 'wp_ajax_gs_esperto_del_risposta', 'gs_ajax_esperto_del_risposta' );
function gs_ajax_esperto_del_risposta() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$did  = isset( $_POST['domanda'] ) ? (int) $_POST['domanda'] : 0;
	$rid  = isset( $_POST['risposta'] ) ? sanitize_text_field( wp_unslash( $_POST['risposta'] ) ) : '';
	$slug = get_post_meta( $did, 'gs_canale', true );
	if ( ! $slug || ! gs_esperto_puo_moderare( $slug ) ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	gs_risposta_sposta_nel_cestino( $did, $rid );
	wp_send_json_success( array( 'message' => 'Risposta spostata nel cestino.' ) );
}

add_action( 'wp_ajax_gs_esperto_ripristina_risposta', 'gs_ajax_esperto_ripristina_risposta' );
function gs_ajax_esperto_ripristina_risposta() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$did  = isset( $_POST['domanda'] ) ? (int) $_POST['domanda'] : 0;
	$rid  = isset( $_POST['risposta'] ) ? sanitize_text_field( wp_unslash( $_POST['risposta'] ) ) : '';
	$slug = get_post_meta( $did, 'gs_canale', true );
	if ( ! $slug || ! gs_esperto_puo_moderare( $slug ) ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	gs_risposta_ripristina_dal_cestino( $did, $rid );
	wp_send_json_success( array( 'message' => 'Risposta ripristinata.' ) );
}

// -----------------------------------------------------------------------------
// PANNELLO GESTORE — "L'Esperto Risponde" (crea/gestisci canali)
// -----------------------------------------------------------------------------
function gs_pannello_esperti() {
	if ( ! ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) ) { return; }
	$canali = gs_esperti_canali();
	$pid    = (int) get_option( 'gs_page_esperto' );
	$base   = $pid ? get_permalink( $pid ) : '';

	echo gs_box_open( 'L\'Esperto Risponde — consulenze private a token', '', 'gs-box-esperti' );
	?>
	<p class="gs-hint">Canali dove un maestro risponde privatamente alle domande delle sfogline (Rina Poletti Risponde, Bruno Cingolani Risponde, e ogni altro canale che crei). Puoi crearne quanti vuoi, assegnare l'esperto (un account già iscritto) e decidere quanti token consuma una domanda in quel canale. Il credito token si gestisce dal pannello «Pagamenti → Token». Le domande ancora senza risposta di tutti i canali compaiono subito qui sotto, con il link diretto alla conversazione privata per rispondere.</p>

	<?php
	// Domande private senza risposta di tutti i canali: prima di gestire i
	// canali, mostra subito le domande in sospeso — è dove atterra chi
	// clicca sulla scheda "Domande senza risposta" della Bacheca di
	// riepilogo. Dal 2026-07-30 sono conversazioni private, non più una
	// bacheca pubblica.
	$senza_risposta = array();
	foreach ( $canali as $slug => $ch ) {
		foreach ( gs_esperto_domande_pendenti( $slug ) as $p ) {
			$senza_risposta[] = array( 'conv_id' => $p['conv_id'], 'msg' => $p['msg'], 'canale' => $ch['nome'] );
		}
	}
	if ( $senza_risposta ) : ?>
		<div style="margin-bottom:14px">
			<strong>🔴 Domande senza risposta (<?php echo count( $senza_risposta ); ?>)</strong>
			<div class="gs-inbox-lista gs-paginate" data-per-page="8">
				<?php foreach ( $senza_risposta as $sr ) :
					$m  = $sr['msg'];
					$au = get_userdata( $m['from'] ); ?>
					<details class="gs-inbox-item gs-non-letto">
						<summary class="gs-inbox-oggetto"><span class="gs-dot"></span> <?php echo esc_html( $au ? $au->display_name : '—' ); ?>
							<span class="gs-msg-data"><?php echo esc_html( $sr['canale'] ); ?> · <?php echo esc_html( date_i18n( 'j/m/Y', (int) $m['time'] ) ); ?></span></summary>
						<div class="gs-inbox-corpo">
							<?php if ( ! empty( $m['oggetto'] ) ) : ?><div class="gs-conv-oggetto"><?php echo esc_html( $m['oggetto'] ); ?></div><?php endif; ?>
							<div class="gs-inbox-testo"><?php echo nl2br( esc_html( wp_strip_all_tags( $m['testo'] ) ) ); ?></div>
							<p><a class="gs-btn gs-btn-sm" href="<?php echo esc_url( gs_conv_link( $sr['conv_id'] ) ); ?>">Apri la conversazione e rispondi ↗</a></p>
						</div>
					</details>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php $lim = gs_esperti_limiti(); ?>
	<form class="gs-form gs-form-limiti" onsubmit="return false" style="background:#f7f3ea;padding:12px;border-radius:6px;margin-bottom:14px">
		<strong>Quante domande può fare una sfoglina</strong>
		<p class="gs-hint">Metti 0 per "illimitato". I limiti valgono per il totale delle domande su tutti i canali.</p>
		<div style="display:flex;flex-wrap:wrap;gap:12px">
			<label>Al giorno<br><input type="number" name="giorno" min="0" value="<?php echo (int) $lim['giorno']; ?>" style="width:90px"></label>
			<label>Alla settimana<br><input type="number" name="settimana" min="0" value="<?php echo (int) $lim['settimana']; ?>" style="width:90px"></label>
			<label>Al mese<br><input type="number" name="mese" min="0" value="<?php echo (int) $lim['mese']; ?>" style="width:90px"></label>
			<label>Pausa tra domande (sec.)<br><input type="number" name="cooldown" min="0" value="<?php echo (int) $lim['cooldown']; ?>" style="width:110px"></label>
		</div>
		<p><button class="gs-btn gs-btn-sm gs-salva-limiti">Salva limiti</button> <span class="gs-limiti-msg gs-richiesta-esito"></span></p>
	</form>

	<form class="gs-form gs-form-nuovo-canale" onsubmit="return false" style="background:var(--gs-uovo);padding:12px;border-radius:6px;margin-bottom:14px">
		<strong>Nuovo canale</strong>
		<p><label>Nome del canale (es. «Rina Poletti Risponde»)<br><input type="text" name="nome" autocomplete="off" style="width:320px"></label></p>
		<p><label>Esperto (account che risponde)<br>
			<select name="esperto" style="min-width:240px">
				<option value="0">— nessuno per ora —</option>
				<?php foreach ( get_users( array( 'orderby' => 'display_name' ) ) as $u ) : ?>
					<?php if ( ! user_can( $u->ID, 'manage_options' ) && ! user_can( $u->ID, 'gs_manage_gaming' ) && ! gs_e_sfoglina_vera( $u ) ) { continue; } ?>
					<option value="<?php echo (int) $u->ID; ?>"><?php echo esc_html( $u->display_name ); ?></option>
				<?php endforeach; ?>
			</select></label>
			<label>Token per domanda<br><input type="number" name="costo_token" min="1" value="1" style="width:90px"></label></p>
		<p><button class="gs-btn gs-btn-sm gs-crea-canale">Crea canale</button> <span class="gs-canale-msg gs-richiesta-esito"></span></p>
	</form>

	<?php if ( empty( $canali ) ) : ?>
		<p>Nessun canale. Creane uno qui sopra.</p>
	<?php else : ?>
		<table class="gs-table gs-paginate gs-tabella-canali" data-per-page="10">
			<thead><tr><th>Canale</th><th>Esperto</th><th>Token/domanda</th><th>Attivo</th><th>Azioni</th></tr></thead>
			<tbody>
			<?php foreach ( $canali as $slug => $ch ) : ?>
				<tr data-slug="<?php echo esc_attr( $slug ); ?>">
					<td><input type="text" class="gs-can-nome" value="<?php echo esc_attr( $ch['nome'] ); ?>" style="width:100%">
						<?php if ( $base ) : ?><br><a class="gs-hint" href="<?php echo esc_url( add_query_arg( 'canale', $slug, $base ) ); ?>" target="_blank">apri il canale ↗</a><?php endif; ?>
					</td>
					<td>
						<select class="gs-can-esperto">
							<option value="0">—</option>
							<?php foreach ( get_users( array( 'orderby' => 'display_name' ) ) as $u ) : ?>
								<?php if ( (int) $ch['esperto'] !== $u->ID && ! user_can( $u->ID, 'manage_options' ) && ! user_can( $u->ID, 'gs_manage_gaming' ) && ! gs_e_sfoglina_vera( $u ) ) { continue; } ?>
								<option value="<?php echo (int) $u->ID; ?>" <?php selected( (int) $ch['esperto'], $u->ID ); ?>><?php echo esc_html( $u->display_name ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
					<td><input type="number" class="gs-can-costo" min="1" value="<?php echo (int) $ch['costo_token']; ?>" style="width:70px"></td>
					<td style="text-align:center"><input type="checkbox" class="gs-can-attivo" <?php checked( ! empty( $ch['attivo'] ) ); ?>></td>
					<td>
						<button class="gs-btn gs-btn-sm gs-btn-verde gs-salva-canale">Salva</button>
						<button class="gs-btn gs-btn-sm gs-btn-ghost gs-del-canale">Elimina</button>
						<span class="gs-canale-riga-msg gs-richiesta-esito"></span>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif;
	echo gs_box_close();
}

function gs_esperti_guard() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
}

add_action( 'wp_ajax_gs_esperto_salva_limiti', 'gs_ajax_esperto_salva_limiti' );
function gs_ajax_esperto_salva_limiti() {
	gs_esperti_guard();
	$s = gs_settings();
	$s['esperti_limiti'] = array(
		'giorno'    => max( 0, (int) ( $_POST['giorno'] ?? 0 ) ),
		'settimana' => max( 0, (int) ( $_POST['settimana'] ?? 0 ) ),
		'mese'      => max( 0, (int) ( $_POST['mese'] ?? 0 ) ),
		'cooldown'  => max( 0, (int) ( $_POST['cooldown'] ?? 0 ) ),
	);
	update_option( GS_OPTION, $s );
	wp_send_json_success( array( 'message' => 'Limiti salvati.' ) );
}

add_action( 'wp_ajax_gs_esperto_crea_canale', 'gs_ajax_esperto_crea_canale' );
function gs_ajax_esperto_crea_canale() {
	gs_esperti_guard();
	$nome    = isset( $_POST['nome'] ) ? sanitize_text_field( wp_unslash( $_POST['nome'] ) ) : '';
	$esperto = isset( $_POST['esperto'] ) ? (int) $_POST['esperto'] : 0;
	if ( '' === trim( $nome ) ) { wp_send_json_error( array( 'message' => 'Dai un nome al canale.' ) ); }
	$costo = isset( $_POST['costo_token'] ) && (int) $_POST['costo_token'] > 0 ? (int) $_POST['costo_token'] : 1;
	$slug = gs_esperto_slug_nuovo( $nome );
	$c = gs_esperti_canali();
	$c[ $slug ] = array( 'nome' => $nome, 'esperto' => $esperto, 'attivo' => true, 'costo_token' => $costo );
	gs_esperti_salva( $c );
	wp_send_json_success( array( 'message' => 'Canale creato.' ) );
}

add_action( 'wp_ajax_gs_esperto_salva_canale', 'gs_ajax_esperto_salva_canale' );
function gs_ajax_esperto_salva_canale() {
	gs_esperti_guard();
	$slug    = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
	$c = gs_esperti_canali();
	if ( ! isset( $c[ $slug ] ) ) { wp_send_json_error( array( 'message' => 'Canale inesistente.' ) ); }
	$c[ $slug ]['nome']        = isset( $_POST['nome'] ) ? sanitize_text_field( wp_unslash( $_POST['nome'] ) ) : $c[ $slug ]['nome'];
	$c[ $slug ]['esperto']     = isset( $_POST['esperto'] ) ? (int) $_POST['esperto'] : 0;
	$c[ $slug ]['attivo']      = ! empty( $_POST['attivo'] );
	$c[ $slug ]['costo_token'] = isset( $_POST['costo_token'] ) && (int) $_POST['costo_token'] > 0 ? (int) $_POST['costo_token'] : 1;
	gs_esperti_salva( $c );
	wp_send_json_success( array( 'message' => 'Canale salvato.' ) );
}

add_action( 'wp_ajax_gs_esperto_del_canale', 'gs_ajax_esperto_del_canale' );
function gs_ajax_esperto_del_canale() {
	gs_esperti_guard();
	$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
	$c = gs_esperti_canali();
	if ( ! isset( $c[ $slug ] ) ) { wp_send_json_error( array( 'message' => 'Canale inesistente.' ) ); }
	// Sposta nel cestino anche le domande del canale (recuperabili, il canale in sé è solo un'impostazione).
	foreach ( gs_esperto_domande( $slug ) as $d ) { wp_trash_post( $d->ID ); }
	unset( $c[ $slug ] );
	gs_esperti_salva( $c );
	wp_send_json_success( array( 'message' => 'Canale eliminato.' ) );
}
