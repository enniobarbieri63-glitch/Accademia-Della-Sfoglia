<?php
// TEST 4 — una funzione che chiama SE STESSA.
//
// Nasce dal quasi-incidente del 26/08/2026: uno script di sostituzione in
// blocco riscrisse anche la riga dentro gs_gate_riservato(), dandole una
// chiamata a se stessa. Il sito sarebbe andato in errore fatale su ogni
// pagina riservata, e `php -l` non ha niente da ridire: per PHP una funzione
// che chiama se stessa e' codice legittimo (la ricorsione esiste).
//
// Questo controllo distingue la ricorsione VERA — che ha sempre un'uscita:
// un return prima della chiamata, o un parametro che cambia — da quella
// nata per sbaglio, che chiama se stessa senza argomenti e senza condizione.
// Segnala solo la seconda.
$dir = $argv[1];
$sospette = []; $ricorsive = 0;

foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ) ) as $f ) {
    if ( $f->getExtension() !== 'php' ) continue;
    $rel = str_replace( $dir . '/', '', $f->getPathname() );
    $tok = token_get_all( file_get_contents( $f->getPathname() ) );
    $n = count( $tok );
    $nome = null; $graffe = 0; $dentro = false; $riga_def = 0; $corpo = '';

    for ( $i = 0; $i < $n; $i++ ) {
        $t = $tok[ $i ];
        if ( ! $dentro && is_array( $t ) && $t[0] === T_FUNCTION ) {
            for ( $j = $i + 1; $j < $n; $j++ ) {
                if ( is_array( $tok[$j] ) && $tok[$j][0] === T_WHITESPACE ) continue;
                if ( is_array( $tok[$j] ) && $tok[$j][0] === T_STRING ) {
                    $nome = $tok[$j][1]; $riga_def = $tok[$j][2]; $dentro = true; $graffe = 0; $corpo = '';
                }
                break;
            }
            continue;
        }
        if ( ! $dentro ) continue;
        if ( $t === '{' ) { $graffe++; continue; }
        if ( $t === '}' ) { $graffe--; if ( $graffe <= 0 ) { $dentro = false; $nome = null; } continue; }
        if ( $graffe < 1 ) continue;
        // chiamata a se stessa?
        if ( is_array( $t ) && $t[0] === T_STRING && $t[1] === $nome ) {
            $k = $i + 1;
            while ( $k < $n && is_array( $tok[$k] ) && $tok[$k][0] === T_WHITESPACE ) $k++;
            if ( $k >= $n || $tok[$k] !== '(' ) continue;
            // argomenti: '()' vuoto = quasi sempre un errore di sostituzione
            $k2 = $k + 1;
            while ( $k2 < $n && is_array( $tok[$k2] ) && $tok[$k2][0] === T_WHITESPACE ) $k2++;
            $senza_argomenti = ( $tok[$k2] === ')' );
            $ricorsive++;
            if ( $senza_argomenti ) $sospette[] = "$rel:{$t[2]}  $nome() chiama $nome() senza argomenti (definita a riga $riga_def)";
        }
    }
}
echo "  funzioni che chiamano se stesse: $ricorsive (la ricorsione vera e' legittima)\n";
echo "  SOSPETTE — chiamata a se stessa senza argomenti: " . count( $sospette ) . "\n";
foreach ( $sospette as $s ) echo "    ✗✗ $s\n";
if ( ! $sospette ) echo "    nessuna ✓\n";
