<?php

namespace App\Support\Http;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class ListQuery
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 100;

    /**
     * Apply search/filter/sort/pagination to a list endpoint query.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<int, string>  $searchable  columns eligible for `?search=`
     * @param  array<int, string>  $filterable  columns eligible for `?filter[column]=value`
     * @param  array<int, string>  $sortable  columns eligible for `?sort=-column`
     * @return LengthAwarePaginator<int, TModel>
     */
    public static function paginate(
        Builder $query,
        Request $request,
        array $searchable = [],
        array $filterable = [],
        array $sortable = [],
    ): LengthAwarePaginator {
        $term = trim((string) $request->query('search', ''));

        if ($term !== '' && $searchable !== []) {
            $query->where(function (Builder $query) use ($searchable, $term): void {
                foreach ($searchable as $column) {
                    $query->orWhere($column, 'like', "%{$term}%");
                }
            });
        }

        foreach ((array) $request->query('filter', []) as $column => $value) {
            if (in_array($column, $filterable, true) && $value !== null && $value !== '') {
                $query->where($column, $value);
            }
        }

        $sort = trim((string) $request->query('sort', ''));

        if ($sort !== '') {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $column = ltrim($sort, '-');

            if (in_array($column, $sortable, true)) {
                $query->orderBy($column, $direction);
            }
        }

        $perPage = (int) $request->query('per_page', self::DEFAULT_PER_PAGE);
        $perPage = min(max($perPage, 1), self::MAX_PER_PAGE);

        return $query->paginate($perPage)->withQueryString();
    }
}
