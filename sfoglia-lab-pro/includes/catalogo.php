<?php
/**
 * catalogo.php — il catalogo dei corsi, da docs/01-progetto-sfoglia-lab.md
 * § 1.4. A differenza del catalogo esercizi/quiz di Sfoglia Lab — Aula,
 * qui i dati NON sono segnaposto: sono il listino già deciso nel
 * documento di progetto. Restano comunque nel codice, non in una tabella
 * a database, perché cambiano raramente e vanno tenuti sotto controllo di
 * versione come il resto del progetto.
 *
 * I prezzi sono in centesimi di euro (mai in virgola mobile): 29000 vale
 * 290,00 €. È la convenzione giusta per i soldi, per non ritrovarsi con
 * arrotondamenti sbagliati dopo qualche calcolo.
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'SLP_TEST' ) ) {
	exit;
}

/** Percentuale dell'acconto, uguale per tutti i corsi (doc 01 § 1.7). */
define( 'SLP_ACCONTO_PERCENTUALE', 30 );

function slp_catalogo_corsi() {
	return array(
		'PRO-1' => array(
			'codice'       => 'PRO-1',
			'titolo'       => "Sfoglia all'uovo, livello professionale",
			'destinatario' => 'Chef, cuochi',
			'durata'       => '1 giornata (8 h)',
			'posti_max'    => 12,
			'quota'        => 29000,
		),
		'PRO-2' => array(
			'codice'       => 'PRO-2',
			'titolo'       => 'Paste ripiene e standard di produzione',
			'destinatario' => 'Chef, capi partita',
			'durata'       => '2 giornate',
			'posti_max'    => 10,
			'quota'        => 54000,
		),
		'PAS-1' => array(
			'codice'       => 'PAS-1',
			'titolo'       => 'Laminazione: pasta sfoglia e croissant',
			'destinatario' => 'Pasticceri',
			'durata'       => '2 giornate',
			'posti_max'    => 10,
			'quota'        => 59000,
		),
		'PAS-2' => array(
			'codice'       => 'PAS-2',
			'titolo'       => 'Paste sottili: strudel, phyllo, sfogliatella',
			'destinatario' => 'Pasticceri',
			'durata'       => '1 giornata',
			'posti_max'    => 10,
			'quota'        => 32000,
		),
		'GES-1' => array(
			'codice'       => 'GES-1',
			'titolo'       => 'Resa, scarto e food cost della pasta fresca',
			'destinatario' => 'Titolari, F&B manager',
			'durata'       => '1 giornata (mezza in aula)',
			'posti_max'    => 15,
			'quota'        => 24000,
		),
		'CER-1' => array(
			'codice'       => 'CER-1',
			'titolo'       => 'Percorso completo Addetto Pasta Fresca',
			'destinatario' => 'Apprendisti, chi cerca lavoro',
			'durata'       => '5 giornate (40 h)',
			'posti_max'    => 10,
			'quota'        => 120000,
		),
		'AZ-1' => array(
			'codice'       => 'AZ-1',
			'titolo'       => 'Formazione in azienda, sulla brigata',
			'destinatario' => 'Ristoranti, pasticcerie, hotel',
			'durata'       => '1 giornata in loco',
			'posti_max'    => 0, // la brigata: non un numero fisso di posti individuali
			'quota'        => 175000,
		),
		'ESA' => array(
			'codice'       => 'ESA',
			'titolo'       => 'Esame di certificazione',
			'destinatario' => 'Tutti i livelli',
			'durata'       => '3 ore',
			'posti_max'    => 12,
			'quota'        => 14000,
		),
	);
}

/**
 * ABB (l'abbonamento annuale alla piattaforma, doc 01 § 1.4) non è in
 * questo catalogo: non è un corso con una data e posti limitati, è un
 * abbonamento ricorrente — un modello di dati diverso (rinnovo, scadenza,
 * fatturazione periodica), che non è ancora stato costruito. Va aggiunto
 * come modulo a parte, non forzato dentro le sessioni.
 */

function slp_get_corso( $codice ) {
	$catalogo = slp_catalogo_corsi();
	return isset( $catalogo[ $codice ] ) ? $catalogo[ $codice ] : null;
}
