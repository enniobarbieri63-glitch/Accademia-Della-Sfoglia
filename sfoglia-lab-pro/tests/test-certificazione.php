<?php
/**
 * Prova indipendente da WordPress di certificazione.php.
 * Esecuzione: php tests/test-certificazione.php
 */

define( 'SLP_TEST', true );
require __DIR__ . '/../includes/certificazione.php';

$falliti = 0;
function prova( $descrizione, $atteso, $risultato, &$falliti ) {
	$ok = ( $atteso === $risultato );
	printf( "[%s] %-65s → %s (atteso %s)\n", $ok ? 'OK' : 'FALLITO', $descrizione,
		var_export( $risultato, true ), var_export( $atteso, true ) );
	if ( ! $ok ) { $falliti++; }
}

function ts( $data ) { return strtotime( $data . ' 12:00:00 UTC' ); }

// --- numerazione -------------------------------------------------------

prova( 'primo certificato dell\'anno', '2026-0001', slp_formatta_numero_certificato( 2026, 1 ), $falliti );
prova( 'settimo certificato, quattro cifre con zeri iniziali', '2026-0007', slp_formatta_numero_certificato( 2026, 7 ), $falliti );
prova( 'oltre le mille unità, non tronca', '2026-1234', slp_formatta_numero_certificato( 2026, 1234 ), $falliti );
prova( 'sequenza a zero (dato assurdo) → almeno 1', '2026-0001', slp_formatta_numero_certificato( 2026, 0 ), $falliti );

// --- scadenza a tre anni -------------------------------------------------

prova( 'un anno dopo → ancora valido', false,
	slp_certificato_scaduto( ts( '2026-01-15' ), ts( '2027-01-15' ) ), $falliti );

prova( 'esattamente tre anni dopo, stesso giorno → ancora valido (non scaduto)', false,
	slp_certificato_scaduto( ts( '2026-01-15' ), ts( '2029-01-15' ) ), $falliti );

prova( 'tre anni e un giorno dopo → scaduto', true,
	slp_certificato_scaduto( ts( '2026-01-15' ), ts( '2029-01-16' ) ), $falliti );

// Il 2027 non è bisestile: "29 febbraio + 3 anni" non esiste, PHP scivola
// in modo prevedibile all'1 marzo 2027 (verificato, nessun valore assurdo).
// Quel giorno stesso conta ancora come valido, per la stessa regola vista
// sopra ("scade il giorno dopo, non lo stesso giorno della scadenza").
prova( "29 febbraio bisestile, +3 anni: proprio il giorno della scadenza calcolata → ancora valido", false,
	slp_certificato_scaduto( ts( '2024-02-29' ), ts( '2027-03-01' ) ), $falliti );

prova( '29 febbraio bisestile, +3 anni e un giorno → scaduto, niente crash sul valore scivolato', true,
	slp_certificato_scaduto( ts( '2024-02-29' ), ts( '2027-03-02' ) ), $falliti );

echo "\n";
if ( $falliti > 0 ) { echo "$falliti test falliti\n"; exit( 1 ); }
echo "Tutti i test passati.\n";
