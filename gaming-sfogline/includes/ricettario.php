<?php
/**
 * ricettario.php — Ricettario delle Famiglie.
 *
 * Archivio permanente delle ricette tramandate: ogni sfoglina invia la sua
 * ricetta di famiglia dal proprio pannello; diventa visibile a tutte nel
 * Ricettario pubblico solo dopo l'approvazione del titolare (stesso
 * meccanismo già usato per la Biografia della Vetrina).
 *
 * CPT gs_ricetta → meta:
 *  - gs_regione      (provenienza)
 *  - gs_famiglia     (chi l'ha tramandata: nonna, famiglia, paese…)
 *  - gs_ingredienti  (testo libero)
 *  - gs_racconto     (la storia dietro alla ricetta)
 *  - gs_media        (array di {url,type})
 *  - gs_stato        ('in_attesa' | 'approvata' | 'rifiutata' | 'per_libro')
 *    'per_libro': scelta per la creazione di un libro cartaceo — NON entra
 *    nel Ricettario pubblico (a differenza di 'approvata'), resta visibile
 *    solo a titolare e collaboratori nella zona dedicata "Ricette per il
 *    Libro". Una ricetta può passare da 'per_libro' al Ricettario pubblico
 *    in qualsiasi momento (o viceversa), sono solo due destinazioni diverse
 *    per una ricetta comunque già approvata.
 *  - gs_regione_mappa (una delle 20 regioni italiane, facoltativa — usata
 *                       dalla Mappa dei Territori; distinta da gs_regione,
 *                       che è testo libero e può contenere anche l'estero)
 *  - gs_ricetta_padre (Genealogia delle Ricette: ID di un'altra ricetta
 *                       approvata da cui questa "nasce", facoltativo; 0 se
 *                       nessuna. Serve a costruire l'albero di discendenza:
 *                       ogni ricetta mostra da chi nasce e quali ricette
 *                       nascono da lei.)
 * Il procedimento sta in post_content, il nome della ricetta in post_title.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_ricetta_register_cpt' );
function gs_ricetta_register_cpt() {
	register_post_type( 'gs_ricetta', array(
		'labels'       => array( 'name' => 'Ricette di Famiglia' ),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'title', 'editor', 'author' ),
	) );
}

// -----------------------------------------------------------------------------
// Helper
// -----------------------------------------------------------------------------

function gs_ricetta_get( $id ) {
	$media = get_post_meta( $id, 'gs_media', true );
	return array(
		'id'          => $id,
		'titolo'      => get_the_title( $id ),
		'procedimento'=> get_post( $id ) ? get_post( $id )->post_content : '',
		'regione'     => (string) get_post_meta( $id, 'gs_regione', true ),
		'regione_mappa' => (string) get_post_meta( $id, 'gs_regione_mappa', true ),
		'famiglia'    => (string) get_post_meta( $id, 'gs_famiglia', true ),
		'ingredienti' => (string) get_post_meta( $id, 'gs_ingredienti', true ),
		'racconto'    => (string) get_post_meta( $id, 'gs_racconto', true ),
		'media'       => is_array( $media ) ? $media : array(),
		'stato'       => get_post_meta( $id, 'gs_stato', true ) ?: 'in_attesa',
		'autore'      => get_post( $id ) ? (int) get_post( $id )->post_author : 0,
		'padre'       => (int) get_post_meta( $id, 'gs_ricetta_padre', true ),
	);
}

/**
 * Genealogia delle Ricette: ricette approvate che indicano questa come
 * "ricetta madre" (gs_ricetta_padre). Restituisce un array di WP_Post.
 */
function gs_ricetta_figlie( $id ) {
	$id = (int) $id;
	if ( ! $id ) { return array(); }
	$figlie = array();
	foreach ( gs_ricette_approvate() as $r ) {
		if ( (int) get_post_meta( $r->ID, 'gs_ricetta_padre', true ) === $id ) {
			$figlie[] = $r;
		}
	}
	return $figlie;
}

/** Ricette approvate, visibili a tutte nel Ricettario pubblico. */
function gs_ricette_approvate() {
	return gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_ricetta',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'meta_key'       => 'gs_stato',
		'meta_value'     => 'approvata',
		'suppress_filters' => true,
	) ), 'gs_ricetta' );
}

/** Ricette inviate da una sfoglina (qualunque stato). */
function gs_ricette_utente( $uid ) {
	$uid = (int) $uid;
	if ( ! $uid ) { return array(); }
	return gs_solo_tipo( gs_get_posts_by_author( array(
		'post_type'      => 'gs_ricetta',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'author'         => $uid,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'suppress_filters' => true,
	) ), 'gs_ricetta' );
}

/** Ricette in attesa di approvazione. */
function gs_ricette_in_attesa() {
	return gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_ricetta',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'orderby'        => 'date',
		'order'          => 'ASC',
		'meta_key'       => 'gs_stato',
		'meta_value'     => 'in_attesa',
		'suppress_filters' => true,
	) ), 'gs_ricetta' );
}

/**
 * Ricette scelte per il libro: approvate ma tenute FUORI dal Ricettario
 * pubblico, visibili solo a titolare e collaboratori.
 */
function gs_ricette_per_libro() {
	return gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_ricetta',
		'post_status'    => 'publish',
		'posts_per_page' => 200,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'meta_key'       => 'gs_stato',
		'meta_value'     => 'per_libro',
		'suppress_filters' => true,
	) ), 'gs_ricetta' );
}

// -----------------------------------------------------------------------------
// [gs_ricettario] — lato sfoglina: ricettario pubblico + invio + le mie ricette
// -----------------------------------------------------------------------------
add_shortcode( 'gs_ricettario', 'gs_sc_ricettario' );
function gs_sc_ricettario() {
	if ( $g = gs_gate_riservato() ) { return $g; }
	if ( $g = gs_blackout_gate() ) { return $g; }

	$out = gs_box_open( '📖 Il Ricettario di Famiglia' );
	$out .= gs_sezione_aiuto( 'Usa la lente di ricerca per trovare una ricetta per nome, provenienza o famiglia. Per inviarne una nuova, compila il modulo qui sotto: le tue ricette inviate compaiono subito dopo, con lo stato aggiornato (in attesa, approvata, non approvata). Se indichi la regione italiana nel modulo, quando la ricetta è approvata colora quella regione sulla tua Mappa dei Territori, in cima alla pagina: pubblica una ricetta approvata per ognuna delle 20 regioni e, se sei la prima ad accenderle tutte, vinci 50 punti. In cima trovi anche la Ricetta del Mese, scelta dai maestri tra quelle approvate; sotto, un archivio con quelle dei mesi passati. Se la tua ricetta nasce da un\'altra già nel Ricettario (una variante, un\'evoluzione), puoi indicarlo nel modulo: comparirà "Nata da…" sotto la ricetta, e quella originale mostrerà a sua volta quali ricette sono nate da lei.' );
	$out .= '<p class="gs-hint">Le ricette tramandate dalle sfogline dell\'Accademia, raccolte in un unico archivio. Cerca per nome, provenienza o famiglia.</p>';

	if ( function_exists( 'gs_mappa_territori_html' ) ) {
		$out .= gs_mappa_territori_html( get_current_user_id() );
	}

	$del_mese = gs_ricetta_del_mese_corrente();
	if ( $del_mese ) {
		$c = gs_ricetta_get( $del_mese->ID );
		$out .= '<div class="gs-todo-riquadro">';
		$out .= '<p class="gs-hint"><strong>🌟 Ricetta del Mese — ' . esc_html( gs_mese_label( gs_mese_corrente() ) ) . '</strong></p>';
		$out .= '<p class="gs-hint" style="font-size:16px"><strong>' . esc_html( $c['titolo'] ) . '</strong>';
		if ( $c['regione'] || $c['famiglia'] ) {
			$out .= '<br>';
			if ( $c['regione'] ) { $out .= '📍 ' . esc_html( $c['regione'] ); }
			if ( $c['regione'] && $c['famiglia'] ) { $out .= ' · '; }
			if ( $c['famiglia'] ) { $out .= '👵 ' . esc_html( $c['famiglia'] ); }
		}
		$out .= '</p>';
		if ( $c['media'] ) {
			foreach ( $c['media'] as $m ) { $out .= gs_msg_media_html( $m['url'], isset( $m['type'] ) ? $m['type'] : 'image' ); }
		}
		if ( $c['ingredienti'] ) { $out .= '<p class="gs-hint"><strong>Ingredienti:</strong><br>' . nl2br( esc_html( $c['ingredienti'] ) ) . '</p>'; }
		if ( $c['procedimento'] ) { $out .= '<p class="gs-hint"><strong>Procedimento:</strong><br>' . nl2br( esc_html( $c['procedimento'] ) ) . '</p>'; }
		if ( $c['racconto'] ) { $out .= '<p class="gs-hint"><strong>La storia:</strong><br>' . nl2br( esc_html( $c['racconto'] ) ) . '</p>'; }
		$out .= '</div>';
	}

	$archivio = gs_ricette_mese_archivio();
	if ( $archivio ) {
		$out .= '<details class="gs-corso-descr"><summary>🗓️ Ricette del mese passate (' . count( $archivio ) . ')</summary>';
		$out .= '<div class="gs-gallery gs-paginate" data-per-page="6" style="margin-top:8px">';
		foreach ( $archivio as $r ) {
			$c    = gs_ricetta_get( $r->ID );
			$mese = get_post_meta( $r->ID, 'gs_mese_del_anno', true );
			$out .= '<div class="gs-card"><div class="gs-card-body">';
			$out .= '<p class="gs-card-author">' . esc_html( gs_mese_label( $mese ) ) . '</p>';
			$out .= '<h4>' . esc_html( $c['titolo'] ) . '</h4>';
			$out .= '<details class="gs-corso-descr"><summary>📖 Leggi la ricetta completa</summary>';
			if ( $c['ingredienti'] ) { $out .= '<p class="gs-hint" style="margin-top:6px"><strong>Ingredienti:</strong><br>' . nl2br( esc_html( $c['ingredienti'] ) ) . '</p>'; }
			if ( $c['procedimento'] ) { $out .= '<p class="gs-hint"><strong>Procedimento:</strong><br>' . nl2br( esc_html( $c['procedimento'] ) ) . '</p>'; }
			$out .= '</details>';
			$out .= '</div></div>';
		}
		$out .= '</div>';
		$out .= '</details>';
	}

	$approvate = gs_ricette_approvate();
	if ( ! $approvate ) {
		$out .= '<p>Non ci sono ancora ricette pubblicate. Sii la prima a inviarne una!</p>';
	} else {
		$out .= '<input type="text" class="gs-cerca-input" data-target=".gs-ricettario-lista" placeholder="🔍 Cerca per nome, regione o famiglia…" style="width:100%;max-width:420px;margin-bottom:10px">';
		$out .= '<div class="gs-gallery gs-ricettario-lista gs-paginate" data-per-page="9">';
		foreach ( $approvate as $r ) {
			$c = gs_ricetta_get( $r->ID );
			$nome_dato = gs_ricetta_search_string( $c );
			$out .= '<div class="gs-card" data-nome="' . esc_attr( $nome_dato ) . '"><div class="gs-card-body">';
			$out .= '<h4>' . esc_html( $c['titolo'] ) . '</h4>';
			if ( $c['regione'] || $c['famiglia'] ) {
				$out .= '<p class="gs-card-author">';
				if ( $c['regione'] ) { $out .= '📍 ' . esc_html( $c['regione'] ); }
				if ( $c['regione'] && $c['famiglia'] ) { $out .= ' · '; }
				if ( $c['famiglia'] ) { $out .= '👵 ' . esc_html( $c['famiglia'] ); }
				$out .= '</p>';
			}
			$out .= '<details class="gs-corso-descr"><summary>📖 Leggi la ricetta completa</summary>';
			if ( $c['ingredienti'] ) { $out .= '<p class="gs-hint" style="margin-top:6px"><strong>Ingredienti:</strong><br>' . nl2br( esc_html( $c['ingredienti'] ) ) . '</p>'; }
			if ( $c['procedimento'] ) { $out .= '<p class="gs-hint"><strong>Procedimento:</strong><br>' . nl2br( esc_html( $c['procedimento'] ) ) . '</p>'; }
			if ( $c['racconto'] ) { $out .= '<p class="gs-hint"><strong>La storia:</strong><br>' . nl2br( esc_html( $c['racconto'] ) ) . '</p>'; }
			if ( $c['media'] ) {
				foreach ( $c['media'] as $m ) { $out .= gs_msg_media_html( $m['url'], isset( $m['type'] ) ? $m['type'] : 'image' ); }
			}
			$out .= '</details>';
			if ( $c['padre'] && 'gs_ricetta' === get_post_type( $c['padre'] ) ) {
				$out .= '<p class="gs-hint">🌱 Nata da: <strong>' . esc_html( get_the_title( $c['padre'] ) ) . '</strong></p>';
			}
			$figlie = gs_ricetta_figlie( $c['id'] );
			if ( $figlie ) {
				$nomi_figlie = array();
				foreach ( $figlie as $f ) { $nomi_figlie[] = $f->post_title; }
				$out .= '<p class="gs-hint">🌿 Ricette nate da questa: <strong>' . esc_html( implode( ', ', $nomi_figlie ) ) . '</strong></p>';
			}
			$out .= '</div></div>';
		}
		$out .= '</div>';
	}
	$out .= gs_box_close();

	// Invio di una nuova ricetta.
	$out .= gs_box_open( '✏️ Invia la tua ricetta di famiglia' );
	$out .= '<p class="gs-hint"><strong>Queste ricette verranno vagliate dai nostri maestri, e quelle approvate le metteremo in un libro: ogni fonte della ricetta verrà citata.</strong></p>';
	$out .= '<div class="gs-todo-riquadro">';
	$out .= '<form class="gs-form gs-form-ricetta" onsubmit="return false">';
	$out .= '<p><label>Nome della ricetta<br><input type="text" name="titolo" autocomplete="off" style="width:100%" required></label></p>';
	$out .= '<p><label>Provenienza (regione e nazione)<br><input type="text" name="regione" autocomplete="off" style="width:100%"></label></p>';
	$out .= '<p><label>Regione italiana (per la Mappa dei Territori) — facoltativa<br><select name="regione_mappa"><option value="">— Non italiana / non indicata —</option>';
	foreach ( gs_regioni_italiane() as $reg ) { $out .= '<option value="' . esc_attr( $reg ) . '">' . esc_html( $reg ) . '</option>'; }
	$out .= '</select></label> <span class="gs-hint">Se questa ricetta arriva da una regione italiana precisa, sceglila qui: quando la ricetta sarà approvata colorerà quella regione sulla tua mappa personale.</span></p>';
	$out .= '<p><label>Chi te l\'ha tramandata (nonna, famiglia…)<br><input type="text" name="famiglia" autocomplete="off" style="width:100%"></label></p>';
	$out .= '<p><label>Ingredienti<br><textarea name="ingredienti" rows="3" style="width:100%"></textarea></label></p>';
	$out .= '<p><label>Procedimento<br><textarea name="procedimento" rows="4" style="width:100%"></textarea></label></p>';
	$out .= '<p><label>La storia dietro alla ricetta<br><textarea name="racconto" rows="3" style="width:100%" placeholder="Perché è importante per te, quando si prepara, un ricordo…"></textarea></label></p>';
	$out .= '<p><label>Nata da quale altra ricetta? — facoltativo<br><select name="ricetta_padre"><option value="0">— Nessuna, è una ricetta a sé —</option>';
	foreach ( gs_ricette_approvate() as $rp ) {
		$out .= '<option value="' . (int) $rp->ID . '">' . esc_html( get_the_title( $rp ) ) . '</option>';
	}
	$out .= '</select></label> <span class="gs-hint">Se questa ricetta è una variante o un\'evoluzione di una ricetta già nel Ricettario, indicalo qui: comparirà "Nata da…" sotto la ricetta.</span></p>';
	$out .= '<p>Aggiungi una foto: ' . gs_msg_file_field() . '</p>';
	$out .= '<p><button class="gs-btn gs-btn-sm gs-ricetta-invia">Invia per approvazione</button> <span class="gs-ricetta-msg gs-richiesta-esito"></span></p>';
	$out .= '</form>';

	// Le mie ricette inviate (qualunque stato).
	$mie = gs_ricette_utente( get_current_user_id() );
	if ( $mie ) {
		$stati_it = array( 'in_attesa' => 'In attesa di approvazione', 'approvata' => 'Approvata, visibile a tutte', 'rifiutata' => 'Non approvata' );
		$out .= '<h4 style="margin-top:14px">Le tue ricette inviate</h4>';
		$out .= '<div class="gs-inbox-lista gs-paginate" data-per-page="5">';
		foreach ( $mie as $r ) {
			$c = gs_ricetta_get( $r->ID );
			$out .= '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">' . esc_html( $c['titolo'] )
				. ' <span class="gs-msg-data">' . esc_html( $stati_it[ $c['stato'] ] ?? $c['stato'] ) . '</span></summary>';
			$out .= '<div class="gs-inbox-corpo"><div class="gs-inbox-testo">';
			if ( $c['ingredienti'] ) { $out .= '<strong>Ingredienti:</strong><br>' . nl2br( esc_html( $c['ingredienti'] ) ) . '<br><br>'; }
			if ( $c['procedimento'] ) { $out .= '<strong>Procedimento:</strong><br>' . nl2br( esc_html( $c['procedimento'] ) ); }
			$out .= '</div></div></details>';
		}
		$out .= '</div>';
	}
	$out .= '</div>';
	$out .= gs_box_close();

	return $out;
}

/** Stringa di ricerca (nome + regione + famiglia + ingredienti) per il filtro card. */
function gs_ricetta_search_string( $c ) {
	return trim( $c['titolo'] . ' ' . $c['regione'] . ' ' . $c['famiglia'] . ' ' . $c['ingredienti'] );
}

// -----------------------------------------------------------------------------
// Ricetta del Mese — una ricetta già approvata, messa in evidenza dal titolare
// per il mese corrente (meta gs_mese_del_anno, formato "Y-m").
// -----------------------------------------------------------------------------

/** Mese corrente in formato "Y-m" (es. 2026-07). */
function gs_mese_corrente() {
	return date_i18n( 'Y-m' );
}

/** Da "2026-07" a "Luglio 2026". */
function gs_mese_label( $ym ) {
	if ( ! preg_match( '/^(\d{4})-(\d{2})$/', (string) $ym, $m ) ) {
		return (string) $ym;
	}
	$mesi = array( 1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile', 5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto', 9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre' );
	return ( $mesi[ (int) $m[2] ] ?? $m[2] ) . ' ' . $m[1];
}

/** Ricette approvate a cui è stato assegnato un mese (qualunque). */
function gs_ricette_con_mese() {
	$out = array();
	foreach ( gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_ricetta',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'meta_key'       => 'gs_mese_del_anno',
		'suppress_filters' => true,
	) ), 'gs_ricetta' ) as $r ) {
		if ( 'approvata' === get_post_meta( $r->ID, 'gs_stato', true ) ) {
			$out[] = $r;
		}
	}
	return $out;
}

/** Ricetta del Mese per il mese corrente (approvata), o null se non ancora scelta. */
function gs_ricetta_del_mese_corrente() {
	$corrente = gs_mese_corrente();
	foreach ( gs_ricette_con_mese() as $r ) {
		if ( get_post_meta( $r->ID, 'gs_mese_del_anno', true ) === $corrente ) {
			return $r;
		}
	}
	return null;
}

/** Ricette del mese passate (mesi diversi da quello corrente), più recenti prima. */
function gs_ricette_mese_archivio() {
	$corrente = gs_mese_corrente();
	$righe    = array();
	foreach ( gs_ricette_con_mese() as $r ) {
		$mese = get_post_meta( $r->ID, 'gs_mese_del_anno', true );
		if ( $mese !== $corrente ) {
			$righe[] = array( 'post' => $r, 'mese' => $mese );
		}
	}
	usort( $righe, function ( $a, $b ) { return strcmp( $b['mese'], $a['mese'] ); } );
	return array_column( $righe, 'post' );
}

// -----------------------------------------------------------------------------
// AJAX lato sfoglina — invio ricetta
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_ricetta_invia', 'gs_ajax_ricetta_invia' );
function gs_ajax_ricetta_invia() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }

	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	if ( '' === trim( $titolo ) ) { wp_send_json_error( array( 'message' => 'Scrivi il nome della ricetta.' ) ); }

	$procedimento   = isset( $_POST['procedimento'] ) ? sanitize_textarea_field( wp_unslash( $_POST['procedimento'] ) ) : '';
	$regione        = isset( $_POST['regione'] ) ? sanitize_text_field( wp_unslash( $_POST['regione'] ) ) : '';
	$regione_mappa  = isset( $_POST['regione_mappa'] ) ? sanitize_text_field( wp_unslash( $_POST['regione_mappa'] ) ) : '';
	if ( ! in_array( $regione_mappa, gs_regioni_italiane(), true ) ) { $regione_mappa = ''; }
	$famiglia     = isset( $_POST['famiglia'] ) ? sanitize_text_field( wp_unslash( $_POST['famiglia'] ) ) : '';
	$ingredienti  = isset( $_POST['ingredienti'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ingredienti'] ) ) : '';
	$racconto     = isset( $_POST['racconto'] ) ? sanitize_textarea_field( wp_unslash( $_POST['racconto'] ) ) : '';
	$padre        = isset( $_POST['ricetta_padre'] ) ? (int) $_POST['ricetta_padre'] : 0;
	if ( $padre && ( 'gs_ricetta' !== get_post_type( $padre ) || 'approvata' !== get_post_meta( $padre, 'gs_stato', true ) ) ) { $padre = 0; }

	if ( function_exists( 'gs_msg_clean' ) ) {
		$procedimento = gs_msg_clean( $procedimento );
		$ingredienti  = gs_msg_clean( $ingredienti );
		$racconto     = gs_msg_clean( $racconto );
	}

	$rid = wp_insert_post( array(
		'post_type'    => 'gs_ricetta',
		'post_status'  => 'publish',
		'post_title'   => $titolo,
		'post_content' => $procedimento,
		'post_author'  => $uid,
	) );
	if ( is_wp_error( $rid ) || ! $rid ) { wp_send_json_error( array( 'message' => 'Errore nell\'invio.' ) ); }

	update_post_meta( $rid, 'gs_regione', $regione );
	if ( $regione_mappa ) { update_post_meta( $rid, 'gs_regione_mappa', $regione_mappa ); }
	update_post_meta( $rid, 'gs_famiglia', $famiglia );
	update_post_meta( $rid, 'gs_ingredienti', $ingredienti );
	update_post_meta( $rid, 'gs_racconto', $racconto );
	update_post_meta( $rid, 'gs_stato', 'in_attesa' );
	if ( $padre ) { update_post_meta( $rid, 'gs_ricetta_padre', $padre ); }

	if ( function_exists( 'gs_msg_upload' ) && ! empty( $_FILES['media']['name'] ) ) {
		$media = gs_msg_upload( 'media' );
		if ( is_wp_error( $media ) ) { wp_send_json_error( array( 'message' => $media->get_error_message() ) ); }
		if ( is_array( $media ) ) {
			update_post_meta( $rid, 'gs_media', array( array( 'url' => $media['url'], 'type' => $media['type'] ) ) );
		}
	}

	if ( function_exists( 'gs_inbox_crea' ) ) {
		$u = get_userdata( $uid );
		gs_inbox_crea( 'Ricetta da approvare: ' . $titolo, ( $u ? $u->display_name : '' ) . ' ha inviato una ricetta per il Ricettario delle Famiglie. Va approvata per comparire nell\'archivio pubblico.', array( 'from' => $u ? $u->display_name : 'Sfoglina', 'link_ancora' => 'admin.php?page=gs-generale#gs-zona-ricettario' ) );
	}

	wp_send_json_success( array( 'message' => 'Ricetta inviata per approvazione.' ) );
}

// -----------------------------------------------------------------------------
// PANNELLO — approvazione ricette (titolare)
// -----------------------------------------------------------------------------
function gs_pannello_ricettario() {
	if ( ! gs_can_manage() ) { return; }

	echo gs_box_open( 'Ricettario delle Famiglie (approvazione)', '', 'gs-box-ricettario' );
	echo '<p class="gs-hint">Le ricette compaiono nel Ricettario pubblico solo dopo l\'approvazione. Clicca sul titolo di una ricetta per aprirla e leggerla per intero (ingredienti, procedimento, storia, foto). Per ogni richiesta in attesa puoi: <strong>Approvare</strong> (diventa visibile al pubblico in tutto il sito, nel Ricettario pubblico), <strong>📕 Salvare per il libro</strong> (resta riservata a titolare e collaboratori, per un eventuale libro cartaceo — la trovi nella sezione "Ricette per il Libro"), oppure <strong>Rifiutare</strong>. Sotto ogni ricetta trovi anche il modulo per rispondere direttamente alla sfoglina che l\'ha pubblicata: il primo messaggio avvia una conversazione privata con lei. Qui sotto trovi anche le già approvate (che puoi togliere o spostare per il libro) e il cestino di quelle rifiutate/eliminate.</p>';

	$attesa = gs_ricette_in_attesa();
	echo '<h4>In attesa di approvazione</h4>';
	if ( ! $attesa ) {
		echo '<p class="gs-hint">Nessuna ricetta in attesa.</p>';
	} else {
		echo '<p><button class="gs-btn gs-btn-sm gs-btn-ghost gs-ricetta-svuota-attesa">🗑️ Sposta nel cestino TUTTE quelle in attesa (' . count( $attesa ) . ')</button> <span class="gs-ricetta-svuota-msg gs-richiesta-esito"></span></p>';
		echo '<div class="gs-inbox-lista gs-paginate" data-per-page="5">';
		foreach ( $attesa as $r ) {
			$c  = gs_ricetta_get( $r->ID );
			$au = get_userdata( $c['autore'] );
			echo '<details class="gs-inbox-item gs-non-letto gs-attesa-forte" data-ricetta="' . (int) $r->ID . '">';
			echo '<summary class="gs-inbox-oggetto"><span class="gs-dot"></span> ' . esc_html( $c['titolo'] )
				. ' <span class="gs-msg-data">di ' . esc_html( $au ? $au->display_name : '—' ) . '</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			if ( $c['regione'] ) { echo '<p><strong>Provenienza:</strong> ' . esc_html( $c['regione'] ) . '</p>'; }
			if ( $c['famiglia'] ) { echo '<p><strong>Tramandata da:</strong> ' . esc_html( $c['famiglia'] ) . '</p>'; }
			if ( $c['ingredienti'] ) { echo '<p><strong>Ingredienti:</strong><br>' . nl2br( esc_html( $c['ingredienti'] ) ) . '</p>'; }
			if ( $c['procedimento'] ) { echo '<p><strong>Procedimento:</strong><br>' . nl2br( esc_html( $c['procedimento'] ) ) . '</p>'; }
			if ( $c['racconto'] ) { echo '<p><strong>La storia:</strong><br>' . nl2br( esc_html( $c['racconto'] ) ) . '</p>'; }
			if ( $c['media'] ) { foreach ( $c['media'] as $m ) { echo gs_msg_media_html( $m['url'], isset( $m['type'] ) ? $m['type'] : 'image' ); } }
			echo '<p><button class="gs-btn gs-btn-sm gs-ricetta-modera" data-ricetta="' . (int) $r->ID . '" data-esito="approvata">Approva — nel Ricettario pubblico</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-ricetta-modera" data-ricetta="' . (int) $r->ID . '" data-esito="per_libro">📕 Salva per il libro</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-ricetta-modera" data-ricetta="' . (int) $r->ID . '" data-esito="rifiutata">Rifiuta</button> ';
			echo '<span class="gs-ricetta-mod-msg gs-richiesta-esito"></span></p>';
			if ( function_exists( 'gs_conv_avvia_rapida_html' ) ) { echo gs_conv_avvia_rapida_html( $c['autore'] ); }
			echo '</div></details>';
		}
		echo '</div>';
	}

	$approvate = gs_ricette_approvate();
	echo '<h4 style="margin-top:14px">Approvate (visibili nel Ricettario)</h4>';
	if ( ! $approvate ) {
		echo '<p class="gs-hint">Nessuna ricetta approvata.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="5">';
		$mese_corrente = gs_mese_corrente();
		foreach ( $approvate as $r ) {
			$c  = gs_ricetta_get( $r->ID );
			$au = get_userdata( $c['autore'] );
			$mese_ric = get_post_meta( $r->ID, 'gs_mese_del_anno', true );
			echo '<details class="gs-inbox-item" data-ricetta="' . (int) $r->ID . '">';
			echo '<summary class="gs-inbox-oggetto">' . ( $mese_ric === $mese_corrente ? '⭐ ' : '' ) . esc_html( $c['titolo'] ) . ' <span class="gs-msg-data">di ' . esc_html( $au ? $au->display_name : '—' ) . '</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			if ( $c['regione'] ) { echo '<p><strong>Provenienza:</strong> ' . esc_html( $c['regione'] ) . '</p>'; }
			if ( $c['famiglia'] ) { echo '<p><strong>Tramandata da:</strong> ' . esc_html( $c['famiglia'] ) . '</p>'; }
			if ( $c['ingredienti'] ) { echo '<p><strong>Ingredienti:</strong><br>' . nl2br( esc_html( $c['ingredienti'] ) ) . '</p>'; }
			if ( $c['procedimento'] ) { echo '<p><strong>Procedimento:</strong><br>' . nl2br( esc_html( $c['procedimento'] ) ) . '</p>'; }
			if ( $c['racconto'] ) { echo '<p><strong>La storia:</strong><br>' . nl2br( esc_html( $c['racconto'] ) ) . '</p>'; }
			if ( $c['media'] ) { foreach ( $c['media'] as $m ) { echo gs_msg_media_html( $m['url'], isset( $m['type'] ) ? $m['type'] : 'image' ); } }
			if ( $mese_ric === $mese_corrente ) {
				echo '<p><strong>⭐ Ricetta del Mese di ' . esc_html( gs_mese_label( $mese_ric ) ) . '</strong> — <button class="gs-btn gs-btn-sm gs-btn-ghost gs-ricetta-mese-rimuovi" data-ricetta="' . (int) $r->ID . '">Togli da Ricetta del Mese</button> <span class="gs-ricetta-mese-msg gs-richiesta-esito"></span></p>';
			} else {
				echo '<p><button class="gs-btn gs-btn-sm gs-ricetta-mese-imposta" data-ricetta="' . (int) $r->ID . '">⭐ Rendi Ricetta del Mese (' . esc_html( gs_mese_label( $mese_corrente ) ) . ')</button> <span class="gs-ricetta-mese-msg gs-richiesta-esito"></span></p>';
			}
			echo '<p><button class="gs-btn gs-btn-sm gs-ricetta-modera" data-ricetta="' . (int) $r->ID . '" data-esito="per_libro">📕 Salva per il libro</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-ricetta-modera" data-ricetta="' . (int) $r->ID . '" data-esito="rifiutata">Togli dal Ricettario</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-ricetta-elimina" data-ricetta="' . (int) $r->ID . '">Elimina</button> ';
			echo '<span class="gs-ricetta-mod-msg gs-richiesta-esito"></span></p>';
			if ( function_exists( 'gs_conv_avvia_rapida_html' ) ) { echo gs_conv_avvia_rapida_html( $c['autore'] ); }
			echo '</div></details>';
		}
		echo '</div>';
	}

	// Cestino.
	$trash = get_posts( array( 'post_type' => 'gs_ricetta', 'post_status' => 'trash', 'posts_per_page' => 50, 'suppress_filters' => true ) );
	echo '<div class="gs-sezione-cestino">';
	echo '<h4 class="gs-titolo-cestino">🗑️ Cestino</h4>';
	if ( ! $trash ) {
		echo '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		echo '<table class="gs-table gs-paginate" data-per-page="8"><thead><tr>' . gs_cestino_th_checkbox() . '<th>Ricetta</th><th>Sfoglina</th><th></th></tr></thead><tbody>';
		foreach ( $trash as $r ) {
			$au = get_userdata( $r->post_author );
			echo '<tr data-ricetta="' . (int) $r->ID . '">' . gs_cestino_td_checkbox( $r->ID ) . '<td>' . esc_html( get_the_title( $r ) ) . '</td><td>' . esc_html( $au ? $au->display_name : '—' ) . '</td>';
			echo '<td><button class="gs-btn gs-btn-sm gs-btn-verde gs-ricetta-ripristina" data-ricetta="' . (int) $r->ID . '">Ripristina</button> <span class="gs-ricetta-trow-msg gs-richiesta-esito"></span></td></tr>';
		}
		echo '</tbody></table>';
		echo gs_cestino_azioni_bulk( 'gs_ricetta' );
	}
	echo '</div>';

	echo gs_box_close();
}

// -----------------------------------------------------------------------------
// PANNELLO — Ricette per il Libro (riservato, titolare e collaboratori)
// -----------------------------------------------------------------------------
function gs_pannello_ricette_libro() {
	if ( ! gs_can_manage() ) { return; }

	echo gs_box_open( '📕 Ricette per il Libro', '', 'gs-box-ricette-libro' );
	echo gs_sezione_aiuto( 'Ricette di famiglia scelte per un eventuale libro cartaceo dell\'Accademia: restano riservate — non compaiono nel Ricettario pubblico, le vede solo chi gestisce il portale. Da qui puoi spostarle nel Ricettario pubblico in qualunque momento (diventa visibile al pubblico in tutto il sito), oppure rifiutarle. Sotto ogni ricetta trovi anche il modulo per rispondere direttamente alla sfoglina che l\'ha condivisa, per esempio per ringraziarla o chiederle qualche dettaglio in più: il messaggio avvia una conversazione privata con lei.' );

	$ricette = gs_ricette_per_libro();
	if ( ! $ricette ) {
		echo '<p class="gs-hint">Nessuna ricetta scelta per il libro al momento. Scegline una dal pulsante "📕 Salva per il libro" nella sezione "Ricettario delle Famiglie".</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="8">';
		foreach ( $ricette as $r ) {
			$c  = gs_ricetta_get( $r->ID );
			$au = get_userdata( $c['autore'] );
			echo '<details class="gs-inbox-item" data-ricetta="' . (int) $r->ID . '">';
			echo '<summary class="gs-inbox-oggetto">' . esc_html( $c['titolo'] ) . ' <span class="gs-msg-data">di ' . esc_html( $au ? $au->display_name : '—' ) . '</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			if ( $c['regione'] ) { echo '<p><strong>Provenienza:</strong> ' . esc_html( $c['regione'] ) . '</p>'; }
			if ( $c['famiglia'] ) { echo '<p><strong>Tramandata da:</strong> ' . esc_html( $c['famiglia'] ) . '</p>'; }
			if ( $c['ingredienti'] ) { echo '<p><strong>Ingredienti:</strong><br>' . nl2br( esc_html( $c['ingredienti'] ) ) . '</p>'; }
			if ( $c['procedimento'] ) { echo '<p><strong>Procedimento:</strong><br>' . nl2br( esc_html( $c['procedimento'] ) ) . '</p>'; }
			if ( $c['racconto'] ) { echo '<p><strong>La storia:</strong><br>' . nl2br( esc_html( $c['racconto'] ) ) . '</p>'; }
			if ( $c['media'] ) { foreach ( $c['media'] as $m ) { echo gs_msg_media_html( $m['url'], isset( $m['type'] ) ? $m['type'] : 'image' ); } }
			echo '<p><button class="gs-btn gs-btn-sm gs-ricetta-modera" data-ricetta="' . (int) $r->ID . '" data-esito="approvata">Sposta nel Ricettario pubblico</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-ricetta-modera" data-ricetta="' . (int) $r->ID . '" data-esito="rifiutata">Rifiuta</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-ricetta-elimina" data-ricetta="' . (int) $r->ID . '">Elimina</button> ';
			echo '<span class="gs-ricetta-mod-msg gs-richiesta-esito"></span></p>';
			if ( function_exists( 'gs_conv_avvia_rapida_html' ) ) { echo gs_conv_avvia_rapida_html( $c['autore'] ); }
			echo '</div></details>';
		}
		echo '</div>';
	}

	echo gs_box_close();
}

add_action( 'wp_ajax_gs_ricetta_svuota_attesa', 'gs_ajax_ricetta_svuota_attesa' );
function gs_ajax_ricetta_svuota_attesa() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$n = 0;
	foreach ( gs_ricette_in_attesa() as $r ) {
		wp_trash_post( $r->ID );
		$n++;
	}
	wp_send_json_success( array( 'message' => $n . ' ricette spostate nel cestino.' ) );
}

add_action( 'wp_ajax_gs_ricetta_modera', 'gs_ajax_ricetta_modera' );
function gs_ajax_ricetta_modera() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$rid   = isset( $_POST['ricetta'] ) ? (int) $_POST['ricetta'] : 0;
	$esito = isset( $_POST['esito'] ) ? sanitize_key( wp_unslash( $_POST['esito'] ) ) : 'rifiutata';
	if ( 'gs_ricetta' !== get_post_type( $rid ) ) { wp_send_json_error( array( 'message' => 'Ricetta non valida.' ) ); }
	if ( ! in_array( $esito, array( 'approvata', 'per_libro', 'rifiutata' ), true ) ) { $esito = 'rifiutata'; }

	update_post_meta( $rid, 'gs_stato', $esito );
	$messaggi = array(
		'approvata'  => 'Ricetta approvata: ora è nel Ricettario pubblico.',
		'per_libro'  => 'Ricetta salvata per il libro: resta riservata, non compare nel Ricettario pubblico.',
		'rifiutata'  => 'Ricetta non approvata (tolta dal Ricettario pubblico).',
	);
	$msg = $messaggi[ $esito ];

	// Il badge "Voce di Famiglia" premia la presenza nel Ricettario pubblico:
	// una ricetta scelta per il libro non ci entra, quindi non lo sblocca.
	if ( 'approvata' === $esito ) {
		do_action( 'gs_ricetta_approvata', (int) get_post_field( 'post_author', $rid ) );
		// Avviso Aeroplanino (punto 11, Ennio 21/08/2026: era uno dei "buchi"
		// trovati) — solo per 'approvata', non per 'per_libro' (resta
		// riservata) né 'rifiutata'.
		if ( function_exists( 'gs_accoda_volo' ) ) {
			gs_accoda_volo( (int) get_post_field( 'post_author', $rid ), 'LA TUA RICETTA È STATA APPROVATA! 📖', function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_ricettario' ) : '' );
		}
	}

	$au = get_userdata( get_post_field( 'post_author', $rid ) );
	if ( $au ) {
		$corpi = array(
			'approvata' => "Ciao " . $au->display_name . ",\n\nla tua ricetta \"" . get_the_title( $rid ) . "\" è stata approvata ed è ora visibile nel Ricettario delle Famiglie.",
			'per_libro' => "Ciao " . $au->display_name . ",\n\nla tua ricetta \"" . get_the_title( $rid ) . "\" è stata scelta per un libro dell'Accademia. Resta riservata (non compare nel Ricettario pubblico), grazie per averla condivisa!",
			'rifiutata' => "Ciao " . $au->display_name . ",\n\nla tua ricetta \"" . get_the_title( $rid ) . "\" non è (più) nel Ricettario pubblico. Puoi scrivere alla segreteria per saperne di più.",
		);
		$corpo_ricetta = $corpi[ $esito ];
		if ( function_exists( 'gs_mail_progetto' ) ) {
			gs_mail_progetto( $au->ID, 'messaggi', 'La tua ricetta — Accademia della Sfoglia', $corpo_ricetta );
		} elseif ( $au->user_email ) {
			wp_mail( $au->user_email, 'La tua ricetta — Accademia della Sfoglia', $corpo_ricetta );
		}
	}
	wp_send_json_success( array( 'message' => $msg ) );
}

add_action( 'wp_ajax_gs_ricetta_mese_imposta', 'gs_ajax_ricetta_mese_imposta' );
function gs_ajax_ricetta_mese_imposta() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$rid = isset( $_POST['ricetta'] ) ? (int) $_POST['ricetta'] : 0;
	if ( 'gs_ricetta' !== get_post_type( $rid ) ) { wp_send_json_error( array( 'message' => 'Ricetta non valida.' ) ); }

	$precedente = gs_ricetta_del_mese_corrente();
	if ( $precedente && (int) $precedente->ID !== $rid ) {
		delete_post_meta( $precedente->ID, 'gs_mese_del_anno' );
	}
	update_post_meta( $rid, 'gs_mese_del_anno', gs_mese_corrente() );
	wp_send_json_success( array( 'message' => 'Impostata come Ricetta del Mese di ' . gs_mese_label( gs_mese_corrente() ) . '.' ) );
}

add_action( 'wp_ajax_gs_ricetta_mese_rimuovi', 'gs_ajax_ricetta_mese_rimuovi' );
function gs_ajax_ricetta_mese_rimuovi() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$rid = isset( $_POST['ricetta'] ) ? (int) $_POST['ricetta'] : 0;
	if ( 'gs_ricetta' !== get_post_type( $rid ) ) { wp_send_json_error( array( 'message' => 'Ricetta non valida.' ) ); }
	delete_post_meta( $rid, 'gs_mese_del_anno' );
	wp_send_json_success( array( 'message' => 'Rimossa da Ricetta del Mese.' ) );
}

add_action( 'wp_ajax_gs_ricetta_elimina', 'gs_ajax_ricetta_elimina' );
function gs_ajax_ricetta_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$rid = isset( $_POST['ricetta'] ) ? (int) $_POST['ricetta'] : 0;
	if ( 'gs_ricetta' !== get_post_type( $rid ) ) { wp_send_json_error( array( 'message' => 'Ricetta non valida.' ) ); }
	wp_trash_post( $rid );
	wp_send_json_success( array( 'message' => 'Spostata nel cestino.' ) );
}

add_action( 'wp_ajax_gs_ricetta_ripristina', 'gs_ajax_ricetta_ripristina' );
function gs_ajax_ricetta_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$rid = isset( $_POST['ricetta'] ) ? (int) $_POST['ricetta'] : 0;
	if ( 'gs_ricetta' !== get_post_type( $rid ) ) { wp_send_json_error( array( 'message' => 'Ricetta non valida.' ) ); }
	wp_untrash_post( $rid );
	update_post_meta( $rid, 'gs_stato', 'in_attesa' );
	wp_send_json_success( array( 'message' => 'Ricetta ripristinata (torna in attesa di approvazione).' ) );
}
