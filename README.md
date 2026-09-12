# Taskari Project Management

A Laravel MVC monolith for a modern project and task management workflow. It uses MySQL, Blade, Tailwind CSS, Eloquent relationships, validation Form Requests, Policies, Middleware, file uploads, soft deletes, and a lightweight JSON API.

## Requirements

- PHP 8.3+
- Composer
- Node.js 20+ for the current local build, preferably Node.js 22+ to match all package engine warnings
- MySQL
- PHP `pdo_mysql` extension

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
