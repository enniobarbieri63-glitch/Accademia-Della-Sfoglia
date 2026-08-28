<?php
/**
 * locandine.php — Diplomi e Locandine a piacimento.
 *
 * Sezione del pannello di controllo dove il titolare crea documenti stampabili
 * liberi (diplomi personalizzati, locandine per un corso...) con la stessa
 * grafica dei diplomi già esistenti (cornice dorata su pergamena, logo tondo
 * dell'Accademia), un'intestazione modificabile (es. "MAESTRA RINA POLETTI"),
 * un titolo grande, un testo libero (es. il programma del corso) e qualche
 * foto. Da qui si possono anche scaricare come immagine PNG, pronte per essere
 * pubblicate su Facebook e Instagram (disegnate su un <canvas>, senza librerie
 * esterne — vedi gsScaricaLocandinaImmagine in gaming.js).
 *
 * CPT gs_locandina, nessuna tabella nuova:
 *  - post_title   = titolo grande del documento (es. un nome, o il titolo del corso)
 *  - post_content = testo libero (es. il programma del corso)
 *  - meta gs_loc_intestazione = riga in alto, sotto il logo (es. "MAESTRA RINA POLETTI")
 *  - meta gs_loc_sottotitolo  = etichetta facoltativa (es. "Diploma", "Nuovo corso")
 *  - meta gs_loc_titolo2      = secondo titolo grande, facoltativo, sotto il primo
 *  - meta gs_loc_foto         = array di URL delle foto caricate
 *
 * Solo chi gestisce il portale crea/vede questa sezione: non è indirizzata a
 * una sfoglina in particolare (a differenza dei diplomi di Area Professionale
 * o dei certificati di Percorso Guidato), quindi niente controllo per utente.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_register_locandina_cpt' );
function gs_register_locandina_cpt() {
	register_post_type( 'gs_locandina', array(
		'labels'          => array( 'name' => 'Diplomi e Locandine' ),
		'public'          => false,
		'show_ui'         => false,
		'show_in_menu'    => false,
		'supports'        => array( 'title', 'editor', 'author' ),
		'capability_type' => 'post',
	) );
}

// -----------------------------------------------------------------------------
// Helper
// -----------------------------------------------------------------------------

function gs_locandina_get( $id ) {
	$foto = get_post_meta( $id, 'gs_loc_foto', true );
	return array(
		'id'           => $id,
		'titolo'       => get_the_title( $id ),
		'titolo2'      => get_post_meta( $id, 'gs_loc_titolo2', true ),
		'testo'        => get_post( $id ) ? get_post( $id )->post_content : '',
		'intestazione' => get_post_meta( $id, 'gs_loc_intestazione', true ),
		'sottotitolo'  => get_post_meta( $id, 'gs_loc_sottotitolo', true ),
		'link'         => get_post_meta( $id, 'gs_loc_link', true ),
		'foto'         => is_array( $foto ) ? array_values( $foto ) : array(),
		'og_image'     => get_post_meta( $id, 'gs_loc_og_image', true ),
	);
}

/** Tutte le locandine pubblicate, più recenti prima. */
function gs_locandine_tutte() {
	return gs_solo_tipo( get_posts( array(
		'post_type'        => 'gs_locandina',
		'post_status'      => 'publish',
		'posts_per_page'   => -1,
		'orderby'          => 'date',
		'order'            => 'DESC',
		'suppress_filters' => true,
	) ), 'gs_locandina' );
}

/** Le locandine nel cestino, per il recupero. */
function gs_locandine_cestino() {
	return gs_solo_tipo( get_posts( array(
		'post_type'        => 'gs_locandina',
		'post_status'      => 'trash',
		'posts_per_page'   => 50,
		'suppress_filters' => true,
	) ), 'gs_locandina' );
}

/** L'URL (firmato) della pagina stampabile di una locandina, per chi gestisce il portale. */
function gs_locandina_url( $id ) {
	return wp_nonce_url(
		add_query_arg( array( 'gs_locandina' => (int) $id ), admin_url( 'admin.php' ) ),
		'gs_locandina_' . $id
	);
}

/**
 * URL pubblico (senza login, senza nonce) della stessa pagina, pensato per
 * essere condiviso su Facebook/Instagram: chi lo apre vede la locandina così
 * com'è, incluso il pulsante "Per prenotarti clicca qui" se impostato.
 * Funziona solo per documenti pubblicati (mai per quelli nel cestino).
 */
function gs_locandina_url_pubblico( $id ) {
	return add_query_arg( array( 'gs_loc' => (int) $id ), home_url( '/' ) );
}

/** Il contenuto HTML del documento (pagina intera): separato per poterlo testare e usare per lo "scarica immagine". */
function gs_locandina_html( $id ) {
	$d = gs_locandina_get( $id );

	$sigillo    = gs_certificato_logo_url();
	$ha_sigillo = gs_certificato_logo_esiste();

	// Per l'anteprima quando il link viene condiviso su Facebook/Instagram
	// (leggono questi tag, non eseguono JavaScript: non possono "vedere" il
	// disegno fatto sul canvas). Finché non è stata preparata un'anteprima
	// vera (pulsante "Prepara link per Facebook"), si usa il logo come
	// immagine di ripiego — non bello quanto la locandina, ma sempre meglio
	// di un'anteprima vuota.
	$og_titolo = $d['titolo'] ? $d['titolo'] : 'Documento';
	$og_descr  = $d['sottotitolo'] ? $d['sottotitolo'] : ( $d['testo'] ? wp_trim_words( $d['testo'], 25 ) : 'Accademia della Sfoglia' );
	$og_img    = $d['og_image'] ? $d['og_image'] : $sigillo;
	$og_url    = gs_locandina_url_pubblico( $id );

	ob_start();
	?><!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="utf-8">
	<title><?php echo esc_html( $d['titolo'] ? $d['titolo'] : 'Documento' ); ?> — Accademia della Sfoglia</title>
	<meta property="og:type" content="website">
	<meta property="og:title" content="<?php echo esc_attr( $og_titolo ); ?>">
	<meta property="og:description" content="<?php echo esc_attr( $og_descr ); ?>">
	<meta property="og:image" content="<?php echo esc_url( $og_img ); ?>">
	<meta property="og:url" content="<?php echo esc_url( $og_url ); ?>">
	<?php echo gs_certificato_css(); ?>
	<style>
		/* Solo per le locandine: testo più grande e leggibile, "Accademia della Sfoglia"
		   e l'intestazione (es. "Maestra Rina Poletti") in grande, e pulsante di prenotazione. */
		#gs-loc-canvas-source .testo { font-size: 19px; max-width: 560px; }
		#gs-loc-canvas-source .eyebrow-top { font-size: 1.9em; font-family: var(--serif); letter-spacing: .08em; margin-bottom: 20px; }
		#gs-loc-canvas-source .intestazione { font-size: 1.6em; }
		.gs-loc-titolo2 { margin-top: 6px; }
		/* Con due titoli grandi insieme, più piccoli: altrimenti risultano
		   esagerati (stessa proporzione usata nell'immagine scaricata). */
		#gs-loc-canvas-source:has(.gs-loc-titolo2) .nome { font-size: 1.6em; }
		.doc-prenota-riga { text-align: center; margin: 26px 0 0; }
		.doc-prenota {
			display: inline-block; font-family: var(--sans); font-size: 14px; font-weight: 700; letter-spacing: .04em;
			color: #fffaf0; text-decoration: none; background: linear-gradient(180deg, var(--gold-line), var(--gold-deep));
			border-radius: 8px; padding: 13px 28px; box-shadow: 0 6px 14px -6px rgba(90,60,10,.5);
		}
	</style>
</head>
<body>
	<div class="page">
		<div class="diploma-wrap">
			<div class="diploma" id="gs-loc-canvas-source" data-loc="<?php echo (int) $id; ?>">
				<span class="corner tl"></span><span class="corner tr"></span>
				<span class="corner bl"></span><span class="corner br"></span>

				<p class="eyebrow-top">Accademia della Sfoglia</p>

				<?php if ( $ha_sigillo ) : ?>
					<div class="sigillo-wrap">
						<img class="sigillo" src="<?php echo esc_url( $sigillo ); ?>" alt="Marchio Accademia della Sfoglia">
					</div>
				<?php endif; ?>

				<?php if ( $d['intestazione'] ) : ?>
					<p class="intestazione"><?php echo esc_html( $d['intestazione'] ); ?></p>
				<?php endif; ?>

				<?php if ( $d['sottotitolo'] ) : ?>
					<div class="chip"><?php echo esc_html( $d['sottotitolo'] ); ?></div>
				<?php endif; ?>

				<div class="divider">
					<span class="line"></span>
					<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
						<rect x="8" y="21" width="32" height="6" rx="3" fill="currentColor"/>
						<circle cx="6" cy="24" r="5" fill="currentColor"/>
						<circle cx="42" cy="24" r="5" fill="currentColor"/>
					</svg>
					<span class="line"></span>
				</div>

				<?php if ( $d['titolo'] ) : ?>
					<div class="nome"><?php echo esc_html( $d['titolo'] ); ?></div>
				<?php endif; ?>

				<?php if ( $d['titolo2'] ) : ?>
					<div class="nome gs-loc-titolo2"><?php echo esc_html( $d['titolo2'] ); ?></div>
				<?php endif; ?>

				<?php if ( $d['foto'] ) : ?>
					<div class="doc-foto-riga">
						<?php foreach ( $d['foto'] as $url ) : ?>
							<img class="doc-foto" src="<?php echo esc_url( $url ); ?>" alt="">
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( $d['testo'] ) : ?>
					<p class="testo"><?php echo nl2br( esc_html( $d['testo'] ) ); ?></p>
				<?php endif; ?>

				<?php if ( $d['link'] ) : ?>
					<p class="doc-prenota-riga"><a class="doc-prenota" href="<?php echo esc_url( $d['link'] ); ?>" target="_blank" rel="noopener">📅 Per prenotarti clicca qui</a></p>
				<?php endif; ?>
			</div>

			<p class="noprint">
				<button onclick="window.print()">🖨️ Stampa / Salva come PDF</button>
				<button type="button" class="gs-loc-scarica-immagine" data-formato="png" data-loc="<?php echo (int) $id; ?>">📸 Scarica PNG</button>
				<button type="button" class="gs-loc-scarica-immagine" data-formato="jpeg" data-loc="<?php echo (int) $id; ?>">📷 Scarica JPG</button>
				<button type="button" class="gs-loc-condividi" data-formato="png">📤 Condividi</button>
				<span class="gs-loc-condividi-msg gs-richiesta-esito"></span>
			</p>
			<?php if ( gs_can_manage() ) : ?>
				<p class="noprint">
					<button type="button" class="gs-loc-link-condivisibile" data-loc="<?php echo (int) $id; ?>">🔗 Prepara link per Facebook</button>
					<span class="gs-loc-link-msg gs-richiesta-esito"></span>
					<br><span class="gs-hint">Prepara l'anteprima e copia il link pubblico da incollare in un post — chi lo apre vede questa pagina, con il pulsante "Per prenotarti" funzionante.</span>
				</p>
			<?php endif; ?>
		</div>
	</div>
	<?php
	// Questa pagina esce subito su admin_init (vedi gs_handle_locandina_stampa),
	// prima che WordPress carichi normalmente jQuery/gaming.js sulle pagine del
	// pannello: senza queste righe i pulsanti "Scarica PNG/JPG" non hanno nessun
	// gestore di clic collegato e la pagina si limita ad aprirsi, senza scaricare
	// nulla. wp_print_scripts() li stampa subito, senza bisogno di wp_footer().
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'gaming-sfogline', GS_URL . 'assets/js/gaming.js', array( 'jquery' ), GS_VERSION, true );
	wp_localize_script( 'gaming-sfogline', 'GS_AJAX', array(
		'url'   => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'gs_ajax' ),
	) );
	wp_print_scripts();
	?>
</body>
</html><?php
	return ob_get_clean();
}

/** Intercetta la richiesta di stampa (?gs_locandina=ID). */
add_action( 'admin_init', 'gs_handle_locandina_stampa' );
function gs_handle_locandina_stampa() {
	if ( empty( $_GET['gs_locandina'] ) ) {
		return;
	}
	$id = (int) $_GET['gs_locandina'];
	if ( 'gs_locandina' !== get_post_type( $id ) ) {
		wp_die( 'Documento non trovato.' );
	}
	check_admin_referer( 'gs_locandina_' . $id );
	if ( ! gs_can_manage() ) {
		wp_die( 'Non hai il permesso di vedere questo documento.' );
	}
	header( 'Content-Type: text/html; charset=utf-8' );
	echo gs_locandina_html( $id );
	exit;
}

/**
 * Pagina pubblica (?gs_loc=ID), senza login e senza nonce: pensata per essere
 * condivisa su Facebook/Instagram. Mostra la stessa pagina di sempre, con gli
 * stessi tag Open Graph che i social leggono per l'anteprima — funziona solo
 * per documenti pubblicati, mai per quelli nel cestino.
 */
add_action( 'template_redirect', 'gs_handle_locandina_pubblica' );
function gs_handle_locandina_pubblica() {
	if ( empty( $_GET['gs_loc'] ) ) {
		return;
	}
	$id = (int) $_GET['gs_loc'];
	if ( 'gs_locandina' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
		wp_die( 'Documento non trovato o non più disponibile.', '', array( 'response' => 404 ) );
	}
	header( 'Content-Type: text/html; charset=utf-8' );
	echo gs_locandina_html( $id );
	exit;
}

// -----------------------------------------------------------------------------
// AJAX — creazione, modifica, foto, cestino
// -----------------------------------------------------------------------------

add_action( 'wp_ajax_gs_locandina_crea', 'gs_ajax_locandina_crea' );
function gs_ajax_locandina_crea() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	if ( '' === $titolo ) { wp_send_json_error( array( 'message' => 'Scrivi un titolo.' ) ); }

	$id = wp_insert_post( array(
		'post_type'    => 'gs_locandina',
		'post_status'  => 'publish',
		'post_title'   => $titolo,
		'post_content' => isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '',
	) );
	if ( is_wp_error( $id ) || ! $id ) { wp_send_json_error( array( 'message' => 'Errore, riprova.' ) ); }

	update_post_meta( $id, 'gs_loc_intestazione', isset( $_POST['intestazione'] ) ? sanitize_text_field( wp_unslash( $_POST['intestazione'] ) ) : '' );
	update_post_meta( $id, 'gs_loc_sottotitolo', isset( $_POST['sottotitolo'] ) ? sanitize_text_field( wp_unslash( $_POST['sottotitolo'] ) ) : '' );
	update_post_meta( $id, 'gs_loc_titolo2', isset( $_POST['titolo2'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo2'] ) ) : '' );
	update_post_meta( $id, 'gs_loc_link', isset( $_POST['link'] ) ? esc_url_raw( wp_unslash( $_POST['link'] ) ) : '' );
	update_post_meta( $id, 'gs_loc_foto', array() );

	// Foto facoltativa già in fase di creazione: un nuovo caricamento (campo "foto"
	// nel form) oppure una scelta dalla libreria media (attachment_id in "foto_attachment").
	gs_locandina_foto_da_richiesta( $id );

	wp_send_json_success( array( 'message' => 'Documento creato.', 'id' => $id ) );
}

/**
 * Aggiunge a un documento la foto arrivata con la richiesta corrente, se
 * c'è: un caricamento diretto (campo $_FILES['foto']) oppure una scelta
 * dalla libreria media ($_POST['foto_attachment']). Usata alla creazione,
 * dove la foto è sempre facoltativa: non genera errori se manca o non è
 * valida, il documento va comunque creato.
 */
function gs_locandina_foto_da_richiesta( $id ) {
	$url = '';

	if ( ! empty( $_FILES['foto']['name'] ) ) {
		$up = function_exists( 'gs_msg_upload' ) ? gs_msg_upload( 'foto' ) : null;
		if ( $up && ! is_wp_error( $up ) && 'image' === $up['type'] ) {
			$url = $up['url'];
		}
	} elseif ( ! empty( $_POST['foto_attachment'] ) ) {
		$attachment_id = (int) $_POST['foto_attachment'];
		if ( $attachment_id && 'attachment' === get_post_type( $attachment_id ) && wp_attachment_is_image( $attachment_id ) ) {
			$url = wp_get_attachment_url( $attachment_id );
		}
	}

	if ( ! $url ) {
		return false;
	}
	$foto   = get_post_meta( $id, 'gs_loc_foto', true );
	$foto   = is_array( $foto ) ? $foto : array();
	$foto[] = $url;
	update_post_meta( $id, 'gs_loc_foto', $foto );
	return true;
}

add_action( 'wp_ajax_gs_locandina_modifica', 'gs_ajax_locandina_modifica' );
function gs_ajax_locandina_modifica() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['loc'] ) ? (int) $_POST['loc'] : 0;
	if ( 'gs_locandina' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Documento non valido.' ) ); }

	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	if ( '' === $titolo ) { wp_send_json_error( array( 'message' => 'Scrivi un titolo.' ) ); }

	wp_update_post( array(
		'ID'           => $id,
		'post_title'   => $titolo,
		'post_content' => isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '',
	) );
	update_post_meta( $id, 'gs_loc_intestazione', isset( $_POST['intestazione'] ) ? sanitize_text_field( wp_unslash( $_POST['intestazione'] ) ) : '' );
	update_post_meta( $id, 'gs_loc_sottotitolo', isset( $_POST['sottotitolo'] ) ? sanitize_text_field( wp_unslash( $_POST['sottotitolo'] ) ) : '' );
	update_post_meta( $id, 'gs_loc_titolo2', isset( $_POST['titolo2'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo2'] ) ) : '' );
	update_post_meta( $id, 'gs_loc_link', isset( $_POST['link'] ) ? esc_url_raw( wp_unslash( $_POST['link'] ) ) : '' );

	wp_send_json_success( array( 'message' => 'Documento aggiornato.' ) );
}

add_action( 'wp_ajax_gs_locandina_foto_aggiungi', 'gs_ajax_locandina_foto_aggiungi' );
function gs_ajax_locandina_foto_aggiungi() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['loc'] ) ? (int) $_POST['loc'] : 0;
	if ( 'gs_locandina' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Documento non valido.' ) ); }

	$up = function_exists( 'gs_msg_upload' ) ? gs_msg_upload( 'foto' ) : new WP_Error( 'no_upload', 'Caricamento non disponibile.' );
	if ( is_wp_error( $up ) ) { wp_send_json_error( array( 'message' => $up->get_error_message() ) ); }
	if ( ! $up || 'image' !== $up['type'] ) { wp_send_json_error( array( 'message' => 'Carica una foto (non un video).' ) ); }

	$foto   = get_post_meta( $id, 'gs_loc_foto', true );
	$foto   = is_array( $foto ) ? $foto : array();
	$foto[] = $up['url'];
	update_post_meta( $id, 'gs_loc_foto', $foto );

	wp_send_json_success( array( 'message' => 'Foto aggiunta.', 'url' => $up['url'] ) );
}

/** Aggiunge una foto già presente nella libreria media del sito (nessun nuovo caricamento). */
add_action( 'wp_ajax_gs_locandina_foto_libreria', 'gs_ajax_locandina_foto_libreria' );
function gs_ajax_locandina_foto_libreria() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id            = isset( $_POST['loc'] ) ? (int) $_POST['loc'] : 0;
	$attachment_id = isset( $_POST['attachment'] ) ? (int) $_POST['attachment'] : 0;
	if ( 'gs_locandina' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Documento non valido.' ) ); }
	if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) || ! wp_attachment_is_image( $attachment_id ) ) {
		wp_send_json_error( array( 'message' => 'Scegli un\'immagine dalla libreria.' ) );
	}
	$url = wp_get_attachment_url( $attachment_id );
	if ( ! $url ) { wp_send_json_error( array( 'message' => 'Immagine non trovata.' ) ); }

	$foto   = get_post_meta( $id, 'gs_loc_foto', true );
	$foto   = is_array( $foto ) ? $foto : array();
	$foto[] = $url;
	update_post_meta( $id, 'gs_loc_foto', $foto );

	wp_send_json_success( array( 'message' => 'Foto aggiunta dalla libreria.', 'url' => $url ) );
}

add_action( 'wp_ajax_gs_locandina_foto_rimuovi', 'gs_ajax_locandina_foto_rimuovi' );
function gs_ajax_locandina_foto_rimuovi() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id  = isset( $_POST['loc'] ) ? (int) $_POST['loc'] : 0;
	$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
	if ( 'gs_locandina' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Documento non valido.' ) ); }

	$foto = get_post_meta( $id, 'gs_loc_foto', true );
	$foto = is_array( $foto ) ? array_values( array_filter( $foto, function ( $u ) use ( $url ) {
		return $u !== $url;
	} ) ) : array();
	update_post_meta( $id, 'gs_loc_foto', $foto );

	wp_send_json_success( array( 'message' => 'Foto tolta.' ) );
}

/**
 * Salva come allegato del sito l'immagine generata dal canvas (la stessa di
 * "Scarica"/"Condividi"), e la ricorda come anteprima per la condivisione
 * social (meta gs_loc_og_image) — serve perché Facebook/Instagram leggono i
 * tag Open Graph della pagina senza eseguire JavaScript, quindi non possono
 * "vedere" un'immagine disegnata solo nel browser: va salvata una volta come
 * file vero, con un URL stabile.
 */
add_action( 'wp_ajax_gs_locandina_salva_anteprima', 'gs_ajax_locandina_salva_anteprima' );
function gs_ajax_locandina_salva_anteprima() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['loc'] ) ? (int) $_POST['loc'] : 0;
	if ( 'gs_locandina' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Documento non valido.' ) ); }

	$up = function_exists( 'gs_msg_upload' ) ? gs_msg_upload( 'anteprima' ) : new WP_Error( 'no_upload', 'Caricamento non disponibile.' );
	if ( is_wp_error( $up ) ) { wp_send_json_error( array( 'message' => $up->get_error_message() ) ); }
	if ( ! $up || 'image' !== $up['type'] ) { wp_send_json_error( array( 'message' => 'Errore nella preparazione dell\'anteprima.' ) ); }

	update_post_meta( $id, 'gs_loc_og_image', $up['url'] );

	wp_send_json_success( array(
		'message'  => 'Link pronto per la condivisione.',
		'url'      => gs_locandina_url_pubblico( $id ),
		'immagine' => $up['url'],
	) );
}

add_action( 'wp_ajax_gs_locandina_elimina', 'gs_ajax_locandina_elimina' );
function gs_ajax_locandina_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['loc'] ) ? (int) $_POST['loc'] : 0;
	if ( 'gs_locandina' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Documento non valido.' ) ); }
	wp_trash_post( $id );
	wp_send_json_success( array( 'message' => 'Spostato nel cestino.' ) );
}

add_action( 'wp_ajax_gs_locandina_ripristina', 'gs_ajax_locandina_ripristina' );
function gs_ajax_locandina_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['loc'] ) ? (int) $_POST['loc'] : 0;
	if ( 'gs_locandina' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Documento non valido.' ) ); }
	wp_untrash_post( $id );
	wp_send_json_success( array( 'message' => 'Ripristinato.' ) );
}

// -----------------------------------------------------------------------------
// Logo personalizzato dei documenti (diploma, certificato, locandine)
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_certificato_logo_salva', 'gs_ajax_certificato_logo_salva' );
function gs_ajax_certificato_logo_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$attachment_id = isset( $_POST['attachment'] ) ? (int) $_POST['attachment'] : 0;
	if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) || ! wp_attachment_is_image( $attachment_id ) ) {
		wp_send_json_error( array( 'message' => 'Scegli un\'immagine dalla libreria.' ) );
	}
	$url = wp_get_attachment_url( $attachment_id );
	if ( ! $url ) { wp_send_json_error( array( 'message' => 'Immagine non trovata.' ) ); }

	$s = gs_settings();
	$s['certificato_logo'] = $url;
	update_option( GS_OPTION, $s );
	gs_settings( true );

	wp_send_json_success( array( 'message' => 'Logo aggiornato su diplomi, certificati e locandine.', 'url' => $url ) );
}

add_action( 'wp_ajax_gs_certificato_logo_reset', 'gs_ajax_certificato_logo_reset' );
function gs_ajax_certificato_logo_reset() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$s = gs_settings();
	$s['certificato_logo'] = '';
	update_option( GS_OPTION, $s );
	gs_settings( true );

	wp_send_json_success( array( 'message' => 'Ripristinato il logo originale di ogni documento.' ) );
}

// -----------------------------------------------------------------------------
// Pannello — creazione e gestione (titolare/collaboratori)
// -----------------------------------------------------------------------------
function gs_pannello_locandine() {
	if ( ! gs_can_manage() ) {
		return;
	}
	echo gs_box_open( '🖼️ Diplomi e Locandine', '', 'gs-box-locandine' );
	echo gs_sezione_aiuto( 'Crea diplomi e locandine a piacimento con la stessa grafica dei diplomi dell\'Accademia: intestazione (es. "Maestra Rina Poletti"), un titolo grande (e, se serve, un secondo titolo grande sotto il primo), il testo libero (es. il programma di un corso) e qualche foto. Ogni documento creato resta salvato qui: riaprilo quando vuoi per rivederlo, modificarlo, stamparlo/salvarlo in PDF, scaricarlo come immagine pronta per Facebook e Instagram, oppure condividerlo direttamente con il pulsante "Condividi" (apre il menu di condivisione del telefono/computer: scegli tu l\'app, es. Instagram o Facebook). Con "🔗 Prepara link per Facebook" ottieni invece un link pubblico da incollare in un post: chi lo apre vede la pagina vera, con il pulsante "Per prenotarti" funzionante (visibile solo a te, non ai visitatori).' );

	$s_logo         = gs_settings();
	$logo_personale = ! empty( $s_logo['certificato_logo'] );
	echo '<div style="background:var(--gs-uovo);padding:12px;border-radius:6px;margin-bottom:14px" class="gs-form-logo-certificati">';
	echo '<strong>Logo dei documenti</strong>';
	echo '<p class="gs-hint">Vale per Diplomi e Locandine, il certificato di Percorso Guidato e il diploma dei Corsi Online.</p>';
	echo '<p><img src="' . esc_url( gs_certificato_logo_url() ) . '" alt="Logo attuale" style="width:80px;height:80px;border-radius:50%;vertical-align:middle;border:1px solid var(--gs-bordo)"> ';
	echo '<button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-logo-certificati-scegli">📁 Cambia logo dalla libreria</button> ';
	if ( $logo_personale ) {
		echo '<button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-logo-certificati-reset">Ripristina logo originale</button> ';
	}
	echo '<span class="gs-logo-certificati-msg gs-richiesta-esito"></span></p>';
	echo '</div>';

	echo '<form class="gs-form gs-form-locandina" onsubmit="return false" style="background:var(--gs-uovo);padding:12px;border-radius:6px;margin-bottom:14px">';
	echo '<strong>Nuovo documento</strong>';
	echo '<p><label>Intestazione (in alto, sotto il logo)<br><input type="text" name="intestazione" value="MAESTRA RINA POLETTI" style="width:100%"></label></p>';
	echo '<p><label>Titolo grande<br><input type="text" name="titolo" autocomplete="off" style="width:100%" required placeholder="Es. un nome, o il titolo del corso"></label></p>';
	echo '<p><label>Secondo titolo grande (facoltativo, sotto il primo)<br><input type="text" name="titolo2" style="width:100%" placeholder="Es. una seconda riga del titolo"></label></p>';
	echo '<p><label>Etichetta (facoltativa)<br><input type="text" name="sottotitolo" style="width:100%" placeholder="Es. Diploma, Nuovo corso in partenza…"></label></p>';
	echo '<p><label>Testo libero / programma del corso<br><textarea name="testo" rows="4" style="width:100%"></textarea></label></p>';
	echo '<p><label>Link di prenotazione (facoltativo)<br><input type="url" name="link" style="width:100%" placeholder="https://…"></label> <span class="gs-hint">Se lo compili, sulla pagina compare un pulsante "Per prenotarti clicca qui" verso questo indirizzo.</span></p>';
	echo '<p><label>Foto (facoltativa)<br><input type="file" class="gs-loc-nuovo-foto-file" accept="image/*"></label> <button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-loc-nuovo-foto-libreria">📁 Scegli dalla libreria</button> <span class="gs-loc-nuovo-foto-scelta gs-hint"></span><input type="hidden" name="foto_attachment" class="gs-loc-nuovo-foto-attachment" value=""></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-loc-crea">Crea documento</button> <span class="gs-loc-msg gs-richiesta-esito"></span></p>';
	echo '</form>';

	$doc = gs_locandine_tutte();
	echo '<h4>Documenti creati (' . count( $doc ) . ')</h4>';
	if ( ! $doc ) {
		echo '<p class="gs-hint">Nessun documento ancora creato.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="10">';
		foreach ( $doc as $p ) {
			$d = gs_locandina_get( $p->ID );
			echo '<details class="gs-inbox-item" data-loc="' . (int) $p->ID . '">';
			echo '<summary class="gs-inbox-oggetto">' . esc_html( $d['titolo'] ) . ( $d['sottotitolo'] ? ' — ' . esc_html( $d['sottotitolo'] ) : '' ) . '</summary>';
			echo '<div class="gs-inbox-corpo">';

			echo '<form class="gs-form gs-form-locandina-modifica" data-loc="' . (int) $p->ID . '" onsubmit="return false">';
			echo '<p><label>Intestazione<br><input type="text" name="intestazione" value="' . esc_attr( $d['intestazione'] ) . '" style="width:100%"></label></p>';
			echo '<p><label>Titolo grande<br><input type="text" name="titolo" value="' . esc_attr( $d['titolo'] ) . '" style="width:100%" required></label></p>';
			echo '<p><label>Secondo titolo grande (facoltativo, sotto il primo)<br><input type="text" name="titolo2" value="' . esc_attr( $d['titolo2'] ) . '" style="width:100%"></label></p>';
			echo '<p><label>Etichetta<br><input type="text" name="sottotitolo" value="' . esc_attr( $d['sottotitolo'] ) . '" style="width:100%"></label></p>';
			echo '<p><label>Testo libero / programma<br><textarea name="testo" rows="4" style="width:100%">' . esc_textarea( $d['testo'] ) . '</textarea></label></p>';
			echo '<p><label>Link di prenotazione (facoltativo)<br><input type="url" name="link" value="' . esc_attr( $d['link'] ) . '" style="width:100%" placeholder="https://…"></label></p>';
			echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-loc-modifica-salva">✏️ Salva modifiche</button> <span class="gs-loc-modifica-msg gs-richiesta-esito"></span></p>';
			echo '</form>';

			echo '<div class="gs-loc-foto-lista">';
			foreach ( $d['foto'] as $url ) {
				echo '<span class="gs-loc-foto-item"><img src="' . esc_url( $url ) . '" alt="" style="max-width:90px;max-height:90px;border-radius:6px;vertical-align:middle">'
					. ' <button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-loc-foto-rimuovi" data-loc="' . (int) $p->ID . '" data-url="' . esc_attr( $url ) . '">Togli</button></span> ';
			}
			echo '</div>';
			echo '<p><label>Aggiungi una foto<br><input type="file" class="gs-loc-foto-file" accept="image/*"></label> <button type="button" class="gs-btn gs-btn-sm gs-loc-foto-aggiungi" data-loc="' . (int) $p->ID . '">Carica</button> <button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-loc-foto-libreria" data-loc="' . (int) $p->ID . '">📁 Scegli dalla libreria</button> <span class="gs-loc-foto-msg gs-richiesta-esito"></span></p>';

			echo '<p>';
			echo '<a class="gs-btn gs-btn-sm" href="' . esc_url( gs_locandina_url( $p->ID ) ) . '" target="_blank">🖨️ Vedi / stampa</a> ';
			echo '<button type="button" class="gs-btn gs-btn-sm gs-loc-scarica-immagine" data-formato="png" data-loc="' . (int) $p->ID . '" data-url="' . esc_url( gs_locandina_url( $p->ID ) ) . '">📸 Scarica PNG</button> ';
			echo '<button type="button" class="gs-btn gs-btn-sm gs-loc-scarica-immagine" data-formato="jpeg" data-loc="' . (int) $p->ID . '" data-url="' . esc_url( gs_locandina_url( $p->ID ) ) . '">📷 Scarica JPG</button> ';
			echo '<button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-loc-elimina" data-loc="' . (int) $p->ID . '">Elimina</button>';
			echo ' <span class="gs-loc-row-msg gs-richiesta-esito"></span>';
			echo '</p>';
			echo '</div></details>';
		}
		echo '</div>';
	}

	$trash = gs_locandine_cestino();
	echo '<div class="gs-sezione-cestino">';
	echo '<h4 class="gs-titolo-cestino">🗑️ Cestino documenti</h4>';
	if ( ! $trash ) {
		echo '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		echo '<table class="gs-table gs-paginate" data-per-page="8"><thead><tr>' . gs_cestino_th_checkbox() . '<th>Titolo</th><th></th></tr></thead><tbody>';
		foreach ( $trash as $p ) {
			echo '<tr data-loc="' . (int) $p->ID . '">' . gs_cestino_td_checkbox( $p->ID ) . '<td>' . esc_html( get_the_title( $p ) ) . '</td>';
			echo '<td><button class="gs-btn gs-btn-sm gs-btn-verde gs-loc-ripristina" data-loc="' . (int) $p->ID . '">Ripristina</button> <span class="gs-loc-trow-msg gs-richiesta-esito"></span></td></tr>';
		}
		echo '</tbody></table>';
		echo gs_cestino_azioni_bulk( 'gs_locandina' );
	}
	echo '</div>';

	echo gs_box_close();
}
