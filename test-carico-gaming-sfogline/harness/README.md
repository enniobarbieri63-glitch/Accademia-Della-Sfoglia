# Banco di prova — Gaming Sfogline con 1.000 utenti

Ricostruisce un WordPress pulito, ci installa il plugin, lo popola con 1.000 sfogline
approvate e i contenuti corrispondenti, poi misura query, tempi, memoria e tenuta sotto carico.

## Sequenza

```bash
./01-setup-wordpress.sh /percorso/gamingsfogline3.321.84.zip
PHP_CLI_SERVER_WORKERS=16 php -S 127.0.0.1:8080 -t ../.work/site &

php bootstrap.php 02-seed-users.php        # 1.000 utenti  (~4 min su SQLite)
php bootstrap.php 03-seed-content.php      # sfide, sfoglie, messaggi, catalogo
php bootstrap.php 04-make-pages.php        # una pagina per shortcode
php bootstrap.php 05-make-auth.php         # 1.000 sessioni vere -> auth.tsv

php bootstrap.php 06-profile-shortcodes.php   # query/tempo/memoria per shortcode
php bootstrap.php 07-profile-panel.php        # query/tempo/HTML per sezione del pannello
./08-load-test.sh                             # prove HTTP

php 09a-scan-unbounded-queries.php ../.work/site/wp-content/plugins/gaming-sfogline
php 09b-scan-ajax-guards.php       ../.work/site/wp-content/plugins/gaming-sfogline
php 09c-scan-idor.php              ../.work/site/wp-content/plugins/gaming-sfogline
```

## Usarlo sul proprio Local (guru2)

`bootstrap.php` serve solo a caricare WordPress: se hai WP-CLI puoi saltarlo.

```bash
export WP_PATH=~/Local\ Sites/guru2/app/public
export GS_TEST_OUT=/tmp/gs-test && mkdir -p "$GS_TEST_OUT"
php bootstrap.php 02-seed-users.php
# oppure, con WP-CLI:  wp eval-file 02-seed-users.php --path="$WP_PATH"
```

⚠️ Gli script **creano 1.000 utenti e migliaia di post**: usali su una copia del sito,
mai sul sito di produzione. Gli utenti creati hanno login `sfoglina0001`…`sfoglina1000`
e sono facili da rimuovere in blocco.
