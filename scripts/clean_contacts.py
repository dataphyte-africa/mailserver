from __future__ import annotations

import csv
import json
import re
import sys
from collections import Counter, defaultdict
from pathlib import Path


EMAIL_RE = re.compile(r"^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$", re.I)
PLACEHOLDER_VALUES = {
    "",
    "user",
    "name",
    "firstname",
    "lastname",
    "first name",
    "last name",
    "email",
    "nil",
    "none",
    "n/a",
    "na",
    "unknown",
}
SUSPICIOUS_NAME_PARTS = {"eavesdrop", "by", "email", "name", "first", "last"}


def normalize_space(value: str) -> str:
    return " ".join(value.replace("\ufeff", "").strip().split())


def is_placeholder(value: str) -> bool:
    return normalize_space(value).casefold() in PLACEHOLDER_VALUES


def clean_name(value: str) -> str:
    value = normalize_space(value)
    if not value:
        return ""
    value = value.strip(".,;:|/_\\-")
    value = normalize_space(value)
    if is_placeholder(value):
        return ""

    words = []
    for chunk in value.split():
        if not chunk:
            continue
        parts = re.split(r"([-'])", chunk)
        cleaned_parts = []
        for part in parts:
            if part in {"-", "'"}:
                cleaned_parts.append(part)
            else:
                part = part.strip(".")
                cleaned_parts.append(part[:1].upper() + part[1:].lower() if part else "")
        words.append("".join(cleaned_parts))
    return " ".join(words).strip()


def clean_preference(value: str) -> str:
    value = normalize_space(value)
    if is_placeholder(value):
        return ""
    return value


def name_quality(value: str) -> int:
    if not value:
        return 0
    score = 0
    if len(value) > 1:
        score += 1
    if re.search(r"[A-Za-z]", value):
        score += 1
    lowered = value.casefold()
    if not any(part in lowered.split() for part in SUSPICIOUS_NAME_PARTS):
        score += 1
    return score


def row_score(row: dict[str, str]) -> tuple[int, int, int]:
    first = clean_name(row.get("firstname", ""))
    last = clean_name(row.get("lastname", ""))
    preference = clean_preference(row.get("preference", ""))
    score = name_quality(first) + name_quality(last)
    if preference:
        score += 1
    completeness = int(bool(first)) + int(bool(last)) + int(bool(preference))
    alpha_chars = len(re.sub(r"[^A-Za-z]", "", first + last))
    return score, completeness, alpha_chars


def choose_best_row(rows: list[dict[str, str]]) -> dict[str, str]:
    best = max(rows, key=row_score)
    first = clean_name(best.get("firstname", ""))
    last = clean_name(best.get("lastname", ""))
    preference = clean_preference(best.get("preference", ""))

    if not first:
        for row in rows:
            candidate = clean_name(row.get("firstname", ""))
            if candidate:
                first = candidate
                break
    if not last:
        for row in rows:
            candidate = clean_name(row.get("lastname", ""))
            if candidate:
                last = candidate
                break
    if not preference:
        for row in rows:
            candidate = clean_preference(row.get("preference", ""))
            if candidate:
                preference = candidate
                break

    return {
        "email": normalize_space(best.get("email", "")).lower(),
        "firstname": first,
        "lastname": last,
        "preference": preference,
    }


def main() -> int:
    if len(sys.argv) != 3:
        print("Usage: clean_contacts.py INPUT.csv OUTPUT.csv", file=sys.stderr)
        return 1

    input_path = Path(sys.argv[1])
    output_path = Path(sys.argv[2])
    summary_path = output_path.with_suffix(".summary.json")

    with input_path.open(newline="", encoding="utf-8-sig") as handle:
        source_rows = list(csv.DictReader(handle))

    grouped: dict[str, list[dict[str, str]]] = defaultdict(list)
    invalid_rows = 0
    for row in source_rows:
        email = normalize_space(row.get("email", "")).lower()
        if not email or not EMAIL_RE.match(email):
            invalid_rows += 1
            continue
        grouped[email].append(row)

    cleaned_rows = []
    duplicate_rows_removed = 0
    placeholder_names_cleared = 0
    for email, rows in grouped.items():
        duplicate_rows_removed += max(len(rows) - 1, 0)
        cleaned = choose_best_row(rows)
        for field in ("firstname", "lastname"):
            original_values = [normalize_space(row.get(field, "")) for row in rows]
            if any(is_placeholder(value) for value in original_values if value) and not cleaned[field]:
                placeholder_names_cleared += 1
        cleaned_rows.append(cleaned)

    cleaned_rows.sort(key=lambda row: row["email"])
    output_path.parent.mkdir(parents=True, exist_ok=True)
    with output_path.open("w", newline="", encoding="utf-8") as handle:
        writer = csv.DictWriter(handle, fieldnames=["email", "firstname", "lastname", "preference"])
        writer.writeheader()
        writer.writerows(cleaned_rows)

    summary = {
        "input_rows": len(source_rows),
        "output_rows": len(cleaned_rows),
        "invalid_email_rows_removed": invalid_rows,
        "duplicate_rows_removed": duplicate_rows_removed,
        "rows_with_placeholder_names_retained_blank": placeholder_names_cleared,
        "preference_values": Counter(row["preference"] for row in cleaned_rows),
    }
    summary["preference_values"] = dict(summary["preference_values"])
    summary_path.write_text(json.dumps(summary, indent=2, ensure_ascii=False), encoding="utf-8")
    print(json.dumps(summary, indent=2, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
