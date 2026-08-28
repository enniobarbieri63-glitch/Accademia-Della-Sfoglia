<?php
/**
 * aiuto.php — Helper "Aiuto e Suggerimenti" sul pannello di ogni sfoglina.
 *
 * Ogni sfoglina, dal proprio pannello (dashboard), può:
 *  • chiedere aiuto diretto all'amministrazione;
 *  • inviare suggerimenti per migliorare il sito o le pubblicazioni.
 * Le richieste arrivano via email agli amministratori e sono gestibili dal
 * pannello (zona "Richieste di aiuto e suggerimenti").
 *
 * CPT gs_aiuto → meta: gs_tipo (aiuto|suggerimento), gs_stato (nuovo|gestito)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_aiuto_register_cpt' );
function gs_aiuto_register_cpt() {
	register_post_type( 'gs_aiuto', array(
		'labels'       => array( 'name' => 'Richieste di aiuto' ),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'editor', 'author' ),
	) );
}

/** Quanti messaggi di aiuto/suggerimento sono ancora "nuovi" (non gestiti). */
function gs_aiuto_count_nuovi() {
	$items = get_posts( array(
		'post_type'      => 'gs_aiuto',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'suppress_filters' => true,
	) );
	$n = 0;
	foreach ( $items as $id ) {
		$st = get_post_meta( $id, 'gs_stato', true ) ?: 'nuovo';
		if ( 'nuovo' === $st ) { $n++; }
	}
	return $n;
}

/** Email degli amministratori (riusa quella del calendario se disponibile). */
function gs_aiuto_admin_emails() {
	if ( function_exists( 'gs_cal_admin_emails' ) ) { return gs_cal_admin_emails(); }
	$e = array();
	if ( $a = get_option( 'admin_email' ) ) { $e[] = $a; }
	return $e;
}

// -----------------------------------------------------------------------------
// Box helper (mostrato nella dashboard della sfoglina)
// -----------------------------------------------------------------------------
add_shortcode( 'gs_aiuto_box', 'gs_sc_aiuto_box' );
function gs_sc_aiuto_box() {
	if ( ! is_user_logged_in() ) { return ''; }

	// Lista dei messaggi già inviati dalla sfoglina, calcolata subito: serve
	// anche per sapere se c'è qualche risposta non ancora vista, e far
	// lampeggiare in rosso il titolo del box (richiesto da Ennio il
	// 2026-07-29: "deve lampeggiare la casella messaggi dentro la mia sfoglia").
	$uid_corr = get_current_user_id();
	$miei = $uid_corr ? gs_solo_tipo( gs_get_posts_by_author( array(
		'post_type'      => 'gs_aiuto',
		'post_status'    => 'publish',
		'author'         => $uid_corr,
		'posts_per_page' => 30,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'suppress_filters' => true,
	) ), 'gs_aiuto' ) : array();
	$box_non_letto = false;
	foreach ( $miei as $it ) {
		if ( gs_aiuto_risposte_non_lette( $it->ID ) > 0 ) { $box_non_letto = true; break; }
	}

	$out  = gs_box_open( '🆘 Aiuto e Suggerimenti', $box_non_letto ? 'gs-lampeggia-rosso' : '' );
	$out .= gs_sezione_aiuto( 'Scegli se è una richiesta di aiuto o un suggerimento, scrivi il messaggio e invia: arriva subito a chi gestisce il portale. I messaggi che hai già inviato, con lo stato (in attesa/gestito), sono elencati più sotto con titolo cliccabile: se hanno ricevuto una risposta, la trovi subito sotto il tuo messaggio, a bolle come nelle Conversazioni, e puoi rispondere ancora da lì per continuare lo scambio. Il titolo di questo riquadro (e il messaggio interessato) lampeggiano in rosso quando c\'è una risposta che non hai ancora visto.' );
	$out .= '<p class="gs-hint">Hai bisogno di una mano dall\'amministrazione o vuoi proporre un\'idea per migliorare il sito o le pubblicazioni? Scrivici qui: riceveremo subito il tuo messaggio.</p>';
	$out .= '<div class="gs-todo-riquadro">';
	$out .= '<form class="gs-form gs-form-aiuto" onsubmit="return false">';
	if ( function_exists( 'gs_antispam_fields' ) ) { ob_start(); gs_antispam_fields(); $out .= ob_get_clean(); }
	$out .= '<p><label>Tipo di messaggio<br><select name="tipo">';
	$out .= '<option value="aiuto">Chiedo aiuto all\'amministrazione</option>';
	$out .= '<option value="suggerimento">Invio un suggerimento per migliorare</option>';
	$out .= '</select></label></p>';
	$out .= '<p><textarea name="testo" rows="3" style="width:100%" placeholder="Scrivi qui il tuo messaggio…"></textarea></p>';
	$out .= '<p>' . gs_msg_file_field() . '</p>';
	$out .= '<p><button class="gs-btn gs-btn-sm gs-aiuto-invia">Invia</button> <span class="gs-aiuto-msg gs-richiesta-esito"></span></p>';
	$out .= '</form>';

	// Lista dei messaggi già inviati dalla sfoglina (solo titolo, si apre al clic).
	if ( $miei ) {
		$stati = array( 'nuovo' => 'In attesa', 'gestito' => 'Gestito' );
		$out  .= '<h4 style="margin-top:14px">I tuoi messaggi inviati</h4>';
		$out  .= '<div class="gs-inbox-lista gs-paginate" data-per-page="5">';
		foreach ( $miei as $it ) {
			$tipo      = get_post_meta( $it->ID, 'gs_tipo', true );
			$st        = get_post_meta( $it->ID, 'gs_stato', true ) ?: 'nuovo';
			$label     = ( 'suggerimento' === $tipo ? '💡 Suggerimento' : '🆘 Aiuto' );
			$non_letto = gs_aiuto_risposte_non_lette( $it->ID ) > 0;
			// id="gs-aiuto-<ID>": l'avviso volante ("l'aeroplanino") che arriva
			// quando qualcuno risponde porta direttamente qui, aperto, invece
			// di lasciarla cercare il messaggio da sola tra tutti gli altri.
			$out  .= '<details class="gs-inbox-item' . ( $non_letto ? ' gs-lampeggia-rosso' : '' ) . '" id="gs-aiuto-' . (int) $it->ID . '"><summary class="gs-inbox-oggetto">' . ( $non_letto ? '<span class="gs-dot"></span> ' : '' ) . $label
				. ' <span class="gs-msg-data">' . esc_html( get_the_time( 'j/m/Y', $it ) ) . ' · ' . esc_html( $stati[ $st ] ?? $st ) . '</span></summary>';
			$out  .= '<div class="gs-inbox-corpo">' . gs_aiuto_messaggio_html( $it, true );
			$murl = get_post_meta( $it->ID, 'gs_media', true );
			if ( $murl && function_exists( 'gs_msg_media_html' ) ) { $out .= gs_msg_media_html( $murl, get_post_meta( $it->ID, 'gs_media_type', true ) ?: 'image' ); }
			$out .= gs_aiuto_risposte_html( $it->ID, false );
			$out .= '<form class="gs-form gs-form-aiuto-risposta" onsubmit="return false" style="margin-top:8px">';
			if ( function_exists( 'gs_antispam_fields' ) ) { ob_start(); gs_antispam_fields(); $out .= ob_get_clean(); }
			$out .= '<input type="hidden" name="aiuto" value="' . (int) $it->ID . '">';
			$out .= '<p><label>Rispondi<br><textarea name="testo" rows="2" style="width:100%" placeholder="Scrivi qui la tua risposta…"></textarea></label></p>';
			$out .= '<p><button class="gs-btn gs-btn-sm gs-aiuto-risposta-sfoglina-invia">Invia risposta</button> <span class="gs-aiuto-risposta-sfoglina-msg gs-richiesta-esito"></span></p>';
			$out .= '</form>';
			$out .= '</div></details>';
			// Segna come vista SOLO dopo averla mostrata (come in Messaggi):
			// al prossimo caricamento non lampeggia più, ma questa volta sì.
			if ( $non_letto ) { gs_aiuto_segna_risposte_lette( $it->ID ); }
		}
		$out .= '</div>';
	}
	$out .= '</div>';
	$out .= gs_box_close();
	return $out;
}

// Aggiunge il box in fondo alla dashboard della sfoglina, rispettando la
// visibilità impostata in "Visibilità delle sezioni e permessi" (la sezione
// "aiuto" non ha una pagina/shortcode propri, quindi va controllata qui a mano).
add_filter( 'do_shortcode_tag', 'gs_aiuto_append_dashboard', 20, 2 );
function gs_aiuto_append_dashboard( $output, $tag ) {
	if ( 'gs_dashboard' === $tag && is_user_logged_in() ) {
		if ( ! function_exists( 'gs_sez_visibile_per' ) || gs_sez_visibile_per( 'aiuto', get_current_user_id() ) ) {
			$output .= gs_sc_aiuto_box();
		}
	}
	return $output;
}

// gs_is_approved() da solo, SENZA gs_puo_partecipare(): «Aiuto e Suggerimenti»
// è come una sfoglina congelata chiede di rientrare — deve restare aperto
// anche da congelata, o il cancello si chiuderebbe dall'interno (documento
// dei trenta giorni, 26/08/2026).
add_action( 'wp_ajax_gs_aiuto_invia', 'gs_ajax_aiuto_invia' );
function gs_ajax_aiuto_invia() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_is_approved( $uid ) ) { wp_send_json_error( array( 'message' => 'Devi accedere.' ) ); }
	$tipo  = ( isset( $_POST['tipo'] ) && 'suggerimento' === $_POST['tipo'] ) ? 'suggerimento' : 'aiuto';
	$testo = isset( $_POST['testo'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) ) : '';
	if ( function_exists( 'gs_msg_clean' ) ) { $testo = gs_msg_clean( $testo ); }
	if ( mb_strlen( $testo ) < 3 ) { wp_send_json_error( array( 'message' => 'Scrivi il tuo messaggio.' ) ); }
	if ( function_exists( 'gs_antispam_check' ) ) {
		$check = gs_antispam_check( $_POST, 'aiuto_invia' );
		if ( is_wp_error( $check ) ) { wp_send_json_error( array( 'message' => $check->get_error_message() ) ); }
	}
	$media = function_exists( 'gs_msg_upload' ) ? gs_msg_upload( 'media' ) : null;
	if ( is_wp_error( $media ) ) { wp_send_json_error( array( 'message' => $media->get_error_message() ) ); }

	$aid = wp_insert_post( array(
		'post_type'    => 'gs_aiuto',
		'post_status'  => 'publish',
		'post_author'  => $uid,
		'post_title'   => ( 'suggerimento' === $tipo ? 'Suggerimento' : 'Richiesta di aiuto' ),
		'post_content' => $testo,
	) );
	if ( is_wp_error( $aid ) || ! $aid ) { wp_send_json_error( array( 'message' => 'Errore, riprova.' ) ); }
	update_post_meta( $aid, 'gs_tipo', $tipo );
	update_post_meta( $aid, 'gs_stato', 'nuovo' );
	if ( is_array( $media ) ) {
		update_post_meta( $aid, 'gs_media', $media['url'] );
		update_post_meta( $aid, 'gs_media_type', $media['type'] );
	}

	$u = get_userdata( $uid );
	$oggetto = ( 'suggerimento' === $tipo ) ? 'Nuovo suggerimento da una sfoglina' : 'Richiesta di aiuto da una sfoglina';
	$corpo   = ( $u ? $u->display_name . ' (' . $u->user_email . ')' : 'Una sfoglina' ) . " ha scritto:\n\n\"" . $testo . "\"\n\nGestisci dal pannello di controllo.";
	foreach ( gs_aiuto_admin_emails() as $to ) { wp_mail( $to, $oggetto, $corpo ); }
	if ( function_exists( 'gs_inbox_crea' ) ) {
		gs_inbox_crea( ( 'suggerimento' === $tipo ? 'Suggerimento da ' : 'Richiesta di aiuto da ' ) . ( $u ? $u->display_name : 'sfoglina' ), $testo, array( 'from' => $u ? $u->display_name : 'Sfoglina' ) );
	}

	wp_send_json_success( array( 'message' => ( 'suggerimento' === $tipo ? 'Grazie per il suggerimento!' : 'Messaggio inviato: ti risponderemo al più presto.' ) ) );
}

/**
 * Risposte già inviate per una richiesta: restano visibili nel pannello
 * (prima la risposta partiva solo via email/messaggio interno e spariva
 * dalla vista — se un collaboratore rispondeva, gli altri non lo sapevano).
 */
function gs_aiuto_risposte_get( $id ) {
	$r = get_post_meta( (int) $id, 'gs_aiuto_risposte', true );
	return is_array( $r ) ? $r : array();
}

function gs_aiuto_risposta_aggiungi( $id, $autore_uid, $testo ) {
	$id     = (int) $id;
	$testo  = trim( (string) $testo );
	if ( ! $id || ! $autore_uid || '' === $testo ) { return false; }
	$risposte   = gs_aiuto_risposte_get( $id );
	$risposte[] = array( 'autore' => (int) $autore_uid, 'testo' => $testo, 'data' => current_time( 'mysql' ) );
	update_post_meta( $id, 'gs_aiuto_risposte', $risposte );
	return true;
}

/**
 * Traccia, per la sfoglina, quante risposte del filone ha già visto: serve
 * a far lampeggiare in rosso il suo messaggio (e il titolo del box) quando
 * c'è qualcosa di nuovo da leggere (richiesto da Ennio il 2026-07-29).
 */
function gs_aiuto_risposte_non_lette( $id ) {
	$tot    = count( gs_aiuto_risposte_get( $id ) );
	$letti  = (int) get_post_meta( (int) $id, 'gs_aiuto_risp_lette', true );
	return max( 0, $tot - $letti );
}
function gs_aiuto_segna_risposte_lette( $id ) {
	update_post_meta( (int) $id, 'gs_aiuto_risp_lette', count( gs_aiuto_risposte_get( $id ) ) );
}

/**
 * Bolla del messaggio originale (richiesta/suggerimento), stesso schema
 * colore di Conversazioni e Messaggi privati (.gs-conv-msg / .mine) — mai
 * un colore nuovo, per restare coerenti in tutto il sito. $mine = true
 * quando chi guarda è l'autrice stessa (la sua bolla va a destra, verde).
 */
function gs_aiuto_messaggio_html( $it, $mine = false ) {
	$au    = get_userdata( $it->post_author );
	$nome  = $mine ? 'Tu' : ( $au ? $au->display_name : '—' );
	$out   = '<div class="gs-conv-thread"><div class="gs-conv-msg' . ( $mine ? ' mine' : '' ) . '">';
	$out  .= '<span class="gs-conv-from">' . esc_html( $nome ) . ':</span> ' . nl2br( esc_html( gs_msg_clean( $it->post_content ) ) );
	$out  .= '</div></div>';
	return $out;
}

/**
 * Bolle delle risposte già inviate. $vista_gestore = true (default): chi
 * guarda gestisce il portale, quindi le risposte (sue o di collaboratori)
 * sono "mine" (verdi, a destra). $vista_gestore = false: chi guarda è la
 * sfoglina stessa, le risposte sono "ricevute" (crema, a sinistra) e il suo
 * messaggio originale è "mine" — vedi gs_aiuto_messaggio_html().
 */
function gs_aiuto_risposte_html( $id, $vista_gestore = true ) {
	$risposte = gs_aiuto_risposte_get( $id );
	if ( ! $risposte ) { return ''; }
	$out = '<div class="gs-conv-thread" style="margin-top:8px">';
	foreach ( $risposte as $r ) {
		$au   = get_userdata( $r['autore'] );
		$out .= '<div class="gs-conv-msg' . ( $vista_gestore ? ' mine' : '' ) . '"><span class="gs-conv-from">' . esc_html( $au ? $au->display_name : '—' ) . ':</span> '
			. nl2br( esc_html( $r['testo'] ) ) . ' <span class="gs-msg-data">' . esc_html( date_i18n( 'j/m/Y H:i', strtotime( $r['data'] ) ) ) . '</span></div>';
	}
	$out .= '</div>';
	return $out;
}

// -----------------------------------------------------------------------------
// PANNELLO GESTORE — Richieste di aiuto e suggerimenti
// -----------------------------------------------------------------------------
function gs_pannello_aiuto() {
	if ( ! gs_can_manage() ) { return; }
	echo gs_box_open( 'Richieste di aiuto e suggerimenti', '', 'gs-box-aiuto' );
	echo '<p class="gs-hint">Le richieste arrivano dalle sfogline. La lista mostra solo il titolo: clicca per aprire e leggere il messaggio, poi puoi rispondere (via email e/o come messaggio interno, secondo le sue preferenze notifiche), segnarlo come gestito o eliminarlo. Il messaggio e le risposte compaiono a bolle come nelle Conversazioni: crema il suo messaggio, verde le risposte — restano visibili qui sotto, se un collaboratore ha già risposto lo vedono anche gli altri. Gli eliminati vanno nel cestino qui sotto e si possono ripristinare.</p>';
	$items = gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_aiuto',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'suppress_filters' => true,
	) ), 'gs_aiuto' );
	if ( ! $items ) {
		echo '<p class="gs-hint">Nessun messaggio al momento.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-paginate" data-per-page="8">';
		foreach ( $items as $it ) {
			$au    = get_userdata( $it->post_author );
			$tipo  = get_post_meta( $it->ID, 'gs_tipo', true );
			$st    = get_post_meta( $it->ID, 'gs_stato', true ) ?: 'nuovo';
			$label = ( 'suggerimento' === $tipo ? '💡 Suggerimento' : '🆘 Aiuto' ) . ' di ' . ( $au ? $au->display_name : '—' );
			echo '<details class="gs-inbox-item' . ( 'nuovo' === $st ? ' gs-non-letto' : '' ) . '" data-aiuto="' . (int) $it->ID . '">';
			echo '<summary class="gs-inbox-oggetto">' . ( 'nuovo' === $st ? '<span class="gs-dot"></span> ' : '' ) . esc_html( $label )
				. ' <span class="gs-msg-data">' . esc_html( get_the_time( 'j/m/Y H:i', $it ) ) . '</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			echo gs_aiuto_messaggio_html( $it, false );
			$murl = get_post_meta( $it->ID, 'gs_media', true );
			if ( $murl && function_exists( 'gs_msg_media_html' ) ) { echo gs_msg_media_html( $murl, get_post_meta( $it->ID, 'gs_media_type', true ) ?: 'image' ); }
			echo gs_aiuto_risposte_html( $it->ID, true );
			echo '<p style="margin-top:8px">';
			echo '<button class="gs-btn gs-btn-sm gs-aiuto-rispondi" data-aiuto="' . (int) $it->ID . '">Rispondi</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-aiuto-gestito" data-aiuto="' . (int) $it->ID . '">Segna gestito</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-aiuto-elimina" data-aiuto="' . (int) $it->ID . '">Elimina</button> ';
			echo '<span class="gs-aiuto-row-msg gs-richiesta-esito"></span></p>';
			echo '</div></details>';
		}
		echo '</div>';
	}

	// Cestino delle richieste (solo contenuti del progetto, ripristinabili).
	$trash = get_posts( array( 'post_type' => 'gs_aiuto', 'post_status' => 'trash', 'posts_per_page' => 50, 'suppress_filters' => true ) );
	echo '<div class="gs-sezione-cestino">';
	echo '<h4 class="gs-titolo-cestino">🗑️ Cestino richieste</h4>';
	if ( ! $trash ) {
		echo '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		echo '<table class="gs-table gs-paginate" data-per-page="8"><thead><tr>' . gs_cestino_th_checkbox() . '<th>Tipo</th><th>Sfoglina</th><th>Data</th><th></th></tr></thead><tbody>';
		foreach ( $trash as $it ) {
			$au = get_userdata( $it->post_author );
			echo '<tr data-aiuto="' . (int) $it->ID . '">' . gs_cestino_td_checkbox( $it->ID ) . '<td>' . ( 'suggerimento' === get_post_meta( $it->ID, 'gs_tipo', true ) ? '💡' : '🆘' ) . '</td>';
			echo '<td>' . esc_html( $au ? $au->display_name : '—' ) . '</td><td>' . esc_html( get_the_time( 'j/m/Y', $it ) ) . '</td>';
			echo '<td><button class="gs-btn gs-btn-sm gs-btn-verde gs-aiuto-ripristina" data-aiuto="' . (int) $it->ID . '">Ripristina</button> <span class="gs-aiuto-trow-msg gs-richiesta-esito"></span></td></tr>';
		}
		echo '</tbody></table>';
		echo gs_cestino_azioni_bulk( 'gs_aiuto' );
	}
	echo '</div>';
	echo gs_box_close();
}

add_action( 'wp_ajax_gs_aiuto_elimina', 'gs_ajax_aiuto_elimina' );
function gs_ajax_aiuto_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['aiuto'] ) ? (int) $_POST['aiuto'] : 0;
	if ( 'gs_aiuto' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Non valido.' ) ); }
	wp_trash_post( $id );
	wp_send_json_success( array( 'message' => 'Spostato nel cestino.' ) );
}

add_action( 'wp_ajax_gs_aiuto_ripristina', 'gs_ajax_aiuto_ripristina' );
function gs_ajax_aiuto_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['aiuto'] ) ? (int) $_POST['aiuto'] : 0;
	if ( 'gs_aiuto' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Non valido.' ) ); }
	wp_untrash_post( $id );
	update_post_meta( $id, 'gs_stato', 'nuovo' );
	wp_send_json_success( array( 'message' => 'Ripristinato: torna tra le richieste da leggere.' ) );
}

add_action( 'wp_ajax_gs_aiuto_gestito', 'gs_ajax_aiuto_gestito' );
function gs_ajax_aiuto_gestito() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['aiuto'] ) ? (int) $_POST['aiuto'] : 0;
	if ( 'gs_aiuto' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Non valido.' ) ); }
	update_post_meta( $id, 'gs_stato', 'gestito' );
	wp_send_json_success( array( 'message' => 'Segnato come gestito.' ) );
}

add_action( 'wp_ajax_gs_aiuto_rispondi', 'gs_ajax_aiuto_rispondi' );
function gs_ajax_aiuto_rispondi() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id    = isset( $_POST['aiuto'] ) ? (int) $_POST['aiuto'] : 0;
	$testo = isset( $_POST['testo'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) ) : '';
	if ( 'gs_aiuto' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Non valido.' ) ); }
	if ( '' === $testo ) { wp_send_json_error( array( 'message' => 'Scrivi la risposta.' ) ); }
	$post = get_post( $id );
	$u    = get_userdata( $post->post_author );
	if ( ! $u ) { wp_send_json_error( array( 'message' => 'Sfoglina non trovata.' ) ); }
	$oggetto = 'Risposta dall\'amministrazione — Accademia della Sfoglia';
	$corpo   = "Ciao " . $u->display_name . ",\n\n" . $testo . "\n\nAccademia della Sfoglia";
	$inviata = function_exists( 'gs_mail_progetto' )
		? gs_mail_progetto( $u->ID, 'messaggi', $oggetto, $corpo )
		: ( $u->user_email ? wp_mail( $u->user_email, $oggetto, $corpo ) : false );
	if ( ! $inviata ) {
		wp_send_json_error( array( 'message' => 'Nessun canale attivo per questa sfoglina (né email né messaggio interno): la risposta non è stata mandata. Controlla le sue preferenze notifiche.' ) );
	}
	gs_aiuto_risposta_aggiungi( $id, get_current_user_id(), $testo );
	update_post_meta( $id, 'gs_stato', 'gestito' );
	if ( function_exists( 'gs_accoda_volo' ) ) {
		// #gs-aiuto-<ID>: porta dritta al SUO messaggio, già aperto, non solo
		// alla pagina "La Mia Sfoglia" in generale (segnalato da Ennio il
		// 2026-07-29: cliccava l'aeroplanino e non capiva cosa doveva vedere).
		gs_accoda_volo( $post->post_author, 'LA TUA RICHIESTA DI AIUTO È STATA GESTITA', gs_pagina_url( 'gs_page_dashboard' ) . '#gs-aiuto-' . $id );
	}
	wp_send_json_success( array( 'message' => 'Risposta inviata.' ) );
}

/**
 * La sfoglina risponde nella SUA richiesta (prima poteva solo leggere la
 * risposta ricevuta, non continuare lo scambio — richiesto da Ennio il
 * 2026-07-29: "deve potere rispondere").
 */
add_action( 'wp_ajax_gs_aiuto_risposta_sfoglina', 'gs_ajax_aiuto_risposta_sfoglina' );
function gs_ajax_aiuto_risposta_sfoglina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_is_approved( $uid ) ) { wp_send_json_error( array( 'message' => 'Devi accedere.' ) ); }
	$id    = isset( $_POST['aiuto'] ) ? (int) $_POST['aiuto'] : 0;
	$testo = isset( $_POST['testo'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) ) : '';
	if ( function_exists( 'gs_msg_clean' ) ) { $testo = gs_msg_clean( $testo ); }
	if ( 'gs_aiuto' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Richiesta non valida.' ) ); }
	$post = get_post( $id );
	if ( ! $post || (int) $post->post_author !== $uid ) { wp_send_json_error( array( 'message' => 'Non è una tua richiesta.' ) ); }
	if ( mb_strlen( $testo ) < 1 ) { wp_send_json_error( array( 'message' => 'Scrivi un messaggio.' ) ); }
	if ( function_exists( 'gs_antispam_check' ) ) {
		$check = gs_antispam_check( $_POST, 'aiuto_risposta_sfoglina' );
		if ( is_wp_error( $check ) ) { wp_send_json_error( array( 'message' => $check->get_error_message() ) ); }
	}

	gs_aiuto_risposta_aggiungi( $id, $uid, $testo );
	gs_aiuto_segna_risposte_lette( $id ); // il suo stesso messaggio non deve segnarsi "da leggere" per lei
	update_post_meta( $id, 'gs_stato', 'nuovo' ); // torna tra le richieste da rivedere per chi gestisce

	$u = get_userdata( $uid );
	$oggetto = 'Nuovo messaggio su una richiesta di aiuto/suggerimento';
	$corpo   = ( $u ? $u->display_name . ' (' . $u->user_email . ')' : 'Una sfoglina' ) . " ha risposto:\n\n\"" . $testo . "\"\n\nGestisci dal pannello di controllo.";
	foreach ( gs_aiuto_admin_emails() as $to ) { wp_mail( $to, $oggetto, $corpo ); }
	if ( function_exists( 'gs_inbox_crea' ) ) {
		gs_inbox_crea( 'Risposta di ' . ( $u ? $u->display_name : 'una sfoglina' ) . ' su una richiesta', $testo, array( 'from' => $u ? $u->display_name : 'Sfoglina' ) );
	}
	wp_send_json_success( array( 'message' => 'Messaggio inviato.' ) );
}
