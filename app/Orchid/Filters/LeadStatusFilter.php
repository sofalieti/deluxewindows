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
                ->empty('All (excl. spam)', '')
                ->options(array_filter(
                    Lead::STATUSES,
                    fn (string $key) => $key !== Lead::STATUS_SPAM,
                    ARRAY_FILTER_USE_KEY
                ))
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
