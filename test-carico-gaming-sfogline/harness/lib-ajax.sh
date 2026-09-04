#!/bin/bash
line="$1"
uid=$(echo "$line" | cut -f1); ck=$(echo "$line" | cut -f2); nc=$(echo "$line" | cut -f3)
body=$(curl -s -o /tmp/a.$$ -w "%{http_code} %{time_total} %{size_download}" -H "Cookie: $ck" -X POST --data "action=$ACTION&nonce=$nc$EXTRA" "http://127.0.0.1:8080/wp-admin/admin-ajax.php")
ok=$(head -c 40 /tmp/a.$$ | tr -d '\n')
echo "$uid $body $ok"
rm -f /tmp/a.$$
