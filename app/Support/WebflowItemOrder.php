<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class WebflowItemOrder
{
    public const MISSING = 999999;

    public static function key(mixed $item): int
    {
        $fieldData = self::fieldData($item);
        $order = $fieldData['order'] ?? null;

        if (is_numeric($order)) {
            return (int) $order;
        }

        return self::MISSING;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     *
     * @param  Collection<TKey, TValue>|iterable<TKey, TValue>  $items
     * @return Collection<int, TValue>
     */
    public static function sort(iterable $items): Collection
    {
        return collect($items)
            ->sortBy([
                fn ($item) => self::key($item),
                fn ($item) => (int) data_get($item, 'id', 0),
            ])
            ->values();
    }

    /**
     * Persist sequential 1..N order for the given item ids in a wf_* table.
     *
     * @param  list<int>  $itemIds
     */
    public static function saveOrder(string $table, array $itemIds): int
    {
        $itemIds = array_values(array_filter(
            array_map(static fn ($id) => (int) $id, $itemIds),
            static fn (int $id) => $id > 0
        ));

        if ($itemIds === [] || ! Schema::hasTable($table)) {
            return 0;
        }

        $hasWfOrder = Schema::hasColumn($table, 'wf_order');
        $updated = 0;

        foreach ($itemIds as $index => $itemId) {
            $order = $index + 1;
            $row = DB::table($table)->where('id', $itemId)->first();
            if ($row === null) {
                continue;
            }

            $fieldData = self::decodeFieldData($row->field_data ?? null);
            $fieldData['order'] = $order;

            $payload = [
                'field_data' => json_encode($fieldData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ];

            if ($hasWfOrder) {
                $payload['wf_order'] = $order;
            }

            DB::table($table)->where('id', $itemId)->update($payload);
            $updated++;
        }

        return $updated;
    }

    /**
     * @return array<string, mixed>
     */
    public static function fieldData(mixed $item): array
    {
        if (is_object($item) && method_exists($item, 'getContent')) {
            $value = $item->getContent('field_data');
            if (is_array($value)) {
                return $value;
            }
            if (is_string($value) && $value !== '') {
                return self::decodeFieldData($value);
            }

            return [];
        }

        $value = data_get($item, 'field_data');
        if (is_array($value)) {
            return $value;
        }
        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        return self::decodeFieldData($value);
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeFieldData(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
