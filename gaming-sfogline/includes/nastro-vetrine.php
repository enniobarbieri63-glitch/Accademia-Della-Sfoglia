<?php
/**
 * nastro-vetrine.php — Nastro scorrevole fisso, appena sotto il menu, su
 * tutte le pagine del sito (non uno shortcode da incollare a mano): alterna
 * sfogline con Vetrina attiva, Artigiani della Pasta e Scuole di Cucina
 * pubblicati. Riusa gli stessi dati già letti dai Caroselli, e l'interruttore
 * per accenderlo/spegnerlo vive nello stesso pannello "Caroselli per la Home
 * Page" (vedi caroselli.php) invece che in uno a parte, per non moltiplicare
 * i posti dove gestire cose molto simili.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Impostazioni del nastro: acceso/spento + quante voci al massimo, dentro lo stesso gruppo dei Caroselli. */
function gs_nastro_impostazioni() {
	$s = gs_settings();
	$c = isset( $s['caroselli'] ) && is_array( $s['caroselli'] ) ? $s['caroselli'] : array();
	return array(
		'nastro_attivo'          => ! empty( $c['nastro_attivo'] ),
		'nastro_max'             => isset( $c['nastro_max'] ) ? max( 6, (int) $c['nastro_max'] ) : 18,
		// Quante file e di che dimensione (richiesto da Ennio, 18/08/2026):
		// 'doppio' = due file normali (comportamento originale), 'singolo' =
		// una sola fila normale, 'grande' = una sola fila con pillole più
		// grandi.
		'nastro_modalita'        => isset( $c['nastro_modalita'] ) && in_array( $c['nastro_modalita'], array( 'singolo', 'doppio', 'grande' ), true ) ? $c['nastro_modalita'] : 'doppio',
		// Di default tutti e tre accesi (installazioni precedenti a questa
		// legenda, 16/08/2026, non hanno ancora salvato queste chiavi).
		'nastro_mostra_sfogline' => ! isset( $c['nastro_mostra_sfogline'] ) || ! empty( $c['nastro_mostra_sfogline'] ),
		'nastro_mostra_scuole'   => ! isset( $c['nastro_mostra_scuole'] ) || ! empty( $c['nastro_mostra_scuole'] ),
		'nastro_mostra_negozi'   => ! isset( $c['nastro_mostra_negozi'] ) || ! empty( $c['nastro_mostra_negozi'] ),
		// Esclusione singola, persona per persona (richiesto da Ennio,
		// 17/08/2026): di default nel nastro appare CHIUNQUE sia idoneo
		// (sfoglina con Vetrina attiva, artigiano/scuola pubblicati) — qui si
		// tolgono a mano i singoli che non si vuole far comparire, senza
		// spegnere l'intera categoria come fanno i tre interruttori sopra.
		'nastro_esclusi_sfogline'  => isset( $c['nastro_esclusi_sfogline'] ) && is_array( $c['nastro_esclusi_sfogline'] ) ? array_map( 'intval', $c['nastro_esclusi_sfogline'] ) : array(),
		'nastro_esclusi_artigiani' => isset( $c['nastro_esclusi_artigiani'] ) && is_array( $c['nastro_esclusi_artigiani'] ) ? array_map( 'intval', $c['nastro_esclusi_artigiani'] ) : array(),
		'nastro_esclusi_scuole'    => isset( $c['nastro_esclusi_scuole'] ) && is_array( $c['nastro_esclusi_scuole'] ) ? array_map( 'intval', $c['nastro_esclusi_scuole'] ) : array(),
	);
}

add_action( 'wp_footer', 'gs_render_nastro_vetrine' );

function gs_render_nastro_vetrine() {
	$cfg = gs_nastro_impostazioni();
	if ( empty( $cfg['nastro_attivo'] ) ) {
		return;
	}
	// Stesso cancello del blackout usato dalle linguette laterali: durante la
	// sospensione del Gaming non ha senso restasse in vista una vetrina viva.
	if ( gs_blackout_attivo()
		&& ! ( function_exists( 'gs_can_manage' ) && gs_can_manage() )
		&& ! ( function_exists( 'gs_blackout_esente' ) && gs_blackout_esente() ) ) {
		return;
	}

	// Dove c'è già il nastro grande, non montare anche il piccolo: si
	// sovrapporrebbero. Prima questo veniva evitato nascondendo il piccolo
	// col CSS (gaming.css:2174, body.page-id-64342), che però lo faceva
	// comunque calcolare — una scansione completa della tabella utenti
	// buttata via a ogni visita di quella pagina.
	// Il controllo guarda il CONTENUTO e non l'id della pagina: lo shortcode
	// del nastro grande è incollato a mano, quindi può stare su qualunque
	// pagina, e l'id cambia da un sito all'altro.
	if ( is_singular() ) {
		$post_corrente = get_post();
		if ( $post_corrente && has_shortcode( (string) $post_corrente->post_content, 'gs_nastro_grande_sfogline' ) ) {
			return;
		}
	}

	$voci = gs_nastro_raccogli_voci( $cfg['nastro_max'], $cfg );

	// La voce fissa "Ennio Barbieri, Fondatore" (aggiunta il 17/08/2026) è
	// stata tolta su richiesta di Ennio il 26/08/2026: il nastro deve
	// mostrare solo le sfogline/artigiani/scuole ammessi e gli sponsor,
	// senza il fondatore in testa.

	if ( empty( $voci ) ) {
		return; // niente da mostrare (sito appena installato, nessuna vetrina attiva) — meglio silenzioso che un nastro vuoto.
	}

	// Sponsor (Mulino Marino, richiesto il 17/08/2026; Molino Caputo e
	// Molini Pivetti aggiunti il 26/08/2026): inseriti a rotazione tra una
	// voce e l'altra su entrambe le file, così ciascuno scorre "in visione
	// multipla" invece di comparire una volta sola per giro.
	$sponsor = array(
		array(
			'tipo' => 'sponsor', 'tag' => 'Partner', 'nome' => 'Mulino Marino',
			'foto' => GS_URL . 'assets/img/mulino-marino-logo.png', 'simbolo' => '🌾', 'url' => 'https://www.mulinomarino.it/it/',
		),
		array(
			'tipo' => 'sponsor', 'tag' => 'Partner', 'nome' => 'Molino Caputo',
			'foto' => GS_URL . 'assets/img/logo-mulino-caputo.png', 'simbolo' => '🌾', 'url' => 'https://www.mulinocaputo.it/',
		),
		array(
			'tipo' => 'sponsor', 'tag' => 'Partner', 'nome' => 'Molini Pivetti',
			'foto' => GS_URL . 'assets/img/logo-molini-pivetti.svg', 'simbolo' => '🌾', 'url' => 'https://www.molinipivetti.it/',
		),
	);
	// Quanti "giri" ripetere le voci prima di intervallarle con gli sponsor:
	// almeno 2 (come da sempre), ma di più se ci sono poche voci e molti
	// sponsor — altrimenti la rotazione non fa in tempo a passare da tutti.
	// Con appena 2 voci (es. solo Bruno Cingolani e Rina Poletti nel nastro)
	// e 3 sponsor, due giri davano solo 4 inserimenti: il terzo sponsor
	// (Molini Pivetti) non compariva mai — trovato il 26/08/2026 controllando
	// il nastro dal vivo dopo l'installazione.
	$giri = max( 2, (int) ceil( count( $sponsor ) / max( 1, count( $voci ) ) ) );
	// Deve essere PARI: l'animazione scorre di -50% e riparte (gaming.css:2213,
	// gsAllineaVelocitaNastroGrande in gaming.js calcola la durata su
	// larghezza/2), quindi le due metà del nastro devono essere identiche —
	// è la ragione per cui prima si disegnava la lista esattamente due
	// volte. Con un numero dispari il salto cade in mezzo a una copia e il
	// nastro sobbalza a ogni giro: succede con 3 sponsor e una sola voce nel
	// nastro, che dopo un reset è esattamente come ripartirà il sito
	// (26/08/2026).
	if ( 0 !== $giri % 2 ) { $giri++; }
	$voci_ripetute = array();
	for ( $g = 0; $g < $giri; $g++ ) { $voci_ripetute = array_merge( $voci_ripetute, $voci ); }
	$voci_intervallate = gs_nastro_intervalla( $voci_ripetute, $sponsor );
	$modalita = $cfg['nastro_modalita'];

	echo '<div class="gs-nastro-fisso gs-nastro-modalita-' . esc_attr( $modalita ) . '" id="gs-nastro-fisso">';
	// Due file, come nel progetto originale: la prima scorre verso sinistra,
	// la seconda (ordine invertito, per non essere identica) verso destra.
	// Nelle modalità "singolo" e "grande" (18/08/2026) si stampa solo la
	// prima fila: una fila sola, normale o con pillole più grandi a seconda
	// della classe gs-nastro-modalita-* sopra (gestita in CSS).
	echo '<div class="gs-nastro-pista gs-nastro-pista-sx">';
	foreach ( $voci_intervallate as $v ) {
		gs_nastro_pillola_render( $v );
	}
	echo '</div>';

	if ( 'doppio' === $modalita ) {
		$voci_dx_ripetute = array();
		$voci_invertite   = array_reverse( $voci );
		for ( $g = 0; $g < $giri; $g++ ) { $voci_dx_ripetute = array_merge( $voci_dx_ripetute, $voci_invertite ); }
		$voci_dx = gs_nastro_intervalla( $voci_dx_ripetute, $sponsor );
		echo '<div class="gs-nastro-pista gs-nastro-pista-dx">';
		foreach ( $voci_dx as $v ) {
			gs_nastro_pillola_render( $v );
		}
		echo '</div>';
	}
	echo '</div>';
}

/** Inserisce, a rotazione, una voce di $extras dopo ogni voce dell'elenco — con più sponsor, ciascuno scorre a turno invece che sempre lo stesso. */
function gs_nastro_intervalla( $voci, $extras ) {
	$out = array();
	$n   = count( $extras );
	$i   = 0;
	foreach ( $voci as $v ) {
		$out[] = $v;
		if ( $n > 0 ) {
			$out[] = $extras[ $i % $n ];
			$i++;
		}
	}
	return $out;
}

/**
 * Raccoglie fino a $max voci alternando sfogline (Vetrina attiva),
 * Artigiani e Scuole pubblicati — un po' per tipo a turno (round-robin),
 * non tutte le sfogline seguite da tutti gli artigiani, così il nastro
 * appare davvero misto fin dall'inizio. $esclusi (opzionale) è l'array con
 * le tre liste di ID esclusi a mano dal pannello Caroselli (richiesto da
 * Ennio, 17/08/2026).
 */
function gs_nastro_raccogli_voci( $max, $esclusi = array() ) {
	// Il Nastro gira su OGNI pagina del sito (wp_footer), per ogni visitatore,
	// anche non collegato. Senza memoria, ogni visita rifà una scansione
	// completa della tabella utenti più due dei CPT partner. Qui la cache è
	// legittima e non contraddice la regola della Torre di controllo: il
	// Nastro è una vetrina che scorre, non un numero che qualcuno guarda per
	// decidere se agire. Una sfoglina nuova che compare un quarto d'ora dopo
	// non fa danno a nessuno.
	$chiave_cache = 'gs_nastro_voci_' . md5( wp_json_encode( array( $max, $esclusi ) ) );
	$in_memoria   = get_transient( $chiave_cache );
	if ( is_array( $in_memoria ) ) {
		return $in_memoria;
	}

	$esc_sfogline  = isset( $esclusi['nastro_esclusi_sfogline'] ) ? $esclusi['nastro_esclusi_sfogline'] : array();
	$esc_artigiani = isset( $esclusi['nastro_esclusi_artigiani'] ) ? $esclusi['nastro_esclusi_artigiani'] : array();
	$esc_scuole    = isset( $esclusi['nastro_esclusi_scuole'] ) ? $esclusi['nastro_esclusi_scuole'] : array();

	// Cartellino personalizzato per due persone specifiche (Ennio,
	// 26/08/2026): Bruno Cingolani (ID 6) "Maestro", Rina Poletti (ID 9)
	// "Maestra", invece del cartellino "Sfoglina" comune a tutte le altre.
	$tag_personalizzati = array(
		6 => 'Maestro',
		9 => 'Maestra',
	);

	$sfogline = array();
	foreach ( get_users( array( 'orderby' => 'display_name', 'order' => 'ASC', 'number' => 500 ) ) as $u ) {
		if ( ! gs_e_sfoglina_vera( $u ) || ! gs_vetrina_disponibile( $u->ID ) || in_array( (int) $u->ID, $esc_sfogline, true ) ) {
			continue;
		}
		$level = function_exists( 'gs_get_level' ) ? gs_get_level( $u->ID ) : array();
		$sfogline[] = array(
			'tipo'   => 'sfoglina',
			'tag'    => isset( $tag_personalizzati[ (int) $u->ID ] ) ? $tag_personalizzati[ (int) $u->ID ] : 'Sfoglina',
			'nome'   => $u->display_name,
			'foto'   => function_exists( 'gs_bio_foto_url' ) ? gs_bio_foto_url( $u->ID ) : '',
			'simbolo' => ! empty( $level['simbolo'] ) ? $level['simbolo'] : '🧑‍🍳',
			'url'    => gs_vetrina_url( $u->ID ),
		);
	}
	usort( $sfogline, function ( $a, $b ) { return strcasecmp( gs_cognome_da_nome( $a['nome'] ), gs_cognome_da_nome( $b['nome'] ) ); } );

	$artigiani = array();
	if ( function_exists( 'gs_art_elenco' ) ) {
		foreach ( gs_art_elenco() as $p ) {
			if ( ! gs_art_pubblicata( $p->ID ) || in_array( (int) $p->ID, $esc_artigiani, true ) ) { continue; }
			$a = gs_art_get( $p->ID );
			$artigiani[] = array(
				'tipo'    => 'artigiano',
				'tag'     => 'Artigiano',
				'nome'    => $a['nome'],
				'foto'    => $a['logo'] ? $a['logo'] : ( ! empty( $a['media'][0]['url'] ) ? $a['media'][0]['url'] : '' ),
				'simbolo' => '🍝',
				'url'     => gs_art_url( $a['id'] ),
			);
		}
	}

	$scuole = array();
	if ( function_exists( 'gs_scu_elenco' ) ) {
		foreach ( gs_scu_elenco() as $p ) {
			if ( ! gs_scu_pubblicata( $p->ID ) || in_array( (int) $p->ID, $esc_scuole, true ) ) { continue; }
			$a = gs_scu_get( $p->ID );
			$scuole[] = array(
				'tipo'    => 'scuola',
				'tag'     => 'Scuola',
				'nome'    => $a['nome'],
				'foto'    => $a['logo'] ? $a['logo'] : ( ! empty( $a['media'][0]['url'] ) ? $a['media'][0]['url'] : '' ),
				'simbolo' => '🎓',
				'url'     => gs_scu_url( $a['id'] ),
			);
		}
	}

	$gruppi = array_values( array_filter( array( $sfogline, $artigiani, $scuole ) ) );
	$voci   = array();
	$indici = array_fill( 0, count( $gruppi ), 0 );
	while ( count( $voci ) < $max && ! empty( $gruppi ) ) {
		$fatto_qualcosa = false;
		foreach ( $gruppi as $g => $lista ) {
			if ( $indici[ $g ] < count( $lista ) ) {
				$voci[]        = $lista[ $indici[ $g ] ];
				$indici[ $g ]++;
				$fatto_qualcosa = true;
				if ( count( $voci ) >= $max ) { break; }
			}
		}
		if ( ! $fatto_qualcosa ) { break; } // tutti i gruppi esauriti
	}
	set_transient( $chiave_cache, $voci, 15 * MINUTE_IN_SECONDS );
	return $voci;
}

/** Stampa una pillola del nastro: foto vera se c'è, altrimenti colore pieno + emoji, come nei caroselli. */
function gs_nastro_pillola_render( $v ) {
	$colori = array(
		'sfoglina'  => 'linear-gradient(150deg,#cd8b0c,#a8712a)',
		'artigiano' => 'linear-gradient(150deg,#1f6e37,#144d27)',
		'scuola'    => 'linear-gradient(150deg,#bd8a13,#8f6a10)',
	);
	$sfondo_ph = isset( $colori[ $v['tipo'] ] ) ? $colori[ $v['tipo'] ] : $colori['sfoglina'];

	$extra_attr = 'sponsor' === $v['tipo'] ? ' target="_blank" rel="noopener"' : '';
	printf(
		'<a class="gs-pillola-nastro" href="%s"%s>',
		esc_url( $v['url'] ),
		$extra_attr
	);
	if ( $v['foto'] ) {
		$classe_ph = 'sponsor' === $v['tipo'] ? 'gs-ph-nastro gs-ph-nastro-logo' : 'gs-ph-nastro';
		printf( '<span class="%s" style="background-image:url(\'%s\')"></span>', esc_attr( $classe_ph ), esc_url( $v['foto'] ) );
	} else {
		printf( '<span class="gs-ph-nastro" style="background:%s">%s</span>', esc_attr( $sfondo_ph ), esc_html( $v['simbolo'] ) );
	}
	printf(
		'<span class="gs-nastro-nome">%s</span><span class="gs-nastro-tag gs-nastro-tag-%s">%s</span>',
		esc_html( $v['nome'] ),
		esc_attr( $v['tipo'] ),
		esc_html( $v['tag'] )
	);
	echo '</a>';
}

/**
 * Nastro grande, 4 file, il doppio delle dimensioni del nastro fisso sotto
 * il menu — [gs_nastro_grande_sfogline], pensato per la pagina "Le Sfogline"
 * (richiesto da Ennio, 17/08/2026).
 *
 */
add_shortcode( 'gs_nastro_grande_sfogline', 'gs_sc_nastro_grande_sfogline' );
function gs_sc_nastro_grande_sfogline() {
	$sfogline = array();
	foreach ( get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) ) as $u ) {
		if ( ! gs_e_sfoglina_vera( $u ) || ! gs_vetrina_disponibile( $u->ID ) ) { continue; }
		$level = function_exists( 'gs_get_level' ) ? gs_get_level( $u->ID ) : array();
		$sfogline[] = array(
			'nome' => $u->display_name, 'tag' => 'Sfoglina',
			'foto' => function_exists( 'gs_bio_foto_url' ) ? gs_bio_foto_url( $u->ID ) : '',
			'url'  => gs_vetrina_url( $u->ID ),
		);
	}

	if ( ! $sfogline ) { return ''; }

	$colori = array( array( '#cd8b0c', '#a8712a' ), array( '#1f6e37', '#144d27' ), array( '#bd8a13', '#8f6a10' ), array( '#38a670', '#1d5c3a' ) );
	$pillola = function ( $v, $i ) use ( $colori ) {
		list( $c1, $c2 ) = $colori[ $i % count( $colori ) ];
		$out = '<a class="gs-pillola-nastro-grande" href="' . esc_url( $v['url'] ) . '">';
		if ( $v['foto'] ) {
			$out .= '<span class="gs-ph-nastro-grande" style="background-image:url(\'' . esc_url( $v['foto'] ) . '\')"></span>';
		} else {
			$out .= '<span class="gs-ph-nastro-grande" style="background:linear-gradient(150deg,' . esc_attr( $c1 ) . ',' . esc_attr( $c2 ) . ')">' . esc_html( mb_substr( $v['nome'], 0, 1 ) ) . '</span>';
		}
		$out .= '<span class="gs-nastro-grande-nome">' . esc_html( $v['nome'] ) . '</span><span class="gs-nastro-grande-tag">' . esc_html( $v['tag'] ) . '</span></a>';
		return $out;
	};

	// Dopo tre tentativi "rallenta ancora" (17/08/2026), Ennio ha chiesto la
	// STESSA velocità del nastro piccolo sotto il menu, non un numero di
	// secondi indovinato a mano — pillole grandi il doppio richiedono un
	// tempo diverso per coprire la stessa velocità in pixel al secondo,
	// quindi il numero giusto va misurato dal vero, non scritto qui. La
	// durata di partenza qui sotto è solo un valore ragionevole prima che
	// gaming.js (gsAllineaVelocitaNastroGrande) la corregga misurando la
	// larghezza vera e la velocità vera del nastro piccolo sulla stessa
	// pagina — stessa tecnica già usata per gsAllineaSpazioMenu().
	$righe = array(
		// Ridotto da 4 a 3 file (richiesto da Ennio, 17/08/2026).
		array( 'etichetta' => 'sx' ),
		array( 'etichetta' => 'dx' ),
		array( 'etichetta' => 'sx' ),
	);
	$out = '<div class="gs-nastro-grande-blocco">';
	foreach ( $righe as $r ) {
		$classe_verso = 'dx' === $r['etichetta'] ? ' gs-nastro-grande-pista-dx' : '';
		$out .= '<div class="gs-nastro-grande-riga"><div class="gs-nastro-grande-pista' . $classe_verso . '" style="animation-duration:150s">';
		for ( $giro = 0; $giro < 2; $giro++ ) {
			foreach ( $sfogline as $i => $v ) { $out .= $pillola( $v, $i ); }
		}
		$out .= '</div></div>';
	}
	$out .= '</div>';
	return $out;
}
