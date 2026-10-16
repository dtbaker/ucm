#!/usr/bin/env bash
#
# Prove the fieldset conversions did not change any form's fields.
#
#   ./scripts/demo-mode.sh on
#   ./scripts/fieldset-parity.sh
#
# For each converted page this puts back the markup from before the conversion and runs
# the characterisation specs against it. Those specs assert an exact list of controls, so
# a run that passes against BOTH the old markup and the new one is saying the two render
# the same fields - which is the thing the conversion had to preserve.
#
# Some pages are expected to fail, and only on a named assertion:
#
#   encrypt_popup, recurring_edit   the row class their scripts switch on. It does not
#                                   exist in the old markup, which used a <tbody id>.
#   newsletter_queue                a trim() deprecation fixed alongside the conversion.
#   newsletter_member               the fatal that fixed alongside it.
#
# Any other failure means a conversion changed a form's fields. Any page that passes when
# it is listed below as expected-to-fail means that assertion stopped being load-bearing.

set -uo pipefail

cd "$(dirname "$0")/.."

# The commit before the first fieldset conversion.
BASE="404ca03^"

PAGES=(
	includes/plugin_data/pages/data_type_admin_field_open.php
	includes/plugin_import_export/pages/import.php
	includes/plugin_newsletter/pages/newsletter_send.php
	includes/plugin_file/pages/file_customer_edit.php
	includes/plugin_ticket/public/ticket_customer_new.php
	includes/plugin_setup/pages/setup1.php
	includes/plugin_theme/themes/whitelabel1/pages/login.php
	includes/plugin_encrypt/pages/encrypt_popup.php
	includes/plugin_newsletter/pages/newsletter_member.php
	includes/plugin_newsletter/pages/newsletter_queue.php
	includes/plugin_newsletter/pages/newsletter_queue_watch.php
	includes/plugin_ticket/public/ticket_customer_view.php
	includes/plugin_invoice/pages/invoice_admin_edit.php
	includes/plugin_finance/pages/recurring_edit.php
	includes/plugin_newsletter/pages/newsletter_edit.php
	includes/plugin_newsletter/pages/newsletter_template_edit.php
	includes/plugin_data/pages/admin_data_search.php
	includes/plugin_ticket/pages/ticket_admin_edit.php
	includes/plugin_note/pages/note_edit.php
	includes/plugin_ticket/pages/ticket_admin_edit_messages.php
	includes/plugin_address/pages/address_block.php
)

# Pages whose specs cannot pass against the old markup, and the one assertion each is
# allowed to fail on. A case rather than an associative array: macOS ships bash 3.2, which
# has none, and silently read the declare -A as arithmetic - so the check did nothing and
# the script still said everything passed.
expected_failure() {
	case "$1" in
		encrypt_popup.php) echo "rows need their class on the row" ;;
		recurring_edit.php) echo "the class the toggle hides" ;;
		newsletter_queue.php) echo "printed a PHP diagnostic" ;;
		newsletter_member.php) echo "should not be dying" ;;
		ticket_admin_edit_messages.php) echo "the class the button reveals" ;;
		*) echo "" ;;
	esac
}

if [ -n "$(git status --porcelain -- "${PAGES[@]}")" ]; then
	echo "Working tree has changes to the pages under test. Commit or stash first." >&2
	exit 1
fi

problems=0
for page in "${PAGES[@]}"; do
	name=$(basename "$page")
	git show "$BASE:$page" > "$page"
	# opcache.revalidate_freq is 2 in the container, so a request made immediately after
	# writing the file can still be served from the old bytecode.
	sleep 3

	# One worker. Run in parallel this reported two pages as changed that had not: the
	# fixtures here share records, and a race between them fails an assertion that has
	# nothing to do with the page being tested. A verification script that cries wolf is
	# worse than a slow one.
	out=$(UCM_DEMO=1 npx playwright test tests/e2e/fieldsets.spec.ts tests/e2e/demo-mode.spec.ts \
		--workers=1 --reporter=list 2>&1)
	status=$?
	git checkout -- "$page"

	expected=$(expected_failure "$name")
	if [ "$status" -eq 0 ]; then
		if [ -n "$expected" ]; then
			echo "PROBLEM  $name passed on the old markup, but is listed as failing on: $expected"
			problems=$((problems + 1))
		else
			echo "same fields  $name"
		fi
	else
		if [ -n "$expected" ] && grep -qF "$expected" <<<"$out"; then
			echo "same fields  $name  (failed only on: $expected)"
		else
			echo "PROBLEM  $name failed on something else:"
			grep -E '^\s+Error:' <<<"$out" | head -3
			problems=$((problems + 1))
		fi
	fi
done

echo
if [ "$problems" -eq 0 ]; then
	echo "All ${#PAGES[@]} converted pages render the same fields as before the conversion."
else
	echo "$problems page(s) need looking at." >&2
fi
exit $(( problems > 0 ? 1 : 0 ))
