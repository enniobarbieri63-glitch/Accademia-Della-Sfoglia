<?php
/**
 * inbox.php — Posta interna del progetto (amministratore + collaboratori).
 *
 * Raccoglie messaggi/avvisi interni (nuove prenotazioni, messaggi dei clienti,
 * blocchi corso, iscrizioni, richieste di aiuto…). Nel pannello si vede la lista
 * dei soli OGGETTI; cliccando l'oggetto si apre il messaggio, con possibilità di
 * rispondere, modificare o eliminare. Il cestino contiene SOLO i messaggi
 * eliminati dal progetto (CPT gs_msg_interno) e sono sempre ripristinabili.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_inbox_cpt' );
function gs_inbox_cpt() {
	register_post_type( 'gs_msg_interno', array(
		'labels'       => array( 'name' => 'Posta interna' ),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'title', 'editor', 'author' ),
	) );
}

/**
 * Crea un messaggio nella posta interna dello staff.
 * @param string $oggetto  Titolo/oggetto (è ciò che appare nella lista).
 * @param string $corpo    Testo del messaggio.
 * @param array  $meta     Extra: 'from' (nome mittente), 'link_pren' (id prenotazione), 'media',
 *                         'link_ancora' (URL/ancora del pannello di approvazione collegato,
 *                         es. "admin.php?page=gs-generale#gs-zona-biografie" — richiesto da
 *                         Ennio il 17/08/2026, così i messaggi "da approvare" hanno un
 *                         pulsante diretto invece di dover cercare la sezione a mano).
 */
function gs_inbox_crea( $oggetto, $corpo, $meta = array() ) {
	$id = wp_insert_post( array(
		'post_type'    => 'gs_msg_interno',
		'post_status'  => 'publish',
		'post_title'   => $oggetto ? $oggetto : 'Messaggio',
		'post_content' => $corpo,
		'post_author'  => isset( $meta['author'] ) ? (int) $meta['author'] : get_current_user_id(),
	) );
	if ( is_wp_error( $id ) || ! $id ) { return 0; }
	update_post_meta( $id, 'gs_from', isset( $meta['from'] ) ? $meta['from'] : 'Sistema' );
	update_post_meta( $id, 'gs_letto', 0 );
	if ( ! empty( $meta['link_pren'] ) ) { update_post_meta( $id, 'gs_link_pren', (int) $meta['link_pren'] ); }
	if ( ! empty( $meta['link_ancora'] ) ) { update_post_meta( $id, 'gs_link_ancora', (string) $meta['link_ancora'] ); }
	if ( ! empty( $meta['media'] ) && is_array( $meta['media'] ) ) {
		update_post_meta( $id, 'gs_media', $meta['media']['url'] );
		update_post_meta( $id, 'gs_media_type', $meta['media']['type'] );
	}
	return $id;
}

/** Numero di messaggi interni non letti. */
function gs_inbox_non_letti() {
	$n = 0;
	foreach ( gs_inbox_messaggi() as $m ) {
		if ( ! (int) get_post_meta( $m->ID, 'gs_letto', true ) ) { $n++; }
	}
	return $n;
}

function gs_inbox_messaggi( $stato = 'publish' ) {
	// posts_per_page a -1 (non un tetto a 100, trovato il 14/08/2026 durante
	// i test): questa stessa lista alimenta sia il conteggio dei non letti
	// sia l'elenco del pannello, con la sua paginazione lato JavaScript —
	// un tetto qui avrebbe reso invisibili e non più contati i messaggi più
	// vecchi non appena la Posta interna avesse superato i 100 messaggi in
	// tutta la sua storia (probabile prima o poi, tra notifiche di
	// iscrizione, prenotazioni e moderazioni).
	return get_posts( array(
		'post_type'      => 'gs_msg_interno',
		'post_status'    => $stato,
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'suppress_filters' => true,
	) );
}

// -----------------------------------------------------------------------------
// PANNELLO — posta interna
// -----------------------------------------------------------------------------
function gs_pannello_inbox() {
	if ( ! gs_can_manage() ) { return; }
	$msgs  = gs_inbox_messaggi();
	$trash = gs_inbox_messaggi( 'trash' );

	echo gs_box_open( 'Posta interna (amministratore e collaboratori)', 'gs-box-msg' );
	echo '<p class="gs-hint">Qui arrivano gli avvisi e i messaggi interni del progetto. La lista mostra solo l\'oggetto: clicca sull\'oggetto per aprire il messaggio, poi puoi rispondere, modificarlo o eliminarlo. Gli eliminati finiscono nel cestino qui sotto e si possono sempre ripristinare.</p>';
	// Riparazione una tantum dei messaggi "Nuova registrazione" salvati prima
	// della correzione del 12/08/2026, che mostravano "nn"/"n" al posto degli
	// a capo (bug nel codice, non un problema di questo messaggio soltanto).
	echo '<p><button type="button" class="gs-btn gs-btn-sm gs-inbox-ripara-registrazioni">Ripara i vecchi messaggi "Nuova registrazione"</button> <span class="gs-inbox-ripara-esito gs-richiesta-esito"></span></p>';

	if ( ! $msgs ) {
		echo '<p class="gs-hint">Nessun messaggio.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="10">';
		foreach ( $msgs as $m ) {
			$letto = (int) get_post_meta( $m->ID, 'gs_letto', true );
			$from  = get_post_meta( $m->ID, 'gs_from', true );
			$thread = get_post_meta( $m->ID, 'gs_thread', true );
			// Non letto: lampeggia in rosso finché non si clicca "LETTO" — aprire
			// il messaggio per leggerlo NON lo segna più come letto da solo
			// (richiesto da Ennio, 13/08/2026: "anche se viene aperto deve
			// continuare a lampeggiare sino a quando non clicco sul pulsante").
			echo '<details class="gs-inbox-item gs-inbox-posta' . ( $letto ? '' : ' gs-non-letto gs-lampeggia-rosso' ) . '" data-id="' . (int) $m->ID . '">';
			echo '<summary class="gs-inbox-oggetto">' . ( $letto ? '' : '<span class="gs-dot"></span> ' ) . esc_html( get_the_title( $m ) )
				. ' <span class="gs-msg-data">' . esc_html( get_the_time( 'j/m/Y H:i', $m ) ) . '</span>'
				. ( $letto ? '' : ' <button type="button" class="gs-btn gs-btn-sm gs-inbox-segna-letto" title="Clicca per segnare come letto e fermare il lampeggio">✓ Segna come letto</button>' )
				. '<span class="gs-inbox-letto-ok" style="display:none">✓ Letto</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			echo '<p class="gs-hint">Da: ' . esc_html( $from ) . '</p>';
			echo '<div class="gs-inbox-testo">' . nl2br( esc_html( $m->post_content ) ) . '</div>';
			$murl = get_post_meta( $m->ID, 'gs_media', true );
			if ( $murl && function_exists( 'gs_msg_media_html' ) ) { echo gs_msg_media_html( $murl, get_post_meta( $m->ID, 'gs_media_type', true ) ?: 'image' ); }
			$lp = (int) get_post_meta( $m->ID, 'gs_link_pren', true );
			if ( $lp ) { echo '<p class="gs-hint">↳ Riferito a una prenotazione: per rispondere direttamente al cliente usa la scheda del corso nel Calendario.</p>'; }
			$ancora = get_post_meta( $m->ID, 'gs_link_ancora', true );
			if ( $ancora ) {
				echo '<p><a class="gs-btn gs-btn-sm gs-btn-verde" href="' . esc_url( admin_url( $ancora ) ) . '">→ Vai all\'approvazione</a></p>';
			}

			// Thread delle risposte interne — stessa bolla whatsapp delle
			// Conversazioni (.gs-conv-msg): a destra le proprie risposte, a
			// sinistra quelle degli altri collaboratori — richiesto da
			// Ennio il 2026-07-30.
			if ( is_array( $thread ) && $thread ) {
				echo '<div class="gs-conv-thread">';
				foreach ( $thread as $t ) {
					$mine = ( (int) ( $t['uid'] ?? 0 ) === get_current_user_id() );
					echo '<div class="gs-conv-msg' . ( $mine ? ' mine' : '' ) . '"><span class="gs-conv-from">' . esc_html( $t['nome'] ) . '</span> '
						. '<span class="gs-msg-data">' . esc_html( date_i18n( 'j/m H:i', (int) $t['time'] ) ) . '</span>'
						. '<div>' . nl2br( esc_html( $t['testo'] ) ) . '</div></div>';
				}
				echo '</div>';
			}

			// Azioni: rispondi (in fondo), modifica, elimina.
			echo '<form class="gs-form gs-form-inbox-risp" data-id="' . (int) $m->ID . '" onsubmit="return false">';
			echo '<textarea name="testo" rows="2" style="width:100%" placeholder="Scrivi una risposta interna…"></textarea>';
			echo '<p><button class="gs-btn gs-btn-sm gs-inbox-rispondi">Rispondi</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-inbox-modifica">Modifica messaggio</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-inbox-elimina">Elimina</button> ';
			echo '<span class="gs-inbox-msg gs-richiesta-esito"></span></p></form>';
			echo '</div></details>';
		}
		echo '</div>';
	}

	// Cestino (solo messaggi del progetto).
	echo '<div class="gs-sezione-cestino">';
	echo '<h4 class="gs-titolo-cestino">🗑️ Cestino posta interna</h4>';
	if ( ! $trash ) {
		echo '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		echo '<table class="gs-table gs-paginate" data-per-page="10"><thead><tr>' . gs_cestino_th_checkbox() . '<th>Oggetto</th><th>Data</th><th></th></tr></thead><tbody>';
		foreach ( $trash as $m ) {
			echo '<tr data-id="' . (int) $m->ID . '">' . gs_cestino_td_checkbox( $m->ID ) . '<td>' . esc_html( get_the_title( $m ) ) . '</td><td>' . esc_html( get_the_time( 'j/m/Y', $m ) ) . '</td>';
			echo '<td><button class="gs-btn gs-btn-sm gs-btn-verde gs-inbox-ripristina" data-id="' . (int) $m->ID . '">Ripristina</button> <span class="gs-inbox-tmsg gs-richiesta-esito"></span></td></tr>';
		}
		echo '</tbody></table>';
		echo gs_cestino_azioni_bulk( 'gs_msg_interno' );
	}
	echo '</div>';
	echo gs_box_close();
}

// -----------------------------------------------------------------------------
// AJAX
// -----------------------------------------------------------------------------
function gs_inbox_guard() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
}

add_action( 'wp_ajax_gs_inbox_letto', 'gs_ajax_inbox_letto' );
function gs_ajax_inbox_letto() {
	gs_inbox_guard();
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( 'gs_msg_interno' === get_post_type( $id ) ) { update_post_meta( $id, 'gs_letto', 1 ); }
	wp_send_json_success( array( 'message' => 'ok' ) );
}

add_action( 'wp_ajax_gs_inbox_rispondi', 'gs_ajax_inbox_rispondi' );
function gs_ajax_inbox_rispondi() {
	gs_inbox_guard();
	$id    = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$testo = isset( $_POST['testo'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) ) : '';
	if ( 'gs_msg_interno' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Messaggio non valido.' ) ); }
	if ( '' === $testo ) { wp_send_json_error( array( 'message' => 'Scrivi una risposta.' ) ); }
	$thread = get_post_meta( $id, 'gs_thread', true );
	if ( ! is_array( $thread ) ) { $thread = array(); }
	$u = wp_get_current_user();
	$thread[] = array( 'uid' => get_current_user_id(), 'nome' => $u ? $u->display_name : 'Staff', 'testo' => $testo, 'time' => current_time( 'timestamp' ) );
	update_post_meta( $id, 'gs_thread', $thread );
	wp_send_json_success( array( 'message' => 'Risposta aggiunta.' ) );
}

add_action( 'wp_ajax_gs_inbox_modifica', 'gs_ajax_inbox_modifica' );
function gs_ajax_inbox_modifica() {
	gs_inbox_guard();
	$id      = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$oggetto = isset( $_POST['oggetto'] ) ? sanitize_text_field( wp_unslash( $_POST['oggetto'] ) ) : '';
	$corpo   = isset( $_POST['corpo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['corpo'] ) ) : '';
	if ( 'gs_msg_interno' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Messaggio non valido.' ) ); }
	wp_update_post( array( 'ID' => $id, 'post_title' => $oggetto ? $oggetto : get_the_title( $id ), 'post_content' => $corpo ) );
	wp_send_json_success( array( 'message' => 'Messaggio modificato.' ) );
}

add_action( 'wp_ajax_gs_inbox_elimina', 'gs_ajax_inbox_elimina' );
function gs_ajax_inbox_elimina() {
	gs_inbox_guard();
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( 'gs_msg_interno' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Messaggio non valido.' ) ); }
	wp_trash_post( $id ); // reversibile: resta nel cestino del progetto
	wp_send_json_success( array( 'message' => 'Spostato nel cestino.' ) );
}

add_action( 'wp_ajax_gs_inbox_ripristina', 'gs_ajax_inbox_ripristina' );
function gs_ajax_inbox_ripristina() {
	gs_inbox_guard();
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( 'gs_msg_interno' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Messaggio non valido.' ) ); }
	wp_untrash_post( $id );
	update_post_meta( $id, 'gs_letto', 0 );
	wp_send_json_success( array( 'message' => 'Messaggio ripristinato: torna tra quelli da leggere.' ) );
}

// Riparazione una tantum: i messaggi "Nuova registrazione" salvati prima del
// 12/08/2026 avevano gli apici sbagliati nel codice e finivano con "nn"/"n"
// al posto degli a capo. Qui si riconoscono dal testo esatto rimasto e si
// ricostruiscono con gli a capo veri, senza toccare nient'altro.
add_action( 'wp_ajax_gs_inbox_ripara_registrazioni', 'gs_ajax_inbox_ripara_registrazioni' );
function gs_ajax_inbox_ripara_registrazioni() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$posts = get_posts( array(
		'post_type'      => 'gs_msg_interno',
		'posts_per_page' => -1,
		'post_status'    => array( 'publish', 'trash' ),
	) );

	$corretti = 0;
	foreach ( $posts as $p ) {
		if ( ! preg_match( '/^Si è registrata una nuova persona\.nnNome: (.+?)nEmail: (.+)$/su', $p->post_content, $m ) ) {
			continue;
		}
		$nome  = trim( $m[1] );
		$email = trim( $m[2] );
		wp_update_post( array(
			'ID'           => $p->ID,
			'post_content' => "Si è registrata una nuova persona.\n\nNome: {$nome}\nEmail: {$email}",
		) );
		$corretti++;
	}

	wp_send_json_success( array(
		'message' => $corretti
			? 'Corretti ' . $corretti . ' messaggi: ora gli a capo sono veri.'
			: 'Nessun messaggio da correggere: erano già tutti a posto.',
	) );
}

// Notifica interna alla registrazione di una nuova sfoglina.
add_action( 'gs_after_registration', 'gs_inbox_nuova_registrazione' );
function gs_inbox_nuova_registrazione( $user_id ) {
	$u = get_userdata( $user_id );
	if ( $u ) {
		gs_inbox_crea( 'Nuova registrazione: ' . $u->display_name, "Si è registrata una nuova persona.\n\nNome: " . $u->display_name . "\nEmail: " . $u->user_email, array( 'from' => 'Sistema' ) );
	}
}
