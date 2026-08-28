<?php
/**
 * testimonianze.php — "Dicono di Noi": le impressioni di chi ha fatto un
 * percorso all'Accademia della Sfoglia (sfogline e sfoglini).
 *
 * Ogni sfoglina/sfoglino può scrivere cosa pensa dell'Accademia dal proprio pannello.
 * Diventa visibile nella sezione pubblica "Dicono di Noi" solo dopo
 * l'approvazione del titolare — stesso meccanismo già usato per il
 * Ricettario delle Famiglie e per la Biografia della Vetrina. A differenza
 * del Ricettario, la sezione pubblica è visibile ANCHE a chi visita il sito
 * senza essere registrato (serve a dare fiducia a chi non conosce ancora
 * l'Accademia): solo il modulo per scriverne una nuova richiede l'accesso.
 *
 * CPT gs_testimonianza → meta:
 *  - gs_credito  (come firmarla, es. "Anna, sfoglina dal 2022" — facoltativo,
 *    se vuoto si usa il nome dell'account)
 *  - gs_stato    ('in_attesa' | 'approvata' | 'rifiutata')
 * Il testo della testimonianza sta in post_content.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_testim_register_cpt' );
function gs_testim_register_cpt() {
	register_post_type( 'gs_testimonianza', array(
		'labels'       => array( 'name' => 'Dicono di Noi' ),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'editor', 'author' ),
	) );
}

// -----------------------------------------------------------------------------
// Helper
// -----------------------------------------------------------------------------
function gs_testim_get( $id ) {
	$au = get_post_field( 'post_author', $id );
	$u  = $au ? get_userdata( $au ) : false;
	$credito = (string) get_post_meta( $id, 'gs_credito', true );
	return array(
		'id'      => $id,
		'testo'   => get_post( $id ) ? get_post( $id )->post_content : '',
		'credito' => '' !== trim( $credito ) ? $credito : ( $u ? $u->display_name : '' ),
		'stato'   => get_post_meta( $id, 'gs_stato', true ) ?: 'in_attesa',
		'autore'  => (int) $au,
	);
}

/** Testimonianze approvate, visibili a chiunque visiti il sito. */
function gs_testim_approvate() {
	return gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_testimonianza',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_key'       => 'gs_stato',
		'meta_value'     => 'approvata',
		'suppress_filters' => true,
	) ), 'gs_testimonianza' );
}

/** Testimonianze inviate da una sfoglina (qualunque stato). */
function gs_testim_utente( $uid ) {
	$uid = (int) $uid;
	if ( ! $uid ) { return array(); }
	return gs_solo_tipo( gs_get_posts_by_author( array(
		'post_type'      => 'gs_testimonianza',
		'post_status'    => 'publish',
		'posts_per_page' => 20,
		'author'         => $uid,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'suppress_filters' => true,
	) ), 'gs_testimonianza' );
}

/** Testimonianze in attesa di approvazione. */
function gs_testim_in_attesa() {
	return gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_testimonianza',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'orderby'        => 'date',
		'order'          => 'ASC',
		'meta_key'       => 'gs_stato',
		'meta_value'     => 'in_attesa',
		'suppress_filters' => true,
	) ), 'gs_testimonianza' );
}

// -----------------------------------------------------------------------------
// [gs_dicono_di_noi] — pubblica: elenco approvate + modulo di invio (solo con accesso effettuato)
// -----------------------------------------------------------------------------
add_shortcode( 'gs_dicono_di_noi', 'gs_sc_dicono_di_noi' );
function gs_sc_dicono_di_noi() {
	if ( $g = gs_blackout_gate() ) { return $g; }

	$out = gs_box_open( '💬 Dicono di Noi — le voci di chi ha frequentato l\'Accademia' );
	$out .= gs_sezione_aiuto( 'Qui sotto trovi le impressioni di sfogline e sfoglini che hanno fatto un percorso all\'Accademia della Sfoglia, raccontate con parole loro. Se anche tu hai seguito un percorso e hai effettuato l\'accesso, più in basso trovi il modulo per lasciare la tua testimonianza (testo libero + come vuoi firmarti, facoltativo) e l\'elenco di quelle che hai già inviato.' );
	$approvate = gs_testim_approvate();
	if ( ! $approvate ) {
		$out .= '<p class="gs-hint">Non ci sono ancora testimonianze pubblicate.</p>';
	} else {
		$out .= '<div class="gs-gallery gs-testim-lista gs-paginate" data-per-page="9">';
		foreach ( $approvate as $t ) {
			$c = gs_testim_get( $t->ID );
			$out .= '<div class="gs-card"><div class="gs-card-body">';
			$out .= '<p class="gs-testim-testo">“' . nl2br( esc_html( $c['testo'] ) ) . '”</p>';
			if ( $c['credito'] ) { $out .= '<p class="gs-card-author">— ' . esc_html( $c['credito'] ) . '</p>'; }
			$out .= '</div></div>';
		}
		$out .= '</div>';
	}
	$out .= gs_box_close();

	// Il modulo per scriverne una nuova richiede l'accesso (solo sfogline).
	if ( ! is_user_logged_in() ) {
		return $out;
	}

	$out .= gs_box_open( '✏️ Racconta la tua esperienza' );
	$out .= '<p class="gs-hint">La tua testimonianza verrà vagliata prima di comparire pubblicamente in questa sezione.</p>';
	$out .= '<div class="gs-todo-riquadro">';
	$out .= '<form class="gs-form gs-form-testimonianza" onsubmit="return false">';
	$out .= '<p><label>La tua testimonianza<br><textarea name="testo" rows="4" style="width:100%" required></textarea></label></p>';
	$out .= '<p><label>Come vuoi firmarla (facoltativo)<br><input type="text" name="credito" autocomplete="off" style="width:100%" placeholder="Es. Anna, sfoglina dal 2022"></label></p>';
	$out .= '<p><button class="gs-btn gs-btn-sm gs-testim-invia">Invia per approvazione</button> <span class="gs-testim-msg gs-richiesta-esito"></span></p>';
	$out .= '</form>';

	$mie = gs_testim_utente( get_current_user_id() );
	if ( $mie ) {
		$stati_it = array( 'in_attesa' => 'In attesa di approvazione', 'approvata' => 'Approvata, visibile a tutti', 'rifiutata' => 'Non approvata' );
		$out .= '<h4 style="margin-top:14px">Le tue testimonianze inviate</h4>';
		$out .= '<div class="gs-inbox-lista gs-paginate" data-per-page="5">';
		foreach ( $mie as $t ) {
			$c = gs_testim_get( $t->ID );
			$anteprima = wp_trim_words( $c['testo'], 10 );
			$out .= '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">' . esc_html( $anteprima )
				. ' <span class="gs-msg-data">' . esc_html( $stati_it[ $c['stato'] ] ?? $c['stato'] ) . '</span></summary>';
			$out .= '<div class="gs-inbox-corpo"><div class="gs-inbox-testo">' . nl2br( esc_html( $c['testo'] ) ) . '</div></div></details>';
		}
		$out .= '</div>';
	}
	$out .= '</div>';
	$out .= gs_box_close();

	return $out;
}

// -----------------------------------------------------------------------------
// AJAX lato sfoglina — invio testimonianza
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_testim_invia', 'gs_ajax_testim_invia' );
function gs_ajax_testim_invia() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }

	$testo = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';
	$testo = trim( $testo );
	if ( '' === $testo ) { wp_send_json_error( array( 'message' => 'Scrivi la tua testimonianza.' ) ); }
	if ( function_exists( 'gs_msg_clean' ) ) { $testo = gs_msg_clean( $testo ); }

	$credito = isset( $_POST['credito'] ) ? sanitize_text_field( wp_unslash( $_POST['credito'] ) ) : '';

	$tid = wp_insert_post( array(
		'post_type'    => 'gs_testimonianza',
		'post_status'  => 'publish',
		'post_content' => $testo,
		'post_author'  => $uid,
	) );
	if ( is_wp_error( $tid ) || ! $tid ) { wp_send_json_error( array( 'message' => 'Errore nell\'invio.' ) ); }

	update_post_meta( $tid, 'gs_credito', $credito );
	update_post_meta( $tid, 'gs_stato', 'in_attesa' );

	if ( function_exists( 'gs_inbox_crea' ) ) {
		$u = get_userdata( $uid );
		gs_inbox_crea( 'Nuova testimonianza da approvare', ( $u ? $u->display_name : '' ) . ' ha inviato una testimonianza per "Dicono di Noi". Va approvata per comparire pubblicamente.', array( 'from' => $u ? $u->display_name : 'Sfoglina', 'link_ancora' => 'admin.php?page=gs-generale#gs-zona-testimonianze' ) );
	}

	wp_send_json_success( array( 'message' => 'Testimonianza inviata per approvazione.' ) );
}

// -----------------------------------------------------------------------------
// PANNELLO — approvazione testimonianze (titolare)
// -----------------------------------------------------------------------------
function gs_pannello_testimonianze() {
	if ( ! gs_can_manage() ) { return; }

	echo gs_box_open( 'Dicono di Noi — approvazione delle testimonianze', '', 'gs-box-testimonianze' );
	echo '<p class="gs-hint">Sono le impressioni lasciate da sfogline e sfoglini che hanno completato un percorso all\'Accademia della Sfoglia. Compaiono pubblicamente solo dopo la tua approvazione. Qui trovi quelle in attesa, quelle già approvate (che puoi togliere dalla vista pubblica) e il cestino.</p>';

	$attesa = gs_testim_in_attesa();
	echo '<h4>In attesa di approvazione</h4>';
	if ( ! $attesa ) {
		echo '<p class="gs-hint">Nessuna testimonianza in attesa.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-paginate" data-per-page="5">';
		foreach ( $attesa as $t ) {
			$c  = gs_testim_get( $t->ID );
			$au = get_userdata( $c['autore'] );
			echo '<details class="gs-inbox-item gs-non-letto gs-attesa-forte" data-testim="' . (int) $t->ID . '">';
			echo '<summary class="gs-inbox-oggetto"><span class="gs-dot"></span> ' . esc_html( wp_trim_words( $c['testo'], 10 ) )
				. ' <span class="gs-msg-data">di ' . esc_html( $au ? $au->display_name : '—' ) . '</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			echo '<p>' . nl2br( esc_html( $c['testo'] ) ) . '</p>';
			if ( $c['credito'] ) { echo '<p class="gs-hint">Firma proposta: ' . esc_html( $c['credito'] ) . '</p>'; }
			echo '<p><button class="gs-btn gs-btn-sm gs-testim-modera" data-testim="' . (int) $t->ID . '" data-esito="approvata">Approva</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-testim-modera" data-testim="' . (int) $t->ID . '" data-esito="rifiutata">Rifiuta</button> ';
			echo '<span class="gs-testim-mod-msg gs-richiesta-esito"></span></p>';
			echo '</div></details>';
		}
		echo '</div>';
	}

	$approvate = gs_testim_approvate();
	echo '<h4 style="margin-top:14px">Approvate (visibili pubblicamente)</h4>';
	if ( ! $approvate ) {
		echo '<p class="gs-hint">Nessuna testimonianza approvata.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="5">';
		foreach ( $approvate as $t ) {
			$c  = gs_testim_get( $t->ID );
			$au = get_userdata( $c['autore'] );
			echo '<details class="gs-inbox-item" data-testim="' . (int) $t->ID . '">';
			echo '<summary class="gs-inbox-oggetto">' . esc_html( wp_trim_words( $c['testo'], 10 ) ) . ' <span class="gs-msg-data">di ' . esc_html( $au ? $au->display_name : '—' ) . '</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			echo '<p>' . nl2br( esc_html( $c['testo'] ) ) . '</p>';
			echo '<p><button class="gs-btn gs-btn-sm gs-btn-ghost gs-testim-modera" data-testim="' . (int) $t->ID . '" data-esito="rifiutata">Togli dalla vista pubblica</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-testim-elimina" data-testim="' . (int) $t->ID . '">Elimina</button> ';
			echo '<span class="gs-testim-mod-msg gs-richiesta-esito"></span></p>';
			echo '</div></details>';
		}
		echo '</div>';
	}

	// Cestino.
	$trash = get_posts( array( 'post_type' => 'gs_testimonianza', 'post_status' => 'trash', 'posts_per_page' => 50, 'suppress_filters' => true ) );
	echo '<div class="gs-sezione-cestino">';
	echo '<h4 class="gs-titolo-cestino">🗑️ Cestino</h4>';
	if ( ! $trash ) {
		echo '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		echo '<table class="gs-table gs-paginate" data-per-page="8"><thead><tr>' . gs_cestino_th_checkbox() . '<th>Testimonianza</th><th>Sfoglina</th><th></th></tr></thead><tbody>';
		foreach ( $trash as $t ) {
			$au = get_userdata( $t->post_author );
			echo '<tr data-testim="' . (int) $t->ID . '">' . gs_cestino_td_checkbox( $t->ID ) . '<td>' . esc_html( wp_trim_words( $t->post_content, 8 ) ) . '</td><td>' . esc_html( $au ? $au->display_name : '—' ) . '</td>';
			echo '<td><button class="gs-btn gs-btn-sm gs-btn-verde gs-testim-ripristina" data-testim="' . (int) $t->ID . '">Ripristina</button> <span class="gs-testim-trow-msg gs-richiesta-esito"></span></td></tr>';
		}
		echo '</tbody></table>';
		echo gs_cestino_azioni_bulk( 'gs_testimonianza' );
	}
	echo '</div>';

	echo gs_box_close();
}

add_action( 'wp_ajax_gs_testim_modera', 'gs_ajax_testim_modera' );
function gs_ajax_testim_modera() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$tid   = isset( $_POST['testim'] ) ? (int) $_POST['testim'] : 0;
	$esito = isset( $_POST['esito'] ) ? sanitize_key( wp_unslash( $_POST['esito'] ) ) : 'rifiutata';
	if ( 'gs_testimonianza' !== get_post_type( $tid ) ) { wp_send_json_error( array( 'message' => 'Testimonianza non valida.' ) ); }

	update_post_meta( $tid, 'gs_stato', 'approvata' === $esito ? 'approvata' : 'rifiutata' );
	$msg = 'approvata' === $esito ? 'Testimonianza approvata: ora è visibile pubblicamente.' : 'Testimonianza non approvata (tolta dalla vista pubblica).';

	if ( 'approvata' === $esito ) {
		do_action( 'gs_testimonianza_approvata', (int) get_post_field( 'post_author', $tid ) );
	}

	$au = get_userdata( get_post_field( 'post_author', $tid ) );
	if ( $au ) {
		$corpo_testim = 'approvata' === $esito
			? "Ciao " . $au->display_name . ",\n\nla tua testimonianza è stata approvata ed è ora visibile pubblicamente nella sezione \"Dicono di Noi\"."
			: "Ciao " . $au->display_name . ",\n\nla tua testimonianza non è (più) nella vista pubblica di \"Dicono di Noi\". Puoi scrivere alla segreteria per saperne di più.";
		if ( function_exists( 'gs_mail_progetto' ) ) {
			gs_mail_progetto( $au->ID, 'messaggi', 'La tua testimonianza — Accademia della Sfoglia', $corpo_testim );
		} elseif ( $au->user_email ) {
			wp_mail( $au->user_email, 'La tua testimonianza — Accademia della Sfoglia', $corpo_testim );
		}
	}
	wp_send_json_success( array( 'message' => $msg ) );
}

add_action( 'wp_ajax_gs_testim_elimina', 'gs_ajax_testim_elimina' );
function gs_ajax_testim_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$tid = isset( $_POST['testim'] ) ? (int) $_POST['testim'] : 0;
	if ( 'gs_testimonianza' !== get_post_type( $tid ) ) { wp_send_json_error( array( 'message' => 'Testimonianza non valida.' ) ); }
	wp_trash_post( $tid );
	wp_send_json_success( array( 'message' => 'Spostata nel cestino.' ) );
}

add_action( 'wp_ajax_gs_testim_ripristina', 'gs_ajax_testim_ripristina' );
function gs_ajax_testim_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$tid = isset( $_POST['testim'] ) ? (int) $_POST['testim'] : 0;
	if ( 'gs_testimonianza' !== get_post_type( $tid ) ) { wp_send_json_error( array( 'message' => 'Testimonianza non valida.' ) ); }
	wp_untrash_post( $tid );
	update_post_meta( $tid, 'gs_stato', 'in_attesa' );
	wp_send_json_success( array( 'message' => 'Testimonianza ripristinata (torna in attesa di approvazione).' ) );
}
