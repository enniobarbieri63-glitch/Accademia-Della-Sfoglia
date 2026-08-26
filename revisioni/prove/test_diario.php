<?php
require 'stub-wp.php';
foreach ( [ 'zip18' => '3.284.4 (prima)', 'zip20' => '3.286.0 (dopo)' ] as $V => $eti ) {
    // isola il solo blocco dei punti del Diario dalla funzione vera
    $src = file_get_contents( "$V/gaming-sfogline/includes/forms.php" );
    preg_match( '/function gs_ajax_aggiungi_diario.*?\n}\n/s', $src, $m );
    $corpo = $m[0];
    // taglia via tutto cio' che tocca WordPress (nonce, upload, wp_insert_post, json)
    $inizio = strpos( $corpo, 'gs_add_points' ) !== false ? strpos( $corpo, "\t// I punti" ) : false;
    $blocco = $inizio !== false
        ? substr( $corpo, $inizio, strpos( $corpo, 'gs_detect_evo' ) - $inizio )
        : "\tgs_add_points( \$user_id, gs_get_points_value( 'voce_diario', 15 ), 'Voce di diario aggiunta' );\n";
    eval( "function scrivi_voce_$V( \$user_id ) {\n$blocco\n}" );

    azzera();
    for ( $i = 0; $i < 6; $i++ ) ( "scrivi_voce_$V" )( 42 );   // sei voci nello stesso giorno
    $p = $GLOBALS['PUNTI'][42] ?? 0;
    echo "  $eti — sei voci di diario nello stesso giorno: $p punti\n";
    echo "    " . ( 15 === $p ? "✓ paga una volta sola" : "✗✗ paga ogni volta (" . ( $p / 15 ) . " volte)" ) . "\n";
}
