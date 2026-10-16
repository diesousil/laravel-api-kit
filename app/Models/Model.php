<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Model extends EloquentModel
{
    use HasUuids;
    use SoftDeletes;

    public function scopeSearch(Builder $query, array $fields = [], string $search = null)
    {
        if (!$search || count($fields) == 0) {
            return $query;
        }

        $sanitizedSearch = preg_replace('/[^a-zA-Z0-9\s]\@/', '', $search);
        $words           = array_filter(explode(' ', $sanitizedSearch));

        return $query->where(function ($q) use ($fields, $words) {
            foreach ($words as $word) {
                $q->where(function ($q) use ($fields, $word) {
                    foreach ($fields as $field) {
                        $q->orWhere($field, 'LIKE', "%{$word}%");
                    }
                });
            }
        });

    }
}
