# Tasharuky Project Hub

Tasharuky is a Laravel-based project and task management workspace for teams that need project ownership, task execution, file sharing, chat, notifications, and collaborative planning in one place. The product is built around a simple idea: project managers and admins control the workspace, while collaborators focus on the projects and tasks they are invited to.

The current project includes a responsive dashboard, Kanban task boards, project invitations, favorites, file previews, real-time chat and notifications, and a shared whiteboard for planning.

## Project Snapshot

- Product name: Tasharuky
- Type: Project and task management web application
- Backend: Laravel 13.31.0, PHP 8.3
- Frontend: Blade, Tailwind CSS 4, Vite 8
- Database: MySQL in production, SQLite-compatible tests where available
- Realtime: Laravel Reverb, Laravel Echo, Pusher protocol
- Cache: Redis through Predis
- File storage: local public disk in development, Cloudflare R2-compatible storage in production
- Mail: SMTP for password reset, invitations, and task assignment emails
- Public development deployment: `https://awarded-guy-cake-treasury.trycloudflare.com`

## What The App Solves

Tasharuky gives a team a single workspace where they can:

- create and organize projects
- invite collaborators into projects
- create tasks and assign them to multiple accepted project members
- track task status using a Kanban board
- discuss project work in a shared chat
- mention teammates with `@` autocomplete
- receive real-time notifications
- upload and preview project files
- sketch ideas and plans on a collaborative whiteboard
- manage users and project access based on role

## Core Features

### Authentication

- Custom login and register screens with animated transitions.
- Forgot password and reset password flow through email.
- Optional Cloudflare Turnstile integration for login/register hardening.
- Login and register requests are rate-limited with `throttle:5,1`.
- Sessions are regenerated after login.
- Production cookies are configured to be secure over HTTPS.

### Roles And Permissions

The application has three main roles:

- Admin
- Project manager
- User

Admins can:

- see and manage all projects
- delete any project
- manage the team
- remove users from the system
- remove users from projects

Project managers can:

- create projects
- manage projects they own
- delete their own projects
- invite users to their projects
- remove users from their projects
- create and manage tasks inside projects they own

Users can:

- create projects for themselves
- manage projects they own
- view projects after accepting invitations
- create tasks in projects they can access
- update task status where allowed
- chat, upload files, and use the whiteboard in accessible projects

Important security behavior:

- An invited user is not added to a project immediately.
- The project appears as joined only after the invited user accepts the invitation.
- Assigning a task does not silently add outside users to a project. The assigned user must already be the owner or an accepted project member.

### Dashboard

The dashboard gives a quick overview of:

- total projects
- to-do tasks
- completed tasks
- team members
- recent projects
- upcoming deadlines

The sidebar includes project search, recent projects, favorite projects, and a compact calendar with upcoming and overdue task indicators.

### Projects

Projects support:

- create, edit, delete, restore
- project ownership
- project status
- description and metadata
- favorites
- responsive grid/list views
- project detail page with tabs

Project tabs:

- Task Board
- Files
- Mentions
- Whiteboard

Timeline was intentionally removed because it was not needed for the current workflow.

### Favorites

Users can star projects and pin them in the sidebar. Favorites are scoped per user, so each user has their own favorite project list.

### Invitations

Project invitation flow:

1. A project owner/admin sends an invitation by email.
2. A `project_invitations` record is created with `pending` status.
3. If the invited email belongs to an existing user, that user receives an in-app notification.
4. The project is not added to the invited user yet.
5. The invited user opens and accepts the invitation.
6. Only then is the user inserted into `project_members`.

This protects the workspace from silently adding users to projects without their acceptance.

### Tasks

Tasks support:

- create, edit, delete, restore
- title and description
- status: `todo`, `in_progress`, `completed`
- priority
- due date
- task attachments
- multiple assignees
- assignment emails
- assignment notifications
- status-change notifications

The task-level progress field is not exposed as a separate UI control. Project progress is calculated from task completion so the project gradually moves toward 100% as tasks are completed.

### Task Board

The Task Board includes:

- Kanban columns
- quick status buttons
- filters for search, status, and user
- a `New Task` action inside the filter bar
- responsive layout for smaller screens
- fast client-side filtering
- server refresh where needed

The column `+` buttons were removed to keep task creation centralized through the main `New Task` button.

### Files

Project files support:

- multiple uploads
- uploader name
- upload time
- file size
- image previews
- PDF previews
- type cards for documents, sheets, decks, text, CSV, and zip files
- delete permissions for the uploader or a project manager/admin

Task attachments also appear in the Files tab so project documents and task-specific files can be reviewed from one place.

Supported upload types:

- `jpg`, `jpeg`, `png`, `webp`
- `pdf`
- `doc`, `docx`
- `xls`, `xlsx`
- `ppt`, `pptx`
- `txt`, `csv`
- `zip`

### Project Chat And Mentions

Each project has a shared chat in the Mentions tab.

Chat behavior:

- Messages are sent immediately in the UI.
- Realtime delivery uses Reverb/WebSockets.
- Polling remains as a fallback if WebSockets are unavailable.
- Pressing `Enter` sends a message.
- Pressing `Shift + Enter` creates a new line.

Mentions:

- Typing `@` opens a user suggestion list.
- Mentionable users come from the project owner, accepted project members, and users assigned to existing project tasks.
- Mentioned users receive notifications.
- Opening the notification takes the user to the project mentions area and marks the notification as read.

### Notifications

Notifications are available in:

- a header bell popup
- the Notifications page
- the sidebar unread count

Notification behavior:

- New notifications arrive in real time.
- The bell count updates without page refresh.
- Opening a notification marks it as read automatically.
- Opening the notification redirects to the relevant project/task/context.
- Invitation notifications route the user into the invitation acceptance flow.

### Whiteboard

Each project has a collaborative whiteboard for visual planning.

Whiteboard tools:

- Select
- Note
- Text
- Box
- Line
- Pen
- Delete
- Clear
- Save

Whiteboard editing:

- Click and drag to move items.
- Select an item to recolor it.
- Resize notes, text, and boxes using resize handles.
- Double-click notes or text to edit content through a custom in-app dialog.
- Color selection is shown as a dropdown with visual color circles.
- Internal sync/save states are hidden from users so the UI feels clean.

Collaboration behavior:

- Changes are synced in the background.
- Saves merge changed items instead of replacing the whole board blindly.
- Deleted item IDs are tracked so concurrent edits are less likely to overwrite another user.

### Responsive Design

The interface supports:

- desktop screens
- tablets
- narrow phone screens
- mobile sidebar
- mobile notification popup positioning
- responsive project header actions
- responsive task board filter bar
- scrollable board areas where needed

## Technical Architecture

### Main Backend Areas

- `app/Http/Controllers/DashboardController.php`: dashboard metrics, recent projects, and upcoming deadlines.
- `app/Http/Controllers/ProjectController.php`: project CRUD, soft delete, restore, and project detail page.
- `app/Http/Controllers/TaskController.php`: task board, create/update/delete/restore, assignment, status changes, notifications, and cache invalidation.
- `app/Http/Controllers/ProjectInvitationController.php`: invitation creation and acceptance.
- `app/Http/Controllers/ProjectFileController.php`: project file uploads and deletion.
- `app/Http/Controllers/ProjectMessageController.php`: project chat, mention parsing, message payloads, and mention notifications.
- `app/Http/Controllers/ProjectWhiteboardController.php`: whiteboard loading, validation, saving, merging, and syncing.
- `app/Http/Controllers/NotificationController.php`: notification center, popup feed, read state, and redirect destinations.
- `app/Http/Controllers/TeamController.php`: team page, user removal, and project membership removal.

### Main Models

- `User`
- `Project`
- `Task`
- `ProjectInvitation`
- `ProjectFile`
- `ProjectMessage`
- `ProjectWhiteboard`
- `WorkspaceNotification`

### Policies

- `ProjectPolicy`
- `TaskPolicy`

Policies centralize the major access rules:

- who can view a project
- who can manage/delete a project
- who can update/delete/restore tasks
- who can update task status

### Support Classes

- `WorkspaceNotifier` creates workspace notifications for project/task events.
- `WorkspaceLookups` caches frequently used user/project-manager lookups.
- `FastSearch` supports search normalization and searchable text.
- `Turnstile` validates Cloudflare Turnstile responses when enabled.

## Database Tables

Important domain tables:

- `users`
- `projects`
- `tasks`
- `task_assignees`
- `project_members`
- `project_favorites`
- `project_invitations`
- `project_messages`
- `project_whiteboards`
- `project_files`
- `workspace_notifications`
- `password_reset_tokens`

Search-related columns/indexes are included for projects and tasks so filtering remains responsive.

## Routes

Main web route groups:

- `/login`
- `/register`
- `/forgot-password`
- `/reset-password/{token}`
- `/dashboard`
- `/projects`
- `/projects/{project}`
- `/projects/{project}/files`
- `/projects/{project}/messages`
- `/projects/{project}/whiteboard`
- `/project-invitations/{invitation}/accept`
- `/tasks`
- `/notifications`
- `/team`

API routes:

- `GET /api/tasks`
- `PATCH /api/tasks/{task}/status`

The API currently uses HTTP Basic authentication and should be used only over HTTPS.

## Realtime Channels

Broadcasting uses private channels:

- `projects.{id}` for project chat messages.
- `users.{id}` for user-specific notifications.

Channel authorization lives in `routes/channels.php`.

## Frontend Structure

Important Blade views:

- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/auth.blade.php`
- `resources/views/dashboard/index.blade.php`
- `resources/views/projects/index.blade.php`
- `resources/views/projects/show.blade.php`
- `resources/views/projects/whiteboard.blade.php`
- `resources/views/tasks/index.blade.php`
- `resources/views/tasks/_board.blade.php`
- `resources/views/tasks/_card.blade.php`
- `resources/views/notifications/index.blade.php`
- `resources/views/team/index.blade.php`

Important frontend scripts:

- `resources/js/app.js`
- `resources/js/echo.js`

## Requirements

- PHP 8.3+
- Composer
- Node.js 20+; Node.js 22+ is preferred to avoid engine warnings
- MySQL with `pdo_mysql`
- Redis
- SMTP account for real mail delivery
- Optional Cloudflare R2 bucket
- Optional Cloudflare Turnstile site/secret keys

## Local Setup

Install dependencies:

```bash
composer install
npm install --ignore-scripts
```

Create environment:

```bash
cp .env.example .env
php artisan key:generate
```

Run database setup:

```bash
php artisan migrate --seed
```

Build assets:

```bash
npm run build
```

Start Laravel:

```bash
php artisan serve
```

Start Reverb in another terminal:

```bash
php artisan reverb:start --debug
```

## Useful Commands

```bash
php artisan route:list
php artisan migrate:status
php artisan test --compact
vendor/bin/pint --dirty --format agent
npm run build
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

## Environment Variables

Core:

```env
APP_NAME="Taskari PM"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```

Database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=taskari_pm
DB_USERNAME=root
DB_PASSWORD=
```

Session/cache/queue:

```env
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax
CACHE_STORE=redis
QUEUE_CONNECTION=sync
REDIS_CLIENT=predis
```

For production HTTPS:

```env
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=true
```

Realtime:

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
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

Cloudflare R2:

```env
FILESYSTEM_DISK=r2
CLOUDFLARE_R2_ACCESS_KEY_ID=
CLOUDFLARE_R2_SECRET_ACCESS_KEY=
CLOUDFLARE_R2_BUCKET=
CLOUDFLARE_R2_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
CLOUDFLARE_R2_URL=https://<public-bucket-domain>
CLOUDFLARE_R2_REGION=auto
```

Turnstile:

```env
TURNSTILE_ENABLED=false
TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=
```

## Seeded Data

Seeders create:

- admin user
- project managers
- regular users
- sample projects
- sample tasks
- sample assignments

Seeded accounts use:

```text
Password: password
```

Default admin:

```text
Email: admin@taskari.test
```

## Deployment Notes

Current AWS development deployment:

- Ubuntu EC2
- Nginx
- PHP 8.3 FPM
- MySQL
- Redis
- Laravel app path: `/var/www/tasharuky`
- Public access through Cloudflare tunnel: `https://awarded-guy-cake-treasury.trycloudflare.com`

Production safety settings used:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `SESSION_SECURE_COOKIE=true`
- direct EC2 IP traffic redirects to the Cloudflare HTTPS URL
- login/register throttling is enabled

After deploying code changes:

```bash
php artisan config:cache
php artisan route:cache
npm run build
```

If routes or config behave strangely:

```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

## Security Notes

Implemented security measures:

- role-based access through policies and model helpers
- CSRF protection on web forms
- session regeneration on login
- login/register rate limiting
- secure cookies in production
- direct IP redirect to HTTPS Cloudflare URL
- invitation acceptance required before project membership
- task assignment cannot add users who are not accepted project members
- notification visibility is scoped to the owning user
- project visibility is scoped by owner, membership, or assignment
- file deletion is limited to project managers/admins or the uploader

Recommended next hardening steps:

- enable Cloudflare Turnstile in production
- use private file storage with signed URLs for sensitive project files
- force downloads for non-image files if stronger file safety is required
- add API token auth or Sanctum instead of HTTP Basic for external integrations
- add API throttling if the API becomes public-facing
- add antivirus scanning for public user-uploaded files

## Testing

Current tests include:

- password reset flow
- project invitation flow
- task assignment security flow
- role permission unit checks

Run tests:

```bash
php artisan test --compact
```

Some feature tests require `pdo_sqlite` when running locally with the default test setup.

## Troubleshooting

If chat or notifications are slow:

- confirm Reverb is running
- confirm `REVERB_HOST`, `REVERB_PORT`, and `REVERB_SCHEME` match the browser URL
- check browser console WebSocket errors
- check `php artisan reverb:start --debug`

If frontend changes do not appear:

```bash
npm run build
```

If Laravel still reads old environment values:

```bash
php artisan config:clear
php artisan config:cache
```

If routes do not update:

```bash
php artisan route:clear
php artisan route:cache
```

If uploads fail:

- confirm `FILESYSTEM_DISK`
- confirm disk credentials
- confirm bucket/public URL if using R2
- confirm Nginx `client_max_body_size`

## Known Limitations

- Queue is synchronous by default, so high mail volume should use a real queue worker.
- The API currently uses HTTP Basic authentication.
- Turnstile is wired but disabled unless production keys are configured.
- Public file previews are convenient, but sensitive deployments should use private signed URLs.

## Handoff Summary

Tasharuky is ready as a functional project-management workspace with:

- admin/project/user roles
- project CRUD and ownership
- invitation-based collaboration
- favorites
- Kanban task board
- multi-assignee tasks
- project files and previews
- real-time chat and mentions
- real-time notification center
- collaborative whiteboard
- responsive UI
- password reset by email
- AWS/Cloudflare deployment support

The most important business rule is that access is project-based: ownership, accepted membership, or valid task assignment decides what a user can see and do.
