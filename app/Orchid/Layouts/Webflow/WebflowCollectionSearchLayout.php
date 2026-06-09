<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Webflow;

use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Rows;

class WebflowCollectionSearchLayout extends Rows
{
    protected function fields(): array
    {
        return [
            Input::make('search')
                ->type('search')
                ->title('Search by name')
                ->value(request()->query('search', ''))
                ->placeholder('Type item name…')
                ->help('Filters the list by the Webflow "name" field.'),
        ];
    }
}
