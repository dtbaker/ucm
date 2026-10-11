#!/usr/bin/env bash
#
# Start a throwaway copy of UCM pointed at an empty database, so the installer can be
# tested from scratch without disturbing the development app on :8090.
#
#   ./scripts/install-test.sh          # reset and start it, print the URL
#   ./scripts/install-test.sh --stop   # shut it down again
#
# The copy runs on http://localhost:8092 against the `ucm_install_test` database, and
# writes its config.local.php inside the container rather than into the working copy.
#
# After it is up, `npx playwright test tests/e2e/install.spec.ts` walks the wizard.

set -euo pipefail

cd "$(dirname "$0")/.."

SERVICE="app-install-test"
DB_NAME="ucm_install_test"
BASE_URL="http://localhost:8092"

if [ "${1:-}" = "--stop" ]; then
	docker compose --profile install-test stop "$SERVICE"
	exit 0
fi

echo "Resetting the $DB_NAME database..."
# Keep retrying rather than waiting for MySQL first. On a brand new volume the official
# image runs a temporary server to set up users and then restarts, so a single "is it up
# yet" check can succeed against the temporary server and the next statement then fails.
reset_ok=0
for _ in $(seq 1 120); do
	if docker compose exec -T db mysql -u root -proot >/dev/null 2>&1 <<SQL
DROP DATABASE IF EXISTS \`$DB_NAME\`;
CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL ON \`$DB_NAME\`.* TO 'ucm'@'%';
FLUSH PRIVILEGES;
SQL
	then
		reset_ok=1
		break
	fi
	sleep 2
done
if [ "$reset_ok" != "1" ]; then
	echo "Could not reset $DB_NAME - MySQL never became usable" >&2
	docker compose logs --tail=40 db >&2
	exit 1
fi

echo "Starting a fresh $SERVICE container..."
docker compose --profile install-test up -d --force-recreate "$SERVICE"

echo "Waiting for $BASE_URL ..."
for _ in $(seq 1 60); do
	if curl -fsS -o /dev/null "$BASE_URL/"; then
		echo
		echo "Ready. The setup wizard is at $BASE_URL/"
		echo "Database: $DB_NAME (empty)."
		exit 0
	fi
	sleep 1
done

echo "Timed out waiting for $BASE_URL" >&2
docker compose --profile install-test logs --tail=40 "$SERVICE" >&2
exit 1
