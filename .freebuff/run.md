# Run Doc — JDPC CMS (Laravel 13)

## Prerequisites (already satisfied)

1. PHP 8.4 at `C:\php84\php.exe` with php.ini enabling: openssl, curl, mbstring, fileinfo, pdo_sqlite, sqlite3, zip, bz2, gd, sodium, intl, exif
2. CA bundle at `C:\php84\cacert.pem` (copied from Python certifi)
3. `vendor/` populated via `php composer.phar install --ignore-platform-reqs`
4. `node_modules/` populated via `npm install`
5. `.env` with APP_KEY set
6. `database/database.sqlite` with migrations run

## How to reproduce artifacts

1. Copy `.env` from main checkout
2. If vendor/ missing: `php composer.phar install --ignore-platform-reqs`
3. If node_modules/ missing: `npm install`
4. If .env missing: `cp .env.example .env && /c/php84/php.exe artisan key:generate`
5. If database.sqlite missing: `touch database/database.sqlite && /c/php84/php.exe artisan migrate`

## How to run (two servers)

### 1. Laravel PHP server (port 8000)
PowerShell detach:
```
Start-Process -FilePath 'C:\php84\php.exe' -ArgumentList 'artisan','serve','--host=127.0.0.1','--port=8000' -RedirectStandardOutput 'D:\jdc\.freebuff\php-server.log' -RedirectStandardError 'D:\jdc\.freebuff\php-server.log.err' -WindowStyle Hidden -PassThru
```

### 2. Vite dev server (port 5173)
PowerShell detach:
```
Start-Process -FilePath 'npm.cmd' -ArgumentList 'run','dev' -RedirectStandardOutput 'D:\jdc\.freebuff\vite.log' -RedirectStandardError 'D:\jdc\.freebuff\vite.log.err' -WindowStyle Hidden -PassThru
```

### Logs
- PHP: `D:\jdc\.freebuff\php-server.log`
- Vite: `D:\jdc\.freebuff\vite.log`

### Main URL
http://127.0.0.1:8000 (Laravel installer page)
