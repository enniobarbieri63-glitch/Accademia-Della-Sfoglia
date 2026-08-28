<?php
/**
 * media-backup.php — Lotto 2
 * - Limite di peso sui caricamenti delle sfogline (lato server, sempre attivo)
 * - Compressione foto (lato browser, gestita in JS con le impostazioni)
 * - Rilevamento di ffmpeg e compressione video best-effort (se disponibile)
 * - Backup automatico giornaliero dei file registrati, con conservazione
 *
 * Tutti i parametri sono comandabili dal Pannello di Controllo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// =============================================================================
// LIMITE DI PESO SUI CARICAMENTI FRONT-END
// =============================================================================
add_action( 'init', 'gs_media_hooks' );
function gs_media_hooks() {
	if ( ! is_admin() ) {
		add_filter( 'wp_handle_upload_prefilter', 'gs_limita_upload' );
		add_filter( 'wp_handle_upload', 'gs_comprimi_video_upload' );
	}
}

/**
 * Blocca i file troppo pesanti caricati dal front-end (sfogline).
 */
function gs_limita_upload( $file ) {
	$m         = gs_media_settings();
	$limite_mb = isset( $m['limite_mb'] ) ? (float) $m['limite_mb'] : 0;
	if ( $limite_mb > 0 && ! empty( $file['size'] ) ) {
		$max = $limite_mb * 1024 * 1024;
		if ( $file['size'] > $max ) {
			$file['error'] = sprintf(
				'Il file è troppo pesante (%.1f MB). Il limite è di %s MB. Riduci le dimensioni e riprova.',
				$file['size'] / 1048576,
				$limite_mb
			);
		}
	}
	return $file;
}

// =============================================================================
// FFMPEG + COMPRESSIONE VIDEO (best-effort)
// =============================================================================

/**
 * Rileva se ffmpeg è disponibile sul server. Risultato in cache 12 ore.
 */
function gs_ffmpeg_disponibile() {
	$cache = get_transient( 'gs_ffmpeg_ok' );
	if ( false !== $cache ) {
		return '1' === $cache;
	}
	$ok = false;
	if ( function_exists( 'shell_exec' ) ) {
		$out = @shell_exec( 'ffmpeg -version 2>&1' );
		$ok  = ( is_string( $out ) && false !== stripos( $out, 'ffmpeg version' ) );
	}
	set_transient( 'gs_ffmpeg_ok', $ok ? '1' : '0', 12 * HOUR_IN_SECONDS );
	return $ok;
}

/**
 * Comprime un video appena caricato, solo se: opzione attiva, ffmpeg presente,
 * il file è un video. In caso di qualsiasi problema, lascia il file originale.
 */
function gs_comprimi_video_upload( $upload ) {
	$m = gs_media_settings();
	if ( empty( $m['comprimi_video'] ) || ! gs_ffmpeg_disponibile() ) {
		return $upload;
	}
	if ( empty( $upload['type'] ) || 0 !== strpos( $upload['type'], 'video/' ) ) {
		return $upload;
	}
	if ( empty( $upload['file'] ) || ! file_exists( $upload['file'] ) ) {
		return $upload;
	}

	$src = $upload['file'];
	$tmp = $src . '.gscompress.mp4';

	// Ricodifica H.264 a bitrate contenuto, ridimensionando se molto grande.
	$cmd = sprintf(
		'ffmpeg -y -i %s -vf "scale=\'min(1280,iw)\':-2" -c:v libx264 -crf 28 -preset veryfast -c:a aac -b:a 128k %s 2>&1',
		escapeshellarg( $src ),
		escapeshellarg( $tmp )
	);
	@shell_exec( $cmd );

	if ( file_exists( $tmp ) && filesize( $tmp ) > 0 && filesize( $tmp ) < filesize( $src ) ) {
		@unlink( $src );
		@rename( $tmp, $src );
	} elseif ( file_exists( $tmp ) ) {
		@unlink( $tmp );
	}
	return $upload;
}

// =============================================================================
// BACKUP GIORNALIERO DEI FILE REGISTRATI
// =============================================================================

/** Prepara la cartella backup protetta (deny pubblico). */
function gs_backup_prepara_dir() {
	$dir = gs_backup_dir();
	if ( ! file_exists( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	$ht = trailingslashit( $dir ) . '.htaccess';
	if ( ! file_exists( $ht ) ) {
		@file_put_contents( $ht, "Deny from all\n" );
	}
	$idx = trailingslashit( $dir ) . 'index.php';
	if ( ! file_exists( $idx ) ) {
		@file_put_contents( $idx, "<?php // Silence is golden." );
	}
	return $dir;
}

/**
 * Raccoglie i file dei contenuti registrati dalle sfogline (allegati alle
 * sfoglie) e li racchiude in un unico zip datato. Conserva solo gli ultimi N.
 *
 * @return array|WP_Error info sul backup creato
 */
function gs_run_backup() {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error( 'no_zip', 'Estensione ZipArchive non disponibile sul server.' );
	}

	$dir = gs_backup_prepara_dir();

	// Allegati che appartengono alle sfoglie (immagini/video inviati).
	$sfoglie = get_posts( array(
		'post_type'      => 'gs_sfoglia',
		'post_status'    => array( 'publish', 'trash' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );

	$files = array();
	foreach ( $sfoglie as $sid ) {
		$thumb = get_post_thumbnail_id( $sid );
		if ( $thumb ) {
			$path = get_attached_file( $thumb );
			if ( $path && file_exists( $path ) ) {
				$files[ $thumb ] = $path;
			}
		}
		// Eventuali altri allegati figli della sfoglia.
		$children = get_children( array( 'post_parent' => $sid, 'post_type' => 'attachment' ) );
		foreach ( $children as $att ) {
			$path = get_attached_file( $att->ID );
			if ( $path && file_exists( $path ) ) {
				$files[ $att->ID ] = $path;
			}
		}
	}

	$nome = 'backup-' . date( 'Y-m-d-His' ) . '.zip';
	$zip_path = trailingslashit( $dir ) . $nome;

	$zip = new ZipArchive();
	if ( true !== $zip->open( $zip_path, ZipArchive::CREATE ) ) {
		return new WP_Error( 'zip_open', 'Impossibile creare il file di backup.' );
	}
	// Un piccolo manifest leggibile.
	$zip->addFromString( 'MANIFEST.txt', "Backup Gaming Sfogline\nData: " . date( 'c' ) . "\nFile: " . count( $files ) . "\n" );
	foreach ( $files as $path ) {
		$zip->addFile( $path, 'media/' . basename( $path ) );
	}
	$zip->close();

	// Retention: mantieni solo gli ultimi N.
	$b = gs_backup_settings();
	gs_backup_pulisci( isset( $b['retention'] ) ? (int) $b['retention'] : 7 );

	update_option( 'gs_last_backup', time() );

	return array( 'file' => $nome, 'count' => count( $files ), 'size' => file_exists( $zip_path ) ? filesize( $zip_path ) : 0 );
}

/** Elenco dei backup presenti (più recenti prima). */
function gs_backup_lista() {
	$dir = gs_backup_dir();
	if ( ! file_exists( $dir ) ) {
		return array();
	}
	$out = array();
	foreach ( glob( trailingslashit( $dir ) . 'backup-*.zip' ) as $f ) {
		$out[] = array( 'nome' => basename( $f ), 'size' => filesize( $f ), 'time' => filemtime( $f ) );
	}
	usort( $out, function ( $a, $b ) { return $b['time'] - $a['time']; } );
	return $out;
}

/** Mantieni solo gli ultimi $keep backup. */
function gs_backup_pulisci( $keep ) {
	$lista = gs_backup_lista();
	if ( $keep < 1 ) { $keep = 1; }
	for ( $i = $keep; $i < count( $lista ); $i++ ) {
		@unlink( trailingslashit( gs_backup_dir() ) . $lista[ $i ]['nome'] );
	}
}

// Esecuzione automatica giornaliera (aggancio al cron già esistente).
add_action( 'gs_daily_cron', 'gs_backup_giornaliero' );
function gs_backup_giornaliero() {
	$b = gs_backup_settings();
	if ( ! empty( $b['attivo'] ) ) {
		gs_run_backup();
	}
}

// =============================================================================
// AJAX (comandi dal pannello) — solo gestori
// =============================================================================

// Salva impostazioni media.
add_action( 'wp_ajax_gs_fe_salva_media', 'gs_ajax_fe_salva_media' );
function gs_ajax_fe_salva_media() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$s = gs_settings();
	$s['media']['comprimi_foto']  = ! empty( $_POST['comprimi_foto'] );
	$s['media']['comprimi_video'] = ! empty( $_POST['comprimi_video'] );
	$s['media']['foto_max_lato']  = max( 400, (int) ( $_POST['foto_max_lato'] ?? 1600 ) );
	$s['media']['limite_mb']      = max( 1, (float) ( $_POST['limite_mb'] ?? 8 ) );
	update_option( GS_OPTION, $s );

	wp_send_json_success( array( 'message' => 'Impostazioni media salvate.' ) );
}

// Salva impostazioni backup.
add_action( 'wp_ajax_gs_fe_salva_backup', 'gs_ajax_fe_salva_backup' );
function gs_ajax_fe_salva_backup() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$s = gs_settings();
	$s['backup']['attivo']    = ! empty( $_POST['attivo'] );
	$s['backup']['retention'] = max( 1, (int) ( $_POST['retention'] ?? 7 ) );
	update_option( GS_OPTION, $s );

	wp_send_json_success( array( 'message' => 'Impostazioni backup salvate.' ) );
}

// Esegui backup ora.
add_action( 'wp_ajax_gs_fe_backup_ora', 'gs_ajax_fe_backup_ora' );
function gs_ajax_fe_backup_ora() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$res = gs_run_backup();
	if ( is_wp_error( $res ) ) {
		wp_send_json_error( array( 'message' => $res->get_error_message() ) );
	}
	wp_send_json_success( array(
		'message' => sprintf( 'Backup creato: %d file, %s.', $res['count'], size_format( $res['size'] ) ),
		'html'    => gs_backup_lista_html(),
	) );
}

// Download di un backup (streaming autenticato, solo gestori).
add_action( 'wp_ajax_gs_fe_backup_download', 'gs_ajax_fe_backup_download' );
function gs_ajax_fe_backup_download() {
	if ( ! gs_can_manage() ) { wp_die( 'Permesso negato.' ); }
	check_admin_referer( 'gs_backup_dl' );

	$nome = isset( $_GET['file'] ) ? basename( $_GET['file'] ) : '';
	if ( ! preg_match( '/^backup-[0-9\-]+\.zip$/', $nome ) ) { wp_die( 'File non valido.' ); }
	$path = trailingslashit( gs_backup_dir() ) . $nome;
	if ( ! file_exists( $path ) ) { wp_die( 'File non trovato.' ); }

	nocache_headers();
	header( 'Content-Type: application/zip' );
	header( 'Content-Disposition: attachment; filename="' . $nome . '"' );
	header( 'Content-Length: ' . filesize( $path ) );
	readfile( $path );
	exit;
}

/** HTML dell'elenco backup (con link di download). */
function gs_backup_lista_html() {
	$lista = gs_backup_lista();
	if ( empty( $lista ) ) {
		return '<p class="gs-hint">Nessun backup presente.</p>';
	}
	$out = '<table class="gs-table gs-paginate" data-per-page="10"><thead><tr><th>Backup</th><th>Data</th><th>Dimensione</th><th></th></tr></thead><tbody>';
	foreach ( $lista as $b ) {
		$url = wp_nonce_url( admin_url( 'admin-ajax.php?action=gs_fe_backup_download&file=' . rawurlencode( $b['nome'] ) ), 'gs_backup_dl' );
		$out .= sprintf(
			'<tr><td>%s</td><td>%s</td><td>%s</td><td><a class="gs-btn gs-btn-sm" href="%s">Scarica</a></td></tr>',
			esc_html( $b['nome'] ),
			esc_html( date_i18n( 'j/m/Y H:i', $b['time'] ) ),
			esc_html( size_format( $b['size'] ) ),
			esc_url( $url )
		);
	}
	$out .= '</tbody></table>';
	return $out;
}

// =============================================================================
// SEZIONI DEL PANNELLO
// =============================================================================

function gs_pannello_media() {
	$m       = gs_media_settings();
	$ffmpeg  = gs_ffmpeg_disponibile();
	echo gs_box_open( 'Foto e video — compressione e limiti' );
	?>
	<p class="gs-hint">Regola come vengono gestiti i file caricati dalle sfogline. Il limite di peso è sempre attivo; la compressione delle foto avviene nel browser prima dell'invio.</p>
	<form class="gs-form gs-form-media" onsubmit="return false">
		<p><label><input type="checkbox" name="comprimi_foto" <?php checked( ! empty( $m['comprimi_foto'] ) ); ?>> Comprimi e ridimensiona le foto automaticamente</label></p>
		<p><label>Lato massimo foto (px)<br><input type="number" name="foto_max_lato" value="<?php echo (int) ( $m['foto_max_lato'] ?? 1600 ); ?>" style="width:140px"></label></p>
		<p><label>Peso massimo per file (MB)<br><input type="number" step="0.5" name="limite_mb" value="<?php echo esc_attr( $m['limite_mb'] ?? 8 ); ?>" style="width:140px"></label></p>
		<p>
			<label>
				<input type="checkbox" name="comprimi_video" <?php checked( ! empty( $m['comprimi_video'] ) ); ?> <?php disabled( ! $ffmpeg ); ?>>
				Comprimi anche i video
			</label><br>
			<small class="gs-hint">
				<?php if ( $ffmpeg ) : ?>
					✅ Il tuo server supporta la compressione video (ffmpeg rilevato).
				<?php else : ?>
					⚠️ Il tuo server non ha ffmpeg: la compressione video non è disponibile. Resta comunque attivo il limite di peso.
				<?php endif; ?>
			</small>
		</p>
		<p><button class="gs-btn gs-btn-sm gs-salva-media">Salva impostazioni media</button> <span class="gs-media-msg gs-richiesta-esito"></span></p>
	</form>
	<?php
	echo gs_box_close();
}

function gs_pannello_backup() {
	$b = gs_backup_settings();
	$last = (int) get_option( 'gs_last_backup', 0 );
	echo gs_box_open( 'Backup automatico dei file' );
	?>
	<p class="gs-hint">Crea una copia dei file registrati dalle sfogline. Il backup viene salvato in una cartella protetta del sito.</p>
	<p style="background:var(--gs-uovo);padding:8px 12px;border-radius:6px;font-size:12px;color:#7a3a12">
		Nota: è un backup <strong>sullo stesso server</strong>. Per la massima sicurezza affiancalo a un backup esterno (plugin o servizio dedicato).
	</p>
	<form class="gs-form gs-form-backup" onsubmit="return false">
		<p><label><input type="checkbox" name="attivo" <?php checked( ! empty( $b['attivo'] ) ); ?>> Esegui un backup automatico ogni giorno</label></p>
		<p><label>Quanti backup conservare<br><input type="number" name="retention" value="<?php echo (int) ( $b['retention'] ?? 7 ); ?>" style="width:120px"></label></p>
		<p>
			<button class="gs-btn gs-btn-sm gs-btn-verde gs-salva-backup">Salva</button>
			<button class="gs-btn gs-btn-sm gs-btn-ghost gs-backup-ora">Esegui backup ora</button>
			<span class="gs-backup-msg gs-richiesta-esito"></span>
		</p>
	</form>
	<?php if ( $last ) : ?>
		<p class="gs-hint">Ultimo backup: <?php echo esc_html( date_i18n( 'j/m/Y H:i', $last ) ); ?></p>
	<?php endif; ?>
	<div class="gs-backup-lista"><?php echo gs_backup_lista_html(); ?></div>
	<?php
	echo gs_box_close();
}
