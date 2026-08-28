<?php
/**
 * points.php — Motore punti e livelli ("Le Insegne della Sfoglia").
 *
 * Tutti i punti passano da gs_add_points(): aggiorna il totale vita natural
 * durante, il totale dell'anno solare corrente, scrive il log e genera
 * l'evento di salita di livello quando serve.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Assegna (o sottrae) punti a un utente.
 *
 * @param int    $user_id ID utente.
 * @param int    $points  Punti da assegnare (può essere negativo).
 * @param string $reason  Motivo leggibile registrato nel log.
 * @return int Nuovo totale punti.
 */
function gs_add_points( $user_id, $points, $reason = '' ) {
	$user_id = (int) $user_id;
	$points  = (int) $points;

	// Una sfoglina congelata non guadagna punti, nemmeno da una scheda del
	// browser rimasta aperta o da una richiesta AJAX costruita a mano: le
	// pagine sono chiuse, ma gli handler wp_ajax_* restano raggiungibili.
	// Qui si scrive il totale, ed è l'unico posto: chiuderlo qui li chiude
	// tutti (trovato 26/08/2026, documento dei trenta giorni).
	//
	// I punti NEGATIVI passano sempre (sono le correzioni del gestore), e
	// passa tutto quello che fa un gestore per conto di qualcun altro dal
	// pannello «Correggi punti di una sfoglina».
	if ( $points > 0
		&& function_exists( 'gs_sfoglina_congelata' ) && gs_sfoglina_congelata( $user_id )
		&& ! ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) ) {
		return (int) get_user_meta( $user_id, 'gs_points', true );
	}

	if ( ! $user_id || 0 === $points ) {
		return (int) get_user_meta( $user_id, 'gs_points', true );
	}

	// Livello prima dell'assegnazione. Questa lettura non è protetta da
	// corse (vedi sotto): nel peggiore dei casi, con due assegnazioni
	// quasi simultanee, l'evento "sei salita di livello" potrebbe scattare
	// due volte invece di una — fastidioso ma innocuo. Non è più, come
	// prima del 14/08/2026, il TOTALE dei punti a poter sparire.
	$old_total = (int) get_user_meta( $user_id, 'gs_points', true );
	$old_level = gs_level_index( $old_total );

	// --- Totale "vita natural durante": incremento atomico ---------------
	// Fino al 14/08/2026 questo era un leggi-poi-scrivi (get_user_meta,
	// calcola, update_user_meta): un test di carico reale sulla stessa
	// identica logica di voto (gs_ajax_vota) ha dimostrato che questo
	// schema perde scritture quando più eventi capitano quasi insieme sulla
	// stessa sfoglina — es. più voti ricevuti in pochi secondi su una
	// sfoglia popolare. Un UPDATE SQL diretto, con l'aritmetica fatta da
	// MySQL, è atomico: non c'è più nulla da leggere prima di scrivere, e
	// quindi nulla che una richiesta concorrente possa far perdere.
	global $wpdb;
	if ( ! metadata_exists( 'user', $user_id, 'gs_points' ) ) {
		add_user_meta( $user_id, 'gs_points', 0 );
	}
	$wpdb->query( $wpdb->prepare(
		"UPDATE {$wpdb->usermeta} SET meta_value = GREATEST(0, CAST(meta_value AS SIGNED) + %d) WHERE user_id = %d AND meta_key = 'gs_points'",
		$points, $user_id
	) );
	wp_cache_delete( $user_id, 'user_meta' ); // il wpdb->query() sopra scavalca la cache: va svuotata a mano.
	$new_total = (int) get_user_meta( $user_id, 'gs_points', true );

	// --- Totale per anno solare: stesso incremento atomico ---------------
	$year      = date( 'Y', current_time( 'timestamp' ) );
	$year_key  = 'gs_points_' . $year;
	if ( ! metadata_exists( 'user', $user_id, $year_key ) ) {
		add_user_meta( $user_id, $year_key, 0 );
	}
	$wpdb->query( $wpdb->prepare(
		"UPDATE {$wpdb->usermeta} SET meta_value = GREATEST(0, CAST(meta_value AS SIGNED) + %d) WHERE user_id = %d AND meta_key = %s",
		$points, $user_id, $year_key
	) );
	wp_cache_delete( $user_id, 'user_meta' );

	// --- Totale del mese in corso: sistema mensile di gioco (Ennio,
	// 19-20/08/2026) — secondo conteggio parallelo, azzerato ogni mese,
	// separato dal totale vita natural durante sopra (quello NON si tocca,
	// resta la base dei livelli). Stesso incremento atomico via SQL diretto,
	// per non reintrodurre la stessa corsa critica corretta il 14/08/2026
	// su gs_points. Chiave "gs_points_mese_AAAA-MM": vedi buono-sfoglia.php
	// per la lettura/chiusura di fine mese. */
	$mese_key = 'gs_points_mese_' . date( 'Y-m', current_time( 'timestamp' ) );
	if ( ! metadata_exists( 'user', $user_id, $mese_key ) ) {
		add_user_meta( $user_id, $mese_key, 0 );
	}
	$wpdb->query( $wpdb->prepare(
		"UPDATE {$wpdb->usermeta} SET meta_value = GREATEST(0, CAST(meta_value AS SIGNED) + %d) WHERE user_id = %d AND meta_key = %s",
		$points, $user_id, $mese_key
	) );
	wp_cache_delete( $user_id, 'user_meta' );

	// --- Totale annuale per il Premio di Fine Anno: chiude il 13 dicembre,
	// non il 31 (Ennio, 19-20/08/2026) — un contatore NUOVO e distinto da
	// gs_points_YEAR sopra (che continua a esistere per compatibilità ma
	// non è più letto da year-prize.php): dopo il 13 dicembre i punti
	// continuano ad arrivare (vita natural durante e mese in corso), solo
	// non contano più per la classifica dell'anno già chiusa. */
	if ( date( 'm-d', current_time( 'timestamp' ) ) <= '12-13' ) {
		$anno_key = 'gs_points_anno_' . $year;
		if ( ! metadata_exists( 'user', $user_id, $anno_key ) ) {
			add_user_meta( $user_id, $anno_key, 0 );
		}
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->usermeta} SET meta_value = GREATEST(0, CAST(meta_value AS SIGNED) + %d) WHERE user_id = %d AND meta_key = %s",
			$points, $user_id, $anno_key
		) );
		wp_cache_delete( $user_id, 'user_meta' );
	}

	// --- Log leggibile ---
	gs_log_points( $user_id, $points, $reason, $new_total );

	// --- Evento salita di livello ---
	$new_level = gs_level_index( $new_total );
	if ( $new_level > $old_level ) {
		update_user_meta( $user_id, 'gs_level', $new_level );
		/**
		 * Hook lanciato quando una sfoglina sale di livello.
		 * Usato da notifications.php per inviare l'email di congratulazioni.
		 */
		do_action( 'gs_level_up', $user_id, $new_level, $old_level );
	}

	return $new_total;
}

/**
 * Scrive una voce nel log punti dell'utente (ultime 100 conservate).
 *
 * ATTENZIONE: è un leggi-modifica-scrivi su un solo meta, quindi due
 * assegnazioni simultanee sulla stessa sfoglina possono far perdere una
 * delle due righe — i PUNTI non si perdono (l'aumento è atomico, vedi
 * gs_add_points), lo storico sì. Non usare questo elenco come unica prova
 * di cosa è successo, e tenerne conto nelle reti di sicurezza che lo
 * leggono (trovato il 25/08/2026 — vale anche per gs_pagamenti_log e
 * gs_token_log, stessa forma, stesso limite).
 */
function gs_log_points( $user_id, $points, $reason, $total ) {
	$log = get_user_meta( $user_id, 'gs_points_log', true );
	if ( ! is_array( $log ) ) {
		$log = array();
	}
	array_unshift( $log, array(
		'time'   => current_time( 'mysql' ),
		'points' => (int) $points,
		'reason' => sanitize_text_field( $reason ),
		'total'  => (int) $total,
	) );
	$log = array_slice( $log, 0, 100 );
	update_user_meta( $user_id, 'gs_points_log', $log );
}

/**
 * Restituisce le ultime N voci del log punti di un utente.
 */
function gs_get_points_log( $user_id, $limit = 15 ) {
	$log = get_user_meta( $user_id, 'gs_points_log', true );
	if ( ! is_array( $log ) ) {
		return array();
	}
	return array_slice( $log, 0, (int) $limit );
}

/**
 * Indice del livello (0-based) dato un totale punti.
 */
function gs_level_index( $total ) {
	$levels = gs_settings()['levels'];
	$index  = 0;
	foreach ( $levels as $i => $level ) {
		if ( (int) $total >= (int) $level['soglia'] ) {
			$index = $i;
		}
	}
	return $index;
}

/**
 * Dati del livello corrente di un utente.
 */
function gs_get_level( $user_id ) {
	$total  = (int) get_user_meta( $user_id, 'gs_points', true );
	$levels = gs_settings()['levels'];
	$index  = gs_level_index( $total );

	$current = $levels[ $index ];
	$next    = isset( $levels[ $index + 1 ] ) ? $levels[ $index + 1 ] : null;

	$progress = 100;
	$to_next  = 0;
	if ( $next ) {
		$base  = (int) $current['soglia'];
		$top   = (int) $next['soglia'];
		$range = max( 1, $top - $base );
		$progress = min( 100, round( ( ( $total - $base ) / $range ) * 100 ) );
		$to_next  = max( 0, $top - $total );
	}

	$risultato = array(
		'index'     => $index,
		'numero'    => $index + 1,
		'titolo'    => $current['titolo'],
		'simbolo'   => $current['simbolo'],
		'punti'     => $total,
		'next'      => $next,
		'progress'  => $progress,
		'to_next'   => $to_next,
	);

	// Titolo onorario: sostituisce l'etichetta del livello ovunque compaia
	// (richiesto da Ennio il 18/08/2026, per Bruno Cingolani → "Socio
	// Onorario") — non tocca punti né avanzamento, solo il titolo/simbolo
	// mostrati. Si imposta dalla scheda personale della sfoglina.
	$onorario = trim( (string) get_user_meta( $user_id, 'gs_titolo_onorario', true ) );
	if ( $onorario ) {
		$risultato['titolo']  = $onorario;
		$risultato['simbolo'] = '🏵️';
	}

	return $risultato;
}

/**
 * Tabellone del percorso: le tappe (livelli) disegnate come un percorso a
 * curve, col tratto già fatto in oro e un segnaposto sulla posizione
 * esatta tra la tappa attuale e la prossima. Sostituisce la barra di
 * avanzamento semplice, stessi dati (indice livello, progresso), solo la
 * presentazione cambia (approvato in anteprima, 2026-07-21).
 */
function gs_tabellone_percorso_html( $level ) {
	$levels = gs_settings()['levels'];
	$n      = count( $levels );
	if ( $n < 2 ) {
		return ''; // con un solo livello non c'è un percorso da disegnare
	}

	$w  = 90 + ( $n - 1 ) * 130;
	$h  = 190;
	$xs = array();
	$ys = array();
	for ( $i = 0; $i < $n; $i++ ) {
		$xs[ $i ] = 50 + $i * ( ( $w - 100 ) / ( $n - 1 ) );
		$ys[ $i ] = 105 + 40 * sin( $i * 1.15 );
	}

	$corrente = (int) $level['index'];

	$path_tutta = 'M ' . round( $xs[0], 1 ) . ' ' . round( $ys[0], 1 );
	for ( $i = 1; $i < $n; $i++ ) {
		$cx          = round( ( $xs[ $i - 1 ] + $xs[ $i ] ) / 2, 1 );
		$path_tutta .= ' Q ' . $cx . ' ' . round( $ys[ $i - 1 ], 1 ) . ' ' . round( $xs[ $i ], 1 ) . ' ' . round( $ys[ $i ], 1 );
	}

	// Segnaposto: interpolato tra la tappa attuale e la prossima, secondo la percentuale già calcolata da gs_get_level().
	$marker_x = $xs[ $corrente ];
	$marker_y = $ys[ $corrente ];
	if ( $level['next'] && isset( $xs[ $corrente + 1 ] ) ) {
		$frac     = min( 1, max( 0, $level['progress'] / 100 ) );
		$marker_x = $xs[ $corrente ] + ( $xs[ $corrente + 1 ] - $xs[ $corrente ] ) * $frac;
		$marker_y = $ys[ $corrente ] + ( $ys[ $corrente + 1 ] - $ys[ $corrente ] ) * $frac;
	}

	$path_fatta = 'M ' . round( $xs[0], 1 ) . ' ' . round( $ys[0], 1 );
	for ( $i = 1; $i <= $corrente; $i++ ) {
		$cx          = round( ( $xs[ $i - 1 ] + $xs[ $i ] ) / 2, 1 );
		$path_fatta .= ' Q ' . $cx . ' ' . round( $ys[ $i - 1 ], 1 ) . ' ' . round( $xs[ $i ], 1 ) . ' ' . round( $ys[ $i ], 1 );
	}
	if ( $level['next'] ) {
		$cx          = round( ( $xs[ $corrente ] + $marker_x ) / 2, 1 );
		$path_fatta .= ' Q ' . $cx . ' ' . round( $ys[ $corrente ], 1 ) . ' ' . round( $marker_x, 1 ) . ' ' . round( $marker_y, 1 );
	}

	$out  = '<div class="gs-tabellone"><svg viewBox="0 0 ' . (int) $w . ' ' . (int) $h . '" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid meet">';
	$out .= '<path d="' . esc_attr( $path_tutta ) . '" fill="none" stroke="rgba(255,255,255,.25)" stroke-width="6" stroke-linecap="round"/>';
	$out .= '<path d="' . esc_attr( $path_fatta ) . '" fill="none" stroke="#d4a017" stroke-width="6" stroke-linecap="round"/>';

	foreach ( $levels as $i => $lv ) {
		$x = round( $xs[ $i ], 1 );
		$y = round( $ys[ $i ], 1 );
		if ( $i < $corrente ) {
			$fill = '#8a5a1f'; $stroke = '#f0c96b'; $sw = '2.5'; $r = 22; $fs = 16;
		} elseif ( $i === $corrente ) {
			$fill = '#a8574a'; $stroke = '#fff'; $sw = '3'; $r = 25; $fs = 20;
		} else {
			$fill = 'rgba(255,255,255,.1)'; $stroke = 'rgba(255,255,255,.4)'; $sw = '2'; $r = 19; $fs = 16;
		}
		$out .= '<g transform="translate(' . $x . ',' . $y . ')">';
		$out .= '<circle r="' . $r . '" fill="' . $fill . '" stroke="' . $stroke . '" stroke-width="' . $sw . '"/>';
		$out .= '<text y="-1" text-anchor="middle" dominant-baseline="central" font-size="' . $fs . '">' . esc_html( $lv['simbolo'] ) . '</text>';
		$out .= '<text y="' . ( $r + 14 ) . '" text-anchor="middle" font-size="9" fill="rgba(255,255,255,.6)" font-family="sans-serif">' . (int) $lv['soglia'] . '</text>';
		$out .= '</g>';
	}

	if ( $level['next'] ) {
		$out .= '<g transform="translate(' . round( $marker_x, 1 ) . ',' . round( $marker_y, 1 ) . ')" style="filter:drop-shadow(0 3px 4px rgba(0,0,0,.35))">';
		$out .= '<circle r="9" fill="#fff"/><circle r="9" fill="none" stroke="#8a5a1f" stroke-width="2"/>';
		$out .= '<text y="0" text-anchor="middle" dominant-baseline="central" font-size="11">' . esc_html( $level['simbolo'] ) . '</text>';
		$out .= '</g>';
	}

	$out .= '</svg></div>';
	return $out;
}

/**
 * Totale punti dell'anno solare indicato (default: anno corrente).
 * NOTA: non più usato dal Premio di Fine Anno dal 22/08/2026 — vedi
 * gs_get_anno_gioco_points() qui sotto, che chiude il 13 dicembre.
 */
function gs_get_year_points( $user_id, $year = null ) {
	$year = $year ? $year : date( 'Y', current_time( 'timestamp' ) );
	return (int) get_user_meta( $user_id, 'gs_points_' . $year, true );
}

/**
 * Totale punti del mese indicato (default: mese in corso). Formato $ym:
 * "AAAA-MM", es. "2026-08". Sistema mensile di gioco (Ennio, 19-20/08/2026).
 */
function gs_get_month_points( $user_id, $ym = null ) {
	$ym = $ym ? $ym : date( 'Y-m', current_time( 'timestamp' ) );
	return (int) get_user_meta( $user_id, 'gs_points_mese_' . $ym, true );
}

/**
 * Totale dell'anno di gioco (gennaio → 13 dicembre incluso) per il Premio
 * di Fine Anno — distinto da gs_get_year_points() sopra, che copre tutto
 * l'anno solare fino al 31 dicembre e non è più quello giusto per questo
 * premio (Ennio, 19-20/08/2026: "il gioco... si chiude il 13 dicembre").
 */
function gs_get_anno_gioco_points( $user_id, $year = null ) {
	$year = $year ? $year : date( 'Y', current_time( 'timestamp' ) );
	return (int) get_user_meta( $user_id, 'gs_points_anno_' . $year, true );
}
