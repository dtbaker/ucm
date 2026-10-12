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

echo "Assembling Documentation..."
cat > "$DOCS/index.html" <<'HTML'
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Ultimate Client Manager &mdash; Documentation</title>
	<style>
		body { font: 16px/1.6 -apple-system, "Segoe UI", Arial, sans-serif; margin: 3rem auto; max-width: 42rem; padding: 0 1rem; }
		code { background: #f4f4f4; padding: 0.1rem 0.3rem; }
		.todo { border-left: 4px solid #c00; background: #fff6f6; padding: 0.75rem 1rem; }
	</style>
</head>
<body>
<h1>Ultimate Client Manager</h1>

<p class="todo">
	<strong>This documentation is not written yet.</strong>
	CodeCanyon requires an HTML or PDF help file that explains every feature of the item.
	This file exists so the packaging build has the right shape; it is not yet fit to
	ship. See <code>docs/proposals/codecanyon-standards.md</code>, requirements D1 to D5.
</p>

<h2>Installing</h2>
<ol>
	<li>Upload the contents of <code>Main Files</code> to your web host.</li>
	<li>Create an empty MySQL database and a user with access to it.</li>
	<li>Open the address you uploaded to in a browser and follow the setup steps.</li>
</ol>

<h2>Upgrading</h2>
<p>See <code>UPGRADE.md</code> inside <code>Main Files</code>.</p>

<h2>Support</h2>
<p>Through the item's comments page on CodeCanyon.</p>
</body>
</html>
HTML

echo "Assembling Licensing..."
# Collect the licence files that genuinely exist in the tree, so this folder cannot drift
# away from what is actually bundled.
found=0
while IFS= read -r licence; do
	target="$LICENSING/$(dirname "${licence#"$MAIN"/}" | tr '/' '_')-$(basename "$licence")"
	cp "$licence" "$target"
	found=$((found + 1))
done < <(find "$MAIN" \
	\( -iname 'LICENSE' -o -iname 'LICENSE.txt' -o -iname 'LICENCE' -o -iname 'license.txt' \
	   -o -iname 'COPYING' -o -iname 'COPYING.*' \) -type f)

cat > "$LICENSING/README.txt" <<EOF
Licensing
=========

This folder holds the licence of every third party component shipped inside
"Main Files". $found licence file(s) were found in the product and copied here by
scripts/build-release.sh.

INCOMPLETE. The product bundles roughly twenty-five third party libraries and only a
handful of them carry a licence file. Two are unresolved and must be settled before the
item is submitted:

  * includes/plugin_calendar/wdCalendar/  - no licence file and no licence header
  * includes/plugin_file/js/plupload*     - header says GPL, which needs the GPL text here

See docs/proposals/codecanyon-standards.md, section 2.2, for the full list and the
reasoning. That file is not shipped; it is in the source repository.
EOF

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
	'Main Files/docs/' 'Main Files/scripts/' 'Main Files/node_modules/' 'Main Files/vendor/' \
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

# Documentation has to be readable without a developer toolchain.
if ! grep -qiE '^Documentation/.+\.(html?|pdf)$' <<<"$listing"; then
	echo "FAIL: Documentation must contain an HTML or PDF file"
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
