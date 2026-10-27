<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class VisitBotFilter extends Filter
{
    public function name(): string
    {
        return 'Bot';
    }

    public function parameters(): ?array
    {
        return ['filter.bot'];
    }

    public function run(Builder $builder): Builder
    {
        $value = (string) $this->request->input('filter.bot', '');

        if ($value === 'yes') {
            return $builder->where('is_bot', true);
        }

        if ($value === 'no') {
            return $builder->where('is_bot', false);
        }

        return $builder;
    }

    public function display(): array
    {
        return [
            Select::make('filter[bot]')
                ->title('Bot')
                ->empty('All')
                ->options([
                    'no' => 'People',
                    'yes' => 'Bots',
                ])
                ->value($this->request->input('filter.bot')),
        ];
    }

    public function value(): string
    {
        $value = (string) $this->request->input('filter.bot', '');

        return $this->name().': '.($value === 'yes' ? 'Bots' : 'People');
    }
}
