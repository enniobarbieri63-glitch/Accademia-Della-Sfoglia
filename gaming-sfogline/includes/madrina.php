<?php
/**
 * madrina.php — Madrina & Allieva: abbina una sfoglina esperta (madrina) a
 * una principiante (allieva) per un periodo di affiancamento, con
 * mini-missioni condivise che le due possono aggiungere e segnare fatte
 * insieme. Un solo Custom Post Type (gs_abbinamento), nessuna tabella
 * nuova, riusa il motore punti già esistente per un piccolo premio quando
 * si completa una missione condivisa.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_register_abbinamento_cpt' );
function gs_register_abbinamento_cpt() {
	register_post_type( 'gs_abbinamento', array(
		'labels'          => array( 'name' => 'Abbinamenti Madrina/Allieva', 'singular_name' => 'Abbinamento' ),
		'public'          => false,
		'show_ui'         => false,
		'show_in_menu'    => false,
		'supports'        => array( 'title' ),
		'capability_type' => 'post',
	) );
}

// -----------------------------------------------------------------------------
// Helper
// -----------------------------------------------------------------------------

/** L'abbinamento attivo di un utente (come madrina o come allieva), o null. */
function gs_get_abbinamento_utente( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return null;
	}
	foreach ( array( 'gs_madrina', 'gs_allieva' ) as $meta_key ) {
		$q = get_posts( array(
			'post_type'      => 'gs_abbinamento',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_key'       => $meta_key,
			'meta_value'     => $user_id,
		) );
		$q = gs_solo_tipo( $q, 'gs_abbinamento' );
		if ( $q && 'attivo' === get_post_meta( $q[0]->ID, 'gs_stato', true ) ) {
			return $q[0];
		}
	}
	return null;
}

/** Il ruolo dell'utente in un abbinamento: 'madrina' | 'allieva' | '' (non ne fa parte). */
function gs_abbinamento_ruolo( $abbinamento_id, $user_id ) {
	if ( (int) get_post_meta( $abbinamento_id, 'gs_madrina', true ) === (int) $user_id ) {
		return 'madrina';
	}
	if ( (int) get_post_meta( $abbinamento_id, 'gs_allieva', true ) === (int) $user_id ) {
		return 'allieva';
	}
	return '';
}

function gs_abbinamento_missioni( $abbinamento_id ) {
	$m = get_post_meta( $abbinamento_id, 'gs_missioni', true );
	return is_array( $m ) ? $m : array();
}
function gs_abbinamento_salva_missioni( $abbinamento_id, $missioni ) {
	update_post_meta( $abbinamento_id, 'gs_missioni', array_values( $missioni ) );
}

/** Cestino delle mini-missioni eliminate: non sono un CPT, niente wp_trash_post(). */
function gs_abbinamento_missioni_cestino( $abbinamento_id ) {
	$m = get_post_meta( $abbinamento_id, 'gs_missioni_cestino', true );
	return is_array( $m ) ? $m : array();
}
function gs_abbinamento_salva_missioni_cestino( $abbinamento_id, $cestino ) {
	if ( count( $cestino ) > 30 ) { $cestino = array_slice( $cestino, -30 ); }
	update_post_meta( $abbinamento_id, 'gs_missioni_cestino', array_values( $cestino ) );
}
/** Sposta una mini-missione dagli attivi al cestino. */
function gs_missione_sposta_nel_cestino( $abbinamento_id, $id ) {
	$missioni = gs_abbinamento_missioni( $abbinamento_id );
	$cestino  = gs_abbinamento_missioni_cestino( $abbinamento_id );
	foreach ( $missioni as $m ) {
		if ( $m['id'] === $id ) { $m['ts'] = time(); $cestino[] = $m; }
	}
	$missioni = array_filter( $missioni, function ( $m ) use ( $id ) { return $m['id'] !== $id; } );
	gs_abbinamento_salva_missioni( $abbinamento_id, $missioni );
	gs_abbinamento_salva_missioni_cestino( $abbinamento_id, $cestino );
}
/** Riporta una mini-missione dal cestino agli attivi. */
function gs_missione_ripristina_dal_cestino( $abbinamento_id, $id ) {
	$cestino  = gs_abbinamento_missioni_cestino( $abbinamento_id );
	$missioni = gs_abbinamento_missioni( $abbinamento_id );
	foreach ( $cestino as $m ) {
		if ( $m['id'] === $id ) { unset( $m['ts'] ); $missioni[] = $m; }
	}
	$cestino = array_filter( $cestino, function ( $m ) use ( $id ) { return $m['id'] !== $id; } );
	gs_abbinamento_salva_missioni( $abbinamento_id, $missioni );
	gs_abbinamento_salva_missioni_cestino( $abbinamento_id, $cestino );
}

// -----------------------------------------------------------------------------
// Box sfoglina-facing (in "La Mia Sfoglia", solo se c'è un abbinamento attivo)
// -----------------------------------------------------------------------------
function gs_render_madrina_box( $user_id ) {
	$abb = gs_get_abbinamento_utente( $user_id );
	if ( ! $abb ) {
		return '';
	}
	$ruolo = gs_abbinamento_ruolo( $abb->ID, $user_id );
	if ( '' === $ruolo ) {
		return '';
	}
	$altro_uid = 'madrina' === $ruolo
		? (int) get_post_meta( $abb->ID, 'gs_allieva', true )
		: (int) get_post_meta( $abb->ID, 'gs_madrina', true );
	$altro = get_user_by( 'id', $altro_uid );
	if ( ! $altro ) {
		return '';
	}

	$titolo = 'madrina' === $ruolo ? '🤝 La tua allieva' : '🤝 La tua madrina';
	$out    = gs_box_open( $titolo );
	$out   .= gs_sezione_aiuto( 'Voi due siete state abbinate dall\'Accademia per un percorso di affiancamento: qui potete aggiungere insieme piccole missioni, modificarne il testo e segnarle fatte quando le completate. Ogni missione completata vale qualche punto per entrambe. Anche le missioni eliminate restano recuperabili, in "🗑️ Missioni eliminate" qui sotto.' );
	$out   .= '<div class="gs-todo-riquadro">';
	$out   .= '<p class="gs-hint">' . ( 'madrina' === $ruolo ? 'Sei la madrina di ' : 'La tua madrina è ' ) . '<strong>' . esc_html( $altro->display_name ) . '</strong></p>';
	$out   .= '<form class="gs-form gs-madrina-form" data-abbinamento="' . (int) $abb->ID . '" onsubmit="return false"><div style="display:flex;gap:8px">'
		. '<input type="text" class="gs-madrina-input" placeholder="Aggiungi una mini-missione…" style="flex:1" autocomplete="off">'
		. '<button class="gs-btn gs-btn-sm gs-madrina-add">Aggiungi</button></div></form>';

	$missioni = gs_abbinamento_missioni( $abb->ID );
	if ( ! $missioni ) {
		$out .= '<p class="gs-hint">Ancora nessuna mini-missione: aggiungetene una insieme!</p>';
	} else {
		$out .= '<ul class="gs-todo-list gs-madrina-list" data-abbinamento="' . (int) $abb->ID . '">';
		foreach ( $missioni as $m ) {
			$done = ! empty( $m['fatta'] ) ? ' done' : '';
			$chk  = ! empty( $m['fatta'] ) ? 'checked' : '';
			$out .= '<li class="gs-todo-item gs-madrina-riga' . $done . '" data-id="' . esc_attr( $m['id'] ) . '" style="display:block">';
			$out .= '<div style="display:flex;align-items:center;gap:6px">'
				. '<input type="checkbox" class="gs-madrina-check" ' . $chk . '>'
				. '<input type="text" class="gs-madrina-testo" value="' . esc_attr( $m['testo'] ) . '" style="flex:1">'
				. '<button class="gs-btn gs-btn-sm gs-madrina-modifica" title="Salva modifiche">✎ Salva</button>'
				. '<button class="gs-todo-del gs-madrina-del" title="Elimina">✕</button></div>';
			$out .= '<span class="gs-madrina-riga-msg gs-richiesta-esito"></span>';
			$out .= '</li>';
		}
		$out .= '</ul>';
	}
	$cestino = gs_abbinamento_missioni_cestino( $abb->ID );
	$out .= '<details class="gs-todo-cestino gs-madrina-cestino" data-abbinamento="' . (int) $abb->ID . '"><summary>🗑️ Missioni eliminate (' . count( $cestino ) . ')</summary>';
	if ( ! $cestino ) {
		$out .= '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		$out .= '<ul class="gs-todo-list gs-todo-list-cestino">';
		foreach ( array_reverse( $cestino ) as $m ) {
			$out .= '<li class="gs-todo-item" data-id="' . esc_attr( $m['id'] ) . '">'
				. '<span>' . esc_html( $m['testo'] ) . '</span>'
				. '<button class="gs-todo-ripristina gs-madrina-ripristina" title="Ripristina">↺ Ripristina</button></li>';
		}
		$out .= '</ul>';
	}
	$out .= '</details>';
	$out .= '</div>';
	$out .= gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// AJAX sfoglina-facing: aggiungi / segna fatta / elimina una mini-missione
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_madrina_add', 'gs_ajax_madrina_add' );
function gs_ajax_madrina_add() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid    = get_current_user_id();
	$abb_id = isset( $_POST['abbinamento'] ) ? (int) $_POST['abbinamento'] : 0;
	if ( ! $uid || ! gs_abbinamento_ruolo( $abb_id, $uid ) ) {
		wp_send_json_error( array( 'message' => 'Accesso negato.' ) );
	}
	$testo = isset( $_POST['testo'] ) ? sanitize_text_field( wp_unslash( $_POST['testo'] ) ) : '';
	if ( '' === $testo ) {
		wp_send_json_error( array( 'message' => 'Scrivi qualcosa.' ) );
	}
	$missioni   = gs_abbinamento_missioni( $abb_id );
	$id         = uniqid( 'm' );
	$missioni[] = array( 'id' => $id, 'testo' => $testo, 'fatta' => false );
	gs_abbinamento_salva_missioni( $abb_id, $missioni );
	wp_send_json_success( array( 'id' => $id, 'testo' => $testo ) );
}

add_action( 'wp_ajax_gs_madrina_toggle', 'gs_ajax_madrina_toggle' );
function gs_ajax_madrina_toggle() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid    = get_current_user_id();
	$abb_id = isset( $_POST['abbinamento'] ) ? (int) $_POST['abbinamento'] : 0;
	if ( ! $uid || ! gs_abbinamento_ruolo( $abb_id, $uid ) ) {
		wp_send_json_error( array( 'message' => 'Accesso negato.' ) );
	}
	$id       = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	$missioni = gs_abbinamento_missioni( $abb_id );
	$fatta_ora = false;
	$da_pagare = false;
	foreach ( $missioni as &$m ) {
		if ( $m['id'] === $id ) {
			$m['fatta'] = empty( $m['fatta'] );
			$fatta_ora  = $m['fatta'];
			// Il premio si paga UNA VOLTA SOLA per mini-missione, non ad ogni
			// spunta/de-spunta: 'fatta' cambia liberamente avanti e indietro,
			// 'pagata' non si toglie mai. Senza questo, deselezionare e
			// riselezionare pagava 5+5 punti a ogni giro, all'infinito
			// (trovato 26/08/2026).
			if ( $fatta_ora && empty( $m['pagata'] ) ) {
				$m['pagata'] = true;
				$da_pagare   = true;
			}
		}
	}
	unset( $m );
	gs_abbinamento_salva_missioni( $abb_id, $missioni );

	if ( $da_pagare && function_exists( 'gs_add_points' ) ) {
		$madrina = (int) get_post_meta( $abb_id, 'gs_madrina', true );
		$allieva = (int) get_post_meta( $abb_id, 'gs_allieva', true );
		gs_add_points( $madrina, 5, 'Mini-missione con l\'allieva completata' );
		gs_add_points( $allieva, 5, 'Mini-missione con la madrina completata' );
	}
	wp_send_json_success();
}

/** Modifica il testo di una mini-missione esistente. */
function gs_missione_modifica_testo( $abbinamento_id, $id, $testo ) {
	$missioni = gs_abbinamento_missioni( $abbinamento_id );
	$trovata  = false;
	foreach ( $missioni as &$m ) {
		if ( $m['id'] === $id ) { $m['testo'] = $testo; $trovata = true; }
	}
	unset( $m );
	if ( $trovata ) { gs_abbinamento_salva_missioni( $abbinamento_id, $missioni ); }
	return $trovata;
}

add_action( 'wp_ajax_gs_madrina_modifica', 'gs_ajax_madrina_modifica' );
function gs_ajax_madrina_modifica() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid    = get_current_user_id();
	$abb_id = isset( $_POST['abbinamento'] ) ? (int) $_POST['abbinamento'] : 0;
	if ( ! $uid || ! gs_abbinamento_ruolo( $abb_id, $uid ) ) {
		wp_send_json_error( array( 'message' => 'Accesso negato.' ) );
	}
	$id    = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	$testo = isset( $_POST['testo'] ) ? sanitize_text_field( wp_unslash( $_POST['testo'] ) ) : '';
	if ( '' === trim( $testo ) ) { wp_send_json_error( array( 'message' => 'Il testo non può essere vuoto.' ) ); }
	if ( ! gs_missione_modifica_testo( $abb_id, $id, $testo ) ) { wp_send_json_error( array( 'message' => 'Missione non trovata.' ) ); }
	wp_send_json_success( array( 'message' => 'Modifiche salvate.' ) );
}

add_action( 'wp_ajax_gs_madrina_del', 'gs_ajax_madrina_del' );
function gs_ajax_madrina_del() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid    = get_current_user_id();
	$abb_id = isset( $_POST['abbinamento'] ) ? (int) $_POST['abbinamento'] : 0;
	if ( ! $uid || ! gs_abbinamento_ruolo( $abb_id, $uid ) ) {
		wp_send_json_error( array( 'message' => 'Accesso negato.' ) );
	}
	$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	gs_missione_sposta_nel_cestino( $abb_id, $id );
	wp_send_json_success();
}

add_action( 'wp_ajax_gs_madrina_ripristina', 'gs_ajax_madrina_ripristina' );
function gs_ajax_madrina_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid    = get_current_user_id();
	$abb_id = isset( $_POST['abbinamento'] ) ? (int) $_POST['abbinamento'] : 0;
	if ( ! $uid || ! gs_abbinamento_ruolo( $abb_id, $uid ) ) {
		wp_send_json_error( array( 'message' => 'Accesso negato.' ) );
	}
	$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	gs_missione_ripristina_dal_cestino( $abb_id, $id );
	wp_send_json_success( array( 'message' => 'Ripristinata tra le missioni.' ) );
}

// -----------------------------------------------------------------------------
// PANNELLO GESTORE — crea e gestisce gli abbinamenti
// -----------------------------------------------------------------------------
function gs_pannello_madrine() {
	if ( ! gs_can_manage() ) {
		return;
	}
	echo gs_box_open( 'Madrina & Allieva' );
	echo '<p class="gs-hint">Abbina una sfoglina esperta (madrina) a una principiante (allieva) per un periodo di affiancamento. Le due potranno aggiungere insieme piccole missioni condivise e segnarle fatte, guadagnando qualche punto ciascuna.</p>';

	$sfogline = function_exists( 'gs_sez_sfogline' ) ? gs_sez_sfogline() : array();

	echo '<form class="gs-form gs-form-nuovo-abbinamento" onsubmit="return false">';
	echo '<p><label>Madrina (esperta)<br><select class="gs-abb-madrina" style="max-width:320px">';
	foreach ( $sfogline as $u ) {
		echo '<option value="' . (int) $u->ID . '">' . esc_html( $u->display_name ) . '</option>';
	}
	echo '</select></label></p>';
	echo '<p><label>Allieva (principiante)<br><select class="gs-abb-allieva" style="max-width:320px">';
	foreach ( $sfogline as $u ) {
		echo '<option value="' . (int) $u->ID . '">' . esc_html( $u->display_name ) . '</option>';
	}
	echo '</select></label></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-crea-abbinamento">Crea abbinamento</button> <span class="gs-abb-msg gs-richiesta-esito"></span></p>';
	echo '</form>';

	$attivi = gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_abbinamento',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_key'       => 'gs_stato',
		'meta_value'     => 'attivo',
	) ), 'gs_abbinamento' );

	echo '<h4 style="margin-top:18px">Abbinamenti attivi (' . count( $attivi ) . ')</h4>';
	if ( ! $attivi ) {
		echo '<p class="gs-hint">Nessun abbinamento attivo al momento.</p>';
	} else {
		echo '<table class="gs-table"><thead><tr><th>Madrina</th><th>Allieva</th><th>Missioni</th><th>Azione</th></tr></thead><tbody>';
		foreach ( $attivi as $a ) {
			$m  = get_user_by( 'id', (int) get_post_meta( $a->ID, 'gs_madrina', true ) );
			$al = get_user_by( 'id', (int) get_post_meta( $a->ID, 'gs_allieva', true ) );
			$missioni  = gs_abbinamento_missioni( $a->ID );
			$n_fatte   = count( array_filter( $missioni, function ( $x ) { return ! empty( $x['fatta'] ); } ) );
			echo '<tr data-id="' . (int) $a->ID . '">';
			echo '<td>' . esc_html( $m ? $m->display_name : '—' ) . '</td>';
			echo '<td>' . esc_html( $al ? $al->display_name : '—' ) . '</td>';
			echo '<td>' . (int) $n_fatte . ' / ' . count( $missioni ) . '</td>';
			echo '<td><button class="gs-btn gs-btn-sm gs-btn-ghost gs-concludi-abbinamento" data-id="' . (int) $a->ID . '">Concludi</button></td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}
	echo gs_box_close();
}

add_action( 'wp_ajax_gs_crea_abbinamento', 'gs_ajax_crea_abbinamento' );
function gs_ajax_crea_abbinamento() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Permesso negato.' ) );
	}
	$madrina = isset( $_POST['madrina'] ) ? (int) $_POST['madrina'] : 0;
	$allieva = isset( $_POST['allieva'] ) ? (int) $_POST['allieva'] : 0;
	if ( ! $madrina || ! $allieva || $madrina === $allieva ) {
		wp_send_json_error( array( 'message' => 'Scegli due sfogline diverse.' ) );
	}
	$mu = get_user_by( 'id', $madrina );
	$au = get_user_by( 'id', $allieva );
	if ( ! $mu || ! $au ) {
		wp_send_json_error( array( 'message' => 'Sfoglina non trovata.' ) );
	}

	$pid = wp_insert_post( array(
		'post_type'   => 'gs_abbinamento',
		'post_status' => 'publish',
		'post_title'  => 'Madrina ' . $mu->display_name . ' — Allieva ' . $au->display_name,
		'post_author' => $madrina,
	) );
	if ( is_wp_error( $pid ) ) {
		wp_send_json_error( array( 'message' => 'Errore nel salvataggio.' ) );
	}
	update_post_meta( $pid, 'gs_madrina', $madrina );
	update_post_meta( $pid, 'gs_allieva', $allieva );
	update_post_meta( $pid, 'gs_stato', 'attivo' );
	update_post_meta( $pid, 'gs_missioni', array() );
	wp_send_json_success( array( 'message' => 'Abbinamento creato.' ) );
}

add_action( 'wp_ajax_gs_concludi_abbinamento', 'gs_ajax_concludi_abbinamento' );
function gs_ajax_concludi_abbinamento() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Permesso negato.' ) );
	}
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( ! $id || 'gs_abbinamento' !== get_post_type( $id ) ) {
		wp_send_json_error( array( 'message' => 'Abbinamento non valido.' ) );
	}
	update_post_meta( $id, 'gs_stato', 'concluso' );
	wp_send_json_success();
}
