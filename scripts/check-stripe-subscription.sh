#!/usr/bin/env bash
#
# Ask Stripe whether it accepts the subscription request UCM actually sends.
#
# The app's own request is rejected at authentication, which happens before Stripe reads
# the body - so without a key there is no way to know whether the body is acceptable. This
# builds the real request through the same method the app uses and posts it with a key you
# supply.
#
# Nothing is charged. A Checkout Session is a URL until somebody pays on it, and no
# customer is involved.
#
#   ./scripts/check-stripe-subscription.sh sk_test_...
#
set -euo pipefail

KEY="${1:-}"
OFFLINE_ONLY=0
if [ -z "$KEY" ]; then
	# No key is not an error. Without one the request can still be checked against
	# Stripe's own published schema, which is most of the answer and needs no account.
	OFFLINE_ONLY=1
	echo "No key given - checking the request against Stripe's published schema instead."
	echo "To also ask Stripe whether it accepts the request:"
	echo "  $0 sk_test_YOUR_SECRET_KEY"
	echo "The secret key, not the publishable one: https://dashboard.stripe.com/test/apikeys"
	echo
fi

if [ "$OFFLINE_ONLY" = "0" ]; then
case "$KEY" in
	sk_test_*) ;;
	sk_live_*) echo "NOTE: that is a live key. Nothing will be charged, but a test key is the safer choice." >&2 ;;
	*) echo "That does not look like a Stripe secret key (expected sk_test_... or sk_live_...)." >&2; exit 64 ;;
esac
fi

# Not `ps --status running`: that flag is not in every compose version, and when it is
# missing the command fails and the check silently decides nothing is running.
if ! docker compose ps 2>/dev/null | grep ucm-app | grep -q 'Up '; then
	echo "The app container is not running. Start it with: docker compose up -d" >&2
	exit 69
fi

echo "Building the request the app would send..."
PAYLOAD="$(docker compose exec -T app php -r '
$_SERVER["HTTP_HOST"] = "localhost:8090";
$_SERVER["REQUEST_URI"] = "/";
$_SERVER["SCRIPT_NAME"] = "/index.php";
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
chdir( "/var/www/html" );
ob_start();
require_once "init.php";
ob_end_clean();

// A representative monthly subscription. The numbers are arbitrary; the shape is not.
$args = module_paymethod_stripe::subscription_session_args(
	1, 49.95, 1, array( "currency_id" => 1 ), "Subscription check",
	array( "id" => 1, "name" => "Subscription check", "period" => array( "interval" => "month", "interval_count" => 1 ) ),
	1, array()
);
echo json_encode( $args );
' 2>/dev/null)"

if [ -z "$PAYLOAD" ] || [ "${PAYLOAD:0:1}" != "{" ]; then
	echo "Could not build the request. Is the app installed and the database up?" >&2
	exit 70
fi

echo "Checking the request against Stripe's published schema..."
if ! printf '%s' "$PAYLOAD" | "$(dirname "$0")/stripe/validate-session-args.py"; then
	echo >&2
	echo "The request does not match Stripe's schema. Fix that before asking Stripe." >&2
	exit 1
fi

if [ "$OFFLINE_ONLY" = "1" ]; then
	echo
	echo "Schema check passed. Re-run with a key to have Stripe confirm it."
	exit 0
fi

echo
echo "Asking Stripe..." 
# Stripe's API takes form encoding, so the JSON is flattened into bracketed keys here
# rather than in PHP, to keep the payload above exactly what the app produces.
FORM="$(printf '%s' "$PAYLOAD" | python3 -c '
import json, sys, urllib.parse
def flatten(prefix, value, out):
    if isinstance(value, dict):
        for k, v in value.items():
            flatten(f"{prefix}[{k}]" if prefix else k, v, out)
    elif isinstance(value, list):
        for i, v in enumerate(value):
            flatten(f"{prefix}[{i}]", v, out)
    else:
        out.append((prefix, "" if value is None else str(value)))
out = []
flatten("", json.load(sys.stdin), out)
print(urllib.parse.urlencode(out))
')"

RESPONSE="$(curl -sS -o /tmp/stripe-check-body.json -w '%{http_code}' \
	https://api.stripe.com/v1/checkout/sessions \
	-u "$KEY:" \
	--data-raw "$FORM" || true)"

echo
if [ "$RESPONSE" = "200" ]; then
	echo "PASS: Stripe opened the session. The subscription request is accepted as sent."
	python3 -c '
import json
d = json.load(open("/tmp/stripe-check-body.json"))
print("  session:  ", d.get("id"))
print("  mode:     ", d.get("mode"))
print("  amount:   ", d.get("amount_total"), d.get("currency"))
print("  status:   ", d.get("status"))
' 2>/dev/null || true
	echo
	echo "Nothing was charged. The session will expire on its own."
	rm -f /tmp/stripe-check-body.json
	exit 0
fi

echo "FAIL: Stripe returned HTTP $RESPONSE. Its own message says which field it objected to:"
python3 -c '
import json
try:
    e = json.load(open("/tmp/stripe-check-body.json")).get("error", {})
    for k in ("type", "code", "param", "message"):
        if e.get(k):
            print(f"  {k}: {e[k]}")
except Exception:
    print(open("/tmp/stripe-check-body.json").read()[:500])
' 2>/dev/null || cat /tmp/stripe-check-body.json
echo
echo "If the message names a field, that field is built in"
echo "module_paymethod_stripe::subscription_session_args()."
rm -f /tmp/stripe-check-body.json
exit 1
