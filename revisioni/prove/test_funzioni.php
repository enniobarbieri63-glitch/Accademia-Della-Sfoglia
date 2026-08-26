<?php
// TEST 2 — ogni funzione gs_* CHIAMATA esiste davvero?
// Usa il tokenizer di PHP: commenti e stringhe non vengono scambiati per codice.
$dir = $argv[1];
$definite = []; $chiamate = []; $guardate = [];

$rii = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ) );
foreach ( $rii as $f ) {
    if ( $f->getExtension() !== 'php' ) continue;
    $rel = str_replace( $dir . '/', '', $f->getPathname() );
    $tok = token_get_all( file_get_contents( $f->getPathname() ) );
    $n = count( $tok );

    for ( $i = 0; $i < $n; $i++ ) {
        $t = $tok[ $i ];
        if ( ! is_array( $t ) ) continue;

        // definizione: function <nome>
        if ( $t[0] === T_FUNCTION ) {
            for ( $j = $i + 1; $j < $n; $j++ ) {
                if ( is_array( $tok[$j] ) && $tok[$j][0] === T_WHITESPACE ) continue;
                if ( is_array( $tok[$j] ) && $tok[$j][0] === T_STRING && str_starts_with( $tok[$j][1], 'gs_' ) )
                    $definite[ $tok[$j][1] ][] = "$rel:{$tok[$j][2]}";
                break;
            }
            continue;
        }

        // chiamata: T_STRING gs_* seguito da '(' e NON preceduto da -> :: function new
        if ( $t[0] === T_STRING && str_starts_with( $t[1], 'gs_' ) ) {
            $k = $i + 1;
            while ( $k < $n && is_array( $tok[$k] ) && $tok[$k][0] === T_WHITESPACE ) $k++;
            if ( $k >= $n || $tok[$k] !== '(' ) continue;
            $p = $i - 1;
            while ( $p >= 0 && is_array( $tok[$p] ) && $tok[$p][0] === T_WHITESPACE ) $p--;
            if ( $p >= 0 && is_array( $tok[$p] ) && in_array( $tok[$p][0], [ T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW ], true ) ) continue;
            $chiamate[ $t[1] ][] = "$rel:{$t[2]}";
        }
    }

    // function_exists('gs_x') e callback passate come stringa
    $src = file_get_contents( $f->getPathname() );
    if ( preg_match_all( '/function_exists\(\s*[\'"](gs_[a-zA-Z0-9_]+)[\'"]/', $src, $m ) )
        foreach ( $m[1] as $fn ) $guardate[ $fn ] = true;
    if ( preg_match_all( '/add_(?:action|filter|shortcode)\(\s*[\'"][^\'"]+[\'"]\s*,\s*[\'"](gs_[a-zA-Z0-9_]+)[\'"]/', $src, $m, PREG_OFFSET_CAPTURE ) )
        foreach ( $m[1] as $h ) $chiamate[ $h[0] ][] = "$rel (aggancio)";
}

$mancanti = array_diff_key( $chiamate, $definite );
$morte    = array_diff_key( $definite, $chiamate );
$dup      = array_filter( $definite, fn( $v ) => count( $v ) > 1 );

echo "  definite: " . count( $definite ) . " · chiamate: " . count( $chiamate ) . "\n";
echo "  definite due volte: " . count( $dup ) . "\n";
foreach ( $dup as $fn => $d ) echo "    ⚠ $fn → " . implode( ' + ', $d ) . "\n";
echo "  CHIAMATE MA NON DEFINITE: " . count( $mancanti ) . "\n";
foreach ( $mancanti as $fn => $d )
    echo ( isset( $guardate[$fn] ) ? "    ~ " : "    ✗✗ " ) . "$fn "
       . ( isset( $guardate[$fn] ) ? "(protetta)" : "← ROMPE IL SITO" ) . " — " . implode( ', ', array_slice( $d, 0, 3 ) ) . "\n";
echo "  definite ma mai chiamate (codice morto): " . count( $morte ) . "\n";
foreach ( array_slice( $morte, 0, 12, true ) as $fn => $d ) echo "    · $fn — {$d[0]}\n";
