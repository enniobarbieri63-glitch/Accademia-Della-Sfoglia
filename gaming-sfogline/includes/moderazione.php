<?php
/**
 * moderazione.php — pannello unico per moderare TUTTE le chat del progetto,
 * non solo quelle di una singola sezione. Richiesto da Ennio dopo "Le
 * Letture": lui e i collaboratori con accesso al pannello devono poter
 * controllare (e, se serve, eliminare) i messaggi scritti ovunque, senza
 * dover aprire sette pannelli diversi uno per uno.
 *
 * Non sostituisce "Messaggi di ogni sfoglina" (messaggi.php), che resta la
 * vista per sfogliare tutto lo storico di UNA sfoglina alla volta: questo è
 * invece un flusso "cosa è stato scritto di recente, ovunque", con un
 * pulsante Elimina per ogni riga.
 *
 * Ogni sistema salva le sue conversazioni in modo diverso (array di
 * meta con chiavi diverse, alcuni annidati per utente, altri con un CPT per
 * ogni messaggio): per questo ogni sistema ha una sua piccola funzione che
 * legge i messaggi e una che li elimina, invece di un'unica funzione
 * generica che non potrebbe conoscere la forma dei dati di ognuno.
 *
 * Eliminare un messaggio qui NON cancella mai nulla per sempre: per i
 * sistemi ad array (conversazioni, messaggi, posta interna, aiuto,
 * calendario) il messaggio si sposta in un array "_cestino" gemello sullo
 * stesso post; per gli auguri di compleanno (post interi) si usa
 * wp_trash_post(), come da regola del progetto.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gs_mod_sistemi() {
	return array(
		'conversazioni' => 'Conversazioni private',
		'messaggi'      => 'Messaggi privati alle sfogline',
		'inbox'         => 'Posta interna',
		'aiuto'         => 'Aiuto e Suggerimenti',
		'calendario'    => "Calendario Corsi (messaggi con l'Accademia)",
		'compleanni'    => 'Auguri di compleanno',
		'letture'       => 'Le Letture',
	);
}

function gs_mod_riga( $sistema, $post_id, $indice, $autore_nome, $testo, $time, $url ) {
	return array(
		'sistema'      => $sistema,
		'sistema_label' => gs_mod_sistemi()[ $sistema ] ?? $sistema,
		'post'         => (int) $post_id,
		'indice'       => (string) $indice,
		'autore'       => $autore_nome,
		'testo'        => (string) $testo,
		'time'         => (int) $time,
		'url'          => $url,
	);
}

function gs_mod_admin_link( $ancora = '' ) {
	if ( ! function_exists( 'admin_url' ) ) { return '#'; }
	return admin_url( 'admin.php?page=gs-generale' ) . ( $ancora ? '#' . $ancora : '' );
}

function gs_mod_feed_conversazioni() {
	$righe = array();
	foreach ( get_posts( array( 'post_type' => 'gs_conversazione', 'post_status' => 'publish', 'posts_per_page' => -1 ) ) as $c ) {
		foreach ( gs_conv_msgs( $c->ID ) as $idx => $m ) {
			$righe[] = gs_mod_riga( 'conversazioni', $c->ID, $idx, $m['nome'] ?? '—', $m['testo'] ?? '', (int) ( $m['time'] ?? 0 ), gs_mod_admin_link( 'gs-zona-conversazioni' ) );
		}
	}
	return $righe;
}

function gs_mod_feed_messaggi() {
	$righe = array();
	foreach ( get_posts( array( 'post_type' => 'gs_messaggio', 'post_status' => 'publish', 'posts_per_page' => -1 ) ) as $m ) {
		$tutte = get_post_meta( $m->ID, 'gs_risposte', true );
		if ( ! is_array( $tutte ) ) { continue; }
		foreach ( $tutte as $thread_uid => $lista ) {
			if ( ! is_array( $lista ) ) { continue; }
			foreach ( $lista as $idx => $r ) {
				$au = get_userdata( (int) ( $r['autore'] ?? 0 ) );
				$righe[] = gs_mod_riga( 'messaggi', $m->ID, $thread_uid . ':' . $idx, $au ? $au->display_name : '—', $r['testo'] ?? '', strtotime( $r['data'] ?? '' ), gs_mod_admin_link( 'gs-zona-messaggi' ) );
			}
		}
	}
	return $righe;
}

function gs_mod_feed_inbox() {
	$righe = array();
	foreach ( get_posts( array( 'post_type' => 'gs_msg_interno', 'post_status' => 'publish', 'posts_per_page' => -1 ) ) as $m ) {
		$thread = get_post_meta( $m->ID, 'gs_thread', true );
		if ( ! is_array( $thread ) ) { continue; }
		foreach ( $thread as $idx => $t ) {
			$righe[] = gs_mod_riga( 'inbox', $m->ID, $idx, $t['nome'] ?? '—', $t['testo'] ?? '', (int) ( $t['time'] ?? 0 ), gs_mod_admin_link() );
		}
	}
	return $righe;
}

function gs_mod_feed_aiuto() {
	$righe = array();
	foreach ( gs_solo_tipo( get_posts( array( 'post_type' => 'gs_aiuto', 'post_status' => 'publish', 'posts_per_page' => -1, 'suppress_filters' => true ) ), 'gs_aiuto' ) as $a ) {
		$risposte = get_post_meta( $a->ID, 'gs_aiuto_risposte', true );
		if ( ! is_array( $risposte ) ) { continue; }
		foreach ( $risposte as $idx => $r ) {
			$au = get_userdata( (int) ( $r['autore'] ?? 0 ) );
			$righe[] = gs_mod_riga( 'aiuto', $a->ID, $idx, $au ? $au->display_name : '—', $r['testo'] ?? '', strtotime( $r['data'] ?? '' ), gs_mod_admin_link( 'gs-zona-aiuto' ) );
		}
	}
	return $righe;
}

function gs_mod_feed_calendario() {
	$righe = array();
	foreach ( get_posts( array( 'post_type' => 'gs_prenotazione', 'post_status' => 'publish', 'posts_per_page' => -1 ) ) as $p ) {
		$msgs = get_post_meta( $p->ID, 'gs_msgs', true );
		if ( ! is_array( $msgs ) ) { continue; }
		foreach ( $msgs as $idx => $m ) {
			$righe[] = gs_mod_riga( 'calendario', $p->ID, $idx, $m['nome'] ?? '—', $m['testo'] ?? '', (int) ( $m['time'] ?? 0 ), gs_mod_admin_link( 'gs-zona-calendario' ) );
		}
	}
	return $righe;
}

function gs_mod_feed_compleanni() {
	$righe = array();
	foreach ( get_posts( array( 'post_type' => 'gs_augurio', 'post_status' => 'publish', 'posts_per_page' => -1 ) ) as $a ) {
		$au = get_userdata( $a->post_author );
		$righe[] = gs_mod_riga( 'compleanni', $a->ID, '', $au ? $au->display_name : '—', $a->post_content, get_post_time( 'U', false, $a ), gs_mod_admin_link( 'gs-zona-compleanni' ) );
	}
	return $righe;
}

function gs_mod_feed_letture() {
	$righe = array();
	foreach ( get_posts( array( 'post_type' => 'gs_lettura', 'post_status' => 'publish', 'posts_per_page' => -1 ) ) as $l ) {
		foreach ( gs_lettura_commenti( $l->ID ) as $c ) {
			$righe[] = gs_mod_riga( 'letture', $l->ID, $c['id'] ?? '', $c['nome'] ?? '—', $c['testo'] ?? '', (int) ( $c['time'] ?? 0 ), gs_mod_admin_link( 'gs-zona-letture' ) );
		}
	}
	return $righe;
}

/**
 * Tutti i messaggi di tutti i sistemi, dal più recente. $sistema filtra a
 * uno solo (vuoto = tutti); $cerca filtra per testo o autore (sottostringa,
 * senza distinguere maiuscole/minuscole).
 */
function gs_mod_feed( $sistema = '', $cerca = '', $limite = 80 ) {
	$funzioni = array(
		'conversazioni' => 'gs_mod_feed_conversazioni',
		'messaggi'      => 'gs_mod_feed_messaggi',
		'inbox'         => 'gs_mod_feed_inbox',
		'aiuto'         => 'gs_mod_feed_aiuto',
		'calendario'    => 'gs_mod_feed_calendario',
		'compleanni'    => 'gs_mod_feed_compleanni',
		'letture'       => 'gs_mod_feed_letture',
	);
	$righe = array();
	foreach ( $funzioni as $k => $fn ) {
		if ( $sistema && $sistema !== $k ) { continue; }
		$righe = array_merge( $righe, call_user_func( $fn ) );
	}
	if ( '' !== trim( (string) $cerca ) ) {
		$q     = function_exists( 'mb_strtolower' ) ? mb_strtolower( $cerca ) : strtolower( $cerca );
		$righe = array_values( array_filter( $righe, function ( $r ) use ( $q ) {
			$hay = ( function_exists( 'mb_strtolower' ) ? mb_strtolower( $r['testo'] . ' ' . $r['autore'] ) : strtolower( $r['testo'] . ' ' . $r['autore'] ) );
			return false !== strpos( $hay, $q );
		} ) );
	}
	usort( $righe, function ( $a, $b ) { return $b['time'] <=> $a['time']; } );
	if ( $limite > 0 ) { $righe = array_slice( $righe, 0, $limite ); }
	return $righe;
}

/**
 * Elimina (spostando nel cestino del suo sistema, o wp_trash_post per i
 * post interi) un singolo messaggio. Ritorna true/false.
 */
function gs_mod_elimina( $sistema, $post_id, $indice ) {
	$post_id = (int) $post_id;
	switch ( $sistema ) {
		case 'conversazioni':
			if ( 'gs_conversazione' !== get_post_type( $post_id ) ) { return false; }
			$msgs = gs_conv_msgs( $post_id );
			if ( ! isset( $msgs[ $indice ] ) ) { return false; }
			$tolto = $msgs[ $indice ];
			unset( $msgs[ $indice ] );
			update_post_meta( $post_id, 'gs_msgs', array_values( $msgs ) );
			$cestino   = get_post_meta( $post_id, 'gs_msgs_cestino', true );
			$cestino   = is_array( $cestino ) ? $cestino : array();
			$cestino[] = $tolto;
			update_post_meta( $post_id, 'gs_msgs_cestino', $cestino );
			return true;

		case 'messaggi':
			if ( 'gs_messaggio' !== get_post_type( $post_id ) ) { return false; }
			$parti = explode( ':', (string) $indice, 2 );
			if ( 2 !== count( $parti ) ) { return false; }
			$thread_uid = $parti[0];
			$idx        = (int) $parti[1];
			$tutte      = get_post_meta( $post_id, 'gs_risposte', true );
			if ( ! is_array( $tutte ) || ! isset( $tutte[ $thread_uid ][ $idx ] ) ) { return false; }
			$tolto = $tutte[ $thread_uid ][ $idx ];
			unset( $tutte[ $thread_uid ][ $idx ] );
			$tutte[ $thread_uid ] = array_values( $tutte[ $thread_uid ] );
			update_post_meta( $post_id, 'gs_risposte', $tutte );
			$cestino = get_post_meta( $post_id, 'gs_risposte_cestino', true );
			$cestino = is_array( $cestino ) ? $cestino : array();
			if ( ! isset( $cestino[ $thread_uid ] ) || ! is_array( $cestino[ $thread_uid ] ) ) { $cestino[ $thread_uid ] = array(); }
			$cestino[ $thread_uid ][] = $tolto;
			update_post_meta( $post_id, 'gs_risposte_cestino', $cestino );
			return true;

		case 'inbox':
			if ( 'gs_msg_interno' !== get_post_type( $post_id ) ) { return false; }
			$thread = get_post_meta( $post_id, 'gs_thread', true );
			if ( ! is_array( $thread ) || ! isset( $thread[ $indice ] ) ) { return false; }
			$tolto = $thread[ $indice ];
			unset( $thread[ $indice ] );
			update_post_meta( $post_id, 'gs_thread', array_values( $thread ) );
			$cestino   = get_post_meta( $post_id, 'gs_thread_cestino', true );
			$cestino   = is_array( $cestino ) ? $cestino : array();
			$cestino[] = $tolto;
			update_post_meta( $post_id, 'gs_thread_cestino', $cestino );
			return true;

		case 'aiuto':
			if ( 'gs_aiuto' !== get_post_type( $post_id ) ) { return false; }
			$risposte = get_post_meta( $post_id, 'gs_aiuto_risposte', true );
			if ( ! is_array( $risposte ) || ! isset( $risposte[ $indice ] ) ) { return false; }
			$tolto = $risposte[ $indice ];
			unset( $risposte[ $indice ] );
			update_post_meta( $post_id, 'gs_aiuto_risposte', array_values( $risposte ) );
			$cestino   = get_post_meta( $post_id, 'gs_aiuto_risposte_cestino', true );
			$cestino   = is_array( $cestino ) ? $cestino : array();
			$cestino[] = $tolto;
			update_post_meta( $post_id, 'gs_aiuto_risposte_cestino', $cestino );
			return true;

		case 'calendario':
			if ( 'gs_prenotazione' !== get_post_type( $post_id ) ) { return false; }
			$msgs = get_post_meta( $post_id, 'gs_msgs', true );
			if ( ! is_array( $msgs ) || ! isset( $msgs[ $indice ] ) ) { return false; }
			$tolto = $msgs[ $indice ];
			unset( $msgs[ $indice ] );
			update_post_meta( $post_id, 'gs_msgs', array_values( $msgs ) );
			$cestino   = get_post_meta( $post_id, 'gs_msgs_cestino', true );
			$cestino   = is_array( $cestino ) ? $cestino : array();
			$cestino[] = $tolto;
			update_post_meta( $post_id, 'gs_msgs_cestino', $cestino );
			return true;

		case 'compleanni':
			if ( 'gs_augurio' !== get_post_type( $post_id ) ) { return false; }
			wp_trash_post( $post_id );
			return true;

		case 'letture':
			return function_exists( 'gs_lettura_commento_rimuovi' ) ? gs_lettura_commento_rimuovi( $post_id, $indice ) : false;
	}
	return false;
}

add_action( 'wp_ajax_gs_mod_elimina', 'gs_ajax_mod_elimina' );
function gs_ajax_mod_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$sistema = isset( $_POST['sistema'] ) ? sanitize_key( wp_unslash( $_POST['sistema'] ) ) : '';
	$post_id = isset( $_POST['post'] ) ? (int) $_POST['post'] : 0;
	$indice  = isset( $_POST['indice'] ) ? sanitize_text_field( wp_unslash( $_POST['indice'] ) ) : '';
	if ( ! gs_mod_elimina( $sistema, $post_id, $indice ) ) { wp_send_json_error( array( 'message' => 'Messaggio non trovato (forse già eliminato).' ) ); }
	wp_send_json_success( array( 'message' => 'Messaggio spostato nel cestino.' ) );
}

function gs_pannello_moderazione() {
	if ( ! gs_can_manage() ) { return; }
	echo gs_box_open( '🛡️ Moderazione di tutte le chat' );
	echo gs_sezione_aiuto( 'Qui compaiono, dal più recente, i messaggi scritti in ogni chat del progetto: Conversazioni, Messaggi privati, Posta interna, Aiuto e Suggerimenti, Calendario Corsi, Auguri di compleanno e i commenti delle Letture. Serve per controllare rapidamente cosa si scrive senza aprire ogni pannello uno per uno. "Elimina" sposta il messaggio nel cestino del suo sistema (mai una cancellazione definitiva): per gli auguri di compleanno, che sono un messaggio a sé, va nel cestino di WordPress. Il link "Vai al pannello" porta dritto alla sezione di origine per vedere il contesto completo dello scambio. Sia il titolare sia i collaboratori con accesso a questo pannello possono moderare.' );

	$sistemi = gs_mod_sistemi();
	echo '<p><label>Filtra per sistema<br><select class="gs-mod-filtro-sistema"><option value="">Tutti</option>';
	foreach ( $sistemi as $k => $label ) { echo '<option value="' . esc_attr( $k ) . '">' . esc_html( $label ) . '</option>'; }
	echo '</select></label></p>';
	echo '<input type="text" class="gs-cerca-input" data-target=".gs-mod-lista" placeholder="🔍 Cerca per testo o autore…" style="width:100%;max-width:420px;margin-bottom:10px">';

	$righe = gs_mod_feed();
	if ( ! $righe ) {
		echo '<p class="gs-hint">Nessun messaggio trovato.</p>' . gs_box_close();
		return;
	}
	echo '<div class="gs-inbox-lista gs-mod-lista gs-paginate" data-per-page="15">';
	foreach ( $righe as $r ) {
		$anteprima = function_exists( 'mb_substr' ) ? mb_substr( $r['testo'], 0, 60 ) : substr( $r['testo'], 0, 60 );
		echo '<details class="gs-inbox-item" data-sistema="' . esc_attr( $r['sistema'] ) . '"><summary class="gs-inbox-oggetto">'
			. '<span class="gs-mod-badge">' . esc_html( $r['sistema_label'] ) . '</span> '
			. esc_html( $r['autore'] ) . ' — ' . esc_html( $anteprima ) . ( mb_strlen( $r['testo'] ) > 60 ? '…' : '' )
			. '<span class="gs-msg-data">' . ( $r['time'] ? esc_html( date_i18n( 'j/m/Y H:i', $r['time'] ) ) : '' ) . '</span></summary>';
		echo '<div class="gs-inbox-corpo">';
		echo '<div class="gs-mod-testo">' . nl2br( esc_html( $r['testo'] ) ) . '</div>';
		echo '<p><a class="gs-btn gs-btn-sm gs-btn-ghost" href="' . esc_url( $r['url'] ) . '">Vai al pannello di origine</a> ';
		echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-mod-elimina" data-sistema="' . esc_attr( $r['sistema'] ) . '" data-post="' . (int) $r['post'] . '" data-indice="' . esc_attr( $r['indice'] ) . '">✕ Elimina questo messaggio</button> ';
		echo '<span class="gs-mod-msg gs-richiesta-esito"></span></p>';
		echo '</div></details>';
	}
	echo '</div>';
	echo gs_box_close();
}
