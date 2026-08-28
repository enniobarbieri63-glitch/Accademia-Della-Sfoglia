<?php
/**
 * cruscotto.php — Il Cruscotto della Verità.
 *
 * Non un altro gioco: un pannello che mostra, per ognuna delle sezioni del
 * gaming basate su un proprio contenuto (Sfide, Ricettario, Diario,
 * Cassaforte del Sapere, ecc.), quante voci ci sono in totale e da quanti
 * giorni non arriva niente di nuovo — per capire cosa è vivo davvero e cosa
 * è fermo, prima di continuare ad aggiungere altre idee.
 *
 * Copre le sezioni che hanno un proprio Custom Post Type (la maggioranza).
 * Non copre (per ora) le poche meccaniche che vivono solo in meta di utenti
 * o opzioni senza un proprio contenuto tracciabile per data (es. Streak,
 * Mappa dei Territori, Percorso a Staffetta, Badge assegnati): lo dichiara
 * esplicitamente nel pannello invece di fingere di coprire tutto.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Elenco delle meccaniche tracciate: chiave => { label, cpt }. */
function gs_cruscotto_meccaniche() {
	return array(
		'sfida'            => array( 'label' => 'Sfida della Settimana', 'cpt' => 'gs_sfida' ),
		'sfoglia'          => array( 'label' => 'Foto inviate alle sfide', 'cpt' => 'gs_sfoglia' ),
		'ricetta'          => array( 'label' => 'Ricettario delle Famiglie', 'cpt' => 'gs_ricetta' ),
		'diario'           => array( 'label' => 'Diario dell\'Impasto', 'cpt' => 'gs_diario' ),
		'consiglio'        => array( 'label' => 'Consigli della Community', 'cpt' => 'gs_consiglio' ),
		'ingrediente'      => array( 'label' => 'Ingrediente Segreto', 'cpt' => 'gs_ingrediente' ),
		'tavolo'           => array( 'label' => 'Il Tavolo di Lavoro', 'cpt' => 'gs_tavolo' ),
		'piatto'           => array( 'label' => 'Adotta un Piatto in Via di Estinzione', 'cpt' => 'gs_piatto' ),
		'voce'             => array( 'label' => 'Il Matterello Parlante', 'cpt' => 'gs_voce' ),
		'cassaforte'       => array( 'label' => 'La Cassaforte del Sapere', 'cpt' => 'gs_cassaforte' ),
		'giuria'           => array( 'label' => 'La Giuria a Turno', 'cpt' => 'gs_giuria' ),
		'lezione'          => array( 'label' => 'Lezioni Video', 'cpt' => 'gs_lezione' ),
		'percorso_lezioni' => array( 'label' => 'Percorsi Guidati', 'cpt' => 'gs_percorso_lezioni' ),
		'faq'              => array( 'label' => 'FAQ - Domande', 'cpt' => 'gs_faq' ),
		'testimonianza'    => array( 'label' => 'Dicono di Noi', 'cpt' => 'gs_testimonianza' ),
		'errore_didattico' => array( 'label' => 'La Sfoglia che Insegna Se Stessa', 'cpt' => 'gs_errore_didattico' ),
		'direttiva'        => array( 'label' => 'Regia del Gaming', 'cpt' => 'gs_direttiva' ),
		'corso_cal'        => array( 'label' => 'Calendario Corsi', 'cpt' => 'gs_corso_cal' ),
		'prenotazione'     => array( 'label' => 'Prenotazioni corsi', 'cpt' => 'gs_prenotazione' ),
		'conversazione'    => array( 'label' => 'Conversazioni private', 'cpt' => 'gs_conversazione' ),
		'aiuto'            => array( 'label' => 'Aiuto e Suggerimenti', 'cpt' => 'gs_aiuto' ),
		'locandina'        => array( 'label' => 'Diplomi e Locandine', 'cpt' => 'gs_locandina' ),
		'misura'           => array( 'label' => 'La Sfoglia Misurata', 'cpt' => 'gs_misura' ),
		'augurio'          => array( 'label' => 'Compleanni (auguri)', 'cpt' => 'gs_augurio' ),
		'msg_interno'      => array( 'label' => 'Posta interna', 'cpt' => 'gs_msg_interno' ),
		'abbinamento'      => array( 'label' => 'Madrina & Allieva', 'cpt' => 'gs_abbinamento' ),
		'premio'           => array( 'label' => 'Premio di Fine Anno', 'cpt' => 'gs_premio' ),
		'corso'            => array( 'label' => 'Corsi Online', 'cpt' => 'gs_corso' ),
		'barometro'        => array( 'label' => 'Guida Stagionale', 'cpt' => 'gs_barometro' ),
	);
}

/**
 * Per ogni meccanica: totale voci pubblicate, data dell'ultima, giorni di
 * silenzio da allora (null se non ha mai avuto nessuna voce). Ordinato dal
 * più silenzioso al più vivo (le mai-usate per prime: sono le più urgenti
 * da valutare).
 */
function gs_cruscotto_dati() {
	$out = array();
	foreach ( gs_cruscotto_meccaniche() as $key => $m ) {
		$ids = get_posts( array(
			'post_type'      => $m['cpt'],
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		) );
		$totale = count( $ids );
		$ultima = null;
		$giorni = null;
		if ( $ids ) {
			$p = get_post( $ids[0] );
			if ( $p ) {
				$ultima = $p->post_date;
				$giorni = (int) floor( ( current_time( 'timestamp' ) - strtotime( $ultima ) ) / DAY_IN_SECONDS );
				if ( $giorni < 0 ) { $giorni = 0; }
			}
		}
		$out[ $key ] = array_merge( $m, array(
			'totale' => $totale,
			'ultima' => $ultima,
			'giorni' => $giorni,
		) );
	}
	uasort( $out, function ( $a, $b ) {
		$ga = null === $a['giorni'] ? PHP_INT_MAX : $a['giorni'];
		$gb = null === $b['giorni'] ? PHP_INT_MAX : $b['giorni'];
		return $gb <=> $ga; // più silenziosa prima
	} );
	return $out;
}

/** Fascia di "vivacità" in base ai giorni di silenzio: mai | dormiente | tiepida | viva. */
function gs_cruscotto_fascia( $giorni ) {
	if ( null === $giorni ) { return 'mai'; }
	if ( $giorni > 60 ) { return 'dormiente'; }
	if ( $giorni > 14 ) { return 'tiepida'; }
	return 'viva';
}

function gs_pannello_cruscotto() {
	if ( ! ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) ) {
		return;
	}
	$dati = gs_cruscotto_dati();
	$fasce_label = array( 'viva' => 'Viva', 'tiepida' => 'Tiepida', 'dormiente' => 'Dormiente', 'mai' => 'Mai usata' );

	echo gs_box_open( '📊 Il Cruscotto della Verità', '', 'gs-box-cruscotto' );
	echo '<p class="gs-hint">Quante voci ha ogni sezione e da quanti giorni non arriva niente di nuovo — per capire cosa è vivo davvero prima di aggiungere altre idee al gaming. Copre le sezioni con un proprio archivio di contenuti; non copre (ancora) le poche meccaniche che vivono solo nei dati degli utenti senza una data propria (es. Streak, Mappa dei Territori, Percorso a Staffetta, Badge assegnati).</p>';

	echo '<table class="gs-table gs-paginate" data-per-page="15">';
	echo '<thead><tr><th>Sezione</th><th>Voci totali</th><th>Ultima attività</th><th>Stato</th></tr></thead><tbody>';
	foreach ( $dati as $riga ) {
		$fascia = gs_cruscotto_fascia( $riga['giorni'] );
		$cls    = 'gs-crusc-' . $fascia;
		echo '<tr class="' . esc_attr( $cls ) . '">';
		echo '<td><strong>' . esc_html( $riga['label'] ) . '</strong></td>';
		echo '<td>' . (int) $riga['totale'] . '</td>';
		if ( null === $riga['giorni'] ) {
			echo '<td>—</td>';
		} elseif ( 0 === $riga['giorni'] ) {
			echo '<td>Oggi</td>';
		} else {
			echo '<td>' . (int) $riga['giorni'] . ' giorni fa</td>';
		}
		echo '<td><span class="gs-crusc-badge gs-crusc-badge-' . esc_attr( $fascia ) . '">' . esc_html( $fasce_label[ $fascia ] ) . '</span></td>';
		echo '</tr>';
	}
	echo '</tbody></table>';

	echo gs_box_close();
}
