<?php
/**
 * Piccolo bootstrap: carica WordPress e poi esegue lo script passato come argomento.
 * Uso: php bootstrap.php 02-seed-users.php
 * Sul tuo Local puoi usare al suo posto: wp eval-file 02-seed-users.php
 *
 * WP_PATH: cartella del sito WordPress (default ../.work/site).
 */
$gs_wp_path = getenv('WP_PATH') ?: __DIR__ . '/../.work/site';
if ( ! is_file( $gs_wp_path . "/wp-load.php" ) ) {
    fwrite(STDERR, "WordPress non trovato in $gs_wp_path — imposta WP_PATH\n");
    exit(1);
}
$_SERVER['HTTP_HOST'] = '127.0.0.1:8080';
$_SERVER['SERVER_NAME'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
define('WP_USE_THEMES', false);
define('GS_TEST_OUT', getenv('GS_TEST_OUT') ?: dirname($gs_wp_path));
require_once $gs_wp_path . "/wp-load.php";
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
$f = $argv[1] ?? null;
if ($f) { require $f; }
