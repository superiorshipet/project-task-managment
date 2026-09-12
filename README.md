# Tasharuky Project Hub

Tasharuky is a Laravel project and task management workspace with role-based project ownership, Kanban task boards, team invitations, project files, project chat with mentions, realtime notifications, and a collaborative whiteboard.

## Stack

- Laravel 13
- PHP 8.3+
- MySQL
- Redis cache via Predis
- Blade + Tailwind CSS + Vite
- Laravel Reverb + Echo for realtime updates
- Cloudflare R2 compatible file storage
- SMTP email for password reset and workspace mail
- Cloudflare Turnstile support for auth hardening

## Main Features

- Admin, project manager, and user roles
- Dashboard with project/task/team summaries
- Project CRUD, soft delete, restore, and favorites
- Kanban task board with drag-and-drop status changes
- Multi-assignee tasks
- Fast client-side board search and filters
- Project files tab for task attachments
- Project chat with `@mention` autocomplete
- Realtime chat delivery through WebSockets
- Realtime notification bell and notification center
- Project invitation flow
- Team management:
  - Admin can remove users from the system or from projects
  - Project managers can remove users from their projects
- Collaborative project whiteboard
- Password reset by email
- JSON task API protected by HTTP Basic auth

## Requirements

- PHP 8.3+
- Composer
- Node.js 20+; Node.js 22+ avoids package engine warnings
- MySQL with `pdo_mysql`
- Redis for cache
- SMTP credentials for real email delivery

## Local Setup

```bash
composer install
npm install --ignore-scripts
cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
npm run build
```

Update `.env` with your real database, mail, Redis, and storage values. Never commit `.env`.

## Run Locally

Run the Laravel app:

```bash
php artisan serve
```

Run the realtime WebSocket server in a second terminal:

```bash
php artisan reverb:start --debug
```

Default local Reverb values:

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=taskari-local
REVERB_APP_KEY=taskari-local-key
REVERB_APP_SECRET=taskari-local-secret
REVERB_HOST=localhost
REVERB_PORT=8081
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8081
REVERB_SCHEME=http
```

If the browser logs `Firefox can’t establish a connection to ws://localhost:8081`, Reverb is not running or the port does not match `.env`.

## Useful Commands

```bash
npm run build
php artisan test --compact
vendor/bin/pint --dirty --format agent
php artisan config:clear
```

## Seeded Accounts

All seeded accounts use:

```text
Password: password
```

Default admin:

```text
Email: admin@taskari.test
```

The database seeder also creates project managers, users, sample projects, and tasks for local testing.

## Database Notes

The project uses domain tables for the workspace plus a few required support tables:

- `users`
- `projects`
- `project_members`
- `project_favorites`
- `project_invitations`
- `project_messages`
- `project_whiteboards`
- `tasks`
- `task_assignees`
- `workspace_notifications`
- `password_reset_tokens`

Sessions use files, queues are synchronous by default, and Redis is used for cache:

```env
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
CACHE_STORE=redis
REDIS_CLIENT=predis
```

## Realtime Behavior

Realtime features use Laravel Reverb private channels:

- `projects.{id}` for project chat messages
- `users.{id}` for notification delivery

Chat sending is optimistic in the UI: the message appears immediately while the database write completes in the background. If a request fails, the message card is marked `Not sent`.

Polling remains as a light fallback, so the app still works if Reverb is temporarily offline.

## Cloudflare R2

Local development can use the public disk:

```env
FILESYSTEM_DISK=public
```

For Cloudflare R2:

```env
FILESYSTEM_DISK=r2
CLOUDFLARE_R2_ACCESS_KEY_ID=
CLOUDFLARE_R2_SECRET_ACCESS_KEY=
CLOUDFLARE_R2_BUCKET=
CLOUDFLARE_R2_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
CLOUDFLARE_R2_URL=https://<public-bucket-domain>
CLOUDFLARE_R2_REGION=auto
```

## Mail

Password reset and workspace mail use SMTP. The default sender is:

```env
MAIL_FROM_ADDRESS=superiorshipet@gmail.com
```

Add the real app password only in `.env`.

## Cloudflare Turnstile

Turnstile is wired for login and registration, but can stay disabled locally:

```env
TURNSTILE_ENABLED=false
TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=
```

Enable it after creating a widget for the production domain.

## Search And Performance

Project and task search use searchable columns plus denormalized `search_text` fields and MySQL FULLTEXT indexes. Board filtering is instant on the client first, with server refreshes used only where needed.

The app also:

- eager-loads board relations
- selects compact board columns
- caches repeated workspace lookups in Redis
- prevents accidental lazy loading during local development
- uses nonblocking chat sends for a faster UX

## API

The JSON API uses HTTP Basic authentication.

```bash
curl -u admin@taskari.test:password http://127.0.0.1:8000/api/tasks
curl -X PATCH -u admin@taskari.test:password \
  -H "Content-Type: application/json" \
  -d '{"status":"completed"}' \
  http://127.0.0.1:8000/api/tasks/1/status
```
