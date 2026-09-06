<?php
/**
 * cpt.php — i tre custom post type del plugin, tutti privati.
 *
 * slp_sessione     — una data programmata di un corso del catalogo.
 *   post_title  = codice del corso (es. "PRO-1") + data, solo per l'admin
 *   meta slp_corso_codice = codice nel catalogo (vedi catalogo.php)
 *   meta slp_data         = data ISO 8601 di inizio
 *   meta slp_posti_max    = posti disponibili per questa data (0 = usa il
 *                           default del catalogo)
 *   meta slp_stato        = 'aperta' | 'chiusa' | 'annullata'
 *
 * slp_iscrizione   — l'iscrizione di una persona a una sessione.
 *   post_parent = ID della sessione
 *   meta slp_nome, slp_email, slp_telefono
 *   meta slp_stato     = 'in_attesa' | 'confermata' | 'annullata'
 *   meta slp_pagamenti = [ {data, importo_centesimi, note} ] — solo
 *                        aggiunto, mai sovrascritto (stesso principio già
 *                        visto in gaming-sfogline per i bonifici)
 *
 * slp_certificato  — un attestato di livello rilasciato.
 *   meta slp_persona_nome, slp_livello (0-3), slp_numero, slp_anno
 *   meta slp_data_rilascio, meta slp_pubblico ('1' = appare nell'elenco)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'slp_register_cpt' );
function slp_register_cpt() {
	$slp_privato = array(
		'public'              => false,
		'publicly_queryable'  => false,
		'show_ui'             => false,
		'show_in_menu'        => false,
		'show_in_nav_menus'   => false,
		'exclude_from_search' => true,
		'has_archive'         => false,
		'hierarchical'        => false,
		'supports'            => array( 'title' ),
	);

	register_post_type( 'slp_sessione', array_merge( $slp_privato, array(
		'labels' => array( 'name' => 'Sessioni corso — Sfoglia Lab Pro' ),
	) ) );

	register_post_type( 'slp_iscrizione', array_merge( $slp_privato, array(
		'labels'       => array( 'name' => 'Iscrizioni — Sfoglia Lab Pro' ),
		'hierarchical' => true,
	) ) );

	register_post_type( 'slp_certificato', array_merge( $slp_privato, array(
		'labels' => array( 'name' => 'Certificati — Sfoglia Lab Pro' ),
	) ) );
}
