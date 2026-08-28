<?php
/**
 * sezioni.php — Visibilità e permessi delle sezioni.
 *
 * Dal pannello (solo il titolare, capacità manage_options) puoi:
 *  • rendere ogni sezione VISIBILE o NASCOSTA (nasconde linguetta e blocca lo
 *    shortcode della pagina pubblica);
 *  • per le sezioni gestibili, scegliere per NOME quali collaboratori possono
 *    vederle/gestirle nel pannello.
 *
 * Impostazioni salvate in gs_settings:
 *  • 'sez_hidden' => array( chiave => true )         (sezioni nascoste)
 *  • 'sez_collab' => array( chiave => array(uid,…) ) (collaboratori abilitati)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Registro delle sezioni: chiave => etichetta, pagina, shortcode, zona pannello. */
function gs_sez_registry() {
	return array(
		'sfida'       => array( 'label' => 'Le Sfide',              'page' => 'gs_page_sfida',      'sc' => array( 'gs_sfida_corrente', 'gs_galleria_sfida' ), 'zona' => true, 'livello' => 'base' ),
		'classifica'  => array( 'label' => 'Classifica',            'page' => 'gs_page_classifica', 'sc' => array( 'gs_classifica', 'gs_squadre_classifica' ), 'zona' => true, 'livello' => 'base' ),
		'sfogline'    => array( 'label' => 'Le Sfogline',           'page' => 'gs_page_sfogline',   'sc' => array( 'gs_sfogline' ), 'zona' => true, 'livello' => 'base' ),
		'ricettario'  => array( 'label' => 'Ricettario delle Famiglie', 'page' => 'gs_page_ricettario', 'sc' => array( 'gs_ricettario' ), 'zona' => true, 'livello' => 'base' ),
		'indovina'    => array( 'label' => 'Indovina la Sfoglia',      'page' => 'gs_page_indovina',   'sc' => array( 'gs_indovina' ), 'zona' => true, 'livello' => 'base' ),
		'tavolo'      => array( 'label' => 'Il Tavolo di Lavoro',      'page' => 'gs_page_tavolo',     'sc' => array( 'gs_tavolo' ), 'zona' => true, 'livello' => 'base' ),
		'misurata'    => array( 'label' => 'La Sfoglia Misurata',      'page' => 'gs_page_misurata',   'sc' => array( 'gs_sfoglia_misurata' ), 'zona' => true, 'livello' => 'base' ),
		'giuria'      => array( 'label' => 'La Giuria a Turno',        'page' => 'gs_page_giuria',     'sc' => array( 'gs_giuria_turno' ), 'zona' => true, 'livello' => 'base' ),
		'sondaggi'    => array( 'label' => 'Sondaggi',                  'page' => 'gs_page_sondaggi',   'sc' => array( 'gs_sondaggi' ), 'zona' => true, 'livello' => 'base' ),
		'piatti_estinzione' => array( 'label' => 'Adotta un Piatto in Via di Estinzione', 'page' => 'gs_page_piatti_estinzione', 'sc' => array( 'gs_piatti_estinzione' ), 'zona' => true, 'livello' => 'base' ),
		'matterello'  => array( 'label' => 'Il Matterello Parlante', 'page' => 'gs_page_matterello', 'sc' => array( 'gs_matterello_parlante' ), 'zona' => true, 'livello' => 'base' ),
		'anno_fa_oggi' => array( 'label' => 'Un Anno Fa Oggi', 'page' => 'gs_page_anno_fa_oggi', 'sc' => array( 'gs_anno_fa_oggi' ), 'zona' => false, 'livello' => 'base' ),
		'testamenti'  => array( 'label' => 'I Testamenti delle Maestre', 'page' => 'gs_page_testamenti', 'sc' => array( 'gs_testamenti' ), 'zona' => false, 'livello' => 'base' ),
		'cassaforte'  => array( 'label' => 'La Cassaforte del Sapere', 'page' => 'gs_page_cassaforte', 'sc' => array( 'gs_cassaforte_sapere' ), 'zona' => true, 'livello' => 'base' ),
		'sfoglia_insegna' => array( 'label' => 'La Sfoglia che Insegna Se Stessa', 'page' => 'gs_page_sfoglia_insegna', 'sc' => array( 'gs_sfoglia_insegna' ), 'zona' => true, 'livello' => 'base' ),
		'letture'     => array( 'label' => 'Le Letture dei Grandi Protagonisti della Cucina', 'page' => 'gs_page_letture', 'sc' => array( 'gs_letture' ), 'zona' => true, 'livello' => 'base' ),
		'piano_anno'  => array( 'label' => 'Pianificazione dell\'Anno', 'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'promemoria'  => array( 'label' => 'Promemoria giornaliero',   'page' => 'gs_page_promemoria', 'sc' => array( 'gs_promemoria' ), 'zona' => false, 'livello' => 'base' ),
		'lezioni'     => array( 'label' => 'Libreria Video delle Lezioni', 'page' => 'gs_page_lezioni', 'sc' => array( 'gs_lezioni' ), 'zona' => true, 'livello' => 'superiore' ),
		'percorso_personale' => array( 'label' => 'Il Tuo Percorso', 'page' => 'gs_page_percorso_personale', 'sc' => array( 'gs_percorso_personale' ), 'zona' => false, 'livello' => 'base' ),
		'riepilogo_anno' => array( 'label' => 'Il Tuo Anno in Accademia', 'page' => 'gs_page_riepilogo_anno', 'sc' => array( 'gs_il_mio_anno' ), 'zona' => false, 'livello' => 'base' ),
		'ricerca_globale' => array( 'label' => 'Cerca in tutto il sito', 'page' => 'gs_page_ricerca_globale', 'sc' => array( 'gs_ricerca_globale' ), 'zona' => true, 'livello' => 'base' ),
		'dicono_di_noi' => array( 'label' => 'Dicono di Noi', 'page' => 'gs_page_dicono_di_noi', 'sc' => array( 'gs_dicono_di_noi' ), 'zona' => true, 'livello' => 'base' ),
		'registro'    => array( 'label' => "Il Registro Ufficiale dell'Accademia della Sfoglia",  'page' => 'gs_page_registro',   'sc' => array( 'gs_registro_ufficiale' ), 'zona' => true, 'livello' => 'base' ),
		'traguardi'   => array( 'label' => 'Ultimi Traguardi',      'page' => 'gs_page_traguardi',  'sc' => array( 'gs_ultimi_traguardi' ), 'zona' => false, 'livello' => 'base' ),
		'compleanni'  => array( 'label' => 'I Compleanni di Oggi',  'page' => 'gs_page_compleanni', 'sc' => array( 'gs_compleanni' ), 'zona' => true, 'livello' => 'base' ),
		'calendario'  => array( 'label' => 'Calendario Corsi',      'page' => 'gs_page_calendario', 'sc' => array( 'gs_calendario' ), 'zona' => true, 'livello' => 'superiore' ),
		'galleria'    => array( 'label' => 'Galleria',              'page' => 'gs_page_galleria',   'sc' => array( 'gs_galleria_pubblica' ), 'zona' => true, 'livello' => 'base' ),
		'badge'       => array( 'label' => 'Badge',                 'page' => 'gs_page_badge',      'sc' => array( 'gs_badge_lista' ), 'zona' => true, 'livello' => 'base' ),
		'diario'      => array( 'label' => 'Diario',                'page' => 'gs_page_diario',     'sc' => array( 'gs_diario' ), 'zona' => false, 'livello' => 'base' ),
		'consigli'    => array( 'label' => 'Consigli',              'page' => 'gs_page_consigli',   'sc' => array( 'gs_consigli' ), 'zona' => false, 'livello' => 'base' ),
		'stagionale'  => array( 'label' => 'Guida Stagionale',      'page' => 'gs_page_barometro',  'sc' => array( 'gs_barometro' ), 'zona' => false, 'livello' => 'base' ),
		'vetrina'     => array( 'label' => 'Vetrina',               'page' => 'gs_page_vetrina',    'sc' => array( 'gs_vetrina' ), 'zona' => false, 'livello' => 'base' ),
		'area_pro'    => array( 'label' => 'Corsi Online',          'page' => 'gs_page_area_pro',   'sc' => array( 'gs_area_pro' ), 'zona' => true, 'livello' => 'superiore' ),
		'messaggi'    => array( 'label' => 'Messaggi',              'page' => 'gs_page_messaggi',   'sc' => array( 'gs_messaggi' ), 'zona' => false, 'livello' => 'superiore' ),
		'esperto'     => array( 'label' => "L'Esperto Risponde",    'page' => 'gs_page_esperto',    'sc' => array( 'gs_esperto' ), 'zona' => true, 'livello' => 'superiore' ),
		'conversazioni' => array( 'label' => 'Conversazioni private', 'page' => '',                 'sc' => array(), 'zona' => true, 'livello' => 'superiore' ),
		'aiuto'       => array( 'label' => 'Aiuto e Suggerimenti',  'page' => '',                 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'inbox'       => array( 'label' => 'Posta interna',        'page' => '',                 'sc' => array(), 'zona' => true, 'livello' => 'superiore' ),

		// -------------------------------------------------------------------
		// Pannelli del Pannello di Controllo senza una pagina pubblica propria
		// (page/sc vuoti: "Visibile" qui nasconde l'intero pannello, non una
		// pagina del sito) — aggiunti per rendere assegnabile per nome anche
		// questi strumenti, non solo le sezioni con pagina pubblica.
		// -------------------------------------------------------------------
		'abbonamenti'            => array( 'label' => 'Abbonamenti delle sfogline',              'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'token'                  => array( 'label' => 'Token per le consulenze private',         'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'biografie'              => array( 'label' => 'Biografie della Vetrina (approvazione)',   'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'correzione_punti'       => array( 'label' => 'Correggi punti di una sfoglina',           'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'premio'                 => array( 'label' => 'Premio di Fine Anno',                      'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'regia'                  => array( 'label' => 'Regia del Gaming (proposte e direttive)',   'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'cruscotto'              => array( 'label' => 'Il Cruscotto della Verità',                 'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'sfide_blindate'         => array( 'label' => 'Sfide blindate (ammissione concorrenti)',  'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'riepilogo_bacheca'      => array( 'label' => 'Bacheca di riepilogo (numeri del giorno)', 'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'silenzio'               => array( 'label' => 'La Sfida del Silenzio (classifica congelata)', 'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'blackout'               => array( 'label' => 'Oscura il Gaming (blackout)',              'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'vetrina_toggle'         => array( 'label' => 'Interruttore Vetrina pubblica',            'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'richieste_iscrizione'   => array( 'label' => 'Richieste di Iscrizione',                  'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'indice_calibrazione'    => array( 'label' => 'Indice laterale — allineamento sezioni',   'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'menu_pagine'            => array( 'label' => 'Pagine pubbliche nel menu del sito',       'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'menu_struttura'         => array( 'label' => 'Applica la struttura del menu',            'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'diagnostica'            => array( 'label' => 'Diagnostica e stato di salute',            'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'export_dati'            => array( 'label' => 'Esporta i dati del percorso',                    'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'locandine'              => array( 'label' => 'Diplomi e Locandine',                      'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'ricette_libro'          => array( 'label' => 'Ricette per il Libro',                     'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'premi_traguardi'        => array( 'label' => 'Premi per Traguardo',                      'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'faq'                    => array( 'label' => 'FAQ - Domande',                            'page' => 'gs_page_faq', 'sc' => array( 'gs_faq' ), 'zona' => true, 'livello' => 'base' ),
		'novita'                 => array( 'label' => 'Novità',                                   'page' => 'gs_page_novita', 'sc' => array( 'gs_novita' ), 'zona' => true, 'livello' => 'base' ),
		'madrine'                => array( 'label' => 'Madrina & Allieva',                        'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'media_limiti'           => array( 'label' => 'Foto e video — compressione e limiti',     'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'backup'                 => array( 'label' => 'Backup automatico dei file',               'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'messaggi_privati'       => array( 'label' => 'Messaggi privati alle sfogline (invio)',   'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'messaggi_sfogline_view' => array( 'label' => 'Messaggi di ogni sfoglina (vista)',        'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'moderazione'            => array( 'label' => 'Moderazione di tutte le chat',             'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'spiegazioni'            => array( 'label' => 'Spiegazioni delle Sezioni',                'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'notifiche_pref'         => array( 'label' => 'Notifiche per sfoglina',                   'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'percorsi_lezioni'       => array( 'label' => 'Percorsi Guidati',                         'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'cerca_sfoglina'         => array( 'label' => 'Cerca sfoglina e recupera file',           'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'sfogline_view'          => array( 'label' => 'Aspetto della pagina «Le Sfogline»',       'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'caroselli'              => array( 'label' => 'Caroselli per la Home Page',              'page' => '', 'sc' => array( 'gs_carosello_sfogline', 'gs_carosello_artigiani', 'gs_carosello_scuole' ), 'zona' => true, 'livello' => 'base' ),
		'messaggio_benvenuto'    => array( 'label' => 'Messaggio di benvenuto in «La Mia Sfoglia»', 'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'come_funziona'          => array( 'label' => 'Come funziona il Percorso (guida in «La Mia Sfoglia»)', 'page' => '', 'sc' => array( 'gs_come_funziona' ), 'zona' => true, 'livello' => 'base' ),
		'aeroplanino'            => array( 'label' => 'Aeroplanino — messaggio istantaneo',        'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'palloncini'             => array( 'label' => 'Palloncini — festeggiamenti in diretta',      'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
		'palloncino_gigante'     => array( 'label' => 'Palloncino Gigante — festeggiamenti in grande', 'page' => '', 'sc' => array(), 'zona' => true, 'livello' => 'base' ),
	);
}

function gs_sez_hidden_map() {
	$s = gs_settings();
	return isset( $s['sez_hidden'] ) && is_array( $s['sez_hidden'] ) ? $s['sez_hidden'] : array();
}
function gs_sez_collab_map() {
	$s = gs_settings();
	return isset( $s['sez_collab'] ) && is_array( $s['sez_collab'] ) ? $s['sez_collab'] : array();
}
/** Mappa: sezione => sfogline a cui NASCONDERE la sezione. */
function gs_sez_hideusers_map() {
	$s = gs_settings();
	return isset( $s['sez_hide_users'] ) && is_array( $s['sez_hide_users'] ) ? $s['sez_hide_users'] : array();
}

/** La sezione è visibile al pubblico in generale? */
function gs_sez_visibile( $key ) {
	$h = gs_sez_hidden_map();
	return empty( $h[ $key ] );
}
/** La sezione è visibile a QUESTA sfoglina? (globale + non nascosta a lei + abbonamento) */
function gs_sez_visibile_per( $key, $uid = 0 ) {
	if ( ! gs_sez_visibile( $key ) ) { return false; }
	$uid = $uid ? $uid : get_current_user_id();

	// Abbonamento scaduto: niente aree di livello superiore (solo primo livello pubblico).
	// I gestori non sono soggetti a questa limitazione.
	if ( $uid && function_exists( 'gs_abbonamento_scaduto' ) && ! user_can( $uid, 'gs_manage_gaming' ) && ! user_can( $uid, 'manage_options' ) ) {
		$reg = gs_sez_registry();
		$liv = isset( $reg[ $key ]['livello'] ) ? $reg[ $key ]['livello'] : 'base';
		if ( 'superiore' === $liv && gs_abbonamento_scaduto( $uid ) ) { return false; }
	}

	if ( ! $uid ) { return true; }
	$map  = gs_sez_hideusers_map();
	$list = isset( $map[ $key ] ) && is_array( $map[ $key ] ) ? array_map( 'intval', $map[ $key ] ) : array();
	return ! in_array( (int) $uid, $list, true );
}
/** Un gestore può vedere/gestire la zona di questa sezione nel pannello? */
function gs_sez_collab_ok( $key, $uid = 0 ) {
	$uid = $uid ? $uid : get_current_user_id();
	if ( user_can( $uid, 'manage_options' ) ) { return true; } // il titolare vede sempre tutto
	$map  = gs_sez_collab_map();
	$list = isset( $map[ $key ] ) && is_array( $map[ $key ] ) ? array_map( 'intval', $map[ $key ] ) : array();
	if ( empty( $list ) ) { return true; } // nessuna restrizione = tutti i collaboratori
	return in_array( (int) $uid, $list, true );
}
/** La zona di gestione è accessibile all'utente corrente? (visibile + permesso) */
function gs_sez_zona_ok( $key ) {
	return gs_sez_visibile( $key ) && gs_sez_collab_ok( $key );
}

/**
 * Per le sezioni pubbliche assegnabili per nome (Galleria, Registro,
 * Le Sfogline, Classifica, Le Sfide, Cerca in tutto il sito, Badge): questo
 * collaboratore specifico può vedere la pagina? Riusa lo stesso elenco
 * "collaboratori abilitati" di gs_sez_collab_ok(), ma si applica SOLO ai
 * collaboratori — mai alle sfogline normali (quelle restano soggette solo a
 * gs_sez_visibile_per(), il "nascondi a questa sfoglina" già esistente) né
 * al titolare (sempre esente). Deliberatamente separata da gs_sez_zona_ok()
 * per non toccare le sezioni con un vero pannello di gestione (Ricettario,
 * Lezioni Video, ecc.): lì l'elenco collaboratori controlla solo l'accesso
 * al pannello di approvazione, non la partecipazione pubblica alla sezione.
 */
function gs_sez_collab_pagina_ok( $key, $uid = 0 ) {
	$uid = $uid ? (int) $uid : get_current_user_id();
	if ( ! $uid || ! user_can( $uid, 'gs_manage_gaming' ) || user_can( $uid, 'manage_options' ) ) {
		return true; // non è un collaboratore, o è il titolare: questo controllo non lo riguarda
	}
	return gs_sez_collab_ok( $key, $uid );
}

/** Elenco dei collaboratori (utenti con gs_manage_gaming), per nome. */
function gs_sez_collaboratori() {
	$out = array();
	foreach ( get_users() as $u ) {
		if ( user_can( $u->ID, 'gs_manage_gaming' ) && ! user_can( $u->ID, 'manage_options' ) ) {
			$out[] = $u;
		}
	}
	return $out;
}

// -----------------------------------------------------------------------------
// Blocco degli shortcode delle sezioni nascoste
// -----------------------------------------------------------------------------
add_filter( 'do_shortcode_tag', 'gs_sez_filtra_shortcode', 10, 2 );
function gs_sez_filtra_shortcode( $output, $tag ) {
	static $map = null;
	if ( null === $map ) {
		$map = array();
		foreach ( gs_sez_registry() as $key => $s ) {
			foreach ( $s['sc'] as $sc ) { $map[ $sc ] = $key; }
		}
	}
	if ( isset( $map[ $tag ] ) && ! gs_sez_visibile_per( $map[ $tag ], get_current_user_id() ) ) {
		return '<div class="gs-box"><p>Questa sezione non è disponibile al momento.</p></div>';
	}
	return $output;
}

/** Elenco delle sfogline approvate (non gestori), per nome. */
function gs_sez_sfogline() {
	// Cache per-richiesta: chiamata da più pannelli nella stessa pagina.
	static $cache = null;
	static $cgen  = -1;
	$gen = gs_cache_generation();
	if ( null !== $cache && $cgen === $gen ) {
		return $cache;
	}
	$out = array();
	foreach ( get_users( array( 'orderby' => 'display_name' ) ) as $u ) {
		// Usa il controllo definitivo e condiviso gs_e_sfoglina_vera() (helpers.php):
		// stesso bug corretto qui la prima volta (2026-08-11) e poi ritrovato
		// duplicato altrove — ora c'è un solo posto dove questa regola vive.
		if ( gs_e_sfoglina_vera( $u ) ) {
			$out[] = $u;
		}
	}
	$cache = $out;
	$cgen  = $gen;
	return $cache;
}

// -----------------------------------------------------------------------------
// PANNELLO — Visibilità e permessi delle sezioni (solo titolare)
// -----------------------------------------------------------------------------
function gs_pannello_sezioni() {
	if ( ! current_user_can( 'manage_options' ) ) { return; } // solo il titolare imposta i permessi
	$reg      = gs_sez_registry();
	$hidden   = gs_sez_hidden_map();
	$collab   = gs_sez_collab_map();
	$hideusrs = gs_sez_hideusers_map();
	$colleghi = gs_sez_collaboratori();
	$sfogline = gs_sez_sfogline();

	echo gs_box_open( 'Visibilità delle sezioni e permessi' );
	echo '<p class="gs-hint">Per ogni sezione decidi se è <strong>visibile</strong> sul sito. Puoi anche nasconderla <strong>solo ad alcune sfogline</strong> (tieni premuto Ctrl/Cmd per sceglierne più di una, o per toglierne una già scelta; il pulsante "✕ Deseleziona tutto" sotto l\'elenco svuota la scelta in un colpo solo). Quasi ogni pannello del Controllo Generale è ormai assegnabile <strong>per nome</strong>: scegli quali collaboratori lo vedono e lo gestiscono — di default (nessun nome selezionato) lo vedono tutti i collaboratori. Fanno eccezione solo <strong>Blocco dashboard WP</strong>, <strong>Collaboratori</strong> e questo stesso pannello «Visibilità delle sezioni e permessi»: restano riservati a te, titolare, perché controllano il sistema dei permessi stesso. Per i pannelli <strong>senza una pagina pubblica propria</strong> (colonna "Nascondi a" vuota) l\'interruttore "Visibile" spegne l\'intero strumento — anche per te, se lo disattivi. Tu, come titolare, vedi comunque sempre tutto ciò che resta acceso. Per <strong>Galleria, Registro Ufficiale, Le Sfogline, Classifica, Le Sfide, Cerca in tutto il sito e Badge</strong> — pagine pubbliche senza un pannello di approvazione — "Collaboratori abilitati" ha un significato diverso: non c\'è nulla da gestire, quindi la scelta decide solo <strong>a quali collaboratori nascondere quella pagina</strong> (le sfogline normali non sono mai toccate da questa colonna).</p>';
	if ( array_filter( $hidden ) ) {
		echo '<p class="gs-hint"><button type="button" class="gs-btn gs-btn-sm gs-sez-reset-visibili">Rendi visibili tutte le sezioni</button> — un solo clic per riaccendere tutto quello che risulta nascosto qui sotto, senza dover cercare le singole righe. Non tocca chi vede cosa tra i collaboratori.</p>';
	}
	?>
	<input type="text" class="gs-cerca-input" data-target=".gs-tabella-sezioni" placeholder="🔍 Cerca una sezione…" style="width:100%;max-width:320px;margin-bottom:10px">
	<?php if ( $colleghi ) : ?>
	<p>
		<label><strong>Vedi come collaboratore:</strong>
			<select class="gs-sez-vedi-come">
				<option value="">— Nessuno (vedi la tabella normale) —</option>
				<?php foreach ( $colleghi as $c ) : ?>
					<option value="<?php echo (int) $c->ID; ?>"><?php echo esc_html( $c->display_name ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<span class="gs-hint gs-sez-vedi-come-esito"></span>
	</p>
	<?php endif; ?>
	<form class="gs-form gs-form-sezioni" onsubmit="return false">
		<table class="gs-table gs-paginate gs-tabella-sezioni" data-per-page="15">
			<thead><tr><th>Sezione</th><th>Visibile</th><th>Nascondi a queste sfogline</th><th>Collaboratori abilitati</th></tr></thead>
			<tbody>
			<?php foreach ( $reg as $key => $s ) :
				$is_vis   = empty( $hidden[ $key ] );
				$allow    = isset( $collab[ $key ] ) && is_array( $collab[ $key ] ) ? array_map( 'intval', $collab[ $key ] ) : array();
				$hide_to  = isset( $hideusrs[ $key ] ) && is_array( $hideusrs[ $key ] ) ? array_map( 'intval', $hideusrs[ $key ] ) : array();
			?>
				<tr data-key="<?php echo esc_attr( $key ); ?>">
					<td><strong><?php echo esc_html( $s['label'] ); ?></strong></td>
					<td style="text-align:center">
						<label class="gs-switch"><input type="checkbox" class="gs-sez-vis" <?php checked( $is_vis ); ?>> <span>visibile</span></label>
					</td>
					<td>
						<?php if ( $s['page'] && $sfogline ) : ?>
							<select multiple class="gs-sez-hideusers" size="3" style="min-width:170px">
								<?php foreach ( $sfogline as $u ) : ?>
									<option value="<?php echo (int) $u->ID; ?>" <?php echo in_array( (int) $u->ID, $hide_to, true ) ? 'selected' : ''; ?>><?php echo esc_html( $u->display_name ); ?></option>
								<?php endforeach; ?>
							</select><br>
							<button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-sez-deseleziona" style="margin-top:4px">✕ Deseleziona tutto</button>
						<?php else : ?>
							<span class="gs-hint">—</span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $s['zona'] ) : ?>
							<?php if ( $colleghi ) : ?>
								<?php foreach ( $colleghi as $c ) : ?>
									<label style="display:inline-block;margin:2px 8px 2px 0">
										<input type="checkbox" class="gs-sez-collab" value="<?php echo (int) $c->ID; ?>" <?php checked( in_array( (int) $c->ID, $allow, true ) ); ?>>
										<?php echo esc_html( $c->display_name ); ?>
									</label>
								<?php endforeach; ?>
							<?php else : ?>
								<span class="gs-hint">Nessun collaboratore. Autorizzali dalla sezione «Collaboratori».</span>
							<?php endif; ?>
						<?php else : ?>
							<span class="gs-hint">— (sezione senza pannello dedicato)</span>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-sez-salva">Salva visibilità e permessi</button> <span class="gs-sez-msg gs-richiesta-esito"></span></p>
	</form>
	<?php
	echo gs_box_close();
}

/**
 * Ricostruisce una mappa "chiave sezione => id[]" leggendo SEMPRE l'elenco
 * completo delle sezioni note al server ($reg), non solo le chiavi arrivate
 * in $raw. Una chiave assente in $raw significa "nessuno selezionato per
 * quella sezione", non "non toccare quanto già salvato": il browser
 * (jQuery) non manda affatto un campo per una casella multipla svuotata del
 * tutto, quindi ricostruire leggendo solo $raw lascerebbe intoccata la
 * selezione precedente invece di svuotarla — segnalato da Ennio il
 * 2026-07-30 ("deseleziono le sfogline ma non vengono tolte").
 */
function gs_sez_normalizza_mappa( $raw, $reg ) {
	$raw = is_array( $raw ) ? $raw : array();
	$out = array();
	foreach ( $reg as $key => $s ) {
		$ids = isset( $raw[ $key ] ) ? array_values( array_unique( array_map( 'intval', (array) $raw[ $key ] ) ) ) : array();
		if ( $ids ) { $out[ $key ] = $ids; }
	}
	return $out;
}

add_action( 'wp_ajax_gs_sez_salva', 'gs_ajax_sez_salva' );
function gs_ajax_sez_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( array( 'message' => 'Solo il titolare può cambiare questi permessi.' ) ); }
	$reg = gs_sez_registry();

	$hidden = array();
	$visibili = isset( $_POST['visibili'] ) ? (array) $_POST['visibili'] : array();
	$visibili = array_map( 'sanitize_key', $visibili );
	foreach ( $reg as $key => $s ) {
		if ( ! in_array( $key, $visibili, true ) ) { $hidden[ $key ] = true; }
	}

	$collab     = gs_sez_normalizza_mappa( isset( $_POST['collab'] ) ? $_POST['collab'] : array(), $reg );
	$hide_users = gs_sez_normalizza_mappa( isset( $_POST['hideusers'] ) ? $_POST['hideusers'] : array(), $reg );

	$s = gs_settings();
	$s['sez_hidden']     = $hidden;
	$s['sez_collab']     = $collab;
	$s['sez_hide_users'] = $hide_users;
	update_option( GS_OPTION, $s );

	wp_send_json_success( array( 'message' => 'Impostazioni di visibilità salvate.' ) );
}

/**
 * Riaccende in un colpo solo tutte le sezioni nascoste (sez_hidden svuotato).
 * Non tocca i permessi per collaboratore né "nascondi a" per sfoglina: serve
 * solo a rimediare in fretta se troppe voci sono finite nascoste per errore
 * in una tabella con decine di righe, senza doverle ricercare una per una.
 */
add_action( 'wp_ajax_gs_sez_reset_visibili', 'gs_ajax_sez_reset_visibili' );
function gs_ajax_sez_reset_visibili() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( array( 'message' => 'Solo il titolare può cambiare questi permessi.' ) ); }
	$s = gs_settings();
	$s['sez_hidden'] = array();
	update_option( GS_OPTION, $s );
	wp_send_json_success( array( 'message' => 'Tutte le sezioni sono di nuovo visibili.' ) );
}

/** True se la pagina (chiave opzione) è nascosta a questa sfoglina. */
function gs_sez_page_hidden( $page_key, $uid = 0 ) {
	foreach ( gs_sez_registry() as $k => $s ) {
		if ( $s['page'] === $page_key && ! gs_sez_visibile_per( $k, $uid ) ) { return true; }
	}
	return false;
}
