<?php
/**
 * Plugin Name: Gaming Sfogline
 * Plugin URI:  https://accademiadellasfoglia.it/
 * Description: Sistema di gamification per l'Accademia della Sfoglia (ex "GuruShot Sfogline"). Livelli, sfide, voti, streak, missioni, badge, squadre, ingrediente segreto, registrazione con approvazione, premio di fine anno e altro.
 * Version:     3.299.0
 * Author:      Accademia della Sfoglia
 * Text Domain: gaming-sfogline
 * License:     GPL-2.0-or-later
 *
 * Nota: il prefisso delle funzioni resta gs_ (Gamification/Gaming Sfogline)
 * per compatibilità con la v1.0; solo il nome pubblico e il file principale
 * sono stati rinominati in "Gaming Sfogline".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Costanti
// -----------------------------------------------------------------------------
define( 'GS_VERSION', '3.299.0' );
define( 'GS_FILE', __FILE__ );
define( 'GS_DIR', plugin_dir_path( __FILE__ ) );
define( 'GS_URL', plugin_dir_url( __FILE__ ) );
define( 'GS_INC', GS_DIR . 'includes/' );
define( 'GS_OPTION', 'gs_settings' ); // chiave unica delle impostazioni

// -----------------------------------------------------------------------------
// Caricamento moduli
// -----------------------------------------------------------------------------
$gs_modules = array(
	'helpers.php',
	'media-msg.php',          // Allegati foto/video per la messaggistica
	'percorso.php',           // Presentazione: percorso di compiti documentato            // impostazioni di default + utilità condivise
	'come-funziona.php',      // Guida "Come funziona il Percorso" in cima a La Mia Sfoglia
	'prossimo-passo.php',     // Suggerimento dinamico "cosa fare dopo" nel profilo
	'cpt.php',                // custom post type + metabox
	'points.php',             // motore punti e livelli
	'antispam.php',           // honeypot / time-trap / limite IP (v2)
	'registration.php',       // registrazione pubblica + approvazione (v2)
	'notifications.php',      // email badge/livello + push OneSignal (v2)
	'voting.php',             // sfide, invio sfoglie, voto, classifica, premi
	'streak.php',             // streak del matterello
	'missions.php',           // missioni giornaliere
	'badges.php',             // badge + trigger di sblocco
	'teams.php',              // squadre regionali
	'seasonal.php',           // barometro stagionale
	'secret-ingredient.php',  // ingrediente segreto del venerdì
	'forms.php',              // diario dell'impasto + consigli
	'year-prize.php',         // premio di fine anno - corso Rina Poletti (v2)
	'export.php',             // esportazione classifica PDF (v2)
	'shortcodes.php',         // shortcode front-end
	'sfogline-extra.php',     // cestino, promemoria, ricerca, Le Sfogline, blackout
	'regia-iscritti.php',     // Regia degli Iscritti ai Corsi: scheda 360° per persona (solo gestori)
	'media-backup.php',       // limite/compressione media + backup giornaliero
	'export-dati.php',        // esportazione CSV di sfogline, prenotazioni e corsi
	'diagnostica.php',        // email di prova + stato di salute della configurazione
	'onboarding.php',         // primo giro di benvenuto per la nuova sfoglina
	'volo-notifiche.php',     // coda di avvisi "aeroplanino" (badge, livello, aiuto, prenotazioni…)
	'ricettario.php',         // Ricettario delle Famiglie: ricette tramandate, con approvazione
	'testimonianze.php',      // "Dicono di Noi": testimonianze pubbliche delle sfogline, con approvazione
	'lezioni-video.php',      // Libreria Video delle Lezioni di Rina (link YouTube/Vimeo)
	'percorsi-lezioni.php',   // Percorsi Guidati: gruppi ordinati di Lezioni Video, con badge dedicato
	'percorso-staffetta.php', // Percorso a Staffetta: chi completa passa personalmente il testimone
	'indovina.php',           // Indovina la Sfoglia: quiz-lampo giornaliero, stesso per tutte
	'tavolo.php',             // Il Tavolo di Lavoro: foto del giorno, privata, con commento di un maestro
	'sfoglia-misurata.php',   // La Sfoglia Misurata: sfide di tecnica con dato numerico, non a voto
	'giuria-turno.php',       // La Giuria a Turno: voto pubblico e motivato tra sfogline, giudici a rotazione
	'sondaggi.php',           // Sondaggi: domanda + proposte votate dalle sfogline, risultati pubblici o riservati
	'login.php',              // Pannello di accesso dedicato (al posto del generico wp-login.php) + password dimenticata
	'account.php',            // "Il tuo account" in La Mia Sfoglia: cambio password/email, esporta dati, richiedi cancellazione
	'piatti-estinzione.php',  // Adotta un Piatto in Via di Estinzione: custodi pubblici di piatti rari
	'matterello-parlante.php', // Il Matterello Parlante: archivio vocale di ricordi/consigli
	'anno-fa-oggi.php',       // Un Anno Fa Oggi: flashback pubblico delle ricette di un anno fa
	'sfida-silenzio.php',     // La Sfida del Silenzio: periodo con classifica congelata
	'diario-doppio-senso.php', // Il Diario a Doppio Senso: flashback privato di un anno fa
	'testamento-sfoglina.php', // Il Testamento della Sfoglina: eredità di chi raggiunge il livello massimo
	'cassaforte-sapere.php',  // La Cassaforte del Sapere: contenuto sbloccato da una soglia collettiva
	'sfoglia-insegna.php',    // La Sfoglia che Insegna Se Stessa: errori promossi a materiale didattico
	'pianificazione-anno.php', // Pianificazione dell'Anno: linea del tempo unica per corsi/gare/percorsi
	'area-pro.php',           // Area Professionale: corsi individuali privati
	'messaggi.php',           // messaggi privati dal pannello alle sfogline
	'premi-traguardi.php',    // premi automatici (video o messaggio) al raggiungimento di un livello o badge
	'sconto-corsi.php',       // sconti sui corsi accumulati vincendo badge (estende premi-traguardi.php)
	'buono-sfoglia.php',      // sistema mensile di gioco: Buono Sfoglia (2500 punti/mese)
	'classifica-mensile.php', // classifica animata del mese, "Matterello che stende"
	'notifiche-pref.php',     // preferenze email/interno per sfoglina + gs_mail_progetto()
	'promemoria.php',         // promemoria giornaliero opt-in: avviso se non ci si è ancora collegate oggi
	'locandine.php',          // diplomi e locandine a piacimento (stampa + scarica immagine)
	'token.php',              // credito a token per le consulenze private a pagamento
	'mail-area-riservata.php', // mail "Accesso e Vetrina" (mese di prova, riattivazione token, nastro) — automatica all'approvazione + invio manuale
	'esperti.php',            // canali "L'Esperto Risponde" (consulenze private a token)
	'regia.php',              // Regia del Gaming: proposte e direttive per programmare sfide/percorsi/contenuti
	'cruscotto.php',          // Il Cruscotto della Verità: quali sezioni sono vive, quali dormienti
	'letture.php',            // Le Letture dei Grandi Protagonisti della Cucina: pubblico + chat a bolle + iscrizione lettore
	'moderazione.php',        // pannello unico per moderare tutte le chat del progetto (elimina un messaggio, ovunque sia stato scritto)
	'spiegazioni.php',        // raccoglitore delle spiegazioni di ogni sezione, con ricerca e invio diretto come messaggio
	'reset.php',              // il Reset del gioco (irreversibile) + lo username fuori dalla rete pubblica — solo per il titolare
	'seo.php',                // SEO/GEO delle pagine pubbliche: meta description, Open Graph, dati strutturati Schema.org
	'menu-struttura.php',     // "Applica la struttura del menu": riorganizza in un clic il menu del sito secondo la proposta approvata
	'conversazioni.php',      // conversazioni private a due vie esperto/sfoglina
	'compleanni.php',         // "I Compleanni di Oggi": vetrina automatica + auguri
	'calendario.php',         // Calendario appuntamenti corsi (prenotazioni, pagamenti)
	'sezioni.php',            // Visibilità sezioni + permessi collaboratori per nome
	'abbonamenti.php',        // Stato abbonamento (gating aree di livello superiore)
	'aiuto.php',              // Helper aiuto/suggerimenti per le sfogline
	'faq.php',                // FAQ - Domande frequenti, pubbliche, raggruppate per argomento
	'novita.php',             // Novità: annunci pubblici di chi gestisce il portale, con aeroplanino opzionale
	'inbox.php',              // Posta interna staff (messaggi, risposte, cestino)
	'biografia.php',          // Biografia Vetrina (testo+foto+video, con approvazione)
	'control-panel.php',      // pannello di controllo front-end (organizzatrici)
	'cronologia.php',         // "Il Tuo Percorso": cronologia personale (punti, badge, ricette, lezioni)
	'riepilogo-anno.php',     // "Il Tuo Anno in Accademia": riepilogo narrativo personale per anno solare
	'ricerca-globale.php',    // Ricerca unica su Ricettario + Domande Esperto + Lezioni Video
	'registro.php',           // Registro Ufficiale: Gli Allievi dell'Accademia (diplomati con Rina Poletti)
	'traguardi.php',          // Ultimi Traguardi: feed pubblico di diplomi + badge di tutta l'Accademia
	'madrina.php',            // Madrina & Allieva: abbinamento con mini-missioni condivise
	'mappa-squadre.php',      // Illustrazione Italia per Classifica a Squadre
	'side-tabs.php',          // linguette laterali di navigazione (destra)
	'artigiani.php',          // Gli Artigiani della Pasta: vetrine di partner paganti (bonifico)
	'scuole-cucina.php',      // Le Scuole di Cucina: vetrine di partner paganti (bonifico), stesso schema degli Artigiani
	'caroselli.php',          // Caroselli copiabili (sfogline, Artigiani, Scuole) per Home Page e altre pagine
	'nastro-vetrine.php',     // Nastro scorrevole fisso sotto il menu, su tutte le pagine (sfogline+Artigiani+Scuole)
	'palloncino-gigante.php', // Palloncino Gigante: si gonfia a schermo intero e scoppia, con foto delle sfogline
	'stato-generale.php',     // Stato Generale: tutti i servizi accesi/spenti in un colpo d'occhio, con interruttori istantanei
	'admin.php',              // pannello di amministrazione
	'pannello-nuovo.php',     // Pannello Generale: navigazione a rotaia/nastro/Torre di controllo, a tutto schermo
	'pagina-supporter.php',   // Pagina "Diventa Supporter": shortcode [gs_pagina_supporter], generata dal plugin invece che scritta a mano
);

foreach ( $gs_modules as $gs_module ) {
	$gs_path = GS_INC . $gs_module;
	if ( file_exists( $gs_path ) ) {
		require_once $gs_path;
	}
}

// -----------------------------------------------------------------------------
// Attivazione: impostazioni di default + creazione pagine + flush permalink
// -----------------------------------------------------------------------------
function gs_activate() {
	// Registra le impostazioni di default se non esistono.
	$defaults = gs_default_settings();
	$current  = get_option( GS_OPTION, array() );
	if ( ! is_array( $current ) ) {
		$current = array();
	}
	update_option( GS_OPTION, wp_parse_args( $current, $defaults ) );

	// Registra i CPT una volta prima del flush.
	gs_register_cpt();

	// Crea le pagine con gli shortcode.
	gs_create_pages();

	// FAQ - Domande: carica il set di base, senza duplicare se già presente.
	// Resta solo nel menu interno del gaming (side-tabs), non nel menu del
	// sito. Il CPT gs_faq è normalmente registrato su 'init' (come tutti gli
	// altri del progetto): qui lo registriamo esplicitamente prima, per non
	// dipendere dall'ordine con cui 'init' è già scattato durante
	// l'attivazione.
	if ( function_exists( 'gs_faq_register_cpt' ) ) { gs_faq_register_cpt(); }
	if ( function_exists( 'gs_faq_carica_set_base' ) ) { gs_faq_carica_set_base(); }

	// L'Esperto Risponde: crea i due canali storici solo se non c'è già
	// nessun canale configurato (non tocca eventuali canali esistenti).
	if ( function_exists( 'gs_esperti_seed_default' ) ) { gs_esperti_seed_default(); }

	// Programma i cron ricorrenti.
	if ( ! wp_next_scheduled( 'gs_daily_cron' ) ) {
		wp_schedule_event( time() + 60, 'daily', 'gs_daily_cron' );
	}
	if ( ! wp_next_scheduled( 'gs_weekly_cron' ) ) {
		wp_schedule_event( time() + 120, 'weekly', 'gs_weekly_cron' );
	}
	if ( ! wp_next_scheduled( 'gs_hourly_cron' ) ) {
		wp_schedule_event( time() + 180, 'hourly', 'gs_hourly_cron' );
	}

	// Assegna il permesso dedicato del plugin al ruolo Amministratore.
	$admin = get_role( 'administrator' );
	if ( $admin && ! $admin->has_cap( 'gs_manage_gaming' ) ) {
		$admin->add_cap( 'gs_manage_gaming' );
	}

	// Migrazione (v3.201.0): "Team Emilia-Romagna" è stato assorbito in
	// "Team Nord" (Ennio, 2026-08-11) — rimuove la squadra separata dalle
	// impostazioni salvate (se presente da un'attivazione precedente) e
	// sposta chi l'aveva scelta su "Team Nord", senza perdere i suoi punti
	// (i punti sono della sfoglina, non della squadra).
	$gs_s = get_option( GS_OPTION, array() );
	if ( is_array( $gs_s ) && isset( $gs_s['teams'] ) && is_array( $gs_s['teams'] ) ) {
		$idx = array_search( 'Team Emilia-Romagna', $gs_s['teams'], true );
		if ( false !== $idx ) {
			unset( $gs_s['teams'][ $idx ] );
			$gs_s['teams'] = array_values( $gs_s['teams'] );
			update_option( GS_OPTION, $gs_s );
		}
	}
	$gs_utenti_emilia = get_users( array( 'meta_key' => 'gs_team', 'meta_value' => 'Team Emilia-Romagna', 'fields' => 'ID' ) );
	foreach ( $gs_utenti_emilia as $gs_uid ) {
		update_user_meta( (int) $gs_uid, 'gs_team', 'Team Nord' );
	}

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'gs_activate' );

// -----------------------------------------------------------------------------
// Disattivazione
// -----------------------------------------------------------------------------
function gs_deactivate() {
	$timestamp = wp_next_scheduled( 'gs_daily_cron' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'gs_daily_cron' );
	}
	$timestamp_w = wp_next_scheduled( 'gs_weekly_cron' );
	if ( $timestamp_w ) {
		wp_unschedule_event( $timestamp_w, 'gs_weekly_cron' );
	}
	$timestamp_h = wp_next_scheduled( 'gs_hourly_cron' );
	if ( $timestamp_h ) {
		wp_unschedule_event( $timestamp_h, 'gs_hourly_cron' );
	}
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'gs_deactivate' );

// -----------------------------------------------------------------------------
// Registrazione CPT + regola di rewrite per la Vetrina pubblica
// -----------------------------------------------------------------------------
add_action( 'init', 'gs_register_cpt' );

add_action( 'init', function () {
	// Permette URL del tipo /vetrina/?sfoglina=nomeutente (query var).
	add_rewrite_tag( '%sfoglina%', '([^&]+)' );
} );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'sfoglina';
	return $vars;
} );

// -----------------------------------------------------------------------------
// Ripristina "unfiltered_html" per gli amministratori (Ennio, 13/08/2026):
// senza questo permesso — tolto probabilmente da una misura di sicurezza
// dell'hosting — WordPress elimina in silenzio i tag <style> e <script> da
// qualunque contenuto salvato (pagine con codice incollato a mano, come
// "Diventa Supporter", "Scrivici", ecc.), anche se l'account è amministratore.
// Riguarda solo il ruolo amministratore: gli altri ruoli restano protetti.
// -----------------------------------------------------------------------------
add_action( 'admin_init', 'gs_ripristina_unfiltered_html' );
function gs_ripristina_unfiltered_html() {
	$ruolo = get_role( 'administrator' );
	if ( $ruolo && ! $ruolo->has_cap( 'unfiltered_html' ) ) {
		$ruolo->add_cap( 'unfiltered_html' );
	}
}

// -----------------------------------------------------------------------------
// Niente wpautop sulle pagine scritte a mano (Ennio, 13/08/2026): pagine come
// "Diventa Supporter", "Scrivici", "Disciplinare", "Appello" hanno codice
// HTML/CSS incollato a mano nell'editor, riconoscibile dall'id
// "gs-<nome>-page" messo apposta all'inizio del contenuto. WordPress applica
// comunque wpautop() al contenuto salvato (aggiunge <p> e <br /> da solo
// dove vede righe vuote), e su HTML già completo questo spezza i tag invece
// di aiutare — è la causa vera dei link e degli stili che si rompevano a ogni
// salvataggio. Qui si toglie wpautop SOLO per queste pagine riconosciute,
// lasciandolo intatto per articoli e pagine normali.
// -----------------------------------------------------------------------------
add_filter( 'the_content', 'gs_niente_wpautop_pagine_su_misura', 0 );
function gs_niente_wpautop_pagine_su_misura( $content ) {
	if ( is_singular( 'page' ) && preg_match( '/id=(["\'])gs-[a-z0-9-]+-page\1/', $content ) ) {
		remove_filter( 'the_content', 'wpautop' );
	}
	return $content;
}

// -----------------------------------------------------------------------------
// Creazione automatica delle pagine con shortcode
// -----------------------------------------------------------------------------
function gs_create_pages() {
	$pages = array(
		'gs_page_registrazione' => array(
			'title'   => 'Registrati come Sfoglina',
			'content' => '[gs_registrazione]',
		),
		'gs_page_dashboard'     => array(
			'title'   => 'La Mia Sfoglia',
			'content' => '[gs_dashboard]',
		),
		'gs_page_sfida'         => array(
			'title'   => 'Sfida della Settimana',
			'content' => '[gs_sfida_corrente][gs_galleria_sfida]',
		),
		'gs_page_classifica'    => array(
			'title'   => 'Classifica',
			'content' => '[gs_classifica][gs_squadre_classifica]',
		),
		'gs_page_diario'        => array(
			'title'   => 'Diario dell\'Impasto',
			'content' => '[gs_diario]',
		),
		'gs_page_consigli'      => array(
			'title'   => 'Consigli della Community',
			'content' => '[gs_consigli]',
		),
		'gs_page_badge'         => array(
			'title'   => 'Badge e Traguardi',
			'content' => '[gs_badge_lista]',
		),
		'gs_page_barometro'     => array(
			'title'   => 'Guida Stagionale',
			'content' => '[gs_barometro]',
		),
		'gs_page_galleria'      => array(
			'title'   => 'Galleria delle Sfogline',
			'content' => '[gs_galleria_pubblica]',
		),
		'gs_page_vetrina'       => array(
			'title'   => 'Vetrina',
			'content' => '[gs_vetrina]',
		),
		'gs_page_pannello'      => array(
			'title'   => 'Pannello di Controllo',
			'content' => '[gs_pannello]',
		),
		'gs_page_sfogline'      => array(
			'title'   => 'Le Sfogline',
			'content' => '[gs_sfogline]',
		),
		'gs_page_area_pro'      => array(
			'title'   => 'Area Professionale',
			'content' => '[gs_area_pro]',
		),
		'gs_page_messaggi'      => array(
			'title'   => 'Messaggi',
			'content' => '[gs_messaggi]',
		),
		'gs_page_esperto'       => array(
			'title'   => 'L\'Esperto Risponde',
			'content' => '[gs_esperto]',
		),
		'gs_page_compleanni'    => array(
			'title'   => 'I Compleanni di Oggi',
			'content' => '[gs_compleanni]',
		),
		'gs_page_calendario'    => array(
			'title'   => 'Calendario Corsi',
			'content' => '[gs_calendario]',
		),
		'gs_page_iscrizione'    => array(
			'title'   => 'Iscrizione',
			'content' => '[gs_registrazione]',
		),
		'gs_page_ricettario'    => array(
			'title'   => 'Le tue ricette di famiglia',
			'content' => '[gs_ricettario]',
		),
		'gs_page_lezioni'       => array(
			'title'   => 'Libreria Video delle Lezioni',
			'content' => '[gs_lezioni]',
		),
		'gs_page_percorso_personale' => array(
			'title'   => 'Il Tuo Percorso',
			'content' => '[gs_percorso_personale]',
		),
		'gs_page_riepilogo_anno' => array(
			'title'   => 'Il Tuo Anno in Accademia',
			'content' => '[gs_il_mio_anno]',
		),
		'gs_page_ricerca_globale' => array(
			'title'   => 'Cerca in tutto il sito',
			'content' => '[gs_ricerca_globale]',
		),
		'gs_page_dicono_di_noi' => array(
			'title'   => 'Dicono di Noi',
			'content' => '[gs_dicono_di_noi]',
		),
		'gs_page_registro'      => array(
			'title'   => "Il Registro Ufficiale dell'Accademia della Sfoglia",
			'content' => '[gs_registro_ufficiale]',
		),
		'gs_page_traguardi'     => array(
			'title'   => 'Ultimi Traguardi',
			'content' => '[gs_ultimi_traguardi]',
		),
		'gs_page_faq'           => array(
			'title'   => 'FAQ - Domande',
			'content' => '[gs_faq]',
		),
		'gs_page_novita'        => array(
			'title'   => 'Novità',
			'content' => '[gs_novita]',
		),
		'gs_page_misurata'      => array(
			'title'   => 'La Sfoglia Misurata',
			'content' => '[gs_sfoglia_misurata]',
		),
		'gs_page_giuria'        => array(
			'title'   => 'La Giuria a Turno',
			'content' => '[gs_giuria_turno]',
		),
		'gs_page_piatti_estinzione' => array(
			'title'   => 'Adotta un Piatto in Via di Estinzione',
			'content' => '[gs_piatti_estinzione]',
		),
		'gs_page_matterello'    => array(
			'title'   => 'Il Matterello Parlante',
			'content' => '[gs_matterello_parlante]',
		),
		'gs_page_anno_fa_oggi'  => array(
			'title'   => 'Un Anno Fa Oggi',
			'content' => '[gs_anno_fa_oggi]',
		),
		'gs_page_testamenti'    => array(
			'title'   => 'I Testamenti delle Maestre',
			'content' => '[gs_testamenti]',
		),
		'gs_page_cassaforte'    => array(
			'title'   => 'La Cassaforte del Sapere',
			'content' => '[gs_cassaforte_sapere]',
		),
		'gs_page_sfoglia_insegna' => array(
			'title'   => 'La Sfoglia che Insegna Se Stessa',
			'content' => '[gs_sfoglia_insegna]',
		),
		'gs_page_letture'       => array(
			'title'   => 'Le Letture dei Grandi Protagonisti della Cucina',
			'content' => '[gs_letture]',
		),
		'gs_page_iscrizione_lettore' => array(
			'title'   => 'Iscriviti per Commentare',
			'content' => '[gs_iscrizione_lettore]',
		),
		'gs_page_sondaggi'      => array(
			'title'   => 'Sondaggi',
			'content' => '[gs_sondaggi]',
		),
		'gs_page_login'         => array(
			'title'   => 'Accedi',
			'content' => '[gs_login]',
		),
		'gs_page_artigiani'     => array(
			'title'   => 'Gli Artigiani della Pasta',
			'content' => '[gs_artigiani]',
		),
		'gs_page_artigiano_pannello' => array(
			'title'   => 'La Mia Vetrina',
			'content' => '[gs_vetrina_artigiano_pannello]',
		),
		'gs_page_scuole'        => array(
			'title'   => 'Le Scuole di Cucina',
			'content' => '[gs_scuole_cucina]',
		),
		'gs_page_scuola_pannello' => array(
			'title'   => 'La Mia Vetrina',
			'content' => '[gs_vetrina_scuola_pannello]',
		),
	);

	foreach ( $pages as $option_key => $page ) {
		$existing = (int) get_option( $option_key );
		if ( $existing && 'page' === get_post_type( $existing ) && 'trash' !== get_post_status( $existing ) ) {
			continue; // già esistente
		}

		$page_id = wp_insert_post( array(
			'post_title'   => $page['title'],
			'post_content' => $page['content'],
			'post_status'  => 'publish',
			'post_type'    => 'page',
		) );

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_option( $option_key, $page_id );
		}
	}
}

// -----------------------------------------------------------------------------
// Classe sul body per lo sfondo crema nelle pagine del plugin
// -----------------------------------------------------------------------------
function gs_body_class( $classes ) {
	if ( ! is_singular() ) { return $classes; }
	$p = get_post();
	if ( ! $p ) { return $classes; }

	// 1) Se è una delle pagine create dal plugin (confronto per ID salvato).
	$opzioni = array(
		'gs_page_dashboard', 'gs_page_sfida', 'gs_page_classifica', 'gs_page_sfogline',
		'gs_page_galleria', 'gs_page_badge', 'gs_page_diario', 'gs_page_consigli',
		'gs_page_barometro', 'gs_page_vetrina', 'gs_page_registrazione', 'gs_page_messaggi',
		'gs_page_esperto', 'gs_page_area_pro', 'gs_page_pannello', 'gs_page_compleanni', 'gs_page_calendario', 'gs_page_iscrizione', 'gs_page_ricettario', 'gs_page_lezioni',
		'gs_page_percorso_personale', 'gs_page_ricerca_globale', 'gs_page_dicono_di_noi', 'gs_page_registro', 'gs_page_traguardi',
		'gs_page_riepilogo_anno', 'gs_page_faq', 'gs_page_novita', 'gs_page_letture', 'gs_page_iscrizione_lettore', 'gs_page_sondaggi', 'gs_page_login',
	);
	foreach ( $opzioni as $opt ) {
		if ( (int) get_option( $opt ) === (int) $p->ID ) { $classes[] = 'gs-page'; return $classes; }
	}

	// 2) Oppure se il contenuto contiene un qualsiasi shortcode del plugin.
	if ( false !== strpos( (string) $p->post_content, '[gs_' ) ) { $classes[] = 'gs-page'; }

	// 3) Pagine "su misura" con HTML incollato a mano (stesso riconoscimento
	// di gs_niente_wpautop_pagine_su_misura sopra: Appello, Diventa Supporter,
	// Disciplinare, Scrivici...): niente titolo di pagina del tema, quindi lo
	// spazio che il tema riserva normalmente per il titolo (margine sopra +
	// padding del contenuto, ~60px) resta vuoto e va ridotto in CSS — segnalato
	// da Ennio il 17/08/2026 con screenshot. Classe dedicata, non "gs-page",
	// perché quella già serve per un'altra regola (lo sfondo crema).
	if ( preg_match( '/id=(["\'])gs-[a-z0-9-]+-page\1/', (string) $p->post_content ) ) {
		$classes[] = 'gs-pagina-su-misura';
	}
	return $classes;
}
add_filter( 'body_class', 'gs_body_class' );

// -----------------------------------------------------------------------------
// Caricamento asset front-end
// -----------------------------------------------------------------------------
function gs_enqueue_assets() {
	wp_enqueue_style( 'gaming-sfogline', GS_URL . 'assets/css/gaming.css', array(), GS_VERSION );
	wp_enqueue_script( 'gaming-sfogline', GS_URL . 'assets/js/gaming.js', array( 'jquery' ), GS_VERSION, true );
	// Cormorant Garamond per le etichette della Ruota dell'Anno nel
	// Calendario Corsi (19/08/2026) — stesso font già usato in "Atelier"
	// nei pannelli, qui caricato anche sul sito pubblico perché la ruota è
	// una vista per i visitatori, non per chi gestisce il portale.
	wp_enqueue_style( 'gs-ruota-font', 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&display=swap', array(), null );
	// Bodoni Moda + Archivo per gs-cs__* (veste nuova dei caroselli in "In
	// Vetrina") RIMOSSI di nuovo il 21/08/2026: caricati su OGNI pagina del
	// sito (questa funzione gira ovunque, non solo dove servono), il nuovo
	// font Google bloccava/ritardava il rendering quel tanto che bastava a
	// mandare in tilt il calcolo JS dell'altezza del menu fisso del tema
	// (--gs-header-h) — intestazione duplicata a metà pagina, sfarfallio,
	// pagina che si blocca (segnalato da Ennio il 21/08/2026 su Rina
	// Poletti, non è un problema di quella pagina sola). Il CSS di gs-cs__*
	// ha già una scaletta di riserva sicura (Georgia/Helvetica), quindi si
	// può restare senza il webfont finché non si trova un modo di caricarlo
	// SOLO sulle pagine che hanno davvero un carosello, non ovunque.

	$s_idx = gs_settings();
	wp_localize_script( 'gaming-sfogline', 'GS_AJAX', array(
		'url'        => admin_url( 'admin-ajax.php' ),
		'admin_url'  => admin_url(),
		'nonce'      => wp_create_nonce( 'gs_ajax' ),
		// Regolazione manuale dell'allineamento dell'indice laterale (in pixel).
		// Valore positivo = la pagina scende di più; negativo = scende di meno.
		'idx_extra'  => isset( $s_idx['idx_extra'] ) ? (int) $s_idx['idx_extra'] : 0, // il calcolo automatico ora tiene conto dell'intestazione appiccicata
		// Dettatura vocale: il titolare può disattivarla in generale o per singola sfoglina.
		'dettatura_vocale' => function_exists( 'gs_dettatura_vocale_abilitata' ) ? gs_dettatura_vocale_abilitata() : true,
		// Voce "Accedi"/"La Mia Sfoglia" inserita nel menu del sito (vedi gaming.js).
		'logged_in'     => is_user_logged_in(),
		'login_url'     => function_exists( 'gs_login_url' ) ? gs_login_url( home_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) ) ) ) : wp_login_url(),
		'dashboard_url' => function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_dashboard' ) : home_url( '/' ),
		// Dimensione del logo sponsor sullo striscione dell'Aeroplanino, in
		// pixel — regolabile da Ennio nel pannello (richiesto il 18/08/2026).
		'aereo_logo_dimensione' => isset( $s_idx['aereo_logo_dimensione'] ) ? (int) $s_idx['aereo_logo_dimensione'] : 52,
	) );

	$m = gs_media_settings();
	wp_localize_script( 'gaming-sfogline', 'GS_MEDIA', array(
		'comprimi'  => ! empty( $m['comprimi_foto'] ) ? 1 : 0,
		'max_lato'  => (int) ( $m['foto_max_lato'] ?? 1600 ),
		'limite_mb' => (float) ( $m['limite_mb'] ?? 8 ),
	) );

	// Messaggi e conversazioni non lette della sfoglina: servono all'animazione
	// dell'aeroplanino ("messaggio in arrivo" / "nuova risposta nella conversazione").
	$non_letti = 0;
	if ( is_user_logged_in() && function_exists( 'gs_messaggi_non_letti' ) ) {
		$non_letti = (int) gs_messaggi_non_letti( get_current_user_id() );
	}
	$conv_non_letti = 0;
	if ( is_user_logged_in() && function_exists( 'gs_conv_non_letti' ) ) {
		$conv_non_letti = (int) gs_conv_non_letti( get_current_user_id() );
	}
	wp_localize_script( 'gaming-sfogline', 'GS_MSG', array(
		'non_letti'      => $non_letti,
		'conv_non_letti' => $conv_non_letti,
		// Chi gestisce il portale (titolare o collaboratore) vede anche gli
		// avvisi "aeroplanino" inviati dalla redazione, su ogni suo dispositivo
		// collegato in quel momento — le sfogline invece li ricevono già dalla
		// coda personale qui sopra e non devono vederli una seconda volta.
		'gestore'        => ( is_user_logged_in() && function_exists( 'gs_can_manage' ) && gs_can_manage() ) ? 1 : 0,
		// Link per rendere cliccabile l'aeroplanino di "messaggio in arrivo" /
		// "nuova risposta nella conversazione": porta dritto alla sezione giusta.
		'msg_url'        => function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_messaggi' ) : '',
	) );

	// Libreria media di WordPress: solo sulla pagina del Pannello di Controllo,
	// per scegliere foto già caricate sul sito nelle Diplomi e Locandine.
	$pannello_id = (int) get_option( 'gs_page_pannello' );
	if ( $pannello_id && is_page( $pannello_id ) && function_exists( 'gs_can_manage' ) && gs_can_manage() ) {
		wp_enqueue_media();
	}
}
add_action( 'wp_enqueue_scripts', 'gs_enqueue_assets', 99 );
