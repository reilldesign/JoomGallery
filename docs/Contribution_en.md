## Contributing to the Project – Bugfixes & Features  
Last updated: 20/04/2025  
[Wechsle zur Deutschen Version](Contribution.md)

Thank you for your interest in contributing to our project!  
To keep our releases consistent and stable, we follow a structured branching strategy. Please follow the instructions below depending on whether you're contributing a **bugfix** or a **new feature**.

---

### Contributing a Bugfix

**Goal:** Bugfixes should always be made against the `master` branch.  
The `master` branch is used for bugfix releases (e.g. `4.0.1`, `4.0.2`).

#### Steps:

1. Fork the repository (if you haven’t already).
2. Create a new branch from `master`:
   ```bash
   git checkout master
   git pull origin master
   git checkout -b fix/<short-description>
   ```
3. Implement the bugfix.
4. Write a meaningful commit message (e.g. `Fix: fixes PHP error during upload`).
5. Open a pull request **against the `master` branch**.
6. Optional: If you know the fix is also relevant for an upcoming feature branch (e.g. `4.1.0`), please mention it in the PR description.

---

### Contributing a Feature

**Goal:** New features should **not** be added to `master`, but to a dedicated branch for the next minor release (e.g. `4.1.0`).

#### Steps:

1. Fork the repository (if you haven’t already).
2. Create a new branch from `4.1.0`:
   ```bash
   git checkout 4.1.0
   git pull origin 4.1.0
   git checkout -b feat/<short-description>
   ```
3. Implement your feature.
4. Write a meaningful commit message (e.g. `Feat: adds dark mode`).
5. Open a pull request **against the `4.1.0` branch**.

---

### Branch Naming Conventions

Please name your branches using the following format:

| Type       | Prefix       | Example                    | Description |
|------------|--------------|----------------------------|-------------|
| **Bugfix**   | `fix/`       | `fix/login-error`           | For bugfixes |
| **Feature**  | `feat/`      | `feat/user-profile-page`    | For new features |
| **Refactor** | `refactor/`  | `refactor/form-validation`  | For internal code changes without altering functionality |
| **Chore**    | `chore/`     | `chore/update-dependencies` | For maintenance tasks such as dependencies, linting, build tools etc. |

This naming convention helps us identify the purpose of branches at a glance.

---

### Notes on the Branching Model

- `master`: For bugfixes only (bugfix releases)
- `4.1.0`, `4.2.0`, ...: Feature development for upcoming minor versions
- Bugfixes are selectively merged from `master` into feature branches – you don’t need to worry about this as a contributor.
- Please **double-check which branch you are targeting** with your PR – pull requests to the wrong branch will delay review.

---

### Pre-PR Checklist

- [ ] Test your code locally
- [ ] Ensure the correct branch is used (`master` for bugfixes, `4.1.0` for features)
- [ ] Fill out the PR description
- [ ] Add relevant PR tags (e.g. `needs testing`)

---

Thanks for contributing 🙌  
If you have any questions, feel free to open a GitHub Issue or comment on the pull request!
