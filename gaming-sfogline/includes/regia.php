<?php
/**
 * regia.php — Regia del Gaming: proposte e direttive per programmare il
 * gaming (sfide, percorsi, contenuti…), scritte da chi gestisce/collabora
 * (es. Rina Poletti, Bruno Cingolani) e decise dal titolare.
 *
 * Non è un corso individuale (quello è l'Area Professionale, un docente
 * per una sola sfoglina): qui il "destinatario" è il gaming nel suo
 * insieme, non una persona sola — più vicino a un diario di regia che a
 * un percorso didattico. Non tocca la Pianificazione dell'Anno (quella
 * organizza le DATE di cose già create): qui si propongono IDEE, prima
 * ancora che esistano.
 *
 * CPT gs_direttiva (privato, non pubblico):
 *  - post_title   = oggetto breve della proposta
 *  - post_content = testo esteso (facoltativo)
 *  - post_author  = chi l'ha scritta
 *  - meta gs_direttiva_stato : proposta | approvata | realizzata | archiviata
 *  - meta gs_direttiva_note  : risposta/note del titolare (facoltativa)
 *  - meta gs_direttiva_link  : link alla sfida/percorso realizzato (facoltativo)
 *
 * Solo per chi gestisce (gs_can_manage()): niente shortcode pubblico.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_register_direttiva_cpt' );
function gs_register_direttiva_cpt() {
	register_post_type( 'gs_direttiva', array(
		'labels'       => array( 'name' => 'Regia del Gaming' ),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'title', 'editor', 'author' ),
	) );
}

// -----------------------------------------------------------------------------
// Helper
// -----------------------------------------------------------------------------
function gs_regia_stati() {
	return array(
		'proposta'   => 'In attesa',
		'approvata'  => 'Approvata',
		'realizzata' => 'Realizzata',
		'archiviata' => 'Archiviata',
	);
}
function gs_regia_stato( $id ) {
	$s = get_post_meta( $id, 'gs_direttiva_stato', true );
	return isset( gs_regia_stati()[ $s ] ) ? $s : 'proposta';
}
function gs_regia_elenco( $stato = '' ) {
	$q = array(
		'post_type'      => 'gs_direttiva',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);
	$tutte = get_posts( $q );
	if ( ! $stato ) {
		return $tutte;
	}
	return array_values( array_filter( $tutte, function ( $p ) use ( $stato ) {
		return gs_regia_stato( $p->ID ) === $stato;
	} ) );
}
function gs_regia_guard() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) ) {
		wp_send_json_error( array( 'message' => 'Permesso negato.' ) );
	}
}

// -----------------------------------------------------------------------------
// AJAX
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_regia_crea', 'gs_ajax_regia_crea' );
function gs_ajax_regia_crea() {
	gs_regia_guard();
	$oggetto = isset( $_POST['oggetto'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['oggetto'] ) ) ) : '';
	$testo   = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';
	if ( mb_strlen( $oggetto ) < 3 ) {
		wp_send_json_error( array( 'message' => 'Scrivi un oggetto per la proposta.' ) );
	}
	$id = wp_insert_post( array(
		'post_type'    => 'gs_direttiva',
		'post_status'  => 'publish',
		'post_author'  => get_current_user_id(),
		'post_title'   => $oggetto,
		'post_content' => $testo,
	) );
	if ( is_wp_error( $id ) || ! $id ) {
		wp_send_json_error( array( 'message' => 'Errore nel salvataggio.' ) );
	}
	update_post_meta( $id, 'gs_direttiva_stato', 'proposta' );
	update_post_meta( $id, 'gs_direttiva_note', '' );
	update_post_meta( $id, 'gs_direttiva_link', '' );
	wp_send_json_success( array( 'message' => 'Proposta salvata.' ) );
}

add_action( 'wp_ajax_gs_regia_salva', 'gs_ajax_regia_salva' );
function gs_ajax_regia_salva() {
	gs_regia_guard();
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( 'gs_direttiva' !== get_post_type( $id ) ) {
		wp_send_json_error( array( 'message' => 'Proposta non trovata.' ) );
	}
	$stato = isset( $_POST['stato'] ) ? sanitize_key( wp_unslash( $_POST['stato'] ) ) : 'proposta';
	if ( ! isset( gs_regia_stati()[ $stato ] ) ) { $stato = 'proposta'; }
	$note = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
	$link = isset( $_POST['link'] ) ? esc_url_raw( trim( wp_unslash( $_POST['link'] ) ) ) : '';

	update_post_meta( $id, 'gs_direttiva_stato', $stato );
	update_post_meta( $id, 'gs_direttiva_note', $note );
	update_post_meta( $id, 'gs_direttiva_link', $link );

	// Avvisa l'autrice/autore quando la sua proposta viene decisa (non per
	// ogni salvataggio: solo quando esce dallo stato "in attesa").
	$post = get_post( $id );
	if ( $post && (int) $post->post_author !== get_current_user_id() && in_array( $stato, array( 'approvata', 'realizzata', 'archiviata' ), true ) && function_exists( 'gs_mail_progetto' ) ) {
		$autore = get_user_by( 'id', $post->post_author );
		if ( $autore ) {
			$etichetta = gs_regia_stati()[ $stato ];
			$corpo = "Ciao " . $autore->display_name . ",\n\nla tua proposta \"" . $post->post_title . "\" per la Regia del Gaming è ora: " . $etichetta . ".\n"
				. ( $note ? "\nNota: " . $note . "\n" : '' )
				. "\n— Accademia della Sfoglia";
			gs_mail_progetto( $post->post_author, 'messaggi', 'La tua proposta per il gaming è stata aggiornata', $corpo );
		}
	}

	wp_send_json_success( array( 'message' => 'Salvato.' ) );
}

add_action( 'wp_ajax_gs_regia_elimina', 'gs_ajax_regia_elimina' );
function gs_ajax_regia_elimina() {
	gs_regia_guard();
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( 'gs_direttiva' !== get_post_type( $id ) ) {
		wp_send_json_error( array( 'message' => 'Proposta non trovata.' ) );
	}
	wp_trash_post( $id );
	wp_send_json_success( array( 'message' => 'Proposta spostata nel cestino.' ) );
}

add_action( 'wp_ajax_gs_regia_ripristina', 'gs_ajax_regia_ripristina' );
function gs_ajax_regia_ripristina() {
	gs_regia_guard();
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( 'gs_direttiva' !== get_post_type( $id ) ) {
		wp_send_json_error( array( 'message' => 'Proposta non trovata.' ) );
	}
	wp_untrash_post( $id );
	wp_send_json_success( array( 'message' => 'Proposta ripristinata.' ) );
}

// -----------------------------------------------------------------------------
// Pannello
// -----------------------------------------------------------------------------
function gs_pannello_regia() {
	if ( ! ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) ) {
		return;
	}
	echo gs_box_open( '🎬 Regia del Gaming — proposte e direttive', '', 'gs-box-regia' );
	echo '<p class="gs-hint">Uno spazio per proporre idee sul gaming (nuove sfide, percorsi, contenuti…) prima ancora che esistano — non organizza le date di cose già create (quello è la Pianificazione dell\'Anno) e non è un corso per una sola sfoglina (quello sono i Corsi Online). Chi gestisce o collabora scrive una proposta, il titolare la approva, la segna realizzata (con un link facoltativo a cosa ne è nato) o la archivia. Le proposte in attesa lampeggiano in rosso.</p>';

	// Nuova proposta.
	echo '<form class="gs-form gs-form-regia-nuova" onsubmit="return false" style="background:var(--gs-uovo);padding:12px;border-radius:6px;margin-bottom:14px">';
	echo '<strong>Nuova proposta</strong>';
	echo '<p><label>Oggetto<br><input type="text" name="oggetto" autocomplete="off" style="width:100%" placeholder="es. Una sfida sui ripieni di stagione"></label></p>';
	echo '<p><label>Dettagli (facoltativo)<br><textarea name="testo" rows="3" style="width:100%" placeholder="Spiega l\'idea, a chi si rivolge, perché…"></textarea></label></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-regia-crea">Salva proposta</button> <span class="gs-regia-crea-msg gs-richiesta-esito"></span></p>';
	echo '</form>';

	// Elenco (tutte le attive, dalla più recente).
	$elenco = gs_regia_elenco();
	if ( ! $elenco ) {
		echo '<p class="gs-hint">Nessuna proposta ancora. Scrivi la prima qui sopra.</p>';
	} else {
		echo '<div class="gs-todo-riquadro"><div class="gs-regia-lista gs-inbox-lista gs-paginate" data-per-page="8">';
		foreach ( $elenco as $p ) {
			$stato   = gs_regia_stato( $p->ID );
			$autore  = get_user_by( 'id', $p->post_author );
			$note    = get_post_meta( $p->ID, 'gs_direttiva_note', true );
			$link    = get_post_meta( $p->ID, 'gs_direttiva_link', true );
			$attesa  = ( 'proposta' === $stato );
			echo '<details class="gs-inbox-item' . ( $attesa ? ' gs-lampeggia-rosso' : '' ) . '" data-id="' . (int) $p->ID . '">';
			echo '<summary class="gs-inbox-oggetto">' . esc_html( $p->post_title )
				. '<span class="gs-regia-badge gs-regia-badge-' . esc_attr( $stato ) . '">' . esc_html( gs_regia_stati()[ $stato ] ) . '</span>'
				. '<span class="gs-msg-data">di ' . esc_html( $autore ? $autore->display_name : '—' ) . ' · ' . esc_html( date_i18n( 'j/m/Y', get_post_time( 'U', false, $p ) ) ) . '</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			if ( $p->post_content ) {
				echo '<div class="gs-inbox-testo">' . nl2br( esc_html( $p->post_content ) ) . '</div>';
			}
			echo '<form class="gs-form gs-form-regia-riga" data-id="' . (int) $p->ID . '" onsubmit="return false">';
			echo '<p><label>Stato<br><select name="stato">';
			foreach ( gs_regia_stati() as $k => $label ) {
				echo '<option value="' . esc_attr( $k ) . '" ' . selected( $stato, $k, false ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select></label></p>';
			echo '<p><label>Nota per chi ha proposto (facoltativa)<br><textarea name="note" rows="2" style="width:100%">' . esc_textarea( $note ) . '</textarea></label></p>';
			echo '<p><label>Link a cosa ne è nato (facoltativo)<br><input type="text" name="link" value="' . esc_attr( $link ) . '" style="width:100%" placeholder="es. link alla nuova sfida"></label></p>';
			echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-regia-salva">Salva</button> '
				. '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-regia-elimina">✕ Sposta nel cestino</button> '
				. '<span class="gs-regia-riga-msg gs-richiesta-esito"></span></p>';
			echo '</form>';
			echo '</div></details>';
		}
		echo '</div></div>';
	}

	// Cestino.
	$cestino = get_posts( array(
		'post_type'      => 'gs_direttiva',
		'post_status'    => 'trash',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
	echo '<details class="gs-todo-cestino"><summary>🗑️ Proposte nel cestino (' . count( $cestino ) . ')</summary>';
	if ( ! $cestino ) {
		echo '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		echo '<ul class="gs-todo-list">';
		foreach ( $cestino as $p ) {
			$autore = get_user_by( 'id', $p->post_author );
			echo '<li class="gs-todo-item" data-id="' . (int) $p->ID . '"><span><strong>' . esc_html( $p->post_title ) . '</strong> — ' . esc_html( $autore ? $autore->display_name : '—' ) . '</span>'
				. '<button class="gs-todo-ripristina gs-regia-ripristina" title="Ripristina">↺ Ripristina</button></li>';
		}
		echo '</ul>';
	}
	echo '</details>';

	echo gs_box_close();
}
