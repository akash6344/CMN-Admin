# CMNHousing Admin Portal

Laravel 13 admin portal for CMNHousing. Server-rendered Blade pages, session login, SQLite by default.
Admin reads data from the other CMNHousing apps (builder portal, user website, operations, agent
portal and live payments). See [Connected apps](#connected-apps).

## Requirements

- PHP 8.3+ with `pdo_sqlite` (and `pdo_mysql` if you point any connection at MySQL)
- Composer

## Setup

```sh
cp .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Open <http://127.0.0.1:8000/login> and sign in with the seeded account:

- Email: `admin@cmnhousing.test`
- Password: `password`

Change or remove this account before deploying.

## Tests

```sh
php artisan test
```

The tests do not need the other apps or any database file: each test builds the tables it needs in
in-memory SQLite (see `tests/Concerns`). Run them first to confirm your setup works.

## Connected apps

Admin's own database holds users, sessions, blogs and similar. Most pages also read, and some
update, other apps' databases through these connections (`config/database.php`):

| Connection  | App                                             | Used for                                                     | Default location                            |
|-------------|-------------------------------------------------|--------------------------------------------------------------|---------------------------------------------|
| `housing`   | Builder portal (CMN-housing)                    | projects, leads, support tickets (Complaints), admin inbox   | `../CMN-housing/database/database.sqlite`   |
| `dashboard` | User website (CMN-Dashboard / `cmn_laravel_13`) | listings, property views, site visits, website notifications | `../CMN-Dashboard/database/database.sqlite` |
| `ops`       | Operations (CMN-Emp)                            | listing review trail (approve / raise a query)               | `../CMN-Emp/database/database.sqlite`       |
| `agent`     | Agent portal (CMN-Agent)                        | agents and agent earnings                                    | `../CMN-Agent/database/database.sqlite`     |
| `live`      | Live website payments                           | payment plans and transactions                               | `database/live.sqlite`                      |

Ways to run with real data, easiest first:

1. **Clone the other repos next to this one** (one parent folder containing `CMN-Admin`,
   `CMN-housing`, `CMN-Dashboard`, `CMN-Emp`, `CMN-Agent`) and run each one's migrations. The defaults
   then work with no `.env` changes.
2. **Use copies of their SQLite files** and set `HOUSING_DB_DATABASE`, `DASHBOARD_DB_DATABASE`,
   `OPS_DB_DATABASE` and `AGENT_DB_DATABASE` to absolute paths.
3. **Use MySQL**: set `AGENT_DB_HOST` / `LIVE_DB_HOST` and the related variables in `.env.example`.

Live payments without MySQL: import the phpMyAdmin dumps into `database/live.sqlite`:

```sh
php artisan live:import-dump payments_plans.sql payment_transactions.sql
```

The code checks which tables and columns exist before using them (`app/Support/WebsiteSchema.php`),
so a page whose table is missing shows an empty state instead of failing.

## Email

`MAIL_MAILER=log` (the default in `.env.example`) writes emails, such as password-reset links, to
`storage/logs/laravel.log`. To actually send email, set the SMTP values in `.env`; ask the team for
the credentials. Never commit `.env`.

## Pages

Dashboard · Users · Properties (review, approve, raise a query) · Leads & bargains · Complaints ·
Reports (with export) · Audit logs (with export) · Notifications · Payment plans · Payment
transactions (with export) · Agent earnings · Deletion requests · Locations · Blogs · Search ·
Settings · Profile · Forgot / reset password.

Routes are in `routes/web.php`. Everything except login and password reset requires an admin
account (`App\Http\Middleware\EnsureAdminRole`).

## Other portals

Admin links to the builder portal (`HOUSING_PUBLIC_URL`, default `http://localhost:8091`) and the
BRM portal (`BRM_URL`, default `http://localhost:8090`). Set these to wherever those apps run.
