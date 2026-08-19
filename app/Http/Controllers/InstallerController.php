<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\LegacyContentImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PDO;
use Throwable;

class InstallerController extends Controller
{
    public function show()
    {
        if ($this->installed()) return redirect('/');
        return view('install.show', ['requirements' => $this->requirements(), 'keyAvailable' => is_readable($this->keyPath())]);
    }

    public function install(Request $request, LegacyContentImporter $importer)
    {
        abort_if($this->installed(), 404);
        $key = is_readable($this->keyPath()) ? trim((string) file_get_contents($this->keyPath())) : '';
        abort_unless(strlen($key) >= 24 && hash_equals($key, (string) $request->input('installer_key')), 403, 'The installation key is not valid.');

        $requirements = $this->requirements();
        if (in_array(false, $requirements, true)) return back()->withErrors(['server' => 'One or more server requirements are not met.'])->withInput();

        $validated = $request->validate([
            'app_url' => ['required', 'url', 'max:255', 'regex:/^https?:\/\//i'],
            'db_host' => ['required', 'max:255'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'regex:/^[A-Za-z0-9_$-]+$/', 'max:64'],
            'db_username' => ['required', 'max:128'],
            'db_password' => ['nullable', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email:rfc', 'max:255'],
            'admin_password' => ['required', 'string', 'min:12', 'confirmed', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/'],
        ], ['admin_password.regex' => 'The password must contain upper-case, lower-case, and numeric characters.']);

        $lock = @fopen(storage_path('app/install.lock'), 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) fclose($lock);
            return back()->withErrors(['install' => 'Another installation request is already running. Wait a moment and try again.']);
        }
        if ($this->installed()) {
            flock($lock, LOCK_UN);
            fclose($lock);
            abort(404);
        }

        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $validated['db_host'], $validated['db_port'], $validated['db_database']),
                $validated['db_username'],
                $validated['db_password'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8]
            );
            $pdo = null;

            $this->writeEnvironment($validated);
            $this->clearBootstrapCache();
            $this->configureDatabase($validated);

            Artisan::call('migrate', ['--force' => true]);
            $admin = User::query()->updateOrCreate(['email' => strtolower($validated['admin_email'])], [
                'name' => $validated['admin_name'],
                'password' => Hash::make($validated['admin_password']),
                'role' => 'super_admin',
                'locale' => 'bn',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $importer->import(database_path('seeders/data/jdpc.json'), $admin->id);

            $markerPath = storage_path('app/installed');
            $marker = json_encode(['installed_at' => now()->toIso8601String(), 'version' => '1.0.0'], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            $written = file_put_contents($markerPath, $marker, LOCK_EX);
            if ($written !== strlen($marker) || file_get_contents($markerPath) !== $marker) {
                @unlink($markerPath);
                throw new \RuntimeException('The installation marker could not be verified.');
            }
            @chmod($markerPath, 0640);
            @unlink($this->keyPath());
            @unlink(base_path('INSTALL-KEY.txt'));

            return redirect()->route('login')->with('status', 'Installation completed. Sign in with the administrator account you created.');
        } catch (Throwable $exception) {
            report($exception);
            return back()->withErrors(['install' => 'Installation could not be completed. Check the database details and writable-directory requirements, then try again.'])->withInput($request->except(['db_password', 'admin_password', 'admin_password_confirmation', 'installer_key']));
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function requirements(): array
    {
        return [
            'PHP 8.3 or newer' => version_compare(PHP_VERSION, '8.3.0', '>='),
            'PDO MySQL extension' => extension_loaded('pdo_mysql'),
            'Multibyte string extension' => extension_loaded('mbstring'),
            'OpenSSL extension' => extension_loaded('openssl'),
            'DOM extension' => extension_loaded('dom'),
            'Fileinfo extension' => extension_loaded('fileinfo'),
            'Storage directory is writable' => is_writable(storage_path()),
            'Bootstrap cache is writable' => is_writable(base_path('bootstrap/cache')),
            'Application root is writable for .env' => is_writable(base_path()),
            'Public uploads directory is writable' => is_writable(public_path('uploads')),
        ];
    }

    private function writeEnvironment(array $values): void
    {
        $env = [
            'APP_NAME' => 'JDPC', 'APP_ENV' => 'production', 'APP_KEY' => (string) config('app.key') ?: 'base64:'.base64_encode(random_bytes(32)),
            'APP_DEBUG' => 'false', 'APP_URL' => rtrim($values['app_url'], '/'), 'APP_TIMEZONE' => 'Asia/Dhaka',
            'APP_LOCALE' => 'bn', 'APP_FALLBACK_LOCALE' => 'bn', 'LOG_CHANNEL' => 'stack', 'LOG_LEVEL' => 'error',
            'DB_CONNECTION' => 'mysql', 'DB_HOST' => $values['db_host'], 'DB_PORT' => (string) $values['db_port'],
            'DB_DATABASE' => $values['db_database'], 'DB_USERNAME' => $values['db_username'], 'DB_PASSWORD' => $values['db_password'] ?? '',
            'SESSION_DRIVER' => 'file', 'SESSION_LIFETIME' => '120', 'SESSION_ENCRYPT' => 'true',
            'CACHE_STORE' => 'file', 'QUEUE_CONNECTION' => 'sync', 'FILESYSTEM_DISK' => 'uploads',
            'BCRYPT_ROUNDS' => '12',
        ];
        $content = implode("\n", array_map(fn ($key, $value) => $key.'='.$this->envValue((string) $value), array_keys($env), $env))."\n";
        $temporary = base_path('.env.installing');
        if (file_put_contents($temporary, $content, LOCK_EX) === false || ! rename($temporary, base_path('.env'))) {
            throw new \RuntimeException('The environment file could not be written.');
        }
        @chmod(base_path('.env'), 0640);
    }

    private function configureDatabase(array $values): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $values['db_host'],
            'database.connections.mysql.port' => $values['db_port'],
            'database.connections.mysql.database' => $values['db_database'],
            'database.connections.mysql.username' => $values['db_username'],
            'database.connections.mysql.password' => $values['db_password'] ?? '',
        ]);
        DB::purge('mysql');
        DB::connection('mysql')->getPdo();
    }

    private function clearBootstrapCache(): void
    {
        foreach (glob(base_path('bootstrap/cache/*.php')) ?: [] as $file) @unlink($file);
    }

    private function envValue(string $value): string
    {
        $escaped = strtr($value, [
            '\\' => '\\\\', '"' => '\\"', '$' => '\\$',
            "\r" => '\\r', "\n" => '\\n', "\t" => '\\t',
        ]);

        return '"'.$escaped.'"';
    }

    private function installed(): bool { return is_file(storage_path('app/installed')); }
    private function keyPath(): string { return storage_path('app/install.key'); }
}
