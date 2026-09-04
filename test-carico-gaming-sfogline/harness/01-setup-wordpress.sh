#!/bin/bash
# Ricostruisce da zero un WordPress con SQLite e ci installa Gaming Sfogline.
# Uso: ./01-setup-wordpress.sh /percorso/gaming-sfogline.zip
set -e
ZIP="${1:?serve lo zip del plugin}"
WPV="${WPV:-7.1}"
BASE="$(cd "$(dirname "$0")" && pwd)/../.work"
mkdir -p "$BASE"; cd "$BASE"

[ -d wp ] || git clone --depth 1 --branch "$WPV" https://github.com/WordPress/WordPress wp
[ -d sdi ] || git clone --depth 1 https://github.com/WordPress/sqlite-database-integration sdi

rm -rf site && cp -r wp site && rm -rf site/.git
cp -r sdi/packages/plugin-sqlite-database-integration site/wp-content/plugins/sqlite-database-integration
rm -rf site/wp-content/plugins/sqlite-database-integration/wp-includes/database
cp -r sdi/packages/mysql-on-sqlite/src site/wp-content/plugins/sqlite-database-integration/wp-includes/database
cp site/wp-content/plugins/sqlite-database-integration/db.copy site/wp-content/db.php

rm -rf plugin && mkdir plugin && unzip -q -o "$ZIP" -d plugin
cp -r plugin/gaming-sfogline site/wp-content/plugins/

cat > site/wp-config.php <<'CFG'
<?php
define( 'DB_NAME', 'wordpress' ); define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' ); define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' ); define( 'DB_COLLATE', '' );
define( 'AUTH_KEY', 'a1' ); define( 'SECURE_AUTH_KEY', 'a2' ); define( 'LOGGED_IN_KEY', 'a3' ); define( 'NONCE_KEY', 'a4' );
define( 'AUTH_SALT', 'b1' ); define( 'SECURE_AUTH_SALT', 'b2' ); define( 'LOGGED_IN_SALT', 'b3' ); define( 'NONCE_SALT', 'b4' );
$table_prefix = 'wp_';
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', __DIR__ . '/wp-content/debug.log' );
define( 'WP_DEBUG_DISPLAY', false );
define( 'DISABLE_WP_CRON', true );
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ . '/' ); }
require_once ABSPATH . 'wp-settings.php';
CFG

php -r '
define("WP_INSTALLING", true);
$_SERVER["HTTP_HOST"]="127.0.0.1:8080"; $_SERVER["REQUEST_URI"]="/"; $_SERVER["SERVER_NAME"]="127.0.0.1";
require_once "'"$BASE"'/site/wp-load.php";
require_once ABSPATH."wp-admin/includes/upgrade.php";
require_once ABSPATH."wp-admin/includes/plugin.php";
wp_install("Test Gaming Sfogline","admin","admin@example.test",true,"","adminpass123");
activate_plugin("gaming-sfogline/gaming-sfogline.php");
update_option("siteurl","http://127.0.0.1:8080");
update_option("home","http://127.0.0.1:8080");
update_option("permalink_structure","/%postname%/"); flush_rewrite_rules(false);
echo "pronto\n";'
echo "Sito in $BASE/site — avvialo con:"
echo "  PHP_CLI_SERVER_WORKERS=16 php -S 127.0.0.1:8080 -t $BASE/site"
