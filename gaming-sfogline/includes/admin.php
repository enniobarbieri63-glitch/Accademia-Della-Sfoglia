<?php
/**
 * admin.php — Pannello di amministrazione del plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Menu
// -----------------------------------------------------------------------------
add_action( 'admin_menu', 'gs_admin_menu' );

function gs_admin_menu() {
	add_menu_page( 'Gaming Sfogline', 'Gaming Sfogline', 'manage_options', 'gs-generale', 'gs_page_generale', 'dashicons-carrot', 26 );

	// Voce di PRIMO LIVELLO a parte (non dentro "Gaming Sfogline"), in cima
	// al menu di WordPress — richiesta 2026-08-08: raggiungibile subito,
	// senza aprire prima "Gaming Sfogline". Capacità gs_manage_gaming (non
	// manage_options): la vedono sia il titolare sia i collaboratori a cui è
	// stato dato il permesso di gestione (gli stessi che possono già
	// accedere a wp-admin, vedi l'esenzione in gs_wp_admin_va_bloccato_per()).
	if ( function_exists( 'gs_pn_pagina' ) ) {
		add_menu_page( 'Pannello Generale', '🚀 Pannello Generale', 'gs_manage_gaming', 'gs-pannello-nuovo', 'gs_pn_pagina', 'dashicons-dashboard', 3 );
	}

	add_submenu_page( 'gs-generale', 'Plancia Generale', '🎛️ Plancia Generale', 'manage_options', 'gs-generale', 'gs_page_generale' );
	// In cima al menu "Gaming Sfogline", subito dopo la Plancia Generale
	// (richiesta 2026-08-08): le due sezioni più recenti e più usate, senza
	// doverle cercare in fondo all'elenco insieme a tutte le altre. Qui sono
	// solo COLLEGAMENTI alla voce di primo livello sopra (o alla zona della
	// Plancia classica) — non una seconda registrazione della pagina.
	if ( function_exists( 'gs_pn_pagina' ) ) {
		add_submenu_page( 'gs-generale', 'Pannello Generale', '🚀 Pannello Generale', 'manage_options', 'admin.php?page=gs-pannello-nuovo' );
	}
	add_submenu_page( 'gs-generale', 'Artigiani della Pasta', '🍝 Artigiani della Pasta', 'manage_options', 'admin.php?page=gs-generale#gs-zona-artigiani' );
	add_submenu_page( 'gs-generale', 'Scuole di Cucina', '🎓 Scuole di Cucina', 'manage_options', 'admin.php?page=gs-generale#gs-zona-scuole' );
	add_submenu_page( 'gs-generale', 'Panoramica', 'Panoramica', 'manage_options', 'gs-panoramica', 'gs_page_panoramica' );
	add_submenu_page( 'gs-generale', 'Richieste di Iscrizione', 'Richieste di Iscrizione', 'manage_options', 'gs-richieste', 'gs_page_richieste' );

	// CPT gestiti dentro il menu del plugin.
	add_submenu_page( 'gs-generale', 'Sfide', 'Sfide', 'manage_options', 'edit.php?post_type=gs_sfida' );
	add_submenu_page( 'gs-generale', 'Ingredienti Segreti', 'Ingredienti Segreti', 'manage_options', 'edit.php?post_type=gs_ingrediente' );
	add_submenu_page( 'gs-generale', 'Guide Stagionali', 'Guide Stagionali', 'manage_options', 'edit.php?post_type=gs_barometro' );
	add_submenu_page( 'gs-generale', 'Consigli', 'Consigli', 'manage_options', 'edit.php?post_type=gs_consiglio' );

	add_submenu_page( 'gs-generale', 'Premio di Fine Anno', 'Premio di Fine Anno', 'manage_options', 'gs-premio', 'gs_page_premio' );
	add_submenu_page( 'gs-generale', 'Correggi Punti', 'Correggi Punti', 'manage_options', 'gs-correggi', 'gs_page_correggi' );
	add_submenu_page( 'gs-generale', 'Impostazioni', 'Impostazioni', 'manage_options', 'gs-impostazioni', 'gs_page_impostazioni' );

	// Sezioni della Plancia (collegamenti diretti alle zone).
	add_submenu_page( 'gs-generale', 'Stato Generale', 'Stato Generale', 'manage_options', 'admin.php?page=gs-generale#gs-zona-stato-generale' );
	add_submenu_page( 'gs-generale', 'Visibilità sezioni e permessi', 'Visibilità sezioni e permessi', 'manage_options', 'admin.php?page=gs-generale#gs-zona-visibilita' );
	add_submenu_page( 'gs-generale', 'Messaggio di benvenuto', 'Messaggio di benvenuto', 'manage_options', 'admin.php?page=gs-generale#gs-zona-benvenuto' );
	add_submenu_page( 'gs-generale', 'Come funziona il Percorso', 'Come funziona il Percorso', 'manage_options', 'admin.php?page=gs-generale#gs-zona-come-funziona' );
	add_submenu_page( 'gs-generale', 'Abbonamenti', 'Abbonamenti', 'manage_options', 'admin.php?page=gs-generale#gs-zona-abbonamenti' );
	add_submenu_page( 'gs-generale', 'Iscrizioni delle sfogline', 'Iscrizioni delle sfogline', 'manage_options', 'admin.php?page=gs-generale#gs-zona-iscrizioni' );
	add_submenu_page( 'gs-generale', 'Contenuti del percorso', 'Contenuti del percorso', 'manage_options', 'admin.php?page=gs-generale#gs-zona-contenuti' );
	add_submenu_page( 'gs-generale', 'Vetrina pubblica', 'Vetrina pubblica', 'manage_options', 'admin.php?page=gs-generale#gs-zona-vetrina' );
	add_submenu_page( 'gs-generale', 'Cerca sfoglina e recupera file', 'Cerca sfoglina e recupera file', 'manage_options', 'admin.php?page=gs-generale#gs-zona-cerca' );
	add_submenu_page( 'gs-generale', 'Messaggi alle sfogline', 'Messaggi alle sfogline', 'manage_options', 'admin.php?page=gs-generale#gs-zona-messaggi' );
	add_submenu_page( 'gs-generale', 'Aeroplanino', 'Aeroplanino', 'manage_options', 'admin.php?page=gs-generale#gs-zona-aeroplanino' );
	add_submenu_page( 'gs-generale', 'Palloncini', 'Palloncini', 'manage_options', 'admin.php?page=gs-generale#gs-zona-palloncini' );
	add_submenu_page( 'gs-generale', 'Palloncino Gigante', 'Palloncino Gigante', 'manage_options', 'admin.php?page=gs-generale#gs-zona-palloncino-gigante' );
	add_submenu_page( 'gs-generale', 'Messaggi di ogni sfoglina', 'Messaggi di ogni sfoglina', 'manage_options', 'admin.php?page=gs-generale#gs-zona-messaggi-sfogline' );
	add_submenu_page( 'gs-generale', 'Notifiche per sfoglina', 'Notifiche per sfoglina', 'manage_options', 'admin.php?page=gs-generale#gs-zona-notifiche-pref' );
	add_submenu_page( 'gs-generale', 'Percorsi Guidati', 'Percorsi Guidati', 'manage_options', 'admin.php?page=gs-generale#gs-zona-percorsi-lezioni' );
	add_submenu_page( 'gs-generale', 'Dicono di Noi', 'Dicono di Noi', 'manage_options', 'admin.php?page=gs-generale#gs-zona-testimonianze' );
	add_submenu_page( 'gs-generale', 'Madrina & Allieva', 'Madrina & Allieva', 'manage_options', 'admin.php?page=gs-generale#gs-zona-madrine' );
	add_submenu_page( 'gs-generale', 'L\'Esperto Risponde', 'L\'Esperto Risponde', 'manage_options', 'admin.php?page=gs-generale#gs-zona-esperto' );
	add_submenu_page( 'gs-generale', 'Conversazioni private', 'Conversazioni private', 'manage_options', 'admin.php?page=gs-generale#gs-zona-conversazioni' );
	add_submenu_page( 'gs-generale', 'Compleanni', 'Compleanni', 'manage_options', 'admin.php?page=gs-generale#gs-zona-compleanni' );
	add_submenu_page( 'gs-generale', 'Calendario Corsi', 'Calendario Corsi', 'manage_options', 'admin.php?page=gs-generale#gs-zona-calendario' );
	add_submenu_page( 'gs-generale', 'Diplomi e Locandine', 'Diplomi e Locandine', 'manage_options', 'admin.php?page=gs-generale#gs-zona-locandine' );
	add_submenu_page( 'gs-generale', 'Ricette per il Libro', 'Ricette per il Libro', 'manage_options', 'admin.php?page=gs-generale#gs-zona-ricette-libro' );
	add_submenu_page( 'gs-generale', 'Premi per Traguardo', 'Premi per Traguardo', 'manage_options', 'admin.php?page=gs-generale#gs-zona-premi-traguardi' );
	add_submenu_page( 'gs-generale', 'FAQ - Domande', 'FAQ - Domande', 'manage_options', 'admin.php?page=gs-generale#gs-zona-faq' );
	add_submenu_page( 'gs-generale', 'Novità', 'Novità', 'manage_options', 'admin.php?page=gs-generale#gs-zona-novita' );
	add_submenu_page( 'gs-generale', 'Aiuto e suggerimenti', 'Aiuto e suggerimenti', 'manage_options', 'admin.php?page=gs-generale#gs-zona-aiuto' );
	add_submenu_page( 'gs-generale', 'Biografie da approvare', 'Biografie da approvare', 'manage_options', 'admin.php?page=gs-generale#gs-zona-biografie' );
	add_submenu_page( 'gs-generale', 'Aspetto «Le Sfogline»', 'Aspetto «Le Sfogline»', 'manage_options', 'admin.php?page=gs-generale#gs-zona-aspetto' );
	add_submenu_page( 'gs-generale', 'Caroselli per la Home Page', 'Caroselli per la Home Page', 'manage_options', 'admin.php?page=gs-generale#gs-zona-caroselli' );
	add_submenu_page( 'gs-generale', 'Posta interna', 'Posta interna', 'manage_options', 'admin.php?page=gs-generale#gs-zona-posta' );
	add_submenu_page( 'gs-generale', 'Blackout', 'Blackout', 'manage_options', 'admin.php?page=gs-generale#gs-zona-blackout' );
	add_submenu_page( 'gs-generale', 'Foto e video', 'Foto e video', 'manage_options', 'admin.php?page=gs-generale#gs-zona-media' );
	add_submenu_page( 'gs-generale', 'Backup dei file', 'Backup dei file', 'manage_options', 'admin.php?page=gs-generale#gs-zona-backup' );
	add_submenu_page( 'gs-generale', 'Esporta i dati del percorso', 'Esporta i dati del percorso', 'manage_options', 'admin.php?page=gs-generale#gs-zona-export' );
	add_submenu_page( 'gs-generale', 'Diagnostica e stato di salute', 'Diagnostica e stato di salute', 'manage_options', 'admin.php?page=gs-generale#gs-zona-diagnostica' );
}

// -----------------------------------------------------------------------------
// Asset nella plancia generale (riusa CSS/JS del front-end + AJAX)
// -----------------------------------------------------------------------------
add_action( 'admin_enqueue_scripts', 'gs_admin_assets' );
function gs_admin_assets( $hook ) {
	$hook = (string) $hook;
	// "gs-generale": la Plancia classica e le sue sottopagine. "gs-pannello-nuovo":
	// il Pannello Generale, che dalla 3.187.2 è anche una voce di PRIMO LIVELLO
	// (hook "toplevel_page_gs-pannello-nuovo", non contiene "gs-generale") —
	// senza questo secondo controllo restava senza gaming.js/CSS/GS_AJAX e i
	// pulsanti delle sezioni caricate via AJAX non avrebbero più funzionato.
	if ( false === strpos( $hook, 'gs-generale' ) && false === strpos( $hook, 'gs-pannello-nuovo' ) ) {
		return;
	}
	wp_enqueue_style( 'gaming-sfogline', GS_URL . 'assets/css/gaming.css', array(), GS_VERSION );
	// Caratteri per la veste "Atelier" della Regia degli Iscritti ai Corsi
	// (Cormorant Garamond per i titoli, Manrope per il corpo del testo) —
	// scelta grafica confermata da Ennio il 18/08/2026 tra due anteprime.
	wp_enqueue_style( 'gs-regia-fonts', 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap', array(), null );
	wp_enqueue_script( 'gaming-sfogline', GS_URL . 'assets/js/gaming.js', array( 'jquery' ), GS_VERSION, true );
	// Libreria media di WordPress: qui in più, non solo sul pannello del sito
	// (richiesto da Ennio il 18/08/2026, per scegliere il logo degli sponsor
	// di Aeroplanino/Palloncini anche dalla Plancia classica e dal Pannello Generale).
	wp_enqueue_media();
	$s_idx = gs_settings();
	wp_localize_script( 'gaming-sfogline', 'GS_AJAX', array(
		'url'       => admin_url( 'admin-ajax.php' ),
		'admin_url' => admin_url(),
		'nonce'     => wp_create_nonce( 'gs_ajax' ),
		'idx_extra' => isset( $s_idx['idx_extra'] ) ? (int) $s_idx['idx_extra'] : 0, // stessa calibrazione salvata dal pannello sul sito
		'dettatura_vocale' => function_exists( 'gs_dettatura_vocale_abilitata' ) ? gs_dettatura_vocale_abilitata() : true,
		'aereo_logo_dimensione' => isset( $s_idx['aereo_logo_dimensione'] ) ? (int) $s_idx['aereo_logo_dimensione'] : 52,
	) );
	$m = gs_media_settings();
	wp_localize_script( 'gaming-sfogline', 'GS_MEDIA', array(
		'comprimi'  => ! empty( $m['comprimi_foto'] ) ? 1 : 0,
		'max_lato'  => (int) ( $m['foto_max_lato'] ?? 1600 ),
		'limite_mb' => (float) ( $m['limite_mb'] ?? 8 ),
	) );
	// Stessa animazione dell'aeroplanino (messaggi/conversazioni non lette) del
	// pannello sul sito: mancava qui, l'unica differenza restante tra le due
	// localizzazioni di script (vedi anche il fix di idx_extra sopra).
	$non_letti = 0;
	if ( function_exists( 'gs_messaggi_non_letti' ) ) {
		$non_letti = (int) gs_messaggi_non_letti( get_current_user_id() );
	}
	$conv_non_letti = 0;
	if ( function_exists( 'gs_conv_non_letti' ) ) {
		$conv_non_letti = (int) gs_conv_non_letti( get_current_user_id() );
	}
	wp_localize_script( 'gaming-sfogline', 'GS_MSG', array(
		'non_letti'      => $non_letti,
		'conv_non_letti' => $conv_non_letti,
		// Solo chi arriva qui è già un amministratore vero (la Plancia Generale
		// è bloccata a tutti gli altri): sempre 1.
		'gestore'        => 1,
	) );
}

/**
 * Il "cartellino": un piccolo avviso a cartellino, con bordo tratteggiato e
 * leggermente ruotato, che compare in alto a una scheda della Plancia SOLO
 * quando c'è davvero qualcosa che aspetta un'azione (approvazioni, risposte,
 * commenti...). Riusa gli stessi conteggi della bacheca di riepilogo
 * (gs_riepilogo_dati() in control-panel.php), passata già calcolata una
 * volta sola per non ripetere le query per ogni scheda.
 */
function gs_cartellino_html( $riepilogo, $chiave ) {
	if ( ! isset( $riepilogo[ $chiave ] ) ) {
		return '';
	}
	list( $conteggio, $etichetta ) = $riepilogo[ $chiave ];
	if ( $conteggio < 1 ) {
		return '';
	}
	return '<span class="gs-cartellino">🔔 ' . (int) $conteggio . ' ' . esc_html( $etichetta ) . '</span>';
}

// -----------------------------------------------------------------------------
// PLANCIA GENERALE — tutto in un'unica schermata, a zone colorate
// -----------------------------------------------------------------------------
function gs_page_generale() {
	$n_sfogline = gs_conta_sfogline_pubbliche();
	$pending    = gs_get_pending_users();
	$n_pending  = count( $pending );
	$n_sfide    = (int) wp_count_posts( 'gs_sfida' )->publish;
	$n_sfoglie  = (int) wp_count_posts( 'gs_sfoglia' )->publish;
	$attiva     = gs_get_active_challenge();
	$riepilogo  = function_exists( 'gs_riepilogo_dati' ) ? gs_riepilogo_dati() : array();
	?>
	<div class="wrap gs-dash">
		<h1>🎛️ Gaming Sfogline — Plancia Generale
			<?php
			// Interruttore rapido del blackout, in prima vista — richiesto
			// da Ennio il 22/08/2026, stesso pulsante e stesso endpoint di
			// quello nella testata del Pannello Generale (pannello-nuovo.php).
			$gs_dash_bo_attivo = function_exists( 'gs_blackout_attivo' ) && gs_blackout_attivo();
			?>
			<button class="gs-btn gs-btn-sm gs-toggle-blackout-rapido<?php echo $gs_dash_bo_attivo ? ' gs-pn-blackout-attivo' : ''; ?>" style="vertical-align:middle;margin-left:14px" title="Oscura o riattiva subito tutto il Gaming per le sfogline">
				<?php echo $gs_dash_bo_attivo ? '🌙 OSCURATO — riattiva' : '🌙 Gaming attivo'; ?>
			</button>
		</h1>
		<p class="gs-versione">Versione plugin: <strong><?php echo esc_html( GS_VERSION ); ?></strong></p>
		<p class="gs-dash-intro">Tutto quello che serve per gestire il portale, in un'unica schermata: ogni zona colorata è uno strumento con la sua descrizione e i suoi pulsanti. Non devi più spostarti tra le pagine.</p>

		<!-- Bacheca di riepilogo: i numeri del giorno -->
		<?php if ( function_exists( 'gs_pannello_riepilogo' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'riepilogo_bacheca' ) ) ) { gs_pannello_riepilogo(); } ?>

		<!-- Statistiche -->
		<div class="gs-dash-stats">
			<div class="gs-dash-card"><span class="n"><?php echo $n_sfogline; ?></span><span class="l">Sfogline registrate</span></div>
			<div class="gs-dash-card <?php echo $n_pending ? 'alert' : ''; ?>"><span class="n"><?php echo $n_pending; ?></span><span class="l">Richieste in attesa</span></div>
			<div class="gs-dash-card"><span class="n"><?php echo $n_sfide; ?></span><span class="l">Sfide pubblicate</span></div>
			<div class="gs-dash-card"><span class="n"><?php echo $n_sfoglie; ?></span><span class="l">Sfoglie inviate</span></div>
		</div>

		<p><?php echo function_exists( 'gs_elenco_sfogline_bottone_html' ) ? gs_elenco_sfogline_bottone_html() : ''; ?></p>

		<div class="gs-dash-grid">

			<!-- ZONA: Cerca sfoglina + cestino (in cima al pannello) -->
			<section data-idx-group="sfogline" class="gs-zone wide" style="--zc:#1f6e37" id="gs-zona-cerca">
				<header class="gs-zone-head">🔍 Cerca sfoglina e recupera file</header>
				<div class="gs-zone-body"><?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'cerca_sfoglina' ) ) { gs_pannello_cerca_sfoglina(); } ?></div>
			</section>

			<!-- ZONA: Regia degli Iscritti ai Corsi (subito sotto gli avvisi in alto,
			     richiesto da Ennio il 18/08/2026 — non "Pianificazione dell'Anno",
			     che è un'altra cosa: quella organizza le DATE, questa le PERSONE) -->
			<section data-idx-group="corsi" class="gs-zone wide" style="--zc:#6b4fa0" id="gs-zona-regia-iscritti">
				<header class="gs-zone-head">🎯 Lista degli Iscritti ai Corsi</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_regia_iscritti' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'regia_iscritti' ) ) ) { gs_pannello_regia_iscritti(); } ?></div>
			</section>

			<!-- ZONA: Pianificazione dell'Anno (subito sotto gli avvisi in alto) -->
			<section data-idx-group="corsi" class="gs-zone wide" style="--zc:#6b4fa0" id="gs-zona-piano-anno">
				<header class="gs-zone-head">🗓️ Pianificazione dell'Anno</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_pianificazione_anno' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'piano_anno' ) ) ) { gs_pannello_pianificazione_anno(); } ?></div>
			</section>

			<!-- ZONA: Calendario Corsi (segue subito la Pianificazione dell'Anno) -->
			<section data-idx-group="corsi" class="gs-zone wide" style="--zc:#6b4fa0" id="gs-zona-calendario">
				<header class="gs-zone-head">📅 Calendario Corsi</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_calendario' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'calendario' ) ) ) { gs_pannello_calendario(); } ?></div>
			</section>

			<!-- ZONA: Diplomi e Locandine (mancava del tutto nella Plancia: esisteva solo nel Pannello di Controllo sul sito) -->
			<section data-idx-group="corsi" class="gs-zone wide" style="--zc:#6b4fa0" id="gs-zona-locandine">
				<header class="gs-zone-head">🖼️ Diplomi e Locandine</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_locandine' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'locandine' ) ) ) { gs_pannello_locandine(); } ?></div>
			</section>

			<!-- ZONA: Iscrizioni -->
			<section data-idx-group="sfogline" class="gs-zone wide" style="--zc:#1f6e37" id="gs-zona-iscrizioni">
				<?php echo gs_cartellino_html( $riepilogo, 'iscrizioni' ); ?>
				<span id="gs-box-iscrizioni"></span>
				<header class="gs-zone-head">🧾 Iscrizioni delle sfogline</header>
				<div class="gs-zone-body">
					<p class="gs-zone-desc">Le nuove sfogline restano "in attesa" finché non le approvi. Verifica prima il pagamento della quota, poi approva (ricevono l'email di conferma) o rifiuta.</p>
					<?php gs_pannello_richieste_inner( $pending ); ?>
					<?php if ( function_exists( 'gs_pannello_invio_mail_area_riservata' ) ) { gs_pannello_invio_mail_area_riservata(); } ?>
				</div>
			</section>

			<!-- ZONA: Stato Generale -->
			<section data-idx-group="impostazioni" class="gs-zone wide" style="--zc:#5c4a34" id="gs-zona-stato-generale">
				<header class="gs-zone-head">🗂️ Stato Generale</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_stato_generale' ) ) { gs_pannello_stato_generale(); } ?></div>
			</section>

			<!-- ZONA: Messaggi -->
			<section data-idx-group="messaggi" class="gs-zone wide" style="--zc:#2b7a9e" id="gs-zona-messaggi">
				<?php echo gs_cartellino_html( $riepilogo, 'messaggi_attesa' ); ?>
				<header class="gs-zone-head">✉️ Messaggi alle sfogline</header>
				<div class="gs-zone-body"><?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'messaggi_privati' ) ) { gs_pannello_messaggi(); } ?></div>
			</section>

			<!-- ZONA: In diretta (Aeroplanino + Palloncini + Palloncino Gigante riuniti) -->
			<section data-idx-group="messaggi" class="gs-zone wide" style="--zc:#2b7a9e" id="gs-zona-in-diretta">
				<header class="gs-zone-head">🎉 In diretta</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_in_diretta' ) ) { gs_pannello_in_diretta(); } ?></div>
			</section>

			<!-- ZONA: Moderazione di tutte le chat -->
			<section data-idx-group="messaggi" class="gs-zone wide" style="--zc:#2b7a9e" id="gs-zona-moderazione">
				<header class="gs-zone-head">🛡️ Moderazione di tutte le chat</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_moderazione' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'moderazione' ) ) ) { gs_pannello_moderazione(); } ?></div>
			</section>

			<!-- ZONA: Spiegazioni delle Sezioni -->
			<section data-idx-group="messaggi" class="gs-zone wide" style="--zc:#2b7a9e" id="gs-zona-spiegazioni">
				<header class="gs-zone-head">📖 Spiegazioni delle Sezioni</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_spiegazioni' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'spiegazioni' ) ) ) { gs_pannello_spiegazioni(); } ?></div>
			</section>

			<!-- ZONA: Il Reset del gioco — solo per il titolare, controllato dentro la funzione stessa -->
			<section data-idx-group="impostazioni" class="gs-zone wide" style="--zc:#b03a2e" id="gs-zona-reset">
				<header class="gs-zone-head">⚠️ Il Reset del gioco</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_reset' ) ) { gs_pannello_reset(); } ?></div>
			</section>

			<!-- ZONA: Messaggi di ogni sfoglina (mancava nella Plancia) -->
			<section data-idx-group="messaggi" class="gs-zone wide" style="--zc:#2b7a9e" id="gs-zona-messaggi-sfogline">
				<header class="gs-zone-head">📨 Messaggi di ogni sfoglina</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_messaggi_sfogline' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'messaggi_sfogline_view' ) ) ) { gs_pannello_messaggi_sfogline(); } ?></div>
			</section>

			<!-- ZONA: Notifiche per sfoglina (mancava nella Plancia) -->
			<section data-idx-group="messaggi" class="gs-zone wide" style="--zc:#2b7a9e" id="gs-zona-notifiche-pref">
				<header class="gs-zone-head">🔔 Notifiche per sfoglina</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_notifiche_pref' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'notifiche_pref' ) ) ) { gs_pannello_notifiche_pref(); } ?></div>
			</section>

			<!-- ZONA: Percorsi Guidati (mancava nella Plancia) -->
			<section data-idx-group="corsi" class="gs-zone wide" style="--zc:#6b4fa0" id="gs-zona-percorsi-lezioni">
				<header class="gs-zone-head">🗺️ Percorsi Guidati</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_percorsi_lezioni' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'percorsi_lezioni' ) ) ) { gs_pannello_percorsi_lezioni(); } ?></div>
			</section>

			<!-- ZONA: Dicono di Noi / Testimonianze (mancava nella Plancia) -->
			<section data-idx-group="contenuti" class="gs-zone wide" style="--zc:#9c5a2e" id="gs-zona-testimonianze">
				<?php echo gs_cartellino_html( $riepilogo, 'testimonianze' ); ?>
				<header class="gs-zone-head">💬 Dicono di Noi (approvazione)</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_testimonianze' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'dicono_di_noi' ) ) ) { gs_pannello_testimonianze(); } ?></div>
			</section>

			<!-- ZONA: Madrina & Allieva (mancava nella Plancia) -->
			<section data-idx-group="sfogline" class="gs-zone wide" style="--zc:#1f6e37" id="gs-zona-madrine">
				<header class="gs-zone-head">🤝 Madrina & Allieva</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_madrine' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'madrine' ) ) ) { gs_pannello_madrine(); } ?></div>
			</section>

			<!-- ZONA: Visibilità sezioni -->
			<section data-idx-group="impostazioni" class="gs-zone wide" style="--zc:#5c4a34" id="gs-zona-visibilita">
				<header class="gs-zone-head">👁️ Visibilità sezioni e permessi</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_sezioni' ) ) { gs_pannello_sezioni(); } ?></div>
			</section>

			<!-- ZONA: Pagine pubbliche nel menu (mancava nella Plancia) -->
			<section data-idx-group="impostazioni" class="gs-zone wide" style="--zc:#5c4a34" id="gs-zona-menu-pagine">
				<header class="gs-zone-head">🔗 Pagine pubbliche nel menu</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_menu_pagine' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'menu_pagine' ) ) ) { gs_pannello_menu_pagine(); } ?></div>
			</section>

			<!-- ZONA: Applica la struttura del menu -->
			<section data-idx-group="impostazioni" class="gs-zone wide" style="--zc:#5c4a34" id="gs-zona-menu-struttura">
				<header class="gs-zone-head">🧭 Applica la struttura del menu</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_menu_struttura' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'menu_struttura' ) ) ) { gs_pannello_menu_struttura(); } ?></div>
			</section>

			<!-- ZONA: Messaggio di benvenuto -->
			<section data-idx-group="impostazioni" class="gs-zone wide" style="--zc:#5c4a34" id="gs-zona-benvenuto">
				<header class="gs-zone-head">👋 Messaggio di benvenuto</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_percorso' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'messaggio_benvenuto' ) ) ) { gs_pannello_percorso(); } ?></div>
			</section>

			<!-- ZONA: Come funziona il Percorso -->
			<section data-idx-group="impostazioni" class="gs-zone wide" style="--zc:#5c4a34" id="gs-zona-come-funziona">
				<header class="gs-zone-head">🗺️ Come funziona il Percorso</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_come_funziona' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'come_funziona' ) ) ) { gs_pannello_come_funziona(); } ?></div>
			</section>

			<!-- ZONA: Abbonamenti -->
			<section data-idx-group="pagamenti" class="gs-zone wide" style="--zc:#3f7d6a" id="gs-zona-abbonamenti">
				<?php echo gs_cartellino_html( $riepilogo, 'abbonamenti_scaduti' ); ?>
				<header class="gs-zone-head">🎫 Abbonamenti</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_abbonamenti' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'abbonamenti' ) ) ) { gs_pannello_abbonamenti(); } ?></div>
			</section>

			<!-- ZONA: Token per le consulenze private -->
			<section data-idx-group="pagamenti" class="gs-zone wide" style="--zc:#3f7d6a" id="gs-zona-token">
				<header class="gs-zone-head">🎫 Pagamenti — Token</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_token' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'token' ) ) ) { gs_pannello_token(); } ?></div>
			</section>

			<!-- ZONA: Contenuti del percorso -->
			<section data-idx-group="sfide" class="gs-zone" style="--zc:#cd8b0c" id="gs-zona-contenuti">
				<header class="gs-zone-head">🍝 Contenuti del percorso</header>
				<div class="gs-zone-body">
					<p class="gs-zone-desc">Crea le sfide, programma l'Ingrediente Segreto del venerdì e pubblica le Guide Stagionali.</p>
					<?php if ( $attiva ) : ?>
						<p><strong>Sfida attiva:</strong> <strong class="gs-sfida-attiva-badge gs-lampeggia-verde"><?php echo esc_html( get_the_title( $attiva ) ); ?></strong><br>
						<span class="gs-hint">scade il <?php echo esc_html( date_i18n( 'j/m/Y H:i', strtotime( get_post_meta( $attiva->ID, 'gs_data_fine', true ) ) ) ); ?></span></p>
					<?php else : ?>
						<p class="gs-hint">Nessuna sfida attiva in questo momento.</p>
					<?php endif; ?>
					<?php
					$gs_prox_ingr = function_exists( 'gs_get_next_ingredient' ) ? gs_get_next_ingredient() : null;
					$gs_riv_ingr  = function_exists( 'gs_get_revealed_ingredient' ) ? gs_get_revealed_ingredient() : null;
					?>
					<?php if ( $gs_prox_ingr ) : ?>
						<p><strong>Ingrediente Segreto in programma:</strong> <?php echo esc_html( get_the_title( $gs_prox_ingr ) ); ?><br>
						<span class="gs-hint">si rivela il <?php echo esc_html( date_i18n( 'j/m/Y H:i', get_post_time( 'U', true, $gs_prox_ingr ) ) ); ?></span></p>
					<?php elseif ( $gs_riv_ingr ) : ?>
						<p><strong>Ultimo Ingrediente Segreto rivelato:</strong> <?php echo esc_html( get_the_title( $gs_riv_ingr ) ); ?> — nessuno in programma dopo questo.</p>
					<?php else : ?>
						<p class="gs-hint">Nessun Ingrediente Segreto ancora creato.</p>
					<?php endif; ?>
					<p>
						<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=gs_sfida' ) ); ?>">+ Nuova sfida</a>
						<a class="button" href="#gs-zona-ingrediente">+ Ingrediente</a>
						<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=gs_barometro' ) ); ?>">+ Guida</a>
					</p>
					<p>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gs_sfida' ) ); ?>">Gestisci sfide</a> ·
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gs_ingrediente' ) ); ?>">Gestisci Ingredienti Segreti</a> ·
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gs_consiglio' ) ); ?>">Consigli</a>
					</p>
				</div>
			</section>

			<!-- ZONA: Ingrediente Segreto — pannello dedicato, senza passare dall'editor di WordPress -->
			<section data-idx-group="sfide" class="gs-zone" style="--zc:#cd8b0c" id="gs-zona-ingrediente">
				<header class="gs-zone-head">🥄 Ingrediente Segreto</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_ingrediente_segreto' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'ingrediente_segreto' ) ) ) { gs_pannello_ingrediente_segreto(); } ?></div>
			</section>

			<!-- ZONA: Sfide blindate (subito sotto "Contenuti del percorso", dove sta "Sfida attiva") -->
			<section data-idx-group="sfide" class="gs-zone wide" style="--zc:#cd8b0c">
				<header class="gs-zone-head">🔒 Sfide blindate — ammissione concorrenti</header>
				<div class="gs-zone-body"><?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'sfide_blindate' ) ) { gs_pannello_sfide_blindate(); } ?></div>
			</section>

			<!-- ZONA: Vetrina -->
			<section data-idx-group="partner" class="gs-zone" style="--zc:#b23a67" id="gs-zona-vetrina">
				<header class="gs-zone-head">🔗 Vetrina pubblica</header>
				<div class="gs-zone-body"><?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'vetrina_toggle' ) ) { gs_pannello_vetrina(); } ?></div>
			</section>

			<!-- ZONA: L'Esperto Risponde -->
			<section data-idx-group="messaggi" class="gs-zone wide" style="--zc:#2b7a9e" id="gs-zona-esperto">
				<?php echo gs_cartellino_html( $riepilogo, 'esperti' ); ?>
				<header class="gs-zone-head">💬 L'Esperto Risponde</header>
				<div class="gs-zone-body"><?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'esperto' ) ) { gs_pannello_esperti(); } ?></div>
			</section>

			<!-- ZONA: Conversazioni private -->
			<section data-idx-group="messaggi" class="gs-zone wide" style="--zc:#2b7a9e" id="gs-zona-conversazioni">
				<?php echo gs_cartellino_html( $riepilogo, 'conversazioni' ); ?>
				<header class="gs-zone-head">🔒 Conversazioni private</header>
				<div class="gs-zone-body"><?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'conversazioni' ) ) { gs_pannello_conversazioni(); } ?></div>
			</section>

			<!-- ZONA: Compleanni -->
			<section data-idx-group="sfogline" class="gs-zone wide" style="--zc:#1f6e37" id="gs-zona-compleanni">
				<header class="gs-zone-head">🎂 Compleanni</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_compleanni' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'compleanni' ) ) ) { gs_pannello_compleanni(); } ?></div>
			</section>

			<!-- ZONA: Aiuto e suggerimenti -->
			<section data-idx-group="messaggi" class="gs-zone wide" style="--zc:#2b7a9e" id="gs-zona-aiuto">
				<header class="gs-zone-head">🆘 Aiuto e suggerimenti</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_aiuto' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'aiuto' ) ) ) { gs_pannello_aiuto(); } ?></div>
			</section>

			<!-- ZONA: Biografie -->
			<section data-idx-group="sfogline" class="gs-zone wide" style="--zc:#1f6e37" id="gs-zona-biografie">
				<?php echo gs_cartellino_html( $riepilogo, 'biografie' ); ?>
				<header class="gs-zone-head">📖 Biografie da approvare</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_biografie' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'biografie' ) ) ) { gs_pannello_biografie(); } ?></div>
			</section>

			<!-- ZONA: Artigiani della Pasta -->
			<section data-idx-group="partner" class="gs-zone wide" style="--zc:#b23a67" id="gs-zona-artigiani">
				<header class="gs-zone-head">🍝 Artigiani della Pasta</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_artigiani' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'artigiani' ) ) ) { gs_pannello_artigiani(); } ?></div>
			</section>

			<!-- ZONA: Scuole di Cucina -->
			<section data-idx-group="partner" class="gs-zone wide" style="--zc:#b23a67" id="gs-zona-scuole">
				<header class="gs-zone-head">🎓 Scuole di Cucina</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_scuole' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'scuole' ) ) ) { gs_pannello_scuole(); } ?></div>
			</section>

			<!-- ZONA: Ricettario delle Famiglie -->
			<section data-idx-group="contenuti" class="gs-zone wide" style="--zc:#9c5a2e" id="gs-zona-ricettario">
				<?php echo gs_cartellino_html( $riepilogo, 'ricettario' ); ?>
				<header class="gs-zone-head">📖 Ricettario delle Famiglie</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_ricettario' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'ricettario' ) ) ) { gs_pannello_ricettario(); } ?></div>
			</section>

			<!-- ZONA: Ricette per il Libro (riservato, non nel Ricettario pubblico) -->
			<section data-idx-group="contenuti" class="gs-zone wide" style="--zc:#9c5a2e" id="gs-zona-ricette-libro">
				<header class="gs-zone-head">📕 Ricette per il Libro</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_ricette_libro' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'ricette_libro' ) ) ) { gs_pannello_ricette_libro(); } ?></div>
			</section>

			<!-- ZONA: Indovina la Sfoglia -->
			<section data-idx-group="sfide" class="gs-zone" style="--zc:#cd8b0c" id="gs-zona-indovina">
				<header class="gs-zone-head">🔮 Indovina la Sfoglia</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_indovina' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'indovina' ) ) ) { gs_pannello_indovina(); } ?></div>
			</section>

			<!-- ZONA: Il Tavolo di Lavoro -->
			<section data-idx-group="sfide" class="gs-zone wide" style="--zc:#cd8b0c" id="gs-zona-tavolo">
				<?php echo gs_cartellino_html( $riepilogo, 'tavolo' ); ?>
				<header class="gs-zone-head">🍳 Il Tavolo di Lavoro</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_tavolo' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'tavolo' ) ) ) { gs_pannello_tavolo(); } ?></div>
			</section>

			<!-- ZONA: La Sfoglia Misurata -->
			<section data-idx-group="sfide" class="gs-zone wide" style="--zc:#cd8b0c" id="gs-zona-misurata">
				<header class="gs-zone-head">📏 La Sfoglia Misurata</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_sfoglia_misurata' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'misurata' ) ) ) { gs_pannello_sfoglia_misurata(); } ?></div>
			</section>

			<!-- ZONA: La Giuria a Turno -->
			<section data-idx-group="sfide" class="gs-zone wide" style="--zc:#cd8b0c" id="gs-zona-giuria">
				<header class="gs-zone-head">⚖️ La Giuria a Turno</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_giuria_turno' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'giuria' ) ) ) { gs_pannello_giuria_turno(); } ?></div>
			</section>

			<!-- ZONA: Sondaggi -->
			<section data-idx-group="sfide" class="gs-zone wide" style="--zc:#cd8b0c" id="gs-zona-sondaggi">
				<header class="gs-zone-head">🗳️ Sondaggi</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_sondaggi' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'sondaggi' ) ) ) { gs_pannello_sondaggi(); } ?></div>
			</section>

			<!-- ZONA: FAQ - Domande -->
			<section data-idx-group="impostazioni" class="gs-zone wide" style="--zc:#5c4a34" id="gs-zona-faq">
				<header class="gs-zone-head">❓ FAQ - Domande</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_faq' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'faq' ) ) ) { gs_pannello_faq(); } ?></div>
			</section>

			<!-- ZONA: Novità -->
			<section data-idx-group="contenuti" class="gs-zone wide" style="--zc:#9c5a2e" id="gs-zona-novita">
				<header class="gs-zone-head">📣 Novità</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_novita' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'novita' ) ) ) { gs_pannello_novita(); } ?></div>
			</section>

			<!-- ZONA: Adotta un Piatto in Via di Estinzione -->
			<section data-idx-group="contenuti" class="gs-zone wide" style="--zc:#9c5a2e" id="gs-zona-piatti-estinzione">
				<header class="gs-zone-head">🦕 Adotta un Piatto in Via di Estinzione</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_piatti_estinzione' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'piatti_estinzione' ) ) ) { gs_pannello_piatti_estinzione(); } ?></div>
			</section>

			<!-- ZONA: Il Matterello Parlante -->
			<section data-idx-group="contenuti" class="gs-zone wide" style="--zc:#9c5a2e" id="gs-zona-matterello">
				<header class="gs-zone-head">🎙️ Il Matterello Parlante</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_matterello_parlante' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'matterello' ) ) ) { gs_pannello_matterello_parlante(); } ?></div>
			</section>

			<!-- ZONA: La Sfida del Silenzio -->
			<section data-idx-group="sfide" class="gs-zone wide" style="--zc:#cd8b0c" id="gs-zona-silenzio">
				<header class="gs-zone-head">🤫 La Sfida del Silenzio</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_silenzio' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'silenzio' ) ) ) { gs_pannello_silenzio(); } ?></div>
			</section>

			<!-- ZONA: La Cassaforte del Sapere -->
			<section data-idx-group="contenuti" class="gs-zone wide" style="--zc:#9c5a2e" id="gs-zona-cassaforte">
				<header class="gs-zone-head">🔐 La Cassaforte del Sapere</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_cassaforte_sapere' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'cassaforte' ) ) ) { gs_pannello_cassaforte_sapere(); } ?></div>
			</section>

			<!-- ZONA: La Sfoglia che Insegna Se Stessa -->
			<section data-idx-group="contenuti" class="gs-zone wide" style="--zc:#9c5a2e" id="gs-zona-sfoglia-insegna">
				<header class="gs-zone-head">🎓 La Sfoglia che Insegna Se Stessa</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_sfoglia_insegna' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'sfoglia_insegna' ) ) ) { gs_pannello_sfoglia_insegna(); } ?></div>
			</section>

			<!-- ZONA: Le Letture dei Grandi Protagonisti della Cucina -->
			<section data-idx-group="contenuti" class="gs-zone wide" style="--zc:#9c5a2e" id="gs-zona-letture">
				<header class="gs-zone-head">📖 Le Letture dei Grandi Protagonisti della Cucina</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_letture' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'letture' ) ) ) { gs_pannello_letture(); } ?></div>
			</section>

			<!-- ZONA: Libreria Video delle Lezioni -->
			<section data-idx-group="corsi" class="gs-zone wide" style="--zc:#6b4fa0" id="gs-zona-lezioni">
				<?php echo gs_cartellino_html( $riepilogo, 'lezioni' ); ?>
				<header class="gs-zone-head">🎬 Libreria Video delle Lezioni</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_lezioni' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'lezioni' ) ) ) { gs_pannello_lezioni(); } ?></div>
			</section>

			<!-- ZONA: Premi per Traguardo (subito dopo la Libreria Video: i video dei
			     premi sono spesso video di lezioni già caricati qui sopra) -->
			<section data-idx-group="sfide" class="gs-zone wide" style="--zc:#cd8b0c" id="gs-zona-premi-traguardi">
				<header class="gs-zone-head">🎁 Premi per Traguardo</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_premi_traguardi' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'premi_traguardi' ) ) ) { gs_pannello_premi_traguardi(); } ?></div>
			</section>

			<!-- ZONA: Aspetto Le Sfogline -->
			<section data-idx-group="partner" class="gs-zone wide" style="--zc:#b23a67" id="gs-zona-aspetto">
				<header class="gs-zone-head">🎨 Aspetto «Le Sfogline»</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_sfogline_view' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'sfogline_view' ) ) ) { gs_pannello_sfogline_view(); } ?></div>
			</section>

			<!-- ZONA: Caroselli per la Home Page (spostata da Partner & Vetrine a
			     Comunicazione, richiesto da Ennio il 18/08/2026: contiene anche
			     il nastro fisso, che qui si trova più facilmente) -->
			<section data-idx-group="messaggi" class="gs-zone wide" style="--zc:#2b7a9e" id="gs-zona-caroselli">
				<header class="gs-zone-head">🎠 Caroselli per la Home Page</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_caroselli' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'caroselli' ) ) ) { gs_pannello_caroselli(); } ?></div>
			</section>

			<!-- ZONA: Posta interna -->
			<section data-idx-group="messaggi" class="gs-zone wide" style="--zc:#2b7a9e" id="gs-zona-posta">
				<header class="gs-zone-head">📬 Posta interna</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_inbox' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'inbox' ) ) ) { gs_pannello_inbox(); } ?></div>
			</section>

			<!-- ZONA: Blackout -->
			<section data-idx-group="impostazioni" class="gs-zone" style="--zc:#5c4a34" id="gs-zona-blackout">
				<header class="gs-zone-head">🌙 Blackout</header>
				<div class="gs-zone-body"><?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'blackout' ) ) { gs_pannello_blackout(); } ?></div>
			</section>

			<!-- ZONA: Media -->
			<section data-idx-group="strumenti" class="gs-zone" style="--zc:#4a5568" id="gs-zona-media">
				<header class="gs-zone-head">🖼️ Foto e video</header>
				<div class="gs-zone-body"><?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'media_limiti' ) ) { gs_pannello_media(); } ?></div>
			</section>

			<!-- ZONA: Backup -->
			<section data-idx-group="strumenti" class="gs-zone wide" style="--zc:#4a5568" id="gs-zona-backup">
				<header class="gs-zone-head">💾 Backup dei file</header>
				<div class="gs-zone-body"><?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'backup' ) ) { gs_pannello_backup(); } ?></div>
			</section>

			<!-- ZONA: Esporta dati -->
			<section data-idx-group="strumenti" class="gs-zone wide" style="--zc:#4a5568" id="gs-zona-export">
				<header class="gs-zone-head">📤 Esporta i dati del percorso</header>
				<div class="gs-zone-body"><?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'export_dati' ) ) { gs_pannello_export_dati(); } ?></div>
			</section>

			<!-- ZONA: Diagnostica -->
			<section data-idx-group="strumenti" class="gs-zone wide" style="--zc:#4a5568" id="gs-zona-diagnostica">
				<header class="gs-zone-head">🩺 Diagnostica e stato di salute</header>
				<div class="gs-zone-body"><?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'diagnostica' ) ) { gs_pannello_diagnostica(); } ?></div>
			</section>

			<!-- ZONA: Il Cruscotto della Verità -->
			<section data-idx-group="strumenti" class="gs-zone wide" style="--zc:#4a5568" id="gs-zona-cruscotto">
				<header class="gs-zone-head">📊 Il Cruscotto della Verità</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_cruscotto' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'cruscotto' ) ) ) { gs_pannello_cruscotto(); } ?></div>
			</section>

			<!-- ZONA: Area Corsi Online -->
			<section data-idx-group="corsi" class="gs-zone wide" style="--zc:#6b4fa0">
				<header class="gs-zone-head">🎓 Area Corsi Online</header>
				<div class="gs-zone-body"><?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'area_pro' ) ) { gs_pannello_area_pro(); } ?></div>
			</section>

			<!-- ZONA: Sfogline in gara — controllo dei giudici -->
			<section data-idx-group="sfide" class="gs-zone wide" style="--zc:#cd8b0c" id="gs-zona-giudici-gara">
				<header class="gs-zone-head">⚖️ Sfogline in gara — controllo dei giudici</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_giudici_gara' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'giudici_gara' ) ) ) { gs_pannello_giudici_gara(); } ?></div>
			</section>

			<!-- ZONA: Correzione punti -->
			<section data-idx-group="sfogline" class="gs-zone" style="--zc:#1f6e37">
				<header class="gs-zone-head">✏️ Correzione punti</header>
				<div class="gs-zone-body"><?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'correzione_punti' ) ) { gs_pannello_correzione_punti(); } ?></div>
			</section>

			<!-- ZONA: Premio di Fine Anno -->
			<section data-idx-group="sfide" class="gs-zone wide" style="--zc:#cd8b0c">
				<header class="gs-zone-head">🎓 Premio di Fine Anno</header>
				<div class="gs-zone-body"><?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'premio' ) ) { gs_pannello_premio(); } ?></div>
			</section>

			<!-- ZONA: Regia del Gaming -->
			<section data-idx-group="sfide" class="gs-zone wide" style="--zc:#cd8b0c" id="gs-zona-regia">
				<header class="gs-zone-head">🎬 Regia del Gaming</header>
				<div class="gs-zone-body"><?php if ( function_exists( 'gs_pannello_regia' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'regia' ) ) ) { gs_pannello_regia(); } ?></div>
			</section>

			<!-- ZONA: Classifiche & Export -->
			<section data-idx-group="sfide" class="gs-zone wide" style="--zc:#cd8b0c">
				<header class="gs-zone-head">📊 Classifiche & esportazioni</header>
				<div class="gs-zone-body">
					<p class="gs-zone-desc">La classifica generale aggiornata e i PDF stampabili per le premiazioni.</p>
					<p>
						<a class="button" href="<?php echo esc_url( gs_export_url( 'generale' ) ); ?>" target="_blank">📄 Classifica generale</a>
						<a class="button" href="<?php echo esc_url( gs_export_url( 'squadre' ) ); ?>" target="_blank">📄 Classifica a squadre</a>
					</p>
					<table class="gs-table gs-paginate" data-per-page="10">
						<thead><tr><th>Pos.</th><th>Sfoglina</th><th>Livello</th><th>Punti</th></tr></thead>
						<tbody>
						<?php $pos = 1; foreach ( gs_leaderboard( 10 ) as $user ) :
							$level = gs_get_level( $user->ID ); ?>
							<tr>
								<td><?php echo $pos++; ?></td>
								<td><?php echo esc_html( $user->display_name ); ?></td>
								<td><?php echo esc_html( $level['simbolo'] . ' ' . $level['titolo'] ); ?></td>
								<td><?php echo (int) get_user_meta( $user->ID, 'gs_points', true ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</section>

			<!-- ZONA: Impostazioni -->
			<section data-idx-group="impostazioni" class="gs-zone" style="--zc:#5c4a34">
				<header class="gs-zone-head">⚙️ Impostazioni</header>
				<div class="gs-zone-body">
					<p class="gs-zone-desc">Punti per azione, livelli, squadre, missioni, anti-spam, OneSignal e testo del premio.</p>
					<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=gs-impostazioni' ) ); ?>">Apri le Impostazioni complete</a></p>
				</div>
			</section>

			<!-- ZONA: Collaboratori (solo amministratori veri) -->
			<?php if ( current_user_can( 'manage_options' ) ) : ?>
			<section data-idx-group="strumenti" class="gs-zone wide" style="--zc:#4a5568">
				<header class="gs-zone-head">👥 Collaboratori del pannello</header>
				<div class="gs-zone-body"><?php gs_pannello_collaboratrici(); ?></div>
			</section>
			<?php endif; ?>

		</div>
	</div>

	<?php gs_dash_styles(); ?>
	<?php
}

/**
 * Stile della plancia generale (iniettato inline nella pagina admin).
 *
 * Stile "prova lamarketer" (v3.127.0), approvato dal committente su
 * un'anteprima separata: sfondo caldo, schede con ombra morbida, testo più
 * grande, verde raffinato come accento. Scoped alla sola Plancia Generale
 * (gs_page_generale, la mega-pagina con tutte le zone), non tocca il sito.
 */
function gs_dash_styles() {
	?>
	<style>
	/* Palette "Atelier" (la stessa della Regia e del Pannello Generale nuovo)
	   estesa anche qui — richiesto da Ennio il 18/08/2026. I colori per
	   categoria delle testate delle zone (var(--zc), task del 18/08/2026)
	   NON si toccano: restano il modo per riconoscere a colpo d'occhio a
	   che gruppo appartiene ogni zona, sono un'altra cosa dalla cornice. */
	.wrap.gs-dash {
		background: linear-gradient(180deg, #fffdf9 0%, #f7f3ea 460px, #f7f3ea 100%);
		border-radius: 16px;
		padding: 32px 34px 60px;
		margin-top: 14px;
		font-family: 'Manrope', -apple-system, sans-serif;
	}
	.wrap.gs-dash h1 { font-family: 'Cormorant Garamond', Georgia, serif; font-size: 34px; font-weight: 650; letter-spacing: -.01em; color: #2c2418; }
	.wrap.gs-dash .gs-versione {
		font-size: 12px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase;
		color: #4a7c5c; margin: -6px 0 14px;
	}
	.gs-dash-intro { font-size: 15px; line-height: 1.6; color: #7a6f5c; max-width: 820px; }
	.gs-dash-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px,1fr)); gap: 16px; margin: 22px 0 30px; }
	.gs-dash-card {
		background: linear-gradient(100deg,#fffdf9,#fdfbf5 48%,#fffdf9);
		border: 1px solid #e6ddc9; border-left: 5px solid #a8541f; border-radius: 12px;
		padding: 18px 22px; display: flex; flex-direction: column;
		box-shadow: 0 16px 34px rgba(44,36,24,.10);
	}
	.gs-dash-card.alert { border-left-color: #b3261e; background: linear-gradient(100deg,#fff5f4,#fffdf9); }
	.gs-dash-card .n { font-family: 'Cormorant Garamond', Georgia, serif; font-size: 34px; font-weight: 700; color: #a8541f; line-height: 1; }
	.gs-dash-card.alert .n { color: #b3261e; }
	.gs-dash-card .l { font-size: 13px; color: #7a6f5c; margin-top: 6px; }

	.gs-dash-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 26px; align-items: start; }
	.gs-zone {
		position: relative;
		background: linear-gradient(100deg,#fffdf9,#fdfbf5 48%,#fffdf9);
		border: 1px solid #e6ddc9; border-radius: 16px; overflow: visible;
		box-shadow: 0 26px 60px rgba(44,36,24,.14), 0 2px 0 rgba(255,255,255,.8) inset;
	}
	.gs-zone.wide { grid-column: 1 / -1; }
	.gs-zone-head { background: var(--zc,#a8541f); color: #fff; font-family: 'Cormorant Garamond', Georgia, serif; font-size: 19px; font-weight: 650; letter-spacing: -.005em; padding: 18px 24px; border-radius: 16px 16px 0 0; }
	.gs-zone-body { padding: 22px 24px 26px; font-size: 15px; line-height: 1.6; overflow: hidden; border-radius: 0 0 16px 16px; }
	.gs-zone-desc { font-size: 14.5px; color: #7a6f5c; margin: 0 0 14px; line-height: 1.6; }

	/* Il cartellino: avvisa a colpo d'occhio quando c'è qualcosa che aspetta
	   una tua azione in questa scheda (approvazioni, risposte...). Compare
	   solo quando c'è davvero qualcosa in sospeso. */
	.gs-cartellino {
		position: absolute; top: -14px; right: 22px; z-index: 2;
		background: linear-gradient(100deg,#fff,#fbfbfa 48%,#fff);
		border: 2px dashed rgba(196,74,20,.55);
		border-radius: 8px;
		padding: 8px 14px;
		font-size: 12.5px;
		font-weight: 750;
		color: #a3421a;
		letter-spacing: .01em;
		transform: rotate(-4deg);
		box-shadow: 0 12px 26px rgba(39,35,29,.22);
		white-space: nowrap;
		text-decoration: none;
		cursor: default;
	}
	a.gs-cartellino { cursor: pointer; transition: transform .15s ease; }
	a.gs-cartellino:hover, a.gs-cartellino:focus { transform: rotate(-4deg) translateY(-2px); }

	/* Le funzioni riutilizzate dal front-end perdono la cornice: la zona è già la cornice. */
	.gs-zone .gs-box { border: 0; box-shadow: none; padding: 0; margin: 0; background: transparent; overflow: visible; font-size: inherit; }
	.gs-zone .gs-box-title { display: none; }
	.gs-zone { min-width: 0; }
	.gs-zone .gs-btn {
		background: var(--zc,#a8541f); color: #fff; border: none; border-radius: 9px;
		padding: 10px 18px; font-size: 13px; font-weight: 700; letter-spacing: .04em; cursor: pointer;
		box-shadow: 0 10px 22px rgba(0,0,0,.16), 0 1px 0 rgba(255,255,255,.25) inset;
		transition: transform .15s ease, filter .15s ease, box-shadow .15s ease;
	}
	.gs-zone .gs-btn:hover { transform: translateY(-2px); filter: brightness(1.06); box-shadow: 0 14px 28px rgba(0,0,0,.2), 0 1px 0 rgba(255,255,255,.25) inset; }
	.gs-zone .gs-btn-ghost { background: transparent; color: var(--zc,#a8541f); border: 1.5px dashed var(--zc,#a8541f); box-shadow: none; }
	.gs-zone .gs-btn-ghost:hover { transform: none; background: rgba(0,0,0,.04); }
	.gs-zone .gs-table { width: 100%; max-width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 14px; }
	.gs-zone .gs-table th, .gs-zone .gs-table td { text-align: left; padding: 9px 12px; border-bottom: 1px solid #eee; overflow-wrap: anywhere; word-break: break-word; vertical-align: top; }
	.gs-zone .gs-table th { background: #faf6ec; font-size: 12px; }

	@media (max-width: 1100px) { .gs-dash-grid { grid-template-columns: 1fr; } .gs-zone.wide { grid-column: auto; } }
	</style>
	<?php
}

// -----------------------------------------------------------------------------
// Panoramica
// -----------------------------------------------------------------------------
function gs_page_panoramica() {
	$n_sfogline = gs_conta_sfogline_pubbliche();
	$n_pending  = count( gs_get_pending_users() );
	$n_sfide    = wp_count_posts( 'gs_sfida' )->publish;
	$n_sfoglie  = wp_count_posts( 'gs_sfoglia' )->publish;
	?>
	<div class="wrap">
		<h1>🌾 Gaming Sfogline — Panoramica</h1>
		<div style="display:flex;gap:16px;flex-wrap:wrap;margin:20px 0;">
			<div class="gs-stat-card"><h2><?php echo (int) $n_sfogline; ?></h2><p>Sfogline registrate</p></div>
			<div class="gs-stat-card"><h2><?php echo (int) $n_pending; ?></h2><p>Richieste in attesa</p></div>
			<div class="gs-stat-card"><h2><?php echo (int) $n_sfide; ?></h2><p>Sfide pubblicate</p></div>
			<div class="gs-stat-card"><h2><?php echo (int) $n_sfoglie; ?></h2><p>Sfoglie inviate</p></div>
		</div>

		<h2>Esporta classifiche (PDF)</h2>
		<p>
			<a class="button" href="<?php echo esc_url( gs_export_url( 'generale' ) ); ?>" target="_blank">📄 Classifica generale</a>
			<a class="button" href="<?php echo esc_url( gs_export_url( 'squadre' ) ); ?>" target="_blank">📄 Classifica a squadre</a>
		</p>

		<h2>Classifica generale (top 10)</h2>
		<table class="widefat striped gs-paginate" data-per-page="10">
			<thead><tr><th>Pos.</th><th>Sfoglina</th><th>Livello</th><th>Punti</th></tr></thead>
			<tbody>
			<?php
			$pos      = 1;
			$rank_cls = array( 1 => 'gs-rank-oro', 2 => 'gs-rank-argento', 3 => 'gs-rank-bronzo' );
			$rank_ico = array( 1 => '🥇 ', 2 => '🥈 ', 3 => '🥉 ' );
			foreach ( gs_leaderboard( 10 ) as $user ) :
				$level = gs_get_level( $user->ID ); ?>
				<tr class="<?php echo esc_attr( $rank_cls[ $pos ] ?? '' ); ?>">
					<td><?php echo esc_html( $rank_ico[ $pos ] ?? '' ) . $pos++; ?></td>
					<td><?php echo esc_html( $user->display_name ); ?></td>
					<td><?php echo esc_html( $level['simbolo'] . ' ' . $level['titolo'] ); ?></td>
					<td><?php echo (int) get_user_meta( $user->ID, 'gs_points', true ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<style>.gs-stat-card{background:#fff;border:1px solid #e0d5be;border-left:4px solid #a8541f;padding:16px 24px;border-radius:6px;min-width:150px}.gs-stat-card h2{margin:0;font-size:32px;color:#a8541f}.gs-stat-card p{margin:4px 0 0;color:#666}</style>
	<?php
}

// -----------------------------------------------------------------------------
// Richieste di Iscrizione
// -----------------------------------------------------------------------------
function gs_page_richieste() {
	// Gestione azioni.
	if ( isset( $_GET['gs_action'], $_GET['user'] ) && check_admin_referer( 'gs_richiesta' ) ) {
		$uid = (int) $_GET['user'];
		if ( 'approva' === $_GET['gs_action'] ) {
			gs_approve_user( $uid );
			echo '<div class="notice notice-success"><p>Sfoglina approvata e avvisata via email.</p></div>';
		} elseif ( 'rifiuta' === $_GET['gs_action'] ) {
			gs_reject_user( $uid );
			echo '<div class="notice notice-warning"><p>Richiesta rifiutata.</p></div>';
		}
	}

	$pending = gs_get_pending_users();
	?>
	<div class="wrap">
		<h1>Richieste di Iscrizione</h1>
		<p>L'iscrizione è gratuita: approva ogni richiesta <strong>dopo averla controllata</strong> (che sia autentica, non spam). L'approvazione è sempre manuale.</p>
		<?php if ( empty( $pending ) ) : ?>
			<p>Nessuna richiesta in attesa. 🎉</p>
		<?php else : ?>
			<table class="widefat striped gs-paginate" data-per-page="10">
				<thead><tr><th>Nome</th><th>Username</th><th>Email</th><th>Squadra</th><th>Data</th><th>Azioni</th></tr></thead>
				<tbody>
				<?php foreach ( $pending as $u ) :
					$approve = wp_nonce_url( admin_url( 'admin.php?page=gs-richieste&gs_action=approva&user=' . $u->ID ), 'gs_richiesta' );
					$reject  = wp_nonce_url( admin_url( 'admin.php?page=gs-richieste&gs_action=rifiuta&user=' . $u->ID ), 'gs_richiesta' ); ?>
					<tr>
						<td><?php echo esc_html( $u->display_name ); ?></td>
						<td><?php echo esc_html( $u->user_login ); ?></td>
						<td><?php echo esc_html( $u->user_email ); ?></td>
						<td><?php echo esc_html( get_user_meta( $u->ID, 'gs_team', true ) ?: '—' ); ?></td>
						<td><?php echo esc_html( mysql2date( 'j/m/Y', $u->user_registered ) ); ?></td>
						<td>
							<a class="button button-primary" href="<?php echo esc_url( $approve ); ?>">Approva</a>
							<a class="button" href="<?php echo esc_url( $reject ); ?>" onclick="return confirm('Rifiutare questa richiesta?')">Rifiuta</a>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}

// -----------------------------------------------------------------------------
// Premio di Fine Anno
// -----------------------------------------------------------------------------
function gs_page_premio() {
	$year = isset( $_GET['anno'] ) ? preg_replace( '/[^0-9]/', '', $_GET['anno'] ) : date( 'Y', current_time( 'timestamp' ) );

	if ( isset( $_POST['gs_assegna_premio'] ) && check_admin_referer( 'gs_premio' ) ) {
		$vincitrici = gs_assign_year_prize( $year );
		echo '<div class="notice notice-success"><p>Premio assegnato a ' . count( $vincitrici ) . ' sfogline: ' . esc_html( implode( ', ', $vincitrici ) ) . '</p></div>';
	}

	$settings = gs_settings()['year_prize'];
	$classifica = gs_year_leaderboard( $year, (int) $settings['numero_vincitrici'] );
	$assigned = gs_year_prize_assigned( $year );
	?>
	<div class="wrap">
		<h1>Premio di Fine Anno — Corso con Rina Poletti</h1>

		<form method="get" style="margin:12px 0">
			<input type="hidden" name="page" value="gs-premio">
			<label>Anno: <input type="number" name="anno" value="<?php echo esc_attr( $year ); ?>" style="width:100px"></label>
			<button class="button">Mostra</button>
		</form>

		<p><em><?php echo esc_html( $settings['testo'] ); ?></em></p>

		<h2>Classifica dell'anno <?php echo esc_html( $year ); ?> — prime <?php echo (int) $settings['numero_vincitrici']; ?></h2>
		<table class="widefat striped gs-paginate" data-per-page="10">
			<thead><tr><th>Pos.</th><th>Sfoglina</th><th>Punti <?php echo esc_html( $year ); ?></th></tr></thead>
			<tbody>
			<?php $pos = 1; foreach ( $classifica as $riga ) : ?>
				<tr><td><?php echo $pos++; ?></td><td><?php echo esc_html( $riga['user']->display_name ); ?></td><td><?php echo (int) $riga['punti']; ?></td></tr>
			<?php endforeach; ?>
			<?php if ( empty( $classifica ) ) : ?><tr><td colspan="3">Nessun dato per quest'anno.</td></tr><?php endif; ?>
			</tbody>
		</table>

		<?php if ( ! empty( $classifica ) ) : ?>
			<form method="post" style="margin-top:16px">
				<?php wp_nonce_field( 'gs_premio' ); ?>
				<?php if ( $assigned ) : ?>
					<p><strong>Premio già assegnato</strong> il <?php echo esc_html( $assigned ); ?>. Riassegnando invierai di nuovo le email.</p>
				<?php endif; ?>
				<button class="button button-primary" name="gs_assegna_premio" value="1" onclick="return confirm('Assegnare il premio e inviare le email alle vincitrici?')">🎓 Assegna il premio alle prime <?php echo (int) $settings['numero_vincitrici']; ?></button>
				<a class="button" href="<?php echo esc_url( gs_export_url( 'generale' ) ); ?>" target="_blank">📄 Esporta PDF</a>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

// -----------------------------------------------------------------------------
// Correggi Punti (con cronologia ultime 15 azioni)
// -----------------------------------------------------------------------------
function gs_page_correggi() {
	$selected = null;
	$message  = '';

	// Ricerca utente.
	if ( isset( $_POST['gs_cerca'] ) && check_admin_referer( 'gs_correggi' ) ) {
		$query = gs_clean( $_POST['utente'] ?? '' );
		$selected = get_user_by( 'login', $query ) ?: get_user_by( 'email', $query );
		if ( ! $selected ) {
			$message = '<div class="notice notice-error"><p>Nessun utente trovato.</p></div>';
		}
	}

	// Applicazione correzione.
	if ( isset( $_POST['gs_correggi_punti'] ) && check_admin_referer( 'gs_correggi' ) ) {
		$uid    = (int) $_POST['user_id'];
		$punti  = (int) $_POST['punti'];
		$motivo = gs_clean( $_POST['motivo'] ?? 'Correzione manuale' );
		if ( $uid && $punti !== 0 ) {
			gs_add_points( $uid, $punti, '[Correzione] ' . $motivo );
			$message = '<div class="notice notice-success"><p>Punti aggiornati (' . ( $punti > 0 ? '+' : '' ) . $punti . ').</p></div>';
		}
		$selected = get_user_by( 'id', $uid );
	}
	?>
	<div class="wrap">
		<h1>Correggi Punti Utente</h1>
		<p>I punti sono <strong>automatici</strong> per ogni azione. Usa questa pagina solo per eccezioni (errori, contenuti rimossi, casi particolari).</p>
		<?php echo $message; ?>

		<form method="post">
			<?php wp_nonce_field( 'gs_correggi' ); ?>
			<label>Username o email: <input type="text" name="utente" value="<?php echo $selected ? esc_attr( $selected->user_login ) : ''; ?>" required></label>
			<button class="button" name="gs_cerca" value="1">Cerca</button>
		</form>

		<?php if ( $selected ) :
			$level = gs_get_level( $selected->ID );
			$log   = gs_get_points_log( $selected->ID, 15 ); ?>
			<hr>
			<h2><?php echo esc_html( $selected->display_name ); ?> — <?php echo esc_html( $level['simbolo'] . ' ' . $level['titolo'] ); ?> (<?php echo (int) $level['punti']; ?> punti)</h2>

			<form method="post" style="margin:16px 0">
				<?php wp_nonce_field( 'gs_correggi' ); ?>
				<input type="hidden" name="user_id" value="<?php echo (int) $selected->ID; ?>">
				<label>Punti (usa il segno meno per togliere): <input type="number" name="punti" value="0" required></label>
				<label>Motivo: <input type="text" name="motivo" style="width:300px"></label>
				<button class="button button-primary" name="gs_correggi_punti" value="1">Applica correzione</button>
			</form>

			<h3>Cronologia delle ultime 15 azioni</h3>
			<table class="widefat striped gs-paginate" data-per-page="10">
				<thead><tr><th>Data</th><th>Punti</th><th>Motivo</th><th>Totale</th></tr></thead>
				<tbody>
				<?php if ( $log ) : foreach ( $log as $entry ) : ?>
					<tr>
						<td><?php echo esc_html( $entry['time'] ); ?></td>
						<td style="color:<?php echo $entry['points'] >= 0 ? 'green' : 'red'; ?>"><?php echo ( $entry['points'] >= 0 ? '+' : '' ) . (int) $entry['points']; ?></td>
						<td><?php echo esc_html( $entry['reason'] ); ?></td>
						<td><?php echo (int) $entry['total']; ?></td>
					</tr>
				<?php endforeach; else : ?>
					<tr><td colspan="4">Nessuna azione registrata.</td></tr>
				<?php endif; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}

// -----------------------------------------------------------------------------
// Impostazioni
// -----------------------------------------------------------------------------
function gs_page_impostazioni() {
	if ( isset( $_POST['gs_save_settings'] ) && check_admin_referer( 'gs_settings' ) ) {
		gs_save_settings_from_post();
		echo '<div class="notice notice-success"><p>Impostazioni salvate.</p></div>';
	}

	$s = gs_settings();
	?>
	<div class="wrap">
		<h1>Impostazioni Gaming Sfogline</h1>
		<form method="post">
			<?php wp_nonce_field( 'gs_settings' ); ?>

			<h2>Punti per azione</h2>
			<table class="form-table">
				<?php
				$labels = array(
					'pubblica_sfoglia' => 'Pubblica una sfoglia',
					'voto_dato'        => 'Vota una sfoglia (azione)',
					'stella_ricevuta'  => 'Per ogni stella ricevuta',
					'voce_diario'      => 'Voce di diario',
					'consiglio'        => 'Consiglio condiviso',
					'commento_sfida'   => 'Commento alla sfida',
					'primo_posto'      => '1° posto in una sfida',
					'secondo_posto'    => '2° posto',
					'terzo_posto'      => '3° posto',
					'streak_settimana'    => 'Streak (per settimana)',
					'lezione_vista'       => 'Guardare una lezione video',
					'percorso_completato' => 'Completare un Percorso Guidato',
					'risposta_esatta'     => 'Risposta esatta a una domanda di verifica',
				);
				foreach ( $labels as $key => $label ) : ?>
					<tr><th><?php echo esc_html( $label ); ?></th>
					<td><input type="number" name="points[<?php echo esc_attr( $key ); ?>]" value="<?php echo (int) $s['points'][ $key ]; ?>"></td></tr>
				<?php endforeach; ?>
			</table>

			<h2>Lezioni Video</h2>
			<table class="form-table">
				<tr><th>Promemoria per una lezione consigliata non vista (giorni)</th>
				<td><input type="number" min="1" name="lezioni[promemoria_giorni]" value="<?php echo (int) ( $s['lezioni']['promemoria_giorni'] ?? 3 ); ?>" style="width:90px">
				<p class="description">Se il titolare consiglia una lezione a una sfoglina e lei non la apre entro questi giorni, riceve un promemoria automatico.</p></td></tr>
			</table>

			<h2>Livelli (Le Insegne della Sfoglia)</h2>
			<table class="form-table">
				<?php foreach ( $s['levels'] as $i => $lv ) : ?>
					<tr>
						<th>Livello <?php echo $i + 1; ?></th>
						<td>
							Soglia <input type="number" name="levels[<?php echo $i; ?>][soglia]" value="<?php echo (int) $lv['soglia']; ?>" style="width:90px">
							Titolo <input type="text" name="levels[<?php echo $i; ?>][titolo]" value="<?php echo esc_attr( $lv['titolo'] ); ?>">
							Simbolo <input type="text" name="levels[<?php echo $i; ?>][simbolo]" value="<?php echo esc_attr( $lv['simbolo'] ); ?>" style="width:60px">
						</td>
					</tr>
				<?php endforeach; ?>
			</table>

			<h2>Squadre regionali (una per riga)</h2>
			<p><textarea name="teams" rows="5" style="width:400px"><?php echo esc_textarea( implode( "\n", $s['teams'] ) ); ?></textarea></p>

			<h2>Missioni giornaliere</h2>
			<table class="form-table">
				<?php foreach ( $s['missions'] as $key => $m ) : ?>
					<tr>
						<th><?php echo esc_html( $m['label'] ); ?></th>
						<td>
							Punti <input type="number" name="missions[<?php echo esc_attr( $key ); ?>][punti]" value="<?php echo (int) $m['punti']; ?>" style="width:80px">
							Obiettivo <input type="number" name="missions[<?php echo esc_attr( $key ); ?>][obiettivo]" value="<?php echo (int) $m['obiettivo']; ?>" style="width:80px">
						</td>
					</tr>
				<?php endforeach; ?>
			</table>

			<h2>Anti-spam</h2>
			<table class="form-table">
				<tr><th>Secondi minimi prima dell'invio</th><td><input type="number" name="antispam[min_seconds]" value="<?php echo (int) $s['antispam']['min_seconds']; ?>"></td></tr>
				<tr><th>Invii massimi per IP all'ora</th><td><input type="number" name="antispam[max_per_hour]" value="<?php echo (int) $s['antispam']['max_per_hour']; ?>"></td></tr>
			</table>

			<h2>Premio di Fine Anno</h2>
			<table class="form-table">
				<tr><th>Numero vincitrici</th><td><input type="number" name="year_prize[numero_vincitrici]" value="<?php echo (int) $s['year_prize']['numero_vincitrici']; ?>"></td></tr>
				<tr><th>Testo del premio</th><td><textarea name="year_prize[testo]" rows="3" style="width:500px"><?php echo esc_textarea( $s['year_prize']['testo'] ); ?></textarea></td></tr>
			</table>

			<h2>Notifiche push (OneSignal)</h2>
			<table class="form-table">
				<tr><th>App ID</th><td><input type="text" name="onesignal[app_id]" value="<?php echo esc_attr( $s['onesignal']['app_id'] ); ?>" style="width:400px"></td></tr>
				<tr><th>REST API Key</th><td><input type="text" name="onesignal[rest_api_key]" value="<?php echo esc_attr( $s['onesignal']['rest_api_key'] ); ?>" style="width:400px"></td></tr>
			</table>

			<h2>Registrazione</h2>
			<table class="form-table">
				<tr><th>Importo contributo gaming dopo la prova (&euro;)</th><td><input type="text" name="registration[importo_quota]" value="<?php echo esc_attr( $s['registration']['importo_quota'] ); ?>" style="width:100px"><p class="description">L'iscrizione resta gratuita: questo è solo l'importo del contributo facoltativo che, dopo il mese di prova, riapre l'accesso alle sezioni di gaming (mostrato negli avvisi di scadenza).</p></td></tr>
				<tr><th>Dati per il bonifico</th><td><p class="description">L'IBAN e l'intestatario del contributo sono gli stessi impostati in "Calendario Corsi &rarr; Dati per il bonifico".</p></td></tr>
			</table>

			<h2>Funzionalità</h2>
			<table class="form-table">
				<tr>
					<th>Vetrina pubblica del profilo</th>
					<td>
						<label>
							<input type="checkbox" name="features[vetrina_attiva]" value="1" <?php checked( ! empty( $s['features']['vetrina_attiva'] ) ); ?>>
							Attiva la pagina pubblica "Vedi/Condividi la tua vetrina"
						</label>
						<p class="description">Disattivandola, lo shortcode [gs_vetrina] e i link alla vetrina spariscono da tutto il sito. Regolabile anche dal Pannello di Controllo front-end.</p>
					</td>
				</tr>
				<tr>
					<th>Dettatura vocale (microfono)</th>
					<td>
						<label>
							<input type="checkbox" name="features[dettatura_vocale_attiva]" value="1" <?php checked( ! empty( $s['features']['dettatura_vocale_attiva'] ) ); ?>>
							Attiva il microfono per scrivere a voce nei campi di testo (di base, per tutte)
						</label>
						<p class="description">Funziona solo sui browser che supportano il riconoscimento vocale (Chrome, Edge, Opera). Qui sotto puoi fare eccezioni per singole sfogline.</p>
					</td>
				</tr>
			</table>

			<h2>Dettatura vocale — eccezioni per sfoglina</h2>
			<table class="form-table">
				<tr>
					<th>Sfogline con il valore capovolto</th>
					<td>
						<?php
						$sfogline_dett = function_exists( 'gs_sez_sfogline' ) ? gs_sez_sfogline() : array();
						$eccezioni_dett = isset( $s['dettatura_vocale_eccezioni'] ) && is_array( $s['dettatura_vocale_eccezioni'] ) ? array_map( 'intval', $s['dettatura_vocale_eccezioni'] ) : array();
						?>
						<?php if ( $sfogline_dett ) : ?>
							<select multiple name="dettatura_vocale_eccezioni[]" size="8" style="min-width:260px;max-width:100%">
								<?php foreach ( $sfogline_dett as $u ) : ?>
									<option value="<?php echo (int) $u->ID; ?>" <?php echo in_array( (int) $u->ID, $eccezioni_dett, true ) ? 'selected' : ''; ?>><?php echo esc_html( $u->display_name ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description">Se l'interruttore sopra è ATTIVO, le sfogline scelte qui sono le uniche SENZA microfono. Se è DISATTIVATO, sono le uniche CON il microfono. Tieni premuto Ctrl/Cmd per sceglierne più di una (o per toglierne una già scelta).</p>
						<?php else : ?>
							<p class="description">Non ci sono ancora sfogline approvate tra cui scegliere.</p>
						<?php endif; ?>
					</td>
				</tr>
			</table>

			<h2>Moderazione chat — parole da segnalare</h2>
			<table class="form-table">
				<tr>
					<th>Parole o frasi vietate</th>
					<td>
						<textarea name="parole_vietate" rows="5" style="width:100%;max-width:420px" placeholder="Una parola o frase per riga…"><?php echo esc_textarea( implode( "\n", isset( $s['parole_vietate'] ) && is_array( $s['parole_vietate'] ) ? $s['parole_vietate'] : array() ) ); ?></textarea>
						<p class="description">Una per riga. Se un commento nella chat del Matterello Parlante ne contiene una, resta comunque pubblicato (per evitare falsi allarmi) ma viene segnato con ⚠️ e arriva un avviso nella tua posta interna: da lì puoi eliminarlo (recuperabile dal cestino della chat).</p>
					</td>
				</tr>
			</table>

			<p><button class="button button-primary" name="gs_save_settings" value="1">Salva impostazioni</button></p>
		</form>
	</div>
	<?php
}

/**
 * Salva le impostazioni dal POST.
 */
function gs_save_settings_from_post() {
	$s = gs_settings();

	if ( isset( $_POST['points'] ) && is_array( $_POST['points'] ) ) {
		foreach ( $_POST['points'] as $k => $v ) {
			$s['points'][ sanitize_key( $k ) ] = (int) $v;
		}
	}

	if ( isset( $_POST['lezioni']['promemoria_giorni'] ) ) {
		$s['lezioni']['promemoria_giorni'] = max( 1, (int) $_POST['lezioni']['promemoria_giorni'] );
	}

	if ( isset( $_POST['levels'] ) && is_array( $_POST['levels'] ) ) {
		$levels = array();
		foreach ( $_POST['levels'] as $lv ) {
			$levels[] = array(
				'soglia'  => (int) ( $lv['soglia'] ?? 0 ),
				'titolo'  => gs_clean( $lv['titolo'] ?? '' ),
				'simbolo' => gs_clean( $lv['simbolo'] ?? '' ),
			);
		}
		// Ordina per soglia crescente.
		usort( $levels, function ( $a, $b ) { return $a['soglia'] <=> $b['soglia']; } );
		$s['levels'] = $levels;
	}

	if ( isset( $_POST['teams'] ) ) {
		$lines = array_filter( array_map( 'trim', explode( "\n", wp_unslash( $_POST['teams'] ) ) ) );
		$s['teams'] = array_map( 'sanitize_text_field', $lines );
	}

	if ( isset( $_POST['parole_vietate'] ) ) {
		$lines = array_filter( array_map( 'trim', explode( "\n", wp_unslash( $_POST['parole_vietate'] ) ) ) );
		$s['parole_vietate'] = array_values( array_map( 'sanitize_text_field', $lines ) );
	}

	if ( isset( $_POST['missions'] ) && is_array( $_POST['missions'] ) ) {
		foreach ( $_POST['missions'] as $k => $m ) {
			$k = sanitize_key( $k );
			if ( isset( $s['missions'][ $k ] ) ) {
				$s['missions'][ $k ]['punti']     = (int) ( $m['punti'] ?? 0 );
				$s['missions'][ $k ]['obiettivo'] = max( 1, (int) ( $m['obiettivo'] ?? 1 ) );
			}
		}
	}

	if ( isset( $_POST['antispam'] ) ) {
		$s['antispam']['min_seconds']  = max( 0, (int) ( $_POST['antispam']['min_seconds'] ?? 3 ) );
		$s['antispam']['max_per_hour'] = max( 1, (int) ( $_POST['antispam']['max_per_hour'] ?? 10 ) );
	}

	if ( isset( $_POST['year_prize'] ) ) {
		$s['year_prize']['numero_vincitrici'] = max( 1, (int) ( $_POST['year_prize']['numero_vincitrici'] ?? 12 ) );
		$s['year_prize']['testo']             = sanitize_textarea_field( wp_unslash( $_POST['year_prize']['testo'] ?? '' ) );
	}

	if ( isset( $_POST['onesignal'] ) ) {
		$s['onesignal']['app_id']       = gs_clean( $_POST['onesignal']['app_id'] ?? '' );
		$s['onesignal']['rest_api_key'] = gs_clean( $_POST['onesignal']['rest_api_key'] ?? '' );
	}

	if ( isset( $_POST['registration'] ) ) {
		$s['registration']['importo_quota'] = sanitize_text_field( wp_unslash( $_POST['registration']['importo_quota'] ?? '' ) );
	}

	// Funzionalità attivabili (checkbox: assente = disattivata).
	$s['features']['vetrina_attiva']          = ! empty( $_POST['features']['vetrina_attiva'] );
	$s['features']['dettatura_vocale_attiva'] = ! empty( $_POST['features']['dettatura_vocale_attiva'] );

	if ( isset( $_POST['dettatura_vocale_eccezioni'] ) && is_array( $_POST['dettatura_vocale_eccezioni'] ) ) {
		$s['dettatura_vocale_eccezioni'] = array_values( array_unique( array_filter( array_map( 'intval', $_POST['dettatura_vocale_eccezioni'] ) ) ) );
	} else {
		$s['dettatura_vocale_eccezioni'] = array();
	}

	update_option( GS_OPTION, $s );
}
