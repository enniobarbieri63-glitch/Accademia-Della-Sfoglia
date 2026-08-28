<?php
/**
 * onboarding.php — Primo giro per la sfoglina appena approvata.
 *
 * Mostra una volta, in cima a "La Mia Sfoglia", un riquadro con i primi passi
 * per guadagnare punti. La sfoglina lo chiude quando vuole (resta chiuso): si
 * salva un flag nel suo profilo. Non tocca nessun'altra logica.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** True se la sfoglina ha già chiuso (o completato) il primo giro. */
function gs_onboarding_fatto( $user_id ) {
	return '1' === get_user_meta( (int) $user_id, 'gs_onboarding_done', true );
}

/**
 * HTML del riquadro di benvenuto, o stringa vuota se già chiuso.
 * Da richiamare nella dashboard.
 */
function gs_onboarding_box( $user_id ) {
	$user_id = (int) $user_id;
	if ( ! $user_id || gs_onboarding_fatto( $user_id ) ) {
		return '';
	}

	$sfida  = (int) get_option( 'gs_page_sfida' );
	$diario = (int) get_option( 'gs_page_diario' );
	$link_sfida  = $sfida ? get_permalink( $sfida ) : '';
	$link_diario = $diario ? get_permalink( $diario ) : '';

	$utente = get_userdata( $user_id );
	$nome   = $utente ? $utente->display_name : '';

	$passi = array(
		$link_sfida
			? '<a href="' . esc_url( $link_sfida ) . '">Partecipa alla Sfida della Settimana</a>: invia la tua sfoglia e guadagni i primi punti.'
			: 'Partecipa alla Sfida della Settimana: invia la tua sfoglia e guadagni i primi punti.',
		'Vota le sfoglie delle altre: ogni voto dato ti fa guadagnare punti.',
		$link_diario
			? '<a href="' . esc_url( $link_diario ) . '">Scrivi nel Diario dell\'Impasto</a>: racconta una prova e prendi altri punti.'
			: 'Scrivi nel Diario dell\'Impasto: racconta una prova e prendi altri punti.',
	);

	$html  = '<div class="gs-box gs-onboarding">';
	$html .= '<button class="gs-onboarding-chiudi" aria-label="Chiudi" title="Chiudi">✕</button>';
	$html .= '<h3 class="gs-box-title">Ciao' . ( $nome ? ', ' . esc_html( $nome ) : '' ) . '! I tuoi primi passi</h3>';
	$html .= '<div class="gs-todo-riquadro">';
	$html .= '<p>Qui il percorso è fatto di compiti veri, seguiti dai docenti. Ecco come cominciare a guadagnare punti e salire di livello:</p>';
	$html .= '<ol>';
	foreach ( $passi as $p ) {
		$html .= '<li>' . $p . '</li>';
	}
	$html .= '</ol>';
	$html .= '<p><button class="gs-btn gs-btn-sm gs-onboarding-ok">Ho capito, comincio</button></p>';
	$html .= '</div>';
	$html .= '</div>';

	return $html;
}

// -----------------------------------------------------------------------------
// AJAX: la sfoglina chiude il primo giro (non riappare più)
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_onboarding_chiudi', 'gs_ajax_onboarding_chiudi' );
function gs_ajax_onboarding_chiudi() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		wp_send_json_error( array( 'message' => 'Devi accedere.' ) );
	}
	update_user_meta( $user_id, 'gs_onboarding_done', '1' );
	wp_send_json_success();
}
