<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacy extends Command
{
    protected $signature = 'drc:import-legacy';

    protected $description = 'Import all legacy MySQL tables to an empty migrated Laravel database';

    public function handle(): int
    {
        $tables = array_keys(config('drc_schema'));
        foreach ($tables as $table) {
            if (DB::table($table)->exists()) {
                $this->error('Target must be empty. Use a separate, freshly migrated database without seeding.');

                return self::FAILURE;
            }
        }
        $host = env('LEGACY_DB_HOST', '127.0.0.1');
        $port = env('LEGACY_DB_PORT', 3306);
        $database = env('LEGACY_DB_DATABASE', 'drc_website');
        try {
            $source = new \PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", env('LEGACY_DB_USERNAME', 'root'), env('LEGACY_DB_PASSWORD', ''), [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
            if (DB::getDriverName() === 'mysql' && config('database.connections.mysql.database') === $database && config('database.connections.mysql.host') === $host) {
                $this->error('Source and target must be different databases.');

                return self::FAILURE;
            }
            // Tables follow FK dependency order, preserving primary keys and password hashes.
            DB::transaction(function () use ($source, $tables) {
                $deferred = [];
                foreach ($tables as $table) {
                    $rows = $source->query("SELECT * FROM `$table` ORDER BY id")->fetchAll(\PDO::FETCH_ASSOC);
                    $columns = array_keys(config("drc_schema.$table.fields"));
                    foreach ($rows as $row) {
                        $row = array_intersect_key($row, array_flip($columns));
                        foreach (config("drc_schema.$table.foreign") as $column => $foreign) {
                            if ($foreign['table'] === $table && ! empty($row[$column])) {
                                $deferred[] = [$table, $row['id'], $column, $row[$column]];
                                $row[$column] = null;
                            }
                        }
                        DB::table($table)->insert($row);
                    }
                    $this->line("$table: ".count($rows).' rows');
                }
                foreach ($deferred as [$table,$id,$column,$value]) {
                    DB::table($table)->where('id', $id)->update([$column => $value]);
                }
            });
            $this->info('Import complete. Copy legacy uploads to storage/app/public; existing paths remain supported.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            report($e);
            $this->error('Import failed and rolled back. Check connection/schema in the private application log.');

            return self::FAILURE;
        }
    }
}
