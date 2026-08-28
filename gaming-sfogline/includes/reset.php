<?php
/**
 * reset.php — Il Reset del gioco, e lo username fuori dalla rete pubblica.
 *
 * Due lavori distinti, nello stesso file perché nascono dallo stesso
 * documento e vanno fatti nella stessa giornata (ISTRUZIONE-IL-RESET.md,
 * Ennio, 26/08/2026): prima lo username (perché tocca gli account, meglio
 * a sito fermo), poi il reset (perché è l'unica cosa che non si annulla).
 *
 * IL RESET — la regola è una sola: si cancella tutto quello che comincia
 * per gs_ (meta utente, contenuti), TRANNE un elenco scritto di cose da
 * tenere. Mai il contrario: un elenco di cose da cancellare dimenticherebbe
 * le chiavi costruite a runtime (gs_points_mese_2026-08, gs_badge_dato_…,
 * gs_lezione_vista_…) e lascerebbe sporcizia invisibile. Un elenco di cose
 * da tenere, se sbaglia, cancella qualcosa che si vede subito.
 *
 * Non tocca MAI: gli utenti stessi (nessun wp_delete_user — le persone le
 * decide Ennio, una alla volta, a mano), il catalogo che il titolare ha
 * scritto (lezioni, corsi, sfide, piatti, FAQ…), le impostazioni del
 * plugin (tranne i due segnaposto del Buono Sfoglia/Premio di Fine Anno e
 * il vincitore della Mappa dei Territori), e la Libreria Media — mai una
 * sola immagine: wp_delete_post() sui contenuti gs_ non tocca gli allegati,
 * che restano orfani ma recuperabili.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// PARTE 1 — Il Reset
// -----------------------------------------------------------------------------

/**
 * Le chiavi meta che il Reset NON tocca mai. Ogni gruppo ha una ragione, ed
 * è scritta: chi aggiungerà una chiave qui dentro fra un anno deve poter
 * capire in quale gruppo va, senza chiedere a nessuno.
 */
function gs_reset_meta_da_tenere() {
	return array(
		// --- Chi è. Non si tocca mai, per nessun motivo. ---
		'gs_status', 'gs_genere', 'gs_birthdate', 'gs_team',
		'gs_conta_come_sfoglina', 'gs_titolo_onorario',
		'gs_email_verificata', 'gs_email_verify_token',

		// --- L'abbonamento e i trenta giorni. Il gruppo più pericoloso: se
		// questi si cancellano, ogni sfoglina già dentro perde la sua
		// scadenza e il congelamento la tratta come se non avesse mai
		// pagato, il giorno dopo il reset.
		'gs_abbonamento', 'gs_abbonamento_scadenza',
		'gs_abbonamento_avviso_per', 'gs_data_approvazione',

		// --- I soldi. I token si comprano con un bonifico: cancellarli è
		// cancellare denaro versato.
		'gs_token_credito', 'gs_token_log', 'gs_token_rif',
		'gs_vetrina_token_attiva',

		// --- La Vetrina pubblica: si paga a parte, 49 euro.
		'gs_bio_testo', 'gs_bio_media', 'gs_bio_foto', 'gs_bio_stato', 'gs_bio_cestino',
		'gs_vetrina_bloccata',   // il blocco amministrativo. Il Reset tiene il contenuto
		                         // della Vetrina (gs_bio_*): cancellare il blocco da solo
		                         // rimetterebbe pubblica una Vetrina bloccata dal titolare.

		// --- Le sue scelte, le sue cose, le vostre note.
		'gs_notifiche_pref', 'gs_promemoria_ora', 'gs_bday_hidden',
		'gs_note_gestore', 'gs_soggiorno_scelta', 'gs_richiesta_cancellazione',
		'gs_lettore_bloccato', 'gs_lettore_bloccato_fino',
		'gs_testamento', 'gs_testamento_proponi',          // decisione 1
		'gs_sconto_pct', 'gs_sconto_livello', 'gs_sconto_log',  // decisione 2
		'gs_telefono',           // il numero di telefono: non lo scrive il plugin, è
		                         // messo a mano una volta e serve ai link WhatsApp
		                         // della regia iscritti. Dato di contatto, non punteggio.
		'gs_todos', 'gs_todos_cestino',  // le Cose da Fare, i promemoria personali: li
		                         // scrive lei, non sono punteggio — stesso motivo del
		                         // Testamento qui sopra (decisione di Ennio, 27/08/2026,
		                         // ISTRUZIONE-LA-PROVA-E-LE-QUATTRO-DECISIONI.md).

		// --- La scatola di chi è nel Cestino. gs_archivia_dati_gaming_utente()
		// (sfogline-extra.php) ci chiude dentro TUTTI i meta gs_ di una sfoglina
		// sospesa o rifiutata: abbonamento, scadenza, token, Vetrina, sconto.
		// Cancellare questa chiave sola vuol dire cancellarli tutti insieme, e
		// il ripristino dal Cestino non riporterebbe più niente. Chi tocca uno
		// dei due elenchi guardi anche l'altro: è
		// gs_archivio_gaming_meta_esclusi() a decidere cosa finisce qui dentro.
		'gs_archivio_gaming',       // = GS_ARCHIVIO_GAMING_META
	);
}

/**
 * I tipi di contenuto (CPT) che il Reset NON tocca mai.
 *
 * CORREZIONE rispetto a ISTRUZIONE-IL-RESET.md: il documento elenca questo
 * array senza 'gs_sfida', ma il testo dello stesso documento dice
 * esplicitamente «le sfide vecchie non le tocca il reset (sono contenuto
 * vostro)» — la sfida (la definizione: nome, criteri, date) è scritta dal
 * titolare come un corso o una lezione, non dalle sfogline. Copiare
 * l'elenco alla lettera avrebbe cancellato tutta la storia delle sfide,
 * contraddicendo il testo dello stesso documento. Aggiunta qui, segnalata
 * nel changelog (trovato 27/08/2026). Le SFOGLIE inviate alle sfide
 * (gs_sfoglia) restano fuori dall'elenco apposta: sono contenuto delle
 * sfogline, si azzerano come il resto del gioco.
 */
function gs_reset_tipi_da_tenere() {
	return array(
		// Il catalogo: l'avete scritto voi, non le sfogline.
		'gs_lezione', 'gs_corso', 'gs_percorso_lezioni', 'gs_piatto',
		'gs_faq', 'gs_novita', 'gs_lettura', 'gs_premio', 'gs_locandina',
		'gs_direttiva', 'gs_artigiano', 'gs_scuola', 'gs_cassaforte',
		'gs_sfida',             // la definizione della sfida — vedi nota sopra
		'gs_sondaggio',         // il sondaggio resta, i voti dentro si azzerano (vedi gs_reset_casi_particolari())
		// Le decisioni 4, 5 e 6.
		'gs_ricetta', 'gs_testimonianza', 'gs_conversazione', 'gs_domanda',

		// Il calendario dei corsi e le prenotazioni (calendario.php): dentro
		// ci sono acconti e saldi già versati e i riferimenti dei bonifici.
		// Stessa ragione dei token: è denaro, non punteggio. Le date le
		// scrive il titolare, come le lezioni.
		'gs_corso_cal', 'gs_prenotazione',

		// Scritti dal titolare in wp-admin, non dalle sfogline: catalogo.
		'gs_barometro',    // le Guide Stagionali (menu proprio in amministrazione)
		'gs_ingrediente',  // gli Ingredienti Segreti, compresi quelli programmati
		                   // nel futuro (post_status 'future'), che sparirebbero
		                   // prima ancora di uscire

		// Decisioni di Ennio, 27/08/2026 (ISTRUZIONE-LA-PROVA-E-LE-QUATTRO-DECISIONI.md):
		// spostati QUI da gs_reset_tipi_da_cancellare_voluti(), dove stavano
		// prima — chi tocca uno dei due elenchi controlli anche l'altro,
		// altrimenti un tipo resta in entrambi e il conto (Parte 4) smette
		// di tornare in silenzio.
		'gs_diario', 'gs_consiglio',   // il Diario dell'Impasto e i Consigli: si tengono interi
		'gs_errore_didattico',         // gli errori didattici, promossi e non: si tengono interi
	);
}

/** I tipi gs_ effettivamente registrati che NON sono nell'elenco da tenere: quelli che il Reset cancella. */
function gs_reset_tipi_da_cancellare() {
	$tutti = array_values( array_filter( get_post_types(), function ( $t ) { return 0 === strpos( $t, 'gs_' ); } ) );
	return array_values( array_diff( $tutti, gs_reset_tipi_da_tenere() ) );
}

/** Conteggio delle righe di meta utente gs_* che il Reset cancellerebbe, raggruppate per chiave. */
function gs_reset_meta_conteggio() {
	global $wpdb;
	$tenere = gs_reset_meta_da_tenere();
	$placeholders = implode( ',', array_fill( 0, count( $tenere ), '%s' ) );
	$sql = $wpdb->prepare(
		"SELECT meta_key, COUNT(*) as n FROM {$wpdb->usermeta} WHERE meta_key LIKE %s AND meta_key NOT IN ($placeholders) GROUP BY meta_key ORDER BY meta_key",
		array_merge( array( $wpdb->esc_like( 'gs_' ) . '%' ), $tenere )
	);
	return $wpdb->get_results( $sql );
}

/** Quanti contenuti per tipo il Reset cancellerebbe (tutti gli stati: pubblicati, bozze, cestino). Solo i tipi che hanno davvero qualcosa. */
function gs_reset_contenuti_conteggio() {
	$conteggio = array();
	foreach ( gs_reset_tipi_da_cancellare() as $tipo ) {
		$stati = (array) wp_count_posts( $tipo );
		$n     = array_sum( array_map( 'intval', $stati ) );
		if ( $n > 0 ) { $conteggio[ $tipo ] = $n; }
	}
	return $conteggio;
}

/** Quanti contenuti per tipo restano (il catalogo). Solo i tipi che hanno davvero qualcosa. */
function gs_reset_contenuti_tenuti_conteggio() {
	$conteggio = array();
	foreach ( gs_reset_tipi_da_tenere() as $tipo ) {
		if ( ! post_type_exists( $tipo ) ) { continue; }
		$stati = (array) wp_count_posts( $tipo );
		$n     = array_sum( array_map( 'intval', $stati ) );
		if ( $n > 0 ) { $conteggio[ $tipo ] = $n; }
	}
	return $conteggio;
}

/** Le sfide ancora aperte (mai chiuse) che hanno già sfoglie inviate — da guardare a mano, il Reset non decide da solo. */
function gs_reset_sfide_aperte_con_invii() {
	if ( ! post_type_exists( 'gs_sfida' ) ) { return array(); }
	$sfide = get_posts( array( 'post_type' => 'gs_sfida', 'post_status' => 'any', 'posts_per_page' => -1 ) );
	$trovate = array();
	foreach ( $sfide as $s ) {
		if ( get_post_meta( $s->ID, 'gs_chiusa', true ) ) { continue; } // già chiusa, non riguarda il reset
		$invii = get_posts( array(
			'post_type'      => 'gs_sfoglia',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'meta_key'       => 'gs_sfida_id',
			'meta_value'     => $s->ID,
			'fields'         => 'ids',
		) );
		if ( $invii ) {
			$trovate[] = array( 'id' => $s->ID, 'titolo' => get_the_title( $s->ID ) );
		}
	}
	return $trovate;
}

/** Riepilogo per sfoglina: cosa resta dopo il reset (abbonamento, scadenza, token, vetrina) — la verifica vera. */
function gs_reset_riepilogo_sfogline() {
	$righe = array();
	foreach ( ( function_exists( 'gs_sez_sfogline' ) ? gs_sez_sfogline() : array() ) as $u ) {
		$scaduto_a_mano = 'scaduto' === get_user_meta( $u->ID, 'gs_abbonamento', true );
		$scadenza       = get_user_meta( $u->ID, 'gs_abbonamento_scadenza', true );
		$righe[] = array(
			'nome'        => $u->display_name,
			'abbonamento' => $scaduto_a_mano ? 'scaduto (a mano)' : 'attivo',
			'scadenza'    => $scadenza ? $scadenza : 'nessuna (accesso libero)',
			'token'       => (int) get_user_meta( $u->ID, 'gs_token_credito', true ),
			'vetrina'     => (bool) get_user_meta( $u->ID, 'gs_vetrina_token_attiva', true ) ? 'sì' : 'no',
		);
	}
	return $righe;
}

/**
 * Per una sfoglina nel Cestino, il valore di una chiave sta DENTRO la
 * scatola dell'archivio, non fra i suoi meta: guardare solo i meta la
 * farebbe sembrare senza abbonamento e senza token, che è l'opposto della
 * verità.
 */
function gs_reset_meta_o_archivio( $uid, $chiave ) {
	$scatola = get_user_meta( $uid, GS_ARCHIVIO_GAMING_META, true );
	if ( is_array( $scatola ) && array_key_exists( $chiave, $scatola ) ) {
		return $scatola[ $chiave ];
	}
	return get_user_meta( $uid, $chiave, true );
}

/** Le sfogline nel Cestino: non sono nell'elenco principale, ma abbonamento e token sono i loro. */
function gs_reset_riepilogo_cestino() {
	$righe = array();
	foreach ( get_users( array( 'meta_key' => GS_ARCHIVIO_GAMING_META, 'orderby' => 'display_name' ) ) as $u ) {
		$scadenza = gs_reset_meta_o_archivio( $u->ID, 'gs_abbonamento_scadenza' );
		$righe[] = array(
			'nome'        => $u->display_name,
			'stato'       => (string) get_user_meta( $u->ID, 'gs_status', true ),
			'abbonamento' => 'scaduto' === gs_reset_meta_o_archivio( $u->ID, 'gs_abbonamento' ) ? 'scaduto (a mano)' : 'attivo',
			'scadenza'    => $scadenza ? $scadenza : 'nessuna (accesso libero)',
			'token'       => (int) gs_reset_meta_o_archivio( $u->ID, 'gs_token_credito' ),
			'vetrina'     => gs_reset_meta_o_archivio( $u->ID, 'gs_vetrina_token_attiva' ) ? 'sì' : 'no',
		);
	}
	return $righe;
}

/**
 * Quanti piatti in via d'estinzione tornerebbero liberi. Il Reset tiene i
 * piatti (sono catalogo) ma svuota il custode: chi legge l'anteprima deve
 * vederlo prima, non scoprirlo dopo — stesso motivo per cui l'anteprima
 * mostra le sfogline nel Cestino.
 */
function gs_reset_conteggio_piatti_da_liberare() {
	if ( ! post_type_exists( 'gs_piatto' ) ) { return 0; }
	$n = 0;
	foreach ( get_posts( array( 'post_type' => 'gs_piatto', 'post_status' => array( 'any', 'trash' ), 'posts_per_page' => -1, 'fields' => 'ids' ) ) as $pid ) {
		if ( get_post_meta( $pid, 'gs_custode_tipo', true ) ) { $n++; }
	}
	return $n;
}

/**
 * I tipi gs_ che il Reset cancella APPOSTA. Non serve al Reset — che decide
 * per differenza, ed è giusto così: un tipo nuovo entra da solo. Serve a chi
 * legge l'anteprima: ogni tipo che non è né qui né in
 * gs_reset_tipi_da_tenere() è un tipo che nessuno ha ancora classificato, e
 * l'anteprima lo segnala invece di lasciarlo passare in silenzio.
 *
 * Chi aggiunge un tipo nuovo al plugin non deve ricordarsi del Reset: deve
 * trovarsi una riga rossa nell'anteprima che glielo ricorda.
 */
function gs_reset_tipi_da_cancellare_voluti() {
	return array(
		'gs_sfoglia', 'gs_misura', 'gs_giuria',
		'gs_messaggio', 'gs_msg_interno', 'gs_aiuto', 'gs_augurio',
		'gs_abbinamento', 'gs_tavolo', 'gs_voce',
		// gs_diario, gs_consiglio e gs_errore_didattico erano qui: spostati in
		// gs_reset_tipi_da_tenere() il 27/08/2026 (decisione di Ennio).
	);
}

/** I tipi che il Reset cancellerebbe senza che nessuno l'abbia mai scritto da nessuna parte. */
function gs_reset_tipi_non_classificati() {
	return array_values( array_diff( gs_reset_tipi_da_cancellare(), gs_reset_tipi_da_cancellare_voluti() ) );
}

/** L'anteprima completa: non cancella niente, solo legge e conta. */
function gs_reset_anteprima() {
	return array(
		'meta'             => gs_reset_meta_conteggio(),
		'contenuti'        => gs_reset_contenuti_conteggio(),
		'tenuti'           => gs_reset_contenuti_tenuti_conteggio(),
		'sfide_aperte'     => gs_reset_sfide_aperte_con_invii(),
		'sfogline'         => gs_reset_riepilogo_sfogline(),
		'cestino'          => gs_reset_riepilogo_cestino(),
		'non_classificati' => gs_reset_tipi_non_classificati(),
		'piatti_da_liberare' => gs_reset_conteggio_piatti_da_liberare(),
	);
}

/**
 * Il Reset vero. Marcatore PRIMA e DOPO (se muore a metà, si sa dov'era —
 * la regola di luglio applicata alla cosa più grossa che il plugin farà
 * mai), cache svuotata alla fine, traccia lasciata in gs_reset_log.
 *
 * Ritorna un riepilogo di quello che è stato cancellato davvero.
 */
function gs_reset_esegui( $chi ) {
	global $wpdb;

	update_option( 'gs_reset_stato', array(
		'fase' => 'iniziato',
		'chi'  => (int) $chi,
		'ora'  => current_time( 'mysql' ),
	) );

	// 1) Meta utente gs_* non nell'elenco da tenere.
	$tenere_meta  = gs_reset_meta_da_tenere();
	$placeholders = implode( ',', array_fill( 0, count( $tenere_meta ), '%s' ) );
	$righe_meta_cancellate = (int) $wpdb->query( $wpdb->prepare(
		"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s AND meta_key NOT IN ($placeholders)",
		array_merge( array( $wpdb->esc_like( 'gs_' ) . '%' ), $tenere_meta )
	) );

	// 1b) La scatola di chi è nel Cestino. La cancellazione qui sopra non la
	// tocca (è nell'elenco da tenere, e deve esserci: dentro ci sono
	// abbonamento, scadenza e token di quella persona). Ma il suo contenuto
	// va trattato con la stessa regola di tutti gli altri meta: si tiene
	// quello che è nell'elenco, si toglie il resto — altrimenti una sfoglina
	// ripristinata dal Cestino dopo il Reset si riprenderebbe i punti di
	// prima, unica in tutto il sito.
	$scatole_ripulite = 0;
	foreach ( get_users( array( 'meta_key' => GS_ARCHIVIO_GAMING_META, 'fields' => 'ID' ) ) as $uid_arch ) {
		$scatola = get_user_meta( $uid_arch, GS_ARCHIVIO_GAMING_META, true );
		if ( ! is_array( $scatola ) || ! $scatola ) { continue; }
		$ripulita = array_intersect_key( $scatola, array_flip( $tenere_meta ) );
		if ( count( $ripulita ) === count( $scatola ) ) { continue; }
		if ( $ripulita ) {
			update_user_meta( $uid_arch, GS_ARCHIVIO_GAMING_META, $ripulita );
		} else {
			// Scatola ripulita fino a restare vuota (es. una registrazione
			// rifiutata subito, senza abbonamento né token): si toglie la
			// chiave, non si lascia in giro vuota. Altrimenti
			// gs_ripristina_dati_gaming_utente() esce subito senza
			// cancellarla — quella persona resterebbe per sempre nella
			// tabella del Cestino nell'anteprima — e un futuro nuovo
			// passaggio nel Cestino non verrebbe più archiviato, perché il
			// controllo confronta con '' e un array vuoto non lo è.
			delete_user_meta( $uid_arch, GS_ARCHIVIO_GAMING_META );
		}
		$scatole_ripulite++;
	}

	// 2) Contenuti gs_* non nell'elenco da tenere — cancellazione vera:
	// questo è per design l'unico posto del plugin dove non si passa dal
	// cestino, perché il Reset stesso è pensato come irreversibile (da qui
	// l'obbligo del backup prima e della parola "RESET" digitata). Non
	// tocca la Libreria Media: wp_delete_post() non cancella gli allegati
	// dei contenuti che cancella, restano orfani ma recuperabili.
	// 'any' in WP_Query ESCLUDE il cestino (è un limite noto ma non ovvio di
	// WordPress): senza aggiungere 'trash' esplicitamente, tutto quello che
	// negli anni è finito nel cestino con wp_trash_post() — la via normale
	// con cui questo plugin elimina le cose (CLAUDE.md, regola 5) —
	// sarebbe sopravvissuto al Reset, lasciando un pulito solo a metà.
	// Trovato provando il Reset con dati reali su guru2 (27/08/2026): 1071
	// gs_messaggio esistevano davvero, solo 20 sarebbero stati cancellati
	// senza questa correzione.
	$stati_da_cancellare = array( 'any', 'trash' );
	$contenuti_cancellati = array();
	foreach ( gs_reset_tipi_da_cancellare() as $tipo ) {
		$ids = get_posts( array( 'post_type' => $tipo, 'post_status' => $stati_da_cancellare, 'posts_per_page' => -1, 'fields' => 'ids' ) );
		foreach ( $ids as $id ) {
			wp_delete_post( $id, true );
		}
		if ( $ids ) { $contenuti_cancellati[ $tipo ] = count( $ids ); }
	}

	// 3) I tre casi particolari.
	// 3a — i voti dentro i sondaggi (il sondaggio resta, i voti si azzerano). Stessa cautela sul cestino qui sopra.
	$sondaggi_svuotati = 0;
	if ( post_type_exists( 'gs_sondaggio' ) ) {
		foreach ( get_posts( array( 'post_type' => 'gs_sondaggio', 'post_status' => $stati_da_cancellare, 'posts_per_page' => -1, 'fields' => 'ids' ) ) as $sid ) {
			update_post_meta( $sid, 'gs_sond_voti', array() );
			$sondaggi_svuotati++;
		}
		// gs_sondaggio_proposta_pagata_* è meta UTENTE, già cancellata al
		// passo 1 (non è nell'elenco da tenere, quindi rientra nel giro
		// generale — nessuna azione in più da fare qui).
	}
	// 3b — le sfide aperte con voti di prova: SEGNALATE in anteprima, non
	// decise qui. Il Reset non le tocca da solo.
	// 3c — le tre opzioni segnaposto.
	delete_option( 'gs_buono_sfoglia_mese_chiuso' );
	$anno_corrente = (int) current_time( 'Y' );
	for ( $anno = $anno_corrente - 1; $anno <= $anno_corrente + 1; $anno++ ) {
		delete_option( 'gs_year_prize_assigned_' . $anno );
	}
	// gs_mappa_territori_vincitrice NON è un'opzione a sé (a differenza di
	// come la descrive il documento): vive dentro GS_OPTION, l'array delle
	// impostazioni. Va tolta da lì, non con delete_option() (che su questa
	// chiave non troverebbe niente da cancellare — trovato 27/08/2026).
	$impostazioni = gs_settings( true );
	unset( $impostazioni['mappa_territori_vincitrice'], $impostazioni['mappa_territori_vincitrice_data'] );
	update_option( GS_OPTION, $impostazioni );
	if ( function_exists( 'gs_settings_flush_cache' ) ) { gs_settings_flush_cache(); }
	// 3d — i piatti restano (sono catalogo), ma il custode è stato di gioco:
	// senza svuotarlo i piatti ripartono già adottati da sfogline che non
	// hanno più niente, e nessuna nuova sfoglina può più adottarli — lo
	// stesso motivo per cui si svuotano i voti dentro i sondaggi (decisione
	// di Ennio, 27/08/2026). Nessuna eccezione per nessuno: la 3.298.0 ne
	// aveva una per i piatti di Rina Poletti, nata da una frase interpretata
	// male — Ennio parlava dei Consigli scritti da lei, che si tengono
	// tutti per conto loro (gs_consiglio è nell'elenco dei tipi da tenere).
	$piatti_liberati = 0;
	if ( post_type_exists( 'gs_piatto' ) ) {
		foreach ( get_posts( array( 'post_type' => 'gs_piatto', 'post_status' => $stati_da_cancellare, 'posts_per_page' => -1, 'fields' => 'ids' ) ) as $pid ) {
			if ( ! get_post_meta( $pid, 'gs_custode_tipo', true ) ) { continue; }
			delete_post_meta( $pid, 'gs_custode_tipo' );
			delete_post_meta( $pid, 'gs_custode_id' );
			delete_post_meta( $pid, 'gs_custode_team' );
			$piatti_liberati++;
		}
	}

	// 4) Traccia e pulizia finale.
	$log = get_option( 'gs_reset_log', array() );
	if ( ! is_array( $log ) ) { $log = array(); }
	$voce = array(
		'chi'                   => (int) $chi,
		'ora'                   => current_time( 'mysql' ),
		'righe_meta_cancellate' => $righe_meta_cancellate,
		'contenuti_cancellati'  => $contenuti_cancellati,
		'sondaggi_svuotati'     => $sondaggi_svuotati,
		'scatole_ripulite'      => $scatole_ripulite,
		'piatti_liberati'       => $piatti_liberati,
	);
	array_unshift( $log, $voce );
	update_option( 'gs_reset_log', array_slice( $log, 0, 20 ) );

	update_option( 'gs_reset_stato', array(
		'fase' => 'finito',
		'chi'  => (int) $chi,
		'ora'  => current_time( 'mysql' ),
	) );

	wp_cache_flush();

	return $voce;
}

// -----------------------------------------------------------------------------
// AJAX — Reset
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_reset_anteprima', 'gs_ajax_reset_anteprima' );
function gs_ajax_reset_anteprima() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	// Non gs_can_manage(): quello comprende i collaboratori (gs_manage_gaming),
	// e tutto questo pannello è del titolare soltanto — il Reset è l'unica
	// operazione del plugin che non si annulla, e la Parte 2 riscrive gli
	// indirizzi pubblici di tutte.
	if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	wp_send_json_success( gs_reset_anteprima() );
}

add_action( 'wp_ajax_gs_reset_esegui', 'gs_ajax_reset_esegui' );
function gs_ajax_reset_esegui() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	// Non gs_can_manage(): quello comprende i collaboratori (gs_manage_gaming),
	// e tutto questo pannello è del titolare soltanto — il Reset è l'unica
	// operazione del plugin che non si annulla, e la Parte 2 riscrive gli
	// indirizzi pubblici di tutte.
	if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$conferma = isset( $_POST['conferma'] ) ? sanitize_text_field( wp_unslash( $_POST['conferma'] ) ) : '';
	if ( 'RESET' !== $conferma ) {
		wp_send_json_error( array( 'message' => 'Scrivi esattamente RESET, in maiuscolo, per confermare.' ) );
	}
	$voce = gs_reset_esegui( get_current_user_id() );
	$msg  = 'Reset completato: ' . $voce['righe_meta_cancellate'] . ' righe di dati cancellate, ' . array_sum( $voce['contenuti_cancellati'] ) . ' contenuti cancellati.';
	if ( $voce['scatole_ripulite'] ) {
		$msg .= ' ' . $voce['scatole_ripulite'] . ' scatol' . ( 1 === $voce['scatole_ripulite'] ? 'a' : 'e' ) . ' di chi è nel Cestino ripulit' . ( 1 === $voce['scatole_ripulite'] ? 'a' : 'e' ) . '.';
	}
	if ( $voce['piatti_liberati'] ) {
		$msg .= ' ' . $voce['piatti_liberati'] . ' piatt' . ( 1 === $voce['piatti_liberati'] ? 'o' : 'i' ) . ' in via d\'estinzione liberat' . ( 1 === $voce['piatti_liberati'] ? 'o' : 'i' ) . '.';
	}
	wp_send_json_success( array(
		'message' => $msg,
		'dettaglio' => $voce,
	) );
}

// -----------------------------------------------------------------------------
// PARTE 2 — Lo username fuori dalla rete pubblica
// -----------------------------------------------------------------------------

/**
 * Il primo slug libero a partire da $base, escludendo $escludi_uid (per non
 * "collidere" con se stesso quando si ricalcola) e ogni slug già presente in
 * $riservati — quelli proposti per ALTRE sfogline nello stesso giro di
 * anteprima, che a differenza di quelli già scritti nel database non sono
 * ancora visibili a get_user_by('slug', …). Senza $riservati, due omonime
 * mai ancora migrate proporrebbero indipendentemente lo STESSO slug, perché
 * nessuna delle due vede la proposta dell'altra (trovato 27/08/2026,
 * provando con dati reali due "Anna Verdi").
 */
function gs_nicename_slug_libero( $base, $escludi_uid = 0, $riservati = array() ) {
	$slug = $base ? $base : 'sfoglina';
	$n = 2;
	while ( true ) {
		$trovato = get_user_by( 'slug', $slug );
		$occupato_nel_db = $trovato && (int) $trovato->ID !== (int) $escludi_uid;
		$occupato_in_anteprima = in_array( $slug, $riservati, true );
		if ( ! $occupato_nel_db && ! $occupato_in_anteprima ) { break; }
		$slug = $base . '-' . $n++;
	}
	return $slug;
}

/** Per ogni sfoglina: nicename attuale, nicename proposto (dal nome visibile), e se cambierebbe. */
function gs_nicename_anteprima() {
	$righe     = array();
	$riservati = array(); // gli slug già assegnati ad altre righe in QUESTO giro
	foreach ( ( function_exists( 'gs_sez_sfogline' ) ? gs_sez_sfogline() : array() ) as $u ) {
		$base     = sanitize_title( $u->display_name );
		$proposto = gs_nicename_slug_libero( $base, $u->ID, $riservati );
		$riservati[] = $proposto;
		$righe[] = array(
			'id'       => $u->ID,
			'nome'     => $u->display_name,
			'attuale'  => $u->user_nicename,
			'proposto' => $proposto,
			'cambia'   => ( $u->user_nicename !== $proposto ),
		);
	}
	return $righe;
}

/** Applica solo alle righe che cambiano davvero — idempotente, si può rilanciare senza danno. */
function gs_nicename_applica() {
	$fatte = array();
	foreach ( gs_nicename_anteprima() as $r ) {
		if ( ! $r['cambia'] ) { continue; }
		wp_update_user( array( 'ID' => $r['id'], 'user_nicename' => $r['proposto'] ) );
		$fatte[] = $r;
	}
	// gs_sez_sfogline() ha una cache per-richiesta (gs_cache_generation()):
	// senza svuotarla, un'anteprima richiamata subito dopo — nella stessa
	// richiesta — vedrebbe ancora l'elenco di prima delle modifiche
	// (trovato 27/08/2026, provando l'idempotenza con dati reali).
	if ( function_exists( 'gs_cache_bump' ) ) { gs_cache_bump(); }
	return $fatte;
}

// -----------------------------------------------------------------------------
// AJAX — nicename
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_nicename_anteprima', 'gs_ajax_nicename_anteprima' );
function gs_ajax_nicename_anteprima() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	// Non gs_can_manage(): quello comprende i collaboratori (gs_manage_gaming),
	// e tutto questo pannello è del titolare soltanto — il Reset è l'unica
	// operazione del plugin che non si annulla, e la Parte 2 riscrive gli
	// indirizzi pubblici di tutte.
	if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	wp_send_json_success( array( 'righe' => gs_nicename_anteprima() ) );
}

add_action( 'wp_ajax_gs_nicename_applica', 'gs_ajax_nicename_applica' );
function gs_ajax_nicename_applica() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	// Non gs_can_manage(): quello comprende i collaboratori (gs_manage_gaming),
	// e tutto questo pannello è del titolare soltanto — il Reset è l'unica
	// operazione del plugin che non si annulla, e la Parte 2 riscrive gli
	// indirizzi pubblici di tutte.
	if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$fatte = gs_nicename_applica();
	wp_send_json_success( array(
		'message' => $fatte ? count( $fatte ) . ' indirizzi aggiornati.' : 'Nessun indirizzo da aggiornare: erano già tutti a posto.',
		'righe'   => $fatte,
	) );
}

// -----------------------------------------------------------------------------
// PANNELLO — titolare soltanto (manage_options), non i collaboratori: è
// l'unica operazione del plugin che non si annulla.
// -----------------------------------------------------------------------------
function gs_pannello_reset() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }

	echo gs_box_open( '⚠️ Il Reset del gioco e lo username fuori dalla rete', 'gs-box-reset' );
	echo gs_sezione_aiuto( 'Due operazioni distinte, in questo ordine: prima "Username fuori dalla rete" (l\'indirizzo pubblico della Vetrina), poi il Reset — perché il Reset è l\'unica operazione di questo pannello che non si annulla. Fai sempre un backup del database prima. Il pulsante "Anteprima" non cancella niente: guardalo con calma prima di procedere.' );

	echo '<h4>1 — Username fuori dalla rete</h4>';
	echo '<p class="gs-hint">Da fare prima delle iscrizioni vere: dopo, gli indirizzi sono già in giro su WhatsApp e Google, e cambiarli li romperebbe.</p>';
	echo '<p><button type="button" class="gs-btn gs-btn-sm gs-nicename-anteprima-btn">Anteprima</button> '
		. '<button type="button" class="gs-btn gs-btn-sm gs-btn-verde gs-nicename-applica-btn" disabled>Applica</button> '
		. '<span class="gs-nicename-msg gs-richiesta-esito"></span></p>';
	echo '<div class="gs-nicename-risultato" style="display:none"></div>';

	echo '<hr style="margin:24px 0">';

	echo '<h4>2 — Il Reset</h4>';
	echo '<p class="gs-hint"><strong>Fai un backup del database prima di premere qualsiasi cosa qui sotto.</strong> "Anteprima" non cancella niente. Il pulsante di cancellazione si sblocca solo dopo aver visto l\'anteprima, e chiede di scrivere RESET a mano.</p>';
	echo '<p><button type="button" class="gs-btn gs-btn-sm gs-reset-anteprima-btn">Anteprima: mostra cosa verrebbe cancellato</button> <span class="gs-reset-anteprima-msg gs-richiesta-esito"></span></p>';
	echo '<div class="gs-reset-risultato" style="display:none"></div>';
	echo '<p style="margin-top:16px">
		<label>Scrivi <code>RESET</code> per confermare<br><input type="text" class="gs-reset-conferma-input" autocomplete="off" style="width:200px"></label>
		<button type="button" class="gs-btn gs-btn-sm gs-btn-rosso gs-reset-esegui-btn" disabled>Cancella tutto — non si annulla</button>
	</p>
	<p><span class="gs-reset-esegui-msg gs-richiesta-esito"></span></p>';

	$log = get_option( 'gs_reset_log', array() );
	if ( $log ) {
		echo '<h4 style="margin-top:20px">Cronologia dei reset già fatti</h4><ul>';
		foreach ( $log as $voce ) {
			$u = get_userdata( (int) $voce['chi'] );
			echo '<li>' . esc_html( $voce['ora'] ) . ' — da ' . esc_html( $u ? $u->display_name : '#' . $voce['chi'] )
				. ' — ' . (int) $voce['righe_meta_cancellate'] . ' righe di dati, '
				. (int) array_sum( $voce['contenuti_cancellati'] ) . ' contenuti'
				// isset(): i reset fatti prima di 3.297.0/3.298.0 non hanno queste voci nel log.
				. ( isset( $voce['scatole_ripulite'] ) && $voce['scatole_ripulite'] ? ', ' . (int) $voce['scatole_ripulite'] . ' scatole del Cestino ripulite' : '' )
				. ( isset( $voce['piatti_liberati'] ) && $voce['piatti_liberati'] ? ', ' . (int) $voce['piatti_liberati'] . ' piatti liberati' : '' )
				. '</li>';
		}
		echo '</ul>';
	}

	echo gs_box_close();
}
