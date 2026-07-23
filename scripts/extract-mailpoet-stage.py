#!/usr/bin/env python3
"""Extract MailPoet + CF7 tables from the prod dump into a staging SQL file.

Renames the four source tables to zimp_* so they can be loaded alongside the
live (newer-schema) MailPoet tables without clobbering them.
"""
import re
import sys

SRC = "reference/prod-db.sql"
OUT = "/tmp/mp-stage.sql"

TABLES = {
    "wp_mailpoet_subscribers": "zimp_subscribers",
    "wp_mailpoet_segments": "zimp_segments",
    "wp_mailpoet_subscriber_segment": "zimp_subscriber_segment",
    "wp_db7_forms": "zimp_db7_forms",
}

# Read bytes as latin-1 so every byte round-trips unchanged.
with open(SRC, "r", encoding="latin-1") as f:
    data = f.read()

# mysqldump escapes control chars in data, so ";\n" only terminates statements.
statements = data.split(";\n")

wanted = {}
for src, dst in TABLES.items():
    wanted[src] = {"create": None, "inserts": []}

create_re = {src: re.compile(r"^\s*CREATE TABLE `" + re.escape(src) + r"`") for src in TABLES}
insert_re = {src: re.compile(r"^\s*INSERT INTO `" + re.escape(src) + r"`") for src in TABLES}

for stmt in statements:
    head = stmt[:200]
    for src in TABLES:
        if create_re[src].search(head):
            wanted[src]["create"] = stmt
        elif insert_re[src].search(head):
            wanted[src]["inserts"].append(stmt)

out = ["SET FOREIGN_KEY_CHECKS=0;", "SET sql_mode='';"]
for src, dst in TABLES.items():
    entry = wanted[src]
    if not entry["create"]:
        sys.stderr.write(f"WARN: no CREATE for {src}\n")
        continue
    out.append(f"DROP TABLE IF EXISTS `{dst}`;")
    create = entry["create"].replace(f"`{src}`", f"`{dst}`", 1)
    # Drop any generated-column / constraint noise is unnecessary here.
    out.append(create + ";")
    n = 0
    for ins in entry["inserts"]:
        out.append(ins.replace(f"`{src}`", f"`{dst}`", 1) + ";")
        n += ins.count("),(") + 1
    sys.stderr.write(f"{src} -> {dst}: ~{n} rows in {len(entry['inserts'])} insert(s)\n")

out.append("SET FOREIGN_KEY_CHECKS=1;")

with open(OUT, "w", encoding="latin-1") as f:
    f.write("\n".join(out) + "\n")

sys.stderr.write(f"Wrote {OUT}\n")
