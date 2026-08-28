<?php
/**
 * percorso.php — Messaggio di benvenuto "Benvenuto Nell'Accademia Della Sfoglia,
 * un percorso di compiti documentato". Titolo + testo modificabili dal pannello,
 * attivabile/disattivabile e mostrabile solo alle sfogline scelte.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gs_percorso_default_titolo() {
	return "Benvenuta nell'**Accademia della Sfoglia**: un percorso di compiti documentato.";
}
function gs_percorso_default_testo() {
	return "Quando una sfoglina entra nell'Accademia della Sfoglia **non entra in un semplice gioco**: entra in un percorso di compiti documentato. È un cammino di crescita, ordinato e progressivo, in cui ogni tappa è un compito svolto in rapporto diretto con dei docenti veri — Rina Poletti, Bruno Cingolani e l'Accademia — e in cui ogni compito **lascia una traccia registrata**.\n\n**A cosa serve:** a trasformare la passione per la sfoglia in un cammino con un senso e una direzione, con la guida di docenti competenti, la motivazione dei traguardi e una memoria del proprio progresso che resta scritta.\n\n**Perché \"documentato\":** ogni compito produce una prova — il lavoro consegnato, la valutazione del docente, la data, il punteggio — e messi in fila diventano un curriculum documentato della sfoglina.\n\n**In una frase:** non un gioco, ma un cammino guidato da docenti, in cui ogni tappa è un compito svolto, valutato e conservato, così che la crescita di ciascuna sfoglina diventi visibile, riconosciuta e duratura.";
}

function gs_percorso_settings() {
	$s = gs_settings();
	$d = array(
		'attivo'      => 1,
		'titolo'      => gs_percorso_default_titolo(),
		'testo'       => gs_percorso_default_testo(),
		'destinatari' => array(), // vuoto = tutte le sfogline
	);
	$c = isset( $s['percorso'] ) && is_array( $s['percorso'] ) ? $s['percorso'] : array();
	return wp_parse_args( $c, $d );
}

/** Mostra il messaggio all'utente corrente? (attivo + destinatario) */
function gs_percorso_visibile_per( $uid = 0 ) {
	$cfg = gs_percorso_settings();
	if ( empty( $cfg['attivo'] ) ) { return false; }
	$dest = is_array( $cfg['destinatari'] ) ? array_map( 'intval', $cfg['destinatari'] ) : array();
	if ( empty( $dest ) ) { return true; } // tutte
	$uid = $uid ? $uid : get_current_user_id();
	return in_array( (int) $uid, $dest, true );
}

/** Blocco richiudibile (details/summary): titolo cliccabile + testo. */
function gs_percorso_html() {
	if ( ! gs_percorso_visibile_per() ) { return ''; }
	$cfg   = gs_percorso_settings();
	$testo = $cfg['testo'];
	$paras = preg_split( "/\n{2,}/", trim( $testo ) );
	$body  = '';
	foreach ( $paras as $p ) {
		$body .= '<p>' . gs_grassetto_html( $p ) . '</p>';
	}
	$h  = '<details class="gs-percorso">';
	$h .= '<summary class="gs-percorso-titolo">' . gs_grassetto_html( $cfg['titolo'], false ) . ' <span class="gs-percorso-hint">— clicca per leggere</span></summary>';
	$h .= '<div class="gs-percorso-testo">' . $body . '</div>';
	$h .= '</details>';
	return $h;
}

add_shortcode( 'gs_percorso', 'gs_percorso_html' );

// -----------------------------------------------------------------------------
// PANNELLO — modifica del messaggio di benvenuto
// -----------------------------------------------------------------------------
function gs_pannello_percorso() {
	if ( ! gs_can_manage() ) { return; }
	$cfg = gs_percorso_settings();
	echo gs_box_open( 'Messaggio di benvenuto in «La Mia Sfoglia»' );
	echo '<p class="gs-hint">È il testo che le sfogline trovano in cima alla loro pagina. Qui puoi modificarlo, disattivarlo del tutto, oppure mostrarlo solo ad alcune sfogline scelte (lascia vuoto per mostrarlo a tutte). Per il grassetto, racchiudi la parola tra due asterischi: **così**.</p>';
	$sfogline = function_exists( 'gs_sez_sfogline' ) ? gs_sez_sfogline() : array();
	$dest = is_array( $cfg['destinatari'] ) ? array_map( 'intval', $cfg['destinatari'] ) : array();
	?>
	<form class="gs-form gs-form-percorso" onsubmit="return false">
		<p><label><input type="checkbox" name="attivo" <?php checked( ! empty( $cfg['attivo'] ) ); ?>> Mostra il messaggio di benvenuto</label></p>
		<p><label>Titolo (cliccabile)<br><input type="text" name="titolo" autocomplete="off" value="<?php echo esc_attr( $cfg['titolo'] ); ?>" style="width:100%"></label></p>
		<p><label>Testo<br><textarea name="testo" rows="8" style="width:100%"><?php echo esc_textarea( $cfg['testo'] ); ?></textarea></label></p>
		<p><label>Mostra solo a queste sfogline (vuoto = tutte)<br>
			<select name="destinatari" multiple size="4" style="min-width:220px">
				<?php foreach ( $sfogline as $u ) : ?>
					<option value="<?php echo (int) $u->ID; ?>" <?php echo in_array( (int) $u->ID, $dest, true ) ? 'selected' : ''; ?>><?php echo esc_html( $u->display_name ); ?></option>
				<?php endforeach; ?>
			</select></label></p>
		<p><button class="gs-btn gs-btn-sm gs-percorso-salva">Salva messaggio</button> <span class="gs-percorso-msg gs-richiesta-esito"></span></p>
	</form>
	<?php
	echo gs_box_close();
}

add_action( 'wp_ajax_gs_percorso_salva', 'gs_ajax_percorso_salva' );
function gs_ajax_percorso_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$dest = isset( $_POST['destinatari'] ) && is_array( $_POST['destinatari'] ) ? array_map( 'intval', $_POST['destinatari'] ) : array();
	$s = gs_settings();
	$s['percorso'] = array(
		'attivo'      => ! empty( $_POST['attivo'] ) ? 1 : 0,
		'titolo'      => sanitize_text_field( wp_unslash( $_POST['titolo'] ?? '' ) ),
		'testo'       => sanitize_textarea_field( wp_unslash( $_POST['testo'] ?? '' ) ),
		'destinatari' => $dest,
	);
	update_option( GS_OPTION, $s );
	wp_send_json_success( array( 'message' => 'Messaggio di benvenuto salvato.' ) );
}
