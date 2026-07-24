<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Leads;

use App\Orchid\Filters\LeadStatusFilter;
use Orchid\Filters\Filter;
use Orchid\Screen\Layouts\Selection;

class LeadFiltersLayout extends Selection
{
    /**
     * @return string[]|Filter[]
     */
    public function filters(): array
    {
        return [
            LeadStatusFilter::class,
        ];
    }
}
