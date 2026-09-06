<?php
/**
 * cpt.php — i quattro custom post type del plugin, tutti privati (non
 * appaiono mai nel sito pubblico né nei risultati di ricerca): la
 * visibilità la decidono gli shortcode del docente e dello studente, non
 * WordPress.
 *
 * sla_classe       — una classe di una scuola.
 *   post_title  = nome della classe (es. "3A Cucina")
 *   post_author = il docente (utente WordPress)
 *   meta sla_codice   = codice di ingresso, 6 caratteri
 *   meta sla_materia  = materia (cucina / pasticceria / sala)
 *   meta sla_anno     = anno scolastico, es. "2026/2027"
 *
 * sla_studente     — un posto nella classe, identificato da un nickname.
 *   post_parent = ID della classe
 *   meta sla_nickname = il nickname scelto dal docente per questo posto
 *   meta sla_occupato = '1' quando uno studente vi si è collegato
 *   meta sla_token    = token di sessione corrente (si rigenera ad ogni
 *                       nuovo ingresso, invalidando il precedente)
 *
 * sla_assegnazione — un compito dato a una classe.
 *   post_parent = ID della classe
 *   meta sla_esercizio  = slug dell'esercizio assegnato (vedi esercizi.php)
 *   meta sla_apertura   = data/ora ISO 8601 da cui è visibile
 *   meta sla_scadenza   = data/ora ISO 8601 oltre cui non si consegna (vuoto = mai)
 *   meta sla_tentativi  = tentativi consentiti (intero, 0 = illimitati)
 *   meta sla_vale_voto  = '1' se il punteggio conta come voto
 *
 * sla_tentativo    — il tentativo di uno studente su un'assegnazione.
 *   post_parent = ID dell'assegnazione
 *   meta sla_studente_id = ID del post sla_studente
 *   meta sla_valore      = il valore numerico inserito dallo studente
 *   meta sla_punteggio   = il punteggio calcolato, 0-100
 *   meta sla_stato       = 'in_corso' | 'consegnato'
 *   meta sla_data        = data/ora ISO 8601 della consegna
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'sla_register_cpt' );
function sla_register_cpt() {
	$sla_privato = array(
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => false,
		'show_in_menu'       => false,
		'show_in_nav_menus'  => false,
		'exclude_from_search'=> true,
		'has_archive'        => false,
		'hierarchical'       => false,
		'supports'           => array( 'title' ),
	);

	register_post_type( 'sla_classe', array_merge( $sla_privato, array(
		'labels' => array( 'name' => 'Classi — Sfoglia Lab Aula' ),
	) ) );

	register_post_type( 'sla_studente', array_merge( $sla_privato, array(
		'labels'       => array( 'name' => 'Studenti — Sfoglia Lab Aula' ),
		'hierarchical' => true, // usa post_parent per legarsi alla classe
	) ) );

	register_post_type( 'sla_assegnazione', array_merge( $sla_privato, array(
		'labels'       => array( 'name' => 'Assegnazioni — Sfoglia Lab Aula' ),
		'hierarchical' => true,
	) ) );

	register_post_type( 'sla_tentativo', array_merge( $sla_privato, array(
		'labels'       => array( 'name' => 'Tentativi — Sfoglia Lab Aula' ),
		'hierarchical' => true,
	) ) );
}
