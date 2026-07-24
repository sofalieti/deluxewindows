<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class LeadStatusFilter extends Filter
{
    public function name(): string
    {
        return 'Status';
    }

    public function parameters(): ?array
    {
        return ['filter.status'];
    }

    public function run(Builder $builder): Builder
    {
        $status = trim((string) $this->request->input('filter.status', ''));

        if ($status === '' || ! array_key_exists($status, Lead::STATUSES)) {
            return $builder;
        }

        return $builder->where('status', $status);
    }

    public function display(): array
    {
        return [
            Select::make('filter[status]')
                ->title('Status')
                ->empty('All (hide spam)', '')
                ->options(Lead::STATUSES)
                ->value($this->request->input('filter.status')),
        ];
    }

    public function value(): string
    {
        $status = (string) $this->request->input('filter.status', '');
        $label = Lead::STATUSES[$status] ?? $status;

        return $this->name().': '.$label;
    }
}
