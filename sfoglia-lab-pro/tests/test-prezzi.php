<?php
/**
 * Prova indipendente da WordPress di prezzi.php.
 * Esecuzione: php tests/test-prezzi.php
 */

define( 'SLP_TEST', true );
define( 'SLP_ACCONTO_PERCENTUALE', 30 );
require __DIR__ . '/../includes/prezzi.php';

$falliti = 0;
function prova( $descrizione, $atteso, $risultato, &$falliti ) {
	$ok = ( $atteso === $risultato );
	printf( "[%s] %-65s → %s (atteso %s)\n", $ok ? 'OK' : 'FALLITO', $descrizione,
		var_export( $risultato, true ), var_export( $atteso, true ) );
	if ( ! $ok ) { $falliti++; }
}

// --- slp_calcola_acconto ---------------------------------------------------

// PRO-1: 290,00 € → 30% = 87,00 € esatti
prova( 'acconto su 290,00 € al 30% → 87,00 €', 8700, slp_calcola_acconto( 29000, 30 ), $falliti );

// GES-1: 240,00 € → 30% = 72,00 € esatti
prova( 'acconto su 240,00 € al 30% → 72,00 €', 7200, slp_calcola_acconto( 24000, 30 ), $falliti );

// Un importo che NON cade su un euro esatto: 100,00 € al 33% = 33,00 € esatti comunque;
// proviamo un caso che genera centesimi frazionari: 100,01 € al 30% = 30,003 € → arrotonda per eccesso a 31 centesimi? No: arrotonda all'euro.
// 10001 centesimi * 30 / 100 = 3000.3 centesimi → ceil / 100 * 100 = ceil(30.003) = 31 -> 3100 centesimi = 31,00 €
prova( 'importo che non cade su un euro esatto → arrotonda per eccesso, mai per difetto',
	3100, slp_calcola_acconto( 10001, 30 ), $falliti );

prova( 'quota a zero → acconto zero', 0, slp_calcola_acconto( 0, 30 ), $falliti );
prova( 'percentuale a zero → acconto zero', 0, slp_calcola_acconto( 29000, 0 ), $falliti );
prova( 'percentuale assurda oltre 100 → non supera la quota intera', 29000, slp_calcola_acconto( 29000, 150 ), $falliti );
prova( 'quota negativa (dato assurdo) → non va sotto zero', 0, slp_calcola_acconto( -5000, 30 ), $falliti );

// --- slp_totale_pagato -------------------------------------------------------

prova( 'nessun pagamento → totale zero', 0, slp_totale_pagato( array() ), $falliti );
prova( 'due pagamenti si sommano', 15000,
	slp_totale_pagato( array(
		array( 'importo_centesimi' => 8700 ),
		array( 'importo_centesimi' => 6300 ),
	) ), $falliti );

// --- slp_stato_pagamento -----------------------------------------------------

prova( 'niente versato → da_pagare', 'da_pagare', slp_stato_pagamento( 29000, 8700, 0 ), $falliti );
prova( "esattamente l'acconto versato → acconto_versato", 'acconto_versato', slp_stato_pagamento( 29000, 8700, 8700 ), $falliti );
prova( "più dell'acconto ma meno del saldo → acconto_versato", 'acconto_versato', slp_stato_pagamento( 29000, 8700, 15000 ), $falliti );
prova( 'quota intera versata → saldo_versato', 'saldo_versato', slp_stato_pagamento( 29000, 8700, 29000 ), $falliti );
prova( 'versato più della quota (dato strano, non deve rompersi) → saldo_versato', 'saldo_versato', slp_stato_pagamento( 29000, 8700, 30000 ), $falliti );
prova( 'un centesimo prima dell\'acconto → ancora da_pagare', 'da_pagare', slp_stato_pagamento( 29000, 8700, 8699 ), $falliti );

echo "\n";
if ( $falliti > 0 ) { echo "$falliti test falliti\n"; exit( 1 ); }
echo "Tutti i test passati.\n";
