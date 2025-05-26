# Release Strategy  
Last updated: 20/04/2025  
[Wechsle zur Deutschen Version](Releasestrategy.md)

We follow a simple yet clearly structured versioning and release strategy based on [Semantic Versioning (SemVer)](https://semver.org/lang/en/).

Our version numbers follow the format:

```
MAJOR.MINOR.PATCH
e.g. 5.0.3
```

Releases are **not scheduled**, but rather **triggered as needed**, once a sufficient number of pull requests (PRs) have been created, tested, and merged.

---

## PATCH Release (e.g. `4.0.1`)

### Purpose:
Fixes minor bugs **without** introducing new features or changing existing behaviour.

### Rules:
- Changes are made exclusively in the `master` branch.
- A release is made once one or more relevant bugfixes have been merged.
- **No change to existing behaviour.**

### Examples:
- Fixes a UI issue
- Corrects an invalid validation check
- Resolves a crash in a specific scenario

---

## MINOR Release (e.g. `4.1.0`)

### Purpose:
Introduces **new features** that are **backward compatible**.

### Rules:
- Feature development takes place in a dedicated branch such as `4.1.0`.
- Before the release, all relevant bugfixes from `master` are **selectively** merged into the feature branch.
- After the release, the `master` branch is updated with the state of the feature branch.

### Examples:
- New components or functionality
- Improved user experience, new APIs
- Internal optimisations that do not affect external behaviour

---

## MAJOR Release (e.g. `5.0.0`)

### Purpose:
Introduces significant changes that **may break backward compatibility**.

### Rules:
- Each major release receives its own branch (`5.0.0`, `6.0.0`, etc.).
- **Breaking changes** are allowed (e.g. altered behaviour, removed features).
- Legacy APIs or behaviours may be removed or replaced.

### Examples:
- Migration to a completely new architecture
- Removal of deprecated functionality
- Changes to the data model or API contract

---

## Release Timing

There is **no fixed schedule** for releases.  
A release occurs when:

- A meaningful number of PRs (bugfixes or features) have been merged,
- All changes have been thoroughly tested,
- The branch structure is clean and properly prepared.

---

## Pre-Release Checklist

- [ ] All relevant PRs have been merged
- [ ] Branch is clean and up to date
- [ ] Version is tagged using Git (e.g. `v4.1.0`)
- [ ] Changelog has been updated (if applicable)
