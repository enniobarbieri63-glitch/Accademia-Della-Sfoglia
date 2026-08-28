<?php
/**
 * giuria-turno.php — La Giuria a Turno.
 *
 * Una gara diversa dalla Sfida della Settimana: qui non vota chiunque, vota
 * una piccola giuria che il titolare assegna apposta per quel turno — un
 * gruppo diverso ogni volta, a rotazione. Chi giudica lo fa a viso aperto
 * (il proprio nome resta accanto al voto, mai anonimo) e deve scrivere
 * perché ha dato quel punteggio: non un clic veloce, un giudizio di cui si
 * risponde. Pensata per responsabilizzare chi vota, non solo chi partecipa.
 *
 * CPT gs_giuria → meta:
 *  - gs_giuria_giudici (array di user_id: le sfogline assegnate come
 *                        giudici di questo turno, decise dal titolare)
 *  - gs_giuria_opere   (array di {id, user_id, titolo, media, testo, data}:
 *                        le opere in gara, una per sfoglina, il reinvio
 *                        sovrascrive la propria finché il turno è aperto)
 *  - gs_giuria_voti    (array di {opera_id, giudice_id, stelle, motivazione,
 *                        data}: un voto per coppia giudice/opera, sempre
 *                        con nome e motivazione — mai anonimo)
 *  - gs_giuria_chiusa  ('1' quando il titolare ha già assegnato il premio)
 * Titolo e descrizione del turno in post_title / post_content.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_giuria_register_cpt' );
function gs_giuria_register_cpt() {
	register_post_type( 'gs_giuria', array(
		'labels'       => array( 'name' => 'La Giuria a Turno' ),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'title', 'editor' ),
	) );
}

// -----------------------------------------------------------------------------
// Helper
// -----------------------------------------------------------------------------

function gs_giuria_get( $id ) {
	$giudici = get_post_meta( $id, 'gs_giuria_giudici', true );
	$opere   = get_post_meta( $id, 'gs_giuria_opere', true );
	$voti    = get_post_meta( $id, 'gs_giuria_voti', true );
	$tipo    = get_post_meta( $id, 'gs_giuria_tipo', true );
	return array(
		'id'          => $id,
		'titolo'      => get_the_title( $id ),
		'descrizione' => get_post( $id ) ? get_post( $id )->post_content : '',
		'tipo'        => in_array( $tipo, array( 'sfogline', 'maestri' ), true ) ? $tipo : 'sfogline',
		'giudici'     => is_array( $giudici ) ? array_map( 'intval', $giudici ) : array(),
		'opere'       => is_array( $opere ) ? $opere : array(),
		'voti'        => is_array( $voti ) ? $voti : array(),
		'chiusa'      => '1' === get_post_meta( $id, 'gs_giuria_chiusa', true ),
	);
}

/** True se l'utente indicato gestisce il portale (titolare o collaboratore), indipendentemente da chi è collegato ora. */
function gs_e_maestro( $uid ) {
	return user_can( $uid, 'manage_options' ) || user_can( $uid, 'gs_manage_gaming' );
}

/** Tutti i turni, gli attivi prima (i più recenti in cima), poi i chiusi. */
function gs_giuria_tutti() {
	$tutti = gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_giuria',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'suppress_filters' => true,
	) ), 'gs_giuria' );
	usort( $tutti, function ( $a, $b ) {
		$ca = '1' === get_post_meta( $a->ID, 'gs_giuria_chiusa', true );
		$cb = '1' === get_post_meta( $b->ID, 'gs_giuria_chiusa', true );
		if ( $ca !== $cb ) { return $ca ? 1 : -1; }
		return strtotime( $b->post_date ) <=> strtotime( $a->post_date );
	} );
	return $tutti;
}

function gs_giuria_attivi() {
	return array_values( array_filter( gs_giuria_tutti(), function ( $p ) {
		return '1' !== get_post_meta( $p->ID, 'gs_giuria_chiusa', true );
	} ) );
}

/**
 * True se questa persona può giudicare questo turno: se il turno è "a
 * maestri", chiunque gestisca il portale; se è "tra sfogline" (di default),
 * solo chi è stata assegnata apposta come giudice di questo turno.
 */
function gs_giuria_e_giudice( $id, $uid ) {
	$c = gs_giuria_get( $id );
	if ( 'maestri' === $c['tipo'] ) {
		return gs_e_maestro( $uid );
	}
	return in_array( (int) $uid, $c['giudici'], true );
}

/** L'opera già inviata da una sfoglina per questo turno, o null. */
function gs_giuria_opera_di( $id, $uid ) {
	$uid = (int) $uid;
	foreach ( gs_giuria_get( $id )['opere'] as $o ) {
		if ( (int) $o['user_id'] === $uid ) { return $o; }
	}
	return null;
}

/** Invia (o aggiorna) l'opera di una sfoglina per il turno. */
function gs_giuria_opera_invia( $id, $uid, $titolo, $media = null, $testo = '' ) {
	$c = gs_giuria_get( $id );
	if ( $c['chiusa'] ) { return false; }
	$opere = $c['opere'];
	$uid   = (int) $uid;

	$trovata = false;
	$opera_id = null;
	foreach ( $opere as &$o ) {
		if ( (int) $o['user_id'] === $uid ) {
			$o['titolo'] = $titolo; $o['media'] = $media; $o['testo'] = $testo; $o['data'] = current_time( 'mysql' );
			$opera_id = $o['id'];
			$trovata = true;
			break;
		}
	}
	unset( $o );
	if ( ! $trovata ) {
		$opera_id = 'op' . $uid . '_' . uniqid();
		$opere[] = array( 'id' => $opera_id, 'user_id' => $uid, 'titolo' => $titolo, 'media' => $media, 'testo' => $testo, 'data' => current_time( 'mysql' ) );
	}
	update_post_meta( $id, 'gs_giuria_opere', $opere );
	return $opera_id;
}

/** I voti ricevuti da una specifica opera. */
function gs_giuria_voti_opera( $id, $opera_id ) {
	return array_values( array_filter( gs_giuria_get( $id )['voti'], function ( $v ) use ( $opera_id ) {
		return $v['opera_id'] === $opera_id;
	} ) );
}

/** Il voto di un giudice per una specifica opera, o null. */
function gs_giuria_voto_di( $id, $opera_id, $giudice_id ) {
	foreach ( gs_giuria_voti_opera( $id, $opera_id ) as $v ) {
		if ( (int) $v['giudice_id'] === (int) $giudice_id ) { return $v; }
	}
	return null;
}

/** Media delle stelle ricevute da un'opera (0 se nessun voto ancora). */
function gs_giuria_media_opera( $id, $opera_id ) {
	$voti = gs_giuria_voti_opera( $id, $opera_id );
	if ( ! $voti ) { return 0.0; }
	$somma = 0;
	foreach ( $voti as $v ) { $somma += (float) $v['stelle']; }
	return round( $somma / count( $voti ), 2 );
}

/** Registra (o aggiorna) il voto di un giudice: solo se assegnato a questo turno, mai anonimo, con motivazione. */
function gs_giuria_voto_invia( $id, $giudice_id, $opera_id, $stelle, $motivazione ) {
	$c = gs_giuria_get( $id );
	if ( $c['chiusa'] ) { return false; }
	if ( ! gs_giuria_e_giudice( $id, $giudice_id ) ) { return false; }
	if ( '' === trim( (string) $motivazione ) ) { return false; }
	$stelle = max( 1, min( 5, (int) $stelle ) );

	$trovata = false;
	foreach ( $c['opere'] as $o ) { if ( $o['id'] === $opera_id ) { $trovata = true; break; } }
	if ( ! $trovata ) { return false; }

	$voti = $c['voti'];
	$aggiornato = false;
	foreach ( $voti as &$v ) {
		if ( $v['opera_id'] === $opera_id && (int) $v['giudice_id'] === (int) $giudice_id ) {
			$v['stelle'] = $stelle; $v['motivazione'] = $motivazione; $v['data'] = current_time( 'mysql' );
			$aggiornato = true;
			break;
		}
	}
	unset( $v );
	if ( ! $aggiornato ) {
		$voti[] = array( 'opera_id' => $opera_id, 'giudice_id' => (int) $giudice_id, 'stelle' => $stelle, 'motivazione' => $motivazione, 'data' => current_time( 'mysql' ) );
	}
	update_post_meta( $id, 'gs_giuria_voti', $voti );

	if ( ! $aggiornato && ! gs_e_maestro( $giudice_id ) ) {
		gs_add_points( $giudice_id, gs_get_points_value( 'giuria_voto', 5 ), 'Giuria a Turno: voto motivato' );
	}
	return true;
}

/** Le opere del turno, ordinate per media stelle decrescente. */
function gs_giuria_classifica( $id ) {
	$opere = gs_giuria_get( $id )['opere'];
	// Calcola la media una sola volta per opera, poi ordina — stessa
	// correzione già applicata al file gemello (gs_challenge_leaderboard() in
	// voting.php): prima veniva ricalcolata dentro il confronto di usort
	// (O(n log n) letture di meta).
	$medie = array();
	foreach ( $opere as $o ) {
		$medie[ $o['id'] ] = gs_giuria_media_opera( $id, $o['id'] );
	}
	usort( $opere, function ( $a, $b ) use ( $medie ) {
		return $medie[ $b['id'] ] <=> $medie[ $a['id'] ];
	} );
	return $opere;
}

/** Modifica titolo/descrizione/tipo/giudici di un turno non ancora chiuso (opere e voti restano intoccati). */
function gs_giuria_modifica( $id, $titolo, $descrizione, $tipo, $giudici = array() ) {
	if ( 'gs_giuria' !== get_post_type( $id ) ) { return false; }
	wp_update_post( array( 'ID' => $id, 'post_title' => $titolo, 'post_content' => $descrizione ) );
	update_post_meta( $id, 'gs_giuria_tipo', $tipo );
	if ( 'sfogline' === $tipo ) {
		update_post_meta( $id, 'gs_giuria_giudici', array_values( array_unique( array_map( 'intval', $giudici ) ) ) );
	}
	return true;
}

/** Chiude il turno e premia l'opera con la media più alta (serve almeno un voto in tutto il turno). */
function gs_giuria_chiudi( $id ) {
	$c = gs_giuria_get( $id );
	if ( $c['chiusa'] || ! $c['voti'] ) { return false; }

	$classifica = gs_giuria_classifica( $id );
	$vincitrice = $classifica[0];
	$uid        = (int) $vincitrice['user_id'];

	update_post_meta( $id, 'gs_giuria_chiusa', '1' );

	$badge_key = 'giuria_' . $id;
	$owned     = gs_get_user_badges( $uid );
	if ( ! in_array( $badge_key, $owned, true ) ) {
		$owned[] = $badge_key;
		update_user_meta( $uid, 'gs_badges', $owned );
		update_user_meta( $uid, 'gs_badge_label_' . $badge_key, '⚖️ Giuria a Turno: ' . $c['titolo'] );

		$log = get_user_meta( $uid, 'gs_badges_log', true );
		$log = is_array( $log ) ? $log : array();
		$log[ $badge_key ] = current_time( 'timestamp' );
		update_user_meta( $uid, 'gs_badges_log', $log );

		// I punti stanno DENTRO questo controllo, non fuori: il marcatore
		// gs_giuria_chiusa qui sopra impedisce il caso normale, ma non
		// basterebbero due chiamate quasi simultanee a gs_giuria_chiudi() per
		// aggirarlo (stessa disposizione già corretta in L2 sui percorsi).
		// Chi ha già il badge di questo turno ha già avuto anche i punti.
		gs_add_points( $uid, gs_get_points_value( 'giuria_vinta', 30 ), 'Giuria a Turno: ' . $c['titolo'] );
	}

	if ( function_exists( 'gs_accoda_volo' ) ) {
		gs_accoda_volo( $uid, 'LA GIURIA TI HA PREMIATA IN "' . mb_strtoupper( $c['titolo'] ) . '"! ⚖️', gs_pagina_url( 'gs_page_giuria' ) );
	}
	$u = get_userdata( $uid );
	if ( $u ) {
		$oggetto = 'La giuria ti ha premiata! ⚖️ — Accademia della Sfoglia';
		$corpo   = "Complimenti " . $u->display_name . "!\n\nNel turno \"" . $c['titolo'] . "\" la tua opera \"" . $vincitrice['titolo'] . "\" ha ricevuto la valutazione più alta dalla giuria di questo turno.\n\nHai sbloccato un badge dedicato e qualche punto in più.\n\nAccademia della Sfoglia";
		if ( function_exists( 'gs_mail_progetto' ) ) {
			gs_mail_progetto( $uid, 'livelli', $oggetto, $corpo );
		} elseif ( $u->user_email ) {
			wp_mail( $u->user_email, $oggetto, $corpo );
		}
	}
	return $uid;
}

// -----------------------------------------------------------------------------
// [gs_giuria_turno] — lato sfoglina
// -----------------------------------------------------------------------------
add_shortcode( 'gs_giuria_turno', 'gs_sc_giuria_turno' );
function gs_sc_giuria_turno() {
	if ( $g = gs_gate_riservato() ) { return $g; }
	if ( $g = gs_blackout_gate() ) { return $g; }

	$uid = get_current_user_id();
	$out = gs_box_open( '⚖️ La Giuria a Turno' );
	$out .= gs_sezione_aiuto( 'Ogni turno ha dei giudici assegnati dal titolare: o una piccola giuria di sfogline a rotazione, o i maestri (titolare e collaboratori), a seconda del turno. Se tocca a te giudicare, il tuo voto non è anonimo: il tuo nome resta accanto al punteggio, insieme alla motivazione che scrivi. Se non sei tra le giudici di questo turno, puoi comunque partecipare inviando la tua opera e leggere i voti ricevuti.' );

	$attivi = gs_giuria_attivi();
	if ( ! $attivi ) {
		$out .= '<p class="gs-hint">Nessun turno di giuria attivo al momento. Torna presto.</p>';
	}
	foreach ( $attivi as $p ) {
		$c        = gs_giuria_get( $p->ID );
		$sono_giudice = gs_giuria_e_giudice( $p->ID, $uid );
		$mia_opera = gs_giuria_opera_di( $p->ID, $uid );

		$out .= '<div class="gs-todo-riquadro">';
		$out .= '<p class="gs-hint" style="font-size:16px"><strong>' . esc_html( $c['titolo'] ) . '</strong>' . ( $sono_giudice ? ' <span class="gs-msg-tag">⚖️ sei giudice di questo turno</span>' : '' ) . '</p>';
		if ( $c['descrizione'] ) { $out .= '<p class="gs-hint">' . esc_html( $c['descrizione'] ) . '</p>'; }

		if ( ! $sono_giudice ) {
			$out .= '<form class="gs-form gs-form-giuria-opera" data-giuria="' . (int) $p->ID . '" onsubmit="return false">';
			$out .= '<p><label>Titolo della tua opera<br><input type="text" name="titolo" value="' . ( $mia_opera ? esc_attr( $mia_opera['titolo'] ) : '' ) . '" autocomplete="off" style="width:100%" required></label></p>';
			$out .= '<p><label>Racconta la tua opera (facoltativo)<br><textarea name="testo" rows="2" style="width:100%">' . ( $mia_opera ? esc_textarea( $mia_opera['testo'] ) : '' ) . '</textarea></label></p>';
			$out .= '<p>Foto: ' . gs_msg_file_field() . '</p>';
			$out .= '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-giuria-opera-invia">' . ( $mia_opera ? 'Aggiorna la mia opera' : 'Invia la mia opera' ) . '</button> <span class="gs-giuria-opera-msg gs-richiesta-esito"></span></p>';
			$out .= '</form>';
		}

		if ( $c['opere'] ) {
			$out .= '<strong>Opere in gara (' . count( $c['opere'] ) . ')</strong>';
			foreach ( $c['opere'] as $o ) {
				$au = get_userdata( $o['user_id'] );
				$media_voto = gs_giuria_media_opera( $p->ID, $o['id'] );
				$out .= '<div class="gs-todo-riquadro" style="margin-top:8px">';
				$out .= '<p class="gs-hint"><strong>' . esc_html( $o['titolo'] ) . '</strong> — di ' . esc_html( $au ? $au->display_name : '—' ) . ( $media_voto > 0 ? ' — media ' . esc_html( $media_voto ) . '⭐' : ' — nessun voto ancora' ) . '</p>';
				if ( ! empty( $o['media'] ) ) { $out .= gs_msg_media_html( $o['media']['url'], isset( $o['media']['type'] ) ? $o['media']['type'] : 'image' ); }
				if ( $o['testo'] ) { $out .= '<p class="gs-hint">' . esc_html( $o['testo'] ) . '</p>'; }

				$voti = gs_giuria_voti_opera( $p->ID, $o['id'] );
				if ( $voti ) {
					$out .= '<ul class="gs-todo-list">';
					foreach ( $voti as $v ) {
						$gu = get_userdata( $v['giudice_id'] );
						$out .= '<li class="gs-todo-item"><span><strong>' . esc_html( $gu ? $gu->display_name : '—' ) . '</strong> — ' . esc_html( $v['stelle'] ) . '⭐ — ' . esc_html( $v['motivazione'] ) . '</span></li>';
					}
					$out .= '</ul>';
				}

				if ( $sono_giudice ) {
					$mio_voto = gs_giuria_voto_di( $p->ID, $o['id'], $uid );
					$out .= '<form class="gs-form gs-form-giuria-voto" data-giuria="' . (int) $p->ID . '" data-opera="' . esc_attr( $o['id'] ) . '" onsubmit="return false">';
					$out .= '<p style="display:flex;gap:8px;align-items:center;flex-wrap:wrap"><label>Il tuo voto<br><select name="stelle">';
					for ( $s = 5; $s >= 1; $s-- ) { $out .= '<option value="' . $s . '" ' . selected( $mio_voto ? (int) $mio_voto['stelle'] : 0, $s, false ) . '>' . $s . ' ⭐</option>'; }
					$out .= '</select></label></p>';
					$out .= '<p><label>Perché? (obbligatorio)<br><textarea name="motivazione" rows="2" style="width:100%" required>' . ( $mio_voto ? esc_textarea( $mio_voto['motivazione'] ) : '' ) . '</textarea></label></p>';
					$out .= '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-giuria-voto-invia">' . ( $mio_voto ? 'Aggiorna il mio voto' : 'Vota' ) . '</button> <span class="gs-giuria-voto-msg gs-richiesta-esito"></span></p>';
					$out .= '</form>';
				}
				$out .= '</div>';
			}
		}
		$out .= '</div>';
	}

	$chiusi = array_values( array_filter( gs_giuria_tutti(), function ( $p ) { return '1' === get_post_meta( $p->ID, 'gs_giuria_chiusa', true ); } ) );
	if ( $chiusi ) {
		$out .= '<details class="gs-corso-descr"><summary>🏆 Turni conclusi (' . count( $chiusi ) . ')</summary>';
		$out .= '<div class="gs-gallery gs-paginate" data-per-page="6" style="margin-top:8px">';
		foreach ( $chiusi as $p ) {
			$c          = gs_giuria_get( $p->ID );
			$classifica = gs_giuria_classifica( $p->ID );
			$out .= '<div class="gs-card"><div class="gs-card-body">';
			$out .= '<h4>' . esc_html( $c['titolo'] ) . '</h4>';
			if ( $classifica ) {
				$au = get_userdata( $classifica[0]['user_id'] );
				$out .= '<p class="gs-hint">🥇 ' . esc_html( $au ? $au->display_name : '—' ) . ' — "' . esc_html( $classifica[0]['titolo'] ) . '" — media ' . esc_html( gs_giuria_media_opera( $p->ID, $classifica[0]['id'] ) ) . '⭐</p>';
			}
			$out .= '</div></div>';
		}
		$out .= '</div></details>';
	}

	$out .= gs_box_close();
	return $out;
}

add_action( 'wp_ajax_gs_giuria_opera_invia', 'gs_ajax_giuria_opera_invia' );
function gs_ajax_giuria_opera_invia() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }
	$id = isset( $_POST['giuria'] ) ? (int) $_POST['giuria'] : 0;
	if ( 'gs_giuria' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Turno non valido.' ) ); }
	if ( gs_giuria_e_giudice( $id, $uid ) ) { wp_send_json_error( array( 'message' => 'Sei giudice di questo turno: le giudici non partecipano con un\'opera propria.' ) ); }
	if ( gs_giuria_get( $id )['chiusa'] ) { wp_send_json_error( array( 'message' => 'Questo turno è già stato chiuso.' ) ); }

	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	if ( '' === trim( $titolo ) ) { wp_send_json_error( array( 'message' => 'Scrivi il titolo della tua opera.' ) ); }
	$testo = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';
	if ( function_exists( 'gs_msg_clean' ) ) { $testo = gs_msg_clean( $testo ); }

	$media = null;
	if ( function_exists( 'gs_msg_upload' ) && ! empty( $_FILES['media']['name'] ) ) {
		$m = gs_msg_upload( 'media' );
		if ( is_wp_error( $m ) ) { wp_send_json_error( array( 'message' => $m->get_error_message() ) ); }
		if ( is_array( $m ) ) { $media = array( 'url' => $m['url'], 'type' => $m['type'] ); }
	} else {
		$precedente = gs_giuria_opera_di( $id, $uid );
		if ( $precedente && ! empty( $precedente['media'] ) ) { $media = $precedente['media']; }
	}

	gs_giuria_opera_invia( $id, $uid, $titolo, $media, $testo );
	wp_send_json_success( array( 'message' => 'Opera inviata.' ) );
}

add_action( 'wp_ajax_gs_giuria_voto_invia', 'gs_ajax_giuria_voto_invia' );
function gs_ajax_giuria_voto_invia() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }
	$id = isset( $_POST['giuria'] ) ? (int) $_POST['giuria'] : 0;
	if ( 'gs_giuria' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Turno non valido.' ) ); }
	if ( ! gs_giuria_e_giudice( $id, $uid ) ) { wp_send_json_error( array( 'message' => 'Non sei tra le giudici assegnate a questo turno.' ) ); }

	$opera_id    = isset( $_POST['opera'] ) ? sanitize_text_field( wp_unslash( $_POST['opera'] ) ) : '';
	$stelle      = isset( $_POST['stelle'] ) ? (int) $_POST['stelle'] : 0;
	$motivazione = isset( $_POST['motivazione'] ) ? sanitize_textarea_field( wp_unslash( $_POST['motivazione'] ) ) : '';
	if ( function_exists( 'gs_msg_clean' ) ) { $motivazione = gs_msg_clean( $motivazione ); }
	if ( '' === trim( $motivazione ) ) { wp_send_json_error( array( 'message' => 'Scrivi perché hai votato così: la motivazione è obbligatoria.' ) ); }

	$ok = gs_giuria_voto_invia( $id, $uid, $opera_id, $stelle, $motivazione );
	if ( ! $ok ) { wp_send_json_error( array( 'message' => 'Impossibile registrare il voto (turno chiuso o opera non trovata).' ) ); }

	wp_send_json_success( array( 'message' => 'Voto registrato.' ) );
}

// -----------------------------------------------------------------------------
// PANNELLO — creazione turni e assegnazione della giuria (titolare)
// -----------------------------------------------------------------------------
function gs_pannello_giuria_turno() {
	if ( ! gs_can_manage() ) { return; }

	echo gs_box_open( '⚖️ La Giuria a Turno', '', 'gs-box-giuria' );
	echo '<p class="gs-hint">Crea un turno e scegli chi giudica: una piccola giuria di sfogline — poche, diverse ogni volta — oppure i maestri (titolare e collaboratori). In entrambi i casi il voto ha sempre un nome visibile e una motivazione obbligatoria. Chi non giudica può comunque partecipare inviando la propria opera. Tu decidi quando chiudere il turno: vince chi ha la media voti più alta.</p>';

	$sfogline = function_exists( 'gs_sez_sfogline' ) ? gs_sez_sfogline() : array();

	echo '<form class="gs-form gs-form-giuria-nuova" onsubmit="return false" style="background:var(--gs-uovo);padding:12px;border-radius:6px;margin-bottom:14px">';
	echo '<strong>Nuovo turno di giuria</strong>';
	echo '<p><label>Nome del turno<br><input type="text" name="titolo" autocomplete="off" style="width:100%" required placeholder="Es. Giuria di Marzo: i ripieni della tradizione"></label></p>';
	echo '<p><label>Descrizione / tema (facoltativa)<br><textarea name="descrizione" rows="2" style="width:100%"></textarea></label></p>';
	echo '<p><label>Chi giudica<br><select name="tipo" class="gs-giuria-tipo-select"><option value="sfogline">Le sfogline, a rotazione (scegli tu chi)</option><option value="maestri">I maestri (titolare e collaboratori)</option></select></label></p>';
	echo '<div class="gs-giuria-lista-giudici">';
	echo '<p><strong>Giudici di questo turno</strong> <span class="gs-hint">— scegline poche, ruota le persone da un turno all\'altro.</span></p>';
	echo '<input type="text" class="gs-cerca-input" data-target=".gs-giuria-lista-check" placeholder="🔍 Cerca una giudice…" style="width:100%;max-width:320px;margin-bottom:6px">';
	echo '<div class="gs-giuria-lista-check" style="max-height:180px;overflow-y:auto;background:#fff;border:1px solid var(--gs-bordo);border-radius:6px;padding:8px">';
	foreach ( $sfogline as $u ) {
		echo '<label style="display:block;font-size:13px;padding:2px 0"><input type="checkbox" name="giudici[]" value="' . (int) $u->ID . '"> ' . esc_html( $u->display_name ) . '</label>';
	}
	echo '</div></div>';
	echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-giuria-crea">Crea turno</button> <span class="gs-giuria-crea-msg gs-richiesta-esito"></span></p>';
	echo '</form>';

	$tutti = gs_giuria_tutti();
	echo '<h4>Turni creati (' . count( $tutti ) . ')</h4>';
	if ( ! $tutti ) {
		echo '<p class="gs-hint">Nessun turno ancora creato.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="8">';
		foreach ( $tutti as $p ) {
			$c          = gs_giuria_get( $p->ID );
			$classifica = gs_giuria_classifica( $p->ID );
			$giudici_lbl = 'maestri' === $c['tipo']
				? 'I maestri (titolare e collaboratori)'
				: implode( ', ', array_map( function ( $gid ) { $u = get_userdata( $gid ); return $u ? $u->display_name : '—'; }, $c['giudici'] ) );
			echo '<details class="gs-inbox-item" data-giuria="' . (int) $p->ID . '">';
			echo '<summary class="gs-inbox-oggetto">' . esc_html( $c['titolo'] ) . ( $c['chiusa'] ? ' <span class="gs-msg-tag">chiuso</span>' : ' <span class="gs-msg-tag">attivo</span>' ) . ' <span class="gs-msg-data">' . count( $c['opere'] ) . ' opere</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			echo '<p class="gs-hint"><strong>Giudici:</strong> ' . esc_html( $giudici_lbl ) . '</p>';

			if ( ! $c['chiusa'] ) {
				echo '<form class="gs-form gs-form-giuria-modifica" data-giuria="' . (int) $p->ID . '" onsubmit="return false" style="margin-bottom:10px">';
				echo '<p><label>Nome del turno<br><input type="text" name="titolo" value="' . esc_attr( $c['titolo'] ) . '" style="width:100%" required></label></p>';
				echo '<p><label>Descrizione / tema<br><textarea name="descrizione" rows="2" style="width:100%">' . esc_textarea( $c['descrizione'] ) . '</textarea></label></p>';
				echo '<p><label>Chi giudica<br><select name="tipo" class="gs-giuria-tipo-select"><option value="sfogline" ' . selected( $c['tipo'], 'sfogline', false ) . '>Le sfogline, a rotazione (scegli tu chi)</option><option value="maestri" ' . selected( $c['tipo'], 'maestri', false ) . '>I maestri (titolare e collaboratori)</option></select></label></p>';
				echo '<div class="gs-giuria-lista-giudici"' . ( 'maestri' === $c['tipo'] ? ' style="display:none"' : '' ) . '>';
				echo '<p><strong>Giudici di questo turno</strong></p>';
				echo '<div style="max-height:150px;overflow-y:auto;background:#fff;border:1px solid var(--gs-bordo);border-radius:6px;padding:8px">';
				foreach ( $sfogline as $u ) {
					echo '<label style="display:block;font-size:13px;padding:2px 0"><input type="checkbox" name="giudici[]" value="' . (int) $u->ID . '" ' . checked( in_array( (int) $u->ID, $c['giudici'], true ), true, false ) . '> ' . esc_html( $u->display_name ) . '</label>';
				}
				echo '</div></div>';
				echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-giuria-modifica-salva">Salva modifiche</button> <span class="gs-giuria-mod-msg gs-richiesta-esito"></span></p>';
				echo '</form>';
			}

			if ( $classifica ) {
				echo '<ol class="gs-todo-list">';
				foreach ( $classifica as $o ) {
					$au = get_userdata( $o['user_id'] );
					$media = gs_giuria_media_opera( $p->ID, $o['id'] );
					echo '<li class="gs-todo-item"><span>' . esc_html( $o['titolo'] ) . ' — ' . esc_html( $au ? $au->display_name : '—' ) . ' — <strong>' . ( $media > 0 ? esc_html( $media ) . '⭐' : 'nessun voto' ) . '</strong> (' . count( gs_giuria_voti_opera( $p->ID, $o['id'] ) ) . ' voti)</span></li>';
				}
				echo '</ol>';
			} else {
				echo '<p class="gs-hint">Nessuna opera inviata ancora.</p>';
			}

			if ( ! $c['chiusa'] ) {
				echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-giuria-chiudi" data-giuria="' . (int) $p->ID . '"' . ( ! $c['voti'] ? ' disabled' : '' ) . '>Chiudi e premia la prima classificata</button> <span class="gs-giuria-chiudi-msg gs-richiesta-esito"></span></p>';
			}
			echo '</div></details>';
		}
		echo '</div>';
	}

	echo gs_box_close();
}

add_action( 'wp_ajax_gs_giuria_crea', 'gs_ajax_giuria_crea' );
function gs_ajax_giuria_crea() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	if ( '' === trim( $titolo ) ) { wp_send_json_error( array( 'message' => 'Scrivi il nome del turno.' ) ); }
	$descrizione = isset( $_POST['descrizione'] ) ? sanitize_textarea_field( wp_unslash( $_POST['descrizione'] ) ) : '';
	$tipo = isset( $_POST['tipo'] ) ? sanitize_key( wp_unslash( $_POST['tipo'] ) ) : 'sfogline';
	if ( ! in_array( $tipo, array( 'sfogline', 'maestri' ), true ) ) { $tipo = 'sfogline'; }

	$giudici = array();
	if ( 'sfogline' === $tipo ) {
		$giudici_raw = isset( $_POST['giudici'] ) && is_array( $_POST['giudici'] ) ? array_map( 'intval', wp_unslash( $_POST['giudici'] ) ) : array();
		$giudici     = array_values( array_unique( array_filter( $giudici_raw ) ) );
		if ( ! $giudici ) { wp_send_json_error( array( 'message' => 'Scegli almeno una giudice per il turno.' ) ); }
	}

	$id = wp_insert_post( array(
		'post_type'    => 'gs_giuria',
		'post_status'  => 'publish',
		'post_title'   => $titolo,
		'post_content' => $descrizione,
	) );
	if ( is_wp_error( $id ) || ! $id ) { wp_send_json_error( array( 'message' => 'Errore nella creazione.' ) ); }

	update_post_meta( $id, 'gs_giuria_tipo', $tipo );
	if ( $giudici ) { update_post_meta( $id, 'gs_giuria_giudici', $giudici ); }
	wp_send_json_success( array( 'message' => 'Turno creato.' ) );
}

add_action( 'wp_ajax_gs_giuria_modifica', 'gs_ajax_giuria_modifica' );
function gs_ajax_giuria_modifica() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['giuria'] ) ? (int) $_POST['giuria'] : 0;
	if ( 'gs_giuria' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Turno non valido.' ) ); }

	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	if ( '' === trim( $titolo ) ) { wp_send_json_error( array( 'message' => 'Scrivi il nome del turno.' ) ); }
	$descrizione = isset( $_POST['descrizione'] ) ? sanitize_textarea_field( wp_unslash( $_POST['descrizione'] ) ) : '';
	$tipo = isset( $_POST['tipo'] ) ? sanitize_key( wp_unslash( $_POST['tipo'] ) ) : 'sfogline';
	if ( ! in_array( $tipo, array( 'sfogline', 'maestri' ), true ) ) { $tipo = 'sfogline'; }

	$giudici = array();
	if ( 'sfogline' === $tipo ) {
		$giudici_raw = isset( $_POST['giudici'] ) && is_array( $_POST['giudici'] ) ? array_map( 'intval', wp_unslash( $_POST['giudici'] ) ) : array();
		$giudici     = array_values( array_unique( array_filter( $giudici_raw ) ) );
		if ( ! $giudici ) { wp_send_json_error( array( 'message' => 'Scegli almeno una giudice per il turno.' ) ); }
	}

	gs_giuria_modifica( $id, $titolo, $descrizione, $tipo, $giudici );
	wp_send_json_success( array( 'message' => 'Turno aggiornato.' ) );
}

add_action( 'wp_ajax_gs_giuria_chiudi', 'gs_ajax_giuria_chiudi' );
function gs_ajax_giuria_chiudi() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['giuria'] ) ? (int) $_POST['giuria'] : 0;
	if ( 'gs_giuria' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Turno non valido.' ) ); }

	$vincitrice = gs_giuria_chiudi( $id );
	if ( ! $vincitrice ) { wp_send_json_error( array( 'message' => 'Impossibile chiudere: turno già chiuso o senza voti ricevuti.' ) ); }

	$u = get_userdata( $vincitrice );
	wp_send_json_success( array( 'message' => 'Turno chiuso. Ha vinto ' . ( $u ? $u->display_name : 'una sfoglina' ) . '.' ) );
}
