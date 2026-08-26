<?php
require 'stub-wp.php';
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );
// stub in più per il cron dei rimborsi
class FintoPost { public $ID; function __construct( $id ) { $this->ID = $id; } }
function get_posts( $a ) { return [ new FintoPost( 77 ) ]; }
function wp_cache_delete( $id, $g ) { return true; }
function gs_conv_msgs( $id ) { $m = get_post_meta( $id, 'gs_msgs', true ); return is_array( $m ) ? $m : []; }
function get_user_by( $f, $id ) { $u = new stdClass; $u->display_name = "Sfoglina $id"; $u->user_email = 'x@y.z'; return $u; }
function gs_settings() { return []; }
function gs_token_giorni_rimborso() { return 7; }
class MorteSimulata extends Exception {}
$GLOBALS['MOVIMENTI'] = 0; $GLOBALS['MUORI_DOPO'] = 999; $GLOBALS['EMAIL'] = 0;
function gs_token_movimento( $uid, $n, $tipo, $causale ) {
    $GLOBALS['MOVIMENTI']++;
    $GLOBALS['SALDO'][ $uid ] = ( $GLOBALS['SALDO'][ $uid ] ?? 0 ) + $n;
    return $GLOBALS['SALDO'][ $uid ];
}
function gs_mail_progetto( $uid, $cat, $og, $co ) {
    $GLOBALS['EMAIL']++;
    if ( $GLOBALS['MOVIMENTI'] >= $GLOBALS['MUORI_DOPO'] ) throw new MorteSimulata( 'processo ucciso dal limite di posta' );
    return true;
}

$V = $argv[1];
$src = file_get_contents( "$V/gaming-sfogline/includes/token.php" );
preg_match( '/function gs_token_controlla_rimborsi.*?\n}\n/s', $src, $m );
$fn = preg_replace( '/^function gs_token_controlla_rimborsi/', 'function cron_rimborsi', $m[0] );
// la funzione legge $giorni/$limite da variabili definite prima: le ricreo
$fn = $fn;
eval( $fn );

function prepara() {
    azzera();
    $GLOBALS['MOVIMENTI'] = 0; $GLOBALS['EMAIL'] = 0; $GLOBALS['SALDO'] = [];
    // tre domande scadute e senza risposta, nella stessa conversazione
    $vecchio = time() - 30 * 86400;
    $msgs = [];
    foreach ( [ 1, 2, 3 ] as $k )
        $msgs[] = [ 'id' => "d$k", 'from' => 500 + $k, 'consulenza' => 1, 'token_costo' => 1, 'time' => $vecchio ];
    update_post_meta( 77, 'gs_msgs', $msgs );
}
function segnati() {
    $n = 0;
    foreach ( get_post_meta( 77, 'gs_msgs', true ) as $m ) if ( ! empty( $m['rimborsato'] ) ) $n++;
    return $n;
}

echo "  === CRON DEI RIMBORSI — versione $V ===\n";
echo "  Tre domande scadute nella stessa conversazione. Il processo muore\n";
echo "  subito dopo il SECONDO rimborso (limite orario di posta dell'hosting).\n\n";
prepara();
$GLOBALS['MUORI_DOPO'] = 2;
try { cron_rimborsi(); } catch ( MorteSimulata $e ) { echo "  ☠  processo ucciso: {$e->getMessage()}\n"; }
$token_usciti = $GLOBALS['MOVIMENTI'];
$marcati = segnati();
echo "  token già usciti: $token_usciti — messaggi segnati «rimborsato»: $marcati\n";
echo "  " . ( $token_usciti === $marcati ? "✓ il registro corrisponde ai soldi usciti" : "✗✗ $token_usciti usciti ma solo $marcati segnati: il resto uscira' di nuovo" ) . "\n\n";

echo "  Il giorno dopo il cron riparte:\n";
$GLOBALS['MUORI_DOPO'] = 999; $GLOBALS['MOVIMENTI'] = 0;
cron_rimborsi();
$tot = 0; foreach ( $GLOBALS['SALDO'] as $s ) $tot += $s;
echo "  nuovi rimborsi in questo giro: {$GLOBALS['MOVIMENTI']} — token restituiti in tutto: $tot\n";
echo "  " . ( 3 === $tot ? "✓ tre domande, tre token. Nessun doppio rimborso." : "✗✗ $tot token per 3 domande: qualcuno e' stato pagato due volte" ) . "\n";
