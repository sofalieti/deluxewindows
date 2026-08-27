<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Crm;

use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Rows;

class CloseTaskModalLayout extends Rows
{
    protected function fields(): iterable
    {
        return [
            Input::make('task')
                ->type('hidden'),
            TextArea::make('result')
                ->title('Comment')
                ->rows(4)
                ->required()
                ->maxlength(1000)
                ->help('Required. What happened with this task?'),
        ];
    }
}
