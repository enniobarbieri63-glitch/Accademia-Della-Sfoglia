<?php
/**
 * Prova indipendente da WordPress di sla_percentuale_errore().
 * Esecuzione: php tests/test-percentuale-errore.php
 */

define( 'SLA_TEST', true );
require __DIR__ . '/../includes/vista-errori.php';

$casi = array(
	array( 0, 0, 0, 'nessun tentativo → 0, non un errore di divisione' ),
	array( 5, 10, 50, 'metà sbagliata → 50' ),
	array( 10, 10, 100, 'tutti sbagliati → 100' ),
	array( 0, 10, 0, 'nessuno sbagliato → 0' ),
	array( 1, 3, 33, 'un terzo, arrotondato → 33' ),
	array( 2, 3, 67, 'due terzi, arrotondato → 67' ),
	array( 15, 10, 100, 'più errori del totale (dato corrotto) → non supera 100' ),
	array( -3, 10, 0, 'errori negativi (dato assurdo) → non va sotto zero' ),
);

$falliti = 0;
foreach ( $casi as $c ) {
	list( $in_errore, $totale, $atteso, $descrizione ) = $c;
	$risultato = sla_percentuale_errore( $in_errore, $totale );
	$ok = ( $risultato === $atteso );
	printf( "[%s] %-65s → %d (atteso %d)\n", $ok ? 'OK' : 'FALLITO', $descrizione, $risultato, $atteso );
	if ( ! $ok ) { $falliti++; }
}

echo "\n";
if ( $falliti > 0 ) { echo "$falliti test falliti su " . count( $casi ) . "\n"; exit( 1 ); }
echo "Tutti i test passati (" . count( $casi ) . ").\n";
