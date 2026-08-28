<?php
/**
 * badges.php — Definizione badge e trigger automatici di sblocco.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Coppia di riserva (luce/ombra) per i badge "dinamici" (chiave non fissa,
 *  es. "Corso con Rina Poletti YYYY" in cronologia.php): non hanno una voce
 *  in gs_get_badges_definitions(), quindi niente coppia propria da usare —
 *  qui la stessa di "Top 3 della Settimana". */
function gs_badge_colore_riserva() {
	return array( '#bd8a13', '#8f6a10' );
}

/** Genera l'attributo style con --bc-luce/--bc-ombra (i due estremi del
 *  gradiente) a partire da una coppia [luce, ombra]. */
function gs_badge_style_colore( $coppia ) {
	return 'style="--bc-luce:' . esc_attr( $coppia[0] ) . ';--bc-ombra:' . esc_attr( $coppia[1] ) . '"';
}

/**
 * Definizioni dei badge.
 */
function gs_get_badges_definitions() {
	// 'colore': coppia [luce, ombra] per il gradiente della scheda — le
	// stesse identiche 9 sfumature (stesso angolo 150deg) già usate nelle
	// schede "a cannuccia" della pagina Contenuti del sito
	// (Registro Ufficiale, Novità, Eventi, Lentium Notizie, Libri, Rina
	// Poletti, Dicono di Noi, Galleria, Consigli della Community) — copiate
	// dal vero su richiesta di Ennio il 18/08/2026 ("segui le tonalità che
	// ti ho inviato"), non la tavolozza dei Palloncini usata in un primo
	// tentativo poi scartato ("non mi piacciono, meglio i precedenti").
	return array(
		'prima_fontana'    => array( 'icon' => '🥚', 'label' => 'Prima Fontana', 'desc' => 'Prima sfoglia pubblicata in assoluto.', 'punti' => 10, 'colore' => array( '#cd8b0c', '#8a5a10' ), 'pietra' => 'acquamarina', 'gradiente' => 'radial-gradient(circle at 34% 24%,#1F7A85 0%,#12555E 46%,#08343A 100%)' ),
		'sfoglina_stagionale' => array( 'icon' => '🌾', 'label' => 'Sfoglina Stagionale', 'desc' => 'Partecipazione in tutti e 4 i trimestri dell\'anno.', 'punti' => 40, 'colore' => array( '#b5722a', '#7a4a24' ), 'pietra' => 'olivina', 'gradiente' => 'radial-gradient(circle at 34% 24%,#5E7A22 0%,#415614 46%,#28340B 100%)' ),
		'top3_settimana'   => array( 'icon' => '🏆', 'label' => 'Top 3 della Settimana', 'desc' => 'Posizione 1°, 2° o 3° in una sfida.', 'punti' => 30, 'colore' => array( '#bd8a13', '#8f6a10' ), 'pietra' => 'ambra', 'gradiente' => 'radial-gradient(circle at 34% 24%,#B4762A 0%,#8A5312 46%,#55320A 100%)' ),
		'custode_nonna'    => array( 'icon' => '👑', 'label' => 'Custode della Nonna', 'desc' => 'Diario o consiglio segnato come "ricetta di famiglia originale".', 'punti' => 30, 'colore' => array( '#d9843f', '#8f4f22' ), 'pietra' => 'porpora antica', 'gradiente' => 'radial-gradient(circle at 34% 24%,#8E2F55 0%,#661A3A 46%,#3E0C22 100%)' ),
		'amante_evo'       => array( 'icon' => '🫒', 'label' => 'Amante dell\'EVO', 'desc' => '5 contenuti che citano olio EVO / extravergine.', 'punti' => 20, 'colore' => array( '#c9a13e', '#8a6a1e' ), 'pietra' => 'giada', 'gradiente' => 'radial-gradient(circle at 34% 24%,#3E7A56 0%,#25563A 46%,#133322 100%)' ),
		'sfoglina_veloce'  => array( 'icon' => '⏱️', 'label' => 'Sfoglina Veloce', 'desc' => 'Invio nelle ultime 24 ore prima della scadenza di una sfida.', 'punti' => 15, 'colore' => array( '#c17a45', '#7d4522' ), 'pietra' => 'corniola', 'gradiente' => 'radial-gradient(circle at 34% 24%,#A8482A 0%,#7A2E15 46%,#4A1B0B 100%)' ),
		'maestra_generosa' => array( 'icon' => '💬', 'label' => 'Maestra Generosa', 'desc' => '50 commenti lasciati su sfoglie altrui.', 'punti' => 60, 'colore' => array( '#cf9448', '#8d5c24' ), 'pietra' => 'malachite', 'gradiente' => 'radial-gradient(circle at 34% 24%,#1F7A66 0%,#125548 46%,#08332B 100%)' ),
		'voce_di_famiglia' => array( 'icon' => '🍝', 'label' => 'Voce di Famiglia', 'desc' => 'Prima ricetta di famiglia approvata nel Ricettario.', 'punti' => 25, 'colore' => array( '#dba759', '#96601f' ), 'pietra' => 'indaco', 'gradiente' => 'radial-gradient(circle at 34% 24%,#3E3A8E 0%,#272466 46%,#15133E 100%)' ),
		'esploratrice_lezioni' => array( 'icon' => '🎬', 'label' => 'Esploratrice delle Lezioni', 'desc' => 'Ha guardato almeno 5 lezioni video diverse.', 'punti' => 20, 'colore' => array( '#c98f4a', '#835220' ), 'pietra' => 'tormalina', 'gradiente' => 'radial-gradient(circle at 34% 24%,#7A2F6E 0%,#561A4E 46%,#340C2E 100%)' ),
	);
}

/** Numero romano I-IX per posizione (0-based) — restyling "gioiello" dei 9
 *  badge (Ennio, 22/08/2026): sul medaglione compare il traguardo in numero
 *  romano invece dell'icona emoji originale. */
function gs_badge_romano( $indice ) {
	$romani = array( 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX' );
	return isset( $romani[ $indice ] ) ? $romani[ $indice ] : ( $indice + 1 );
}

/**
 * Badge sbloccati da un utente.
 */
function gs_get_user_badges( $user_id ) {
	$badges = get_user_meta( $user_id, 'gs_badges', true );
	return is_array( $badges ) ? $badges : array();
}

/**
 * Sblocca un badge (se non già posseduto). Assegna punti e lancia l'evento.
 */
function gs_unlock_badge( $user_id, $badge_key ) {
	$defs = gs_get_badges_definitions();
	if ( ! isset( $defs[ $badge_key ] ) ) {
		return false;
	}

	$owned = gs_get_user_badges( $user_id );
	if ( in_array( $badge_key, $owned, true ) ) {
		return false; // già sbloccato
	}

	$owned[] = $badge_key;
	update_user_meta( $user_id, 'gs_badges', $owned );

	// Data di sblocco (per il feed "Ultimi Traguardi" in traguardi.php).
	$log = get_user_meta( $user_id, 'gs_badges_log', true );
	$log = is_array( $log ) ? $log : array();
	$log[ $badge_key ] = current_time( 'timestamp' );
	update_user_meta( $user_id, 'gs_badges_log', $log );

	gs_add_points( $user_id, (int) $defs[ $badge_key ]['punti'], 'Badge sbloccato: ' . $defs[ $badge_key ]['label'] );

	/** Usato da notifications.php per l'email. */
	do_action( 'gs_badge_unlocked', $user_id, $badge_key );
	return true;
}

// -----------------------------------------------------------------------------
// Trigger automatici
// -----------------------------------------------------------------------------

// Prima Fontana + Sfoglina Veloce + Sfoglina Stagionale.
add_action( 'gs_sfoglia_pubblicata', 'gs_badge_on_sfoglia', 10, 3 );
function gs_badge_on_sfoglia( $user_id, $sfoglia_id, $sfida_id ) {

	// Prima Fontana: prima sfoglia in assoluto.
	$count = (int) get_user_meta( $user_id, 'gs_num_sfoglie', true );
	$count++;
	update_user_meta( $user_id, 'gs_num_sfoglie', $count );
	if ( 1 === $count ) {
		gs_unlock_badge( $user_id, 'prima_fontana' );
	}

	// Sfoglina Veloce: invio nelle ultime 24h prima della scadenza.
	$fine = strtotime( get_post_meta( $sfida_id, 'gs_data_fine', true ) );
	if ( $fine ) {
		$diff = $fine - current_time( 'timestamp' );
		if ( $diff >= 0 && $diff <= DAY_IN_SECONDS ) {
			gs_unlock_badge( $user_id, 'sfoglina_veloce' );
		}
	}

	// Sfoglina Stagionale: registra il trimestre e verifica i 4.
	$q = gs_current_quarter();
	$quarters = get_user_meta( $user_id, 'gs_quarters', true );
	if ( ! is_array( $quarters ) ) {
		$quarters = array();
	}
	$year_q = date( 'Y', current_time( 'timestamp' ) );
	if ( ! isset( $quarters[ $year_q ] ) ) {
		$quarters[ $year_q ] = array();
	}
	$qn = substr( $q, -2 ); // "Q1".."Q4"
	if ( ! in_array( $qn, $quarters[ $year_q ], true ) ) {
		$quarters[ $year_q ][] = $qn;
	}
	update_user_meta( $user_id, 'gs_quarters', $quarters );
	if ( count( $quarters[ $year_q ] ) >= 4 ) {
		gs_unlock_badge( $user_id, 'sfoglina_stagionale' );
	}
}

// Top 3 della Settimana.
add_action( 'gs_podio_sfida', function ( $user_id, $sfida_id, $posizione ) {
	if ( $posizione <= 3 ) {
		gs_unlock_badge( $user_id, 'top3_settimana' );
	}
}, 10, 3 );

// Custode della Nonna: contenuto segnato come "ricetta di famiglia originale".
add_action( 'gs_ricetta_famiglia', function ( $user_id ) {
	gs_unlock_badge( $user_id, 'custode_nonna' );
}, 10, 1 );

// Amante dell'EVO: 5 contenuti che citano olio EVO / extravergine.
add_action( 'gs_contenuto_evo', function ( $user_id ) {
	$n = (int) get_user_meta( $user_id, 'gs_evo_count', true ) + 1;
	update_user_meta( $user_id, 'gs_evo_count', $n );
	if ( $n >= 5 ) {
		gs_unlock_badge( $user_id, 'amante_evo' );
	}
}, 10, 1 );

// Maestra Generosa: 50 commenti su sfoglie altrui.
add_action( 'gs_commento_sfoglia', function ( $user_id ) {
	$n = (int) get_user_meta( $user_id, 'gs_commenti_count', true ) + 1;
	update_user_meta( $user_id, 'gs_commenti_count', $n );
	if ( $n >= 50 ) {
		gs_unlock_badge( $user_id, 'maestra_generosa' );
	}
}, 10, 1 );

// Voce di Famiglia: prima ricetta di famiglia approvata nel Ricettario.
add_action( 'gs_ricetta_approvata', function ( $user_id ) {
	gs_unlock_badge( $user_id, 'voce_di_famiglia' );
}, 10, 1 );

// Esploratrice delle Lezioni: almeno 5 lezioni video diverse guardate.
add_action( 'gs_lezione_vista', function ( $user_id ) {
	$viste = get_user_meta( $user_id, 'gs_lezioni_viste', true );
	$n = is_array( $viste ) ? count( $viste ) : 0;
	if ( $n >= 5 ) {
		gs_unlock_badge( $user_id, 'esploratrice_lezioni' );
	}
}, 10, 1 );

/**
 * Rileva menzioni di olio EVO in un testo e lancia l'hook relativo.
 */
function gs_detect_evo( $user_id, $text ) {
	if ( preg_match( '/\b(evo|extra\s?vergine|extravergine)\b/i', $text ) ) {
		do_action( 'gs_contenuto_evo', $user_id );
	}
}
