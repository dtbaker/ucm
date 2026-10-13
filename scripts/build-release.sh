#!/usr/bin/env bash
#
# Build the item archive in the layout CodeCanyon expects.
#
#   ./scripts/build-release.sh            # writes build/ucm-codecanyon.zip
#   ./scripts/build-release.sh --verify   # build, then check it is shaped correctly
#
# The zip must not have the application at its root. It needs a wrapper of three folders:
#
#   Main Files/     the product itself
#   Documentation/  buyer documentation, as HTML or PDF
#   Licensing/      the licence of everything bundled inside the product
#
# "Main Files" is produced with `git archive`, so only committed files go in and the
# export-ignore list in .gitattributes decides what stays out. Anything lying around in
# the working copy - node_modules, vendor, a stray config.local.php, yesterday's
# playwright report - cannot leak in by accident.

set -euo pipefail

cd "$(dirname "$0")/.."

BUILD_DIR="build"
ZIP_NAME="ucm-codecanyon.zip"
MAIN="$BUILD_DIR/Main Files"
DOCS="$BUILD_DIR/Documentation"
LICENSING="$BUILD_DIR/Licensing"

rm -rf "$BUILD_DIR"
mkdir -p "$MAIN" "$DOCS" "$LICENSING"

echo "Assembling Main Files from the current commit..."
# --worktree-attributes so the .gitattributes being edited right now is the one that
# decides, rather than the copy in the last commit.
git archive --worktree-attributes HEAD | tar -x -C "$MAIN"

echo "Installing the PHP dependencies buyers need..."
# Buyers get vendor/ pre-built. Composer is a developer tool: most of them are on shared
# hosting with no shell, and the modern Stripe SDK is a hard requirement for taking a
# Stripe payment at all - it has no bundled fallback, because the 2014 copy still on disk
# speaks an API Stripe no longer accepts. Shipping vendor/ is what keeps that feature
# working out of the box.
#
# --no-dev so the test and analysis tooling stays out, and an authoritative classmap so
# the autoloader never scans the filesystem at runtime.
# composer.json and composer.lock are export-ignored, so they are not in the archive we
# just unpacked. Copy them in, install against them, then take them back out - buyers get
# the built vendor/ without the manifest that would invite them to rebuild it.
cp composer.json composer.lock "$MAIN/"
composer install --no-dev --no-interaction \
	--no-progress --optimize-autoloader --classmap-authoritative \
	--working-dir="$MAIN"
rm -f "$MAIN/composer.json" "$MAIN/composer.lock"

echo "Assembling Documentation..."
# Written by hand and kept in the repository, the same as Licensing, so that a change to
# what buyers read shows up in a diff and goes through review like any other change.
cp -R Documentation/. "$DOCS/"

echo "Assembling Licensing..."
# The Licensing folder is written by hand and lives in the repository, so that the
# licence of every bundled component is reviewable in a diff rather than guessed at by a
# find(1) that can only see the components which happened to ship a licence file.
cp -R Licensing/. "$LICENSING/"

# third-party-libraries.tsv is the single source of truth. The web page is generated
# from it at build time so the two can never disagree.
echo "Rendering the licence manifest..."
awk -F'\t' '
	function esc(s) {
		gsub( /&/, "\\&amp;", s ); gsub( /</, "\\&lt;", s ); gsub( />/, "\\&gt;", s )
		return s
	}
	BEGIN {
		print "<!doctype html>"
		print "<html lang=\"en\">"
		print "<head>"
		print "<meta charset=\"utf-8\">"
		print "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">"
		print "<title>Ultimate Client Manager &mdash; Third party libraries</title>"
		print "<style>"
		print "body{font:15px/1.6 -apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Arial,sans-serif;color:#222;background:#fff;margin:0;padding:2rem 1rem 4rem}"
		print "main{max-width:62rem;margin:0 auto}"
		print "h1{font-size:1.6rem;margin:0 0 .25rem}"
		print "p.lead{color:#555;margin:0 0 1.5rem}"
		print ".wrap{overflow-x:auto;border:1px solid #e3e3e3;border-radius:6px}"
		print "table{border-collapse:collapse;width:100%;font-size:.88rem}"
		print "th,td{text-align:left;vertical-align:top;padding:.55rem .7rem;border-bottom:1px solid #eee}"
		print "th{background:#fafafa;font-weight:600;white-space:nowrap;position:sticky;top:0}"
		print "tr:last-child td{border-bottom:0}"
		print "code{font:12px/1.5 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;background:#f5f5f5;padding:.1rem .3rem;border-radius:3px;word-break:break-all}"
		print "a{color:#15609b}"
		print ".note{margin-top:1.5rem;padding:.9rem 1rem;border-left:4px solid #d08a00;background:#fffaf0}"
		print "</style>"
		print "</head>"
		print "<body><main>"
		print "<h1>Third party libraries</h1>"
	}
	NR == 1 { next }
	NF < 8 { next }
	{
		rows++
		if ( $5 == "licenses/REVIEWED.txt" ) { reviewed++ }
		body = body "<tr><td>" esc($2) "</td><td>" esc($3) "</td><td>" esc($4)
		body = body "</td><td><a href=\"" esc($5) "\">" esc($5) "</a></td>"
		body = body "<td>" esc($6) "</td>"
		if ( $7 == "unknown" ) { body = body "<td>unknown</td>" }
		else { body = body "<td><a href=\"" esc($7) "\">" esc($7) "</a></td>" }
		body = body "<td><code>" esc($8) "</code></td></tr>\n"
	}
	END {
		print "<p class=\"lead\">" rows " components are bundled inside <code>Main Files</code>. " \
			reviewed " of them ship without a licence file or licence header; each of those was " \
			"reviewed individually and " \
			"<a href=\"licenses/REVIEWED.txt\">licenses/REVIEWED.txt</a> records the outcome.</p>"
		print "<div class=\"wrap\"><table>"
		print "<thead><tr><th>Component</th><th>Version</th><th>Licence</th><th>Licence text</th>" \
			"<th>Copyright</th><th>Upstream</th><th>Path inside Main Files</th></tr></thead>"
		printf "<tbody>\n%s</tbody>", body
		print "</table></div>"
		print "<p class=\"note\">Every component keeps its original copyright header inside the shipped" \
			" source file. <a href=\"README.txt\">README.txt</a> explains how the Envato licence and" \
			" these licences fit together.</p>"
		print "</main></body></html>"
	}
' "$LICENSING/third-party-libraries.tsv" > "$LICENSING/third-party-libraries.html"

echo "Zipping..."
( cd "$BUILD_DIR" && zip -qr "$ZIP_NAME" "Main Files" Documentation Licensing )

echo "Built $BUILD_DIR/$ZIP_NAME ($(du -h "$BUILD_DIR/$ZIP_NAME" | cut -f1))"

if [ "${1:-}" != "--verify" ]; then
	exit 0
fi

echo
echo "Verifying..."
listing=$(unzip -Z1 "$BUILD_DIR/$ZIP_NAME")
fail=0

for folder in "Main Files/" "Documentation/" "Licensing/"; do
	if ! grep -q "^$folder" <<<"$listing"; then
		echo "FAIL: the archive has no $folder"
		fail=1
	fi
done

# Nothing may sit at the root except those three folders.
stray=$(sed -E 's#/.*##' <<<"$listing" | sort -u | grep -vxE 'Main Files|Documentation|Licensing' || true)
if [ -n "$stray" ]; then
	echo "FAIL: these are at the root of the archive and should not be:"
	sed 's/^/       /' <<<"$stray"
	fail=1
fi

# Development files, listed one by one rather than matched by extension, because the
# bundled libraries legitimately carry their own composer.json and .travis.yml.
for dir in \
	'Main Files/.github/' 'Main Files/.git/' 'Main Files/tests/' 'Main Files/docker/' \
	'Main Files/docs/' 'Main Files/scripts/' 'Main Files/node_modules/' \
	'Main Files/docs-site/' 'Main Files/website/' \
	'Main Files/build/' 'Main Files/playwright-report/' 'Main Files/test-results/'
do
	if grep -qF "$dir" <<<"$listing"; then
		echo "FAIL: development directory in the archive: $dir"
		fail=1
	fi
done

for file in \
	'Main Files/composer.json' 'Main Files/composer.lock' \
	'Main Files/package.json' 'Main Files/package-lock.json' \
	'Main Files/playwright.config.ts' 'Main Files/phpcs.xml.dist' \
	'Main Files/phpstan.neon' 'Main Files/phpstan-baseline.neon' \
	'Main Files/phpunit.xml' 'Main Files/rector.php' \
	'Main Files/Dockerfile' 'Main Files/docker-compose.yml' 'Main Files/.dockerignore' \
	'Main Files/.gitignore' 'Main Files/.gitattributes' \
	'Main Files/includes/config.local.php' 'Main Files/README.md' 'Main Files/TESTING.md'
do
	if grep -qxF "$file" <<<"$listing"; then
		echo "FAIL: development file in the archive: $file"
		fail=1
	fi
done

# The dependencies buyers cannot install themselves. Stripe is the one that bites: it has
# no bundled fallback, so an archive without vendor/ ships a payment method that cannot
# take a payment, and nothing else in the build would notice.
for required in \
	'Main Files/vendor/autoload.php' \
	'Main Files/vendor/stripe/stripe-php/init.php'
do
	if ! grep -qxF "$required" <<<"$listing"; then
		echo "FAIL: the archive is missing $required"
		echo "      Buyers cannot run composer; vendor/ has to be built into the archive."
		fail=1
	fi
done

# ...but the development half of vendor/ must not come with it.
if grep -qE '^Main Files/vendor/(phpunit|phpstan|squizlabs|friendsofphp)/' <<<"$listing"; then
	echo "FAIL: development dependencies in the archive - build vendor/ with --no-dev"
	fail=1
fi

# Documentation has to be readable without a developer toolchain.
if ! grep -qiE '^Documentation/.+\.(html?|pdf)$' <<<"$listing"; then
	echo "FAIL: Documentation must contain an HTML or PDF file"
	fail=1
fi

# Licensing has to be real. A manifest that lists a licence file which is not in the
# archive is worse than no manifest, because it reads as a promise that was kept.
for path in 'Licensing/README.txt' 'Licensing/third-party-libraries.tsv' \
	'Licensing/third-party-libraries.html'
do
	if ! grep -qxF "$path" <<<"$listing"; then
		echo "FAIL: the archive needs $path"
		fail=1
	fi
done

if [ -f "$LICENSING/third-party-libraries.tsv" ]; then
	entries=$(( $(wc -l < "$LICENSING/third-party-libraries.tsv") - 1 ))
	if [ "$entries" -lt 20 ]; then
		echo "FAIL: the licence manifest lists only $entries components; the product bundles far more"
		fail=1
	fi

	# Every licence text the manifest points at must exist...
	while IFS=$'\t' read -r _ name _ _ licence_file _ _ bundled_path; do
		if [ -n "$licence_file" ] && [ ! -f "$LICENSING/$licence_file" ]; then
			echo "FAIL: $name points at $licence_file and that file is not in Licensing/"
			fail=1
		fi
		# ...and every component it describes must still be in the product. A library that
		# has been deleted since the manifest was written is a stale claim, not a missing
		# licence, so say so loudly without failing a build that is otherwise correct.
		if [ -n "$bundled_path" ] && [ ! -e "$MAIN/$bundled_path" ]; then
			echo "STALE: the manifest lists $name at $bundled_path, which is no longer in the product."
			echo "       Remove its row from Licensing/third-party-libraries.tsv."
		fi
	done < <(tail -n +2 "$LICENSING/third-party-libraries.tsv")
fi

# Documentation and Licensing must not still be the scaffolding. These phrases were in
# the placeholders this script used to generate; if any of them survives into a release,
# the folder was never filled in.
placeholders=$(grep -rilE 'not written yet|INCOMPLETE\.|lorem ipsum|TODO|FIXME|XXX' \
	"$DOCS" "$LICENSING" 2>/dev/null | grep -v '/licenses/' || true)
if [ -n "$placeholders" ]; then
	echo "FAIL: placeholder text is still in the archive:"
	sed "s#^$BUILD_DIR/#       #" <<<"$placeholders"
	fail=1
fi

# A documentation folder with one thin page passes the "is there an HTML file" check
# below while telling a buyer nothing. CodeCanyon asks for every feature to be explained.
doc_pages=$(grep -cE '^Documentation/.+\.html?$' <<<"$listing" || true)
if [ "$doc_pages" -lt 5 ]; then
	echo "FAIL: Documentation has $doc_pages HTML pages; that is not buyer documentation"
	fail=1
fi

# The things the product needs at runtime that are easy to lose to an exclusion rule.
for path in 'Main Files/index.php' 'Main Files/init.php' 'Main Files/temp/.htaccess' \
	'Main Files/includes/config.php'
do
	if ! grep -qF "$path" <<<"$listing"; then
		echo "FAIL: the product needs $path and it is not in the archive"
		fail=1
	fi
done

if [ "$fail" = "0" ]; then
	echo "OK: $(wc -l <<<"$listing") entries, shaped the way CodeCanyon wants."
fi
exit $fail
