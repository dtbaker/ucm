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
#   Documentation/  readme.html: getting started, linking to the live documentation
#   Licensing/      LICENSE (MIT), THIRD_PARTY_NOTICES.md and LICENSES/, from the repository root
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

echo "Writing the release file list..."
# Every file in Main Files, this list included. The update server builds the old one-file
# installer's zip from it (ucm_upgrade_server::release_manifest), so that zip and this one
# cannot drift apart: the server used to keep its own list of folders, and it fell behind
# the product without anyone noticing. Written last, so vendor/ is in it. The path has to
# match ucm_upgrade_server::RELEASE_MANIFEST.
MANIFEST="includes/release-files.txt"
{
	echo "# Every file in this release. Written by scripts/build-release.sh; the update server"
	echo "# builds its install zip from it. Do not edit."
	{ ( cd "$MAIN" && find . -type f | sed 's#^\./##' ); echo "$MANIFEST"; } | LC_ALL=C sort
} > "$BUILD_DIR/release-files.txt"
mv "$BUILD_DIR/release-files.txt" "$MAIN/$MANIFEST"

echo "Assembling Documentation..."
# One short getting-started page, written by hand. The full documentation lives at
# https://ultimateclientmanager.com/docs/ (source: the ucm-web repository) and the page
# links to it, so the download never carries a second copy that drifts out of date.
cp -R Documentation/. "$DOCS/"

echo "Assembling Licensing..."
# The product is MIT licensed. LICENSE (ours), THIRD_PARTY_NOTICES.md (what is bundled and
# under which licence) and LICENSES/ (the full texts) live at the repository root, where
# GitHub and everyone else look for them; the CodeCanyon archive gets the same three files
# in its Licensing folder.
cp LICENSE "$LICENSING/LICENSE.txt"
cp THIRD_PARTY_NOTICES.md "$LICENSING/THIRD_PARTY_NOTICES.md"
cp -R LICENSES "$LICENSING/LICENSES"

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
	'Main Files/build/' 'Main Files/playwright-report/' 'Main Files/test-results/' \
	'Main Files/custom/'
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
	'Main Files/phpunit.xml' \
	'Main Files/Dockerfile' 'Main Files/docker-compose.yml' 'Main Files/.dockerignore' \
	'Main Files/.gitignore' 'Main Files/.gitattributes' \
	'Main Files/includes/config.local.php' 'Main Files/README.md' 'Main Files/TESTING.md' \
	'Main Files/SECURITY.md' 'Main Files/UPGRADE.md' \
	'Main Files/includes/config.local.php.example'
do
	if grep -qxF "$file" <<<"$listing"; then
		echo "FAIL: development file in the archive: $file"
		fail=1
	fi
done

# The dependencies buyers cannot install themselves. None has a bundled fallback: an archive
# without vendor/ ships a payment method that cannot take a payment, PDFs that cannot be
# made and email that cannot be sent, and nothing else in the build would notice. dompdf's
# fonts are checked too, because it cannot draw a page without them.
for required in \
	'Main Files/vendor/autoload.php' \
	'Main Files/vendor/stripe/stripe-php/init.php' \
	'Main Files/vendor/dompdf/dompdf/src/Dompdf.php' \
	'Main Files/vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf' \
	'Main Files/vendor/phpmailer/phpmailer/src/PHPMailer.php'
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

# Licensing has to be real: our licence, the notices, and a text for every licence the
# notices name. A notice that points at a licence text which is not there reads as a
# promise that was kept.
for path in 'Licensing/LICENSE.txt' 'Licensing/THIRD_PARTY_NOTICES.md'; do
	if ! grep -qxF "$path" <<<"$listing"; then
		echo "FAIL: the archive needs $path"
		fail=1
	fi
done
if ! grep -q '^MIT License' "$LICENSING/LICENSE.txt" 2>/dev/null; then
	echo "FAIL: Licensing/LICENSE.txt is not the MIT licence"
	fail=1
fi
if [ -f "$LICENSING/THIRD_PARTY_NOTICES.md" ]; then
	while IFS= read -r text; do
		if [ ! -f "$LICENSING/$text" ]; then
			echo "FAIL: THIRD_PARTY_NOTICES.md links $text and it is not in the archive"
			fail=1
		fi
	done < <(grep -oE '\(LICENSES/[^)]+\)' "$LICENSING/THIRD_PARTY_NOTICES.md" | tr -d '()' | sort -u)
	# Every component the notices describe must still be in the product. A library that
	# has been deleted since is a stale claim rather than a missing licence, so say so
	# loudly without failing a build that is otherwise correct.
	while IFS= read -r bundled_path; do
		if [ ! -e "$MAIN/$bundled_path" ]; then
			echo "STALE: THIRD_PARTY_NOTICES.md lists $bundled_path, which is no longer in the product."
		fi
	done < <(grep -oE '\| `[^`]+` \|$' "$LICENSING/THIRD_PARTY_NOTICES.md" | sed -E 's/^\| `//; s/` \|$//')
fi

# Documentation and Licensing must not still be the scaffolding. These phrases were in
# the placeholders this script used to generate; if any of them survives into a release,
# the folder was never filled in.
placeholders=$(grep -rilE 'not written yet|INCOMPLETE\.|lorem ipsum|TODO|FIXME|XXX' \
	"$DOCS" "$LICENSING" 2>/dev/null | grep -v '/LICENSES/' || true)
if [ -n "$placeholders" ]; then
	echo "FAIL: placeholder text is still in the archive:"
	sed "s#^$BUILD_DIR/#       #" <<<"$placeholders"
	fail=1
fi

# Documentation is the getting-started page and nothing else: the full documentation is
# online, and a copy here would drift out of date. The page has to exist and has to send
# people to the live documentation, which is the point of keeping it short.
if ! grep -qxF 'Documentation/readme.html' <<<"$listing"; then
	echo "FAIL: Documentation/readme.html is missing"
	fail=1
elif ! grep -qF 'https://ultimateclientmanager.com/docs/' "$DOCS/readme.html"; then
	echo "FAIL: Documentation/readme.html does not link to https://ultimateclientmanager.com/docs/"
	fail=1
fi
extra_docs=$(grep -E '^Documentation/.' <<<"$listing" | grep -vxF 'Documentation/readme.html' || true)
if [ -n "$extra_docs" ]; then
	echo "FAIL: Documentation should hold only readme.html; the full docs live online. Also found:"
	sed 's/^/       /' <<<"$extra_docs"
	fail=1
fi

# The release file list must be this archive's Main Files, exactly. The update server
# trusts it to say what an install is made of.
listed=$(grep -v '^#' "$MAIN/$MANIFEST" | LC_ALL=C sort)
archived=$(grep '^Main Files/' <<<"$listing" | grep -v '/$' | sed 's#^Main Files/##' | LC_ALL=C sort)
if [ "$listed" != "$archived" ]; then
	echo "FAIL: $MANIFEST does not match the files in Main Files:"
	diff <(echo "$listed") <(echo "$archived") | sed 's/^/       /' | head -20 || true
	fail=1
fi

# The things the product needs at runtime that are easy to lose to an exclusion rule.
for path in 'Main Files/index.php' 'Main Files/init.php' 'Main Files/temp/.htaccess' \
	'Main Files/includes/config.php' "Main Files/$MANIFEST"
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
