<?php
/**
 * esercizi.php — il catalogo degli esercizi assegnabili.
 *
 * In questa prima versione il catalogo è un array scritto nel codice, non
 * una tabella gestibile dal pannello: è così finché non arrivano i valori
 * veri dalla giornata di rilevazione con i maestri (vedi
 * docs/04-griglia-parametri-tecnici.md). L'unico esercizio presente,
 * "tagliatella-spessore", ha valori di ESEMPIO chiaramente segnati come
 * tali: non sono numeri misurati, servono solo a far vedere il meccanismo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ritorna la definizione di un esercizio dato il suo slug, o null se non
 * esiste. Ogni esercizio ha: un enunciato, l'unità di misura, il valore
 * corretto, le due soglie di tolleranza, e i tre messaggi (verde/giallo/
 * rosso) scritti come le direbbe il maestro.
 */
function sla_get_esercizio( $slug ) {
	$catalogo = sla_catalogo_esercizi();
	return isset( $catalogo[ $slug ] ) ? $catalogo[ $slug ] : null;
}

function sla_catalogo_esercizi() {
	return array(
		'tagliatella-spessore' => array(
			'slug'       => 'tagliatella-spessore',
			'titolo'     => 'Tagliatella — spessore della sfoglia',
			'enunciato'  => 'A che spessore, in millimetri, va chiusa la sfoglia per la tagliatella?',
			'unita'      => 'mm',
			'corretto'   => 0.8,
			'verde'      => 0.1,
			'giallo'     => 0.3,
			'esempio'    => true, // ATTENZIONE: valori segnaposto, non ancora validati dai maestri
			'messaggi'   => array(
				'verde'  => 'Spessore corretto: la sfoglia regge la cottura e il sugo non scivola via.',
				'giallo' => 'Spessore accettabile, ma ai limiti: controlla la tiratura al centro e ai bordi.',
				'rosso'  => 'Troppo spessa o troppo sottile: in cottura resta cruda dentro oppure si rompe.',
			),
		),
	);
}

/**
 * All'attivazione non c'è altro da seminare: il catalogo è nel codice, non
 * nel database. La funzione resta comunque agganciata all'attivazione (vedi
 * sfoglia-lab-aula.php) in modo che, quando il catalogo diventerà gestibile
 * dal pannello, l'aggancio ci sia già.
 */
function sla_esercizi_seed_demo() {
	// Nessuna azione necessaria in questa versione.
}
