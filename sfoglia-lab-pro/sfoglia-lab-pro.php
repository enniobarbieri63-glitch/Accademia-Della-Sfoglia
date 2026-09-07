<?php
/**
 * Plugin Name: Sfoglia Lab — Pro
 * Plugin URI:  https://accademiadellasfoglia.it/
 * Description: Corsi professionali per chef e pasticceri, iscrizione con
 *              acconto, percorso di certificazione a livelli con elenco
 *              pubblico. Progetto indipendente da gaming-sfogline e da
 *              Sfoglia Lab — Aula: nessuna dipendenza tra i tre.
 * Version:     0.1.0
 * Author:      Accademia della Sfoglia
 * Text Domain: sfoglia-lab-pro
 * License:     GPL-2.0-or-later
 *
 * Prefisso delle funzioni: slp_ (Sfoglia Lab Pro), per non confondersi con
 * sla_ di Sfoglia Lab — Aula né con gs_ di Gaming Sfogline: tre plugin
 * distinti, installabili insieme sullo stesso sito senza collisioni.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SLP_VERSION', '0.1.0' );
define( 'SLP_FILE', __FILE__ );
define( 'SLP_DIR', plugin_dir_path( __FILE__ ) );
define( 'SLP_URL', plugin_dir_url( __FILE__ ) );
define( 'SLP_INC', SLP_DIR . 'includes/' );
define( 'SLP_OPTION', 'slp_settings' );

$slp_modules = array(
	'helpers.php',        // utilità condivise
	'cpt.php',            // custom post type: sessione, iscrizione, certificato
	'catalogo.php',       // il catalogo dei corsi (docs/01 §1.4)
	'prezzi.php',         // calcolo acconto e stato del pagamento
	'certificazione.php', // numerazione, scadenza, elenco pubblico
	'sessioni.php',       // date dei corsi, posti disponibili
	'iscrizioni.php',     // iscrizione pubblica + storico pagamenti
	'impostazioni.php',   // coordinate del bonifico, interruttore delle email
	'email.php',          // conferme automatiche a iscritto e gestore
	'pannello.php',       // pannello del gestore corsi
	'esportazione.php',   // elenco partecipanti in CSV
	'shortcodes.php',     // pagine pubbliche
	'privacy.php',        // esportazione e cancellazione dei dati su richiesta
);

foreach ( $slp_modules as $slp_module ) {
	$slp_path = SLP_INC . $slp_module;
	if ( file_exists( $slp_path ) ) {
		require_once $slp_path;
	}
}

function slp_activate() {
	slp_register_cpt();
	flush_rewrite_rules();

	// Solo agli amministratori. Il pannello mostra nome, email e telefono di
	// ogni iscritto e permette di registrare incassi: è un livello di
	// accesso che va dato a una persona precisa, non a un intero ruolo
	// redazionale, dove di solito finisce chi scrive gli articoli del sito.
	$slp_role = get_role( 'administrator' );
	if ( $slp_role && ! $slp_role->has_cap( 'slp_gestisci_corsi' ) ) {
		$slp_role->add_cap( 'slp_gestisci_corsi' );
	}

	// Le versioni precedenti la concedevano anche agli Editor: qui viene
	// tolta, altrimenti su un sito già attivato resterebbe per sempre.
	// Per dare il pannello a un collaboratore che non è amministratore,
	// assegnare la capacità slp_gestisci_corsi al singolo utente (per
	// esempio con un plugin di gestione ruoli), non all'intero ruolo.
	$slp_editor = get_role( 'editor' );
	if ( $slp_editor && $slp_editor->has_cap( 'slp_gestisci_corsi' ) ) {
		$slp_editor->remove_cap( 'slp_gestisci_corsi' );
	}
}
register_activation_hook( __FILE__, 'slp_activate' );

function slp_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'slp_deactivate' );
