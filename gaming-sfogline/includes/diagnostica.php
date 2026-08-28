<?php
/**
 * diagnostica.php — Strumenti di controllo per chi gestisce il portale.
 *
 *  • Invio di un'email di prova (l'email dipende dall'SMTP del server: questo
 *    dice subito se il server spedisce, senza dover indovinare).
 *  • "Stato di salute": controlli automatici su cron, permalink, pagine,
 *    cartella backup ed estensioni PHP, per scoprire in anticipo i problemi di
 *    configurazione (spesso all'origine dei "non funziona").
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Stato di salute
// -----------------------------------------------------------------------------

/**
 * Esegue i controlli e restituisce un elenco di voci:
 *   array( 'label' => ..., 'stato' => 'ok'|'warn', 'dettaglio' => ... )
 */
function gs_health_report() {
	$out = array();

	// Cron giornaliero programmato?
	$cron = function_exists( 'wp_next_scheduled' ) ? wp_next_scheduled( 'gs_daily_cron' ) : false;
	$out[] = array(
		'label'     => 'Attività giornaliera automatica (cron)',
		'stato'     => $cron ? 'ok' : 'warn',
		'dettaglio' => $cron ? 'Programmata: chiusura sfide, backup, avvisi.' : 'Non programmata: riattiva il plugin per riprogrammarla.',
	);

	// Permalink "carini" attivi? (servono per la Vetrina)
	$perma = get_option( 'permalink_structure' );
	$out[] = array(
		'label'     => 'Struttura dei permalink',
		'stato'     => $perma ? 'ok' : 'warn',
		'dettaglio' => $perma ? 'Attiva.' : 'Impostata su "Semplice": vai in Impostazioni → Permalink e scegli un\'opzione diversa.',
	);

	// Pagine chiave pubblicate?
	$pagine = array(
		'gs_page_dashboard'  => 'La Mia Sfoglia',
		'gs_page_sfida'      => 'Sfida della Settimana',
		'gs_page_calendario' => 'Calendario Corsi',
		'gs_page_pannello'   => 'Pannello di Controllo',
	);
	$mancanti = array();
	foreach ( $pagine as $opt => $nome ) {
		$pid = (int) get_option( $opt );
		if ( ! $pid || 'publish' !== get_post_status( $pid ) ) {
			$mancanti[] = $nome;
		}
	}
	$out[] = array(
		'label'     => 'Pagine pubbliche del plugin',
		'stato'     => $mancanti ? 'warn' : 'ok',
		'dettaglio' => $mancanti ? 'Mancano o non pubblicate: ' . implode( ', ', $mancanti ) . '. Riattiva il plugin per ricrearle.' : 'Tutte presenti e pubblicate.',
	);

	// Cartella dei caricamenti scrivibile? (foto/video e backup)
	$up  = function_exists( 'wp_upload_dir' ) ? wp_upload_dir() : array( 'basedir' => '' );
	$dir = isset( $up['basedir'] ) ? $up['basedir'] : '';
	$scrivibile = $dir && is_writable( $dir );
	$out[] = array(
		'label'     => 'Cartella dei caricamenti',
		'stato'     => $scrivibile ? 'ok' : 'warn',
		'dettaglio' => $scrivibile ? 'Scrivibile (foto, video e backup).' : 'Non scrivibile: i caricamenti e i backup potrebbero fallire.',
	);

	// Estensione ZipArchive (backup e "Esporta tutto")
	$out[] = array(
		'label'     => 'Estensione ZipArchive',
		'stato'     => class_exists( 'ZipArchive' ) ? 'ok' : 'warn',
		'dettaglio' => class_exists( 'ZipArchive' ) ? 'Disponibile (backup e zip di esportazione).' : 'Assente: backup e "Esporta tutto (zip)" non disponibili. Restano i CSV singoli.',
	);

	// Estensione GD (elaborazione immagini)
	$out[] = array(
		'label'     => 'Elaborazione immagini (GD)',
		'stato'     => extension_loaded( 'gd' ) ? 'ok' : 'warn',
		'dettaglio' => extension_loaded( 'gd' ) ? 'Disponibile.' : 'Assente: le miniature potrebbero non generarsi.',
	);

	// mbstring (il plugin ha un fallback, ma è meglio averla)
	$out[] = array(
		'label'     => 'Estensione mbstring',
		'stato'     => extension_loaded( 'mbstring' ) ? 'ok' : 'warn',
		'dettaglio' => extension_loaded( 'mbstring' ) ? 'Disponibile.' : 'Assente: il plugin usa un ripiego, ma è consigliata.',
	);

	// ffmpeg (compressione video): senza, i video restano al peso originale
	// e nessun errore lo segnala altrove — va detto qui a chiare lettere.
	$ffmpeg = function_exists( 'gs_ffmpeg_disponibile' ) && gs_ffmpeg_disponibile();
	$out[] = array(
		'label'     => 'Compressione video (ffmpeg)',
		'stato'     => $ffmpeg ? 'ok' : 'warn',
		'dettaglio' => $ffmpeg ? 'Disponibile: i video vengono compressi se l\'opzione è attiva.' : 'Assente sul server (spesso perché shell_exec è disabilitato dall\'hosting): i video non vengono compressi, restano al peso originale. Non è un errore, è un limite del server.',
	);

	// L'aggiramento del bug del tema (gs_get_posts_by_author(), corretto
	// nella 3.281.0) cerca l'aggancio del tema per NOME esatto
	// (the_newspaper_post_author_archive). Se un aggiornamento del tema lo
	// rinomina o lo toglie, l'aggiramento smette di servire a qualcosa in
	// silenzio — esattamente come il bug originale è rimasto per settimane
	// senza che nessuno se ne accorgesse. Segnalato in revisione (A2,
	// 26/08/2026): questo controllo dice solo se l'aggancio esiste ancora
	// con questo nome, non se il difetto del tema è ancora presente.
	$aggancio_tema = has_action( 'pre_get_posts', 'the_newspaper_post_author_archive' );
	$out[] = array(
		'label'     => 'Aggiramento del bug del tema (ricerche per autore)',
		'stato'     => ( false !== $aggancio_tema ) ? 'ok' : 'warn',
		'dettaglio' => ( false !== $aggancio_tema )
			? 'L\'aggancio del tema che altera le ricerche "per autore" è ancora al suo posto con lo stesso nome: gs_get_posts_by_author() lo sta aggirando correttamente.'
			: 'Non trovato più con questo nome: il tema potrebbe averlo rinominato, tolto, o essere stato aggiornato. Va controllato se il difetto originale (ricerche per autore che tornano vuote o sbagliate) è ancora presente sotto un altro nome, o se è stato risolto dal tema stesso.',
	);

	return $out;
}

// -----------------------------------------------------------------------------
// Invio email di prova (AJAX)
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_diag_email', 'gs_ajax_diag_email' );
function gs_ajax_diag_email() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$utente = wp_get_current_user();
	$dest   = $utente ? $utente->user_email : '';
	if ( ! $dest || ! is_email( $dest ) ) {
		wp_send_json_error( array( 'message' => 'Il tuo profilo non ha un indirizzo email valido.' ) );
	}

	// Cattura l'eventuale errore riportato da wp_mail.
	$errore = '';
	$cattura = function ( $wp_error ) use ( &$errore ) {
		$errore = $wp_error->get_error_message();
	};
	add_action( 'wp_mail_failed', $cattura );

	$ok = wp_mail(
		$dest,
		'Email di prova — Gaming Sfogline',
		"Questa è un'email di prova inviata dal pannello del portale.\n\nSe la stai leggendo, l'invio delle email dal sito funziona.",
		array( 'Content-Type: text/plain; charset=UTF-8' )
	);

	remove_action( 'wp_mail_failed', $cattura );

	if ( $ok ) {
		wp_send_json_success( array( 'message' => 'Email di prova inviata a ' . $dest . '. Controlla la posta (anche lo spam).' ) );
	}
	wp_send_json_error( array( 'message' => 'Invio fallito. ' . ( $errore ? $errore : 'Il server non ha potuto spedire: probabilmente manca la configurazione SMTP.' ) ) );
}

// -----------------------------------------------------------------------------
// Elenco utenti "oltre i sei" — Passo 1 del reset (nessuna cancellazione)
// -----------------------------------------------------------------------------

/**
 * Chi possiede una vetrina (Artigiani della Pasta o Scuole di Cucina),
 * ANCHE se cestinata: una vetrina cestinata torna con un clic, quindi conta
 * come "ha una vetrina" allo stesso modo di una pubblicata — decisione di
 * RESET-RISPOSTE-DI-ENNIO.md, Passo 2.3 (26/08/2026). Filtro in PHP
 * sull'elenco già letto, non su 'author' nella query: stessa ragione già
 * documentata su gs_art_owner_post() (il tema altera le query con quel
 * parametro), e qui gli elenchi restano piccoli.
 */
function gs_diag_autori_con_vetrina() {
	$autori = array();
	if ( function_exists( 'gs_art_elenco' ) ) {
		foreach ( gs_art_elenco( array( 'publish', 'trash' ) ) as $p ) { $autori[ (int) $p->post_author ] = true; }
	}
	if ( function_exists( 'gs_scu_elenco' ) ) {
		foreach ( gs_scu_elenco( array( 'publish', 'trash' ) ) as $p ) { $autori[ (int) $p->post_author ] = true; }
	}
	return $autori;
}

/**
 * L'elenco degli account "oltre i sei": tutti gli utenti WordPress ESCLUSI
 * quelli che, per qualunque motivo, non si toccano mai (RESET-RISPOSTE-DI-
 * ENNIO.md, Passo 2). Nessuna cancellazione qui: solo l'elenco, da mostrare
 * a Ennio perché segni chi resta e chi no.
 */
function gs_diag_elenco_utenti_extra() {
	$autori_vetrina = gs_diag_autori_con_vetrina();
	$righe = array();
	foreach ( get_users( array( 'fields' => 'all' ) ) as $u ) {
		if ( user_can( $u, 'manage_options' ) ) { continue; }                              // 1. amministratori
		$stato = get_user_meta( $u->ID, 'gs_status', true );
		if ( in_array( $stato, array( 'artigiano', 'scuola_cucina', 'lettore' ), true ) ) { continue; } // 2.
		if ( isset( $autori_vetrina[ $u->ID ] ) ) { continue; }                            // 3. ha una vetrina
		if ( get_user_meta( $u->ID, 'gs_abbonamento_scadenza', true ) ) { continue; }       // 4. abbonamento vero
		if ( '1' === get_user_meta( $u->ID, 'gs_conta_come_sfoglina', true ) ) { continue; } // 5. interruttore manuale
		$righe[] = $u;
	}
	return $righe;
}

// -----------------------------------------------------------------------------
// Il Tavolo di Lavoro — quanto ha già dato il bug del tema (D1, 26/08/2026)
// -----------------------------------------------------------------------------

/**
 * Prima della correzione del bug del tema (gs_get_posts_by_author(),
 * 25/08/2026), gs_tavolo_di_oggi() restituiva sempre null: il limite di una
 * foto al giorno non ha mai funzionato davvero, e ogni foto ha dato i suoi
 * 5 punti. Con TAVOLO-SENZA-LIMITE.md le foto diventano libere di proposito
 * — ma va distinto quello che è già successo per il difetto da quello che
 * succederà da adesso per scelta di Ennio. Solo lettura: non tocca niente.
 *
 * gs_points_log tiene al massimo le ultime 100 voci per sfoglina (tutte le
 * fonti di punti insieme, non solo il Tavolo): il numero può essere un
 * minimo, non un totale esatto, per chi ha avuto molta altra attività.
 */
function gs_diag_tavolo_punti_pregressi() {
	$righe = array();
	foreach ( get_users( array( 'fields' => array( 'ID', 'display_name' ) ) ) as $u ) {
		$log = get_user_meta( $u->ID, 'gs_points_log', true );
		if ( ! is_array( $log ) ) { continue; }
		$giorni = array();
		$voci   = 0;
		foreach ( $log as $voce ) {
			if ( ! isset( $voce['reason'] ) || 'Il Tavolo di Lavoro: foto del giorno' !== $voce['reason'] ) { continue; }
			$voci++;
			$giorni[ substr( (string) $voce['time'], 0, 10 ) ] = true;
		}
		if ( $voci > 0 ) {
			$righe[] = array( 'nome' => $u->display_name, 'voci' => $voci, 'giorni' => count( $giorni ) );
		}
	}
	return $righe;
}

// -----------------------------------------------------------------------------
// Sezione nella Plancia
// -----------------------------------------------------------------------------
function gs_pannello_diagnostica() {
	$utente = wp_get_current_user();
	$dest   = $utente ? $utente->user_email : '';

	echo gs_box_open( 'Diagnostica e stato di salute' );

	// --- Stato di salute ---
	echo '<p class="gs-hint">Controlli automatici della configurazione. Un ⚠️ indica qualcosa da sistemare (di solito da Impostazioni o riattivando il plugin).</p>';
	echo '<table class="gs-table"><tbody>';
	foreach ( gs_health_report() as $v ) {
		$icona = 'ok' === $v['stato'] ? '✅' : '⚠️';
		echo '<tr><td style="white-space:nowrap">' . $icona . ' ' . esc_html( $v['label'] ) . '</td><td>' . esc_html( $v['dettaglio'] ) . '</td></tr>';
	}
	echo '</tbody></table>';

	// --- Email di prova ---
	echo '<hr>';
	echo '<p class="gs-hint">L\'invio email dipende dal server (serve un SMTP configurato). Manda un\'email di prova a te stessa per verificare se il sito riesce a spedire.</p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-diag-email">Invia email di prova' . ( $dest ? ' a ' . esc_html( $dest ) : '' ) . '</button> <span class="gs-diag-email-msg gs-richiesta-esito"></span></p>';

	// --- Elenco utenti "oltre i sei" (Passo 1 del reset, 26/08/2026) ---
	echo '<hr>';
	echo '<p class="gs-hint">Elenco degli account che NON rientrano fra amministratori, Artigiani/Scuole di Cucina (anche con vetrina cestinata), lettori, abbonamenti veri e sfogline abilitate a mano — sono quelli su cui decidere. <strong>Nessuna cancellazione avviene qui</strong>: è solo un elenco da guardare.</p>';
	$extra = gs_diag_elenco_utenti_extra();
	if ( ! $extra ) {
		echo '<p class="gs-hint">Nessun account fuori dalle esclusioni.</p>';
	} else {
		echo '<div style="overflow-x:auto"><table class="gs-table"><thead><tr>';
		echo '<th>Nome</th><th>Email</th><th>Ruolo WP</th><th>gs_status</th><th>Punti</th><th>Ultimo accesso</th><th>Iscritta il</th>';
		echo '</tr></thead><tbody>';
		foreach ( $extra as $u ) {
			$ultimo = get_user_meta( $u->ID, 'gs_ultimo_accesso', true );
			echo '<tr>';
			echo '<td>' . esc_html( $u->display_name ) . '</td>';
			echo '<td>' . esc_html( $u->user_email ) . '</td>';
			echo '<td>' . esc_html( implode( ', ', $u->roles ) ) . '</td>';
			echo '<td>' . esc_html( get_user_meta( $u->ID, 'gs_status', true ) ?: '—' ) . '</td>';
			echo '<td>' . (int) get_user_meta( $u->ID, 'gs_points', true ) . '</td>';
			echo '<td>' . ( $ultimo ? esc_html( gs_data_it( $ultimo ) ) : '—' ) . '</td>';
			echo '<td>' . esc_html( date_i18n( 'd/m/Y', strtotime( $u->user_registered ) ) ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';
		echo '<p class="gs-hint">' . count( $extra ) . ' account.</p>';
	}

	// --- Il Tavolo di Lavoro: quanto ha già dato il bug del tema (D1) ---
	echo '<hr>';
	echo '<p class="gs-hint">Fino al 25/08/2026 il limite di una foto al giorno sul Tavolo di Lavoro non ha mai funzionato per il bug del tema (corretto nella 3.281.0): ogni foto caricata ha dato i suoi punti. Qui sotto, per chi ha caricato più foto in un giorno, quanti punti sono già stati assegnati per quel motivo — <strong>da guardare prima di installare "Tavolo senza limite"</strong>, per distinguere quello arrivato dal difetto da quello che arriverà dalla scelta di Ennio.</p>';
	$tavolo_pregresso = gs_diag_tavolo_punti_pregressi();
	if ( ! $tavolo_pregresso ) {
		echo '<p class="gs-hint">Nessuna voce trovata nel registro punti con questo motivo.</p>';
	} else {
		echo '<table class="gs-table"><thead><tr><th>Sfoglina</th><th>Voci nel registro</th><th>Giorni distinti</th></tr></thead><tbody>';
		foreach ( $tavolo_pregresso as $r ) {
			echo '<tr><td>' . esc_html( $r['nome'] ) . '</td><td>' . (int) $r['voci'] . '</td><td>' . (int) $r['giorni'] . '</td></tr>';
		}
		echo '</tbody></table>';
		echo '<p class="gs-hint">Il registro tiene al massimo le ultime 100 voci per sfoglina (tutte le fonti di punti insieme): questi numeri possono essere un minimo, non un totale esatto.</p>';
	}

	echo gs_box_close();
}
