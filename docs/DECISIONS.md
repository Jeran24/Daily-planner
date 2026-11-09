# Decisions

## Phase 0

- **Use the official Laravel 13 Livewire starter kit.** The brief calls for Blade, Livewire, Tailwind, Alpine, authentication, password reset, and two-factor. Laravel's official Livewire kit is maintained for Laravel 13 and uses Laravel Fortify for authentication. Alternative: install Fortify and build the authentication shell manually.
- **Use XAMPP MySQL locally.** The brief initially names SQLite for development, but the owner later explicitly selected XAMPP MySQL at `127.0.0.1` with user `planner`. The database name is `daily_planner`; the owner later updated that account's password, which is stored only in ignored `.env`. PHPUnit remains isolated on in-memory SQLite so automated tests do not touch local MySQL. Alternative: follow the original SQLite development default.
- **Use a separate Git metadata directory.** The workspace already had an empty `.git/` directory, but the execution permissions make it read-only. Git metadata is kept in ignored `.git-meta/`, and `scripts/git.sh` supplies the required Git environment variables. Alternative: change the existing `.git/` directory permissions, which this workspace does not allow.
- **Omit Larastan/PHPStan from the starter dependencies.** Its 30 MB PHPStan archive repeatedly timed out on the available package connection. PHPUnit and Laravel Pint remain installed; the PHPStan static-analysis command is omitted. Alternative: keep retrying the large archive download.
- **Keep the default Livewire starter dependencies.** The official starter installs Laravel Fortify, Livewire, Flux, Blaze, and Laravel's session/auth scaffolding. No AI feature or AI SDK is installed as an application feature.
- **Use the system font stack and avoid remote font downloads.** The design brief specifies system fonts. The starter's Bunny font plugin tried to download Instrument Sans during the build, so it was removed. Alternative: bundle a local font file or fetch a hosted font.
- **Keep Laravel's app clock and persisted timestamps in UTC; default the owner's display timezone to Africa/Kigali.** This avoids timezone shifts in database timestamps while showing the owner local time. Alternative: set Laravel's global timezone to the owner's zone, which would require careful timestamp normalization on every write.

## Phase 1

- **Use the first registered account as the sole owner.** Registration becomes unavailable once a user exists, with a matching server-side check. Alternative: admin-managed invites, which are outside this personal app's scope.
- **Require passwords of at least 12 characters and keep the existing Fortify login throttling.** Failed login responses remain generic. Alternative: the starter kit's shorter default password rule.
- **Store timezone, week start, theme, and time format directly on `users`.** These are per-owner preferences with explicit defaults. Alternative: put them in a general settings table; reserve that table for the key/value settings in the brief.
- **Disable email verification.** This is a single-user local app, and the owner can use password reset without a third-party mail service. Alternative: require verified email, which would block local sign-in until mail delivery is configured.
- **Use PHP's built-in non-parallel Pint check in the test script.** Parallel Pint cannot bind local sockets in this environment. Alternative: run it in parallel, which fails before linting.
- **Remove the starter's Larastan/PHPStan dependency and check.** Its large archive could not be fetched reliably. Alternative: keep retrying the download; PHPUnit and Pint remain in the full test command.

## Phase 2

- **Keep task timestamps stored in UTC and convert to the owner's timezone for display and reminder calculations.** Laravel's global timezone is UTC so database date-times round-trip without shifting. Alternative: run the whole app clock in Africa/Kigali, which caused UTC values to be reinterpreted as local time when read back.
- **Use Sunday as weekday zero and store each routine's selected weekdays as JSON.** This maps directly to the brief's Sunday-first defaults and supports SQLite/MySQL without database-specific SQL. Alternative: use a bit mask, which is compact but less readable.
- **Soft-delete routines and remove only future task logs on deletion.** Completed/past logs remain available for history. Alternative: hard-delete the routine, which cascades away its task logs.
- **Seed defaults on registration and on the first Today visit only when an account has never had a routine.** This also initializes an owner account created during Phase 1. Alternative: add a manual setup command; no extra command is needed for the common upgrade path.
- **The reminder timestamp calculation is tested now; browser notification and push delivery are in Phase 6.** The app currently has no reminder event table or delivery worker. Alternative: implement notifications before their planned phase.

## Phase 3

- **Store one encrypted journal body and mood per owner and local date.** Laravel's built-in encrypted cast handles the text; no encryption package was added. Alternative: encrypt the whole database, which would not protect exported or copied database files at the application layer.
- **The calendar only displays saved logs; it does not reconstruct missing past schedules from today's routines.** It marks pending logs missed when a past day is opened. Alternative: backfill historical routines, which could falsely attribute current routines to days before they were created or edited.
- **Run one idempotent planner tick every minute.** It creates today's missing logs and finalizes yesterday for each owner's timezone, aligning with the midnight rule without requiring a separate job per timezone. Alternative: one daily scheduled event, which would need a fixed server timezone.
- **Keep demo credentials out of tracked files.** The demo password is read from ignored `.env` or generated at runtime and printed once. The demo command is blocked in production. Alternative: commit a fixed example password, which would violate the no-password-in-Git rule.
- **Allow the local demo owner alongside an existing owner, but keep data scoped by user ID.** This lets an existing local database preview demo screens without changing the owner's routines. Alternative: refuse whenever any user exists, which would make the requested preview command unusable after account setup.

## Phase 4

- **Calculate goal progress from completed logs only.** A completed task with no entered minutes contributes its planned duration; skipped, pending, missed, and minimum statuses do not count as a completed task. Alternative: treat a minimum completion as a full task, which would overstate progress.
- **Use daily per-goal snapshots for the feasibility pace estimate.** The scheduled local-day tick records the prior day's progress; the current actual progress is compared with the latest usable snapshot from the past 14 days. Alternative: infer daily changes only from task events, which cannot measure manual goals.
- **Use the date span from start through deadline for the plan marker, with the start date at 0% and the deadline at 100%.** This makes an inclusive deadline a full target. Alternative: count both calendar dates as elapsed days, which would put a goal above 0% on its first day.
- **Soft-delete goals and clear their linked routines' goal IDs.** The goal stops tracking future work while task logs remain. Alternative: hard-delete the goal and cascade away its checklist and pace history.
- **No new package was added for goals or progress.** The calculations are in `app/Services/GoalProgressService.php`. Alternative: install a progress/reporting package, which is unnecessary for these four simple measures.

## Phase 6

- **Use `laravel-notification-channels/webpush` 13.0.1 for Web Push.** Its package metadata requires Laravel 13.13+ and the app is on Laravel 13.17; it includes VAPID support, subscription storage, and expired-subscription cleanup. VAPID keys are generated locally and kept only in ignored `.env`. Alternative: write and maintain the Web Push encryption/VAPID implementation ourselves.
- **Keep push reminders synchronous inside the once-per-minute scheduler command.** The reminder-event unique key prevents duplicate sends and tests verify completed tasks are excluded. Alternative: add a queue job per reminder, which would require additional queued/sent state to avoid duplicates.
- **Do not cache authenticated pages for offline mode.** The app is installable and the service worker handles push notifications and clicks; it deliberately leaves private planner pages off the cache. Alternative: add an offline data store, which risks retaining journal and task notes on a device.
