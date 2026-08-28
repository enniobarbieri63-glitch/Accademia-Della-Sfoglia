<?php
/**
 * sondaggi.php — Sondaggi tra le sfogline.
 *
 * Il titolare/i collaboratori creano un sondaggio (una domanda, con qualche
 * proposta iniziale) dal Pannello Generale; le sfogline votano una proposta,
 * e — se il sondaggio lo permette — possono aggiungerne di nuove, che si
 * aggiungono a quelle esistenti per il voto di tutte.
 *
 * Ogni sondaggio può avere i risultati:
 *  - PUBBLICI: visibili anche alle sfogline nella pagina del sito;
 *  - PRIVATI:  visibili solo dal Pannello Generale (titolare/collaboratori).
 * In entrambi i casi il pannello mostra sempre i risultati completi.
 *
 * CPT gs_sondaggio, nessuna tabella nuova:
 *  - post_title   = la domanda del sondaggio
 *  - post_content = descrizione facoltativa
 *  - meta gs_sond_proposte = array di { id, testo, autore (0 = staff) }
 *  - meta gs_sond_voti     = array [ uid => proposta_id ] (un voto per sfoglina)
 *  - meta gs_sond_pubblico = '1' se i risultati sono visibili anche alle sfogline
 *  - meta gs_sond_proposte_aperte = '1' se le sfogline possono proporre nuove idee
 *  - meta gs_sond_stato    = 'aperto' | 'chiuso'
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_register_sondaggio_cpt' );
function gs_register_sondaggio_cpt() {
	register_post_type( 'gs_sondaggio', array(
		'labels'          => array( 'name' => 'Sondaggi' ),
		'public'          => false,
		'show_ui'         => false,
		'show_in_menu'    => false,
		'supports'        => array( 'title', 'editor' ),
		'capability_type' => 'post',
	) );
}

// -----------------------------------------------------------------------------
// Helper
// -----------------------------------------------------------------------------

function gs_sondaggio_get( $id ) {
	$proposte = get_post_meta( $id, 'gs_sond_proposte', true );
	$voti     = get_post_meta( $id, 'gs_sond_voti', true );
	$stato    = get_post_meta( $id, 'gs_sond_stato', true );
	return array(
		'id'              => $id,
		'domanda'         => get_the_title( $id ),
		'descrizione'     => get_post( $id ) ? get_post( $id )->post_content : '',
		'proposte'        => is_array( $proposte ) ? $proposte : array(),
		'voti'            => is_array( $voti ) ? $voti : array(),
		'pubblico'        => (bool) get_post_meta( $id, 'gs_sond_pubblico', true ),
		'proposte_aperte' => (bool) get_post_meta( $id, 'gs_sond_proposte_aperte', true ),
		'stato'           => $stato ? $stato : 'aperto',
	);
}

/** Tutti i sondaggi pubblicati, più recenti prima. */
function gs_sondaggi_tutti() {
	return gs_solo_tipo( get_posts( array(
		'post_type'        => 'gs_sondaggio',
		'post_status'      => 'publish',
		'posts_per_page'   => -1,
		'orderby'          => 'date',
		'order'            => 'DESC',
		'suppress_filters' => true,
	) ), 'gs_sondaggio' );
}

/** I sondaggi nel cestino, per il recupero. */
function gs_sondaggi_cestino() {
	return gs_solo_tipo( get_posts( array(
		'post_type'        => 'gs_sondaggio',
		'post_status'      => 'trash',
		'posts_per_page'   => 50,
		'suppress_filters' => true,
	) ), 'gs_sondaggio' );
}

/** Conteggio voti per proposta: { proposta_id => n_voti }, più il totale. */
function gs_sondaggio_conteggio( $proposte, $voti ) {
	$conteggio = array();
	foreach ( $proposte as $p ) {
		$conteggio[ $p['id'] ] = 0;
	}
	foreach ( $voti as $uid => $proposta_id ) {
		if ( isset( $conteggio[ $proposta_id ] ) ) {
			$conteggio[ $proposta_id ]++;
		}
	}
	return $conteggio;
}

/** La proposta votata da questa sfoglina, o 0 se non ha ancora votato. */
function gs_sondaggio_scelta_di( $voti, $uid ) {
	return isset( $voti[ $uid ] ) ? (int) $voti[ $uid ] : 0;
}

/** Prossimo id libero per una nuova proposta (il più alto esistente + 1). */
function gs_sondaggio_prossimo_id_proposta( $proposte ) {
	$max = 0;
	foreach ( $proposte as $p ) {
		if ( (int) $p['id'] > $max ) { $max = (int) $p['id']; }
	}
	return $max + 1;
}

// -----------------------------------------------------------------------------
// AJAX — creazione, modifica, cestino (titolare/collaboratori)
// -----------------------------------------------------------------------------

add_action( 'wp_ajax_gs_sondaggio_crea', 'gs_ajax_sondaggio_crea' );
function gs_ajax_sondaggio_crea() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$domanda = isset( $_POST['domanda'] ) ? sanitize_text_field( wp_unslash( $_POST['domanda'] ) ) : '';
	if ( '' === $domanda ) { wp_send_json_error( array( 'message' => 'Scrivi la domanda del sondaggio.' ) ); }

	$id = wp_insert_post( array(
		'post_type'    => 'gs_sondaggio',
		'post_status'  => 'publish',
		'post_title'   => $domanda,
		'post_content' => isset( $_POST['descrizione'] ) ? sanitize_textarea_field( wp_unslash( $_POST['descrizione'] ) ) : '',
	) );
	if ( is_wp_error( $id ) || ! $id ) { wp_send_json_error( array( 'message' => 'Errore, riprova.' ) ); }

	// Proposte iniziali: una per riga, righe vuote ignorate.
	$righe    = isset( $_POST['proposte'] ) ? explode( "\n", wp_unslash( $_POST['proposte'] ) ) : array();
	$proposte = array();
	$next_id  = 1;
	foreach ( $righe as $riga ) {
		$testo = sanitize_text_field( trim( $riga ) );
		if ( '' === $testo ) { continue; }
		$proposte[] = array( 'id' => $next_id, 'testo' => $testo, 'autore' => 0 );
		$next_id++;
	}

	update_post_meta( $id, 'gs_sond_proposte', $proposte );
	update_post_meta( $id, 'gs_sond_voti', array() );
	update_post_meta( $id, 'gs_sond_pubblico', ! empty( $_POST['pubblico'] ) ? '1' : '' );
	update_post_meta( $id, 'gs_sond_proposte_aperte', ! empty( $_POST['proposte_aperte'] ) ? '1' : '' );
	update_post_meta( $id, 'gs_sond_stato', 'aperto' );

	$avvisate = 0;
	if ( ! empty( $_POST['avvisa'] ) && function_exists( 'gs_sez_sfogline' ) && function_exists( 'gs_accoda_volo' ) ) {
		$link = function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_sondaggi' ) : '';
		foreach ( gs_sez_sfogline() as $u ) {
			gs_accoda_volo( $u->ID, '🗳️ Nuovo sondaggio: «' . $domanda . '»', $link );
			$avvisate++;
		}
	}

	wp_send_json_success( array(
		'message' => 'Sondaggio creato.' . ( $avvisate ? ' Avviso inviato a ' . $avvisate . ( 1 === $avvisate ? ' sfoglina.' : ' sfogline.' ) : '' ),
		'id'      => $id,
	) );
}

add_action( 'wp_ajax_gs_sondaggio_modifica', 'gs_ajax_sondaggio_modifica' );
function gs_ajax_sondaggio_modifica() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['sondaggio'] ) ? (int) $_POST['sondaggio'] : 0;
	if ( 'gs_sondaggio' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Sondaggio non valido.' ) ); }

	$domanda = isset( $_POST['domanda'] ) ? sanitize_text_field( wp_unslash( $_POST['domanda'] ) ) : '';
	if ( '' === $domanda ) { wp_send_json_error( array( 'message' => 'Scrivi la domanda del sondaggio.' ) ); }

	wp_update_post( array(
		'ID'           => $id,
		'post_title'   => $domanda,
		'post_content' => isset( $_POST['descrizione'] ) ? sanitize_textarea_field( wp_unslash( $_POST['descrizione'] ) ) : '',
	) );
	update_post_meta( $id, 'gs_sond_pubblico', ! empty( $_POST['pubblico'] ) ? '1' : '' );
	update_post_meta( $id, 'gs_sond_proposte_aperte', ! empty( $_POST['proposte_aperte'] ) ? '1' : '' );
	$stato = isset( $_POST['stato'] ) && 'chiuso' === $_POST['stato'] ? 'chiuso' : 'aperto';
	update_post_meta( $id, 'gs_sond_stato', $stato );

	wp_send_json_success( array( 'message' => 'Sondaggio aggiornato.' ) );
}

add_action( 'wp_ajax_gs_sondaggio_elimina', 'gs_ajax_sondaggio_elimina' );
function gs_ajax_sondaggio_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['sondaggio'] ) ? (int) $_POST['sondaggio'] : 0;
	if ( 'gs_sondaggio' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Sondaggio non valido.' ) ); }
	wp_trash_post( $id );
	wp_send_json_success( array( 'message' => 'Spostato nel cestino.' ) );
}

add_action( 'wp_ajax_gs_sondaggio_ripristina', 'gs_ajax_sondaggio_ripristina' );
function gs_ajax_sondaggio_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['sondaggio'] ) ? (int) $_POST['sondaggio'] : 0;
	if ( 'gs_sondaggio' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Sondaggio non valido.' ) ); }
	wp_untrash_post( $id );
	wp_send_json_success( array( 'message' => 'Ripristinato.' ) );
}

// -----------------------------------------------------------------------------
// AJAX — voto e proposta (sfogline)
// -----------------------------------------------------------------------------

add_action( 'wp_ajax_gs_sondaggio_vota', 'gs_ajax_sondaggio_vota' );
function gs_ajax_sondaggio_vota() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	// gs_puo_partecipare() copre anche la congelata, non solo la non
	// approvata: era rimasto il controllo vecchio, trovato il 26/08/2026
	// insieme ad altri dieci nella stessa condizione.
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }

	$id = isset( $_POST['sondaggio'] ) ? (int) $_POST['sondaggio'] : 0;
	if ( 'gs_sondaggio' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
		wp_send_json_error( array( 'message' => 'Sondaggio non valido.' ) );
	}
	if ( 'aperto' !== get_post_meta( $id, 'gs_sond_stato', true ) ) {
		wp_send_json_error( array( 'message' => 'Questo sondaggio è chiuso.' ) );
	}
	$proposta_id = isset( $_POST['proposta'] ) ? (int) $_POST['proposta'] : 0;

	$r = gs_sondaggio_vota_uid( $id, $uid, $proposta_id );
	if ( $r['ok'] ) { wp_send_json_success( array( 'message' => $r['message'] ) ); }
	wp_send_json_error( array( 'message' => $r['message'] ) );
}

/**
 * Registra il voto di $uid sul sondaggio $id, con lucchetto MySQL per
 * sondaggio. Voti e proposte condividono lo stesso array per-sondaggio
 * (gs_sond_voti / gs_sond_proposte): due sfogline che votano nello stesso
 * istante leggevano entrambe l'array vecchio e l'ultima a scrivere
 * cancellava il voto dell'altra, in silenzio (trovato 26/08/2026). Stessa
 * chiave del lucchetto della proposta (gs_sondaggio_proponi_uid), così i
 * due handler non si pestano i piedi fra loro.
 */
function gs_sondaggio_vota_uid( $id, $uid, $proposta_id ) {
	global $wpdb;
	$lock = 'gs_sond_' . (int) $id;
	if ( '1' !== $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) ) ) {
		return array( 'ok' => false, 'message' => 'Il sondaggio è occupato, riprova tra un attimo.' );
	}

	try {
		// L'handler AJAX ha già letto gs_sond_stato prima di questo lucchetto,
		// e quella lettura precarica in cache TUTTI i meta del sondaggio
		// (compresi gs_sond_voti e gs_sond_proposte): senza svuotarla, questa
		// lettura vedrebbe ancora il valore di prima del lucchetto invece di
		// quello scritto nel frattempo da un altro voto/proposta appena
		// conclusi (trovato 26/08/2026, stesso limite del rimborso token).
		wp_cache_delete( $id, 'post_meta' );
		$voti = get_post_meta( $id, 'gs_sond_voti', true );
		$voti = is_array( $voti ) ? $voti : array();
		if ( isset( $voti[ $uid ] ) ) {
			return array( 'ok' => false, 'message' => 'Hai già votato in questo sondaggio.' );
		}

		$proposte = get_post_meta( $id, 'gs_sond_proposte', true );
		$proposte = is_array( $proposte ) ? $proposte : array();
		$valida   = false;
		foreach ( $proposte as $p ) {
			if ( (int) $p['id'] === (int) $proposta_id ) { $valida = true; break; }
		}
		if ( ! $valida ) {
			return array( 'ok' => false, 'message' => 'Scegli una proposta.' );
		}

		$voti[ $uid ] = (int) $proposta_id;
		update_post_meta( $id, 'gs_sond_voti', $voti );

		gs_add_points( $uid, gs_get_points_value( 'voto_sondaggio', 5 ), 'Voto dato in un sondaggio' );

		return array( 'ok' => true, 'message' => 'Voto registrato, grazie!' );
	} finally {
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	}
}

add_action( 'wp_ajax_gs_sondaggio_proponi', 'gs_ajax_sondaggio_proponi' );
function gs_ajax_sondaggio_proponi() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	// Stessa cautela del voto qui sopra.
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }

	$check = gs_antispam_check( $_POST, 'sondaggio_proposta' );
	if ( is_wp_error( $check ) ) { wp_send_json_error( array( 'message' => $check->get_error_message() ) ); }

	$id = isset( $_POST['sondaggio'] ) ? (int) $_POST['sondaggio'] : 0;
	if ( 'gs_sondaggio' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
		wp_send_json_error( array( 'message' => 'Sondaggio non valido.' ) );
	}
	if ( 'aperto' !== get_post_meta( $id, 'gs_sond_stato', true ) ) {
		wp_send_json_error( array( 'message' => 'Questo sondaggio è chiuso.' ) );
	}
	if ( ! get_post_meta( $id, 'gs_sond_proposte_aperte', true ) ) {
		wp_send_json_error( array( 'message' => 'In questo sondaggio non si possono proporre nuove idee.' ) );
	}

	$testo = isset( $_POST['testo'] ) ? sanitize_text_field( wp_unslash( $_POST['testo'] ) ) : '';
	$testo = mb_substr( $testo, 0, 200 );
	if ( '' === $testo ) { wp_send_json_error( array( 'message' => 'Scrivi la tua proposta.' ) ); }

	$r = gs_sondaggio_proponi_uid( $id, $uid, $testo );
	if ( $r['ok'] ) { wp_send_json_success( array( 'message' => $r['message'] ) ); }
	wp_send_json_error( array( 'message' => $r['message'] ) );
}

/**
 * Aggiunge una proposta di $uid al sondaggio $id, con lo stesso lucchetto
 * MySQL del voto (gs_sondaggio_vota_uid): senza, due proposte inviate nello
 * stesso istante leggevano lo stesso array e l'ultima a scrivere cancellava
 * la prima (trovato 26/08/2026).
 */
function gs_sondaggio_proponi_uid( $id, $uid, $testo ) {
	global $wpdb;
	$lock = 'gs_sond_' . (int) $id;
	if ( '1' !== $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) ) ) {
		return array( 'ok' => false, 'message' => 'Il sondaggio è occupato, riprova tra un attimo.' );
	}

	try {
		// Stessa cautela del voto qui sopra: le letture di gs_sond_stato e
		// gs_sond_proposte_aperte nell'handler AJAX hanno già precaricato in
		// cache tutti i meta del sondaggio, prima del lucchetto.
		wp_cache_delete( $id, 'post_meta' );
		$proposte = get_post_meta( $id, 'gs_sond_proposte', true );
		$proposte = is_array( $proposte ) ? $proposte : array();
		foreach ( $proposte as $p ) {
			if ( 0 === strcasecmp( $p['testo'], $testo ) ) {
				return array( 'ok' => false, 'message' => 'Questa proposta è già presente.' );
			}
		}

		$proposte[] = array( 'id' => gs_sondaggio_prossimo_id_proposta( $proposte ), 'testo' => $testo, 'autore' => $uid );
		update_post_meta( $id, 'gs_sond_proposte', $proposte );

		// I punti si prendono UNA VOLTA PER SONDAGGIO, non una volta a proposta:
		// il limitatore antispam permette comunque 10 proposte diverse all'ora
		// nello stesso sondaggio, cioè 100 punti da una sola pagina (trovato
		// 26/08/2026). Proporre resta libero, paga solo la prima.
		$gia_pagato = 'gs_sondaggio_proposta_pagata_' . (int) $id;
		if ( ! get_user_meta( $uid, $gia_pagato, true ) ) {
			update_user_meta( $uid, $gia_pagato, current_time( 'mysql' ) );   // contrassegno PRIMA
			gs_add_points( $uid, gs_get_points_value( 'proposta_sondaggio', 10 ), 'Nuova proposta in un sondaggio' );
		}

		return array( 'ok' => true, 'message' => 'Proposta aggiunta, grazie!' );
	} finally {
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	}
}

// -----------------------------------------------------------------------------
// Shortcode pubblico [gs_sondaggi] — lato sfoglina
// -----------------------------------------------------------------------------
add_shortcode( 'gs_sondaggi', 'gs_sc_sondaggi' );
function gs_sc_sondaggi() {
	if ( $g = gs_gate_riservato() ) { return $g; }
	if ( $g = gs_blackout_gate() ) { return $g; }

	$uid = get_current_user_id();

	$out  = gs_box_open( '🗳️ Sondaggi' );
	$out .= gs_sezione_aiuto( 'Qui trovi i sondaggi aperti dalla segreteria: scegli la proposta che preferisci e vota, un solo voto per sondaggio. Se il sondaggio lo permette, puoi anche scrivere una tua proposta: si aggiunge a quelle già presenti e diventa votabile da tutte. Se i risultati sono pubblici li vedi subito qui sotto; se sono riservati, restano visibili solo alla segreteria dal Pannello Generale, ma il tuo voto conta comunque.' );

	$tutti = gs_sondaggi_tutti();
	if ( ! $tutti ) {
		$out .= '<p class="gs-hint">Nessun sondaggio al momento.</p>' . gs_box_close();
		return $out;
	}

	$out .= '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="10">';
	foreach ( $tutti as $post ) {
		$s          = gs_sondaggio_get( $post->ID );
		$conteggio  = gs_sondaggio_conteggio( $s['proposte'], $s['voti'] );
		$totale     = array_sum( $conteggio );
		$mia_scelta = gs_sondaggio_scelta_di( $s['voti'], $uid );
		$chiuso     = 'chiuso' === $s['stato'];

		$out .= '<details class="gs-inbox-item">';
		$out .= '<summary class="gs-inbox-oggetto">' . esc_html( $s['domanda'] ) . ( $chiuso ? ' — <em>chiuso</em>' : '' ) . '</summary>';
		$out .= '<div class="gs-inbox-corpo">';
		if ( $s['descrizione'] ) { $out .= '<p>' . nl2br( esc_html( $s['descrizione'] ) ) . '</p>'; }

		if ( ! $s['proposte'] ) {
			$out .= '<p class="gs-hint">Nessuna proposta ancora inserita.</p>';
		} elseif ( $mia_scelta || $chiuso ) {
			// Ha già votato, o il sondaggio è chiuso: niente form di voto.
			if ( $mia_scelta ) {
				foreach ( $s['proposte'] as $p ) {
					if ( (int) $p['id'] === $mia_scelta ) {
						$out .= '<p class="gs-hint">✅ Hai votato: <strong>' . esc_html( $p['testo'] ) . '</strong></p>';
						break;
					}
				}
			} elseif ( $chiuso ) {
				$out .= '<p class="gs-hint">Questo sondaggio è chiuso, non hai votato.</p>';
			}
		} else {
			$out .= '<form class="gs-form" data-action="gs_sondaggio_vota">';
			$out .= '<input type="hidden" name="sondaggio" value="' . (int) $post->ID . '">';
			foreach ( $s['proposte'] as $p ) {
				$out .= '<p><label><input type="radio" name="proposta" value="' . (int) $p['id'] . '" required> ' . esc_html( $p['testo'] ) . '</label></p>';
			}
			$out .= '<p><button type="submit" class="gs-btn gs-btn-sm">Vota</button> <span class="gs-form-msg gs-richiesta-esito"></span></p>';
			$out .= '</form>';
		}

		if ( $s['pubblico'] && $s['proposte'] ) {
			$out .= '<div class="gs-sond-risultati"><p class="gs-hint">Risultati (' . (int) $totale . ( 1 === $totale ? ' voto' : ' voti' ) . '):</p>';
			foreach ( $s['proposte'] as $p ) {
				$n    = isset( $conteggio[ $p['id'] ] ) ? $conteggio[ $p['id'] ] : 0;
				$perc = $totale ? round( $n / $totale * 100 ) : 0;
				$out .= '<p class="gs-progress-label">' . esc_html( $p['testo'] ) . ' — ' . (int) $n . ( 1 === $n ? ' voto' : ' voti' ) . ' (' . $perc . '%)</p>';
				$out .= '<div class="gs-progress"><div class="gs-progress-bar" style="width:' . $perc . '%"></div></div>';
			}
			$out .= '</div>';
		}

		if ( $s['proposte_aperte'] && ! $chiuso ) {
			$out .= '<form class="gs-form" data-action="gs_sondaggio_proponi">';
			$out .= '<input type="hidden" name="sondaggio" value="' . (int) $post->ID . '">';
			ob_start(); gs_antispam_fields(); $out .= ob_get_clean();
			$out .= '<p><label>Proponi una tua idea<br><input type="text" name="testo" maxlength="200" style="width:100%" placeholder="Scrivi la tua proposta..."></label></p>';
			$out .= '<p><button type="submit" class="gs-btn gs-btn-sm gs-btn-ghost">Aggiungi proposta</button> <span class="gs-form-msg gs-richiesta-esito"></span></p>';
			$out .= '</form>';
		}

		$out .= '</div></details>';
	}
	$out .= '</div>';

	$out .= gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// Pannello — creazione e gestione (titolare/collaboratori)
// -----------------------------------------------------------------------------
function gs_pannello_sondaggi() {
	if ( ! gs_can_manage() ) {
		return;
	}
	echo gs_box_open( '🗳️ Sondaggi', '', 'gs-box-sondaggi' );
	echo gs_sezione_aiuto( 'Crea un sondaggio con una domanda e, se vuoi, qualche proposta di partenza (una per riga). "Risultati visibili alle sfogline" mostra il conteggio dei voti anche sulla pagina pubblica; lasciandolo spento i risultati restano visibili solo qui, in questo pannello, ma le sfogline possono comunque votare. "Le sfogline possono proporre nuove idee" apre un piccolo modulo nella pagina pubblica: ogni proposta scritta da una sfoglina si aggiunge a quelle esistenti e diventa votabile da tutte. "Avvisa subito le sfogline" manda l\'aeroplanino a tutte le sfogline approvate nel momento in cui crei il sondaggio, con un link che le porta dritte alla pagina Sondaggi: usalo quando vuoi che se ne accorgano subito, altrimenti resta silenzioso finché non ci passano da sole. Da qui vedi sempre il conteggio completo dei voti, anche per i sondaggi con risultati riservati.' );

	echo '<form class="gs-form" data-action="gs_sondaggio_crea" style="background:var(--gs-uovo);padding:12px;border-radius:6px;margin-bottom:14px">';
	echo '<strong>Nuovo sondaggio</strong>';
	echo '<p><label>Domanda<br><input type="text" name="domanda" required style="width:100%" placeholder="Es. Quale nuovo corso preferireste?"></label></p>';
	echo '<p><label>Descrizione (facoltativa)<br><textarea name="descrizione" rows="2" style="width:100%"></textarea></label></p>';
	echo '<p><label>Proposte di partenza (facoltative, una per riga)<br><textarea name="proposte" rows="4" style="width:100%" placeholder="Corso avanzato di paste colorate&#10;Weekend intensivo tortellini&#10;Corso per bambini"></textarea></label></p>';
	echo '<p><label><input type="checkbox" name="pubblico" value="1"> Risultati visibili alle sfogline</label></p>';
	echo '<p><label><input type="checkbox" name="proposte_aperte" value="1"> Le sfogline possono proporre nuove idee</label></p>';
	echo '<p><label><input type="checkbox" name="avvisa" value="1"> Avvisa subito le sfogline (aeroplanino, con link diretto al sondaggio)</label></p>';
	echo '<p><button type="submit" class="gs-btn gs-btn-sm gs-btn-verde">Crea sondaggio</button> <span class="gs-form-msg gs-richiesta-esito"></span></p>';
	echo '</form>';

	$tutti = gs_sondaggi_tutti();
	echo '<h4>Sondaggi (' . count( $tutti ) . ')</h4>';
	if ( ! $tutti ) {
		echo '<p class="gs-hint">Nessun sondaggio ancora creato.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="10">';
		foreach ( $tutti as $post ) {
			$s         = gs_sondaggio_get( $post->ID );
			$conteggio = gs_sondaggio_conteggio( $s['proposte'], $s['voti'] );
			$totale    = array_sum( $conteggio );

			echo '<details class="gs-inbox-item" data-sondaggio="' . (int) $post->ID . '">';
			echo '<summary class="gs-inbox-oggetto">' . esc_html( $s['domanda'] )
				. ' — ' . ( 'aperto' === $s['stato'] ? 'aperto' : 'chiuso' )
				. ' — ' . ( $s['pubblico'] ? 'pubblico' : 'privato' )
				. ' (' . (int) $totale . ( 1 === $totale ? ' voto' : ' voti' ) . ')</summary>';
			echo '<div class="gs-inbox-corpo">';

			echo '<form class="gs-form" data-action="gs_sondaggio_modifica">';
			echo '<input type="hidden" name="sondaggio" value="' . (int) $post->ID . '">';
			echo '<p><label>Domanda<br><input type="text" name="domanda" value="' . esc_attr( $s['domanda'] ) . '" required style="width:100%"></label></p>';
			echo '<p><label>Descrizione<br><textarea name="descrizione" rows="2" style="width:100%">' . esc_textarea( $s['descrizione'] ) . '</textarea></label></p>';
			echo '<p><label><input type="checkbox" name="pubblico" value="1"' . checked( $s['pubblico'], true, false ) . '> Risultati visibili alle sfogline</label></p>';
			echo '<p><label><input type="checkbox" name="proposte_aperte" value="1"' . checked( $s['proposte_aperte'], true, false ) . '> Le sfogline possono proporre nuove idee</label></p>';
			echo '<p><label>Stato<br><select name="stato"><option value="aperto"' . selected( $s['stato'], 'aperto', false ) . '>Aperto</option><option value="chiuso"' . selected( $s['stato'], 'chiuso', false ) . '>Chiuso</option></select></label></p>';
			echo '<p><button type="submit" class="gs-btn gs-btn-sm gs-btn-verde">✏️ Salva modifiche</button> <span class="gs-form-msg gs-richiesta-esito"></span></p>';
			echo '</form>';

			echo '<div class="gs-sond-risultati"><strong>Voti per proposta:</strong>';
			if ( ! $s['proposte'] ) {
				echo '<p class="gs-hint">Nessuna proposta ancora inserita.</p>';
			}
			foreach ( $s['proposte'] as $p ) {
				$n    = isset( $conteggio[ $p['id'] ] ) ? $conteggio[ $p['id'] ] : 0;
				$perc = $totale ? round( $n / $totale * 100 ) : 0;
				echo '<p class="gs-progress-label">' . esc_html( $p['testo'] ) . ( $p['autore'] ? ' <span class="gs-hint">(proposta da una sfoglina)</span>' : '' ) . ' — ' . (int) $n . ( 1 === $n ? ' voto' : ' voti' ) . ' (' . $perc . '%)</p>';
				echo '<div class="gs-progress"><div class="gs-progress-bar" style="width:' . $perc . '%"></div></div>';
			}
			echo '</div>';

			echo '<p><button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-sond-elimina" data-sondaggio="' . (int) $post->ID . '">Elimina</button> <span class="gs-sond-row-msg gs-richiesta-esito"></span></p>';
			echo '</div></details>';
		}
		echo '</div>';
	}

	$trash = gs_sondaggi_cestino();
	echo '<div class="gs-sezione-cestino">';
	echo '<h4 class="gs-titolo-cestino">🗑️ Cestino sondaggi</h4>';
	if ( ! $trash ) {
		echo '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		echo '<table class="gs-table gs-paginate" data-per-page="8"><thead><tr>' . gs_cestino_th_checkbox() . '<th>Domanda</th><th></th></tr></thead><tbody>';
		foreach ( $trash as $post ) {
			echo '<tr data-sondaggio="' . (int) $post->ID . '">' . gs_cestino_td_checkbox( $post->ID ) . '<td>' . esc_html( get_the_title( $post ) ) . '</td>';
			echo '<td><button class="gs-btn gs-btn-sm gs-btn-verde gs-sond-ripristina" data-sondaggio="' . (int) $post->ID . '">Ripristina</button> <span class="gs-sond-trow-msg gs-richiesta-esito"></span></td></tr>';
		}
		echo '</tbody></table>';
		echo gs_cestino_azioni_bulk( 'gs_sondaggio' );
	}
	echo '</div>';

	echo gs_box_close();
}
