<?php
/**
 * conversazioni.php — Conversazioni PRIVATE a due vie tra un esperto e una
 * sfoglina. Si avviano dall'esperto (dal pulsante "Rispondi in privato" su una
 * domanda pubblica, oppure scrivendo direttamente a una sfoglina) e proseguono
 * come chat privata: solo i due partecipanti la vedono. Notifiche via email.
 *
 * CPT gs_conversazione: meta gs_conv_sfoglina, gs_conv_esperto, gs_conv_canale
 * Messaggi in meta gs_msgs = [ {id, from, nome, is_esperto, testo, time, letti[]} ]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_register_conversazione_cpt' );
function gs_register_conversazione_cpt() {
	register_post_type( 'gs_conversazione', array(
		'labels'       => array( 'name' => 'Conversazioni' ),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'title' ),
	) );
}

// -----------------------------------------------------------------------------
// Trova / crea
// -----------------------------------------------------------------------------
function gs_conv_trova( $sfoglina, $esperto ) {
	$q = get_posts( array(
		'post_type'      => 'gs_conversazione',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'meta_query'     => array(
			array( 'key' => 'gs_conv_sfoglina', 'value' => (int) $sfoglina ),
			array( 'key' => 'gs_conv_esperto', 'value' => (int) $esperto ),
		),
	) );
	return $q ? $q[0] : null;
}
function gs_conv_trova_o_crea( $sfoglina, $esperto, $canale = '' ) {
	$c = gs_conv_trova( $sfoglina, $esperto );
	if ( $c ) { return $c->ID; }
	$cid = wp_insert_post( array(
		'post_type'   => 'gs_conversazione',
		'post_status' => 'publish',
		'post_title'  => 'Conversazione',
	) );
	if ( is_wp_error( $cid ) || ! $cid ) { return 0; }
	update_post_meta( $cid, 'gs_conv_sfoglina', (int) $sfoglina );
	update_post_meta( $cid, 'gs_conv_esperto', (int) $esperto );
	update_post_meta( $cid, 'gs_conv_canale', sanitize_key( $canale ) );
	update_post_meta( $cid, 'gs_conv_stato', 'attiva' );
	update_post_meta( $cid, 'gs_msgs', array() );
	return $cid;
}

/** Modalità con cui le sfogline possono avviare conversazioni: off|diretto|approvazione. */
function gs_conv_avvio_mode() {
	$s = gs_settings();
	$m = isset( $s['conv_sfoglina_avvio'] ) ? $s['conv_sfoglina_avvio'] : 'diretto';
	return in_array( $m, array( 'off', 'diretto', 'approvazione' ), true ) ? $m : 'diretto';
}
function gs_conv_stato( $cid ) {
	$s = get_post_meta( $cid, 'gs_conv_stato', true );
	return $s ? $s : 'attiva';
}

function gs_conv_msgs( $conv_id ) {
	$m = get_post_meta( $conv_id, 'gs_msgs', true );
	return is_array( $m ) ? $m : array();
}
function gs_conv_partecipa( $conv_id, $uid ) {
	$sf = (int) get_post_meta( $conv_id, 'gs_conv_sfoglina', true );
	$es = (int) get_post_meta( $conv_id, 'gs_conv_esperto', true );
	return (int) $uid === $sf || (int) $uid === $es;
}

/**
 * Link diretto a UNA conversazione, non solo alla pagina "Messaggi" in
 * generale: l'ancora #gs-conv-ID fa scrollare e aprire subito quella
 * conversazione (gaming.js, gestione dell'hash all'arrivo sulla pagina) —
 * altrimenti chi riceve l'email atterra sulla pagina giusta ma deve
 * cercarsi la conversazione da sola, come segnalato da Rina Poletti.
 */
function gs_conv_link( $conv_id ) {
	$base = function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_messaggi' ) : home_url();
	return $base . '#gs-conv-' . (int) $conv_id;
}

/** Aggiunge un messaggio, aggiorna la data e notifica l'altro partecipante. */
function gs_conv_aggiungi( $conv_id, $from_uid, $testo, $notifica = true, $media = null, $extra = array() ) {
	$es    = (int) get_post_meta( $conv_id, 'gs_conv_esperto', true );
	$sf    = (int) get_post_meta( $conv_id, 'gs_conv_sfoglina', true );
	$user  = get_user_by( 'id', $from_uid );
	$msgs  = gs_conv_msgs( $conv_id );
	$msg   = array(
		'id'      => uniqid( 'm' ),
		'from'    => (int) $from_uid,
		'nome'    => $user ? $user->display_name : 'utente',
		'is_esperto' => ( (int) $from_uid === $es ),
		'testo'   => $testo,
		'media'   => is_array( $media ) ? $media['url'] : '',
		'media_type' => is_array( $media ) ? $media['type'] : '',
		'time'    => time(),
		'letti'   => array( (int) $from_uid ),
	);
	// Campi facoltativi per le domande private a pagamento (oggetto, costo in
	// token, canale, stato del rimborso) — vedi esperti.php.
	if ( is_array( $extra ) && $extra ) {
		$msg = array_merge( $msg, $extra );
	}
	$msgs[] = $msg;
	update_post_meta( $conv_id, 'gs_msgs', $msgs );
	// "Bump" data di modifica per l'ordinamento.
	wp_update_post( array( 'ID' => $conv_id, 'post_modified' => current_time( 'mysql' ) ) );

	// Notifica email al destinatario.
	$dest_uid = ( (int) $from_uid === $es ) ? $sf : $es;
	$dest     = get_user_by( 'id', $dest_uid );
	if ( $notifica && $dest ) {
		$link  = gs_conv_link( $conv_id );
		$corpo = "Ciao " . $dest->display_name . ",\n\n"
			. ( $user ? $user->display_name : 'Qualcuno' ) . " ti ha scritto un messaggio privato:\n\n"
			. "\"" . $testo . "\"\n\n"
			. "Leggi e rispondi qui: " . $link . "\n\n— Accademia della Sfoglia";
		if ( function_exists( 'gs_mail_progetto' ) ) {
			gs_mail_progetto( $dest_uid, 'messaggi', 'Nuovo messaggio privato', $corpo );
		} elseif ( $dest->user_email ) {
			wp_mail( $dest->user_email, 'Nuovo messaggio privato', $corpo );
		}
	}
}

// -----------------------------------------------------------------------------
// Conversazioni di un utente + non letti
// -----------------------------------------------------------------------------
function gs_conv_di_utente( $uid ) {
	return get_posts( array(
		'post_type'      => 'gs_conversazione',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'orderby'        => 'modified',
		'order'          => 'DESC',
		'meta_query'     => array(
			'relation' => 'OR',
			array( 'key' => 'gs_conv_sfoglina', 'value' => (int) $uid ),
			array( 'key' => 'gs_conv_esperto', 'value' => (int) $uid ),
		),
	) );
}
function gs_conv_non_letti( $uid ) {
	$n = 0;
	foreach ( gs_conv_di_utente( $uid ) as $c ) {
		$es = (int) get_post_meta( $c->ID, 'gs_conv_esperto', true );
		// L'esperto non "vede" le richieste ancora in attesa di approvazione.
		if ( (int) $uid === $es && 'attesa' === gs_conv_stato( $c->ID ) ) { continue; }
		foreach ( gs_conv_msgs( $c->ID ) as $m ) {
			// Una domanda a pagamento rimborsata non compare più nella
			// conversazione: non deve restare un non letto "fantasma".
			if ( ! empty( $m['consulenza'] ) && ! empty( $m['rimborsato'] ) ) { continue; }
			$letti = isset( $m['letti'] ) ? array_map( 'intval', (array) $m['letti'] ) : array();
			if ( ! in_array( (int) $uid, $letti, true ) ) { $n++; }
		}
	}
	return $n;
}
/**
 * AJAX: conteggio dei messaggi non letti nelle conversazioni, interrogato ogni
 * pochi secondi per l'aeroplanino "NUOVA RISPOSTA NELLA CONVERSAZIONE".
 */
add_action( 'wp_ajax_gs_conv_conteggio', 'gs_ajax_conv_conteggio' );
function gs_ajax_conv_conteggio() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid ) {
		wp_send_json_error( array( 'non_letti' => 0 ) );
	}
	wp_send_json_success( array( 'non_letti' => (int) gs_conv_non_letti( $uid ) ) );
}

function gs_conv_segna_letti( $conv_id, $uid ) {
	$msgs = gs_conv_msgs( $conv_id );
	$mod = false;
	foreach ( $msgs as &$m ) {
		$letti = isset( $m['letti'] ) ? array_map( 'intval', (array) $m['letti'] ) : array();
		if ( ! in_array( (int) $uid, $letti, true ) ) { $letti[] = (int) $uid; $m['letti'] = $letti; $mod = true; }
	}
	unset( $m );
	if ( $mod ) { update_post_meta( $conv_id, 'gs_msgs', $msgs ); }
}

// -----------------------------------------------------------------------------
// Rendering (usato nella pagina Messaggi, per entrambi i ruoli)
// -----------------------------------------------------------------------------
function gs_render_conversazioni( $uid, $mark_read = true ) {
	$convs = gs_conv_di_utente( $uid );
	if ( empty( $convs ) ) { return ''; }

	$out = gs_box_open( '🔒 Conversazioni private', 'gs-box-msg' );
	$out .= gs_sezione_aiuto( 'Clicca su una conversazione per aprirla e rispondere. Le richieste nuove restano in attesa finché l\'esperto non le approva; da quel momento potete scrivervi a vicenda in privato.' );
	$out .= '<div class="gs-conv-list gs-paginate" data-per-page="5">';
	foreach ( $convs as $c ) {
		$sf = (int) get_post_meta( $c->ID, 'gs_conv_sfoglina', true );
		$es = (int) get_post_meta( $c->ID, 'gs_conv_esperto', true );
		$stato = gs_conv_stato( $c->ID );
		// L'esperto non vede la richiesta finché non è approvata.
		if ( (int) $uid === $es && 'attesa' === $stato ) { continue; }
		$altro_uid = ( (int) $uid === $sf ) ? $es : $sf;
		$altro = get_user_by( 'id', $altro_uid );
		// Una domanda a pagamento rimborsata scompare dalla conversazione (i
		// dati restano comunque nello storico token, mai cancellati per
		// sempre): se la sfoglina vuole rifare la domanda, ne invia una
		// nuova — richiesto da Ennio il 2026-07-30.
		$msgs = array_values( array_filter( gs_conv_msgs( $c->ID ), function ( $m ) {
			if ( ! empty( $m['gs_eliminato'] ) ) { return false; }
			return empty( $m['consulenza'] ) || empty( $m['rimborsato'] );
		} ) );
		if ( ! $msgs ) { continue; } // restava solo una domanda rimborsata: niente da mostrare

		$non_letti = 0;
		foreach ( $msgs as $m ) {
			$letti = isset( $m['letti'] ) && is_array( $m['letti'] ) ? $m['letti'] : array();
			if ( (int) $m['from'] !== (int) $uid && ! in_array( (int) $uid, array_map( 'intval', $letti ), true ) ) { $non_letti++; }
		}
		$out .= '<details class="gs-inbox-item gs-conv' . ( $non_letti ? ' gs-non-letto' : '' ) . '" data-id="' . (int) $c->ID . '" id="gs-conv-' . (int) $c->ID . '">';
		$out .= '<summary class="gs-inbox-oggetto">' . ( $non_letti ? '<span class="gs-dot"></span> ' : '' )
			. 'Con ' . esc_html( $altro ? $altro->display_name : 'utente' );
		if ( 'attesa' === $stato ) { $out .= ' <span class="gs-msg-tag">in attesa di approvazione</span>'; }
		$out .= ' <span class="gs-msg-data">' . count( $msgs ) . ' messaggi</span></summary>';
		$out .= '<div class="gs-inbox-corpo">';
		$out .= '<div class="gs-conv-thread">';
		foreach ( $msgs as $m ) {
			$mine = ( (int) $m['from'] === (int) $uid );
			$out .= '<div class="gs-conv-msg' . ( $mine ? ' mine' : '' ) . '">';
			$out .= '<span class="gs-conv-from">' . esc_html( $m['nome'] ) . ( ! empty( $m['is_esperto'] ) ? ' <span class="gs-esperto-badge">Esperto</span>' : '' ) . '</span> ';
			$out .= '<span class="gs-msg-data">' . esc_html( date_i18n( 'j/m/Y H:i', (int) $m['time'] ) ) . '</span>';
			if ( ! empty( $m['oggetto'] ) ) {
				$out .= '<div class="gs-conv-oggetto">' . esc_html( $m['oggetto'] ) . '</div>';
			}
			$out .= '<div>' . nl2br( esc_html( gs_msg_clean( $m['testo'] ) ) ) . '</div>';
			if ( ! empty( $m['media'] ) && function_exists( 'gs_msg_media_html' ) ) {
				$out .= gs_msg_media_html( $m['media'], isset( $m['media_type'] ) ? $m['media_type'] : 'image' );
			}
			// Domanda privata a pagamento non ancora rimborsata: pallino del
			// token consumato + pulsante per rimborsarla a mano, solo a chi
			// modera il canale (esperto o gestore), mai alla sfoglina. Una
			// domanda già rimborsata non arriva più qui: è stata tolta da
			// $msgs più sopra, quindi "scompare" dalla conversazione.
			if ( ! empty( $m['consulenza'] ) && ! empty( $m['token_costo'] ) ) {
				$out .= '<div class="gs-conv-token">🎫 ' . (int) $m['token_costo'] . ' token</div>';
				$puo_rimborsare = function_exists( 'gs_esperto_puo_moderare' ) && ! empty( $m['canale'] )
					? gs_esperto_puo_moderare( $m['canale'] )
					: ( function_exists( 'gs_can_manage' ) && gs_can_manage() );
				if ( $puo_rimborsare ) {
					$out .= '<p><button class="gs-btn gs-btn-sm gs-btn-ghost gs-conv-rimborsa" data-conv="' . (int) $c->ID . '" data-msg="' . esc_attr( $m['id'] ) . '">↩️ Rimborsa token</button> <span class="gs-conv-rimborsa-msg gs-richiesta-esito"></span></p>';
				}
			}
			$out .= '</div>';
		}
		$out .= '</div>';
		$out .= '<form class="gs-form gs-form-conv" data-id="' . (int) $c->ID . '" onsubmit="return false">';
		if ( function_exists( 'gs_antispam_fields' ) ) { ob_start(); gs_antispam_fields(); $out .= ob_get_clean(); }
		$out .= '<textarea name="testo" rows="2" style="width:100%" placeholder="Scrivi una risposta privata…"></textarea>'
			. '<p>' . gs_msg_file_field() . '</p>'
			. '<p><button class="gs-btn gs-btn-sm gs-conv-invia">Invia</button> <span class="gs-conv-msg-out gs-richiesta-esito"></span></p></form>';
		$out .= '</div></details>';

		if ( $mark_read ) { gs_conv_segna_letti( $c->ID, $uid ); }
	}
	$out .= '</div>';
	$out .= gs_box_close();
	return $out;
}

/**
 * Sola lettura, per il titolare: tutte le conversazioni di un iscritto o
 * collaboratore (in entrambi i ruoli, sfoglina o esperto), senza segnarle
 * come lette e senza il modulo di risposta — qui si legge soltanto.
 */
function gs_msg_conversazioni_html( $uid ) {
	$convs = gs_conv_di_utente( $uid );
	if ( ! $convs ) {
		return '<p class="gs-hint">Nessuna conversazione privata.</p>';
	}
	$out = '<div class="gs-inbox-lista gs-paginate" data-per-page="5">';
	foreach ( $convs as $c ) {
		$sf_id     = (int) get_post_meta( $c->ID, 'gs_conv_sfoglina', true );
		$es_id     = (int) get_post_meta( $c->ID, 'gs_conv_esperto', true );
		$altro_id  = ( (int) $uid === $sf_id ) ? $es_id : $sf_id;
		$altro     = get_userdata( $altro_id );
		$msgs      = gs_conv_msgs( $c->ID );
		$out .= '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">Con ' . esc_html( $altro ? $altro->display_name : '—' )
			. ' <span class="gs-msg-data">' . count( $msgs ) . ' messaggi</span></summary>';
		$out .= '<div class="gs-inbox-corpo"><div class="gs-conv-thread">';
		foreach ( $msgs as $m ) {
			$out .= '<div class="gs-conv-msg">';
			$out .= '<span class="gs-conv-from">' . esc_html( $m['nome'] ) . ( ! empty( $m['is_esperto'] ) ? ' <span class="gs-esperto-badge">Esperto</span>' : '' ) . '</span> ';
			$out .= '<span class="gs-msg-data">' . esc_html( date_i18n( 'j/m/Y H:i', (int) $m['time'] ) ) . '</span>';
			$out .= '<div>' . nl2br( esc_html( gs_msg_clean( $m['testo'] ) ) ) . '</div>';
			if ( ! empty( $m['media'] ) && function_exists( 'gs_msg_media_html' ) ) {
				$out .= gs_msg_media_html( $m['media'], isset( $m['media_type'] ) ? $m['media_type'] : 'image' );
			}
			$out .= '</div>';
		}
		$out .= '</div></div></details>';
	}
	$out .= '</div>';
	return $out;
}

// -----------------------------------------------------------------------------
// AJAX
// -----------------------------------------------------------------------------

/** Invio in una conversazione esistente (partecipante o gestore). */
add_action( 'wp_ajax_gs_conv_invia', 'gs_ajax_conv_invia' );
function gs_ajax_conv_invia() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	$cid = isset( $_POST['conv'] ) ? (int) $_POST['conv'] : 0;
	if ( ! $cid || 'gs_conversazione' !== get_post_type( $cid ) ) { wp_send_json_error( array( 'message' => 'Conversazione non valida.' ) ); }
	if ( ! gs_conv_partecipa( $cid, $uid ) ) { wp_send_json_error( array( 'message' => 'Non fai parte di questa conversazione.' ) ); }
	if ( function_exists( 'gs_antispam_check' ) ) {
		$check = gs_antispam_check( $_POST, 'conv_invia' );
		if ( is_wp_error( $check ) ) { wp_send_json_error( array( 'message' => $check->get_error_message() ) ); }
	}
	$testo = isset( $_POST['testo'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) ) : '';
	$media = function_exists( 'gs_msg_upload' ) ? gs_msg_upload( 'media' ) : null;
	if ( is_wp_error( $media ) ) { wp_send_json_error( array( 'message' => $media->get_error_message() ) ); }
	if ( '' === $testo && ! $media ) { wp_send_json_error( array( 'message' => 'Scrivi un messaggio o allega un file.' ) ); }
	gs_conv_aggiungi( $cid, $uid, $testo, true, $media );
	wp_send_json_success( array( 'message' => 'Inviato.' ) );
}

// -----------------------------------------------------------------------------
// Permessi: con quali sfogline un esperto può conversare (deciso dal gestore)
// -----------------------------------------------------------------------------
function gs_conv_allow() {
	$s = gs_settings();
	return isset( $s['conv_allow'] ) && is_array( $s['conv_allow'] ) ? $s['conv_allow'] : array();
}
/** True se l'esperto può scrivere a quella sfoglina (lista vuota = tutte). */
function gs_conv_permesso( $esperto_uid, $sfoglina_uid ) {
	$a = gs_conv_allow();
	if ( empty( $a[ $esperto_uid ] ) ) { return true; }
	return in_array( (int) $sfoglina_uid, array_map( 'intval', (array) $a[ $esperto_uid ] ), true );
}
function gs_conv_all() {
	return get_posts( array(
		'post_type'      => 'gs_conversazione',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'modified',
		'order'          => 'DESC',
	) );
}

/** L'esperto/gestore avvia (o continua) una privata rispondendo a una domanda. */
add_action( 'wp_ajax_gs_conv_da_domanda', 'gs_ajax_conv_da_domanda' );
function gs_ajax_conv_da_domanda() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$did  = isset( $_POST['domanda'] ) ? (int) $_POST['domanda'] : 0;
	$slug = get_post_meta( $did, 'gs_canale', true );
	if ( ! $slug || 'gs_domanda' !== get_post_type( $did ) ) { wp_send_json_error( array( 'message' => 'Domanda non valida.' ) ); }
	if ( ! gs_esperto_puo_moderare( $slug ) ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$testo = isset( $_POST['testo'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) ) : '';
	if ( '' === $testo ) { wp_send_json_error( array( 'message' => 'Scrivi un messaggio.' ) ); }

	$domanda   = get_post( $did );
	$sfoglina  = (int) $domanda->post_author;
	$esperto   = get_current_user_id();
	if ( ! gs_can_manage() && ! gs_conv_permesso( $esperto, $sfoglina ) ) {
		wp_send_json_error( array( 'message' => 'Non sei abilitato a scrivere in privato a questa sfoglina.' ) );
	}
	$cid = gs_conv_trova_o_crea( $sfoglina, $esperto, $slug );
	if ( ! $cid ) { wp_send_json_error( array( 'message' => 'Errore.' ) ); }
	gs_conv_aggiungi( $cid, $esperto, $testo );
	wp_send_json_success( array( 'message' => 'Messaggio privato inviato.' ) );
}

/**
 * Piccolo modulo per rispondere/avviare una conversazione privata con una
 * sfoglina già nota (es. l'autrice di una ricetta, di una sfoglia in
 * gara...), da qualunque pannello: il gestore che lo usa è "l'esperto"
 * della conversazione, la sfoglina è già scelta, non serve un menu.
 * Riusa l'azione gs_conv_admin_crea (gs_can_manage(), nessun vincolo di
 * canale) e lo stesso JS di "Avvia una conversazione" nel pannello
 * Conversazioni — nessun nuovo codice JS necessario.
 */
function gs_conv_avvia_rapida_html( $sfoglina_uid ) {
	$uid = get_current_user_id();
	if ( ! $uid || ! $sfoglina_uid ) { return ''; }
	$out  = '<form class="gs-form gs-form-conv-admin" onsubmit="return false" style="margin-top:8px">';
	$out .= '<input type="hidden" name="esperto" value="' . (int) $uid . '">';
	$out .= '<input type="hidden" name="sfoglina" value="' . (int) $sfoglina_uid . '">';
	$out .= '<p><label>✉️ Rispondi alla sfoglina (avvia una conversazione privata)<br><textarea name="testo" rows="2" style="width:100%" placeholder="Il tuo messaggio…"></textarea></label></p>';
	$out .= '<p><button class="gs-btn gs-btn-sm gs-conv-admin-crea">Invia</button> <span class="gs-conv-admin-msg gs-richiesta-esito"></span></p>';
	$out .= '</form>';
	return $out;
}

/** L'esperto/gestore scrive in privato a una sfoglina scelta. */
add_action( 'wp_ajax_gs_conv_nuova', 'gs_ajax_conv_nuova' );
function gs_ajax_conv_nuova() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$slug = isset( $_POST['canale'] ) ? sanitize_key( wp_unslash( $_POST['canale'] ) ) : '';
	if ( ! gs_esperto_puo_moderare( $slug ) ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$sfoglina = isset( $_POST['sfoglina'] ) ? (int) $_POST['sfoglina'] : 0;
	$testo    = isset( $_POST['testo'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) ) : '';
	if ( ! $sfoglina || ! get_userdata( $sfoglina ) ) { wp_send_json_error( array( 'message' => 'Scegli una sfoglina.' ) ); }
	if ( '' === $testo ) { wp_send_json_error( array( 'message' => 'Scrivi un messaggio.' ) ); }
	$esperto = get_current_user_id();
	if ( ! gs_can_manage() && ! gs_conv_permesso( $esperto, $sfoglina ) ) {
		wp_send_json_error( array( 'message' => 'Non sei abilitato a scrivere in privato a questa sfoglina.' ) );
	}
	$cid = gs_conv_trova_o_crea( $sfoglina, $esperto, $slug );
	if ( ! $cid ) { wp_send_json_error( array( 'message' => 'Errore.' ) ); }
	gs_conv_aggiungi( $cid, $esperto, $testo );
	wp_send_json_success( array( 'message' => 'Messaggio privato inviato.' ) );
}

// =============================================================================
// PANNELLO GESTORE — controllo delle conversazioni private
// =============================================================================
function gs_pannello_conversazioni() {
	if ( ! ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) ) { return; }

	// Esperti = utenti assegnati come esperto in almeno un canale.
	$esperti = array();
	if ( function_exists( 'gs_esperti_canali' ) ) {
		foreach ( gs_esperti_canali() as $ch ) {
			if ( ! empty( $ch['esperto'] ) ) { $esperti[ (int) $ch['esperto'] ] = true; }
		}
	}
	$esperti = array_keys( $esperti );
	$approvate = array();
	foreach ( get_users( array( 'orderby' => 'display_name' ) ) as $u ) {
		if ( gs_e_sfoglina_vera( $u ) ) { $approvate[] = $u; }
	}
	$allow = gs_conv_allow();

	echo gs_box_open( 'Conversazioni private — controllo', 'gs-box-msg', 'gs-box-conversazioni' );
	$mode = gs_conv_avvio_mode();
	?>
	<p class="gs-hint">Decidi con quali sfogline ogni esperto può parlare in privato, avvia tu una conversazione e tieni tutto sotto controllo. Lista vuota = l'esperto può scrivere a tutte.</p>

	<form class="gs-form gs-form-conv-mode" onsubmit="return false" style="background:#f7f3ea;padding:10px 12px;border-radius:6px;margin-bottom:12px">
		<strong>Le sfogline possono avviare una conversazione con un esperto?</strong>
		<p style="margin:6px 0">
			<label style="margin-right:14px"><input type="radio" name="mode" value="off" <?php checked( $mode, 'off' ); ?>> No</label>
			<label style="margin-right:14px"><input type="radio" name="mode" value="diretto" <?php checked( $mode, 'diretto' ); ?>> Sì, subito</label>
			<label><input type="radio" name="mode" value="approvazione" <?php checked( $mode, 'approvazione' ); ?>> Sì, con la mia approvazione</label>
		</p>
		<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-salva-conv-mode">Salva</button> <span class="gs-conv-mode-msg gs-richiesta-esito"></span></p>
	</form>

	<?php
	// Richieste in attesa di approvazione.
	$attesa = gs_conv_in_attesa();
	if ( $attesa ) : ?>
		<div style="background:#f3e5c7;border:1px solid #a8862e;border-radius:6px;padding:10px 12px;margin-bottom:12px">
			<strong>Richieste in attesa (<?php echo count( $attesa ); ?>)</strong>
			<table class="gs-table gs-paginate" data-per-page="10"><tbody>
			<?php foreach ( $attesa as $c ) :
				$sf = get_user_by( 'id', (int) get_post_meta( $c->ID, 'gs_conv_sfoglina', true ) );
				$es = get_user_by( 'id', (int) get_post_meta( $c->ID, 'gs_conv_esperto', true ) );
				$ms = gs_conv_msgs( $c->ID );
				$primo = $ms ? $ms[0]['testo'] : ''; ?>
				<tr data-conv="<?php echo (int) $c->ID; ?>">
					<td><strong><?php echo esc_html( $sf ? $sf->display_name : '—' ); ?></strong> → <?php echo esc_html( $es ? $es->display_name : '—' ); ?>
						<br><span class="gs-hint"><?php echo esc_html( wp_trim_words( $primo, 20 ) ); ?></span></td>
					<td>
						<button class="gs-btn gs-btn-sm gs-conv-approva">Approva</button>
						<button class="gs-btn gs-btn-sm gs-btn-ghost gs-conv-rifiuta">Rifiuta</button>
						<span class="gs-conv-att-msg gs-richiesta-esito"></span>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody></table>
		</div>
	<?php endif; ?>

	<?php if ( $esperti ) : ?>
		<strong>Con chi può parlare ogni esperto</strong>
		<?php foreach ( $esperti as $euid ) :
			$eu = get_user_by( 'id', $euid ); if ( ! $eu ) { continue; }
			$sel = isset( $allow[ $euid ] ) ? array_map( 'intval', (array) $allow[ $euid ] ) : array(); ?>
			<div class="gs-conv-perm" data-esperto="<?php echo (int) $euid; ?>" style="border:1px solid var(--gs-bordo);border-radius:6px;padding:10px;margin:8px 0">
				<div><strong><?php echo esc_html( $eu->display_name ); ?></strong></div>
				<p class="gs-hint">Tieni premuto Ctrl (o Cmd) per selezionare più sfogline. Nessuna selezione = tutte.</p>
				<select multiple size="5" class="gs-perm-sfogline" style="width:100%;max-width:420px">
					<?php foreach ( $approvate as $u ) : ?>
						<option value="<?php echo (int) $u->ID; ?>" <?php echo in_array( $u->ID, $sel, true ) ? 'selected' : ''; ?>><?php echo esc_html( $u->display_name ); ?></option>
					<?php endforeach; ?>
				</select>
				<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-salva-permessi">Salva</button> <span class="gs-perm-msg gs-richiesta-esito"></span></p>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>

	<hr>
	<strong>Avvia una conversazione</strong>
	<form class="gs-form gs-form-conv-admin" onsubmit="return false" style="background:var(--gs-uovo);padding:10px;border-radius:6px;margin:8px 0">
		<p><label>Esperto/collaboratore<br>
			<select name="esperto" style="min-width:220px">
				<?php foreach ( $approvate as $u ) : ?>
					<option value="<?php echo (int) $u->ID; ?>"><?php echo esc_html( $u->display_name ); ?></option>
				<?php endforeach; ?>
			</select></label></p>
		<p><label>Sfoglina<br>
			<select name="sfoglina" style="min-width:220px">
				<?php foreach ( $approvate as $u ) : ?>
					<option value="<?php echo (int) $u->ID; ?>"><?php echo esc_html( $u->display_name ); ?></option>
				<?php endforeach; ?>
			</select></label></p>
		<textarea name="testo" rows="2" style="width:100%" placeholder="Primo messaggio (verrà attribuito all'esperto)…"></textarea>
		<p><button class="gs-btn gs-btn-sm gs-conv-admin-crea">Avvia conversazione</button> <span class="gs-conv-admin-msg gs-richiesta-esito"></span></p>
	</form>

	<hr>
	<strong>Tutte le conversazioni</strong>
	<?php $tutte = gs_conv_all();
	if ( ! $tutte ) : ?>
		<p class="gs-hint">Ancora nessuna conversazione.</p>
	<?php else : ?>
		<table class="gs-table gs-paginate" data-per-page="10"><thead><tr><th>Sfoglina</th><th>Esperto</th><th>Ultimo</th><th>Messaggi</th><th>Azioni</th></tr></thead><tbody>
		<?php foreach ( $tutte as $c ) :
			$sf = get_user_by( 'id', (int) get_post_meta( $c->ID, 'gs_conv_sfoglina', true ) );
			$es = get_user_by( 'id', (int) get_post_meta( $c->ID, 'gs_conv_esperto', true ) );
			$ms = gs_conv_msgs( $c->ID ); ?>
			<tr data-conv="<?php echo (int) $c->ID; ?>">
				<td><?php echo esc_html( $sf ? $sf->display_name : '—' ); ?></td>
				<td><?php echo esc_html( $es ? $es->display_name : '—' ); ?></td>
				<td><?php echo esc_html( get_post_modified_time( 'j/m/Y H:i', false, $c ) ); ?></td>
				<td><?php echo count( $ms ); ?></td>
				<td>
					<details><summary class="gs-hint" style="cursor:pointer">Apri</summary>
						<div style="margin-top:6px">
						<?php foreach ( $ms as $m ) :
							$eliminato = ! empty( $m['gs_eliminato'] ); ?>
							<div style="margin-bottom:6px<?php echo $eliminato ? ';opacity:.5' : ''; ?>"><strong><?php echo esc_html( $m['nome'] ); ?></strong>
							<span class="gs-hint"><?php echo esc_html( date_i18n( 'j/m/Y H:i', (int) $m['time'] ) ); ?></span>
							<?php if ( $eliminato ) : ?><span class="gs-msg-tag">eliminato</span><?php endif; ?>
							<button class="gs-btn gs-btn-sm gs-btn-ghost gs-conv-msg-toggle" data-conv="<?php echo (int) $c->ID; ?>" data-msg="<?php echo esc_attr( $m['id'] ); ?>" data-azione="<?php echo $eliminato ? 'ripristina' : 'elimina'; ?>"><?php echo $eliminato ? 'Ripristina' : 'Elimina'; ?></button>
							<span class="gs-conv-msg-toggle-esito gs-richiesta-esito"></span><br>
							<?php echo nl2br( esc_html( $m['testo'] ) ); ?></div>
						<?php endforeach; ?>
						</div>
					</details>
					<button class="gs-btn gs-btn-sm gs-btn-ghost gs-conv-admin-del">Elimina conversazione</button>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody></table>
	<?php endif;
	echo gs_box_close();
}

function gs_conv_guard() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
}

add_action( 'wp_ajax_gs_conv_salva_mode', 'gs_ajax_conv_salva_mode' );
function gs_ajax_conv_salva_mode() {
	gs_conv_guard();
	$mode = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'diretto';
	if ( ! in_array( $mode, array( 'off', 'diretto', 'approvazione' ), true ) ) { $mode = 'diretto'; }
	$s = gs_settings();
	$s['conv_sfoglina_avvio'] = $mode;
	update_option( GS_OPTION, $s );
	wp_send_json_success( array( 'message' => 'Impostazione salvata.' ) );
}

add_action( 'wp_ajax_gs_conv_salva_permessi', 'gs_ajax_conv_salva_permessi' );
function gs_ajax_conv_salva_permessi() {
	gs_conv_guard();
	$euid = isset( $_POST['esperto'] ) ? (int) $_POST['esperto'] : 0;
	$sfog = isset( $_POST['sfogline'] ) && is_array( $_POST['sfogline'] ) ? array_map( 'intval', $_POST['sfogline'] ) : array();
	if ( ! $euid ) { wp_send_json_error( array( 'message' => 'Esperto non valido.' ) ); }
	$s = gs_settings();
	if ( ! isset( $s['conv_allow'] ) || ! is_array( $s['conv_allow'] ) ) { $s['conv_allow'] = array(); }
	$s['conv_allow'][ $euid ] = $sfog;
	update_option( GS_OPTION, $s );
	wp_send_json_success( array( 'message' => empty( $sfog ) ? 'Salvato: può parlare con tutte.' : 'Salvato: ' . count( $sfog ) . ' sfogline consentite.' ) );
}

add_action( 'wp_ajax_gs_conv_admin_crea', 'gs_ajax_conv_admin_crea' );
function gs_ajax_conv_admin_crea() {
	gs_conv_guard();
	$esperto  = isset( $_POST['esperto'] ) ? (int) $_POST['esperto'] : 0;
	$sfoglina = isset( $_POST['sfoglina'] ) ? (int) $_POST['sfoglina'] : 0;
	$testo    = isset( $_POST['testo'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) ) : '';
	if ( ! $esperto || ! $sfoglina || $esperto === $sfoglina ) { wp_send_json_error( array( 'message' => 'Scegli esperto e sfoglina diversi.' ) ); }
	if ( '' === $testo ) { wp_send_json_error( array( 'message' => 'Scrivi il primo messaggio.' ) ); }
	$cid = gs_conv_trova_o_crea( $sfoglina, $esperto );
	if ( ! $cid ) { wp_send_json_error( array( 'message' => 'Errore.' ) ); }
	gs_conv_aggiungi( $cid, $esperto, $testo );
	wp_send_json_success( array( 'message' => 'Conversazione avviata.' ) );
}

add_action( 'wp_ajax_gs_conv_admin_del', 'gs_ajax_conv_admin_del' );
function gs_ajax_conv_admin_del() {
	gs_conv_guard();
	$cid = isset( $_POST['conv'] ) ? (int) $_POST['conv'] : 0;
	if ( 'gs_conversazione' !== get_post_type( $cid ) ) { wp_send_json_error( array( 'message' => 'Conversazione non valida.' ) ); }
	wp_trash_post( $cid );
	wp_send_json_success( array( 'message' => 'Conversazione spostata nel cestino.' ) );
}

/**
 * Elimina/ripristina UN messaggio dentro una conversazione, senza toccare il
 * resto: eliminazione "leggera", il messaggio resta nei dati (solo segnato) e
 * si può ripristinare dallo stesso pannello — mai una perdita definitiva
 * (punto 6, Ennio, delega "finisci tutto" del 22/08/2026).
 */
add_action( 'wp_ajax_gs_conv_msg_toggle', 'gs_ajax_conv_msg_toggle' );
function gs_ajax_conv_msg_toggle() {
	gs_conv_guard();
	$cid    = isset( $_POST['conv'] ) ? (int) $_POST['conv'] : 0;
	$mid    = isset( $_POST['msg'] ) ? sanitize_text_field( wp_unslash( $_POST['msg'] ) ) : '';
	$azione = isset( $_POST['azione'] ) ? sanitize_key( wp_unslash( $_POST['azione'] ) ) : '';
	if ( 'gs_conversazione' !== get_post_type( $cid ) ) { wp_send_json_error( array( 'message' => 'Conversazione non valida.' ) ); }
	if ( ! in_array( $azione, array( 'elimina', 'ripristina' ), true ) ) { wp_send_json_error( array( 'message' => 'Azione non valida.' ) ); }
	$msgs    = gs_conv_msgs( $cid );
	$trovato = false;
	foreach ( $msgs as &$m ) {
		if ( $m['id'] === $mid ) {
			$m['gs_eliminato'] = ( 'elimina' === $azione );
			$trovato = true;
			break;
		}
	}
	unset( $m );
	if ( ! $trovato ) { wp_send_json_error( array( 'message' => 'Messaggio non trovato.' ) ); }
	update_post_meta( $cid, 'gs_msgs', $msgs );
	wp_send_json_success( array( 'message' => 'elimina' === $azione ? 'Messaggio eliminato (recuperabile da qui).' : 'Messaggio ripristinato.' ) );
}

// -----------------------------------------------------------------------------
// AJAX — la sfoglina avvia una conversazione con un esperto
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_conv_sfoglina_richiesta', 'gs_ajax_conv_sfoglina_richiesta' );
function gs_ajax_conv_sfoglina_richiesta() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	// Anche qui si può arrivare a spendere un token: stessa cautela delle
	// altre dieci trovate insieme il 26/08/2026.
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Non puoi scrivere.' ) ); }

	$mode = gs_conv_avvio_mode();
	if ( 'off' === $mode ) { wp_send_json_error( array( 'message' => 'Le conversazioni con gli esperti non sono attive.' ) ); }

	$esperto = isset( $_POST['esperto'] ) ? (int) $_POST['esperto'] : 0;
	$canale  = isset( $_POST['canale'] ) ? sanitize_key( wp_unslash( $_POST['canale'] ) ) : '';
	$testo   = isset( $_POST['testo'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) ) : '';
	if ( ! $esperto || ! get_userdata( $esperto ) ) { wp_send_json_error( array( 'message' => 'Esperto non valido.' ) ); }
	if ( '' === $testo ) { wp_send_json_error( array( 'message' => 'Scrivi un messaggio.' ) ); }
	if ( $esperto === $uid ) { wp_send_json_error( array( 'message' => 'Non puoi scrivere a te stessa.' ) ); }

	// Rispetta la lista di chi l'esperto può contattare (vale nei due sensi).
	if ( ! gs_conv_permesso( $esperto, $uid ) ) {
		wp_send_json_error( array( 'message' => 'Questo esperto al momento non è disponibile per te.' ) );
	}

	$esistente = gs_conv_trova( $uid, $esperto );
	if ( $esistente ) {
		// Se già attiva, il messaggio entra normalmente; se in attesa, resta in attesa.
		$attesa = ( 'attesa' === gs_conv_stato( $esistente->ID ) );
		gs_conv_aggiungi( $esistente->ID, $uid, $testo, ! $attesa );
		wp_send_json_success( array( 'message' => $attesa ? 'Messaggio aggiunto alla richiesta in attesa.' : 'Messaggio inviato.' ) );
	}

	$cid = gs_conv_trova_o_crea( $uid, $esperto, $canale );
	if ( ! $cid ) { wp_send_json_error( array( 'message' => 'Errore.' ) ); }

	if ( 'approvazione' === $mode ) {
		update_post_meta( $cid, 'gs_conv_stato', 'attesa' );
		gs_conv_aggiungi( $cid, $uid, $testo, false ); // niente email all'esperto finché non è approvata
		wp_send_json_success( array( 'message' => 'Richiesta inviata: sarà visibile all\'esperto dopo l\'approvazione della segreteria.' ) );
	}

	gs_conv_aggiungi( $cid, $uid, $testo, true );
	wp_send_json_success( array( 'message' => 'Messaggio inviato all\'esperto.' ) );
}

// -----------------------------------------------------------------------------
// AJAX — approva / rifiuta una richiesta (gestore)
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_conv_approva', 'gs_ajax_conv_approva' );
function gs_ajax_conv_approva() {
	gs_conv_guard();
	$cid = isset( $_POST['conv'] ) ? (int) $_POST['conv'] : 0;
	if ( 'gs_conversazione' !== get_post_type( $cid ) ) { wp_send_json_error( array( 'message' => 'Conversazione non valida.' ) ); }
	update_post_meta( $cid, 'gs_conv_stato', 'attiva' );

	// Avvisa l'esperto che ora la conversazione è visibile.
	$es   = (int) get_post_meta( $cid, 'gs_conv_esperto', true );
	$dest = get_user_by( 'id', $es );
	if ( $dest && $dest->user_email ) {
		$link = gs_conv_link( $cid );
		wp_mail( $dest->user_email, 'Nuova conversazione approvata', "Ciao " . $dest->display_name . ",\n\nUna sfoglina ti ha scritto in privato e la segreteria ha approvato la conversazione.\nLeggi e rispondi qui: " . $link . "\n\n— Accademia della Sfoglia" );
	}
	wp_send_json_success( array( 'message' => 'Richiesta approvata.' ) );
}

add_action( 'wp_ajax_gs_conv_rifiuta', 'gs_ajax_conv_rifiuta' );
function gs_ajax_conv_rifiuta() {
	gs_conv_guard();
	$cid = isset( $_POST['conv'] ) ? (int) $_POST['conv'] : 0;
	if ( 'gs_conversazione' !== get_post_type( $cid ) ) { wp_send_json_error( array( 'message' => 'Conversazione non valida.' ) ); }
	wp_trash_post( $cid );
	wp_send_json_success( array( 'message' => 'Richiesta rifiutata.' ) );
}

/** Conversazioni in attesa di approvazione (per il pannello). */
function gs_conv_in_attesa() {
	return get_posts( array(
		'post_type'      => 'gs_conversazione',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_key'       => 'gs_conv_stato',
		'meta_value'     => 'attesa',
	) );
}

/**
 * Rimborso manuale del token di una domanda privata a pagamento — l'esperto
 * del canale o un gestore possono restituirlo subito (es. sanno già che non
 * risponderanno), senza aspettare il rimborso automatico del cron.
 */
add_action( 'wp_ajax_gs_conv_rimborsa_token', 'gs_ajax_conv_rimborsa_token' );
function gs_ajax_conv_rimborsa_token() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$cid = isset( $_POST['conv'] ) ? (int) $_POST['conv'] : 0;
	$mid = isset( $_POST['msg'] ) ? sanitize_text_field( wp_unslash( $_POST['msg'] ) ) : '';
	if ( 'gs_conversazione' !== get_post_type( $cid ) ) {
		wp_send_json_error( array( 'message' => 'Conversazione non valida.' ) );
	}
	$msgs = gs_conv_msgs( $cid );
	$trovato = -1;
	foreach ( $msgs as $i => $m ) {
		if ( $m['id'] === $mid ) { $trovato = $i; break; }
	}
	if ( $trovato < 0 ) {
		wp_send_json_error( array( 'message' => 'Domanda non trovata.' ) );
	}
	$m = $msgs[ $trovato ];
	if ( empty( $m['consulenza'] ) || empty( $m['token_costo'] ) ) {
		wp_send_json_error( array( 'message' => 'Questa domanda non è a pagamento.' ) );
	}
	$puo = ! empty( $m['canale'] ) && function_exists( 'gs_esperto_puo_moderare' )
		? gs_esperto_puo_moderare( $m['canale'] )
		: ( function_exists( 'gs_can_manage' ) && gs_can_manage() );
	if ( ! $puo ) {
		wp_send_json_error( array( 'message' => 'Permesso negato.' ) );
	}

	$r = gs_conv_rimborsa_token_uid( $cid, $mid );
	if ( $r['ok'] ) { wp_send_json_success( array( 'message' => $r['message'] ) ); }
	wp_send_json_error( array( 'message' => $r['message'] ) );
}

/**
 * Rimborsa il token del messaggio $mid nella conversazione $cid, con
 * lucchetto MySQL per conversazione. Questo percorso a mano e il rimborso
 * automatico del cron (gs_token_controlla_rimborsi in token.php) leggono e
 * riscrivono lo stesso gs_msgs: senza un lucchetto condiviso, un clic a
 * mano nello stesso istante del cron poteva restituire il token due volte
 * (trovato 26/08/2026). Stessa chiave in entrambi i file:
 * 'gs_conv_rimborso_' . $cid.
 */
function gs_conv_rimborsa_token_uid( $cid, $mid ) {
	global $wpdb;
	$lock = 'gs_conv_rimborso_' . (int) $cid;
	if ( '1' !== $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) ) ) {
		return array( 'ok' => false, 'message' => 'Operazione occupata, riprova tra un attimo.' );
	}

	try {
		// Svuota la cache dei meta di questo post prima di leggere: se un
		//'get_posts()' precedente nella stessa richiesta li avesse già letti,
		// la lettura dentro il lucchetto vedrebbe un valore vecchio invece di
		// quello scritto nel frattempo dal rimborso automatico del cron
		// (stesso motivo della cautela gemella in gs_token_controlla_rimborsi).
		wp_cache_delete( $cid, 'post_meta' );
		$msgs    = gs_conv_msgs( $cid );
		$trovato = -1;
		foreach ( $msgs as $i => $m ) {
			if ( $m['id'] === $mid ) { $trovato = $i; break; }
		}
		if ( $trovato < 0 ) {
			return array( 'ok' => false, 'message' => 'Domanda non trovata.' );
		}
		$m = $msgs[ $trovato ];
		if ( ! empty( $m['rimborsato'] ) ) {
			return array( 'ok' => false, 'message' => 'Già rimborsata.' );
		}

		$sfoglina = (int) $m['from'];

		// Contrassegno PRIMA del movimento: il lucchetto impedisce a due
		// rimborsi di partire insieme, ma non impedisce a UNO di morire a
		// metà (errore fatale, tempo massimo di esecuzione). Se il
		// contrassegno fosse scritto dopo gs_token_movimento(), un'
		// interruzione lascerebbe il token già restituito e il messaggio
		// ancora "da rimborsare": il giro dopo lo rimborsa di nuovo — stessa
		// protezione già scritta in buono-sfoglia.php, stesso motivo
		// (trovato 26/08/2026).
		$msgs[ $trovato ]['rimborsato'] = true;
		update_post_meta( $cid, 'gs_msgs', $msgs );
		$nuovo = gs_token_movimento( $sfoglina, (int) $m['token_costo'], 'rimborso', 'Rimborso a mano di una domanda senza risposta' );

		$user = get_user_by( 'id', $sfoglina );
		if ( $user && function_exists( 'gs_mail_progetto' ) ) {
			gs_mail_progetto(
				$sfoglina,
				'messaggi',
				'Token restituito',
				"Ciao " . $user->display_name . ",\n\nti abbiamo restituito il token della tua domanda privata.\n\nSaldo attuale: " . $nuovo . " token.\n\n— Accademia della Sfoglia"
			);
		}

		return array( 'ok' => true, 'message' => 'Token restituito. Saldo sfoglina: ' . $nuovo . '.' );
	} finally {
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	}
}
