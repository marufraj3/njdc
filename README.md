# JDPC dynamic website

A bilingual Laravel Blade CMS conversion of the JDPC public website. The public design is retained while pages, notices and other typed content, officers, galleries, menus, homepage/sidebar sections, media, footer data, and site identity are managed in MySQL.

## Runtime

- PHP 8.3 or 8.4 with PDO MySQL, mbstring, OpenSSL, DOM, and Fileinfo
- MySQL/MariaDB with an empty database and database user
- Domain Document Root set to this application's `public/` directory
- Writable `storage/`, `bootstrap/cache/`, and `public/uploads/`

Production does not require Composer, Node, Artisan, SSH, or a `storage:link`. The **Production ZIP** GitHub workflow packages Composer vendor files and compiled Vite assets.

## cPanel installation (no terminal)

1. Download a `jdpc-cpanel-*.zip` artifact produced by the Production ZIP workflow.
2. Upload and extract its `jdpc` directory outside `public_html` where possible.
3. In cPanel Domains, set the domain Document Root to `jdpc/public`.
4. Select PHP 8.3 or 8.4 and enable the extensions listed above.
5. Make `storage`, `bootstrap/cache`, and `public/uploads` writable by PHP (normally `775`; use the host's recommended ownership).
6. Create an empty MySQL database and user in cPanel, grant that user all privileges on the database, and keep the details ready.
7. Open `https://your-domain.example/install` and copy the one-time key from `INSTALL-KEY.txt` into the installer.
8. Enter the site URL, database details, and first administrator account. The installer writes `.env`, migrates the schema, imports the bundled normalized legacy data, and locks itself.
9. Sign in at `/admin/login`. Confirm the homepage and one download, then delete any local copy of the installation key. The server copies are removed automatically after success.

Never point the Document Root at the project root. Never commit `.env`, `INSTALL-KEY.txt`, or uploaded files. Back up both the database and `public/uploads`.

## Local development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan test
```

To import the bundled reference data after creating a publisher/super-admin:

```bash
php artisan jdpc:import --user=1
```

The importer is checksum-idempotent. `database/seeders/data/jdpc.json` is only an installation/reference source; production pages are read from MySQL.

## Editorial workflow

Editors create drafts and submit them. Editing already-published content creates a non-destructive revision, keeping the live version online. Publishers can approve and publish or reject with a note. Publisher edits to live records remain published and are audited.
