<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

/**
 * One search box for the CRM lists. A phone fragment matches stored digits
 * even when the query still has spaces, dashes, or parentheses.
 */
final class CrmSmartSearch
{
    public function term(?string $term = null): string
    {
        $term ??= (string) request()->input('q', '');

        return trim($term);
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  list<string>  $columns
     * @param  list<string>  $phoneColumns
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public function apply(Builder $query, array $columns, array $phoneColumns = [], ?string $term = null, ?\Closure $extra = null): Builder
    {
        $term = $this->term($term);
        if ($term === '') {
            return $query;
        }

        $tokens = preg_split('/\s+/', $term) ?: [];

        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }

            $like = '%'.$this->escapeLike($token).'%';
            $digits = preg_replace('/\D+/', '', $token) ?? '';

            $query->where(function (Builder $inner) use ($columns, $phoneColumns, $like, $digits, $token, $extra): void {
                foreach ($columns as $column) {
                    $inner->orWhere($column, 'like', $like);
                }

                if ($digits !== '' && strlen($digits) >= 3) {
                    $digitLike = '%'.$digits.'%';
                    foreach ($phoneColumns as $column) {
                        $inner->orWhere($column, 'like', $digitLike);
                    }
                }

                if ($digits !== '' && $digits === $token && strlen($digits) <= 7) {
                    $inner->orWhere($inner->getModel()->getQualifiedKeyName(), (int) $digits);
                }

                if ($extra !== null) {
                    $extra($inner, $like, $digits);
                }
            });
        }

        return $query;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
