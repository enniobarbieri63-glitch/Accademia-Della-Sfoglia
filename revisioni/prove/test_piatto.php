<?php
require 'stub-wp.php';
$V = $argv[1] ?? 'zip20';
// estrae solo le due funzioni pure di adozione/rinuncia dal file vero
$src = file_get_contents( "$V/gaming-sfogline/includes/piatti-estinzione.php" );
preg_match( '/function gs_piatto_adotta_uid.*?\n}\n/s', $src, $a );
preg_match( '/function gs_piatto_libera_uid.*?\n}\n/s', $src, $b );
eval( $a[0] . "\n" . $b[0] );

echo "  === PIATTI — versione $V ===\n";
$tot = 0; $ok = 0;

// PROVA 1: adotta, rinuncia, riadotta → i punti si prendono UNA VOLTA SOLA
azzera();
$GLOBALS['PM']['5|__tipo'] = 'gs_piatto'; $GLOBALS['PM']['5|__titolo'] = 'Tagliatelle di San Nicola';
gs_piatto_adotta_uid( 5, 100 );
$dopo1 = $GLOBALS['PUNTI'][100] ?? 0;
gs_piatto_libera_uid( 5, 100 );
gs_piatto_adotta_uid( 5, 100 );
gs_piatto_libera_uid( 5, 100 );
gs_piatto_adotta_uid( 5, 100 );
$dopo3 = $GLOBALS['PUNTI'][100] ?? 0;
$tot++; $ok += esito( $dopo1 === 20 && $dopo3 === 20, "adotta/rinuncia/riadotta x3 → 20 punti in tutto (presi: $dopo3)" );

// PROVA 2: due sfogline sullo stesso piatto → una sola custode, una sola pagata
azzera();
$GLOBALS['PM']['7|__tipo'] = 'gs_piatto';
$r1 = gs_piatto_adotta_uid( 7, 200 );
$r2 = gs_piatto_adotta_uid( 7, 300 );
$tot++; $ok += esito( $r1['ok'] && ! $r2['ok'], "la seconda che adotta viene respinta ({$r2['message']})" );
$tot++; $ok += esito( ( $GLOBALS['PUNTI'][300] ?? 0 ) === 0, "chi e' stata respinta NON prende punti (" . ( $GLOBALS['PUNTI'][300] ?? 0 ) . ")" );
$tot++; $ok += esito( (int) get_post_meta( 7, 'gs_custode_id', true ) === 200, "la custode e' la prima arrivata" );

// PROVA 3: il lucchetto viene sempre riaperto, su ogni uscita
azzera();
$GLOBALS['PM']['9|__tipo'] = 'gs_piatto';
gs_piatto_adotta_uid( 9, 400 );                        // riuscita
$l1 = count( $GLOBALS['LOCKS'] );
gs_piatto_adotta_uid( 9, 500 );                        // "ha gia' una custode"
$l2 = count( $GLOBALS['LOCKS'] );
azzera(); $GLOBALS['PM']['9|__tipo'] = 'gs_piatto';
gs_piatto_adotta_uid( 9, 600, 'squadra' );             // "non fai parte di una squadra"
$l3 = count( $GLOBALS['LOCKS'] );
$tot++; $ok += esito( $l1 === 0 && $l2 === 0 && $l3 === 0, "lucchetto riaperto su tutte e 3 le uscite ($l1/$l2/$l3 rimasti aperti)" );

// PROVA 4: adozione di squadra
azzera();
$GLOBALS['PM']['11|__tipo'] = 'gs_piatto'; $GLOBALS['UM']['700|team'] = 'Le Rezdore';
$r = gs_piatto_adotta_uid( 11, 700, 'squadra' );
$tot++; $ok += esito( $r['ok'] && get_post_meta( 11, 'gs_custode_team', true ) === 'Le Rezdore' && ( $GLOBALS['PUNTI'][700] ?? 0 ) === 20, "adozione di squadra: custodia alla squadra, 20 punti a chi ha cliccato" );

echo "  → $ok/$tot superate\n";
