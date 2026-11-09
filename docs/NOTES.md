# Project notes

## Folders

- `app/` — application models, services, controllers, and providers.
- `app/Console/Commands/` — scheduled task generation, day closing, and local demo setup.
- `app/Services/` — task planning and business rules, kept out of screen controllers.
- `bootstrap/` — framework startup and application bootstrap.
- `config/` — Laravel and application configuration.
- `database/` — migrations, factories, and seeders.
- `public/` — web entry point and built public assets.
- `resources/` — Blade templates, CSS, and JavaScript source files.
- `routes/` — web and console route definitions.
- `storage/` — logs, cache, compiled views, and local files.
- `tests/` — PHPUnit feature and unit tests.
- `docs/` — project notes, decisions, deployment plan, and final report.

## Run locally

Use PHP 8.3+, Composer, Node.js and npm. Start XAMPP's MySQL server on `127.0.0.1:3306`; create an empty `daily_planner` database and grant access to user `planner`. The local `.env` stores that account's password and other secrets and is ignored by Git. PHPUnit uses an in-memory SQLite database and does not touch local MySQL.

```bash
composer install
npm ci
php artisan key:generate
php artisan migrate
npm run dev
```

In separate terminals run `php artisan serve`, `npm run dev`, `php artisan schedule:work`, and `php artisan queue:work`, then open <http://127.0.0.1:8000>. The scheduler creates each user's task logs, closes the previous day in that user's timezone, and sends due push reminders.

## Test

```bash
php artisan test
```

PHPUnit is configured to use a fresh in-memory SQLite database for tests. Tests therefore do not read or change the local MySQL database.

## Account and appearance

The first account is created from the login screen. Registration closes as soon as that owner exists. Passwords must be at least 12 characters. Log in at `/login`; authenticated users land on Today (`/`). Password reset uses Laravel's local mail log when `MAIL_MAILER=log`. Account preferences (timezone, first weekday, theme, and time format) are stored on the user record. The Appearance page is under Settings. The app shell uses a left rail above 700 px and a labeled bottom tab bar at narrower widths.

## Tasks and Today

`task_templates` stores each owner's routine and `task_logs` stores a daily UTC schedule snapshot and status. New owners receive the six default routines. An existing owner with no routines receives them on their first Today visit. Today generates missing logs once, supports done/minimum/skip/undo, and shows daily progress and the streak. Use `/tasks` to turn routines on or off, edit them, remove them from future days, or add a custom task. Past task log rows remain when a routine is edited or deleted. Task log notes use Laravel's encrypted cast. The business rules are in `app/Services/TaskPlannerService.php`, separately from the page controller.

## Calendar and journal

`/calendar` shows recorded monthly completion, with links into each day's saved task logs and journal. An empty past day stays empty; the app does not infer a former schedule from today's routines. Opening a past day changes any still-pending saved logs to missed. `/journal` stores one mood and encrypted note per local date. `php artisan demo:seed` adds a local demo account, six routines, 30 days of sample task history, six journal notes, and the three sample goals. It refuses production. Set `DEMO_PASSWORD` in ignored `.env` to choose a fixed local demo password; otherwise the command generates and prints one. The demo email is `demo.owner@example.test`.

## Goals

`/goals` supports checklist, linked task minutes, linked task count, and manual progress. Use `/goals/{id}/edit` to change dates and scope or check checklist items. Task routines can be linked to an active goal in their create/edit form. Goal progress uses the user's own task logs; the day scheduler stores daily progress snapshots for the recent-pace estimate. Deleting a goal soft-deletes it, stops future tracking, clears routine links, and retains task history.

## Projects and reports

`/goals/projects` tracks project name, description, newline-separated skills, status, priority, and encrypted private notes. Archived projects are soft-deleted and remain in exports. `/goals/reports?period=week` and `?period=month` summarize completion, best/worst recorded day, completed minutes by category, streak, and the average mood from that owner's journal entries. Settings now include reminder defaults, goal/calendar preferences, privacy lock timeout, visible database sessions, JSON/CSV export, and account deletion with a current-password check. The inactivity lock is session-based and checks authenticated app routes.

## Reminders and installable app

The authenticated app polls `/reminders/due` every minute while open. Due reminders show an in-page banner and chime; browser notifications appear only when permission is granted after clicking **Turn on reminders**. The same action registers this browser's Web Push subscription. `planner:send-reminders` runs every minute and sends one push for each pending task with a due reminder event. Generate VAPID keys with `php artisan webpush:vapid`; keep both keys and the subject in ignored `.env`. The PWA manifest and service worker are in `public/`. The worker does not cache authenticated pages or journal content. The Web Push channel package is `laravel-notification-channels/webpush` 13.0.1; its current metadata supports Laravel 13.13+ and it uses Laravel's HTTP client there.

## Deploy

Deployment has not started. Phase 7 is local only; hosting options and the exact deployment checklist will be written in `docs/DEPLOY.md`. No host or account will be selected or created. Configure `VAPID_SUBJECT`, `VAPID_PUBLIC_KEY`, and `VAPID_PRIVATE_KEY` on any future host; never copy them into Git.

## Git in this workspace

The workspace's pre-existing `.git/` directory is read-only in this environment. To keep commits and phase tags in the workspace without modifying it, Git metadata is stored in ignored `.git-meta/`. Use `scripts/git.sh` for Git commands, for example `scripts/git.sh status`, `scripts/git.sh commit`, and `scripts/git.sh tag phase-0`.
