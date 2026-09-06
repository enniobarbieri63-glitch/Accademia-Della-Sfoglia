<?php
/**
 * Prova indipendente da WordPress di sla_valuta_quiz().
 * Esecuzione: php tests/test-quiz.php
 */

define( 'SLA_TEST', true );
require __DIR__ . '/../includes/quiz.php';

$domande = array(
	array( 'id' => 'd1', 'corrette' => array( 'a' ) ),
	array( 'id' => 'd2', 'corrette' => array( 'b' ) ),
	array( 'id' => 'd3', 'corrette' => array( 'a', 'c' ) ),
	array( 'id' => 'd4', 'corrette' => array( 'b' ) ),
);

$casi = array(
	array(
		'risposte'   => array( 'd1' => 'a', 'd2' => 'b', 'd3' => array( 'a', 'c' ), 'd4' => 'b' ),
		'atteso'     => 100,
		'descrizione' => 'tutte le risposte giuste → 100',
	),
	array(
		'risposte'   => array( 'd1' => 'b', 'd2' => 'b', 'd3' => array( 'a', 'c' ), 'd4' => 'b' ),
		'atteso'     => 75,
		'descrizione' => 'tre giuste su quattro → 75',
	),
	array(
		'risposte'   => array( 'd1' => 'b', 'd2' => 'a', 'd3' => array( 'a' ), 'd4' => 'a' ),
		'atteso'     => 0,
		'descrizione' => 'tutte sbagliate → 0',
	),
	array(
		'risposte'   => array( 'd1' => 'a', 'd2' => 'b', 'd3' => array( 'a' ), 'd4' => 'b' ),
		'atteso'     => 75,
		'descrizione' => 'multipla con una sola delle due corrette selezionata → non vale',
	),
	array(
		'risposte'   => array( 'd1' => 'a', 'd2' => 'b', 'd3' => array( 'a', 'c', 'd' ), 'd4' => 'b' ),
		'atteso'     => 75,
		'descrizione' => 'multipla con una scelta di troppo → non vale',
	),
	array(
		'risposte'   => array(),
		'atteso'     => 0,
		'descrizione' => 'nessuna risposta data → 0, non un errore',
	),
);

$falliti = 0;
foreach ( $casi as $c ) {
	$risultato = sla_valuta_quiz( $c['risposte'], $domande );
	$ok = ( $risultato['punteggio'] === $c['atteso'] );
	printf( "[%s] %-70s → %d (atteso %d)\n", $ok ? 'OK' : 'FALLITO', $c['descrizione'], $risultato['punteggio'], $c['atteso'] );
	if ( ! $ok ) {
		$falliti++;
	}
}

// L'ordine con cui lo studente ha selezionato le opzioni di una domanda a
// scelta multipla non deve avere importanza: {a,c} e {c,a} sono la stessa
// risposta.
$r1 = sla_valuta_quiz( array( 'd3' => array( 'a', 'c' ) ), array( $domande[2] ) );
$r2 = sla_valuta_quiz( array( 'd3' => array( 'c', 'a' ) ), array( $domande[2] ) );
$ordine_ok = ( $r1['punteggio'] === $r2['punteggio'] && 100 === $r1['punteggio'] );
printf( "[%s] %s\n", $ordine_ok ? 'OK' : 'FALLITO', 'ordine delle scelte multiple non conta' );
if ( ! $ordine_ok ) {
	$falliti++;
}

echo "\n";
if ( $falliti > 0 ) {
	echo "$falliti test falliti su " . ( count( $casi ) + 1 ) . "\n";
	exit( 1 );
}
echo "Tutti i test passati (" . ( count( $casi ) + 1 ) . ").\n";
