<?php
/**
 * prezzi.php — calcolo dell'acconto e dello stato del pagamento. Nessuna
 * funzione di WordPress qui dentro, apposta per poter provare i calcoli
 * da soli (vedi tests/test-prezzi.php): sono soldi, un arrotondamento
 * sbagliato qui non è un dettaglio.
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'SLP_TEST' ) ) {
	exit;
}

/**
 * L'acconto dovuto su una quota, in centesimi, arrotondato per eccesso
 * all'euro (mai per difetto: un acconto arrotondato in meno è un mancato
 * incasso, uno arrotondato in più è un centesimo in più che nessuno nota).
 */
function slp_calcola_acconto( $quota_centesimi, $percentuale = SLP_ACCONTO_PERCENTUALE ) {
	$quota_centesimi = max( 0, (int) $quota_centesimi );
	$percentuale     = max( 0, min( 100, (float) $percentuale ) );
	$acconto_esatto  = $quota_centesimi * $percentuale / 100;
	return (int) ceil( $acconto_esatto / 100 ) * 100;
}

/**
 * La somma di uno storico di pagamenti (array di {importo_centesimi}).
 */
function slp_totale_pagato( $pagamenti ) {
	$totale = 0;
	foreach ( (array) $pagamenti as $pagamento ) {
		$totale += (int) ( $pagamento['importo_centesimi'] ?? 0 );
	}
	return $totale;
}

/**
 * Lo stato del pagamento di un'iscrizione, in parole: 'da_pagare',
 * 'acconto_versato' o 'saldo_versato'. Confronta quanto è stato versato
 * con l'acconto dovuto e con la quota intera.
 */
function slp_stato_pagamento( $quota_centesimi, $acconto_centesimi, $pagato_centesimi ) {
	$quota   = max( 0, (int) $quota_centesimi );
	$acconto = max( 0, (int) $acconto_centesimi );
	$pagato  = max( 0, (int) $pagato_centesimi );

	if ( $pagato >= $quota ) {
		return 'saldo_versato';
	}
	if ( $pagato >= $acconto && $acconto > 0 ) {
		return 'acconto_versato';
	}
	return 'da_pagare';
}
