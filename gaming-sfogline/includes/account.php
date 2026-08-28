<?php
/**
 * account.php — "Il tuo account", dentro La Mia Sfoglia: cambio password,
 * cambio email, esportazione dei propri dati, richiesta di cancellazione
 * dell'account.
 *
 * Nessuna tabella, nessun CPT nuovo: tutto passa dalle funzioni utente
 * native di WordPress (wp_check_password, wp_set_password, wp_update_user).
 * La cancellazione vera dell'account NON è mai automatica: è sempre una
 * richiesta che la segreteria gestisce a mano, come per l'approvazione
 * dell'iscrizione — coerente con tutto il resto del progetto, dove nulla
 * di distruttivo è mai un solo clic della sfoglina.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// AJAX — cambio password
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_account_password', 'gs_ajax_account_password' );
function gs_ajax_account_password() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid ) {
		wp_send_json_error( array( 'message' => 'Devi accedere.' ) );
	}

	$user     = get_userdata( $uid );
	$vecchia  = isset( $_POST['gs_acc_vecchia'] ) ? (string) $_POST['gs_acc_vecchia'] : '';
	$nuova    = isset( $_POST['gs_acc_nuova'] ) ? (string) $_POST['gs_acc_nuova'] : '';
	$conferma = isset( $_POST['gs_acc_nuova2'] ) ? (string) $_POST['gs_acc_nuova2'] : '';

	if ( ! $user || ! wp_check_password( $vecchia, (string) ( $user->user_pass ?? '' ), $uid ) ) {
		wp_send_json_error( array( 'message' => 'La password attuale non è corretta.' ) );
	}
	if ( strlen( $nuova ) < 6 ) {
		wp_send_json_error( array( 'message' => 'La nuova password deve avere almeno 6 caratteri.' ) );
	}
	if ( $nuova !== $conferma ) {
		wp_send_json_error( array( 'message' => 'Le due password non coincidono.' ) );
	}

	wp_set_password( $nuova, $uid );
	wp_send_json_success( array( 'message' => 'Password aggiornata.' ) );
}

// -----------------------------------------------------------------------------
// AJAX — cambio email
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_account_email', 'gs_ajax_account_email' );
function gs_ajax_account_email() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid ) {
		wp_send_json_error( array( 'message' => 'Devi accedere.' ) );
	}

	$user        = get_userdata( $uid );
	$password    = isset( $_POST['gs_acc_password'] ) ? (string) $_POST['gs_acc_password'] : '';
	$nuova_email = isset( $_POST['gs_acc_email'] ) ? sanitize_email( wp_unslash( $_POST['gs_acc_email'] ) ) : '';

	if ( ! $user || ! wp_check_password( $password, (string) ( $user->user_pass ?? '' ), $uid ) ) {
		wp_send_json_error( array( 'message' => 'La password non è corretta.' ) );
	}
	if ( ! is_email( $nuova_email ) ) {
		wp_send_json_error( array( 'message' => 'Inserisci un\'email valida.' ) );
	}
	if ( strtolower( $nuova_email ) !== strtolower( $user->user_email ) && email_exists( $nuova_email ) ) {
		wp_send_json_error( array( 'message' => 'Esiste già un account con questa email.' ) );
	}

	$esito = wp_update_user( array( 'ID' => $uid, 'user_email' => $nuova_email ) );
	if ( is_wp_error( $esito ) ) {
		wp_send_json_error( array( 'message' => $esito->get_error_message() ) );
	}
	wp_send_json_success( array( 'message' => 'Email aggiornata.' ) );
}

// -----------------------------------------------------------------------------
// Esportazione dei propri dati (diritto GDPR di accesso/portabilità)
// -----------------------------------------------------------------------------

/** Riepilogo dei dati personali di una sfoglina — usato dall'export e testabile da solo. */
function gs_account_dati_personali( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return array();
	}
	return array(
		'nome'          => $user->display_name,
		'username'      => $user->user_login,
		'email'         => $user->user_email,
		'squadra'       => function_exists( 'gs_get_user_team' ) ? (string) gs_get_user_team( $user_id ) : '',
		'livello'       => function_exists( 'gs_get_level' ) ? (string) gs_get_level( $user_id )['titolo'] : '',
		'punti_totali'  => (int) get_user_meta( $user_id, 'gs_points', true ),
		'data_nascita'  => (string) get_user_meta( $user_id, 'gs_birthdate', true ),
		'stato_account' => (string) get_user_meta( $user_id, 'gs_status', true ),
	);
}

add_action( 'wp_ajax_gs_account_esporta', 'gs_ajax_account_esporta' );
function gs_ajax_account_esporta() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid ) {
		wp_send_json_error( array( 'message' => 'Devi accedere.' ) );
	}
	wp_send_json_success( array( 'dati' => gs_account_dati_personali( $uid ) ) );
}

// -----------------------------------------------------------------------------
// Richiesta di cancellazione account — mai automatica, sempre gestita a mano
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_account_richiedi_cancellazione', 'gs_ajax_account_richiedi_cancellazione' );
function gs_ajax_account_richiedi_cancellazione() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid ) {
		wp_send_json_error( array( 'message' => 'Devi accedere.' ) );
	}
	if ( get_user_meta( $uid, 'gs_richiesta_cancellazione', true ) ) {
		wp_send_json_error( array( 'message' => 'Hai già inviato una richiesta: la segreteria la sta esaminando.' ) );
	}
	update_user_meta( $uid, 'gs_richiesta_cancellazione', current_time( 'mysql' ) );

	$user        = get_userdata( $uid );
	$admin_email = get_option( 'admin_email' );
	if ( $user && $admin_email ) {
		wp_mail(
			$admin_email,
			'[Gaming Sfogline] Richiesta di cancellazione account',
			sprintf(
				"%s (%s, %s) ha chiesto la cancellazione del proprio account.\n\nGestiscila da WP-Admin > Utenti (verifica prima cosa ha creato la sfoglina, per non perdere contenuti collettivi collegati, es. una sfida vinta o un badge di squadra).",
				$user->display_name, $user->user_login, $user->user_email
			)
		);
	}

	wp_send_json_success( array( 'message' => 'Richiesta inviata: la segreteria la esaminerà ed elimina il tuo account entro qualche giorno. Se cambi idea, scrivi in segreteria.' ) );
}

// -----------------------------------------------------------------------------
// Riquadro "Il tuo account" — dentro La Mia Sfoglia
// -----------------------------------------------------------------------------
function gs_account_box_html( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return '';
	}
	$richiesta_fatta = get_user_meta( $user_id, 'gs_richiesta_cancellazione', true );

	ob_start();
	echo gs_box_open( '⚙️ Il tuo account' );
	echo gs_sezione_aiuto( 'Da qui puoi cambiare la tua password, aggiornare l\'email con cui accedi, scaricare un riepilogo dei tuoi dati (nome, punti, livello, squadra…) o chiedere alla segreteria di eliminare il tuo account. La cancellazione non è mai immediata: la richiesta arriva alla segreteria, che la gestisce a mano, così nessun account sparisce per un clic sbagliato.' );
	?>
	<details class="gs-sezione-aiuto">
		<summary>🔑 Cambia password</summary>
		<form class="gs-form gs-form-account-password" onsubmit="return false">
			<p><label>Password attuale<br><input type="password" name="gs_acc_vecchia" autocomplete="current-password" required></label></p>
			<p><label>Nuova password (almeno 6 caratteri)<br><input type="password" name="gs_acc_nuova" autocomplete="new-password" required minlength="6"></label></p>
			<p><label>Ripeti la nuova password<br><input type="password" name="gs_acc_nuova2" autocomplete="new-password" required minlength="6"></label></p>
			<p><button type="submit" class="gs-btn gs-btn-sm gs-account-password-invia">Salva la nuova password</button> <span class="gs-form-msg gs-richiesta-esito"></span></p>
		</form>
	</details>
	<details class="gs-sezione-aiuto">
		<summary>✉️ Cambia email (attuale: <?php echo esc_html( $user->user_email ); ?>)</summary>
		<form class="gs-form gs-form-account-email" onsubmit="return false">
			<p><label>Nuova email<br><input type="email" name="gs_acc_email" autocomplete="email" required></label></p>
			<p><label>Conferma con la tua password<br><input type="password" name="gs_acc_password" autocomplete="current-password" required></label></p>
			<p><button type="submit" class="gs-btn gs-btn-sm gs-account-email-invia">Salva la nuova email</button> <span class="gs-form-msg gs-richiesta-esito"></span></p>
		</form>
	</details>
	<details class="gs-sezione-aiuto">
		<summary>📄 Scarica i tuoi dati</summary>
		<p class="gs-hint">Un riepilogo di quello che il sito sa di te (profilo, punti, livello, squadra), da scaricare in un file di testo.</p>
		<p><button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-account-esporta">Scarica i miei dati</button> <span class="gs-form-msg gs-account-esporta-msg gs-richiesta-esito"></span></p>
	</details>
	<details class="gs-sezione-aiuto">
		<summary>🗑️ Elimina il mio account</summary>
		<?php if ( $richiesta_fatta ) : ?>
			<p class="gs-hint">Hai già inviato una richiesta di cancellazione (<?php echo esc_html( $richiesta_fatta ); ?>): la segreteria la sta esaminando.</p>
		<?php else : ?>
			<p class="gs-hint">Chiedi alla segreteria di eliminare definitivamente il tuo account e i tuoi dati. Non è immediato: qualcuno la esamina prima di procedere.</p>
			<p><button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-account-cancella-richiedi">Richiedi la cancellazione dell'account</button> <span class="gs-form-msg gs-account-cancella-msg gs-richiesta-esito"></span></p>
		<?php endif; ?>
	</details>
	<?php
	echo gs_box_close();
	return ob_get_clean();
}
