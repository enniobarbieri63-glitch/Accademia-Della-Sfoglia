<?php
/**
 * ricerca-globale.php — Ricerca unica su tutte le sezioni pubbliche di
 * contenuto del sito (Ricettario, Lezioni Video, FAQ, Cassaforte del Sapere,
 * Sfoglia che Insegna Se Stessa, Matterello Parlante, Letture, Adotta un
 * Piatto, Novità, Dicono di Noi, Sondaggi), invece di dover cercare sezione
 * per sezione.
 *
 * Non introduce un motore di ricerca nuovo: interroga con lo stesso criterio
 * (sottostringa case-insensitive) i dati che i moduli già espongono, e
 * mostra i risultati raggruppati con un collegamento alla sezione giusta
 * (non apre direttamente l'elemento: una volta nella sezione, il campo di
 * ricerca locale già esistente lo trova subito).
 *
 * Escluse DI PROPOSITO (non sono "pagine di contenuto pubblico" ma dati
 * privati o personali di ogni sfoglina — cercarci dentro sarebbe un problema
 * di riservatezza, non solo fuori tema):
 *  - L'Esperto Risponde: consulenze private a pagamento (dal 2026-07-30).
 *  - Messaggi, Conversazioni private, Posta interna, Madrina & Allieva: 1:1
 *    tra due persone, mai contenuto pubblico.
 *  - Diario, Il Tuo Percorso, Il Tuo Anno in Accademia: dati personali della
 *    singola sfoglina, non un archivio da consultare da parte di altri.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// [gs_ricerca_globale] — lato sfoglina
// -----------------------------------------------------------------------------
add_shortcode( 'gs_ricerca_globale', 'gs_sc_ricerca_globale' );
function gs_sc_ricerca_globale() {
	if ( $g = gs_gate_riservato() ) { return $g; }
	if ( $g = gs_blackout_gate() ) { return $g; }
	if ( function_exists( 'gs_sez_collab_pagina_ok' ) && ! gs_sez_collab_pagina_ok( 'ricerca_globale' ) ) { return '<div class="gs-box"><p>Questa sezione non è disponibile al momento.</p></div>'; }

	$out  = gs_box_open( '🔎 Cerca in tutto il sito' );
	$out .= gs_sezione_aiuto( 'Scrivi almeno 3 lettere nel campo qui sotto: dopo una breve pausa la ricerca parte da sola, non serve premere Invio. I risultati compaiono raggruppati per sezione, ciascuno con un pulsante "Vai alla sezione" per aprire direttamente quella giusta.' );
	$out .= '<p class="gs-hint">Cerca in una volta sola nel Ricettario delle Famiglie, nelle Lezioni Video, nelle FAQ, nella Cassaforte del Sapere, ne La Sfoglia che Insegna Se Stessa, nel Matterello Parlante, nelle Letture, in Adotta un Piatto in Via di Estinzione, nelle Novità, nei Sondaggi e in Dicono di Noi.</p>';
	$out .= '<input type="text" class="gs-cerca-input gs-ricerca-globale-input" placeholder="🔍 Scrivi almeno 3 lettere…" style="width:100%;max-width:420px;margin-bottom:10px">';
	$out .= '<div class="gs-ricerca-globale-risultati"><p class="gs-hint">Scrivi qualcosa per iniziare a cercare.</p></div>';
	$out .= gs_box_close();

	return $out;
}

// -----------------------------------------------------------------------------
// AJAX — esegue la ricerca sui tre moduli
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_ricerca_globale', 'gs_ajax_ricerca_globale' );
function gs_ajax_ricerca_globale() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! is_user_logged_in() ) { wp_send_json_error( array( 'message' => 'Devi accedere.' ) ); }

	$q = isset( $_POST['q'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['q'] ) ) ) : '';
	if ( mb_strlen( $q ) < 3 ) { wp_send_json_error( array( 'message' => 'Scrivi almeno 3 lettere.' ) ); }
	$ago = function ( $haystack ) use ( $q ) {
		return false !== mb_stripos( (string) $haystack, $q );
	};

	$risultati = array(
		'ricettario' => array(), 'lezioni' => array(), 'faq' => array(),
		'cassaforte' => array(), 'sfoglia_insegna' => array(), 'matterello' => array(),
		'letture' => array(), 'piatti_estinzione' => array(), 'novita' => array(),
		'sondaggi' => array(), 'dicono_di_noi' => array(), 'esperto' => array(),
	);

	// --- Ricettario (solo ricette approvate, pubbliche) ---
	if ( function_exists( 'gs_ricette_approvate' ) && function_exists( 'gs_ricetta_get' ) ) {
		foreach ( gs_ricette_approvate() as $r ) {
			$c = gs_ricetta_get( $r->ID );
			if ( $ago( gs_ricetta_search_string( $c ) ) ) {
				$risultati['ricettario'][] = $c['titolo'];
			}
		}
	}

	// --- Lezioni Video ---
	if ( function_exists( 'gs_lezioni_tutte' ) && function_exists( 'gs_lezione_get' ) ) {
		foreach ( gs_lezioni_tutte() as $l ) {
			$c = gs_lezione_get( $l->ID );
			if ( $ago( gs_lezione_search_string( $c ) ) ) {
				$risultati['lezioni'][] = $c['titolo'];
			}
		}
	}

	// --- FAQ - Domande (pubbliche) ---
	if ( function_exists( 'gs_faq_per_categoria' ) ) {
		foreach ( gs_faq_per_categoria() as $gruppo ) {
			foreach ( $gruppo as $c ) {
				if ( $ago( $c['domanda'] ) || $ago( wp_strip_all_tags( $c['risposta'] ) ) ) {
					$risultati['faq'][] = $c['domanda'];
				}
			}
		}
	}

	// --- Cassaforte del Sapere: solo le voci già sbloccate ---
	// Il contenuto si "svela" quando la community raggiunge una soglia di
	// livello: farlo trovare dalla ricerca prima dello sblocco rovinerebbe
	// la sorpresa, oltre a mostrare qualcosa che nella sezione stessa non è
	// ancora visibile.
	if ( function_exists( 'gs_cassaforti_tutte' ) && function_exists( 'gs_cassaforte_get' ) && function_exists( 'gs_cassaforte_sbloccata' ) ) {
		foreach ( gs_cassaforti_tutte() as $p ) {
			if ( ! gs_cassaforte_sbloccata( $p->ID ) ) { continue; }
			$c = gs_cassaforte_get( $p->ID );
			if ( $ago( $c['titolo'] ) || $ago( wp_strip_all_tags( $c['contenuto'] ) ) ) {
				$risultati['cassaforte'][] = $c['titolo'];
			}
		}
	}

	// --- La Sfoglia che Insegna Se Stessa: solo gli errori promossi a materiale didattico ---
	if ( function_exists( 'gs_errori_promossi' ) && function_exists( 'gs_errore_get' ) ) {
		foreach ( gs_errori_promossi() as $p ) {
			$c = gs_errore_get( $p->ID );
			if ( $ago( $c['titolo'] ) || $ago( wp_strip_all_tags( $c['errore'] ) ) || $ago( $c['lezione'] ) ) {
				$risultati['sfoglia_insegna'][] = $c['titolo'];
			}
		}
	}

	// --- Il Matterello Parlante: solo le voci approvate ---
	if ( function_exists( 'gs_voci_approvate' ) && function_exists( 'gs_voce_get' ) ) {
		foreach ( gs_voci_approvate() as $p ) {
			$c = gs_voce_get( $p->ID );
			if ( $ago( $c['titolo'] ) || $ago( $c['trascrizione'] ) ) {
				$risultati['matterello'][] = $c['titolo'];
			}
		}
	}

	// --- Le Letture dei Grandi Protagonisti della Cucina (pubbliche) ---
	if ( function_exists( 'gs_letture_elenco' ) ) {
		foreach ( gs_letture_elenco() as $p ) {
			if ( $ago( $p->post_title ) || $ago( wp_strip_all_tags( $p->post_content ) ) ) {
				$risultati['letture'][] = $p->post_title;
			}
		}
	}

	// --- Adotta un Piatto in Via di Estinzione ---
	if ( function_exists( 'gs_piatti_tutti' ) && function_exists( 'gs_piatto_get' ) ) {
		foreach ( gs_piatti_tutti() as $p ) {
			$c = gs_piatto_get( $p->ID );
			if ( $ago( $c['nome'] ) || $ago( wp_strip_all_tags( $c['descrizione'] ) ) || $ago( $c['regione'] ) ) {
				$risultati['piatti_estinzione'][] = $c['nome'];
			}
		}
	}

	// --- Novità ---
	if ( function_exists( 'gs_novita_pubblicate' ) && function_exists( 'gs_novita_get' ) ) {
		foreach ( gs_novita_pubblicate() as $p ) {
			$c = gs_novita_get( $p->ID );
			if ( $ago( $c['titolo'] ) || $ago( wp_strip_all_tags( $c['testo'] ) ) ) {
				$risultati['novita'][] = $c['titolo'];
			}
		}
	}

	// --- Sondaggi ---
	if ( function_exists( 'gs_sondaggi_tutti' ) && function_exists( 'gs_sondaggio_get' ) ) {
		foreach ( gs_sondaggi_tutti() as $p ) {
			$c = gs_sondaggio_get( $p->ID );
			if ( $ago( $c['domanda'] ) || $ago( wp_strip_all_tags( $c['descrizione'] ) ) ) {
				$risultati['sondaggi'][] = $c['domanda'];
			}
		}
	}

	// --- Dicono di Noi: solo le testimonianze approvate ---
	if ( function_exists( 'gs_testim_approvate' ) && function_exists( 'gs_testim_get' ) ) {
		foreach ( gs_testim_approvate() as $p ) {
			$c = gs_testim_get( $p->ID );
			$testo = wp_strip_all_tags( $c['testo'] );
			if ( $ago( $testo ) || $ago( $c['credito'] ) ) {
				$risultati['dicono_di_noi'][] = mb_substr( $testo, 0, 60 ) . ( mb_strlen( $testo ) > 60 ? '…' : '' );
			}
		}
	}

	// --- L'Esperto Risponde: NON indicizzato ---
	// Dal 2026-07-30 le domande sono consulenze private a pagamento (vedi
	// esperti.php): non devono comparire in una ricerca che le renderebbe
	// visibili a chi non è coinvolto nella conversazione.

	// Link alle sezioni (pagine già create dal plugin).
	$link = array(
		'ricettario'        => get_permalink( (int) get_option( 'gs_page_ricettario' ) ),
		'lezioni'           => get_permalink( (int) get_option( 'gs_page_lezioni' ) ),
		'faq'               => get_permalink( (int) get_option( 'gs_page_faq' ) ),
		'cassaforte'        => get_permalink( (int) get_option( 'gs_page_cassaforte' ) ),
		'sfoglia_insegna'   => get_permalink( (int) get_option( 'gs_page_sfoglia_insegna' ) ),
		'matterello'        => get_permalink( (int) get_option( 'gs_page_matterello' ) ),
		'letture'           => get_permalink( (int) get_option( 'gs_page_letture' ) ),
		'piatti_estinzione' => get_permalink( (int) get_option( 'gs_page_piatti_estinzione' ) ),
		'novita'            => get_permalink( (int) get_option( 'gs_page_novita' ) ),
		'sondaggi'          => get_permalink( (int) get_option( 'gs_page_sondaggi' ) ),
		'dicono_di_noi'     => get_permalink( (int) get_option( 'gs_page_dicono_di_noi' ) ),
		'esperto'           => get_permalink( (int) get_option( 'gs_page_esperto' ) ),
	);

	$totale = 0;
	foreach ( $risultati as $lista ) { $totale += count( $lista ); }

	wp_send_json_success( array(
		'risultati' => $risultati,
		'link'      => $link,
		'totale'    => $totale,
	) );
}
