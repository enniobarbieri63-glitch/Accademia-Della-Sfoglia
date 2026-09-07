<?php
/**
 * iscrizioni.php — iscrizione pubblica a una sessione, con lo storico dei
 * pagamenti. Il gestore registra ogni bonifico ricevuto; lo storico non si
 * sovrascrive mai, si aggiunge soltanto (stesso principio già collaudato
 * in gaming-sfogline per gli Artigiani/Scuole partner).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Iscrive una persona a una sessione, se ci sono posti. Ritorna l'ID
 * dell'iscrizione o un WP_Error.
 *
 * Il controllo sullo stato della sessione non è una ripetizione di quello
 * che fa già il catalogo pubblico (che mostra il modulo solo per le
 * sessioni aperte): il modulo è solo la porta d'ingresso normale, ma
 * chiunque può chiamare direttamente admin-ajax.php con un ID qualsiasi.
 * Chi decide se un'iscrizione è ammessa è questa funzione, non la pagina.
 */
function slp_iscrivi( $sessione_id, $nome, $email, $telefono, $consenso = false ) {
	$sessione = get_post( $sessione_id );
	if ( ! $sessione || 'slp_sessione' !== $sessione->post_type || 'publish' !== $sessione->post_status ) {
		return new WP_Error( 'slp_sessione_non_trovata', 'Sessione non trovata.' );
	}

	if ( 'aperta' !== get_post_meta( $sessione->ID, 'slp_stato', true ) ) {
		return new WP_Error( 'slp_sessione_chiusa', 'Le iscrizioni per questa data non sono aperte.' );
	}

	$liberi = slp_posti_liberi( $sessione_id );
	if ( null !== $liberi && $liberi <= 0 ) {
		return new WP_Error( 'slp_posti_esauriti', 'Non ci sono più posti disponibili per questa data.' );
	}

	$nome = slp_clean( $nome );
	if ( '' === $nome ) {
		return new WP_Error( 'slp_nome_vuoto', 'Il nome non può essere vuoto.' );
	}
	$email = sanitize_email( wp_unslash( $email ) );
	if ( ! is_email( $email ) ) {
		return new WP_Error( 'slp_email_non_valida', 'Indirizzo email non valido.' );
	}

	// Il consenso non è una formalità del modulo: senza, qui non si
	// raccolgono nome, email e telefono di nessuno. Viene registrato con la
	// data, perché in caso di contestazione è l'unica prova che c'è stato.
	if ( ! $consenso ) {
		return new WP_Error( 'slp_consenso_mancante', "Per iscriverti serve il consenso al trattamento dei tuoi dati." );
	}

	if ( slp_gia_iscritto( $sessione_id, $email ) ) {
		return new WP_Error( 'slp_gia_iscritto', 'Risulta già un\'iscrizione con questa email per questa data.' );
	}

	$iscrizione_id = wp_insert_post( array(
		'post_type'   => 'slp_iscrizione',
		'post_title'  => $nome . ' — ' . get_the_title( $sessione ),
		'post_parent' => (int) $sessione_id,
		'post_status' => 'publish',
		'meta_input'  => array(
			'slp_nome'          => $nome,
			'slp_email'         => $email,
			'slp_telefono'      => slp_clean( $telefono ),
			'slp_stato'         => 'in_attesa',
			'slp_pagamenti'     => array(),
			'slp_consenso_data' => current_time( 'mysql' ),
			'slp_consenso_url'  => get_privacy_policy_url(),
		),
	), true );

	if ( is_wp_error( $iscrizione_id ) ) {
		return $iscrizione_id;
	}

	// Due persone che si iscrivono nello stesso istante all'ultimo posto
	// passerebbero entrambe il controllo qui sopra, perché tra la lettura
	// dei posti liberi e il salvataggio non c'è nulla che le trattenga.
	// Ricontare dopo aver salvato è il modo più semplice per accorgersene
	// (slp_posti_liberi() non serve, perché si ferma a zero e non mostra lo
	// sconfinamento): chi arriva secondo viene annullato subito e riceve il
	// messaggio dei posti esauriti, invece di presentarsi al corso senza
	// posto.
	$posti_max = slp_posti_max_effettivi( $sessione_id );
	if ( null !== $posti_max && slp_conta_iscritti( $sessione_id ) > $posti_max ) {
		wp_delete_post( $iscrizione_id, true );
		return new WP_Error( 'slp_posti_esauriti', 'Non ci sono più posti disponibili per questa data.' );
	}

	slp_email_conferma_iscrizione( $iscrizione_id );
	slp_email_avviso_gestore( $iscrizione_id );

	return $iscrizione_id;
}

/**
 * Annulla un'iscrizione: chi si è ritirato non deve continuare a occupare
 * un posto. L'iscrizione resta (con il suo storico dei pagamenti, che è
 * una scrittura di cassa e non si cancella), ma smette di contare tra gli
 * iscritti e il posto torna disponibile.
 */
function slp_annulla_iscrizione( $iscrizione_id ) {
	$iscrizione = get_post( $iscrizione_id );
	if ( ! $iscrizione || 'slp_iscrizione' !== $iscrizione->post_type ) {
		return new WP_Error( 'slp_iscrizione_non_trovata', 'Iscrizione non trovata.' );
	}

	update_post_meta( $iscrizione_id, 'slp_stato', 'annullata' );
	return true;
}

/**
 * True se quell'email risulta già iscritta a quella sessione (iscrizione
 * non annullata): evita il doppio invio del modulo e i duplicati creati a
 * ripetizione da un bot.
 */
function slp_gia_iscritto( $sessione_id, $email ) {
	$esistenti = get_posts( array(
		'post_type'      => 'slp_iscrizione',
		'post_status'    => 'publish',
		'post_parent'    => (int) $sessione_id,
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array( 'key' => 'slp_email', 'value' => $email ),
			array( 'key' => 'slp_stato', 'value' => 'annullata', 'compare' => '!=' ),
		),
	) );

	return ! empty( $esistenti );
}

/**
 * Registra un pagamento ricevuto (bonifico) su un'iscrizione. Aggiunge
 * allo storico, non sovrascrive mai le voci precedenti. Se il totale
 * versato raggiunge almeno l'acconto dovuto, l'iscrizione passa a
 * "confermata".
 */
function slp_registra_pagamento( $iscrizione_id, $importo_centesimi, $nota = '' ) {
	$iscrizione = get_post( $iscrizione_id );
	if ( ! $iscrizione || 'slp_iscrizione' !== $iscrizione->post_type ) {
		return new WP_Error( 'slp_iscrizione_non_trovata', 'Iscrizione non trovata.' );
	}

	// Un importo a zero o negativo era finito nello storico come "0,00 €"
	// senza dire niente a nessuno: un errore di battitura diventava una
	// riga di cassa falsa. Meglio rifiutare e farlo vedere.
	$importo_centesimi = (int) $importo_centesimi;
	if ( $importo_centesimi <= 0 ) {
		return new WP_Error( 'slp_importo_non_valido', 'L\'importo deve essere maggiore di zero.' );
	}

	$pagamenti   = get_post_meta( $iscrizione_id, 'slp_pagamenti', true );
	$pagamenti   = is_array( $pagamenti ) ? $pagamenti : array();
	$pagamenti[] = array(
		'data'              => current_time( 'mysql' ),
		'importo_centesimi' => $importo_centesimi,
		'nota'              => slp_clean( $nota ),
		// Chi ha registrato il bonifico. Sono soldi: se un domani il
		// pannello lo usa più di una persona, deve restare scritto chi ha
		// messo mano alla cassa e quando.
		'utente_id'         => get_current_user_id(),
	);
	update_post_meta( $iscrizione_id, 'slp_pagamenti', $pagamenti );

	$corso_codice = get_post_meta( $iscrizione->post_parent, 'slp_corso_codice', true );
	$corso        = slp_get_corso( $corso_codice );
	if ( $corso ) {
		$acconto    = slp_calcola_acconto( $corso['quota'] );
		$totale     = slp_totale_pagato( $pagamenti );
		$stato      = slp_stato_pagamento( $corso['quota'], $acconto, $totale );
		$era_confermata = 'confermata' === get_post_meta( $iscrizione_id, 'slp_stato', true );

		if ( in_array( $stato, array( 'acconto_versato', 'saldo_versato' ), true ) ) {
			update_post_meta( $iscrizione_id, 'slp_stato', 'confermata' );

			// La conferma si manda una volta sola, quando l'iscrizione passa
			// da "in attesa" a "confermata": un secondo bonifico a saldo non
			// deve far ripartire lo stesso messaggio.
			if ( ! $era_confermata ) {
				slp_email_conferma_pagamento( $iscrizione_id, $totale, $corso['quota'] );
			}
		}
	}

	return true;
}

/**
 * Le iscrizioni di una sessione, più recenti prima.
 */
function slp_iscrizioni_della_sessione( $sessione_id ) {
	return get_posts( array(
		'post_type'      => 'slp_iscrizione',
		'post_status'    => 'publish',
		'post_parent'    => (int) $sessione_id,
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
}

// -----------------------------------------------------------------------------
// AJAX
// -----------------------------------------------------------------------------

/**
 * Quante iscrizioni può inviare in un'ora chi arriva dallo stesso
 * indirizzo. Il modulo è pubblico e senza login: il nonce ferma il
 * riutilizzo da un altro sito, non un programma che compila il modulo
 * mille volte. Cinque è largo per una persona vera (che si iscrive a una
 * data, forse due) e stretto per chi riempie il database di finti nomi.
 */
const SLP_ISCRIZIONI_PER_ORA = 5;

function slp_chiave_limite_iscrizioni() {
	$indirizzo = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : 'ignoto';
	return 'slp_iscr_' . md5( $indirizzo );
}

add_action( 'wp_ajax_nopriv_slp_iscrivi', 'slp_ajax_iscrivi' );
add_action( 'wp_ajax_slp_iscrivi', 'slp_ajax_iscrivi' );
function slp_ajax_iscrivi() {
	check_ajax_referer( 'slp_ajax_pubblico', 'nonce' );

	// Campo esca: invisibile a chi legge la pagina, irresistibile per un
	// programma che compila tutti i campi che trova. Se è pieno, chi ha
	// inviato non è una persona.
	if ( '' !== trim( (string) ( $_POST['sito_web'] ?? '' ) ) ) {
		wp_send_json_error( array( 'message' => 'Iscrizione non registrata: il modulo risulta compilato da un programma automatico. Se sei una persona, scrivici pure direttamente.' ) );
	}

	// Il limite non vale per chi gestisce i corsi: è pensato contro i
	// programmi automatici, e sbatterci contro mentre si prova il modulo
	// dal proprio sito sarebbe solo un modo per far sembrare rotto quello
	// che funziona.
	if ( ! slp_can_manage() ) {
		$chiave    = slp_chiave_limite_iscrizioni();
		$tentativi = (int) get_transient( $chiave );
		if ( $tentativi >= SLP_ISCRIZIONI_PER_ORA ) {
			wp_send_json_error( array( 'message' => 'Troppe iscrizioni inviate da questo collegamento. Riprova più tardi, oppure scrivici direttamente.' ) );
		}
		set_transient( $chiave, $tentativi + 1, HOUR_IN_SECONDS );
	}

	$iscrizione_id = slp_iscrivi(
		$_POST['sessione_id'] ?? 0,
		$_POST['nome'] ?? '',
		$_POST['email'] ?? '',
		$_POST['telefono'] ?? '',
		'1' === (string) ( $_POST['consenso'] ?? '' )
	);

	if ( is_wp_error( $iscrizione_id ) ) {
		wp_send_json_error( array( 'message' => $iscrizione_id->get_error_message() ) );
	}

	wp_send_json_success( array( 'iscrizione_id' => $iscrizione_id ) );
}

add_action( 'wp_ajax_slp_registra_pagamento', 'slp_ajax_registra_pagamento' );
function slp_ajax_registra_pagamento() {
	check_ajax_referer( 'slp_ajax', 'nonce' );
	if ( ! slp_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Non hai i permessi per registrare un pagamento.' ) );
	}

	$importo_euro = (float) str_replace( ',', '.', (string) ( $_POST['importo'] ?? 0 ) );

	// Un importo assurdo è quasi sempre uno zero di troppo battuto in
	// fretta. Nessun corso del catalogo arriva vicino a questa cifra, e
	// una riga di cassa sbagliata di dieci volte è più difficile da
	// scoprire dopo che da fermare adesso.
	if ( $importo_euro > 100000 ) {
		wp_send_json_error( array( 'message' => 'Importo troppo alto: controlla se è sfuggito uno zero.' ) );
	}

	$risultato = slp_registra_pagamento(
		(int) ( $_POST['iscrizione_id'] ?? 0 ),
		(int) round( $importo_euro * 100 ),
		$_POST['nota'] ?? ''
	);

	if ( is_wp_error( $risultato ) ) {
		wp_send_json_error( array( 'message' => $risultato->get_error_message() ) );
	}

	wp_send_json_success();
}

add_action( 'wp_ajax_slp_annulla_iscrizione', 'slp_ajax_annulla_iscrizione' );
function slp_ajax_annulla_iscrizione() {
	check_ajax_referer( 'slp_ajax', 'nonce' );
	if ( ! slp_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Non hai i permessi per annullare un\'iscrizione.' ) );
	}

	$esito = slp_annulla_iscrizione( (int) ( $_POST['iscrizione_id'] ?? 0 ) );
	if ( is_wp_error( $esito ) ) {
		wp_send_json_error( array( 'message' => $esito->get_error_message() ) );
	}

	wp_send_json_success();
}
