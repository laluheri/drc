<?php

// Build a private cPanel release without changing the working application.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$source = dirname(__DIR__);
$output = dirname($source).'/dist/cpanel-'.date('Ymd-His');
mkdir($output, 0700, true);
$private = $output.'/drc-app';
$public = $output.'/public_html';

function copyReleaseTree(string $from, string $to): void
{
    if (is_dir($from)) {
        if (!is_dir($to)) mkdir($to, 0755, true);
        foreach (new DirectoryIterator($from) as $entry) {
            if ($entry->isDot() || $entry->isLink() || in_array($entry->getFilename(), ['.git', '.gitignore', '.DS_Store'])) continue;
            copyReleaseTree($entry->getPathname(), $to.'/'.$entry->getFilename());
        }
    } else {
        if (!is_dir(dirname($to))) mkdir(dirname($to), 0755, true);
        copy($from, $to);
    }
}

foreach (['app', 'config', 'resources', 'routes', 'database/migrations', 'database/seeders', 'database/factories', 'public'] as $folder) copyReleaseTree($source.'/'.$folder, $private.'/'.$folder);
foreach (['artisan', 'composer.json', 'composer.lock', 'README-DRC.md'] as $file) copyReleaseTree($source.'/'.$file, $private.'/'.$file);
foreach (['app.php', 'providers.php'] as $file) copyReleaseTree($source.'/bootstrap/'.$file, $private.'/bootstrap/'.$file);
foreach (['bootstrap/cache', 'storage/app/private', 'storage/app/public', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'] as $folder) if (!is_dir($private.'/'.$folder)) mkdir($private.'/'.$folder, 0755, true);
copyReleaseTree($source.'/public', $public);
copy($source.'/deploy/cpanel/index.php', $public.'/index.php');
copy($source.'/deploy/cpanel/PANDUAN-CPANEL.md', $output.'/PANDUAN-CPANEL.md');

$password = Illuminate\Support\Str::password(24);
$key = 'base64:'.base64_encode(random_bytes(32));
$env = str_replace('GENERATED_BY_PACKAGE_BUILDER', $key, file_get_contents($source.'/deploy/cpanel/.env.production.example'));
file_put_contents($private.'/.env', $env);
file_put_contents($private.'/.env.example', str_replace($key, '', $env));

$schema = config('drc_schema');
$rows = [];
Illuminate\Support\Facades\DB::transaction(function () use ($schema, &$rows) {
    foreach ($schema as $table => $definition) $rows[$table] = in_array($table, ['logs', 'visitor_statistics']) ? [] : Illuminate\Support\Facades\DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
    $rows['migrations'] = Illuminate\Support\Facades\DB::table('migrations')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
});

$adminIndex = null;
foreach ($rows['users'] as $index => &$user) {
    foreach (['remember_token', 'reset_token', 'reset_expires', 'locked_until', 'last_login'] as $column) $user[$column] = null;
    $user['login_attempts'] = 0;
    if ($user['role'] === 'super_admin' && $user['status'] === 'active' && $user['deleted_at'] === null && ($adminIndex === null || $user['username'] === 'superadmin')) $adminIndex = $index;
}
unset($user);
if ($adminIndex === null) throw new RuntimeException('An active super administrator is required before packaging.');
$admin = &$rows['users'][$adminIndex];
$admin['password'] = Illuminate\Support\Facades\Hash::make($password);
file_put_contents($output.'/AKSES-ADMIN.txt', "LOGIN HOSTING DRC\n\nURL: https://drcproject.org/admin/login\nUsername: {$admin['username']}\nEmail: {$admin['email']}\nKata sandi: $password\n\nKredensial ini berlaku setelah database.sql paket diimpor.\nKata sandi admin lokal tidak diubah.\nUbah sandi setelah login dan simpan file ini di luar public_html.\n");
unset($admin);

function sqlString(string $value): string { return "'".str_replace("'", "''", $value)."'"; }
function sqlValue(mixed $value): string {
    if ($value === null) return 'NULL';
    if (is_int($value) || is_float($value)) return (string) $value;
    if ($value === '') return "''";
    return 'CONVERT(0x'.bin2hex((string) $value).' USING utf8mb4)';
}

$sql = "-- DRC Laravel local snapshot for a NEW, EMPTY MySQL database\n-- No database names, database passwords, or DROP statements are included.\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
foreach ($schema as $table => $definition) {
    $columns = [];
    foreach ($definition['fields'] as $column => $field) {
        if ($column === 'id') { $columns[] = '`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY'; continue; }
        $type = match ($field['type']) {
            'INT', 'TINYINT' => isset($definition['foreign'][$column]) ? 'BIGINT UNSIGNED' : 'INT',
            'YEAR' => 'SMALLINT UNSIGNED',
            'VARCHAR' => 'VARCHAR('.($field['length'] ?? 255).')',
            'ENUM' => 'ENUM('.implode(',', array_map('sqlString', $field['options'])).')',
            default => $field['type'],
        };
        $line = "`$column` $type ".($field['required'] ? 'NOT NULL' : 'NULL');
        if (array_key_exists('default', $field)) $line .= ' DEFAULT '.($field['default'] === 'CURRENT_TIMESTAMP' ? 'CURRENT_TIMESTAMP' : ($field['default'] === null ? 'NULL' : sqlString((string) $field['default'])));
        if ($field['unique']) $line .= ' UNIQUE';
        $columns[] = $line;
    }
    foreach ($definition['foreign'] as $column => $foreign) $columns[] = "CONSTRAINT `fk_{$table}_{$column}` FOREIGN KEY (`$column`) REFERENCES `{$foreign['table']}` (`{$foreign['column']}`) ON DELETE {$foreign['delete']}";
    foreach (['status', 'deleted_at'] as $column) if (isset($definition['fields'][$column])) $columns[] = "KEY `idx_{$table}_{$column}` (`$column`)";
    $sql .= "CREATE TABLE `$table` (\n  ".implode(",\n  ", $columns)."\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";
}
$sql .= <<<'SQL'
CREATE TABLE `migrations` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `migration` VARCHAR(255) NOT NULL, `batch` INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `password_reset_tokens` (`email` VARCHAR(255) NOT NULL PRIMARY KEY, `token` VARCHAR(255) NOT NULL, `created_at` TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `cache` (`key` VARCHAR(255) NOT NULL PRIMARY KEY, `value` MEDIUMTEXT NOT NULL, `expiration` INT NOT NULL, KEY `cache_expiration_index` (`expiration`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `cache_locks` (`key` VARCHAR(255) NOT NULL PRIMARY KEY, `owner` VARCHAR(255) NOT NULL, `expiration` INT NOT NULL, KEY `cache_locks_expiration_index` (`expiration`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `jobs` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `queue` VARCHAR(255) NOT NULL, `payload` LONGTEXT NOT NULL, `attempts` TINYINT UNSIGNED NOT NULL, `reserved_at` INT UNSIGNED NULL, `available_at` INT UNSIGNED NOT NULL, `created_at` INT UNSIGNED NOT NULL, KEY `jobs_queue_index` (`queue`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `job_batches` (`id` VARCHAR(255) NOT NULL PRIMARY KEY, `name` VARCHAR(255) NOT NULL, `total_jobs` INT NOT NULL, `pending_jobs` INT NOT NULL, `failed_jobs` INT NOT NULL, `failed_job_ids` LONGTEXT NOT NULL, `options` MEDIUMTEXT NULL, `cancelled_at` INT NULL, `created_at` INT NOT NULL, `finished_at` INT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `failed_jobs` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `uuid` VARCHAR(255) NOT NULL UNIQUE, `connection` TEXT NOT NULL, `queue` TEXT NOT NULL, `payload` LONGTEXT NOT NULL, `exception` LONGTEXT NOT NULL, `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

START TRANSACTION;
SQL;
foreach ($rows as $table => $records) foreach ($records as $row) {
    $sql .= "\nINSERT INTO `$table` (`".implode('`,`', array_keys($row)).'`) VALUES ('.implode(',', array_map('sqlValue', array_values($row))).');';
}
$sql .= "\nCOMMIT;\nSET FOREIGN_KEY_CHECKS=1;\n";
file_put_contents($output.'/database.sql', $sql);

// Include only uploads referenced by exported content, plus the existing logo.
$files = ['logo.png'];
foreach ($schema as $table => $definition) foreach ($rows[$table] as $row) foreach (array_merge(config('drc.images'), config('drc.files')) as $column) if (!empty($row[$column])) $files[] = preg_replace('~^/?uploads/~', '', $row[$column]);
$missing = [];
foreach (array_unique($files) as $file) {
    if (str_contains($file, '..') || !preg_match('~^[a-zA-Z0-9/_ .-]+$~', $file)) continue;
    if (is_file($source.'/storage/app/public/'.$file)) copyReleaseTree($source.'/storage/app/public/'.$file, $private.'/storage/app/public/'.$file);
    else $missing[] = $file;
}
file_put_contents($output.'/MANIFEST.json', json_encode(['built_at' => date(DATE_ATOM), 'domain' => 'https://drcproject.org', 'snapshot' => 'local Laravel database', 'tables' => count($schema) + 7, 'records' => array_map('count', $rows), 'missing_uploads' => $missing, 'requires' => 'PHP >= 8.2 with pdo_mysql; HTTPS'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
file_put_contents(dirname($source).'/dist/latest-cpanel-path.txt', $output);
echo "Release prepared: $output\n";
