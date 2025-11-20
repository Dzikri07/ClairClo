# ClairoCloud AI Coding Agent Instructions

## Project Overview
ClairoCloud is a PHP/MySQL web application for cloud file management. It features user and admin dashboards, file upload/download, favorites, trash management, storage quota, and activity logging. The architecture is modular, with clear separation between public user features, admin tools, database migrations, and utility scripts.

## Key Directories & Files
- `app/public/`: Main web interface (user & admin PHP pages)
- `app/public/admin/`: Admin panel (user, storage, logs management)
- `app/public/assets/`: Static assets (CSS, JS, images)
- `app/public/uploads/`: User file storage (ensure writable)
- `app/public/connection.php`: Database connection config
- `app/public/file_functions.php`: File utility functions
- `app/public/delete.php`, `rename.php`, `favorite.php`, `download.php`, `upload.php`: API endpoints for file actions
- `app/src/`: Core PHP classes (e.g., `Database.php`, `Migration.php`, `StorageManager.php`)
- `database/`: Migration scripts, runners, seeders
- `tools/`: Dev/debug scripts for database and sidebar

## Database & Migrations
- Migrations are managed via PHP scripts in `database/migrations/`.
- Run migrations with `php database/run_migrations.php` or `php database/migrate.php up`.
- Migration files follow `00X_description.php` naming and extend the `Migration` class.
- Default admin user: `admin` / `admin123` (change password after setup).
- Storage quotas: User=5GB, Admin=100GB (editable).

## Development Patterns
- Add new features by creating PHP files in `app/public/` and updating the sidebar/menu.
- For database changes, create a new migration file and run migrations.
- Use `file_functions.php` for reusable file logic.
- API endpoints are simple PHP scripts, typically expecting JSON POST requests.
- Frontend uses Bootstrap, Font Awesome, Iconify, SweetAlert2, and Chart.js.
- JS event delegation is used for file actions (see `sampah.php` for examples).

## Testing & Debugging
- Use `examples/` for sample usage/testing.
- Use scripts in `tools/` for DB inspection, migration checks, and sidebar validation.
- Check `logs/` for application logs.

## Conventions & Patterns
- Migration scripts must not be edited after execution; always create new ones for changes.
- File actions (delete, restore, rename, favorite) are handled via AJAX to PHP endpoints.
- File categories and allowed extensions are enforced in migrations and upload logic.
- All user actions are tracked for activity logging.

## Setup & Build
- Install PHP dependencies with Composer (if any).
- Install frontend dependencies with npm.
- Database setup: import `clariocloud (1).sql` or run migrations.
- Configure database in `connection.php`.
- Ensure `uploads/` is writable.
- Access app via browser at `/app/public`.

## Example: File Restore (Trash)
- Frontend triggers AJAX POST to `delete.php` with `{ file_id, action: 'restore' }`.
- Backend updates DB, returns JSON `{ success, counts }`.
- JS updates UI and file counts dynamically.

---
For unclear conventions or missing patterns, ask maintainers for clarification. Always backup data before running migrations or major updates.
