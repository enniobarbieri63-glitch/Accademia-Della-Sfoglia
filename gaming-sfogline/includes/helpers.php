<?php
/**
 * helpers.php — Impostazioni di default e funzioni di utilità condivise.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Polyfill mbstring: su alcuni server l'estensione mbstring può mancare.
 * Definiamo le funzioni usate dal plugin come fallback (byte-based) solo se
 * assenti, così il plugin non va mai in errore fatale.
 */
if ( ! function_exists( 'mb_strlen' ) ) {
	function mb_strlen( $string, $encoding = null ) {
		return strlen( (string) $string );
	}
}
if ( ! function_exists( 'mb_substr' ) ) {
	function mb_substr( $string, $start, $length = null, $encoding = null ) {
		return ( null === $length ) ? substr( (string) $string, $start ) : substr( (string) $string, $start, $length );
	}
}

/**
 * Dalla versione 5.6, WordPress ripristina di default ogni elemento tolto
 * dal cestino con lo stato "bozza" invece che con lo stato che aveva prima
 * — scoperto il 14/08/2026 testando "Ripristina" nella Posta interna: il
 * messaggio tornava nel database ma restava invisibile ovunque (le liste
 * filtrano per stato "pubblicato"), come se il ripristino non avesse fatto
 * nulla. Tocca ALLO STESSO MODO ogni "Ripristina" del plugin — sono più di
 * venti punti diversi che usano wp_untrash_post() — quindi si corregge in
 * un solo posto invece che in ognuno: si richiede esplicitamente il
 * comportamento di prima del 5.6 (torna esattamente com'era), usando la
 * funzione che WordPress stesso fornisce già pronta per questo.
 */
add_filter( 'wp_untrash_post_status', 'wp_untrash_post_set_previous_status', 10, 3 );

/**
 * Registro unico delle categorie — richiesto da Ennio il 18/08/2026 per dare
 * un colore e un nome coerenti a ogni sezione, letti in un solo posto da
 * tutte e quattro le superfici (Plancia Generale, Pannello Generale, menu
 * verde "Sezioni", menu marrone di navigazione delle sfogline) invece che
 * duplicati (e spesso diversi) in ciascuna.
 *
 * Le CHIAVI restano quelle già usate come data-idx-group/'g' in tutto il
 * plugin (messaggi, sfogline, corsi, sfide, contenuti, pagamenti,
 * impostazioni, strumenti) — cambiano solo etichetta, icona e colore, per
 * non dover ritoccare le decine di punti che assegnano già una sezione al
 * suo gruppo. 'partner' è l'unica chiave nuova (Artigiani, Scuole, Caroselli,
 * Vetrina, Aspetto «Le Sfogline» — prima mescolati altrove).
 */
function gs_categorie() {
	return array(
		'messaggi'     => array( 'nome' => 'Comunicazione',                     'ico' => '📣', 'colore' => '#2b7a9e' ),
		'sfogline'     => array( 'nome' => 'Sfogline',                          'ico' => '👭', 'colore' => '#1f6e37' ),
		'corsi'        => array( 'nome' => 'Corsi & Formazione',                'ico' => '🎓', 'colore' => '#6b4fa0' ),
		'sfide'        => array( 'nome' => 'Percorsi e Sfide',                  'ico' => '🏆', 'colore' => '#cd8b0c' ),
		'contenuti'    => array( 'nome' => 'Contenuti & Racconti',              'ico' => '📚', 'colore' => '#9c5a2e' ),
		'partner'      => array( 'nome' => 'Partner & Vetrine',                 'ico' => '🤝', 'colore' => '#b23a67' ),
		'pagamenti'    => array( 'nome' => 'Pagamenti',                         'ico' => '💶', 'colore' => '#3f7d6a' ),
		'impostazioni' => array( 'nome' => 'Impostazioni & Sito',               'ico' => '⚙️', 'colore' => '#5c4a34' ),
		'strumenti'    => array( 'nome' => 'Strumenti',                         'ico' => '🧰', 'colore' => '#4a5568' ),
	);
}

/** Una categoria del registro (nome/ico/colore), o un ripiego neutro se la chiave non esiste. */
function gs_categoria( $chiave ) {
	$cat = gs_categorie();
	return isset( $cat[ $chiave ] ) ? $cat[ $chiave ] : array( 'nome' => 'Altro', 'ico' => '🗂️', 'colore' => '#8a7a5a' );
}

/** Il colore esadecimale di una categoria, pronto per --zc o per un pallino. */
function gs_categoria_colore( $chiave ) {
	return gs_categoria( $chiave )['colore'];
}

/**
 * Impostazioni di default del plugin.
 * Tutto è modificabile da Gaming Sfogline > Impostazioni, senza toccare il codice.
 */
function gs_default_settings() {
	return array(

		// --- Punti per azione ---
		'points' => array(
			'pubblica_sfoglia' => 20,
			'voto_dato'        => 5,
			'stella_ricevuta'  => 1,
			'voce_diario'      => 15,
			'consiglio'        => 20,
			'commento_sfida'   => 5,
			'primo_posto'      => 100,
			'secondo_posto'    => 60,
			'terzo_posto'      => 30,
			'streak_settimana'    => 10,
			'lezione_vista'       => 5,
			'percorso_completato' => 30,
			'tutti_percorsi_completati' => 100,
			'risposta_esatta'     => 10,
			'indovina_corretta'   => 5,
			'tavolo_foto'         => 5,
			'misura_vinta'        => 30,
			'giuria_vinta'        => 30,
			'giuria_voto'         => 5,
			'piatto_adottato'     => 20,
			'voto_sondaggio'      => 5,
			'proposta_sondaggio'  => 10,
		),

		// --- Livelli (Le Insegne della Sfoglia) ---
		'levels' => array(
			array( 'soglia' => 0,    'titolo' => 'Sfoglina Novella',       'simbolo' => '🌱' ),
			array( 'soglia' => 100,  'titolo' => 'Sfoglina',               'simbolo' => '🌾' ),
			array( 'soglia' => 300,  'titolo' => 'Sfoglina Provetta',      'simbolo' => '🥚' ),
			array( 'soglia' => 700,  'titolo' => 'Maestra della Sfoglia',  'simbolo' => '🫒' ),
			array( 'soglia' => 1500, 'titolo' => 'Sfoglina d\'Oro',        'simbolo' => '🏅' ),
			array( 'soglia' => 3000, 'titolo' => 'Custode della Tradizione','simbolo' => '👑' ),
		),

		// --- Missioni giornaliere ---
		'missions' => array(
			'vota_3'     => array( 'label' => 'Vota 3 sfoglie e commentane almeno una', 'punti' => 10, 'obiettivo' => 3 ),
			'diario'     => array( 'label' => 'Aggiungi una voce al Diario dell\'Impasto', 'punti' => 15, 'obiettivo' => 1 ),
			'consiglio'  => array( 'label' => 'Condividi un consiglio su farina o uova', 'punti' => 20, 'obiettivo' => 1 ),
			'commento'   => array( 'label' => 'Commenta la sfida della settimana', 'punti' => 5, 'obiettivo' => 1 ),
		),

		// --- Squadre regionali ---
		'teams' => array( 'Team Nord', 'Team Centro', 'Team Sud e Isole' ),

		// --- Livelli dei Percorsi Guidati (gestibili dal pannello: crea/rinomina/elimina/riordina) ---
		'percorso_livelli' => array( 'Base', 'Intermedio', 'Avanzato' ),

		// --- Indovina la Sfoglia: banca di domande per il quiz-lampo giornaliero ---
		'indovina_domande' => array(),

		// --- Anti-spam ---
		'antispam' => array(
			'min_seconds'   => 3,   // secondi minimi tra caricamento pagina e invio
			'max_per_hour'  => 10,  // invii massimi per IP nell'arco di un'ora
		),

		// --- Premio di fine anno ---
		'year_prize' => array(
			'numero_vincitrici' => 12,
			'testo'             => 'Corso di una giornata (10:00–18:00) con Rina Poletti, pranzo incluso, con possibilità di confronto personale sulle tematiche di proprio interesse.',
		),

		// --- Notifiche push (OneSignal) ---
		'onesignal' => array(
			'app_id'       => '',
			'rest_api_key' => '',
		),

		// --- Contributo gaming dopo la prova gratuita (l'iscrizione resta sempre gratuita) ---
		'registration' => array(
			'importo_quota' => '29,00',
		),

		// --- Funzionalità attivabili/disattivabili ---
		'features' => array(
			'vetrina_attiva'        => true,  // Vetrina pubblica del profilo
			'percorso_mensile'      => false, // Buono Sfoglia: gara mensile a 2500 punti. SPENTO finché non parte davvero (Ennio, 23/08/2026)
			'blackout'              => false, // Oscura tutto il Gaming (blackout)
			'blocca_wp_admin'       => false, // Nega la dashboard di WP a chi non è amministratore vero
			'dettatura_vocale_attiva' => true, // Microfono per scrivere a voce nei campi di testo
		),

		// --- Dettatura vocale: sfogline per cui il valore sopra è capovolto ---
		'dettatura_vocale_eccezioni' => array(),

		// --- Logo personalizzato per diplomi/certificati/locandine (vuoto = logo di default) ---
		'certificato_logo' => '',

		// --- Moderazione chat: parole/frasi da segnalare automaticamente alla redazione ---
		'parole_vietate' => array(),

		// --- Media (compressione/limite caricamenti) ---
		'media' => array(
			'comprimi_foto'   => true,  // ridimensiona/comprime le foto lato browser
			'foto_max_lato'   => 1600,  // lato massimo in px
			'limite_mb'       => 8,     // peso massimo per file (MB)
			'comprimi_video'  => false, // richiede ffmpeg sul server
		),

		// --- Backup automatico giornaliero dei file registrati ---
		'backup' => array(
			'attivo'    => false, // backup giornaliero via WP-Cron
			'retention' => 7,     // quanti backup conservare
		),

		// --- Canali "Esperto Risponde" (consulenze private a token, dal 2026-07-30) ---
		'esperti' => array(
			'rina'  => array( 'nome' => 'Rina Poletti Risponde', 'esperto' => 0, 'attivo' => true, 'costo_token' => 1 ),
			'bruno' => array( 'nome' => 'Bruno Cingolani Risponde', 'esperto' => 0, 'attivo' => true, 'costo_token' => 1 ),
		),

		// --- Token per le consulenze private (contributi associativi) ---
		'token' => array(
			'valore_euro'     => 5,  // quanti euro vale 1 token, per il calcolo suggerito nell'accredito
			'giorni_rimborso' => 7, // giorni senza risposta prima del rimborso automatico
		),

		// --- Limiti domande per sfoglina (0 = illimitato) ---
		'esperti_limiti' => array(
			'giorno'    => 3,
			'settimana' => 10,
			'mese'      => 30,
			'cooldown'  => 20, // secondi tra una domanda e l'altra
		),

		// --- Lezioni Video: promemoria per le lezioni consigliate non ancora viste ---
		'lezioni' => array(
			'promemoria_giorni' => 3,
		),

		// --- La Sfida del Silenzio: periodo con classifica congelata (impostato a mano) ---
		'silenzio' => array(
			'da'       => '',
			'a'        => '',
			'periodo'  => '',
			'snapshot' => array(),
		),
	);
}

/** Scorciatoia alle impostazioni media. */
function gs_media_settings() {
	$s = gs_settings();
	return isset( $s['media'] ) ? $s['media'] : array();
}

/** Scorciatoia alle impostazioni backup. */
function gs_backup_settings() {
	$s = gs_settings();
	return isset( $s['backup'] ) ? $s['backup'] : array();
}

/** Cartella (protetta) dei backup, dentro uploads. */
function gs_backup_dir() {
	$up = wp_upload_dir();
	return trailingslashit( $up['basedir'] ) . 'gs-backups';
}

/**
 * True se il Gaming è in blackout (oscurato a tutte tranne chi gestisce).
 */
function gs_blackout_attivo() {
	$s = gs_settings();
	return ! empty( $s['features']['blackout'] );
}

/**
 * True se la gara mensile del Buono Sfoglia è davvero in corso.
 *
 * Nasce il 23/08/2026 da un fatto concreto: il percorso mensile era scritto
 * nel codice ma non era mai partito davvero, e la chiusura automatica del
 * mese girava lo stesso — mandando a tutte un resoconto di una gara che non
 * c'era ("hai totalizzato 0 punti, mancavano 2500"). È già successo con
 * luglio 2026 e si sarebbe ripetuto il 1° settembre con agosto.
 *
 * Spento di default, apposta: il resoconto mensile deve partire quando lo
 * decide Ennio, non quando arriva il primo del mese.
 */
function gs_percorso_mensile_attivo() {
	$s = gs_settings();
	return ! empty( $s['features']['percorso_mensile'] );
}

/** Elenco degli ID delle sfogline che vedono il Gaming anche durante il blackout. */
function gs_blackout_esenti_lista() {
	$s = gs_settings();
	return isset( $s['blackout_esenti'] ) && is_array( $s['blackout_esenti'] )
		? array_map( 'intval', $s['blackout_esenti'] )
		: array();
}

/** True se questa sfoglina (o quella corrente) è tra le eccezioni al blackout. */
function gs_blackout_esente( $uid = 0 ) {
	$uid = $uid ? (int) $uid : get_current_user_id();
	if ( ! $uid ) {
		return false;
	}
	return in_array( $uid, gs_blackout_esenti_lista(), true );
}

/**
 * True se la dettatura vocale (microfono) è abilitata per questa sfoglina (o
 * quella corrente). C'è un interruttore generale (Impostazioni →
 * Funzionalità) e un elenco di eccezioni per nome che lo capovolgono solo
 * per loro — stesso schema del blackout.
 */
function gs_dettatura_vocale_abilitata( $uid = 0 ) {
	$uid     = $uid ? (int) $uid : get_current_user_id();
	$s       = gs_settings();
	$default = ! empty( $s['features']['dettatura_vocale_attiva'] );
	if ( ! $uid ) { return $default; }
	$eccezioni = isset( $s['dettatura_vocale_eccezioni'] ) && is_array( $s['dettatura_vocale_eccezioni'] )
		? array_map( 'intval', $s['dettatura_vocale_eccezioni'] )
		: array();
	return in_array( $uid, $eccezioni, true ) ? ! $default : $default;
}

/**
 * True se la Vetrina pubblica è attiva (interruttore generale).
 */
function gs_vetrina_attiva() {
	$s = gs_settings();
	return ! empty( $s['features']['vetrina_attiva'] );
}

/**
 * True se l'interruttore "Blocca dashboard WP" è acceso. Utile durante la
 * manutenzione, per condividere il sito con i collaboratori senza dare loro
 * accesso alla dashboard vera di WordPress — solo al pannello del progetto.
 */
function gs_blocco_wpadmin_attivo() {
	$s = gs_settings();
	return ! empty( $s['features']['blocca_wp_admin'] );
}

/**
 * True se, con l'interruttore acceso, questo utente va rimandato al pannello
 * del sito invece di poter aprire la dashboard di WordPress. Gli
 * amministratori veri (manage_options) non sono mai bloccati, altrimenti
 * nessuno potrebbe più riaccendere l'interruttore. Nemmeno gli Editori
 * (edit_others_posts — capacità che ha solo chi è Editore o Amministratore,
 * non le sfogline registrate col ruolo Iscritto): servono per chi scrive
 * davvero articoli sul sito (es. Rina Poletti, Bruno Cingolani) e deve poter
 * usare la bacheca di WordPress — segnalato da Ennio il 2026-08-06, prima
 * restavano bloccati anche loro insieme alle sfogline.
 */
function gs_wp_admin_va_bloccato_per( $uid ) {
	if ( ! gs_blocco_wpadmin_attivo() ) {
		return false;
	}
	// Esente anche chi ha gs_manage_gaming: è la capacità data SOLO ai
	// collaboratori del pannello nominati dal titolare (gs_ajax_collab_crea)
	// e, dall'attivazione del plugin, agli Amministratori veri — mai alle
	// sfogline, che si registrano con ruolo Iscritto e non la possiedono.
	// Senza questa esenzione un collaboratore autorizzato al "Pannello
	// Generale" nuovo (2026-08-08) veniva comunque rimandato fuori da
	// wp-admin prima ancora di poterlo vedere.
	if ( user_can( $uid, 'manage_options' ) || user_can( $uid, 'edit_others_posts' ) || user_can( $uid, 'gs_manage_gaming' ) ) {
		return false;
	}
	return true;
}

/**
 * True se la vetrina di UNA singola sfoglina è stata bloccata dall'amministrazione.
 */
function gs_vetrina_bloccata( $user_id ) {
	return '1' === get_user_meta( (int) $user_id, 'gs_vetrina_bloccata', true );
}

/**
 * True se la sfoglina ha attivato la propria Vetrina spendendo i token
 * (richiesto da Ennio il 2026-08-11: la Vetrina — e il link condivisibile
 * fuori dal sito — si sblocca solo comprandola coi token, non è più gratuita
 * per chi ha l'interruttore generale acceso). Vedi gs_ajax_vetrina_attiva_token()
 * in shortcodes.php e gs_token_costo_vetrina() in token.php per il costo.
 */
function gs_vetrina_token_attivata( $user_id ) {
	return '1' === get_user_meta( (int) $user_id, 'gs_vetrina_token_attiva', true );
}

/**
 * True se, a prescindere dai token, questa sfoglina PUÒ in linea di massima
 * avere una Vetrina: interruttore generale acceso e non bloccata a mano
 * dall'amministrazione. Usata per decidere se mostrarle il riquadro con
 * l'offerta di attivazione (a pagamento in token) dentro "La Mia Sfoglia".
 */
function gs_vetrina_permessa( $user_id ) {
	return gs_vetrina_attiva() && ! gs_vetrina_bloccata( $user_id );
}

/**
 * True se la vetrina di una sfoglina è effettivamente visibile al pubblico:
 * interruttore generale acceso, sfoglina non bloccata, E attivata da lei con
 * i token. Usata sia per la pagina pubblica della Vetrina sia per decidere,
 * altrove nel sito (Le Sfogline, caroselli…), se il suo nome è cliccabile.
 */
function gs_vetrina_disponibile( $user_id ) {
	return gs_vetrina_permessa( $user_id ) && gs_vetrina_token_attivata( $user_id );
}

/**
 * Restituisce l'intero array delle impostazioni (con fallback ai default).
 *
 * Il risultato viene messo in cache per la durata della richiesta: la funzione
 * è chiamata moltissime volte (punti, livelli, ogni shortcode) e senza cache
 * rieseguirebbe ogni volta lettura dell'opzione + merge dei default. La cache
 * si azzera da sola quando le impostazioni vengono salvate (vedi hook sotto).
 */
function gs_settings( $force = false ) {
	static $cache = null;
	if ( $force ) {
		$cache = null;
	}
	if ( is_array( $cache ) ) {
		return $cache;
	}

	$saved = get_option( GS_OPTION, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	// Merge non ricorsivo sui primi livelli per non perdere i default mancanti.
	$defaults = gs_default_settings();
	foreach ( $defaults as $key => $value ) {
		if ( ! isset( $saved[ $key ] ) ) {
			$saved[ $key ] = $value;
		} elseif ( is_array( $value ) && is_array( $saved[ $key ] ) && ! wp_is_numeric_array( $value ) ) {
			$saved[ $key ] = wp_parse_args( $saved[ $key ], $value );
		}
	}

	$cache = $saved;
	return $cache;
}

/**
 * Azzera la cache delle impostazioni quando l'opzione viene creata o aggiornata,
 * così una lettura successiva nella stessa richiesta vede i valori nuovi.
 */
function gs_settings_flush_cache() {
	gs_settings( true );
}
add_action( 'add_option_' . GS_OPTION, 'gs_settings_flush_cache' );
add_action( 'update_option_' . GS_OPTION, 'gs_settings_flush_cache' );

/**
 * Contatore di "generazione" per le cache di elenco valide per la richiesta.
 *
 * Alcune funzioni (elenco sfogline, richieste in attesa) rifanno la stessa query
 * più volte nella stessa pagina. Le mettiamo in cache; quando qualcosa che
 * cambia quegli elenchi viene modificato (stato di un'iscrizione, permesso di
 * gestore) si chiama gs_cache_bump() e la cache viene ricostruita.
 */
function gs_cache_generation( $bump = false ) {
	static $gen = 0;
	if ( $bump ) {
		$gen++;
	}
	return $gen;
}

/** Invalida le cache di elenco per-richiesta. */
function gs_cache_bump() {
	gs_cache_generation( true );
}

/**
 * Ottiene il valore in punti di una determinata azione.
 */
function gs_get_points_value( $action, $default = 0 ) {
	$settings = gs_settings();
	return isset( $settings['points'][ $action ] ) ? (int) $settings['points'][ $action ] : (int) $default;
}

/**
 * Sanitizza un testo semplice.
 */
function gs_clean( $value ) {
	return sanitize_text_field( wp_unslash( $value ) );
}

/**
 * URL della pagina Privacy Policy del sito. Usa quella impostata in
 * Impostazioni > Privacy di WordPress (si aggiorna da sola se la pagina
 * cambia slug); se non è impostata, ricade su /privacy-policy/.
 */
function gs_privacy_policy_url() {
	$url = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '';
	return $url ? $url : home_url( '/privacy-policy/' );
}

/**
 * Cerca nel testo una delle parole/frasi vietate impostate dal titolare
 * (Impostazioni > Moderazione). Confronto senza distinzione fra maiuscole e
 * minuscole, per sotto-stringa. Ritorna la parola/frase trovata, o '' se
 * nessuna corrispondenza (o se la lista è vuota).
 */
function gs_testo_parola_vietata_trovata( $testo ) {
	$s     = gs_settings();
	$lista = isset( $s['parole_vietate'] ) && is_array( $s['parole_vietate'] ) ? $s['parole_vietate'] : array();
	$testo = (string) $testo;
	if ( empty( $lista ) || '' === trim( $testo ) ) { return ''; }
	foreach ( $lista as $parola ) {
		$parola = trim( (string) $parola );
		if ( '' === $parola ) { continue; }
		if ( false !== mb_stripos( $testo, $parola ) ) {
			return $parola;
		}
	}
	return '';
}

/**
 * Recupera l'IP del visitatore (best effort).
 */
function gs_get_ip() {
	if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	}
	return '0.0.0.0';
}

/**
 * URL della pagina pubblica registrata in un'opzione gs_page_* (es.
 * 'gs_page_esperto'), o la home se la pagina non è (ancora) impostata.
 * Usata per far puntare l'aeroplanino cliccabile alla sezione giusta.
 */
function gs_pagina_url( $option_key ) {
	$pid = (int) get_option( $option_key );
	return $pid ? get_permalink( $pid ) : home_url( '/' );
}

/**
 * Testo semplice scritto dal pannello (titoli, messaggi di benvenuto…) con un
 * minimo di formattazione concessa: **testo** diventa grassetto. Sempre
 * esc_html() prima di tutto, quindi nessun rischio di HTML arbitrario — solo
 * i marcatori ** vengono trasformati in <strong>, dopo l'escape (i due
 * asterischi restano caratteri normali, non toccati da esc_html).
 */
function gs_grassetto_html( $testo, $con_a_capo = true ) {
	$out = esc_html( trim( $testo ) );
	$out = preg_replace( '/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $out );
	return $con_a_capo ? nl2br( $out ) : $out;
}

/**
 * Genere di un utente: 'f' (sfoglina) o 'm' (sfoglino).
 * Default 'f' per compatibilità con gli account storici, mai impostati esplicitamente.
 */
function gs_genere( $user_id ) {
	$user_id = (int) $user_id;
	if ( ! $user_id ) {
		return 'f';
	}
	$g = get_user_meta( $user_id, 'gs_genere', true );
	return ( 'm' === $g ) ? 'm' : 'f';
}

/**
 * Restituisce "sfoglina" o "sfoglino" (o qualunque coppia femminile/maschile
 * passata) in base al genere dell'utente. Uso: gs_decl( $uid, 'sfoglina', 'sfoglino' ).
 */
function gs_decl( $user_id, $femminile, $maschile ) {
	return ( 'm' === gs_genere( $user_id ) ) ? $maschile : $femminile;
}

/**
 * Verifica DEFINITIVA se un utente è davvero una sfoglina (non un docente,
 * un amministratore, un account Artigiani/Scuole/Lettore, o un doppione di
 * prova) — non solo "approvata".
 *
 * Segnalato più volte da Ennio (11/08/2026): senza il controllo sul ruolo,
 * gs_is_approved() da solo considera "approvata" chiunque abbia
 * manage_options (quindi anche il titolare, un admin di prova, un editore
 * come Bruno Cingolani se il suo gs_status è vuoto) — bug corretto una prima
 * volta in gs_sez_sfogline() (v3.197.0) ma poi ripetuto in altri punti scritti
 * separatamente (pagina "Le Sfogline", caroselli, elenco del pannello "Cerca
 * sfoglina", esportazione dati). Questa è ora LA funzione da usare ovunque si
 * elenchino "le sfogline": non duplicare più il controllo a mano altrove.
 */
function gs_e_sfoglina_vera( $user, $richiedi_approvata = true ) {
	$user = is_numeric( $user ) ? get_user_by( 'id', (int) $user ) : $user;
	if ( ! $user || ! isset( $user->ID ) ) {
		return false;
	}
	$stato = get_user_meta( $user->ID, 'gs_status', true );

	// "Abilitata come sfoglina": interruttore esplicito sulla scheda personale.
	// È il SÌ definitivo — vale anche per un docente che resta Editor o
	// Amministratore per i suoi permessi wp-admin (Rina Poletti, Bruno
	// Cingolani), senza doverne cambiare il ruolo. Va controllato PRIMA di
	// qualunque altro controllo su $stato, non solo sospesa/eliminata (bug
	// corretto il 16/08/2026 per sospesa/eliminata, era successo davvero a
	// Rina Poletti e Bruno Cingolani; esteso il 25/08/2026 anche ad
	// artigiano/scuola_cucina/lettore: un account diventato partner per
	// errore — vedi P2, caso reale dell'8/08/2026 — restava fuori anche con
	// l'interruttore acceso, perché quel controllo stava sopra a questo).
	if ( '1' === get_user_meta( $user->ID, 'gs_conta_come_sfoglina', true ) ) {
		return true;
	}

	// Account di altro tipo: mai sfogline, in nessun caso.
	if ( in_array( $stato, array( 'artigiano', 'scuola_cucina', 'lettore' ), true ) ) {
		return false;
	}

	// Disattivata/eliminata a mano dalla scheda personale: fuori da tutto.
	if ( in_array( $stato, array( 'sospesa', 'eliminata' ), true ) ) {
		return false;
	}

	// Da qui in poi valgono le regole automatiche, per le sfogline che si
	// iscrivono dal modulo pubblico senza che tu debba abilitarle a mano.
	if ( ! in_array( 'subscriber', (array) $user->roles, true ) ) {
		return false;
	}
	if ( user_can( $user->ID, 'manage_options' ) || user_can( $user->ID, 'gs_manage_gaming' ) ) {
		return false;
	}

	// $richiedi_approvata = false: gli strumenti del gestore ("Cerca sfoglina")
	// devono trovare anche chi è ancora in attesa o rifiutata.
	if ( ! $richiedi_approvata ) {
		return true;
	}

	// REGOLA DEFINITIVA (Ennio, 11/08/2026: "tutti gli altri account vanno
	// eliminati dal database sfogline e da qualsiasi pagina"). Serve lo stato
	// 'approvata' ESPLICITO, messo dalla registrazione del plugin + successiva
	// approvazione in segreteria. Prima bastava non avere nessuno stato, per
	// non escludere le iscritte storiche: ma su questo sito quella tolleranza
	// faceva entrare tra le sfogline QUALSIASI utente WordPress (contatti,
	// iscritti alla newsletter, account creati a mano), che ricomparivano di
	// continuo anche dopo averli disattivati uno per uno. Chi è una sfoglina
	// vera senza stato registrato si abilita in un clic dalla sua scheda
	// personale ("Abilitata come sfoglina").
	return 'approvata' === $stato;
}

/**
 * Conta le sfogline vere che compaiono nelle pagine pubbliche — per i numeri
 * mostrati nei cruscotti dei pannelli. Prima questi numeri contavano
 * letteralmente ogni utente WordPress del sito.
 */
function gs_conta_sfogline_pubbliche() {
	$n = 0;
	foreach ( get_users( array( 'fields' => 'ID' ) ) as $uid ) {
		if ( gs_e_sfoglina_vera( (int) $uid ) ) { $n++; }
	}
	return $n;
}

/**
 * Verifica se un utente è una sfoglina approvata (può votare/pubblicare/salire).
 */
function gs_is_approved( $user_id ) {
	$user_id = (int) $user_id;
	if ( ! $user_id ) {
		return false;
	}
	// Gli amministratori sono sempre "approvati".
	if ( user_can( $user_id, 'manage_options' ) ) {
		return true;
	}
	$status = get_user_meta( $user_id, 'gs_status', true );
	// Chi non ha stato impostato (utenti preesistenti) è considerato approvato.
	return ( '' === $status || 'approvata' === $status );
}

/**
 * Questa persona può PARTECIPARE al gaming adesso? Approvata e non congelata.
 *
 * È la porta unica lato AJAX, gemella di gs_gate_riservato() lato pagina:
 * quella decide cosa si vede, questa decide cosa si può scrivere. Nasce da
 * due cose trovate insieme il 26/08/2026 — l'account leggero "solo per
 * commentare" (gs_status = 'lettore') che passava in 31 handler su 38
 * controllando solo "sei collegata?" invece di gs_is_approved(), e il
 * congelamento a trenta giorni che senza questa riga si sarebbe fermato
 * alle pagine, lasciando scrivibili i contenuti da una scheda del browser
 * rimasta aperta.
 */
function gs_puo_partecipare( $uid ) {
	$uid = (int) $uid;
	if ( ! $uid || ! gs_is_approved( $uid ) ) { return false; }
	if ( function_exists( 'gs_sfoglina_congelata' ) && gs_sfoglina_congelata( $uid ) ) { return false; }
	return true;
}

/**
 * Trimestre corrente nel formato "2026-Q1".
 */
function gs_current_quarter( $timestamp = null ) {
	$timestamp = $timestamp ? $timestamp : current_time( 'timestamp' );
	$month     = (int) date( 'n', $timestamp );
	$year      = date( 'Y', $timestamp );
	$quarter   = (int) ceil( $month / 3 );
	return $year . '-Q' . $quarter;
}

/**
 * Settimana ISO corrente nel formato "2026-W27".
 */
function gs_current_week( $timestamp = null ) {
	$timestamp = $timestamp ? $timestamp : current_time( 'timestamp' );
	return date( 'o-\WW', $timestamp );
}

/**
 * Filtro di sicurezza da usare sul risultato di get_posts(): tiene solo i post
 * che sono DAVVERO del tipo richiesto (uno solo, o un elenco di tipi
 * ammessi). Alcuni temi/plugin di terze parti alterano le query anche con
 * "suppress_filters" attivo (pre_get_posts è un'azione, non un filtro, e
 * parte sempre) — capitato con contenuti reali del sito (articoli, pagine)
 * mescolati a elenchi del plugin come le richieste di aiuto o le ricette.
 * Questo controllo in più garantisce che negli elenchi non finisca mai
 * nulla che non sia del tipo giusto, qualunque sia la causa a monte.
 */
function gs_solo_tipo( $posts, $post_type ) {
	$tipi_ammessi = (array) $post_type;
	return array_values( array_filter( (array) $posts, function ( $p ) use ( $tipi_ammessi ) {
		return isset( $p->ID ) && in_array( get_post_type( $p->ID ), $tipi_ammessi, true );
	} ) );
}

/**
 * get_posts() con un filtro per autore, aggirando un'interferenza reale del
 * tema Newspaper: the_newspaper_post_author_archive() (nel tema, non nel
 * plugin — framework/function/general-functions.php) intercetta OGNI query
 * — non solo quella principale della pagina — che porti con sé un
 * parametro "author", e le forza il post_type a ['post','project'],
 * qualunque cosa fosse stato richiesto davvero. L'aggancio è
 * pre_get_posts, quindi parte anche con "suppress_filters" attivo (è
 * un'azione, non un filtro — stesso avviso già scritto in gs_solo_tipo()),
 * e scatta perché WP_Query segna is_author=true appena vede un parametro
 * "author" fra gli argomenti: il tema controlla proprio quel segnale,
 * pensato per le pagine archivio "tutti gli articoli di questo autore", non
 * per una query interna di un plugin.
 *
 * Trovato il 25/08/2026, cercando perché gs_art_owner_post() non trovava
 * mai la vetrina appena creata; a quel punto corretto lì evitando il
 * parametro (elenco piccolo, filtro in PHP). Qui la stessa interferenza
 * riguarda query più grandi e con "posts_per_page" a -1 (tutti i lavori di
 * una sfoglina, in più tipi di contenuto insieme): filtrare in PHP
 * vorrebbe dire caricare in memoria tutto il contenuto pubblicato del sito
 * per poi scartarne quasi tutto. Meglio togliere per un istante l'aggancio
 * del tema, fare la query com'era scritta, e rimetterlo subito dopo: la
 * query gira come se quell'interferenza non esistesse.
 *
 * @param array $args Gli stessi argomenti di get_posts(), incluso 'author'.
 */
function gs_get_posts_by_author( $args ) {
	// has_action() restituisce la PRIORITÀ, non vero/falso: va riusata sia
	// per togliere sia per rimettere. Con la priorità predefinita (10) su un
	// aggancio registrato altrove, remove_action() non toglierebbe niente
	// — in silenzio — e add_action() finirebbe per registrarne una seconda
	// copia. E con priorità 0, has_action() restituisce 0, che è falso:
	// il controllo va fatto con !== false, non con la verità del valore.
	$priorita = has_action( 'pre_get_posts', 'the_newspaper_post_author_archive' );
	if ( false === $priorita ) {
		return get_posts( $args );   // aggancio non presente: niente da aggirare
	}

	remove_action( 'pre_get_posts', 'the_newspaper_post_author_archive', $priorita );
	try {
		return get_posts( $args );
	} finally {
		// "finally" garantisce che l'aggancio del tema torni al suo posto
		// anche se la query solleva un errore: lasciarlo tolto per il resto
		// della richiesta romperebbe le pagine archivio del tema.
		add_action( 'pre_get_posts', 'the_newspaper_post_author_archive', $priorita );
	}
}

// -----------------------------------------------------------------------------
// CESTINO — eliminazione definitiva (solo dal Pannello Generale del titolare)
// -----------------------------------------------------------------------------

/**
 * True solo per chi ha davvero i permessi di amministratore del sito
 * (manage_options) — non per i collaboratori a cui è stato dato solo il
 * permesso gs_manage_gaming (vedi gs_can_manage()). Usata per l'unica azione
 * del progetto che non passa dal cestino, quindi non è recuperabile:
 * l'eliminazione definitiva.
 */
function gs_is_titolare() {
	return current_user_can( 'manage_options' );
}

/**
 * Tipi di contenuto per cui è permessa l'eliminazione definitiva dal cestino
 * del Pannello Generale — solo i contenuti gestiti dal titolare/staff
 * elencati nei vari moduli, mai contenuti personali di una sfoglina.
 */
function gs_cestino_tipi_permessi() {
	return array(
		'gs_locandina', 'gs_lezione', 'gs_ricetta', 'gs_testimonianza', 'gs_msg_interno', 'gs_aiuto',
		'gs_cassaforte', 'gs_voce', 'gs_piatto', 'gs_errore_didattico', 'gs_misura', 'gs_premio', 'gs_faq',
		'gs_sondaggio', 'gs_novita', 'gs_messaggio',
	);
}

/** Intestazione con la casella "seleziona tutto" per una tabella di cestino — vuota per chi non è il titolare. */
function gs_cestino_th_checkbox() {
	return gs_is_titolare() ? '<th style="width:34px"><input type="checkbox" class="gs-cestino-check-tutti" title="Seleziona tutto"></th>' : '';
}

/** Casella di spunta per una riga di cestino — vuota per chi non è il titolare. */
function gs_cestino_td_checkbox( $id ) {
	return gs_is_titolare() ? '<td><input type="checkbox" class="gs-cestino-check" value="' . (int) $id . '"></td>' : '';
}

/**
 * Riga con il pulsante "Elimina definitivamente i selezionati", da mettere
 * subito sotto una tabella di cestino che usa gs_cestino_th_checkbox()/
 * gs_cestino_td_checkbox(). Vuota per chi non è il titolare: un'eliminazione
 * definitiva non è mai recuperabile, a differenza di tutto il resto del
 * progetto che passa sempre dal cestino.
 */
function gs_cestino_azioni_bulk( $tipo ) {
	if ( ! gs_is_titolare() ) {
		return '';
	}
	return '<p class="gs-cestino-azioni"><button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-cestino-elimina-def" data-tipo="' . esc_attr( $tipo ) . '">🗑️ Elimina definitivamente i selezionati</button> <span class="gs-cestino-bulk-msg gs-richiesta-esito"></span></p>';
}

add_action( 'wp_ajax_gs_cestino_elimina_definitivo', 'gs_ajax_cestino_elimina_definitivo' );
function gs_ajax_cestino_elimina_definitivo() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_is_titolare() ) {
		wp_send_json_error( array( 'message' => 'Solo il titolare del sito può eliminare in modo definitivo.' ) );
	}
	$tipo = isset( $_POST['tipo'] ) ? sanitize_key( wp_unslash( $_POST['tipo'] ) ) : '';
	if ( ! in_array( $tipo, gs_cestino_tipi_permessi(), true ) ) {
		wp_send_json_error( array( 'message' => 'Tipo non valido.' ) );
	}
	$ids = isset( $_POST['ids'] ) ? array_filter( array_map( 'intval', (array) $_POST['ids'] ) ) : array();
	if ( ! $ids ) {
		wp_send_json_error( array( 'message' => 'Seleziona almeno un elemento.' ) );
	}

	$eliminati = 0;
	foreach ( $ids as $id ) {
		// Solo se è davvero del tipo indicato ed è già nel cestino: non deve
		// mai poter eliminare per errore qualcosa che non ci è ancora passato.
		if ( $tipo === get_post_type( $id ) && 'trash' === get_post_status( $id ) && wp_delete_post( $id, true ) ) {
			$eliminati++;
		}
	}
	if ( ! $eliminati ) {
		wp_send_json_error( array( 'message' => 'Nessun elemento eliminato: erano già stati ripristinati o rimossi.' ) );
	}
	wp_send_json_success( array( 'message' => $eliminati . ( 1 === $eliminati ? ' elemento eliminato definitivamente.' : ' elementi eliminati definitivamente.' ), 'eliminati' => $eliminati ) );
}

/**
 * Restituisce l'URL della vetrina pubblica di una sfoglina.
 */
function gs_vetrina_url( $user_id ) {
	$page_id = (int) get_option( 'gs_page_vetrina' );
	$user    = get_userdata( $user_id );
	if ( ! $user || ! $page_id ) {
		return '';
	}
	// user_nicename, non user_login: lo username è metà delle credenziali di
	// accesso e non deve finire in un indirizzo pubblico che gira su
	// WhatsApp e su Google (Ennio, 26/08/2026, ISTRUZIONE-IL-RESET.md).
	return add_query_arg( 'sfoglina', rawurlencode( $user->user_nicename ), get_permalink( $page_id ) );
}

/**
 * URL del logo tondo (sigillo) usato nei documenti stampabili. Se il
 * titolare ne ha caricato uno personalizzato dal pannello, quello vince
 * ovunque (diploma, certificato di percorso, locandine); altrimenti ogni
 * documento resta con il proprio logo di default, passato in $default_path
 * relativo alla cartella assets/img/ del plugin.
 */
function gs_certificato_logo_url( $default_path = 'assets/img/sigillo-accademia.png' ) {
	$s = gs_settings();
	if ( ! empty( $s['certificato_logo'] ) ) {
		return $s['certificato_logo'];
	}
	return defined( 'GS_URL' ) ? GS_URL . $default_path : '';
}

/** True se il logo (personalizzato o di default) esiste davvero. */
function gs_certificato_logo_esiste( $default_path = 'assets/img/sigillo-accademia.png' ) {
	$s = gs_settings();
	if ( ! empty( $s['certificato_logo'] ) ) {
		return true; // caricato dal titolare (libreria media): si assume esistente
	}
	return defined( 'GS_DIR' ) && file_exists( GS_DIR . $default_path );
}

/**
 * Foglio di stile condiviso per gli attestati stampabili a piena pagina
 * (cornice dorata su pergamena, sigillo, targa col titolo, firma/data) —
 * usato sia dal diploma dell'Area Professionale (area-pro.php) sia dal
 * certificato di Percorso Guidato (percorsi-lezioni.php), per restare
 * visivamente identici senza duplicare il CSS in due file.
 */
function gs_certificato_css() {
	return '<style>
		:root {
			--parchment-a: #fbf4e2; --parchment-b: #f3e6c6;
			--card-a: #fffdf7; --card-b: #f8edd3;
			--gold: #bd8a13; --gold-deep: #8a5a1f; --gold-line: #c79a3f; --gold-soft: #e7c877;
			--ink: #3a2a1a; --ink-soft: #6b5a42;
			--serif: Georgia, "Times New Roman", serif;
			--sans: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
		}
		* { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
		html, body {
			margin: 0; padding: 0; font-family: var(--sans); color: var(--ink);
			background: radial-gradient(ellipse at top, var(--parchment-a), var(--parchment-b) 75%);
		}
		.page { padding: 30px 16px 40px; }

		.diploma-wrap {
			max-width: 760px; margin: 0 auto; padding: 14px;
			background: linear-gradient(180deg, var(--gold-soft), var(--gold-line));
			border-radius: 22px;
			box-shadow: 0 24px 60px -20px rgba(90, 60, 10, .45);
		}
		.diploma {
			position: relative;
			background:
				radial-gradient(circle at 15% 10%, rgba(255,255,255,.5), transparent 45%),
				linear-gradient(155deg, var(--card-a) 0%, var(--card-b) 100%);
			border: 1px solid var(--gold);
			border-radius: 16px;
			padding: 46px 54px 40px;
			text-align: center;
			overflow: hidden;
		}
		.diploma::before {
			content: ""; position: absolute; inset: 10px;
			border: 1.5px solid rgba(189,138,19,.55); border-radius: 10px; pointer-events: none;
		}
		.corner { position: absolute; width: 26px; height: 26px; border: 2px solid var(--gold-deep); opacity: .8; }
		.corner.tl { top: 14px; left: 14px; border-right: none; border-bottom: none; border-top-left-radius: 8px; }
		.corner.tr { top: 14px; right: 14px; border-left: none; border-bottom: none; border-top-right-radius: 8px; }
		.corner.bl { bottom: 14px; left: 14px; border-right: none; border-top: none; border-bottom-left-radius: 8px; }
		.corner.br { bottom: 14px; right: 14px; border-left: none; border-top: none; border-bottom-right-radius: 8px; }

		.eyebrow-top { font-size: 11px; font-weight: 700; letter-spacing: .32em; text-transform: uppercase; color: var(--gold-deep); margin: 0 0 22px; }

		.sigillo-wrap { margin: 0 auto 22px; width: 168px; height: 168px; position: relative; }
		.sigillo-wrap::after { content: ""; position: absolute; inset: -10px; border-radius: 50%; box-shadow: 0 0 0 1px rgba(189,138,19,.35); }
		.sigillo { width: 100%; height: 100%; display: block; border-radius: 50%; filter: drop-shadow(0 10px 18px rgba(90,60,10,.35)); }

		.chip {
			display: inline-block; font-family: var(--serif); font-weight: 700; font-size: 1.28em; color: #fffaf0;
			background: linear-gradient(180deg, #c79a3f, #96691e); padding: 10px 30px; border-radius: 999px;
			box-shadow: inset 0 1px 0 rgba(255,255,255,.35), inset 0 -6px 10px -4px rgba(0,0,0,.28), 0 6px 14px -6px rgba(90,60,10,.5);
			margin-bottom: 10px;
		}
		.sottotitolo { font-size: 13px; color: var(--ink-soft); letter-spacing: .08em; text-transform: uppercase; margin: 14px 0 26px; }

		.intestazione { font-family: var(--serif); font-weight: 700; font-size: 1.15em; letter-spacing: .03em; color: var(--ink); margin: 0 0 8px; }

		.doc-foto-riga { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; margin: 22px 0; }
		.doc-foto { max-width: 220px; max-height: 260px; width: auto; height: auto; border-radius: 10px; border: 1px solid var(--gold-line); box-shadow: 0 10px 22px -10px rgba(90,60,10,.4); }

		.divider { display: flex; align-items: center; gap: 14px; margin: 0 auto 22px; max-width: 320px; }
		.divider .line { flex: 1; height: 1px; background: linear-gradient(90deg, transparent, var(--gold-line), transparent); }
		.divider svg { width: 30px; height: 30px; flex: none; color: var(--gold-deep); }

		.nome { font-family: var(--serif); font-size: 2.1em; font-weight: 700; color: var(--ink); margin: 0 0 10px; padding-bottom: 10px; display: inline-block; border-bottom: 1.5px solid var(--gold-line); }

		.testo { font-family: var(--serif); font-style: italic; font-size: 16px; line-height: 1.75; color: var(--ink); max-width: 480px; margin: 22px auto 0; }

		.firma-riga { display: flex; align-items: flex-end; justify-content: center; gap: 60px; margin-top: 34px; }
		.firma, .data-riga { text-align: center; }
		.firma .nome-firma { font-family: var(--serif); font-style: italic; font-size: 1.2em; color: var(--ink); border-bottom: 1px solid var(--ink-soft); padding-bottom: 4px; min-width: 170px; }
		.firma .etichetta, .data-riga .etichetta { margin-top: 6px; font-size: 10.5px; letter-spacing: .12em; text-transform: uppercase; color: var(--ink-soft); }
		.data-riga .valore { font-family: var(--serif); font-size: 1.05em; color: var(--ink); border-bottom: 1px solid var(--ink-soft); padding-bottom: 4px; min-width: 130px; display: inline-block; }

		.noprint { margin-top: 22px; display: flex; flex-wrap: wrap; justify-content: center; align-items: stretch; gap: 12px; }
		.noprint button {
			font-family: var(--sans); font-size: 13px; font-weight: 700; letter-spacing: .04em; color: #fffaf0;
			background: linear-gradient(180deg, var(--gold-line), var(--gold-deep)); border: none; border-radius: 8px;
			padding: 11px 22px; cursor: pointer; box-shadow: 0 6px 14px -6px rgba(90,60,10,.5);
			min-width: 200px;
		}

		@media print {
			.page { padding: 0; }
			.diploma-wrap { box-shadow: none; margin: 0 auto; }
			.noprint { display: none; }
		}
		@media (max-width: 560px) {
			.diploma { padding: 34px 20px 30px; }
			.nome { font-size: 1.5em; }
			.firma-riga { flex-direction: column; gap: 18px; }
		}
	</style>';
}
