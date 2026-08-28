<?php
/**
 * lezioni-video.php — Libreria Video delle Lezioni di Rina.
 *
 * Brevi video-tutorial organizzati per tecnica. A differenza del Ricettario,
 * qui è il titolare (o Rina) a caricare le lezioni direttamente dal pannello:
 * niente invio dalle sfogline, niente approvazione — sono subito pubbliche.
 * I video restano su YouTube/Vimeo (nessun file caricato sul sito, per non
 * appesantire lo spazio hosting): qui si salva solo il link, trasformato in
 * un lettore incorporato.
 *
 * CPT gs_lezione → meta:
 *  - gs_categoria  (tecnica: sfoglia a mano, ripieni, formati regionali…)
 *  - gs_video_url  (link YouTube o Vimeo)
 *  - gs_domande    (array di { id, testo, risposta_esatta }: domande di
 *                    verifica scritte da Rina Poletti / Bruno Cingolani dal
 *                    pannello, con la risposta esatta che il sistema usa
 *                    per correggere automaticamente)
 *  - gs_risposte   (array di { user_id, risposte: {domanda_id: {testo,
 *                    corretta, punti_assegnati}}, data, feedback }: una
 *                    voce per sfoglina che ha risposto)
 * Il testo della lezione sta in post_content, il titolo in post_title.
 * Sezione di livello "superiore": visibile solo con abbonamento attivo
 * (gestito automaticamente da [[sezioni]], nessun controllo extra qui).
 *
 * Aprire il video dà pochi punti (gs_get_points_value('lezione_vista', 5)),
 * una tantum: non c'è modo di verificare che il video sia stato guardato
 * per intero, quindi il punteggio resta basso apposta.
 *
 * Le domande di verifica sotto al video le scrivono i docenti, insieme
 * alla risposta esatta: quando la sfoglina risponde, il sistema confronta
 * (senza badare a maiuscole, spazi in più o punteggiatura finale) e se
 * corrisponde assegna in automatico gs_get_points_value('risposta_esatta').
 * Il riscontro testuale del maestro resta comunque disponibile, come tocco
 * personale in più — non sostituisce la correzione automatica, la
 * affianca. Regola generale del progetto (2026-07-22): ogni volta che un
 * docente scrive una "domanda" in qualunque modulo, deve poter indicare
 * anche la risposta esatta, per lo stesso meccanismo di correzione e
 * punteggio automatico — vedi [[domande-risposta-esatta-punteggio]].
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_lezione_register_cpt' );
function gs_lezione_register_cpt() {
	register_post_type( 'gs_lezione', array(
		'labels'       => array( 'name' => 'Lezioni Video' ),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'title', 'editor' ),
	) );
}

// -----------------------------------------------------------------------------
// Helper
// -----------------------------------------------------------------------------

/**
 * Trasforma un link YouTube o Vimeo in un lettore incorporato responsive.
 * Se il link non è riconosciuto, mostra solo un collegamento cliccabile.
 */
function gs_video_embed_html( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}
	$embed = '';
	if ( preg_match( '~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/|youtube\.com/shorts/)([A-Za-z0-9_-]{6,})~i', $url, $m ) ) {
		$embed = 'https://www.youtube.com/embed/' . $m[1];
	} elseif ( preg_match( '~vimeo\.com/(?:video/)?(\d+)~i', $url, $m ) ) {
		$embed = 'https://player.vimeo.com/video/' . $m[1];
	}
	if ( '' === $embed ) {
		return '<p class="gs-hint">Link video non riconosciuto (serve un link YouTube o Vimeo). <a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">Apri il link</a></p>';
	}
	return '<div class="gs-video-embed"><iframe src="' . esc_url( $embed ) . '" title="Video della lezione" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
}

function gs_lezione_get( $id ) {
	return array(
		'id'          => $id,
		'titolo'      => get_the_title( $id ),
		'descrizione' => get_post( $id ) ? get_post( $id )->post_content : '',
		'categoria'   => (string) get_post_meta( $id, 'gs_categoria', true ),
		'video_url'   => (string) get_post_meta( $id, 'gs_video_url', true ),
		'data_uscita' => (string) get_post_meta( $id, 'gs_lezione_data_uscita', true ),
	);
}

/**
 * Lezioni "a puntate" (2026-07-24): una data di uscita è facoltativa — senza,
 * la lezione è visibile subito come sempre. Con una data futura, resta
 * bloccata (anche se fa parte di un Percorso già sbloccato) finché non
 * arriva quel giorno: usata per pubblicare un percorso un pezzo alla volta,
 * invece di tutto insieme, e dare un motivo per tornare.
 */
function gs_lezione_disponibile( $lid ) {
	$data = get_post_meta( $lid, 'gs_lezione_data_uscita', true );
	if ( '' === trim( (string) $data ) ) { return true; }
	return strtotime( $data . ' 00:00:00' ) <= current_time( 'timestamp' );
}

/** Per il messaggio "disponibile dal…" quando la lezione è ancora bloccata dalla data di uscita. */
function gs_lezione_data_uscita_label( $lid ) {
	$data = get_post_meta( $lid, 'gs_lezione_data_uscita', true );
	return $data ? date_i18n( 'j F Y', strtotime( $data . ' 00:00:00' ) ) : '';
}

// -----------------------------------------------------------------------------
// Domande di verifica (scritte dai docenti) e risposte (delle sfogline)
// -----------------------------------------------------------------------------

/** Domande di verifica di una lezione. */
function gs_lezione_domande( $lid ) {
	$d = get_post_meta( $lid, 'gs_domande', true );
	return is_array( $d ) ? $d : array();
}

/** Cestino delle domande eliminate: non sono un CPT, niente wp_trash_post(). */
function gs_lezione_domande_cestino( $lid ) {
	$d = get_post_meta( $lid, 'gs_domande_cestino', true );
	return is_array( $d ) ? $d : array();
}
function gs_lezione_salva_domande_cestino( $lid, $cestino ) {
	if ( count( $cestino ) > 30 ) { $cestino = array_slice( $cestino, -30 ); }
	update_post_meta( $lid, 'gs_domande_cestino', array_values( $cestino ) );
}
/** Sposta una domanda dalle attive al cestino. */
function gs_lezione_domanda_sposta_nel_cestino( $lid, $id ) {
	$domande = gs_lezione_domande( $lid );
	$cestino = gs_lezione_domande_cestino( $lid );
	foreach ( $domande as $d ) {
		if ( $d['id'] === $id ) { $d['ts'] = time(); $cestino[] = $d; }
	}
	$domande = array_values( array_filter( $domande, function ( $d ) use ( $id ) { return $d['id'] !== $id; } ) );
	update_post_meta( $lid, 'gs_domande', $domande );
	gs_lezione_salva_domande_cestino( $lid, $cestino );
}
/** Riporta una domanda dal cestino alle attive. */
function gs_lezione_domanda_ripristina_dal_cestino( $lid, $id ) {
	$cestino = gs_lezione_domande_cestino( $lid );
	$domande = gs_lezione_domande( $lid );
	foreach ( $cestino as $d ) {
		if ( $d['id'] === $id ) { unset( $d['ts'] ); $domande[] = $d; }
	}
	$cestino = array_values( array_filter( $cestino, function ( $d ) use ( $id ) { return $d['id'] !== $id; } ) );
	update_post_meta( $lid, 'gs_domande', $domande );
	gs_lezione_salva_domande_cestino( $lid, $cestino );
}

/** Tutte le risposte ricevute per una lezione (una voce per sfoglina). */
function gs_lezione_risposte_tutte( $lid ) {
	$r = get_post_meta( $lid, 'gs_risposte', true );
	return is_array( $r ) ? $r : array();
}

/** La risposta di una specifica sfoglina a una lezione, o null se non ha ancora risposto. */
function gs_lezione_risposta_utente( $lid, $uid ) {
	$uid = (int) $uid;
	foreach ( gs_lezione_risposte_tutte( $lid ) as $r ) {
		if ( (int) $r['user_id'] === $uid ) {
			return $r;
		}
	}
	return null;
}

/**
 * Normalizza un testo per il confronto automatico: minuscolo, spazi
 * multipli ridotti a uno, punteggiatura finale tolta. Un confronto
 * "esatto ma indulgente", non un giudizio di significato — per questo le
 * domande di verifica devono avere una risposta breve e univoca (una
 * parola, un numero, una data), non un tema libero.
 */
function gs_risposta_normalizza( $s ) {
	$s = mb_strtolower( trim( (string) $s ) );
	$s = preg_replace( '/\s+/', ' ', $s );
	$s = trim( $s, " .!?,;:" );
	return $s;
}

/** True se la risposta della sfoglina corrisponde alla risposta esatta impostata dal docente. */
function gs_risposta_corretta( $data, $attesa ) {
	if ( '' === trim( (string) $attesa ) ) {
		return false; // nessuna risposta esatta impostata: non giudicabile
	}
	return gs_risposta_normalizza( $data ) === gs_risposta_normalizza( $attesa );
}

// -----------------------------------------------------------------------------
// Lezioni consigliate (assegnate dal titolare a una sfoglina) e promemoria
// se dopo N giorni non risultano ancora viste.
// -----------------------------------------------------------------------------

/** Sfogline a cui questa lezione è stata consigliata. */
function gs_lezione_assegnazioni( $lid ) {
	$a = get_post_meta( $lid, 'gs_assegnazioni', true );
	return is_array( $a ) ? $a : array();
}

/** L'assegnazione di questa lezione per una specifica sfoglina, o null. */
function gs_lezione_assegnazione_utente( $lid, $uid ) {
	$uid = (int) $uid;
	foreach ( gs_lezione_assegnazioni( $lid ) as $a ) {
		if ( (int) $a['user_id'] === $uid ) {
			return $a;
		}
	}
	return null;
}

/** True se la sfoglina ha già visto questa lezione. */
function gs_lezione_e_vista( $lid, $uid ) {
	$viste = get_user_meta( (int) $uid, 'gs_lezioni_viste', true );
	return is_array( $viste ) && in_array( (int) $lid, $viste, true );
}

/** Consiglia una lezione a una sfoglina (se non già consigliata). */
function gs_lezione_assegna( $lid, $uid ) {
	$uid = (int) $uid;
	if ( gs_lezione_assegnazione_utente( $lid, $uid ) ) {
		return false; // già consigliata
	}
	$assegnazioni   = gs_lezione_assegnazioni( $lid );
	$assegnazioni[] = array( 'user_id' => $uid, 'data' => current_time( 'mysql' ), 'promemoria_inviato' => false );
	update_post_meta( $lid, 'gs_assegnazioni', $assegnazioni );
	return true;
}

/** Toglie il consiglio di una lezione per una sfoglina. */
function gs_lezione_assegna_rimuovi( $lid, $uid ) {
	$uid = (int) $uid;
	$assegnazioni = array_values( array_filter( gs_lezione_assegnazioni( $lid ), function ( $a ) use ( $uid ) {
		return (int) $a['user_id'] !== $uid;
	} ) );
	update_post_meta( $lid, 'gs_assegnazioni', $assegnazioni );
}

/**
 * Cron giornaliero: per ogni lezione consigliata e non ancora vista, se sono
 * passati abbastanza giorni dal consiglio, manda un promemoria (una sola
 * volta, come gs_cal_promemoria_domani in calendario.php).
 */
add_action( 'gs_daily_cron', 'gs_lezioni_promemoria_non_viste' );
function gs_lezioni_promemoria_non_viste() {
	$giorni = (int) ( gs_settings()['lezioni']['promemoria_giorni'] ?? 3 );
	if ( $giorni < 1 ) { return; }
	$adesso = current_time( 'timestamp' );

	foreach ( gs_lezioni_tutte() as $l ) {
		$assegnazioni = gs_lezione_assegnazioni( $l->ID );
		if ( ! $assegnazioni ) { continue; }
		$cambiato = false;

		foreach ( $assegnazioni as &$a ) {
			if ( ! empty( $a['promemoria_inviato'] ) ) { continue; }
			$trascorsi = floor( ( $adesso - strtotime( $a['data'] ) ) / DAY_IN_SECONDS );
			if ( $trascorsi < $giorni ) { continue; }

			// Vista nel frattempo: nessun promemoria da mandare, ma segna per non ricontrollare ogni giorno.
			if ( gs_lezione_e_vista( $l->ID, $a['user_id'] ) ) {
				$a['promemoria_inviato'] = true;
				$cambiato = true;
				continue;
			}

			$u = get_userdata( $a['user_id'] );
			if ( $u ) {
				$corpo_promemoria = "Ciao " . $u->display_name . ",\n\nti era stata consigliata la lezione \"" . get_the_title( $l->ID ) . "\" e non risulta ancora vista.\n\nLa trovi nella Libreria Video delle Lezioni, quando vuoi.\n\nAccademia della Sfoglia";
				if ( function_exists( 'gs_mail_progetto' ) ) {
					gs_mail_progetto( $u->ID, 'calendario', 'Una lezione ti aspetta — Accademia della Sfoglia', $corpo_promemoria );
				} elseif ( $u->user_email ) {
					wp_mail( $u->user_email, 'Una lezione ti aspetta — Accademia della Sfoglia', $corpo_promemoria );
				}
			}
			if ( function_exists( 'gs_accoda_volo' ) ) {
				gs_accoda_volo( $a['user_id'], 'UNA LEZIONE TI ASPETTA: ' . mb_strtoupper( get_the_title( $l->ID ) ), gs_pagina_url( 'gs_page_lezioni' ) );
			}
			$a['promemoria_inviato'] = true;
			$cambiato = true;
		}
		unset( $a );

		if ( $cambiato ) {
			update_post_meta( $l->ID, 'gs_assegnazioni', $assegnazioni );
		}
	}
}

/** Tutte le lezioni pubblicate. */
function gs_lezioni_tutte() {
	return gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_lezione',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'suppress_filters' => true,
	) ), 'gs_lezione' );
}

/** Stringa di ricerca (titolo + categoria + descrizione) per il filtro card. */
function gs_lezione_search_string( $c ) {
	return trim( $c['titolo'] . ' ' . $c['categoria'] . ' ' . $c['descrizione'] );
}

// -----------------------------------------------------------------------------
// [gs_lezioni] — lato sfoglina: libreria video (sola lettura)
// -----------------------------------------------------------------------------
add_shortcode( 'gs_lezioni', 'gs_sc_lezioni' );
function gs_sc_lezioni() {
	if ( $g = gs_gate_riservato() ) { return $g; }
	if ( $g = gs_blackout_gate() ) { return $g; }

	$out = gs_box_open( '🎬 Libreria Video delle Lezioni' );
	$out .= gs_sezione_aiuto( 'Cerca per nome o tecnica, poi clicca su "Guarda la lezione" per aprire il video direttamente nella pagina. Aprire una lezione la segna come vista e ti dà qualche punto. Se la lezione ha delle domande di verifica (scritte da Rina o Bruno), le trovi subito sotto al video: rispondi con le tue parole, il sistema ti dice subito se è esatta e in quel caso ti assegna dei punti; un maestro può comunque leggere le tue risposte e lasciarti un riscontro in più. Se vedi "📌 Consigliata per te" su una scheda, un maestro te l\'ha consigliata apposta: se non la apri entro qualche giorno ricevi un promemoria. Se ci sono "Percorsi Guidati", vederli tutti in ordine sblocca un badge dedicato; l\'etichetta accanto al nome del percorso (es. "Base", "Intermedio") indica quanto è impegnativo, ma non cambia l\'ordine di sblocco. Completandoli tutti sblocchi anche il Diploma Finale, stampabile. Alcune lezioni possono comparire con la scritta "🗓️ Disponibile dal…": sono pubblicate a puntate, un pezzo alla volta, e si aprono da sole a partire da quel giorno — torna a trovarle. Se vedi la sezione "🎄 Percorsi Stagionali", sono contenuti speciali disponibili solo per un periodo limitato: aperti a tutti da subito (senza dover finire prima gli altri), ma solo finché dura la finestra — dopo tornano nascosti. Se vedi la sezione "🎽 Percorsi di Squadra", l\'avanzamento è di tutta la tua squadra insieme: basta che una compagna qualsiasi veda una lezione perché conti per il gruppo, e quando la squadra li finisce tutti, il badge e i punti arrivano a ognuna.' );
	$out .= '<p class="gs-hint">Brevi lezioni video con Rina, organizzate per tecnica. Cerca per nome o argomento.</p>';

	$uid = get_current_user_id();
	if ( function_exists( 'gs_percorsi_lezioni_html' ) ) { $out .= gs_percorsi_lezioni_html( $uid ); }

	$lezioni = gs_lezioni_tutte();
	if ( ! $lezioni ) {
		$out .= '<p>Non ci sono ancora lezioni pubblicate. Torna presto!</p>';
	} else {
		$out .= '<input type="text" class="gs-cerca-input" data-target=".gs-lezioni-lista" placeholder="🔍 Cerca per nome o tecnica…" style="width:100%;max-width:420px;margin-bottom:10px">';
		$out .= '<div class="gs-gallery gs-lezioni-lista gs-paginate" data-per-page="9">';
		foreach ( $lezioni as $l ) {
			$c = gs_lezione_get( $l->ID );
			$bloccata    = $uid && function_exists( 'gs_lezione_bloccata_per' ) && gs_lezione_bloccata_per( $l->ID, $uid );
			$non_uscita  = ! gs_lezione_disponibile( $l->ID );
			$out .= '<div class="gs-card" data-nome="' . esc_attr( gs_lezione_search_string( $c ) ) . '"><div class="gs-card-body">';
			if ( $uid && gs_lezione_assegnazione_utente( $l->ID, $uid ) && ! gs_lezione_e_vista( $l->ID, $uid ) ) {
				$out .= '<p class="gs-card-author">📌 Consigliata per te</p>';
			}
			$out .= '<h4>' . esc_html( $c['titolo'] ) . '</h4>';
			if ( $c['categoria'] ) { $out .= '<p class="gs-card-author">🎓 ' . esc_html( $c['categoria'] ) . '</p>'; }
			if ( $bloccata ) {
				$out .= '<p class="gs-hint">🔒 Fa parte di un Percorso Guidato non ancora sbloccato. Guarda le lezioni del percorso precedente, in ordine, per raggiungerla.</p>';
			} elseif ( $non_uscita ) {
				$out .= '<p class="gs-hint">🗓️ Disponibile dal ' . esc_html( gs_lezione_data_uscita_label( $l->ID ) ) . '. Torna a trovarla quel giorno.</p>';
			} else {
				$out .= '<details class="gs-corso-descr gs-lezione-apertura" data-lezione="' . (int) $l->ID . '"><summary>▶️ Guarda la lezione</summary>';
				$out .= gs_video_embed_html( $c['video_url'] );
				if ( $c['descrizione'] ) { $out .= '<p class="gs-hint" style="margin-top:6px">' . nl2br( esc_html( $c['descrizione'] ) ) . '</p>'; }
				$out .= gs_lezione_domande_html( $l->ID, $uid );
				$out .= '</details>';
			}
			$out .= '</div></div>';
		}
		$out .= '</div>';
	}
	$out .= gs_box_close();

	return $out;
}

/** Blocco domande/risposte da mostrare sotto al video, lato sfoglina. */
function gs_lezione_domande_html( $lid, $uid ) {
	$domande = gs_lezione_domande( $lid );
	if ( ! $domande ) {
		return '';
	}
	$mia = $uid ? gs_lezione_risposta_utente( $lid, $uid ) : null;

	$out  = '<div class="gs-todo-riquadro" style="margin-top:10px">';
	$out .= '<p class="gs-hint"><strong>📝 Domande di verifica</strong><br>Rispondi con le tue parole: se la risposta è esatta, il sistema te lo dice subito e ti assegna dei punti.</p>';
	if ( $mia && ! empty( $mia['feedback'] ) ) {
		$out .= '<p class="gs-hint"><strong>Riscontro del maestro:</strong><br>' . nl2br( esc_html( $mia['feedback'] ) ) . '</p>';
	}
	$out .= '<form class="gs-form gs-form-lezione-risposte" data-lezione="' . (int) $lid . '" onsubmit="return false">';
	foreach ( $domande as $d ) {
		$risp = $mia && isset( $mia['risposte'][ $d['id'] ] ) ? $mia['risposte'][ $d['id'] ] : null;
		// Compatibilità con le risposte inviate prima della correzione automatica (formato solo testo).
		$val  = $risp ? ( is_array( $risp ) ? $risp['testo'] : $risp ) : '';
		$out .= '<p><label>' . esc_html( $d['testo'] );
		if ( $risp && is_array( $risp ) ) {
			$out .= $risp['corretta'] ? ' <strong>✅ esatta</strong>' : ' <strong>❌ non esatta, riprova</strong>';
		}
		$out .= '<br><textarea name="risposta_' . esc_attr( $d['id'] ) . '" rows="2" style="width:100%">' . esc_textarea( $val ) . '</textarea></label></p>';
	}
	$out .= '<p><button class="gs-btn gs-btn-sm gs-lezione-risposte-invia">' . ( $mia ? 'Aggiorna le tue risposte' : 'Invia le risposte' ) . '</button> <span class="gs-lezione-risposte-msg gs-richiesta-esito"></span></p>';
	$out .= '</form>';
	$out .= '</div>';
	return $out;
}

// -----------------------------------------------------------------------------
// Statistiche per il pannello del titolare (nessun dato nuovo: solo
// aggregazione di quello che i moduli scrivono già nei meta).
// -----------------------------------------------------------------------------

/** Quante sfogline hanno visto ciascuna lezione: [lezione_id => conteggio]. */
function gs_lezioni_conteggio_viste() {
	$conteggi = array();
	foreach ( get_users( array( 'meta_key' => 'gs_lezioni_viste', 'meta_compare' => 'EXISTS', 'number' => -1 ) ) as $u ) {
		$viste = get_user_meta( $u->ID, 'gs_lezioni_viste', true );
		if ( ! is_array( $viste ) ) { continue; }
		foreach ( $viste as $lid ) {
			$conteggi[ $lid ] = ( isset( $conteggi[ $lid ] ) ? $conteggi[ $lid ] : 0 ) + 1;
		}
	}
	return $conteggi;
}

/** Statistiche di una lezione: viste, risposte ricevute, risposte ancora senza riscontro. */
function gs_lezione_statistiche( $lid, $conteggio_viste ) {
	$risposte        = gs_lezione_risposte_tutte( $lid );
	$senza_riscontro = 0;
	foreach ( $risposte as $r ) {
		if ( empty( $r['feedback'] ) ) { $senza_riscontro++; }
	}
	return array(
		'viste'           => isset( $conteggio_viste[ $lid ] ) ? (int) $conteggio_viste[ $lid ] : 0,
		'risposte'        => count( $risposte ),
		'senza_riscontro' => $senza_riscontro,
	);
}

/** Totale di risposte alle lezioni ancora senza un riscontro del maestro, su tutte le lezioni. */
function gs_lezioni_totale_senza_riscontro() {
	$tot = 0;
	foreach ( gs_lezioni_tutte() as $l ) {
		foreach ( gs_lezione_risposte_tutte( $l->ID ) as $r ) {
			if ( empty( $r['feedback'] ) ) { $tot++; }
		}
	}
	return $tot;
}

// -----------------------------------------------------------------------------
// PANNELLO — gestione lezioni (titolare)
// -----------------------------------------------------------------------------
function gs_pannello_lezioni() {
	if ( ! gs_can_manage() ) { return; }

	echo gs_box_open( 'Libreria Video delle Lezioni', '', 'gs-box-lezioni' );
	echo '<p class="gs-hint">Carica qui le lezioni video di Rina: il video resta su YouTube o Vimeo, incolla solo il link. Il link è facoltativo: puoi anche pubblicare una lezione solo di testo (utile per un percorso "a puntate", scritto un pezzo alla volta). Le lezioni sono subito visibili alle sfogline con abbonamento attivo, nessuna approvazione — a meno che tu non imposti una "Data di uscita" futura: in quel caso restano nascoste e compaiono da sole quel giorno, per dare un motivo di tornare. Per ogni lezione puoi scrivere domande di verifica, consigliarla a una sfoglina specifica (le arriva un promemoria se non la vede) e leggere le risposte ricevute. La tabella "Statistiche" mostra, per ogni lezione, quante l\'hanno vista e quante risposte aspettano ancora un tuo riscontro (le righe più urgenti stanno in cima).</p>';

	echo '<form class="gs-form gs-form-lezione" onsubmit="return false" style="background:var(--gs-uovo);padding:12px;border-radius:6px;margin-bottom:14px">';
	echo '<strong>Nuova lezione</strong>';
	echo '<p><label>Titolo<br><input type="text" name="titolo" autocomplete="off" style="width:100%" required></label></p>';
	echo '<p><label>Tecnica / categoria<br><input type="text" name="categoria" autocomplete="off" style="width:100%" placeholder="Es. Sfoglia a mano, Ripieni, Formati regionali…"></label></p>';
	echo '<p><label>Link video (YouTube o Vimeo) — facoltativo<br><input type="text" name="video_url" autocomplete="off" style="width:100%" placeholder="https://www.youtube.com/watch?v=…"></label></p>';
	echo '<p><label>Descrizione / testo della lezione<br><textarea name="descrizione" rows="3" style="width:100%"></textarea></label></p>';
	echo '<p><label>Data di uscita — facoltativa<br><input type="date" name="data_uscita"></label>';
	echo '<span class="gs-hint">Lasciala vuota per pubblicarla subito. Impostane una futura per farla comparire solo da quel giorno (utile per un percorso "a puntate", pubblicato un pezzo alla volta) — anche senza link video, basta il testo.</span></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-lezione-crea">Pubblica lezione</button> <span class="gs-lezione-msg gs-richiesta-esito"></span></p>';
	echo '</form>';

	$lezioni = gs_lezioni_tutte();

	// Statistiche: quante hanno visto ciascuna lezione, quante hanno risposto,
	// quante risposte aspettano ancora un riscontro. Righe ordinate per
	// "da leggere" decrescente, per vedere subito dove serve attenzione.
	if ( $lezioni ) {
		$conteggio_viste = gs_lezioni_conteggio_viste();
		$righe = array();
		foreach ( $lezioni as $l ) {
			$righe[] = array( 'titolo' => get_the_title( $l ) ) + gs_lezione_statistiche( $l->ID, $conteggio_viste );
		}
		usort( $righe, function ( $a, $b ) { return $b['senza_riscontro'] <=> $a['senza_riscontro']; } );

		echo '<h4>📊 Statistiche</h4>';
		echo '<table class="gs-table gs-paginate" data-per-page="8"><thead><tr><th>Lezione</th><th>Viste</th><th>Risposte</th><th>Da leggere</th></tr></thead><tbody>';
		foreach ( $righe as $r ) {
			echo '<tr><td>' . esc_html( $r['titolo'] ) . '</td><td>' . (int) $r['viste'] . '</td><td>' . (int) $r['risposte'] . '</td>'
				. '<td>' . ( $r['senza_riscontro'] > 0 ? '<strong>' . (int) $r['senza_riscontro'] . '</strong>' : '—' ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	echo '<h4>Lezioni pubblicate (' . count( $lezioni ) . ')</h4>';
	if ( ! $lezioni ) {
		echo '<p class="gs-hint">Nessuna lezione ancora pubblicata.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="8">';
		foreach ( $lezioni as $l ) {
			$c = gs_lezione_get( $l->ID );
			echo '<details class="gs-inbox-item" data-lezione="' . (int) $l->ID . '">';
			echo '<summary class="gs-inbox-oggetto">' . esc_html( $c['titolo'] ) . ( $c['categoria'] ? ' <span class="gs-msg-data">' . esc_html( $c['categoria'] ) . '</span>' : '' ) . ( ! gs_lezione_disponibile( $l->ID ) ? ' <span class="gs-msg-tag">🗓️ dal ' . esc_html( gs_lezione_data_uscita_label( $l->ID ) ) . '</span>' : '' ) . '</summary>';
			echo '<div class="gs-inbox-corpo">';
			echo gs_video_embed_html( $c['video_url'] );
			echo '<form class="gs-form gs-form-lezione-modifica" data-lezione="' . (int) $l->ID . '" onsubmit="return false">';
			echo '<p><label>Titolo<br><input type="text" name="titolo" autocomplete="off" value="' . esc_attr( $c['titolo'] ) . '" style="width:100%"></label></p>';
			echo '<p><label>Tecnica / categoria<br><input type="text" name="categoria" autocomplete="off" value="' . esc_attr( $c['categoria'] ) . '" style="width:100%"></label></p>';
			echo '<p><label>Link video — facoltativo<br><input type="text" name="video_url" autocomplete="off" value="' . esc_attr( $c['video_url'] ) . '" style="width:100%"></label></p>';
			echo '<p><label>Descrizione / testo della lezione<br><textarea name="descrizione" rows="3" style="width:100%">' . esc_textarea( $c['descrizione'] ) . '</textarea></label></p>';
			echo '<p><label>Data di uscita — facoltativa<br><input type="date" name="data_uscita" value="' . esc_attr( $c['data_uscita'] ) . '"></label> <span class="gs-hint">Vuota = subito visibile.</span></p>';
			echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-lezione-salva">Salva modifiche</button> ';
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-lezione-elimina" data-lezione="' . (int) $l->ID . '">Elimina</button> ';
			echo '<span class="gs-lezione-row-msg gs-richiesta-esito"></span></p>';
			echo '</form>';

			// Domande di verifica (scritte da Rina Poletti / Bruno Cingolani), con la risposta esatta per la correzione automatica.
			$domande = gs_lezione_domande( $l->ID );
			echo '<div style="margin-top:12px;padding-top:10px;border-top:1px solid var(--gs-bordo)">';
			echo '<strong>📝 Domande di verifica</strong> <span class="gs-hint">— scrivi anche la risposta esatta: il sistema corregge da solo e assegna i punti quando la sfoglina risponde giusto. Usa risposte brevi e univoche (una parola, un numero, una data).</span>';
			echo '<ul class="gs-todo-list gs-lezione-domande-list" data-lezione="' . (int) $l->ID . '">';
			foreach ( $domande as $d ) {
				$risp_esatta = isset( $d['risposta_esatta'] ) ? $d['risposta_esatta'] : '';
				echo '<li class="gs-todo-item" data-domanda="' . esc_attr( $d['id'] ) . '" style="display:block;padding:8px 0">';
				echo '<div style="display:flex;justify-content:space-between;align-items:center;gap:8px">'
					. '<input type="text" class="gs-lezione-domanda-testo" value="' . esc_attr( $d['testo'] ) . '" style="flex:1">'
					. '<button class="gs-todo-del gs-lezione-domanda-elimina" data-lezione="' . (int) $l->ID . '" data-domanda="' . esc_attr( $d['id'] ) . '" title="Elimina">✕</button></div>';
				echo '<div style="display:flex;gap:8px;margin-top:4px">';
				echo '<input type="text" class="gs-lezione-domanda-risposta-esatta" value="' . esc_attr( $risp_esatta ) . '" placeholder="Risposta esatta…" style="flex:1">';
				echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-lezione-domanda-risposta-salva" data-lezione="' . (int) $l->ID . '" data-domanda="' . esc_attr( $d['id'] ) . '">✎ Salva modifiche</button>';
				echo '</div>';
				echo '<span class="gs-lezione-domanda-riga-msg gs-richiesta-esito"></span>';
				if ( '' === trim( $risp_esatta ) ) { echo '<p class="gs-hint" style="margin:4px 0 0">⚠️ Senza risposta esatta, questa domanda non assegna punti in automatico.</p>'; }
				echo '</li>';
			}
			echo '</ul>';
			if ( ! $domande ) { echo '<p class="gs-hint gs-lezione-domande-vuoto">Nessuna domanda ancora: le sfogline vedranno solo il video.</p>'; }

			$domande_cestino = gs_lezione_domande_cestino( $l->ID );
			echo '<details class="gs-todo-cestino"><summary>🗑️ Domande eliminate (' . count( $domande_cestino ) . ')</summary>';
			if ( ! $domande_cestino ) {
				echo '<p class="gs-hint">Il cestino è vuoto.</p>';
			} else {
				echo '<ul class="gs-todo-list gs-todo-list-cestino">';
				foreach ( array_reverse( $domande_cestino ) as $d ) {
					echo '<li class="gs-todo-item" data-id="' . esc_attr( $d['id'] ) . '">'
						. '<span>' . esc_html( $d['testo'] ) . '</span>'
						. '<button class="gs-todo-ripristina gs-lezione-domanda-ripristina" data-lezione="' . (int) $l->ID . '" data-domanda="' . esc_attr( $d['id'] ) . '" title="Ripristina">↺ Ripristina</button></li>';
				}
				echo '</ul>';
			}
			echo '</details>';

			echo '<div style="display:flex;gap:8px;margin-top:6px;flex-wrap:wrap">';
			echo '<input type="text" class="gs-lezione-domanda-input" placeholder="Nuova domanda…" style="flex:2;min-width:160px" autocomplete="off">';
			echo '<input type="text" class="gs-lezione-domanda-risposta-input" placeholder="Risposta esatta…" style="flex:1;min-width:120px" autocomplete="off">';
			echo '<button class="gs-btn gs-btn-sm gs-lezione-domanda-add" data-lezione="' . (int) $l->ID . '">Aggiungi</button>';
			echo '</div><span class="gs-lezione-domanda-msg gs-richiesta-esito"></span>';
			echo '</div>';

			// Consiglia la lezione a una sfoglina: se dopo N giorni non l'ha vista, un promemoria automatico glielo ricorda.
			$assegnazioni = gs_lezione_assegnazioni( $l->ID );
			$sfogline_tutte = function_exists( 'gs_sez_sfogline' ) ? gs_sez_sfogline() : array();
			echo '<div style="margin-top:12px;padding-top:10px;border-top:1px solid var(--gs-bordo)">';
			echo '<strong>📌 Consigliata a</strong> <span class="gs-hint">— se non la vede entro ' . (int) ( gs_settings()['lezioni']['promemoria_giorni'] ?? 3 ) . ' giorni, le arriva un promemoria automatico.</span>';
			echo '<ul class="gs-todo-list gs-lezione-assegnazioni-list" data-lezione="' . (int) $l->ID . '">';
			foreach ( $assegnazioni as $a ) {
				$au    = get_userdata( $a['user_id'] );
				$vista = gs_lezione_e_vista( $l->ID, $a['user_id'] );
				echo '<li class="gs-todo-item" data-sfoglina="' . (int) $a['user_id'] . '"><span>' . esc_html( $au ? $au->display_name : '—' ) . ' — ' . ( $vista ? '✅ vista' : '⏳ non ancora vista' ) . '</span>'
					. '<button class="gs-todo-del gs-lezione-assegna-rimuovi" data-lezione="' . (int) $l->ID . '" data-sfoglina="' . (int) $a['user_id'] . '" title="Togli il consiglio">✕</button></li>';
			}
			echo '</ul>';
			if ( ! $assegnazioni ) { echo '<p class="gs-hint gs-lezione-assegna-vuoto">Non ancora consigliata a nessuna sfoglina in particolare.</p>'; }
			echo '<div style="display:flex;gap:8px;margin-top:6px">';
			echo '<select class="gs-lezione-assegna-select" style="flex:1"><option value="">Scegli una sfoglina…</option>';
			foreach ( $sfogline_tutte as $u ) { echo '<option value="' . (int) $u->ID . '">' . esc_html( $u->display_name ) . '</option>'; }
			echo '</select>';
			echo '<button class="gs-btn gs-btn-sm gs-lezione-assegna-add" data-lezione="' . (int) $l->ID . '">Consiglia</button>';
			echo '</div><span class="gs-lezione-assegna-msg gs-richiesta-esito"></span>';
			echo '</div>';

			// Risposte ricevute, con spazio per il riscontro del maestro.
			$risposte = gs_lezione_risposte_tutte( $l->ID );
			echo '<div style="margin-top:12px;padding-top:10px;border-top:1px solid var(--gs-bordo)">';
			echo '<strong>Risposte ricevute (' . count( $risposte ) . ')</strong>';
			if ( ! $risposte ) {
				echo '<p class="gs-hint">Nessuna risposta ancora.</p>';
			} else {
				foreach ( $risposte as $r ) {
					$au = get_userdata( $r['user_id'] );
					echo '<div style="border:1px solid var(--gs-bordo);border-radius:6px;padding:10px;margin-top:8px" data-lezione="' . (int) $l->ID . '" data-sfoglina="' . (int) $r['user_id'] . '">';
					echo '<p><strong>' . esc_html( $au ? $au->display_name : '—' ) . '</strong> <span class="gs-msg-data">' . esc_html( $r['data'] ) . '</span></p>';
					foreach ( $domande as $d ) {
						if ( ! isset( $r['risposte'][ $d['id'] ] ) ) { continue; }
						$ris = $r['risposte'][ $d['id'] ];
						// Compatibilità con le risposte inviate prima della correzione automatica (formato solo testo).
						$testo    = is_array( $ris ) ? $ris['testo'] : $ris;
						$corretta = is_array( $ris ) && ! empty( $ris['corretta'] );
						echo '<p><em>' . esc_html( $d['testo'] ) . '</em> ' . ( $corretta ? '✅' : '❌' ) . '<br>' . nl2br( esc_html( $testo ) ) . '</p>';
					}
					echo '<textarea class="gs-lezione-risposta-feedback" rows="2" style="width:100%" placeholder="Il tuo riscontro per questa sfoglina…">' . esc_textarea( $r['feedback'] ?? '' ) . '</textarea>';
					echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-lezione-risposta-feedback-salva">Salva riscontro</button> <span class="gs-lezione-risposta-msg gs-richiesta-esito"></span></p>';
					echo '</div>';
				}
			}
			echo '</div>';

			echo '</div></details>';
		}
		echo '</div>';
	}

	// Cestino.
	$trash = get_posts( array( 'post_type' => 'gs_lezione', 'post_status' => 'trash', 'posts_per_page' => 50, 'suppress_filters' => true ) );
	echo '<div class="gs-sezione-cestino">';
	echo '<h4 class="gs-titolo-cestino">🗑️ Cestino</h4>';
	if ( ! $trash ) {
		echo '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		echo '<table class="gs-table gs-paginate" data-per-page="8"><thead><tr>' . gs_cestino_th_checkbox() . '<th>Lezione</th><th></th></tr></thead><tbody>';
		foreach ( $trash as $l ) {
			echo '<tr data-lezione="' . (int) $l->ID . '">' . gs_cestino_td_checkbox( $l->ID ) . '<td>' . esc_html( get_the_title( $l ) ) . '</td>';
			echo '<td><button class="gs-btn gs-btn-sm gs-btn-verde gs-lezione-ripristina" data-lezione="' . (int) $l->ID . '">Ripristina</button> <span class="gs-lezione-trow-msg gs-richiesta-esito"></span></td></tr>';
		}
		echo '</tbody></table>';
		echo gs_cestino_azioni_bulk( 'gs_lezione' );
	}
	echo '</div>';

	echo gs_box_close();
}

add_action( 'wp_ajax_gs_lezione_crea', 'gs_ajax_lezione_crea' );
function gs_ajax_lezione_crea() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	if ( '' === trim( $titolo ) ) { wp_send_json_error( array( 'message' => 'Scrivi il titolo della lezione.' ) ); }
	$video_url = isset( $_POST['video_url'] ) ? esc_url_raw( wp_unslash( $_POST['video_url'] ) ) : '';

	$categoria   = isset( $_POST['categoria'] ) ? sanitize_text_field( wp_unslash( $_POST['categoria'] ) ) : '';
	$descrizione = isset( $_POST['descrizione'] ) ? sanitize_textarea_field( wp_unslash( $_POST['descrizione'] ) ) : '';
	if ( function_exists( 'gs_msg_clean' ) ) { $descrizione = gs_msg_clean( $descrizione ); }
	if ( '' === trim( $video_url ) && '' === trim( $descrizione ) ) {
		wp_send_json_error( array( 'message' => 'Metti un link video o scrivi un testo per la lezione.' ) );
	}
	$data_uscita = isset( $_POST['data_uscita'] ) ? sanitize_text_field( wp_unslash( $_POST['data_uscita'] ) ) : '';
	if ( $data_uscita && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data_uscita ) ) { $data_uscita = ''; }

	$lid = wp_insert_post( array(
		'post_type'    => 'gs_lezione',
		'post_status'  => 'publish',
		'post_title'   => $titolo,
		'post_content' => $descrizione,
	) );
	if ( is_wp_error( $lid ) || ! $lid ) { wp_send_json_error( array( 'message' => 'Errore nella pubblicazione.' ) ); }

	update_post_meta( $lid, 'gs_categoria', $categoria );
	update_post_meta( $lid, 'gs_video_url', $video_url );
	if ( $data_uscita ) {
		update_post_meta( $lid, 'gs_lezione_data_uscita', $data_uscita );
	}

	wp_send_json_success( array( 'message' => 'Lezione pubblicata.' ) );
}

add_action( 'wp_ajax_gs_lezione_salva', 'gs_ajax_lezione_salva' );
function gs_ajax_lezione_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$lid = isset( $_POST['lezione'] ) ? (int) $_POST['lezione'] : 0;
	if ( 'gs_lezione' !== get_post_type( $lid ) ) { wp_send_json_error( array( 'message' => 'Lezione non valida.' ) ); }

	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	if ( '' === trim( $titolo ) ) { wp_send_json_error( array( 'message' => 'Scrivi il titolo della lezione.' ) ); }
	$categoria   = isset( $_POST['categoria'] ) ? sanitize_text_field( wp_unslash( $_POST['categoria'] ) ) : '';
	$video_url   = isset( $_POST['video_url'] ) ? esc_url_raw( wp_unslash( $_POST['video_url'] ) ) : '';
	$descrizione = isset( $_POST['descrizione'] ) ? sanitize_textarea_field( wp_unslash( $_POST['descrizione'] ) ) : '';
	if ( function_exists( 'gs_msg_clean' ) ) { $descrizione = gs_msg_clean( $descrizione ); }
	if ( '' === trim( $video_url ) && '' === trim( $descrizione ) ) {
		wp_send_json_error( array( 'message' => 'Metti un link video o scrivi un testo per la lezione.' ) );
	}
	$data_uscita = isset( $_POST['data_uscita'] ) ? sanitize_text_field( wp_unslash( $_POST['data_uscita'] ) ) : '';
	if ( $data_uscita && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data_uscita ) ) { $data_uscita = ''; }

	wp_update_post( array( 'ID' => $lid, 'post_title' => $titolo, 'post_content' => $descrizione ) );
	update_post_meta( $lid, 'gs_categoria', $categoria );
	update_post_meta( $lid, 'gs_video_url', $video_url );
	if ( $data_uscita ) {
		update_post_meta( $lid, 'gs_lezione_data_uscita', $data_uscita );
	} else {
		delete_post_meta( $lid, 'gs_lezione_data_uscita' );
	}

	wp_send_json_success( array( 'message' => 'Lezione aggiornata.' ) );
}

// -----------------------------------------------------------------------------
// AJAX — domande di verifica (gestore) e risposte (sfoglina)
// -----------------------------------------------------------------------------

add_action( 'wp_ajax_gs_lezione_domanda_aggiungi', 'gs_ajax_lezione_domanda_aggiungi' );
function gs_ajax_lezione_domanda_aggiungi() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$lid = isset( $_POST['lezione'] ) ? (int) $_POST['lezione'] : 0;
	if ( 'gs_lezione' !== get_post_type( $lid ) ) { wp_send_json_error( array( 'message' => 'Lezione non valida.' ) ); }
	$testo = isset( $_POST['testo'] ) ? sanitize_text_field( wp_unslash( $_POST['testo'] ) ) : '';
	if ( '' === trim( $testo ) ) { wp_send_json_error( array( 'message' => 'Scrivi il testo della domanda.' ) ); }
	$risposta_esatta = isset( $_POST['risposta_esatta'] ) ? sanitize_text_field( wp_unslash( $_POST['risposta_esatta'] ) ) : '';

	$id        = uniqid( 'd' );
	$domande   = gs_lezione_domande( $lid );
	$domande[] = array( 'id' => $id, 'testo' => $testo, 'risposta_esatta' => $risposta_esatta );
	update_post_meta( $lid, 'gs_domande', $domande );

	wp_send_json_success( array( 'message' => 'Domanda aggiunta.', 'id' => $id ) );
}

/** Il docente salva (o corregge) la risposta esatta di una domanda già esistente. */
/**
 * Modifica una domanda di verifica esistente: risposta esatta sempre,
 * testo solo se passato (null = non toccarlo). true se trovata e salvata.
 */
function gs_lezione_domanda_modifica( $lid, $did, $risposta_esatta, $testo = null ) {
	$domande = gs_lezione_domande( $lid );
	$trovata = false;
	foreach ( $domande as &$d ) {
		if ( $d['id'] === $did ) {
			$d['risposta_esatta'] = $risposta_esatta;
			if ( null !== $testo ) { $d['testo'] = $testo; }
			$trovata = true;
			break;
		}
	}
	unset( $d );
	if ( ! $trovata ) { return false; }
	update_post_meta( $lid, 'gs_domande', $domande );
	return true;
}

add_action( 'wp_ajax_gs_lezione_domanda_risposta_salva', 'gs_ajax_lezione_domanda_risposta_salva' );
function gs_ajax_lezione_domanda_risposta_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$lid = isset( $_POST['lezione'] ) ? (int) $_POST['lezione'] : 0;
	if ( 'gs_lezione' !== get_post_type( $lid ) ) { wp_send_json_error( array( 'message' => 'Lezione non valida.' ) ); }
	$did = isset( $_POST['domanda'] ) ? sanitize_text_field( wp_unslash( $_POST['domanda'] ) ) : '';
	$risposta_esatta = isset( $_POST['risposta_esatta'] ) ? sanitize_text_field( wp_unslash( $_POST['risposta_esatta'] ) ) : '';
	$testo = isset( $_POST['testo'] ) ? sanitize_text_field( wp_unslash( $_POST['testo'] ) ) : null;
	if ( null !== $testo && '' === trim( $testo ) ) { wp_send_json_error( array( 'message' => 'La domanda non può essere vuota.' ) ); }
	if ( ! gs_lezione_domanda_modifica( $lid, $did, $risposta_esatta, $testo ) ) { wp_send_json_error( array( 'message' => 'Domanda non trovata.' ) ); }
	wp_send_json_success( array( 'message' => 'Modifiche salvate.' ) );
}

add_action( 'wp_ajax_gs_lezione_domanda_elimina', 'gs_ajax_lezione_domanda_elimina' );
function gs_ajax_lezione_domanda_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$lid = isset( $_POST['lezione'] ) ? (int) $_POST['lezione'] : 0;
	if ( 'gs_lezione' !== get_post_type( $lid ) ) { wp_send_json_error( array( 'message' => 'Lezione non valida.' ) ); }
	$did = isset( $_POST['domanda'] ) ? sanitize_text_field( wp_unslash( $_POST['domanda'] ) ) : '';
	gs_lezione_domanda_sposta_nel_cestino( $lid, $did );
	wp_send_json_success( array( 'message' => 'Domanda spostata nel cestino.' ) );
}

add_action( 'wp_ajax_gs_lezione_domanda_ripristina', 'gs_ajax_lezione_domanda_ripristina' );
function gs_ajax_lezione_domanda_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$lid = isset( $_POST['lezione'] ) ? (int) $_POST['lezione'] : 0;
	if ( 'gs_lezione' !== get_post_type( $lid ) ) { wp_send_json_error( array( 'message' => 'Lezione non valida.' ) ); }
	$did = isset( $_POST['domanda'] ) ? sanitize_text_field( wp_unslash( $_POST['domanda'] ) ) : '';
	gs_lezione_domanda_ripristina_dal_cestino( $lid, $did );
	wp_send_json_success( array( 'message' => 'Domanda ripristinata.' ) );
}

/** La sfoglina invia (o aggiorna) le sue risposte alle domande di una lezione. */
add_action( 'wp_ajax_gs_lezione_risposte_invia', 'gs_ajax_lezione_risposte_invia' );
function gs_ajax_lezione_risposte_invia() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }
	$lid = isset( $_POST['lezione'] ) ? (int) $_POST['lezione'] : 0;
	if ( 'gs_lezione' !== get_post_type( $lid ) ) { wp_send_json_error( array( 'message' => 'Lezione non valida.' ) ); }

	$domande = gs_lezione_domande( $lid );
	if ( ! $domande ) { wp_send_json_error( array( 'message' => 'Questa lezione non ha domande.' ) ); }

	$precedente = gs_lezione_risposta_utente( $lid, $uid );

	$risposte_nuove = array();
	$punti_ora      = 0;
	foreach ( $domande as $d ) {
		$val      = isset( $_POST[ 'risposta_' . $d['id'] ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ 'risposta_' . $d['id'] ] ) ) : '';
		$corretta = gs_risposta_corretta( $val, isset( $d['risposta_esatta'] ) ? $d['risposta_esatta'] : '' );
		$gia_assegnati = $precedente && isset( $precedente['risposte'][ $d['id'] ]['punti_assegnati'] ) && $precedente['risposte'][ $d['id'] ]['punti_assegnati'];

		if ( $corretta && ! $gia_assegnati ) {
			gs_add_points( $uid, gs_get_points_value( 'risposta_esatta', 10 ), 'Risposta esatta: ' . $d['testo'] );
			$punti_ora += gs_get_points_value( 'risposta_esatta', 10 );
			$gia_assegnati = true;
		}
		$risposte_nuove[ $d['id'] ] = array( 'testo' => $val, 'corretta' => $corretta, 'punti_assegnati' => $gia_assegnati );
	}

	$tutte = gs_lezione_risposte_tutte( $lid );
	$trovata = false;
	foreach ( $tutte as &$r ) {
		if ( (int) $r['user_id'] === $uid ) {
			$r['risposte'] = $risposte_nuove;
			$r['data']     = current_time( 'mysql' );
			$r['feedback'] = ''; // una nuova risposta va riletta dal maestro
			$trovata = true;
			break;
		}
	}
	unset( $r );
	if ( ! $trovata ) {
		$tutte[] = array( 'user_id' => $uid, 'risposte' => $risposte_nuove, 'data' => current_time( 'mysql' ), 'feedback' => '' );
	}
	update_post_meta( $lid, 'gs_risposte', $tutte );

	if ( function_exists( 'gs_inbox_crea' ) ) {
		$u = get_userdata( $uid );
		gs_inbox_crea( 'Risposte alla lezione: ' . get_the_title( $lid ), ( $u ? $u->display_name : '' ) . ' ha risposto alle domande della lezione "' . get_the_title( $lid ) . '".', array( 'from' => $u ? $u->display_name : 'Sfoglina' ) );
	}

	$msg = 'Risposte inviate. Grazie!';
	if ( $punti_ora > 0 ) { $msg .= ' +' . $punti_ora . ' punti per le risposte esatte.'; }
	wp_send_json_success( array( 'message' => $msg ) );
}

/** Il docente lascia un riscontro sulle risposte di una sfoglina. */
add_action( 'wp_ajax_gs_lezione_risposta_feedback', 'gs_ajax_lezione_risposta_feedback' );
function gs_ajax_lezione_risposta_feedback() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$lid = isset( $_POST['lezione'] ) ? (int) $_POST['lezione'] : 0;
	if ( 'gs_lezione' !== get_post_type( $lid ) ) { wp_send_json_error( array( 'message' => 'Lezione non valida.' ) ); }
	$target_uid = isset( $_POST['sfoglina'] ) ? (int) $_POST['sfoglina'] : 0;
	$feedback   = isset( $_POST['feedback'] ) ? sanitize_textarea_field( wp_unslash( $_POST['feedback'] ) ) : '';

	$tutte = gs_lezione_risposte_tutte( $lid );
	$trovata = false;
	foreach ( $tutte as &$r ) {
		if ( (int) $r['user_id'] === $target_uid ) {
			$r['feedback'] = $feedback;
			$trovata = true;
			break;
		}
	}
	unset( $r );
	if ( ! $trovata ) { wp_send_json_error( array( 'message' => 'Risposta non trovata.' ) ); }
	update_post_meta( $lid, 'gs_risposte', $tutte );

	$au = get_userdata( $target_uid );
	if ( $au ) {
		$oggetto_feedback = 'Riscontro sulla lezione: ' . get_the_title( $lid ) . ' — Accademia della Sfoglia';
		$corpo_feedback   = "Ciao " . $au->display_name . ",\n\nun maestro ha letto le tue risposte alla lezione \"" . get_the_title( $lid ) . "\" e ha lasciato un riscontro:\n\n" . $feedback;
		if ( function_exists( 'gs_mail_progetto' ) ) {
			gs_mail_progetto( $target_uid, 'messaggi', $oggetto_feedback, $corpo_feedback );
		} elseif ( $au->user_email ) {
			wp_mail( $au->user_email, $oggetto_feedback, $corpo_feedback );
		}
	}
	if ( function_exists( 'gs_accoda_volo' ) ) {
		gs_accoda_volo( $target_uid, 'UN MAESTRO HA LETTO LE TUE RISPOSTE SU: ' . mb_strtoupper( get_the_title( $lid ) ), gs_pagina_url( 'gs_page_lezioni' ) );
	}

	wp_send_json_success( array( 'message' => 'Riscontro salvato.' ) );
}

add_action( 'wp_ajax_gs_lezione_elimina', 'gs_ajax_lezione_elimina' );
function gs_ajax_lezione_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$lid = isset( $_POST['lezione'] ) ? (int) $_POST['lezione'] : 0;
	if ( 'gs_lezione' !== get_post_type( $lid ) ) { wp_send_json_error( array( 'message' => 'Lezione non valida.' ) ); }
	wp_trash_post( $lid );
	wp_send_json_success( array( 'message' => 'Spostata nel cestino.' ) );
}

add_action( 'wp_ajax_gs_lezione_segna_vista', 'gs_ajax_lezione_segna_vista' );
function gs_ajax_lezione_segna_vista() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }
	$lid = isset( $_POST['lezione'] ) ? (int) $_POST['lezione'] : 0;
	if ( 'gs_lezione' !== get_post_type( $lid ) ) { wp_send_json_error( array( 'message' => 'Lezione non valida.' ) ); }
	if ( function_exists( 'gs_lezione_bloccata_per' ) && gs_lezione_bloccata_per( $lid, $uid ) ) {
		wp_send_json_error( array( 'message' => 'Questa lezione fa parte di un Percorso Guidato non ancora sbloccato.' ) );
	}
	if ( ! gs_lezione_disponibile( $lid ) ) {
		wp_send_json_error( array( 'message' => 'Questa lezione non è ancora disponibile: torna dal ' . gs_lezione_data_uscita_label( $lid ) . '.' ) );
	}

	// Lucchetto di MySQL per sfoglina+lezione, stesso meccanismo già provato
	// su SiteGround per i posti dei corsi (calendario.php:571 — "un corso
	// con 3 posti ne ha accettate 4"). Il freno nel JavaScript (vedi
	// gaming.js, evento "toggle") chiude il caso concreto e sistematico
	// (SiteGround Optimizer che esegue il file due volte); questo lucchetto
	// chiude anche il caso raro di due schede/dispositivi aperti insieme —
	// leggero, riguarda solo questa sfoglina su questa lezione, dura
	// millesimi di secondo (trovato il 25/08/2026, corretto il 26/08/2026:
	// un contrassegno da solo NON basta, vedi sotto).
	global $wpdb;
	$lock_lezione = 'gs_lez_' . $uid . '_' . (int) $lid;
	if ( '1' !== $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock_lezione ) ) ) {
		wp_send_json_success(); // ci sta già pensando un'altra richiesta
	}
	// I meta utente sono già in cache dall'inizio della richiesta (WordPress
	// li carica tutti insieme al riconoscimento di chi è collegato): senza
	// svuotarla, get_user_meta() qui sotto risponderebbe da quella
	// fotografia vecchia invece di leggere dal database, e il lucchetto non
	// servirebbe a niente — stesso wp_cache_delete() già usato in
	// gs_add_points() (points.php) dopo i suoi incrementi atomici.
	wp_cache_delete( $uid, 'user_meta' );

	// Contrassegno per lezione, indipendente dall'array gs_lezioni_viste:
	// una chiave sola invece di un array è quello che il lucchetto sopra
	// protegge davvero. Ma un contrassegno da solo, senza riparazione, apre
	// un vicolo cieco: se una richiesta si interrompe DOPO aver scritto
	// questo contrassegno e PRIMA di scrivere gs_lezioni_viste (connessione
	// caduta, timeout), la lezione resta "non vista" per sempre — riaperta,
	// il contrassegno la respinge in silenzio, e gs_percorso_completato_da()
	// (che legge l'array, non questo contrassegno) non vedrà mai quel
	// percorso completo. Per questo, se il contrassegno c'è ma l'elenco no,
	// si rimette a posto l'elenco invece di respingere e basta — la
	// sfoglina perde i 5 punti di quella lezione (non recuperabili), ma non
	// perde il percorso né il Diploma Finale.
	$chiave_vista = 'gs_lezione_vista_' . (int) $lid;
	$viste        = get_user_meta( $uid, 'gs_lezioni_viste', true );
	if ( ! is_array( $viste ) ) { $viste = array(); }
	if ( get_user_meta( $uid, $chiave_vista, true ) ) {
		if ( ! in_array( $lid, $viste, true ) ) {
			$viste[] = $lid;
			update_user_meta( $uid, 'gs_lezioni_viste', $viste );
		}
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_lezione ) );
		wp_send_json_success();
	}
	update_user_meta( $uid, $chiave_vista, current_time( 'mysql' ) );

	if ( ! in_array( $lid, $viste, true ) ) {
		$viste[] = $lid;
		update_user_meta( $uid, 'gs_lezioni_viste', $viste );
		gs_add_points( $uid, gs_get_points_value( 'lezione_vista', 5 ), 'Lezione video guardata: ' . get_the_title( $lid ) );
		do_action( 'gs_lezione_vista', $uid );
	}
	$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_lezione ) );
	wp_send_json_success();
}

add_action( 'wp_ajax_gs_lezione_ripristina', 'gs_ajax_lezione_ripristina' );
function gs_ajax_lezione_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$lid = isset( $_POST['lezione'] ) ? (int) $_POST['lezione'] : 0;
	if ( 'gs_lezione' !== get_post_type( $lid ) ) { wp_send_json_error( array( 'message' => 'Lezione non valida.' ) ); }
	wp_untrash_post( $lid );
	wp_send_json_success( array( 'message' => 'Lezione ripristinata.' ) );
}

add_action( 'wp_ajax_gs_lezione_assegna', 'gs_ajax_lezione_assegna' );
function gs_ajax_lezione_assegna() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$lid = isset( $_POST['lezione'] ) ? (int) $_POST['lezione'] : 0;
	if ( 'gs_lezione' !== get_post_type( $lid ) ) { wp_send_json_error( array( 'message' => 'Lezione non valida.' ) ); }
	$uid = isset( $_POST['sfoglina'] ) ? (int) $_POST['sfoglina'] : 0;
	if ( ! $uid || ! get_userdata( $uid ) ) { wp_send_json_error( array( 'message' => 'Scegli una sfoglina.' ) ); }

	if ( ! gs_lezione_assegna( $lid, $uid ) ) {
		wp_send_json_error( array( 'message' => 'Questa lezione è già consigliata a questa sfoglina.' ) );
	}
	if ( function_exists( 'gs_accoda_volo' ) ) {
		gs_accoda_volo( $uid, 'TI È STATA CONSIGLIATA UNA LEZIONE: ' . mb_strtoupper( get_the_title( $lid ) ), gs_pagina_url( 'gs_page_lezioni' ) );
	}
	wp_send_json_success( array( 'message' => 'Lezione consigliata.' ) );
}

add_action( 'wp_ajax_gs_lezione_assegna_rimuovi', 'gs_ajax_lezione_assegna_rimuovi' );
function gs_ajax_lezione_assegna_rimuovi() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$lid = isset( $_POST['lezione'] ) ? (int) $_POST['lezione'] : 0;
	if ( 'gs_lezione' !== get_post_type( $lid ) ) { wp_send_json_error( array( 'message' => 'Lezione non valida.' ) ); }
	$uid = isset( $_POST['sfoglina'] ) ? (int) $_POST['sfoglina'] : 0;
	gs_lezione_assegna_rimuovi( $lid, $uid );
	wp_send_json_success( array( 'message' => 'Consiglio rimosso.' ) );
}
