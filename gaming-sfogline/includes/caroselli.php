<?php
/**
 * caroselli.php — Tre caroselli scorrevoli copiabili con uno shortcode in
 * qualunque pagina del sito (Home Page compresa): sfogline iscritte,
 * Artigiani della Pasta, Scuole di Cucina. Riusano le stesse schede già
 * disegnate altrove (.gs-card per le sfogline, .gs-art-card per Artigiani e
 * Scuole) dentro un involucro comune scorrevole con frecce e puntini.
 *
 * Non c'è un "contenuto" da gestire qui dentro: i dati sono sempre gli stessi
 * già gestiti altrove (approvazione sfogline, Artigiani, Scuole) — il
 * pannello "Caroselli per la Home Page" serve solo a copiare lo shortocode
 * giusto e a scegliere quante schede mostrare.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gs_carosello_impostazioni() {
	$s = gs_settings();
	$d = array(
		'max_sfogline'  => 12,
		'max_artigiani' => 10,
		'max_scuole'    => 10,
		// Scorrimento automatico, uguale per tutti e tre i caroselli: si
		// ferma da solo al passaggio del mouse/dito sopra, e riparte quando
		// ci si allontana (richiesto da Ennio il 2026-08-11).
		'autoplay'      => 1,
		'velocita'      => 4, // secondi tra un passaggio automatico e l'altro
		// Solo per il carosello sfogline: ordine casuale a ogni caricamento
		// della pagina per chi NON ha attivato la Vetrina a token — chi l'ha
		// attivata resta sempre in prima fila, ordinata per cognome (richiesto
		// da Ennio il 2026-08-11).
		'ordine_casuale' => 0,
	);
	$c = isset( $s['caroselli'] ) && is_array( $s['caroselli'] ) ? $s['caroselli'] : array();
	return wp_parse_args( $c, $d );
}

/**
 * Involucro comune: titolo, sottotitolo, pista scorrevole con le schede già
 * pronte (passate come HTML), frecce e puntini. $n_schede serve solo per
 * decidere quanti puntini disegnare (una stima, non un conteggio esatto di
 * "pagine" — lo scorrimento è libero, i puntini sono solo decorativi).
 * Lo scorrimento automatico (attivo/velocità) si legge dalle impostazioni
 * del pannello "Caroselli per la Home Page", uguale per tutti e tre.
 */
function gs_carosello_html( $titolo, $sottotitolo, $schede_html, $n_schede, $classe_extra = '' ) {
	if ( ! $n_schede ) {
		return '';
	}
	$id  = 'gs-car-' . wp_generate_password( 6, false, false );
	$n_punti = min( 6, max( 1, (int) ceil( $n_schede / 3 ) ) );
	$cfg = gs_carosello_impostazioni();
	$autoplay = ( $n_schede > 1 && ! empty( $cfg['autoplay'] ) ) ? 1 : 0;
	$velocita = max( 1, (int) $cfg['velocita'] );

	$out  = '<div class="gs-carosello-wrap' . ( $classe_extra ? ' ' . esc_attr( $classe_extra ) : '' ) . '"'
		. ( $autoplay ? ' data-autoplay="1" data-velocita="' . esc_attr( $velocita ) . '"' : '' ) . '>';
	$out .= '<p class="gs-carosello-titolo">' . esc_html( $titolo ) . '</p>';
	if ( $sottotitolo ) {
		$out .= '<p class="gs-carosello-sottotitolo">' . esc_html( $sottotitolo ) . '</p>';
	}
	if ( $n_schede > 1 ) {
		$out .= '<button type="button" class="gs-carosello-freccia prev" data-target="' . esc_attr( $id ) . '" aria-label="Indietro">‹</button>';
		$out .= '<button type="button" class="gs-carosello-freccia next" data-target="' . esc_attr( $id ) . '" aria-label="Avanti">›</button>';
	}
	$out .= '<div class="gs-carosello-pista" id="' . esc_attr( $id ) . '">' . $schede_html . '</div>';
	if ( $n_schede > 1 ) {
		$out .= '<div class="gs-carosello-punti">';
		for ( $i = 0; $i < $n_punti; $i++ ) {
			$out .= '<span' . ( 0 === $i ? ' class="attivo"' : '' ) . '></span>';
		}
		$out .= '</div>';
	}
	$out .= '</div>';
	return $out;
}

/**
 * Una scheda in stile "Vetrina dei Corsi" (.gs-vc-oggetto: icona/foto su
 * fondo colorato, titolo, testo, cartellino) — richiesta da Ennio il
 * 19/08/2026 per i tre caroselli sotto, così le schede sono uguali e
 * lineari sui tre fronti (Sfogline/Artigiani/Scuole), non ognuna con
 * un suo stile diverso. $foto_url vuoto = mostra $icona su un fondo
 * sfumato dei due colori $c1/$c2; $foto_url pieno = mostra la foto vera.
 */
function gs_carosello_scheda_html( $href, $foto_url, $icona, $c1, $c2, $titolo, $testo, $tag ) {
	$bg = $foto_url
		? 'background-image:url(\'' . esc_url( $foto_url ) . '\')'
		: 'background:linear-gradient(150deg,' . esc_attr( $c1 ) . ',' . esc_attr( $c2 ) . ')';
	$tag_apre = $href ? '<a class="gs-vc-oggetto" href="' . esc_url( $href ) . '" style="text-decoration:none;display:block">' : '<div class="gs-vc-oggetto gs-vc-oggetto-statico">';
	$tag_chiude = $href ? '</a>' : '</div>';

	$out  = $tag_apre;
	$out .= '<div class="gs-vc-ph" style="' . esc_attr( $bg ) . '">' . ( $foto_url ? '' : $icona ) . '</div>';
	$out .= '<p class="gs-vc-titolo" style="color:#4a3a28 !important">' . esc_html( $titolo ) . '</p>';
	$out .= '<p class="gs-vc-luogo" style="color:#8a7a5c !important">' . esc_html( $testo ) . '</p>';
	if ( $tag ) {
		$out .= '<span class="gs-vc-cartellino">' . esc_html( $tag ) . '</span>';
	}
	$out .= $tag_chiude;
	return $out;
}

/**
 * Una scheda "gs-cs" (Carosello Sfogline) — veste nuova richiesta da Ennio
 * il 21/08/2026, grafica ripresa da un file di riferimento consegnato
 * ("Carosello 2"), adattata per mostrare i dati reali della sfoglina invece
 * dei corsi: livello, punti, streak, badge, avanzamento al livello
 * successivo, link alla Vetrina (solo se attiva). $indice serve alla
 * finestra di dettaglio del JS per recuperare i dati della sfoglina giusta.
 */
function gs_carosello_sfoglina_scheda_html( $u, $indice ) {
	$level  = gs_get_level( $u->ID );
	$streak = (int) gs_get_streak( $u->ID );
	$badges = function_exists( 'gs_get_user_badges' ) ? gs_get_user_badges( $u->ID ) : array();
	$punti  = (int) get_user_meta( $u->ID, 'gs_points', true );
	// La foto della scheda è SOLO quella della biografia (richiesto da
	// Ennio il 17/08/2026): prima veniva presa dall'ultimo lavoro caricato
	// in "gs_sfoglia", che poteva mostrare una foto qualsiasi invece del
	// volto della sfoglina.
	$thumb  = function_exists( 'gs_bio_foto_url' ) ? gs_bio_foto_url( $u->ID ) : '';
	$attiva = gs_vetrina_disponibile( $u->ID );
	$bg     = $thumb ? 'background-image:url(\'' . esc_url( $thumb ) . '\')' : 'background:linear-gradient(150deg,#2d8a4e,#164d27)';

	// Stessa struttura (e quindi stessa altezza) delle schede semplici di
	// Artigiani/Scuole/Corsi — richiesto da Ennio il 21/08/2026 ("stessa
	// grandezza di quelle dei corsi"): niente più riga streak separata né
	// barra di avanzamento in scheda, quei dati restano nella finestra di
	// dettaglio al clic (vedi gs-cs__finestra-dati più sotto).
	$out  = '<article class="gs-cs__scheda" data-livello="' . (int) $level['index'] . '" data-attiva="' . ( $attiva ? '1' : '0' ) . '" data-indice="' . (int) $indice . '">';
	$out .= '<div class="gs-cs__figura" style="' . esc_attr( $bg ) . '"><div class="gs-cs__cartellini"><span class="gs-cs__etichetta">' . esc_html( $level['simbolo'] . ' ' . $level['titolo'] ) . '</span></div></div>';
	$out .= '<div class="gs-cs__corpo">';
	$out .= '<h3 class="gs-cs__nome">' . esc_html( $u->display_name ) . '</h3>';
	$out .= '<p class="gs-cs__descrizione">🔥 ' . $streak . ' sett. · 🎖️ ' . count( $badges ) . ' badge · ' . $punti . ' punti</p>';
	$out .= '<div class="gs-cs__azioni">';
	$out .= $attiva
		? '<a class="gs-cs__link" href="' . esc_url( gs_vetrina_url( $u->ID ) ) . '">Vai alla Vetrina <span aria-hidden="true">→</span></a>'
		: '<span class="gs-cs__link is-disattivo">Vetrina non attiva</span>';
	$out .= '<button type="button" class="gs-cs__dettagli" data-apri-scheda="' . (int) $indice . '">Dettagli</button>';
	$out .= '</div></div></article>';
	return $out;
}

/**
 * [gs_carosello_sfogline] — tutte le sfogline approvate, veste "gs-cs"
 * (livello, streak, badge, punti, avanzamento, Vetrina): cliccabile solo
 * per chi ha attivato la Vetrina coi token, come nella pagina normale.
 * Sostituisce le vecchie schede .gs-vc-oggetto SOLO qui — Artigiani e
 * Scuole restano con lo stile precedente, non toccato.
 */
add_shortcode( 'gs_carosello_sfogline', 'gs_sc_carosello_sfogline' );
function gs_sc_carosello_sfogline() {
	if ( $g = gs_blackout_gate() ) { return $g; }
	$max = (int) gs_carosello_impostazioni()['max_sfogline'];

	$sfogline = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) );
	$attive = array();
	$altre  = array();
	foreach ( $sfogline as $u ) {
		if ( ! gs_e_sfoglina_vera( $u ) ) { continue; }
		if ( gs_vetrina_disponibile( $u->ID ) ) { $attive[] = $u; } else { $altre[] = $u; }
	}
	usort( $attive, function ( $a, $b ) { return strcasecmp( gs_cognome_da_nome( $a->display_name ), gs_cognome_da_nome( $b->display_name ) ); } );
	if ( ! empty( gs_carosello_impostazioni()['ordine_casuale'] ) ) {
		shuffle( $altre ); // chi ha attivato la Vetrina resta comunque sempre in prima fila.
	}
	$sfogline = array_slice( array_merge( $attive, $altre ), 0, $max ? $max : 12 );
	if ( ! $sfogline ) { return ''; }

	$livelli = gs_settings()['levels'];
	$dati_js = array();
	$schede  = '';
	foreach ( $sfogline as $i => $u ) {
		$schede .= gs_carosello_sfoglina_scheda_html( $u, $i );
		$level  = gs_get_level( $u->ID );
		$badges = function_exists( 'gs_get_user_badges' ) ? gs_get_user_badges( $u->ID ) : array();
		$defs   = function_exists( 'gs_get_badges_definitions' ) ? gs_get_badges_definitions() : array();
		$nomi_badge = array();
		foreach ( $badges as $chiave ) {
			if ( isset( $defs[ $chiave ] ) ) { $nomi_badge[] = $defs[ $chiave ]['label']; }
		}
		$dati_js[] = array(
			'nome'    => $u->display_name,
			'livello' => $level['simbolo'] . ' ' . $level['titolo'],
			'punti'   => (int) get_user_meta( $u->ID, 'gs_points', true ),
			'streak'  => (int) gs_get_streak( $u->ID ),
			'badge'   => count( $badges ),
			'badgeNomi' => $nomi_badge,
			'attiva'  => gs_vetrina_disponibile( $u->ID ) ? 1 : 0,
			'url'     => gs_vetrina_disponibile( $u->ID ) ? gs_vetrina_url( $u->ID ) : '',
		);
	}

	$out  = '<section class="gs-cs" aria-roledescription="carosello" aria-label="Le Sfogline dell\'Accademia" data-gs-cs-dati=\'' . esc_attr( wp_json_encode( $dati_js ) ) . '\'>';
	$out .= '<div class="gs-cs__testata"><div><p class="gs-cs__occhiello">In Vetrina</p><h2 class="gs-cs__titolo">👭 Le Sfogline dell\'Accademia</h2></div>';
	$out .= '<div class="gs-cs__frecce"><button type="button" class="gs-cs__bottone gs-cs__prec" aria-label="Sfogline precedenti">‹</button><button type="button" class="gs-cs__bottone gs-cs__succ" aria-label="Sfogline successive">›</button></div></div>';

	$out .= '<div class="gs-cs__comandi"><div class="gs-cs__filtri" role="group" aria-label="Filtra per livello"><button type="button" class="gs-cs__filtro is-active" data-filtro="tutte">Tutte</button>';
	foreach ( $livelli as $i => $lv ) {
		$out .= '<button type="button" class="gs-cs__filtro" data-filtro="' . (int) $i . '">' . esc_html( $lv['simbolo'] . ' ' . $lv['titolo'] ) . '</button>';
	}
	$out .= '</div><label class="gs-cs__interruttore"><input type="checkbox" class="gs-cs__solo-attive"> Solo con Vetrina attiva</label></div>';

	$out .= '<div class="gs-cs__carrello" tabindex="0" role="group" aria-label="Elenco sfogline, scorri orizzontalmente">' . $schede . '</div>';
	$out .= '<div class="gs-cs__barra"></div>';

	$out .= '<dialog class="gs-cs__finestra"><div class="gs-cs__finestra-testata"><div><p class="gs-cs__finestra-occhiello">In Vetrina</p><h3 class="gs-cs__finestra-titolo"></h3></div>';
	$out .= '<button type="button" class="gs-cs__chiudi" aria-label="Chiudi">✕</button></div>';
	$out .= '<div class="gs-cs__finestra-corpo"><dl class="gs-cs__finestra-dati"></dl><div><h4>Badge sbloccati</h4><ul class="gs-cs__badge"></ul></div></div>';
	$out .= '<div class="gs-cs__finestra-piede"><a class="gs-cs__cta" href="#">Vai alla Vetrina</a></div></dialog>';
	$out .= '</section>';
	return $out;
}

/**
 * Una scheda semplice in veste "gs-cs" (senza progresso/filtri/dettaglio —
 * quelli esistono solo per le sfogline, che hanno livello/punti/badge):
 * usata da Artigiani, Scuole e Corsi, così tutti e quattro i caroselli di
 * "In Vetrina" hanno la stessa grafica (21/08/2026, richiesto da Ennio
 * "applica lo stesso stile agli artigiani e alle scuole" [e ai corsi]).
 */
function gs_carosello_gs_cs_scheda_semplice_html( $href, $foto, $icona, $c1, $c2, $etichetta, $nome, $descrizione ) {
	$bg = $foto ? 'background-image:url(\'' . esc_url( $foto ) . '\')' : 'background:linear-gradient(150deg,' . esc_attr( $c1 ) . ',' . esc_attr( $c2 ) . ')';
	$tag_apre = $href ? '<a class="gs-cs__scheda" href="' . esc_url( $href ) . '">' : '<div class="gs-cs__scheda gs-cs__scheda-statica">';
	$tag_chiude = $href ? '</a>' : '</div>';

	$out  = $tag_apre;
	$out .= '<div class="gs-cs__figura" style="' . esc_attr( $bg ) . '">' . ( $foto ? '' : '<div class="gs-cs__icona-ph">' . $icona . '</div>' );
	if ( $etichetta ) { $out .= '<div class="gs-cs__cartellini"><span class="gs-cs__etichetta">' . esc_html( $etichetta ) . '</span></div>'; }
	$out .= '</div>';
	$out .= '<div class="gs-cs__corpo">';
	$out .= '<h3 class="gs-cs__nome">' . esc_html( $nome ) . '</h3>';
	if ( $descrizione ) { $out .= '<p class="gs-cs__descrizione">' . esc_html( $descrizione ) . '</p>'; }
	if ( $href ) { $out .= '<div class="gs-cs__azioni"><span class="gs-cs__link">Vai alla scheda <span aria-hidden="true">→</span></span></div>'; }
	$out .= '</div>' . $tag_chiude;
	return $out;
}

/**
 * Involucro comune "gs-cs" con testata + carrello scorrevole (frecce,
 * scorrimento automatico) — usato da Artigiani, Scuole e Sfogline. I Corsi
 * (5 fissi) usano invece gs_carosello_gs_cs_statico_html() più sotto, senza
 * scorrimento.
 */
function gs_carosello_gs_cs_html( $occhiello, $titolo, $schede_html, $n_schede ) {
	if ( ! $n_schede ) { return ''; }
	$out  = '<section class="gs-cs" aria-roledescription="carosello" aria-label="' . esc_attr( $titolo ) . '">';
	$out .= '<div class="gs-cs__testata"><div><p class="gs-cs__occhiello">' . esc_html( $occhiello ) . '</p><h2 class="gs-cs__titolo">' . esc_html( $titolo ) . '</h2></div>';
	if ( $n_schede > 1 ) {
		$out .= '<div class="gs-cs__frecce"><button type="button" class="gs-cs__bottone gs-cs__prec" aria-label="Precedenti">‹</button><button type="button" class="gs-cs__bottone gs-cs__succ" aria-label="Successivi">›</button></div>';
	}
	$out .= '</div>';
	$out .= '<div class="gs-cs__carrello" tabindex="0" role="group" aria-label="' . esc_attr( $titolo ) . ', scorri orizzontalmente">' . $schede_html . '</div>';
	$out .= '</section>';
	return $out;
}

/**
 * Involucro statico "gs-cs" (niente frecce/scorrimento/autoplay) — solo per
 * i Corsi: restano 5 schede fisse che vanno a capo su più righe (Ennio,
 * 19/08/2026: "i corsi sono 5 non deve scorrere nulla" — vale ancora).
 */
function gs_carosello_gs_cs_statico_html( $occhiello, $titolo, $sottotitolo, $schede_html ) {
	$out  = '<section class="gs-cs" aria-label="' . esc_attr( $titolo ) . '">';
	$out .= '<div class="gs-cs__testata"><div><p class="gs-cs__occhiello">' . esc_html( $occhiello ) . '</p><h2 class="gs-cs__titolo">' . esc_html( $titolo ) . '</h2></div></div>';
	if ( $sottotitolo ) { $out .= '<p class="gs-cs__sottotitolo">' . esc_html( $sottotitolo ) . '</p>'; }
	$out .= '<div class="gs-cs__carrello gs-cs__carrello-statico">' . $schede_html . '</div>';
	$out .= '</section>';
	return $out;
}

/**
 * [gs_carosello_artigiani] — Artigiani della Pasta con vetrina pubblicata,
 * veste "gs-cs" (21/08/2026).
 */
add_shortcode( 'gs_carosello_artigiani', 'gs_sc_carosello_artigiani' );
function gs_sc_carosello_artigiani() {
	if ( ! function_exists( 'gs_art_elenco' ) ) { return ''; }
	$max = (int) gs_carosello_impostazioni()['max_artigiani'];
	$attivi = array_values( array_filter( gs_art_elenco(), function ( $p ) { return gs_art_pubblicata( $p->ID ); } ) );
	$attivi = array_slice( $attivi, 0, $max ? $max : 10 );

	$schede = '';
	foreach ( $attivi as $p ) {
		$a = gs_art_get( $p->ID );
		$foto = $a['logo'] ? $a['logo'] : ( ! empty( $a['media'][0]['url'] ) ? $a['media'][0]['url'] : '' );
		$schede .= gs_carosello_gs_cs_scheda_semplice_html(
			gs_art_url( $a['id'] ),
			$foto,
			'🍝',
			'#bd6d5e', '#7a3d33',
			$a['comune'] ? $a['comune'] : '',
			$a['nome'],
			$a['testo'] ? wp_trim_words( $a['testo'], 16 ) : ''
		);
	}

	return gs_carosello_gs_cs_html( 'In Vetrina', '🍝 Gli Artigiani della Pasta', $schede, count( $attivi ) );
}

/**
 * [gs_carosello_scuole] — Scuole di Cucina con vetrina pubblicata, veste
 * "gs-cs" (21/08/2026).
 */
add_shortcode( 'gs_carosello_scuole', 'gs_sc_carosello_scuole' );
function gs_sc_carosello_scuole() {
	if ( ! function_exists( 'gs_scu_elenco' ) ) { return ''; }
	$max = (int) gs_carosello_impostazioni()['max_scuole'];
	$attive = array_values( array_filter( gs_scu_elenco(), function ( $p ) { return gs_scu_pubblicata( $p->ID ); } ) );
	$attive = array_slice( $attive, 0, $max ? $max : 10 );

	$schede = '';
	foreach ( $attive as $p ) {
		$a = gs_scu_get( $p->ID );
		$foto = $a['logo'] ? $a['logo'] : ( ! empty( $a['media'][0]['url'] ) ? $a['media'][0]['url'] : '' );
		$schede .= gs_carosello_gs_cs_scheda_semplice_html(
			gs_scu_url( $a['id'] ),
			$foto,
			'🎓',
			'#38a670', '#1d5c3a',
			$a['comune'] ? $a['comune'] : '',
			$a['nome'],
			$a['testo'] ? wp_trim_words( $a['testo'], 16 ) : ''
		);
	}

	return gs_carosello_gs_cs_html( 'In Vetrina', '🎓 Le Scuole di Cucina', $schede, count( $attive ) );
}

/**
 * [gs_carosello_corsi] — I 5 livelli/corsi della Vetrina dei Corsi, veste
 * "gs-cs" statica (21/08/2026 — resta senza scorrimento, vedi
 * gs_carosello_gs_cs_statico_html()). Stessi dati di [gs_percorsi_corsi]:
 * gs_percorsi_corsi_dati() in calendario.php è l'unica fonte, cambiare un
 * corso lì aggiorna anche qui.
 */
add_shortcode( 'gs_carosello_corsi', 'gs_sc_carosello_corsi' );
function gs_sc_carosello_corsi() {
	if ( ! function_exists( 'gs_percorsi_corsi_dati' ) ) { return ''; }
	$carte  = gs_percorsi_corsi_dati();
	$schede = '';
	foreach ( $carte as $c ) {
		$schede .= gs_carosello_gs_cs_scheda_semplice_html(
			$c['href'],
			'',
			'<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16" rx="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="8" y1="2.5" x2="8" y2="6.5"></line><line x1="16" y1="2.5" x2="16" y2="6.5"></line></svg>',
			$c['c1'], $c['c2'],
			$c['tag'],
			$c['titolo'],
			$c['testo']
		);
	}

	return gs_carosello_gs_cs_statico_html( 'In Vetrina', '📅 La Vetrina dei Corsi', 'I 5 livelli dell\'Accademia — clicca una scheda per vedere la descrizione e le prossime date.', $schede );
}

// -----------------------------------------------------------------------------
// PANNELLO — shortcode da copiare + quante schede mostrare
// -----------------------------------------------------------------------------
function gs_pannello_caroselli() {
	if ( ! gs_can_manage() ) { return; }
	$cfg = gs_carosello_impostazioni();
	$cfg_nastro = function_exists( 'gs_nastro_impostazioni' ) ? gs_nastro_impostazioni() : array( 'nastro_attivo' => false, 'nastro_max' => 18 );
	echo gs_box_open( '🎠 Caroselli per la Home Page' );
	echo gs_sezione_aiuto( 'Quattro caroselli scorrevoli, pronti per essere incollati in Home Page o in qualunque altra pagina del sito con il relativo shortcode: copialo e incollalo nell\'editor della pagina, come un blocco di testo. Stessa grafica su tutti — icona su fondo colorato, titolo, testo, cartellino. I dati sono sempre quelli già gestiti altrove (Le Sfogline, Artigiani della Pasta, Scuole di Cucina, il Calendario Corsi): qui scegli quante schede mostrare, la velocità di scorrimento automatico, e se l\'ordine delle sfogline senza Vetrina attiva cambia a ogni caricamento della pagina.' );

	echo '<form class="gs-form gs-form-caroselli" onsubmit="return false">';
	echo '<div class="gs-todo-riquadro">';
	echo '<p><label><input type="checkbox" name="autoplay" ' . checked( ! empty( $cfg['autoplay'] ), true, false ) . '> Scorrimento automatico (si ferma da solo al passaggio del mouse/dito)</label></p>';
	echo '<p><label>Velocità — ogni quanti secondi passa alla scheda successiva<br><input type="number" min="1" max="60" step="1" name="velocita" value="' . esc_attr( $cfg['velocita'] ) . '" style="width:100px"></label></p>';
	echo '</div>';
	echo '<p style="margin-top:16px"><label>👭 Sfogline — massimo schede<br><input type="number" min="1" step="1" name="max_sfogline" value="' . esc_attr( $cfg['max_sfogline'] ) . '" style="width:100px"></label></p>';
	echo '<p><label><input type="checkbox" name="ordine_casuale" ' . checked( ! empty( $cfg['ordine_casuale'] ), true, false ) . '> Ordine casuale a ogni caricamento per chi non ha attivato la Vetrina — chi l\'ha attivata resta comunque sempre in prima fila</label></p>';
	echo '<div class="blocco-codice-copiabile"><code>[gs_carosello_sfogline]</code> <button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-copia-shortcode" data-sc="[gs_carosello_sfogline]">Copia</button></div>';

	echo '<p style="margin-top:16px"><label>🍝 Artigiani della Pasta — massimo schede<br><input type="number" min="1" step="1" name="max_artigiani" value="' . esc_attr( $cfg['max_artigiani'] ) . '" style="width:100px"></label></p>';
	echo '<div class="blocco-codice-copiabile"><code>[gs_carosello_artigiani]</code> <button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-copia-shortcode" data-sc="[gs_carosello_artigiani]">Copia</button></div>';

	echo '<p style="margin-top:16px"><label>🎓 Scuole di Cucina — massimo schede<br><input type="number" min="1" step="1" name="max_scuole" value="' . esc_attr( $cfg['max_scuole'] ) . '" style="width:100px"></label></p>';
	echo '<div class="blocco-codice-copiabile"><code>[gs_carosello_scuole]</code> <button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-copia-shortcode" data-sc="[gs_carosello_scuole]">Copia</button></div>';

	echo '<p style="margin-top:16px">📅 La Vetrina dei Corsi — sempre i 5 livelli, niente da scegliere qui: per cambiarli vedi Calendario Corsi &gt; "I Nostri Percorsi".</p>';
	echo '<div class="blocco-codice-copiabile"><code>[gs_carosello_corsi]</code> <button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-copia-shortcode" data-sc="[gs_carosello_corsi]">Copia</button></div>';

	echo '<hr style="margin:22px 0;border:none;border-top:1px solid var(--gs-bordo,#e3d5b8)">';
	echo '<p style="font-weight:700;color:var(--gs-terracotta,#b5722a);margin:0 0 8px;">🎗️ Nastro fisso sotto il menu</p>';
	echo '<p style="font-size:13px;color:var(--gs-testo-soft,#6b6246);margin:0 0 10px;">Diverso dai tre caroselli sopra: non uno shortcode da incollare, ma una fila che scorre da sola, sempre agganciata appena sotto il menu, su ogni pagina del sito — mescola sfogline con Vetrina attiva, Artigiani e Scuole.</p>';
	echo '<p><label><input type="checkbox" name="nastro_attivo" ' . checked( ! empty( $cfg_nastro['nastro_attivo'] ), true, false ) . '> Nastro attivo su tutto il sito</label></p>';
	echo '<p><label>Quante voci al massimo (si ripetono in coda formando il giro continuo)<br><input type="number" min="6" step="1" name="nastro_max" value="' . esc_attr( $cfg_nastro['nastro_max'] ) . '" style="width:100px"></label></p>';

	// Fila singola, doppia o singola-grande (richiesto da Ennio, 18/08/2026).
	echo '<p style="font-weight:600;margin:14px 0 6px">Come mostrarlo</p>';
	echo '<p style="display:flex;flex-wrap:wrap;gap:16px;margin:0 0 10px">';
	$gs_nastro_modalita_opzioni = array(
		'singolo' => 'Una fila sola',
		'doppio'  => 'Due file (come ora)',
		'grande'  => 'Una fila sola, più grande',
	);
	foreach ( $gs_nastro_modalita_opzioni as $valore => $etichetta ) {
		echo '<label style="display:flex;align-items:center;gap:6px"><input type="radio" name="nastro_modalita" value="' . esc_attr( $valore ) . '" ' . checked( $cfg_nastro['nastro_modalita'], $valore, false ) . '> ' . esc_html( $etichetta ) . '</label>';
	}
	echo '</p>';

	// Legenda con interruttore per tipo: quali categorie mescolare nel nastro
	// (richiesto da Ennio, 16/08/2026) — stessi colori delle pillole vere,
	// così la legenda corrisponde davvero a quello che si vede scorrere.
	echo '<p style="font-weight:600;margin:14px 0 6px">Cosa mostrare nel nastro</p>';
	echo '<p style="display:flex;flex-wrap:wrap;gap:16px;margin:0">';
	echo '<label style="display:flex;align-items:center;gap:6px"><input type="checkbox" name="nastro_mostra_sfogline" ' . checked( ! empty( $cfg_nastro['nastro_mostra_sfogline'] ), true, false ) . '> <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:linear-gradient(150deg,#cd8b0c,#a8712a)"></span> Sfogline</label>';
	echo '<label style="display:flex;align-items:center;gap:6px"><input type="checkbox" name="nastro_mostra_scuole" ' . checked( ! empty( $cfg_nastro['nastro_mostra_scuole'] ), true, false ) . '> <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:linear-gradient(150deg,#bd8a13,#8f6a10)"></span> Scuole</label>';
	echo '<label style="display:flex;align-items:center;gap:6px"><input type="checkbox" name="nastro_mostra_negozi" ' . checked( ! empty( $cfg_nastro['nastro_mostra_negozi'] ), true, false ) . '> <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:linear-gradient(150deg,#1f6e37,#144d27)"></span> Negozi (Artigiani della Pasta)</label>';
	echo '</p>';

	// Esclusione singola, persona per persona (richiesto da Ennio, 17/08/2026):
	// di default appare chiunque sia idoneo (sfoglina con Vetrina attiva,
	// artigiano/scuola pubblicati) — qui si tolgono a mano i singoli senza
	// spegnere l'intera categoria. Checkbox SPUNTATA = appare nel nastro
	// (più intuitivo di "spuntato = escluso"), quindi al salvataggio si
	// inverte per ottenere l'elenco degli esclusi da conservare.
	echo '<p style="font-weight:600;margin:18px 0 6px">Chi appare nel nastro (deseleziona per escludere una singola persona)</p>';

	$gs_disegna_lista_nastro = function ( $titolo, $chiave_campo, $elenco, $esclusi ) {
		echo '<div class="gs-todo-riquadro" style="margin-bottom:10px">';
		echo '<p style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin:0 0 6px">';
		echo '<strong>' . esc_html( $titolo ) . '</strong>';
		echo '<span><button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-nastro-esc-tutti" data-target=".gs-nastro-esc-' . esc_attr( $chiave_campo ) . '" data-stato="1">Tutti</button> '
			. '<button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-nastro-esc-tutti" data-target=".gs-nastro-esc-' . esc_attr( $chiave_campo ) . '" data-stato="0">Nessuno</button></span>';
		echo '</p>';
		if ( ! $elenco ) {
			echo '<p class="gs-hint">Nessuno ancora in questa categoria.</p></div>';
			return;
		}
		echo '<input type="text" class="gs-cerca-input" data-target=".gs-nastro-esc-' . esc_attr( $chiave_campo ) . '-lista" placeholder="🔍 Cerca…" style="width:100%;max-width:320px;margin-bottom:6px">';
		echo '<div class="gs-nastro-esc-' . esc_attr( $chiave_campo ) . '-lista" style="max-height:160px;overflow-y:auto;background:#fff;border:1px solid var(--gs-bordo,#e3d5b8);border-radius:6px;padding:8px">';
		foreach ( $elenco as $id => $nome ) {
			$appare = ! in_array( (int) $id, $esclusi, true );
			echo '<label style="display:block;font-size:13px;padding:2px 0"><input type="checkbox" class="gs-nastro-esc-' . esc_attr( $chiave_campo ) . '" name="nastro_appare_' . esc_attr( $chiave_campo ) . '[]" value="' . (int) $id . '" ' . checked( $appare, true, false ) . '> ' . esc_html( $nome ) . '</label>';
		}
		echo '</div></div>';
	};

	$elenco_sfogline = array();
	foreach ( get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) ) as $u ) {
		if ( function_exists( 'gs_e_sfoglina_vera' ) && function_exists( 'gs_vetrina_disponibile' ) && gs_e_sfoglina_vera( $u ) && gs_vetrina_disponibile( $u->ID ) ) {
			$elenco_sfogline[ $u->ID ] = $u->display_name;
		}
	}
	$elenco_artigiani = array();
	if ( function_exists( 'gs_art_elenco' ) ) {
		foreach ( gs_art_elenco() as $p ) {
			if ( gs_art_pubblicata( $p->ID ) ) { $a = gs_art_get( $p->ID ); $elenco_artigiani[ $p->ID ] = $a['nome']; }
		}
	}
	$elenco_scuole = array();
	if ( function_exists( 'gs_scu_elenco' ) ) {
		foreach ( gs_scu_elenco() as $p ) {
			if ( gs_scu_pubblicata( $p->ID ) ) { $a = gs_scu_get( $p->ID ); $elenco_scuole[ $p->ID ] = $a['nome']; }
		}
	}
	$gs_disegna_lista_nastro( '👭 Sfogline', 'sfogline', $elenco_sfogline, $cfg_nastro['nastro_esclusi_sfogline'] );
	$gs_disegna_lista_nastro( '🍝 Artigiani della Pasta', 'artigiani', $elenco_artigiani, $cfg_nastro['nastro_esclusi_artigiani'] );
	$gs_disegna_lista_nastro( '🎓 Scuole di Cucina', 'scuole', $elenco_scuole, $cfg_nastro['nastro_esclusi_scuole'] );

	echo '<p style="margin-top:16px"><button class="gs-btn gs-btn-sm gs-caroselli-salva">Salva</button> <span class="gs-caroselli-msg gs-richiesta-esito"></span></p>';
	echo '</form>';
	echo gs_box_close();
}

add_action( 'wp_ajax_gs_caroselli_salva', 'gs_ajax_caroselli_salva' );
function gs_ajax_caroselli_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$s = gs_settings();
	$s['caroselli'] = array(
		'max_sfogline'   => max( 1, (int) ( $_POST['max_sfogline'] ?? 12 ) ),
		'max_artigiani'  => max( 1, (int) ( $_POST['max_artigiani'] ?? 10 ) ),
		'max_scuole'     => max( 1, (int) ( $_POST['max_scuole'] ?? 10 ) ),
		'autoplay'       => ! empty( $_POST['autoplay'] ) ? 1 : 0,
		// Tetto a 60s: senza un limite era possibile salvare un numero enorme
		// per errore (successo davvero, 7001 — il carosello smetteva di
		// sembrare vivo, sedeva 2 ore su una scheda), Ennio 16/08/2026.
		'velocita'       => max( 1, min( 60, (int) ( $_POST['velocita'] ?? 4 ) ) ),
		'ordine_casuale' => ! empty( $_POST['ordine_casuale'] ) ? 1 : 0,
		'nastro_attivo'  => ! empty( $_POST['nastro_attivo'] ) ? 1 : 0,
		'nastro_max'     => max( 6, (int) ( $_POST['nastro_max'] ?? 18 ) ),
		'nastro_modalita' => in_array( $_POST['nastro_modalita'] ?? '', array( 'singolo', 'doppio', 'grande' ), true ) ? $_POST['nastro_modalita'] : 'doppio',
		'nastro_mostra_sfogline' => ! empty( $_POST['nastro_mostra_sfogline'] ) ? 1 : 0,
		'nastro_mostra_scuole'   => ! empty( $_POST['nastro_mostra_scuole'] ) ? 1 : 0,
		'nastro_mostra_negozi'   => ! empty( $_POST['nastro_mostra_negozi'] ) ? 1 : 0,
		// Le checkbox mandano "chi appare" (nastro_appare_*[]), non "chi è
		// escluso": qui si inverte, ricostruendo SEMPRE l'elenco completo di
		// chi è idoneo — altrimenti una casella svuotata del tutto (nessuno
		// selezionato) non manderebbe alcun campo e la selezione precedente
		// resterebbe intoccata invece di escludere tutti (stesso problema già
		// risolto per le sezioni in gs_sez_normalizza_mappa(), 30/07/2026).
		'nastro_esclusi_sfogline'  => gs_nastro_calcola_esclusi( 'nastro_appare_sfogline', gs_nastro_idonei_sfogline() ),
		'nastro_esclusi_artigiani' => gs_nastro_calcola_esclusi( 'nastro_appare_artigiani', gs_nastro_idonei_artigiani() ),
		'nastro_esclusi_scuole'    => gs_nastro_calcola_esclusi( 'nastro_appare_scuole', gs_nastro_idonei_scuole() ),
	);
	update_option( GS_OPTION, $s );
	wp_send_json_success( array( 'message' => 'Impostazioni caroselli salvate.' ) );
}

/** ID di chi è idoneo ad apparire nel nastro, per ciascuna categoria — stesso filtro usato per disegnare le checkbox e per calcolare gli esclusi al salvataggio. */
function gs_nastro_idonei_sfogline() {
	$out = array();
	foreach ( get_users( array( 'orderby' => 'display_name', 'order' => 'ASC', 'fields' => array( 'ID' ) ) ) as $row ) {
		$id = (int) $row->ID;
		$u  = get_userdata( $id );
		if ( $u && function_exists( 'gs_e_sfoglina_vera' ) && function_exists( 'gs_vetrina_disponibile' ) && gs_e_sfoglina_vera( $u ) && gs_vetrina_disponibile( $id ) ) {
			$out[] = $id;
		}
	}
	return $out;
}
function gs_nastro_idonei_artigiani() {
	$out = array();
	if ( function_exists( 'gs_art_elenco' ) ) {
		foreach ( gs_art_elenco() as $p ) { if ( gs_art_pubblicata( $p->ID ) ) { $out[] = (int) $p->ID; } }
	}
	return $out;
}
function gs_nastro_idonei_scuole() {
	$out = array();
	if ( function_exists( 'gs_scu_elenco' ) ) {
		foreach ( gs_scu_elenco() as $p ) { if ( gs_scu_pubblicata( $p->ID ) ) { $out[] = (int) $p->ID; } }
	}
	return $out;
}
/** Dato il nome del campo "chi appare" arrivato in $_POST e l'elenco di chi è idoneo, restituisce l'elenco di chi va escluso (idonei meno chi appare). */
function gs_nastro_calcola_esclusi( $campo_post, $idonei ) {
	$appare = isset( $_POST[ $campo_post ] ) && is_array( $_POST[ $campo_post ] ) ? array_map( 'intval', $_POST[ $campo_post ] ) : array();
	return array_values( array_diff( $idonei, $appare ) );
}
