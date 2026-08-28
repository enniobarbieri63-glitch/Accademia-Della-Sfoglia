<?php
/**
 * percorso-staffetta.php — Percorso a Staffetta.
 *
 * Estende i Percorsi Guidati (percorsi-lezioni.php): un percorso individuale
 * (non di squadra) può avere la staffetta attiva (meta gs_staffetta_attiva,
 * impostata dal pannello). Il "testimone" parte libero: la prima sfoglina
 * che completa il percorso lo raccoglie e sceglie personalmente a quale
 * altra sfoglina passarlo. Da quel momento solo chi ha in mano il testimone
 * (e ha completato il percorso) può passarlo oltre.
 *
 * Meta su gs_percorso_lezioni:
 *  - gs_staffetta_attiva  (bool, impostata dal gestore)
 *  - gs_staffetta_turno   (ID utente che ha in mano il testimone ora, 0 = libero)
 *  - gs_staffetta_storico (array di { da, a, data })
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gs_percorso_staffetta_attiva( $pid ) {
	return '1' === (string) get_post_meta( $pid, 'gs_staffetta_attiva', true );
}

function gs_percorso_staffetta_turno( $pid ) {
	return (int) get_post_meta( $pid, 'gs_staffetta_turno', true );
}

function gs_percorso_staffetta_storico( $pid ) {
	$s = get_post_meta( $pid, 'gs_staffetta_storico', true );
	return is_array( $s ) ? $s : array();
}

/** True se $uid può passare ora il testimone di questo percorso (staffetta attiva, ha completato, e il testimone è libero o già suo). */
function gs_percorso_staffetta_puo_passare( $pid, $uid ) {
	if ( ! gs_percorso_staffetta_attiva( $pid ) ) { return false; }
	if ( ! function_exists( 'gs_percorso_completato_da' ) || ! gs_percorso_completato_da( $pid, $uid ) ) { return false; }
	$turno = gs_percorso_staffetta_turno( $pid );
	return 0 === $turno || $turno === (int) $uid;
}

/** Passa il testimone da $uid_da a $uid_a. Restituisce array( 'ok' => bool, 'message' => string ). */
function gs_percorso_staffetta_passa( $pid, $uid_da, $uid_a ) {
	if ( ! gs_percorso_staffetta_puo_passare( $pid, $uid_da ) ) {
		return array( 'ok' => false, 'message' => 'Non hai il testimone di questo percorso.' );
	}
	$uid_a = (int) $uid_a;
	if ( ! $uid_a || $uid_a === (int) $uid_da ) {
		return array( 'ok' => false, 'message' => 'Scegli un\'altra sfoglina a cui passarlo.' );
	}
	if ( ! function_exists( 'gs_is_approved' ) || ! gs_is_approved( $uid_a ) ) {
		return array( 'ok' => false, 'message' => 'Questa sfoglina non è (ancora) approvata.' );
	}

	update_post_meta( $pid, 'gs_staffetta_turno', $uid_a );
	$storico   = gs_percorso_staffetta_storico( $pid );
	$storico[] = array( 'da' => (int) $uid_da, 'a' => $uid_a, 'data' => current_time( 'mysql' ) );
	update_post_meta( $pid, 'gs_staffetta_storico', $storico );

	if ( function_exists( 'gs_mail_progetto' ) ) {
		$da_u = get_userdata( $uid_da );
		gs_mail_progetto( $uid_a, 'messaggi', 'Ti hanno passato il testimone! — Accademia della Sfoglia',
			( $da_u ? $da_u->display_name : 'Una sfoglina' ) . " ti ha passato il testimone del percorso \"" . get_the_title( $pid ) . "\". Tocca a te continuare la staffetta: quando completerai il percorso, potrai passarlo a un'altra sfoglina." );
	}

	return array( 'ok' => true, 'message' => 'Testimone passato!' );
}

/** Blocchetto HTML da inserire sotto un percorso individuale, se la staffetta è attiva. */
function gs_staffetta_html( $pid, $uid, $completo ) {
	if ( ! gs_percorso_staffetta_attiva( $pid ) ) { return ''; }

	$turno = gs_percorso_staffetta_turno( $pid );
	$out   = '<p class="gs-hint">🏃 <strong>Percorso a Staffetta:</strong> ';
	if ( $turno ) {
		$tu  = get_userdata( $turno );
		$out .= 'il testimone è ora in mano a <strong>' . esc_html( $tu ? $tu->display_name : '—' ) . '</strong>.';
	} else {
		$out .= 'il testimone è ancora libero: chi completa per prima questo percorso lo raccoglie e sceglie a chi passarlo.';
	}
	$out .= '</p>';

	if ( gs_percorso_staffetta_puo_passare( $pid, $uid ) ) {
		$out .= '<form class="gs-form gs-form-staffetta-passa" data-percorso="' . (int) $pid . '" onsubmit="return false">';
		$out .= '<p><label>Passa il testimone a<br><select name="a">';
		foreach ( get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) ) as $u ) {
			if ( (int) $u->ID === (int) $uid ) { continue; }
			if ( ! function_exists( 'gs_e_sfoglina_vera' ) || ! gs_e_sfoglina_vera( $u ) ) { continue; }
			$out .= '<option value="' . (int) $u->ID . '">' . esc_html( $u->display_name ) . '</option>';
		}
		$out .= '</select></label> <button class="gs-btn gs-btn-sm gs-staffetta-passa">Passa il testimone</button> <span class="gs-staffetta-msg gs-richiesta-esito"></span></p>';
		$out .= '</form>';
	}
	return $out;
}

add_action( 'wp_ajax_gs_staffetta_passa', 'gs_ajax_staffetta_passa' );
function gs_ajax_staffetta_passa() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }

	$pid = isset( $_POST['percorso'] ) ? (int) $_POST['percorso'] : 0;
	$a   = isset( $_POST['a'] ) ? (int) $_POST['a'] : 0;
	if ( 'gs_percorso_lezioni' !== get_post_type( $pid ) ) { wp_send_json_error( array( 'message' => 'Percorso non valido.' ) ); }

	$r = gs_percorso_staffetta_passa( $pid, $uid, $a );
	if ( $r['ok'] ) { wp_send_json_success( array( 'message' => $r['message'] ) ); }
	wp_send_json_error( array( 'message' => $r['message'] ) );
}
