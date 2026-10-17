#!/usr/bin/env bash
#
# Sign in to the demo as each account its login page advertises, and fail if any is
# turned away.
#
#   ./scripts/demo-login-check.sh https://demo.ultimateclientmanager.com
#
# Used by the "Deploy to demo" and "Reset demo" workflows. These are the public demo
# logins, printed on the demo's own login page, so nothing here is a secret.

set -euo pipefail

base="${1:?usage: demo-login-check.sh <demo url>}"
base="${base%/}"
accounts=(admin@example.com user@example.com staff@example.com)
fail=0

for email in "${accounts[@]}"; do
	jar=$(mktemp)
	# A session first, the way a browser arrives, then the login form's own fields.
	curl -fsS --max-time 30 -c "$jar" -b "$jar" "$base/" -o /dev/null
	curl -fsS --max-time 30 -c "$jar" -b "$jar" -L -o /dev/null \
		--data-urlencode "_process_login=true" \
		--data-urlencode "email=$email" \
		--data-urlencode "password=password" \
		"$base/"
	page=$(curl -fsS --max-time 30 -c "$jar" -b "$jar" "$base/")
	rm -f "$jar"

	if grep -q 'name="_process_login"' <<<"$page" || ! grep -qiE 'log ?out' <<<"$page"; then
		echo "FAIL: $email cannot sign in to $base"
		fail=1
	else
		echo "OK:   $email signs in"
	fi
done

exit $fail
