# Banking Documents API

API backend for secure document storage, sharing and audit logging — built on Laravel 12.

This README documents the project specifics and the recent development decisions made in this repository (authentication, sharing, audit, queue jobs, policies, and routes).

## Quick start

-   Requirements: PHP 8.2+, Composer, MySQL (or SQLite for local), Node (optional for assets)
-   Install dependencies and prepare environment:

```bash
composer install
cp .env.example .env
# configure DB in .env
php artisan key:generate
php artisan migrate --seed
```

## Authentication

-   The project uses Laravel Sanctum for API token authentication. The `User` model uses `HasApiTokens` and `AuthController` implements `register`, `login` and `logout` returning plain-text tokens.
-   Use the `Authorization: Bearer <token>` header for protected routes.

Commands to prepare Sanctum (if needed):

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

## Routes overview

-   Public routes (no auth):

    -   `POST /api/auth/register` — register
    -   `POST /api/auth/login` — login
    -   `GET  /api/documents/share/{token}` — download public share
    -   `GET  /api/documents/share/{token}/info` — public metadata

-   Protected routes (require `auth:sanctum`):
    -   `POST   /api/auth/logout`
    -   `GET    /api/auth/user` — current user
    -   `POST   /api/documents` — upload document
    -   `GET    /api/documents` — list user's documents
    -   `GET    /api/documents/{id}` — show document
    -   `GET    /api/documents/{id}/download` — download (policy protected)
    -   `DELETE /api/documents/{id}` — delete
    -   `POST   /api/documents/{id}/share` — create share
    -   `GET    /api/documents/{id}/shares` — list shares

You can list routes with:

```bash
php artisan route:list --path=api
```

## Middleware and API JSON responses

-   The application override `app/Http/Middleware/Authenticate.php` so API requests return JSON 401 responses (instead of redirect to `login`) when unauthenticated.
-   FormRequests were adjusted (`failedValidation`) to always return JSON 422 so clients get consistent API validation responses even without `Accept: application/json` header.

## Authorization (Policies)

-   Policies are registered via `app/Providers/AuthServiceProvider.php`.
-   `DocumentPolicy` implements `view`, `download`, `share` and `delete` rules (ownership-based and checks document status for share/download).
-   Controllers use `$this->authorize('download', $document)` etc. Make sure policies are loaded; clear cache if you change providers:

```bash
php artisan config:clear
php artisan cache:clear
```

## Audit logging

-   An `Audit` model and `audits` table track actions (RGPD-friendly): created, viewed, downloaded, shared, share_accessed, scans, etc.
-   Use `Audit::log($actionEnum, $auditableModel, $userId?, $userEmail?, $metadata?)` to write an audit entry. The helper stores `user_id`, `auditable_type`, `auditable_id`, `action`, `metadata`, `ip_address`, `user_agent`, `created_at`.
-   The `audits` table intentionally only stores `created_at` (no `updated_at`) and the model disables Eloquent timestamps.

## Sharing

-   `SharingService` creates `DocumentShare` records with token, expiry, max downloads and `is_active` flag.
-   Public routes allow downloading by token and listing share info. Accesses are recorded via `SharingService::recordAccess()` which increments counters and disables expired/overused shares.

## Queue & background jobs

-   File uploads dispatch a `ScanAntivirusJob` to queue `default` to simulate antivirus scanning.
-   Default queue connection in `config/queue.php` is `database`. To process jobs once:

```bash
php artisan queue:work --once --queue=default
```

-   To run worker continuously:

```bash
php artisan queue:work --queue=default
```

## Storage

-   Uploaded files are stored under `storage/app/private/documents`. Ensure `storage` is writable.

## Testing

-   Run the test suite:

```bash
composer test
# or
php artisan test
```

## Useful commands

-   Regenerate autoload & clear caches:

```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan clear-compiled
```

-   Re-run migrations and seeders (destructive):

```bash
php artisan migrate:fresh --seed
```

## Notes & recommendations for after deployment

-   The project includes example implementations for services (`DocumentService`, `SharingService`) and policies. For production, consider:
    -   using a real antivirus scanner job,
    -   protecting shared downloads with optional password or signed URLs,
    -   adding rate-limiting on public share endpoints,
    -   reviewing retention/archiving for audit logs.

If you want, I can add a small section with curl examples for register/login/upload/download and create automated Feature tests that assert authentication/authorization and audit writing.

---
