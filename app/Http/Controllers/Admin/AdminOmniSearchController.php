<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Services\AdminOmniSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOmniSearchController
{
    public function __invoke(Request $request, AdminOmniSearch $search): JsonResponse
    {
        return response()->json([
            'groups' => $search->groups($request->user(), (string) $request->query('q', '')),
        ]);
    }
}
