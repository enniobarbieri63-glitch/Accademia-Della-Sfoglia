<?php
/**
 * tavolo.php — Il Tavolo di Lavoro: la foto del giorno, con un commento
 * diretto di un maestro.
 *
 * Diverso dal Ricettario (archivio pubblico di ricette): qui la sfoglina
 * carica una foto del suo lavoro di oggi (la sfoglia tirata, un piatto, un
 * dettaglio), un pezzo alla volta, e la vede solo lei — insieme al
 * commento che un maestro le lascia apposta. È pensato per essere il gesto
 * più semplice e più personale del sito: non un archivio da consultare, ma
 * un piccolo rituale quotidiano con un riscontro umano vero.
 *
 * CPT gs_tavolo → meta:
 *  - gs_media         ({url, type}, obbligatorio)
 *  - gs_didascalia    (testo facoltativo della sfoglina)
 *  - gs_commento      (il riscontro del maestro, vuoto finché non arriva)
 *  - gs_commento_data (quando è stato lasciato)
 * post_author = la sfoglina, post_date = il giorno della foto. Le foto sono
 * libere (Ennio, 26/08/2026: tolto il limite di una al giorno): niente
 * approvazione, è privata fra la sfoglina e chi gestisce il portale, non
 * finisce nella Galleria pubblica. I punti del gioco restano una volta al
 * giorno (vedi gs_tavolo_punti_giorno in gs_ajax_tavolo_invia()): le due
 * cose non sono più la stessa regola.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_tavolo_register_cpt' );
function gs_tavolo_register_cpt() {
	register_post_type( 'gs_tavolo', array(
		'labels'       => array( 'name' => 'Il Tavolo di Lavoro' ),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'author' ),
	) );
}

// -----------------------------------------------------------------------------
// Helper
// -----------------------------------------------------------------------------

function gs_tavolo_get( $id ) {
	$media = get_post_meta( $id, 'gs_media', true );
	return array(
		'id'            => $id,
		'autore'        => get_post( $id ) ? (int) get_post( $id )->post_author : 0,
		'data'          => get_post( $id ) ? get_post( $id )->post_date : '',
		'media'         => is_array( $media ) ? $media : null,
		'didascalia'    => (string) get_post_meta( $id, 'gs_didascalia', true ),
		'commento'      => (string) get_post_meta( $id, 'gs_commento', true ),
		'commento_data' => (string) get_post_meta( $id, 'gs_commento_data', true ),
	);
}

/** La foto di oggi di una sfoglina, o null se non ha ancora caricato nulla oggi (in base alla sua foto più recente). */
function gs_tavolo_di_oggi( $uid ) {
	$oggi   = date( 'Y-m-d', current_time( 'timestamp' ) );
	$ultimi = gs_get_posts_by_author( array(
		'post_type'      => 'gs_tavolo',
		'post_status'    => 'publish',
		'author'         => $uid,
		'posts_per_page' => 1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'suppress_filters' => true,
	) );
	if ( ! $ultimi ) { return null; }
	return substr( $ultimi[0]->post_date, 0, 10 ) === $oggi ? $ultimi[0]->ID : null;
}

/** Tutte le foto caricate oggi da una sfoglina (le più recenti prima). */
function gs_tavolo_di_oggi_tutte( $uid ) {
	$oggi = date( 'Y-m-d', current_time( 'timestamp' ) );
	return array_values( array_filter( gs_tavolo_tutti_di( $uid, 50 ), function ( $p ) use ( $oggi ) {
		return substr( $p->post_date, 0, 10 ) === $oggi;
	} ) );
}

/** Le foto passate di una sfoglina (le più recenti prima), per la sua vista personale. */
function gs_tavolo_tutti_di( $uid, $limite = 20 ) {
	return gs_solo_tipo( gs_get_posts_by_author( array(
		'post_type'      => 'gs_tavolo',
		'post_status'    => 'publish',
		'author'         => $uid,
		'posts_per_page' => $limite,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'suppress_filters' => true,
	) ), 'gs_tavolo' );
}

/** Tutte le foto, per il pannello del gestore: quelle senza commento prima, poi le più recenti. */
function gs_tavolo_tutti() {
	$tutti = gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_tavolo',
		'post_status'    => 'publish',
		'posts_per_page' => 200,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'suppress_filters' => true,
	) ), 'gs_tavolo' );
	usort( $tutti, function ( $a, $b ) {
		$sa = '' === get_post_meta( $a->ID, 'gs_commento', true );
		$sb = '' === get_post_meta( $b->ID, 'gs_commento', true );
		if ( $sa !== $sb ) { return $sa ? -1 : 1; } // senza commento prima
		return strtotime( $b->post_date ) <=> strtotime( $a->post_date );
	} );
	return $tutti;
}

/** Quante foto aspettano ancora un commento (per la Bacheca di riepilogo). */
function gs_tavolo_totale_senza_commento() {
	$n = 0;
	foreach ( gs_tavolo_tutti() as $t ) {
		if ( '' === get_post_meta( $t->ID, 'gs_commento', true ) ) { $n++; }
	}
	return $n;
}

// -----------------------------------------------------------------------------
// [gs_tavolo] — lato sfoglina
// -----------------------------------------------------------------------------
add_shortcode( 'gs_tavolo', 'gs_sc_tavolo' );
function gs_sc_tavolo() {
	if ( $g = gs_gate_riservato() ) { return $g; }
	if ( $g = gs_blackout_gate() ) { return $g; }

	$out = gs_box_open( '🍳 Il Tavolo di Lavoro' );
	$out .= gs_sezione_aiuto( 'Carica una foto del tuo lavoro di oggi — la sfoglia tirata, un piatto, un dettaglio — non serve che sia perfetta. È privata: la vedi solo tu, insieme al commento che un maestro ti lascerà apposta appena la vede. Puoi caricarne quante vuoi: ogni foto avrà il suo commento. I punti del gioco si prendono una volta al giorno, ma le foto no — carica pure quando ti va.' );

	$uid     = get_current_user_id();
	$di_oggi = gs_tavolo_di_oggi_tutte( $uid );

	$out .= '<div class="gs-todo-riquadro">';

	// Il modulo resta SEMPRE disponibile: le foto non hanno più un limite
	// giornaliero (Ennio, 26/08/2026). Prima stava nell'"else" — tolto il
	// blocco lato server senza toccare questo, la seconda foto non si
	// sarebbe comunque potuta caricare, perché il modulo era già sparito.
	$out .= '<form class="gs-form gs-form-tavolo" onsubmit="return false">';
	$out .= '<p>Aggiungi una foto: ' . gs_msg_file_field() . '</p>';
	$out .= '<p><label>Una riga per raccontarla (facoltativa)<br><input type="text" name="didascalia" autocomplete="off" style="width:100%" placeholder="Es. Prima volta con i pizzicati…"></label></p>';
	$out .= '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-tavolo-invia">Carica la foto</button> <span class="gs-tavolo-msg gs-richiesta-esito"></span></p>';
	$out .= '</form>';

	if ( $di_oggi ) {
		$out .= '<p class="gs-hint" style="font-size:16px;margin-top:14px"><strong>'
			. ( count( $di_oggi ) > 1 ? 'Le tue foto di oggi' : 'La tua foto di oggi' ) . '</strong></p>';
		foreach ( $di_oggi as $p ) {
			$c = gs_tavolo_get( $p->ID );
			if ( $c['media'] ) { $out .= gs_msg_media_html( $c['media']['url'], isset( $c['media']['type'] ) ? $c['media']['type'] : 'image' ); }
			if ( $c['didascalia'] ) { $out .= '<p class="gs-hint">' . esc_html( $c['didascalia'] ) . '</p>'; }
			$out .= $c['commento']
				? '<p class="gs-hint"><strong>💬 Un maestro ha scritto:</strong><br>' . nl2br( esc_html( $c['commento'] ) ) . '</p>'
				: '<p class="gs-hint">In attesa del commento di un maestro.</p>';
		}
	}
	$out .= '</div>';

	$ids_oggi = array_map( function ( $p ) { return $p->ID; }, $di_oggi );
	$passate  = array_values( array_filter( gs_tavolo_tutti_di( $uid ), function ( $p ) use ( $ids_oggi ) { return ! in_array( $p->ID, $ids_oggi, true ); } ) );
	if ( $passate ) {
		$out .= '<h4 style="margin-top:14px">Le tue foto passate</h4>';
		$out .= '<div class="gs-gallery gs-paginate" data-per-page="6">';
		foreach ( $passate as $p ) {
			$c = gs_tavolo_get( $p->ID );
			$out .= '<div class="gs-card"><div class="gs-card-body">';
			$out .= '<p class="gs-card-author">' . esc_html( date_i18n( 'j F Y', strtotime( $c['data'] ) ) ) . '</p>';
			if ( $c['media'] ) { $out .= gs_msg_media_html( $c['media']['url'], isset( $c['media']['type'] ) ? $c['media']['type'] : 'image' ); }
			if ( $c['didascalia'] ) { $out .= '<p class="gs-hint">' . esc_html( $c['didascalia'] ) . '</p>'; }
			$out .= $c['commento'] ? '<p class="gs-hint"><strong>💬</strong> ' . nl2br( esc_html( $c['commento'] ) ) . '</p>' : '<p class="gs-hint">In attesa del commento di un maestro.</p>';
			$out .= '</div></div>';
		}
		$out .= '</div>';
	}

	$out .= gs_box_close();
	return $out;
}

add_action( 'wp_ajax_gs_tavolo_invia', 'gs_ajax_tavolo_invia' );
function gs_ajax_tavolo_invia() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }
	if ( empty( $_FILES['media']['name'] ) ) { wp_send_json_error( array( 'message' => 'Scegli una foto da caricare.' ) ); }

	$media = gs_msg_upload( 'media' );
	if ( is_wp_error( $media ) ) { wp_send_json_error( array( 'message' => $media->get_error_message() ) ); }
	if ( ! is_array( $media ) ) { wp_send_json_error( array( 'message' => 'Errore nel caricamento della foto.' ) ); }

	$didascalia = isset( $_POST['didascalia'] ) ? sanitize_text_field( wp_unslash( $_POST['didascalia'] ) ) : '';
	if ( function_exists( 'gs_msg_clean' ) ) { $didascalia = gs_msg_clean( $didascalia ); }

	$tid = wp_insert_post( array(
		'post_type'   => 'gs_tavolo',
		'post_status' => 'publish',
		'post_author' => $uid,
	) );
	if ( is_wp_error( $tid ) || ! $tid ) { wp_send_json_error( array( 'message' => 'Errore nel salvataggio.' ) ); }

	update_post_meta( $tid, 'gs_media', array( 'url' => $media['url'], 'type' => $media['type'] ) );
	update_post_meta( $tid, 'gs_didascalia', $didascalia );

	// I punti restano UNA volta al giorno, anche se le foto adesso sono
	// libere (Ennio, 26/08/2026: "togliamo il limite della foto
	// giornaliera"). Le due cose erano una sola per effetto collaterale, non
	// per scelta: senza questa distinzione, 20 foto al giorno per 30 giorni
	// fanno 3.000 punti contro i 2.500 della soglia del Buono Sfoglia, e il
	// Buono si vincerebbe caricando foto invece che facendo sfoglia.
	//
	// Il contrassegno è un meta con la data, NON una ricerca fra i post: il
	// vecchio blocco si appoggiava a gs_tavolo_di_oggi(), ed è proprio
	// quella ricerca che il tema ha alterato in silenzio per settimane
	// (vedi gs_get_posts_by_author in helpers.php) lasciando i punti senza
	// alcun freno. Un contrassegno diretto non dipende da nessuna query e
	// non può rompersi allo stesso modo.
	$oggi_ymd = current_time( 'Y-m-d' );
	if ( get_user_meta( $uid, 'gs_tavolo_punti_giorno', true ) !== $oggi_ymd ) {
		update_user_meta( $uid, 'gs_tavolo_punti_giorno', $oggi_ymd );
		gs_add_points( $uid, gs_get_points_value( 'tavolo_foto', 5 ), 'Il Tavolo di Lavoro: foto del giorno' );
	}

	// Un avviso al giorno per sfoglina, non uno per foto: con le foto libere
	// (26/08/2026) dieci caricamenti farebbero dieci avvisi identici nella
	// stessa casella dove arrivano le disdette con acconto da restituire e i
	// rendiconti di fine mese. Il pannello del Tavolo mostra comunque tutte
	// le foto, con quelle senza commento in cima.
	if ( function_exists( 'gs_inbox_crea' )
		&& get_user_meta( $uid, 'gs_tavolo_avviso_giorno', true ) !== $oggi_ymd ) {
		update_user_meta( $uid, 'gs_tavolo_avviso_giorno', $oggi_ymd );
		$u = get_userdata( $uid );
		gs_inbox_crea(
			'Nuove foto sul Tavolo di Lavoro',
			( $u ? $u->display_name : '' ) . ' ha caricato una o più foto oggi.',
			array( 'from' => $u ? $u->display_name : 'Sfoglina' )
		);
	}

	wp_send_json_success( array( 'message' => 'Foto caricata! Un maestro la vedrà presto.' ) );
}

// -----------------------------------------------------------------------------
// PANNELLO — feed di tutte le foto, con spazio per il commento (gestore)
// -----------------------------------------------------------------------------
function gs_pannello_tavolo() {
	if ( ! gs_can_manage() ) { return; }

	echo gs_box_open( '🍳 Il Tavolo di Lavoro', '', 'gs-box-tavolo' );
	echo '<p class="gs-hint">Le foto del giorno caricate dalle sfogline, private fra loro e voi: quelle senza commento stanno in cima. Scrivi un riscontro breve e personale — è il gesto che le fa tornare più volentieri.</p>';

	$tutti = gs_tavolo_tutti();
	if ( ! $tutti ) {
		echo '<p class="gs-hint">Nessuna foto ancora caricata.</p>';
	} else {
		echo '<div class="gs-gallery gs-paginate" data-per-page="8">';
		foreach ( $tutti as $t ) {
			$c  = gs_tavolo_get( $t->ID );
			$au = get_userdata( $c['autore'] );
			echo '<div class="gs-card" data-tavolo="' . (int) $t->ID . '"><div class="gs-card-body">';
			echo '<p class="gs-card-author">' . esc_html( $au ? $au->display_name : '—' ) . ' — ' . esc_html( date_i18n( 'j F Y', strtotime( $c['data'] ) ) ) . '</p>';
			if ( $c['media'] ) { echo gs_msg_media_html( $c['media']['url'], isset( $c['media']['type'] ) ? $c['media']['type'] : 'image' ); }
			if ( $c['didascalia'] ) { echo '<p class="gs-hint">' . esc_html( $c['didascalia'] ) . '</p>'; }
			echo '<textarea class="gs-tavolo-commento-input" rows="2" style="width:100%" placeholder="Il tuo commento per lei…">' . esc_textarea( $c['commento'] ) . '</textarea>';
			echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-tavolo-commento-salva" data-tavolo="' . (int) $t->ID . '">Salva commento</button> <span class="gs-tavolo-commento-msg gs-richiesta-esito"></span></p>';
			echo '</div></div>';
		}
		echo '</div>';
	}

	echo gs_box_close();
}

add_action( 'wp_ajax_gs_tavolo_commento_salva', 'gs_ajax_tavolo_commento_salva' );
function gs_ajax_tavolo_commento_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$tid = isset( $_POST['tavolo'] ) ? (int) $_POST['tavolo'] : 0;
	if ( 'gs_tavolo' !== get_post_type( $tid ) ) { wp_send_json_error( array( 'message' => 'Foto non valida.' ) ); }
	$commento = isset( $_POST['commento'] ) ? sanitize_textarea_field( wp_unslash( $_POST['commento'] ) ) : '';
	if ( function_exists( 'gs_msg_clean' ) ) { $commento = gs_msg_clean( $commento ); }

	$era_vuoto = '' === get_post_meta( $tid, 'gs_commento', true );
	update_post_meta( $tid, 'gs_commento', $commento );
	update_post_meta( $tid, 'gs_commento_data', current_time( 'mysql' ) );

	// Avvisa la sfoglina solo la prima volta che arriva un commento (non a ogni piccola correzione successiva).
	if ( $era_vuoto && $commento ) {
		$target_uid = (int) get_post( $tid )->post_author;
		$au = get_userdata( $target_uid );
		if ( $au ) {
			$oggetto_tv = 'Un maestro ha commentato la tua foto! — Accademia della Sfoglia';
			$corpo_tv   = "Ciao " . $au->display_name . ",\n\nun maestro ha visto la tua foto sul Tavolo di Lavoro e ti ha lasciato un commento:\n\n" . $commento;
			if ( function_exists( 'gs_mail_progetto' ) ) {
				gs_mail_progetto( $target_uid, 'messaggi', $oggetto_tv, $corpo_tv );
			} elseif ( $au->user_email ) {
				wp_mail( $au->user_email, $oggetto_tv, $corpo_tv );
			}
		}
		if ( function_exists( 'gs_accoda_volo' ) ) {
			gs_accoda_volo( $target_uid, 'UN MAESTRO HA COMMENTATO LA TUA FOTO DI OGGI 🍳', gs_pagina_url( 'gs_page_tavolo' ) );
		}
	}

	wp_send_json_success( array( 'message' => 'Commento salvato.' ) );
}
