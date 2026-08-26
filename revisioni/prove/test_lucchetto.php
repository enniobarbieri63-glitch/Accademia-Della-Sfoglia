<?php
require 'stub-wp.php';
foreach ( [ 'zip18' => '3.284.4 (prima)', 'zip20' => '3.286.0 (dopo)' ] as $V => $eti ) {
    $src = file_get_contents( "$V/gaming-sfogline/includes/piatti-estinzione.php" );
    preg_match( '/function gs_piatto_adotta_uid.*?\n}\n/s', $src, $a );
    $fn = str_replace( 'function gs_piatto_adotta_uid', "function adotta_$V", $a[0] );
    eval( $fn );

    azzera();
    $GLOBALS['PM']['3|__tipo'] = 'gs_piatto';
    // Simula "un'altra richiesta sta gia' adottando questo piatto proprio adesso":
    // il lucchetto e' preso da lei.
    $GLOBALS['LOCKS']['gs_piatto_adotta_3'] = true;
    $r = ( "adotta_$V" )( 3, 900 );
    $preso = ( $GLOBALS['PUNTI'][900] ?? 0 );
    echo "  $eti\n";
    if ( $r['ok'] ) echo "    ✗✗ entra lo stesso mentre un'altra sta adottando → $preso punti, e sovrascrive la custode\n";
    else            echo "    ✓ si ferma e aspetta: «{$r['message']}» — $preso punti\n";
}
