<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class VisitConversionFilter extends Filter
{
    public function name(): string
    {
        return 'Converted';
    }

    public function parameters(): ?array
    {
        return ['filter.converted'];
    }

    public function run(Builder $builder): Builder
    {
        $value = (string) $this->request->input('filter.converted', '');

        if ($value === 'yes') {
            return $builder->converted();
        }

        if ($value === 'no') {
            return $builder->notConverted();
        }

        return $builder;
    }

    public function display(): array
    {
        return [
            Select::make('filter[converted]')
                ->title('Converted')
                ->empty('All')
                ->options([
                    'yes' => 'Converted',
                    'no' => 'Not converted',
                ])
                ->value($this->request->input('filter.converted')),
        ];
    }

    public function value(): string
    {
        $value = (string) $this->request->input('filter.converted', '');
        $label = $value === 'yes' ? 'Converted' : 'Not converted';

        return $this->name().': '.$label;
    }
}
