<?php

namespace App\Support\Scoping;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * @implements Scope<Model>
 */
final class BelongsToInstitutionScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! $model instanceof ScopesToInstitution) {
            return;
        }

        $context = app(InstitutionContextResolver::class)->resolve();

        foreach ($model->institutionScopeColumns() as $column) {
            if (! array_key_exists($column, $context)) {
                continue;
            }

            $value = $context[$column];

            if (is_array($value)) {
                $builder->whereIn($column, $value);
            } else {
                $builder->where($column, $value);
            }
        }
    }
}
