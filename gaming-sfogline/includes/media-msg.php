<?php
/**
 * media-msg.php — Allegati foto/video per la messaggistica del progetto.
 *
 * Un unico punto per gestire il caricamento di una foto o di un video nei
 * messaggi (conversazioni, calendario, auguri, aiuto, segreteria). I limiti di
 * peso e la compressione sono quelli del pannello generale (sezione Media),
 * applicati automaticamente dai filtri di media-backup.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gestisce l'upload di un allegato media da $_FILES[$field].
 * @return array|WP_Error|null  array('id','url','type'=>image|video|audio) | errore | null (nessun file)
 */
function gs_msg_upload( $field = 'media' ) {
	if ( empty( $_FILES[ $field ] ) || empty( $_FILES[ $field ]['name'] ) ) { return null; }
	if ( ! empty( $_FILES[ $field ]['error'] ) ) {
		$err = (int) $_FILES[ $field ]['error'];
		if ( UPLOAD_ERR_INI_SIZE === $err || UPLOAD_ERR_FORM_SIZE === $err ) {
			return new WP_Error( 'upload', 'Il file è troppo pesante per il server: riducine le dimensioni (o scattane una foto meno definita) e riprova.' );
		}
		return new WP_Error( 'upload', 'Caricamento non riuscito, riprova.' );
	}

	// Tipo dedotto dall'estensione reale del file, non da quello
	// che dichiara il browser (che chi carica può falsificare).
	$info   = wp_check_filetype( $_FILES[ $field ]['name'] );
	$type   = ! empty( $info['type'] ) ? (string) $info['type'] : '';
	$is_img = 0 === strpos( $type, 'image/' );
	$is_vid = 0 === strpos( $type, 'video/' );
	$is_aud = 0 === strpos( $type, 'audio/' );
	if ( ! $is_img && ! $is_vid && ! $is_aud ) {
		return new WP_Error( 'tipo', 'Puoi allegare solo una foto, un video o un audio.' );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	// I limiti di peso/compressione del pannello sono applicati dai filtri di upload.
	$id = media_handle_upload( $field, 0 );
	if ( is_wp_error( $id ) ) { return $id; }

	return array(
		'id'   => (int) $id,
		'url'  => wp_get_attachment_url( $id ),
		'type' => $is_aud ? 'audio' : ( $is_vid ? 'video' : 'image' ),
	);
}

/** Markup per mostrare un allegato media in un messaggio. */
function gs_msg_media_html( $url, $type = 'image' ) {
	if ( ! $url ) { return ''; }
	if ( 'video' === $type ) {
		return '<div class="gs-msg-media"><video controls preload="metadata" src="' . esc_url( $url ) . '"></video></div>';
	}
	if ( 'audio' === $type ) {
		return '<div class="gs-msg-media"><audio controls preload="metadata" src="' . esc_url( $url ) . '"></audio></div>';
	}
	return '<div class="gs-msg-media"><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener"><img src="' . esc_url( $url ) . '" alt=""></a></div>';
}

/** Campo file pronto da inserire nei form dei messaggi. */
function gs_msg_file_field() {
	return '<input type="file" name="media" class="gs-msg-file" accept="image/*,video/*,audio/*"> <span class="gs-hint">foto, video o audio (facoltativo)</span>';
}

/**
 * Pulisce il testo di un messaggio: rimuove i commenti dei blocchi Gutenberg
 * (<!-- wp:... -->), converte i paragrafi in a-capo e toglie eventuali tag HTML,
 * così il testo incollato dall'editor appare pulito e leggibile.
 */
function gs_msg_clean( $text ) {
	$text = (string) $text;
	if ( '' === $text ) { return ''; }
	// Commenti dei blocchi Gutenberg.
	$text = preg_replace( '/<!--\s*\/?\s*wp:[^>]*?-->/s', '', $text );
	// Altri commenti HTML.
	$text = preg_replace( '/<!--.*?-->/s', '', $text );
	// Paragrafi e <br> → a-capo.
	$text = preg_replace( '#</p\s*>\s*<p[^>]*>#i', "\n\n", $text );
	$text = preg_replace( '#<br\s*/?>#i', "\n", $text );
	// Le entità vanno decodificate PRIMA di togliere i tag, così che
	// un &lt;script&gt; diventi <script> e venga rimosso dallo strip.
	$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
	// Via i tag rimasti.
	$text = wp_strip_all_tags( $text );
	// Righe vuote multiple → doppio a-capo.
	$text = preg_replace( "/\n{3,}/", "\n\n", $text );
	return trim( $text );
}
