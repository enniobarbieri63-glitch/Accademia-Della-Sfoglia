<?php
require 'stub-wp.php';
// Il cuore vero di gs_ajax_madrina_toggle, estratto dai due file.
foreach ( [ 'zip20' => '3.286.0 (prima)', 'zip21' => '3.287.0 (dopo)' ] as $V => $eti ) {
    $src = file_get_contents( "$V/gaming-sfogline/includes/madrina.php" );
    preg_match( '/function gs_ajax_madrina_toggle.*?\n}\n/s', $src, $mm );
    $c = $mm[0];
    // isola dal "$fatta_ora = false;" al blocco dei punti compreso
    $i = strpos( $c, '$fatta_ora = false;' );
    $j = strpos( $c, 'wp_send_json_success' );
    $blocco = substr( $c, $i, $j - $i );
    $blocco = str_replace( 'gs_abbinamento_salva_missioni( $abb_id, $missioni );', '$GLOBALS["MISS"] = $missioni;', $blocco );
    $blocco = str_replace( "\$id       = isset", '// ', $blocco );
    eval( "function toggle_$V( \$missioni, \$id, \$abb_id ) {\n$blocco\n return \$GLOBALS['MISS']; }" );

    azzera();
    $GLOBALS['PM']['1|gs_madrina'] = 11; $GLOBALS['PM']['1|gs_allieva'] = 22;
    $missioni = [ [ 'id' => 'm1', 'testo' => 'Fate la sfoglia insieme', 'fatta' => false ] ];
    for ( $k = 0; $k < 10; $k++ ) $missioni = ( "toggle_$V" )( $missioni, 'm1', 1 );
    $tot = ( $GLOBALS['PUNTI'][11] ?? 0 ) + ( $GLOBALS['PUNTI'][22] ?? 0 );
    echo "  $eti — 10 clic sulla stessa casella → madrina " . ( $GLOBALS['PUNTI'][11] ?? 0 )
       . ", allieva " . ( $GLOBALS['PUNTI'][22] ?? 0 ) . " (totale $tot)\n";
    echo "    " . ( 10 === $tot ? "✓ pagata una volta sola" : "✗✗ pagata " . ( $tot / 10 ) . " volte" ) . "\n";
    // e la casella funziona ancora?
    echo "    stato finale della casella: " . ( $missioni[0]['fatta'] ? 'spuntata' : 'non spuntata' )
       . " — l'interruttore funziona ancora " . ( isset( $missioni[0]['pagata'] ) ? "(contrassegno 'pagata' presente ✓)" : "" ) . "\n";
}
