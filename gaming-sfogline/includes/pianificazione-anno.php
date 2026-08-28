<?php
/**
 * pianificazione-anno.php — Pianificazione dell'Anno.
 *
 * Pannello riepilogativo (solo titolare/collaboratori) che mette in un'unica
 * linea del tempo, mese per mese, TUTTO ciò che ha una data nel plugin:
 *  - Calendario Corsi (date reali dei corsi di Rina/Bruno)
 *  - Le gare a tempo (Sfida della Settimana, con gs_data_inizio/gs_data_fine)
 *  - I Percorsi Guidati Stagionali (già con una finestra di date reale)
 *  - I Percorsi Guidati normali (propedeutici, "automatizzati": si sbloccano
 *    uno dopo l'altro completando il precedente, non hanno mai avuto una
 *    data reale — qui si può dare loro una data PIANIFICATA, solo per
 *    organizzare l'anno: non tocca in alcun modo lo sblocco propedeutico,
 *    che resta governato dall'ordine e dal completamento come sempre)
 *  - I corsi dell'Area Professionale (idem: nessuna data reale nel sistema,
 *    qui solo una data pianificata per l'organizzazione)
 *
 * Trascinando un evento a sinistra/destra si cambia la sua data (per i primi
 * tre tipi è una data REALE, che le sfogline vedono sul sito; per gli ultimi
 * due è una data di PIANIFICAZIONE, visibile solo qui). Trascinando la riga
 * di un Percorso Guidato normale sopra/sotto un altro, si scambia il loro
 * ordine reale nella sequenza propedeutica (lo stesso che fanno i pulsanti
 * ▲▼ nel pannello Percorsi Guidati).
 *
 * Nessuna tabella nuova: solo meta sui post esistenti. Per i Percorsi
 * Guidati normali e per l'Area Professionale, uniche AGGIUNTE:
 *  - gs_percorso_piano_inizio / gs_percorso_piano_fine (su gs_percorso_lezioni)
 *  - gs_areapro_piano_inizio / gs_areapro_piano_fine (su gs_corso)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Nome e foto di chi ha creato un evento (l'autore del post, chi lo ha
 * inserito dal pannello — es. Rina Poletti, Bruno Cingolani, o chi gestisce
 * il portale). Foto: prima quella caricata sulla Biografia, altrimenti
 * l'avatar generico dell'account. Usata sul quadratino colorato della
 * linea del tempo, per riconoscere a colpo d'occhio chi ha organizzato
 * quel corso.
 */
function gs_piano_autore_info( $uid ) {
	$uid = (int) $uid;
	if ( ! $uid ) { return array( 'uid' => 0, 'nome' => '', 'foto' => '' ); }
	$u = get_userdata( $uid );
	$foto = function_exists( 'gs_bio_foto_url' ) ? gs_bio_foto_url( $uid ) : '';
	if ( ! $foto ) { $foto = get_avatar_url( $uid, array( 'size' => 60 ) ); }
	return array( 'uid' => $uid, 'nome' => $u ? $u->display_name : '', 'foto' => (string) $foto );
}

/**
 * Ripara l'autore di un evento SOLO se manca o non corrisponde a nessun
 * utente vero (post_author a 0, oppure un ID di un account cancellato nel
 * frattempo): capitato a qualche corso creato prima che il quadratino
 * mostrasse la foto di chi l'ha creato — quei corsi restano senza nessuno
 * finché non vengono "riparati". Non sovrascrive mai un autore valido
 * già presente, per non rubare la paternità a chi ha creato davvero
 * l'evento quando qualcun altro lo modifica in seguito (es. il titolare
 * che sposta una data creata da Rina).
 */
function gs_piano_ripara_autore( $id ) {
	$id = (int) $id;
	if ( ! $id ) { return; }
	$post = get_post( $id );
	if ( ! $post ) { return; }
	$autore_valido = (int) $post->post_author && get_userdata( (int) $post->post_author );
	if ( ! $autore_valido ) {
		$uid = get_current_user_id();
		if ( $uid ) {
			wp_update_post( array( 'ID' => $id, 'post_author' => $uid ) );
		}
	}
}

/**
 * Tutti gli eventi pianificabili, con data (reale o pianificata), colore e
 * tipo. Gli eventi senza nessuna data (mai trascinati sulla linea del tempo)
 * compaiono comunque, in una corsia "Da programmare" a parte.
 */
function gs_piano_eventi( $anno = null ) {
	$anno = $anno ? (int) $anno : (int) date_i18n( 'Y', current_time( 'timestamp' ) );
	$out  = array();

	// --- 1. Calendario Corsi (data singola: gs_data) ---------------------
	if ( function_exists( 'gs_cal_corsi' ) ) {
		foreach ( gs_cal_corsi() as $c ) {
			$d = get_post_meta( $c->ID, 'gs_data', true );
			$out[] = array(
				'chiave'      => 'corso_' . $c->ID,
				'tipo'        => 'corso',
				'id'          => $c->ID,
				'titolo'      => get_the_title( $c ),
				'data_inizio' => $d ? substr( $d, 0, 10 ) : '',
				'data_fine'   => $d ? substr( $d, 0, 10 ) : '',
				'colore'      => '#a8862e',
				'etichetta'   => 'Calendario Corsi',
				'autore'      => gs_piano_autore_info( $c->post_author ),
			);
		}
	}

	// --- 2. Le gare a tempo (gs_sfida, gs_data_inizio/gs_data_fine) ------
	foreach ( gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_sfida',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'suppress_filters' => true,
	) ), 'gs_sfida' ) as $s ) {
		$di = get_post_meta( $s->ID, 'gs_data_inizio', true );
		$df = get_post_meta( $s->ID, 'gs_data_fine', true );
		$out[] = array(
			'chiave'      => 'gara_' . $s->ID,
			'tipo'        => 'gara',
			'id'          => $s->ID,
			'titolo'      => get_the_title( $s ),
			'data_inizio' => $di ? substr( $di, 0, 10 ) : '',
			'data_fine'   => $df ? substr( $df, 0, 10 ) : '',
			'colore'      => '#9c3a2a',
			'etichetta'   => 'Sfida della Settimana',
			'autore'      => gs_piano_autore_info( $s->post_author ),
		);
	}

	// --- 3. Percorsi Guidati: stagionali (date reali) + normali (piano) --
	if ( function_exists( 'gs_percorsi_tutti' ) ) {
		foreach ( gs_percorsi_tutti() as $p ) {
			$c = gs_percorso_get( $p->ID );
			if ( ! $c['lezioni'] ) { continue; } // percorso vuoto: niente da pianificare
			$stagionale = gs_percorso_e_stagionale( $p->ID );
			if ( $stagionale ) {
				$di = $c['data_inizio'];
				$df = $c['data_fine'];
			} else {
				$di = get_post_meta( $p->ID, 'gs_percorso_piano_inizio', true );
				$df = get_post_meta( $p->ID, 'gs_percorso_piano_fine', true );
			}
			$out[] = array(
				'chiave'      => 'percorso_' . $p->ID,
				'tipo'        => $stagionale ? 'percorso_stagionale' : 'percorso_normale',
				'id'          => $p->ID,
				'titolo'      => $c['titolo'],
				'data_inizio' => $di ?: '',
				'data_fine'   => $df ?: '',
				'ordine'      => $c['ordine'],
				'colore'      => $stagionale ? '#4a7a5c' : '#6b9c7a',
				'etichetta'   => $stagionale ? 'Percorso Stagionale (data reale)' : 'Percorso Guidato (data pianificata)',
				'autore'      => gs_piano_autore_info( $p->post_author ),
			);
		}
	}

	// --- 4. Area Professionale (solo data pianificata) --------------------
	foreach ( gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_corso',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'suppress_filters' => true,
	) ), 'gs_corso' ) as $c ) {
		$di = get_post_meta( $c->ID, 'gs_areapro_piano_inizio', true );
		$df = get_post_meta( $c->ID, 'gs_areapro_piano_fine', true );
		$out[] = array(
			'chiave'      => 'areapro_' . $c->ID,
			'tipo'        => 'area_pro',
			'id'          => $c->ID,
			'titolo'      => get_the_title( $c ),
			'data_inizio' => $di ?: '',
			'data_fine'   => $df ?: '',
			'colore'      => '#8a6c22',
			'etichetta'   => 'Corsi Online (data pianificata)',
			'autore'      => gs_piano_autore_info( $c->post_author ),
		);
	}

	return $out;
}

/** Eventi con almeno una data che cade (anche parzialmente) nell'anno indicato. */
function gs_piano_eventi_anno( $anno ) {
	$anno = (int) $anno;
	return array_values( array_filter( gs_piano_eventi( $anno ), function ( $e ) use ( $anno ) {
		$anno_inizio = $e['data_inizio'] ? (int) substr( $e['data_inizio'], 0, 4 ) : 0;
		$anno_fine   = $e['data_fine'] ? (int) substr( $e['data_fine'], 0, 4 ) : 0;
		return $anno_inizio === $anno || $anno_fine === $anno || ( ! $anno_inizio && ! $anno_fine );
	} ) );
}

// -----------------------------------------------------------------------------
// [gs_piano_dati] — restituisce gli eventi in JSON per il disegno via JS
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_piano_dati', 'gs_ajax_piano_dati' );
function gs_ajax_piano_dati() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$anno = isset( $_POST['anno'] ) ? (int) $_POST['anno'] : (int) date_i18n( 'Y', current_time( 'timestamp' ) );
	wp_send_json_success( array( 'eventi' => gs_piano_eventi_anno( $anno ) ) );
}

/** Valida una data 'Y-m-d', o '' se non valida. */
function gs_piano_data_valida( $data ) {
	$data = sanitize_text_field( wp_unslash( (string) $data ) );
	return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data ) ? $data : '';
}

// -----------------------------------------------------------------------------
// [gs_piano_sposta] — trascinamento: cambia la data (reale o pianificata)
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_piano_sposta', 'gs_ajax_piano_sposta' );
function gs_ajax_piano_sposta() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$tipo = isset( $_POST['tipo'] ) ? sanitize_key( wp_unslash( $_POST['tipo'] ) ) : '';
	$id   = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$di   = gs_piano_data_valida( $_POST['data_inizio'] ?? '' );
	$df   = gs_piano_data_valida( $_POST['data_fine'] ?? '' );
	if ( ! $di || ! $df ) { wp_send_json_error( array( 'message' => 'Data non valida.' ) ); }
	if ( $df < $di ) { wp_send_json_error( array( 'message' => 'La data di fine non può essere prima di quella di inizio.' ) ); }

	switch ( $tipo ) {
		case 'corso':
			if ( 'gs_corso_cal' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Corso non valido.' ) ); }
			update_post_meta( $id, 'gs_data', $di );
			break;

		case 'gara':
			if ( 'gs_sfida' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Sfida non valida.' ) ); }
			update_post_meta( $id, 'gs_data_inizio', $di . ' 00:00' );
			update_post_meta( $id, 'gs_data_fine', $df . ' 23:59' );
			break;

		case 'percorso_stagionale':
			if ( 'gs_percorso_lezioni' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Percorso non valido.' ) ); }
			update_post_meta( $id, 'gs_percorso_data_inizio', $di );
			update_post_meta( $id, 'gs_percorso_data_fine', $df );
			break;

		case 'percorso_normale':
			if ( 'gs_percorso_lezioni' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Percorso non valido.' ) ); }
			update_post_meta( $id, 'gs_percorso_piano_inizio', $di );
			update_post_meta( $id, 'gs_percorso_piano_fine', $df );
			break;

		case 'area_pro':
			if ( 'gs_corso' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Corso non valido.' ) ); }
			update_post_meta( $id, 'gs_areapro_piano_inizio', $di );
			update_post_meta( $id, 'gs_areapro_piano_fine', $df );
			break;

		default:
			wp_send_json_error( array( 'message' => 'Tipo non riconosciuto.' ) );
	}

	wp_send_json_success( array( 'message' => 'Data aggiornata.' ) );
}

// -----------------------------------------------------------------------------
// [gs_piano_scambia] — trascinamento della riga: scambia l'ordine reale di
// due Percorsi Guidati normali nella sequenza propedeutica.
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_piano_scambia', 'gs_ajax_piano_scambia' );
function gs_ajax_piano_scambia() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$a = isset( $_POST['a'] ) ? (int) $_POST['a'] : 0;
	$b = isset( $_POST['b'] ) ? (int) $_POST['b'] : 0;
	if ( 'gs_percorso_lezioni' !== get_post_type( $a ) || 'gs_percorso_lezioni' !== get_post_type( $b ) ) {
		wp_send_json_error( array( 'message' => 'Percorso non valido.' ) );
	}
	if ( function_exists( 'gs_percorso_e_stagionale' ) && ( gs_percorso_e_stagionale( $a ) || gs_percorso_e_stagionale( $b ) ) ) {
		wp_send_json_error( array( 'message' => 'I Percorsi Stagionali non hanno un ordine: si spostano solo cambiando le date.' ) );
	}

	$ordine_a = (int) get_post_meta( $a, 'gs_percorso_ordine', true );
	$ordine_b = (int) get_post_meta( $b, 'gs_percorso_ordine', true );
	update_post_meta( $a, 'gs_percorso_ordine', $ordine_b );
	update_post_meta( $b, 'gs_percorso_ordine', $ordine_a );

	wp_send_json_success( array( 'message' => 'Ordine scambiato.' ) );
}

// -----------------------------------------------------------------------------
// La Scheda: clic su un blocco (senza trascinarlo) apre i dettagli completi,
// non solo la data.
// -----------------------------------------------------------------------------

/** Dettagli completi di un evento, per la scheda. Null se non trovato/tipo sbagliato. */
function gs_piano_dettaglio( $tipo, $id ) {
	switch ( $tipo ) {
		case 'corso':
			if ( 'gs_corso_cal' !== get_post_type( $id ) || ! function_exists( 'gs_cal_corso_get' ) ) { return null; }
			$c = gs_cal_corso_get( $id );
			return array(
				'tipo' => 'corso', 'id' => $id, 'titolo' => $c['titolo'],
				'data_inizio' => $c['data'], 'data_fine' => $c['data'], 'reale' => true,
				'campi' => array(
					'ora_inizio' => $c['inizio'], 'ora_fine' => $c['fine'],
					'posti' => $c['posti'], 'prezzo' => $c['prezzo'], 'acconto' => $c['acconto'],
					'descrizione' => $c['descr'],
				),
			);

		case 'gara':
			if ( 'gs_sfida' !== get_post_type( $id ) ) { return null; }
			$di = get_post_meta( $id, 'gs_data_inizio', true );
			$df = get_post_meta( $id, 'gs_data_fine', true );
			return array(
				'tipo' => 'gara', 'id' => $id, 'titolo' => get_the_title( $id ),
				'data_inizio' => $di ? substr( $di, 0, 10 ) : '', 'data_fine' => $df ? substr( $df, 0, 10 ) : '', 'reale' => true,
				'campi' => array( 'descrizione' => get_post( $id ) ? get_post( $id )->post_content : '' ),
			);

		case 'percorso_normale':
		case 'percorso_stagionale':
			if ( 'gs_percorso_lezioni' !== get_post_type( $id ) || ! function_exists( 'gs_percorso_get' ) ) { return null; }
			$c    = gs_percorso_get( $id );
			$stag = function_exists( 'gs_percorso_e_stagionale' ) && gs_percorso_e_stagionale( $id );
			$di   = $stag ? $c['data_inizio'] : get_post_meta( $id, 'gs_percorso_piano_inizio', true );
			$df   = $stag ? $c['data_fine'] : get_post_meta( $id, 'gs_percorso_piano_fine', true );
			return array(
				'tipo' => $tipo, 'id' => $id, 'titolo' => $c['titolo'],
				'data_inizio' => $di ?: '', 'data_fine' => $df ?: '', 'reale' => $stag,
				'campi' => array( 'descrizione' => $c['descrizione'], 'livello' => $c['livello'] ),
			);

		case 'area_pro':
			if ( 'gs_corso' !== get_post_type( $id ) ) { return null; }
			$di = get_post_meta( $id, 'gs_areapro_piano_inizio', true );
			$df = get_post_meta( $id, 'gs_areapro_piano_fine', true );
			return array(
				'tipo' => 'area_pro', 'id' => $id, 'titolo' => get_the_title( $id ),
				'data_inizio' => $di ?: '', 'data_fine' => $df ?: '', 'reale' => false,
				'campi' => array(),
			);
	}
	return null;
}

add_action( 'wp_ajax_gs_piano_scheda', 'gs_ajax_piano_scheda' );
function gs_ajax_piano_scheda() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$tipo = isset( $_POST['tipo'] ) ? sanitize_key( wp_unslash( $_POST['tipo'] ) ) : '';
	$id   = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$d    = gs_piano_dettaglio( $tipo, $id );
	if ( ! $d ) { wp_send_json_error( array( 'message' => 'Non trovato.' ) ); }
	wp_send_json_success( $d );
}

add_action( 'wp_ajax_gs_piano_scheda_salva', 'gs_ajax_piano_scheda_salva' );
function gs_ajax_piano_scheda_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$tipo   = isset( $_POST['tipo'] ) ? sanitize_key( wp_unslash( $_POST['tipo'] ) ) : '';
	$id     = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	$di     = gs_piano_data_valida( $_POST['data_inizio'] ?? '' );
	$df     = gs_piano_data_valida( $_POST['data_fine'] ?? '' );

	if ( '' === trim( $titolo ) ) { wp_send_json_error( array( 'message' => 'Scrivi un titolo.' ) ); }
	if ( $di && $df && $df < $di ) { wp_send_json_error( array( 'message' => 'La data di fine non può essere prima di quella di inizio.' ) ); }

	switch ( $tipo ) {
		case 'corso':
			if ( 'gs_corso_cal' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Corso non valido.' ) ); }
			wp_update_post( array( 'ID' => $id, 'post_title' => $titolo ) );
			if ( $di ) { update_post_meta( $id, 'gs_data', $di ); }
			update_post_meta( $id, 'gs_ora_inizio', sanitize_text_field( wp_unslash( $_POST['ora_inizio'] ?? '' ) ) );
			update_post_meta( $id, 'gs_ora_fine', sanitize_text_field( wp_unslash( $_POST['ora_fine'] ?? '' ) ) );
			update_post_meta( $id, 'gs_posti', max( 0, (int) ( $_POST['posti'] ?? 0 ) ) );
			update_post_meta( $id, 'gs_prezzo', max( 0, (float) ( $_POST['prezzo'] ?? 0 ) ) );
			update_post_meta( $id, 'gs_acconto', max( 0, (float) ( $_POST['acconto'] ?? 0 ) ) );
			update_post_meta( $id, 'gs_descrizione', sanitize_textarea_field( wp_unslash( $_POST['descrizione'] ?? '' ) ) );
			break;

		case 'gara':
			if ( 'gs_sfida' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Sfida non valida.' ) ); }
			wp_update_post( array( 'ID' => $id, 'post_title' => $titolo, 'post_content' => sanitize_textarea_field( wp_unslash( $_POST['descrizione'] ?? '' ) ) ) );
			if ( $di ) { update_post_meta( $id, 'gs_data_inizio', $di . ' 00:00' ); }
			if ( $df ) { update_post_meta( $id, 'gs_data_fine', $df . ' 23:59' ); }
			break;

		case 'percorso_normale':
		case 'percorso_stagionale':
			if ( 'gs_percorso_lezioni' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Percorso non valido.' ) ); }
			wp_update_post( array( 'ID' => $id, 'post_title' => $titolo, 'post_content' => sanitize_textarea_field( wp_unslash( $_POST['descrizione'] ?? '' ) ) ) );
			$livello = isset( $_POST['livello'] ) ? sanitize_text_field( wp_unslash( $_POST['livello'] ) ) : '';
			if ( $livello && function_exists( 'gs_percorso_livelli' ) && in_array( $livello, gs_percorso_livelli(), true ) ) {
				update_post_meta( $id, 'gs_percorso_livello', $livello );
			} else {
				delete_post_meta( $id, 'gs_percorso_livello' );
			}
			if ( 'percorso_stagionale' === $tipo ) {
				if ( $di ) { update_post_meta( $id, 'gs_percorso_data_inizio', $di ); }
				if ( $df ) { update_post_meta( $id, 'gs_percorso_data_fine', $df ); }
			} else {
				if ( $di ) { update_post_meta( $id, 'gs_percorso_piano_inizio', $di ); }
				if ( $df ) { update_post_meta( $id, 'gs_percorso_piano_fine', $df ); }
			}
			break;

		case 'area_pro':
			if ( 'gs_corso' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Corso non valido.' ) ); }
			wp_update_post( array( 'ID' => $id, 'post_title' => $titolo ) );
			if ( $di ) { update_post_meta( $id, 'gs_areapro_piano_inizio', $di ); }
			if ( $df ) { update_post_meta( $id, 'gs_areapro_piano_fine', $df ); }
			break;

		default:
			wp_send_json_error( array( 'message' => 'Tipo non riconosciuto.' ) );
	}

	// Ripara l'autore se questo evento non ne aveva uno (vedi gs_piano_ripara_autore):
	// così basta aprire la scheda e salvare per far comparire la foto sul quadratino.
	gs_piano_ripara_autore( $id );

	wp_send_json_success( array( 'message' => 'Scheda salvata.' ) );
}

// -----------------------------------------------------------------------------
// PANNELLO — Pianificazione dell'Anno
// -----------------------------------------------------------------------------
function gs_pannello_pianificazione_anno() {
	if ( ! gs_can_manage() ) { return; }

	$anno_corrente = (int) date_i18n( 'Y', current_time( 'timestamp' ) );

	echo gs_box_open( '🗓️ Pianificazione dell\'Anno', '', 'gs-box-piano' );
	echo '<p class="gs-hint">Un unico colpo d\'occhio su tutto ciò che ha una data: i corsi del Calendario, le gare a tempo, i Percorsi Guidati (quelli Stagionali con data reale, quelli normali/propedeutici con una data pianificata) e i Corsi Online (data pianificata). Il blocco colorato mostra solo la data e la foto di chi l\'ha creato: il colore dice già di che tipo si tratta (vedi la legenda) e il titolo vero è scritto a sinistra, nell\'etichetta della riga. <strong>Clicca</strong> su un blocco (senza trascinarlo) per aprire la sua scheda completa: titolo, date e gli altri campi principali, tutti modificabili da lì. <strong>Trascina</strong> un blocco a sinistra o a destra per spostarlo di mese: per Calendario Corsi, gare e Percorsi Stagionali cambia la data VERA, visibile subito sul sito; per i Percorsi Guidati normali e i Corsi Online è solo una data pianificata per organizzarti, non cambia come si sbloccano davvero. Trascina la maniglia ☰ di un Percorso Guidato normale sopra o sotto un altro per scambiare il loro ordine reale nella sequenza (lo stesso effetto dei pulsanti ▲▼ nel pannello Percorsi Guidati).</p>';

	echo '<div class="gs-piano-legenda">';
	echo '<span class="gs-piano-tag" style="background:#a8862e">Calendario Corsi</span>';
	echo '<span class="gs-piano-tag" style="background:#9c3a2a">Gare a tempo</span>';
	echo '<span class="gs-piano-tag" style="background:#4a7a5c">Percorsi Stagionali</span>';
	echo '<span class="gs-piano-tag" style="background:#6b9c7a">Percorsi Guidati</span>';
	echo '<span class="gs-piano-tag" style="background:#8a6c22">Corsi Online</span>';
	echo '</div>';

	echo '<p><label>Anno: <select class="gs-piano-anno">';
	for ( $y = $anno_corrente - 1; $y <= $anno_corrente + 2; $y++ ) {
		echo '<option value="' . (int) $y . '" ' . selected( $y, $anno_corrente, false ) . '>' . (int) $y . '</option>';
	}
	echo '</select></label> <span class="gs-piano-msg gs-richiesta-esito"></span></p>';

	echo '<div class="gs-piano-timeline" data-anno="' . (int) $anno_corrente . '"><p class="gs-hint">Caricamento…</p></div>';

	echo gs_box_close();
}
