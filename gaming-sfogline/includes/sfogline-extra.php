<?php
/**
 * sfogline-extra.php — Lotto 1
 * - Cestino personale di ogni sfoglina (recupero dei contenuti cancellati)
 * - "Le cose da fare" (promemoria personali)
 * - Ricerca personale (solo nei propri contenuti)
 * - "Le Mie Sfide" (i propri lavori inviati)
 * - Pagina pubblica "Le Sfogline"
 * - Blackout: oscura tutto il Gaming
 * - Blocco dashboard WP: nega wp-admin a chi non è amministratore vero
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// ARCHIVIO DATI DI GIOCO — quando una sfoglina finisce nel Cestino
// (Rifiutata/Sospesa/Eliminata, vedi gs_e_stato_cestino_sfoglina() più sotto),
// i suoi dati di gioco (punti, streak, badge, squadra, classifica…) vengono
// tolti da ogni conteggio/elenco pubblico ma MAI cancellati per sempre —
// stessa regola "mai cancellazione definitiva" già seguita per i contenuti
// (wp_trash_post), estesa qui ai dati di gioco. L'account WordPress vero
// (login, email, password) non viene mai toccato in nessun caso.
// Richiesto da Ennio il 16/08/2026.
// -----------------------------------------------------------------------------
define( 'GS_ARCHIVIO_GAMING_META', 'gs_archivio_gaming' );

/** Meta "di identità/account", MAI archiviate/toccate anche per chi finisce nel Cestino: non sono dati di gioco, sono dell'account stesso. */
function gs_archivio_gaming_meta_esclusi() {
	return array( 'gs_status', 'gs_email_verificata', 'gs_email_verify_token', 'gs_genere', 'gs_birthdate', 'gs_richiesta_cancellazione', GS_ARCHIVIO_GAMING_META );
}

/**
 * Archivia tutti i meta 'gs_' di una sfoglina (tranne quelli "di identità"
 * sopra) in un unico meta recuperabile, poi li toglie da dove erano — così
 * spariscono da punti/squadra/classifica/badge senza perdersi per sempre.
 * Non fa nulla se non trova nessun meta gs_ da archiviare.
 */
function gs_archivia_dati_gaming_utente( $uid ) {
	$uid = (int) $uid;
	if ( ! $uid ) { return 0; }
	$tutti    = get_user_meta( $uid );
	$esclusi  = gs_archivio_gaming_meta_esclusi();
	$archivio = get_user_meta( $uid, GS_ARCHIVIO_GAMING_META, true );
	$archivio = is_array( $archivio ) ? $archivio : array();
	$tolti    = 0;
	foreach ( $tutti as $chiave => $valori ) {
		if ( 0 !== strpos( $chiave, 'gs_' ) || in_array( $chiave, $esclusi, true ) ) {
			continue;
		}
		$archivio[ $chiave ] = isset( $valori[0] ) ? maybe_unserialize( $valori[0] ) : null;
		delete_user_meta( $uid, $chiave );
		$tolti++;
	}
	if ( $tolti ) {
		update_user_meta( $uid, GS_ARCHIVIO_GAMING_META, $archivio );
	}
	return $tolti;
}

/** Ripristina i dati di gioco archiviati (una sfoglina torna Approvata dal Cestino). */
function gs_ripristina_dati_gaming_utente( $uid ) {
	$uid      = (int) $uid;
	$archivio = get_user_meta( $uid, GS_ARCHIVIO_GAMING_META, true );
	if ( ! is_array( $archivio ) || ! $archivio ) {
		return 0;
	}
	$rimessi = 0;
	foreach ( $archivio as $chiave => $valore ) {
		update_user_meta( $uid, $chiave, $valore );
		$rimessi++;
	}
	delete_user_meta( $uid, GS_ARCHIVIO_GAMING_META );
	return $rimessi;
}

/** Tipi di contenuto che una sfoglina possiede e può cestinare/ripristinare. */
function gs_tipi_contenuto_sfoglina() {
	return array( 'gs_sfoglia', 'gs_diario', 'gs_consiglio' );
}

function gs_tipo_etichetta( $tipo ) {
	$map = array(
		'gs_sfoglia'   => 'Sfoglia',
		'gs_diario'    => 'Diario',
		'gs_consiglio' => 'Consiglio',
	);
	return isset( $map[ $tipo ] ) ? $map[ $tipo ] : $tipo;
}

// -----------------------------------------------------------------------------
// BLACKOUT — oscura tutto il Gaming tranne che per chi gestisce
// -----------------------------------------------------------------------------

/**
 * Da chiamare in cima agli shortcode pubblici: se il blackout è attivo e
 * l'utente non gestisce il portale, restituisce l'avviso e blocca il contenuto.
 * Ritorna '' quando si può proseguire.
 */
function gs_blackout_gate() {
	if ( gs_blackout_attivo()
		&& ! ( function_exists( 'gs_can_manage' ) && gs_can_manage() )
		&& ! ( function_exists( 'gs_blackout_esente' ) && gs_blackout_esente() ) ) {
		// Palloncini decorativi statici, in loop: "ricetta" grafica identica ai
		// palloncini interattivi (gradiente + ombre) ma classi tutte nuove,
		// apposta per non finire nei selettori JS del sistema Palloncini /
		// Palloncino Gigante (che genera, fa scoppiare e ripulisce i suoi
		// .gs-palloncino) — qui devono solo galleggiare, senza mai scoppiare,
		// per tutta la durata del blackout (Ennio, 22/08/2026: "una pagina
		// bella con i palloncini che si muovono").
		$colori     = array( '#b5722a', '#d9a441', '#6b8e4e', '#8a5420', '#d9a441', '#b5722a' );
		$posizioni  = array( '6%', '22%', '40%', '58%', '76%', '90%' );
		$ritardi    = array( '-1s', '-4s', '-2.5s', '-6s', '-0.5s', '-3.5s' );
		$palloncini = '<div class="gs-blackout-palloncini">';
		foreach ( $colori as $i => $colore ) {
			$palloncini .= '<span class="gs-blackout-palloncino" style="--bx:' . esc_attr( $posizioni[ $i ] ) . ';--bc:' . esc_attr( $colore ) . ';--bd:' . esc_attr( $ritardi[ $i ] ) . '">'
				. '<span class="filo"></span><span class="corpo"></span><span class="nodo"></span></span>';
		}
		$palloncini .= '</div>';

		return '<div class="gs-box gs-notice gs-blackout-avviso">'
			. $palloncini
			. '<div class="gs-blackout-avviso-icona">🌙</div>'
			. '<h3>Area momentaneamente sospesa</h3>'
			. '<p>Ci scusiamo per l\'interruzione: l\'area riservata del Percorso è temporaneamente non visitabile per lavori in corso.</p>'
			. '<p>Stiamo lavorando proprio per voi sfogline: tutto quello che hai già fatto resta al sicuro. Torna a trovarci tra poco!</p>'
			. '</div>';
	}
	return '';
}

// -----------------------------------------------------------------------------
// BLOCCO DASHBOARD WP — con l'interruttore acceso, chi non è amministratore
// vero viene rimandato al pannello del progetto invece di poter aprire
// wp-admin. Utile per condividere il sito coi collaboratori durante la
// manutenzione, senza dare loro accesso alla dashboard.
// -----------------------------------------------------------------------------
add_action( 'admin_init', 'gs_blocca_wp_admin_dashboard' );
function gs_blocca_wp_admin_dashboard() {
	if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
		return; // le chiamate AJAX del pannello passano da admin-ajax.php: non sono "la dashboard"
	}
	if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
		return;
	}
	// Le pagine stampabili del progetto (diplomi, certificati, locandine,
	// esportazioni) passano da admin.php per comodità tecnica, ma sono
	// contenuto del progetto, non la dashboard: restano raggiungibili, protette
	// dai loro stessi controlli di permesso.
	foreach ( array( 'gs_diploma', 'gs_export', 'gs_certificato_percorso', 'gs_locandina', 'gs_attestato_cal' ) as $qv ) {
		if ( isset( $_GET[ $qv ] ) ) {
			return;
		}
	}
	if ( ! gs_wp_admin_va_bloccato_per( get_current_user_id() ) ) {
		return;
	}
	$pannello_id = (int) get_option( 'gs_page_pannello' );
	$url         = $pannello_id ? get_permalink( $pannello_id ) : home_url();
	wp_safe_redirect( $url );
	exit;
}

// -----------------------------------------------------------------------------
// LAVORI E CESTINO DELLA SFOGLINA
// -----------------------------------------------------------------------------

/** Contenuti pubblicati di una sfoglina. */
function gs_get_user_works( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) { return array(); } // mai autore 0: eviterebbe di pescare tutto il sito
	$tipi = gs_tipi_contenuto_sfoglina();
	// gs_solo_tipo(): un tema o un altro plugin può alterare la query tramite
	// pre_get_posts (non bloccato da suppress_filters) e far comparire
	// contenuti veri del sito — già capitato con Ricettario/Aiuto/Messaggi.
	return gs_solo_tipo( gs_get_posts_by_author( array(
		'post_type'      => $tipi,
		'author'         => $user_id,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'suppress_filters' => true,
	) ), $tipi );
}

/** Contenuti nel cestino di una sfoglina. */
function gs_get_user_trash( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) { return array(); } // mai autore 0
	$tipi = gs_tipi_contenuto_sfoglina();
	return gs_solo_tipo( gs_get_posts_by_author( array(
		'post_type'      => $tipi,
		'author'         => $user_id,
		'post_status'    => 'trash',
		'posts_per_page' => -1,
		'orderby'        => 'modified',
		'order'          => 'DESC',
		'suppress_filters' => true,
	) ), $tipi );
}

/** Vero se l'utente possiede il post ed è di un tipo gestibile. */
function gs_utente_possiede( $user_id, $post_id ) {
	$p = get_post( $post_id );
	return $p
		&& (int) $p->post_author === (int) $user_id
		&& in_array( $p->post_type, gs_tipi_contenuto_sfoglina(), true );
}

// ---- AJAX: cestina un proprio contenuto --------------------------------------
add_action( 'wp_ajax_gs_cestina', 'gs_ajax_cestina' );
function gs_ajax_cestina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	$pid = isset( $_POST['post'] ) ? (int) $_POST['post'] : 0;

	// Un gestore può cestinare per conto di chiunque; una sfoglina solo i propri.
	$owner = gs_can_manage() ? ( get_post( $pid ) ? (int) get_post( $pid )->post_author : 0 ) : $uid;

	if ( ! $pid || ! gs_utente_possiede( $owner, $pid ) ) {
		wp_send_json_error( array( 'message' => 'Contenuto non valido.' ) );
	}
	wp_trash_post( $pid );
	wp_send_json_success( array( 'message' => 'Spostato nel cestino.' ) );
}

// ---- AJAX: ripristina un proprio contenuto -----------------------------------
add_action( 'wp_ajax_gs_ripristina', 'gs_ajax_ripristina' );
function gs_ajax_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	$pid = isset( $_POST['post'] ) ? (int) $_POST['post'] : 0;
	$p   = get_post( $pid );

	$owner = gs_can_manage() ? ( $p ? (int) $p->post_author : 0 ) : $uid;

	if ( ! $pid || ! $p || (int) $p->post_author !== (int) $owner || 'trash' !== $p->post_status ) {
		wp_send_json_error( array( 'message' => 'Contenuto non ripristinabile.' ) );
	}
	wp_untrash_post( $pid );
	wp_send_json_success( array( 'message' => 'Ripristinato.' ) );
}

/** Riga (tabella) di un contenuto, con azione cestina o ripristina. */
function gs_riga_contenuto( $p, $azione ) {
	$titolo = get_the_title( $p ) ? get_the_title( $p ) : '(senza titolo)';
	$data   = mysql2date( 'j/m/Y', $p->post_date );
	$btn = ( 'cestina' === $azione )
		? '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-cestina" data-post="' . (int) $p->ID . '">Cestina</button>'
		: '<button class="gs-btn gs-btn-sm gs-btn-verde gs-ripristina" data-post="' . (int) $p->ID . '">Ripristina</button>';
	return sprintf(
		'<tr data-post="%1$d" data-nome="%2$s"><td>%3$s</td><td>%2$s</td><td>%4$s</td><td>%5$s</td></tr>',
		(int) $p->ID,
		esc_attr( strtolower( $titolo ) ),
		esc_html( gs_tipo_etichetta( $p->post_type ) ),
		esc_html( $titolo ),
		$data . '</td><td>' . $btn
	);
}

/**
 * Come gs_riga_contenuto(), ma cliccabile: apre il contenuto vero (testo e,
 * se c'è, la foto) invece di restare solo un titolo scritto. Usata nel
 * dossier di "Cerca sfoglina e recupera i suoi file", dove il titolare deve
 * poter vedere davvero il lavoro, non solo saperne il nome.
 */
function gs_dossier_riga( $p, $azione ) {
	$titolo = get_the_title( $p ) ? get_the_title( $p ) : '(senza titolo)';
	$data   = mysql2date( 'j/m/Y', $p->post_date );
	$btn    = ( 'cestina' === $azione )
		? '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-cestina" data-post="' . (int) $p->ID . '">Cestina</button>'
		: '<button class="gs-btn gs-btn-sm gs-btn-verde gs-ripristina" data-post="' . (int) $p->ID . '">Ripristina</button>';

	$out  = '<details class="gs-inbox-item" data-post="' . (int) $p->ID . '" data-nome="' . esc_attr( strtolower( $titolo ) ) . '">';
	$out .= '<summary class="gs-inbox-oggetto">' . esc_html( $titolo )
		. ' <span class="gs-msg-data">' . esc_html( gs_tipo_etichetta( $p->post_type ) ) . ' · ' . esc_html( $data ) . '</span></summary>';
	$out .= '<div class="gs-inbox-corpo">';
	if ( has_post_thumbnail( $p ) ) {
		$out .= '<div class="gs-msg-media"><img src="' . esc_url( get_the_post_thumbnail_url( $p, 'medium' ) ) . '" alt=""></div>';
	}
	$contenuto = trim( wp_strip_all_tags( $p->post_content ) );
	$out .= $contenuto ? '<div class="gs-inbox-testo">' . nl2br( esc_html( $contenuto ) ) . '</div>' : '<p class="gs-hint">Nessun testo.</p>';
	$out .= '<p>' . $btn . ' <span class="gs-richiesta-esito"></span></p>';
	$out .= '</div></details>';
	return $out;
}

/** Sezione "I Miei Lavori" + ricerca personale (solo nei propri file). */
function gs_render_mie_sfide( $user_id ) {
	$works = gs_get_user_works( $user_id );
	$out  = gs_box_open( '🏆 Le Mie Sfide' );
	$out .= gs_sezione_aiuto( 'Qui trovi tutti i lavori che hai inviato finora, in tutte le sezioni. Usa la ricerca per ritrovarne uno per nome; da ogni riga puoi spostarlo nel cestino, recuperabile in ogni momento dal riquadro "Il Mio Cestino" qui sotto.' );
	$out .= '<p class="gs-hint">Tutti i tuoi lavori inviati. Usa la ricerca per ritrovarli per nome; puoi spostarli nel cestino e recuperarli quando vuoi.</p>';
	$out .= '<input type="text" class="gs-cerca-input" data-target="#gs-miei-lavori" placeholder="🔍 Cerca nei miei lavori…" style="width:100%;max-width:360px;margin-bottom:10px">';
	if ( empty( $works ) ) {
		$out .= '<p>Non hai ancora inviato lavori.</p>';
	} else {
		$out .= '<table class="gs-table gs-paginate" data-per-page="10" id="gs-miei-lavori"><thead><tr><th>Tipo</th><th>Titolo</th><th>Data</th><th>Azione</th></tr></thead><tbody>';
		foreach ( $works as $p ) {
			$out .= gs_riga_contenuto( $p, 'cestina' );
		}
		$out .= '</tbody></table>';
	}
	$out .= gs_box_close();
	return $out;
}

/** Sezione "Il Mio Cestino". */
function gs_render_cestino( $user_id ) {
	$trash = gs_get_user_trash( $user_id );
	$out  = gs_box_open( '🗑️ Il Mio Cestino', 'gs-box-cestino' );
	$out .= gs_sezione_aiuto( 'Quando cancelli un tuo lavoro, finisce qui invece di sparire per sempre. Cercalo per nome e premi "Ripristina" per riportarlo esattamente com\'era prima.' );
	$out .= '<p class="gs-hint">Qui trovi i contenuti che hai cancellato. Puoi cercarli per nome e ripristinarli.</p>';
	$out .= '<input type="text" class="gs-cerca-input" data-target="#gs-mio-cestino" placeholder="🔍 Cerca nel cestino…" style="width:100%;max-width:360px;margin-bottom:10px">';
	if ( empty( $trash ) ) {
		$out .= '<p>Il cestino è vuoto.</p>';
	} else {
		$out .= '<table class="gs-table gs-paginate" data-per-page="10" id="gs-mio-cestino"><thead><tr><th>Tipo</th><th>Titolo</th><th>Data</th><th>Azione</th></tr></thead><tbody>';
		foreach ( $trash as $p ) {
			$out .= gs_riga_contenuto( $p, 'ripristina' );
		}
		$out .= '</tbody></table>';
	}
	$out .= gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// "LE COSE DA FARE" — promemoria personali
// -----------------------------------------------------------------------------

function gs_get_todos( $user_id ) {
	$t = get_user_meta( $user_id, 'gs_todos', true );
	return is_array( $t ) ? $t : array();
}
function gs_save_todos( $user_id, $todos ) {
	update_user_meta( $user_id, 'gs_todos', array_values( $todos ) );
}

/**
 * Cestino personale delle "Cose da Fare": non sono post (solo un elenco in
 * user meta), quindi wp_trash_post() non si applica — ma la regola resta la
 * stessa, niente cancellazione definitiva. Elenco separato, capped come la
 * coda dell'aeroplanino, per non crescere all'infinito.
 */
function gs_get_todos_cestino( $user_id ) {
	$t = get_user_meta( $user_id, 'gs_todos_cestino', true );
	return is_array( $t ) ? $t : array();
}
function gs_save_todos_cestino( $user_id, $cestino ) {
	if ( count( $cestino ) > 30 ) {
		$cestino = array_slice( $cestino, -30 );
	}
	update_user_meta( $user_id, 'gs_todos_cestino', array_values( $cestino ) );
}

add_action( 'wp_ajax_gs_todo_add', 'gs_ajax_todo_add' );
function gs_ajax_todo_add() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid  = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }
	$testo = isset( $_POST['testo'] ) ? sanitize_text_field( wp_unslash( $_POST['testo'] ) ) : '';
	if ( '' === $testo ) { wp_send_json_error( array( 'message' => 'Scrivi qualcosa.' ) ); }

	$todos   = gs_get_todos( $uid );
	$id      = uniqid( 't' );
	$todos[] = array( 'id' => $id, 'testo' => $testo, 'fatto' => false );
	gs_save_todos( $uid, $todos );
	wp_send_json_success( array( 'id' => $id, 'testo' => $testo ) );
}

add_action( 'wp_ajax_gs_todo_toggle', 'gs_ajax_todo_toggle' );
function gs_ajax_todo_toggle() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	$id  = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	$todos = gs_get_todos( $uid );
	foreach ( $todos as &$t ) {
		if ( $t['id'] === $id ) { $t['fatto'] = empty( $t['fatto'] ); }
	}
	gs_save_todos( $uid, $todos );
	wp_send_json_success();
}

/** Sposta un promemoria dagli attivi al cestino personale. */
function gs_todo_sposta_nel_cestino( $uid, $id ) {
	$todos   = gs_get_todos( $uid );
	$cestino = gs_get_todos_cestino( $uid );
	foreach ( $todos as $t ) {
		if ( $t['id'] === $id ) {
			$t['ts']   = time();
			$cestino[] = $t;
		}
	}
	$todos = array_filter( $todos, function ( $t ) use ( $id ) {
		return $t['id'] !== $id;
	} );
	gs_save_todos( $uid, $todos );
	gs_save_todos_cestino( $uid, $cestino );
}

/** Riporta un promemoria dal cestino agli attivi. */
function gs_todo_ripristina_dal_cestino( $uid, $id ) {
	$cestino = gs_get_todos_cestino( $uid );
	$todos   = gs_get_todos( $uid );
	foreach ( $cestino as $t ) {
		if ( $t['id'] === $id ) {
			unset( $t['ts'] );
			$todos[] = $t;
		}
	}
	$cestino = array_filter( $cestino, function ( $t ) use ( $id ) {
		return $t['id'] !== $id;
	} );
	gs_save_todos( $uid, $todos );
	gs_save_todos_cestino( $uid, $cestino );
}

add_action( 'wp_ajax_gs_todo_del', 'gs_ajax_todo_del' );
function gs_ajax_todo_del() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	$id  = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	gs_todo_sposta_nel_cestino( $uid, $id );
	wp_send_json_success();
}

add_action( 'wp_ajax_gs_todo_ripristina', 'gs_ajax_todo_ripristina' );
function gs_ajax_todo_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	$id  = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
	gs_todo_ripristina_dal_cestino( $uid, $id );
	wp_send_json_success( array( 'message' => 'Ripristinato tra le cose da fare.' ) );
}

function gs_render_todos( $user_id ) {
	$todos   = gs_get_todos( $user_id );
	$cestino = gs_get_todos_cestino( $user_id );
	$out  = gs_box_open( '📝 Le Cose da Fare' );
	$out .= gs_sezione_aiuto( 'I tuoi promemoria personali: scrivili, spuntali quando li hai fatti, cancellali. Anche quelli eliminati restano recuperabili, in "🗑️ Cose eliminate" qui sotto.' );
	$out .= '<div class="gs-todo-riquadro">';
	$out .= '<form class="gs-form gs-todo-form" onsubmit="return false"><div style="display:flex;gap:8px">'
		. '<input type="text" class="gs-todo-input" placeholder="Aggiungi un promemoria…" style="flex:1">'
		. '<button class="gs-btn gs-btn-sm gs-todo-add">Aggiungi</button></div></form>';
	$out .= '<ul class="gs-todo-list gs-todo-list-attive">';
	foreach ( $todos as $t ) {
		$done = ! empty( $t['fatto'] ) ? ' done' : '';
		$chk  = ! empty( $t['fatto'] ) ? 'checked' : '';
		$out .= '<li class="gs-todo-item' . $done . '" data-id="' . esc_attr( $t['id'] ) . '">'
			. '<label><input type="checkbox" class="gs-todo-check" ' . $chk . '> ' . esc_html( $t['testo'] ) . '</label>'
			. '<button class="gs-todo-del" title="Elimina">✕</button></li>';
	}
	$out .= '</ul>';
	$out .= '<details class="gs-todo-cestino"><summary>🗑️ Cose eliminate (' . count( $cestino ) . ')</summary>';
	if ( ! $cestino ) {
		$out .= '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		$out .= '<ul class="gs-todo-list gs-todo-list-cestino">';
		foreach ( array_reverse( $cestino ) as $t ) {
			$out .= '<li class="gs-todo-item" data-id="' . esc_attr( $t['id'] ) . '">'
				. '<span>' . esc_html( $t['testo'] ) . '</span>'
				. '<button class="gs-todo-ripristina" title="Ripristina">↺ Ripristina</button></li>';
		}
		$out .= '</ul>';
	}
	$out .= '</details>';
	$out .= '</div>';
	$out .= gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// PANNELLO GESTORE: cerca una sfoglina e recupera i suoi file dal cestino
// -----------------------------------------------------------------------------

/** Zona "Cerca sfoglina" per il pannello di controllo / plancia. */
function gs_pannello_cerca_sfoglina() {
	echo gs_box_open( 'Cerca sfoglina e recupera i suoi file', '', 'gs-box-cerca-sfoglina' );
	?>
	<p class="gs-hint">Trova una sfoglina per nome, username o email, oppure apri l'elenco completo qui sotto: in entrambi i casi vedrai la sua scheda personale completa — dati anagrafici (modificabili), attivazioni (abbonamento, Vetrina, token), il suo percorso fino a questo momento, la sua Vetrina pubblica, la biografia, tutti i suoi lavori e sfide (clicca il titolo per aprirli davvero, non solo per leggerne il nome), il suo cestino da cui ripristinare i file cancellati, e i pulsanti per attivare/disattivare o eliminare l'account.</p>
	<form class="gs-form gs-form-cerca-sfoglina" onsubmit="return false">
		<div style="display:flex;gap:8px;max-width:420px">
			<input type="text" class="gs-cerca-sfoglina-input" placeholder="🔍 Nome, username o email…" style="flex:1">
			<button class="gs-btn gs-btn-sm gs-cerca-sfoglina-btn">Cerca</button>
		</div>
	</form>
	<div class="gs-cerca-sfoglina-risultati" style="margin-top:14px"></div>
	<p style="margin-top:14px"><?php echo gs_elenco_sfogline_bottone_html(); ?> <?php echo gs_cestino_sfogline_bottone_html(); ?></p>
	<?php
	echo gs_box_close();
}

/**
 * Elenco di TUTTE le sfogline registrate (a differenza della ricerca sopra,
 * non serve conoscere un nome): un pulsante che apre un popup con l'intero
 * elenco e i dati anagrafici registrati — utile per scorrere/controllare
 * invece di cercare una persona precisa. Esclude chi gestisce il pannello
 * (titolare/collaboratori) e gli account "artigiano"/"lettore", che non sono
 * sfogline. Riusabile in più pannelli (richiesto 2026-08-08): Cerca
 * sfoglina, Plancia Generale classica, Pannello Generale nuovo.
 */
function gs_elenco_sfogline_bottone_html() {
	$n = gs_conta_sfogline_registrate();
	return '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-vedi-tutte-sfogline">📋 Vedi tutte le sfogline registrate (' . (int) $n . ')</button> <span class="gs-elenco-sfogline-msg gs-richiesta-esito"></span>';
}

/** True se questo stato va nel Cestino delle sfogline (non nell'elenco principale). */
function gs_e_stato_cestino_sfoglina( $stato ) {
	return in_array( $stato, array( 'rifiutata', 'sospesa', 'eliminata' ), true );
}

/**
 * Le sfogline vere, per l'elenco PRINCIPALE del pannello. SOLO chi ha
 * davvero a che fare con la community (Ennio, 11/08/2026: "non voglio
 * vedere altri nomi all'infuori delle sfogline iscritte nella lista
 * generale, creano confusione"): chi compare nelle pagine pubbliche, più chi
 * è in attesa di essere valutata. Chi è Rifiutata/Sospesa/Eliminata non
 * compare più qui (richiesto da Ennio il 16/08/2026, stesso motivo di
 * prima portato oltre: restano "gestibili" ma dal Cestino qui sotto, non
 * mescolate con le sfogline vere — vedi gs_utenti_sfogline_cestino()).
 * Restano fuori tutti gli altri utenti WordPress del sito (contatti,
 * iscritti alla newsletter, account creati a mano), che non sono sfogline:
 * per trovarli e, se serve, abilitarli come sfogline, c'è la ricerca per
 * nome/username/email qui sopra, che continua a cercare fra TUTTI gli
 * account del sito.
 */
function gs_utenti_sfogline_veri() {
	$out = array();
	foreach ( get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) ) as $u ) {
		$stato = get_user_meta( $u->ID, 'gs_status', true );
		// Chi ha l'interruttore "Abilitata come sfoglina" acceso resta SEMPRE
		// nell'elenco principale, mai nel Cestino, qualunque sia gs_status —
		// controllato PRIMA di artigiano/scuola_cucina/lettore, coerente con
		// l'ordine corretto in gs_e_sfoglina_vera() (bug corretto il
		// 16/08/2026 per sospesa/eliminata, esteso il 25/08/2026 anche qui:
		// era successo davvero a Rina Poletti e Bruno Cingolani).
		if ( '1' === get_user_meta( $u->ID, 'gs_conta_come_sfoglina', true ) ) {
			$out[] = $u;
			continue;
		}
		if ( in_array( $stato, array( 'artigiano', 'scuola_cucina', 'lettore' ), true ) ) { continue; }
		if ( gs_e_stato_cestino_sfoglina( $stato ) ) { continue; }
		if ( gs_e_sfoglina_vera( $u ) || 'in_attesa' === $stato ) {
			$out[] = $u;
		}
	}
	return $out;
}

function gs_conta_sfogline_registrate() {
	return count( gs_utenti_sfogline_veri() );
}

/**
 * Sfogline nel Cestino: Rifiutata, Sospesa, Eliminata — tolte dall'elenco
 * principale (16/08/2026) ma sempre gestibili da qui, con l'account
 * WordPress intatto. Ogni volta che questo elenco viene aperto, chi non ha
 * ancora i dati di gioco archiviati li archivia adesso (pesca a freddo per
 * chi era già in questo stato prima che esistesse l'archiviazione) — vedi
 * gs_archivia_dati_gaming_utente().
 */
function gs_utenti_sfogline_cestino() {
	$out = array();
	foreach ( get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) ) as $u ) {
		// Mai nel Cestino chi ha l'interruttore "Abilitata come sfoglina"
		// acceso, qualunque sia gs_status — vedi gs_utenti_sfogline_veri().
		if ( '1' === get_user_meta( $u->ID, 'gs_conta_come_sfoglina', true ) ) { continue; }
		$stato = get_user_meta( $u->ID, 'gs_status', true );
		if ( gs_e_stato_cestino_sfoglina( $stato ) ) {
			if ( function_exists( 'gs_archivia_dati_gaming_utente' ) && '' === get_user_meta( $u->ID, GS_ARCHIVIO_GAMING_META, true ) ) {
				gs_archivia_dati_gaming_utente( $u->ID );
			}
			$out[] = $u;
		}
	}
	return $out;
}

function gs_conta_sfogline_cestino() {
	return count( gs_utenti_sfogline_cestino() );
}

/** Pulsante che apre il Cestino delle sfogline (Rifiutata/Sospesa/Eliminata), stesso stile del pulsante "Vedi tutte" qui sopra. */
function gs_cestino_sfogline_bottone_html() {
	$n = gs_conta_sfogline_cestino();
	return '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-vedi-cestino-sfogline">🗑️ Cestino sfogline (' . (int) $n . ')</button> <span class="gs-cestino-sfogline-msg gs-richiesta-esito"></span>';
}

/** Etichette leggibili di gs_status, riusate dall'elenco principale e dal Cestino. */
function gs_etichette_stato_sfoglina() {
	return array(
		''          => 'Mai iscritta dal sito',
		'approvata' => 'Approvata',
		'in_attesa' => 'In attesa',
		'rifiutata' => 'Rifiutata',
		'sospesa'   => 'Sospesa',
		'eliminata' => 'Eliminata (nascosta)',
	);
}

/**
 * Tabella condivisa tra l'elenco principale e il Cestino delle sfogline —
 * stessa identica resa, cambia solo l'elenco di utenti passato. $id_tabella
 * deve essere diverso nei due popup, altrimenti la ricerca/paginazione
 * dell'uno agirebbe per errore sulla tabella dell'altro se restassero
 * entrambi nel DOM contemporaneamente.
 */
function gs_tabella_sfogline_html( $utenti, $id_tabella ) {
	$etichette_stato = gs_etichette_stato_sfoglina();
	ob_start();
	if ( ! $utenti ) {
		echo '<p class="gs-hint">Nessuna sfoglina qui.</p>';
	} else {
		echo '<input type="text" class="gs-cerca-input" data-target="#' . esc_attr( $id_tabella ) . '" placeholder="🔍 Cerca…" style="width:100%;max-width:320px;margin-bottom:10px">';
		echo '<table class="gs-table gs-paginate" data-per-page="15" id="' . esc_attr( $id_tabella ) . '"><thead><tr><th>Sfoglina</th><th>Nome</th><th>Username</th><th>Email</th><th>Registrata il</th><th>Stato</th></tr></thead><tbody>';
		foreach ( $utenti as $u ) {
			$stato   = get_user_meta( $u->ID, 'gs_status', true );
			$compare = gs_e_sfoglina_vera( $u );
			$flag    = '1' === get_user_meta( $u->ID, 'gs_conta_come_sfoglina', true );
			echo '<tr data-nome="' . esc_attr( strtolower( $u->display_name . ' ' . $u->user_login . ' ' . $u->user_email ) ) . '">';
			echo '<td class="gs-conta-sfoglina-riga" data-uid="' . (int) $u->ID . '" style="text-align:center">'
				. '<input type="checkbox" class="gs-conta-come-sfoglina" ' . checked( $flag, true, false ) . ' title="Abilitata come sfoglina: compare nelle pagine pubbliche">'
				. ( $compare && ! $flag ? ' <span class="gs-hint" title="Compare perché approvata dalla registrazione">✓</span>' : '' )
				. ' <span class="gs-conta-sfoglina-msg gs-richiesta-esito"></span></td>';
			echo '<td><a href="#" class="gs-apri-scheda-sfoglina" data-uid="' . (int) $u->ID . '">' . esc_html( $u->display_name ) . '</a></td>';
			echo '<td>' . esc_html( $u->user_login ) . '</td>';
			echo '<td>' . esc_html( $u->user_email ) . '</td>';
			echo '<td>' . esc_html( date_i18n( 'd/m/Y', strtotime( $u->user_registered ) ) ) . '</td>';
			echo '<td>' . esc_html( $etichette_stato[ $stato ] ?? $stato ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '<div class="gs-scheda-sfoglina-aperta" style="margin-top:16px"></div>';
	}
	return ob_get_clean();
}

add_action( 'wp_ajax_gs_elenco_sfogline', 'gs_ajax_elenco_sfogline' );
function gs_ajax_elenco_sfogline() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$utenti = gs_utenti_sfogline_veri();
	$n_pubbliche = 0;
	foreach ( $utenti as $u ) { if ( gs_e_sfoglina_vera( $u ) ) { $n_pubbliche++; } }
	$intro = '<p class="gs-hint">Solo le sfogline: ' . count( $utenti ) . ' in elenco, di cui <strong>' . (int) $n_pubbliche . '</strong> compaiono nelle pagine pubbliche (Le Sfogline, caroselli, Classifica). Rifiutata/Sospesa/Eliminata sono nel 🗑️ Cestino qui accanto, non qui. Gli altri utenti WordPress del sito (contatti, iscritti alla newsletter, account creati a mano) non compaiono qui e non entrano mai da soli tra le sfogline: se devi abilitarne uno, cercalo per nome/username/email con la ricerca del riquadro e apri la sua scheda. La spunta nella colonna "Sfoglina" decide chi compare sul sito; clicca un nome per la scheda personale completa.</p>';

	wp_send_json_success( array(
		'html'   => $intro . gs_tabella_sfogline_html( $utenti, 'gs-tabella-tutte-sfogline' ),
		'titolo' => 'Tutte le sfogline registrate (' . count( $utenti ) . ')',
	) );
}

/**
 * Cestino delle sfogline: Rifiutata/Sospesa/Eliminata, tolte dall'elenco
 * principale (16/08/2026) ma sempre gestibili da qui — stessa scheda
 * personale, stessi pulsanti per riattivarle. I loro dati di gioco sono
 * archiviati (mai cancellati per sempre): tornando Approvata da qui, li
 * ritrovano com'erano — vedi gs_ripristina_dati_gaming_utente(), agganciata
 * a gs_ajax_utente_stato().
 */
add_action( 'wp_ajax_gs_cestino_sfogline', 'gs_ajax_cestino_sfogline' );
function gs_ajax_cestino_sfogline() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$utenti = gs_utenti_sfogline_cestino();
	$intro  = '<p class="gs-hint">Sfogline Rifiutate, Sospese o Eliminate: tolte dall\'elenco principale delle sfogline, ma sempre qui se serve controllarle o farle tornare Approvate. I loro dati del percorso (punti, streak, badge, squadra, classifica…) sono archiviati, non cancellati: tornando Approvata da qui, li ritrovano esattamente come li avevano lasciati.</p>';

	wp_send_json_success( array(
		'html'   => $intro . gs_tabella_sfogline_html( $utenti, 'gs-tabella-cestino-sfogline' ),
		'titolo' => 'Cestino sfogline (' . count( $utenti ) . ')',
	) );
}

/** Apre la scheda personale completa di UNA sfoglina, per nome cliccato dall'elenco. */
add_action( 'wp_ajax_gs_scheda_sfoglina', 'gs_ajax_scheda_sfoglina' );
function gs_ajax_scheda_sfoglina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$uid = isset( $_POST['uid'] ) ? (int) $_POST['uid'] : 0;
	$u   = $uid ? get_user_by( 'id', $uid ) : null;
	if ( ! $u ) { wp_send_json_error( array( 'message' => 'Sfoglina non trovata.' ) ); }
	wp_send_json_success( array( 'html' => gs_sfoglina_dossier_html( $u ) ) );
}

/**
 * Attiva/disattiva/"elimina" (nasconde, mai cancellazione vera) l'account di
 * una sfoglina dalla sua scheda. Riusa lo stato gs_status già esistente
 * (in_attesa/approvata/rifiutata…): 'sospesa' la nasconde ovunque restando
 * recuperabile, 'eliminata' fa lo stesso in modo più marcato — in entrambi i
 * casi l'account WordPress vero non viene mai toccato con wp_delete_user(),
 * per restare coerenti con la regola del progetto "mai cancellazione
 * definitiva" (vale per i contenuti, e qui per gli account allo stesso modo).
 */
add_action( 'wp_ajax_gs_utente_stato', 'gs_ajax_utente_stato' );
function gs_ajax_utente_stato() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$uid   = isset( $_POST['uid'] ) ? (int) $_POST['uid'] : 0;
	$esito = isset( $_POST['esito'] ) ? sanitize_key( wp_unslash( $_POST['esito'] ) ) : '';
	$u     = $uid ? get_user_by( 'id', $uid ) : null;
	if ( ! $u || ! in_array( $esito, array( 'approvata', 'sospesa', 'eliminata' ), true ) ) {
		wp_send_json_error( array( 'message' => 'Dati non validi.' ) );
	}
	// Unica protezione reale: non disattivarsi/eliminarsi da soli (rischio di
	// restare fuori dal pannello). Docenti, collaboratori e altri account con
	// permessi da gestore SI possono disattivare/eliminare da qui — è
	// esattamente il caso d'uso per gli account di prova con ruolo Editor/Admin.
	if ( $uid === get_current_user_id() ) {
		wp_send_json_error( array( 'message' => 'Non puoi disattivare o eliminare il tuo stesso account da qui.' ) );
	}
	update_user_meta( $uid, 'gs_status', 'approvata' === $esito ? '' : $esito );
	// Entra nel Cestino: archivia i dati di gioco. Ne esce (torna Approvata):
	// li ripristina com'erano — vedi le due funzioni in cima a questo file.
	if ( in_array( $esito, array( 'sospesa', 'eliminata' ), true ) ) {
		gs_archivia_dati_gaming_utente( $uid );
	} elseif ( 'approvata' === $esito ) {
		gs_ripristina_dati_gaming_utente( $uid );
	}
	if ( function_exists( 'gs_cache_bump' ) ) { gs_cache_bump(); }
	$etichette = array( 'approvata' => 'riattivato', 'sospesa' => 'disattivato', 'eliminata' => 'eliminato' );
	wp_send_json_success( array( 'message' => 'Account ' . $etichette[ $esito ] . '.', 'html' => gs_sfoglina_dossier_html( $u ) ) );
}

/** Salva le modifiche anagrafiche (nome, email, squadra, data di nascita) fatte dal gestore. */
add_action( 'wp_ajax_gs_utente_modifica', 'gs_ajax_utente_modifica' );
function gs_ajax_utente_modifica() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$uid = isset( $_POST['uid'] ) ? (int) $_POST['uid'] : 0;
	$u   = $uid ? get_user_by( 'id', $uid ) : null;
	if ( ! $u ) { wp_send_json_error( array( 'message' => 'Sfoglina non trovata.' ) ); }

	$nome     = gs_clean( $_POST['nome'] ?? '' );
	$email    = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$squadra  = gs_clean( $_POST['squadra'] ?? '' );
	$nascita  = isset( $_POST['nascita'] ) ? sanitize_text_field( wp_unslash( $_POST['nascita'] ) ) : '';
	$titolo_onorario = gs_clean( $_POST['titolo_onorario'] ?? '' );

	if ( ! $nome ) { wp_send_json_error( array( 'message' => 'Il nome non può restare vuoto.' ) ); }
	if ( $email && ! is_email( $email ) ) { wp_send_json_error( array( 'message' => 'Email non valida.' ) ); }
	if ( $email && email_exists( $email ) && (int) email_exists( $email ) !== $uid ) {
		wp_send_json_error( array( 'message' => 'Questa email è già usata da un altro account.' ) );
	}

	wp_update_user( array( 'ID' => $uid, 'display_name' => $nome, 'first_name' => $nome, 'user_email' => $email ? $email : $u->user_email ) );
	update_user_meta( $uid, 'gs_team', $squadra );
	if ( $nascita && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $nascita ) ) {
		update_user_meta( $uid, 'gs_birthdate', $nascita );
	} elseif ( '' === $nascita ) {
		delete_user_meta( $uid, 'gs_birthdate' );
	}
	if ( $titolo_onorario ) {
		update_user_meta( $uid, 'gs_titolo_onorario', $titolo_onorario );
	} else {
		delete_user_meta( $uid, 'gs_titolo_onorario' );
	}
	if ( function_exists( 'gs_cache_bump' ) ) { gs_cache_bump(); }

	$u = get_user_by( 'id', $uid );
	wp_send_json_success( array( 'message' => 'Dati aggiornati.', 'html' => gs_sfoglina_dossier_html( $u ) ) );
}

/**
 * Eccezione "conta come sfoglina": un docente/admin/account di prova compare
 * comunque negli elenchi pubblici di sfogline (Le Sfogline, caroselli,
 * classifica…) pur restando Editor/Amministratore/Collaboratore — vedi
 * gs_e_sfoglina_vera() in helpers.php. Non tocca ruolo o permessi wp-admin.
 */
add_action( 'wp_ajax_gs_conta_come_sfoglina', 'gs_ajax_conta_come_sfoglina' );
function gs_ajax_conta_come_sfoglina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$uid = isset( $_POST['uid'] ) ? (int) $_POST['uid'] : 0;
	$u   = $uid ? get_user_by( 'id', $uid ) : null;
	if ( ! $u ) { wp_send_json_error( array( 'message' => 'Utente non trovato.' ) ); }
	$attiva = ! empty( $_POST['attiva'] );
	if ( $attiva ) {
		update_user_meta( $uid, 'gs_conta_come_sfoglina', '1' );
	} else {
		delete_user_meta( $uid, 'gs_conta_come_sfoglina' );
	}
	if ( function_exists( 'gs_cache_bump' ) ) { gs_cache_bump(); }
	wp_send_json_success( array( 'message' => $attiva ? 'Ora compare tra le sfogline.' : 'Non compare più tra le sfogline.' ) );
}

/**
 * Attiva/disattiva la Vetrina pubblica di QUALSIASI sfoglina dalla sua scheda
 * personale, SENZA spendere token — diverso da gs_ajax_vetrina_attiva_token()
 * (shortcodes.php), che agisce solo su chi è collegato e le spende dal suo
 * saldo. Pensata per il gestore: attivarla a mano per un collaboratore/docente
 * o come regalo (Ennio, 16/08/2026).
 */
add_action( 'wp_ajax_gs_vetrina_admin_attiva', 'gs_ajax_vetrina_admin_attiva' );
function gs_ajax_vetrina_admin_attiva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$uid = isset( $_POST['uid'] ) ? (int) $_POST['uid'] : 0;
	$u   = $uid ? get_user_by( 'id', $uid ) : null;
	if ( ! $u ) { wp_send_json_error( array( 'message' => 'Utente non trovato.' ) ); }
	$attiva = ! empty( $_POST['attiva'] );
	if ( $attiva ) {
		update_user_meta( $uid, 'gs_vetrina_token_attiva', '1' );
	} else {
		delete_user_meta( $uid, 'gs_vetrina_token_attiva' );
	}
	if ( function_exists( 'gs_cache_bump' ) ) { gs_cache_bump(); }
	wp_send_json_success( array( 'message' => $attiva ? 'Vetrina attivata.' : 'Vetrina disattivata.' ) );
}

add_action( 'wp_ajax_gs_fe_cerca_sfoglina', 'gs_ajax_fe_cerca_sfoglina' );
function gs_ajax_fe_cerca_sfoglina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Permesso negato.' ) );
	}
	$q = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';
	if ( strlen( $q ) < 2 ) {
		wp_send_json_error( array( 'message' => 'Scrivi almeno 2 caratteri.' ) );
	}

	$utenti = get_users( array(
		'search'         => '*' . $q . '*',
		'search_columns' => array( 'user_login', 'user_email', 'display_name', 'user_nicename' ),
		'number'         => 10,
	) );

	if ( empty( $utenti ) ) {
		wp_send_json_success( array( 'html' => '<p>Nessuna sfoglina trovata per «' . esc_html( $q ) . '».</p>' ) );
	}

	ob_start();
	foreach ( $utenti as $u ) {
		echo gs_sfoglina_dossier_html( $u );
	}
	wp_send_json_success( array( 'html' => ob_get_clean() ) );
}

/**
 * Scheda personale completa di una sfoglina, per chi gestisce il pannello:
 * dati anagrafici, attivazioni (abbonamento, Vetrina, token), il suo
 * percorso fino a questo momento, Vetrina/biografia/lavori/cestino già
 * esistenti, e le azioni (modifica, attiva/disattiva, elimina). Riusata sia
 * dalla ricerca (gs_ajax_fe_cerca_sfoglina) sia dall'apertura diretta di una
 * scheda dall'elenco completo (gs_ajax_scheda_sfoglina).
 */
function gs_sfoglina_dossier_html( $u ) {
	$works = gs_get_user_works( $u->ID );
	$trash = gs_get_user_trash( $u->ID );
	$level = gs_get_level( $u->ID );
	$bio   = function_exists( 'gs_bio_get' ) ? gs_bio_get( $u->ID ) : array( 'testo' => '', 'media' => array(), 'stato' => 'vuota' );
	$genere_attuale = gs_genere( $u->ID );
	$stato_account  = get_user_meta( $u->ID, 'gs_status', true );
	$etichette_stato = array( '' => 'Mai iscritta dal sito', 'approvata' => 'Approvata', 'in_attesa' => 'In attesa', 'rifiutata' => 'Rifiutata', 'sospesa' => 'Sospesa', 'eliminata' => 'Eliminata (nascosta)' );

	ob_start();
	echo '<div class="gs-sfoglina-blocco" id="gs-scheda-' . (int) $u->ID . '" style="margin-bottom:24px">';
	echo '<h4 style="margin:0 0 6px">' . esc_html( $u->display_name ) . ' <span class="gs-hint">(' . esc_html( $u->user_login ) . ' — ' . esc_html( $level['simbolo'] . ' ' . $level['titolo'] ) . ')</span></h4>';

	// --- Dati anagrafici + modifica ---
	echo '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">📇 Dati anagrafici <span class="gs-msg-data">clicca per modificare</span></summary>';
	echo '<div class="gs-inbox-corpo">';
	echo '<form class="gs-form gs-form-utente-modifica" data-uid="' . (int) $u->ID . '" onsubmit="return false">';
	echo '<p><label>Nome e cognome<br><input type="text" name="nome" value="' . esc_attr( $u->display_name ) . '" style="width:100%"></label></p>';
	echo '<p><label>Email<br><input type="email" name="email" value="' . esc_attr( $u->user_email ) . '" style="width:100%"></label></p>';
	echo '<p><label>Squadra<br><select name="squadra"><option value="">— nessuna —</option>';
	foreach ( gs_get_teams() as $t ) { echo '<option value="' . esc_attr( $t ) . '"' . selected( get_user_meta( $u->ID, 'gs_team', true ), $t, false ) . '>' . esc_html( $t ) . '</option>'; }
	echo '</select></label> ';
	echo '<label>Data di nascita<br><input type="date" name="nascita" value="' . esc_attr( get_user_meta( $u->ID, 'gs_birthdate', true ) ) . '"></label></p>';
	echo '<p><label>Titolo onorario<br><input type="text" name="titolo_onorario" value="' . esc_attr( get_user_meta( $u->ID, 'gs_titolo_onorario', true ) ) . '" style="width:100%" placeholder="es. Socio Onorario"></label><br><span class="gs-hint">Se scritto, sostituisce il livello mostrato in ogni scheda del sito (🏵️ + questo testo, al posto di punti/livello guadagnati) — lascia vuoto per il livello normale.</span></p>';
	echo '<p class="gs-hint">Username: ' . esc_html( $u->user_login ) . ' (non modificabile) — registrata il ' . esc_html( date_i18n( 'd/m/Y', strtotime( $u->user_registered ) ) ) . '</p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-utente-modifica-salva">Salva modifiche</button> <span class="gs-utente-modifica-msg gs-richiesta-esito"></span></p>';
	echo '</form>';
	echo '</div></details>';

	// --- Attivazioni ---
	echo '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">🔌 Attivazioni <span class="gs-msg-data">' . esc_html( $etichette_stato[ $stato_account ] ?? $stato_account ) . '</span></summary>';
	echo '<div class="gs-inbox-corpo">';
	echo '<ul class="gs-todo-list">';
	$abb_scaduto = function_exists( 'gs_abbonamento_scaduto' ) && gs_abbonamento_scaduto( $u->ID );
	$abb_scadenza = get_user_meta( $u->ID, 'gs_abbonamento_scadenza', true );
	echo '<li class="gs-todo-item"><span>Abbonamento: ' . ( $abb_scaduto ? '<strong style="color:#b23223">Scaduto</strong>' : 'Attivo' ) . ( $abb_scadenza ? ' (scadenza ' . esc_html( gs_data_it( $abb_scadenza ) ) . ')' : ' (nessuna scadenza impostata)' ) . '</span></li>';
	$vetrina_attiva = function_exists( 'gs_vetrina_token_attivata' ) && gs_vetrina_token_attivata( $u->ID );
	echo '<li class="gs-todo-item"><span>Vetrina pubblica: ' . ( $vetrina_attiva ? '<strong style="color:#2f8f5b">Attiva</strong> (a token)' : 'Non attivata' ) . ( function_exists( 'gs_vetrina_bloccata' ) && gs_vetrina_bloccata( $u->ID ) ? ' — bloccata dall\'amministrazione' : '' ) . '</span></li>';
	echo '<li class="gs-todo-item"><span>Saldo token: ' . (int) ( function_exists( 'gs_token_saldo' ) ? gs_token_saldo( $u->ID ) : 0 ) . '</span></li>';
	echo '</ul>';

	// "Abilitata come sfoglina": l'interruttore principale, disponibile per
	// OGNI account (prima solo per Editor/Amministratori). È il sì definitivo
	// che fa comparire una persona in "Le Sfogline", nei caroselli e nella
	// Classifica anche se non è mai passata dal modulo pubblico di iscrizione,
	// e senza toccarne ruolo o permessi wp-admin.
	$conta_come = '1' === get_user_meta( $u->ID, 'gs_conta_come_sfoglina', true );
	$compare    = gs_e_sfoglina_vera( $u );
	echo '<p class="gs-conta-sfoglina-riga" data-uid="' . (int) $u->ID . '" style="margin-top:10px">';
	echo '<label><input type="checkbox" class="gs-conta-come-sfoglina" ' . checked( $conta_come, true, false ) . '> <strong>Abilitata come sfoglina</strong> — compare in "Le Sfogline", nei caroselli e in Classifica. Non tocca ruolo né permessi wp-admin.</label> ';
	echo '<span class="gs-conta-sfoglina-msg gs-richiesta-esito"></span>';
	echo '<br><span class="gs-hint">Stato attuale nelle pagine pubbliche: <strong>' . ( $compare ? 'compare' : 'non compare' ) . '</strong>.</span></p>';

	// Attivazione della Vetrina SENZA spendere token: pensata per il gestore,
	// diversa dal pulsante che la sfoglina stessa usa nel suo pannello (quello
	// spende 5 token dal suo saldo) — qui serve per attivarla a mano, es. per
	// collaboratori/docenti o come regalo (Ennio, 16/08/2026).
	echo '<p class="gs-vetrina-admin-riga" data-uid="' . (int) $u->ID . '" style="margin-top:10px">';
	echo '<label><input type="checkbox" class="gs-vetrina-admin-attiva" ' . checked( $vetrina_attiva, true, false ) . '> <strong>Vetrina pubblica attiva</strong> — accesa da qui senza spendere token, indipendente dal saldo.</label> ';
	echo '<span class="gs-vetrina-admin-msg gs-richiesta-esito"></span>';
	echo '</p>';
	echo '</div></details>';

	// --- Il suo percorso finora ---
	$lezioni_viste = get_user_meta( $u->ID, 'gs_lezioni_viste', true );
	$lezioni_n = is_array( $lezioni_viste ) ? count( $lezioni_viste ) : 0;
	$badges_n = count( function_exists( 'gs_get_user_badges' ) ? gs_get_user_badges( $u->ID ) : array() );
	$ricette_n = 0;
	if ( function_exists( 'gs_ricette_approvate' ) ) {
		foreach ( gs_ricette_approvate() as $r ) { if ( (int) $r->post_author === (int) $u->ID ) { $ricette_n++; } }
	}
	echo '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">🧭 Il suo percorso finora</summary>';
	echo '<div class="gs-inbox-corpo"><ul class="gs-todo-list">';
	echo '<li class="gs-todo-item"><span>Livello: ' . esc_html( $level['simbolo'] . ' ' . $level['titolo'] ) . ' — ' . (int) $level['punti'] . ' punti</span></li>';
	echo '<li class="gs-todo-item"><span>Streak: ' . (int) gs_get_streak( $u->ID ) . ' settimane consecutive</span></li>';
	echo '<li class="gs-todo-item"><span>Badge sbloccati: ' . (int) $badges_n . '</span></li>';
	echo '<li class="gs-todo-item"><span>Lezioni video viste: ' . (int) $lezioni_n . '</span></li>';
	echo '<li class="gs-todo-item"><span>Ricette approvate nel Ricettario: ' . (int) $ricette_n . '</span></li>';
	echo '<li class="gs-todo-item"><span>Lavori e sfide inviati: ' . count( $works ) . '</span></li>';
	echo '</ul></div></details>';

	echo '<p class="gs-genere-riga" data-user="' . (int) $u->ID . '">Genere: '
		. '<select class="gs-genere-select">'
		. '<option value="f"' . selected( $genere_attuale, 'f', false ) . '>Sfoglina</option>'
		. '<option value="m"' . selected( $genere_attuale, 'm', false ) . '>Sfoglino</option>'
		. '</select> '
		. '<button class="gs-btn gs-btn-sm gs-genere-salva">Salva</button> '
		. '<span class="gs-genere-msg gs-richiesta-esito"></span></p>';

	// Vetrina pubblica.
	if ( function_exists( 'gs_vetrina_disponibile' ) && gs_vetrina_disponibile( $u->ID ) ) {
		echo '<p><a class="gs-btn gs-btn-sm" href="' . esc_url( gs_vetrina_url( $u->ID ) ) . '" target="_blank">🔗 Vedi la sua Vetrina pubblica</a></p>';
	} else {
		echo '<p class="gs-hint">Vetrina non disponibile per questa sfoglina.</p>';
	}

	// Biografia (anche se non ancora approvata: il titolare deve poterla vedere comunque).
	$stati_bio = array( 'vuota' => 'non ancora compilata', 'in_attesa' => 'in attesa di approvazione', 'approvata' => 'approvata e pubblica' );
	echo '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">📖 Biografia <span class="gs-msg-data">' . esc_html( $stati_bio[ $bio['stato'] ] ?? $bio['stato'] ) . '</span></summary>';
	echo '<div class="gs-inbox-corpo">';
	$foto_bio = function_exists( 'gs_bio_foto_url' ) ? gs_bio_foto_url( $u->ID ) : '';
	if ( $foto_bio ) { echo '<img class="gs-profile-foto" src="' . esc_url( $foto_bio ) . '" alt="">'; }
	echo trim( $bio['testo'] ) ? '<div class="gs-bio-testo">' . nl2br( esc_html( $bio['testo'] ) ) . '</div>' : '<p class="gs-hint">Nessun testo scritto.</p>';
	if ( ! empty( $bio['media'] ) ) {
		echo '<div class="gs-bio-media-wrap">';
		foreach ( $bio['media'] as $m ) { echo gs_msg_media_html( $m['url'], isset( $m['type'] ) ? $m['type'] : 'image' ); }
		echo '</div>';
	}
	echo '</div></details>';

	echo '<strong>Lavori e sfide (' . count( $works ) . ')</strong>';
	if ( $works ) {
		echo '<div class="gs-inbox-lista gs-paginate" data-per-page="8">';
		foreach ( $works as $p ) { echo gs_dossier_riga( $p, 'cestina' ); }
		echo '</div>';
	} else { echo '<p class="gs-hint">Nessun lavoro pubblicato.</p>'; }

	echo '<div class="gs-sezione-cestino">';
	echo '<strong class="gs-titolo-cestino">🗑️ Cestino (' . count( $trash ) . ')</strong>';
	if ( $trash ) {
		echo '<div class="gs-inbox-lista gs-paginate" data-per-page="8">';
		foreach ( $trash as $p ) { echo gs_dossier_riga( $p, 'ripristina' ); }
		echo '</div>';
	} else { echo '<p class="gs-hint">Cestino vuoto.</p>'; }
	echo '</div>';

	// --- Azioni sull'account ---
	echo '<div class="gs-sfoglina-azioni" style="margin-top:14px;padding-top:12px;border-top:1px solid var(--gs-bordo,#e4d8bb)">';
	if ( 'sospesa' === $stato_account ) {
		echo '<button class="gs-btn gs-btn-sm gs-btn-verde gs-utente-stato" data-uid="' . (int) $u->ID . '" data-esito="approvata">Riattiva account</button> ';
	} else {
		echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-utente-stato" data-uid="' . (int) $u->ID . '" data-esito="sospesa">Disattiva account</button> ';
	}
	echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-utente-elimina" data-uid="' . (int) $u->ID . '">🗑️ Elimina</button> ';
	echo '<span class="gs-utente-stato-msg gs-richiesta-esito"></span>';
	echo '<p class="gs-hint" style="margin-top:6px">"Disattiva" nasconde l\'account da tutto il sito senza cancellarlo (come un blackout personale, reversibile). "Elimina" fa lo stesso ma in modo più marcato: l\'account WordPress NON viene mai cancellato per davvero (nessuna perdita di dati), resta sempre recuperabile riattivandolo da qui.</p>';
	echo '</div>';

	echo '</div>';
	return ob_get_clean();
}

/** Correzione manuale del genere di una sfoglina/sfoglino da parte del gestore. */
add_action( 'wp_ajax_gs_imposta_genere', 'gs_ajax_imposta_genere' );
function gs_ajax_imposta_genere() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Permesso negato.' ) );
	}
	$uid    = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;
	$genere = ( 'm' === ( $_POST['genere'] ?? '' ) ) ? 'm' : 'f';
	if ( ! $uid || ! get_userdata( $uid ) ) {
		wp_send_json_error( array( 'message' => 'Utente non trovato.' ) );
	}
	update_user_meta( $uid, 'gs_genere', $genere );
	wp_send_json_success( array( 'message' => 'Genere aggiornato: ' . ( 'm' === $genere ? 'sfoglino' : 'sfoglina' ) . '.' ) );
}

add_shortcode( 'gs_sfogline', 'gs_sc_sfogline' );
/** Ultima parola di un nome visualizzato ("Maria Rossi" → "Rossi"), per ordinare per cognome senza un campo dedicato. */
function gs_cognome_da_nome( $nome_completo ) {
	$parti = preg_split( '/\s+/', trim( (string) $nome_completo ) );
	return $parti ? end( $parti ) : $nome_completo;
}

function gs_sc_sfogline() {
	if ( $g = gs_blackout_gate() ) { return $g; }
	if ( function_exists( 'gs_sez_collab_pagina_ok' ) && ! gs_sez_collab_pagina_ok( 'sfogline' ) ) { return '<div class="gs-box"><p>Questa sezione non è disponibile al momento.</p></div>'; }

	$cfg  = gs_sfogline_cfg();
	$sfogline = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) );

	// Le sfogline con la Vetrina attivata (a token) salgono in cima, ordinate
	// per cognome (l'ultima parola del nome visualizzato — non c'è un campo
	// cognome separato in registrazione); le altre restano sotto, nell'ordine
	// consueto per nome. Le schede di chi non ha attivato la Vetrina restano
	// senza alcun link: non c'è nulla da aprire, per come già funziona
	// gs_vetrina_disponibile() (richiesto da Ennio il 2026-08-11).
	$attive = array();
	$altre  = array();
	foreach ( $sfogline as $u ) {
		if ( gs_vetrina_disponibile( $u->ID ) ) { $attive[] = $u; } else { $altre[] = $u; }
	}
	usort( $attive, function ( $a, $b ) {
		return strcasecmp( gs_cognome_da_nome( $a->display_name ), gs_cognome_da_nome( $b->display_name ) );
	} );
	$sfogline = array_merge( $attive, $altre );

	$out  = gs_box_open( '👭 Le Sfogline' );
	$out .= gs_sezione_aiuto( 'Cerca una sfoglina per nome, oppure filtra per livello. In cima trovi le sfogline con la Vetrina pubblica attiva, ordinate per cognome: clicca sulla loro scheda per aprirla. Le altre schede sono di sola consultazione.' );
	$out .= '<p class="gs-hint">Tutte le sfogline della community con i loro traguardi e i lavori più recenti.</p>';
	$out .= '<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px">';
	$out .= '<input type="text" class="gs-cerca-input" data-target="#gs-elenco-sfogline" placeholder="🔍 Cerca una sfoglina…" style="flex:1;min-width:200px;max-width:360px">';
	$out .= '<select class="gs-filtro-livello" data-target="#gs-elenco-sfogline" style="max-width:220px">';
	$out .= '<option value="">Tutti i livelli</option>';
	foreach ( gs_settings()['levels'] as $i => $lv ) {
		$out .= '<option value="' . (int) $i . '">' . esc_html( $lv['simbolo'] . ' ' . $lv['titolo'] ) . '</option>';
	}
	$out .= '</select>';
	$out .= '</div>';
	$cls  = 'gs-gallery gs-paginate gs-cols-' . (int) $cfg['per_riga'] . ( 'piccola' === $cfg['scheda'] ? ' gs-card-small' : '' );
	$out .= '<div class="' . esc_attr( $cls ) . '" data-per-page="' . (int) $cfg['per_pagina'] . '" id="gs-elenco-sfogline">';

	foreach ( $sfogline as $u ) {
		if ( ! gs_e_sfoglina_vera( $u ) ) { continue; }
		$level  = gs_get_level( $u->ID );
		$streak = gs_get_streak( $u->ID );
		$badges = function_exists( 'gs_get_user_badges' ) ? gs_get_user_badges( $u->ID ) : array();
		// La foto della scheda è SOLO quella della biografia (richiesto da
		// Ennio il 17/08/2026): prima veniva presa dall'ultimo lavoro
		// caricato in "gs_sfoglia", che poteva mostrare una foto qualsiasi
		// invece del volto della sfoglina.
		$thumb = function_exists( 'gs_bio_foto_url' ) ? gs_bio_foto_url( $u->ID ) : '';

		$attiva = gs_vetrina_disponibile( $u->ID );
		$out .= '<div class="gs-card' . ( $attiva ? ' gs-card-apribile' : '' ) . '" data-nome="' . esc_attr( strtolower( $u->display_name ) ) . '" data-livello="' . (int) $level['index'] . '">';
		// Testata (colore diverso dal corpo): foto oppure emoji su fondo beige.
		if ( $thumb ) {
			$out .= '<div class="gs-card-head" style="background-image:url(' . esc_url( $thumb ) . ')"></div>';
		} else {
			$out .= '<div class="gs-card-head gs-card-head-ph">' . esc_html( $level['simbolo'] ? $level['simbolo'] : '🧑‍🍳' ) . '</div>';
		}
		$out .= '<div class="gs-card-body">';
		$out .= '<p class="gs-card-author">' . esc_html( $level['titolo'] ) . '</p>';
		$out .= '<h4>' . esc_html( $u->display_name ) . '</h4>';
		$out .= '<p class="gs-card-media">🔥 ' . (int) $streak . ' sett. · 🎖️ ' . count( $badges ) . ' badge · ' . (int) get_user_meta( $u->ID, 'gs_points', true ) . ' punti</p>';
		$out .= '</div>';
		if ( $attiva ) {
			$out .= '<div class="gs-card-foot"><a class="gs-btn gs-btn-sm" href="' . esc_url( gs_vetrina_url( $u->ID ) ) . '">Vedi la vetrina</a></div>';
		}
		$out .= '</div>';
	}
	$out .= '</div>';
	$out .= gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// Impostazioni di visualizzazione della pagina "Le Sfogline"
// -----------------------------------------------------------------------------
function gs_sfogline_cfg() {
	$s = gs_settings();
	$d = array( 'scheda' => 'normale', 'per_riga' => 3, 'per_pagina' => 12 );
	$c = isset( $s['sfogline_view'] ) && is_array( $s['sfogline_view'] ) ? $s['sfogline_view'] : array();
	return wp_parse_args( $c, $d );
}

function gs_pannello_sfogline_view() {
	if ( ! gs_can_manage() ) { return; }
	$cfg = gs_sfogline_cfg();
	echo gs_box_open( 'Aspetto della pagina «Le Sfogline»' );
	echo '<p class="gs-hint">Scegli la dimensione delle schede, quante schede mostrare per riga e quante per pagina (in fondo alla pagina compaiono i comandi di paginazione e il pulsante «Vedi tutti»).</p>';
	?>
	<form class="gs-form gs-form-sfview" onsubmit="return false">
		<p style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end">
			<label>Dimensione schede<br>
				<select name="scheda">
					<option value="normale" <?php selected( $cfg['scheda'], 'normale' ); ?>>Normale</option>
					<option value="piccola" <?php selected( $cfg['scheda'], 'piccola' ); ?>>Piccola</option>
				</select>
			</label>
			<label>Schede per riga<br>
				<select name="per_riga">
					<?php foreach ( array( 2, 3, 4, 5, 6 ) as $n ) : ?>
						<option value="<?php echo (int) $n; ?>" <?php selected( (int) $cfg['per_riga'], $n ); ?>><?php echo (int) $n; ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>Schede per pagina<br>
				<input type="number" name="per_pagina" min="4" max="60" value="<?php echo (int) $cfg['per_pagina']; ?>" style="width:90px">
			</label>
			<button class="gs-btn gs-btn-sm gs-btn-verde gs-sfview-salva">Salva</button>
			<span class="gs-sfview-msg gs-richiesta-esito"></span>
		</p>
	</form>
	<?php
	echo gs_box_close();
}

add_action( 'wp_ajax_gs_sfview_salva', 'gs_ajax_sfview_salva' );
function gs_ajax_sfview_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$s = gs_settings();
	$s['sfogline_view'] = array(
		'scheda'     => ( isset( $_POST['scheda'] ) && 'piccola' === $_POST['scheda'] ) ? 'piccola' : 'normale',
		'per_riga'   => max( 2, min( 6, (int) ( $_POST['per_riga'] ?? 3 ) ) ),
		'per_pagina' => max( 4, min( 60, (int) ( $_POST['per_pagina'] ?? 12 ) ) ),
	);
	update_option( GS_OPTION, $s );
	wp_send_json_success( array( 'message' => 'Aspetto salvato.' ) );
}
