<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait HasDataTable
{
    /**
     * Apply search filters to the query
     */
    public function scopeApplySearch(Builder $query, Request $request): Builder
    {
        $search = $request->get('search');
        $searchableColumns = $this->searchableColumns ?? [];

        if ($search && ! empty($searchableColumns)) {
            $query->where(function ($q) use ($search, $searchableColumns) {
                foreach ($searchableColumns as $column) {
                    if (str_contains($column, '.')) {
                        $segments = explode('.', $column);
                        $relationColumn = array_pop($segments);
                        $relations = implode('.', $segments);

                        $q->orWhereHas($relations, function ($relationQuery) use ($search, $relationColumn) {
                            $relationQuery->where($relationColumn, 'like', "%{$search}%");
                        });
                    } else {
                        $q->orWhere($column, 'like', "%{$search}%");
                    }
                }
            });
        }

        return $query;
    }

    /**
     * Apply sorting to the query
     */
    public function scopeApplySort(Builder $query, Request $request): Builder
    {
        $sortColumn = $request->get('sort_column');
        $sortDirection = $request->get('sort_direction', 'asc');
        $sortableColumns = $this->sortableColumns ?? [];

        if ($sortColumn && in_array($sortColumn, $sortableColumns)) {
            if (str_contains($sortColumn, '.')) {
                [$relation, $relationColumn] = explode('.', $sortColumn);

                // Alias to avoid ambiguous columns
                $relationTable = app($this->$relation()->getRelated()::class)->getTable();
                $alias = $relationTable.'_'.$relation;

                $query->leftJoin(
                    $relationTable.' as '.$alias,
                    $this->getTable().'.id',
                    '=',
                    $alias.'.id'
                )->orderBy($alias.'.'.$relationColumn, $sortDirection);
            } else {
                $query->orderBy($sortColumn, $sortDirection);
            }
        }

        return $query;
    }

    /**
     * Apply pagination to the query
     */
    public function scopeApplyPagination(Builder $query, Request $request): LengthAwarePaginator
    {
        $perPage = $request->get('per_page', 15);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 15;

        return $query->paginate($perPage)
            ->appends($request->query());
    }

    /**
     * Scope to process full data table request
     */
    public function scopeProcessDataTable(
        Builder $query,
        Request $request,
        ?string $resourceClass = null,
        bool $associative = false
    ): array {
        $paginator = $query
            ->applySearch($request)
            ->applySort($request)
            ->applyPagination($request);

        $items = $resourceClass
            ? $resourceClass::collection($paginator->items())
            : $paginator->items();

        $meta = $this->formatPaginationData($paginator);

        if ($resourceClass || $associative) {
            return [
                'data' => $items,
                'meta' => $meta,
            ];
        }

        return [$items, $meta];
    }

    /**
     * Format pagination data for frontend
     */
    public function formatPaginationData(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
