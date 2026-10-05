<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    protected $guarded = ['id'];

    public static function forTable(string $table): self
    {
        abort_unless(array_key_exists($table, config('drc_schema')), 404);
        $model = new self;
        $model->setTable($table);
        $model->fillable(array_values(array_diff(array_keys(config("drc_schema.$table.fields")), ['id'])));
        $model->timestamps = isset(config("drc_schema.$table.fields")['updated_at']);

        return $model;
    }

    public static function visible(string $table)
    {
        $query = self::forTable($table)->newQuery();
        $fields = config("drc_schema.$table.fields");
        if (isset($fields['deleted_at'])) {
            $query->whereNull('deleted_at');
        }
        if (isset($fields['status'])) {
            $query->where('status', in_array('published', $fields['status']['options']) ? 'published' : 'active');
        }
        if (isset($fields['published_at'])) {
            $query->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
        }

        return $query;
    }

    public function label(): string
    {
        return $this->title ?? $this->name ?? $this->question ?? $this->platform ?? '#'.$this->id;
    }
}
