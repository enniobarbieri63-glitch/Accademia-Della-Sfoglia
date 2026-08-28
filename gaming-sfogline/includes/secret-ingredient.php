<?php
/**
 * secret-ingredient.php — Ingrediente Segreto del venerdì.
 * Sfrutta la pubblicazione programmata nativa di WordPress. Finché non è
 * rivelato, il sito mostra solo un countdown, mai il contenuto.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ultimo ingrediente segreto già pubblicato (rivelato).
 */
function gs_get_revealed_ingredient() {
	$posts = get_posts( array(
		'post_type'      => 'gs_ingrediente',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
	return $posts ? $posts[0] : null;
}

/**
 * Prossimo ingrediente programmato (futuro non ancora rivelato).
 */
function gs_get_next_ingredient() {
	$posts = get_posts( array(
		'post_type'      => 'gs_ingrediente',
		'post_status'    => 'future',
		'posts_per_page' => 1,
		'orderby'        => 'date',
		'order'          => 'ASC',
	) );
	return $posts ? $posts[0] : null;
}

/**
 * Timestamp del prossimo reveal (per il countdown), o null.
 */
function gs_next_reveal_timestamp() {
	$next = gs_get_next_ingredient();
	if ( $next ) {
		return get_post_time( 'U', true, $next );
	}
	return null;
}

/** Chi comunica l'ingrediente alle sfogline (Rina Poletti, Bruno Cingolani, o un altro nome scritto a mano). */
function gs_ingrediente_annunciato_da( $post_id ) {
	return (string) get_post_meta( (int) $post_id, 'gs_ingr_annunciato_da', true );
}

/** Prossimo venerdì alle 18:00 (Y-m-d\TH:i, per precompilare il modulo). */
function gs_prossimo_venerdi_1800() {
	$oggi = current_time( 'timestamp' );
	$giorni_al_venerdi = ( 5 - (int) date( 'N', $oggi ) + 7 ) % 7;
	if ( 0 === $giorni_al_venerdi && (int) date( 'H', $oggi ) >= 18 ) {
		$giorni_al_venerdi = 7; // è già venerdì dopo le 18: punta al venerdì successivo.
	}
	$ts = $oggi + $giorni_al_venerdi * DAY_IN_SECONDS;
	return date( 'Y-m-d', $ts ) . 'T18:00';
}

// -----------------------------------------------------------------------------
// PANNELLO — creare un nuovo Ingrediente Segreto senza passare dall'editor di
// WordPress (richiesto da Ennio, 12/08/2026: "come mai apre la pagina di un
// articolo?" — perché finora usava l'editor standard di WordPress, uguale per
// qualunque tipo di contenuto; questo modulo è pensato apposta).
// -----------------------------------------------------------------------------
function gs_pannello_ingrediente_segreto() {
	if ( ! gs_can_manage() ) { return; }
	echo gs_box_open( '🥄 Ingrediente Segreto', '', 'gs-box-ingrediente-segreto' );
	echo gs_sezione_aiuto( 'Ogni venerdì Rina Poletti o Bruno Cingolani svelano un ingrediente a sorpresa da usare in una ricetta. Qui lo crei senza passare dall\'editor di WordPress: scrivi il nome, il testo, scegli quando si svela (di norma il venerdì alle 18:00, già proposto) e chi lo comunica. Finché la data non arriva, le sfogline vedono solo il conto alla rovescia.' );

	$prossimo = gs_get_next_ingredient();
	$rivelato = gs_get_revealed_ingredient();
	if ( $prossimo ) {
		echo '<p><strong>In programma:</strong> ' . esc_html( get_the_title( $prossimo ) ) . ' — si svela il ' . esc_html( date_i18n( 'j F Y \a\l\l\e H:i', get_post_time( 'U', true, $prossimo ) ) ) . '</p>';
	} elseif ( $rivelato ) {
		echo '<p><strong>Ultimo svelato:</strong> ' . esc_html( get_the_title( $rivelato ) ) . ' — nessuno in programma dopo questo.</p>';
	}

	echo '<form class="gs-form gs-form-ingrediente" onsubmit="return false">';
	echo '<p><label>Nome dell\'ingrediente<br><input type="text" name="nome" autocomplete="off" style="width:100%" required></label></p>';
	echo '<p><label>Descrizione — come usarlo, un consiglio, un aneddoto<br><textarea name="testo" rows="4" style="width:100%"></textarea></label></p>';
	echo '<p><label>Foto (facoltativa)<br><input type="file" name="foto" accept="image/*"></label></p>';
	echo '<p><label>Si svela il<br><input type="datetime-local" name="quando" value="' . esc_attr( gs_prossimo_venerdi_1800() ) . '"></label></p>';
	echo '<p><label>Chi lo comunica<br><select name="annunciato_da">';
	echo '<option value="Rina Poletti">Rina Poletti</option>';
	echo '<option value="Bruno Cingolani">Bruno Cingolani</option>';
	echo '<option value="">— non specificato —</option>';
	echo '</select></label></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-ingrediente-crea">Crea Ingrediente Segreto</button> <span class="gs-ingrediente-crea-msg gs-richiesta-esito"></span></p>';
	echo '</form>';
	echo '<p class="gs-hint">Per modificare un ingrediente già creato (correggere il testo, la foto, riprogrammare la data) usa <a href="' . esc_url( admin_url( 'edit.php?post_type=gs_ingrediente' ) ) . '">Gestisci Ingredienti Segreti</a>, l\'elenco di WordPress.</p>';
	echo gs_box_close();
}

add_action( 'wp_ajax_gs_ingrediente_crea', 'gs_ajax_ingrediente_crea' );
function gs_ajax_ingrediente_crea() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$nome    = gs_clean( $_POST['nome'] ?? '' );
	$testo   = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';
	$quando  = isset( $_POST['quando'] ) ? sanitize_text_field( wp_unslash( $_POST['quando'] ) ) : '';
	$annunciato_da = gs_clean( $_POST['annunciato_da'] ?? '' );

	if ( ! $nome ) { wp_send_json_error( array( 'message' => 'Scrivi il nome dell\'ingrediente.' ) ); }
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $quando ) ) {
		wp_send_json_error( array( 'message' => 'Indica quando si svela.' ) );
	}
	$ts = strtotime( str_replace( 'T', ' ', $quando ) );
	if ( ! $ts ) { wp_send_json_error( array( 'message' => 'Data non valida.' ) ); }

	$nel_futuro = $ts > current_time( 'timestamp' );
	$post_id = wp_insert_post( array(
		'post_type'    => 'gs_ingrediente',
		'post_status'  => $nel_futuro ? 'future' : 'publish',
		'post_title'   => $nome,
		'post_content' => $testo,
		'post_date'    => date( 'Y-m-d H:i:s', $ts ),
	), true );
	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
	}

	if ( $annunciato_da ) {
		update_post_meta( $post_id, 'gs_ingr_annunciato_da', $annunciato_da );
	}
	if ( function_exists( 'gs_msg_upload' ) ) {
		$foto = gs_msg_upload( 'foto' );
		if ( ! is_wp_error( $foto ) && is_array( $foto ) && 'image' === $foto['type'] ) {
			$attach_id = gs_allega_immagine_url_a_post( $foto['url'], $post_id );
			if ( $attach_id ) { set_post_thumbnail( $post_id, $attach_id ); }
		}
	}

	wp_send_json_success( array(
		'message' => $nel_futuro
			? 'Creato: si svela il ' . date_i18n( 'j F Y \a\l\l\e H:i', $ts ) . '.'
			: 'Creato e già pubblicato (la data scelta non era nel futuro).',
	) );
}

/**
 * Le foto caricate dal modulo di contatto/messaggistica (gs_msg_upload)
 * restano fuori dalla libreria media di WordPress: per usarne una come
 * immagine in evidenza di un post serve prima un vero allegato. Scarica il
 * file già caricato e lo registra come allegato del post indicato.
 */
function gs_allega_immagine_url_a_post( $url, $post_id ) {
	$path = str_replace( content_url(), WP_CONTENT_DIR, $url );
	if ( ! file_exists( $path ) ) { return 0; }
	$filetype = wp_check_filetype( basename( $path ), null );
	$attach_id = wp_insert_attachment( array(
		'post_mime_type' => $filetype['type'],
		'post_title'     => sanitize_file_name( basename( $path ) ),
		'post_status'    => 'inherit',
	), $path, $post_id );
	if ( ! is_wp_error( $attach_id ) && $attach_id ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $path ) );
		return (int) $attach_id;
	}
	return 0;
}
