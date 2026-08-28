<?php
/**
 * testamento-sfoglina.php — Il Testamento della Sfoglina.
 *
 * Chi raggiunge il livello massimo (l'ultima delle "Insegne della Sfoglia")
 * viene invitata a lasciare un testo permanente: qualche riga di consiglio o
 * eredità per chi arriva dopo. Compare nella propria Vetrina pubblica ed
 * entra nell'elenco "I Testamenti delle Maestre", proposto a chi si iscrive.
 *
 * Meta utente:
 *  - gs_testamento           (testo, vuoto = non ancora scritto)
 *  - gs_testamento_proponi   ('1' = ha raggiunto il livello massimo e non ha
 *                              ancora scritto il testamento: le viene proposto)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** True se l'indice di livello indicato è l'ultimo (il livello massimo configurato). */
function gs_e_livello_massimo( $level_index ) {
	$levels = gs_settings()['levels'];
	return ! empty( $levels ) && (int) $level_index === count( $levels ) - 1;
}

add_action( 'gs_level_up', 'gs_testamento_controlla_livello_massimo', 10, 2 );
function gs_testamento_controlla_livello_massimo( $user_id, $new_level ) {
	if ( ! gs_e_livello_massimo( $new_level ) ) { return; }
	$testamento = get_user_meta( $user_id, 'gs_testamento', true );
	if ( '' !== trim( (string) $testamento ) ) { return; } // ha già scritto il suo testamento
	update_user_meta( $user_id, 'gs_testamento_proponi', 1 );
}

/**
 * Blocco d'invito a scrivere il testamento (se proposto e non ancora
 * scritto), oppure — una volta scritto — lo stesso riquadro resta sulla
 * dashboard per poterlo rileggere e correggere quando vuole: non è un
 * invito una tantum che sparisce dopo il primo salvataggio.
 */
function gs_testamento_prompt_html( $uid ) {
	$testo   = (string) get_user_meta( $uid, 'gs_testamento', true );
	$proposto = '1' === (string) get_user_meta( $uid, 'gs_testamento_proponi', true );
	if ( ! $proposto && '' === trim( $testo ) ) { return ''; }

	$out  = gs_box_open( '📜 Il Tuo Testamento' );
	$out .= '<p class="gs-hint">Hai raggiunto il livello più alto dell\'Accademia. Lascia qualche riga per chi arriva dopo di te: un consiglio, un ricordo, un\'eredità. Comparirà nella tua Vetrina pubblica e tra "I Testamenti delle Maestre", proposto alle nuove iscritte. Puoi correggerlo quando vuoi, da qui.</p>';
	$out .= '<div class="gs-todo-riquadro">';
	$out .= '<form class="gs-form gs-form-testamento" onsubmit="return false">';
	$out .= '<p><label>Il tuo testamento<br><textarea name="testo" rows="5" style="width:100%" placeholder="Scrivi qui…">' . esc_textarea( $testo ) . '</textarea></label></p>';
	$out .= '<p><button class="gs-btn gs-btn-sm gs-testamento-salva">' . ( '' !== trim( $testo ) ? 'Aggiorna il tuo testamento' : 'Lascia il tuo testamento' ) . '</button> <span class="gs-testamento-msg gs-richiesta-esito"></span></p>';
	$out .= '</form>';
	$out .= '</div>';
	$out .= gs_box_close();
	return $out;
}

/** Blocco di sola lettura per la Vetrina pubblica, se questa sfoglina ha lasciato un testamento. */
function gs_testamento_vetrina_html( $uid ) {
	$testo = get_user_meta( $uid, 'gs_testamento', true );
	if ( '' === trim( (string) $testo ) ) { return ''; }

	$out  = gs_box_open( '📜 Il Testamento' );
	$out .= '<p class="gs-hint" style="font-style:italic">' . nl2br( esc_html( $testo ) ) . '</p>';
	$out .= gs_box_close();
	return $out;
}

add_action( 'wp_ajax_gs_testamento_salva', 'gs_ajax_testamento_salva' );
function gs_ajax_testamento_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }

	$testo = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';
	if ( function_exists( 'gs_msg_clean' ) ) { $testo = gs_msg_clean( $testo ); }
	if ( '' === trim( $testo ) ) { wp_send_json_error( array( 'message' => 'Scrivi qualcosa prima di salvare.' ) ); }

	update_user_meta( $uid, 'gs_testamento', $testo );
	delete_user_meta( $uid, 'gs_testamento_proponi' );

	wp_send_json_success( array( 'message' => 'Testamento salvato: ora è nella tua Vetrina e tra i Testamenti delle Maestre.' ) );
}

// -----------------------------------------------------------------------------
// [gs_testamenti] — elenco pubblico "I Testamenti delle Maestre"
// -----------------------------------------------------------------------------
add_shortcode( 'gs_testamenti', 'gs_sc_testamenti' );
function gs_sc_testamenti() {
	if ( $g = gs_gate_riservato() ) { return $g; }
	if ( $g = gs_blackout_gate() ) { return $g; }

	$out = gs_box_open( '📜 I Testamenti delle Maestre' );
	$out .= gs_sezione_aiuto( 'Chi raggiunge il livello più alto dell\'Accademia può lasciare un testamento: un consiglio o un\'eredità per chi arriva dopo. Qui trovi quelli già scritti, proposti anche alle nuove iscritte.' );

	$autrici = array();
	foreach ( get_users( array( 'fields' => 'ID' ) ) as $uid ) {
		if ( ! gs_e_sfoglina_vera( (int) $uid ) ) { continue; }
		$testo = get_user_meta( $uid, 'gs_testamento', true );
		if ( '' !== trim( (string) $testo ) ) {
			$autrici[] = array( 'id' => $uid, 'testo' => $testo );
		}
	}

	if ( ! $autrici ) {
		$out .= '<p>Nessun testamento ancora scritto.</p>' . gs_box_close();
		return $out;
	}

	$out .= '<div class="gs-inbox-lista gs-paginate" data-per-page="8">';
	foreach ( $autrici as $a ) {
		$u = get_userdata( $a['id'] );
		$out .= '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">' . esc_html( $u ? $u->display_name : '—' ) . '</summary>';
		$out .= '<div class="gs-inbox-corpo"><p style="font-style:italic">' . nl2br( esc_html( $a['testo'] ) ) . '</p></div></details>';
	}
	$out .= '</div>';
	$out .= gs_box_close();
	return $out;
}
