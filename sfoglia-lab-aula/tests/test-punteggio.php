<?php
/**
 * Prova indipendente da WordPress di sla_calcola_punteggio().
 * Esecuzione: php tests/test-punteggio.php
 */

define( 'SLA_TEST', true );
require __DIR__ . '/../includes/punteggio.php';

$casi = array(
	// [valore, corretto, verde, giallo, atteso minimo, atteso massimo, descrizione]
	array( 0.8, 0.8, 0.1, 0.3, 100, 100, 'valore esatto → 100' ),
	array( 0.85, 0.8, 0.1, 0.3, 100, 100, 'dentro la fascia verde → 100' ),
	array( 0.9, 0.8, 0.1, 0.3, 100, 100, 'esattamente al bordo verde → 100' ),
	array( 1.1, 0.8, 0.1, 0.3, 55, 55, 'esattamente al bordo giallo → 55' ),
	array( 1.0, 0.8, 0.1, 0.3, 70, 80, 'a metà della fascia gialla → intorno a 77' ),
	array( 1.5, 0.8, 0.1, 0.3, 0, 30, 'molto oltre la fascia gialla → vicino a 0' ),
	array( 1.0, 0.8, 0.1, 0.1, 0, 100, 'fascia gialla non più larga della verde → non esplode (resta 0-100)' ),
	array( -5, 0.8, 0.1, 0.3, 0, 0, 'valore assurdo (negativo) → mai sotto zero' ),
	array( 0.7, 0.8, 0.1, 0.3, 100, 100, 'scarto negativo ma dentro tolleranza → 100' ),
);

$falliti = 0;
foreach ( $casi as $c ) {
	list( $valore, $corretto, $verde, $giallo, $min, $max, $descrizione ) = $c;
	$risultato = sla_calcola_punteggio( $valore, $corretto, $verde, $giallo );
	$ok = ( $risultato >= $min && $risultato <= $max );
	printf(
		"[%s] %-55s → %d (atteso %d-%d)\n",
		$ok ? 'OK' : 'FALLITO',
		$descrizione,
		$risultato,
		$min,
		$max
	);
	if ( ! $ok ) {
		$falliti++;
	}
}

// La fascia deve essere monotona: un valore più vicino al corretto non può
// mai avere un punteggio più basso di uno più lontano.
$precedente_scarto = 0;
$precedente_punti   = 100;
$non_monotono        = false;
for ( $s = 0; $s <= 200; $s += 5 ) {
	$scarto    = $s / 100;
	$punteggio = sla_calcola_punteggio( 0.8 + $scarto, 0.8, 0.1, 0.3 );
	if ( $scarto > $precedente_scarto && $punteggio > $precedente_punti ) {
		$non_monotono = true;
	}
	$precedente_scarto = $scarto;
	$precedente_punti  = $punteggio;
}
printf( "[%s] %s\n", $non_monotono ? 'FALLITO' : 'OK', 'il punteggio non risale mai allontanandosi dal valore corretto' );
if ( $non_monotono ) {
	$falliti++;
}

echo "\n";
if ( $falliti > 0 ) {
	echo "$falliti test falliti su " . ( count( $casi ) + 1 ) . "\n";
	exit( 1 );
}
echo "Tutti i test passati (" . ( count( $casi ) + 1 ) . ").\n";
