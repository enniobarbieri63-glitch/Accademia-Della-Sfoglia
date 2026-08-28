<?php
/**
 * classifica-mensile.php — Classifica animata del mese, "Matterello che
 * stende" (sistema mensile di gioco, Ennio 19-20/08/2026).
 *
 * Mostra le prime 10 sfogline per punti del MESE in corso (gs_get_month_points()
 * in points.php — non il totale vita natural durante, non l'anno di gioco:
 * la competizione mensile ripartita a zero il 1° di ogni mese). Aggiunta in
 * cima a [gs_classifica], sopra la tabella generale già esistente, che resta
 * invariata.
 *
 * L'animazione — una sfoglia cruda che copre le schede finché il matterello
 * non la stende, rivelandole una per una — riprende fedelmente il prototipo
 * 2026-08-16_vetrine-terza-serie.html (sezione 6), pensato lì per le Vetrine
 * e qui riusato per la Classifica su richiesta esplicita di Ennio. Gioca
 * SOLO al primo caricamento della pagina (già nel prototipo originale era
 * segnalato "l'effetto va visto una volta sola per sessione" — rifarla ad
 * ogni aggiornamento sarebbe stancante): i numeri restano "in tempo reale"
 * perché il contenuto delle schede si aggiorna da solo con un polling
 * leggero, senza ripetere lo stendino ogni volta — stesso principio già
 * seguito per la Torre di controllo (mai congelare un numero vero dietro
 * una cache, ma qui il "vero" è il dato, non l'animazione d'ingresso).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Le prime N sfogline per punti del mese indicato (default: mese in corso).
 * Stesso schema di gs_leaderboard() (voting.php) e gs_year_leaderboard()
 * (year-prize.php): query su tutti gli utenti ordinata per meta numerico,
 * filtro gs_e_sfoglina_vera() DOPO (WP_User_Query non sa filtrare per ruolo
 * insieme a un ordinamento per meta), 'number' a -1 per non tagliare via
 * sfogline vere prima del filtro.
 *
 * @return array di array( 'user' => WP_User, 'punti' => int )
 */
function gs_classifica_mensile_top( $limit = 10, $ym = null ) {
	$ym       = $ym ? $ym : date( 'Y-m', current_time( 'timestamp' ) );
	$meta_key = 'gs_points_mese_' . $ym;

	$tutti = get_users( array(
		'meta_key' => $meta_key,
		'orderby'  => 'meta_value_num',
		'order'    => 'DESC',
		'number'   => -1,
	) );
	$veri  = array_values( array_filter( $tutti, 'gs_e_sfoglina_vera' ) );
	$veri  = array_slice( $veri, 0, $limit );

	$out = array();
	foreach ( $veri as $user ) {
		$out[] = array( 'user' => $user, 'punti' => (int) get_user_meta( $user->ID, $meta_key, true ) );
	}
	return $out;
}

/**
 * Endpoint di aggiornamento "in tempo reale": la stessa classifica in JSON,
 * chiamata dal polling lato client per rinfrescare le schede già rivelate,
 * senza ricaricare la pagina né ripetere l'animazione del matterello.
 */
add_action( 'wp_ajax_gs_classifica_mensile_dati', 'gs_ajax_classifica_mensile_dati' );
add_action( 'wp_ajax_nopriv_gs_classifica_mensile_dati', 'gs_ajax_classifica_mensile_dati' );
function gs_ajax_classifica_mensile_dati() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$righe = array();
	foreach ( gs_classifica_mensile_top( 10 ) as $r ) {
		$righe[] = array(
			'id'     => $r['user']->ID,
			'nome'   => $r['user']->display_name,
			'punti'  => $r['punti'],
			'link'   => esc_url( function_exists( 'gs_vetrina_url' ) ? gs_vetrina_url( $r['user']->ID ) : '' ),
			'iniziali' => gs_classifica_mensile_iniziali( $r['user']->display_name ),
		);
	}
	wp_send_json_success( array( 'righe' => $righe, 'mese' => date_i18n( 'F Y', current_time( 'timestamp' ) ) ) );
}

/** Iniziali (max 2 lettere) per l'avatar segnaposto di una scheda. */
function gs_classifica_mensile_iniziali( $nome ) {
	$parole = preg_split( '/\s+/', trim( (string) $nome ) );
	$ini    = '';
	foreach ( array_slice( $parole, 0, 2 ) as $p ) {
		if ( $p !== '' ) { $ini .= mb_strtoupper( mb_substr( $p, 0, 1 ) ); }
	}
	return $ini ?: '?';
}

/**
 * HTML della classifica animata — da inserire in cima a [gs_classifica].
 * Le prime 3 posizioni hanno un colore dedicato (oro/argento/bronzo), le
 * altre condividono la stessa sfumatura terracotta del resto del plugin.
 */
function gs_classifica_mensile_html() {
	$righe = gs_classifica_mensile_top( 10 );
	if ( ! $righe ) {
		return '';
	}
	$mese = date_i18n( 'F Y', current_time( 'timestamp' ) );

	$out  = '<div class="gs-cm-intestazione"><h3>🥖 Classifica del mese — ' . esc_html( $mese ) . '</h3>';
	$out .= '<p class="gs-hint">Le prime 10 per punti guadagnati questo mese (si azzera il 1° di ogni mese — è la corsa al Buono Sfoglia, separata dal totale punti di sempre). Premi il matterello per scoprirle.</p></div>';

	$out .= '<div class="gs-cm-scena" id="gsCmScena">';
	$out .= '<div class="gs-cm-schede" id="gsCmSchede">';
	$posto = 1;
	foreach ( $righe as $r ) {
		$rango = $posto <= 3 ? ' gs-cm-rango-' . $posto : '';
		$link  = function_exists( 'gs_vetrina_url' ) ? gs_vetrina_url( $r['user']->ID ) : '';
		$out  .= '<a class="gs-cm-scheda' . $rango . '" href="' . esc_url( $link ) . '" data-uid="' . (int) $r['user']->ID . '">';
		$out  .= '<div class="gs-cm-posto">' . $posto . '</div>';
		$out  .= '<div class="gs-cm-avatar">' . esc_html( gs_classifica_mensile_iniziali( $r['user']->display_name ) ) . '</div>';
		$out  .= '<div class="gs-cm-nome">' . esc_html( $r['user']->display_name ) . '</div>';
		$out  .= '<div class="gs-cm-punti">' . (int) $r['punti'] . ' pt</div>';
		$out  .= '</a>';
		$posto++;
	}
	$out .= '</div>'; // .gs-cm-schede
	$out .= '<div class="gs-cm-sfoglia" id="gsCmSfoglia"></div>';
	$out .= '<div class="gs-cm-matterello" id="gsCmMatterello"><div class="gs-cm-manico su"></div><div class="gs-cm-corpo"></div><div class="gs-cm-manico giu"></div></div>';
	$out .= '</div>'; // .gs-cm-scena
	$out .= '<button type="button" class="gs-btn gs-btn-sm gs-cm-stendi" id="gsCmStendi">🥖 Stendi la sfoglia!</button>';

	return $out;
}
