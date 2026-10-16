#!/usr/bin/env bash
#
# Turn the demo chrome on or off for the local app.
#
#   ./scripts/demo-mode.sh on      # demo banner, theme switcher, demo login
#   ./scripts/demo-mode.sh off     # back to a plain install
#   ./scripts/demo-mode.sh         # say which it currently is
#
# Demo mode is the UCM_DEMO_MODE environment variable, read by includes/config.php. The
# app container has to be recreated to pick up a new value, which is what this does - it
# only touches the app service, so the database and its data stay as they are.
#
# demo.ultimateclientmanager.com sets the same variable. Nothing else about the demo is
# special: the chrome in custom/ only renders when this is on.

set -euo pipefail

cd "$(dirname "$0")/.."

current() {
	docker compose exec -T app sh -c 'printf %s "${UCM_DEMO_MODE:-0}"' 2>/dev/null || printf '0'
}

case "${1:-status}" in
	on|off)
		want=$([ "$1" = "on" ] && echo 1 || echo 0)
		echo "Setting UCM_DEMO_MODE=$want and recreating the app container..."
		UCM_DEMO_MODE="$want" docker compose up -d --force-recreate --no-deps app
		# opcache.revalidate_freq is 2 in this image, so give it a moment before anything
		# checks the page - otherwise the first request can answer from stale bytecode.
		sleep 3
		echo "Demo mode is now: $([ "$want" = "1" ] && echo on || echo off)  ->  http://localhost:8090/"
		;;
	status)
		echo "Demo mode is currently: $([ "$(current)" = "0" ] && echo off || echo on)"
		;;
	*)
		echo "usage: $0 [on|off]" >&2
		exit 64
		;;
esac
