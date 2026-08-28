<?php
/**
 * login.php — Pannello di accesso dedicato alle sfogline, al posto del
 * generico wp-login.php di WordPress, con la funzione "password
 * dimenticata" integrata.
 *
 * Nessuna tabella, nessun CPT: l'autenticazione vera resta quella nativa di
 * WordPress (wp_signon, get_password_reset_key, reset_password), il modulo
 * è solo una veste su misura per il sito, via AJAX come tutti gli altri
 * moduli pubblici del progetto.
 *
 * Sicurezza:
 *  - stesse protezioni antispam di ogni altro modulo (honeypot + trappola
 *    del tempo + limite invii per indirizzo, vedi antispam.php);
 *  - blocco temporaneo per indirizzo IP dopo troppi tentativi con password
 *    sbagliata (5 tentativi, 15 minuti), contati in un transient — si
 *    autodistrugge da solo, nessuna tabella nuova;
 *  - il modulo "password dimenticata" risponde sempre con lo stesso
 *    messaggio, che l'account esista o no: non deve rivelare quali email
 *    sono registrate.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GS_LOGIN_MAX_TENTATIVI', 5 );
define( 'GS_LOGIN_BLOCCO_SECONDI', 900 ); // 15 minuti

// -----------------------------------------------------------------------------
// Blocco per tentativi falliti (bruteforce), per indirizzo IP
// -----------------------------------------------------------------------------

/** Chiave del transient dei tentativi falliti per l'indirizzo IP corrente. */
function gs_login_chiave_tentativi() {
	return 'gs_login_tent_' . md5( gs_get_ip() );
}

/** Quanti tentativi falliti risultano, in questo momento, per questo IP. */
function gs_login_tentativi_falliti() {
	return (int) get_transient( gs_login_chiave_tentativi() );
}

/** True se l'indirizzo è temporaneamente bloccato per troppi tentativi falliti. */
function gs_login_bloccato() {
	return gs_login_tentativi_falliti() >= GS_LOGIN_MAX_TENTATIVI;
}

/** Registra un tentativo fallito per questo IP (finestra di blocco: 15 minuti). */
function gs_login_registra_fallimento() {
	set_transient( gs_login_chiave_tentativi(), gs_login_tentativi_falliti() + 1, GS_LOGIN_BLOCCO_SECONDI );
}

/** Azzera i tentativi falliti per questo IP (login riuscito). */
function gs_login_azzera_tentativi() {
	delete_transient( gs_login_chiave_tentativi() );
}

/**
 * Destinazione dopo un accesso riuscito: quella esplicitamente richiesta
 * (redirect_to, es. la pagina che ha rimandato al login); altrimenti, per un
 * account "Artigiano della Pasta", la sua "La Mia Vetrina"; altrimenti "La
 * Mia Sfoglia".
 *
 * $uid va passato esplicitamente subito dopo un wp_signon() riuscito: in
 * quel momento la sessione non è ancora "corrente" per get_current_user_id()
 * nella stessa richiesta, quindi senza il parametro un artigiano
 * finirebbe comunque sulla dashboard delle sfogline al primo accesso.
 */
function gs_login_destinazione( $redirect_to = '', $uid = 0 ) {
	$redirect_to = trim( (string) $redirect_to );
	if ( $redirect_to ) {
		return $redirect_to;
	}
	$uid = $uid ? (int) $uid : get_current_user_id();
	if ( $uid && function_exists( 'gs_art_is_artigiano' ) && gs_art_is_artigiano( $uid ) ) {
		return gs_art_pannello_url();
	}
	return function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_dashboard' ) : home_url( '/' );
}

// -----------------------------------------------------------------------------
// AJAX — accesso
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_nopriv_gs_login', 'gs_ajax_login' );
add_action( 'wp_ajax_gs_login', 'gs_ajax_login' );
function gs_ajax_login() {
	check_ajax_referer( 'gs_ajax', 'nonce' );

	if ( is_user_logged_in() ) {
		wp_send_json_success( array( 'message' => 'Sei già collegata.', 'redirect' => gs_login_destinazione( '' ) ) );
	}

	// Da qui in poi, tutto il controllo "sei bloccata? prova la password, e
	// se sbagli conta il tentativo" è racchiuso in un lock per indirizzo IP
	// — esattamente il tipo di protezione che un attacco a forza bruta
	// aggirerebbe sparando tentativi in parallelo: senza il lock, più
	// richieste arrivate insieme possono passare TUTTE il controllo "sei
	// bloccata?" prima che una sola di loro sia stata contata. Dimostrato
	// con un test il 14/08/2026: 30 tentativi insieme ne facevano passare
	// 10 invece dei 5 previsti — il doppio. Il lock riguarda solo
	// l'indirizzo IP di chi sta tentando l'accesso in quel momento: un
	// login legittimo da un altro indirizzo non aspetta nulla.
	global $wpdb;
	$lock_login = gs_login_chiave_tentativi();
	$wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 10)', $lock_login ) );

	if ( gs_login_bloccato() ) {
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_login ) );
		wp_send_json_error( array( 'message' => 'Troppi tentativi con password sbagliata da questo indirizzo. Riprova tra qualche minuto.' ) );
	}

	$check = gs_antispam_check( $_POST, 'login' );
	if ( is_wp_error( $check ) ) {
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_login ) );
		wp_send_json_error( array( 'message' => $check->get_error_message() ) );
	}

	$utente   = isset( $_POST['gs_login_user'] ) ? sanitize_text_field( wp_unslash( $_POST['gs_login_user'] ) ) : '';
	$password = isset( $_POST['gs_login_pwd'] ) ? (string) $_POST['gs_login_pwd'] : '';
	if ( '' === $utente || '' === $password ) {
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_login ) );
		wp_send_json_error( array( 'message' => 'Inserisci utente/email e password.' ) );
	}

	$user = wp_signon( array(
		'user_login'    => $utente,
		'user_password' => $password,
		'remember'      => ! empty( $_POST['gs_login_remember'] ),
	), is_ssl() );

	if ( is_wp_error( $user ) ) {
		gs_login_registra_fallimento();
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_login ) );
		wp_send_json_error( array( 'message' => 'Utente o password non corretti.' ) );
	}

	gs_login_azzera_tentativi();
	$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_login ) );
	$redirect = isset( $_POST['gs_login_redirect'] ) ? wp_unslash( $_POST['gs_login_redirect'] ) : '';
	wp_send_json_success( array( 'message' => 'Accesso riuscito!', 'redirect' => gs_login_destinazione( $redirect, $user->ID ) ) );
}

// -----------------------------------------------------------------------------
// AJAX — password dimenticata: invia l'email con il link per sceglierne una nuova
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_nopriv_gs_password_dimenticata', 'gs_ajax_password_dimenticata' );
add_action( 'wp_ajax_gs_password_dimenticata', 'gs_ajax_password_dimenticata' );
function gs_ajax_password_dimenticata() {
	check_ajax_referer( 'gs_ajax', 'nonce' );

	$check = gs_antispam_check( $_POST, 'password_dimenticata' );
	if ( is_wp_error( $check ) ) {
		wp_send_json_error( array( 'message' => $check->get_error_message() ) );
	}

	$utente = isset( $_POST['gs_pwd_user'] ) ? sanitize_text_field( wp_unslash( $_POST['gs_pwd_user'] ) ) : '';
	// Stesso messaggio in ogni caso: non deve rivelare se un'email/username esiste.
	$esito = 'Se l\'account esiste, riceverai a breve un\'email con le istruzioni per scegliere una nuova password.';
	if ( '' === $utente ) {
		wp_send_json_error( array( 'message' => 'Inserisci il tuo username o la tua email.' ) );
	}

	$user = is_email( $utente ) ? get_user_by( 'email', $utente ) : get_user_by( 'login', $utente );
	if ( $user ) {
		$key = get_password_reset_key( $user );
		if ( ! is_wp_error( $key ) ) {
			$link = add_query_arg(
				array( 'gs_reset_key' => $key, 'gs_reset_login' => rawurlencode( $user->user_login ) ),
				function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_login' ) : home_url( '/' )
			);
			wp_mail(
				$user->user_email,
				'Reimposta la tua password — Accademia della Sfoglia',
				sprintf(
					"Ciao %s,\n\nhai chiesto di reimpostare la password dell'Accademia della Sfoglia. Clicca qui per sceglierne una nuova (il link scade tra un'ora):\n%s\n\nSe non sei stata tu a chiederlo, ignora pure questa email: la tua password resta quella di sempre.",
					$user->display_name, $link
				)
			);
		}
	}

	wp_send_json_success( array( 'message' => $esito ) );
}

// -----------------------------------------------------------------------------
// AJAX — reimposta la password (dal link ricevuto via email)
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_nopriv_gs_password_reimposta', 'gs_ajax_password_reimposta' );
add_action( 'wp_ajax_gs_password_reimposta', 'gs_ajax_password_reimposta' );
function gs_ajax_password_reimposta() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$check = gs_antispam_check( $_POST, 'password_reimposta' );
	if ( is_wp_error( $check ) ) {
		wp_send_json_error( array( 'message' => $check->get_error_message() ) );
	}

	$login = isset( $_POST['gs_reset_login'] ) ? sanitize_text_field( wp_unslash( $_POST['gs_reset_login'] ) ) : '';
	$key   = isset( $_POST['gs_reset_key'] ) ? sanitize_text_field( wp_unslash( $_POST['gs_reset_key'] ) ) : '';
	$nuova = isset( $_POST['gs_reset_pwd'] ) ? (string) $_POST['gs_reset_pwd'] : '';
	$conferma = isset( $_POST['gs_reset_pwd2'] ) ? (string) $_POST['gs_reset_pwd2'] : '';

	$user = check_password_reset_key( $key, $login );
	if ( is_wp_error( $user ) ) {
		wp_send_json_error( array( 'message' => 'Il link non è più valido: chiedine uno nuovo con "Password dimenticata".' ) );
	}
	if ( strlen( $nuova ) < 6 ) {
		wp_send_json_error( array( 'message' => 'La password deve avere almeno 6 caratteri.' ) );
	}
	if ( $nuova !== $conferma ) {
		wp_send_json_error( array( 'message' => 'Le due password non coincidono.' ) );
	}

	reset_password( $user, $nuova );
	wp_send_json_success( array( 'message' => 'Password aggiornata! Ora puoi accedere con la nuova password.' ) );
}

// -----------------------------------------------------------------------------
// Shortcode pubblico [gs_login]
// -----------------------------------------------------------------------------
add_shortcode( 'gs_login', 'gs_sc_login' );
function gs_sc_login() {
	if ( is_user_logged_in() ) {
		$dash = function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_dashboard' ) : home_url( '/' );
		return gs_box_open( '🔑 Accedi' ) . '<p>Sei già collegata. Vai a <a href="' . esc_url( $dash ) . '">La Mia Sfoglia</a>.</p>' . gs_box_close();
	}

	// Arrivo dal link dell'email "password dimenticata": mostra il modulo
	// per scegliere la nuova password, invece del modulo di accesso.
	$reset_key   = isset( $_GET['gs_reset_key'] ) ? sanitize_text_field( wp_unslash( $_GET['gs_reset_key'] ) ) : '';
	$reset_login = isset( $_GET['gs_reset_login'] ) ? sanitize_text_field( wp_unslash( $_GET['gs_reset_login'] ) ) : '';
	if ( $reset_key && $reset_login ) {
		return gs_sc_login_reset_form( $reset_key, $reset_login );
	}

	// Arrivo dal link dell'email "conferma la tua email": verifica il token
	// e mostra un avviso, senza saltare il modulo di accesso normale.
	$avviso_verifica = '';
	if ( isset( $_GET['gs_verifica_email'], $_GET['gs_verifica_token'] ) && function_exists( 'gs_email_verifica_conferma' ) ) {
		$uid_verifica   = (int) $_GET['gs_verifica_email'];
		$token_verifica = sanitize_text_field( wp_unslash( $_GET['gs_verifica_token'] ) );
		$avviso_verifica = gs_email_verifica_conferma( $uid_verifica, $token_verifica )
			? '<p class="gs-form-msg ok">✅ Email confermata, grazie! Il tuo account sarà comunque attivato dalla segreteria dopo il controllo della tua iscrizione.</p>'
			: '<p class="gs-form-msg err">Questo link di conferma non è più valido (è già stato usato, oppure è scaduto).</p>';
	}

	$redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '';
	$bloccato    = gs_login_bloccato();

	ob_start();
	echo gs_box_open( '🔑 Accedi', '', 'gs-box-login' );
	echo gs_sezione_aiuto( 'Inserisci lo username (o l\'email) e la password scelti in fase di iscrizione. Se non hai ancora un account, iscriviti dalla pagina "Iscrizione"; se hai dimenticato la password, apri "Password dimenticata?" qui sotto e scrivi la tua email — arriverà un link per sceglierne una nuova. Per proteggere gli account, dopo alcuni tentativi con password sbagliata dallo stesso indirizzo l\'accesso resta temporaneamente bloccato: se succede, riprova tra qualche minuto.' );
	echo $avviso_verifica;
	if ( $bloccato ) {
		echo '<p class="gs-form-msg err">Troppi tentativi con password sbagliata da questo indirizzo. Riprova tra qualche minuto.</p>';
	}
	?>
	<form class="gs-form gs-form-login" onsubmit="return false">
		<?php gs_antispam_fields(); ?>
		<input type="hidden" name="gs_login_redirect" value="<?php echo esc_attr( $redirect_to ); ?>">
		<p><label>Username o email<br>
			<input type="text" name="gs_login_user" autocomplete="username" required <?php disabled( $bloccato, true ); ?>>
		</label></p>
		<p><label>Password<br>
			<input type="password" name="gs_login_pwd" autocomplete="current-password" required <?php disabled( $bloccato, true ); ?>>
		</label></p>
		<p><label><input type="checkbox" name="gs_login_remember" value="1" checked <?php disabled( $bloccato, true ); ?>> Resta collegata su questo dispositivo</label></p>
		<p><button type="submit" class="gs-btn gs-login-invia" <?php disabled( $bloccato, true ); ?>>Accedi</button> <span class="gs-form-msg gs-richiesta-esito"></span></p>
	</form>
	<p class="gs-hint">Non hai ancora un account? <a href="<?php echo esc_url( function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_iscrizione' ) : home_url( '/' ) ); ?>">Iscriviti</a></p>
	<details class="gs-sezione-aiuto">
		<summary>Password dimenticata?</summary>
		<form class="gs-form gs-form-password-dimenticata" onsubmit="return false">
			<?php gs_antispam_fields(); ?>
			<p><label>Il tuo username o la tua email<br><input type="text" name="gs_pwd_user" autocomplete="username" required></label></p>
			<p><button type="submit" class="gs-btn gs-btn-sm gs-pwd-dimenticata-invia">Invia le istruzioni</button> <span class="gs-form-msg gs-richiesta-esito"></span></p>
		</form>
	</details>
	<?php
	echo gs_box_close();
	return ob_get_clean();
}

/** Modulo "scegli una nuova password", mostrato dal link ricevuto via email. */
function gs_sc_login_reset_form( $reset_key, $reset_login ) {
	$valido = ! is_wp_error( check_password_reset_key( $reset_key, $reset_login ) );

	ob_start();
	echo gs_box_open( '🔑 Scegli una nuova password', '', 'gs-box-login' );
	if ( ! $valido ) {
		echo '<p class="gs-form-msg err">Questo link non è più valido (è già stato usato, oppure è scaduto). Torna alla pagina di accesso e richiedi di nuovo "Password dimenticata".</p>';
		echo '<p><a class="gs-btn gs-btn-sm" href="' . esc_url( function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_login' ) : home_url( '/' ) ) . '">Torna all\'accesso</a></p>';
		echo gs_box_close();
		return ob_get_clean();
	}
	?>
	<form class="gs-form gs-form-password-reimposta" onsubmit="return false">
		<?php gs_antispam_fields(); ?>
		<input type="hidden" name="gs_reset_key" value="<?php echo esc_attr( $reset_key ); ?>">
		<input type="hidden" name="gs_reset_login" value="<?php echo esc_attr( $reset_login ); ?>">
		<p><label>Nuova password (almeno 6 caratteri)<br><input type="password" name="gs_reset_pwd" autocomplete="new-password" required minlength="6"></label></p>
		<p><label>Ripeti la nuova password<br><input type="password" name="gs_reset_pwd2" autocomplete="new-password" required minlength="6"></label></p>
		<p><button type="submit" class="gs-btn gs-pwd-reimposta-invia">Salva la nuova password</button> <span class="gs-form-msg gs-richiesta-esito"></span></p>
	</form>
	<?php
	echo gs_box_close();
	return ob_get_clean();
}
