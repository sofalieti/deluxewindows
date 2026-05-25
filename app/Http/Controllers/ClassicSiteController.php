<?php

namespace App\Http\Controllers;

use App\Models\Webflow\WindowsWebflowItem;

class ClassicSiteController extends Controller
{
    public function home()
    {
        return view('webflow.mirror.home');
    }

    public function windowBySlug(string $slug)
    {
        $slug = strtolower(trim($slug));

        $window = WindowsWebflowItem::query()
            ->where('field_data->slug', $slug)
            ->orWhere('webflow_item_id', $slug)
            ->orderByDesc('id')
            ->first();

        abort_if(! $window, 404);

        $viewName = 'webflow.mirror.windows.'.$slug;
        abort_if(! view()->exists($viewName), 404);

        return view($viewName, [
            'windowItem' => $window,
            'windowFieldData' => is_array($window->field_data ?? null) ? $window->field_data : [],
        ]);
    }
}
