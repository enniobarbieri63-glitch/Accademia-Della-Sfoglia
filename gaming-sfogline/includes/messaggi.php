<?php
/**
 * messaggi.php — Messaggi privati dal pannello alle sfogline.
 * Il gestore invia messaggi a una singola sfoglina oppure a tutte in una volta.
 * Ogni sfoglina ha la sua casella "Messaggi" con lo stato letto/non letto.
 *
 * Struttura: CPT privato gs_messaggio
 *  - post_title   = oggetto
 *  - post_content = testo
 *  - meta gs_dest = ID sfoglina destinataria, oppure 0 = a tutte
 *  - meta gs_letto_da = array di ID che hanno letto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_register_messaggio_cpt' );
function gs_register_messaggio_cpt() {
	register_post_type( 'gs_messaggio', array(
		'labels'       => array( 'name' => 'Messaggi' ),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'title', 'editor', 'author' ),
	) );
}

// -----------------------------------------------------------------------------
// Invio
// -----------------------------------------------------------------------------

/** Crea un messaggio. $dest = ID sfoglina, oppure 0 per "a tutte". */
function gs_invia_messaggio( $dest, $oggetto, $testo ) {
	$mid = wp_insert_post( array(
		'post_type'    => 'gs_messaggio',
		'post_status'  => 'publish',
		'post_title'   => $oggetto ? $oggetto : 'Messaggio',
		'post_content' => $testo,
	) );
	if ( is_wp_error( $mid ) || ! $mid ) {
		return false;
	}
	update_post_meta( $mid, 'gs_dest', (int) $dest );
	update_post_meta( $mid, 'gs_letto_da', array() );
	return $mid;
}

// -----------------------------------------------------------------------------
// Lettura (lato sfoglina)
// -----------------------------------------------------------------------------

/** Messaggi per una sfoglina: quelli a lei destinati + quelli a tutte. */
function gs_get_messaggi_utente( $uid ) {
	$msgs = get_posts( array(
		'post_type'      => 'gs_messaggio',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_query'     => array(
			'relation' => 'OR',
			array( 'key' => 'gs_dest', 'value' => (int) $uid ),
			array( 'key' => 'gs_dest', 'value' => 0 ),
		),
	) );
	return gs_solo_tipo( $msgs, 'gs_messaggio' );
}

function gs_messaggio_letto( $mid, $uid ) {
	$letti = get_post_meta( $mid, 'gs_letto_da', true );
	return is_array( $letti ) && in_array( (int) $uid, array_map( 'intval', $letti ), true );
}

function gs_segna_letto( $mid, $uid ) {
	$letti = get_post_meta( $mid, 'gs_letto_da', true );
	$letti = is_array( $letti ) ? array_map( 'intval', $letti ) : array();
	if ( ! in_array( (int) $uid, $letti, true ) ) {
		$letti[] = (int) $uid;
		update_post_meta( $mid, 'gs_letto_da', $letti );
	}
}

/** Numero di messaggi non letti per una sfoglina. */
function gs_messaggi_non_letti( $uid ) {
	$n = 0;
	foreach ( gs_get_messaggi_utente( $uid ) as $m ) {
		if ( ! gs_messaggio_letto( $m->ID, $uid ) ) { $n++; }
	}
	return $n;
}

// -----------------------------------------------------------------------------
// Risposte: ogni sfoglina ha il proprio thread privato su ogni messaggio
// ricevuto (anche quelli "a tutte" — le risposte non sono condivise fra
// sfogline). Stesso stile a bolle delle Conversazioni (.gs-conv-msg).
// -----------------------------------------------------------------------------

/** Il thread di risposte di una sfoglina su un messaggio (array di autore/testo/data). */
function gs_msg_risposte_get( $mid, $uid ) {
	$tutte = get_post_meta( (int) $mid, 'gs_risposte', true );
	$tutte = is_array( $tutte ) ? $tutte : array();
	$uid   = (int) $uid;
	return isset( $tutte[ $uid ] ) && is_array( $tutte[ $uid ] ) ? $tutte[ $uid ] : array();
}

/** Aggiunge una voce al thread di $thread_uid, scritta da $autore_uid (la sfoglina o la segreteria). */
function gs_msg_risposta_aggiungi( $mid, $thread_uid, $autore_uid, $testo ) {
	$mid        = (int) $mid;
	$thread_uid = (int) $thread_uid;
	$autore_uid = (int) $autore_uid;
	$testo      = trim( (string) $testo );
	if ( ! $mid || ! $thread_uid || ! $autore_uid || '' === $testo ) { return false; }
	$tutte = get_post_meta( $mid, 'gs_risposte', true );
	$tutte = is_array( $tutte ) ? $tutte : array();
	if ( ! isset( $tutte[ $thread_uid ] ) || ! is_array( $tutte[ $thread_uid ] ) ) { $tutte[ $thread_uid ] = array(); }
	$tutte[ $thread_uid ][] = array( 'autore' => $autore_uid, 'testo' => $testo, 'data' => current_time( 'mysql' ) );
	if ( count( $tutte[ $thread_uid ] ) > 100 ) { $tutte[ $thread_uid ] = array_slice( $tutte[ $thread_uid ], -100 ); }
	update_post_meta( $mid, 'gs_risposte', $tutte );
	return true;
}

/**
 * True se l'ultima voce del thread è stata scritta dalla sfoglina: la
 * segreteria non ha ancora risposto e il messaggio "attende una risposta".
 */
function gs_msg_attende_risposta( $mid, $thread_uid ) {
	// Una risposta eliminata dal gestore non deve far lampeggiare "in attesa"
	// se era proprio lei l'ultima della sfoglina.
	$thread = array_values( array_filter( gs_msg_risposte_get( $mid, $thread_uid ), function ( $r ) {
		return empty( $r['gs_eliminato'] );
	} ) );
	if ( ! $thread ) { return false; }
	$ultima = end( $thread );
	return (int) $ultima['autore'] === (int) $thread_uid;
}

/** Uid delle sfogline che hanno almeno un thread di risposte su questo messaggio. */
function gs_msg_risposte_utenti( $mid ) {
	$tutte = get_post_meta( (int) $mid, 'gs_risposte', true );
	return is_array( $tutte ) ? array_map( 'intval', array_keys( $tutte ) ) : array();
}

/** Quanti thread (su tutti i messaggi) attendono ancora una risposta della segreteria (per la Bacheca di riepilogo). */
function gs_msg_totale_in_attesa() {
	$n = 0;
	$msgs = gs_solo_tipo( get_posts( array( 'post_type' => 'gs_messaggio', 'post_status' => 'publish', 'posts_per_page' => -1, 'suppress_filters' => true ) ), 'gs_messaggio' );
	foreach ( $msgs as $m ) {
		foreach ( gs_msg_risposte_utenti( $m->ID ) as $ru ) {
			if ( gs_msg_attende_risposta( $m->ID, $ru ) ) { $n++; }
		}
	}
	return $n;
}

/**
 * Bolla del messaggio originale della segreteria, stesso schema colore di
 * Conversazioni e Aiuto (.gs-conv-msg, mai "mine": chi lo consulta — la
 * sfoglina o il titolare — non ne è mai l'autrice) — richiesto da Ennio il
 * 2026-07-29: "crea la stessa impostazione visiva che hai creato in
 * Conversazioni private", prima era testo semplice senza bolla.
 */
function gs_messaggio_bolla_html( $m ) {
	$out  = '<div class="gs-conv-thread"><div class="gs-conv-msg">';
	$out .= '<span class="gs-conv-from">Segreteria:</span> ' . nl2br( esc_html( gs_msg_clean( $m->post_content ) ) );
	$out .= '</div></div>';
	return $out;
}

/**
 * Bolle whatsapp (stesso stile di .gs-conv-msg) per il thread di $thread_uid,
 * più il modulo per aggiungere una risposta.
 * $vista_gestore = false: guarda la sfoglina, le sue bolle vanno a destra (verde).
 * $vista_gestore = true: guarda la segreteria, le bolle della segreteria (chiunque
 * abbia risposto) vanno a destra — quelle della sfoglina restano a sinistra.
 */
function gs_msg_thread_html( $mid, $thread_uid, $vista_gestore = false ) {
	$thread = gs_msg_risposte_get( $mid, $thread_uid );
	$out    = '';
	if ( $thread ) {
		$out .= '<div class="gs-conv-thread">';
		foreach ( $thread as $i => $r ) {
			// Una risposta eliminata dal gestore resta nei dati (mai una
			// perdita definitiva) ma non compare a chi non modera: solo il
			// gestore la vede, sbiadita, con il pulsante per ripristinarla
			// (punto 6, Ennio, delega "finisci tutto" del 22/08/2026).
			$eliminata = ! empty( $r['gs_eliminato'] );
			if ( $eliminata && ! $vista_gestore ) { continue; }
			$au        = get_userdata( $r['autore'] );
			$e_sfoglina = (int) $r['autore'] === (int) $thread_uid;
			$mine      = ( $vista_gestore ? ! $e_sfoglina : $e_sfoglina ) ? ' mine' : '';
			$out .= '<div class="gs-conv-msg' . $mine . '"' . ( $eliminata ? ' style="opacity:.5"' : '' ) . '><span class="gs-conv-from">' . esc_html( $au ? $au->display_name : '—' ) . '</span> '
				. nl2br( esc_html( $r['testo'] ) ) . ' <span class="gs-msg-data">' . esc_html( date_i18n( 'j/m/Y H:i', strtotime( $r['data'] ) ) ) . '</span>';
			if ( $eliminata ) { $out .= ' <span class="gs-msg-tag">eliminata</span>'; }
			if ( $vista_gestore ) {
				$out .= ' <button class="gs-btn gs-btn-sm gs-btn-ghost gs-msg-risposta-toggle" data-msg="' . (int) $mid . '" data-thread="' . (int) $thread_uid . '" data-i="' . (int) $i . '" data-azione="' . ( $eliminata ? 'ripristina' : 'elimina' ) . '">' . ( $eliminata ? 'Ripristina' : 'Elimina' ) . '</button> <span class="gs-msg-risposta-toggle-esito gs-richiesta-esito"></span>';
			}
			$out .= '</div>';
		}
		$out .= '</div>';
	}
	$out .= '<form class="gs-form gs-form-msg-risposta" onsubmit="return false" data-msg="' . (int) $mid . '" data-thread="' . (int) $thread_uid . '">';
	if ( function_exists( 'gs_antispam_fields' ) ) { ob_start(); gs_antispam_fields(); $out .= ob_get_clean(); }
	$out .= '<p><textarea name="testo" rows="2" style="width:100%" placeholder="Scrivi una risposta…"></textarea></p>';
	$out .= '<p><button class="gs-btn gs-btn-sm gs-msg-risposta-invia">Invia risposta</button> <span class="gs-msg-risposta-msg gs-richiesta-esito"></span></p>';
	$out .= '</form>';
	return $out;
}

add_action( 'wp_ajax_gs_msg_risposta_invia', 'gs_ajax_msg_risposta_invia' );
function gs_ajax_msg_risposta_invia() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }
	$mid    = isset( $_POST['msg'] ) ? (int) $_POST['msg'] : 0;
	$thread = isset( $_POST['thread'] ) ? (int) $_POST['thread'] : 0;
	if ( 'gs_messaggio' !== get_post_type( $mid ) ) { wp_send_json_error( array( 'message' => 'Messaggio non valido.' ) ); }

	$sono_gestore = function_exists( 'gs_can_manage' ) && gs_can_manage();
	// La sfoglina può scrivere solo nel proprio thread; la segreteria in quello di chiunque.
	if ( ! $sono_gestore ) {
		$thread = $uid;
		$dest   = (int) get_post_meta( $mid, 'gs_dest', true );
		if ( 0 !== $dest && $dest !== $uid ) { wp_send_json_error( array( 'message' => 'Questo messaggio non è per te.' ) ); }
	}
	if ( ! $thread ) { wp_send_json_error( array( 'message' => 'Destinataria non valida.' ) ); }

	if ( function_exists( 'gs_antispam_check' ) ) {
		$check = gs_antispam_check( $_POST, 'msg_risposta' );
		if ( is_wp_error( $check ) ) { wp_send_json_error( array( 'message' => $check->get_error_message() ) ); }
	}

	$testo = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';
	if ( function_exists( 'gs_msg_clean' ) ) { $testo = gs_msg_clean( $testo ); }
	if ( '' === trim( $testo ) ) { wp_send_json_error( array( 'message' => 'Scrivi qualcosa prima di inviare.' ) ); }

	gs_msg_risposta_aggiungi( $mid, $thread, $uid, $testo );

	if ( $sono_gestore && function_exists( 'gs_mail_progetto' ) ) {
		gs_mail_progetto( $thread, 'messaggi', 'Nuova risposta — Accademia della Sfoglia', "La segreteria ti ha risposto:\n\n" . $testo );
	} elseif ( ! $sono_gestore && function_exists( 'gs_inbox_crea' ) ) {
		$u = get_userdata( $uid );
		gs_inbox_crea( 'Risposta a un messaggio: ' . get_the_title( $mid ), ( $u ? $u->display_name : '' ) . ' ha risposto a un messaggio della segreteria.', array( 'from' => $u ? $u->display_name : 'Sfoglina' ) );
	}
	wp_send_json_success( array( 'message' => 'Risposta inviata.' ) );
}

/**
 * Elimina/ripristina UNA risposta nel thread di un messaggio, senza toccare
 * il resto del thread: eliminazione "leggera", resta nei dati e si può
 * ripristinare dallo stesso pannello — mai una perdita definitiva (punto 6,
 * Ennio, delega "finisci tutto" del 22/08/2026).
 */
add_action( 'wp_ajax_gs_msg_risposta_toggle', 'gs_ajax_msg_risposta_toggle' );
function gs_ajax_msg_risposta_toggle() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$mid    = isset( $_POST['msg'] ) ? (int) $_POST['msg'] : 0;
	$thread = isset( $_POST['thread'] ) ? (int) $_POST['thread'] : 0;
	$i      = isset( $_POST['i'] ) ? (int) $_POST['i'] : -1;
	$azione = isset( $_POST['azione'] ) ? sanitize_key( wp_unslash( $_POST['azione'] ) ) : '';
	if ( 'gs_messaggio' !== get_post_type( $mid ) ) { wp_send_json_error( array( 'message' => 'Messaggio non valido.' ) ); }
	if ( ! in_array( $azione, array( 'elimina', 'ripristina' ), true ) ) { wp_send_json_error( array( 'message' => 'Azione non valida.' ) ); }
	$tutte = get_post_meta( $mid, 'gs_risposte', true );
	$tutte = is_array( $tutte ) ? $tutte : array();
	if ( ! isset( $tutte[ $thread ][ $i ] ) ) { wp_send_json_error( array( 'message' => 'Risposta non trovata.' ) ); }
	$tutte[ $thread ][ $i ]['gs_eliminato'] = ( 'elimina' === $azione );
	update_post_meta( $mid, 'gs_risposte', $tutte );
	wp_send_json_success( array( 'message' => 'elimina' === $azione ? 'Risposta eliminata (recuperabile da qui).' : 'Risposta ripristinata.' ) );
}

/**
 * AJAX: conteggio dei messaggi non letti della sfoglina corrente.
 * Interrogato ogni pochi secondi dal front-end per far comparire l'aeroplanino
 * "MESSAGGIO IN ARRIVO" in tempo reale, senza ricaricare la pagina.
 */
add_action( 'wp_ajax_gs_msg_conteggio', 'gs_ajax_msg_conteggio' );
function gs_ajax_msg_conteggio() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) {
		wp_send_json_error( array( 'non_letti' => 0 ) );
	}
	wp_send_json_success( array( 'non_letti' => (int) gs_messaggi_non_letti( $uid ) ) );
}

/** Sezione casella messaggi (segna letti alla visualizzazione). */
function gs_render_messaggi( $uid, $mark_read = true ) {
	$msgs = gs_get_messaggi_utente( $uid );
	$out  = gs_box_open( '✉️ Messaggi dalla segreteria' );
	$out .= gs_sezione_aiuto( 'Apri un messaggio per leggerlo: viene segnato come letto automaticamente. Il messaggio della segreteria e le tue risposte compaiono a bolle, come nelle Conversazioni: crema il messaggio ricevuto, verde le tue risposte. Puoi rispondere direttamente sotto ogni messaggio. Qualche messaggio speciale (un premio per un livello o un badge raggiunto) porta con sé anche un video, che vedi direttamente qui dentro. Se hai anche una conversazione privata con un esperto, la trovi più sotto e puoi rispondere direttamente da lì.' );
	if ( empty( $msgs ) ) {
		$out .= '<p>Non hai messaggi.</p>';
	} else {
		$out .= '<div class="gs-inbox-lista">';
		foreach ( $msgs as $m ) {
			$non_letto = ! gs_messaggio_letto( $m->ID, $uid );
			$cls = $non_letto ? ' gs-non-letto' : '';
			$tag = ( (int) get_post_meta( $m->ID, 'gs_dest', true ) === 0 ) ? ' <span class="gs-msg-tag">a tutte</span>' : '';
			$out .= '<details class="gs-inbox-item' . $cls . '">';
			$out .= '<summary class="gs-inbox-oggetto">' . ( $non_letto ? '<span class="gs-dot"></span> ' : '' )
				. esc_html( get_the_title( $m ) ) . $tag
				. ' <span class="gs-msg-data">' . esc_html( date_i18n( 'j/m/Y H:i', get_post_time( 'U', false, $m ) ) ) . '</span></summary>';
			$out .= '<div class="gs-inbox-corpo">' . gs_messaggio_bolla_html( $m );
			// Un premio-video (premi-traguardi.php) allega il link qui: lo si vede
			// incorporato, non solo come URL scritto nel testo del messaggio.
			$premio_video = get_post_meta( $m->ID, 'gs_premio_video_url', true );
			if ( $premio_video && function_exists( 'gs_video_embed_html' ) ) {
				$out .= gs_video_embed_html( $premio_video );
			}
			$out .= gs_msg_thread_html( $m->ID, $uid );
			$out .= '</div>';
			$out .= '</details>';
			if ( $mark_read && $non_letto ) {
				gs_segna_letto( $m->ID, $uid );
			}
		}
		$out .= '</div>';
	}
	$out .= gs_box_close();
	return $out;
}

// Shortcode dedicato per la pagina "Messaggi".
add_shortcode( 'gs_messaggi', 'gs_sc_messaggi' );
function gs_sc_messaggi() {
	if ( $g = gs_gate_riservato() ) { return $g; }
	if ( ! gs_is_approved( get_current_user_id() ) ) {
		return '<div class="gs-box gs-notice">Il tuo account è in attesa di approvazione.</div>';
	}
	$out  = gs_render_messaggi( get_current_user_id(), true );
	if ( function_exists( 'gs_render_conversazioni' ) ) {
		$out .= gs_render_conversazioni( get_current_user_id(), true );
	}
	return $out;
}

// -----------------------------------------------------------------------------
// Pannello gestore: composizione e invio
// -----------------------------------------------------------------------------
function gs_pannello_messaggi() {
	if ( ! ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) ) { return; }

	echo gs_box_open( 'Messaggi privati alle sfogline', 'gs-box-msg' );
	?>
	<p class="gs-hint">Invia un messaggio privato a una singola sfoglina oppure a tutte in una volta. Le sfogline lo leggono nella loro casella «Messaggi» e possono risponderti direttamente da lì: le risposte compaiono qui sotto, sotto ogni messaggio inviato, a bolle come nelle Conversazioni. Il titolo del messaggio lampeggia in rosso 🔴 quando c'è una sfoglina che aspetta ancora una tua risposta.</p>
	<form class="gs-form gs-form-messaggio" onsubmit="return false">
		<p><label>Destinatario<br>
			<select name="dest" style="min-width:260px">
				<option value="tutte">📣 Tutte le sfogline</option>
				<?php foreach ( get_users( array( 'orderby' => 'display_name' ) ) as $u ) :
					if ( ! gs_e_sfoglina_vera( $u, false ) ) { continue; } ?>
					<option value="<?php echo (int) $u->ID; ?>"><?php echo esc_html( $u->display_name ); ?></option>
				<?php endforeach; ?>
			</select></label></p>
		<p><label>Oggetto<br><input type="text" name="oggetto" autocomplete="off" style="width:100%;max-width:420px"></label></p>
		<p><label>Messaggio<br><textarea name="testo" rows="4" style="width:100%"></textarea></label></p>
		<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-invia-messaggio">Invia messaggio</button> <span class="gs-messaggio-msg gs-richiesta-esito"></span></p>
	</form>
	<?php
	// Ultimi messaggi inviati.
	$inviati = gs_solo_tipo( get_posts( array( 'post_type' => 'gs_messaggio', 'post_status' => 'publish', 'posts_per_page' => 8 ) ), 'gs_messaggio' );
	if ( $inviati ) {
		echo '<hr><strong>Ultimi messaggi inviati</strong><div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="8">';
		foreach ( $inviati as $m ) {
			$dest = (int) get_post_meta( $m->ID, 'gs_dest', true );
			if ( 0 === $dest ) {
				$a = 'Tutte';
			} else {
				$u = get_user_by( 'id', $dest );
				$a = $u ? $u->display_name : '—';
			}
			$letti    = get_post_meta( $m->ID, 'gs_letto_da', true );
			$nletti   = is_array( $letti ) ? count( $letti ) : 0;
			$risp_uid = gs_msg_risposte_utenti( $m->ID );
			$attende  = false;
			foreach ( $risp_uid as $ru ) {
				if ( gs_msg_attende_risposta( $m->ID, $ru ) ) { $attende = true; break; }
			}
			echo '<details class="gs-inbox-item' . ( $attende ? ' gs-msg-attende-risposta gs-lampeggia-rosso' : '' ) . '"><summary class="gs-inbox-oggetto">' . ( $attende ? '🔴 ' : '' ) . esc_html( get_the_title( $m ) )
				. ' <span class="gs-msg-data">a ' . esc_html( $a ) . ' · ' . esc_html( date_i18n( 'j/m/Y H:i', get_post_time( 'U', false, $m ) ) ) . ' · letti: ' . (int) $nletti . '</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			echo '<form class="gs-form gs-form-msgedit" data-id="' . (int) $m->ID . '" onsubmit="return false">';
			echo '<p><label>Oggetto<br><input type="text" name="titolo" autocomplete="off" value="' . esc_attr( get_the_title( $m ) ) . '" style="width:100%"></label></p>';
			echo '<p><label>Testo<br><textarea name="testo" rows="4" style="width:100%">' . esc_textarea( $m->post_content ) . '</textarea></label></p>';
			echo '<p><button class="gs-btn gs-btn-sm gs-msgedit-salva">Salva modifiche</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-msgedit-elimina">Elimina</button> ';
			echo '<span class="gs-msgedit-msg gs-richiesta-esito"></span></p></form>';
			if ( $risp_uid ) {
				echo '<h4>Risposte</h4>';
				foreach ( $risp_uid as $ru ) {
					$su       = get_userdata( $ru );
					$in_attesa = gs_msg_attende_risposta( $m->ID, $ru );
					echo '<details class="gs-inbox-item' . ( $in_attesa ? ' gs-msg-attende-risposta gs-lampeggia-rosso' : '' ) . '"><summary class="gs-inbox-oggetto">' . ( $in_attesa ? '🔴 ' : '' ) . esc_html( $su ? $su->display_name : '—' ) . '</summary>';
					echo '<div class="gs-inbox-corpo">' . gs_msg_thread_html( $m->ID, $ru, true ) . '</div></details>';
				}
			}
			echo '</div></details>';
		}
		echo '</div>';
	}

	// Cestino: messaggi eliminati, sempre recuperabili — mai una cancellazione
	// definitiva salvo la sola azione bulk riservata al titolare qui sotto
	// (Ennio, 16/08/2026: "crea un cestino con messaggi recuperabili").
	$trash = gs_solo_tipo( get_posts( array( 'post_type' => 'gs_messaggio', 'post_status' => 'trash', 'posts_per_page' => 50 ) ), 'gs_messaggio' );
	echo '<div class="gs-sezione-cestino">';
	echo '<h4 class="gs-titolo-cestino">🗑️ Cestino messaggi</h4>';
	if ( ! $trash ) {
		echo '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		echo '<table class="gs-table gs-paginate" data-per-page="10"><thead><tr>' . gs_cestino_th_checkbox() . '<th>Oggetto</th><th>Data</th><th></th></tr></thead><tbody>';
		foreach ( $trash as $m ) {
			echo '<tr data-id="' . (int) $m->ID . '">' . gs_cestino_td_checkbox( $m->ID ) . '<td>' . esc_html( get_the_title( $m ) ) . '</td><td>' . esc_html( get_the_time( 'j/m/Y', $m ) ) . '</td>';
			echo '<td><button class="gs-btn gs-btn-sm gs-btn-verde gs-msgedit-ripristina" data-id="' . (int) $m->ID . '">Ripristina</button> <span class="gs-msgedit-tmsg gs-richiesta-esito"></span></td></tr>';
		}
		echo '</tbody></table>';
		echo gs_cestino_azioni_bulk( 'gs_messaggio' );
	}
	echo '</div>';
	echo gs_box_close();
}

add_action( 'wp_ajax_gs_fe_invia_messaggio', 'gs_ajax_fe_invia_messaggio' );
function gs_ajax_fe_invia_messaggio() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$dest_raw = isset( $_POST['dest'] ) ? sanitize_text_field( wp_unslash( $_POST['dest'] ) ) : '';
	$oggetto  = isset( $_POST['oggetto'] ) ? sanitize_text_field( wp_unslash( $_POST['oggetto'] ) ) : '';
	$testo    = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';

	if ( '' === trim( $testo ) ) { wp_send_json_error( array( 'message' => 'Scrivi un messaggio.' ) ); }
	if ( '' === trim( $oggetto ) ) { $oggetto = 'Messaggio dalla segreteria'; }

	$dest = ( 'tutte' === $dest_raw ) ? 0 : (int) $dest_raw;
	if ( 0 !== $dest && ! get_userdata( $dest ) ) { wp_send_json_error( array( 'message' => 'Destinataria non valida.' ) ); }

	$mid = gs_invia_messaggio( $dest, $oggetto, $testo );
	if ( ! $mid ) { wp_send_json_error( array( 'message' => 'Errore nell\'invio.' ) ); }

	$dove = ( 0 === $dest ) ? 'a tutte le sfogline' : 'alla sfoglina selezionata';
	wp_send_json_success( array( 'message' => 'Messaggio inviato ' . $dove . '.' ) );
}

// -----------------------------------------------------------------------------
// Modifica / elimina messaggi inviati (gestore)
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_msgedit_salva', 'gs_ajax_msgedit_salva' );
function gs_ajax_msgedit_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( 'gs_messaggio' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Messaggio non valido.' ) ); }
	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	$testo  = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';
	if ( function_exists( 'gs_msg_clean' ) ) { $testo = gs_msg_clean( $testo ); }
	wp_update_post( array( 'ID' => $id, 'post_title' => $titolo ? $titolo : get_the_title( $id ), 'post_content' => $testo ) );
	wp_send_json_success( array( 'message' => 'Messaggio modificato.' ) );
}

add_action( 'wp_ajax_gs_msgedit_elimina', 'gs_ajax_msgedit_elimina' );
function gs_ajax_msgedit_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( 'gs_messaggio' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Messaggio non valido.' ) ); }
	wp_trash_post( $id ); // reversibile: resta nel Cestino messaggi qui sotto
	wp_send_json_success( array( 'message' => 'Spostato nel cestino.' ) );
}

add_action( 'wp_ajax_gs_msgedit_ripristina', 'gs_ajax_msgedit_ripristina' );
function gs_ajax_msgedit_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( 'gs_messaggio' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Messaggio non valido.' ) ); }
	wp_untrash_post( $id );
	wp_send_json_success( array( 'message' => 'Messaggio ripristinato.' ) );
}

// -----------------------------------------------------------------------------
// PANNELLO — messaggi di ogni sfoglina (controllo completo)
// -----------------------------------------------------------------------------
function gs_pannello_messaggi_sfogline() {
	if ( ! gs_can_manage() ) { return; }
	$sfogline = function_exists( 'gs_sez_sfogline' ) ? gs_sez_sfogline() : array();
	echo gs_box_open( 'Messaggi di ogni sfoglina', 'gs-box-msg' );
	echo '<p class="gs-hint">Scegli una sfoglina per vedere tutti i suoi messaggi: quelli ricevuti dalla segreteria, le sue richieste di aiuto e le sue conversazioni private. Clicca il titolo per aprire il contenuto.</p>';
	if ( ! $sfogline ) {
		echo '<p class="gs-hint">Nessuna sfoglina registrata.</p>' . gs_box_close();
		return;
	}
	echo '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="8">';
	foreach ( $sfogline as $u ) {
		$ric   = gs_get_messaggi_utente( $u->ID );
		$aiuti = gs_solo_tipo( gs_get_posts_by_author( array( 'post_type' => 'gs_aiuto', 'post_status' => 'publish', 'author' => $u->ID, 'posts_per_page' => 50, 'suppress_filters' => true ) ), 'gs_aiuto' );
		$conv  = function_exists( 'gs_conv_di_utente' ) ? gs_conv_di_utente( $u->ID ) : array();
		$tot   = count( $ric ) + count( $aiuti ) + count( $conv );
		echo '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">' . esc_html( $u->display_name )
			. ' <span class="gs-msg-data">' . (int) $tot . ' messaggi</span></summary><div class="gs-inbox-corpo">';

		echo '<h4>Ricevuti dalla segreteria</h4>';
		if ( $ric ) {
			echo '<div class="gs-inbox-lista gs-paginate" data-per-page="5">';
			foreach ( $ric as $m ) {
				echo '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">' . esc_html( get_the_title( $m ) )
					. ' <span class="gs-msg-data">' . esc_html( date_i18n( 'j/m/Y', get_post_time( 'U', false, $m ) ) ) . ' · ' . ( gs_messaggio_letto( $m->ID, $u->ID ) ? 'letto' : 'non letto' ) . '</span></summary>';
				echo '<div class="gs-inbox-corpo">' . gs_messaggio_bolla_html( $m ) . '</div></details>';
			}
			echo '</div>';
		} else {
			echo '<p class="gs-hint">Nessun messaggio ricevuto.</p>';
		}

		echo '<h4>Richieste di aiuto e suggerimenti</h4>';
		if ( $aiuti ) {
			echo '<div class="gs-inbox-lista gs-paginate" data-per-page="5">';
			foreach ( $aiuti as $a ) {
				$tipo = get_post_meta( $a->ID, 'gs_tipo', true );
				echo '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">' . ( 'suggerimento' === $tipo ? '💡 Suggerimento' : '🆘 Aiuto' )
					. ' <span class="gs-msg-data">' . esc_html( get_the_time( 'j/m/Y', $a ) ) . '</span></summary>';
				echo '<div class="gs-inbox-corpo"><div class="gs-inbox-testo">' . nl2br( esc_html( gs_msg_clean( $a->post_content ) ) ) . '</div></div></details>';
			}
			echo '</div>';
		} else {
			echo '<p class="gs-hint">Nessuna richiesta inviata.</p>';
		}

		echo '<h4>🔒 Conversazioni private</h4>';
		echo function_exists( 'gs_msg_conversazioni_html' ) ? gs_msg_conversazioni_html( $u->ID ) : '<p class="gs-hint">Nessuna conversazione privata.</p>';

		echo '</div></details>';
	}
	echo '</div>';
	echo gs_box_close();

	// Collaboratori: solo le loro conversazioni private (non hanno "ricevuti dalla segreteria" né "richieste di aiuto", concetti solo per le sfogline).
	$collaboratori = function_exists( 'gs_sez_collaboratori' ) ? gs_sez_collaboratori() : array();
	if ( $collaboratori ) {
		echo gs_box_open( 'Conversazioni di ogni collaboratore', 'gs-box-msg' );
		echo '<p class="gs-hint">Le conversazioni private di ogni collaboratore (es. Rina Poletti, Bruno Cingolani) con le sfogline. Solo tu, come titolare, vedi questo pannello — le sfogline vedono sempre e solo le proprie conversazioni.</p>';
		echo '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="8">';
		foreach ( $collaboratori as $co ) {
			$conv = function_exists( 'gs_conv_di_utente' ) ? gs_conv_di_utente( $co->ID ) : array();
			echo '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">' . esc_html( $co->display_name )
				. ' <span class="gs-msg-data">' . count( $conv ) . ' conversazioni</span></summary><div class="gs-inbox-corpo">';
			echo function_exists( 'gs_msg_conversazioni_html' ) ? gs_msg_conversazioni_html( $co->ID ) : '<p class="gs-hint">Nessuna conversazione privata.</p>';
			echo '</div></details>';
		}
		echo '</div>';
		echo gs_box_close();
	}
}
