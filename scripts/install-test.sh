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

echo "Waiting for MySQL..."
# Run a real query, not mysqladmin ping - ping answers while MySQL is still setting up
# its users, and the very next statement then fails with "access denied".
mysql_ready=0
for _ in $(seq 1 120); do
	if docker compose exec -T db mysql -u root -proot -e 'SELECT 1' >/dev/null 2>&1; then
		mysql_ready=1
		break
	fi
	sleep 1
done
if [ "$mysql_ready" != "1" ]; then
	echo "Timed out waiting for MySQL" >&2
	docker compose logs --tail=40 db >&2
	exit 1
fi

echo "Resetting the $DB_NAME database..."
docker compose exec -T db mysql -u root -proot <<SQL
DROP DATABASE IF EXISTS \`$DB_NAME\`;
CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL ON \`$DB_NAME\`.* TO 'ucm'@'%';
FLUSH PRIVILEGES;
SQL

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
