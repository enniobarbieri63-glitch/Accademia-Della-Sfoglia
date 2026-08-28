<?php
/**
 * pannello-nuovo.php — "Pannello Generale": nuova modalità di navigazione
 * della Plancia, pensata per uno schermo 16:9 o un portatile 16" a tutto
 * schermo, senza scorrimento infinito.
 *
 * NON sostituisce la Plancia Generale classica (gs-generale, in admin.php):
 * è una pagina AGGIUNTIVA nello stesso menu. Ogni sezione qui dentro
 * richiama esattamente la stessa funzione PHP che disegna già quella
 * sezione nella Plancia classica — questo file non duplica nessuna logica
 * di gestione, aggiunge solo un altro modo di arrivarci: una rotaia di
 * gruppi, un nastro di scorciatoie, una ricerca rapida (⌘K) e una "Torre di
 * controllo" con gli allarmi sempre a vista, che carica ogni sezione via
 * AJAX invece di stampare tutte le 60 e passa sezioni in una pagina sola.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Menu — la voce di menu ("🚀 Pannello Generale", in cima all'elenco insieme
// ad "Artigiani della Pasta") è registrata in admin.php → gs_admin_menu(),
// insieme a tutte le altre voci del plugin, non qui: così l'ordine del menu
// si decide in un solo punto invece che in due file diversi.
// -----------------------------------------------------------------------------

// -----------------------------------------------------------------------------
// Registro — gruppi della rotaia (stessi data-idx-group già usati in admin.php)
// -----------------------------------------------------------------------------
/**
 * Gruppi della rotaia: 'stato' resta a parte (non è una vera categoria, è la
 * pagina di riepilogo), tutti gli altri sono letti dal registro unico
 * gs_categorie() (helpers.php) — un solo posto dove nome, icona e colore
 * di ogni categoria sono definiti, richiesto da Ennio il 18/08/2026.
 */
function gs_pn_gruppi() {
	$gruppi = array(
		'stato' => array( 'ico' => '🗂️', 'nome' => 'Stato', 'titolo' => 'Stato Generale', 'colore' => '#5a4a8c' ),
	);
	foreach ( gs_categorie() as $chiave => $cat ) {
		$gruppi[ $chiave ] = array( 'ico' => $cat['ico'], 'nome' => $cat['nome'], 'titolo' => $cat['nome'], 'colore' => $cat['colore'] );
	}
	return $gruppi;
}

/**
 * Registro delle sezioni: ognuna corrisponde a una zona già esistente nella
 * Plancia classica (admin.php). 'id' = lo stesso id="gs-zona-…" quando la
 * zona ne ha uno; per le poche zone senza id proprio nella Plancia classica
 * viene usato un id interno "pn-…", solo per questa pagina.
 * 'cb'  = la funzione PHP che disegna già quella sezione (invariata).
 * 'sez' = la chiave di gs_sez_zona_ok(), quando quella zona ne ha una
 *         (stessa identica chiave già usata in admin.php — nessuna nuova
 *         regola di permesso, solo riletta da qui).
 */
function gs_pn_sezioni() {
	return array(
		array( 'id' => 'gs-zona-stato-generale',   'ico' => '🗂️', 'n' => 'Stato Generale',                      'g' => 'stato',        'cb' => 'gs_pannello_stato_generale',    'sez' => '', 'solo_admin' => true ),
		array( 'id' => 'gs-zona-cerca',            'ico' => '🔍', 'n' => 'Cerca sfoglina e recupera file',      'g' => 'sfogline',     'cb' => 'gs_pannello_cerca_sfoglina',    'sez' => 'cerca_sfoglina' ),
		array( 'id' => 'gs-zona-regia-iscritti',   'ico' => '🎯', 'n' => 'Lista degli Iscritti ai Corsi',       'g' => 'corsi',        'cb' => 'gs_pannello_regia_iscritti',    'sez' => 'regia_iscritti' ),
		array( 'id' => 'gs-zona-piano-anno',       'ico' => '🗓️', 'n' => "Pianificazione dell'Anno",            'g' => 'corsi',        'cb' => 'gs_pannello_pianificazione_anno','sez' => 'piano_anno' ),
		array( 'id' => 'gs-zona-calendario',       'ico' => '📅', 'n' => 'Calendario Corsi',                    'g' => 'corsi',        'cb' => 'gs_pannello_calendario',        'sez' => 'calendario' ),
		array( 'id' => 'gs-zona-locandine',        'ico' => '🖼️', 'n' => 'Diplomi e Locandine',                 'g' => 'corsi',        'cb' => 'gs_pannello_locandine',         'sez' => 'locandine' ),
		array( 'id' => 'gs-zona-iscrizioni',       'ico' => '🧾', 'n' => 'Iscrizioni delle sfogline',           'g' => 'sfogline',     'cb' => 'gs_pn_render_iscrizioni',       'sez' => '' ),
		array( 'id' => 'gs-zona-messaggi',         'ico' => '✉️', 'n' => 'Messaggi alle sfogline',              'g' => 'messaggi',     'cb' => 'gs_pannello_messaggi',          'sez' => 'messaggi_privati' ),
		array( 'id' => 'gs-zona-in-diretta',       'ico' => '🎉', 'n' => 'In diretta: Aeroplanino, Palloncini e Palloncino Gigante', 'g' => 'messaggi', 'cb' => 'gs_pannello_in_diretta', 'sez' => '' ),
		array( 'id' => 'gs-zona-moderazione',      'ico' => '🛡️', 'n' => 'Moderazione di tutte le chat',        'g' => 'messaggi',     'cb' => 'gs_pannello_moderazione',       'sez' => 'moderazione' ),
		array( 'id' => 'gs-zona-spiegazioni',      'ico' => '📖', 'n' => 'Spiegazioni delle Sezioni',           'g' => 'messaggi',     'cb' => 'gs_pannello_spiegazioni',       'sez' => 'spiegazioni' ),
		array( 'id' => 'gs-zona-reset',            'ico' => '⚠️', 'n' => 'Il Reset del gioco',                  'g' => 'impostazioni', 'cb' => 'gs_pannello_reset',             'sez' => '' ),
		array( 'id' => 'gs-zona-messaggi-sfogline','ico' => '📨', 'n' => 'Messaggi di ogni sfoglina',           'g' => 'messaggi',     'cb' => 'gs_pannello_messaggi_sfogline', 'sez' => 'messaggi_sfogline_view' ),
		array( 'id' => 'gs-zona-notifiche-pref',   'ico' => '🔔', 'n' => 'Notifiche per sfoglina',              'g' => 'messaggi',     'cb' => 'gs_pannello_notifiche_pref',    'sez' => 'notifiche_pref' ),
		array( 'id' => 'gs-zona-percorsi-lezioni', 'ico' => '🗺️', 'n' => 'Percorsi Guidati',                    'g' => 'corsi',        'cb' => 'gs_pannello_percorsi_lezioni',  'sez' => 'percorsi_lezioni' ),
		array( 'id' => 'gs-zona-testimonianze',    'ico' => '💬', 'n' => 'Dicono di Noi (approvazione)',        'g' => 'contenuti',    'cb' => 'gs_pannello_testimonianze',     'sez' => 'dicono_di_noi' ),
		array( 'id' => 'gs-zona-madrine',          'ico' => '🤝', 'n' => 'Madrina & Allieva',                   'g' => 'sfogline',     'cb' => 'gs_pannello_madrine',           'sez' => 'madrine' ),
		array( 'id' => 'gs-zona-visibilita',       'ico' => '👁️', 'n' => 'Visibilità sezioni e permessi',       'g' => 'impostazioni', 'cb' => 'gs_pannello_sezioni',           'sez' => '' ),
		array( 'id' => 'gs-zona-menu-pagine',      'ico' => '🔗', 'n' => 'Pagine pubbliche nel menu',           'g' => 'impostazioni', 'cb' => 'gs_pannello_menu_pagine',       'sez' => 'menu_pagine' ),
		array( 'id' => 'gs-zona-menu-struttura',   'ico' => '🧭', 'n' => 'Applica la struttura del menu',       'g' => 'impostazioni', 'cb' => 'gs_pannello_menu_struttura',    'sez' => 'menu_struttura' ),
		array( 'id' => 'gs-zona-benvenuto',        'ico' => '👋', 'n' => 'Messaggio di benvenuto',              'g' => 'impostazioni', 'cb' => 'gs_pannello_percorso',          'sez' => 'messaggio_benvenuto' ),
		array( 'id' => 'gs-zona-come-funziona',    'ico' => '🗺️', 'n' => 'Come funziona il Percorso',              'g' => 'impostazioni', 'cb' => 'gs_pannello_come_funziona',     'sez' => 'come_funziona' ),
		array( 'id' => 'gs-zona-abbonamenti',      'ico' => '🎫', 'n' => 'Abbonamenti',                         'g' => 'pagamenti',    'cb' => 'gs_pannello_abbonamenti',       'sez' => 'abbonamenti' ),
		array( 'id' => 'gs-zona-token',            'ico' => '🎫', 'n' => 'Pagamenti — Token',                   'g' => 'pagamenti',    'cb' => 'gs_pannello_token',             'sez' => 'token' ),
		array( 'id' => 'gs-zona-contenuti',        'ico' => '🍝', 'n' => 'Contenuti del percorso',                  'g' => 'sfide',        'cb' => 'gs_pn_render_contenuti_gioco',  'sez' => '' ),
		array( 'id' => 'gs-zona-ingrediente',      'ico' => '🥄', 'n' => 'Ingrediente Segreto',                 'g' => 'sfide',        'cb' => 'gs_pannello_ingrediente_segreto','sez' => 'ingrediente_segreto' ),
		array( 'id' => 'pn-sfide-blindate',        'ico' => '🔒', 'n' => 'Sfide blindate — ammissione',         'g' => 'sfide',        'cb' => 'gs_pannello_sfide_blindate',    'sez' => 'sfide_blindate' ),
		array( 'id' => 'gs-zona-vetrina',          'ico' => '🔗', 'n' => 'Vetrina pubblica',                    'g' => 'partner',      'cb' => 'gs_pannello_vetrina',           'sez' => 'vetrina_toggle' ),
		array( 'id' => 'gs-zona-esperto',          'ico' => '💬', 'n' => "L'Esperto Risponde",                  'g' => 'messaggi',     'cb' => 'gs_pannello_esperti',           'sez' => 'esperto' ),
		array( 'id' => 'gs-zona-conversazioni',    'ico' => '🔒', 'n' => 'Conversazioni private',               'g' => 'messaggi',     'cb' => 'gs_pannello_conversazioni',     'sez' => 'conversazioni' ),
		array( 'id' => 'gs-zona-compleanni',       'ico' => '🎂', 'n' => 'Compleanni',                          'g' => 'sfogline',     'cb' => 'gs_pannello_compleanni',        'sez' => 'compleanni' ),
		array( 'id' => 'gs-zona-aiuto',            'ico' => '🆘', 'n' => 'Aiuto e suggerimenti',                'g' => 'messaggi',     'cb' => 'gs_pannello_aiuto',             'sez' => 'aiuto' ),
		array( 'id' => 'gs-zona-biografie',        'ico' => '📖', 'n' => 'Biografie da approvare',              'g' => 'sfogline',     'cb' => 'gs_pannello_biografie',         'sez' => 'biografie' ),
		array( 'id' => 'gs-zona-artigiani',        'ico' => '🍝', 'n' => 'Artigiani della Pasta',               'g' => 'partner',      'cb' => 'gs_pannello_artigiani',         'sez' => 'artigiani' ),
		array( 'id' => 'gs-zona-scuole',           'ico' => '🎓', 'n' => 'Scuole di Cucina',                    'g' => 'partner',      'cb' => 'gs_pannello_scuole',            'sez' => 'scuole' ),
		array( 'id' => 'gs-zona-ricettario',       'ico' => '📖', 'n' => 'Ricettario delle Famiglie',           'g' => 'contenuti',    'cb' => 'gs_pannello_ricettario',        'sez' => 'ricettario' ),
		array( 'id' => 'gs-zona-ricette-libro',    'ico' => '📕', 'n' => 'Ricette per il Libro',                'g' => 'contenuti',    'cb' => 'gs_pannello_ricette_libro',     'sez' => 'ricette_libro' ),
		array( 'id' => 'gs-zona-indovina',         'ico' => '🔮', 'n' => 'Indovina la Sfoglia',                 'g' => 'sfide',        'cb' => 'gs_pannello_indovina',          'sez' => 'indovina' ),
		array( 'id' => 'gs-zona-tavolo',           'ico' => '🍳', 'n' => 'Il Tavolo di Lavoro',                 'g' => 'sfide',        'cb' => 'gs_pannello_tavolo',            'sez' => 'tavolo' ),
		array( 'id' => 'gs-zona-misurata',         'ico' => '📏', 'n' => 'La Sfoglia Misurata',                 'g' => 'sfide',        'cb' => 'gs_pannello_sfoglia_misurata',  'sez' => 'misurata' ),
		array( 'id' => 'gs-zona-giuria',           'ico' => '⚖️', 'n' => 'La Giuria a Turno',                   'g' => 'sfide',        'cb' => 'gs_pannello_giuria_turno',      'sez' => 'giuria' ),
		array( 'id' => 'gs-zona-sondaggi',         'ico' => '🗳️', 'n' => 'Sondaggi',                            'g' => 'sfide',        'cb' => 'gs_pannello_sondaggi',          'sez' => 'sondaggi' ),
		array( 'id' => 'gs-zona-faq',              'ico' => '❓', 'n' => 'FAQ - Domande',                       'g' => 'impostazioni', 'cb' => 'gs_pannello_faq',               'sez' => 'faq' ),
		array( 'id' => 'gs-zona-novita',           'ico' => '📣', 'n' => 'Novità',                              'g' => 'contenuti',    'cb' => 'gs_pannello_novita',            'sez' => 'novita' ),
		array( 'id' => 'gs-zona-piatti-estinzione','ico' => '🦕', 'n' => 'Adotta un Piatto in Via di Estinzione','g' => 'contenuti',   'cb' => 'gs_pannello_piatti_estinzione', 'sez' => 'piatti_estinzione' ),
		array( 'id' => 'gs-zona-matterello',       'ico' => '🎙️', 'n' => 'Il Matterello Parlante',              'g' => 'contenuti',    'cb' => 'gs_pannello_matterello_parlante','sez' => 'matterello' ),
		array( 'id' => 'gs-zona-silenzio',         'ico' => '🤫', 'n' => 'La Sfida del Silenzio',               'g' => 'sfide',        'cb' => 'gs_pannello_silenzio',          'sez' => 'silenzio' ),
		array( 'id' => 'gs-zona-cassaforte',       'ico' => '🔐', 'n' => 'La Cassaforte del Sapere',            'g' => 'contenuti',    'cb' => 'gs_pannello_cassaforte_sapere', 'sez' => 'cassaforte' ),
		array( 'id' => 'gs-zona-sfoglia-insegna',  'ico' => '🎓', 'n' => 'La Sfoglia che Insegna Se Stessa',    'g' => 'contenuti',    'cb' => 'gs_pannello_sfoglia_insegna',   'sez' => 'sfoglia_insegna' ),
		array( 'id' => 'gs-zona-letture',          'ico' => '📖', 'n' => 'Le Letture dei Grandi Protagonisti',  'g' => 'contenuti',    'cb' => 'gs_pannello_letture',           'sez' => 'letture' ),
		array( 'id' => 'gs-zona-lezioni',          'ico' => '🎬', 'n' => 'Libreria Video delle Lezioni',        'g' => 'corsi',        'cb' => 'gs_pannello_lezioni',           'sez' => 'lezioni' ),
		array( 'id' => 'gs-zona-premi-traguardi',  'ico' => '🎁', 'n' => 'Premi per Traguardo',                 'g' => 'sfide',        'cb' => 'gs_pannello_premi_traguardi',   'sez' => 'premi_traguardi' ),
		array( 'id' => 'gs-zona-aspetto',          'ico' => '🎨', 'n' => 'Aspetto «Le Sfogline»',               'g' => 'partner',      'cb' => 'gs_pannello_sfogline_view',     'sez' => 'sfogline_view' ),
		array( 'id' => 'gs-zona-caroselli',        'ico' => '🎠', 'n' => 'Caroselli per la Home Page',          'g' => 'messaggi',     'cb' => 'gs_pannello_caroselli',         'sez' => 'caroselli' ),
		array( 'id' => 'gs-zona-posta',            'ico' => '📬', 'n' => 'Posta interna',                       'g' => 'messaggi',     'cb' => 'gs_pannello_inbox',             'sez' => 'inbox' ),
		array( 'id' => 'gs-zona-blackout',         'ico' => '🌙', 'n' => 'Blackout',                            'g' => 'impostazioni', 'cb' => 'gs_pannello_blackout',          'sez' => 'blackout' ),
		array( 'id' => 'gs-zona-media',            'ico' => '🖼️', 'n' => 'Foto e video',                        'g' => 'strumenti',    'cb' => 'gs_pannello_media',             'sez' => 'media_limiti' ),
		array( 'id' => 'gs-zona-backup',           'ico' => '💾', 'n' => 'Backup dei file',                     'g' => 'strumenti',    'cb' => 'gs_pannello_backup',            'sez' => 'backup' ),
		array( 'id' => 'gs-zona-export',           'ico' => '📤', 'n' => 'Esporta i dati del percorso',               'g' => 'strumenti',    'cb' => 'gs_pannello_export_dati',       'sez' => 'export_dati' ),
		array( 'id' => 'gs-zona-diagnostica',      'ico' => '🩺', 'n' => 'Diagnostica e stato di salute',       'g' => 'strumenti',    'cb' => 'gs_pannello_diagnostica',       'sez' => 'diagnostica' ),
		array( 'id' => 'gs-zona-cruscotto',        'ico' => '📊', 'n' => 'Il Cruscotto della Verità',           'g' => 'strumenti',    'cb' => 'gs_pannello_cruscotto',         'sez' => 'cruscotto' ),
		array( 'id' => 'pn-area-pro',              'ico' => '🎓', 'n' => 'Area Corsi Online',                   'g' => 'corsi',        'cb' => 'gs_pannello_area_pro',          'sez' => 'area_pro' ),
		array( 'id' => 'gs-zona-giudici-gara',     'ico' => '⚖️', 'n' => 'Sfogline in gara — giudici',          'g' => 'sfide',        'cb' => 'gs_pannello_giudici_gara',      'sez' => 'giudici_gara' ),
		array( 'id' => 'pn-correzione-punti',      'ico' => '✏️', 'n' => 'Correzione punti',                    'g' => 'sfogline',     'cb' => 'gs_pannello_correzione_punti',  'sez' => 'correzione_punti' ),
		array( 'id' => 'pn-premio',                'ico' => '🎓', 'n' => 'Premio di Fine Anno',                 'g' => 'sfide',        'cb' => 'gs_pannello_premio',            'sez' => 'premio' ),
		array( 'id' => 'gs-zona-regia',            'ico' => '🎬', 'n' => 'Regia del Gaming',                    'g' => 'sfide',        'cb' => 'gs_pannello_regia',             'sez' => 'regia' ),
		array( 'id' => 'pn-classifiche',           'ico' => '📊', 'n' => 'Classifiche & esportazioni',          'g' => 'sfide',        'cb' => 'gs_pn_render_classifiche',      'sez' => '' ),
		array( 'id' => 'pn-impostazioni',          'ico' => '⚙️', 'n' => 'Impostazioni generali',               'g' => 'impostazioni', 'cb' => 'gs_pn_render_impostazioni',     'sez' => '' ),
		array( 'id' => 'pn-collaboratori',         'ico' => '👥', 'n' => 'Collaboratori del pannello',          'g' => 'strumenti',    'cb' => 'gs_pn_render_collaboratori',    'sez' => '', 'solo_admin' => true ),
	);
}

/** La voce del registro con quell'id, o null. */
function gs_pn_trova_sezione( $id ) {
	foreach ( gs_pn_sezioni() as $s ) {
		if ( $s['id'] === $id ) { return $s; }
	}
	return null;
}

// -----------------------------------------------------------------------------
// Involucri per le poche zone che nella Plancia classica sono disegnate
// inline in admin.php invece che da una funzione propria — riproducono
// esattamente la stessa cosa, chiamando le stesse funzioni dati.
// -----------------------------------------------------------------------------

function gs_pn_render_iscrizioni() {
	$pending = function_exists( 'gs_get_pending_users' ) ? gs_get_pending_users() : array();
	echo '<p class="gs-zone-desc">Le nuove sfogline restano "in attesa" finché non le approvi. Verifica prima il pagamento della quota, poi approva (ricevono l\'email di conferma) o rifiuta.</p>';
	// Scorciatoia verso "Visibilità sezioni e permessi" (Ennio, 20/08/2026:
	// "metti una copia del bottone dentro le sfogline") — stesso pannello di
	// sempre, solo raggiungibile anche da qui invece che solo dal gruppo
	// Impostazioni. Riservato al titolare, come il pannello di destinazione.
	if ( current_user_can( 'manage_options' ) ) {
		echo '<p><button type="button" class="gs-btn gs-btn-sm gs-btn-ghost" data-apri="Visibilità sezioni e permessi">👁️ Visibilità sezioni e permessi</button></p>';
	}
	if ( function_exists( 'gs_pannello_richieste_inner' ) ) {
		gs_pannello_richieste_inner( $pending );
	}
	if ( function_exists( 'gs_pannello_invio_mail_area_riservata' ) ) {
		gs_pannello_invio_mail_area_riservata();
	}
}

function gs_pn_render_contenuti_gioco() {
	$attiva = function_exists( 'gs_get_active_challenge' ) ? gs_get_active_challenge() : null;
	?>
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
	<?php
}

function gs_pn_render_classifiche() {
	?>
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
	<?php
}

function gs_pn_render_impostazioni() {
	echo '<p class="gs-zone-desc">Punti per azione, livelli, squadre, missioni, anti-spam, OneSignal e testo del premio.</p>';
	echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=gs-impostazioni' ) ) . '">Apri le Impostazioni complete</a></p>';
}

function gs_pn_render_collaboratori() {
	if ( function_exists( 'gs_pannello_collaboratrici' ) ) {
		gs_pannello_collaboratrici();
	}
}

// -----------------------------------------------------------------------------
// Dispatcher: esegue una sezione con GLI STESSI controlli di permesso della
// Plancia classica, e ne cattura l'output HTML.
// -----------------------------------------------------------------------------
function gs_pn_esegui_sezione( $id ) {
	$s = gs_pn_trova_sezione( $id );
	if ( ! $s ) {
		return new WP_Error( 'gs_pn', 'Sezione non trovata.' );
	}
	if ( ! empty( $s['solo_admin'] ) && ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'gs_pn', 'Solo un amministratore può aprire questa sezione.' );
	}
	if ( $s['sez'] && function_exists( 'gs_sez_zona_ok' ) && ! gs_sez_zona_ok( $s['sez'] ) ) {
		return new WP_Error( 'gs_pn', 'Non hai il permesso per questa sezione.' );
	}
	if ( ! function_exists( $s['cb'] ) ) {
		return new WP_Error( 'gs_pn', 'Questa sezione non è disponibile in questa versione del plugin.' );
	}
	ob_start();
	try {
		call_user_func( $s['cb'] );
	} catch ( \Throwable $e ) {
		ob_end_clean();
		error_log( 'Gaming Sfogline — errore nella sezione "' . $id . '": ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ')' );
		return new WP_Error( 'gs_pn', 'Errore nel caricamento della sezione "' . $s['n'] . '". Controlla il log del server per i dettagli.' );
	}
	return ob_get_clean();
}

// -----------------------------------------------------------------------------
// AJAX — carica una sezione nell'area di lavoro
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_pn_carica_sezione', 'gs_ajax_pn_carica_sezione' );
function gs_ajax_pn_carica_sezione() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Permesso negato.' ) );
	}
	$id  = isset( $_POST['sezione'] ) ? sanitize_text_field( wp_unslash( $_POST['sezione'] ) ) : '';
	$out = gs_pn_esegui_sezione( $id );
	if ( is_wp_error( $out ) ) {
		wp_send_json_error( array( 'message' => $out->get_error_message() ) );
	}
	wp_send_json_success( array( 'html' => $out ) );
}

// -----------------------------------------------------------------------------
// Allarmi reali — remappa gs_riepilogo_dati() (già usato dal pannello del
// sito) sugli id delle sezioni di QUESTA pagina, invece di inventare un
// secondo conteggio parallelo.
// -----------------------------------------------------------------------------
function gs_pn_allarmi() {
	if ( ! function_exists( 'gs_riepilogo_dati' ) ) {
		return array();
	}
	// chiave di gs_riepilogo_dati() => id di sezione + descrizione breve.
	$mappa = array(
		'iscrizioni'          => array( 'id' => 'gs-zona-iscrizioni',        'd' => 'Verifica la quota, poi approva o rifiuta' ),
		'esperti'             => array( 'id' => 'gs-zona-esperto',           'd' => 'In attesa nei canali consulenza' ),
		'conversazioni'       => array( 'id' => 'gs-zona-conversazioni',     'd' => 'Richieste di conversazione privata' ),
		'ricettario'          => array( 'id' => 'gs-zona-ricettario',        'd' => 'Ricette di famiglia da approvare' ),
		'testimonianze'       => array( 'id' => 'gs-zona-testimonianze',     'd' => '"Dicono di Noi" da pubblicare' ),
		'biografie'           => array( 'id' => 'gs-zona-biografie',         'd' => 'Vanno approvate per comparire in Vetrina' ),
		'artigiani'           => array( 'id' => 'gs-zona-artigiani',         'd' => 'Vetrine modificate da approvare' ),
		'scuole'              => array( 'id' => 'gs-zona-scuole',            'd' => 'Vetrine modificate da approvare' ),
		'lezioni'             => array( 'id' => 'gs-zona-lezioni',           'd' => 'Risposte alle lezioni video di Rina' ),
		'tavolo'              => array( 'id' => 'gs-zona-tavolo',            'd' => 'Foto senza il commento di un maestro' ),
		'messaggi_attesa'     => array( 'id' => 'gs-zona-messaggi',          'd' => 'Messaggi privati senza risposta' ),
		'abbonamenti_scaduti' => array( 'id' => 'gs-zona-abbonamenti',       'd' => 'Le sezioni superiori si sono chiuse da sole' ),
	);
	$dati = gs_riepilogo_dati();
	$out  = array();
	foreach ( $mappa as $chiave => $info ) {
		if ( ! isset( $dati[ $chiave ] ) ) { continue; }
		list( $n, $label ) = $dati[ $chiave ];
		if ( $n < 1 ) { continue; }
		$sez = gs_pn_trova_sezione( $info['id'] );
		$out[] = array(
			'n'   => (int) $n,
			't'   => $label,
			'd'   => $info['d'],
			's'   => $info['id'],
			'ico' => $sez ? $sez['ico'] : '•',
		);
	}
	usort( $out, function ( $a, $b ) { return $b['n'] - $a['n']; } );
	return $out;
}

/** Voci "va tutto bene" (informative, senza sezione di destinazione). */
function gs_pn_allarmi_calmi() {
	if ( ! function_exists( 'gs_riepilogo_dati' ) ) { return array(); }
	$dati = gs_riepilogo_dati();
	$out  = array();
	foreach ( array( 'sfoglie_oggi', 'corsi_attivi' ) as $chiave ) {
		if ( isset( $dati[ $chiave ] ) ) {
			$out[] = array( 'n' => (int) $dati[ $chiave ][0], 't' => $dati[ $chiave ][1] );
		}
	}
	if ( function_exists( 'gs_art_elenco' ) ) {
		$attivi = 0;
		foreach ( gs_art_elenco() as $p ) { if ( function_exists( 'gs_art_attivo' ) && gs_art_attivo( $p->ID ) ) { $attivi++; } }
		$out[] = array( 'n' => $attivi, 't' => 'Vetrine Artigiani con abbonamento attivo' );
	}
	if ( function_exists( 'gs_scu_elenco' ) ) {
		$attivi = 0;
		foreach ( gs_scu_elenco() as $p ) { if ( function_exists( 'gs_scu_attivo' ) && gs_scu_attivo( $p->ID ) ) { $attivi++; } }
		$out[] = array( 'n' => $attivi, 't' => 'Vetrine Scuole di Cucina con abbonamento attivo' );
	}
	return $out;
}

/** Ultimi messaggi della Posta interna, per la Torre di controllo. */
function gs_pn_messaggi_recenti( $quanti = 8 ) {
	if ( ! function_exists( 'gs_inbox_messaggi' ) ) { return array(); }
	$out = array();
	$i   = 0;
	foreach ( gs_inbox_messaggi() as $m ) {
		if ( $i >= $quanti ) { break; }
		$out[] = array(
			'id'     => $m->ID,
			'da'     => (string) get_post_meta( $m->ID, 'gs_from', true ),
			'testo'  => wp_strip_all_tags( $m->post_title ),
			'quando' => human_time_diff( strtotime( $m->post_date ), current_time( 'timestamp' ) ) . ' fa',
			'letto'  => (bool) get_post_meta( $m->ID, 'gs_letto', true ),
		);
		$i++;
	}
	return $out;
}

// -----------------------------------------------------------------------------
// La pagina
// -----------------------------------------------------------------------------
function gs_pn_pagina() {
	if ( ! gs_can_manage() ) {
		echo '<div class="wrap"><p>Non hai il permesso per vedere questa pagina.</p></div>';
		return;
	}

	$gruppi   = gs_pn_gruppi();
	$sezioni  = gs_pn_sezioni();
	$messaggi = gs_pn_messaggi_recenti();
	$u        = wp_get_current_user();
	$iniziali = $u ? mb_strtoupper( mb_substr( $u->display_name, 0, 1 ) . ( strpos( $u->display_name, ' ' ) ? mb_substr( $u->display_name, strpos( $u->display_name, ' ' ) + 1, 1 ) : '' ) ) : '';

	// Solo le sezioni che questo utente può davvero aprire (collaboratori
	// limitati non vedono nella rotaia/ricerca quello che non possono aprire).
	$sezioni_visibili = array_values( array_filter( $sezioni, function ( $s ) {
		if ( ! empty( $s['solo_admin'] ) && ! current_user_can( 'manage_options' ) ) { return false; }
		if ( $s['sez'] && function_exists( 'gs_sez_zona_ok' ) && ! gs_sez_zona_ok( $s['sez'] ) ) { return false; }
		return true;
	} ) );
	$id_visibili = array_column( $sezioni_visibili, 'id' );

	// Gli allarmi si filtrano sulle stesse sezioni visibili: un collaboratore
	// limitato non deve mai vedere un numero rosso su qualcosa che poi,
	// cliccandolo, non può aprire.
	$allarmi = array_values( array_filter( gs_pn_allarmi(), function ( $a ) use ( $id_visibili ) {
		return in_array( $a['s'], $id_visibili, true );
	} ) );
	$calmi  = gs_pn_allarmi_calmi();
	$totale = array_sum( wp_list_pluck( $allarmi, 'n' ) );

	?>
	<div class="gs-pn-shell" id="gsPnShell">

		<header class="gs-pn-testata">
			<div class="gs-pn-marchio">
				<div class="gs-pn-sigillo">🍝</div>
				<div><b>Pannello Generale</b><span>Accademia della Sfoglia</span></div>
			</div>
			<div class="gs-pn-cerca" id="gsPnAprCerca">
				<span>🔍</span><span class="gs-pn-cerca-txt">Cerca una sezione…</span><kbd>⌘K</kbd>
			</div>
			<div class="gs-pn-testata-dx">
				<?php
				// Interruttore rapido del blackout, in prima vista nella
				// testata — richiesto da Ennio il 22/08/2026: prima era
				// raggiungibile solo dentro la sezione "Blackout" (gruppo
				// "impostazioni"), da cercare. Stesso endpoint del pulsante
				// già esistente in gs_pannello_blackout() (control-panel.php),
				// solo un accesso più rapido allo stesso interruttore.
				$gs_pn_bo_attivo = function_exists( 'gs_blackout_attivo' ) && gs_blackout_attivo();
				?>
				<button class="gs-toggle-blackout-rapido<?php echo $gs_pn_bo_attivo ? ' gs-pn-blackout-attivo' : ''; ?>" title="Oscura o riattiva subito tutto il Gaming per le sfogline">
					<?php echo $gs_pn_bo_attivo ? '🌙 OSCURATO — riattiva' : '🌙 Gaming attivo'; ?>
				</button>
				<button class="gs-pn-campana" id="gsPnVaiQuadro" title="Allarmi">🔔<span class="gs-pn-pallino"><?php echo (int) $totale; ?></span></button>
				<div class="gs-pn-chisono">
					<div class="gs-pn-faccia"><?php echo esc_html( $iniziali ); ?></div>
					<div><b><?php echo esc_html( $u ? $u->display_name : '' ); ?></b><span><?php echo current_user_can( 'manage_options' ) ? 'Titolare · vede tutto' : 'Collaboratore'; ?></span></div>
				</div>
				<?php if ( function_exists( 'gs_ajax_elenco_sfogline' ) ) : ?>
				<button class="gs-pn-classica gs-vedi-tutte-sfogline" title="Elenco di tutte le sfogline registrate">📋 Sfogline registrate</button>
				<?php endif; ?>
				<a class="gs-pn-classica" href="<?php echo esc_url( admin_url( 'admin.php?page=gs-generale' ) ); ?>" title="Vai alla Plancia classica (tutte le sezioni in una pagina)">Plancia classica</a>
			</div>
		</header>
		<div class="gs-pn-onda"></div>

		<div class="gs-pn-scheletro">
			<nav class="gs-pn-rotaia" id="gsPnRotaia"></nav>
			<main class="gs-pn-centro">
				<div class="gs-pn-nastro" id="gsPnNastro"></div>
				<div class="gs-pn-lavoro" id="gsPnLavoro"><div class="gs-pn-caricamento">Caricamento…</div></div>
			</main>
			<aside class="gs-pn-torre">
				<div class="gs-pn-torre-top"><span class="gs-pn-pulsa"></span><h3>Torre di controllo</h3></div>
				<div class="gs-pn-torre-corpo" id="gsPnTorre"></div>
			</aside>
		</div>

		<div class="gs-pn-velo" id="gsPnVelo">
			<div class="gs-pn-palette">
				<div class="gs-pn-pal-cerca">
					<span>🔍</span>
					<input id="gsPnPalInput" placeholder="Scrivi dove vuoi andare…" autocomplete="off">
					<span class="gs-pn-esc">esc</span>
				</div>
				<div class="gs-pn-pal-lista" id="gsPnPalLista"></div>
			</div>
		</div>
	</div>

	<script>
	var GS_PN = {
		gruppi: <?php echo wp_json_encode( $gruppi, JSON_UNESCAPED_UNICODE ); ?>,
		sezioni: <?php echo wp_json_encode( $sezioni_visibili, JSON_UNESCAPED_UNICODE ); ?>,
		allarmi: <?php echo wp_json_encode( $allarmi, JSON_UNESCAPED_UNICODE ); ?>,
		calmi: <?php echo wp_json_encode( $calmi, JSON_UNESCAPED_UNICODE ); ?>,
		messaggi: <?php echo wp_json_encode( $messaggi, JSON_UNESCAPED_UNICODE ); ?>,
		totale: <?php echo (int) $totale; ?>,
		ajaxUrl: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
		nonce: <?php echo wp_json_encode( wp_create_nonce( 'gs_ajax' ) ); ?>
	};
	</script>
	<?php
	gs_pn_stili();
	gs_pn_script();
}

/**
 * Stile del Pannello Generale nuovo. Iniettato inline SOLO da gs_pn_pagina(),
 * quindi non tocca nessun'altra pagina di wp-admin né il sito pubblico.
 * Icone e nomi dei gruppi nella rotaia più grandi (richiesta esplicita
 * 2026-08-08), rotaia allargata di conseguenza perché restino leggibili.
 */
function gs_pn_stili() {
	?>
	<style>
	/* Schermo davvero pieno: la colonna nera di WordPress a sinistra non deve
	   solo restare COPERTA (un position:fixed sopra di lei bastava a livello
	   visivo ma lei restava lì, alta quanto tutta la pagina, e a scorrimento
	   lungo o su schermi piccoli tornava a vedersi ai bordi) — va tolta
	   davvero dal flusso, e lo spazio che occupava restituito al pannello.
	   Resta solo la barra nera in alto (32px, 46px su mobile) con "Bacheca"
	   e il nome utente, per non perdere del tutto l'uscita da questa vista. */
	#adminmenumain, #adminmenuback, #wpfooter { display: none !important; }
	#wpcontent, #wpbody, #wpbody-content { margin-left: 0 !important; padding-left: 0 !important; }
	html.wp-toolbar { padding-top: 32px; }
	@media (max-width: 782px){ html.wp-toolbar { padding-top: 46px; } }
	#wpbody-content .notice-info, #wpbody-content .notice-success, #wpbody-content > div.updated { display: none !important; }
	/* .notice-error e .notice-warning restano visibili: sono gli avvisi
	   di sicurezza e di configurazione che non vanno persi. */
	:root{
		/* Palette "Atelier" (la stessa della Regia degli Iscritti ai Corsi,
		   estesa a tutta la cornice del Pannello Generale su richiesta di
		   Ennio il 18/08/2026 — solo intestazione/rotaia/sfondo comuni, non
		   il contenuto già rifinito di ogni singola sezione). */
		--pn-crema:#F7F3EA; --pn-crema-chiaro:#FBF8F1; --pn-crema-scuro:#EEE6D5; --pn-carta:#FFFDF9;
		--pn-oro-chiaro:#C9A24A; --pn-oro:#A8862E; --pn-oro-scuro:#8A6C22;
		--pn-bruno:#2C2418; --pn-bruno-2:#3D3323; --pn-bordeaux:#5C1F1D; --pn-verde:#1F6E37;
		--pn-testo:#2C2418; --pn-testo-chiaro:#7A6F5C; --pn-rosso:#B23223;
		--pn-bordo:rgba(168,134,46,.28);
		--pn-ombra:0 1px 2px rgba(42,31,20,.08), 0 18px 34px -22px rgba(42,31,20,.45);
		--pn-rail:96px; --pn-torre:300px; --pn-testata:54px;
	}
	.gs-pn-shell, .gs-pn-shell *{ box-sizing: border-box; }
	.gs-pn-shell{
		position: fixed; inset: 32px 0 0 0; z-index: 9000;
		font-family: 'Manrope', -apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif;
		background: var(--pn-crema); color: var(--pn-testo); font-size: 13px; line-height: 1.45;
		display: flex; flex-direction: column;
	}
	@media (max-width: 782px){ .gs-pn-shell{ inset: 46px 0 0 0; } }
	.gs-pn-shell h1,.gs-pn-shell h2,.gs-pn-shell h3,.gs-pn-shell h4{
		font-family: 'Cormorant Garamond', Georgia, serif; font-weight: 600; color:#1c1408; letter-spacing:.01em; margin:0;
	}
	.gs-pn-shell button{ font: inherit; color: inherit; background: none; border: 0; cursor: pointer; }
	.gs-pn-shell input, .gs-pn-shell textarea{ font: inherit; color: inherit; }

	.gs-pn-testata{ height: var(--pn-testata); flex: 0 0 auto; display:flex; align-items:center; gap:14px; padding:0 16px; background: var(--pn-bruno); color:#F0E6C8; }
	.gs-pn-marchio{ display:flex; align-items:center; gap:9px; flex:0 0 auto; }
	.gs-pn-sigillo{ width:30px;height:30px;border-radius:50%;background:linear-gradient(145deg,var(--pn-oro-chiaro),var(--pn-oro-scuro));display:flex;align-items:center;justify-content:center;font-size:15px; }
	.gs-pn-marchio b{ font-family:'Manrope',Arial,sans-serif;font-size:14px;letter-spacing:.06em;text-transform:uppercase;color:#FAF3E2;display:block; }
	.gs-pn-marchio span{ font-size:10.5px;color:#B9A87C;display:block;letter-spacing:.04em;margin-top:-2px; }
	.gs-pn-cerca{ flex:1;max-width:520px;margin:0 auto;display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.09);border:1px solid rgba(233,198,107,.32);border-radius:999px;padding:6px 14px;cursor:text; }
	.gs-pn-cerca:hover{ background:rgba(255,255,255,.15);border-color:var(--pn-oro); }
	.gs-pn-cerca-txt{ flex:1;color:#C8B896;font-size:12.5px; }
	.gs-pn-cerca kbd{ background:rgba(233,198,107,.18);border:1px solid rgba(233,198,107,.3);border-radius:5px;padding:1px 6px;font-size:10.5px;color:var(--pn-oro-chiaro); }
	.gs-pn-testata-dx{ margin-left:auto;display:flex;align-items:center;gap:12px;flex:0 0 auto; }
	.gs-pn-campana{ position:relative;font-size:17px;padding:4px; }
	.gs-pn-pallino{ position:absolute;top:0;right:0;min-width:16px;height:16px;border-radius:999px;background:var(--pn-rosso);color:#fff;font-size:9.5px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 4px;border:2px solid var(--pn-bruno); }
	.gs-pn-chisono{ display:flex;align-items:center;gap:8px;font-size:12px; }
	.gs-pn-faccia{ width:26px;height:26px;border-radius:50%;background:var(--pn-oro-scuro);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px; }
	.gs-pn-chisono span{ font-size:10px;color:#B9A87C;display:block;margin-top:-3px; }
	.gs-pn-classica{ font-size:11px;color:#C8B896!important;text-decoration:none;border:1px solid rgba(233,198,107,.35)!important;border-radius:999px;padding:5px 11px;white-space:normal; }
	.gs-pn-classica:hover{ color:#F0E6C8!important;border-color:var(--pn-oro)!important; }
	/* L'interruttore rapido del blackout (.gs-toggle-blackout-rapido) qui in
	   testata è stilizzato da gaming.css (selettore .gs-pn-testata-dx
	   .gs-toggle-blackout-rapido), condiviso con la Plancia classica. */

	.gs-pn-onda{ height:9px;flex:0 0 auto;background-color:var(--pn-oro);background-repeat:repeat-x;background-size:280px 100%;
		background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 340 100' preserveAspectRatio='none'><path d='M0,50 C60,90 110,10 170,50 C230,90 280,10 340,50 L340,100 L0,100 Z' fill='%23e9c66b'/></svg>"); }

	.gs-pn-scheletro{ flex:1;display:flex;min-height:0; }

	/* Rotaia — icone e nomi ingranditi (richiesta 2026-08-08) */
	.gs-pn-rotaia{ width:var(--pn-rail);flex:0 0 auto;background:var(--pn-bruno-2);display:flex;flex-direction:column;align-items:center;padding:10px 0 12px;gap:4px;overflow-y:auto; }
	.gs-pn-rgruppo{ width:84px;padding:10px 3px 8px;border-radius:13px;display:flex;flex-direction:column;align-items:center;gap:5px;color:#C8B896;position:relative;text-align:center; }
	.gs-pn-rgruppo:hover{ background:rgba(233,198,107,.13);color:#F0E6C8; }
	.gs-pn-rgruppo.on{ background:var(--pn-oro);color:#2A1F14; }
	/* Pallino col colore della categoria (registro unico gs_categorie(),
	   richiesto da Ennio il 18/08/2026) — stesso linguaggio di colore della
	   Plancia classica e del menu verde, senza cambiare l'oro dello stato
	   "attivo" già in uso qui. */
	.gs-pn-r-dot{ position:absolute; top:7px; left:9px; width:7px; height:7px; border-radius:50%; }
	.gs-pn-r-ico{ font-size:28px;line-height:1; }
	.gs-pn-r-lbl{ font-size:12px;font-weight:700;letter-spacing:.01em;line-height:1.2; }
	.gs-pn-r-all{ position:absolute;top:4px;right:8px;min-width:18px;height:18px;border-radius:999px;background:var(--pn-rosso);color:#fff;font-size:10.5px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 4px; }
	.gs-pn-rotaia hr{ width:44px;border:0;border-top:1px solid rgba(233,198,107,.2);margin:6px 0; }

	.gs-pn-centro{ flex:1;min-width:0;display:flex;flex-direction:column;background:var(--pn-crema); }
	.gs-pn-nastro{ flex:0 0 auto;background:var(--pn-crema-chiaro);border-bottom:1px solid var(--pn-bordo);padding:7px 14px;display:flex;align-items:center;gap:7px;overflow-x:auto; }
	.gs-pn-nastro-tit{ font-family:'Manrope',Arial,sans-serif;font-size:10.5px;letter-spacing:.13em;text-transform:uppercase;color:var(--pn-oro-scuro);font-weight:700;flex:0 0 auto;padding-right:6px;border-right:1px solid var(--pn-bordo);margin-right:3px; }
	.gs-pn-chip{ flex:0 0 auto;display:flex;align-items:center;gap:5px;background:var(--pn-carta);border:1px solid var(--pn-bordo);border-radius:999px;padding:5px 12px;font-size:12px;white-space:nowrap;position:relative; }
	.gs-pn-chip:hover{ background:var(--pn-oro-chiaro);border-color:var(--pn-oro); }
	.gs-pn-chip.on{ background:linear-gradient(var(--pn-oro-chiaro),var(--pn-oro-scuro));border-color:var(--pn-oro-scuro);color:#2A1F14;font-weight:700; }
	.gs-pn-chip .gs-pn-cnum{ background:var(--pn-rosso);color:#fff;border-radius:999px;font-size:9.5px;font-weight:700;padding:0 5px;min-width:15px;height:15px;display:inline-flex;align-items:center;justify-content:center; }

	.gs-pn-lavoro{ flex:1;min-height:0;overflow-y:auto;padding:16px 18px 20px; }
	.gs-pn-caricamento{ color:var(--pn-testo-chiaro);font-size:13px;padding:20px; }
	.gs-pn-titolo-vista{ display:flex;align-items:baseline;gap:11px;margin-bottom:13px;flex-wrap:wrap; }
	.gs-pn-titolo-vista h2{ font-size:20px; }
	.gs-pn-titolo-vista .gs-pn-sott{ font-size:12px;color:var(--pn-testo-chiaro); }

	.gs-pn-griglia-allarmi{ display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:9px;margin-bottom:15px; }
	.gs-pn-card-num{ background:var(--pn-carta);border:1px solid var(--pn-bordo);border-radius:13px;padding:11px 10px;box-shadow:var(--pn-ombra);text-align:center;position:relative;overflow:hidden;width:100%; }
	.gs-pn-card-num.rosso{ border-color:var(--pn-rosso);background:#fff; }
	.gs-pn-card-num.rosso::before{ content:"";position:absolute;inset:0 auto 0 0;width:4px;background:var(--pn-rosso); }
	.gs-pn-card-num .cn{ font-family:'Manrope',Arial,sans-serif;font-size:27px;font-weight:700;line-height:1;color:var(--pn-bordeaux); }
	.gs-pn-card-num.rosso .cn{ color:var(--pn-rosso); }
	.gs-pn-card-num .cl{ font-size:10.5px;color:var(--pn-testo-chiaro);margin-top:3px;line-height:1.25; }

	.gs-pn-griglia-sez{ display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:8px; }
	.gs-pn-tessera{ display:flex;align-items:center;gap:9px;background:var(--pn-carta);border:1px solid var(--pn-bordo);border-radius:11px;padding:10px 12px;text-align:left;position:relative;width:100%; }
	.gs-pn-tessera:hover{ background:var(--pn-oro-chiaro);border-color:var(--pn-oro-scuro); }
	.gs-pn-tessera .t-ico{ font-size:18px;flex:0 0 auto; }
	.gs-pn-tessera .t-nome{ font-size:12px;line-height:1.25;flex:1; }
	.gs-pn-tessera .t-all{ position:absolute;top:-5px;right:-5px;min-width:17px;height:17px;border-radius:999px;background:var(--pn-rosso);color:#fff;font-size:9.5px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 4px;border:2px solid var(--pn-crema); }

	.gs-pn-gruppo-blocco{ margin-bottom:16px; }
	.gs-pn-gruppo-tit{ display:flex;align-items:center;gap:8px;margin-bottom:8px;padding-bottom:5px;border-bottom:2px solid var(--pn-oro); }
	.gs-pn-gruppo-tit h3{ font-size:14px; }
	.gs-pn-gruppo-tit .conta{ font-size:10px;background:var(--pn-crema-scuro);border-radius:999px;padding:1px 9px;color:var(--pn-oro-scuro);font-weight:700; }

	.gs-pn-torre{ width:var(--pn-torre);flex:0 0 auto;background:var(--pn-carta);border-left:1px solid var(--pn-bordo);display:flex;flex-direction:column;min-height:0; }
	.gs-pn-torre-top{ padding:9px 13px;border-bottom:1px solid var(--pn-bordo);display:flex;align-items:center;gap:7px;background:var(--pn-crema-scuro); }
	.gs-pn-torre-top h3{ font-size:13px;flex:1; }
	.gs-pn-pulsa{ width:8px;height:8px;border-radius:50%;background:var(--pn-rosso);animation:gsPnPulsa 1.7s infinite; }
	@keyframes gsPnPulsa{ 0%,100%{ opacity:1;transform:scale(1);} 50%{ opacity:.35;transform:scale(.8);} }
	.gs-pn-torre-corpo{ flex:1;min-height:0;overflow-y:auto;padding:10px 11px 14px; }
	.gs-pn-t-sez{ font-family:'Manrope',Arial,sans-serif;font-size:10px;letter-spacing:.13em;text-transform:uppercase;color:var(--pn-oro-scuro);font-weight:700;margin:11px 0 6px; }
	.gs-pn-t-sez:first-child{ margin-top:0; }
	.gs-pn-allarme{ display:flex;align-items:center;gap:9px;background:#fff;border:1px solid var(--pn-bordo);border-left:4px solid var(--pn-rosso);border-radius:9px;padding:7px 10px;margin-bottom:6px;width:100%;text-align:left; }
	.gs-pn-allarme:hover{ background:var(--pn-oro-chiaro); }
	.gs-pn-allarme.calmo{ border-left-color:var(--pn-oro); }
	.gs-pn-allarme .an{ font-family:'Manrope',Arial,sans-serif;font-size:19px;font-weight:700;color:var(--pn-rosso);min-width:26px;text-align:center;line-height:1; }
	.gs-pn-allarme.calmo .an{ color:var(--pn-oro-scuro); }
	.gs-pn-allarme .al{ flex:1;font-size:11.5px;line-height:1.25; }
	.gs-pn-allarme .frec{ color:var(--pn-oro-scuro);font-size:13px; }

	.gs-pn-msg-riga{ display:flex;gap:8px;background:#fff;border:1px solid var(--pn-bordo);border-radius:9px;padding:8px 10px;margin-bottom:6px; }
	.gs-pn-msg-riga.nuovo{ border-left:4px solid var(--pn-bordeaux); }
	.gs-pn-msg-riga.gs-appena-letto{ border-left:4px solid var(--pn-verde); }
	.gs-pn-msg-testo{ flex:1;min-width:0;cursor:pointer; }
	.gs-pn-msg-testo b{ font-size:11.5px;display:block; }
	.gs-pn-msg-testo p{ font-size:11px;color:var(--pn-testo-chiaro);margin:0;
		display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3;overflow:hidden; }
	.gs-pn-msg-quando{ font-size:9.5px;color:var(--pn-oro-scuro);flex:0 0 auto; }
	.gs-pn-msg-azioni{ display:flex;gap:4px;flex:0 0 auto; }
	.gs-pn-msg-azioni button{ font-size:12px;padding:2px 4px;border-radius:5px; }
	.gs-pn-msg-azioni button:hover{ background:var(--pn-crema-scuro); }
	.gs-pn-risposta{ background:var(--pn-crema-chiaro);border:1px dashed var(--pn-oro);border-radius:9px;padding:8px;margin:-2px 0 8px; }
	.gs-pn-risposta textarea{ width:100%;border:1px solid var(--pn-bordo);border-radius:7px;padding:5px 7px;font-size:11.5px;background:#fff;resize:none;height:44px;margin-bottom:6px; }
	.gs-pn-risposta button{ background:linear-gradient(var(--pn-oro-chiaro),var(--pn-oro-scuro));color:#2a1f14;font-weight:700;font-size:11px;padding:5px 13px;border-radius:999px; }
	.gs-pn-risposta .esito{ font-size:11px;margin-left:6px; }

	.gs-pn-velo{ position:fixed;inset:0;background:rgba(36,26,16,.55);backdrop-filter:blur(3px);z-index:99999;display:none;align-items:flex-start;justify-content:center;padding-top:11vh; }
	.gs-pn-velo.aperto{ display:flex; }
	.gs-pn-palette{ width:min(620px,90vw);background:var(--pn-carta);border-radius:16px;overflow:hidden;box-shadow:0 30px 70px -20px rgba(0,0,0,.6);border:1px solid var(--pn-oro);display:flex;flex-direction:column;max-height:70vh; }
	.gs-pn-pal-cerca{ display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--pn-bordo); }
	.gs-pn-pal-cerca input{ flex:1;border:0;background:none;font-size:16px;outline:none; }
	.gs-pn-esc{ font-size:10.5px;color:var(--pn-testo-chiaro);border:1px solid var(--pn-bordo);border-radius:5px;padding:2px 7px; }
	.gs-pn-pal-lista{ flex:1;overflow-y:auto;padding:7px; }
	.gs-pn-pal-voce{ display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:9px;width:100%;text-align:left; }
	.gs-pn-pal-voce.sel{ background:linear-gradient(var(--pn-oro-chiaro),var(--pn-oro-scuro));color:#2A1F14; }
	.gs-pn-pal-voce .pv-ico{ font-size:15px; }
	.gs-pn-pal-voce .pv-nome{ flex:1;font-size:12.5px; }
	.gs-pn-pal-voce .pv-gr{ font-size:10px;color:var(--pn-oro-scuro);background:var(--pn-crema-scuro);border-radius:999px;padding:1px 8px; }
	.gs-pn-pal-voce.sel .pv-gr{ background:rgba(255,255,255,.5);color:#5C1F1D; }
	.gs-pn-pal-vuoto{ padding:22px;text-align:center;color:var(--pn-testo-chiaro);font-size:12.5px; }

	@media (max-width:1400px){ :root{ --pn-torre:262px; } }
	</style>
	<?php
}

/**
 * JavaScript del Pannello Generale nuovo — vanilla JS (nessuna dipendenza da
 * jQuery/gaming.js, per non dover badare all'ordine di caricamento): rotaia,
 * nastro, ricerca ⌘K e Torre di controllo con dati reali (GS_PN, stampato
 * sopra da gs_pn_pagina()). Ogni sezione, una volta aperta, carica via AJAX
 * l'HTML che la Plancia classica disegna già — quell'HTML porta le sue
 * classi normali (gs-btn, gs-form, …) e continua a funzionare da sola perché
 * gaming.js resta comunque caricato su questa pagina (stessa condizione di
 * enqueue della Plancia classica, riconosce "gs-generale" anche nell'hook di
 * questa sottopagina).
 */
function gs_pn_script() {
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		var GRUPPI  = GS_PN.gruppi;
		var SEZIONI = GS_PN.sezioni;
		var ALLARMI = GS_PN.allarmi;
		var CALMI   = GS_PN.calmi;
		var MESSAGGI = GS_PN.messaggi.slice();

		var elRotaia = document.getElementById('gsPnRotaia');
		var elNastro = document.getElementById('gsPnNastro');
		var elLavoro = document.getElementById('gsPnLavoro');
		var elTorre  = document.getElementById('gsPnTorre');
		var elVelo   = document.getElementById('gsPnVelo');
		var elPalIn  = document.getElementById('gsPnPalInput');
		var elPalLis = document.getElementById('gsPnPalLista');

		var vistaAttiva = 'quadro';
		var sezioneAperta = null;

		function idGruppi() {
			var out = {};
			for (var k in GRUPPI) { if (GRUPPI.hasOwnProperty(k)) { out[k] = true; } }
			return out;
		}
		var GID = idGruppi();

		function sezioniDiGruppo(g) {
			return SEZIONI.filter(function (s) { return s.g === g; });
		}
		function trovaSezione(nome) {
			for (var i = 0; i < SEZIONI.length; i++) { if (SEZIONI[i].n === nome) { return SEZIONI[i]; } }
			return null;
		}
		function allarmiDiGruppo(g) {
			var tot = 0;
			ALLARMI.forEach(function (a) {
				var s = trovaSezione(zonaANome(a.s));
				if (s && s.g === g) { tot += a.n; }
			});
			return tot;
		}
		// Gli allarmi (da PHP) portano l'id della sezione (a.s); qui serve
		// spesso il nome per riusare apriSezione(nome) — piccola mappa id->nome.
		var ID_TO_NOME = {};
		SEZIONI.forEach(function (s) { ID_TO_NOME[s.id] = s.n; });
		function zonaANome(id) { return ID_TO_NOME[id] || ''; }
		function allarmoDellaSezione(id) {
			var tot = 0;
			ALLARMI.forEach(function (a) { if (a.s === id) { tot += a.n; } });
			return tot;
		}

		function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

		/* ── ROTAIA ── */
		function disegnaRotaia() {
			var html = '';
			var chiavi = Object.keys(GRUPPI);
			chiavi.forEach(function (gid, idx) {
				var g = GRUPPI[gid];
				var a = allarmiDiGruppo(gid);
				html += (idx === 2 ? '<hr>' : '') +
					'<button class="gs-pn-rgruppo' + (vistaAttiva === gid ? ' on' : '') + '" data-g="' + gid + '" title="' + esc(g.titolo) + '">' +
					(g.colore ? '<span class="gs-pn-r-dot" style="background:' + g.colore + '"></span>' : '') +
					'<span class="gs-pn-r-ico">' + g.ico + '</span><span class="gs-pn-r-lbl">' + esc(g.nome) + '</span>' +
					(a ? '<span class="gs-pn-r-all">' + a + '</span>' : '') + '</button>';
			});
			elRotaia.innerHTML =
				'<button class="gs-pn-rgruppo' + (vistaAttiva === 'quadro' ? ' on' : '') + '" data-g="quadro" title="Quadro generale"><span class="gs-pn-r-ico">🏠</span><span class="gs-pn-r-lbl">Quadro</span></button>' +
				'<button class="gs-pn-rgruppo' + (vistaAttiva === 'tutte' ? ' on' : '') + '" data-g="tutte" title="Tutte le sezioni"><span class="gs-pn-r-ico">🗂️</span><span class="gs-pn-r-lbl">Tutte</span></button>' +
				html;
			elRotaia.querySelectorAll('[data-g]').forEach(function (b) {
				b.addEventListener('click', function () { vaiA(b.getAttribute('data-g')); });
			});
		}

		/* ── NASTRO ── */
		function disegnaNastro() {
			var html;
			if (vistaAttiva === 'quadro' || vistaAttiva === 'tutte') {
				var scorciatoie = ['Posta interna', 'Calendario Corsi', 'Iscrizioni delle sfogline', 'Artigiani della Pasta', 'Ricettario delle Famiglie', 'Collaboratori del pannello'];
				html = '<span class="gs-pn-nastro-tit">Scorciatoie</span>' + scorciatoie.map(function (nome) {
					var s = trovaSezione(nome);
					if (!s) { return ''; }
					var a = allarmoDellaSezione(s.id);
					return '<button class="gs-pn-chip" data-apri="' + esc(nome) + '">' + s.ico + ' ' + esc(nome) + (a ? '<span class="gs-pn-cnum">' + a + '</span>' : '') + '</button>';
				}).join('');
			} else {
				var g = GRUPPI[vistaAttiva];
				html = '<span class="gs-pn-nastro-tit">' + esc(g.nome) + '</span>' + sezioniDiGruppo(vistaAttiva).map(function (s) {
					var a = allarmoDellaSezione(s.id);
					return '<button class="gs-pn-chip' + (sezioneAperta === s.n ? ' on' : '') + '" data-apri="' + esc(s.n) + '">' + s.ico + ' ' + esc(s.n) + (a ? '<span class="gs-pn-cnum">' + a + '</span>' : '') + '</button>';
				}).join('');
			}
			elNastro.innerHTML = html;
			elNastro.querySelectorAll('[data-apri]').forEach(function (b) {
				b.addEventListener('click', function () { apriSezione(b.getAttribute('data-apri')); });
			});
		}

		/* ── TORRE ── */
		function disegnaTorre() {
			var html = '<div class="gs-pn-t-sez">Da sbrigare — ' + GS_PN.totale + '</div>';
			if (!ALLARMI.length) {
				html += '<p style="font-size:11.5px;color:var(--pn-testo-chiaro)">Niente in sospeso: sei in pari con tutto.</p>';
			}
			ALLARMI.forEach(function (a) {
				html += '<button class="gs-pn-allarme" data-apri="' + esc(zonaANome(a.s)) + '"><span class="an">' + a.n + '</span><span class="al"><b>' + esc(a.t) + '</b><br><span style="color:var(--pn-testo-chiaro);font-size:10.5px">' + esc(a.d) + '</span></span><span class="frec">›</span></button>';
			});
			if (CALMI.length) {
				html += '<div class="gs-pn-t-sez">Va tutto bene</div>';
				CALMI.forEach(function (c) {
					html += '<div class="gs-pn-allarme calmo"><span class="an">' + c.n + '</span><span class="al">' + esc(c.t) + '</span></div>';
				});
			}
			html += '<div class="gs-pn-t-sez">Posta interna — ultimi arrivi</div>';
			if (!MESSAGGI.length) {
				html += '<p style="font-size:11.5px;color:var(--pn-testo-chiaro)">Nessun messaggio recente.</p>';
			}
			MESSAGGI.forEach(function (m) {
				// Non letto: lampeggia in rosso finché non si preme "LETTO" —
				// aprire/leggere il messaggio da solo non lo segna più come letto
				// (Ennio, 13/08/2026).
				html += '<div class="gs-pn-msg-riga' + (m.letto ? '' : ' nuovo gs-lampeggia-rosso') + '" data-msg="' + m.id + '">' +
					'<span class="gs-pn-msg-testo" data-apri-msg="' + m.id + '"><b>' + esc(m.da) + '</b><p>' + esc(m.testo) + '</p></span>' +
					'<span class="gs-pn-msg-quando">' + esc(m.quando) + '</span>' +
					'<span class="gs-pn-msg-azioni">' +
					(m.letto ? '' : '<button title="Segna come letto e ferma il lampeggio" data-letto="' + m.id + '">✓ Letto</button>') +
					'<button title="Rispondi al volo" data-rispondi="' + m.id + '">💬</button>' +
					'</span></div>' +
					'<div class="gs-pn-risposta" id="gsPnRisp' + m.id + '" style="display:none">' +
					'<textarea placeholder="Scrivi una nota interna…" id="gsPnRispTesto' + m.id + '"></textarea>' +
					'<button data-invia-risposta="' + m.id + '">Aggiungi nota</button><span class="esito" id="gsPnRispEsito' + m.id + '"></span></div>';
			});
			elTorre.innerHTML = html;

			elTorre.querySelectorAll('[data-apri]').forEach(function (b) { b.addEventListener('click', function () { apriSezione(b.getAttribute('data-apri')); }); });
			elTorre.querySelectorAll('[data-apri-msg]').forEach(function (b) { b.addEventListener('click', function () { apriSezione('Posta interna'); }); });
			elTorre.querySelectorAll('[data-letto]').forEach(function (b) {
				b.addEventListener('click', function (e) {
					e.stopPropagation();
					var id = b.getAttribute('data-letto');
					var riga = b.closest('.gs-pn-msg-riga');
					postAjax('gs_inbox_letto', { id: id }).then(function () {
						MESSAGGI = MESSAGGI.map(function (m) { if (String(m.id) === String(id)) { m.letto = true; } return m; });
						// Lampeggio verde di conferma per un attimo prima di ridisegnare.
						if (riga) {
							riga.classList.remove('nuovo', 'gs-lampeggia-rosso');
							riga.classList.add('gs-appena-letto');
							var btn = riga.querySelector('[data-letto]'); if (btn) { btn.remove(); }
						}
						setTimeout(disegnaTorre, 900);
					});
				});
			});
			elTorre.querySelectorAll('[data-rispondi]').forEach(function (b) {
				b.addEventListener('click', function (e) {
					e.stopPropagation();
					var box = document.getElementById('gsPnRisp' + b.getAttribute('data-rispondi'));
					if (box) { box.style.display = box.style.display === 'none' ? 'block' : 'none'; }
				});
			});
			elTorre.querySelectorAll('[data-invia-risposta]').forEach(function (b) {
				b.addEventListener('click', function (e) {
					e.stopPropagation();
					var id = b.getAttribute('data-invia-risposta');
					var ta = document.getElementById('gsPnRispTesto' + id);
					var esito = document.getElementById('gsPnRispEsito' + id);
					var testo = ta ? ta.value.trim() : '';
					if (!testo) { esito.textContent = 'Scrivi qualcosa prima.'; return; }
					esito.textContent = 'Invio…';
					postAjax('gs_inbox_rispondi', { id: id, testo: testo }).then(function (res) {
						esito.textContent = res && res.success ? '✅ Aggiunta' : 'Errore.';
						if (res && res.success) { ta.value = ''; }
					});
				});
			});
		}

		/* ── AJAX ── */
		function postAjax(action, dati) {
			var fd = new FormData();
			fd.append('action', action);
			fd.append('nonce', GS_PN.nonce);
			for (var k in dati) { if (dati.hasOwnProperty(k)) { fd.append(k, dati[k]); } }
			return fetch(GS_PN.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
				.then(function (r) { return r.json(); })
				.catch(function () { return { success: false }; });
		}

		/* ── AREA DI LAVORO ── */
		function disegnaLavoro() {
			if (sezioneAperta) { caricaSezione(sezioneAperta); return; }
			if (vistaAttiva === 'quadro') { elLavoro.innerHTML = vistaQuadro(); legaAzioniLocali(); return; }
			if (vistaAttiva === 'tutte') { elLavoro.innerHTML = vistaTutte(); legaAzioniLocali(); return; }
			elLavoro.innerHTML = vistaGruppo(vistaAttiva);
			legaAzioniLocali();
		}
		function legaAzioniLocali() {
			elLavoro.querySelectorAll('[data-apri]').forEach(function (b) {
				b.addEventListener('click', function () { apriSezione(b.getAttribute('data-apri')); });
			});
			elLavoro.querySelectorAll('[data-g]').forEach(function (b) {
				b.addEventListener('click', function () { vaiA(b.getAttribute('data-g')); });
			});
		}

		function vistaQuadro() {
			var h = '<div class="gs-pn-titolo-vista"><h2>Quadro generale</h2><span class="gs-pn-sott">' + GS_PN.totale + ' cose da sbrigare, tutte in una schermata sola</span></div>';
			h += '<div class="gs-pn-griglia-allarmi">';
			ALLARMI.slice(0, 6).forEach(function (a) {
				h += '<button class="gs-pn-card-num rosso" data-apri="' + esc(zonaANome(a.s)) + '"><div class="cn">' + a.n + '</div><div class="cl">' + esc(a.t) + '</div></button>';
			});
			CALMI.slice(0, 2).forEach(function (c) {
				h += '<div class="gs-pn-card-num"><div class="cn">' + c.n + '</div><div class="cl">' + esc(c.t) + '</div></div>';
			});
			h += '</div>';
			if (!ALLARMI.length) {
				h += '<p style="color:var(--pn-testo-chiaro)">Non c\'è nulla in sospeso in questo momento: tutto è già stato approvato o risposto.</p>';
			} else {
				h += '<div class="gs-pn-gruppo-blocco"><div class="gs-pn-gruppo-tit"><h3>⚡ Cosa faccio adesso</h3></div><div class="gs-pn-griglia-sez">';
				ALLARMI.forEach(function (a) {
					var s = trovaSezione(zonaANome(a.s));
					h += '<button class="gs-pn-tessera" data-apri="' + esc(zonaANome(a.s)) + '"><span class="t-ico">' + (s ? s.ico : '•') + '</span><span class="t-nome"><b>' + a.n + '</b> — ' + esc(a.t.toLowerCase()) + '</span><span class="t-all">' + a.n + '</span></button>';
				});
				h += '</div></div>';
			}
			return h;
		}

		function vistaTutte() {
			var h = '<div class="gs-pn-titolo-vista"><h2>Tutte le sezioni</h2><span class="gs-pn-sott">' + SEZIONI.length + ' sezioni, raggruppate come nella rotaia</span></div>';
			Object.keys(GRUPPI).forEach(function (gid) {
				var g = GRUPPI[gid];
				var ss = sezioniDiGruppo(gid);
				if (!ss.length) { return; }
				h += '<div class="gs-pn-gruppo-blocco"><div class="gs-pn-gruppo-tit"><span style="font-size:16px">' + g.ico + '</span><h3>' + esc(g.titolo) + '</h3><span class="conta">' + ss.length + '</span></div><div class="gs-pn-griglia-sez">';
				ss.forEach(function (s) {
					var a = allarmoDellaSezione(s.id);
					h += '<button class="gs-pn-tessera" data-apri="' + esc(s.n) + '"><span class="t-ico">' + s.ico + '</span><span class="t-nome">' + esc(s.n) + '</span>' + (a ? '<span class="t-all">' + a + '</span>' : '') + '</button>';
				});
				h += '</div></div>';
			});
			return h;
		}

		function vistaGruppo(gid) {
			var g = GRUPPI[gid];
			var ss = sezioniDiGruppo(gid);
			var a = allarmiDiGruppo(gid);
			var h = '<div class="gs-pn-titolo-vista"><h2>' + g.ico + ' ' + esc(g.titolo) + '</h2><span class="gs-pn-sott">' + ss.length + ' sezioni' + (a ? ' — ' + a + ' cose da sbrigare' : ' — niente in sospeso') + '</span></div><div class="gs-pn-griglia-sez">';
			ss.forEach(function (s) {
				var al = allarmoDellaSezione(s.id);
				h += '<button class="gs-pn-tessera" data-apri="' + esc(s.n) + '"><span class="t-ico">' + s.ico + '</span><span class="t-nome">' + esc(s.n) + '</span>' + (al ? '<span class="t-all">' + al + '</span>' : '') + '</button>';
			});
			h += '</div>';
			return h;
		}

		/* Carica davvero il contenuto della sezione, via AJAX, dalla stessa
		   funzione PHP che disegna già questa zona nella Plancia classica. */
		function caricaSezione(nome) {
			var s = trovaSezione(nome);
			if (!s) { elLavoro.innerHTML = '<p>Sezione non trovata.</p>'; return; }
			var g = GRUPPI[s.g];
			elLavoro.innerHTML = '<div class="gs-pn-titolo-vista"><h2>' + s.ico + ' ' + esc(s.n) + '</h2><span class="gs-pn-sott">' + esc(g.titolo) + '</span></div><div class="gs-pn-caricamento">Caricamento…</div>';
			postAjax('gs_pn_carica_sezione', { sezione: s.id }).then(function (res) {
				if (res && res.success) {
					elLavoro.querySelector('.gs-pn-caricamento').outerHTML = '<div class="gs-pn-sezione-corpo">' + res.data.html + '</div>';
					// Alcuni pezzi della Plancia si inizializzano normalmente una sola
					// volta, al primo caricamento della pagina intera (le tabelle
					// paginate con classe "gs-paginate", e la linea del tempo di
					// "Pianificazione dell'Anno"): senza rilanciarli qui restano
					// vuoti o fermi su "Caricamento…" per sempre — segnalato
					// 2026-08-08 proprio su Pianificazione dell'Anno.
					if (window.gsInitPagination) { window.gsInitPagination(elLavoro); }
					if (window.gsInitPianoTimeline) { window.gsInitPianoTimeline(elLavoro); }
				} else {
					elLavoro.querySelector('.gs-pn-caricamento').outerHTML = '<p style="color:#8A1F1F">' + esc(res && res.data ? res.data.message : 'Errore nel caricamento.') + '</p>';
				}
			});
		}

		/* ── NAVIGAZIONE ── */
		function vaiA(gid) {
			vistaAttiva = gid; sezioneAperta = null;
			disegnaRotaia(); disegnaNastro(); disegnaLavoro();
			elLavoro.scrollTop = 0;
		}
		function apriSezione(nome) {
			var s = trovaSezione(nome);
			if (!s) { return; }
			vistaAttiva = s.g; sezioneAperta = nome;
			disegnaRotaia(); disegnaNastro(); disegnaLavoro();
			elLavoro.scrollTop = 0;
			chiudiPalette();
		}
		document.getElementById('gsPnVaiQuadro').addEventListener('click', function () { vaiA('quadro'); });

		// Delega su elLavoro (invece di un binding per-elemento): copre anche
		// i pulsanti "vai a" scritti a mano dentro il PHP di una sezione (es.
		// il collegamento a "Visibilità sezioni e permessi" messo dentro
		// "Iscrizioni delle sfogline", 20/08/2026), che altrimenti non
		// avrebbero mai il click agganciato — quei bottoni non passano dal
		// disegnaNastro()/disegnaLavoro() che fa il binding per gli altri.
		elLavoro.addEventListener('click', function ( e ) {
			var b = e.target.closest( '[data-apri]' );
			if ( b ) { apriSezione( b.getAttribute( 'data-apri' ) ); }
		} );

		/* ── PALETTE ⌘K ── */
		var palSel = 0, palRis = [];
		function apriPalette() {
			elVelo.classList.add('aperto');
			elPalIn.value = '';
			elPalIn.focus();
			filtraPalette('');
		}
		function chiudiPalette() { elVelo.classList.remove('aperto'); }
		function filtraPalette(q) {
			q = q.toLowerCase().trim();
			palRis = SEZIONI.filter(function (s) { return s.n.toLowerCase().indexOf(q) > -1; }).slice(0, 40);
			palSel = 0;
			disegnaPalette();
		}
		function disegnaPalette() {
			if (!palRis.length) { elPalLis.innerHTML = '<div class="gs-pn-pal-vuoto">Nessuna sezione con questo nome.</div>'; return; }
			elPalLis.innerHTML = palRis.map(function (s, i) {
				var g = GRUPPI[s.g];
				return '<button class="gs-pn-pal-voce' + (i === palSel ? ' sel' : '') + '" data-apri="' + esc(s.n) + '"><span class="pv-ico">' + s.ico + '</span><span class="pv-nome">' + esc(s.n) + '</span><span class="pv-gr">' + esc(g.nome) + '</span></button>';
			}).join('');
			elPalLis.querySelectorAll('[data-apri]').forEach(function (b) {
				b.addEventListener('click', function () { apriSezione(b.getAttribute('data-apri')); });
			});
		}
		document.getElementById('gsPnAprCerca').addEventListener('click', apriPalette);
		elVelo.addEventListener('click', function (e) { if (e.target === elVelo) { chiudiPalette(); } });
		elPalIn.addEventListener('input', function (e) { filtraPalette(e.target.value); });
		document.addEventListener('keydown', function (e) {
			var aperta = elVelo.classList.contains('aperto');
			if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); aperta ? chiudiPalette() : apriPalette(); return; }
			if (!aperta) { return; }
			if (e.key === 'Escape') { chiudiPalette(); }
			if (e.key === 'ArrowDown') { e.preventDefault(); palSel = Math.min(palSel + 1, palRis.length - 1); disegnaPalette(); }
			if (e.key === 'ArrowUp') { e.preventDefault(); palSel = Math.max(palSel - 1, 0); disegnaPalette(); }
			if (e.key === 'Enter' && palRis[palSel]) { apriSezione(palRis[palSel].n); }
		});

		/* ── AVVIO ── */
		disegnaRotaia(); disegnaNastro(); disegnaTorre(); disegnaLavoro();
	});
	</script>
	<?php
}
