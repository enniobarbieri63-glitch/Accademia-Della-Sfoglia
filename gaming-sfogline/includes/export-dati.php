<?php
/**
 * export-dati.php — Esportazione dei dati di gioco (assicurazione dei dati).
 *
 * Tutto il "cuore" del plugin vive nei meta di utenti e post (niente tabelle
 * personalizzate). Questo modulo permette a chi gestisce il portale di scaricare
 * in qualsiasi momento una copia leggibile di:
 *   • Sfogline    → punti, livello, squadra, stato iscrizione e abbonamento
 *   • Prenotazioni → corso, stato, acconto e saldo versati
 *   • Corsi        → date, posti, prezzi, occupazione
 *
 * I file sono CSV (apribili con Excel/LibreOffice/Numbers). Il pulsante "Tutto"
 * produce un unico zip con i tre CSV. Serve a fare i conti di fine anno e come
 * rete di sicurezza in caso di problemi ai dati.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Raccolta dei dati (una riga per record)
// -----------------------------------------------------------------------------

/** Righe delle sfogline: anagrafica + dati di gioco. */
function gs_export_rows_sfogline() {
	$header = array( 'Nome', 'Username', 'Email', 'Squadra', 'Stato iscrizione', 'Abbonamento', 'Punti totali', 'Punti anno ' . date( 'Y' ), 'Livello' );
	$rows   = array();
	foreach ( get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) ) as $u ) {
		if ( ! gs_e_sfoglina_vera( $u, false ) ) { continue; }
		$uid    = (int) $u->ID;
		$stato  = get_user_meta( $uid, 'gs_status', true );
		$stato  = $stato ? $stato : 'approvata';
		$abb    = ( function_exists( 'gs_abbonamento_scaduto' ) && gs_abbonamento_scaduto( $uid ) ) ? 'scaduto' : 'attivo';
		$livello = function_exists( 'gs_get_level' ) ? gs_get_level( $uid ) : array( 'titolo' => '' );
		$rows[] = array(
			$u->display_name,
			$u->user_login,
			$u->user_email,
			get_user_meta( $uid, 'gs_team', true ),
			$stato,
			$abb,
			(int) get_user_meta( $uid, 'gs_points', true ),
			function_exists( 'gs_get_year_points' ) ? gs_get_year_points( $uid ) : (int) get_user_meta( $uid, 'gs_points_' . date( 'Y' ), true ),
			isset( $livello['titolo'] ) ? $livello['titolo'] : '',
		);
	}
	return array( $header, $rows );
}

/** Righe delle prenotazioni corsi: solo il CPT del plugin, mai l'intero DB. */
function gs_export_rows_prenotazioni() {
	$header = array( 'Cliente', 'Email', 'Corso', 'Data corso', 'Stato', 'Acconto versato', 'Saldo versato', 'Totale versato' );
	$rows   = array();
	if ( ! function_exists( 'gs_cal_prenotazioni' ) ) {
		return array( $header, $rows );
	}
	$stati = array(
		'prenotato'       => 'In attesa di acconto',
		'confermato'      => 'Confermata',
		'no_show'         => 'Assente',
		'annullato'       => 'Annullata',
		'annullato_tardi' => 'Annullata (fuori termine)',
		'lista_attesa'    => "In lista d'attesa",
		'rimborsato'      => 'Rimborsata',
	);
	foreach ( gs_cal_prenotazioni() as $p ) {
		$pr      = gs_cal_pren_get( $p->ID );
		$cliente = get_userdata( $pr['cliente'] );
		$corso   = gs_cal_corso_get( $pr['corso'] );
		$acc     = (float) $pr['acconto'];
		$sal     = (float) $pr['saldo'];
		$rows[]  = array(
			$cliente ? $cliente->display_name : '(sfoglina rimossa)',
			$cliente ? $cliente->user_email : '',
			$corso ? get_the_title( $pr['corso'] ) : '(corso rimosso)',
			$corso ? $corso['data'] : '',
			isset( $stati[ $pr['stato'] ] ) ? $stati[ $pr['stato'] ] : $pr['stato'],
			number_format( $acc, 2, ',', '' ),
			number_format( $sal, 2, ',', '' ),
			number_format( $acc + $sal, 2, ',', '' ),
		);
	}
	return array( $header, $rows );
}

/** Righe dei corsi a calendario. */
function gs_export_rows_corsi() {
	$header = array( 'Corso', 'Data', 'Inizio', 'Fine', 'Posti totali', 'Posti occupati', 'Posti liberi', 'Prezzo', 'Acconto', 'Stato' );
	$rows   = array();
	if ( ! function_exists( 'gs_cal_corsi' ) ) {
		return array( $header, $rows );
	}
	foreach ( gs_cal_corsi() as $c ) {
		$corso = gs_cal_corso_get( $c->ID );
		$occ   = gs_cal_posti_occupati( $c->ID );
		$rows[] = array(
			get_the_title( $c->ID ),
			$corso['data'],
			$corso['inizio'],
			$corso['fine'],
			(int) $corso['posti'],
			$occ,
			max( 0, (int) $corso['posti'] - $occ ),
			number_format( (float) $corso['prezzo'], 2, ',', '' ),
			number_format( (float) $corso['acconto'], 2, ',', '' ),
			$corso['stato'],
		);
	}
	return array( $header, $rows );
}

/** Mappa set → [nome file, funzione di raccolta]. */
function gs_export_sets() {
	return array(
		'sfogline'     => array( 'sfogline',     'gs_export_rows_sfogline' ),
		'prenotazioni' => array( 'prenotazioni', 'gs_export_rows_prenotazioni' ),
		'corsi'        => array( 'corsi',        'gs_export_rows_corsi' ),
	);
}

// -----------------------------------------------------------------------------
// Costruzione CSV
// -----------------------------------------------------------------------------

/**
 * Trasforma intestazione + righe in una stringa CSV.
 * Separatore ";" e BOM UTF-8: così Excel in italiano apre correttamente le
 * colonne e gli accenti.
 */
function gs_export_csv_string( $header, $rows ) {
	$out = fopen( 'php://temp', 'r+' );
	fputcsv( $out, $header, ';' );
	foreach ( $rows as $r ) {
		fputcsv( $out, $r, ';' );
	}
	rewind( $out );
	$csv = stream_get_contents( $out );
	fclose( $out );
	return "\xEF\xBB\xBF" . $csv; // BOM
}

// -----------------------------------------------------------------------------
// Download autenticato (stesso schema del backup: admin-ajax + nonce)
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_export_dati', 'gs_ajax_export_dati' );
function gs_ajax_export_dati() {
	if ( ! gs_can_manage() ) { wp_die( 'Permesso negato.' ); }
	check_admin_referer( 'gs_export_dati' );

	$set  = isset( $_GET['set'] ) ? sanitize_key( $_GET['set'] ) : '';
	$sets = gs_export_sets();
	$oggi = date( 'Y-m-d' );

	// Un unico zip con tutti i CSV.
	if ( 'tutto' === $set ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			wp_die( 'ZipArchive non disponibile sul server: scarica i file singoli.' );
		}
		$tmp = wp_tempnam( 'gs-export' );
		$zip = new ZipArchive();
		if ( true !== $zip->open( $tmp, ZipArchive::OVERWRITE ) ) {
			wp_die( 'Impossibile creare lo zip di esportazione.' );
		}
		$zip->addFromString( 'LEGGIMI.txt', "Esportazione dati Gaming Sfogline\nData: " . date( 'c' ) . "\nFile CSV separati da ; con BOM UTF-8 (apribili con Excel).\n" );
		foreach ( $sets as $info ) {
			list( $nome, $fn ) = $info;
			list( $header, $rows ) = call_user_func( $fn );
			$zip->addFromString( $nome . '.csv', gs_export_csv_string( $header, $rows ) );
		}
		$zip->close();

		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="gaming-dati-' . $oggi . '.zip"' );
		header( 'Content-Length: ' . filesize( $tmp ) );
		// readfile() manda il file al browser senza tenerlo tutto in RAM.
		readfile( $tmp );
		@unlink( $tmp );
		exit;
	}

	// Un singolo CSV.
	if ( ! isset( $sets[ $set ] ) ) { wp_die( 'Tipo di esportazione non valido.' ); }
	list( $nome, $fn ) = $sets[ $set ];
	list( $header, $rows ) = call_user_func( $fn );
	$csv = gs_export_csv_string( $header, $rows );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="gaming-' . $nome . '-' . $oggi . '.csv"' );
	header( 'Content-Length: ' . strlen( $csv ) );
	echo $csv;
	exit;
}

/** URL firmato per un set di dati. */
function gs_export_dati_url( $set ) {
	return wp_nonce_url(
		admin_url( 'admin-ajax.php?action=gs_export_dati&set=' . rawurlencode( $set ) ),
		'gs_export_dati'
	);
}

// -----------------------------------------------------------------------------
// Sezione nella Plancia
// -----------------------------------------------------------------------------
function gs_pannello_export_dati() {
	echo gs_box_open( 'Esporta i dati del percorso' );
	?>
	<p class="gs-hint">Scarica una copia leggibile dei dati del portale (file CSV apribili con Excel). Utile per i conti di fine anno e come rete di sicurezza: punti e prenotazioni vivono nei dati del sito, questa è la loro copia esportabile.</p>
	<p>
		<a class="gs-btn gs-btn-sm" href="<?php echo esc_url( gs_export_dati_url( 'sfogline' ) ); ?>">Sfogline e punti</a>
		<a class="gs-btn gs-btn-sm" href="<?php echo esc_url( gs_export_dati_url( 'prenotazioni' ) ); ?>">Prenotazioni e pagamenti</a>
		<a class="gs-btn gs-btn-sm" href="<?php echo esc_url( gs_export_dati_url( 'corsi' ) ); ?>">Corsi a calendario</a>
		<a class="gs-btn gs-btn-sm gs-btn-ghost" href="<?php echo esc_url( gs_export_dati_url( 'tutto' ) ); ?>">Tutto (zip)</a>
	</p>
	<p class="gs-hint">Nota: è un'esportazione, non un backup automatico. Conserva il file in un luogo sicuro; per i file (foto/video) usa invece il «Backup dei file».</p>
	<?php
	echo gs_box_close();
}
