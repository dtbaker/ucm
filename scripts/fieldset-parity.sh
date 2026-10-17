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
# This is also what "write the spec before changing the page" amounts to after the fact: a
# spec that passes against the old markup AND the new one is a spec that would have passed
# had it been written first. Where a spec was written after its page was converted, this is
# the check that makes the ordering moot.
#
# Some pages are expected to fail, and only on a named assertion:
#
#   encrypt_popup, recurring_edit   the row class their scripts switch on. It does not
#                                   exist in the old markup, which used a <tbody id>.
#   newsletter_queue                a trim() deprecation fixed alongside the conversion.
#   newsletter_member               the fatal that fixed alongside it.
#   customer_admin_edit             the empty cell in its read-only contact block. The old
#                                   markup captured the contact summary into an inner
#                                   ob_start() that only fed the responsive heading, so the
#                                   cell rendered empty.
#
# Any other failure means a conversion changed a form's fields. Any page that passes when
# it is listed below as expected-to-fail means that assertion stopped being load-bearing.
#
# The two renderers - includes/plugin_form/form.php and the metis copy - are deliberately
# NOT in these lists. Reverting one of them does not test a page: it withdraws the options
# (title_fields, title_attributes, row_attributes) that the converted pages now pass, so
# every one of those pages fails at once and the result says nothing about any of them.
# Those options are covered by removing each one in turn and watching its own spec fail,
# and tests/unit/FieldsetRenderersTest.php keeps the two renderers in step.

set -uo pipefail

cd "$(dirname "$0")/.."

# The commit before the first fieldset conversion.
BASE="404ca03^"

# The commit before the second pass, which converted what the first pass had left.
BASE_LATER="de9404f"

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

# Converted in the second pass, so their "before" is BASE_LATER.
#
# The three summary partials are here for completeness and will report no change: nothing
# renders them, which is why they are covered by scripts/render-partials.php instead - that
# diffs their text and their links across the conversion. A pass here only says the specs
# do not reach them.
PAGES_LATER=(
	includes/plugin_user/pages/user_summary.php
	includes/plugin_user/pages/contact_summary.php
	includes/plugin_customer/pages/customer_summary.php
	includes/plugin_newsletter/pages/newsletter_statistics.php
	includes/plugin_subscription/hooks/invoice_sidebar.php
	includes/plugin_subscription/hooks/member_edit.php
	includes/plugin_ticket/inc/ticket_priority_sidebar.php
	includes/plugin_ticket/ticket.php
	includes/plugin_user/pages/user_admin_edit_company.php
	includes/plugin_group/group.php
	includes/plugin_data/pages/render_group.php
	includes/plugin_customer/pages/customer_admin_edit.php
	includes/plugin_vendor/pages/vendor_admin_edit.php
	includes/plugin_faq/hooks/ticket_create.php
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
		customer_admin_edit.php) echo "the read-only contact block rendered an empty cell" ;;
		*) echo "" ;;
	esac
}

if [ -n "$(git status --porcelain -- "${PAGES[@]}" "${PAGES_LATER[@]}")" ]; then
	echo "Working tree has changes to the pages under test. Commit or stash first." >&2
	exit 1
fi

problems=0
checked=0
for entry in "${PAGES[@]/#/$BASE }" "${PAGES_LATER[@]/#/$BASE_LATER }"; do
	base=${entry%% *}
	page=${entry#* }
	name=$(basename "$page")
	checked=$((checked + 1))
	git show "$base:$page" > "$page"
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
	echo "All $checked converted pages render the same fields as before the conversion."
else
	echo "$problems page(s) need looking at." >&2
fi
exit $(( problems > 0 ? 1 : 0 ))
