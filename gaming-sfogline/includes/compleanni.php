<?php
/**
 * compleanni.php — "I Compleanni di Oggi".
 *
 * In base alla data di nascita delle iscritte (meta gs_birthdate = Y-m-d), il
 * giorno del compleanno la vetrina della sfoglina viene mostrata automaticamente
 * e resta visibile fino al compleanno successivo. Sotto la vetrina le sfogline
 * possono lasciare messaggi di auguri (CPT gs_augurio). Dal pannello il gestore
 * può oscurare o mostrare la vetrina del compleanno.
 *
 * Il giorno stesso, un annuncio "🎂 OGGI FESTEGGIA [NOME]!" vola in automatico
 * a tutte le sfogline (aeroplanino, gira sul cron giornaliero) — non serve
 * aprire la pagina per scoprirlo, e cliccandoci sopra si arriva dritti alla
 * vetrina degli auguri.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// CPT per i messaggi di auguri.
add_action( 'init', 'gs_register_augurio_cpt' );
function gs_register_augurio_cpt() {
	register_post_type( 'gs_augurio', array(
		'labels'       => array( 'name' => 'Auguri' ),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'editor', 'author' ),
	) );
}

// -----------------------------------------------------------------------------
// Helper data di nascita / compleanni
// -----------------------------------------------------------------------------
function gs_bday_get( $uid ) {
	$d = get_user_meta( $uid, 'gs_birthdate', true );
	return ( $d && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) ) ? $d : '';
}
function gs_bday_hidden( $uid ) {
	return '1' === (string) get_user_meta( $uid, 'gs_bday_hidden', true );
}

/** Tutte le sfogline approvate con una data di nascita valida. */
function gs_bday_tutte() {
	$out = array();
	foreach ( get_users( array( 'orderby' => 'display_name' ) ) as $u ) {
		if ( ! gs_e_sfoglina_vera( $u ) ) { continue; }
		$d = gs_bday_get( $u->ID );
		if ( $d ) { $out[] = array( 'user' => $u, 'date' => $d ); }
	}
	return $out;
}

/** Timestamp dell'ultima occorrenza del compleanno (<= oggi) per l'ordinamento. */
function gs_bday_ultima_occorrenza( $date, $oggi = null ) {
	$oggi = $oggi ? $oggi : current_time( 'timestamp' );
	$md   = substr( $date, 5 ); // MM-DD
	$anno = (int) date( 'Y', $oggi );
	$q    = strtotime( $anno . '-' . $md );
	if ( false === $q ) { // 29/02 su anno non bisestile
		$q = strtotime( $anno . '-' . substr( $md, 0, 2 ) . '-28' );
	}
	if ( $q > $oggi ) { $q = strtotime( ( $anno - 1 ) . '-' . $md ) ?: $q; }
	return $q;
}
function gs_bday_is_oggi( $date, $oggi = null ) {
	$oggi = $oggi ? $oggi : current_time( 'timestamp' );
	return substr( $date, 5 ) === date( 'm-d', $oggi );
}

/**
 * Le festeggiate "correnti": chi compie gli anni oggi; se oggi non c'è nessuno,
 * l'ultima festeggiata (la vetrina resta visibile fino al prossimo compleanno).
 * Esclude quelle oscurate dal gestore.
 */
function gs_bday_festeggiate() {
	$oggi  = current_time( 'timestamp' );
	$tutte = gs_bday_tutte();

	$today = array();
	foreach ( $tutte as $r ) {
		if ( gs_bday_is_oggi( $r['date'], $oggi ) && ! gs_bday_hidden( $r['user']->ID ) ) {
			$today[] = $r['user'];
		}
	}
	if ( $today ) { return array( 'oggi' => true, 'sfogline' => $today ); }

	// Nessuno oggi → l'ultima festeggiata (occorrenza più recente).
	$best = null; $best_ts = 0;
	foreach ( $tutte as $r ) {
		if ( gs_bday_hidden( $r['user']->ID ) ) { continue; }
		$ts = gs_bday_ultima_occorrenza( $r['date'], $oggi );
		if ( $ts > $best_ts ) { $best_ts = $ts; $best = $r['user']; }
	}
	return array( 'oggi' => false, 'sfogline' => $best ? array( $best ) : array(), 'quando' => $best_ts );
}

// -----------------------------------------------------------------------------
// Annuncio automatico: quando una sfoglina compie gli anni, tutte le altre
// lo vedono comparire da sole (aeroplanino), senza dover aprire la pagina
// dei Compleanni per scoprirlo. Gira sul cron giornaliero già esistente
// (gs_daily_cron); un flag con la data di oggi evita invii doppi se il cron
// gira più di una volta nello stesso giorno.
// -----------------------------------------------------------------------------
add_action( 'gs_daily_cron', 'gs_bday_annuncio_giornaliero' );
function gs_bday_annuncio_giornaliero() {
	$oggi = date( 'Y-m-d', current_time( 'timestamp' ) );
	if ( get_option( 'gs_bday_annuncio_data' ) === $oggi ) {
		return; // già annunciato oggi
	}

	$fest = gs_bday_festeggiate();
	if ( empty( $fest['oggi'] ) || empty( $fest['sfogline'] ) ) {
		update_option( 'gs_bday_annuncio_data', $oggi ); // nessun compleanno oggi: segna comunque il giorno come controllato
		return;
	}

	$link      = function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_compleanni' ) : home_url( '/' );
	$sfogline  = function_exists( 'gs_sez_sfogline' ) ? gs_sez_sfogline() : array();
	foreach ( $fest['sfogline'] as $festeggiata ) {
		$testo = '🎂 OGGI FESTEGGIA ' . mb_strtoupper( $festeggiata->display_name ) . '! FAI GLI AUGURI';
		foreach ( $sfogline as $u ) {
			if ( function_exists( 'gs_accoda_volo' ) ) {
				gs_accoda_volo( $u->ID, $testo, $link );
			}
		}
	}
	update_option( 'gs_bday_annuncio_data', $oggi );
}

// -----------------------------------------------------------------------------
// Auguri (messaggistica collegata)
// -----------------------------------------------------------------------------
function gs_auguri_lista( $target_uid, $anno ) {
	return get_posts( array(
		'post_type'      => 'gs_augurio',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'ASC',
		'meta_query'     => array(
			array( 'key' => 'gs_target', 'value' => (int) $target_uid ),
			array( 'key' => 'gs_anno', 'value' => (int) $anno ),
		),
	) );
}

add_action( 'wp_ajax_gs_augurio_invia', 'gs_ajax_augurio_invia' );
function gs_ajax_augurio_invia() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	// gs_puo_partecipare() copre anche la congelata: era rimasto il
	// controllo vecchio, trovato il 26/08/2026 insieme ad altri dieci. Una
	// congelata è ancora "iscritta", quindi il messaggio vecchio era diventato
	// fuorviante.
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }

	$check = gs_antispam_check( $_POST, 'auguri' );
	if ( is_wp_error( $check ) ) { wp_send_json_error( array( 'message' => $check->get_error_message() ) ); }

	$target = isset( $_POST['target'] ) ? (int) $_POST['target'] : 0;
	$testo  = isset( $_POST['testo'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) ) : '';
	if ( ! $target || ! get_userdata( $target ) ) { wp_send_json_error( array( 'message' => 'Destinataria non valida.' ) ); }
	if ( mb_strlen( $testo ) < 2 ) { wp_send_json_error( array( 'message' => 'Scrivi un messaggio di auguri.' ) ); }
	if ( mb_strlen( $testo ) > 1000 ) { $testo = mb_substr( $testo, 0, 1000 ); }
	$media = function_exists( 'gs_msg_upload' ) ? gs_msg_upload( 'media' ) : null;
	if ( is_wp_error( $media ) ) { wp_send_json_error( array( 'message' => $media->get_error_message() ) ); }

	// La destinataria deve essere una festeggiata corrente.
	$fest = gs_bday_festeggiate();
	$ok = false;
	foreach ( $fest['sfogline'] as $u ) { if ( (int) $u->ID === $target ) { $ok = true; break; } }
	if ( ! $ok ) { wp_send_json_error( array( 'message' => 'Gli auguri si possono lasciare solo alla festeggiata del momento.' ) ); }

	$aid = wp_insert_post( array(
		'post_type'    => 'gs_augurio',
		'post_status'  => 'publish',
		'post_author'  => $uid,
		'post_content' => $testo,
		'post_title'   => 'Augurio',
	) );
	if ( is_wp_error( $aid ) || ! $aid ) { wp_send_json_error( array( 'message' => 'Errore, riprova.' ) ); }
	update_post_meta( $aid, 'gs_target', $target );
	update_post_meta( $aid, 'gs_anno', (int) date( 'Y', current_time( 'timestamp' ) ) );
	if ( is_array( $media ) ) {
		update_post_meta( $aid, 'gs_media', $media['url'] );
		update_post_meta( $aid, 'gs_media_type', $media['type'] );
	}

	if ( function_exists( 'gs_add_points' ) ) { gs_add_points( $uid, 5, 'Auguri di compleanno' ); }

	wp_send_json_success( array( 'message' => 'Auguri inviati! 🎉' ) );
}

// La sfoglina imposta/aggiorna la propria data di nascita.
add_action( 'wp_ajax_gs_bday_salva', 'gs_ajax_bday_salva' );
function gs_ajax_bday_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }
	$d = isset( $_POST['nascita'] ) ? sanitize_text_field( wp_unslash( $_POST['nascita'] ) ) : '';
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) ) { wp_send_json_error( array( 'message' => 'Data non valida.' ) ); }
	update_user_meta( $uid, 'gs_birthdate', $d );
	wp_send_json_success( array( 'message' => 'Data di nascita salvata.' ) );
}

// -----------------------------------------------------------------------------
// Shortcode [gs_compleanni] — la pagina "I Compleanni di Oggi"
// -----------------------------------------------------------------------------
add_shortcode( 'gs_compleanni', 'gs_sc_compleanni' );
function gs_sc_compleanni() {
	if ( function_exists( 'gs_blackout_gate' ) && ( $g = gs_blackout_gate() ) ) { return $g; }

	$fest = gs_bday_festeggiate();
	$anno = (int) date( 'Y', current_time( 'timestamp' ) );

	$out  = gs_box_open( '🎂 I Compleanni di Oggi' );
	$out .= gs_sezione_aiuto( 'Pagina che si aggiorna da sola ogni giorno. Se qualcuna festeggia, lo scopri anche senza aprire questa pagina: un annuncio "🎂 Oggi festeggia..." attraversa lo schermo di tutte le sfogline, e cliccandoci sopra si arriva dritti qui. Più sotto trovi la vetrina e la bacheca degli auguri, dove puoi lasciarle un messaggio. Se non hai ancora impostato la tua data di nascita, in fondo trovi il modulo per farlo.' );

	if ( empty( $fest['sfogline'] ) ) {
		$out .= '<p>Non ci sono ancora compleanni da mostrare. Quando una sfoglina compirà gli anni, qui comparirà la sua vetrina con lo spazio per gli auguri.</p>';
	} elseif ( $fest['oggi'] ) {
		$out .= '<p class="gs-bday-intro">Oggi è un giorno speciale! Facciamo tanti auguri a chi festeggia. 🎉</p>';
	} else {
		$q = isset( $fest['quando'] ) ? date_i18n( 'j F', $fest['quando'] ) : '';
		$out .= '<p class="gs-bday-intro">L\'ultimo compleanno festeggiato' . ( $q ? ' (' . esc_html( $q ) . ')' : '' ) . '. La vetrina resta qui fino al prossimo compleanno.</p>';
	}
	$out .= gs_box_close();

	// Setter data di nascita per chi ha effettuato l'accesso e non l'ha ancora impostata.
	$me = get_current_user_id();
	if ( $me && gs_is_approved( $me ) && ! gs_bday_get( $me ) ) {
		$out .= gs_box_open( '🎈 La tua data di nascita' );
		$out .= '<p class="gs-hint">Impostala per comparire in questa pagina il giorno del tuo compleanno.</p>';
		$out .= '<form class="gs-form gs-form-bday" onsubmit="return false">';
		$out .= '<p><input type="date" name="nascita"> <button class="gs-btn gs-btn-sm gs-btn-verde gs-bday-salva">Salva</button> <span class="gs-bday-msg gs-richiesta-esito"></span></p>';
		$out .= '</form>' . gs_box_close();
	}

	// Vetrina + auguri per ogni festeggiata.
	foreach ( $fest['sfogline'] as $u ) {
		$out .= '<div class="gs-bday-card">';
		$out .= gs_box_open( '🎉 Tanti auguri, ' . esc_html( $u->display_name ) . '!' );

		if ( gs_bday_hidden( $u->ID ) ) {
			$out .= '<p class="gs-hint">La vetrina di questo compleanno è momentaneamente oscurata.</p>';
		} elseif ( function_exists( 'gs_render_profile' ) ) {
			$level = function_exists( 'gs_get_level' ) ? gs_get_level( $u->ID ) : array( 'simbolo' => '', 'titolo' => '', 'punti' => 0 );
			$out  .= gs_render_profile( $u->ID, true );
			$out  .= '<div class="gs-todo-riquadro"><ul class="gs-vetrina-stats">';
			$out  .= '<li>' . $level['simbolo'] . ' Livello: <strong>' . esc_html( $level['titolo'] ) . '</strong></li>';
			$out  .= '<li>💯 Punti: <strong>' . (int) $level['punti'] . '</strong></li>';
			if ( function_exists( 'gs_vetrina_url' ) ) {
				$out .= '<li>🔗 <a href="' . esc_url( gs_vetrina_url( $u->ID ) ) . '">Vai alla vetrina completa</a></li>';
			}
			$out  .= '</ul></div>';

			// Biografia approvata (parte della vetrina).
			if ( function_exists( 'gs_bio_pubblica_html' ) ) {
				$out .= gs_bio_pubblica_html( $u->ID );
			}
		}
		$out .= gs_box_close();

		// Bacheca degli auguri.
		$out .= gs_box_open( '💌 Gli auguri per ' . esc_html( $u->display_name ) );
		$out .= '<div class="gs-todo-riquadro">';
		$auguri = gs_auguri_lista( $u->ID, $anno );
		if ( $auguri ) {
			$out .= '<div class="gs-auguri-lista">';
			foreach ( $auguri as $a ) {
				$au = get_userdata( $a->post_author );
				$out .= '<div class="gs-augurio">';
				$out .= '<span class="gs-augurio-da">' . esc_html( $au ? $au->display_name : 'Una sfoglina' ) . '</span> ';
				$out .= '<span class="gs-msg-data">' . esc_html( get_the_time( 'j/m/Y H:i', $a ) ) . '</span>';
				$out .= '<div>' . nl2br( esc_html( gs_msg_clean( $a->post_content ) ) ) . '</div>';
				$amedia = get_post_meta( $a->ID, 'gs_media', true );
				if ( $amedia && function_exists( 'gs_msg_media_html' ) ) {
					$out .= gs_msg_media_html( $amedia, get_post_meta( $a->ID, 'gs_media_type', true ) ?: 'image' );
				}
				$out .= '</div>';
			}
			$out .= '</div>';
		} else {
			$out .= '<p class="gs-hint">Ancora nessun augurio: sii la prima a scrivere! 🎂</p>';
		}

		if ( $me && gs_is_approved( $me ) ) {
			$out .= '<form class="gs-form gs-form-augurio" data-target="' . (int) $u->ID . '" onsubmit="return false">';
			ob_start(); gs_antispam_fields(); $out .= ob_get_clean();
			$out .= '<textarea name="testo" rows="2" style="width:100%" placeholder="Scrivi i tuoi auguri…"></textarea>';
			$out .= '<p>' . gs_msg_file_field() . '</p>';
			$out .= '<p><button class="gs-btn gs-btn-sm gs-augurio-invia">Invia gli auguri</button> <span class="gs-augurio-msg gs-richiesta-esito"></span></p>';
			$out .= '</form>';
		} elseif ( ! $me ) {
			$out .= '<p class="gs-hint">Accedi con il tuo account per lasciare un messaggio di auguri.</p>';
		}
		$out .= '</div>';
		$out .= gs_box_close();
		$out .= '</div>';
	}

	return $out;
}

// -----------------------------------------------------------------------------
// PANNELLO GESTORE — controllo compleanni (oscura/mostra + date di nascita)
// -----------------------------------------------------------------------------
function gs_pannello_compleanni() {
	if ( ! ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) ) { return; }
	$oggi  = current_time( 'timestamp' );
	$tutte = gs_bday_tutte();

	echo gs_box_open( 'Compleanni — controllo vetrina' );
	?>
	<p class="gs-hint">Il giorno del compleanno la vetrina della sfoglina compare da sola in «I Compleanni di Oggi» e resta fino al compleanno successivo. Qui puoi oscurarla o mostrarla, e correggere le date di nascita.</p>

	<?php
	// Festeggiate del momento con toggle oscura/mostra.
	$fest = gs_bday_festeggiate();
	if ( ! empty( $fest['sfogline'] ) ) : ?>
		<strong><?php echo $fest['oggi'] ? 'Festeggiano oggi' : 'Vetrina attualmente in mostra'; ?></strong>
		<table class="gs-table gs-paginate" data-per-page="10"><tbody>
		<?php foreach ( $fest['sfogline'] as $u ) :
			$hidden = gs_bday_hidden( $u->ID ); ?>
			<tr data-uid="<?php echo (int) $u->ID; ?>">
				<td><strong><?php echo esc_html( $u->display_name ); ?></strong> <span class="gs-hint"><?php echo esc_html( date_i18n( 'j F', strtotime( gs_bday_get( $u->ID ) ) ) ); ?></span></td>
				<td>
					<button class="gs-btn gs-btn-sm gs-bday-toggle" data-uid="<?php echo (int) $u->ID; ?>" data-hide="<?php echo $hidden ? '0' : '1'; ?>">
						<?php echo $hidden ? 'Mostra vetrina' : 'Oscura vetrina'; ?>
					</button>
					<span class="gs-hint gs-bday-stato"><?php echo $hidden ? 'oscurata' : 'visibile'; ?></span>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody></table>
	<?php else : ?>
		<p class="gs-hint">Al momento nessuna vetrina di compleanno da mostrare.</p>
	<?php endif; ?>

	<hr>
	<strong>Date di nascita delle sfogline</strong>
	<p class="gs-hint">Imposta o correggi la data di nascita di una sfoglina.</p>
	<form class="gs-form gs-form-bday-admin" onsubmit="return false" style="background:var(--gs-uovo);padding:10px;border-radius:6px">
		<p><label>Sfoglina<br>
			<select name="uid" style="min-width:220px">
				<?php foreach ( get_users( array( 'orderby' => 'display_name' ) ) as $u ) {
					if ( ! gs_e_sfoglina_vera( $u, false ) ) { continue; }
					echo '<option value="' . (int) $u->ID . '">' . esc_html( $u->display_name ) . ( gs_bday_get( $u->ID ) ? ' — ' . esc_html( gs_bday_get( $u->ID ) ) : ' — (mancante)' ) . '</option>';
				} ?>
			</select></label></p>
		<p><input type="date" name="nascita"> <button class="gs-btn gs-btn-sm gs-btn-verde gs-bday-admin-salva">Salva</button> <span class="gs-bday-admin-msg gs-richiesta-esito"></span></p>
	</form>
	<?php
	echo gs_box_close();
}

add_action( 'wp_ajax_gs_bday_toggle', 'gs_ajax_bday_toggle' );
function gs_ajax_bday_toggle() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$uid  = isset( $_POST['uid'] ) ? (int) $_POST['uid'] : 0;
	$hide = ! empty( $_POST['hide'] );
	if ( ! $uid || ! get_userdata( $uid ) ) { wp_send_json_error( array( 'message' => 'Sfoglina non valida.' ) ); }
	if ( $hide ) { update_user_meta( $uid, 'gs_bday_hidden', '1' ); }
	else { delete_user_meta( $uid, 'gs_bday_hidden' ); }
	wp_send_json_success( array( 'message' => $hide ? 'Vetrina oscurata.' : 'Vetrina di nuovo visibile.' ) );
}

add_action( 'wp_ajax_gs_bday_admin_salva', 'gs_ajax_bday_admin_salva' );
function gs_ajax_bday_admin_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$uid = isset( $_POST['uid'] ) ? (int) $_POST['uid'] : 0;
	$d   = isset( $_POST['nascita'] ) ? sanitize_text_field( wp_unslash( $_POST['nascita'] ) ) : '';
	if ( ! $uid || ! get_userdata( $uid ) ) { wp_send_json_error( array( 'message' => 'Sfoglina non valida.' ) ); }
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) ) { wp_send_json_error( array( 'message' => 'Data non valida.' ) ); }
	update_user_meta( $uid, 'gs_birthdate', $d );
	wp_send_json_success( array( 'message' => 'Data di nascita salvata.' ) );
}
