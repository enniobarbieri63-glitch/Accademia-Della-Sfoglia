<?php
/**
 * Plugin Name: Sfoglia Lab — Aula
 * Plugin URI:  https://accademiadellasfoglia.it/
 * Description: Classi virtuali per gli istituti alberghieri: il docente crea
 *              una classe, gli studenti entrano con un codice e un nickname
 *              (nessun dato personale), svolgono gli esercizi assegnati e il
 *              docente esporta i punteggi. Progetto indipendente dal plugin
 *              Gaming Sfogline: nessuna community, nessuna vetrina, nessuna
 *              pubblicità.
 * Version:     0.1.0
 * Author:      Accademia della Sfoglia
 * Text Domain: sfoglia-lab-aula
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SLA_VERSION', '0.1.0' );
define( 'SLA_FILE', __FILE__ );
define( 'SLA_DIR', plugin_dir_path( __FILE__ ) );
define( 'SLA_URL', plugin_dir_url( __FILE__ ) );
define( 'SLA_INC', SLA_DIR . 'includes/' );
define( 'SLA_OPTION', 'sla_settings' );

// -----------------------------------------------------------------------------
// Caricamento moduli
// -----------------------------------------------------------------------------
$sla_modules = array(
	'helpers.php',      // utilità condivise: generazione codici, pulizia input
	'cpt.php',           // custom post type: classe, studente, assegnazione, tentativo
	'punteggio.php',     // calcolo del punteggio per scarto dal valore corretto
	'esercizi.php',      // catalogo degli esercizi (dati di esempio, in attesa dei maestri)
	'quiz.php',          // motore dei quiz a scelta singola/multipla
	'classi.php',        // pannello docente: crea classe, gestisce l'elenco, assegna
	'ingresso.php',      // ingresso studente: codice classe + nickname, sessione
	'svolgimento.php',   // lo studente svolge l'esercizio assegnato
	'cruscotto.php',     // le tre viste del docente + esportazione CSV
	'shortcodes.php',    // registrazione degli shortcode pubblici
);

foreach ( $sla_modules as $sla_module ) {
	$sla_path = SLA_INC . $sla_module;
	if ( file_exists( $sla_path ) ) {
		require_once $sla_path;
	}
}

// -----------------------------------------------------------------------------
// Attivazione
// -----------------------------------------------------------------------------
function sla_activate() {
	sla_register_cpt();
	flush_rewrite_rules();

	// Capacità dedicata per chi gestisce le classi (il "docente"). In questa
	// prima versione, senza SSO d'istituto (cantiere 8 della specifica), la
	// concede a chi è già Amministratore o Editor: è una semplificazione
	// temporanea, da sostituire quando si collega l'accesso Google
	// Workspace / Microsoft 365 dell'istituto.
	foreach ( array( 'administrator', 'editor' ) as $sla_ruolo ) {
		$sla_role = get_role( $sla_ruolo );
		if ( $sla_role && ! $sla_role->has_cap( 'sla_gestisci_classi' ) ) {
			$sla_role->add_cap( 'sla_gestisci_classi' );
		}
	}

	// Un esercizio dimostrativo, così il prodotto si prova subito. I valori
	// sono segnati come ESEMPIO: quelli veri arrivano dalla giornata di
	// rilevazione con i maestri (docs/04-griglia-parametri-tecnici.md).
	if ( function_exists( 'sla_esercizi_seed_demo' ) ) {
		sla_esercizi_seed_demo();
	}
}
register_activation_hook( __FILE__, 'sla_activate' );

function sla_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'sla_deactivate' );
