#!/bin/bash
# Prove HTTP. Richiede il server avviato su 127.0.0.1:8080 e auth.tsv generato da 05-make-auth.php.
cd "$(dirname "$0")"
export BASE_URL="${BASE_URL:-http://127.0.0.1:8080}"

echo "### polling: 1000 utenti, 20 in parallelo"
for A in gs_voli_preleva gs_palloncini_ultimo gs_palloncino_gigante_ultimo; do
  export ACTION=$A
  t0=$(date +%s.%N)
  cat ../.work/auth.tsv | xargs -d '\n' -P 20 -I{} ./lib-ajax.sh "{}" > "/tmp/load_$A.txt"
  t1=$(date +%s.%N)
  echo "--- $A  wall=$(echo "$t1-$t0"|bc)s"
  python3 stats.py < "/tmp/load_$A.txt"
done

echo; echo "### carico misto: 1 pagina + 3 poll per utente, 40 in parallelo"
t0=$(date +%s.%N)
cat ../.work/auth.tsv | xargs -d '\n' -P 40 -I{} ./lib-mix.sh "{}" > /tmp/mix.txt
t1=$(date +%s.%N)
echo "wall=$(echo "$t1-$t0"|bc)s"
python3 stats.py < /tmp/mix.txt

echo; echo "### gara sulle spunte 'letto' (perdita di scritture)"
echo "azzera gs_letto_da su un messaggio broadcast, poi apri la pagina Messaggi con 100 utenti"
echo "in parallelo e riconta le spunte: vedi RAPPORTO §3.1"
