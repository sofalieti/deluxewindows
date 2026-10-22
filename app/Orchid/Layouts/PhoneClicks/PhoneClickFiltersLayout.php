<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\PhoneClicks;

use App\Orchid\Filters\PhoneClickTrafficSourceFilter;
use Orchid\Filters\Filter;
use Orchid\Screen\Layouts\Selection;

class PhoneClickFiltersLayout extends Selection
{
    /**
     * @return string[]|Filter[]
     */
    public function filters(): array
    {
        return [
            PhoneClickTrafficSourceFilter::class,
        ];
    }
}
