#!/usr/bin/env bash
#
# Look for the traces three bugs fixed this release would have left in a live database.
#
# All three are read-only. Nothing is written, nothing is deleted, and the script can be
# run on a production database as it stands - though taking a backup first is never wrong.
#
#   ./scripts/check-live-data.sh                     # the development database
#   ./scripts/check-live-data.sh -h HOST -u USER -p  # somewhere else
#
# Anything it finds is historical: the bugs themselves are fixed, so nothing new will be
# recorded. What it cannot do is repair the rows, because only you can say what the
# correct figure should have been.
set -euo pipefail

PREFIX="${UCM_DB_PREFIX:-ucm_}"

if [ "$#" -gt 0 ]; then
	MYSQL=(mysql "$@")
else
	if ! docker compose ps 2>/dev/null | grep ucm-db | grep -q 'Up '; then
		echo "The database container is not running. Start it with: docker compose up -d" >&2
		echo "Or pass connection details: $0 -h HOST -u USER -p DATABASE" >&2
		exit 69
	fi
	MYSQL=(docker compose exec -T db mysql -uucm -pucm ucm)
fi

# grep exits non-zero when a query returns nothing, which under set -e would end the
# script at the first clean result - the opposite of what you want from a check like this.
run() {
	local out
	out="$( "${MYSQL[@]}" -t -e "$1" 2>/dev/null | grep -v '^mysql:' || true )"
	if [ -z "$out" ]; then
		echo "  Nothing found."
	else
		echo "$out"
	fi
}

echo "=============================================================="
echo " 1. Payments recorded for nothing"
echo "=============================================================="
echo "A gateway renewal used to email the invoice and stop before recording the payment,"
echo "leaving a payment row of 0.00 against an invoice the customer had already been"
echo "charged for. Any rows here are invoices that may have been paid but still look unpaid."
echo
run "
SELECT p.invoice_payment_id, p.invoice_id, p.amount, p.method, p.date_paid, i.name AS invoice
FROM ${PREFIX}invoice_payment p
LEFT JOIN ${PREFIX}invoice i ON i.invoice_id = p.invoice_id
WHERE p.amount = 0
  AND ( p.date_paid IS NULL OR p.date_paid = '0000-00-00' )
  AND p.invoice_payment_subscription_id > 0
ORDER BY p.date_created DESC
LIMIT 50;"

echo
echo "=============================================================="
echo " 2. Invoices raised twice for the same thing"
echo "=============================================================="
echo "Two scheduled runs overlapping used to bill a subscription twice, producing two"
echo "invoices for the same customer, the same period and often the same number."
echo
echo "Reading it: a count of exactly 2 is the shape this bug makes. Larger counts are"
echo "usually something else - test data, or a name you reuse deliberately - so look at"
echo "the pairs first."
echo
run "
SELECT name, customer_id, COUNT(*) AS copies, GROUP_CONCAT(invoice_id) AS invoice_ids, MIN(date_create) AS first_seen
FROM ${PREFIX}invoice
WHERE name IS NOT NULL AND name <> ''
GROUP BY name, customer_id, date_create
HAVING copies > 1
ORDER BY first_seen DESC
LIMIT 50;"

echo
echo "=============================================================="
echo " 3. Customer credit that may have been overwritten"
echo "=============================================================="
echo "Adding credit used to read the balance through a cache, so a second adjustment within"
echo "the cache lifetime replaced the first instead of adding to it. A balance here is not"
echo "proof of a loss - it is where to look. Each customer's own notes record what was"
echo "actually assigned, which is what makes the correct figure recoverable."
echo
run "
SELECT customer_id, customer_name, credit
FROM ${PREFIX}customer
WHERE credit IS NOT NULL AND credit <> 0
ORDER BY credit DESC
LIMIT 50;"

echo
echo "Done. Nothing was changed."
