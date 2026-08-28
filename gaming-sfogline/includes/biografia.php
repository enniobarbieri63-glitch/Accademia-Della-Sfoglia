<?php
/**
 * biografia.php — Sezione biografia della Vetrina (testo + foto + video).
 *
 * La sfoglina compila la propria biografia dal suo pannello (La Mia Sfoglia).
 * La biografia diventa visibile nell'area PUBBLICA (Vetrina) solo dopo
 * l'APPROVAZIONE dal pannello generale di controllo. Ogni modifica rimette la
 * biografia "in attesa" di approvazione.
 *
 * Meta utente:
 *  - gs_bio_testo  (string)
 *  - gs_bio_media  (array di array 'url','type')
 *  - gs_bio_stato  ('vuota' | 'in_attesa' | 'approvata')
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gs_bio_get( $uid ) {
	$media = get_user_meta( (int) $uid, 'gs_bio_media', true );
	return array(
		'testo' => (string) get_user_meta( (int) $uid, 'gs_bio_testo', true ),
		'media' => is_array( $media ) ? $media : array(),
		'stato' => get_user_meta( (int) $uid, 'gs_bio_stato', true ) ?: 'vuota',
	);
}

/**
 * Cestino della biografia: foto profilo e media della galleria rimossi non
 * sono post (solo user meta), quindi wp_trash_post() non si applica — ma la
 * regola resta la stessa, niente cancellazione definitiva. Capped come le
 * altre code personali, per non crescere all'infinito.
 */
function gs_bio_get_cestino( $uid ) {
	$c = get_user_meta( (int) $uid, 'gs_bio_cestino', true );
	return is_array( $c ) ? $c : array();
}
function gs_bio_save_cestino( $uid, $cestino ) {
	if ( count( $cestino ) > 30 ) {
		$cestino = array_slice( $cestino, -30 );
	}
	update_user_meta( (int) $uid, 'gs_bio_cestino', array_values( $cestino ) );
}

/** Sposta la foto profilo nel cestino della biografia e la rimuove. */
function gs_bio_foto_sposta_nel_cestino( $uid ) {
	$uid = (int) $uid;
	$url = get_user_meta( $uid, 'gs_bio_foto', true );
	if ( $url ) {
		$cestino   = gs_bio_get_cestino( $uid );
		$cestino[] = array( 'id' => uniqid( 'bmc' ), 'tipo' => 'foto', 'url' => $url, 'ts' => time() );
		gs_bio_save_cestino( $uid, $cestino );
	}
	delete_user_meta( $uid, 'gs_bio_foto' );
	update_user_meta( $uid, 'gs_bio_stato', 'in_attesa' );
}

/** Sposta un elemento della galleria media (per indice) nel cestino della biografia. */
function gs_bio_media_sposta_nel_cestino( $uid, $i ) {
	$uid = (int) $uid;
	$i   = (int) $i;
	$cur = get_user_meta( $uid, 'gs_bio_media', true );
	if ( ! is_array( $cur ) || ! isset( $cur[ $i ] ) ) {
		return false;
	}
	$cestino   = gs_bio_get_cestino( $uid );
	$cestino[] = array(
		'id'         => uniqid( 'bmc' ),
		'tipo'       => 'media',
		'url'        => $cur[ $i ]['url'],
		'media_type' => isset( $cur[ $i ]['type'] ) ? $cur[ $i ]['type'] : 'image',
		'ts'         => time(),
	);
	gs_bio_save_cestino( $uid, $cestino );
	unset( $cur[ $i ] );
	update_user_meta( $uid, 'gs_bio_media', array_values( $cur ) );
	update_user_meta( $uid, 'gs_bio_stato', 'in_attesa' );
	return true;
}

/** Ripristina un elemento (foto profilo o media) dal cestino della biografia. */
function gs_bio_cestino_ripristina( $uid, $id ) {
	$uid     = (int) $uid;
	$cestino = gs_bio_get_cestino( $uid );
	$voce    = null;
	foreach ( $cestino as $v ) {
		if ( $v['id'] === $id ) { $voce = $v; break; }
	}
	if ( ! $voce ) {
		return false;
	}
	if ( 'foto' === $voce['tipo'] ) {
		update_user_meta( $uid, 'gs_bio_foto', $voce['url'] );
	} else {
		$media = get_user_meta( $uid, 'gs_bio_media', true );
		if ( ! is_array( $media ) ) { $media = array(); }
		$media[] = array( 'url' => $voce['url'], 'type' => $voce['media_type'] );
		update_user_meta( $uid, 'gs_bio_media', $media );
	}
	$cestino = array_values( array_filter( $cestino, function ( $v ) use ( $id ) {
		return $v['id'] !== $id;
	} ) );
	gs_bio_save_cestino( $uid, $cestino );
	update_user_meta( $uid, 'gs_bio_stato', 'in_attesa' );
	return true;
}

/** Biografia pubblica (solo se approvata). Usata nella Vetrina. */
function gs_bio_pubblica_html( $uid ) {
	$bio = gs_bio_get( $uid );
	if ( 'approvata' !== $bio['stato'] ) { return ''; }
	if ( '' === trim( $bio['testo'] ) && empty( $bio['media'] ) ) { return ''; }
	$out  = gs_box_open( '📖 Biografia' );
	if ( '' !== trim( $bio['testo'] ) ) {
		$out .= '<div class="gs-bio-testo">' . nl2br( esc_html( $bio['testo'] ) ) . '</div>';
	}
	if ( $bio['media'] ) {
		$out .= '<div class="gs-bio-media-wrap">';
		foreach ( $bio['media'] as $m ) {
			$out .= gs_msg_media_html( $m['url'], isset( $m['type'] ) ? $m['type'] : 'image' );
		}
		$out .= '</div>';
	}
	$out .= gs_box_close();
	return $out;
}

/** URL della foto profilo della sfoglina (se caricata). */
function gs_bio_foto_url( $uid ) {
	return (string) get_user_meta( (int) $uid, 'gs_bio_foto', true );
}

/**
 * Biografie in attesa di approvazione. Considera TUTTI gli utenti (anche
 * amministratori e collaboratori), non solo le sfogline: altrimenti la
 * biografia del titolare resterebbe per sempre in attesa.
 */
// Considera TUTTI gli utenti (anche amministratori e collaboratori), non
// solo le sfogline: altrimenti la biografia del titolare o di un docente
// resterebbe per sempre in attesa (stessa scelta, deliberata, di gs_bio_approvate() qui sotto).
function gs_bio_in_attesa() {
	$attesa = array();
	foreach ( get_users( array( 'orderby' => 'display_name' ) ) as $u ) {
		if ( 'in_attesa' === get_user_meta( $u->ID, 'gs_bio_stato', true ) ) { $attesa[] = $u; }
	}
	return $attesa;
}

/** Editor della biografia, mostrato nel pannello della sfoglina. */
// Aggiunge il box in fondo alla dashboard, subito dopo "Aiuto e Suggerimenti"
// (che si aggancia con lo stesso meccanismo in aiuto.php, priorità 20): usare
// una priorità più alta (21) mette la biografia dopo, non prima, dato che i
// filtri su 'do_shortcode_tag' si accumulano nell'ordine di priorità.
add_filter( 'do_shortcode_tag', 'gs_bio_append_dashboard', 21, 2 );
function gs_bio_append_dashboard( $output, $tag ) {
	if ( 'gs_dashboard' === $tag && is_user_logged_in() ) {
		$output .= gs_bio_editor_html();
	}
	return $output;
}

function gs_bio_editor_html() {
	$uid = get_current_user_id();
	if ( ! $uid ) { return ''; }
	$bio  = gs_bio_get( $uid );
	$foto = gs_bio_foto_url( $uid );
	$stati = array(
		'vuota'     => 'Non ancora compilata',
		'in_attesa' => 'In attesa di approvazione',
		'approvata' => 'Approvata e visibile nella tua Vetrina pubblica',
	);
	$out  = gs_box_open( '📖 La Mia Biografia (per la Vetrina)' );
	$out .= gs_sezione_aiuto( 'Scrivi qui il testo e carica la foto che vuoi mostrare nella tua Vetrina pubblica. Ogni modifica torna in attesa di approvazione: finché un\'amministratrice non la rivede, resta visibile solo a te, non sulla Vetrina.' );
	$out .= '<p class="gs-hint">La tua foto e la tua biografia compaiono nella Vetrina pubblica <strong>solo dopo l\'approvazione</strong> dell\'amministrazione. Ogni modifica la rimette in attesa.</p>';
	$out .= '<div class="gs-todo-riquadro">';
	$out .= '<p>Stato attuale: <strong>' . esc_html( $stati[ $bio['stato'] ] ?? $bio['stato'] ) . '</strong></p>';

	// Foto profilo attuale.
	$out .= '<div class="gs-bio-foto-riga">';
	if ( $foto ) {
		$out .= '<img class="gs-profile-foto" src="' . esc_url( $foto ) . '" alt="">';
		$out .= '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-bio-foto-rimuovi">Rimuovi foto</button>';
	} else {
		$out .= '<span class="gs-hint">Nessuna foto profilo caricata.</span>';
	}
	$out .= '</div>';

	// Testo salvato: titolo cliccabile che mostra la biografia salvata.
	if ( '' !== trim( $bio['testo'] ) ) {
		$out .= '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">📄 La tua biografia salvata</summary>';
		$out .= '<div class="gs-inbox-corpo"><div class="gs-inbox-testo">' . nl2br( esc_html( $bio['testo'] ) ) . '</div></div></details>';
	}

	// Media già caricati, con rimozione.
	if ( $bio['media'] ) {
		$out .= '<div class="gs-bio-media-wrap">';
		foreach ( $bio['media'] as $i => $m ) {
			$out .= '<div class="gs-bio-media-item">' . gs_msg_media_html( $m['url'], isset( $m['type'] ) ? $m['type'] : 'image' )
				. '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-bio-rimuovi" data-i="' . (int) $i . '">Rimuovi</button></div>';
		}
		$out .= '</div>';
	}

	// Cestino: foto profilo e media rimossi, recuperabili.
	$cestino = gs_bio_get_cestino( $uid );
	$out    .= '<details class="gs-todo-cestino"><summary>🗑️ Foto eliminate (' . count( $cestino ) . ')</summary>';
	if ( ! $cestino ) {
		$out .= '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		$out .= '<div class="gs-bio-media-wrap">';
		foreach ( array_reverse( $cestino ) as $v ) {
			$tipo_html = 'foto' === $v['tipo'] ? '<img class="gs-profile-foto" src="' . esc_url( $v['url'] ) . '" alt="">' : gs_msg_media_html( $v['url'], isset( $v['media_type'] ) ? $v['media_type'] : 'image' );
			$out .= '<div class="gs-bio-media-item">' . $tipo_html
				. '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-bio-ripristina" data-id="' . esc_attr( $v['id'] ) . '">↺ Ripristina</button></div>';
		}
		$out .= '</div>';
	}
	$out .= '</details>';

	// Modulo: testo sempre visibile e modificabile.
	$out .= '<form class="gs-form gs-form-bio" onsubmit="return false">';
	$out .= '<p><label>La tua foto (per la Vetrina)<br><input type="file" name="foto" class="gs-bio-foto-file" accept="image/*"></label></p>';
	$out .= '<p><label>Testo della biografia (modificabile)<br><textarea name="testo" rows="6" style="width:100%" placeholder="Scrivi qui la tua biografia…">' . esc_textarea( $bio['testo'] ) . '</textarea></label></p>';
	$out .= '<p>Aggiungi anche una foto o un video al racconto: ' . gs_msg_file_field() . '</p>';
	$out .= '<p><button class="gs-btn gs-btn-sm gs-bio-salva">Salva e invia per approvazione</button> <span class="gs-bio-msg gs-richiesta-esito"></span></p>';

	// Chi gestisce il plugin può approvare (o sospendere) la propria biografia da qui.
	if ( gs_can_manage() ) {
		$out .= '<p class="gs-hint">Hai i permessi di gestione: puoi approvare subito la tua biografia.</p><p>';
		if ( 'approvata' === $bio['stato'] ) {
			$out .= '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-bio-modera" data-uid="' . (int) $uid . '" data-esito="sospesa">Sospendi (togli dalla Vetrina)</button> ';
		} else {
			$out .= '<button class="gs-btn gs-btn-sm gs-bio-modera" data-uid="' . (int) $uid . '" data-esito="approvata">Approva subito la mia biografia</button> ';
		}
		$out .= '<span class="gs-bio-mod-msg gs-richiesta-esito"></span></p>';
	}
	$out .= '</form>';
	$out .= '</div>';
	$out .= gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// AJAX lato sfoglina
//
// Qui sotto il controllo è gs_is_approved() da solo, SENZA gs_puo_partecipare():
// la Vetrina pubblica si paga a parte (49€, non i 29€ del gaming), quindi una
// sfoglina congelata dal gaming ma con la Vetrina attiva deve poter continuare
// a modificare la propria biografia (documento dei trenta giorni, 26/08/2026).
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_bio_salva', 'gs_ajax_bio_salva' );
function gs_ajax_bio_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_is_approved( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }
	$testo = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';
	if ( function_exists( 'gs_msg_clean' ) ) { $testo = gs_msg_clean( $testo ); }
	update_user_meta( $uid, 'gs_bio_testo', $testo );

	// Eventuale foto profilo.
	if ( function_exists( 'gs_msg_upload' ) && ! empty( $_FILES['foto']['name'] ) ) {
		$foto = gs_msg_upload( 'foto' );
		if ( is_wp_error( $foto ) ) { wp_send_json_error( array( 'message' => $foto->get_error_message() ) ); }
		if ( is_array( $foto ) && 'image' === $foto['type'] ) {
			update_user_meta( $uid, 'gs_bio_foto', $foto['url'] );
		} elseif ( is_array( $foto ) ) {
			wp_send_json_error( array( 'message' => 'La foto profilo deve essere un\'immagine.' ) );
		}
	}

	// Eventuale nuovo media.
	if ( function_exists( 'gs_msg_upload' ) ) {
		$media = gs_msg_upload( 'media' );
		if ( is_wp_error( $media ) ) { wp_send_json_error( array( 'message' => $media->get_error_message() ) ); }
		if ( is_array( $media ) ) {
			$cur = get_user_meta( $uid, 'gs_bio_media', true );
			if ( ! is_array( $cur ) ) { $cur = array(); }
			$cur[] = array( 'url' => $media['url'], 'type' => $media['type'] );
			update_user_meta( $uid, 'gs_bio_media', $cur );
		}
	}
	// Ogni modifica torna in attesa di approvazione.
	update_user_meta( $uid, 'gs_bio_stato', 'in_attesa' );

	if ( function_exists( 'gs_inbox_crea' ) ) {
		$u = get_userdata( $uid );
		gs_inbox_crea( 'Biografia da approvare: ' . ( $u ? $u->display_name : '' ), 'Una sfoglina ha inviato/modificato la propria biografia. Va approvata per comparire nella Vetrina pubblica.', array( 'from' => $u ? $u->display_name : 'Sfoglina', 'link_ancora' => 'admin.php?page=gs-generale#gs-zona-biografie' ) );
	}
	wp_send_json_success( array( 'message' => 'Biografia salvata e inviata per approvazione.' ) );
}

add_action( 'wp_ajax_gs_bio_rimuovi', 'gs_ajax_bio_rimuovi' );
function gs_ajax_bio_rimuovi() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_is_approved( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }
	$i = isset( $_POST['i'] ) ? (int) $_POST['i'] : -1;
	gs_bio_media_sposta_nel_cestino( $uid, $i );
	wp_send_json_success( array( 'message' => 'Elemento rimosso. Puoi ripristinarlo dal cestino della biografia.' ) );
}

add_action( 'wp_ajax_gs_bio_cestino_ripristina', 'gs_ajax_bio_cestino_ripristina' );
function gs_ajax_bio_cestino_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_is_approved( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }
	$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	if ( ! gs_bio_cestino_ripristina( $uid, $id ) ) {
		wp_send_json_error( array( 'message' => 'Elemento non trovato nel cestino.' ) );
	}
	wp_send_json_success( array( 'message' => 'Ripristinato nella tua biografia.' ) );
}

// -----------------------------------------------------------------------------
// PANNELLO — approvazione biografie
// -----------------------------------------------------------------------------
function gs_pannello_biografie() {
	if ( ! gs_can_manage() ) { return; }

	// Considera TUTTI gli utenti (anche amministratori e collaboratori), non solo
	// le sfogline: altrimenti la biografia del titolare resterebbe per sempre in attesa.
	$attesa    = gs_bio_in_attesa();
	$approvate = array();
	foreach ( get_users( array( 'orderby' => 'display_name' ) ) as $u ) {
		if ( 'approvata' === get_user_meta( $u->ID, 'gs_bio_stato', true ) ) { $approvate[] = $u; }
	}

	echo gs_box_open( 'Biografie della Vetrina (approvazione)', '', 'gs-box-biografie' );
	echo '<p class="gs-hint">La biografia e la foto compaiono nella Vetrina pubblica solo dopo l\'approvazione. Qui trovi quelle in attesa (da approvare o rifiutare) e quelle già approvate (che puoi sospendere). Vale per tutti, compresa la tua.</p>';

	echo '<h4>In attesa di approvazione</h4>';
	if ( ! $attesa ) {
		echo '<p class="gs-hint">Nessuna biografia in attesa.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-paginate" data-per-page="5">';
		foreach ( $attesa as $u ) {
			$bio  = gs_bio_get( $u->ID );
			$foto = gs_bio_foto_url( $u->ID );
			echo '<details class="gs-inbox-item gs-non-letto gs-attesa-forte" data-uid="' . (int) $u->ID . '">';
			echo '<summary class="gs-inbox-oggetto"><span class="gs-dot"></span> ' . esc_html( $u->display_name )
				. ' <span class="gs-msg-data">in attesa</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			if ( $foto ) { echo '<img class="gs-profile-foto" src="' . esc_url( $foto ) . '" alt="">'; }
			echo '<div class="gs-bio-testo">' . nl2br( esc_html( $bio['testo'] ) ) . '</div>';
			if ( $bio['media'] ) {
				echo '<div class="gs-bio-media-wrap">';
				foreach ( $bio['media'] as $m ) { echo gs_msg_media_html( $m['url'], isset( $m['type'] ) ? $m['type'] : 'image' ); }
				echo '</div>';
			}
			echo '<p><button class="gs-btn gs-btn-sm gs-bio-modera" data-uid="' . (int) $u->ID . '" data-esito="approvata">Approva</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-bio-modera" data-uid="' . (int) $u->ID . '" data-esito="rifiutata">Rifiuta</button> ';
			echo '<span class="gs-bio-mod-msg gs-richiesta-esito"></span></p>';
			echo '</div></details>';
		}
		echo '</div>';
	}

	echo '<h4 style="margin-top:14px">Approvate (visibili in Vetrina)</h4>';
	if ( ! $approvate ) {
		echo '<p class="gs-hint">Nessuna biografia approvata.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="5">';
		foreach ( $approvate as $u ) {
			$bio  = gs_bio_get( $u->ID );
			$foto = gs_bio_foto_url( $u->ID );
			echo '<details class="gs-inbox-item" data-uid="' . (int) $u->ID . '">';
			echo '<summary class="gs-inbox-oggetto">' . esc_html( $u->display_name )
				. ' <span class="gs-msg-data">approvata</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			if ( $foto ) { echo '<img class="gs-profile-foto" src="' . esc_url( $foto ) . '" alt="">'; }
			echo '<div class="gs-bio-testo">' . nl2br( esc_html( $bio['testo'] ) ) . '</div>';
			echo '<p><button class="gs-btn gs-btn-sm gs-btn-ghost gs-bio-modera" data-uid="' . (int) $u->ID . '" data-esito="sospesa">Sospendi (togli dalla Vetrina)</button> ';
			echo '<span class="gs-bio-mod-msg gs-richiesta-esito"></span></p>';
			echo '</div></details>';
		}
		echo '</div>';
	}
	echo gs_box_close();
}

add_action( 'wp_ajax_gs_bio_modera', 'gs_ajax_bio_modera' );
function gs_ajax_bio_modera() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$uid   = isset( $_POST['uid'] ) ? (int) $_POST['uid'] : 0;
	$esito = isset( $_POST['esito'] ) ? sanitize_key( wp_unslash( $_POST['esito'] ) ) : 'rifiutata';
	if ( ! $uid ) { wp_send_json_error( array( 'message' => 'Utente non valido.' ) ); }

	if ( 'approvata' === $esito ) {
		update_user_meta( $uid, 'gs_bio_stato', 'approvata' );
		$msg = 'Biografia approvata: ora è visibile nella Vetrina pubblica.';
		// Avviso Aeroplanino (punto 11, Ennio 21/08/2026: era uno dei "buchi" trovati).
		if ( function_exists( 'gs_accoda_volo' ) ) {
			gs_accoda_volo( $uid, 'LA TUA BIOGRAFIA È STATA APPROVATA! 🖼️', function_exists( 'gs_vetrina_url' ) ? gs_vetrina_url( $uid ) : '' );
		}
	} elseif ( 'sospesa' === $esito ) {
		update_user_meta( $uid, 'gs_bio_stato', 'in_attesa' );
		$msg = 'Biografia sospesa: non è più visibile nella Vetrina (torna in attesa).';
	} else {
		update_user_meta( $uid, 'gs_bio_stato', 'vuota' );
		$msg = 'Biografia rifiutata (non sarà pubblicata).';
	}
	// Avvisa la persona (non per la sospensione tecnica).
	$u = get_userdata( $uid );
	if ( $u && 'sospesa' !== $esito ) {
		$corpo = 'approvata' === $esito
			? "Ciao " . $u->display_name . ",\n\nla tua biografia è stata approvata ed è ora visibile nella tua Vetrina pubblica."
			: "Ciao " . $u->display_name . ",\n\nla tua biografia non è stata approvata. Puoi modificarla e inviarla di nuovo dal tuo pannello.";
		if ( function_exists( 'gs_mail_progetto' ) ) {
			gs_mail_progetto( $uid, 'messaggi', 'La tua biografia — Accademia della Sfoglia', $corpo );
		} elseif ( $u->user_email ) {
			wp_mail( $u->user_email, 'La tua biografia — Accademia della Sfoglia', $corpo );
		}
	}
	wp_send_json_success( array( 'message' => $msg ) );
}

add_action( 'wp_ajax_gs_bio_foto_rimuovi', 'gs_ajax_bio_foto_rimuovi' );
function gs_ajax_bio_foto_rimuovi() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_is_approved( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }
	gs_bio_foto_sposta_nel_cestino( $uid );
	wp_send_json_success( array( 'message' => 'Foto rimossa. Puoi ripristinarla dal cestino della biografia.' ) );
}
