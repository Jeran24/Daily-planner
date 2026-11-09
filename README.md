# Daily Planner

Daily Planner is a personal Laravel web app. Its visual reference is `daily-planner-mockup.html`; the project brief defines product behavior and scope.

## Local setup

Requirements: PHP 8.3+, Composer, Node.js and npm, and XAMPP MySQL running at `127.0.0.1:3306`. Create an empty MySQL database named `daily_planner` and grant access to the `planner` user. The local `.env` is ignored by Git and holds the account password and VAPID keys.

```bash
composer install
npm ci
php artisan key:generate
php artisan migrate
npm run dev
```

In another terminal, run `php artisan serve`. Open <http://127.0.0.1:8000>.

For automated tests, PHPUnit uses a separate in-memory SQLite database, so tests do not touch the local MySQL database:

```bash
php artisan test
```

## Project documentation

- `docs/NOTES.md` explains folders and the setup, test, and deployment workflow.
- `docs/DECISIONS.md` records decisions and deviations from the brief.
- `docs/DEPLOY.md` will contain deployment guidance when Phase 7 is reached.
- `docs/REPORT.md` will be written at the end of the requested works.
