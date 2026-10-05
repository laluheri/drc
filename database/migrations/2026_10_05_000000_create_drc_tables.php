<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (config('drc_schema') as $name => $definition) {
            Schema::create($name, function (Blueprint $table) use ($definition) {
                foreach ($definition['fields'] as $name => $field) {
                    if ($name === 'id') {
                        $table->id();

                        continue;
                    }
                    $column = match ($field['type']) {
                        'INT', 'TINYINT' => isset($definition['foreign'][$name]) ? $table->unsignedBigInteger($name) : $table->integer($name),
                        'YEAR' => $table->unsignedSmallInteger($name),
                        'VARCHAR' => $table->string($name, $field['length'] ?? 255),
                        'ENUM' => $table->enum($name, $field['options']),
                        'TEXT' => $table->text($name), 'LONGTEXT' => $table->longText($name),
                        'DATE' => $table->date($name), 'TIME' => $table->time($name),
                        default => $table->dateTime($name),
                    };
                    if (! $field['required']) {
                        $column->nullable();
                    }
                    if (array_key_exists('default', $field)) {
                        if ($field['default'] === 'CURRENT_TIMESTAMP') {
                            $column->useCurrent();
                        } else {
                            $column->default($field['default']);
                        }
                    }
                    if ($field['unique']) {
                        $column->unique();
                    }
                }
                foreach ($definition['foreign'] as $name => $foreign) {
                    $key = $table->foreign($name)->references($foreign['column'])->on($foreign['table']);
                    $foreign['delete'] === 'CASCADE' ? $key->cascadeOnDelete() : $key->nullOnDelete();
                }
                if (isset($definition['fields']['status'])) {
                    $table->index('status');
                }
                if (isset($definition['fields']['deleted_at'])) {
                    $table->index('deleted_at');
                }
            });
        }
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        foreach (array_reverse(array_keys(config('drc_schema'))) as $name) {
            Schema::dropIfExists($name);
        }
    }
};
