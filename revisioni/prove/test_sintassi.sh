#!/bin/bash
# TEST 1 — sintassi PHP di ogni file del pacchetto
D="$1"; err=0; n=0
while IFS= read -r f; do
  n=$((n+1))
  out=$(php -l "$f" 2>&1)
  if [ $? -ne 0 ]; then err=$((err+1)); echo "  ✗ ${f#$D/}"; echo "$out" | head -3; fi
done < <(find "$D" -name "*.php")
echo "  file controllati: $n — errori di sintassi: $err"
