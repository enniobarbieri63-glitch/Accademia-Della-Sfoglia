<?php
/**
 * piatti-estinzione.php — Adotta un Piatto in Via di Estinzione.
 *
 * Il titolare segnala piatti tradizionali rari, poco preparati, a rischio di
 * essere dimenticati. Una sfoglina (o una squadra) può "adottarli": diventa
 * il custode pubblico di quel piatto, visibile a tutte. Un piatto ha un solo
 * custode alla volta.
 *
 * CPT gs_piatto → meta:
 *  - gs_regione       (provenienza del piatto)
 *  - gs_custode_tipo  ('' | 'sfoglina' | 'squadra')
 *  - gs_custode_id    (ID utente, se custode è una sfoglina)
 *  - gs_custode_team  (nome squadra, se custode è una squadra)
 * Il nome del piatto sta in post_title, la descrizione/storia in post_content.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_piatto_register_cpt' );
function gs_piatto_register_cpt() {
	register_post_type( 'gs_piatto', array(
		'labels'       => array( 'name' => 'Piatti in Via di Estinzione' ),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'title', 'editor' ),
	) );
}

// -----------------------------------------------------------------------------
// Helper
// -----------------------------------------------------------------------------

function gs_piatto_get( $id ) {
	return array(
		'id'           => $id,
		'nome'         => get_the_title( $id ),
		'descrizione'  => get_post( $id ) ? get_post( $id )->post_content : '',
		'regione'      => (string) get_post_meta( $id, 'gs_regione', true ),
		'custode_tipo' => (string) get_post_meta( $id, 'gs_custode_tipo', true ),
		'custode_id'   => (int) get_post_meta( $id, 'gs_custode_id', true ),
		'custode_team' => (string) get_post_meta( $id, 'gs_custode_team', true ),
	);
}

function gs_piatti_tutti() {
	return gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_piatto',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'suppress_filters' => true,
	) ), 'gs_piatto' );
}

/** Etichetta leggibile del custode attuale, o '' se libero. */
function gs_piatto_custode_label( $c ) {
	if ( 'sfoglina' === $c['custode_tipo'] && $c['custode_id'] ) {
		$u = get_userdata( $c['custode_id'] );
		return $u ? $u->display_name : '';
	}
	if ( 'squadra' === $c['custode_tipo'] && $c['custode_team'] ) {
		return $c['custode_team'];
	}
	return '';
}

// -----------------------------------------------------------------------------
// [gs_piatti_estinzione] — lato sfoglina: elenco pubblico + adozione
// -----------------------------------------------------------------------------
add_shortcode( 'gs_piatti_estinzione', 'gs_sc_piatti_estinzione' );
function gs_sc_piatti_estinzione() {
	if ( $g = gs_gate_riservato() ) { return $g; }
	if ( $g = gs_blackout_gate() ) { return $g; }

	$out = gs_box_open( '🦕 Adotta un Piatto in Via di Estinzione' );
	$out .= gs_sezione_aiuto( 'Qui trovi i piatti tradizionali che i maestri hanno segnalato come a rischio di essere dimenticati. Un piatto "libero" può essere adottato: diventi la sua custode pubblica, il tuo nome compare accanto al piatto per tutte. Se fai parte di una squadra, puoi adottarlo anche a nome della squadra. Ogni piatto ha una sola custode alla volta.' );
	$out .= '<p class="gs-hint">Piatti tradizionali rari: qualcuno deve tenerli vivi.</p>';

	$piatti = gs_piatti_tutti();
	if ( ! $piatti ) {
		$out .= '<p>Nessun piatto segnalato al momento.</p>' . gs_box_close();
		return $out;
	}

	$uid  = get_current_user_id();
	$team = gs_get_user_team( $uid );

	$out .= '<div class="gs-gallery gs-paginate" data-per-page="9">';
	foreach ( $piatti as $p ) {
		$c        = gs_piatto_get( $p->ID );
		$custode  = gs_piatto_custode_label( $c );
		$out .= '<div class="gs-card"><div class="gs-card-body">';
		$out .= '<h4>' . esc_html( $c['nome'] ) . '</h4>';
		if ( $c['regione'] ) { $out .= '<p class="gs-card-author">📍 ' . esc_html( $c['regione'] ) . '</p>'; }
		if ( $c['descrizione'] ) { $out .= '<p class="gs-hint">' . nl2br( esc_html( $c['descrizione'] ) ) . '</p>'; }
		if ( $custode ) {
			$out .= '<p class="gs-hint">🛡️ Custodita da <strong>' . esc_html( $custode ) . '</strong></p>';
		} else {
			$out .= '<p class="gs-hint">🔓 Nessuna custode: puoi essere tu!</p>';
			$out .= '<p><button class="gs-btn gs-btn-sm gs-piatto-adotta" data-piatto="' . (int) $p->ID . '" data-come="sfoglina">Adotta come singola</button> ';
			if ( $team ) {
				$out .= '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-piatto-adotta" data-piatto="' . (int) $p->ID . '" data-come="squadra">Adotta per «' . esc_html( $team ) . '»</button> ';
			}
			$out .= '<span class="gs-piatto-msg gs-richiesta-esito"></span></p>';
		}
		if ( ( 'sfoglina' === $c['custode_tipo'] && $c['custode_id'] === $uid ) || ( 'squadra' === $c['custode_tipo'] && $team && $c['custode_team'] === $team ) ) {
			$out .= '<p><button class="gs-btn gs-btn-sm gs-btn-ghost gs-piatto-libera" data-piatto="' . (int) $p->ID . '">Rinuncia alla custodia</button> <span class="gs-piatto-msg gs-richiesta-esito"></span></p>';
		}
		$out .= '</div></div>';
	}
	$out .= '</div>';
	$out .= gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// Logica pura di adozione/rinuncia (testabile senza AJAX)
// -----------------------------------------------------------------------------

/**
 * Adotta un piatto libero per $uid, come 'sfoglina' o 'squadra'. Restituisce
 * array( 'ok' => bool, 'message' => string ).
 *
 * Due correzioni insieme (trovate il 26/08/2026 cercando il limitatore del
 * Diario — non è un tetto di gioco, sono due difetti):
 *
 * PE1 — "leggi poi scrivi" su gs_custode_tipo senza protezione: due persone
 * che adottano lo stesso piatto quasi insieme leggevano entrambe "libero" e
 * scrivevano entrambe, con l'ultima scrittura che sovrascriveva silenziosa
 * la prima (che restava comunque pagata). Corretto con lo stesso lucchetto
 * MySQL già usato altrove nel plugin per questa classe di difetto.
 *
 * Punti infiniti — gs_custode_tipo dice CHI è la custode ADESSO e viene
 * cancellato dalla rinuncia: da solo non impedisce di adottare, rinunciare e
 * riadottare per 20 punti a giro, all'infinito, sullo stesso piatto (stesso
 * punto cieco della mini-missione Madrina & Allieva, M2). Corretto con un
 * contrassegno per persona+piatto che non si toglie mai, nemmeno alla
 * rinuncia.
 */
function gs_piatto_adotta_uid( $pid, $uid, $come = 'sfoglina' ) {
	if ( 'gs_piatto' !== get_post_type( $pid ) ) { return array( 'ok' => false, 'message' => 'Piatto non valido.' ); }

	global $wpdb;
	$lock = 'gs_piatto_adotta_' . (int) $pid;
	if ( '1' !== $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) ) ) {
		return array( 'ok' => false, 'message' => 'Ci sta già pensando qualcun\'altra, riprova tra un attimo.' );
	}

	// try/finally invece del rilascio manuale ad ogni uscita: se una delle
	// chiamate qui dentro (update_post_meta, gs_add_points…) muore con un
	// errore fatale, il lucchetto si rilascia comunque, invece di restare
	// chiuso fino alla fine della connessione MySQL (segnalato 26/08/2026).
	try {
		if ( get_post_meta( $pid, 'gs_custode_tipo', true ) ) {
			return array( 'ok' => false, 'message' => 'Questo piatto ha già una custode.' );
		}

		if ( 'squadra' === $come ) {
			$team = gs_get_user_team( $uid );
			if ( ! $team ) {
				return array( 'ok' => false, 'message' => 'Non fai parte di una squadra.' );
			}
			update_post_meta( $pid, 'gs_custode_tipo', 'squadra' );
			update_post_meta( $pid, 'gs_custode_team', $team );
			delete_post_meta( $pid, 'gs_custode_id' );
		} else {
			update_post_meta( $pid, 'gs_custode_tipo', 'sfoglina' );
			update_post_meta( $pid, 'gs_custode_id', $uid );
			delete_post_meta( $pid, 'gs_custode_team' );
		}

		$gia_pagato = 'gs_piatto_pagato_' . (int) $pid;
		if ( ! get_user_meta( $uid, $gia_pagato, true ) ) {
			update_user_meta( $uid, $gia_pagato, current_time( 'mysql' ) );   // contrassegno PRIMA
			gs_add_points( $uid, gs_get_points_value( 'piatto_adottato', 20 ), 'Hai adottato un piatto in via di estinzione: ' . get_the_title( $pid ) );
		}

		return array( 'ok' => true, 'message' => 'Sei ora la custode di "' . get_the_title( $pid ) . '"!' );
	} finally {
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	}
}

/** Rinuncia alla custodia di $pid per conto di $uid (o del titolare/collaboratore). */
function gs_piatto_libera_uid( $pid, $uid ) {
	if ( 'gs_piatto' !== get_post_type( $pid ) ) { return array( 'ok' => false, 'message' => 'Piatto non valido.' ); }

	// Stesso lucchetto dell'adozione: senza, una rinuncia e un'adozione nello
	// stesso istante potevano incastrarsi — chi adotta si vede detto "sei la
	// custode" e riceve il contrassegno, ma chi rinunciava nel frattempo
	// lascia il piatto libero per davvero solo dopo, e il piatto risulta
	// senza custode nonostante il messaggio (segnalato 26/08/2026).
	global $wpdb;
	$lock = 'gs_piatto_adotta_' . (int) $pid;
	if ( '1' !== $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) ) ) {
		return array( 'ok' => false, 'message' => 'Operazione occupata, riprova tra un attimo.' );
	}

	try {
		$c    = gs_piatto_get( $pid );
		$team = gs_get_user_team( $uid );
		$mia  = ( 'sfoglina' === $c['custode_tipo'] && $c['custode_id'] === $uid ) || ( 'squadra' === $c['custode_tipo'] && $team && $c['custode_team'] === $team );
		if ( ! $mia && ! gs_can_manage() ) { return array( 'ok' => false, 'message' => 'Non sei la custode di questo piatto.' ); }

		delete_post_meta( $pid, 'gs_custode_tipo' );
		delete_post_meta( $pid, 'gs_custode_id' );
		delete_post_meta( $pid, 'gs_custode_team' );
		return array( 'ok' => true, 'message' => 'Custodia lasciata: il piatto è di nuovo libero per l\'adozione.' );
	} finally {
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	}
}

// -----------------------------------------------------------------------------
// AJAX lato sfoglina
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_piatto_adotta', 'gs_ajax_piatto_adotta' );
function gs_ajax_piatto_adotta() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }

	$pid  = isset( $_POST['piatto'] ) ? (int) $_POST['piatto'] : 0;
	$come = isset( $_POST['come'] ) ? sanitize_key( wp_unslash( $_POST['come'] ) ) : 'sfoglina';
	$r    = gs_piatto_adotta_uid( $pid, $uid, $come );
	if ( $r['ok'] ) { wp_send_json_success( array( 'message' => $r['message'] ) ); }
	wp_send_json_error( array( 'message' => $r['message'] ) );
}

add_action( 'wp_ajax_gs_piatto_libera', 'gs_ajax_piatto_libera' );
function gs_ajax_piatto_libera() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }

	$pid = isset( $_POST['piatto'] ) ? (int) $_POST['piatto'] : 0;
	$r   = gs_piatto_libera_uid( $pid, $uid );
	if ( $r['ok'] ) { wp_send_json_success( array( 'message' => $r['message'] ) ); }
	wp_send_json_error( array( 'message' => $r['message'] ) );
}

// -----------------------------------------------------------------------------
// PANNELLO — gestione piatti (titolare)
// -----------------------------------------------------------------------------
function gs_pannello_piatti_estinzione() {
	if ( ! gs_can_manage() ) { return; }

	echo gs_box_open( 'Piatti in Via di Estinzione', '', 'gs-box-piatti' );
	echo '<p class="gs-hint">Segnala qui i piatti tradizionali a rischio. Le sfogline (o le squadre) possono adottarli come custodi pubblici dalla sezione «Adotta un Piatto».</p>';

	echo '<form class="gs-form gs-form-piatto-crea" onsubmit="return false">';
	echo '<p><label>Nome del piatto<br><input type="text" name="nome" style="width:100%" required></label></p>';
	echo '<p><label>Provenienza<br><input type="text" name="regione" style="width:100%"></label></p>';
	echo '<p><label>Descrizione / perché è a rischio<br><textarea name="descrizione" rows="3" style="width:100%"></textarea></label></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-piatto-crea">Aggiungi piatto</button> <span class="gs-piatto-crea-msg gs-richiesta-esito"></span></p>';
	echo '</form>';

	$piatti = gs_piatti_tutti();
	echo '<h4 style="margin-top:14px">Piatti segnalati (' . count( $piatti ) . ')</h4>';
	if ( ! $piatti ) {
		echo '<p class="gs-hint">Nessun piatto segnalato ancora.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="8">';
		foreach ( $piatti as $p ) {
			$c       = gs_piatto_get( $p->ID );
			$custode = gs_piatto_custode_label( $c );
			echo '<details class="gs-inbox-item" data-piatto="' . (int) $p->ID . '">';
			echo '<summary class="gs-inbox-oggetto">' . esc_html( $c['nome'] ) . ' <span class="gs-msg-data">' . ( $custode ? 'custodita da ' . esc_html( $custode ) : 'libera' ) . '</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			echo '<form class="gs-form gs-form-piatto-modifica" data-piatto="' . (int) $p->ID . '" onsubmit="return false">';
			echo '<p><label>Nome del piatto<br><input type="text" name="nome" value="' . esc_attr( $c['nome'] ) . '" style="width:100%" required></label></p>';
			echo '<p><label>Provenienza<br><input type="text" name="regione" value="' . esc_attr( $c['regione'] ) . '" style="width:100%"></label></p>';
			echo '<p><label>Descrizione / perché è a rischio<br><textarea name="descrizione" rows="3" style="width:100%">' . esc_textarea( $c['descrizione'] ) . '</textarea></label></p>';
			echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-piatto-salva">Salva modifiche</button> <span class="gs-piatto-riga-msg gs-richiesta-esito"></span></p>';
			echo '</form>';
			if ( $custode ) {
				echo '<p><button class="gs-btn gs-btn-sm gs-btn-ghost gs-piatto-libera" data-piatto="' . (int) $p->ID . '">Libera dalla custodia</button> <span class="gs-piatto-msg gs-richiesta-esito"></span></p>';
			}
			echo '<p><button class="gs-btn gs-btn-sm gs-btn-ghost gs-piatto-elimina" data-piatto="' . (int) $p->ID . '">Elimina</button> <span class="gs-piatto-mod-msg gs-richiesta-esito"></span></p>';
			echo '</div></details>';
		}
		echo '</div>';
	}

	// Cestino.
	$trash = get_posts( array( 'post_type' => 'gs_piatto', 'post_status' => 'trash', 'posts_per_page' => 50, 'suppress_filters' => true ) );
	echo '<div class="gs-sezione-cestino">';
	echo '<h4 class="gs-titolo-cestino">🗑️ Cestino</h4>';
	if ( ! $trash ) {
		echo '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		echo '<table class="gs-table gs-paginate" data-per-page="8"><thead><tr>' . gs_cestino_th_checkbox() . '<th>Piatto</th><th></th></tr></thead><tbody>';
		foreach ( $trash as $p ) {
			echo '<tr data-piatto="' . (int) $p->ID . '">' . gs_cestino_td_checkbox( $p->ID ) . '<td>' . esc_html( get_the_title( $p ) ) . '</td>';
			echo '<td><button class="gs-btn gs-btn-sm gs-btn-verde gs-piatto-ripristina" data-piatto="' . (int) $p->ID . '">Ripristina</button> <span class="gs-piatto-trow-msg gs-richiesta-esito"></span></td></tr>';
		}
		echo '</tbody></table>';
		echo gs_cestino_azioni_bulk( 'gs_piatto' );
	}
	echo '</div>';

	echo gs_box_close();
}

add_action( 'wp_ajax_gs_piatto_crea', 'gs_ajax_piatto_crea' );
function gs_ajax_piatto_crea() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$nome = isset( $_POST['nome'] ) ? sanitize_text_field( wp_unslash( $_POST['nome'] ) ) : '';
	if ( '' === trim( $nome ) ) { wp_send_json_error( array( 'message' => 'Scrivi il nome del piatto.' ) ); }
	$regione     = isset( $_POST['regione'] ) ? sanitize_text_field( wp_unslash( $_POST['regione'] ) ) : '';
	$descrizione = isset( $_POST['descrizione'] ) ? sanitize_textarea_field( wp_unslash( $_POST['descrizione'] ) ) : '';

	$pid = wp_insert_post( array(
		'post_type'    => 'gs_piatto',
		'post_status'  => 'publish',
		'post_title'   => $nome,
		'post_content' => $descrizione,
	) );
	if ( is_wp_error( $pid ) || ! $pid ) { wp_send_json_error( array( 'message' => 'Errore nella creazione.' ) ); }
	update_post_meta( $pid, 'gs_regione', $regione );

	wp_send_json_success( array( 'message' => 'Piatto aggiunto: ora può essere adottato.' ) );
}

add_action( 'wp_ajax_gs_piatto_modifica', 'gs_ajax_piatto_modifica' );
function gs_ajax_piatto_modifica() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$pid = isset( $_POST['piatto'] ) ? (int) $_POST['piatto'] : 0;
	if ( 'gs_piatto' !== get_post_type( $pid ) ) { wp_send_json_error( array( 'message' => 'Piatto non valido.' ) ); }

	$nome = isset( $_POST['nome'] ) ? sanitize_text_field( wp_unslash( $_POST['nome'] ) ) : '';
	if ( '' === trim( $nome ) ) { wp_send_json_error( array( 'message' => 'Scrivi il nome del piatto.' ) ); }
	$regione     = isset( $_POST['regione'] ) ? sanitize_text_field( wp_unslash( $_POST['regione'] ) ) : '';
	$descrizione = isset( $_POST['descrizione'] ) ? sanitize_textarea_field( wp_unslash( $_POST['descrizione'] ) ) : '';

	wp_update_post( array( 'ID' => $pid, 'post_title' => $nome, 'post_content' => $descrizione ) );
	update_post_meta( $pid, 'gs_regione', $regione );

	wp_send_json_success( array( 'message' => 'Piatto aggiornato.' ) );
}

add_action( 'wp_ajax_gs_piatto_elimina', 'gs_ajax_piatto_elimina' );
function gs_ajax_piatto_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$pid = isset( $_POST['piatto'] ) ? (int) $_POST['piatto'] : 0;
	if ( 'gs_piatto' !== get_post_type( $pid ) ) { wp_send_json_error( array( 'message' => 'Piatto non valido.' ) ); }
	wp_trash_post( $pid );
	wp_send_json_success( array( 'message' => 'Spostato nel cestino.' ) );
}

add_action( 'wp_ajax_gs_piatto_ripristina', 'gs_ajax_piatto_ripristina' );
function gs_ajax_piatto_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$pid = isset( $_POST['piatto'] ) ? (int) $_POST['piatto'] : 0;
	if ( 'gs_piatto' !== get_post_type( $pid ) ) { wp_send_json_error( array( 'message' => 'Piatto non valido.' ) ); }
	wp_untrash_post( $pid );
	wp_send_json_success( array( 'message' => 'Piatto ripristinato.' ) );
}
