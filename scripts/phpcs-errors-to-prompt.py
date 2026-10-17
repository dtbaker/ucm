#!/usr/bin/env python3
"""
Read PHPCS JSON output from stdin and emit a prompt-friendly format for AI agents.
Usage: ./vendor/bin/phpcs --report=json -q 2>/dev/null | python3 scripts/phpcs-errors-to-prompt.py
"""

import argparse
import json
import sys
from pathlib import Path


def main() -> None:
    parser = argparse.ArgumentParser(description="Convert PHPCS JSON to AI prompt format")
    parser.add_argument(
        "--summary",
        action="store_true",
        help="Print only a summary (file count, error/warning counts)",
    )
    parser.add_argument(
        "--fixable-only",
        action="store_true",
        help="Include only fixable issues (for phpcbf)",
    )
    parser.add_argument(
        "--non-fixable-only",
        action="store_true",
        help="Include only non-fixable issues (for manual/AI fixes)",
    )
    parser.add_argument(
        "--errors-only",
        action="store_true",
        help="Include only errors, not warnings",
    )
    parser.add_argument(
        "--include-source",
        action="store_true",
        help="Include sniff source (e.g. PSR2.ControlStructures.SwitchDeclaration)",
    )
    args = parser.parse_args()

    try:
        data = json.load(sys.stdin)
    except json.JSONDecodeError as e:
        print(f"Error: Invalid JSON from phpcs: {e}", file=sys.stderr)
        sys.exit(1)

    totals = data.get("totals", {})
    files = data.get("files", {})

    if args.summary:
        print(f"Files with issues: {len(files)}")
        print(f"Total errors: {totals.get('errors', 0)}")
        print(f"Total warnings: {totals.get('warnings', 0)}")
        print(f"Fixable: {totals.get('fixable', 0)}")
        return

    # Build output grouped by file
    for filepath, file_data in sorted(files.items()):
        messages = file_data.get("messages", [])
        if not messages:
            continue

        # Filter by type
        if args.errors_only:
            messages = [m for m in messages if m.get("type") == "ERROR"]

        # Filter by fixable
        if args.fixable_only:
            messages = [m for m in messages if m.get("fixable")]
        elif args.non_fixable_only:
            messages = [m for m in messages if not m.get("fixable")]

        if not messages:
            continue

        # Use relative path if under cwd
        try:
            rel_path = Path(filepath).relative_to(Path.cwd())
        except ValueError:
            rel_path = Path(filepath)

        print(f"\nFile: {rel_path}")
        for m in sorted(messages, key=lambda x: (x.get("line", 0), x.get("column", 0))):
            line = m.get("line", "?")
            msg = m.get("message", "")
            typ = m.get("type", "")
            fixable = " [fixable]" if m.get("fixable") else ""
            source = f" ({m.get('source', '')})" if args.include_source else ""
            print(f"  - Line {line}: [{typ}]{fixable} {msg}{source}")


if __name__ == "__main__":
    main()
