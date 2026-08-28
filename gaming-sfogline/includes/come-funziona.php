<?php
/**
 * come-funziona.php — "Come funziona il Percorso": spiegazione di tutti i sistemi
 * di gamification in un unico blocco richiudibile, mostrato in cima a
 * «La Mia Sfoglia». Titolo e testo modificabili dal pannello, come il
 * messaggio di benvenuto (percorso.php), ma sempre visibile a tutte le
 * sfogline approvate (non ha destinatari scelti: è una guida, non un annuncio).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gs_come_funziona_default_titolo() {
	return 'Come funziona il Percorso';
}

function gs_come_funziona_default_testo() {
	return "L'Accademia della Sfoglia è un percorso a punti: fai le cose, guadagni punti, sali di livello. Ecco come si mette insieme tutto quello che trovi nel sito.\n\n"
		. "PUNTI — Si guadagnano facendo: pubblicare una sfoglia alla Sfida della Settimana (20 punti), votare una sfoglia di un'altra sfoglina (5 punti) e ricevere una stella (1 punto per stella), scrivere nel Diario dell'Impasto (15 punti), condividere un consiglio (20 punti), commentare la sfida (5 punti), arrivare 1ª/2ª/3ª in classifica (100/60/30 punti), vedere una lezione video (5 punti), completare un Percorso Guidato (30 punti, 100 se li completi tutti), rispondere bene a un quiz (10 punti), indovinare la Sfoglia del giorno (5 punti), vincere alla Sfoglia Misurata o alla Giuria a Turno (30 punti), votare o proporre in un Sondaggio (5 o 10 punti), adottare un Piatto in Via di Estinzione (20 punti), e altro ancora: ogni sezione del sito ha il suo modo di far guadagnare punti.\n\n"
		. "LIVELLI — Le Insegne della Sfoglia: 🌱 Sfoglina Novella (da 0 punti), 🌾 Sfoglina (da 100), 🥚 Sfoglina Provetta (da 300), 🫒 Maestra della Sfoglia (da 700), 🏅 Sfoglina d'Oro (da 1500), 👑 Custode della Tradizione (da 3000). Il livello sale da solo appena raggiungi la soglia: lo vedi in cima al tuo profilo, con la barra che mostra quanto manca al livello successivo.\n\n"
		. "SFIDA DELLA SETTIMANA — Ogni settimana c'è una sfida attiva: invii la tua sfoglia (titolo, descrizione, foto) e la community vota quelle delle altre con 4 criteri a stelle. Chi vota riceve punti; chi riceve stelle pure. In classifica contano sia i punti dei singoli sia quelli delle Squadre Regionali.\n\n"
		. "STREAK DEL MATTERELLO — Ogni settimana in cui partecipi (voti, invii, scrivi) la tua serie di settimane consecutive cresce. Ogni 4 settimane di streak guadagni uno scudo salva-streak: se una settimana la salti, lo scudo la copre in automatico e la serie non si azzera.\n\n"
		. "MISSIONI GIORNALIERE — Piccoli compiti che si aggiornano da soli mentre usi il sito: votare 3 sfoglie (10 punti), scrivere nel Diario (15), condividere un consiglio (20), commentare la sfida (5). Le trovi in \"Missioni di oggi\", ognuna cliccabile fino al punto esatto dove si compie.\n\n"
		. "BADGE — Si sbloccano da soli al raggiungimento di traguardi particolari (non solo i punti): li trovi sotto il tuo profilo, ognuno con una spiegazione che si apre al clic.\n\n"
		. "SQUADRE REGIONALI — Ogni sfoglina può scegliere una squadra (Team Nord, Team Centro, Team Sud e Isole): i punti dei singoli si sommano anche alla classifica della propria squadra.\n\n"
		. "INGREDIENTE SEGRETO DEL VENERDÌ — Ogni venerdì un ingrediente a sorpresa da usare in una ricetta: partecipare fa guadagnare punti come le altre attività.\n\n"
		. "GUIDA STAGIONALE — Contenuti che si sbloccano seguendo la stagione dell'anno, non tutti disponibili subito.\n\n"
		. "MADRINA & ALLIEVA — Se sei abbinata a una madrina o a un'allieva, trovi in \"La Mia Sfoglia\" un riquadro dedicato con mini-missioni condivise.\n\n"
		. "VETRINA PUBBLICA — Un profilo pubblico (foto, biografia, lavori) che puoi condividere fuori dal sito, se attivo per il tuo account.\n\n"
		. "PREMIO DI FINE ANNO — Le sfogline in cima alla classifica a fine anno vincono un corso di una giornata con Rina Poletti, pranzo incluso.\n\n"
		. "In una frase: partecipa, guadagna punti, sali di livello — tutto quello che fai nel sito lascia il segno.";
}

function gs_come_funziona_settings() {
	$s = gs_settings();
	$d = array(
		'attivo' => 1,
		'titolo' => gs_come_funziona_default_titolo(),
		'testo'  => gs_come_funziona_default_testo(),
	);
	$c = isset( $s['come_funziona'] ) && is_array( $s['come_funziona'] ) ? $s['come_funziona'] : array();
	return wp_parse_args( $c, $d );
}

/**
 * Barra colorata dei livelli (Le Insegne della Sfoglia), letta dalle
 * impostazioni vere (gs_settings()['levels']): resta sempre allineata anche
 * se le soglie vengono cambiate dal pannello "Impostazioni generali".
 */
function gs_come_funziona_schema_livelli_html() {
	$livelli = gs_settings()['levels'];
	$n       = count( $livelli );
	$out     = '<div class="gs-schema-livelli">';
	foreach ( $livelli as $i => $lv ) {
		$pct   = $n > 1 ? $i / ( $n - 1 ) : 0; // 0 (primo) → 1 (ultimo), per la sfumatura di colore.
		$style = 'background:' . gs_schema_colore_gradiente( $pct ) . ';';
		$out  .= '<div class="gs-livello-tappa" style="' . esc_attr( $style ) . '">';
		$out  .= '<span class="gs-livello-tappa-simbolo">' . esc_html( $lv['simbolo'] ) . '</span>';
		$out  .= '<span class="gs-livello-tappa-titolo">' . esc_html( $lv['titolo'] ) . '</span>';
		$out  .= '<span class="gs-livello-tappa-soglia">da ' . (int) $lv['soglia'] . ' punti</span>';
		$out  .= '</div>';
		if ( $i < $n - 1 ) {
			$out .= '<span class="gs-livello-freccia">→</span>';
		}
	}
	$out .= '</div>';
	return $out;
}

/** Colore intermedio tra crema e oro/verde scuro, per la barra dei livelli ($pct da 0 a 1). */
function gs_schema_colore_gradiente( $pct ) {
	$da = array( 0xf5, 0xef, 0xdc ); // crema chiaro
	$a  = array( 0x1f, 0x6e, 0x37 ); // verde scuro del progetto
	$rgb = array();
	foreach ( $da as $k => $v ) {
		$rgb[] = round( $v + ( $a[ $k ] - $v ) * $pct );
	}
	return sprintf( '#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2] );
}

/**
 * Griglia colorata di alcune fonti di punti principali, con i valori veri
 * letti da gs_settings()['points'] (etichetta e icona sono scelte qui, il
 * numero no: se il titolare cambia i punti dal pannello, questa griglia
 * cambia da sola).
 */
function gs_come_funziona_schema_punti_html() {
	$punti = gs_settings()['points'];
	$voci  = array(
		'pubblica_sfoglia' => array( '🏆', 'Pubblica una sfoglia' ),
		'voto_dato'        => array( '🗳️', 'Vota una sfoglia' ),
		'stella_ricevuta'  => array( '⭐', 'Stella ricevuta' ),
		'voce_diario'      => array( '📔', 'Voce nel Diario' ),
		'consiglio'        => array( '💡', 'Condividi un consiglio' ),
		'commento_sfida'   => array( '💬', 'Commenta la sfida' ),
		'streak_settimana' => array( '🔥', 'Settimana di streak' ),
		'lezione_vista'    => array( '🎬', 'Guarda una lezione' ),
		'risposta_esatta'  => array( '❓', 'Risposta esatta al quiz' ),
		'primo_posto'      => array( '🥇', '1º posto in una sfida' ),
	);
	$out = '<div class="gs-schema-punti">';
	foreach ( $voci as $chiave => $v ) {
		if ( ! isset( $punti[ $chiave ] ) ) { continue; }
		$out .= '<div class="gs-punto-chip"><span class="gs-punto-chip-icona">' . $v[0] . '</span>'
			. '<span class="gs-punto-chip-label">' . esc_html( $v[1] ) . '</span>'
			. '<span class="gs-punto-chip-valore">+' . (int) $punti[ $chiave ] . '</span></div>';
	}
	$out .= '</div>';
	return $out;
}

/** Blocco richiudibile (details/summary): titolo cliccabile + schemi colorati + testo, sempre per tutte. */
function gs_come_funziona_html() {
	$cfg = gs_come_funziona_settings();
	if ( empty( $cfg['attivo'] ) ) {
		return '';
	}
	$paras = preg_split( "/\n{2,}/", trim( $cfg['testo'] ) );
	$body  = '';
	foreach ( $paras as $p ) {
		$body .= '<p>' . gs_grassetto_html( $p ) . '</p>';
	}
	$h  = '<details class="gs-percorso gs-come-funziona">';
	$h .= '<summary class="gs-percorso-titolo">🗺️ ' . gs_grassetto_html( $cfg['titolo'], false ) . ' <span class="gs-percorso-hint">— clicca per leggere</span></summary>';
	$h .= '<div class="gs-percorso-testo">';
	$h .= '<p class="gs-schema-sottotitolo">I sei livelli — Le Insegne della Sfoglia</p>';
	$h .= gs_come_funziona_schema_livelli_html();
	$h .= '<p class="gs-schema-sottotitolo">Alcuni modi per guadagnare punti</p>';
	$h .= gs_come_funziona_schema_punti_html();
	$h .= $body;
	$h .= '</div>';
	$h .= '</details>';
	return $h;
}

add_shortcode( 'gs_come_funziona', 'gs_come_funziona_html' );

// -----------------------------------------------------------------------------
// PANNELLO — modifica del testo "Come funziona il Percorso"
// -----------------------------------------------------------------------------
function gs_pannello_come_funziona() {
	if ( ! gs_can_manage() ) { return; }
	$cfg = gs_come_funziona_settings();
	echo gs_box_open( 'Come funziona il Percorso (in «La Mia Sfoglia»)' );
	echo '<p class="gs-hint">La guida che spiega punti, livelli, missioni, badge e tutti gli altri sistemi del percorso. Compare in cima alla pagina «La Mia Sfoglia» di ogni sfoglina, titolo cliccabile che apre il testo. Sopra il testo compaiono anche due schemi colorati (i sei livelli e alcune fonti di punti): non si modificano da qui, si aggiornano da soli leggendo le soglie dei livelli e i punti per azione impostati in "Impostazioni generali". Per il grassetto nel testo, racchiudi la parola tra due asterischi: **così**.</p>';
	?>
	<form class="gs-form gs-form-come-funziona" onsubmit="return false">
		<p><label><input type="checkbox" name="attivo" <?php checked( ! empty( $cfg['attivo'] ) ); ?>> Mostra la guida "Come funziona il Percorso"</label></p>
		<p><label>Titolo (cliccabile)<br><input type="text" name="titolo" autocomplete="off" value="<?php echo esc_attr( $cfg['titolo'] ); ?>" style="width:100%"></label></p>
		<p><label>Testo<br><textarea name="testo" rows="14" style="width:100%"><?php echo esc_textarea( $cfg['testo'] ); ?></textarea></label></p>
		<p><button class="gs-btn gs-btn-sm gs-come-funziona-salva">Salva guida</button> <span class="gs-come-funziona-msg gs-richiesta-esito"></span></p>
	</form>
	<?php
	echo gs_box_close();
}

add_action( 'wp_ajax_gs_come_funziona_salva', 'gs_ajax_come_funziona_salva' );
function gs_ajax_come_funziona_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$s = gs_settings();
	$s['come_funziona'] = array(
		'attivo' => ! empty( $_POST['attivo'] ) ? 1 : 0,
		'titolo' => sanitize_text_field( wp_unslash( $_POST['titolo'] ?? '' ) ),
		'testo'  => sanitize_textarea_field( wp_unslash( $_POST['testo'] ?? '' ) ),
	);
	update_option( GS_OPTION, $s );
	wp_send_json_success( array( 'message' => 'Guida "Come funziona il Percorso" salvata.' ) );
}
