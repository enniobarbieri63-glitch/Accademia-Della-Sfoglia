#!/bin/bash
# Batteria di prove per un pacchetto del plugin.
#   ./prova.sh /percorso/alla/cartella/che/contiene/gaming-sfogline
# Serve solo PHP da riga di comando. Non serve WordPress, non serve un database.
D="${1:?uso: ./prova.sh <cartella che contiene gaming-sfogline>}"
cd "$(dirname "$0")"
echo "═══ 1. Sintassi PHP di ogni file ═══"
./test_sintassi.sh "$D/gaming-sfogline"
echo; echo "═══ 2. Ogni funzione gs_* chiamata esiste? ═══"
php test_funzioni.php "$D/gaming-sfogline"
echo; echo "═══ 3. Le due sponde: JavaScript ↔ PHP ═══"
php test_ajax.php "$D/gaming-sfogline"
echo; echo "═══ 4. Funzioni che chiamano se stesse per sbaglio ═══"
php test_ricorsione.php "$D/gaming-sfogline"
