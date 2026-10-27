<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Visits;

use App\Orchid\Filters\VisitBotFilter;
use App\Orchid\Filters\VisitConversionFilter;
use Orchid\Filters\Filter;
use Orchid\Screen\Layouts\Selection;

class VisitFiltersLayout extends Selection
{
    /**
     * @return string[]|Filter[]
     */
    public function filters(): array
    {
        return [
            VisitConversionFilter::class,
            VisitBotFilter::class,
        ];
    }
}
