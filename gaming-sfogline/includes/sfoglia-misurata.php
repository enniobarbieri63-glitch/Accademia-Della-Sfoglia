<?php
/**
 * sfoglia-misurata.php — La Sfoglia Misurata.
 *
 * Una sfida periodica basata su un numero, non su un voto: spessore della
 * sfoglia in millimetri, tempo di tiratura in minuti, grammi di farina per
 * uovo — quello che il titolare sceglie di misurare. Ogni sfoglina dichiara
 * la propria misura (a fiducia, come tutte le missioni del sito, con una
 * foto facoltativa a corredo) e la classifica si ordina da sola in base al
 * numero, non al gradimento: complementare alle Sfide normali (che restano
 * un voto di gusto/qualità), non le sostituisce.
 *
 * CPT gs_misura → meta:
 *  - gs_misura_cosa   (cosa si misura, es. "Spessore della sfoglia")
 *  - gs_misura_unita  (es. "mm", "minuti", "g")
 *  - gs_misura_modo   ('meno' = vince il numero più basso, 'piu' = vince il
 *                       più alto, 'target' = vince chi si avvicina di più a
 *                       un valore target)
 *  - gs_misura_target (float, solo se modo = 'target')
 *  - gs_misura_chiusa ('1' quando il titolare ha già assegnato il premio)
 *  - gs_misura_voci   (array di {user_id, valore, media, nota, data}: una
 *                       voce per sfoglina, il reinvio sovrascrive la
 *                       precedente finché la sfida non è chiusa)
 * Titolo e descrizione della sfida in post_title / post_content.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_misura_register_cpt' );
function gs_misura_register_cpt() {
	register_post_type( 'gs_misura', array(
		'labels'       => array( 'name' => 'La Sfoglia Misurata' ),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'title', 'editor' ),
	) );
}

// -----------------------------------------------------------------------------
// Helper
// -----------------------------------------------------------------------------

function gs_misura_get( $id ) {
	$voci = get_post_meta( $id, 'gs_misura_voci', true );
	return array(
		'id'          => $id,
		'titolo'      => get_the_title( $id ),
		'descrizione' => get_post( $id ) ? get_post( $id )->post_content : '',
		'cosa'        => (string) get_post_meta( $id, 'gs_misura_cosa', true ),
		'unita'       => (string) get_post_meta( $id, 'gs_misura_unita', true ),
		'modo'        => get_post_meta( $id, 'gs_misura_modo', true ) ?: 'meno',
		'target'      => (float) get_post_meta( $id, 'gs_misura_target', true ),
		'chiusa'      => '1' === get_post_meta( $id, 'gs_misura_chiusa', true ),
		'voci'        => is_array( $voci ) ? $voci : array(),
	);
}

/** Tutte le sfide di misura, le attive prima (le più recenti in cima), poi le chiuse (le più recenti in cima). */
function gs_misure_tutte() {
	$tutte = gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_misura',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'suppress_filters' => true,
	) ), 'gs_misura' );
	usort( $tutte, function ( $a, $b ) {
		$ca = '1' === get_post_meta( $a->ID, 'gs_misura_chiusa', true );
		$cb = '1' === get_post_meta( $b->ID, 'gs_misura_chiusa', true );
		if ( $ca !== $cb ) { return $ca ? 1 : -1; } // attive prima
		return strtotime( $b->post_date ) <=> strtotime( $a->post_date );
	} );
	return $tutte;
}

/** Solo le sfide ancora attive (non chiuse). */
function gs_misure_attive() {
	return array_values( array_filter( gs_misure_tutte(), function ( $p ) {
		return '1' !== get_post_meta( $p->ID, 'gs_misura_chiusa', true );
	} ) );
}

/** La voce inviata da una sfoglina per questa sfida, o null se non ha ancora partecipato. */
function gs_misura_voce_utente( $id, $uid ) {
	$uid = (int) $uid;
	foreach ( gs_misura_get( $id )['voci'] as $v ) {
		if ( (int) $v['user_id'] === $uid ) { return $v; }
	}
	return null;
}

/**
 * La classifica della sfida, ordinata secondo il suo "modo": crescente per
 * 'meno', decrescente per 'piu', per distanza dal target per 'target'.
 */
function gs_misura_classifica( $id ) {
	$c    = gs_misura_get( $id );
	$voci = $c['voci'];
	usort( $voci, function ( $a, $b ) use ( $c ) {
		if ( 'piu' === $c['modo'] ) { return (float) $b['valore'] <=> (float) $a['valore']; }
		if ( 'target' === $c['modo'] ) { return abs( (float) $a['valore'] - $c['target'] ) <=> abs( (float) $b['valore'] - $c['target'] ); }
		return (float) $a['valore'] <=> (float) $b['valore']; // 'meno'
	} );
	return $voci;
}

/** Salva o aggiorna la misura di una sfoglina (il reinvio sovrascrive). */
function gs_misura_invia( $id, $uid, $valore, $media = null, $nota = '' ) {
	$c    = gs_misura_get( $id );
	if ( $c['chiusa'] ) { return false; }
	$voci = $c['voci'];
	$uid  = (int) $uid;
	$voce = array( 'user_id' => $uid, 'valore' => (float) $valore, 'media' => $media, 'nota' => $nota, 'data' => current_time( 'mysql' ) );

	$trovata = false;
	foreach ( $voci as &$v ) {
		if ( (int) $v['user_id'] === $uid ) { $v = $voce; $trovata = true; break; }
	}
	unset( $v );
	if ( ! $trovata ) { $voci[] = $voce; }

	update_post_meta( $id, 'gs_misura_voci', $voci );
	return true;
}

/**
 * Chiude la sfida e premia chi è prima in classifica: badge dedicato (chiave
 * dinamica, come i badge dei Percorsi) + punti + avviso + email. Non
 * assegnabile due volte sulla stessa sfida.
 */
function gs_misura_chiudi( $id ) {
	$c = gs_misura_get( $id );
	if ( $c['chiusa'] || ! $c['voci'] ) { return false; }

	$classifica = gs_misura_classifica( $id );
	$vincitrice = $classifica[0];
	$uid        = (int) $vincitrice['user_id'];

	update_post_meta( $id, 'gs_misura_chiusa', '1' );

	$badge_key = 'misura_' . $id;
	$owned     = gs_get_user_badges( $uid );
	if ( ! in_array( $badge_key, $owned, true ) ) {
		$owned[] = $badge_key;
		update_user_meta( $uid, 'gs_badges', $owned );
		update_user_meta( $uid, 'gs_badge_label_' . $badge_key, '📏 La Sfoglia Misurata: ' . $c['titolo'] );

		$log = get_user_meta( $uid, 'gs_badges_log', true );
		$log = is_array( $log ) ? $log : array();
		$log[ $badge_key ] = current_time( 'timestamp' );
		update_user_meta( $uid, 'gs_badges_log', $log );

		// I punti stanno DENTRO questo controllo, non fuori: stessa
		// disposizione già corretta in giuria-turno.php (G1, 26/08/2026).
		// Chi ha già il badge di questo turno ha già avuto anche i punti.
		gs_add_points( $uid, gs_get_points_value( 'misura_vinta', 30 ), 'La Sfoglia Misurata: ' . $c['titolo'] );
	}

	if ( function_exists( 'gs_accoda_volo' ) ) {
		gs_accoda_volo( $uid, 'HAI VINTO "' . mb_strtoupper( $c['titolo'] ) . '"! 📏', gs_pagina_url( 'gs_page_misurata' ) );
	}
	$u = get_userdata( $uid );
	if ( $u ) {
		$oggetto = 'Hai vinto "' . $c['titolo'] . '"! 📏 — Accademia della Sfoglia';
		$corpo   = "Complimenti " . $u->display_name . "!\n\nHai la migliore misura di \"" . $c['cosa'] . "\" nella sfida \"" . $c['titolo'] . "\": " . $vincitrice['valore'] . ' ' . $c['unita'] . ".\n\nHai sbloccato un badge dedicato e qualche punto in più.\n\nAccademia della Sfoglia";
		if ( function_exists( 'gs_mail_progetto' ) ) {
			gs_mail_progetto( $uid, 'livelli', $oggetto, $corpo );
		} elseif ( $u->user_email ) {
			wp_mail( $u->user_email, $oggetto, $corpo );
		}
	}
	return $uid;
}

// -----------------------------------------------------------------------------
// [gs_sfoglia_misurata] — lato sfoglina
// -----------------------------------------------------------------------------
add_shortcode( 'gs_sfoglia_misurata', 'gs_sc_sfoglia_misurata' );
function gs_sc_sfoglia_misurata() {
	if ( $g = gs_gate_riservato() ) { return $g; }
	if ( $g = gs_blackout_gate() ) { return $g; }

	$out = gs_box_open( '📏 La Sfoglia Misurata' );
	$out .= gs_sezione_aiuto( 'Sfide di tecnica pura, non di gradimento: dichiara la tua misura (a fiducia, come per tutte le altre missioni del sito) e la classifica si ordina da sola in base al numero. Puoi rimandare una misura nuova finché la sfida non viene chiusa: l\'ultima che invii sostituisce la precedente.' );

	$uid    = get_current_user_id();
	$attive = gs_misure_attive();

	if ( ! $attive ) {
		$out .= '<p class="gs-hint">Nessuna sfida di misura attiva al momento. Torna presto.</p>';
	}
	foreach ( $attive as $p ) {
		$c        = gs_misura_get( $p->ID );
		$mia      = gs_misura_voce_utente( $p->ID, $uid );
		$classifica = gs_misura_classifica( $p->ID );

		$out .= '<div class="gs-todo-riquadro">';
		$out .= '<p class="gs-hint" style="font-size:16px"><strong>' . esc_html( $c['titolo'] ) . '</strong><br>Si misura: <strong>' . esc_html( $c['cosa'] ) . '</strong> (' . esc_html( $c['unita'] ) . ') — vince ' . ( 'piu' === $c['modo'] ? 'il valore più alto' : ( 'target' === $c['modo'] ? 'chi si avvicina di più a ' . esc_html( (string) $c['target'] ) . ' ' . esc_html( $c['unita'] ) : 'il valore più basso' ) ) . '.</p>';
		if ( $c['descrizione'] ) { $out .= '<p class="gs-hint">' . esc_html( $c['descrizione'] ) . '</p>'; }

		$out .= '<form class="gs-form gs-form-misura" data-misura="' . (int) $p->ID . '" onsubmit="return false">';
		$out .= '<p><label>La tua misura (' . esc_html( $c['unita'] ) . ')<br><input type="number" step="any" name="valore" value="' . ( $mia ? esc_attr( $mia['valore'] ) : '' ) . '" style="width:140px" required></label></p>';
		$out .= '<p><label>Una riga di nota (facoltativa)<br><input type="text" name="nota" value="' . ( $mia ? esc_attr( $mia['nota'] ) : '' ) . '" autocomplete="off" style="width:100%"></label></p>';
		$out .= '<p>Foto di prova, facoltativa: ' . gs_msg_file_field() . '</p>';
		$out .= '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-misura-invia">' . ( $mia ? 'Aggiorna la mia misura' : 'Invia la mia misura' ) . '</button> <span class="gs-misura-msg gs-richiesta-esito"></span></p>';
		$out .= '</form>';

		if ( $classifica ) {
			$out .= '<strong>Classifica del momento</strong><ol class="gs-todo-list">';
			foreach ( array_slice( $classifica, 0, 10 ) as $i => $v ) {
				$au = get_userdata( $v['user_id'] );
				$medaglia = 0 === $i ? '🥇 ' : ( 1 === $i ? '🥈 ' : ( 2 === $i ? '🥉 ' : '' ) );
				$out .= '<li class="gs-todo-item"><span>' . $medaglia . esc_html( $au ? $au->display_name : '—' ) . ' — <strong>' . esc_html( $v['valore'] ) . ' ' . esc_html( $c['unita'] ) . '</strong></span></li>';
			}
			$out .= '</ol>';
		}
		$out .= '</div>';
	}

	$chiuse = array_values( array_filter( gs_misure_tutte(), function ( $p ) { return '1' === get_post_meta( $p->ID, 'gs_misura_chiusa', true ); } ) );
	if ( $chiuse ) {
		$out .= '<details class="gs-corso-descr"><summary>🏆 Sfide di misura concluse (' . count( $chiuse ) . ')</summary>';
		$out .= '<div class="gs-gallery gs-paginate" data-per-page="6" style="margin-top:8px">';
		foreach ( $chiuse as $p ) {
			$c          = gs_misura_get( $p->ID );
			$classifica = gs_misura_classifica( $p->ID );
			$out .= '<div class="gs-card"><div class="gs-card-body">';
			$out .= '<h4>' . esc_html( $c['titolo'] ) . '</h4>';
			$out .= '<p class="gs-card-author">' . esc_html( $c['cosa'] ) . '</p>';
			if ( $classifica ) {
				$au = get_userdata( $classifica[0]['user_id'] );
				$out .= '<p class="gs-hint">🥇 ' . esc_html( $au ? $au->display_name : '—' ) . ' — ' . esc_html( $classifica[0]['valore'] ) . ' ' . esc_html( $c['unita'] ) . '</p>';
			}
			$out .= '</div></div>';
		}
		$out .= '</div></details>';
	}

	$out .= gs_box_close();
	return $out;
}

add_action( 'wp_ajax_gs_misura_invia', 'gs_ajax_misura_invia' );
function gs_ajax_misura_invia() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }
	$id = isset( $_POST['misura'] ) ? (int) $_POST['misura'] : 0;
	if ( 'gs_misura' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Sfida non valida.' ) ); }
	if ( gs_misura_get( $id )['chiusa'] ) { wp_send_json_error( array( 'message' => 'Questa sfida è già stata chiusa.' ) ); }

	$valore = isset( $_POST['valore'] ) ? sanitize_text_field( wp_unslash( $_POST['valore'] ) ) : '';
	if ( '' === trim( $valore ) || ! is_numeric( $valore ) ) { wp_send_json_error( array( 'message' => 'Scrivi un numero valido.' ) ); }

	$nota = isset( $_POST['nota'] ) ? sanitize_text_field( wp_unslash( $_POST['nota'] ) ) : '';
	if ( function_exists( 'gs_msg_clean' ) ) { $nota = gs_msg_clean( $nota ); }

	$media = null;
	if ( function_exists( 'gs_msg_upload' ) && ! empty( $_FILES['media']['name'] ) ) {
		$m = gs_msg_upload( 'media' );
		if ( is_wp_error( $m ) ) { wp_send_json_error( array( 'message' => $m->get_error_message() ) ); }
		if ( is_array( $m ) ) { $media = array( 'url' => $m['url'], 'type' => $m['type'] ); }
	} else {
		$precedente = gs_misura_voce_utente( $id, $uid );
		if ( $precedente && ! empty( $precedente['media'] ) ) { $media = $precedente['media']; } // reinvio senza nuova foto: tiene la precedente
	}

	gs_misura_invia( $id, $uid, (float) $valore, $media, $nota );
	wp_send_json_success( array( 'message' => 'Misura inviata.' ) );
}

// -----------------------------------------------------------------------------
// PANNELLO — creazione e chiusura delle sfide di misura (titolare)
// -----------------------------------------------------------------------------
function gs_pannello_sfoglia_misurata() {
	if ( ! gs_can_manage() ) { return; }

	echo gs_box_open( '📏 La Sfoglia Misurata', '', 'gs-box-misurata' );
	echo '<p class="gs-hint">Crea una sfida basata su un numero, non su un voto: scegli cosa si misura (es. "Spessore della sfoglia", unità "mm") e chi vince ("il più basso", "il più alto", oppure "il più vicino a" un valore che indichi tu). Le sfogline dichiarano la propria misura, a fiducia; tu decidi quando chiudere la sfida e il sistema premia da solo chi è prima in classifica in quel momento.</p>';

	echo '<form class="gs-form gs-form-misura-nuova" onsubmit="return false" style="background:var(--gs-uovo);padding:12px;border-radius:6px;margin-bottom:14px">';
	echo '<strong>Nuova sfida di misura</strong>';
	echo '<p><label>Nome della sfida<br><input type="text" name="titolo" autocomplete="off" style="width:100%" required placeholder="Es. Sfida di Marzo: la sfoglia più sottile"></label></p>';
	echo '<p><label>Cosa si misura<br><input type="text" name="cosa" autocomplete="off" style="width:100%" required placeholder="Es. Spessore della sfoglia"></label></p>';
	echo '<p style="display:flex;gap:12px;flex-wrap:wrap"><label>Unità<br><input type="text" name="unita" autocomplete="off" style="width:100px" placeholder="mm"></label>';
	echo '<label>Vince chi ha<br><select name="modo"><option value="meno">il valore più basso</option><option value="piu">il valore più alto</option><option value="target">il valore più vicino a…</option></select></label>';
	echo '<label>Valore target (solo se "più vicino a")<br><input type="number" step="any" name="target" style="width:120px"></label></p>';
	echo '<p><label>Descrizione (facoltativa)<br><textarea name="descrizione" rows="2" style="width:100%"></textarea></label></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-misura-crea">Crea sfida</button> <span class="gs-misura-crea-msg gs-richiesta-esito"></span></p>';
	echo '</form>';

	$tutte = gs_misure_tutte();
	echo '<h4>Sfide create (' . count( $tutte ) . ')</h4>';
	if ( ! $tutte ) {
		echo '<p class="gs-hint">Nessuna sfida ancora creata.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="8">';
		foreach ( $tutte as $p ) {
			$c          = gs_misura_get( $p->ID );
			$classifica = gs_misura_classifica( $p->ID );
			echo '<details class="gs-inbox-item" data-misura="' . (int) $p->ID . '">';
			echo '<summary class="gs-inbox-oggetto">' . esc_html( $c['titolo'] ) . ( $c['chiusa'] ? ' <span class="gs-msg-tag">chiusa</span>' : ' <span class="gs-msg-tag">attiva</span>' ) . ' <span class="gs-msg-data">' . count( $c['voci'] ) . ' misure ricevute</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			echo '<p class="gs-hint">Si misura: <strong>' . esc_html( $c['cosa'] ) . '</strong> (' . esc_html( $c['unita'] ) . ') — vince ' . ( 'piu' === $c['modo'] ? 'il più alto' : ( 'target' === $c['modo'] ? 'il più vicino a ' . esc_html( (string) $c['target'] ) : 'il più basso' ) ) . '.</p>';

			echo '<form class="gs-form gs-form-misura-modifica" data-misura="' . (int) $p->ID . '" onsubmit="return false">';
			echo '<p><label>Nome della sfida<br><input type="text" name="titolo" autocomplete="off" value="' . esc_attr( $c['titolo'] ) . '" style="width:100%" required></label></p>';
			echo '<p><label>Cosa si misura<br><input type="text" name="cosa" autocomplete="off" value="' . esc_attr( $c['cosa'] ) . '" style="width:100%" required></label></p>';
			echo '<p style="display:flex;gap:12px;flex-wrap:wrap"><label>Unità<br><input type="text" name="unita" autocomplete="off" value="' . esc_attr( $c['unita'] ) . '" style="width:100px"></label>';
			echo '<label>Vince chi ha<br><select name="modo"><option value="meno" ' . selected( $c['modo'], 'meno', false ) . '>il valore più basso</option><option value="piu" ' . selected( $c['modo'], 'piu', false ) . '>il valore più alto</option><option value="target" ' . selected( $c['modo'], 'target', false ) . '>il valore più vicino a…</option></select></label>';
			echo '<label>Valore target<br><input type="number" step="any" name="target" value="' . esc_attr( (string) $c['target'] ) . '" style="width:120px"></label></p>';
			echo '<p><label>Descrizione<br><textarea name="descrizione" rows="2" style="width:100%">' . esc_textarea( $c['descrizione'] ) . '</textarea></label></p>';
			echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-misura-salva">Salva modifiche</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-misura-elimina" data-misura="' . (int) $p->ID . '">Elimina</button> <span class="gs-misura-riga-msg gs-richiesta-esito"></span></p>';
			echo '</form>';

			if ( $classifica ) {
				echo '<ol class="gs-todo-list">';
				foreach ( $classifica as $i => $v ) {
					$au = get_userdata( $v['user_id'] );
					$medaglia = 0 === $i ? '🥇 ' : ( 1 === $i ? '🥈 ' : ( 2 === $i ? '🥉 ' : '' ) );
					echo '<li class="gs-todo-item"><span>' . $medaglia . esc_html( $au ? $au->display_name : '—' ) . ' — <strong>' . esc_html( $v['valore'] ) . ' ' . esc_html( $c['unita'] ) . '</strong>' . ( ! empty( $v['nota'] ) ? ' — ' . esc_html( $v['nota'] ) : '' ) . '</span></li>';
				}
				echo '</ol>';
			} else {
				echo '<p class="gs-hint">Nessuna misura ricevuta ancora.</p>';
			}

			if ( ! $c['chiusa'] ) {
				echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-misura-chiudi" data-misura="' . (int) $p->ID . '"' . ( ! $classifica ? ' disabled' : '' ) . '>Chiudi e premia chi è prima</button> <span class="gs-misura-chiudi-msg gs-richiesta-esito"></span></p>';
			}
			echo '</div></details>';
		}
		echo '</div>';
	}

	// Cestino.
	$trash = get_posts( array( 'post_type' => 'gs_misura', 'post_status' => 'trash', 'posts_per_page' => 50, 'suppress_filters' => true ) );
	echo '<div class="gs-sezione-cestino">';
	echo '<h4 class="gs-titolo-cestino">🗑️ Cestino</h4>';
	if ( ! $trash ) {
		echo '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		echo '<table class="gs-table gs-paginate" data-per-page="8"><thead><tr>' . gs_cestino_th_checkbox() . '<th>Sfida</th><th></th></tr></thead><tbody>';
		foreach ( $trash as $p ) {
			echo '<tr data-misura="' . (int) $p->ID . '">' . gs_cestino_td_checkbox( $p->ID ) . '<td>' . esc_html( get_the_title( $p ) ) . '</td>';
			echo '<td><button class="gs-btn gs-btn-sm gs-btn-verde gs-misura-ripristina" data-misura="' . (int) $p->ID . '">Ripristina</button> <span class="gs-misura-trow-msg gs-richiesta-esito"></span></td></tr>';
		}
		echo '</tbody></table>';
		echo gs_cestino_azioni_bulk( 'gs_misura' );
	}
	echo '</div>';

	echo gs_box_close();
}

add_action( 'wp_ajax_gs_misura_modifica', 'gs_ajax_misura_modifica' );
function gs_ajax_misura_modifica() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['misura'] ) ? (int) $_POST['misura'] : 0;
	if ( 'gs_misura' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Sfida non valida.' ) ); }

	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	if ( '' === trim( $titolo ) ) { wp_send_json_error( array( 'message' => 'Scrivi il nome della sfida.' ) ); }
	$cosa = isset( $_POST['cosa'] ) ? sanitize_text_field( wp_unslash( $_POST['cosa'] ) ) : '';
	if ( '' === trim( $cosa ) ) { wp_send_json_error( array( 'message' => 'Scrivi cosa si misura.' ) ); }
	$unita       = isset( $_POST['unita'] ) ? sanitize_text_field( wp_unslash( $_POST['unita'] ) ) : '';
	$modo        = isset( $_POST['modo'] ) ? sanitize_key( wp_unslash( $_POST['modo'] ) ) : 'meno';
	if ( ! in_array( $modo, array( 'meno', 'piu', 'target' ), true ) ) { $modo = 'meno'; }
	$target      = isset( $_POST['target'] ) ? (float) $_POST['target'] : 0;
	$descrizione = isset( $_POST['descrizione'] ) ? sanitize_textarea_field( wp_unslash( $_POST['descrizione'] ) ) : '';

	wp_update_post( array( 'ID' => $id, 'post_title' => $titolo, 'post_content' => $descrizione ) );
	update_post_meta( $id, 'gs_misura_cosa', $cosa );
	update_post_meta( $id, 'gs_misura_unita', $unita );
	update_post_meta( $id, 'gs_misura_modo', $modo );
	update_post_meta( $id, 'gs_misura_target', $target );

	wp_send_json_success( array( 'message' => 'Sfida aggiornata.' ) );
}

add_action( 'wp_ajax_gs_misura_elimina', 'gs_ajax_misura_elimina' );
function gs_ajax_misura_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['misura'] ) ? (int) $_POST['misura'] : 0;
	if ( 'gs_misura' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Sfida non valida.' ) ); }
	wp_trash_post( $id );
	wp_send_json_success( array( 'message' => 'Spostata nel cestino.' ) );
}

add_action( 'wp_ajax_gs_misura_ripristina', 'gs_ajax_misura_ripristina' );
function gs_ajax_misura_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['misura'] ) ? (int) $_POST['misura'] : 0;
	if ( 'gs_misura' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Sfida non valida.' ) ); }
	wp_untrash_post( $id );
	wp_send_json_success( array( 'message' => 'Sfida ripristinata.' ) );
}

add_action( 'wp_ajax_gs_misura_crea', 'gs_ajax_misura_crea' );
function gs_ajax_misura_crea() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	if ( '' === trim( $titolo ) ) { wp_send_json_error( array( 'message' => 'Scrivi il nome della sfida.' ) ); }
	$cosa = isset( $_POST['cosa'] ) ? sanitize_text_field( wp_unslash( $_POST['cosa'] ) ) : '';
	if ( '' === trim( $cosa ) ) { wp_send_json_error( array( 'message' => 'Scrivi cosa si misura.' ) ); }
	$unita       = isset( $_POST['unita'] ) ? sanitize_text_field( wp_unslash( $_POST['unita'] ) ) : '';
	$modo        = isset( $_POST['modo'] ) ? sanitize_key( wp_unslash( $_POST['modo'] ) ) : 'meno';
	if ( ! in_array( $modo, array( 'meno', 'piu', 'target' ), true ) ) { $modo = 'meno'; }
	$target      = isset( $_POST['target'] ) ? (float) $_POST['target'] : 0;
	$descrizione = isset( $_POST['descrizione'] ) ? sanitize_textarea_field( wp_unslash( $_POST['descrizione'] ) ) : '';

	$id = wp_insert_post( array(
		'post_type'    => 'gs_misura',
		'post_status'  => 'publish',
		'post_title'   => $titolo,
		'post_content' => $descrizione,
	) );
	if ( is_wp_error( $id ) || ! $id ) { wp_send_json_error( array( 'message' => 'Errore nella creazione.' ) ); }

	update_post_meta( $id, 'gs_misura_cosa', $cosa );
	update_post_meta( $id, 'gs_misura_unita', $unita );
	update_post_meta( $id, 'gs_misura_modo', $modo );
	if ( 'target' === $modo ) { update_post_meta( $id, 'gs_misura_target', $target ); }

	wp_send_json_success( array( 'message' => 'Sfida creata.' ) );
}

add_action( 'wp_ajax_gs_misura_chiudi', 'gs_ajax_misura_chiudi' );
function gs_ajax_misura_chiudi() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['misura'] ) ? (int) $_POST['misura'] : 0;
	if ( 'gs_misura' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Sfida non valida.' ) ); }

	$vincitrice = gs_misura_chiudi( $id );
	if ( ! $vincitrice ) { wp_send_json_error( array( 'message' => 'Impossibile chiudere: sfida già chiusa o senza misure ricevute.' ) ); }

	$u = get_userdata( $vincitrice );
	wp_send_json_success( array( 'message' => 'Sfida chiusa. Ha vinto ' . ( $u ? $u->display_name : 'una sfoglina' ) . '.' ) );
}
