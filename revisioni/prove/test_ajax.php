<?php
// TEST 3 — le due sponde: ogni azione AJAX chiamata dal JavaScript ha un
// gestore in PHP, e ogni gestore PHP e' raggiunto da qualcuno.
$dir = $argv[1];

// lato PHP: add_action( 'wp_ajax_X', 'handler' )
$php = [];
foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ) ) as $f ) {
    if ( $f->getExtension() !== 'php' ) continue;
    $rel = str_replace( $dir . '/', '', $f->getPathname() );
    $src = file_get_contents( $f->getPathname() );
    if ( preg_match_all( '/add_action\(\s*[\'"]wp_ajax(?:_nopriv)?_([a-zA-Z0-9_]+)[\'"]\s*,\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $src, $m, PREG_SET_ORDER ) )
        foreach ( $m as $x ) $php[ $x[1] ][] = [ 'fn' => $x[2], 'file' => $rel ];
}
// le funzioni davvero definite
$def = [];
foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ) ) as $f ) {
    if ( $f->getExtension() !== 'php' ) continue;
    if ( preg_match_all( '/\bfunction\s+([a-zA-Z0-9_]+)\s*\(/', file_get_contents( $f->getPathname() ), $m ) )
        foreach ( $m[1] as $fn ) $def[ $fn ] = true;
}
// lato JS: action: 'X'  oppure  action=X
$js = [];
foreach ( glob( "$dir/assets/js/*.js" ) as $f ) {
    $src = file_get_contents( $f );
    $rel = str_replace( $dir . '/', '', $f );
    if ( preg_match_all( '/action\s*:\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $src, $m, PREG_OFFSET_CAPTURE ) )
        foreach ( $m[1] as $h ) $js[ $h[0] ][] = $rel . ':' . ( substr_count( substr( $src, 0, $h[1] ), "\n" ) + 1 );
}

echo "  azioni registrate in PHP: " . count( $php ) . " · chiamate dal JS: " . count( $js ) . "\n\n";

$rotti = 0;
echo "  A) il JS chiama un'azione che in PHP NON esiste (il pulsante non fa niente):\n";
foreach ( $js as $a => $dove ) if ( ! isset( $php[ $a ] ) ) { $rotti++; echo "    ✗✗ $a — " . implode( ', ', $dove ) . "\n"; }
if ( ! $rotti ) echo "    nessuna ✓\n";

echo "\n  B) gestore registrato ma la funzione non esiste (errore fatale al clic):\n";
$f2 = 0;
foreach ( $php as $a => $lista ) foreach ( $lista as $h )
    if ( ! isset( $def[ $h['fn'] ] ) ) { $f2++; echo "    ✗✗ wp_ajax_$a → {$h['fn']}() — {$h['file']}\n"; }
if ( ! $f2 ) echo "    nessuno ✓\n";

echo "\n  C) gestore PHP che nessun JavaScript chiama (orfano o chiamato altrove):\n";
$orf = array_diff_key( $php, $js );
echo "    " . count( $orf ) . " — " . implode( ', ', array_slice( array_keys( $orf ), 0, 25 ) ) . "\n";
