<?php
/**
 * side-tabs.php — Linguette laterali di navigazione lungo il bordo destro.
 * Compaiono su tutte le pagine del sito e permettono di saltare rapidamente
 * a qualsiasi pannello del plugin. Per chi gestisce il portale compare anche
 * la linguetta del Pannello di Controllo.
 *
 * Dal 18/08/2026 (richiesta di Ennio, "riprogettami al meglio anche il menu
 * marrone"): le voci non sono più tutte in fila (erano arrivate a 35) — sono
 * raggruppate per categoria, stessa suddivisione del registro unico
 * gs_categorie() (helpers.php) usato anche da Plancia, Pannello Generale e
 * menu verde. "Pannello di Controllo", il gruppo "Messaggi" (bolle, invariato)
 * e le due voci più usate (La Mia Sfoglia, Cerca) restano fisse in cima,
 * fuori dai gruppi: sono il motivo per cui si apre il menu più spesso.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_footer', 'gs_render_side_tabs' );

function gs_render_side_tabs() {
	// Solo per utenti loggati (le pagine di gioco richiedono l'accesso).
	if ( ! is_user_logged_in() ) {
		return;
	}

	// Blackout: alle sfogline non compaiono le linguette (i gestori e le eccezioni sì).
	if ( gs_blackout_attivo()
		&& ! ( function_exists( 'gs_can_manage' ) && gs_can_manage() )
		&& ! ( function_exists( 'gs_blackout_esente' ) && gs_blackout_esente() ) ) {
		return;
	}

	// Elenco linguette: etichetta, icona, option della pagina, categoria.
	// 'pin' = resta fissa in cima, fuori dai gruppi (non una vera categoria).
	$voci = array(
		array( 'La Mia Sfoglia',   '🧑‍🍳', 'gs_page_dashboard', 'pin' ),
		array( 'Cerca in tutto il sito', '🔎', 'gs_page_ricerca_globale', 'pin' ),
		array( 'Le Mie Sfide',     '🏆',   'gs_page_sfida', 'sfide' ),
		array( 'Classifica',       '📊',   'gs_page_classifica', 'sfide' ),
		array( 'Le Sfogline',      '👭',   'gs_page_sfogline', 'sfogline' ),
		array( "Il Registro Ufficiale dell'Accademia della Sfoglia", '📜', 'gs_page_registro', 'contenuti' ),
		array( 'Ultimi Traguardi', '🏅', 'gs_page_traguardi', 'sfide' ),
		array( 'FAQ - Domande',    '❓',   'gs_page_faq', 'impostazioni' ),
		array( 'Novità',           '📣',   'gs_page_novita', 'contenuti' ),
		array( 'Il Tuo Percorso',  '🧭',   'gs_page_percorso_personale', 'sfogline' ),
		array( 'Il Tuo Anno in Accademia', '🎊', 'gs_page_riepilogo_anno', 'sfogline' ),
		array( 'Le tue ricette di famiglia', '📖', 'gs_page_ricettario', 'contenuti' ),
		array( 'Indovina la Sfoglia', '🔮', 'gs_page_indovina', 'sfide' ),
		array( 'Il Tavolo di Lavoro', '🍳', 'gs_page_tavolo', 'sfide' ),
		array( 'La Sfoglia Misurata', '📏', 'gs_page_misurata', 'sfide' ),
		array( 'La Giuria a Turno', '⚖️', 'gs_page_giuria', 'sfide' ),
		array( 'Sondaggi',         '🗳️',  'gs_page_sondaggi', 'sfide' ),
		array( 'Adotta un Piatto in Via di Estinzione', '🦕', 'gs_page_piatti_estinzione', 'contenuti' ),
		array( 'Il Matterello Parlante', '🎙️', 'gs_page_matterello', 'contenuti' ),
		array( 'Un Anno Fa Oggi', '🕰️', 'gs_page_anno_fa_oggi', 'sfogline' ),
		array( 'I Testamenti delle Maestre', '📜', 'gs_page_testamenti', 'contenuti' ),
		array( 'La Cassaforte del Sapere', '🔐', 'gs_page_cassaforte', 'contenuti' ),
		array( 'La Sfoglia che Insegna Se Stessa', '🎓', 'gs_page_sfoglia_insegna', 'contenuti' ),
		array( 'Promemoria giornaliero', '⏰', 'gs_page_promemoria', 'sfogline' ),
		array( 'Dicono di Noi', '💬', 'gs_page_dicono_di_noi', 'contenuti' ),
		array( 'Lezioni Video', '🎬', 'gs_page_lezioni', 'corsi' ),
		array( 'I Compleanni di Oggi', '🎂', 'gs_page_compleanni', 'sfogline' ),
		array( 'Calendario Corsi', '📅', 'gs_page_calendario', 'corsi' ),
		array( 'Galleria',         '🖼️',  'gs_page_galleria', 'contenuti' ),
		array( 'Badge',            '🎖️',  'gs_page_badge', 'sfide' ),
		array( 'Diario',           '📔',   'gs_page_diario', 'sfogline' ),
		array( 'Consigli',         '💡',   'gs_page_consigli', 'contenuti' ),
		array( 'Guida Stagionale', '🌦️',  'gs_page_barometro', 'contenuti' ),
		array( 'Iscrizione',       '📝',   'gs_page_iscrizione', 'sfogline' ),
	);

	// Vetrina: solo se attiva e non bloccata per la sfoglina corrente.
	if ( gs_vetrina_disponibile( get_current_user_id() ) ) {
		$voci[] = array( 'La tua Vetrina', '🔗', '__vetrina__', 'sfogline' );
	}

	// Corsi Online: solo se la sfoglina ha un corso attivo e non oscurato.
	if ( function_exists( 'gs_get_corso_utente' ) ) {
		$corso = gs_get_corso_utente( get_current_user_id() );
		if ( $corso && 'sospeso' !== gs_corso_stato( $corso->ID ) && ! gs_corso_oscurato( $corso->ID ) ) {
			$voci[] = array( 'Corsi Online', '🎓', 'gs_page_area_pro', 'corsi' );
		}
	}

	// Gruppo "Messaggi": messaggeria generale + un canale per ogni "Esperto
	// Risponde" attivo (es. Rina Poletti Risponde, Bruno Cingolani Risponde).
	// Resta fisso in cima con lo stesso stile a bolla di sempre (richiesto da
	// Ennio il 2026-07-30) — non è uno dei gruppi richiudibili qui sotto, per
	// non spezzare in due posti diversi tutto ciò che è "posta".
	$msg_gruppo = array();
	if ( function_exists( 'gs_messaggi_non_letti' ) ) {
		$nl = gs_messaggi_non_letti( get_current_user_id() );
		if ( function_exists( 'gs_conv_non_letti' ) ) { $nl += gs_conv_non_letti( get_current_user_id() ); }
		$msg_gruppo[] = array( 'Messaggi', '✉️', 'gs_page_messaggi', $nl );
	}
	if ( function_exists( 'gs_esperti_canali' ) ) {
		$pid_e = (int) get_option( 'gs_page_esperto' );
		$base_e = $pid_e ? get_permalink( $pid_e ) : '';
		if ( $base_e ) {
			$uid_c = get_current_user_id();
			$mgr   = function_exists( 'gs_can_manage' ) && gs_can_manage();
			foreach ( gs_esperti_canali() as $slug => $ch ) {
				if ( empty( $ch['attivo'] ) ) { continue; }
				// Numero domande senza risposta: visibile all'esperto del canale e ai gestori.
				$sr = ( $mgr || gs_is_esperto( $uid_c, $slug ) ) ? gs_esperto_senza_risposta( $slug ) : 0;
				$msg_gruppo[] = array( $ch['nome'], '💬', '__url__' . add_query_arg( 'canale', $slug, $base_e ), $sr );
			}
		}
	}
	// Pannello di Controllo: solo per chi gestisce il portale, sempre per primo.
	$pannello_ctrl = ( function_exists( 'gs_can_manage' ) && gs_can_manage() )
		? array( 'Pannello di Controllo', '🎛️', 'gs_page_pannello' )
		: null;

	// Smista le voci normali (non 'pin') nei gruppi di categoria, scartando
	// quelle senza url risolvibile (sezione nascosta dal pannello) — così un
	// gruppo il cui unico contenuto è nascosto non compare vuoto.
	$pin = array();
	$per_gruppo = array();
	foreach ( $voci as $v ) {
		if ( ! gs_side_tab_url( $v ) ) { continue; }
		$cat = isset( $v[3] ) ? $v[3] : '';
		if ( 'pin' === $cat ) {
			$pin[] = $v;
		} else {
			if ( ! isset( $per_gruppo[ $cat ] ) ) { $per_gruppo[ $cat ] = array(); }
			$per_gruppo[ $cat ][] = $v;
		}
	}

	// Pulsante che apre/chiude il pannello delle linguette (utile quando sono tante).
	echo '<button type="button" class="gs-side-launcher" aria-label="Apri il menu" aria-expanded="false">';
	echo '<span class="gs-launcher-ico">🍝</span><span class="gs-launcher-lbl">Menu</span>';
	echo '</button>';

	echo '<nav class="gs-side-tabs" aria-label="Navigazione Gaming Sfogline">';

	if ( $pannello_ctrl ) {
		gs_side_tab_render( $pannello_ctrl );
	}

	if ( ! empty( $msg_gruppo ) ) {
		echo '<div class="gs-side-msg-grp"><div class="gs-side-msg-label"><span class="gs-side-msg-label-ico">💬</span>Messaggi</div>';
		foreach ( $msg_gruppo as $v ) {
			gs_side_msg_render( $v );
		}
		echo '</div>';
	}

	foreach ( $pin as $v ) {
		gs_side_tab_render( $v );
	}

	// Gruppi richiudibili, stesso ordine e stessi nomi/icone/colori del
	// registro unico gs_categorie() — un gruppo senza voci visibili non
	// compare affatto.
	foreach ( gs_categorie() as $chiave => $cat ) {
		if ( empty( $per_gruppo[ $chiave ] ) ) { continue; }
		echo '<div class="gs-side-grp" data-grp="' . esc_attr( $chiave ) . '">';
		printf(
			'<button type="button" class="gs-side-grp-btn" data-grp="%s" aria-expanded="false"><span class="gs-side-grp-dot" style="background:%s"></span><span class="gs-side-grp-ico">%s</span><span class="gs-side-grp-lbl">%s</span><span class="gs-side-grp-chev">›</span></button>',
			esc_attr( $chiave ),
			esc_attr( $cat['colore'] ),
			$cat['ico'],
			esc_html( $cat['nome'] )
		);
		echo '<div class="gs-side-grp-body">';
		foreach ( $per_gruppo[ $chiave ] as $v ) {
			gs_side_tab_render( $v );
		}
		echo '</div></div>';
	}

	echo '</nav>';
}

/** Risolve etichetta/icona/url di una voce e stampa la linguetta piatta consueta. */
function gs_side_tab_render( $v ) {
	$url = gs_side_tab_url( $v );
	if ( ! $url ) {
		return;
	}
	printf(
		'<a class="gs-side-tab" href="%s"><span class="gs-side-ico">%s</span><span class="gs-side-lbl">%s</span></a>',
		esc_url( $url ),
		$v[1],
		esc_html( $v[0] )
	);
}

/** Stampa una voce del gruppo "Messaggi" in stile bolla (come le Conversazioni private). */
function gs_side_msg_render( $v ) {
	$url = gs_side_tab_url( $v );
	if ( ! $url ) {
		return;
	}
	$conta = isset( $v[3] ) ? (int) $v[3] : 0;
	// Lampeggio rosso condiviso (stesso di segreteria/abbonamenti scaduti)
	// quando c'è qualcosa di nuovo da leggere in questa voce — richiesto da
	// Ennio il 2026-07-30, prima la casella restava "muta" all'arrivo.
	printf(
		'<a class="gs-side-msg-item%s" href="%s"><span class="gs-side-msg-ico">%s</span><span class="gs-side-msg-testo">%s</span>%s</a>',
		$conta > 0 ? ' gs-lampeggia-rosso' : '',
		esc_url( $url ),
		$v[1],
		esc_html( $v[0] ),
		$conta > 0 ? '<span class="gs-side-msg-badge">' . (int) $conta . '</span>' : ''
	);
}

/** Risolve l'url di una voce (pagina interna, url diretto, o vetrina) — null se non disponibile. */
function gs_side_tab_url( $v ) {
	$key = $v[2];
	if ( '__vetrina__' === $key ) {
		return gs_vetrina_url( get_current_user_id() );
	}
	if ( 0 === strpos( $key, '__url__' ) ) {
		return substr( $key, 7 );
	}
	if ( function_exists( 'gs_sez_page_hidden' ) && gs_sez_page_hidden( $key ) ) {
		return null; // sezione nascosta dal pannello
	}
	$pid = (int) get_option( $key );
	return $pid ? get_permalink( $pid ) : null;
}
