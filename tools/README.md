# Core Blueprint Profiles release tooling

Profiles launch closure uses local CLI execution only. GitHub Actions are intentionally not part of the current launch gate.

## Prerequisites

Use PHP 8.4+, Python 3, WP-CLI with the i18n commands, GNU gettext (`msgmerge`, `msgattrib`, `msgfmt`), Node.js, `zip`, `unzip`, Git and `sha256sum`.

## Translation authority

English runtime source is authoritative. Reviewed translation sources live in the six PO catalogs for `nl_NL`, `de_DE`, `fr_FR`, `es_ES`, `it_IT` and `pt_PT`.

When translatable source deliberately changes, update catalogs with:

```bash
bash tools/i18n/update
```

Review and commit the resulting POT/PO diff. `tools/i18n/update` is the only mutating localization authority.

For the launch gate, run the read-only check:

```bash
bash tools/i18n/check
```

Profiles uses `commit_mo: false`. MO files are generated deterministically from the reviewed PO files inside release staging and are not source artifacts in Git.

## Conformance

Run:

```bash
php tools/conformance.php
```

Every `tests/*-regression.php` script runs in its own PHP process. This is required for the dependency-loss regression so it cannot inherit constants, hooks or test stubs from another regression.

## Release package

Build and validate the customer artifact with:

```bash
bash tools/build-release
```

The builder fails closed unless canonical localization, PHP/JavaScript syntax and the isolated regression suite pass. It generates the six MO files in staging, packages only customer runtime files under `core-blueprint-profiles/`, normalizes timestamps, validates the ZIP boundary and writes SHA-256 evidence only after archive acceptance.

For launch evidence, run the builder twice from the same exact candidate SHA and compare the resulting ZIP SHA-256 values. They must be identical before staging approval.

Do not bypass a failed release gate. Fix source or reviewed catalogs, keep the candidate SHA explicit, and rerun the complete local sequence.
