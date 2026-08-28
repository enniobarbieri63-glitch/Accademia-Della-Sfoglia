<?php
/**
 * novita.php — "Novità": annunci brevi di chi gestisce il portale su cosa è
 * cambiato o si è aggiunto, in ordine cronologico. Pubblica (visibile anche
 * a chi non ha effettuato l'accesso, come FAQ e Registro Ufficiale): serve
 * a far sapere a chi torna sul sito cosa è cambiato, senza dover leggere il
 * changelog tecnico del plugin.
 *
 * CPT gs_novita: titolo dell'annuncio in post_title, testo in post_content,
 * data in post_date (l'ordine cronologico normale del CPT).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_novita_register_cpt' );
function gs_novita_register_cpt() {
	register_post_type( 'gs_novita', array(
		'labels'       => array( 'name' => 'Novità' ),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'title', 'editor' ),
	) );
}

// -----------------------------------------------------------------------------
// Helper
// -----------------------------------------------------------------------------
function gs_novita_get( $id ) {
	return array(
		'id'     => $id,
		'titolo' => get_the_title( $id ),
		'testo'  => get_post( $id ) ? get_post( $id )->post_content : '',
		'data'   => get_post( $id ) ? get_post( $id )->post_date : '',
	);
}

/** Tutti gli annunci pubblicati, dal più recente. */
function gs_novita_pubblicate() {
	return gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_novita',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'suppress_filters' => true,
	) ), 'gs_novita' );
}

// -----------------------------------------------------------------------------
// [gs_novita] — pubblica: elenco degli annunci
// -----------------------------------------------------------------------------
add_shortcode( 'gs_novita', 'gs_sc_novita' );
function gs_sc_novita() {
	$out = gs_box_open( '📣 Novità' );
	$out .= gs_sezione_aiuto( 'Qui trovi gli ultimi annunci di chi gestisce il portale: nuove sezioni, cambiamenti, avvisi importanti. Clicca su un titolo per leggere il testo completo.' );

	$annunci = gs_novita_pubblicate();
	if ( ! $annunci ) {
		$out .= '<p class="gs-hint">Non ci sono ancora annunci pubblicati.</p>';
		$out .= gs_box_close();
		return $out;
	}

	$out .= '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="10">';
	foreach ( $annunci as $p ) {
		$c = gs_novita_get( $p->ID );
		$out .= '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">' . esc_html( $c['titolo'] )
			. ' <span class="gs-msg-data">' . esc_html( mysql2date( 'j/m/Y', $c['data'] ) ) . '</span></summary>';
		$out .= '<div class="gs-inbox-corpo"><div class="gs-inbox-testo">' . nl2br( esc_html( $c['testo'] ) ) . '</div></div></details>';
	}
	$out .= '</div>';

	$out .= gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// PANNELLO — gestione degli annunci (titolare/collaboratori)
// -----------------------------------------------------------------------------
function gs_pannello_novita() {
	if ( ! gs_can_manage() ) { return; }

	echo gs_box_open( '📣 Novità', '', 'gs-box-novita' );
	echo gs_sezione_aiuto( 'Scrivi qui gli annunci pubblici che compaiono nella sezione "Novità" del sito: nuove sezioni, cambiamenti, avvisi. Spuntando "Avvisa subito le sfogline" arriva anche l\'aeroplanino a tutte le sfogline approvate, con un link diretto all\'annuncio.' );

	echo '<form class="gs-form gs-form-novita-crea" onsubmit="return false" style="background:var(--gs-uovo);padding:12px;border-radius:6px;margin-bottom:14px">';
	echo '<strong>Nuovo annuncio</strong>';
	echo '<p><label>Titolo<br><input type="text" name="titolo" autocomplete="off" style="width:100%" required></label></p>';
	echo '<p><label>Testo<br><textarea name="testo" rows="4" style="width:100%" required></textarea></label></p>';
	echo '<p><label><input type="checkbox" name="avvisa" value="1"> Avvisa subito le sfogline (aeroplanino, con link diretto all\'annuncio)</label></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-novita-crea">Pubblica annuncio</button> <span class="gs-novita-crea-msg gs-richiesta-esito"></span></p>';
	echo '</form>';

	$annunci = gs_novita_pubblicate();
	echo '<h4>Annunci pubblicati (' . count( $annunci ) . ')</h4>';
	if ( ! $annunci ) {
		echo '<p class="gs-hint">Nessun annuncio ancora creato.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-lista-risultati gs-novita-lista-admin gs-paginate" data-per-page="10">';
		foreach ( $annunci as $p ) {
			$c = gs_novita_get( $p->ID );
			echo '<details class="gs-inbox-item" data-novita="' . (int) $c['id'] . '">';
			echo '<summary class="gs-inbox-oggetto">' . esc_html( $c['titolo'] ) . ' <span class="gs-msg-data">' . esc_html( mysql2date( 'j/m/Y', $c['data'] ) ) . '</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			echo '<form class="gs-form gs-form-novita-modifica" data-novita="' . (int) $c['id'] . '" onsubmit="return false">';
			echo '<p><label>Titolo<br><input type="text" name="titolo" autocomplete="off" value="' . esc_attr( $c['titolo'] ) . '" style="width:100%" required></label></p>';
			echo '<p><label>Testo<br><textarea name="testo" rows="4" style="width:100%" required>' . esc_textarea( $c['testo'] ) . '</textarea></label></p>';
			echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-novita-salva">Salva modifiche</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-novita-elimina" data-novita="' . (int) $c['id'] . '">Elimina</button> <span class="gs-novita-riga-msg gs-richiesta-esito"></span></p>';
			echo '</form>';
			echo '</div></details>';
		}
		echo '</div>';
	}

	// Cestino.
	$trash = get_posts( array( 'post_type' => 'gs_novita', 'post_status' => 'trash', 'posts_per_page' => 50, 'suppress_filters' => true ) );
	echo '<div class="gs-sezione-cestino">';
	echo '<h4 class="gs-titolo-cestino">🗑️ Cestino</h4>';
	if ( ! $trash ) {
		echo '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		echo '<table class="gs-table gs-paginate" data-per-page="8"><thead><tr>' . gs_cestino_th_checkbox() . '<th>Annuncio</th><th></th></tr></thead><tbody>';
		foreach ( $trash as $p ) {
			echo '<tr data-novita="' . (int) $p->ID . '">' . gs_cestino_td_checkbox( $p->ID ) . '<td>' . esc_html( get_the_title( $p ) ) . '</td>';
			echo '<td><button class="gs-btn gs-btn-sm gs-btn-verde gs-novita-ripristina" data-novita="' . (int) $p->ID . '">Ripristina</button> <span class="gs-novita-trow-msg gs-richiesta-esito"></span></td></tr>';
		}
		echo '</tbody></table>';
		echo gs_cestino_azioni_bulk( 'gs_novita' );
	}
	echo '</div>';

	echo gs_box_close();
}

add_action( 'wp_ajax_gs_novita_crea', 'gs_ajax_novita_crea' );
function gs_ajax_novita_crea() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	$testo  = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';
	if ( '' === trim( $titolo ) ) { wp_send_json_error( array( 'message' => 'Scrivi il titolo.' ) ); }
	if ( '' === trim( $testo ) ) { wp_send_json_error( array( 'message' => 'Scrivi il testo dell\'annuncio.' ) ); }

	$id = wp_insert_post( array( 'post_type' => 'gs_novita', 'post_status' => 'publish', 'post_title' => $titolo, 'post_content' => $testo ) );
	if ( is_wp_error( $id ) || ! $id ) { wp_send_json_error( array( 'message' => 'Errore nella creazione.' ) ); }

	$avvisate = 0;
	if ( ! empty( $_POST['avvisa'] ) && function_exists( 'gs_sez_sfogline' ) && function_exists( 'gs_accoda_volo' ) ) {
		$link = function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_novita' ) : '';
		foreach ( gs_sez_sfogline() as $u ) {
			gs_accoda_volo( $u->ID, '📣 Novità: «' . $titolo . '»', $link );
			$avvisate++;
		}
	}

	wp_send_json_success( array(
		'message' => 'Annuncio pubblicato.' . ( $avvisate ? ' Avviso inviato a ' . $avvisate . ( 1 === $avvisate ? ' sfoglina.' : ' sfogline.' ) : '' ),
	) );
}

add_action( 'wp_ajax_gs_novita_modifica', 'gs_ajax_novita_modifica' );
function gs_ajax_novita_modifica() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['novita'] ) ? (int) $_POST['novita'] : 0;
	if ( 'gs_novita' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Annuncio non valido.' ) ); }
	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	$testo  = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';
	if ( '' === trim( $titolo ) ) { wp_send_json_error( array( 'message' => 'Scrivi il titolo.' ) ); }
	if ( '' === trim( $testo ) ) { wp_send_json_error( array( 'message' => 'Scrivi il testo dell\'annuncio.' ) ); }

	wp_update_post( array( 'ID' => $id, 'post_title' => $titolo, 'post_content' => $testo ) );
	wp_send_json_success( array( 'message' => 'Annuncio aggiornato.' ) );
}

add_action( 'wp_ajax_gs_novita_elimina', 'gs_ajax_novita_elimina' );
function gs_ajax_novita_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['novita'] ) ? (int) $_POST['novita'] : 0;
	if ( 'gs_novita' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Annuncio non valido.' ) ); }
	wp_trash_post( $id );
	wp_send_json_success( array( 'message' => 'Spostato nel cestino.' ) );
}

add_action( 'wp_ajax_gs_novita_ripristina', 'gs_ajax_novita_ripristina' );
function gs_ajax_novita_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['novita'] ) ? (int) $_POST['novita'] : 0;
	if ( 'gs_novita' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Annuncio non valido.' ) ); }
	wp_untrash_post( $id );
	wp_send_json_success( array( 'message' => 'Annuncio ripristinato.' ) );
}
