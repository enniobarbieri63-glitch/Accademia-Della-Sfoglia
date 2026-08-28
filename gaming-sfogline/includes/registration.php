<?php
/**
 * registration.php — Registrazione pubblica delle sfogline, gratuita, con
 * approvazione manuale della segreteria: un controllo della richiesta, non
 * la verifica di un pagamento (Ennio, 28/08/2026: l'iscrizione resta
 * gratuita, il contributo di 29€ riguarda solo il gaming dopo la prova).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gestisce l'invio del modulo di registrazione (AJAX).
 */
add_action( 'wp_ajax_nopriv_gs_registrati', 'gs_ajax_registrati' );
add_action( 'wp_ajax_gs_registrati', 'gs_ajax_registrati' );

function gs_ajax_registrati() {
	check_ajax_referer( 'gs_ajax', 'nonce' );

	// Anti-spam.
	$check = gs_antispam_check( $_POST, 'registrazione' );
	if ( is_wp_error( $check ) ) {
		wp_send_json_error( array( 'message' => $check->get_error_message() ) );
	}

	$nome     = gs_clean( $_POST['nome'] ?? '' );
	$email    = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$username = sanitize_user( wp_unslash( $_POST['username'] ?? '' ) );
	$password = (string) ( $_POST['password'] ?? '' );
	$squadra  = gs_clean( $_POST['squadra'] ?? '' );
	$genere   = ( 'm' === ( $_POST['genere'] ?? '' ) ) ? 'm' : 'f';
	$privacy  = ! empty( $_POST['privacy'] );

	// Validazioni.
	if ( ! $nome || ! is_email( $email ) || ! $username || strlen( $password ) < 6 ) {
		wp_send_json_error( array( 'message' => 'Compila tutti i campi obbligatori (password almeno 6 caratteri).' ) );
	}
	if ( ! $privacy ) {
		wp_send_json_error( array( 'message' => 'Devi accettare la Privacy Policy per iscriverti.' ) );
	}
	if ( username_exists( $username ) ) {
		wp_send_json_error( array( 'message' => 'Username già in uso, scegline un altro.' ) );
	}
	if ( email_exists( $email ) ) {
		wp_send_json_error( array( 'message' => 'Esiste già un account con questa email.' ) );
	}

	// Crea l'utente con ruolo "subscriber" ma stato "in attesa".
	$user_id = wp_insert_user( array(
		'user_login'   => $username,
		'user_email'   => $email,
		'user_pass'    => $password,
		'display_name' => $nome,
		'first_name'   => $nome,
		'role'         => 'subscriber',
	) );

	if ( is_wp_error( $user_id ) ) {
		wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
	}

	// user_nicename è l'identificatore PUBBLICO: finisce negli indirizzi che
	// girano su WhatsApp e su Google. WordPress lo copia dallo username, che è
	// metà delle credenziali di accesso — qui lo stacchiamo e lo costruiamo dal
	// nome visibile (Ennio, 26/08/2026: "nessun dato personale di accesso deve
	// andare in rete"). Non cambia lo username: la sfoglina continua ad
	// accedere con quello che ha sempre usato, cambia solo cosa finisce
	// nell'indirizzo pubblico.
	$base = sanitize_title( $nome );
	$slug = $base ? $base : 'sfoglina';
	$n    = 2;
	while ( get_user_by( 'slug', $slug ) ) { $slug = $base . '-' . $n++; } // due "Maria Rossi" convivono
	wp_update_user( array( 'ID' => $user_id, 'user_nicename' => $slug ) );

	update_user_meta( $user_id, 'gs_status', 'in_attesa' );
	update_user_meta( $user_id, 'gs_genere', $genere );
	gs_cache_bump();
	if ( $squadra ) {
		update_user_meta( $user_id, 'gs_team', $squadra );
	}
	// Data di nascita (per "I Compleanni di Oggi").
	$nascita = isset( $_POST['nascita'] ) ? sanitize_text_field( wp_unslash( $_POST['nascita'] ) ) : '';
	if ( $nascita && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $nascita ) ) {
		update_user_meta( $user_id, 'gs_birthdate', $nascita );
	}

	// Notifica all'amministrazione.
	$admin_email = get_option( 'admin_email' );
	$requests_url = admin_url( 'admin.php?page=gs-richieste' );
	$soggetto = ( 'm' === $genere ) ? 'Un nuovo sfoglino' : 'Una nuova sfoglina';
	wp_mail(
		$admin_email,
		'[Gaming Sfogline] Nuova richiesta di iscrizione',
		sprintf(
			"%s ha richiesto l'iscrizione:\n\nNome: %s\nUsername: %s\nEmail: %s\nSquadra: %s\n\nGestisci le richieste qui:\n%s",
			$soggetto, $nome, $username, $email, $squadra ? $squadra : '—', $requests_url
		)
	);

	// Verifica email: non blocca né rallenta l'approvazione (che resta sempre
	// manuale, decisa dalla segreteria) — serve solo a intercettare email
	// scritte male/inventate prima che la segreteria perda tempo ad
	// approvare un account che poi non riceve nessuna comunicazione.
	gs_email_verifica_invia( $user_id );

	// Avvisa tutti gli amministratori del pannello (admin + collaboratori).
	do_action( 'gs_after_registration', $user_id );

	wp_send_json_success( array(
		'message' => 'Registrazione ricevuta! Il tuo account sarà attivato dopo il controllo della segreteria. Ti abbiamo anche inviato un\'email: clicca sul link per confermare che è la tua.',
	) );
}

// -----------------------------------------------------------------------------
// Verifica email — informativa, non blocca l'approvazione manuale
// -----------------------------------------------------------------------------

/** True se questa sfoglina ha già confermato la propria email. */
function gs_email_verificata( $user_id ) {
	return (bool) get_user_meta( $user_id, 'gs_email_verificata', true );
}

/** Genera un token, lo salva, e invia l'email con il link di conferma. */
function gs_email_verifica_invia( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return;
	}
	$token = wp_generate_password( 32, false );
	// Il contrassegno va scritto PRIMA di mandare: gs_mail_template_render()
	// legge questo stesso meta per costruire {{LINK_VERIFICA_EMAIL}} — vedi
	// mail-area-riservata.php, stesso schema delle due date della mail di
	// benvenuto. La mail vive nel registro (chiave 'conferma_email'), non
	// più qui — documento IL-PANNELLO-DELLE-MAIL.md, 26/08/2026.
	update_user_meta( $user_id, 'gs_email_verify_token', $token );

	if ( function_exists( 'gs_invia_mail_template' ) ) {
		gs_invia_mail_template( 'conferma_email', $user_id );
	}
}

/** Verifica il token e segna l'email come confermata. Ritorna true/false. */
function gs_email_verifica_conferma( $user_id, $token ) {
	$user_id = (int) $user_id;
	$salvato = get_user_meta( $user_id, 'gs_email_verify_token', true );
	if ( ! $salvato || ! $token || ! hash_equals( (string) $salvato, (string) $token ) ) {
		return false;
	}
	update_user_meta( $user_id, 'gs_email_verificata', 1 );
	delete_user_meta( $user_id, 'gs_email_verify_token' );
	return true;
}

/**
 * Elenco delle richieste in attesa.
 */
function gs_get_pending_users() {
	// Cache per-richiesta: l'elenco viene letto più volte (badge, tabella, conteggi).
	static $cache = null;
	static $cgen  = -1;
	$gen = function_exists( 'gs_cache_generation' ) ? gs_cache_generation() : 0;
	if ( null !== $cache && $cgen === $gen ) {
		return $cache;
	}
	$cache = get_users( array(
		'meta_key'   => 'gs_status',
		'meta_value' => 'in_attesa',
		'orderby'    => 'registered',
		'order'      => 'ASC',
	) );
	$cgen = $gen;
	return $cache;
}

/**
 * Approva una sfoglina.
 */
function gs_approve_user( $user_id ) {
	$user_id = (int) $user_id;
	update_user_meta( $user_id, 'gs_status', 'approvata' );
	gs_cache_bump();

	$user = get_userdata( $user_id );
	if ( $user ) {
		// La data di approvazione va scritta PRIMA di mandare la mail di
		// benvenuto: quella mail porta le due date dei trenta giorni
		// (gs_mail_template_render() le legge da qui), e la scrittura è
		// idempotente — su una sfoglina già approvata non sposta nulla.
		if ( ! get_user_meta( $user_id, 'gs_data_approvazione', true ) ) {
			update_user_meta( $user_id, 'gs_data_approvazione', current_time( 'Y-m-d' ) );
			update_user_meta(
				$user_id,
				'gs_abbonamento_scadenza',
				date( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' +30 days' ) )
			);
			update_user_meta( $user_id, 'gs_abbonamento', 'attivo' );
		}

		// La mail di benvenuto ora vive nel registro (gs_mail_template_registro()
		// in mail-area-riservata.php, chiave 'benvenuto'), non più qui: si
		// può modificare dal pannello «Le mail del sito» senza toccare il
		// codice — documento IL-PANNELLO-DELLE-MAIL.md, 26/08/2026.
		if ( function_exists( 'gs_invia_mail_template' ) ) {
			gs_invia_mail_template( 'benvenuto', $user_id );
		}
		// La scrittura una-volta-sola qui sopra serve anche a distanziare
		// "La Mia Sfoglia" e "Accesso e Vetrina" nei giorni successivi
		// (Ennio, 26/08/2026: "non tediamo gli iscritti") — vedi
		// gs_mail_benvenuto_differite() più sotto, sul cron giornaliero.
		// gs_dopo_approvazione_sfoglina resta per chi altro lo usa (nessuno
		// dei due mail-hook immediati è più agganciato qui, vedi sotto), ma
		// il nome dell'evento resta com'è per non rompere agganci esterni
		// futuri.
		do_action( 'gs_dopo_approvazione_sfoglina', $user_id );
	}
	return true;
}

/**
 * Le mail di benvenuto, distanziate nel tempo (Ennio, 26/08/2026: "non
 * mandiamo troppe mail, non tediamo gli iscritti"). Prima "La Mia Sfoglia" e
 * "Accesso e Vetrina" partivano insieme alla presentazione, tutte e tre nello
 * stesso istante: tre messaggi dallo stesso mittente in trenta secondi si
 * leggono come uno solo, e quello letto è il primo.
 *
 * Sul cron GIORNALIERO e non con wp_schedule_single_event(): un evento
 * singolo programmato fra due giorni dipende dalle visite al sito (WP-Cron)
 * e può non partire mai, in silenzio — pagato già una volta su questo
 * progetto. Qui, se il cron salta un giorno, il giorno dopo recupera da solo:
 * il confronto è sui giorni passati (>=), non su un momento esatto.
 */
add_action( 'gs_daily_cron', 'gs_mail_benvenuto_differite' );
function gs_mail_benvenuto_differite() {
	$piano = array(
		2 => 'la_mia_sfoglia',
		5 => 'accesso_vetrina',
	);

	$sfogline = function_exists( 'gs_sez_sfogline' ) ? gs_sez_sfogline() : array();
	foreach ( $sfogline as $u ) {
		$approvata = get_user_meta( $u->ID, 'gs_data_approvazione', true );
		if ( ! $approvata || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $approvata ) ) { continue; }

		// Congelata: niente mail di benvenuto mentre è fuori (stessa regola
		// del digest settimanale, 3.283.0).
		if ( function_exists( 'gs_abbonamento_scaduto' ) && gs_abbonamento_scaduto( $u->ID ) ) { continue; }

		// Due date attraverso la stessa funzione: mai un timestamp contro una
		// mezzanotte (è l'errore di P3, 25/08/2026).
		$giorni = (int) round(
			( strtotime( current_time( 'Y-m-d' ) ) - strtotime( $approvata ) ) / DAY_IN_SECONDS
		);

		foreach ( $piano as $quando => $chiave ) {
			if ( $giorni < $quando ) { continue; }
			$fatto = 'gs_mail_benv_' . $chiave;
			if ( get_user_meta( $u->ID, $fatto, true ) ) { continue; }
			// Contrassegno PRIMA di mandare: un cron che riparte non deve
			// poter mandare due volte la stessa mail.
			update_user_meta( $u->ID, $fatto, current_time( 'mysql' ) );
			if ( function_exists( 'gs_invia_mail_template' ) ) {
				gs_invia_mail_template( $chiave, $u->ID );
			}
		}
	}
}

/**
 * Rifiuta una richiesta.
 */
function gs_reject_user( $user_id ) {
	$user_id = (int) $user_id;
	$user    = get_userdata( $user_id );
	if ( $user && function_exists( 'gs_invia_mail_template' ) ) {
		// La mail vive nel registro (chiave 'richiesta_non_accolta'), non più
		// qui — documento IL-PANNELLO-DELLE-MAIL.md, 26/08/2026.
		gs_invia_mail_template( 'richiesta_non_accolta', $user_id );
	}
	update_user_meta( $user_id, 'gs_status', 'rifiutata' );
	// Entra nel Cestino delle sfogline anche da qui: archivia gli eventuali
	// dati di gioco già accumulati — vedi sfogline-extra.php.
	if ( function_exists( 'gs_archivia_dati_gaming_utente' ) ) {
		gs_archivia_dati_gaming_utente( $user_id );
	}
	gs_cache_bump();
	return true;
}
