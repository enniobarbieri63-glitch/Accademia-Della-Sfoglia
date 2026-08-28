<?php
/**
 * percorsi-lezioni.php — Percorsi Guidati: gruppi ordinati di Lezioni Video
 * (es. "Dalla sfoglia ai tortellini"). Un solo Custom Post Type
 * (gs_percorso_lezioni), nessuna tabella nuova. Quando una sfoglina ha
 * visto tutte le lezioni di un percorso, sblocca un badge dedicato a quel
 * percorso (chiave dinamica, come il badge "Corso con Rina Poletti" di
 * year-prize.php) più qualche punto, e riceve un avviso.
 *
 * CPT gs_percorso_lezioni → meta:
 *  - gs_lezioni_ordine (array di ID di gs_lezione, nell'ordine del percorso)
 *  - gs_percorso_ordine (numero: posizione del percorso nella sequenza)
 * Il nome del percorso sta in post_title, la descrizione in post_content.
 *
 * Propedeuticità (2026-07-22, su richiesta esplicita di Ennio: "una vera
 * scuola... partendo dal basso"): i percorsi si sbloccano in sequenza. Il
 * primo (per gs_percorso_ordine) è sempre accessibile; ogni percorso
 * successivo si sblocca solo quando la sfoglina ha completato quello
 * immediatamente precedente. Finché un percorso è bloccato, le sue lezioni
 * non si possono aprire dalla Libreria Video (a meno che la stessa lezione
 * non compaia anche in un percorso già sbloccato, o in nessun percorso).
 *
 * Vetrina pubblica (2026-07-23): lo shortcode [gs_percorsi_pubblici] mostra
 * la mappa dei percorsi (titolo, numero di lezioni, descrizione) anche a chi
 * non si è ancora iscritto, a scopo motivazionale — senza dare accesso alle
 * lezioni.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_register_percorso_lezioni_cpt' );
function gs_register_percorso_lezioni_cpt() {
	register_post_type( 'gs_percorso_lezioni', array(
		'labels'          => array( 'name' => 'Percorsi Guidati', 'singular_name' => 'Percorso Guidato' ),
		'public'          => false,
		'show_ui'         => false,
		'show_in_menu'    => false,
		'supports'        => array( 'title', 'editor' ),
		'capability_type' => 'post',
	) );
}

// -----------------------------------------------------------------------------
// Helper
// -----------------------------------------------------------------------------

function gs_percorso_get( $id ) {
	$lezioni = get_post_meta( $id, 'gs_lezioni_ordine', true );
	return array(
		'id'           => $id,
		'titolo'       => get_the_title( $id ),
		'descrizione'  => get_post( $id ) ? get_post( $id )->post_content : '',
		'lezioni'      => is_array( $lezioni ) ? array_map( 'intval', $lezioni ) : array(),
		'ordine'       => (int) get_post_meta( $id, 'gs_percorso_ordine', true ),
		'livello'      => (string) get_post_meta( $id, 'gs_percorso_livello', true ),
		'data_inizio'  => (string) get_post_meta( $id, 'gs_percorso_data_inizio', true ),
		'data_fine'    => (string) get_post_meta( $id, 'gs_percorso_data_fine', true ),
		'squadra'      => (bool) get_post_meta( $id, 'gs_percorso_squadra', true ),
		'staffetta'    => (bool) get_post_meta( $id, 'gs_staffetta_attiva', true ),
	);
}

/**
 * Percorso a Tappe Stagionali (2026-07-24): un percorso con una data di
 * inizio e/o fine facoltative (es. "Percorso di Natale") esiste solo dentro
 * quella finestra, poi torna nascosto — non fa parte della sequenza con
 * propedeuticità dei Percorsi Guidati evergreen (non blocca né viene
 * bloccato da quelli), è semplicemente aperto o chiuso in base alla data.
 */
function gs_percorso_e_stagionale( $pid ) {
	$c = gs_percorso_get( $pid );
	return '' !== trim( $c['data_inizio'] ) || '' !== trim( $c['data_fine'] );
}

/** True se un percorso stagionale è dentro la sua finestra di date adesso (un percorso non stagionale è sempre "dentro"). */
function gs_percorso_in_stagione( $pid ) {
	$c = gs_percorso_get( $pid );
	// Confronto fra date nello stesso fuso: current_time('timestamp') è UTC
	// più lo scarto di WordPress, strtotime() lavora nel fuso del server, e
	// mescolarli sposta l'apertura/chiusura di una o due ore (stesso errore
	// di P3 sugli avvisi di scadenza, 25/08/2026).
	$oggi = current_time( 'Y-m-d' );
	if ( $c['data_inizio'] && $oggi < $c['data_inizio'] ) { return false; }
	if ( $c['data_fine']   && $oggi > $c['data_fine'] )   { return false; }
	return true;
}

/** True se il percorso è un Percorso di Squadra: avanzamento collettivo dei membri, non individuale. */
function gs_percorso_e_squadra( $pid ) {
	return (bool) get_post_meta( $pid, 'gs_percorso_squadra', true );
}

/** Solo i percorsi evergreen: quelli che partecipano alla sequenza con propedeuticità (esclude stagionali e di squadra). */
function gs_percorsi_sequenza() {
	return array_values( array_filter( gs_percorsi_tutti(), function ( $p ) {
		return ! gs_percorso_e_stagionale( $p->ID ) && ! gs_percorso_e_squadra( $p->ID );
	} ) );
}

/**
 * Quante lezioni distinte di un Percorso di Squadra ha già visto ALMENO UN
 * membro della squadra, sul totale — l'avanzamento è collettivo, non della
 * singola sfoglina.
 */
function gs_percorso_squadra_progresso( $pid, $team ) {
	$lezioni = gs_percorso_get( $pid )['lezioni'];
	$membri  = gs_squadra_membri( $team );
	$viste   = 0;
	foreach ( $lezioni as $lid ) {
		foreach ( $membri as $mid ) {
			if ( gs_lezione_e_vista( $lid, $mid ) ) { $viste++; break; }
		}
	}
	return array( 'viste' => $viste, 'totale' => count( $lezioni ) );
}

/** True se la squadra, insieme, ha visto tutte le lezioni del percorso (ognuna vista da almeno un membro). */
function gs_percorso_squadra_completato( $pid, $team ) {
	$lezioni = gs_percorso_get( $pid )['lezioni'];
	if ( ! $lezioni || ! $team ) { return false; }
	$membri = gs_squadra_membri( $team );
	if ( ! $membri ) { return false; }
	foreach ( $lezioni as $lid ) {
		$vista_da_qualcuno = false;
		foreach ( $membri as $mid ) {
			if ( gs_lezione_e_vista( $lid, $mid ) ) { $vista_da_qualcuno = true; break; }
		}
		if ( ! $vista_da_qualcuno ) { return false; }
	}
	return true;
}

/**
 * True se il percorso risulta completato "ai fini della visualizzazione e
 * del certificato" per questa sfoglina: per un Percorso di Squadra conta il
 * badge già assegnato (la squadra lo ha completato insieme, non serve che
 * la singola sfoglina abbia visto ogni lezione); per un percorso normale
 * conta come sempre l'aver visto personalmente tutte le lezioni.
 */
function gs_percorso_completato_effettivo_da( $pid, $uid ) {
	if ( gs_percorso_e_squadra( $pid ) ) {
		return in_array( 'percorso_' . $pid, gs_get_user_badges( $uid ), true );
	}
	return gs_percorso_completato_da( $pid, $uid );
}

/**
 * Assegna a un membro della squadra il badge/punti/avviso del percorso, nel
 * momento in cui la squadra lo ha completato insieme — stessi effetti di
 * gs_percorso_assegna_badge_se_completo(), ma senza richiedere che il
 * singolo membro abbia visto personalmente ogni lezione.
 */
function gs_percorso_squadra_badge_assegna( $pid, $uid ) {
	$badge_key = 'percorso_' . $pid;
	$owned     = gs_get_user_badges( $uid );
	if ( in_array( $badge_key, $owned, true ) ) { return false; } // già assegnato

	// Contrassegno dedicato, scritto PRIMA di punti ed email: l'array
	// gs_badges è un leggi-controlla-scrivi e non regge due esecuzioni
	// simultanee — il badge resta uno (l'ultima scrittura vince) ma i punti
	// si sommano due volte. Per i Percorsi di Squadra questo succede senza
	// bisogno di un doppio clic: bastano due socie che finiscono l'ultima
	// lezione insieme, e ognuna fa partire un giro su tutti i membri
	// (trovato il 25/08/2026). L'ordine conta: prima in_array (sopra),
	// poi questo — così chi ha già il badge da prima di questa correzione
	// non viene toccato.
	//
	// Il contrassegno da solo non basta: i meta utente sono già in cache
	// dall'inizio della richiesta (WordPress li carica tutti insieme al
	// riconoscimento di chi è collegato), quindi senza svuotarla
	// get_user_meta() qui sotto risponderebbe dalla fotografia di inizio
	// richiesta invece che dal database — il lucchetto che protegge questa
	// funzione (in gs_percorso_squadra_assegna_se_completo(), attorno al
	// giro su tutti i membri) non servirebbe a niente senza questa riga.
	// Stesso wp_cache_delete() già usato in gs_add_points() (points.php)
	// dopo i suoi incrementi atomici (corretto il 26/08/2026).
	wp_cache_delete( $uid, 'user_meta' );
	$chiave_fatto = 'gs_badge_dato_' . $badge_key;
	if ( get_user_meta( $uid, $chiave_fatto, true ) ) { return false; }
	update_user_meta( $uid, $chiave_fatto, current_time( 'mysql' ) );

	$percorso = gs_percorso_get( $pid );
	$owned[]  = $badge_key;
	update_user_meta( $uid, 'gs_badges', $owned );
	update_user_meta( $uid, 'gs_badge_label_' . $badge_key, '🎽 Percorso di Squadra completato: ' . $percorso['titolo'] );

	$log = get_user_meta( $uid, 'gs_badges_log', true );
	$log = is_array( $log ) ? $log : array();
	$log[ $badge_key ] = current_time( 'timestamp' );
	update_user_meta( $uid, 'gs_badges_log', $log );

	gs_add_points( $uid, gs_get_points_value( 'percorso_completato', 30 ), 'Percorso di Squadra completato: ' . $percorso['titolo'] );

	if ( function_exists( 'gs_accoda_volo' ) ) {
		gs_accoda_volo( $uid, 'LA TUA SQUADRA HA COMPLETATO IL PERCORSO: ' . mb_strtoupper( $percorso['titolo'] ), gs_pagina_url( 'gs_page_percorso_personale' ) );
	}
	$u = get_userdata( $uid );
	if ( $u ) {
		$oggetto_ps = 'La tua squadra ha completato un percorso! — Accademia della Sfoglia';
		$corpo_ps   = "Ciao " . $u->display_name . ",\n\nla tua squadra ha completato insieme il percorso \"" . $percorso['titolo'] . "\": ognuno di voi ha sbloccato il badge dedicato e qualche punto in più.\n\nAccademia della Sfoglia";
		if ( function_exists( 'gs_mail_progetto' ) ) {
			gs_mail_progetto( $uid, 'livelli', $oggetto_ps, $corpo_ps );
		} elseif ( $u->user_email ) {
			wp_mail( $u->user_email, $oggetto_ps, $corpo_ps );
		}
	}
	return true;
}

/** Se la squadra della sfoglina ha appena completato collettivamente questo percorso, assegna il badge a TUTTI i membri attuali che non lo hanno già. */
function gs_percorso_squadra_assegna_se_completo( $pid, $uid ) {
	$team = gs_get_user_team( $uid );
	if ( ! $team || ! gs_percorso_squadra_completato( $pid, $team ) ) { return false; }

	// Lucchetto sulla coppia percorso+squadra, stesso meccanismo dei posti
	// dei corsi (calendario.php:571): qui non basta un contrassegno per
	// sfoglina, perché due socie DIVERSE che finiscono l'ultima lezione
	// insieme fanno partire due giri completi su tutti i membri, e ognuna
	// legge i contrassegni dalla fotografia dei meta scattata all'inizio
	// della propria richiesta. Con una squadra da sei sono 360 punti invece
	// di 180 (trovato il 25/08/2026, corretto il 26/08/2026). Il lucchetto
	// va attorno al giro, non dentro: gs_percorso_squadra_badge_assegna()
	// non contiene nessun wp_send_json/return che possa saltare il rilascio
	// qui sotto — verificato.
	global $wpdb;
	$lock = 'gs_perc_sq_' . (int) $pid . '_' . md5( (string) $team );
	if ( '1' !== $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) ) ) {
		return false; // ci sta già pensando un'altra richiesta: va bene così
	}

	$assegnati = false;
	foreach ( gs_squadra_membri( $team ) as $mid ) {
		if ( gs_percorso_squadra_badge_assegna( $pid, $mid ) ) { $assegnati = true; }
	}

	$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	return $assegnati;
}

/** Solo i percorsi stagionali attualmente attivi (dentro la loro finestra) — gli altri restano nascosti. */
function gs_percorsi_stagionali_attivi() {
	return array_values( array_filter( gs_percorsi_tutti(), function ( $p ) {
		return gs_percorso_e_stagionale( $p->ID ) && gs_percorso_in_stagione( $p->ID );
	} ) );
}

/** Elenco dei livelli dei Percorsi Guidati, nell'ordine scelto dal titolare. */
function gs_percorso_livelli() {
	$s = gs_settings();
	return isset( $s['percorso_livelli'] ) && is_array( $s['percorso_livelli'] ) ? array_values( $s['percorso_livelli'] ) : array();
}

/** Tutti i percorsi pubblicati, in ordine di sequenza (gs_percorso_ordine crescente). */
function gs_percorsi_tutti() {
	$percorsi = gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_percorso_lezioni',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'suppress_filters' => true,
	) ), 'gs_percorso_lezioni' );
	usort( $percorsi, function ( $a, $b ) {
		$oa = (int) get_post_meta( $a->ID, 'gs_percorso_ordine', true );
		$ob = (int) get_post_meta( $b->ID, 'gs_percorso_ordine', true );
		return $oa <=> $ob ?: $a->ID <=> $b->ID;
	} );
	return $percorsi;
}

/**
 * True se la sfoglina può accedere a questo percorso: è il primo, o ha
 * completato il precedente. I percorsi stagionali e quelli di Squadra non
 * seguono questa regola: sono sempre aperti (i primi per tutta la loro
 * finestra di date, i secondi sempre), senza propedeuticità.
 */
function gs_percorso_sbloccato_per( $pid, $uid ) {
	if ( gs_percorso_e_stagionale( $pid ) ) {
		return gs_percorso_in_stagione( $pid );
	}
	if ( gs_percorso_e_squadra( $pid ) ) {
		return true;
	}
	$percorsi = gs_percorsi_sequenza();
	$indice   = null;
	foreach ( $percorsi as $i => $p ) {
		if ( (int) $p->ID === (int) $pid ) { $indice = $i; break; }
	}
	if ( null === $indice || 0 === $indice ) { return true; } // non trovato o primo della sequenza
	return gs_percorso_completato_da( $percorsi[ $indice - 1 ]->ID, $uid );
}

/**
 * True se la lezione va tenuta bloccata per questa sfoglina: appartiene a
 * uno o più percorsi ed è bloccata in TUTTI (se compare anche in un
 * percorso già sbloccato, o in nessun percorso, resta accessibile).
 */
function gs_lezione_bloccata_per( $lid, $uid ) {
	$appartiene = false;
	foreach ( gs_percorsi_tutti() as $p ) {
		if ( in_array( (int) $lid, gs_percorso_get( $p->ID )['lezioni'], true ) ) {
			$appartiene = true;
			if ( gs_percorso_sbloccato_per( $p->ID, $uid ) ) { return false; }
		}
	}
	return $appartiene;
}

/** Quante lezioni del percorso ha già visto una sfoglina, sul totale. */
function gs_percorso_progresso( $pid, $uid ) {
	$lezioni = gs_percorso_get( $pid )['lezioni'];
	$viste   = 0;
	foreach ( $lezioni as $lid ) {
		if ( gs_lezione_e_vista( $lid, $uid ) ) { $viste++; }
	}
	return array( 'viste' => $viste, 'totale' => count( $lezioni ) );
}

/**
 * Se alla sfoglina manca esattamente una lezione per completare il percorso
 * che sta facendo ora (il primo, in ordine, che ha sbloccato ma non ha
 * ancora finito), restituisce percorso e lezione mancante — utile per il
 * digest settimanale ("sei quasi arrivata"). Altrimenti false.
 */
function gs_percorso_quasi_completo_per( $uid ) {
	foreach ( gs_percorsi_tutti() as $p ) {
		if ( ! gs_percorso_sbloccato_per( $p->ID, $uid ) || gs_percorso_completato_da( $p->ID, $uid ) ) {
			continue;
		}
		$c = gs_percorso_get( $p->ID );
		if ( ! $c['lezioni'] ) {
			continue;
		}
		$prog = gs_percorso_progresso( $p->ID, $uid );
		if ( $prog['totale'] - $prog['viste'] !== 1 ) {
			return false; // è il percorso attuale, ma manca più di una lezione (o zero: già gestito da completato_da)
		}
		foreach ( $c['lezioni'] as $lid ) {
			if ( ! gs_lezione_e_vista( $lid, $uid ) ) {
				return array( 'percorso' => $c['titolo'], 'lezione' => get_the_title( $lid ) );
			}
		}
	}
	return false;
}

/** True se la sfoglina ha visto tutte le lezioni del percorso (percorso non vuoto). */
function gs_percorso_completato_da( $pid, $uid ) {
	$lezioni = gs_percorso_get( $pid )['lezioni'];
	if ( ! $lezioni ) { return false; }
	foreach ( $lezioni as $lid ) {
		if ( ! gs_lezione_e_vista( $lid, $uid ) ) { return false; }
	}
	return true;
}

/**
 * Se il percorso è ora completo per questa sfoglina e non le era ancora
 * stato assegnato, sblocca il badge dedicato (chiave dinamica) + punti +
 * avviso. Stessi effetti di gs_unlock_badge(), scritti a mano perché quella
 * funzione lavora solo su chiavi statiche di gs_get_badges_definitions().
 */
function gs_percorso_assegna_badge_se_completo( $pid, $uid ) {
	if ( ! gs_percorso_completato_da( $pid, $uid ) ) { return false; }

	$badge_key = 'percorso_' . $pid;
	$owned     = gs_get_user_badges( $uid );
	if ( in_array( $badge_key, $owned, true ) ) { return false; } // già assegnato

	// Stessa serratura di gs_percorso_squadra_badge_assegna() più sopra: qui
	// si arriva solo da gs_lezione_vista, già protetta dalla sua (L1), ma
	// la stessa forma va tenuta identica nelle tre funzioni (trovato il
	// 25/08/2026).
	$chiave_fatto = 'gs_badge_dato_' . $badge_key;
	if ( get_user_meta( $uid, $chiave_fatto, true ) ) { return false; }
	update_user_meta( $uid, $chiave_fatto, current_time( 'mysql' ) );

	$percorso = gs_percorso_get( $pid );
	$owned[]  = $badge_key;
	update_user_meta( $uid, 'gs_badges', $owned );
	update_user_meta( $uid, 'gs_badge_label_' . $badge_key, '🗺️ Percorso completato: ' . $percorso['titolo'] );

	$log = get_user_meta( $uid, 'gs_badges_log', true );
	$log = is_array( $log ) ? $log : array();
	$log[ $badge_key ] = current_time( 'timestamp' );
	update_user_meta( $uid, 'gs_badges_log', $log );

	gs_add_points( $uid, gs_get_points_value( 'percorso_completato', 30 ), 'Percorso completato: ' . $percorso['titolo'] );

	if ( function_exists( 'gs_accoda_volo' ) ) {
		gs_accoda_volo( $uid, 'PERCORSO COMPLETATO: ' . mb_strtoupper( $percorso['titolo'] ), gs_pagina_url( 'gs_page_percorso_personale' ) );
	}
	$u = get_userdata( $uid );
	if ( $u ) {
		$oggetto_pc = 'Percorso completato! — Accademia della Sfoglia';
		$corpo_pc   = "Complimenti " . $u->display_name . "!\n\nHai visto tutte le lezioni del percorso \"" . $percorso['titolo'] . "\": hai sbloccato un badge dedicato e qualche punto in più.\n\nAccademia della Sfoglia";
		if ( function_exists( 'gs_mail_progetto' ) ) {
			gs_mail_progetto( $uid, 'livelli', $oggetto_pc, $corpo_pc );
		} elseif ( $u->user_email ) {
			wp_mail( $u->user_email, $oggetto_pc, $corpo_pc );
		}
	}
	return true;
}

/** A ogni lezione vista, verifica se qualche percorso si è appena completato per quella sfoglina (o per la sua squadra). */
add_action( 'gs_lezione_vista', 'gs_percorsi_controlla_completamento', 10, 1 );
function gs_percorsi_controlla_completamento( $uid ) {
	foreach ( gs_percorsi_tutti() as $p ) {
		if ( gs_percorso_e_squadra( $p->ID ) ) {
			gs_percorso_squadra_assegna_se_completo( $p->ID, $uid );
		} else {
			gs_percorso_assegna_badge_se_completo( $p->ID, $uid );
		}
	}
	gs_diploma_finale_assegna_se_completo( $uid );
}

/**
 * True se la sfoglina ha completato TUTTI i Percorsi Guidati pubblicati
 * (quelli con almeno una lezione — i percorsi vuoti non contano). I
 * percorsi stagionali non contano: sono contenuti extra a tempo, non fanno
 * parte del percorso di formazione principale, e non devono bloccare per
 * sempre il Diploma Finale una volta chiusa la loro finestra. Nemmeno i
 * Percorsi di Squadra contano: dipendono dall'aver scelto una squadra, non
 * devono essere obbligatori per chi non ne fa parte. Se non esiste ancora
 * nessun percorso con lezioni, non c'è nulla da completare.
 */
function gs_tutti_percorsi_completati_da( $uid ) {
	$percorsi_reali = array_filter( gs_percorsi_tutti(), function ( $p ) {
		return ! gs_percorso_e_stagionale( $p->ID ) && ! gs_percorso_e_squadra( $p->ID ) && ! empty( gs_percorso_get( $p->ID )['lezioni'] );
	} );
	if ( ! $percorsi_reali ) { return false; }
	foreach ( $percorsi_reali as $p ) {
		if ( ! gs_percorso_completato_da( $p->ID, $uid ) ) { return false; }
	}
	return true;
}

/**
 * Se la sfoglina ha appena completato l'ULTIMO percorso rimasto (quindi ora
 * li ha completati tutti) e non le era ancora stato assegnato, sblocca il
 * diploma finale: badge dedicato + punti extra + avviso + email. Stesso
 * schema di gs_percorso_assegna_badge_se_completo(), ma per l'insieme.
 */
function gs_diploma_finale_assegna_se_completo( $uid ) {
	if ( ! gs_tutti_percorsi_completati_da( $uid ) ) { return false; }

	$badge_key = 'percorsi_tutti_completi';
	$owned     = gs_get_user_badges( $uid );
	if ( in_array( $badge_key, $owned, true ) ) { return false; } // già assegnato

	// Stessa serratura delle altre due funzioni di badge di percorso più
	// sopra: qui si arriva solo da gs_lezione_vista, già protetta da L1, ma
	// la stessa forma va tenuta identica nelle tre funzioni (trovato il
	// 25/08/2026).
	$chiave_fatto = 'gs_badge_dato_' . $badge_key;
	if ( get_user_meta( $uid, $chiave_fatto, true ) ) { return false; }
	update_user_meta( $uid, $chiave_fatto, current_time( 'mysql' ) );

	$owned[] = $badge_key;
	update_user_meta( $uid, 'gs_badges', $owned );
	update_user_meta( $uid, 'gs_badge_label_' . $badge_key, '🎓 Diploma Finale: Percorso di Formazione Completo' );

	$log = get_user_meta( $uid, 'gs_badges_log', true );
	$log = is_array( $log ) ? $log : array();
	$log[ $badge_key ] = current_time( 'timestamp' );
	update_user_meta( $uid, 'gs_badges_log', $log );

	gs_add_points( $uid, gs_get_points_value( 'tutti_percorsi_completati', 100 ), 'Tutti i Percorsi Guidati completati' );

	if ( function_exists( 'gs_accoda_volo' ) ) {
		gs_accoda_volo( $uid, 'HAI COMPLETATO TUTTI I PERCORSI GUIDATI! 🎓 DIPLOMA FINALE SBLOCCATO', gs_pagina_url( 'gs_page_percorso_personale' ) );
	}
	$u = get_userdata( $uid );
	if ( $u ) {
		$oggetto_df = 'Diploma Finale sbloccato! — Accademia della Sfoglia';
		$corpo_df   = "Complimenti " . $u->display_name . "!\n\nHai completato TUTTI i Percorsi Guidati dell'Accademia: hai sbloccato il Diploma Finale, con un bel bonus di punti.\n\nAccademia della Sfoglia";
		if ( function_exists( 'gs_mail_progetto' ) ) {
			gs_mail_progetto( $uid, 'livelli', $oggetto_df, $corpo_df );
		} elseif ( $u->user_email ) {
			wp_mail( $u->user_email, $oggetto_df, $corpo_df );
		}
	}
	return true;
}

// -----------------------------------------------------------------------------
// Certificato stampabile di un percorso completato — stesso stile del
// diploma dell'Area Professionale (gs_certificato_css() in helpers.php).
// -----------------------------------------------------------------------------

/** URL firmato per aprire/stampare il certificato di una sfoglina per un percorso. */
function gs_certificato_percorso_url( $pid, $uid ) {
	return wp_nonce_url(
		add_query_arg( array( 'gs_certificato_percorso' => (int) $pid, 'gs_sfoglina' => (int) $uid ), admin_url( 'admin.php' ) ),
		'gs_certificato_percorso_' . $pid . '_' . $uid
	);
}

/** Il contenuto HTML del certificato (pagina intera): separato per poterlo testare/mostrare in anteprima. */
function gs_certificato_percorso_html( $pid, $uid ) {
	$percorso = gs_percorso_get( $pid );
	$u        = get_userdata( $uid );
	$nome     = $u ? $u->display_name : '';

	$badge_key = 'percorso_' . $pid;
	$log       = get_user_meta( $uid, 'gs_badges_log', true );
	$ts        = ( is_array( $log ) && isset( $log[ $badge_key ] ) ) ? (int) $log[ $badge_key ] : current_time( 'timestamp' );
	$data_lbl  = date_i18n( 'j F Y', $ts );

	$sigillo    = gs_certificato_logo_url( 'assets/img/diploma-rina.png' );
	$ha_sigillo = gs_certificato_logo_esiste( 'assets/img/diploma-rina.png' );

	ob_start();
	?><!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="utf-8">
	<title>Certificato di Percorso Guidato — <?php echo esc_html( $nome ); ?></title>
	<?php echo gs_certificato_css(); ?>
</head>
<body onload="window.print()">
	<div class="page">
		<div class="diploma-wrap">
			<div class="diploma">
				<span class="corner tl"></span><span class="corner tr"></span>
				<span class="corner bl"></span><span class="corner br"></span>

				<p class="eyebrow-top">Accademia della Sfoglia</p>

				<?php if ( $ha_sigillo ) : ?>
					<div class="sigillo-wrap">
						<img class="sigillo" src="<?php echo esc_url( $sigillo ); ?>" alt="Marchio Accademia della Sfoglia">
					</div>
				<?php endif; ?>

				<div class="chip">Certificato di Percorso Guidato</div>
				<p class="sottotitolo"><?php echo esc_html( $percorso['titolo'] ); ?></p>

				<div class="divider">
					<span class="line"></span>
					<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
						<rect x="8" y="21" width="32" height="6" rx="3" fill="currentColor"/>
						<circle cx="6" cy="24" r="5" fill="currentColor"/>
						<circle cx="42" cy="24" r="5" fill="currentColor"/>
					</svg>
					<span class="line"></span>
				</div>

				<div class="nome"><?php echo esc_html( $nome ); ?></div>

				<p class="testo">ha completato il percorso guidato «<?php echo esc_html( $percorso['titolo'] ); ?>», guardando con profitto tutte le lezioni previste, nell'ordine, presso l'Accademia della Sfoglia.</p>

				<div class="firma-riga">
					<div class="firma">
						<div class="nome-firma">Accademia della Sfoglia</div>
						<div class="etichetta">Percorso Guidato</div>
					</div>
					<div class="data-riga">
						<div class="valore"><?php echo esc_html( $data_lbl ); ?></div>
						<div class="etichetta">Data</div>
					</div>
				</div>
			</div>
		</div>

		<p class="noprint"><button onclick="window.print()">🖨️ Stampa / Salva come PDF</button></p>
	</div>
</body>
</html><?php
	return ob_get_clean();
}

/** Intercetta la richiesta di stampa del certificato (?gs_certificato_percorso=ID&gs_sfoglina=ID). */
add_action( 'admin_init', 'gs_handle_certificato_percorso_stampa' );
function gs_handle_certificato_percorso_stampa() {
	if ( empty( $_GET['gs_certificato_percorso'] ) ) {
		return;
	}
	$pid = (int) $_GET['gs_certificato_percorso'];
	if ( 'gs_percorso_lezioni' !== get_post_type( $pid ) ) {
		wp_die( 'Percorso non trovato.' );
	}
	$uid = isset( $_GET['gs_sfoglina'] ) ? (int) $_GET['gs_sfoglina'] : get_current_user_id();
	check_admin_referer( 'gs_certificato_percorso_' . $pid . '_' . $uid );

	$puo_vedere = ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) || ( get_current_user_id() === $uid );
	if ( ! $puo_vedere ) {
		wp_die( 'Non hai il permesso di vedere questo certificato.' );
	}
	if ( ! gs_percorso_completato_effettivo_da( $pid, $uid ) ) {
		wp_die( 'Questo percorso non risulta ancora completato da questa sfoglina.' );
	}

	header( 'Content-Type: text/html; charset=utf-8' );
	echo gs_certificato_percorso_html( $pid, $uid );
	exit;
}

// -----------------------------------------------------------------------------
// Diploma Finale — per aver completato TUTTI i Percorsi Guidati, non solo
// uno. Stesso stile e stesso schema del certificato di singolo percorso.
// -----------------------------------------------------------------------------

/** URL firmato per aprire/stampare il Diploma Finale di una sfoglina. */
function gs_certificato_diploma_finale_url( $uid ) {
	return wp_nonce_url(
		add_query_arg( array( 'gs_diploma_finale' => 1, 'gs_sfoglina' => (int) $uid ), admin_url( 'admin.php' ) ),
		'gs_diploma_finale_' . $uid
	);
}

/** Il contenuto HTML del Diploma Finale (pagina intera). */
function gs_certificato_diploma_finale_html( $uid ) {
	$u    = get_userdata( $uid );
	$nome = $u ? $u->display_name : '';

	$badge_key = 'percorsi_tutti_completi';
	$log       = get_user_meta( $uid, 'gs_badges_log', true );
	$ts        = ( is_array( $log ) && isset( $log[ $badge_key ] ) ) ? (int) $log[ $badge_key ] : current_time( 'timestamp' );
	$data_lbl  = date_i18n( 'j F Y', $ts );

	$n_percorsi = count( array_filter( gs_percorsi_tutti(), function ( $p ) {
		return ! empty( gs_percorso_get( $p->ID )['lezioni'] );
	} ) );

	$sigillo    = gs_certificato_logo_url( 'assets/img/diploma-rina.png' );
	$ha_sigillo = gs_certificato_logo_esiste( 'assets/img/diploma-rina.png' );

	ob_start();
	?><!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="utf-8">
	<title>Diploma Finale — <?php echo esc_html( $nome ); ?></title>
	<?php echo gs_certificato_css(); ?>
</head>
<body onload="window.print()">
	<div class="page">
		<div class="diploma-wrap">
			<div class="diploma">
				<span class="corner tl"></span><span class="corner tr"></span>
				<span class="corner bl"></span><span class="corner br"></span>

				<p class="eyebrow-top">Accademia della Sfoglia</p>

				<?php if ( $ha_sigillo ) : ?>
					<div class="sigillo-wrap">
						<img class="sigillo" src="<?php echo esc_url( $sigillo ); ?>" alt="Marchio Accademia della Sfoglia">
					</div>
				<?php endif; ?>

				<div class="chip">🎓 Diploma Finale</div>
				<p class="sottotitolo">Percorso di Formazione Completo</p>

				<div class="divider">
					<span class="line"></span>
					<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
						<rect x="8" y="21" width="32" height="6" rx="3" fill="currentColor"/>
						<circle cx="6" cy="24" r="5" fill="currentColor"/>
						<circle cx="42" cy="24" r="5" fill="currentColor"/>
					</svg>
					<span class="line"></span>
				</div>

				<div class="nome"><?php echo esc_html( $nome ); ?></div>

				<p class="testo">ha completato con profitto tutti i <?php echo (int) $n_percorsi; ?> Percorsi Guidati dell'Accademia della Sfoglia, dal primo all'ultimo, nell'ordine previsto.</p>

				<div class="firma-riga">
					<div class="firma">
						<div class="nome-firma">Accademia della Sfoglia</div>
						<div class="etichetta">Diploma Finale</div>
					</div>
					<div class="data-riga">
						<div class="valore"><?php echo esc_html( $data_lbl ); ?></div>
						<div class="etichetta">Data</div>
					</div>
				</div>
			</div>
		</div>

		<p class="noprint"><button onclick="window.print()">🖨️ Stampa / Salva come PDF</button></p>
	</div>
</body>
</html><?php
	return ob_get_clean();
}

/** Intercetta la richiesta di stampa del Diploma Finale (?gs_diploma_finale=1&gs_sfoglina=ID). */
add_action( 'admin_init', 'gs_handle_diploma_finale_stampa' );
function gs_handle_diploma_finale_stampa() {
	if ( empty( $_GET['gs_diploma_finale'] ) ) {
		return;
	}
	$uid = isset( $_GET['gs_sfoglina'] ) ? (int) $_GET['gs_sfoglina'] : get_current_user_id();
	check_admin_referer( 'gs_diploma_finale_' . $uid );

	$puo_vedere = ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) || ( get_current_user_id() === $uid );
	if ( ! $puo_vedere ) {
		wp_die( 'Non hai il permesso di vedere questo certificato.' );
	}
	if ( ! gs_tutti_percorsi_completati_da( $uid ) ) {
		wp_die( 'Il Diploma Finale non risulta ancora sbloccato per questa sfoglina: mancano ancora dei percorsi da completare.' );
	}

	header( 'Content-Type: text/html; charset=utf-8' );
	echo gs_certificato_diploma_finale_html( $uid );
	exit;
}

// -----------------------------------------------------------------------------
// Vista sfoglina: elenco dei percorsi, con progresso e stato "completato".
// Richiamata da gs_sc_lezioni() in lezioni-video.php.
// -----------------------------------------------------------------------------
function gs_percorsi_lezioni_html( $uid ) {
	$percorsi     = gs_percorsi_sequenza();
	$stagionali   = gs_percorsi_stagionali_attivi();
	$c_e_squadra  = ! empty( array_filter( gs_percorsi_tutti(), function ( $p ) {
		return gs_percorso_e_squadra( $p->ID ) && ! empty( gs_percorso_get( $p->ID )['lezioni'] );
	} ) );
	if ( ! $percorsi && ! $stagionali && ! $c_e_squadra ) { return ''; }

	$out = '<div class="gs-todo-riquadro">';
	$out .= '<p class="gs-hint" style="font-size:16px"><strong>🗺️ Percorsi Guidati</strong><br>Un percorso alla volta, in ordine: completa quello attuale per sbloccare il successivo.</p>';

	if ( $uid && function_exists( 'gs_tutti_percorsi_completati_da' ) && gs_tutti_percorsi_completati_da( $uid ) ) {
		$out .= '<p class="gs-hint"><strong>🎓 Diploma Finale sbloccato!</strong><br>Hai completato tutti i Percorsi Guidati dell\'Accademia.</p>';
		if ( function_exists( 'gs_certificato_diploma_finale_url' ) ) {
			$out .= '<p><a class="gs-btn gs-btn-sm gs-btn-verde" href="' . esc_url( gs_certificato_diploma_finale_url( $uid ) ) . '" target="_blank">🖨️ Vedi / stampa il Diploma Finale</a></p>';
		}
	}

	$precedente_titolo = null;
	foreach ( $percorsi as $p ) {
		$c = gs_percorso_get( $p->ID );
		if ( ! $c['lezioni'] ) { continue; }
		$sbloccato = ! $uid || gs_percorso_sbloccato_per( $p->ID, $uid );

		if ( ! $sbloccato ) {
			$out .= '<p class="gs-hint"><strong>🔒 ' . esc_html( $c['titolo'] ) . '</strong><br>Si sblocca completando «' . esc_html( $precedente_titolo ) . '».</p>';
			$precedente_titolo = $c['titolo'];
			continue;
		}

		$prog     = $uid ? gs_percorso_progresso( $p->ID, $uid ) : array( 'viste' => 0, 'totale' => count( $c['lezioni'] ) );
		$completo = $uid && gs_percorso_completato_da( $p->ID, $uid );
		$out .= '<p class="gs-hint"><strong>' . ( $completo ? '🏆 ' : '' ) . esc_html( $c['titolo'] ) . '</strong>' . ( $c['livello'] ? ' <span class="gs-msg-tag">' . esc_html( $c['livello'] ) . '</span>' : '' ) . ' — ' . (int) $prog['viste'] . ' di ' . (int) $prog['totale'] . ' lezioni viste' . ( $completo ? ' — completato!' : '' );
		if ( $c['descrizione'] ) { $out .= '<br>' . esc_html( $c['descrizione'] ); }
		$out .= '<br>';
		$pezzi = array();
		foreach ( $c['lezioni'] as $lid ) {
			if ( 'gs_lezione' !== get_post_type( $lid ) ) { continue; }
			$vista   = $uid && gs_lezione_e_vista( $lid, $uid );
			$pezzi[] = ( $vista ? '✅ ' : '⏳ ' ) . esc_html( get_the_title( $lid ) );
		}
		$out .= implode( '<br>', $pezzi );
		$out .= '</p>';
		if ( $completo && function_exists( 'gs_certificato_percorso_url' ) ) {
			$out .= '<p><a class="gs-btn gs-btn-sm" href="' . esc_url( gs_certificato_percorso_url( $p->ID, $uid ) ) . '" target="_blank">🖨️ Vedi / stampa il certificato</a></p>';
		}
		if ( $uid && function_exists( 'gs_staffetta_html' ) ) { $out .= gs_staffetta_html( $p->ID, $uid, $completo ); }
		$precedente_titolo = $c['titolo'];
	}

	if ( $stagionali ) {
		$out .= '<p class="gs-hint" style="font-size:16px;margin-top:14px"><strong>🎄 Percorsi Stagionali</strong><br>Contenuti speciali disponibili solo per un periodo limitato, aperti a tutti da subito, senza dover finire prima gli altri.</p>';
		foreach ( $stagionali as $p ) {
			$c = gs_percorso_get( $p->ID );
			if ( ! $c['lezioni'] ) { continue; }
			$prog     = $uid ? gs_percorso_progresso( $p->ID, $uid ) : array( 'viste' => 0, 'totale' => count( $c['lezioni'] ) );
			$completo = $uid && gs_percorso_completato_da( $p->ID, $uid );
			$out .= '<p class="gs-hint"><strong>' . ( $completo ? '🏆 ' : '🎄 ' ) . esc_html( $c['titolo'] ) . '</strong>' . ( $c['livello'] ? ' <span class="gs-msg-tag">' . esc_html( $c['livello'] ) . '</span>' : '' ) . ' — ' . (int) $prog['viste'] . ' di ' . (int) $prog['totale'] . ' lezioni viste' . ( $completo ? ' — completato!' : '' );
			if ( $c['data_fine'] ) { $out .= '<br>Disponibile fino al ' . esc_html( date_i18n( 'j F Y', strtotime( $c['data_fine'] . ' 00:00:00' ) ) ) . '.'; }
			if ( $c['descrizione'] ) { $out .= '<br>' . esc_html( $c['descrizione'] ); }
			$out .= '<br>';
			$pezzi = array();
			foreach ( $c['lezioni'] as $lid ) {
				if ( 'gs_lezione' !== get_post_type( $lid ) ) { continue; }
				$vista   = $uid && gs_lezione_e_vista( $lid, $uid );
				$pezzi[] = ( $vista ? '✅ ' : '⏳ ' ) . esc_html( get_the_title( $lid ) );
			}
			$out .= implode( '<br>', $pezzi );
			$out .= '</p>';
			if ( $completo && function_exists( 'gs_certificato_percorso_url' ) ) {
				$out .= '<p><a class="gs-btn gs-btn-sm" href="' . esc_url( gs_certificato_percorso_url( $p->ID, $uid ) ) . '" target="_blank">🖨️ Vedi / stampa il certificato</a></p>';
			}
		}
	}

	$team = $uid ? gs_get_user_team( $uid ) : '';
	if ( $team ) {
		$squadre = array_values( array_filter( gs_percorsi_tutti(), function ( $p ) {
			return gs_percorso_e_squadra( $p->ID ) && ! empty( gs_percorso_get( $p->ID )['lezioni'] );
		} ) );
		if ( $squadre ) {
			$out .= '<p class="gs-hint" style="font-size:16px;margin-top:14px"><strong>🎽 Percorsi di Squadra</strong><br>Avanzamento di tutta la squadra "' . esc_html( $team ) . '" insieme: basta che qualcuno del gruppo veda una lezione perché conti per tutti.</p>';
			foreach ( $squadre as $p ) {
				$c        = gs_percorso_get( $p->ID );
				$prog     = gs_percorso_squadra_progresso( $p->ID, $team );
				$completo = gs_percorso_completato_effettivo_da( $p->ID, $uid );
				$out .= '<p class="gs-hint"><strong>' . ( $completo ? '🏆 ' : '🎽 ' ) . esc_html( $c['titolo'] ) . '</strong>' . ( $c['livello'] ? ' <span class="gs-msg-tag">' . esc_html( $c['livello'] ) . '</span>' : '' ) . ' — ' . (int) $prog['viste'] . ' di ' . (int) $prog['totale'] . ' lezioni viste dalla squadra' . ( $completo ? ' — completato!' : '' );
				if ( $c['descrizione'] ) { $out .= '<br>' . esc_html( $c['descrizione'] ); }
				$out .= '<br>';
				$pezzi = array();
				foreach ( $c['lezioni'] as $lid ) {
					if ( 'gs_lezione' !== get_post_type( $lid ) ) { continue; }
					$vista_squadra = gs_lezione_e_vista_da_qualcuno( $lid, gs_squadra_membri( $team ) );
					$pezzi[] = ( $vista_squadra ? '✅ ' : '⏳ ' ) . esc_html( get_the_title( $lid ) );
				}
				$out .= implode( '<br>', $pezzi );
				$out .= '</p>';
				if ( $completo && function_exists( 'gs_certificato_percorso_url' ) ) {
					$out .= '<p><a class="gs-btn gs-btn-sm" href="' . esc_url( gs_certificato_percorso_url( $p->ID, $uid ) ) . '" target="_blank">🖨️ Vedi / stampa il certificato</a></p>';
				}
			}
		}
	}

	$out .= '</div>';
	return $out;
}

/** True se almeno uno degli ID passati (es. i membri di una squadra) ha visto questa lezione. */
function gs_lezione_e_vista_da_qualcuno( $lid, $uids ) {
	foreach ( $uids as $mid ) {
		if ( gs_lezione_e_vista( $lid, $mid ) ) { return true; }
	}
	return false;
}

// -----------------------------------------------------------------------------
// PANNELLO — gestione percorsi (titolare)
// -----------------------------------------------------------------------------
function gs_pannello_percorsi_lezioni() {
	if ( ! gs_can_manage() ) { return; }

	echo gs_box_open( '🗺️ Percorsi Guidati', '', 'gs-box-percorsi' );
	echo '<p class="gs-hint">Raggruppa più lezioni in un percorso in sequenza (es. "Dalla sfoglia ai tortellini"). I percorsi si sbloccano uno alla volta, nell\'ordine qui sotto: una sfoglina deve completare un percorso prima che il successivo diventi accessibile. Usa ▲▼ per cambiare l\'ordine. Chi completa un percorso sblocca un badge dedicato e qualche punto in più. Se dai a un percorso una data di inizio e/o fine, diventa "Stagionale": esce dalla sequenza (non blocca né viene bloccato dagli altri), è aperto a tutti da subito ma solo dentro quella finestra, poi torna nascosto — utile per contenuti a tempo (Natale, una stagione dell\'anno…). Se lo spunti come "Percorso di Squadra", l\'avanzamento diventa collettivo: basta che una sfoglina qualsiasi della squadra veda una lezione perché conti per tutta la squadra, e quando lo finiscono tutti insieme il badge e i punti arrivano a ogni membro. Anche questo esce dalla sequenza normale. Se lo spunti come "Percorso a Staffetta" (solo se non è di squadra), il completamento diventa un testimone che passa di mano: la prima sfoglina che completa il percorso lo raccoglie e sceglie personalmente a quale altra sfoglina darlo; solo chi ha in mano il testimone (e ha completato il percorso) può passarlo oltre.</p>';

	$lezioni_tutte = function_exists( 'gs_lezioni_tutte' ) ? gs_lezioni_tutte() : array();
	$livelli       = gs_percorso_livelli();

	// --- Livelli dei percorsi: crea, rinomina, elimina, riordina ---
	echo '<div style="background:var(--gs-uovo);padding:12px;border-radius:6px;margin-bottom:14px">';
	echo '<strong>Livelli dei percorsi</strong>';
	echo '<p class="gs-hint">Etichette facoltative (es. Base, Intermedio, Avanzato) da assegnare a ogni percorso, solo per indicare alla sfoglina quanto è impegnativo — non influenzano lo sblocco in sequenza, che resta deciso dall\'ordine qui sotto. Rinominare un livello aggiorna anche i percorsi che lo usano già; eliminarlo li lascia semplicemente senza livello, non li cancella.</p>';
	if ( $livelli ) {
		echo '<ul class="gs-todo-list gs-livelli-list">';
		foreach ( $livelli as $i => $l ) {
			echo '<li class="gs-todo-item" data-indice="' . (int) $i . '">';
			echo '<span class="gs-livello-nome">' . esc_html( $l ) . '</span> ';
			echo '<button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-livello-rinomina-apri" data-indice="' . (int) $i . '" data-nome="' . esc_attr( $l ) . '">✏️ Rinomina</button> ';
			if ( $i > 0 ) { echo '<button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-livello-sposta" data-indice="' . (int) $i . '" data-direzione="su">▲</button> '; }
			if ( $i < count( $livelli ) - 1 ) { echo '<button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-livello-sposta" data-indice="' . (int) $i . '" data-direzione="giu">▼</button> '; }
			echo '<button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-livello-elimina" data-indice="' . (int) $i . '">Elimina</button>';
			echo '</li>';
		}
		echo '</ul>';
	} else {
		echo '<p class="gs-hint">Nessun livello creato: i percorsi restano senza etichetta finché non ne crei uno.</p>';
	}
	echo '<form class="gs-form gs-form-livello-rinomina" onsubmit="return false" style="display:none;margin-top:8px">';
	echo '<input type="hidden" name="indice">';
	echo '<p><label>Nuovo nome<br><input type="text" name="nome" style="width:100%" required></label></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-livello-rinomina-salva">Salva</button> <button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-livello-rinomina-annulla">Annulla</button> <span class="gs-livello-rinomina-msg gs-richiesta-esito"></span></p>';
	echo '</form>';
	echo '<form class="gs-form gs-form-livello-nuovo" onsubmit="return false" style="margin-top:8px">';
	echo '<p><label>Nuovo livello<br><input type="text" name="nome" autocomplete="off" style="width:100%" placeholder="es. Avanzato"></label></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-livello-crea">Crea livello</button> <span class="gs-livello-msg gs-richiesta-esito"></span></p>';
	echo '</form>';
	echo '</div>';

	echo '<form class="gs-form gs-form-percorso" onsubmit="return false" style="background:var(--gs-uovo);padding:12px;border-radius:6px;margin-bottom:14px">';
	echo '<strong>Nuovo percorso</strong>';
	echo '<p><label>Nome del percorso<br><input type="text" name="titolo" autocomplete="off" style="width:100%" required></label></p>';
	echo '<p><label>Descrizione (facoltativa)<br><textarea name="descrizione" rows="2" style="width:100%"></textarea></label></p>';
	echo '<p><label>Livello (facoltativo)<br><select name="livello"><option value="">— Nessun livello —</option>';
	foreach ( $livelli as $l ) { echo '<option value="' . esc_attr( $l ) . '">' . esc_html( $l ) . '</option>'; }
	echo '</select></label></p>';
	echo '<p><strong>Percorso Stagionale (facoltativo)</strong> <span class="gs-hint">— imposta una data per farlo comparire solo in quel periodo (es. "Percorso di Natale"): niente propedeuticità, è aperto a tutti da subito e sparisce da solo a fine finestra. Lascia entrambe vuote per un percorso normale, sempre disponibile.</span></p>';
	echo '<p style="display:flex;gap:12px;flex-wrap:wrap"><label>Dal<br><input type="date" name="data_inizio"></label><label>Al<br><input type="date" name="data_fine"></label></p>';
	echo '<p><label><input type="checkbox" name="squadra" value="1"> <strong>Percorso di Squadra</strong></label> <span class="gs-hint">— l\'avanzamento è della squadra intera, non del singolo: basta che una qualsiasi sfoglina della squadra veda una lezione perché conti per tutti. Niente propedeuticità, sempre aperto.</span></p>';
	echo '<p><label><input type="checkbox" name="staffetta" value="1"> <strong>Percorso a Staffetta</strong></label> <span class="gs-hint">— solo per percorsi individuali (non di squadra): chi lo completa può scegliere personalmente a quale altra sfoglina passare il testimone. Il testimone parte libero: la prima che completa il percorso lo raccoglie e decide a chi darlo.</span></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-percorso-crea">Crea percorso</button> <span class="gs-percorso-msg gs-richiesta-esito"></span></p>';
	echo '</form>';

	$percorsi = gs_percorsi_tutti();
	echo '<h4>Percorsi creati (' . count( $percorsi ) . ')</h4>';
	if ( ! $percorsi ) {
		echo '<p class="gs-hint">Nessun percorso ancora creato.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="8">';
		foreach ( $percorsi as $i => $p ) {
			$c = gs_percorso_get( $p->ID );
			$tag_stagione = '';
			if ( gs_percorso_e_stagionale( $p->ID ) ) {
				$tag_stagione = gs_percorso_in_stagione( $p->ID ) ? ' <span class="gs-msg-tag">🎄 stagionale — attivo ora</span>' : ' <span class="gs-msg-tag">🎄 stagionale — non attivo</span>';
			}
			$tag_squadra   = $c['squadra'] ? ' <span class="gs-msg-tag">🎽 di squadra</span>' : '';
			$tag_staffetta = $c['staffetta'] ? ' <span class="gs-msg-tag">🏃 a staffetta</span>' : '';
			echo '<details class="gs-inbox-item" data-percorso="' . (int) $p->ID . '">';
			echo '<summary class="gs-inbox-oggetto">' . ( $i + 1 ) . 'º — ' . esc_html( $c['titolo'] ) . ( $c['livello'] ? ' <span class="gs-msg-tag">' . esc_html( $c['livello'] ) . '</span>' : '' ) . $tag_stagione . $tag_squadra . $tag_staffetta . ' <span class="gs-msg-data">' . count( $c['lezioni'] ) . ' lezioni</span></summary>';
			echo '<div class="gs-inbox-corpo">';

			echo '<form class="gs-form gs-form-percorso-modifica" data-percorso="' . (int) $p->ID . '" onsubmit="return false">';
			echo '<p><label>Nome del percorso<br><input type="text" name="titolo" value="' . esc_attr( $c['titolo'] ) . '" style="width:100%" required></label></p>';
			echo '<p><label>Descrizione<br><textarea name="descrizione" rows="2" style="width:100%">' . esc_textarea( $c['descrizione'] ) . '</textarea></label></p>';
			echo '<p><label>Livello<br><select name="livello"><option value="">— Nessun livello —</option>';
			foreach ( $livelli as $l ) { echo '<option value="' . esc_attr( $l ) . '" ' . selected( $c['livello'], $l, false ) . '>' . esc_html( $l ) . '</option>'; }
			echo '</select></label></p>';
			echo '<p><strong>Percorso Stagionale</strong> <span class="gs-hint">— entrambe vuote = percorso normale, sempre disponibile.</span></p>';
			echo '<p style="display:flex;gap:12px;flex-wrap:wrap"><label>Dal<br><input type="date" name="data_inizio" value="' . esc_attr( $c['data_inizio'] ) . '"></label><label>Al<br><input type="date" name="data_fine" value="' . esc_attr( $c['data_fine'] ) . '"></label></p>';
			echo '<p><label><input type="checkbox" name="squadra" value="1" ' . checked( $c['squadra'], true, false ) . '> <strong>Percorso di Squadra</strong></label> <span class="gs-hint">— avanzamento collettivo, non individuale.</span></p>';
			echo '<p><label><input type="checkbox" name="staffetta" value="1" ' . checked( $c['staffetta'], true, false ) . '> <strong>Percorso a Staffetta</strong></label> <span class="gs-hint">— solo se non è di squadra: chi lo completa passa personalmente il testimone a un\'altra sfoglina.</span></p>';
			echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-percorso-modifica-salva">✏️ Salva modifiche</button> <span class="gs-percorso-modifica-msg gs-richiesta-esito"></span></p>';
			echo '</form>';

			echo '<p>';
			if ( $i > 0 ) { echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-percorso-sposta" data-percorso="' . (int) $p->ID . '" data-direzione="su">▲ Sposta su</button> '; }
			if ( $i < count( $percorsi ) - 1 ) { echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-percorso-sposta" data-percorso="' . (int) $p->ID . '" data-direzione="giu">▼ Sposta giù</button>'; }
			echo '</p>';

			echo '<strong>Lezioni nel percorso, in ordine:</strong>';
			echo '<ol class="gs-todo-list gs-percorso-lezioni-list" data-percorso="' . (int) $p->ID . '">';
			foreach ( $c['lezioni'] as $lid ) {
				if ( 'gs_lezione' !== get_post_type( $lid ) ) { continue; }
				echo '<li class="gs-todo-item" data-lezione="' . (int) $lid . '"><span>' . esc_html( get_the_title( $lid ) ) . '</span>'
					. '<button class="gs-todo-del gs-percorso-lezione-rimuovi" data-percorso="' . (int) $p->ID . '" data-lezione="' . (int) $lid . '" title="Togli dal percorso">✕</button></li>';
			}
			echo '</ol>';
			if ( ! $c['lezioni'] ) { echo '<p class="gs-hint gs-percorso-vuoto">Ancora nessuna lezione in questo percorso.</p>'; }

			echo '<div style="display:flex;gap:8px;margin-top:6px">';
			echo '<select class="gs-percorso-lezione-select" style="flex:1"><option value="">Aggiungi una lezione al percorso…</option>';
			foreach ( $lezioni_tutte as $l ) {
				if ( in_array( (int) $l->ID, $c['lezioni'], true ) ) { continue; }
				echo '<option value="' . (int) $l->ID . '">' . esc_html( get_the_title( $l ) ) . '</option>';
			}
			echo '</select>';
			echo '<button class="gs-btn gs-btn-sm gs-percorso-lezione-add" data-percorso="' . (int) $p->ID . '">Aggiungi</button>';
			echo '</div><span class="gs-percorso-lezione-msg gs-richiesta-esito"></span>';

			echo '<p style="margin-top:10px"><button class="gs-btn gs-btn-sm gs-btn-ghost gs-percorso-elimina" data-percorso="' . (int) $p->ID . '">Elimina percorso</button> <span class="gs-percorso-row-msg gs-richiesta-esito"></span></p>';
			echo '</div></details>';
		}
		echo '</div>';
	}

	echo gs_box_close();
}

// -----------------------------------------------------------------------------
// AJAX
// -----------------------------------------------------------------------------

add_action( 'wp_ajax_gs_percorso_crea', 'gs_ajax_percorso_crea' );
function gs_ajax_percorso_crea() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	if ( '' === trim( $titolo ) ) { wp_send_json_error( array( 'message' => 'Scrivi il nome del percorso.' ) ); }
	$descrizione = isset( $_POST['descrizione'] ) ? sanitize_textarea_field( wp_unslash( $_POST['descrizione'] ) ) : '';
	$livello     = isset( $_POST['livello'] ) ? sanitize_text_field( wp_unslash( $_POST['livello'] ) ) : '';
	$data_inizio = gs_percorso_data_valida( $_POST['data_inizio'] ?? '' );
	$data_fine   = gs_percorso_data_valida( $_POST['data_fine'] ?? '' );
	$squadra     = ! empty( $_POST['squadra'] );
	$staffetta   = ! empty( $_POST['staffetta'] ) && ! $squadra; // la staffetta è solo per percorsi individuali

	$pid = wp_insert_post( array(
		'post_type'    => 'gs_percorso_lezioni',
		'post_status'  => 'publish',
		'post_title'   => $titolo,
		'post_content' => $descrizione,
	) );
	if ( is_wp_error( $pid ) || ! $pid ) { wp_send_json_error( array( 'message' => 'Errore nella creazione.' ) ); }

	// In coda alla sequenza: l'ultimo della fila, si sblocca dopo tutti gli altri.
	update_post_meta( $pid, 'gs_percorso_ordine', count( gs_percorsi_tutti() ) );
	if ( $livello && in_array( $livello, gs_percorso_livelli(), true ) ) {
		update_post_meta( $pid, 'gs_percorso_livello', $livello );
	}
	if ( $data_inizio ) { update_post_meta( $pid, 'gs_percorso_data_inizio', $data_inizio ); }
	if ( $data_fine ) { update_post_meta( $pid, 'gs_percorso_data_fine', $data_fine ); }
	if ( $squadra ) { update_post_meta( $pid, 'gs_percorso_squadra', 1 ); }
	if ( $staffetta ) { update_post_meta( $pid, 'gs_staffetta_attiva', 1 ); }

	wp_send_json_success( array( 'message' => 'Percorso creato.' ) );
}

/** Ripulisce e valida una data del form (Y-m-d), o stringa vuota se non valida/assente. */
function gs_percorso_data_valida( $data ) {
	$data = sanitize_text_field( wp_unslash( (string) $data ) );
	return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data ) ? $data : '';
}

add_action( 'wp_ajax_gs_percorso_modifica', 'gs_ajax_percorso_modifica' );
function gs_ajax_percorso_modifica() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$pid = isset( $_POST['percorso'] ) ? (int) $_POST['percorso'] : 0;
	if ( 'gs_percorso_lezioni' !== get_post_type( $pid ) ) { wp_send_json_error( array( 'message' => 'Percorso non valido.' ) ); }
	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	if ( '' === trim( $titolo ) ) { wp_send_json_error( array( 'message' => 'Scrivi il nome del percorso.' ) ); }
	$descrizione = isset( $_POST['descrizione'] ) ? sanitize_textarea_field( wp_unslash( $_POST['descrizione'] ) ) : '';
	$livello     = isset( $_POST['livello'] ) ? sanitize_text_field( wp_unslash( $_POST['livello'] ) ) : '';
	$data_inizio = gs_percorso_data_valida( $_POST['data_inizio'] ?? '' );
	$data_fine   = gs_percorso_data_valida( $_POST['data_fine'] ?? '' );
	$squadra     = ! empty( $_POST['squadra'] );
	$staffetta   = ! empty( $_POST['staffetta'] ) && ! $squadra;

	wp_update_post( array( 'ID' => $pid, 'post_title' => $titolo, 'post_content' => $descrizione ) );
	if ( $livello && in_array( $livello, gs_percorso_livelli(), true ) ) {
		update_post_meta( $pid, 'gs_percorso_livello', $livello );
	} else {
		delete_post_meta( $pid, 'gs_percorso_livello' );
	}
	if ( $data_inizio ) { update_post_meta( $pid, 'gs_percorso_data_inizio', $data_inizio ); } else { delete_post_meta( $pid, 'gs_percorso_data_inizio' ); }
	if ( $data_fine ) { update_post_meta( $pid, 'gs_percorso_data_fine', $data_fine ); } else { delete_post_meta( $pid, 'gs_percorso_data_fine' ); }
	if ( $squadra ) { update_post_meta( $pid, 'gs_percorso_squadra', 1 ); } else { delete_post_meta( $pid, 'gs_percorso_squadra' ); }
	if ( $staffetta ) { update_post_meta( $pid, 'gs_staffetta_attiva', 1 ); } else { delete_post_meta( $pid, 'gs_staffetta_attiva' ); }
	wp_send_json_success( array( 'message' => 'Percorso aggiornato.' ) );
}

/** Sposta un percorso su o giù nella sequenza, scambiando l'ordine col vicino. */
add_action( 'wp_ajax_gs_percorso_sposta', 'gs_ajax_percorso_sposta' );
function gs_ajax_percorso_sposta() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$pid  = isset( $_POST['percorso'] ) ? (int) $_POST['percorso'] : 0;
	$dir  = isset( $_POST['direzione'] ) ? sanitize_key( wp_unslash( $_POST['direzione'] ) ) : '';
	if ( 'gs_percorso_lezioni' !== get_post_type( $pid ) ) { wp_send_json_error( array( 'message' => 'Percorso non valido.' ) ); }

	$percorsi = gs_percorsi_tutti();
	$indice   = null;
	foreach ( $percorsi as $i => $p ) {
		if ( (int) $p->ID === $pid ) { $indice = $i; break; }
	}
	if ( null === $indice ) { wp_send_json_error( array( 'message' => 'Percorso non trovato.' ) ); }

	$vicino_indice = 'su' === $dir ? $indice - 1 : $indice + 1;
	if ( $vicino_indice < 0 || $vicino_indice >= count( $percorsi ) ) {
		wp_send_json_error( array( 'message' => 'Non si può spostare oltre.' ) );
	}

	$vicino      = $percorsi[ $vicino_indice ];
	$ordine_mio    = (int) get_post_meta( $pid, 'gs_percorso_ordine', true );
	$ordine_vicino = (int) get_post_meta( $vicino->ID, 'gs_percorso_ordine', true );
	update_post_meta( $pid, 'gs_percorso_ordine', $ordine_vicino );
	update_post_meta( $vicino->ID, 'gs_percorso_ordine', $ordine_mio );

	wp_send_json_success( array( 'message' => 'Ordine aggiornato.' ) );
}

add_action( 'wp_ajax_gs_percorso_elimina', 'gs_ajax_percorso_elimina' );
function gs_ajax_percorso_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$pid = isset( $_POST['percorso'] ) ? (int) $_POST['percorso'] : 0;
	if ( 'gs_percorso_lezioni' !== get_post_type( $pid ) ) { wp_send_json_error( array( 'message' => 'Percorso non valido.' ) ); }
	wp_trash_post( $pid );
	wp_send_json_success( array( 'message' => 'Percorso spostato nel cestino.' ) );
}

add_action( 'wp_ajax_gs_percorso_lezione_aggiungi', 'gs_ajax_percorso_lezione_aggiungi' );
function gs_ajax_percorso_lezione_aggiungi() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$pid = isset( $_POST['percorso'] ) ? (int) $_POST['percorso'] : 0;
	if ( 'gs_percorso_lezioni' !== get_post_type( $pid ) ) { wp_send_json_error( array( 'message' => 'Percorso non valido.' ) ); }
	$lid = isset( $_POST['lezione'] ) ? (int) $_POST['lezione'] : 0;
	if ( 'gs_lezione' !== get_post_type( $lid ) ) { wp_send_json_error( array( 'message' => 'Lezione non valida.' ) ); }

	$lezioni = gs_percorso_get( $pid )['lezioni'];
	if ( ! in_array( $lid, $lezioni, true ) ) {
		$lezioni[] = $lid;
		update_post_meta( $pid, 'gs_lezioni_ordine', $lezioni );
	}
	wp_send_json_success( array( 'message' => 'Lezione aggiunta al percorso.' ) );
}

add_action( 'wp_ajax_gs_percorso_lezione_rimuovi', 'gs_ajax_percorso_lezione_rimuovi' );
function gs_ajax_percorso_lezione_rimuovi() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$pid = isset( $_POST['percorso'] ) ? (int) $_POST['percorso'] : 0;
	if ( 'gs_percorso_lezioni' !== get_post_type( $pid ) ) { wp_send_json_error( array( 'message' => 'Percorso non valido.' ) ); }
	$lid = isset( $_POST['lezione'] ) ? (int) $_POST['lezione'] : 0;

	$lezioni = array_values( array_filter( gs_percorso_get( $pid )['lezioni'], function ( $l ) use ( $lid ) {
		return $l !== $lid;
	} ) );
	update_post_meta( $pid, 'gs_lezioni_ordine', $lezioni );
	wp_send_json_success( array( 'message' => 'Lezione tolta dal percorso.' ) );
}

// -----------------------------------------------------------------------------
// AJAX — Livelli dei Percorsi Guidati (crea, rinomina, elimina, riordina)
// -----------------------------------------------------------------------------

add_action( 'wp_ajax_gs_livello_crea', 'gs_ajax_livello_crea' );
function gs_ajax_livello_crea() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$nome = isset( $_POST['nome'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['nome'] ) ) ) : '';
	if ( '' === $nome ) { wp_send_json_error( array( 'message' => 'Scrivi il nome del livello.' ) ); }

	$livelli = gs_percorso_livelli();
	if ( in_array( $nome, $livelli, true ) ) { wp_send_json_error( array( 'message' => 'Esiste già un livello con questo nome.' ) ); }
	$livelli[] = $nome;

	$s = gs_settings();
	$s['percorso_livelli'] = $livelli;
	update_option( GS_OPTION, $s );

	wp_send_json_success( array( 'message' => 'Livello creato.' ) );
}

add_action( 'wp_ajax_gs_livello_rinomina', 'gs_ajax_livello_rinomina' );
function gs_ajax_livello_rinomina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$indice = isset( $_POST['indice'] ) ? (int) $_POST['indice'] : -1;
	$nuovo  = isset( $_POST['nome'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['nome'] ) ) ) : '';
	if ( '' === $nuovo ) { wp_send_json_error( array( 'message' => 'Scrivi il nome del livello.' ) ); }

	$livelli = gs_percorso_livelli();
	if ( ! isset( $livelli[ $indice ] ) ) { wp_send_json_error( array( 'message' => 'Livello non trovato.' ) ); }
	$vecchio = $livelli[ $indice ];
	if ( $vecchio === $nuovo ) { wp_send_json_success( array( 'message' => 'Nessuna modifica.' ) ); }
	if ( in_array( $nuovo, $livelli, true ) ) { wp_send_json_error( array( 'message' => 'Esiste già un livello con questo nome.' ) ); }

	$livelli[ $indice ] = $nuovo;
	$s = gs_settings();
	$s['percorso_livelli'] = $livelli;
	update_option( GS_OPTION, $s );

	// I percorsi già assegnati al vecchio nome seguono la rinomina.
	foreach ( gs_percorsi_tutti() as $p ) {
		if ( get_post_meta( $p->ID, 'gs_percorso_livello', true ) === $vecchio ) {
			update_post_meta( $p->ID, 'gs_percorso_livello', $nuovo );
		}
	}

	wp_send_json_success( array( 'message' => 'Livello rinominato.' ) );
}

add_action( 'wp_ajax_gs_livello_elimina', 'gs_ajax_livello_elimina' );
function gs_ajax_livello_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$indice = isset( $_POST['indice'] ) ? (int) $_POST['indice'] : -1;

	$livelli = gs_percorso_livelli();
	if ( ! isset( $livelli[ $indice ] ) ) { wp_send_json_error( array( 'message' => 'Livello non trovato.' ) ); }
	$rimosso = $livelli[ $indice ];
	unset( $livelli[ $indice ] );

	$s = gs_settings();
	$s['percorso_livelli'] = array_values( $livelli );
	update_option( GS_OPTION, $s );

	// I percorsi che lo avevano restano, solo senza livello — non si eliminano.
	foreach ( gs_percorsi_tutti() as $p ) {
		if ( get_post_meta( $p->ID, 'gs_percorso_livello', true ) === $rimosso ) {
			delete_post_meta( $p->ID, 'gs_percorso_livello' );
		}
	}

	wp_send_json_success( array( 'message' => 'Livello eliminato. I percorsi che lo avevano sono rimasti senza livello, puoi riassegnarli.' ) );
}

add_action( 'wp_ajax_gs_livello_sposta', 'gs_ajax_livello_sposta' );
function gs_ajax_livello_sposta() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$indice = isset( $_POST['indice'] ) ? (int) $_POST['indice'] : -1;
	$dir    = isset( $_POST['direzione'] ) ? sanitize_key( wp_unslash( $_POST['direzione'] ) ) : '';

	$livelli = gs_percorso_livelli();
	if ( ! isset( $livelli[ $indice ] ) ) { wp_send_json_error( array( 'message' => 'Livello non trovato.' ) ); }
	$vicino = 'su' === $dir ? $indice - 1 : $indice + 1;
	if ( ! isset( $livelli[ $vicino ] ) ) { wp_send_json_error( array( 'message' => 'Non si può spostare oltre.' ) ); }

	$tmp             = $livelli[ $indice ];
	$livelli[ $indice ] = $livelli[ $vicino ];
	$livelli[ $vicino ] = $tmp;

	$s = gs_settings();
	$s['percorso_livelli'] = $livelli;
	update_option( GS_OPTION, $s );

	wp_send_json_success( array( 'message' => 'Ordine aggiornato.' ) );
}

// -----------------------------------------------------------------------------
// [gs_percorsi_pubblici] — vetrina pubblica, a scopo motivazionale: mostra i
// Percorsi Guidati anche a chi non si è ancora iscritto, senza rivelare le
// lezioni vere e proprie (riservate a chi ha l'abbonamento). Pagina pubblica,
// senza login richiesto, come la Vetrina, il Registro Ufficiale e gli Ultimi
// Traguardi: non passa dal cancello del blackout.
// -----------------------------------------------------------------------------
add_shortcode( 'gs_percorsi_pubblici', 'gs_sc_percorsi_pubblici' );
function gs_sc_percorsi_pubblici() {
	if ( $g = gs_blackout_gate() ) { return $g; }

	$out  = gs_box_open( '🗺️ I Percorsi Guidati dell\'Accademia' );
	$out .= gs_sezione_aiuto( 'Qui vedi il percorso di formazione dell\'Accademia della Sfoglia: le tappe in cui sono raggruppate le Lezioni Video, una dopo l\'altra. Le lezioni vere e proprie si vedono solo con l\'abbonamento attivo — questa pagina è solo la mappa del cammino, pensata anche per chi non si è ancora iscritto. Se compare "🎄 Attivi ora, per tempo limitato", sono Percorsi Stagionali: contenuti speciali visibili solo per un periodo, poi tornano nascosti. Se compare "🎽 Percorsi di Squadra", si completano tutti insieme: basta che qualcuno della squadra veda una lezione perché conti per tutti i compagni.' );

	$percorsi = array();
	foreach ( gs_percorsi_sequenza() as $p ) {
		$c = gs_percorso_get( $p->ID );
		if ( $c['lezioni'] ) {
			$percorsi[] = $c;
		}
	}
	$stagionali = array();
	foreach ( gs_percorsi_stagionali_attivi() as $p ) {
		$c = gs_percorso_get( $p->ID );
		if ( $c['lezioni'] ) {
			$stagionali[] = $c;
		}
	}
	$squadra_percorsi = array();
	foreach ( gs_percorsi_tutti() as $p ) {
		if ( ! gs_percorso_e_squadra( $p->ID ) ) { continue; }
		$c = gs_percorso_get( $p->ID );
		if ( $c['lezioni'] ) {
			$squadra_percorsi[] = $c;
		}
	}

	if ( ! $percorsi && ! $stagionali && ! $squadra_percorsi ) {
		$out .= '<p class="gs-hint">Il percorso di formazione è in preparazione: torna presto.</p>';
		$out .= gs_box_close();
		return $out;
	}

	if ( $percorsi ) {
		$out .= '<div class="gs-todo-riquadro gs-paginate" data-per-page="6">';
		foreach ( $percorsi as $i => $c ) {
			$num_lezioni = count( $c['lezioni'] );
			$out .= '<p class="gs-hint"><strong>' . (int) ( $i + 1 ) . '. 🗺️ ' . esc_html( $c['titolo'] ) . '</strong>' . ( $c['livello'] ? ' <span class="gs-msg-tag">' . esc_html( $c['livello'] ) . '</span>' : '' );
			$out .= '<br>' . (int) $num_lezioni . ( 1 === $num_lezioni ? ' lezione video' : ' lezioni video' );
			if ( $c['descrizione'] ) {
				$out .= '<br>' . esc_html( $c['descrizione'] );
			}
			$out .= '</p>';
		}
		$out .= '</div>';
	}

	if ( $stagionali ) {
		$out .= '<p class="gs-hint" style="font-size:16px;margin-top:14px"><strong>🎄 Attivi ora, per tempo limitato</strong></p>';
		$out .= '<div class="gs-todo-riquadro gs-paginate" data-per-page="6">';
		foreach ( $stagionali as $c ) {
			$num_lezioni = count( $c['lezioni'] );
			$out .= '<p class="gs-hint"><strong>🎄 ' . esc_html( $c['titolo'] ) . '</strong>' . ( $c['livello'] ? ' <span class="gs-msg-tag">' . esc_html( $c['livello'] ) . '</span>' : '' );
			$out .= '<br>' . (int) $num_lezioni . ( 1 === $num_lezioni ? ' lezione video' : ' lezioni video' );
			if ( $c['data_fine'] ) { $out .= '<br>Disponibile fino al ' . esc_html( date_i18n( 'j F Y', strtotime( $c['data_fine'] . ' 00:00:00' ) ) ) . '.'; }
			if ( $c['descrizione'] ) {
				$out .= '<br>' . esc_html( $c['descrizione'] );
			}
			$out .= '</p>';
		}
		$out .= '</div>';
	}

	if ( $squadra_percorsi ) {
		$out .= '<p class="gs-hint" style="font-size:16px;margin-top:14px"><strong>🎽 Percorsi di Squadra</strong></p>';
		$out .= '<div class="gs-todo-riquadro gs-paginate" data-per-page="6">';
		foreach ( $squadra_percorsi as $c ) {
			$num_lezioni = count( $c['lezioni'] );
			$out .= '<p class="gs-hint"><strong>🎽 ' . esc_html( $c['titolo'] ) . '</strong>' . ( $c['livello'] ? ' <span class="gs-msg-tag">' . esc_html( $c['livello'] ) . '</span>' : '' );
			$out .= '<br>' . (int) $num_lezioni . ( 1 === $num_lezioni ? ' lezione video' : ' lezioni video' );
			if ( $c['descrizione'] ) {
				$out .= '<br>' . esc_html( $c['descrizione'] );
			}
			$out .= '</p>';
		}
		$out .= '</div>';
	}

	$pagina_iscrizione = (int) get_option( 'gs_page_iscrizione' );
	if ( $pagina_iscrizione && get_post( $pagina_iscrizione ) && 'trash' !== get_post_status( $pagina_iscrizione ) ) {
		$out .= '<p style="margin-top:14px"><a class="gs-btn gs-btn-verde" href="' . esc_url( get_permalink( $pagina_iscrizione ) ) . '">Iscriviti per iniziare il tuo percorso</a></p>';
	}

	$out .= gs_box_close();
	return $out;
}
