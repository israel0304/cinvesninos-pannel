# AGENTS.md

## Stack

- **Backend:** Laravel 12 (PHP 8.2+)
- **Frontend:** Vue 3 + Inertia.js + TypeScript + Tailwind CSS 4
- **UI:** Reka UI + Lucide Icons
- **Auth:** Laravel Fortify (2FA)

## Commands

```bash
# Frontend
npm run dev        # Start dev server
npm run build      # Build assets
npm run lint       # ESLint fix
npm run format    # Prettier write

# Backend
composer dev      # Run all (server + queue + logs + vite)
composer test    # Pint lint + PHPUnit
composer lint    # Pint only
php artisan tinker
```

## Testing

- **PHP:** PHPUnit (run with `composer test`)
- **JS/Vue:** ESLint + Prettier

## Key Directories

- `app/Http/Controllers` - Controllers
- `app/Models` - Eloquent models
- `resources/js/` - Vue components & Inertia pages
- `routes/web.php` - Web routes
- `database/migrations/` - Migrations

## Architecture Notes

- SPA via Inertia.js - no API routes needed for frontend
- Stock logic: `committed` vs `delivered` states
- SoftDeletes on users/requests for audit trail
- Settings table drives real-time feature flags

## Recent Updates

- **User Model:** Split `name` into `first_name` + `last_name`
- **Registration:** Requires `dni` (unique) and `role_id` (FK)
- New users automatically receive "Participante" role
- Tests: Run with `composer test` (includes lint + PHPUnit)
- **GitHub Actions:** PHP 8.2, Node.js 22 - workflows configured in `.github/workflows/`
