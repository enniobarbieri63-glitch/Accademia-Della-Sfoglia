#!/bin/bash
line="$1"
uid=$(echo "$line"|cut -f1); ck=$(echo "$line"|cut -f2); nc=$(echo "$line"|cut -f3)
r=$(( RANDOM % 5 ))
case $r in
 0) u="http://127.0.0.1:8080/test-gs-dashboard/";;
 1) u="http://127.0.0.1:8080/test-gs-messaggi/";;
 2) u="http://127.0.0.1:8080/test-gs-classifica/";;
 3) u="http://127.0.0.1:8080/test-gs-sfogline/";;
 4) u="http://127.0.0.1:8080/test-gs-galleria-sfida/";;
esac
o=$(curl -sL -o /dev/null -w "%{http_code} %{time_total} %{size_download}" -H "Cookie: $ck" "$u")
echo "$uid $o"
for a in gs_voli_preleva gs_palloncini_ultimo gs_palloncino_gigante_ultimo; do
  o=$(curl -s -o /dev/null -w "%{http_code} %{time_total} %{size_download}" -H "Cookie: $ck" -X POST --data "action=$a&nonce=$nc" http://127.0.0.1:8080/wp-admin/admin-ajax.php)
  echo "$uid $o"
done
