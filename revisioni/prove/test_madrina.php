<?php
require 'stub-wp.php';
// Il cuore vero di gs_ajax_madrina_toggle in 3.286.0 (invariato).
echo "  === MADRINA & ALLIEVA 3.286.0 — la stessa mini-missione, spuntata e ri-spuntata ===\n\n";
azzera();
$missioni = [ [ 'id' => 'm1', 'testo' => 'Fate insieme la sfoglia per il ragù', 'fatta' => false ] ];
$madrina = 11; $allieva = 22;

for ( $clic = 1; $clic <= 10; $clic++ ) {
    $fatta_ora = false;
    foreach ( $missioni as &$m ) {
        if ( $m['id'] === 'm1' ) { $m['fatta'] = empty( $m['fatta'] ); $fatta_ora = $m['fatta']; }
    }
    unset( $m );
    if ( $fatta_ora ) {
        gs_add_points( $madrina, 5, 'Mini-missione con l\'allieva completata' );
        gs_add_points( $allieva, 5, 'Mini-missione con la madrina completata' );
    }
    if ( $clic <= 4 ) echo "  clic $clic — casella " . ( $missioni[0]['fatta'] ? 'spuntata  ' : 'tolta     ' )
        . "→ madrina " . ( $GLOBALS['PUNTI'][$madrina] ?? 0 ) . ", allieva " . ( $GLOBALS['PUNTI'][$allieva] ?? 0 ) . "\n";
}
echo "  …\n";
echo "  dopo 10 clic — madrina " . ( $GLOBALS['PUNTI'][$madrina] ?? 0 ) . ", allieva " . ( $GLOBALS['PUNTI'][$allieva] ?? 0 ) . "\n\n";
echo "  ✗✗ una sola mini-missione, mai davvero completata due volte, ha pagato "
   . ( ( $GLOBALS['PUNTI'][$madrina] ?? 0 ) + ( $GLOBALS['PUNTI'][$allieva] ?? 0 ) ) . " punti in tutto.\n";
echo "     Non serve malafede: basta cambiare idea. Non c'e' nessun limite.\n";
