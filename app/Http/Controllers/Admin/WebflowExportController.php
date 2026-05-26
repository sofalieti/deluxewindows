<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\WebflowCollectionRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WebflowExportController extends Controller
{
    public function export(string $collection): StreamedResponse
    {
        $meta = WebflowCollectionRegistry::find($collection);
        abort_if($meta === null, 404);

        if (! Schema::hasTable((string) $meta['table'])) {
            abort(404, 'Collection table not found.');
        }

        $filename = $collection.'-export-'.date('Y-m-d').'.json';

        return response()->streamDownload(function () use ($meta) {
            $items = DB::table((string) $meta['table'])->orderBy('id')->get()->map(function ($row) {
                $item = (array) $row;
                if (isset($item['field_data']) && is_string($item['field_data'])) {
                    $decoded = json_decode($item['field_data'], true);
                    $item['field_data'] = is_array($decoded) ? $decoded : [];
                }
                return $item;
            })->values()->all();

            echo json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }
}
