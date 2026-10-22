<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use App\Models\PhoneClick;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class PhoneClickTrafficSourceFilter extends Filter
{
    public function name(): string
    {
        return 'Source';
    }

    public function parameters(): ?array
    {
        return ['filter.traffic_source'];
    }

    public function run(Builder $builder): Builder
    {
        $source = trim((string) $this->request->input('filter.traffic_source', ''));

        if ($source === '' || ! array_key_exists($source, PhoneClick::trafficSourceOptions())) {
            return $builder;
        }

        return $builder->where('traffic_source', $source);
    }

    public function display(): array
    {
        return [
            Select::make('filter[traffic_source]')
                ->title('Source')
                ->empty('All sources', '')
                ->options(PhoneClick::trafficSourceOptions())
                ->value($this->request->input('filter.traffic_source')),
        ];
    }

    public function value(): string
    {
        $source = (string) $this->request->input('filter.traffic_source', '');
        $label = PhoneClick::trafficSourceOptions()[$source] ?? $source;

        return $this->name().': '.$label;
    }
}
