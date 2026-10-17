#!/usr/bin/env python3
"""
Check the Checkout Session request UCM builds against Stripe's own published schema.

Stripe publishes its API as OpenAPI. The relevant fragment is vendored next to this file
so the check needs no network and cannot start failing because a download went wrong -
and so the version it was taken against is recorded rather than implied.

This proves every field we send exists, every required field is supplied, and every value
we use for a fixed-choice field is one Stripe allows. It does not prove Stripe accepts the
request: that needs a key, and check-stripe-subscription.sh does it.

    validate-session-args.py < payload.json
"""
import json
import pathlib
import sys

SCHEMA = pathlib.Path(__file__).with_name("checkout-session-schema.json")


def check(payload, schema, path, problems):
    props = schema.get("properties", {})
    for key, value in payload.items():
        if key not in props:
            problems.append(f"{path}{key} is not a parameter Stripe accepts here")
            continue
        node = props[key]
        enum = node.get("enum")
        if enum and not isinstance(value, (dict, list)) and str(value) not in [str(e) for e in enum]:
            problems.append(f"{path}{key} is {value!r}, but Stripe allows only {enum}")
        if isinstance(value, dict) and node.get("properties"):
            check(value, node, f"{path}{key}.", problems)
        if isinstance(value, list) and node.get("items"):
            for i, item in enumerate(value):
                if isinstance(item, dict):
                    check(item, {"properties": node["items"], "required": node.get("item_required")}, f"{path}{key}[{i}].", problems)

    # Only this level's own required list. The nested ones are reached by the recursion
    # above, and checking them here as well reported the same missing field twice.
    for required in schema.get("required") or []:
        if required not in payload:
            problems.append(f"{path}{required} is required and was not sent")


def main():
    schema = json.loads(SCHEMA.read_text())
    payload = json.load(sys.stdin)

    problems = []
    check(payload, schema, "", problems)

    print(f"Stripe schema version {schema['spec_version']}, captured {schema['captured']}")
    print(f"Checking {len(payload)} parameters against {schema['path']}")

    if problems:
        print("\nFAIL")
        for problem in problems:
            print(f"  {problem}")
        return 1

    print("\nPASS: every parameter exists, every required one is present, every fixed choice is allowed.")
    print("This does not prove Stripe accepts the request - run check-stripe-subscription.sh with a key for that.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
