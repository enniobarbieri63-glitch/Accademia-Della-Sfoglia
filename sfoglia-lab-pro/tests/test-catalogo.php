<?php
/**
 * test-catalogo.php — controlla che slp_get_corso() sia sensibile alle
 * maiuscole come i codici del catalogo (es. "PRO-1"). Nasce dal bug reale
 * di "Crea sessione": una versione precedente salvava il codice passato
 * per sanitize_key(), che lo forzava in minuscolo ("pro-1"), rompendo
 * ogni lookup successivo nel catalogo e facendo sparire la sessione
 * dall'elenco pur essendo salvata nel database. Questo test non
 * verifica slp_crea_sessione() (dipende da wp_insert_post, non
 * disponibile qui), ma la funzione di lookup che quel bug aveva colpito.
 */

define( 'SLP_TEST', true );
require __DIR__ . '/../includes/catalogo.php';

$falliti = 0;

function verifica( $etichetta, $atteso, $ottenuto ) {
	global $falliti;
	$ok = $atteso === $ottenuto;
	if ( ! $ok ) {
		$falliti++;
	}
	printf(
		"[%s] %-70s → %s (atteso %s)\n",
		$ok ? 'OK' : 'FALLITO',
		$etichetta,
		var_export( $ottenuto, true ),
		var_export( $atteso, true )
	);
}

$corso = slp_get_corso( 'PRO-1' );
verifica( 'codice esatto → trova il corso', 'PRO-1', $corso ? $corso['codice'] : null );

verifica( 'codice minuscolo (il bug: sanitize_key lo produce così) → non trova nulla', null, slp_get_corso( 'pro-1' ) );

verifica( 'codice inesistente → null, non un errore', null, slp_get_corso( 'XXX-9' ) );

if ( $falliti > 0 ) {
	echo "\n$falliti test falliti.\n";
	exit( 1 );
}
echo "\nTutti i test passati.\n";
