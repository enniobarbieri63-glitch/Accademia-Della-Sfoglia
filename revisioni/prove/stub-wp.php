<?php
// Finto WordPress: quel tanto che basta per far girare davvero le funzioni.
$GLOBALS['PM'] = []; $GLOBALS['UM'] = []; $GLOBALS['PUNTI'] = []; $GLOBALS['LOCKS'] = [];
$GLOBALS['LOG'] = [];

function get_post_meta( $id, $k, $single = false ) { return $GLOBALS['PM']["$id|$k"] ?? ''; }
function update_post_meta( $id, $k, $v ) { $GLOBALS['PM']["$id|$k"] = $v; return true; }
function add_post_meta( $id, $k, $v, $unique = false ) {
    if ( $unique && isset( $GLOBALS['PM']["$id|$k"] ) ) return false;
    $GLOBALS['PM']["$id|$k"] = $v; return 1;
}
function delete_post_meta( $id, $k ) { unset( $GLOBALS['PM']["$id|$k"] ); return true; }
function get_user_meta( $id, $k, $single = false ) { return $GLOBALS['UM']["$id|$k"] ?? ''; }
function update_user_meta( $id, $k, $v ) { $GLOBALS['UM']["$id|$k"] = $v; return true; }
function get_post_type( $id ) { return $GLOBALS['PM']["$id|__tipo"] ?? false; }
function get_the_title( $id ) { return $GLOBALS['PM']["$id|__titolo"] ?? 'Piatto'; }
function current_time( $f ) { return $f === 'mysql' ? date( 'Y-m-d H:i:s' ) : ( $f === 'timestamp' ? time() : date( $f ) ); }

function gs_add_points( $uid, $p, $r = '' ) {
    $GLOBALS['PUNTI'][ $uid ] = ( $GLOBALS['PUNTI'][ $uid ] ?? 0 ) + $p;
    $GLOBALS['LOG'][] = "+$p a #$uid ($r)";
    return $GLOBALS['PUNTI'][ $uid ];
}
function gs_get_points_value( $a, $d ) { return $d; }
function gs_get_user_team( $uid ) { return $GLOBALS['UM']["$uid|team"] ?? ''; }
function gs_can_manage() { return false; }
function gs_piatto_get( $pid ) {
    return [ 'custode_tipo' => get_post_meta( $pid, 'gs_custode_tipo', true ),
             'custode_id'   => (int) get_post_meta( $pid, 'gs_custode_id', true ),
             'custode_team' => get_post_meta( $pid, 'gs_custode_team', true ) ];
}
// Finto $wpdb con lucchetti veri: GET_LOCK fallisce se qualcun altro lo tiene.
class FakeWpdb {
    public function prepare( $q, ...$a ) { foreach ( $a as $v ) $q = preg_replace( '/%[sd]/', is_string($v) ? "'$v'" : $v, $q, 1 ); return $q; }
    public function get_var( $q ) {
        if ( preg_match( "/GET_LOCK\('([^']+)'/", $q, $m ) ) {
            if ( ! empty( $GLOBALS['LOCKS'][ $m[1] ] ) ) return '0';   // occupato
            $GLOBALS['LOCKS'][ $m[1] ] = true; return '1';
        }
        if ( preg_match( "/RELEASE_LOCK\('([^']+)'/", $q, $m ) ) { unset( $GLOBALS['LOCKS'][ $m[1] ] ); return '1'; }
        return null;
    }
    public function query( $q ) { return $this->get_var( $q ); }
}
$GLOBALS['wpdb'] = new FakeWpdb();
function azzera() { $GLOBALS['PM'] = []; $GLOBALS['UM'] = []; $GLOBALS['PUNTI'] = []; $GLOBALS['LOCKS'] = []; $GLOBALS['LOG'] = []; }
function esito( $ok, $t ) { echo ( $ok ? "    ✓ " : "    ✗✗ FALLITO — " ) . "$t\n"; return $ok; }
