<?php
/**
 * regia-iscritti.php — "Regia degli Iscritti ai Corsi": scheda unica a 360°
 * per ogni persona iscritta a un corso, per chi gestisce il portale.
 *
 * Nome scelto per non confondersi con "Pianificazione dell'Anno"
 * (pianificazione-anno.php), già esistente e con uno scopo diverso (quando
 * cadono gli eventi nell'anno) — questa invece è un elenco di PERSONE.
 * Richiesto da Ennio il 18/08/2026.
 *
 * Non introduce nessun sistema nuovo dove uno vero già esiste: riusa
 * gs_sfoglina_dossier_html() (sfogline-extra.php) per anagrafica/token/
 * percorso, e le funzioni vere di calendario.php per corso/pagamenti/
 * diploma. Aggiunge solo le tre cose che mancavano davvero: Comunicazioni
 * unificate (messaggi + conversazioni), Note riservate e Soggiorno — con
 * meta nuovi, nessuna tabella.
 *
 * Solo per chi gestisce (gs_can_manage()): mai una pagina pubblica, mai
 * visibile alle sfogline — per questo non ha un interruttore vero/proprio
 * nel registro delle sezioni (gs_sez_registry()), che serve a nascondere
 * pagine PUBBLICHE.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Tutte le prenotazioni di un utente, su tutti i corsi — non esiste già una funzione così in calendario.php, si scrive qui con lo stesso pattern già usato altrove nel file (gs_cal_prenotazioni() + array_filter su gs_cliente). */
function gs_regia_prenotazioni_utente( $uid ) {
	$uid = (int) $uid;
	if ( ! $uid || ! function_exists( 'gs_cal_prenotazioni' ) ) {
		return array();
	}
	return array_values( array_filter( gs_cal_prenotazioni(), function ( $p ) use ( $uid ) {
		return (int) get_post_meta( $p->ID, 'gs_cliente', true ) === $uid;
	} ) );
}

/**
 * Una prenotazione è "in sospeso" quando è ferma da più di 15 giorni senza
 * nessun movimento: ancora "prenotato" (non confermata né annullata) e senza
 * un euro di acconto versato. Soglia dei 15 giorni scelta per lo studio
 * dell'architettura del 18/08/2026, regolabile qui in un solo punto.
 */
function gs_regia_e_sospesa( $pren_id ) {
	$stato   = get_post_meta( $pren_id, 'gs_stato', true );
	$acconto = (float) get_post_meta( $pren_id, 'gs_acconto_versato', true );
	if ( 'prenotato' !== $stato || $acconto > 0 ) {
		return false;
	}
	$post = get_post( $pren_id );
	if ( ! $post ) { return false; }
	return ( time() - strtotime( $post->post_date ) ) > 15 * DAY_IN_SECONDS;
}

/** Riepilogo di stato di un utente iscritto, per i filtri dell'elenco: 'sospeso' | 'diplomato' | 'confermato' | 'acconto' | 'concluso'. */
function gs_regia_stato_utente( $uid ) {
	$pren = gs_regia_prenotazioni_utente( $uid );
	if ( ! $pren ) { return ''; }

	$ha_sospesa    = false;
	$ha_diploma    = false;
	$ha_confermata = false;
	$ha_acconto    = false;
	$tutte_finite  = true;

	foreach ( $pren as $p ) {
		if ( gs_regia_e_sospesa( $p->ID ) ) { $ha_sospesa = true; }
		if ( '1' === get_post_meta( $p->ID, 'gs_cal_attestato', true ) ) { $ha_diploma = true; }
		$stato = get_post_meta( $p->ID, 'gs_stato', true );
		if ( 'confermato' === $stato ) { $ha_confermata = true; }
		if ( (float) get_post_meta( $p->ID, 'gs_acconto_versato', true ) > 0 ) { $ha_acconto = true; }
		if ( ! in_array( $stato, array( 'annullato', 'annullato_tardi', 'rimborsato', 'no_show' ), true ) ) {
			$corso = function_exists( 'gs_cal_corso_get' ) ? gs_cal_corso_get( (int) get_post_meta( $p->ID, 'gs_corso', true ) ) : null;
			if ( ! $corso || ! empty( $corso['data'] ) && strtotime( $corso['data'] ) >= strtotime( 'today' ) ) { $tutte_finite = false; }
		}
	}

	if ( $ha_sospesa ) { return 'sospeso'; }
	if ( $ha_diploma ) { return 'diplomato'; }
	if ( $tutte_finite && ( $ha_confermata || $ha_acconto ) ) { return 'concluso'; }
	if ( $ha_confermata ) { return 'confermato'; }
	if ( $ha_acconto ) { return 'acconto'; }
	return '';
}

// -----------------------------------------------------------------------------
// PANNELLO — Elenco degli iscritti (colonna dell'elenco)
// -----------------------------------------------------------------------------
function gs_pannello_regia_iscritti() {
	if ( ! gs_can_manage() ) { return; }

	echo gs_box_open( '🎯 Lista degli Iscritti ai Corsi', 'gs-regia-atelier', 'gs-regia-box' );
	echo gs_sezione_aiuto( 'Una scheda unica per ogni persona iscritta a un corso: dati, corso e calendario, pagamenti, token, comunicazioni, diploma, note riservate e proposta di soggiorno. Cerca un nome o filtra per stato, poi clicca sulla riga per aprire la scheda completa — è la stessa scheda che trovi già in "Cerca sfoglina", con in più le sezioni dedicate ai corsi.' );

	$etichette = array(
		''          => 'Tutti',
		'acconto'   => 'In attesa acconto',
		'confermato'=> 'Confermati',
		'sospeso'   => '⏸ In sospeso',
		'concluso'  => 'Conclusi',
		'diplomato' => 'Diplomati',
	);

	// Filtro per corso, arrivando dal link "Vedi chi è iscritto a questo
	// corso" dentro Pianificazione dell'Anno (richiesto da Ennio, 18/08/2026).
	$corso_id = isset( $_GET['gs_regia_corso'] ) ? (int) $_GET['gs_regia_corso'] : 0;
	$corso    = $corso_id && function_exists( 'gs_cal_corso_get' ) ? gs_cal_corso_get( $corso_id ) : null;
	if ( $corso_id && $corso ) {
		echo '<p class="gs-regia-filtro-corso-banner" style="background:var(--gs-crema,#f3e5c7);border-radius:8px;padding:9px 13px;margin-bottom:12px">';
		echo '🎯 Solo iscritti a: <strong>' . esc_html( $corso['titolo'] ) . '</strong> — <a href="' . esc_url( remove_query_arg( 'gs_regia_corso' ) . '#gs-zona-regia-iscritti' ) . '">mostra tutti</a>';
		echo '</p>';
	}

	echo '<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px">';
	echo '<input type="text" class="gs-cerca-input gs-regia-cerca" placeholder="🔍 Cerca per nome…" style="flex:1;min-width:200px;max-width:320px">';
	echo '</div>';
	echo '<p class="gs-regia-filtri" data-corso="' . (int) $corso_id . '" style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px">';
	foreach ( $etichette as $val => $lbl ) {
		$classe = ( '' === $val ) ? 'gs-btn gs-btn-sm gs-regia-filtro attivo' : 'gs-btn gs-btn-sm gs-btn-ghost gs-regia-filtro';
		echo '<button type="button" class="' . esc_attr( $classe ) . '" data-stato="' . esc_attr( $val ) . '">' . esc_html( $lbl ) . '</button>';
	}
	echo '</p>';

	echo '<div id="gs-regia-elenco">';
	echo gs_regia_elenco_html( '', $corso_id );
	echo '</div>';
	echo '<div id="gs-regia-scheda" style="margin-top:18px"></div>';

	echo gs_box_close();
}

/** Righe dell'elenco, filtrate per stato e (facoltativo) per un corso specifico — usata sia al primo caricamento sia dal filtro AJAX. */
function gs_regia_elenco_html( $filtro, $corso_id = 0 ) {
	$uid_visti = array();
	$righe = array();

	if ( ! function_exists( 'gs_cal_prenotazioni' ) ) {
		return '<p class="gs-hint">Il Calendario Corsi non è disponibile.</p>';
	}

	foreach ( gs_cal_prenotazioni() as $p ) {
		$uid = (int) get_post_meta( $p->ID, 'gs_cliente', true );
		if ( ! $uid || isset( $uid_visti[ $uid ] ) ) { continue; }
		if ( $corso_id && $corso_id !== (int) get_post_meta( $p->ID, 'gs_corso', true ) ) {
			// Non è iscritto a QUESTO corso specifico — ma potrebbe esserlo con
			// un'altra prenotazione più avanti nell'elenco: non lo segno come
			// "visto" finché non trovo davvero una sua prenotazione per questo corso.
			continue;
		}
		$uid_visti[ $uid ] = true;

		$stato = gs_regia_stato_utente( $uid );
		if ( $filtro && $filtro !== $stato ) { continue; }

		$u = get_userdata( $uid );
		if ( ! $u ) { continue; }
		$righe[] = array( 'u' => $u, 'stato' => $stato );
	}

	usort( $righe, function ( $a, $b ) { return strcasecmp( $a['u']->display_name, $b['u']->display_name ); } );

	if ( ! $righe ) {
		return '<p class="gs-hint">Nessun iscritto in questa categoria.</p>';
	}

	$colori = array( 'sospeso' => '#c23b3b', 'diplomato' => '#96473a', 'confermato' => '#1f6e37', 'acconto' => '#bd8a13', 'concluso' => '#5c6270', '' => '#9a8d6c' );
	$nomi   = array( 'sospeso' => '⏸ Sospeso', 'diplomato' => 'Diplomato', 'confermato' => 'Confermato', 'acconto' => 'Acconto', 'concluso' => 'Concluso', '' => '—' );

	$out = '<div class="gs-regia-lista gs-paginate" data-per-page="15">';
	foreach ( $righe as $r ) {
		$u = $r['u'];
		$out .= '<div class="gs-regia-riga' . ( 'sospeso' === $r['stato'] ? ' gs-regia-riga-sospesa' : '' ) . '" data-uid="' . (int) $u->ID . '" role="button" tabindex="0">';
		$out .= '<span class="gs-regia-semaforo" style="background:' . esc_attr( $colori[ $r['stato'] ] ) . '"></span>';
		$out .= '<span class="gs-regia-nome">' . esc_html( $u->display_name ) . '</span>';
		$out .= '<span class="gs-regia-stato" style="color:' . esc_attr( $colori[ $r['stato'] ] ) . '">' . esc_html( $nomi[ $r['stato'] ] ) . '</span>';
		$out .= '</div>';
	}
	$out .= '</div>';
	return $out;
}

add_action( 'wp_ajax_gs_regia_filtra', 'gs_ajax_regia_filtra' );
function gs_ajax_regia_filtra() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$filtro   = sanitize_key( $_POST['stato'] ?? '' );
	$corso_id = (int) ( $_POST['corso'] ?? 0 );
	wp_send_json_success( array( 'html' => gs_regia_elenco_html( $filtro, $corso_id ) ) );
}

add_action( 'wp_ajax_gs_regia_apri_scheda', 'gs_ajax_regia_apri_scheda' );
function gs_ajax_regia_apri_scheda() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$uid = (int) ( $_POST['user_id'] ?? 0 );
	$u   = $uid ? get_userdata( $uid ) : false;
	if ( ! $u ) { wp_send_json_error( array( 'message' => 'Utente non trovato.' ) ); }
	// Veste "Atelier" (scelta da Ennio il 18/08/2026 tra due anteprime): tutto
	// il contenuto — testa, Percorso, scheda base, sezioni aggiuntive — viene
	// avvolto in .gs-regia-atelier, che in gaming.css ridisegna anche i
	// riquadri già esistenti di gs_sfoglina_dossier_html() (numeri romani,
	// colori per sezione), senza dover riscrivere quella funzione.
	$html = '<div class="gs-regia-atelier">'
		. gs_regia_scheda_testa_html( $u )
		. gs_regia_percorso_html( $u->ID )
		. gs_sfoglina_dossier_html( $u )
		. gs_regia_dossier_extra_html( $u )
		. '</div>';
	wp_send_json_success( array( 'html' => $html ) );
}

/** Intestazione della scheda: iniziali, nome, corso principale, contatti rapidi. */
function gs_regia_scheda_testa_html( $u ) {
	$uid  = (int) $u->ID;
	$pren = gs_regia_prenotazioni_utente( $uid );
	$sotto = '';
	if ( $pren ) {
		$p0 = $pren[0];
		$c  = gs_cal_corso_get( (int) get_post_meta( $p0->ID, 'gs_corso', true ) );
		$sotto = 'Iscritta a: ' . ( $c['titolo'] ?? '' ) . ( ! empty( $c['data'] ) ? ' · ' . gs_data_it( $c['data'] ) : '' );
	}
	$parti    = preg_split( '/\s+/', trim( $u->display_name ) );
	$iniziali = mb_strtoupper( mb_substr( $parti[0] ?? '', 0, 1 ) . mb_substr( end( $parti ) ?: '', 0, 1 ) );
	$tel = preg_replace( '/[^0-9+]/', '', get_user_meta( $uid, 'gs_telefono', true ) );

	$out  = '<div class="gs-regia-testa">';
	$out .= '<div class="gs-regia-faccia">' . esc_html( $iniziali ) . '</div>';
	$out .= '<div class="gs-regia-chi"><h2>' . esc_html( $u->display_name ) . '</h2>';
	if ( $sotto ) { $out .= '<p>' . esc_html( $sotto ) . '</p>'; }
	$out .= '</div>';
	$out .= '<div class="gs-regia-contatti-rapidi">';
	$out .= '<a href="mailto:' . esc_attr( $u->user_email ) . '">✉️ Email</a>';
	if ( $tel ) {
		$out .= '<a href="tel:' . esc_attr( $tel ) . '">📞 Chiama</a>';
		$out .= '<a href="https://wa.me/' . esc_attr( ltrim( $tel, '+' ) ) . '" target="_blank">💬 WhatsApp</a>';
		$out .= '<a href="sms:' . esc_attr( $tel ) . '">📩 SMS</a>';
	}
	$out .= '</div></div>';
	return $out;
}

/**
 * Il Percorso a 6 tappe (Iscrizione → Acconto → Corso → Saldo → Diploma →
 * Soggiorno), calcolato dalla prenotazione più recente della persona.
 * Nessun dato nuovo: legge solo meta già esistenti (o già aggiunti da
 * questo stesso file per Note/Soggiorno).
 */
function gs_regia_percorso_html( $uid ) {
	$pren = gs_regia_prenotazioni_utente( $uid );
	if ( ! $pren ) { return ''; }
	// La più recente (le prenotazioni sono già in ordine, ma per sicurezza si
	// ordina esplicitamente per data di creazione, la più nuova prima).
	usort( $pren, function ( $a, $b ) { return strtotime( $b->post_date ) <=> strtotime( $a->post_date ); } );
	$p = $pren[0];

	$acconto    = (float) get_post_meta( $p->ID, 'gs_acconto_versato', true );
	$saldo      = (float) get_post_meta( $p->ID, 'gs_saldo_versato', true );
	$corso      = gs_cal_corso_get( (int) get_post_meta( $p->ID, 'gs_corso', true ) );
	$attestato  = '1' === get_post_meta( $p->ID, 'gs_cal_attestato', true );
	$soggiorno  = (string) get_user_meta( $uid, 'gs_soggiorno_scelta', true );
	$corso_fatto = $attestato || ( ! empty( $corso['data'] ) && strtotime( $corso['data'] ) < time() );

	$tappe = array(
		array( 'nome' => 'Iscrizione', 'fatta' => true ),
		array( 'nome' => 'Acconto',    'fatta' => $acconto > 0 ),
		array( 'nome' => 'Corso',      'fatta' => $corso_fatto ),
		array( 'nome' => 'Saldo',      'fatta' => ( $acconto + $saldo ) >= (float) ( $corso['prezzo'] ?? 0 ) && (float) ( $corso['prezzo'] ?? 0 ) > 0 ),
		array( 'nome' => 'Diploma',    'fatta' => $attestato ),
		array( 'nome' => 'Soggiorno',  'fatta' => '' !== trim( $soggiorno ) ),
	);
	$corrente = null;
	foreach ( $tappe as $i => $t ) { if ( ! $t['fatta'] ) { $corrente = $i; break; } }

	$out  = '<div class="gs-regia-percorso"><h3>Il Percorso</h3><div class="gs-regia-tappe">';
	foreach ( $tappe as $i => $t ) {
		$cls = $t['fatta'] ? ' fatta' : ( $i === $corrente ? ' corrente' : '' );
		$out .= '<div class="gs-regia-tappa' . $cls . '"><div class="gs-regia-pallino">' . ( $t['fatta'] ? '✓' : ( $i + 1 ) ) . '</div><div class="gs-regia-nome-tappa">' . esc_html( $t['nome'] ) . '</div></div>';
	}
	$out .= '</div></div>';
	return $out;
}

// -----------------------------------------------------------------------------
// Le sezioni aggiuntive: Corso & Calendario + Diploma, Comunicazioni,
// Note riservate, Soggiorno — aggiunte dopo la scheda base già esistente.
// -----------------------------------------------------------------------------
function gs_regia_dossier_extra_html( $u ) {
	$uid  = (int) $u->ID;
	$pren = gs_regia_prenotazioni_utente( $uid );

	ob_start();
	echo '<div class="gs-regia-extra" id="gs-regia-extra-' . $uid . '">';

	// --- Corso & Calendario + Diploma (per prenotazione) ---
	echo '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">🎓 Corso &amp; Calendario <span class="gs-msg-data">' . count( $pren ) . ( 1 === count( $pren ) ? ' prenotazione' : ' prenotazioni' ) . '</span></summary>';
	echo '<div class="gs-inbox-corpo">';
	if ( ! $pren ) {
		echo '<p class="gs-hint">Nessuna prenotazione a un corso.</p>';
	} else {
		$stati_lbl = array( 'prenotato' => 'Prenotato', 'confermato' => 'Confermato', 'no_show' => 'Assente', 'annullato' => 'Annullato', 'annullato_tardi' => 'Annullato tardi', 'lista_attesa' => 'Lista d\'attesa', 'rimborsato' => 'Rimborsato' );
		echo '<table class="gs-tabella-semplice" style="width:100%;font-size:13px"><tr><th style="text-align:left">Corso</th><th style="text-align:left">Data</th><th style="text-align:left">Stato</th><th style="text-align:left">Pagato</th><th style="text-align:left">Diploma</th></tr>';
		foreach ( $pren as $p ) {
			$c = gs_cal_corso_get( (int) get_post_meta( $p->ID, 'gs_corso', true ) );
			$stato = get_post_meta( $p->ID, 'gs_stato', true );
			$pagato = function_exists( 'gs_cal_pren_pagato' ) ? gs_cal_pren_pagato( $p->ID ) : 0;
			$ha_attestato = '1' === get_post_meta( $p->ID, 'gs_cal_attestato', true );
			echo '<tr' . ( gs_regia_e_sospesa( $p->ID ) ? ' style="background:#fdf4f4"' : '' ) . '>';
			echo '<td>' . esc_html( $c['titolo'] ?? '—' ) . '</td>';
			echo '<td>' . esc_html( ! empty( $c['data'] ) ? gs_data_it( $c['data'] ) : '—' ) . '</td>';
			echo '<td>' . esc_html( $stati_lbl[ $stato ] ?? $stato ) . ( gs_regia_e_sospesa( $p->ID ) ? ' <strong style="color:#c23b3b">⏸ sospesa</strong>' : '' ) . '</td>';
			echo '<td>' . number_format_i18n( $pagato, 2 ) . ' € / ' . number_format_i18n( (float) ( $c['prezzo'] ?? 0 ), 2 ) . ' €</td>';
			echo '<td>';
			if ( $ha_attestato ) {
				echo '<a href="' . esc_url( gs_cal_attestato_url( $p->ID ) ) . '" target="_blank">📜 vedi</a>';
			} else {
				echo '<button type="button" class="gs-btn gs-btn-sm gs-regia-diploma-toggle" data-pren="' . (int) $p->ID . '">Genera diploma</button>';
			}
			echo '</td></tr>';
		}
		echo '</table>';
	}
	echo '<span class="gs-regia-diploma-msg gs-richiesta-esito"></span>';
	echo '</div></details>';

	// --- Comunicazioni (messaggi ufficiali + conversazioni, unificate) ---
	$eventi = array();
	if ( function_exists( 'gs_get_messaggi_utente' ) ) {
		foreach ( gs_get_messaggi_utente( $uid ) as $m ) {
			$eventi[] = array( 'quando' => $m->post_date, 'chi' => 'Messaggio', 'testo' => $m->post_title );
		}
	}
	if ( function_exists( 'gs_conv_di_utente' ) ) {
		foreach ( gs_conv_di_utente( $uid ) as $c ) {
			$eventi[] = array( 'quando' => $c->post_modified, 'chi' => 'Conversazione', 'testo' => $c->post_title ? $c->post_title : 'Conversazione privata' );
		}
	}
	usort( $eventi, function ( $a, $b ) { return strtotime( $b['quando'] ) <=> strtotime( $a['quando'] ); } );

	echo '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">✉️ Comunicazioni <span class="gs-msg-data">' . count( $eventi ) . '</span></summary>';
	echo '<div class="gs-inbox-corpo">';
	$tel = preg_replace( '/[^0-9+]/', '', get_user_meta( $uid, 'gs_telefono', true ) );
	echo '<p class="gs-regia-contatti">';
	echo '<a class="gs-btn gs-btn-sm" href="mailto:' . esc_attr( $u->user_email ) . '">✉️ Email</a> ';
	if ( $tel ) {
		echo '<a class="gs-btn gs-btn-sm" href="tel:' . esc_attr( $tel ) . '">📞 Chiama</a> ';
		echo '<a class="gs-btn gs-btn-sm" href="https://wa.me/' . esc_attr( ltrim( $tel, '+' ) ) . '" target="_blank">💬 WhatsApp</a> ';
		echo '<a class="gs-btn gs-btn-sm" href="sms:' . esc_attr( $tel ) . '">📩 SMS</a>';
	} else {
		echo '<span class="gs-hint">Nessun numero di telefono salvato: telefono/WhatsApp/SMS non disponibili da qui.</span>';
	}
	echo '</p>';
	if ( $eventi ) {
		foreach ( array_slice( $eventi, 0, 20 ) as $e ) {
			echo '<div class="gs-nota-riga"><span class="gs-msg-data">' . esc_html( gs_data_it( $e['quando'] ) ) . ' — ' . esc_html( $e['chi'] ) . '</span><br>' . esc_html( $e['testo'] ) . '</div>';
		}
	} else {
		echo '<p class="gs-hint">Nessuna comunicazione ancora.</p>';
	}
	echo '</div></details>';

	// --- Note riservate ---
	$nota = get_user_meta( $uid, 'gs_note_gestore', true );
	echo '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">📝 Note riservate</summary>';
	echo '<div class="gs-inbox-corpo">';
	echo '<p class="gs-hint">Visibili solo a chi gestisce il portale, mai alla sfoglina.</p>';
	echo '<form class="gs-form gs-form-regia-nota" data-uid="' . $uid . '" onsubmit="return false">';
	echo '<textarea name="nota" rows="4" style="width:100%">' . esc_textarea( $nota ) . '</textarea>';
	echo '<p><button class="gs-btn gs-btn-sm gs-regia-nota-salva">Salva nota</button> <span class="gs-regia-nota-msg gs-richiesta-esito"></span></p>';
	echo '</form>';
	echo '</div></details>';

	// --- Soggiorno ---
	$scelta = get_user_meta( $uid, 'gs_soggiorno_scelta', true );
	$strutture = gs_regia_strutture_soggiorno();
	echo '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">🛏️ Soggiorno</summary>';
	echo '<div class="gs-inbox-corpo">';
	echo '<div class="gs-todo-riquadro"><strong>🏡 B&amp;B GUGO</strong> — prima proposta.</div>';
	if ( $strutture ) {
		echo '<p style="font-weight:600;margin-top:10px">Oppure lì vicino</p><ul class="gs-todo-list">';
		foreach ( $strutture as $s ) {
			echo '<li class="gs-todo-item"><span>' . esc_html( $s['nome'] ) . ' — ' . esc_html( $s['distanza'] ) . ( $s['note'] ? ' (' . esc_html( $s['note'] ) . ')' : '' ) . '</span></li>';
		}
		echo '</ul>';
	}
	echo '<form class="gs-form gs-form-regia-soggiorno" data-uid="' . $uid . '" onsubmit="return false">';
	echo '<p><label>Scelta di ' . esc_html( $u->display_name ) . '<br><input type="text" name="soggiorno" value="' . esc_attr( $scelta ) . '" style="width:100%" placeholder="es. B&amp;B GUGO, 11-13 settembre"></label></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-regia-soggiorno-salva">Salva</button> <span class="gs-regia-soggiorno-msg gs-richiesta-esito"></span></p>';
	echo '</form>';
	echo '</div></details>';

	echo '</div>';
	return ob_get_clean();
}

/** Elenco condiviso delle strutture consigliate vicine al B&B GUGO, gestito dai gestori (impostazione unica, GS_OPTION). */
function gs_regia_strutture_soggiorno() {
	$s = gs_settings();
	return isset( $s['regia_soggiorno_strutture'] ) && is_array( $s['regia_soggiorno_strutture'] ) ? $s['regia_soggiorno_strutture'] : array();
}

add_action( 'wp_ajax_gs_regia_nota_salva', 'gs_ajax_regia_nota_salva' );
function gs_ajax_regia_nota_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$uid = (int) ( $_POST['user_id'] ?? 0 );
	if ( ! $uid || ! get_userdata( $uid ) ) { wp_send_json_error( array( 'message' => 'Utente non trovato.' ) ); }
	update_user_meta( $uid, 'gs_note_gestore', sanitize_textarea_field( wp_unslash( $_POST['nota'] ?? '' ) ) );
	wp_send_json_success( array( 'message' => 'Nota salvata.' ) );
}

add_action( 'wp_ajax_gs_regia_soggiorno_salva', 'gs_ajax_regia_soggiorno_salva' );
function gs_ajax_regia_soggiorno_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$uid = (int) ( $_POST['user_id'] ?? 0 );
	if ( ! $uid || ! get_userdata( $uid ) ) { wp_send_json_error( array( 'message' => 'Utente non trovato.' ) ); }
	update_user_meta( $uid, 'gs_soggiorno_scelta', sanitize_text_field( wp_unslash( $_POST['soggiorno'] ?? '' ) ) );
	wp_send_json_success( array( 'message' => 'Soggiorno salvato.' ) );
}

add_action( 'wp_ajax_gs_regia_diploma_toggle', 'gs_ajax_regia_diploma_toggle' );
function gs_ajax_regia_diploma_toggle() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$pren_id = (int) ( $_POST['pren_id'] ?? 0 );
	if ( ! $pren_id || ! function_exists( 'gs_cal_attestato_toggle' ) ) { wp_send_json_error( array( 'message' => 'Prenotazione non trovata.' ) ); }
	$attivo = gs_cal_attestato_toggle( $pren_id );
	wp_send_json_success( array(
		'message' => $attivo ? 'Diploma generato.' : 'Diploma revocato.',
		'attivo'  => $attivo,
		'url'     => $attivo && function_exists( 'gs_cal_attestato_url' ) ? gs_cal_attestato_url( $pren_id ) : '',
	) );
}
