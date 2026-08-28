<?php
/**
 * letture.php — "Le Letture dei Grandi Protagonisti della Cucina".
 *
 * Sezione PUBBLICA (visibile anche senza accesso, in chiaro nel menu del
 * sito — vedi gs_menu_pagine_elenco() in control-panel.php): giornalisti e
 * collaboratori pubblicano una lettura, chiunque può leggerla. Sotto ogni
 * lettura c'è una chat pubblica in bolle stile whatsapp (.gs-conv-msg,
 * stesso stile di Conversazioni), aperta a chi è già sfoglina E a chi si è
 * iscritto solo per commentare (vedi più sotto) — mai a chi non ha un
 * account.
 *
 * CPT gs_lettura (privato come tutti i CPT del plugin, la visibilità la
 * decide lo shortcode, non WordPress):
 *  - post_title/post_content = titolo e testo della lettura
 *  - post_author = chi l'ha scritta
 *  - meta gs_commenti          = [ {id, uid, nome, testo, time} ]
 *  - meta gs_commenti_cestino  = commenti rimossi da un gestore, recuperabili
 *
 * --- Account "lettore" (chi si iscrive solo per commentare) ---
 * Iscrizione leggera e immediata (niente quota associativa, niente
 * approvazione): meta gs_status = 'lettore' invece di 'in_attesa'/
 * 'approvata'. Siccome gs_is_approved() considera "approvata" SOLO lo stato
 * vuoto o 'approvata', un account 'lettore' resta escluso automaticamente
 * da tutto il resto del gaming (sfide, corsi, ricettario…) senza dover
 * toccare gli altri moduli: gli si concede esplicitamente solo il permesso
 * di commentare qui.
 * Moderazione: gs_lettore_bloccato (bool) + gs_lettore_bloccato_fino
 * (data facoltativa: vuota = a tempo indeterminato, valorizzata = sospensione
 * che scade da sola a quella data, senza bisogno di un cron).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_lettura_register_cpt' );
function gs_lettura_register_cpt() {
	register_post_type( 'gs_lettura', array(
		'labels'       => array( 'name' => 'Le Letture dei Grandi Protagonisti della Cucina' ),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'title', 'editor', 'author' ),
	) );
}

// -----------------------------------------------------------------------------
// Helper — letture
// -----------------------------------------------------------------------------
function gs_letture_elenco() {
	return get_posts( array(
		'post_type'      => 'gs_lettura',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
}
function gs_lettura_commenti( $id ) {
	$c = get_post_meta( $id, 'gs_commenti', true );
	return is_array( $c ) ? $c : array();
}
function gs_lettura_commenti_cestino( $id ) {
	$c = get_post_meta( $id, 'gs_commenti_cestino', true );
	return is_array( $c ) ? $c : array();
}

// -----------------------------------------------------------------------------
// Helper — account "lettore" (iscrizione leggera solo per commentare)
// -----------------------------------------------------------------------------
/** True se questo utente può commentare le Letture: chi gestisce il portale (titolare o collaboratore), sfoglina vera, o "lettore" non bloccato. */
function gs_lettore_puo_commentare( $uid ) {
	$uid = (int) $uid;
	if ( ! $uid ) { return false; }
	// Titolare e collaboratori hanno sempre accesso, senza doversi "iscrivere": gs_is_approved()
	// da sola riconosce solo un vero Amministratore WordPress (manage_options), non un
	// collaboratore a cui è stato dato solo il permesso gs_manage_gaming.
	if ( user_can( $uid, 'manage_options' ) || user_can( $uid, 'gs_manage_gaming' ) ) { return true; }
	if ( function_exists( 'gs_is_approved' ) && gs_is_approved( $uid ) ) { return true; }
	$status = get_user_meta( $uid, 'gs_status', true );
	if ( 'lettore' !== $status ) { return false; }
	return ! gs_lettore_bloccato_attivo( $uid );
}
/** True se il blocco/sospensione è attualmente in vigore (una sospensione con data scaduta si considera finita da sola). */
function gs_lettore_bloccato_attivo( $uid ) {
	if ( ! get_user_meta( $uid, 'gs_lettore_bloccato', true ) ) { return false; }
	$fino = get_user_meta( $uid, 'gs_lettore_bloccato_fino', true );
	if ( ! $fino ) { return true; } // a tempo indeterminato
	return strtotime( $fino . ' 23:59:59' ) >= current_time( 'timestamp' );
}
/** Tutti gli account "lettore" (non sfogline vere), per il pannello di moderazione. */
function gs_lettori_elenco() {
	return get_users( array(
		'meta_key'   => 'gs_status',
		'meta_value' => 'lettore',
		'orderby'    => 'display_name',
	) );
}

// -----------------------------------------------------------------------------
// Iscrizione leggera "solo per commentare"
// -----------------------------------------------------------------------------
add_shortcode( 'gs_iscrizione_lettore', 'gs_sc_iscrizione_lettore' );
function gs_sc_iscrizione_lettore() {
	if ( is_user_logged_in() ) {
		return '<div class="gs-box gs-notice"><p>Hai già un account: <a href="' . esc_url( gs_login_url() ) . '">accedi</a> se non lo hai già fatto.</p></div>';
	}
	$out  = gs_box_open( '✍️ Iscriviti per commentare' );
	$out .= '<p class="gs-hint">Bastano nome, email e una password: potrai lasciare la tua risposta sotto le Letture. Non è un\'iscrizione come sfoglina: non dà accesso alle sfide, ai corsi o al resto del sito — solo ai commenti pubblici.</p>';
	$out .= '<form class="gs-form gs-form-iscrizione-lettore" onsubmit="return false">';
	ob_start(); gs_antispam_fields(); $out .= ob_get_clean();
	$out .= '<p><label>Nome<br><input type="text" name="nome" autocomplete="name" style="width:100%"></label></p>';
	$out .= '<p><label>Email<br><input type="email" name="email" autocomplete="email" style="width:100%"></label></p>';
	$out .= '<p><label>Nome utente<br><input type="text" name="username" autocomplete="username" style="width:100%"></label></p>';
	$out .= '<p><label>Password (almeno 6 caratteri)<br><input type="password" name="password" autocomplete="new-password" style="width:100%"></label></p>';
	$out .= '<p><button class="gs-btn gs-btn-sm gs-iscrizione-lettore-invia">Iscrivimi</button> <span class="gs-iscrizione-lettore-msg gs-richiesta-esito"></span></p>';
	$out .= '</form>';
	$out .= gs_box_close();
	return $out;
}

add_action( 'wp_ajax_nopriv_gs_registrati_lettore', 'gs_ajax_registrati_lettore' );
add_action( 'wp_ajax_gs_registrati_lettore', 'gs_ajax_registrati_lettore' );
function gs_ajax_registrati_lettore() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$check = gs_antispam_check( $_POST, 'iscrizione_lettore' );
	if ( is_wp_error( $check ) ) { wp_send_json_error( array( 'message' => $check->get_error_message() ) ); }

	$nome     = gs_clean( $_POST['nome'] ?? '' );
	$email    = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$username = sanitize_user( wp_unslash( $_POST['username'] ?? '' ) );
	$password = (string) ( $_POST['password'] ?? '' );

	if ( ! $nome || ! is_email( $email ) || ! $username || strlen( $password ) < 6 ) {
		wp_send_json_error( array( 'message' => 'Compila tutti i campi (password almeno 6 caratteri).' ) );
	}
	if ( username_exists( $username ) ) { wp_send_json_error( array( 'message' => 'Username già in uso, scegline un altro.' ) ); }
	if ( email_exists( $email ) ) { wp_send_json_error( array( 'message' => 'Esiste già un account con questa email.' ) ); }

	$user_id = wp_insert_user( array(
		'user_login'   => $username,
		'user_email'   => $email,
		'user_pass'    => $password,
		'display_name' => $nome,
		'first_name'   => $nome,
		'role'         => 'subscriber',
	) );
	if ( is_wp_error( $user_id ) ) { wp_send_json_error( array( 'message' => $user_id->get_error_message() ) ); }

	update_user_meta( $user_id, 'gs_status', 'lettore' );

	wp_send_json_success( array( 'message' => 'Iscrizione completata! Ora puoi accedere e commentare le Letture.' ) );
}

// -----------------------------------------------------------------------------
// Shortcode pubblico [gs_letture]
// -----------------------------------------------------------------------------
add_shortcode( 'gs_letture', 'gs_sc_letture' );
function gs_sc_letture() {
	$uid          = get_current_user_id();
	$puo_scrivere = gs_can_manage();
	$puo_commentare = $uid && gs_lettore_puo_commentare( $uid );

	$out = gs_box_open( '📖 Le Letture dei Grandi Protagonisti della Cucina' );
	$out .= gs_sezione_aiuto( 'Riflessioni, ricordi e racconti pubblicati dai giornalisti e dai collaboratori dell\'Accademia. Sono visibili a chiunque, anche senza account. Per rispondere sotto una lettura, con una chat pubblica a bolle come su WhatsApp, serve essere una sfoglina iscritta oppure iscriversi con l\'account leggero "solo per commentare" (senza quota associativa, senza accesso al resto del gaming). Chi gestisce il portale può bloccare o sospendere temporaneamente un account "solo per commentare" in caso di abuso.' );
	$out .= '<p class="gs-hint">Riflessioni, ricordi e racconti dei maestri e di chi scrive per l\'Accademia. Chiunque può leggere; per rispondere serve un account — come sfoglina, oppure iscrivendoti solo per commentare.</p>';

	if ( $puo_scrivere ) {
		$out .= '<details class="gs-priv-nuova"><summary>✏️ Pubblica una nuova lettura</summary>';
		$out .= '<form class="gs-form gs-form-lettura-nuova" onsubmit="return false" style="margin-top:8px">';
		$out .= '<p><label>Titolo<br><input type="text" name="titolo" autocomplete="off" style="width:100%"></label></p>';
		$out .= '<p><label>Testo<br><textarea name="testo" rows="6" style="width:100%"></textarea></label></p>';
		$out .= '<p><button class="gs-btn gs-btn-sm gs-lettura-crea">Pubblica</button> <span class="gs-lettura-crea-msg gs-richiesta-esito"></span></p>';
		$out .= '</form></details><hr>';
	}

	if ( ! $uid ) {
		$out .= '<p class="gs-hint">Per rispondere: <a href="' . esc_url( gs_login_url( get_permalink() ) ) . '">accedi</a> se hai già un account, oppure <a href="' . esc_url( gs_pagina_url( 'gs_page_iscrizione_lettore' ) ) . '">iscriviti solo per commentare</a>.</p>';
	} elseif ( ! $puo_commentare && ! $puo_scrivere ) {
		$out .= '<p class="gs-hint">Il tuo account non può momentaneamente commentare qui.</p>';
	}

	$letture = gs_letture_elenco();
	if ( ! $letture ) {
		$out .= '<p class="gs-hint">Nessuna lettura pubblicata ancora.</p>';
	} else {
		$out .= '<input type="text" class="gs-cerca-input" data-target=".gs-letture-lista" placeholder="🔍 Cerca tra le letture…" style="width:100%;max-width:420px;margin-bottom:10px">';
		$out .= '<div class="gs-letture-lista gs-inbox-lista gs-paginate" data-per-page="8">';
		foreach ( $letture as $l ) {
			$autore    = get_user_by( 'id', $l->post_author );
			$commenti  = gs_lettura_commenti( $l->ID );
			$out .= '<details class="gs-inbox-item" data-id="' . (int) $l->ID . '">';
			$out .= '<summary class="gs-inbox-oggetto">' . esc_html( get_the_title( $l ) )
				. '<span class="gs-msg-data">di ' . esc_html( $autore ? $autore->display_name : '—' ) . ' · ' . esc_html( get_the_time( 'j/m/Y', $l ) )
				. ' · ' . count( $commenti ) . ' risposte</span></summary>';
			$out .= '<div class="gs-inbox-corpo">';
			$out .= '<div class="gs-lettura-testo">' . wpautop( esc_html( $l->post_content ) ) . '</div>';
			if ( $puo_scrivere ) {
				$out .= '<p><button class="gs-btn gs-btn-sm gs-btn-ghost gs-lettura-elimina" data-id="' . (int) $l->ID . '">✕ Sposta nel cestino</button></p>';
			}

			// Chat pubblica in bolle whatsapp.
			$out .= '<div class="gs-conv-thread">';
			foreach ( $commenti as $c ) {
				$mine = ( (int) $c['uid'] === $uid );
				$out .= '<div class="gs-conv-msg' . ( $mine ? ' mine' : '' ) . '"><span class="gs-conv-from">' . esc_html( $c['nome'] ) . '</span> '
					. '<span class="gs-msg-data">' . esc_html( date_i18n( 'j/m/Y H:i', (int) $c['time'] ) ) . '</span>'
					. '<div>' . nl2br( esc_html( $c['testo'] ) ) . '</div>';
				if ( $puo_scrivere ) {
					$out .= '<p><button class="gs-btn gs-btn-sm gs-btn-ghost gs-lettura-commento-elimina" data-lettura="' . (int) $l->ID . '" data-commento="' . esc_attr( $c['id'] ) . '">✕ elimina risposta</button></p>';
				}
				$out .= '</div>';
			}
			$out .= '</div>';

			if ( $puo_commentare || $puo_scrivere ) {
				$out .= '<form class="gs-form gs-form-lettura-commento" data-id="' . (int) $l->ID . '" onsubmit="return false">';
				ob_start(); gs_antispam_fields(); $out .= ob_get_clean();
				$out .= '<textarea name="testo" rows="2" style="width:100%" placeholder="Scrivi la tua risposta…"></textarea>';
				$out .= '<p><button class="gs-btn gs-btn-sm gs-lettura-commento-invia">Rispondi</button> <span class="gs-lettura-commento-msg gs-richiesta-esito"></span></p>';
				$out .= '</form>';
			}
			$out .= '</div></details>';
		}
		$out .= '</div>';
	}

	$out .= gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// AJAX — pubblicazione e gestione letture (giornalisti/collaboratori)
// -----------------------------------------------------------------------------
function gs_lettura_guard() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
}

add_action( 'wp_ajax_gs_lettura_crea', 'gs_ajax_lettura_crea' );
function gs_ajax_lettura_crea() {
	gs_lettura_guard();
	$titolo = isset( $_POST['titolo'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) ) : '';
	$testo  = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';
	if ( mb_strlen( $titolo ) < 3 ) { wp_send_json_error( array( 'message' => 'Scrivi un titolo.' ) ); }
	if ( mb_strlen( trim( $testo ) ) < 10 ) { wp_send_json_error( array( 'message' => 'Scrivi il testo della lettura.' ) ); }
	$id = wp_insert_post( array(
		'post_type'    => 'gs_lettura',
		'post_status'  => 'publish',
		'post_author'  => get_current_user_id(),
		'post_title'   => $titolo,
		'post_content' => $testo,
	) );
	if ( is_wp_error( $id ) || ! $id ) { wp_send_json_error( array( 'message' => 'Errore nel salvataggio.' ) ); }
	update_post_meta( $id, 'gs_commenti', array() );
	update_post_meta( $id, 'gs_commenti_cestino', array() );
	wp_send_json_success( array( 'message' => 'Lettura pubblicata.' ) );
}

add_action( 'wp_ajax_gs_lettura_elimina', 'gs_ajax_lettura_elimina' );
function gs_ajax_lettura_elimina() {
	gs_lettura_guard();
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( 'gs_lettura' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Lettura non trovata.' ) ); }
	wp_trash_post( $id );
	wp_send_json_success( array( 'message' => 'Lettura spostata nel cestino.' ) );
}

add_action( 'wp_ajax_gs_lettura_ripristina', 'gs_ajax_lettura_ripristina' );
function gs_ajax_lettura_ripristina() {
	gs_lettura_guard();
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( 'gs_lettura' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Lettura non trovata.' ) ); }
	wp_untrash_post( $id );
	wp_send_json_success( array( 'message' => 'Lettura ripristinata.' ) );
}

// -----------------------------------------------------------------------------
// AJAX — commenti pubblici (sfogline + "lettori")
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_lettura_commento', 'gs_ajax_lettura_commento' );
function gs_ajax_lettura_commento() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_lettore_puo_commentare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può commentare qui.' ) ); }

	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( 'gs_lettura' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Lettura non trovata.' ) ); }

	$check = gs_antispam_check( $_POST, 'lettura_commento' );
	if ( is_wp_error( $check ) ) { wp_send_json_error( array( 'message' => $check->get_error_message() ) ); }

	$testo = isset( $_POST['testo'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) ) : '';
	if ( mb_strlen( $testo ) < 2 ) { wp_send_json_error( array( 'message' => 'Scrivi una risposta.' ) ); }
	if ( mb_strlen( $testo ) > 2000 ) { $testo = mb_substr( $testo, 0, 2000 ); }

	$user = get_user_by( 'id', $uid );
	$commenti = gs_lettura_commenti( $id );
	$commenti[] = array(
		'id'    => uniqid( 'c' ),
		'uid'   => $uid,
		'nome'  => $user ? $user->display_name : 'utente',
		'testo' => $testo,
		'time'  => time(),
	);
	update_post_meta( $id, 'gs_commenti', $commenti );
	wp_send_json_success( array( 'message' => 'Risposta pubblicata.' ) );
}

/**
 * Sposta un commento nel cestino della sua lettura (recuperabile). Estratta
 * dall'AJAX per essere richiamata anche dal pannello unico di moderazione
 * (moderazione.php), che deve poter eliminare un commento delle Letture
 * senza duplicare questa logica.
 */
function gs_lettura_commento_rimuovi( $id, $cid ) {
	if ( 'gs_lettura' !== get_post_type( $id ) ) { return false; }
	$commenti = gs_lettura_commenti( $id );
	$trovato  = null;
	foreach ( $commenti as $k => $c ) {
		if ( $c['id'] === $cid ) { $trovato = $c; unset( $commenti[ $k ] ); break; }
	}
	if ( ! $trovato ) { return false; }
	update_post_meta( $id, 'gs_commenti', array_values( $commenti ) );
	$cestino   = gs_lettura_commenti_cestino( $id );
	$cestino[] = $trovato;
	update_post_meta( $id, 'gs_commenti_cestino', $cestino );
	return true;
}

add_action( 'wp_ajax_gs_lettura_commento_elimina', 'gs_ajax_lettura_commento_elimina' );
function gs_ajax_lettura_commento_elimina() {
	gs_lettura_guard();
	$id  = isset( $_POST['lettura'] ) ? (int) $_POST['lettura'] : 0;
	$cid = isset( $_POST['commento'] ) ? sanitize_text_field( wp_unslash( $_POST['commento'] ) ) : '';
	if ( ! gs_lettura_commento_rimuovi( $id, $cid ) ) { wp_send_json_error( array( 'message' => 'Risposta non trovata.' ) ); }
	wp_send_json_success( array( 'message' => 'Risposta spostata nel cestino.' ) );
}

// -----------------------------------------------------------------------------
// AJAX — moderazione degli account "lettore" (blocca/sospendi/riattiva)
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_lettore_modera', 'gs_ajax_lettore_modera' );
function gs_ajax_lettore_modera() {
	gs_lettura_guard();
	$uid = isset( $_POST['uid'] ) ? (int) $_POST['uid'] : 0;
	if ( ! $uid || 'lettore' !== get_user_meta( $uid, 'gs_status', true ) ) {
		wp_send_json_error( array( 'message' => 'Account non trovato.' ) );
	}
	$azione = isset( $_POST['azione'] ) ? sanitize_key( wp_unslash( $_POST['azione'] ) ) : '';
	if ( 'riattiva' === $azione ) {
		update_user_meta( $uid, 'gs_lettore_bloccato', 0 );
		update_user_meta( $uid, 'gs_lettore_bloccato_fino', '' );
		wp_send_json_success( array( 'message' => 'Account riattivato.' ) );
	}
	if ( 'blocca' === $azione ) {
		$fino = isset( $_POST['fino'] ) ? sanitize_text_field( wp_unslash( $_POST['fino'] ) ) : '';
		if ( $fino && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $fino ) ) { $fino = ''; }
		update_user_meta( $uid, 'gs_lettore_bloccato', 1 );
		update_user_meta( $uid, 'gs_lettore_bloccato_fino', $fino );
		wp_send_json_success( array( 'message' => $fino ? ( 'Sospeso fino al ' . $fino . '.' ) : 'Bloccato a tempo indeterminato.' ) );
	}
	wp_send_json_error( array( 'message' => 'Azione non valida.' ) );
}

// -----------------------------------------------------------------------------
// Pannello gestore
// -----------------------------------------------------------------------------
function gs_pannello_letture() {
	if ( ! gs_can_manage() ) { return; }
	echo gs_box_open( '📖 Le Letture dei Grandi Protagonisti della Cucina' );
	echo '<p class="gs-hint">Pubblica dal box qui sotto o direttamente dalla pagina pubblica. Qui trovi anche il cestino delle letture e la moderazione di chi si è iscritto solo per commentare (blocco a tempo indeterminato o sospensione fino a una data).</p>';

	echo '<form class="gs-form gs-form-lettura-nuova" onsubmit="return false">';
	echo '<p><label>Titolo<br><input type="text" name="titolo" autocomplete="off" style="width:100%"></label></p>';
	echo '<p><label>Testo<br><textarea name="testo" rows="5" style="width:100%"></textarea></label></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-lettura-crea">Pubblica</button> <span class="gs-lettura-crea-msg gs-richiesta-esito"></span></p>';
	echo '</form><hr>';

	// Cestino letture.
	$cestino = get_posts( array( 'post_type' => 'gs_lettura', 'post_status' => 'trash', 'posts_per_page' => -1 ) );
	echo '<details class="gs-todo-cestino"><summary>🗑️ Letture nel cestino (' . count( $cestino ) . ')</summary>';
	if ( ! $cestino ) {
		echo '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		echo '<ul class="gs-todo-list">';
		foreach ( $cestino as $l ) {
			echo '<li class="gs-todo-item" data-id="' . (int) $l->ID . '"><span><strong>' . esc_html( get_the_title( $l ) ) . '</strong></span>'
				. '<button class="gs-todo-ripristina gs-lettura-ripristina" title="Ripristina">↺ Ripristina</button></li>';
		}
		echo '</ul>';
	}
	echo '</details><hr>';

	// Moderazione account "lettore".
	echo '<h4>Chi si è iscritto solo per commentare</h4>';
	$lettori = gs_lettori_elenco();
	if ( ! $lettori ) {
		echo '<p class="gs-hint">Nessuno ancora.</p>';
	} else {
		echo '<table class="gs-table gs-paginate" data-per-page="15"><thead><tr><th>Nome</th><th>Email</th><th>Stato</th><th>Azioni</th></tr></thead><tbody>';
		foreach ( $lettori as $u ) {
			$bloccato = gs_lettore_bloccato_attivo( $u->ID );
			$fino     = get_user_meta( $u->ID, 'gs_lettore_bloccato_fino', true );
			echo '<tr data-uid="' . (int) $u->ID . '"' . ( $bloccato ? ' class="gs-lettore-bloccato"' : '' ) . '>';
			echo '<td>' . esc_html( $u->display_name ) . '</td><td>' . esc_html( $u->user_email ) . '</td>';
			echo '<td>' . ( $bloccato ? ( $fino ? 'Sospeso fino al ' . esc_html( $fino ) : 'Bloccato' ) : 'Attivo' ) . '</td>';
			echo '<td>';
			if ( $bloccato ) {
				echo '<button class="gs-btn gs-btn-sm gs-btn-verde gs-lettore-riattiva">Riattiva</button> ';
			} else {
				echo '<input type="date" class="gs-lettore-fino" style="width:140px"> <button class="gs-btn gs-btn-sm gs-btn-ghost gs-lettore-blocca">Blocca/Sospendi</button> ';
			}
			echo '<span class="gs-lettore-msg gs-richiesta-esito"></span></td></tr>';
		}
		echo '</tbody></table>';
		echo '<p class="gs-hint">Lascia vuota la data per un blocco a tempo indeterminato, oppure scegli fino a quando sospenderlo: torna attivo da solo dopo quella data.</p>';
	}

	echo gs_box_close();
}
