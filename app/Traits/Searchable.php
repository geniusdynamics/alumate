<?php

namespace App\Traits;

trait Searchable
{
    /**
     * Apply quick search to a query based on given columns.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $search
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeQuickSearch($query, $search = null, $columns = [])
    {
        if ($search) {
            $query->where(function ($q) use ($search, $columns) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'LIKE', "%{$search}%");
                }
            });
        }
        return $query;
    }
}

