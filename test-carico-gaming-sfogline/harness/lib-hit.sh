#!/bin/bash
# $1 = "uid<TAB>cookie", env URL, METHOD, DATA
line="$1"
uid="${line%%	*}"; ck="${line#*	}"
if [ -n "$DATA" ]; then
  out=$(curl -s -o /tmp/out.$$ -w "%{http_code} %{time_total} %{size_download}" -H "Cookie: $ck" -X POST --data "$DATA" "$URL")
else
  out=$(curl -sL -o /tmp/out.$$ -w "%{http_code} %{time_total} %{size_download}" -H "Cookie: $ck" "$URL")
fi
echo "$uid $out"
rm -f /tmp/out.$$
