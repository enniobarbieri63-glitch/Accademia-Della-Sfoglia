<?php
/**
 * matterello-parlante.php — Il Matterello Parlante: archivio vocale di
 * ricordi e consigli registrati a voce dalle sfogline. Stesso schema di
 * approvazione del Ricettario delle Famiglie e della Biografia: ogni
 * registrazione compare nell'archivio pubblico solo dopo l'approvazione.
 *
 * CPT gs_voce → meta:
 *  - gs_audio_url     (URL del file audio caricato)
 *  - gs_trascrizione  (testo facoltativo di accompagnamento)
 *  - gs_stato         ('in_attesa' | 'approvata' | 'rifiutata')
 * Il titolo del ricordo sta in post_title.
 *
 * Limite da dichiarare: la registrazione diretta dal browser richiede
 * MediaRecorder (Chrome, Edge, Opera, Firefox recenti); dove non è
 * supportata resta sempre possibile caricare un file audio già registrato.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_voce_register_cpt' );
function gs_voce_register_cpt() {
	register_post_type( 'gs_voce', array(
		'labels'       => array( 'name' => 'Il Matterello Parlante' ),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'title', 'author' ),
	) );
}

// -----------------------------------------------------------------------------
// Helper
// -----------------------------------------------------------------------------

function gs_voce_get( $id ) {
	return array(
		'id'           => $id,
		'titolo'       => get_the_title( $id ),
		'audio_url'    => (string) get_post_meta( $id, 'gs_audio_url', true ),
		'trascrizione' => (string) get_post_meta( $id, 'gs_trascrizione', true ),
		'stato'        => get_post_meta( $id, 'gs_stato', true ) ?: 'in_attesa',
		'autore'       => get_post( $id ) ? (int) get_post( $id )->post_author : 0,
	);
}

function gs_voci_approvate() {
	return gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_voce',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_key'       => 'gs_stato',
		'meta_value'     => 'approvata',
		'suppress_filters' => true,
	) ), 'gs_voce' );
}

function gs_voci_utente( $uid ) {
	$uid = (int) $uid;
	if ( ! $uid ) { return array(); }
	return gs_solo_tipo( gs_get_posts_by_author( array(
		'post_type'      => 'gs_voce',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'author'         => $uid,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'suppress_filters' => true,
	) ), 'gs_voce' );
}

/**
 * Chat testuale sotto ogni registrazione approvata: solo commenti scritti,
 * mai audio — la voce resta quella di chi ha registrato il ricordo.
 * Nessuna approvazione: come nelle Conversazioni, il commento compare subito.
 */
function gs_voce_commenti_get( $id ) {
	$c = get_post_meta( (int) $id, 'gs_voce_commenti', true );
	return is_array( $c ) ? $c : array();
}

function gs_voce_commenti_cestino_get( $id ) {
	$c = get_post_meta( (int) $id, 'gs_voce_commenti_cestino', true );
	return is_array( $c ) ? $c : array();
}

/**
 * Aggiunge un commento. Se contiene una parola/frase vietata (Impostazioni >
 * Moderazione), viene comunque pubblicato (per non censurare falsi allarmi
 * in automatico) ma segnato come "segnalato" e la redazione riceve un
 * avviso nella posta interna per poterlo controllare ed eventualmente
 * eliminare.
 */
function gs_voce_commento_aggiungi( $id, $uid, $testo ) {
	$id    = (int) $id;
	$uid   = (int) $uid;
	$testo = trim( (string) $testo );
	if ( ! $id || ! $uid || '' === $testo ) { return false; }

	$parola     = function_exists( 'gs_testo_parola_vietata_trovata' ) ? gs_testo_parola_vietata_trovata( $testo ) : '';
	$commenti   = gs_voce_commenti_get( $id );
	$commenti[] = array(
		'id'        => uniqid( 'vc' ),
		'autore'    => $uid,
		'testo'     => $testo,
		'data'      => current_time( 'mysql' ),
		'segnalato' => '' !== $parola,
	);
	if ( count( $commenti ) > 200 ) { $commenti = array_slice( $commenti, -200 ); }
	update_post_meta( $id, 'gs_voce_commenti', $commenti );

	if ( '' !== $parola && function_exists( 'gs_inbox_crea' ) ) {
		$au = get_userdata( $uid );
		gs_inbox_crea(
			'⚠️ Parola da controllare nel Matterello Parlante',
			( $au ? $au->display_name : 'Qualcuno' ) . ' ha scritto un commento sotto "' . get_the_title( $id ) . '" che contiene "' . $parola . "\":\n\n" . $testo,
			array( 'from' => $au ? $au->display_name : 'Sfoglina', 'link_ancora' => 'admin.php?page=gs-generale#gs-zona-matterello' )
		);
	}
	return true;
}

/** Sposta un commento nel cestino (recuperabile), mai cancellazione definitiva. */
function gs_voce_commento_elimina( $id, $comment_id ) {
	$id       = (int) $id;
	$commenti = gs_voce_commenti_get( $id );
	$trovato  = null;
	$restanti = array();
	foreach ( $commenti as $c ) {
		if ( $c['id'] === $comment_id ) { $trovato = $c; } else { $restanti[] = $c; }
	}
	if ( ! $trovato ) { return false; }
	update_post_meta( $id, 'gs_voce_commenti', $restanti );
	$cestino   = gs_voce_commenti_cestino_get( $id );
	$cestino[] = $trovato;
	if ( count( $cestino ) > 100 ) { $cestino = array_slice( $cestino, -100 ); }
	update_post_meta( $id, 'gs_voce_commenti_cestino', $cestino );
	return true;
}

function gs_voce_commento_ripristina( $id, $comment_id ) {
	$id      = (int) $id;
	$cestino = gs_voce_commenti_cestino_get( $id );
	$trovato = null;
	$resto   = array();
	foreach ( $cestino as $c ) {
		if ( $c['id'] === $comment_id ) { $trovato = $c; } else { $resto[] = $c; }
	}
	if ( ! $trovato ) { return false; }
	update_post_meta( $id, 'gs_voce_commenti_cestino', $resto );
	$commenti   = gs_voce_commenti_get( $id );
	$commenti[] = $trovato;
	update_post_meta( $id, 'gs_voce_commenti', $commenti );
	return true;
}

function gs_voce_commenti_html( $id ) {
	$id        = (int) $id;
	$commenti  = gs_voce_commenti_get( $id );
	$modera    = function_exists( 'gs_can_manage' ) && gs_can_manage();
	$out       = '<div class="gs-voce-chat">';
	if ( $commenti ) {
		$out .= '<div class="gs-voce-chat-lista">';
		foreach ( $commenti as $c ) {
			$au      = get_userdata( $c['autore'] );
			$classe  = ! empty( $c['segnalato'] ) ? ' gs-voce-chat-msg-segnalato' : '';
			$out    .= '<div class="gs-voce-chat-msg' . $classe . '">';
			if ( ! empty( $c['segnalato'] ) ) { $out .= '<span class="gs-voce-chat-bollino" title="Contiene una parola segnalata">⚠️</span> '; }
			$out .= '<strong>' . esc_html( $au ? $au->display_name : '—' ) . ':</strong> ' . nl2br( esc_html( $c['testo'] ) );
			if ( $modera ) {
				$out .= ' <button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-voce-commento-elimina" data-voce="' . $id . '" data-commento="' . esc_attr( $c['id'] ) . '">🗑️</button>';
			}
			$out .= '</div>';
		}
		$out .= '</div>';
	}
	if ( is_user_logged_in() ) {
		$out .= '<form class="gs-form gs-form-voce-commento" onsubmit="return false" data-voce="' . $id . '">';
		if ( function_exists( 'gs_antispam_fields' ) ) { ob_start(); gs_antispam_fields(); $out .= ob_get_clean(); }
		$out .= '<p><textarea name="testo" rows="2" style="width:100%" placeholder="Scrivi un commento (solo testo, niente audio)…"></textarea></p>';
		$out .= '<p><button class="gs-btn gs-btn-sm gs-voce-commento-invia">💬 Invia commento</button> <span class="gs-voce-commento-msg gs-richiesta-esito"></span></p>';
		$out .= '</form>';
	}
	if ( $modera ) {
		$cestino = gs_voce_commenti_cestino_get( $id );
		$out .= '<details class="gs-todo-cestino"><summary>🗑️ Commenti eliminati (' . count( $cestino ) . ')</summary>';
		if ( ! $cestino ) {
			$out .= '<p class="gs-hint">Il cestino è vuoto.</p>';
		} else {
			foreach ( array_reverse( $cestino ) as $c ) {
				$au   = get_userdata( $c['autore'] );
				$out .= '<div class="gs-voce-chat-msg"><strong>' . esc_html( $au ? $au->display_name : '—' ) . ':</strong> ' . nl2br( esc_html( $c['testo'] ) )
					. ' <button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-voce-commento-ripristina" data-voce="' . $id . '" data-commento="' . esc_attr( $c['id'] ) . '">↺ Ripristina</button></div>';
			}
		}
		$out .= '</details>';
	}
	$out .= '</div>';
	return $out;
}

function gs_voci_in_attesa() {
	return gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_voce',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'orderby'        => 'date',
		'order'          => 'ASC',
		'meta_key'       => 'gs_stato',
		'meta_value'     => 'in_attesa',
		'suppress_filters' => true,
	) ), 'gs_voce' );
}

// -----------------------------------------------------------------------------
// [gs_matterello_parlante] — lato sfoglina: archivio pubblico + invio
// -----------------------------------------------------------------------------
add_shortcode( 'gs_matterello_parlante', 'gs_sc_matterello_parlante' );
function gs_sc_matterello_parlante() {
	if ( $g = gs_gate_riservato() ) { return $g; }
	if ( $g = gs_blackout_gate() ) { return $g; }

	$out = gs_box_open( '🎙️ Il Matterello Parlante' );
	$out .= gs_sezione_aiuto( 'Un archivio di ricordi e consigli registrati a voce, non scritti: la voce di chi li racconta fa parte del ricordo. Registra direttamente dal browser (se supportato) oppure carica un file audio già pronto: puoi sempre riascoltare quello scelto e rimuoverlo per rifarlo, prima di inviarlo. Le registrazioni vengono ascoltate dai maestri prima di comparire nell\'archivio pubblico. Sotto ogni registrazione pubblicata si può chattare a testo per commentarla — non è possibile aggiungere altro audio nei commenti, solo nella registrazione originale. La registrazione diretta funziona sui browser più comuni (Chrome, Edge, Opera, Firefox recenti); se il tuo browser non la supporta, usa comunque il caricamento file.' );
	$out .= '<p class="gs-hint">Voci vere, non testi: ricordi e consigli raccontati a voce dalle sfogline dell\'Accademia.</p>';

	$approvate = gs_voci_approvate();
	if ( ! $approvate ) {
		$out .= '<p>Ancora nessuna registrazione pubblicata. Sii la prima!</p>';
	} else {
		$out .= '<div class="gs-inbox-lista gs-paginate" data-per-page="8">';
		foreach ( $approvate as $v ) {
			$c  = gs_voce_get( $v->ID );
			$au = get_userdata( $c['autore'] );
			$out .= '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">' . esc_html( $c['titolo'] ) . ' <span class="gs-msg-data">di ' . esc_html( $au ? $au->display_name : '—' ) . '</span></summary>';
			$out .= '<div class="gs-inbox-corpo">';
			$out .= gs_msg_media_html( $c['audio_url'], 'audio' );
			if ( $c['trascrizione'] ) { $out .= '<p class="gs-hint">' . nl2br( esc_html( $c['trascrizione'] ) ) . '</p>'; }
			$out .= gs_voce_commenti_html( $c['id'] );
			$out .= '</div></details>';
		}
		$out .= '</div>';
	}
	$out .= gs_box_close();

	// Invio di una nuova registrazione.
	$out .= gs_box_open( '🎤 Registra un ricordo o un consiglio' );
	$out .= '<div class="gs-todo-riquadro">';
	$out .= '<form class="gs-form gs-form-voce-nuova" onsubmit="return false">';
	$out .= '<p><label>Titolo<br><input type="text" name="titolo" autocomplete="off" style="width:100%" required></label></p>';
	$out .= '<p><button class="gs-btn gs-btn-sm gs-voce-registra" type="button">🎙️ Registra dal browser</button> <span class="gs-voce-registra-msg gs-richiesta-esito"></span></p>';
	$out .= '<div class="gs-voce-anteprima" style="display:none;margin-bottom:8px"></div>';
	$out .= '<p><label>Oppure carica un file audio già registrato<br><input type="file" class="gs-voce-file" accept="audio/*"></label></p>';
	$out .= '<p><label>Trascrizione — facoltativa<br><textarea name="trascrizione" rows="3" style="width:100%" placeholder="Se vuoi, scrivi anche il testo di ciò che dici"></textarea></label></p>';
	$out .= '<p><button class="gs-btn gs-btn-sm gs-voce-invia">Invia per approvazione</button> <span class="gs-voce-msg gs-richiesta-esito"></span></p>';
	$out .= '</form>';

	$mie = gs_voci_utente( get_current_user_id() );
	if ( $mie ) {
		$stati_it = array( 'in_attesa' => 'In attesa di approvazione', 'approvata' => 'Approvata, visibile a tutte', 'rifiutata' => 'Non approvata' );
		$out .= '<h4 style="margin-top:14px">Le tue registrazioni inviate</h4>';
		$out .= '<div class="gs-inbox-lista gs-paginate" data-per-page="5">';
		foreach ( $mie as $v ) {
			$c = gs_voce_get( $v->ID );
			$out .= '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">' . esc_html( $c['titolo'] )
				. ' <span class="gs-msg-data">' . esc_html( $stati_it[ $c['stato'] ] ?? $c['stato'] ) . '</span></summary>';
			$out .= '<div class="gs-inbox-corpo">' . gs_msg_media_html( $c['audio_url'], 'audio' ) . '</div></details>';
		}
		$out .= '</div>';
	}
	$out .= '</div>';
	$out .= gs_box_close();

	return $out;
}

// -----------------------------------------------------------------------------
// AJAX lato sfoglina
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_voce_invia', 'gs_ajax_voce_invia' );
function gs_ajax_voce_invia() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }

	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	if ( '' === trim( $titolo ) ) { wp_send_json_error( array( 'message' => 'Scrivi il titolo del ricordo.' ) ); }

	if ( empty( $_FILES['media']['name'] ) ) { wp_send_json_error( array( 'message' => 'Registra o carica un file audio.' ) ); }
	$media = gs_msg_upload( 'media' );
	if ( is_wp_error( $media ) ) { wp_send_json_error( array( 'message' => $media->get_error_message() ) ); }
	if ( ! is_array( $media ) || 'audio' !== $media['type'] ) { wp_send_json_error( array( 'message' => 'Il file inviato non è un audio.' ) ); }

	$trascrizione = isset( $_POST['trascrizione'] ) ? sanitize_textarea_field( wp_unslash( $_POST['trascrizione'] ) ) : '';
	if ( function_exists( 'gs_msg_clean' ) ) { $trascrizione = gs_msg_clean( $trascrizione ); }

	$vid = wp_insert_post( array(
		'post_type'   => 'gs_voce',
		'post_status' => 'publish',
		'post_title'  => $titolo,
		'post_author' => $uid,
	) );
	if ( is_wp_error( $vid ) || ! $vid ) { wp_send_json_error( array( 'message' => 'Errore nell\'invio.' ) ); }

	update_post_meta( $vid, 'gs_audio_url', $media['url'] );
	update_post_meta( $vid, 'gs_trascrizione', $trascrizione );
	update_post_meta( $vid, 'gs_stato', 'in_attesa' );

	if ( function_exists( 'gs_inbox_crea' ) ) {
		$u = get_userdata( $uid );
		gs_inbox_crea( 'Registrazione da approvare: ' . $titolo, ( $u ? $u->display_name : '' ) . ' ha inviato una registrazione per il Matterello Parlante. Va approvata per comparire nell\'archivio pubblico.', array( 'from' => $u ? $u->display_name : 'Sfoglina', 'link_ancora' => 'admin.php?page=gs-generale#gs-zona-matterello' ) );
	}

	wp_send_json_success( array( 'message' => 'Registrazione inviata per approvazione.' ) );
}

add_action( 'wp_ajax_gs_voce_commento_invia', 'gs_ajax_voce_commento_invia' );
function gs_ajax_voce_commento_invia() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }
	$vid = isset( $_POST['voce'] ) ? (int) $_POST['voce'] : 0;
	if ( 'gs_voce' !== get_post_type( $vid ) ) { wp_send_json_error( array( 'message' => 'Registrazione non valida.' ) ); }
	if ( 'approvata' !== get_post_meta( $vid, 'gs_stato', true ) ) { wp_send_json_error( array( 'message' => 'Questa registrazione non è (più) nell\'archivio pubblico.' ) ); }

	$testo = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';
	if ( function_exists( 'gs_msg_clean' ) ) { $testo = gs_msg_clean( $testo ); }
	if ( '' === trim( $testo ) ) { wp_send_json_error( array( 'message' => 'Scrivi qualcosa prima di inviare.' ) ); }
	if ( function_exists( 'gs_antispam_check' ) ) {
		$check = gs_antispam_check( $_POST, 'voce_commento' );
		if ( is_wp_error( $check ) ) { wp_send_json_error( array( 'message' => $check->get_error_message() ) ); }
	}

	gs_voce_commento_aggiungi( $vid, $uid, $testo );

	$autore = (int) get_post_field( 'post_author', $vid );
	if ( $autore && $autore !== $uid && function_exists( 'gs_mail_progetto' ) ) {
		$u = get_userdata( $uid );
		gs_mail_progetto( $autore, 'messaggi', 'Un commento sulla tua registrazione — Accademia della Sfoglia',
			"Ciao,\n\n" . ( $u ? $u->display_name : 'Qualcuno' ) . ' ha commentato la tua registrazione "' . get_the_title( $vid ) . "\" nel Matterello Parlante:\n\n" . $testo );
	}
	wp_send_json_success( array( 'message' => 'Commento inviato.' ) );
}

add_action( 'wp_ajax_gs_voce_commento_elimina', 'gs_ajax_voce_commento_elimina' );
function gs_ajax_voce_commento_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$vid = isset( $_POST['voce'] ) ? (int) $_POST['voce'] : 0;
	$cid = isset( $_POST['commento'] ) ? sanitize_text_field( wp_unslash( $_POST['commento'] ) ) : '';
	if ( 'gs_voce' !== get_post_type( $vid ) || ! gs_voce_commento_elimina( $vid, $cid ) ) {
		wp_send_json_error( array( 'message' => 'Commento non trovato.' ) );
	}
	wp_send_json_success( array( 'message' => 'Commento spostato nel cestino.' ) );
}

add_action( 'wp_ajax_gs_voce_commento_ripristina', 'gs_ajax_voce_commento_ripristina' );
function gs_ajax_voce_commento_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$vid = isset( $_POST['voce'] ) ? (int) $_POST['voce'] : 0;
	$cid = isset( $_POST['commento'] ) ? sanitize_text_field( wp_unslash( $_POST['commento'] ) ) : '';
	if ( 'gs_voce' !== get_post_type( $vid ) || ! gs_voce_commento_ripristina( $vid, $cid ) ) {
		wp_send_json_error( array( 'message' => 'Commento non trovato nel cestino.' ) );
	}
	wp_send_json_success( array( 'message' => 'Commento ripristinato.' ) );
}

// -----------------------------------------------------------------------------
// PANNELLO — approvazione registrazioni (titolare)
// -----------------------------------------------------------------------------
function gs_pannello_matterello_parlante() {
	if ( ! gs_can_manage() ) { return; }

	echo gs_box_open( 'Il Matterello Parlante (approvazione)', '', 'gs-box-matterello' );
	echo '<p class="gs-hint">Le registrazioni compaiono nell\'archivio pubblico solo dopo l\'approvazione. Ascoltale prima di decidere. Sotto ogni registrazione approvata trovi anche la chat testuale: i commenti con ⚠️ contengono una delle parole segnalate in Impostazioni > Moderazione — puoi eliminarli da lì (recuperabili dal cestino della chat).</p>';

	$attesa = gs_voci_in_attesa();
	echo '<h4>In attesa di approvazione</h4>';
	if ( ! $attesa ) {
		echo '<p class="gs-hint">Nessuna registrazione in attesa.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-paginate" data-per-page="5">';
		foreach ( $attesa as $v ) {
			$c  = gs_voce_get( $v->ID );
			$au = get_userdata( $c['autore'] );
			echo '<details class="gs-inbox-item gs-non-letto gs-attesa-forte" data-voce="' . (int) $v->ID . '">';
			echo '<summary class="gs-inbox-oggetto"><span class="gs-dot"></span> ' . esc_html( $c['titolo'] ) . ' <span class="gs-msg-data">di ' . esc_html( $au ? $au->display_name : '—' ) . '</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			echo gs_msg_media_html( $c['audio_url'], 'audio' );
			if ( $c['trascrizione'] ) { echo '<p>' . nl2br( esc_html( $c['trascrizione'] ) ) . '</p>'; }
			echo '<p><button class="gs-btn gs-btn-sm gs-matterello-modera" data-voce="' . (int) $v->ID . '" data-esito="approvata">Approva</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-matterello-modera" data-voce="' . (int) $v->ID . '" data-esito="rifiutata">Rifiuta</button> ';
			echo '<span class="gs-matterello-mod-msg gs-richiesta-esito"></span></p>';
			echo '</div></details>';
		}
		echo '</div>';
	}

	$approvate = gs_voci_approvate();
	echo '<h4 style="margin-top:14px">Approvate (visibili nell\'archivio)</h4>';
	if ( ! $approvate ) {
		echo '<p class="gs-hint">Nessuna registrazione approvata.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="5">';
		foreach ( $approvate as $v ) {
			$c  = gs_voce_get( $v->ID );
			$au = get_userdata( $c['autore'] );
			echo '<details class="gs-inbox-item" data-voce="' . (int) $v->ID . '">';
			echo '<summary class="gs-inbox-oggetto">' . esc_html( $c['titolo'] ) . ' <span class="gs-msg-data">di ' . esc_html( $au ? $au->display_name : '—' ) . '</span></summary>';
			echo '<div class="gs-inbox-corpo">' . gs_msg_media_html( $c['audio_url'], 'audio' );
			echo '<p><button class="gs-btn gs-btn-sm gs-btn-ghost gs-matterello-modera" data-voce="' . (int) $v->ID . '" data-esito="rifiutata">Togli dall\'archivio</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-matterello-elimina" data-voce="' . (int) $v->ID . '">Elimina</button> ';
			echo '<span class="gs-matterello-mod-msg gs-richiesta-esito"></span></p>';
			echo gs_voce_commenti_html( $v->ID );
			echo '</div></details>';
		}
		echo '</div>';
	}

	$trash = get_posts( array( 'post_type' => 'gs_voce', 'post_status' => 'trash', 'posts_per_page' => 50, 'suppress_filters' => true ) );
	echo '<div class="gs-sezione-cestino">';
	echo '<h4 class="gs-titolo-cestino">🗑️ Cestino</h4>';
	if ( ! $trash ) {
		echo '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		echo '<table class="gs-table gs-paginate" data-per-page="8"><thead><tr>' . gs_cestino_th_checkbox() . '<th>Registrazione</th><th>Sfoglina</th><th></th></tr></thead><tbody>';
		foreach ( $trash as $v ) {
			$au = get_userdata( $v->post_author );
			echo '<tr data-voce="' . (int) $v->ID . '">' . gs_cestino_td_checkbox( $v->ID ) . '<td>' . esc_html( get_the_title( $v ) ) . '</td><td>' . esc_html( $au ? $au->display_name : '—' ) . '</td>';
			echo '<td><button class="gs-btn gs-btn-sm gs-btn-verde gs-matterello-ripristina" data-voce="' . (int) $v->ID . '">Ripristina</button> <span class="gs-matterello-trow-msg gs-richiesta-esito"></span></td></tr>';
		}
		echo '</tbody></table>';
		echo gs_cestino_azioni_bulk( 'gs_voce' );
	}
	echo '</div>';

	echo gs_box_close();
}

add_action( 'wp_ajax_gs_matterello_modera', 'gs_ajax_matterello_modera' );
function gs_ajax_matterello_modera() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$vid   = isset( $_POST['voce'] ) ? (int) $_POST['voce'] : 0;
	$esito = isset( $_POST['esito'] ) ? sanitize_key( wp_unslash( $_POST['esito'] ) ) : 'rifiutata';
	if ( 'gs_voce' !== get_post_type( $vid ) ) { wp_send_json_error( array( 'message' => 'Registrazione non valida.' ) ); }

	update_post_meta( $vid, 'gs_stato', 'approvata' === $esito ? 'approvata' : 'rifiutata' );
	$msg = 'approvata' === $esito ? 'Registrazione approvata: ora è nell\'archivio pubblico.' : 'Registrazione non approvata (tolta dall\'archivio pubblico).';

	$au = get_userdata( get_post_field( 'post_author', $vid ) );
	if ( $au && function_exists( 'gs_mail_progetto' ) ) {
		$corpo = 'approvata' === $esito
			? "Ciao " . $au->display_name . ",\n\nla tua registrazione \"" . get_the_title( $vid ) . "\" è stata approvata ed è ora nell'archivio del Matterello Parlante."
			: "Ciao " . $au->display_name . ",\n\nla tua registrazione \"" . get_the_title( $vid ) . "\" non è (più) nell'archivio pubblico.";
		gs_mail_progetto( $au->ID, 'messaggi', 'La tua registrazione — Accademia della Sfoglia', $corpo );
	}
	wp_send_json_success( array( 'message' => $msg ) );
}

add_action( 'wp_ajax_gs_matterello_elimina', 'gs_ajax_matterello_elimina' );
function gs_ajax_matterello_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$vid = isset( $_POST['voce'] ) ? (int) $_POST['voce'] : 0;
	if ( 'gs_voce' !== get_post_type( $vid ) ) { wp_send_json_error( array( 'message' => 'Registrazione non valida.' ) ); }
	wp_trash_post( $vid );
	wp_send_json_success( array( 'message' => 'Spostata nel cestino.' ) );
}

add_action( 'wp_ajax_gs_matterello_ripristina', 'gs_ajax_matterello_ripristina' );
function gs_ajax_matterello_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$vid = isset( $_POST['voce'] ) ? (int) $_POST['voce'] : 0;
	if ( 'gs_voce' !== get_post_type( $vid ) ) { wp_send_json_error( array( 'message' => 'Registrazione non valida.' ) ); }
	wp_untrash_post( $vid );
	update_post_meta( $vid, 'gs_stato', 'in_attesa' );
	wp_send_json_success( array( 'message' => 'Registrazione ripristinata (torna in attesa di approvazione).' ) );
}
