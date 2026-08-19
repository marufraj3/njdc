# JDPC dynamic website

A bilingual Laravel Blade CMS conversion of the JDPC public website. The public design is retained while pages, notices and other typed content, officers, galleries, menus, homepage/sidebar sections, media, footer data, and site identity are managed in MySQL.

## Runtime

- PHP 8.3 or 8.4 with PDO MySQL, mbstring, OpenSSL, DOM, and Fileinfo
- MySQL/MariaDB with an empty database and database user
- Domain Document Root set to this application's `public/` directory
- Writable `storage/`, `bootstrap/cache/`, and `public/uploads/`

Production does not require Composer, Node, Artisan, SSH, or a `storage:link`. The compiled app is ready after `composer install` and `npm run build`.

## Manual Installation (no `/install` page)

This project no longer includes a browser installer. Install fully manually via MySQL + `.env`.

### 1) Upload files

1. Upload the project directory (e.g. `jdpc`) to the server **outside** `public_html` where possible.
2. In Hosting / cPanel → Domains, set the domain **Document Root** to `jdpc/public`.
3. Select PHP 8.3 or 8.4 and enable extensions: `pdo_mysql`, `mbstring`, `openssl`, `dom`, `fileinfo`.
4. Make `storage`, `bootstrap/cache`, and `public/uploads` writable by PHP (usually `775`).

### 2) Create database

Create an empty MySQL database and a dedicated user in cPanel → MySQL® Databases. Grant the user **ALL PRIVILEGES** on the database and keep the credentials.

### 3) Import SQL

Import the bundled SQL dump (schema + demo content + default admin):

- **phpMyAdmin**: open the new database → Import → Choose `database/jdpc.sql` → Go.
- **CLI**: `mysql -u DB_USER -p DB_NAME < database/jdpc.sql`

What it contains:
- All tables (`users`, `pages`, `content_items`, `media_files`, `menus`, `layout_sections`, `officers`, etc.)
- Legacy normalized data from `database/seeders/data/jdpc.json` (pages, notices, officers, menus, home, sidebar, footer, gallery)
- Default super-admin user

> Alternative: if you prefer empty schema only, run `php artisan migrate` instead of importing the SQL, then create the admin and run `php artisan jdpc:import --user=1`.

### 4) Configure `.env`

On the server, copy `.env.example` to `.env` and edit:

```env
APP_NAME=JDPC
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
APP_KEY=base64:...   # generate with php artisan key:generate

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

SESSION_DRIVER=file
CACHE_STORE=file
```

Generate `APP_KEY`:

- With SSH: `php artisan key:generate`
- Without SSH: on any local Laravel app run `php artisan key:generate --show` and paste the `base64:...` value.

Set permissions after editing: `chmod 640 .env`.

### 5) Admin account

Default admin after SQL import:

- **Email:** `admin@jdpc.gov.bd`
- **Password:** `Admin@123456`

Log in at `https://your-domain.example/admin/login` and change the password immediately. If login fails, reset via phpMyAdmin:

```sql
-- set password to 'Admin@123456' (bcrypt with 12 rounds)
UPDATE `users` SET `password` = '$2y$12$FSiHMr2mMrsOMV6.LgT2U.XfFfuXNFoM/EAxpT1jgAXhdsBuqD0C.' WHERE `email`='admin@jdpc.gov.bd';
-- then login with password: Admin@123456
```

Create additional admin via SQL:

```sql
INSERT INTO `users` (`name`,`email`,`password`,`role`,`is_active`,`email_verified_at`,`created_at`,`updated_at`) VALUES ('New Admin','newadmin@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','super_admin',1,NOW(),NOW(),NOW());
```

Or with SSH: `php artisan tinker` then `User::create([...])`.

Never point Document Root at project root. Never commit `.env` or `public/uploads`. Back up both database and `public/uploads`.

## Local development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
# or import SQL
# mysql -u root -p jdpc < database/jdpc.sql
npm install
npm run build
php artisan test
```

To (re-)import bundled reference data after creating a publisher/super-admin:

```bash
php artisan jdpc:import --user=1
```

The importer is checksum-idempotent. `database/seeders/data/jdpc.json` is only a reference source; production pages are read from MySQL.

## Editorial workflow

Editors create drafts and submit them. Editing already-published content creates a non-destructive revision, keeping the live version online. Publishers can approve and publish or reject with a note. Publisher edits to live records remain published and are audited.
