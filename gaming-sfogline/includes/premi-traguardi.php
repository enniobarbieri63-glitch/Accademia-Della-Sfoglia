<?php
/**
 * premi-traguardi.php — Premi per Traguardo.
 *
 * Quando una sfoglina raggiunge un livello (una delle Insegne della Sfoglia)
 * oppure sblocca un badge, il titolare può aver preparato in anticipo un
 * premio da consegnarle in automatico: un video (link YouTube/Vimeo) oppure
 * un messaggio di testo, che arriva nella sua casella "Messaggi" — sempre
 * annunciato da un aeroplanino cliccabile che porta dritti lì.
 *
 * CPT gs_premio → meta:
 *  - gs_premio_tipo      ('livello' | 'badge')
 *  - gs_premio_livello   (int, indice del livello — se tipo = 'livello')
 *  - gs_premio_badge     (chiave badge — se tipo = 'badge')
 *  - gs_premio_azione    ('video' | 'testo' | 'sconto')
 *  - gs_premio_video_url (link YouTube/Vimeo — se azione = 'video')
 *  - gs_premio_sconto_pct (float 0-100 — se azione = 'sconto': percentuale
 *                          di sconto sui corsi di Rina, vedi sconto-corsi.php)
 *  - gs_premio_testo     (testo del messaggio — sempre presente: è anche
 *                          l'introduzione che accompagna un video, o il
 *                          messaggio di annuncio di uno sconto)
 *  - gs_premio_oggetto   (oggetto del messaggio che arriva alla sfoglina)
 * Il nome scelto dal titolare (solo per riconoscerlo nell'elenco) sta in
 * post_title.
 *
 * Ogni premio consegnato lascia una traccia sul messaggio creato
 * (meta gs_premio_origine = id del premio): serve a costruire lo "storico
 * premi" che la sfoglina vede nel suo pannello (gs_premio_storico_html()).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_premio_register_cpt' );
function gs_premio_register_cpt() {
	register_post_type( 'gs_premio', array(
		'labels'       => array( 'name' => 'Premi per Traguardo' ),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'title' ),
	) );
}

// -----------------------------------------------------------------------------
// Helper
// -----------------------------------------------------------------------------
function gs_premio_get( $id ) {
	$tipo = get_post_meta( $id, 'gs_premio_tipo', true );
	$azione = get_post_meta( $id, 'gs_premio_azione', true );
	return array(
		'id'         => $id,
		'nome'       => get_the_title( $id ),
		'tipo'       => in_array( $tipo, array( 'livello', 'badge' ), true ) ? $tipo : 'livello',
		'livello'    => (int) get_post_meta( $id, 'gs_premio_livello', true ),
		'badge'      => (string) get_post_meta( $id, 'gs_premio_badge', true ),
		'azione'     => in_array( $azione, array( 'video', 'testo', 'sconto' ), true ) ? $azione : 'testo',
		'video_url'  => (string) get_post_meta( $id, 'gs_premio_video_url', true ),
		'sconto_pct' => (float) get_post_meta( $id, 'gs_premio_sconto_pct', true ),
		'testo'      => (string) get_post_meta( $id, 'gs_premio_testo', true ),
		'oggetto'    => (string) get_post_meta( $id, 'gs_premio_oggetto', true ),
	);
}

function gs_premi_tutti() {
	return gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_premio',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'suppress_filters' => true,
	) ), 'gs_premio' );
}

/** I premi impostati per un certo indice di livello. */
function gs_premi_per_livello( $livello_index ) {
	$out = array();
	foreach ( gs_premi_tutti() as $p ) {
		$c = gs_premio_get( $p->ID );
		if ( 'livello' === $c['tipo'] && (int) $c['livello'] === (int) $livello_index ) { $out[] = $c; }
	}
	return $out;
}

/** I premi impostati per un certo badge. */
function gs_premi_per_badge( $badge_key ) {
	$out = array();
	foreach ( gs_premi_tutti() as $p ) {
		$c = gs_premio_get( $p->ID );
		if ( 'badge' === $c['tipo'] && $c['badge'] === $badge_key ) { $out[] = $c; }
	}
	return $out;
}

/**
 * Consegna un premio alla sfoglina: crea il messaggio nella sua casella
 * "Messaggi" (con il link video allegato, o l'aggiunta allo sconto
 * accumulato, se previsti) e fa volare l'aeroplanino cliccabile verso
 * quella casella. Il messaggio resta marcato (gs_premio_origine) così
 * compare anche nello "storico premi" della sfoglina.
 */
function gs_premio_consegna( $premio_id, $user_id ) {
	$c = gs_premio_get( $premio_id );

	if ( 'sconto' === $c['azione'] ) {
		if ( $c['sconto_pct'] <= 0 || ! function_exists( 'gs_sconto_aggiungi' ) ) { return false; }
		gs_sconto_aggiungi( $user_id, $c['sconto_pct'], $c['nome'] );
		$livello = function_exists( 'gs_sconto_livello_corrente' ) ? gs_sconto_livello_corrente( $user_id ) : '';
		$livello_lbl = function_exists( 'gs_sconto_livello_label' ) ? gs_sconto_livello_label( $livello ) : '';
		$oggetto = $c['oggetto'] ?: 'Hai guadagnato uno sconto sui corsi! 🎓';
		$corpo   = $c['testo'] . "\n\n+" . number_format( $c['sconto_pct'], 0 ) . '% di sconto sul corso ' . $livello_lbl . '.';
	} else {
		$oggetto = $c['oggetto'] ?: ( 'video' === $c['azione'] ? 'Un video speciale per te! 🎬' : 'Un messaggio speciale per te! 💌' );
		$corpo   = $c['testo'];
	}

	if ( ! function_exists( 'gs_invia_messaggio' ) ) { return false; }
	$mid = gs_invia_messaggio( $user_id, $oggetto, $corpo );
	if ( ! $mid ) { return false; }
	if ( 'video' === $c['azione'] && $c['video_url'] ) {
		update_post_meta( $mid, 'gs_premio_video_url', $c['video_url'] );
	}
	update_post_meta( $mid, 'gs_premio_origine', $premio_id );

	if ( function_exists( 'gs_accoda_volo' ) ) {
		if ( 'sconto' === $c['azione'] ) {
			$avviso = 'HAI GUADAGNATO UNO SCONTO SUI CORSI! 🎓';
		} else {
			$avviso = 'video' === $c['azione'] ? 'HAI RICEVUTO UN VIDEO SPECIALE! 🎬' : 'HAI RICEVUTO UN MESSAGGIO SPECIALE! 💌';
		}
		gs_accoda_volo( $user_id, $avviso, function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_messaggi' ) : home_url( '/' ) );
	}
	return $mid;
}

/**
 * Storico dei premi ricevuti da una sfoglina (tutti i tipi: video, testo,
 * sconto), per il suo pannello personale — solo titolo cliccabile, come
 * il resto della posta.
 */
function gs_premio_storico_html( $uid ) {
	if ( ! function_exists( 'gs_get_messaggi_utente' ) ) { return ''; }
	$tutti = array_filter( gs_get_messaggi_utente( $uid ), function ( $m ) {
		return (bool) get_post_meta( $m->ID, 'gs_premio_origine', true );
	} );
	if ( ! $tutti ) {
		return '<p class="gs-hint">Nessun premio ricevuto ancora.</p>';
	}
	$out = '<h4 style="margin-top:14px">🏆 Storico premi</h4>';
	$out .= '<div class="gs-inbox-lista gs-paginate" data-per-page="5">';
	foreach ( $tutti as $m ) {
		$out .= '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">' . esc_html( get_the_title( $m ) )
			. ' <span class="gs-msg-data">' . esc_html( date_i18n( 'j/m/Y', get_post_time( 'U', false, $m ) ) ) . '</span></summary>';
		$out .= '<div class="gs-inbox-corpo"><div class="gs-inbox-testo">' . nl2br( esc_html( gs_msg_clean( $m->post_content ) ) ) . '</div></div></details>';
	}
	$out .= '</div>';
	return $out;
}

// -----------------------------------------------------------------------------
// Trigger automatici: livello raggiunto, badge sbloccato
// -----------------------------------------------------------------------------
add_action( 'gs_level_up', 'gs_premio_su_livello', 30, 3 );
function gs_premio_su_livello( $user_id, $new_level, $old_level ) {
	foreach ( gs_premi_per_livello( $new_level ) as $c ) {
		gs_premio_consegna( $c['id'], $user_id );
	}
}

add_action( 'gs_badge_unlocked', 'gs_premio_su_badge', 30, 2 );
function gs_premio_su_badge( $user_id, $badge_key ) {
	foreach ( gs_premi_per_badge( $badge_key ) as $c ) {
		gs_premio_consegna( $c['id'], $user_id );
	}
}

// -----------------------------------------------------------------------------
// PANNELLO — creazione e gestione dei premi (titolare)
// -----------------------------------------------------------------------------
function gs_pannello_premi_traguardi() {
	if ( ! gs_can_manage() ) { return; }

	$livelli = gs_settings()['levels'];
	$badges  = function_exists( 'gs_get_badges_definitions' ) ? gs_get_badges_definitions() : array();

	echo gs_box_open( '🎁 Premi per Traguardo', '', 'gs-box-premi' );
	echo gs_sezione_aiuto( 'Prepara in anticipo un premio da consegnare in automatico quando una sfoglina raggiunge un livello o sblocca un badge: un video (link YouTube o Vimeo), un messaggio di testo, oppure — novità — una percentuale di sconto sui corsi di Rina Poletti, che si accumula badge dopo badge finché non viene spesa iscrivendosi a un corso vero. Lo sconto si riferisce sempre al livello corso su cui la sfoglina si trova (parte da Base, poi Avanzato, poi Professionale: vedi il pannello Iscrizioni in Calendario Corsi per applicarlo e far avanzare il livello). Ogni premio arriva nella sua casella "Messaggi" ed è sempre annunciato da un aeroplanino cliccabile, che la porta dritta lì; lo vede anche nel suo pannello personale, in "🎁 Premi e sconti sui corsi". Puoi impostare più premi per lo stesso traguardo: arrivano tutti insieme. È anche il posto giusto per presentare, a chi finisce il percorso (livello massimo), le opportunità vere — corso in presenza, corso online personalizzato, corso professionale — con un video di presentazione: puoi riusare uno dei video già caricati in "Libreria Video delle Lezioni" qui sopra, o incollarne uno nuovo.' );

	echo '<form class="gs-form gs-form-premio-crea" onsubmit="return false" style="background:var(--gs-uovo);padding:12px;border-radius:6px;margin-bottom:14px">';
	echo '<strong>Nuovo premio</strong>';
	echo '<p><label>Nome (solo per te, per riconoscerlo nell\'elenco)<br><input type="text" name="nome" autocomplete="off" style="width:100%" required placeholder="Es. Video di Rina per chi arriva a Sfoglina Esperta"></label></p>';
	echo '<p><label>Quando si attiva<br><select name="tipo" class="gs-premio-tipo-select"><option value="livello">Al raggiungimento di un livello</option><option value="badge">Allo sblocco di un badge</option></select></label></p>';
	echo '<div class="gs-premio-campo-livello"><p><label>Livello<br><select name="livello">';
	foreach ( $livelli as $i => $lv ) { echo '<option value="' . (int) $i . '">' . esc_html( $lv['simbolo'] . ' ' . $lv['titolo'] ) . '</option>'; }
	echo '</select></label></p></div>';
	echo '<div class="gs-premio-campo-badge" style="display:none"><p><label>Badge<br><select name="badge">';
	foreach ( $badges as $key => $b ) { echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $b['icon'] . ' ' . $b['label'] ) . '</option>'; }
	echo '</select></label></p></div>';
	echo '<p><label>Cosa consegna<br><select name="azione" class="gs-premio-azione-select"><option value="video">Un video (link YouTube o Vimeo)</option><option value="testo">Solo un messaggio di testo</option><option value="sconto">🎓 Una percentuale di sconto sui corsi</option></select></label></p>';
	echo '<div class="gs-premio-campo-video"><p><label>Link del video<br><input type="text" name="video_url" autocomplete="off" style="width:100%" placeholder="https://www.youtube.com/watch?v=…"></label></p></div>';
	echo '<div class="gs-premio-campo-sconto" style="display:none"><p><label>Percentuale di sconto<br><input type="number" name="sconto_pct" min="1" max="100" step="1" style="width:90px" placeholder="10"> %</label></p></div>';
	echo '<p><label>Oggetto del messaggio (facoltativo)<br><input type="text" name="oggetto" autocomplete="off" style="width:100%" placeholder="Es. Un video speciale per te! 🎬"></label></p>';
	echo '<p><label>Testo del messaggio<br><textarea name="testo" rows="3" style="width:100%" placeholder="Il testo che accompagna il video o lo sconto, o il messaggio da solo…"></textarea></label></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-premio-crea">Crea premio</button> <span class="gs-premio-crea-msg gs-richiesta-esito"></span></p>';
	echo '</form>';

	$premi = gs_premi_tutti();
	echo '<h4>Premi impostati (' . count( $premi ) . ')</h4>';
	if ( ! $premi ) {
		echo '<p class="gs-hint">Nessun premio impostato ancora.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="8">';
		foreach ( $premi as $p ) {
			$c = gs_premio_get( $p->ID );
			$traguardo_lbl = 'livello' === $c['tipo']
				? ( isset( $livelli[ $c['livello'] ] ) ? 'Livello: ' . $livelli[ $c['livello'] ]['simbolo'] . ' ' . $livelli[ $c['livello'] ]['titolo'] : 'Livello sconosciuto' )
				: ( isset( $badges[ $c['badge'] ] ) ? 'Badge: ' . $badges[ $c['badge'] ]['icon'] . ' ' . $badges[ $c['badge'] ]['label'] : 'Badge sconosciuto' );
			$azione_lbl = 'video' === $c['azione'] ? '🎬 Video' : ( 'sconto' === $c['azione'] ? '🎓 Sconto ' . number_format( $c['sconto_pct'], 0 ) . '%' : '💌 Solo testo' );
			echo '<details class="gs-inbox-item" data-premio="' . (int) $p->ID . '">';
			echo '<summary class="gs-inbox-oggetto">' . esc_html( $c['nome'] ) . ' <span class="gs-msg-data">' . esc_html( $traguardo_lbl ) . ' · ' . esc_html( $azione_lbl ) . '</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			echo '<form class="gs-form gs-form-premio-modifica" data-premio="' . (int) $p->ID . '" onsubmit="return false">';
			echo '<p><label>Nome<br><input type="text" name="nome" autocomplete="off" value="' . esc_attr( $c['nome'] ) . '" style="width:100%" required></label></p>';
			echo '<p><label>Quando si attiva<br><select name="tipo" class="gs-premio-tipo-select"><option value="livello" ' . selected( $c['tipo'], 'livello', false ) . '>Al raggiungimento di un livello</option><option value="badge" ' . selected( $c['tipo'], 'badge', false ) . '>Allo sblocco di un badge</option></select></label></p>';
			echo '<div class="gs-premio-campo-livello"' . ( 'livello' !== $c['tipo'] ? ' style="display:none"' : '' ) . '><p><label>Livello<br><select name="livello">';
			foreach ( $livelli as $i => $lv ) { echo '<option value="' . (int) $i . '" ' . selected( $c['livello'], $i, false ) . '>' . esc_html( $lv['simbolo'] . ' ' . $lv['titolo'] ) . '</option>'; }
			echo '</select></label></p></div>';
			echo '<div class="gs-premio-campo-badge"' . ( 'badge' !== $c['tipo'] ? ' style="display:none"' : '' ) . '><p><label>Badge<br><select name="badge">';
			foreach ( $badges as $key => $b ) { echo '<option value="' . esc_attr( $key ) . '" ' . selected( $c['badge'], $key, false ) . '>' . esc_html( $b['icon'] . ' ' . $b['label'] ) . '</option>'; }
			echo '</select></label></p></div>';
			echo '<p><label>Cosa consegna<br><select name="azione" class="gs-premio-azione-select"><option value="video" ' . selected( $c['azione'], 'video', false ) . '>Un video (link YouTube o Vimeo)</option><option value="testo" ' . selected( $c['azione'], 'testo', false ) . '>Solo un messaggio di testo</option><option value="sconto" ' . selected( $c['azione'], 'sconto', false ) . '>🎓 Una percentuale di sconto sui corsi</option></select></label></p>';
			echo '<div class="gs-premio-campo-video"' . ( 'video' !== $c['azione'] ? ' style="display:none"' : '' ) . '><p><label>Link del video<br><input type="text" name="video_url" autocomplete="off" value="' . esc_attr( $c['video_url'] ) . '" style="width:100%"></label></p></div>';
			echo '<div class="gs-premio-campo-sconto"' . ( 'sconto' !== $c['azione'] ? ' style="display:none"' : '' ) . '><p><label>Percentuale di sconto<br><input type="number" name="sconto_pct" min="1" max="100" step="1" value="' . esc_attr( $c['sconto_pct'] ?: '' ) . '" style="width:90px"> %</label></p></div>';
			echo '<p><label>Oggetto del messaggio<br><input type="text" name="oggetto" autocomplete="off" value="' . esc_attr( $c['oggetto'] ) . '" style="width:100%"></label></p>';
			echo '<p><label>Testo del messaggio<br><textarea name="testo" rows="3" style="width:100%">' . esc_textarea( $c['testo'] ) . '</textarea></label></p>';
			echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-premio-salva">Salva modifiche</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-premio-elimina" data-premio="' . (int) $p->ID . '">Elimina</button> <span class="gs-premio-riga-msg gs-richiesta-esito"></span></p>';
			echo '</form>';
			echo '</div></details>';
		}
		echo '</div>';
	}

	// Cestino.
	$trash = get_posts( array( 'post_type' => 'gs_premio', 'post_status' => 'trash', 'posts_per_page' => 50, 'suppress_filters' => true ) );
	echo '<div class="gs-sezione-cestino">';
	echo '<h4 class="gs-titolo-cestino">🗑️ Cestino</h4>';
	if ( ! $trash ) {
		echo '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		echo '<table class="gs-table gs-paginate" data-per-page="8"><thead><tr>' . gs_cestino_th_checkbox() . '<th>Premio</th><th></th></tr></thead><tbody>';
		foreach ( $trash as $p ) {
			echo '<tr data-premio="' . (int) $p->ID . '">' . gs_cestino_td_checkbox( $p->ID ) . '<td>' . esc_html( get_the_title( $p ) ) . '</td>';
			echo '<td><button class="gs-btn gs-btn-sm gs-btn-verde gs-premio-ripristina" data-premio="' . (int) $p->ID . '">Ripristina</button> <span class="gs-premio-trow-msg gs-richiesta-esito"></span></td></tr>';
		}
		echo '</tbody></table>';
		echo gs_cestino_azioni_bulk( 'gs_premio' );
	}
	echo '</div>';

	echo gs_box_close();
}

/** Legge e valida i campi comuni del form (crea/modifica) da $_POST. */
function gs_premio_leggi_post() {
	$nome = isset( $_POST['nome'] ) ? sanitize_text_field( wp_unslash( $_POST['nome'] ) ) : '';
	$tipo = isset( $_POST['tipo'] ) ? sanitize_key( wp_unslash( $_POST['tipo'] ) ) : 'livello';
	if ( ! in_array( $tipo, array( 'livello', 'badge' ), true ) ) { $tipo = 'livello'; }
	$livello = isset( $_POST['livello'] ) ? max( 0, (int) $_POST['livello'] ) : 0;
	$badge   = isset( $_POST['badge'] ) ? sanitize_key( wp_unslash( $_POST['badge'] ) ) : '';
	$azione  = isset( $_POST['azione'] ) ? sanitize_key( wp_unslash( $_POST['azione'] ) ) : 'testo';
	if ( ! in_array( $azione, array( 'video', 'testo', 'sconto' ), true ) ) { $azione = 'testo'; }
	$video_url  = isset( $_POST['video_url'] ) ? esc_url_raw( wp_unslash( $_POST['video_url'] ) ) : '';
	$sconto_pct = isset( $_POST['sconto_pct'] ) ? max( 0, min( 100, (float) $_POST['sconto_pct'] ) ) : 0;
	$oggetto    = isset( $_POST['oggetto'] ) ? sanitize_text_field( wp_unslash( $_POST['oggetto'] ) ) : '';
	$testo      = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';
	return compact( 'nome', 'tipo', 'livello', 'badge', 'azione', 'video_url', 'sconto_pct', 'oggetto', 'testo' );
}

add_action( 'wp_ajax_gs_premio_crea', 'gs_ajax_premio_crea' );
function gs_ajax_premio_crea() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$d = gs_premio_leggi_post();
	if ( '' === trim( $d['nome'] ) ) { wp_send_json_error( array( 'message' => 'Scrivi un nome per riconoscere questo premio.' ) ); }
	if ( 'badge' === $d['tipo'] && '' === $d['badge'] ) { wp_send_json_error( array( 'message' => 'Scegli un badge.' ) ); }
	if ( 'video' === $d['azione'] && '' === trim( $d['video_url'] ) ) { wp_send_json_error( array( 'message' => 'Scrivi il link del video.' ) ); }
	if ( 'sconto' === $d['azione'] && $d['sconto_pct'] <= 0 ) { wp_send_json_error( array( 'message' => 'Scrivi una percentuale di sconto maggiore di zero.' ) ); }
	if ( '' === trim( $d['testo'] ) ) { wp_send_json_error( array( 'message' => 'Scrivi il testo del messaggio.' ) ); }

	$id = wp_insert_post( array( 'post_type' => 'gs_premio', 'post_status' => 'publish', 'post_title' => $d['nome'] ) );
	if ( is_wp_error( $id ) || ! $id ) { wp_send_json_error( array( 'message' => 'Errore nella creazione.' ) ); }

	update_post_meta( $id, 'gs_premio_tipo', $d['tipo'] );
	update_post_meta( $id, 'gs_premio_livello', $d['livello'] );
	update_post_meta( $id, 'gs_premio_badge', $d['badge'] );
	update_post_meta( $id, 'gs_premio_azione', $d['azione'] );
	update_post_meta( $id, 'gs_premio_video_url', $d['video_url'] );
	update_post_meta( $id, 'gs_premio_sconto_pct', $d['sconto_pct'] );
	update_post_meta( $id, 'gs_premio_oggetto', $d['oggetto'] );
	update_post_meta( $id, 'gs_premio_testo', $d['testo'] );

	wp_send_json_success( array( 'message' => 'Premio creato.' ) );
}

add_action( 'wp_ajax_gs_premio_modifica', 'gs_ajax_premio_modifica' );
function gs_ajax_premio_modifica() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['premio'] ) ? (int) $_POST['premio'] : 0;
	if ( 'gs_premio' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Premio non valido.' ) ); }
	$d = gs_premio_leggi_post();
	if ( '' === trim( $d['nome'] ) ) { wp_send_json_error( array( 'message' => 'Scrivi un nome per riconoscere questo premio.' ) ); }
	if ( 'badge' === $d['tipo'] && '' === $d['badge'] ) { wp_send_json_error( array( 'message' => 'Scegli un badge.' ) ); }
	if ( 'video' === $d['azione'] && '' === trim( $d['video_url'] ) ) { wp_send_json_error( array( 'message' => 'Scrivi il link del video.' ) ); }
	if ( 'sconto' === $d['azione'] && $d['sconto_pct'] <= 0 ) { wp_send_json_error( array( 'message' => 'Scrivi una percentuale di sconto maggiore di zero.' ) ); }
	if ( '' === trim( $d['testo'] ) ) { wp_send_json_error( array( 'message' => 'Scrivi il testo del messaggio.' ) ); }

	wp_update_post( array( 'ID' => $id, 'post_title' => $d['nome'] ) );
	update_post_meta( $id, 'gs_premio_tipo', $d['tipo'] );
	update_post_meta( $id, 'gs_premio_livello', $d['livello'] );
	update_post_meta( $id, 'gs_premio_badge', $d['badge'] );
	update_post_meta( $id, 'gs_premio_azione', $d['azione'] );
	update_post_meta( $id, 'gs_premio_video_url', $d['video_url'] );
	update_post_meta( $id, 'gs_premio_sconto_pct', $d['sconto_pct'] );
	update_post_meta( $id, 'gs_premio_oggetto', $d['oggetto'] );
	update_post_meta( $id, 'gs_premio_testo', $d['testo'] );

	wp_send_json_success( array( 'message' => 'Premio aggiornato.' ) );
}

add_action( 'wp_ajax_gs_premio_elimina', 'gs_ajax_premio_elimina' );
function gs_ajax_premio_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['premio'] ) ? (int) $_POST['premio'] : 0;
	if ( 'gs_premio' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Premio non valido.' ) ); }
	wp_trash_post( $id );
	wp_send_json_success( array( 'message' => 'Spostato nel cestino.' ) );
}

add_action( 'wp_ajax_gs_premio_ripristina', 'gs_ajax_premio_ripristina' );
function gs_ajax_premio_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['premio'] ) ? (int) $_POST['premio'] : 0;
	if ( 'gs_premio' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Premio non valido.' ) ); }
	wp_untrash_post( $id );
	wp_send_json_success( array( 'message' => 'Premio ripristinato.' ) );
}
