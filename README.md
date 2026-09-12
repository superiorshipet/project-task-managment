# Taskari Project Management

A Laravel MVC monolith for a modern project and task management workflow. It uses MySQL, Blade, Tailwind CSS, Eloquent relationships, validation Form Requests, Policies, Middleware, file uploads, soft deletes, and a lightweight JSON API.

## Requirements

- PHP 8.3+
- Composer
- Node.js 20+ for the current local build, preferably Node.js 22+ to match all package engine warnings
- MySQL
- PHP `pdo_mysql` extension
- Redis server for production cache

## Setup

```bash
composer install
npm install --ignore-scripts
cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
npm run build
php artisan serve
```

Update `.env` with the real MySQL host, database, username, and password. Do not commit `.env`.

The project intentionally keeps only the domain tables in MySQL:

```text
users
projects
tasks
migrations
```

Sessions use files, queues run synchronously, and cache uses Redis:

```env
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
CACHE_STORE=redis
REDIS_CLIENT=predis
```

## Cloudflare R2

File uploads use the configured default filesystem disk. Local development can keep:

```env
FILESYSTEM_DISK=public
```

For Cloudflare R2, fill the R2 credentials and switch:

```env
FILESYSTEM_DISK=r2
CLOUDFLARE_R2_ACCESS_KEY_ID=
CLOUDFLARE_R2_SECRET_ACCESS_KEY=
CLOUDFLARE_R2_BUCKET=
CLOUDFLARE_R2_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
CLOUDFLARE_R2_URL=https://<public-bucket-domain>
```

## Mail

The default sender address is:

```env
MAIL_FROM_ADDRESS=superiorshipet@gmail.com
```

Use real SMTP credentials in `.env` before sending emails from production.

## Cloudflare Turnstile

Turnstile is wired for login and registration, but disabled by default until a real domain is ready:

```env
TURNSTILE_ENABLED=false
TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=
```

Set `TURNSTILE_ENABLED=true` after creating a Cloudflare Turnstile widget for the production domain.

## Search

Project and task search checks normal columns and JSON metadata tags/labels, so keywords like `design`, `backend`, `qa`, and `workflow` are searchable.

Search is optimized through a denormalized `search_text` column with MySQL FULLTEXT indexes. JSON metadata remains the source of structured tags/labels, while `search_text` is rebuilt automatically when projects or tasks are saved.

The app also keeps repeated workspace lookups in Redis, eager-loads board relations, selects only the columns needed for board lists, and prevents accidental Eloquent lazy loading during local development.

Seeded admin account:

```text
Email: admin@taskari.test
Password: password
```

## API

The API uses HTTP Basic authentication.

```bash
curl -u admin@taskari.test:password http://127.0.0.1:8000/api/tasks
curl -X PATCH -u admin@taskari.test:password \
  -H "Content-Type: application/json" \
  -d '{"status":"completed"}' \
  http://127.0.0.1:8000/api/tasks/1/status
```

## Suggested Commit Plan

1. `chore: scaffold laravel project`
2. `feat: add auth roles and domain models`
3. `feat: implement project and task CRUD`
4. `feat: build dashboard and kanban UI`
5. `feat: add task JSON API`
6. `test: align feature tests with auth flow`
7. `docs: document setup and API usage`
