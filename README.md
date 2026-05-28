# Captor API (captor-back)

Laravel 13 + PostgreSQL + Sanctum API powering the Career360Consult marketing site
and its custom admin portal (served from `captor-front/src/app/admin`).

## What it does

Three public endpoints capture leads from the marketing site:

| Endpoint                          | Source on the marketing site                          |
| --------------------------------- | ----------------------------------------------------- |
| `POST /api/public/contact`        | `/contact` page form                                  |
| `POST /api/public/org-inquiry`    | "Talk to us" modal on the home page                   |
| `POST /api/public/applications`   | `/apply` 4-step flow (multipart, accepts file uploads) |

Each submission creates a `Lead` row plus a kind-specific detail row. The admin
portal API (Sanctum-protected) reads & manages those leads.

## Data model

- `users` — admins (role: `admin` | `super_admin`), per-user `calendly_url`.
- `leads` — unified pipeline row: `kind`, `status`, `assigned_user_id`, `name`,
  `email`, `phone`, `source`, `calendly_url`, `scheduled_at`, `tags`.
- `contact_messages` — detail rows for `kind = contact`.
- `org_inquiries` — detail rows for `kind = org`.
- `applications` + `application_files` — detail for `kind = application`.
- `notes` — admin notes on a lead.
- `meetings` — Calendly bookings tied to a lead.

## Local setup

```powershell
# 1. Make sure a Postgres database exists & update .env credentials.
#    Defaults: host=127.0.0.1 port=5432 db=captor user=postgres password=postgres
createdb captor   # or use pgAdmin

# 2. Run migrations & seed the default admin.
php artisan migrate --seed

# 3. Storage symlink for serving uploaded application files (optional).
php artisan storage:link

# 4. Run the dev server.
php artisan serve   # http://localhost:8000
```

Default seeded admin: `admin@career360consult.com` / `change-me-now` (rotate immediately).

## Admin portal auth flow

1. Front-end posts `{ email, password }` to `POST /api/auth/login`.
2. Response: `{ token, user }`. Store the token (HTTP-only cookie recommended).
3. Send `Authorization: Bearer <token>` on every `/api/admin/*` request.
4. `POST /api/auth/logout` revokes the current token.

## Admin API surface

```
GET    /api/admin/dashboard
GET    /api/admin/leads?kind=&status=&assignee=&q=&per_page=
GET    /api/admin/leads/{id}
PATCH  /api/admin/leads/{id}            { status, assigned_user_id, calendly_url, tags }
POST   /api/admin/leads/{id}/notes      { body }
POST   /api/admin/leads/{id}/meetings   { scheduled_at, calendly_event_url?, status?, notes? }
```

## CORS

`config/cors.php` allows the origin in `FRONTEND_URL` (default
`http://localhost:3000`) with credentials. Update for production deploys.
