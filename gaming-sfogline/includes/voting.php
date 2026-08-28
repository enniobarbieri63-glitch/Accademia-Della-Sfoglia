<?php
/**
 * voting.php — Sfida attiva, invio sfoglie, voto a stelle, classifica, premi.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Criteri di voto (1-5 stelle ciascuno).
function gs_vote_criteria() {
	return array(
		'colore'        => 'Colore — dorata al punto giusto?',
		'uniformita'    => 'Uniformità — spessore omogeneo?',
		'forma'         => 'Forma — tagliatella perfetta o creativa?',
		'presentazione' => 'Presentazione — il piatto finale',
	);
}

/**
 * Tutti i voti di una sfoglia, vecchi e nuovi insieme.
 *
 * Fino al 14/08/2026 ogni voto veniva aggiunto a un unico array in
 * 'gs_votes' (leggi-tutto, aggiungi, riscrivi-tutto): sotto voti simultanei
 * di più sfogline si perdevano scritture, dimostrato con un test di carico
 * reale. Dal 14/08/2026 ogni voto nuovo è una riga di meta indipendente
 * ('gs_voto', non-unique): non c'è più niente da "riscrivere", quindi
 * niente da perdere. I voti già salvati nel vecchio formato restano dove
 * sono — non vengono mai spostati o riscritti — e questa funzione li legge
 * insieme ai nuovi, così la classifica e le medie restano corrette anche
 * per le sfoglie votate prima della correzione.
 */
function gs_sfoglia_tutti_i_voti( $sfoglia_id ) {
	$sfoglia_id = (int) $sfoglia_id;
	$vecchi = get_post_meta( $sfoglia_id, 'gs_votes', true );
	$vecchi = is_array( $vecchi ) ? $vecchi : array();
	$nuovi  = get_post_meta( $sfoglia_id, 'gs_voto', false );
	$nuovi  = is_array( $nuovi ) ? $nuovi : array();
	return array_merge( $vecchi, $nuovi );
}

/** True se questo utente ha già votato questa sfoglia (vecchio formato o nuovo). */
function gs_utente_ha_votato( $sfoglia_id, $user_id ) {
	$sfoglia_id = (int) $sfoglia_id;
	$user_id    = (int) $user_id;

	$vecchi_voters = get_post_meta( $sfoglia_id, 'gs_voters', true );
	if ( is_array( $vecchi_voters ) && in_array( $user_id, $vecchi_voters, true ) ) {
		return true;
	}
	return metadata_exists( 'post', $sfoglia_id, 'gs_voto_uid_' . $user_id );
}

/**
 * Restituisce la sfida attiva (data inizio passata, scadenza futura, non chiusa).
 */
function gs_get_active_challenge() {
	// Cache per-richiesta: la funzione è chiamata più volte nella stessa pagina
	// (sfida corrente, galleria, pannello) e la sfida attiva non cambia nel
	// frattempo. false = ancora da calcolare; null = calcolata, nessuna attiva.
	static $cache = false;
	if ( false !== $cache ) {
		return $cache;
	}

	$now = current_time( 'timestamp' );

	$sfide = get_posts( array(
		'post_type'      => 'gs_sfida',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_key'       => 'gs_data_fine',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
	) );

	foreach ( $sfide as $sfida ) {
		if ( get_post_meta( $sfida->ID, 'gs_chiusa', true ) ) {
			continue;
		}
		$inizio = strtotime( get_post_meta( $sfida->ID, 'gs_data_inizio', true ) );
		$fine   = strtotime( get_post_meta( $sfida->ID, 'gs_data_fine', true ) );
		if ( $inizio && $fine && $now >= $inizio && $now <= $fine ) {
			$cache = $sfida;
			return $cache;
		}
	}
	$cache = null;
	return $cache;
}

// -----------------------------------------------------------------------------
// Sfide blindate: eleggibilità e ammissioni a discrezione
// -----------------------------------------------------------------------------

/**
 * True se la sfida è blindata.
 */
function gs_sfida_blindata( $sfida_id ) {
	return '1' === get_post_meta( $sfida_id, 'gs_blindata', true );
}

/**
 * Elenco (array di ID) delle sfogline ammesse a discrezione (whitelist).
 */
function gs_sfida_whitelist( $sfida_id ) {
	$list = get_post_meta( $sfida_id, 'gs_whitelist', true );
	return is_array( $list ) ? array_map( 'intval', $list ) : array();
}

/**
 * Elenco (array di ID) delle sfogline escluse a discrezione (blacklist).
 */
function gs_sfida_blacklist( $sfida_id ) {
	$list = get_post_meta( $sfida_id, 'gs_blacklist', true );
	return is_array( $list ) ? array_map( 'intval', $list ) : array();
}

/**
 * Aggiorna l'ammissione di una sfoglina a una sfida blindata.
 *
 * @param string $stato 'ammetti' | 'escludi' | 'reset'
 */
function gs_set_ammissione( $sfida_id, $user_id, $stato ) {
	$white = gs_sfida_whitelist( $sfida_id );
	$black = gs_sfida_blacklist( $sfida_id );
	$user_id = (int) $user_id;

	$white = array_diff( $white, array( $user_id ) );
	$black = array_diff( $black, array( $user_id ) );

	if ( 'ammetti' === $stato ) {
		$white[] = $user_id;
	} elseif ( 'escludi' === $stato ) {
		$black[] = $user_id;
	}

	update_post_meta( $sfida_id, 'gs_whitelist', array_values( array_unique( $white ) ) );
	update_post_meta( $sfida_id, 'gs_blacklist', array_values( array_unique( $black ) ) );
}

/**
 * True se $user_id è tra le prime N posizioni della classifica generale,
 * secondo il limite impostato per questa sfida (gs_escludi_top_n, 0 =
 * nessuna esclusione). Pensata per dare spazio a chi è più indietro:
 * l'esclusione vale per QUALSIASI sfida, blindata o no.
 */
function gs_esclusa_per_classifica( $user_id, $sfida_id ) {
	$top_n = (int) get_post_meta( $sfida_id, 'gs_escludi_top_n', true );
	if ( $top_n <= 0 ) {
		return false;
	}
	$user_id = (int) $user_id;
	foreach ( gs_leaderboard( $top_n ) as $u ) {
		if ( (int) $u->ID === $user_id ) {
			return true;
		}
	}
	return false;
}

/**
 * Può l'utente partecipare a questa sfida?
 *
 * Prima di tutto: se la sfida esclude le prime N posizioni della
 * classifica generale (gs_escludi_top_n) e questa sfoglina è tra quelle,
 * NON partecipa — a meno che non sia comunque ammessa a discrezione
 * (whitelist). Questa regola vale per qualunque sfida, blindata o no.
 *
 * Poi, solo per le sfide blindate:
 *  - se in blacklist → NO (esclusa a discrezione)
 *  - se in whitelist → SÌ (ammessa a discrezione, anche sotto il livello minimo)
 *  - altrimenti → SÌ solo se il livello è ≥ livello minimo richiesto
 * Le sfide non blindate sono aperte a tutte le iscritte approvate.
 */
function gs_can_participate( $user_id, $sfida_id ) {
	$user_id = (int) $user_id;

	if ( gs_esclusa_per_classifica( $user_id, $sfida_id )
		&& ! in_array( $user_id, gs_sfida_whitelist( $sfida_id ), true ) ) {
		return false;
	}

	if ( ! gs_sfida_blindata( $sfida_id ) ) {
		return true;
	}

	if ( in_array( $user_id, gs_sfida_blacklist( $sfida_id ), true ) ) {
		return false;
	}
	if ( in_array( $user_id, gs_sfida_whitelist( $sfida_id ), true ) ) {
		return true;
	}

	$livello_min = (int) get_post_meta( $sfida_id, 'gs_livello_min', true );
	if ( $livello_min <= 0 ) {
		// Blindata ma senza livello minimo: solo su ammissione esplicita.
		return false;
	}

	$livello = gs_get_level( $user_id ); // numero 1..6
	return (int) $livello['numero'] >= $livello_min;
}

/** 'sfogline' (di default, voto tra di loro) o 'maestri' (solo chi gestisce il portale può votare questa sfida). */
function gs_sfida_tipo_voto( $sfida_id ) {
	$tipo = get_post_meta( $sfida_id, 'gs_tipo_voto', true );
	return in_array( $tipo, array( 'sfogline', 'maestri' ), true ) ? $tipo : 'sfogline';
}

// -----------------------------------------------------------------------------
// Invio di una sfoglia (AJAX)
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_invia_sfoglia', 'gs_ajax_invia_sfoglia' );

function gs_ajax_invia_sfoglia() {
	check_ajax_referer( 'gs_ajax', 'nonce' );

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		wp_send_json_error( array( 'message' => 'Devi accedere per partecipare.' ) );
	}
	// gs_puo_partecipare() copre sia "non approvata" sia "congelata" (data di
	// scadenza passata): questo handler, come altri dieci trovati insieme il
	// 26/08/2026, era rimasto con il solo controllo vecchio (gs_is_approved)
	// da prima che esistesse il congelamento — una sfoglina congelata
	// restava comunque "approvata" e passava.
	if ( ! gs_puo_partecipare( $user_id ) ) {
		wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) );
	}

	$check = gs_antispam_check( $_POST, 'invio_sfoglia' );
	if ( is_wp_error( $check ) ) {
		wp_send_json_error( array( 'message' => $check->get_error_message() ) );
	}

	$sfida_id = (int) ( $_POST['sfida_id'] ?? 0 );
	$sfida    = get_post( $sfida_id );
	if ( ! $sfida || 'gs_sfida' !== $sfida->post_type ) {
		wp_send_json_error( array( 'message' => 'Sfida non valida.' ) );
	}

	// Sfida blindata: verifica livello minimo e ammissioni a discrezione.
	if ( ! gs_can_participate( $user_id, $sfida_id ) ) {
		wp_send_json_error( array( 'message' => 'Questa è una sfida blindata: non sei tra le persone ammesse a partecipare.' ) );
	}

	// Verifica modalità: se "solo a squadre", serve una squadra.
	$modalita = get_post_meta( $sfida_id, 'gs_modalita', true );
	$modalita = $modalita ? $modalita : 'entrambe';
	if ( 'squadre' === $modalita && ! get_user_meta( $user_id, 'gs_team', true ) ) {
		wp_send_json_error( array(
			'message'    => 'Questa sfida è a squadre: scegli prima una squadra.',
			'need_team'  => true,
		) );
	}

	$titolo      = gs_clean( $_POST['titolo'] ?? '' );
	$descrizione = sanitize_textarea_field( wp_unslash( $_POST['descrizione'] ?? '' ) );
	if ( ! $titolo ) {
		$titolo = 'Sfoglia di ' . wp_get_current_user()->display_name;
	}

	// Crea il post gs_sfoglia.
	$sfoglia_id = wp_insert_post( array(
		'post_type'    => 'gs_sfoglia',
		'post_status'  => 'publish',
		'post_title'   => $titolo,
		'post_content' => $descrizione,
		'post_author'  => $user_id,
	) );

	if ( is_wp_error( $sfoglia_id ) ) {
		wp_send_json_error( array( 'message' => 'Errore nel salvataggio.' ) );
	}

	update_post_meta( $sfoglia_id, 'gs_sfida_id', $sfida_id );

	// Gestione upload immagine.
	if ( ! empty( $_FILES['foto']['name'] ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		$attach_id = media_handle_upload( 'foto', $sfoglia_id );
		if ( ! is_wp_error( $attach_id ) ) {
			set_post_thumbnail( $sfoglia_id, $attach_id );
		}
	}

	// Punti + trigger.
	gs_add_points( $user_id, gs_get_points_value( 'pubblica_sfoglia', 20 ), 'Sfoglia pubblicata: ' . $titolo );
	do_action( 'gs_sfoglia_pubblicata', $user_id, $sfoglia_id, $sfida_id );

	wp_send_json_success( array( 'message' => 'Sfoglia inviata! Hai guadagnato punti. 🌾' ) );
}

// -----------------------------------------------------------------------------
// Voto a stelle (AJAX)
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_vota', 'gs_ajax_vota' );

function gs_ajax_vota() {
	check_ajax_referer( 'gs_ajax', 'nonce' );

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		wp_send_json_error( array( 'message' => 'Devi accedere per votare.' ) );
	}
	// Stessa cautela di gs_ajax_invia_sfoglia qui sopra.
	if ( ! gs_puo_partecipare( $user_id ) ) {
		wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) );
	}

	$check = gs_antispam_check( $_POST, 'voto' );
	if ( is_wp_error( $check ) ) {
		wp_send_json_error( array( 'message' => $check->get_error_message() ) );
	}

	$sfoglia_id = (int) ( $_POST['sfoglia_id'] ?? 0 );
	$sfoglia    = get_post( $sfoglia_id );
	if ( ! $sfoglia || 'gs_sfoglia' !== $sfoglia->post_type ) {
		wp_send_json_error( array( 'message' => 'Sfoglia non valida.' ) );
	}

	// Non si vota la propria sfoglia.
	if ( (int) $sfoglia->post_author === $user_id ) {
		wp_send_json_error( array( 'message' => 'Non puoi votare la tua sfoglia.' ) );
	}

	// Sfida giudicata dai maestri: solo chi gestisce il portale può votare.
	$sfida_id_voto = (int) get_post_meta( $sfoglia_id, 'gs_sfida_id', true );
	if ( $sfida_id_voto && 'maestri' === gs_sfida_tipo_voto( $sfida_id_voto ) && ! ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) ) {
		wp_send_json_error( array( 'message' => 'Questa sfida è giudicata dai maestri: le sfogline non votano.' ) );
	}

	// Non si vota due volte (controlla anche i voti salvati prima della
	// correzione del 14/08/2026 — vedi gs_utente_ha_votato()).
	if ( gs_utente_ha_votato( $sfoglia_id, $user_id ) ) {
		wp_send_json_error( array( 'message' => 'Hai già votato questa sfoglia.' ) );
	}

	// Raccoglie i punteggi (1-5 per criterio).
	$criteria = gs_vote_criteria();
	$somma_stelle = 0;
	$scores = array();
	foreach ( $criteria as $key => $label ) {
		$val = (int) ( $_POST[ 'voto_' . $key ] ?? 0 );
		$val = max( 1, min( 5, $val ) );
		$scores[ $key ] = $val;
		$somma_stelle  += $val;
	}

	// Commento obbligatorio sul perché di quel voto (Ennio, 13/08/2026): senza
	// un motivo scritto il voto non si registra.
	$commento_voto = isset( $_POST['commento_voto'] ) ? sanitize_textarea_field( wp_unslash( $_POST['commento_voto'] ) ) : '';
	if ( function_exists( 'gs_msg_clean' ) ) { $commento_voto = gs_msg_clean( $commento_voto ); }
	if ( '' === trim( $commento_voto ) ) {
		wp_send_json_error( array( 'message' => 'Scrivi il motivo del voto prima di inviarlo.' ) );
	}

	// "Prenota" il diritto a votare: add_post_meta con $unique=true non
	// inserisce (torna false) se questo utente ha già una riga per questa
	// sfoglia — un vero test di carico il 14/08/2026 ha dimostrato che il
	// vecchio schema (leggere l'array gs_voters, aggiungere, riscrivere
	// tutto) perdeva fino a metà dei voti quando molte sfogline votavano la
	// stessa sfoglia nello stesso momento: la seconda scrittura sovrascriveva
	// la prima invece di sommarsi. Qui ogni voto è una riga a sé, mai in
	// competizione con le altre.
	if ( ! add_post_meta( $sfoglia_id, 'gs_voto_uid_' . $user_id, 1, true ) ) {
		wp_send_json_error( array( 'message' => 'Hai già votato questa sfoglia.' ) );
	}
	add_post_meta( $sfoglia_id, 'gs_voto', array(
		'user' => $user_id, 'scores' => $scores, 'somma' => $somma_stelle, 'commento' => $commento_voto, 'ts' => time(),
	), false );

	// Aggiorna media voti (su 20 = 4 criteri x 5).
	update_post_meta( $sfoglia_id, 'gs_media_voti', gs_calc_media_voti( $sfoglia_id ) );

	// Punti: a chi vota + all'autrice per ogni stella.
	gs_add_points( $user_id, gs_get_points_value( 'voto_dato', 5 ), 'Voto dato a una sfoglia' );
	gs_add_points( (int) $sfoglia->post_author, gs_get_points_value( 'stella_ricevuta', 1 ) * $somma_stelle, 'Stelle ricevute (' . $somma_stelle . ')' );

	do_action( 'gs_voto_dato', $user_id, $sfoglia_id );

	wp_send_json_success( array( 'message' => 'Voto registrato, grazie! Hai guadagnato punti.' ) );
}

/**
 * Commenti già scritti su una sfoglia (non il voto — un testo libero).
 *
 * Stessa correzione del 14/08/2026 spiegata sopra per i voti: fino a quel
 * giorno anche questi commenti erano un unico array riscritto ogni volta
 * ('gs_sfoglia_commenti'), quindi vulnerabile alla stessa perdita di dati
 * sotto commenti simultanei. I nuovi si aggiungono come righe indipendenti
 * ('gs_commento', non-unique); questa funzione legge insieme vecchi e nuovi.
 */
function gs_sfoglia_commenti( $sfoglia_id ) {
	$sfoglia_id = (int) $sfoglia_id;
	$vecchi = get_post_meta( $sfoglia_id, 'gs_sfoglia_commenti', true );
	$vecchi = is_array( $vecchi ) ? $vecchi : array();
	$nuovi  = get_post_meta( $sfoglia_id, 'gs_commento', false );
	$nuovi  = is_array( $nuovi ) ? $nuovi : array();
	return array_merge( $vecchi, $nuovi );
}

/** I motivi scritti da chi ha votato questa sfoglia (uno per voto, obbligatorio dal 13/08/2026). */
function gs_sfoglia_voti_commenti( $sfoglia_id ) {
	$votes = gs_sfoglia_tutti_i_voti( $sfoglia_id );
	$out = array();
	foreach ( $votes as $v ) {
		if ( empty( $v['commento'] ) ) { continue; }
		$u = get_userdata( (int) ( $v['user'] ?? 0 ) );
		$out[] = array( 'nome' => $u ? $u->display_name : '—', 'testo' => $v['commento'] );
	}
	return $out;
}

/**
 * Commenta una sfoglia altrui (non il voto a stelle): fa avanzare la
 * missione "vota_3" (che richiede anche un commento, non solo 3 voti) e il
 * badge "Maestra Generosa" (50 commenti su sfoglie altrui).
 */
add_action( 'wp_ajax_gs_sfoglia_commento', 'gs_ajax_sfoglia_commento' );
function gs_ajax_sfoglia_commento() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$user_id = get_current_user_id();
	if ( ! $user_id ) { wp_send_json_error( array( 'message' => 'Devi accedere.' ) ); }
	// Stessa cautela di gs_ajax_invia_sfoglia/gs_ajax_vota qui sopra.
	if ( ! gs_puo_partecipare( $user_id ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }

	$sfoglia_id = (int) ( $_POST['sfoglia_id'] ?? 0 );
	$sfoglia    = get_post( $sfoglia_id );
	if ( ! $sfoglia || 'gs_sfoglia' !== $sfoglia->post_type ) {
		wp_send_json_error( array( 'message' => 'Sfoglia non valida.' ) );
	}

	$check = gs_antispam_check( $_POST, 'sfoglia_commento' );
	if ( is_wp_error( $check ) ) { wp_send_json_error( array( 'message' => $check->get_error_message() ) ); }

	$testo = isset( $_POST['testo'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) ) : '';
	if ( function_exists( 'gs_msg_clean' ) ) { $testo = gs_msg_clean( $testo ); }
	if ( mb_strlen( $testo ) < 2 ) { wp_send_json_error( array( 'message' => 'Scrivi un commento.' ) ); }
	if ( mb_strlen( $testo ) > 500 ) { $testo = mb_substr( $testo, 0, 500 ); }

	$user = get_user_by( 'id', $user_id );
	add_post_meta( $sfoglia_id, 'gs_commento', array(
		'id'    => uniqid( 'c', true ),
		'uid'   => $user_id,
		'nome'  => $user ? $user->display_name : 'utente',
		'testo' => $testo,
		'time'  => time(),
	), false );

	do_action( 'gs_commento_sfoglia', $user_id );

	wp_send_json_success( array(
		'message' => 'Commento pubblicato.',
		'nome'    => $user ? $user->display_name : 'utente',
		'testo'   => $testo,
		'n'       => count( gs_sfoglia_commenti( $sfoglia_id ) ),
	) );
}

/**
 * Media voti di una sfoglia (media della somma stelle su tutti i voti).
 */
function gs_calc_media_voti( $sfoglia_id ) {
	$votes = gs_sfoglia_tutti_i_voti( $sfoglia_id );
	if ( empty( $votes ) ) {
		return 0;
	}
	$tot = 0;
	foreach ( $votes as $v ) {
		$tot += (int) $v['somma'];
	}
	return round( $tot / count( $votes ), 2 );
}

/**
 * Sfoglie di una sfida.
 */
function gs_get_sfoglie( $sfida_id ) {
	return get_posts( array(
		'post_type'      => 'gs_sfoglia',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_key'       => 'gs_sfida_id',
		'meta_value'     => (int) $sfida_id,
	) );
}

// -----------------------------------------------------------------------------
// Controllo dei giudici: verde quando una sfoglia in gara è stata controllata,
// rossa finché resta da valutare.
// -----------------------------------------------------------------------------

function gs_sfoglia_controllata( $id ) {
	return (bool) get_post_meta( (int) $id, 'gs_controllata', true );
}

function gs_sfoglia_controllata_toggle( $id ) {
	$id    = (int) $id;
	$nuovo = ! gs_sfoglia_controllata( $id );
	update_post_meta( $id, 'gs_controllata', $nuovo ? 1 : 0 );
	return $nuovo;
}

add_action( 'wp_ajax_gs_sfoglia_controllata_toggle', 'gs_ajax_sfoglia_controllata_toggle' );
function gs_ajax_sfoglia_controllata_toggle() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['sfoglia'] ) ? (int) $_POST['sfoglia'] : 0;
	if ( 'gs_sfoglia' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Sfoglia non valida.' ) ); }
	$controllata = gs_sfoglia_controllata_toggle( $id );
	wp_send_json_success( array(
		'message'     => $controllata ? 'Segnata come controllata.' : 'Rimessa da controllare.',
		'controllata' => $controllata,
	) );
}

/**
 * Pagina unica per maestri e collaboratori: tutte le sfogline in gara nella
 * sfida attiva, con ricerca in testa, il pulsante per segnarle come
 * controllate (verde) o da controllare (rossa) e, per ciascuna, la
 * correzione punti già pronta con il suo nome.
 */
function gs_pannello_giudici_gara() {
	if ( ! gs_can_manage() ) { return; }

	echo gs_box_open( 'Sfogline in gara — controllo dei giudici', '', 'gs-box-giudici-gara' );
	$sfida = gs_get_active_challenge();
	if ( ! $sfida ) {
		echo '<p class="gs-hint">Nessuna sfida attiva al momento.</p>' . gs_box_close();
		return;
	}
	echo gs_sezione_aiuto( 'Tutte le sfoglie inviate per la sfida attiva, in un\'unica videata: usa la ricerca in cima per trovare subito una sfoglina. Ogni scheda è rossa finché resta da controllare; clicca "✅ Segnala controllata" quando l\'hai valutata e diventa verde (puoi sempre rimetterla da controllare). Dentro ogni scheda trovi anche "Correggi punti di questa sfoglina", già pronta con il suo nome, per un errore o un caso particolare.' );

	echo '<p><strong>' . esc_html( get_the_title( $sfida ) ) . '</strong></p>';
	echo '<input type="text" class="gs-cerca-input" data-target="#gs-lista-giudici-gara" placeholder="🔍 Cerca sfoglina…" style="width:100%;max-width:320px;margin-bottom:10px">';

	$sfoglie = gs_get_sfoglie( $sfida->ID );
	if ( ! $sfoglie ) {
		echo '<p class="gs-hint">Ancora nessuna sfoglia inviata per questa sfida.</p>' . gs_box_close();
		return;
	}

	echo '<div id="gs-lista-giudici-gara" class="gs-inbox-lista gs-paginate" data-per-page="10">';
	foreach ( $sfoglie as $s ) {
		$autrice     = get_userdata( $s->post_author );
		$controllata = gs_sfoglia_controllata( $s->ID );
		$cls         = $controllata ? 'gs-giudici-verde' : 'gs-giudici-rossa';
		echo '<details class="gs-inbox-item ' . esc_attr( $cls ) . '" data-sfoglia="' . (int) $s->ID . '">';
		echo '<summary class="gs-inbox-oggetto">' . ( $controllata ? '✅ ' : '🔴 ' ) . esc_html( $autrice ? $autrice->display_name : '—' )
			. ' <span class="gs-msg-data">' . esc_html( get_the_title( $s ) ) . ' · media ' . esc_html( gs_calc_media_voti( $s->ID ) ) . '/20</span></summary>';
		echo '<div class="gs-inbox-corpo">';
		echo '<p><button type="button" class="gs-btn gs-btn-sm ' . ( $controllata ? 'gs-btn-ghost' : 'gs-btn-verde' ) . ' gs-giudici-toggle" data-sfoglia="' . (int) $s->ID . '">'
			. ( $controllata ? '↺ Rimetti da controllare' : '✅ Segnala controllata' ) . '</button> <span class="gs-giudici-toggle-msg gs-richiesta-esito"></span></p>';

		echo '<form class="gs-form gs-form-correzione">';
		echo '<p><strong>Correggi punti di ' . esc_html( $autrice ? $autrice->display_name : 'questa sfoglina' ) . '</strong></p>';
		echo '<input type="hidden" name="utente" value="' . esc_attr( $autrice ? $autrice->user_login : '' ) . '">';
		echo '<p><label>Punti (es. 50 oppure -20)<br><input type="number" name="punti" required style="max-width:160px"></label></p>';
		echo '<p><label>Motivo<br><input type="text" name="motivo" required></label></p>';
		echo '<p><button type="submit" class="gs-btn gs-btn-sm">Applica correzione</button></p>';
		echo '<p class="gs-form-msg"></p>';
		echo '</form>';

		echo '</div></details>';
	}
	echo '</div>';
	echo gs_box_close();
}

// -----------------------------------------------------------------------------
// Classifiche
// -----------------------------------------------------------------------------

/**
 * Classifica generale individuale (per punti totali).
 */
function gs_leaderboard( $limit = 50 ) {
	// Filtrato con gs_e_sfoglina_vera() dopo la query (non prima, WP_User_Query
	// non sa filtrare per ruolo insieme a un ordinamento per meta numerico):
	// senza, in classifica finivano anche account con punti accumulati per
	// prova (titolare, docenti) — segnalato da Ennio l'11/08/2026. 'number' a
	// -1 per non tagliare via sfogline vere prima del filtro.
	$tutti = get_users( array(
		'meta_key' => 'gs_points',
		'orderby'  => 'meta_value_num',
		'order'    => 'DESC',
		'number'   => -1,
	) );
	$veri = array_values( array_filter( $tutti, 'gs_e_sfoglina_vera' ) );
	return array_slice( $veri, 0, $limit );
}

/**
 * Classifica di una singola sfida (per media voti).
 */
function gs_challenge_leaderboard( $sfida_id ) {
	$sfoglie = gs_get_sfoglie( $sfida_id );
	// Calcola la media una sola volta per sfoglia, poi ordina. Prima veniva
	// ricalcolata dentro il confronto di usort (O(n log n) letture di meta).
	$medie = array();
	foreach ( $sfoglie as $s ) {
		$medie[ $s->ID ] = gs_calc_media_voti( $s->ID );
	}
	usort( $sfoglie, function ( $a, $b ) use ( $medie ) {
		return $medie[ $b->ID ] <=> $medie[ $a->ID ];
	} );
	return $sfoglie;
}

// -----------------------------------------------------------------------------
// Chiusura sfide + premi (WP-Cron giornaliero)
// -----------------------------------------------------------------------------
add_action( 'gs_daily_cron', 'gs_close_expired_challenges' );

function gs_close_expired_challenges() {
	// Due date passate ENTRAMBE per strtotime(), mai current_time('timestamp')
	// (UTC + scarto di WordPress) contro uno strtotime() nel fuso del server:
	// la sfida si chiuderebbe un'ora o due prima o dopo quello che dice la
	// data (stesso difetto già corretto per P3 e L4).
	$now = strtotime( current_time( 'Y-m-d H:i:s' ) );

	$sfide = get_posts( array(
		'post_type'      => 'gs_sfida',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	) );

	foreach ( $sfide as $sfida ) {
		if ( get_post_meta( $sfida->ID, 'gs_chiusa', true ) ) {
			continue;
		}
		// gs_data_fine è già salvata con l'orario (Y-m-d H:i, vedi
		// pianificazione-anno.php): non aggiungere altro qui, altrimenti la
		// stringa diventa "…23:59 23:59:59" e strtotime() la legge male.
		$fine = strtotime( get_post_meta( $sfida->ID, 'gs_data_fine', true ) );
		if ( ! $fine || $now < $fine ) {
			continue; // ancora aperta
		}

		// Il marcatore va scritto PRIMA di assegnare i premi, non dopo: sono
		// 190 punti (100+60+30) e un giro interrotto a metà — o due giri del
		// cron sovrapposti — li darebbe due volte. È la stessa lezione della
		// chiusura del mese (luglio 2026), e in giuria-turno.php:225 è già
		// applicata così.
		update_post_meta( $sfida->ID, 'gs_chiusa', 1 );
		gs_award_challenge_prizes( $sfida->ID );
	}
}

/**
 * Assegna i punti bonus alle prime tre classificate di una sfida.
 */
function gs_award_challenge_prizes( $sfida_id ) {
	$classifica = gs_challenge_leaderboard( $sfida_id );
	$premi = array(
		0 => gs_get_points_value( 'primo_posto', 100 ),
		1 => gs_get_points_value( 'secondo_posto', 60 ),
		2 => gs_get_points_value( 'terzo_posto', 30 ),
	);
	$posizione_label = array( 0 => '1°', 1 => '2°', 2 => '3°' );

	$premiate = 0;
	foreach ( $classifica as $pos => $sfoglia ) {
		if ( ! isset( $premi[ $pos ] ) ) {
			break;
		}
		$autrice = (int) $sfoglia->post_author;
		gs_add_points( $autrice, $premi[ $pos ], sprintf( 'Premio sfida "%s" (%s posto)', get_the_title( $sfida_id ), $posizione_label[ $pos ] ) );
		do_action( 'gs_podio_sfida', $autrice, $sfida_id, $pos + 1 );
		$premiate++;
	}

	// Il marcatore ora è scritto PRIMA di questa funzione: se si interrompe a
	// metà, la sfida resta chiusa e le ultime classificate non ricevono il
	// premio, senza che nessuno se ne accorga — meglio un premio mancato e
	// visibile che 190 punti regalati due volte e invisibili, ma va segnalato.
	if ( function_exists( 'gs_inbox_crea' ) ) {
		gs_inbox_crea(
			'Sfida chiusa: ' . get_the_title( $sfida_id ),
			'Premi assegnati a ' . $premiate . ' sfoglin' . ( 1 === $premiate ? 'a' : 'e' ) . '. Se il numero non torna, controlla la classifica.',
			array()
		);
	}
}
