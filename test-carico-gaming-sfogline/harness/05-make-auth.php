<?php
/**
 * Genera 1.000 sessioni WordPress vere: cookie di autenticazione + nonce gs_ajax.
 * Prodotto: <GS_TEST_OUT>/auth.tsv  con  uid <TAB> cookie <TAB> nonce
 */
global $wpdb;
$uids = $wpdb->get_col("SELECT ID FROM {$wpdb->users} WHERE user_login LIKE 'sfoglina%' ORDER BY ID LIMIT 1000");
$exp  = time() + 86400;
$rows = array();
$wpdb->query('START TRANSACTION');
foreach ($uids as $i => $uid) {
    $token = WP_Session_Tokens::get_instance($uid)->create($exp);
    $lc = wp_generate_auth_cookie($uid, $exp, 'logged_in', $token);
    $ac = wp_generate_auth_cookie($uid, $exp, 'auth', $token);
    // il nonce dipende dall'utente E dal token di sessione: va calcolato con
    // il cookie "montato", altrimenti admin-ajax.php lo rifiuta
    $_COOKIE[LOGGED_IN_COOKIE] = $lc;
    wp_set_current_user($uid);
    $rows[] = $uid . "\t" . LOGGED_IN_COOKIE . '=' . $lc . '; ' . AUTH_COOKIE . '=' . $ac . "\t" . wp_create_nonce('gs_ajax');
    if ($i % 200 === 0) { $wpdb->query('COMMIT'); $wpdb->query('START TRANSACTION'); }
}
$wpdb->query('COMMIT');
file_put_contents(GS_TEST_OUT . '/auth.tsv', implode("\n", $rows) . "\n");
echo "sessioni generate: " . count($rows) . " -> " . GS_TEST_OUT . "/auth.tsv\n";
