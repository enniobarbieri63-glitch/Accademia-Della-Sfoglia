<?php
/**
 * sfida-silenzio.php — La Sfida del Silenzio.
 *
 * Un periodo (impostato a mano dal titolare, non automatico) in cui la
 * Classifica Generale resta congelata a come era all'inizio del periodo:
 * tutto il resto del sito continua a funzionare normalmente (missioni,
 * punti, badge), solo la classifica non si muove, per spostare l'attenzione
 * dal punteggio al lavoro fatto. Alla fine del periodo la classifica torna
 * di nuovo viva, con tutti i punti accumulati nel frattempo.
 *
 * Impostazioni in gs_settings()['silenzio']:
 *  - 'da' / 'a'   (date 'Y-m-d', impostate a mano dal titolare)
 *  - 'periodo'    (firma "da|a" del periodo per cui esiste già uno scatto)
 *  - 'snapshot'   (array di { id, nome, punti }, scattato all'inizio del periodo)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gs_silenzio_impostazioni() {
	$s = gs_settings()['silenzio'] ?? array();
	return wp_parse_args( is_array( $s ) ? $s : array(), array( 'da' => '', 'a' => '', 'periodo' => '', 'snapshot' => array() ) );
}

/** True se oggi cade dentro il periodo di silenzio impostato (entrambe le date devono essere impostate). */
function gs_silenzio_attivo() {
	$c = gs_silenzio_impostazioni();
	if ( ! $c['da'] || ! $c['a'] ) { return false; }
	$oggi = date_i18n( 'Y-m-d', current_time( 'timestamp' ) );
	return $oggi >= $c['da'] && $oggi <= $c['a'];
}

function gs_silenzio_periodo_id( $c = null ) {
	$c = $c ?: gs_silenzio_impostazioni();
	return $c['da'] . '|' . $c['a'];
}

/** Se il periodo attivo non ha ancora uno scatto, lo cattura ora (classifica live del momento). */
function gs_silenzio_snapshot_assicura() {
	if ( ! gs_silenzio_attivo() ) { return; }
	$c = gs_silenzio_impostazioni();
	if ( $c['periodo'] === gs_silenzio_periodo_id( $c ) && ! empty( $c['snapshot'] ) ) { return; }

	$righe = array();
	foreach ( gs_leaderboard( 50 ) as $u ) {
		$righe[] = array( 'id' => (int) $u->ID, 'nome' => $u->display_name, 'punti' => (int) get_user_meta( $u->ID, 'gs_points', true ) );
	}
	$s               = gs_settings();
	$s['silenzio']['da']       = $c['da'];
	$s['silenzio']['a']        = $c['a'];
	$s['silenzio']['periodo']  = gs_silenzio_periodo_id( $c );
	$s['silenzio']['snapshot'] = $righe;
	update_option( GS_OPTION, $s );
	gs_settings_flush_cache();
}

/** Righe della classifica congelata (array di id/nome/punti), o array() se nessuno scatto esiste. */
function gs_silenzio_classifica_snapshot() {
	$c = gs_silenzio_impostazioni();
	return is_array( $c['snapshot'] ) ? $c['snapshot'] : array();
}

// -----------------------------------------------------------------------------
// PANNELLO — impostare il periodo di silenzio (titolare)
// -----------------------------------------------------------------------------
function gs_pannello_silenzio() {
	if ( ! gs_can_manage() ) { return; }

	$c = gs_silenzio_impostazioni();
	echo gs_box_open( 'La Sfida del Silenzio', '', 'gs-box-silenzio' );
	echo '<p class="gs-hint">Imposta un periodo in cui la Classifica Generale resta congelata a come era all\'inizio: tutto il resto (punti, missioni, badge) continua a funzionare normalmente, solo la classifica non si muove agli occhi delle sfogline. Utile per una settimana al mese in cui vuoi spostare l\'attenzione dal punteggio al lavoro fatto. Nessun automatismo: il periodo va impostato (e tolto) a mano, ogni volta.</p>';

	if ( gs_silenzio_attivo() ) {
		echo '<p class="gs-hint"><strong>🤫 Attivo ora</strong>, fino al ' . esc_html( date_i18n( 'j F Y', strtotime( $c['a'] ) ) ) . '.</p>';
		echo '<p><button class="gs-btn gs-btn-sm gs-btn-ghost gs-silenzio-disattiva">Disattiva subito</button> <span class="gs-silenzio-msg gs-richiesta-esito"></span></p>';
	} else {
		echo '<p class="gs-hint">Non attivo al momento.</p>';
	}

	echo '<form class="gs-form gs-form-silenzio" onsubmit="return false">';
	echo '<p style="display:flex;gap:12px;flex-wrap:wrap"><label>Dal<br><input type="date" name="da" value="' . esc_attr( $c['da'] ) . '"></label><label>Al<br><input type="date" name="a" value="' . esc_attr( $c['a'] ) . '"></label></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-silenzio-salva">Imposta periodo</button> <span class="gs-silenzio-salva-msg gs-richiesta-esito"></span></p>';
	echo '</form>';

	echo gs_box_close();
}

add_action( 'wp_ajax_gs_silenzio_salva', 'gs_ajax_silenzio_salva' );
function gs_ajax_silenzio_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$da = isset( $_POST['da'] ) ? sanitize_text_field( wp_unslash( $_POST['da'] ) ) : '';
	$a  = isset( $_POST['a'] ) ? sanitize_text_field( wp_unslash( $_POST['a'] ) ) : '';
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $da ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $a ) ) {
		wp_send_json_error( array( 'message' => 'Scegli entrambe le date.' ) );
	}
	if ( $a < $da ) { wp_send_json_error( array( 'message' => 'La data di fine non può essere prima di quella di inizio.' ) ); }

	$s = gs_settings();
	$s['silenzio']['da']       = $da;
	$s['silenzio']['a']        = $a;
	$s['silenzio']['periodo']  = '';
	$s['silenzio']['snapshot'] = array();
	update_option( GS_OPTION, $s );
	gs_settings_flush_cache();

	wp_send_json_success( array( 'message' => 'Periodo di silenzio impostato dal ' . $da . ' al ' . $a . '.' ) );
}

add_action( 'wp_ajax_gs_silenzio_disattiva', 'gs_ajax_silenzio_disattiva' );
function gs_ajax_silenzio_disattiva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$s = gs_settings();
	$s['silenzio']['da']       = '';
	$s['silenzio']['a']        = '';
	$s['silenzio']['periodo']  = '';
	$s['silenzio']['snapshot'] = array();
	update_option( GS_OPTION, $s );
	gs_settings_flush_cache();

	wp_send_json_success( array( 'message' => 'Sfida del Silenzio disattivata: la classifica torna viva subito.' ) );
}
