<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $sql = file_get_contents(__DIR__.'/drc.sql');
        DB::transaction(function () use ($sql) {
            // Seed each table only when empty; never overwrite existing content.
            foreach (preg_split('/;\s*(?=INSERT INTO|$)/', $sql) as $statement) {
                if (! preg_match('/INSERT INTO (\w+)/', $statement, $match)) {
                    continue;
                }
                if (DB::table($match[1])->count() === 0) {
                    DB::unprepared(trim($statement).';');
                }
            }
        });
    }
}
