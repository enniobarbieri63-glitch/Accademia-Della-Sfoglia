<?php
/**
 * punteggio.php — il calcolo del punteggio per scarto dal valore corretto,
 * come descritto in docs/04-griglia-parametri-tecnici.md § 5.
 *
 * sla_calcola_punteggio() non chiama nessuna funzione di WordPress: è
 * scritta apposta così, per poter essere provata da sola, fuori da
 * WordPress, con un caso per volta (vedi tests/test-punteggio.php).
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'SLA_TEST' ) ) {
	exit;
}

/**
 * Calcola il punteggio (0-100) di un valore inserito rispetto al valore
 * corretto e alle due soglie di tolleranza.
 *
 * - scarto entro la soglia verde   → 100
 * - scarto tra verde e gialla      → scende linearmente da 100 a 55
 * - scarto oltre la soglia gialla  → scende da 54 verso 0, mai sotto zero
 *
 * @param float $valore  Il valore inserito dallo studente.
 * @param float $corretto Il valore corretto, dettato dal maestro.
 * @param float $verde   Tolleranza della fascia verde (± verde).
 * @param float $giallo  Tolleranza della fascia gialla (± giallo, giallo > verde).
 * @return int Punteggio da 0 a 100.
 */
function sla_calcola_punteggio( $valore, $corretto, $verde, $giallo ) {
	$valore   = (float) $valore;
	$corretto = (float) $corretto;
	$verde    = abs( (float) $verde );
	$giallo   = abs( (float) $giallo );

	// Una griglia con la soglia gialla non più larga di quella verde è un
	// errore di inserimento dei parametri, non un caso limite da gestire in
	// silenzio: si allarga la gialla appena sopra la verde, così il calcolo
	// resta ben definito invece di dividere per zero o per un numero
	// negativo.
	if ( $giallo <= $verde ) {
		$giallo = $verde + 0.0001;
	}

	$scarto = abs( $valore - $corretto );

	// Tolleranza minima per l'aritmetica in virgola mobile: senza, un
	// valore esattamente al bordo di una fascia (es. 1,1 − 0,8) può finire
	// nel ramo sbagliato perché la sottrazione non produce mai un numero
	// perfettamente esatto (trovato dal test di sla_calcola_punteggio()).
	$epsilon = 1e-9;

	if ( $scarto <= $verde + $epsilon ) {
		return 100;
	}

	if ( $scarto <= $giallo + $epsilon ) {
		$proporzione = ( $scarto - $verde ) / ( $giallo - $verde );
		return (int) round( 100 - 45 * $proporzione );
	}

	// Oltre la soglia gialla il punteggio scende da 54 verso 0. La discesa
	// è proporzionale a quanto lo scarto supera la soglia gialla, con la
	// stessa ampiezza della fascia gialla come riferimento: chi sbaglia il
	// doppio della fascia gialla prende 0, non un numero arbitrario.
	$oltre       = $scarto - $giallo;
	$penalita    = 54 * min( 1, $oltre / $giallo );
	return (int) max( 0, round( 54 - $penalita ) );
}

/**
 * La fascia (per il colore da mostrare: verde, giallo o rosso) di un
 * punteggio già calcolato.
 */
function sla_fascia_punteggio( $punteggio ) {
	$punteggio = (int) $punteggio;
	if ( $punteggio >= 85 ) {
		return 'verde';
	}
	if ( $punteggio >= 55 ) {
		return 'giallo';
	}
	return 'rosso';
}
