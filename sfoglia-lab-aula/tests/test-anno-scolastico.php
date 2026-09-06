<?php
/**
 * Prova indipendente da WordPress di sla_anno_da_data().
 * Esecuzione: php tests/test-anno-scolastico.php
 */

define( 'SLA_TEST', true );
require __DIR__ . '/../includes/anno-scolastico.php';

function ts( $data ) { return strtotime( $data . ' 12:00:00 UTC' ); }

$casi = array(
	array( ts( '2026-09-01' ), '2026/2027', 'primo giorno di settembre → inizia il nuovo anno' ),
	array( ts( '2026-08-31' ), '2025/2026', 'ultimo giorno di agosto → è ancora l\'anno vecchio' ),
	array( ts( '2027-01-15' ), '2026/2027', 'gennaio → è ancora l\'anno iniziato a settembre' ),
	array( ts( '2026-06-10' ), '2025/2026', 'giugno, fine anno scolastico → l\'anno iniziato l\'autunno prima' ),
	array( ts( '2026-12-31' ), '2026/2027', 'ultimo giorno dell\'anno solare → dentro l\'anno scolastico iniziato a settembre' ),
);

$falliti = 0;
foreach ( $casi as $c ) {
	list( $timestamp, $atteso, $descrizione ) = $c;
	$risultato = sla_anno_da_data( $timestamp );
	$ok = ( $risultato === $atteso );
	printf( "[%s] %-70s → %s (atteso %s)\n", $ok ? 'OK' : 'FALLITO', $descrizione, $risultato, $atteso );
	if ( ! $ok ) { $falliti++; }
}

echo "\n";
if ( $falliti > 0 ) { echo "$falliti test falliti su " . count( $casi ) . "\n"; exit( 1 ); }
echo "Tutti i test passati (" . count( $casi ) . ").\n";
