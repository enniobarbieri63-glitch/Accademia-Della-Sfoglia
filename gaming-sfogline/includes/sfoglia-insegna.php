<?php
/**
 * sfoglia-insegna.php — La Sfoglia che Insegna Se Stessa.
 *
 * Una sfoglina racconta onestamente un errore che ha fatto e cosa ha
 * imparato. Se il titolare lo ritiene un errore comune e utile per le
 * altre, lo "promuove" a materiale didattico ufficiale: compare
 * nell'archivio pubblico con il credito a chi l'ha segnalato. Stesso
 * schema di approvazione del Ricettario/Biografia/Matterello Parlante.
 *
 * CPT gs_errore_didattico → meta:
 *  - gs_lezione  (cosa ho imparato — testo)
 *  - gs_stato    ('in_attesa' | 'promosso' | 'rifiutato')
 * Il titolo breve dell'errore sta in post_title, il racconto dell'errore in
 * post_content.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_errore_register_cpt' );
function gs_errore_register_cpt() {
	register_post_type( 'gs_errore_didattico', array(
		'labels'       => array( 'name' => 'La Sfoglia che Insegna Se Stessa' ),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'title', 'editor', 'author' ),
	) );
}

function gs_errore_get( $id ) {
	return array(
		'id'      => $id,
		'titolo'  => get_the_title( $id ),
		'errore'  => get_post( $id ) ? get_post( $id )->post_content : '',
		'lezione' => (string) get_post_meta( $id, 'gs_lezione', true ),
		'stato'   => get_post_meta( $id, 'gs_stato', true ) ?: 'in_attesa',
		'autore'  => get_post( $id ) ? (int) get_post( $id )->post_author : 0,
	);
}

function gs_errori_promossi() {
	return gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_errore_didattico',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_key'       => 'gs_stato',
		'meta_value'     => 'promosso',
		'suppress_filters' => true,
	) ), 'gs_errore_didattico' );
}

function gs_errori_utente( $uid ) {
	$uid = (int) $uid;
	if ( ! $uid ) { return array(); }
	return gs_solo_tipo( gs_get_posts_by_author( array(
		'post_type'      => 'gs_errore_didattico',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'author'         => $uid,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'suppress_filters' => true,
	) ), 'gs_errore_didattico' );
}

function gs_errori_in_attesa() {
	return gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_errore_didattico',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'orderby'        => 'date',
		'order'          => 'ASC',
		'meta_key'       => 'gs_stato',
		'meta_value'     => 'in_attesa',
		'suppress_filters' => true,
	) ), 'gs_errore_didattico' );
}

// -----------------------------------------------------------------------------
// [gs_sfoglia_insegna] — lato sfoglina: archivio pubblico + invio
// -----------------------------------------------------------------------------
add_shortcode( 'gs_sfoglia_insegna', 'gs_sc_sfoglia_insegna' );
function gs_sc_sfoglia_insegna() {
	if ( $g = gs_gate_riservato() ) { return $g; }
	if ( $g = gs_blackout_gate() ) { return $g; }

	$out = gs_box_open( '🎓 La Sfoglia che Insegna Se Stessa' );
	$out .= gs_sezione_aiuto( 'Racconta onestamente un errore che hai fatto e cosa hai imparato: sbagliare fa parte del percorso. Se il titolare lo ritiene un errore comune e utile alle altre, lo promuove a materiale didattico ufficiale — con il credito a te, che l\'hai segnalato. Qui sotto trovi quelli già promossi.' );

	$promossi = gs_errori_promossi();
	if ( ! $promossi ) {
		$out .= '<p>Nessun errore promosso a materiale didattico, per ora.</p>';
	} else {
		$out .= '<div class="gs-inbox-lista gs-paginate" data-per-page="8">';
		foreach ( $promossi as $e ) {
			$c  = gs_errore_get( $e->ID );
			$au = get_userdata( $c['autore'] );
			$out .= '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">' . esc_html( $c['titolo'] ) . ' <span class="gs-msg-data">segnalato da ' . esc_html( $au ? $au->display_name : '—' ) . '</span></summary>';
			$out .= '<div class="gs-inbox-corpo">';
			$out .= '<p><strong>Cos\'è successo:</strong><br>' . nl2br( esc_html( $c['errore'] ) ) . '</p>';
			$out .= '<p><strong>Cosa ho imparato:</strong><br>' . nl2br( esc_html( $c['lezione'] ) ) . '</p>';
			$out .= '</div></details>';
		}
		$out .= '</div>';
	}
	$out .= gs_box_close();

	$out .= gs_box_open( '✏️ Racconta un tuo errore' );
	$out .= '<div class="gs-todo-riquadro">';
	$out .= '<form class="gs-form gs-form-errore-invia" onsubmit="return false">';
	$out .= '<p><label>Titolo breve<br><input type="text" name="titolo" autocomplete="off" style="width:100%" required></label></p>';
	$out .= '<p><label>Cos\'è successo<br><textarea name="errore" rows="3" style="width:100%"></textarea></label></p>';
	$out .= '<p><label>Cosa hai imparato<br><textarea name="lezione" rows="3" style="width:100%"></textarea></label></p>';
	$out .= '<p><button class="gs-btn gs-btn-sm gs-errore-invia">Invia</button> <span class="gs-errore-msg gs-richiesta-esito"></span></p>';
	$out .= '</form>';

	$mie = gs_errori_utente( get_current_user_id() );
	if ( $mie ) {
		$stati_it = array( 'in_attesa' => 'In attesa di valutazione', 'promosso' => 'Promosso a materiale didattico!', 'rifiutato' => 'Non promosso' );
		$out .= '<h4 style="margin-top:14px">I tuoi racconti inviati</h4>';
		$out .= '<div class="gs-inbox-lista gs-paginate" data-per-page="5">';
		foreach ( $mie as $e ) {
			$c = gs_errore_get( $e->ID );
			$out .= '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">' . esc_html( $c['titolo'] )
				. ' <span class="gs-msg-data">' . esc_html( $stati_it[ $c['stato'] ] ?? $c['stato'] ) . '</span></summary>';
			$out .= '<div class="gs-inbox-corpo"><p>' . nl2br( esc_html( $c['errore'] ) ) . '</p></div></details>';
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
add_action( 'wp_ajax_gs_errore_invia', 'gs_ajax_errore_invia' );
function gs_ajax_errore_invia() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }

	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	if ( '' === trim( $titolo ) ) { wp_send_json_error( array( 'message' => 'Scrivi un titolo breve.' ) ); }

	$errore  = isset( $_POST['errore'] ) ? sanitize_textarea_field( wp_unslash( $_POST['errore'] ) ) : '';
	$lezione = isset( $_POST['lezione'] ) ? sanitize_textarea_field( wp_unslash( $_POST['lezione'] ) ) : '';
	if ( function_exists( 'gs_msg_clean' ) ) {
		$errore  = gs_msg_clean( $errore );
		$lezione = gs_msg_clean( $lezione );
	}

	$eid = wp_insert_post( array(
		'post_type'    => 'gs_errore_didattico',
		'post_status'  => 'publish',
		'post_title'   => $titolo,
		'post_content' => $errore,
		'post_author'  => $uid,
	) );
	if ( is_wp_error( $eid ) || ! $eid ) { wp_send_json_error( array( 'message' => 'Errore nell\'invio.' ) ); }

	update_post_meta( $eid, 'gs_lezione', $lezione );
	update_post_meta( $eid, 'gs_stato', 'in_attesa' );

	if ( function_exists( 'gs_inbox_crea' ) ) {
		$u = get_userdata( $uid );
		gs_inbox_crea( 'Errore da valutare: ' . $titolo, ( $u ? $u->display_name : '' ) . ' ha raccontato un errore per "La Sfoglia che Insegna Se Stessa". Valuta se promuoverlo a materiale didattico.', array( 'from' => $u ? $u->display_name : 'Sfoglina' ) );
	}

	wp_send_json_success( array( 'message' => 'Racconto inviato: grazie per la sincerità.' ) );
}

// -----------------------------------------------------------------------------
// PANNELLO — valutazione (titolare)
// -----------------------------------------------------------------------------
function gs_pannello_sfoglia_insegna() {
	if ( ! gs_can_manage() ) { return; }

	echo gs_box_open( 'La Sfoglia che Insegna Se Stessa (valutazione)', '', 'gs-box-errore' );
	echo '<p class="gs-hint">Promuovi a materiale didattico ufficiale gli errori comuni e utili: compariranno nell\'archivio pubblico con il credito a chi li ha segnalati.</p>';

	$attesa = gs_errori_in_attesa();
	echo '<h4>In attesa di valutazione</h4>';
	if ( ! $attesa ) {
		echo '<p class="gs-hint">Nessun racconto in attesa.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-paginate" data-per-page="5">';
		foreach ( $attesa as $e ) {
			$c  = gs_errore_get( $e->ID );
			$au = get_userdata( $c['autore'] );
			echo '<details class="gs-inbox-item gs-non-letto gs-attesa-forte" data-errore="' . (int) $e->ID . '">';
			echo '<summary class="gs-inbox-oggetto"><span class="gs-dot"></span> ' . esc_html( $c['titolo'] ) . ' <span class="gs-msg-data">di ' . esc_html( $au ? $au->display_name : '—' ) . '</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			echo '<p><strong>Cos\'è successo:</strong><br>' . nl2br( esc_html( $c['errore'] ) ) . '</p>';
			echo '<p><strong>Cosa ha imparato:</strong><br>' . nl2br( esc_html( $c['lezione'] ) ) . '</p>';
			echo '<p><button class="gs-btn gs-btn-sm gs-errore-modera" data-errore="' . (int) $e->ID . '" data-esito="promosso">Promuovi a materiale didattico</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-errore-modera" data-errore="' . (int) $e->ID . '" data-esito="rifiutato">Non promuovere</button> ';
			echo '<span class="gs-errore-mod-msg gs-richiesta-esito"></span></p>';
			echo '</div></details>';
		}
		echo '</div>';
	}

	$promossi = gs_errori_promossi();
	echo '<h4 style="margin-top:14px">Promossi (visibili nell\'archivio)</h4>';
	if ( ! $promossi ) {
		echo '<p class="gs-hint">Nessun errore promosso ancora.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="5">';
		foreach ( $promossi as $e ) {
			$c  = gs_errore_get( $e->ID );
			$au = get_userdata( $c['autore'] );
			echo '<details class="gs-inbox-item" data-errore="' . (int) $e->ID . '">';
			echo '<summary class="gs-inbox-oggetto">' . esc_html( $c['titolo'] ) . ' <span class="gs-msg-data">di ' . esc_html( $au ? $au->display_name : '—' ) . '</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			echo '<p><button class="gs-btn gs-btn-sm gs-btn-ghost gs-errore-modera" data-errore="' . (int) $e->ID . '" data-esito="rifiutato">Togli dall\'archivio</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-errore-elimina" data-errore="' . (int) $e->ID . '">Elimina</button> ';
			echo '<span class="gs-errore-mod-msg gs-richiesta-esito"></span></p>';
			echo '</div></details>';
		}
		echo '</div>';
	}

	$trash = get_posts( array( 'post_type' => 'gs_errore_didattico', 'post_status' => 'trash', 'posts_per_page' => 50, 'suppress_filters' => true ) );
	echo '<div class="gs-sezione-cestino">';
	echo '<h4 class="gs-titolo-cestino">🗑️ Cestino</h4>';
	if ( ! $trash ) {
		echo '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		echo '<table class="gs-table gs-paginate" data-per-page="8"><thead><tr>' . gs_cestino_th_checkbox() . '<th>Racconto</th><th>Sfoglina</th><th></th></tr></thead><tbody>';
		foreach ( $trash as $e ) {
			$au = get_userdata( $e->post_author );
			echo '<tr data-errore="' . (int) $e->ID . '">' . gs_cestino_td_checkbox( $e->ID ) . '<td>' . esc_html( get_the_title( $e ) ) . '</td><td>' . esc_html( $au ? $au->display_name : '—' ) . '</td>';
			echo '<td><button class="gs-btn gs-btn-sm gs-btn-verde gs-errore-ripristina" data-errore="' . (int) $e->ID . '">Ripristina</button> <span class="gs-errore-trow-msg gs-richiesta-esito"></span></td></tr>';
		}
		echo '</tbody></table>';
		echo gs_cestino_azioni_bulk( 'gs_errore_didattico' );
	}
	echo '</div>';

	echo gs_box_close();
}

add_action( 'wp_ajax_gs_errore_modera', 'gs_ajax_errore_modera' );
function gs_ajax_errore_modera() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$eid   = isset( $_POST['errore'] ) ? (int) $_POST['errore'] : 0;
	$esito = isset( $_POST['esito'] ) ? sanitize_key( wp_unslash( $_POST['esito'] ) ) : 'rifiutato';
	if ( 'gs_errore_didattico' !== get_post_type( $eid ) ) { wp_send_json_error( array( 'message' => 'Racconto non valido.' ) ); }

	update_post_meta( $eid, 'gs_stato', 'promosso' === $esito ? 'promosso' : 'rifiutato' );
	$msg = 'promosso' === $esito ? 'Promosso a materiale didattico: ora è nell\'archivio pubblico.' : 'Non promosso (tolto dall\'archivio pubblico).';

	$au = get_userdata( get_post_field( 'post_author', $eid ) );
	if ( $au && function_exists( 'gs_mail_progetto' ) ) {
		$corpo = 'promosso' === $esito
			? "Ciao " . $au->display_name . ",\n\nil tuo racconto \"" . get_the_title( $eid ) . "\" è stato promosso a materiale didattico ufficiale, con il tuo credito, ed è ora visibile a tutte."
			: "Ciao " . $au->display_name . ",\n\nil tuo racconto \"" . get_the_title( $eid ) . "\" non è (più) nell'archivio pubblico. Grazie comunque per la sincerità.";
		gs_mail_progetto( $au->ID, 'messaggi', 'Il tuo racconto — Accademia della Sfoglia', $corpo );
	}
	wp_send_json_success( array( 'message' => $msg ) );
}

add_action( 'wp_ajax_gs_errore_elimina', 'gs_ajax_errore_elimina' );
function gs_ajax_errore_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$eid = isset( $_POST['errore'] ) ? (int) $_POST['errore'] : 0;
	if ( 'gs_errore_didattico' !== get_post_type( $eid ) ) { wp_send_json_error( array( 'message' => 'Racconto non valido.' ) ); }
	wp_trash_post( $eid );
	wp_send_json_success( array( 'message' => 'Spostato nel cestino.' ) );
}

add_action( 'wp_ajax_gs_errore_ripristina', 'gs_ajax_errore_ripristina' );
function gs_ajax_errore_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$eid = isset( $_POST['errore'] ) ? (int) $_POST['errore'] : 0;
	if ( 'gs_errore_didattico' !== get_post_type( $eid ) ) { wp_send_json_error( array( 'message' => 'Racconto non valido.' ) ); }
	wp_untrash_post( $eid );
	update_post_meta( $eid, 'gs_stato', 'in_attesa' );
	wp_send_json_success( array( 'message' => 'Ripristinato (torna in attesa di valutazione).' ) );
}
