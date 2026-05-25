<?php

namespace App\Http\Controllers;

use App\Models\Webflow\WindowsWebflowItem;

class ClassicSiteController extends Controller
{
    private const SHARED_CSS_HREF = '/webflow-overrides/classic-shared.css?v=1';

    public function home()
    {
        return $this->renderMirrorView('webflow.mirror.home');
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

        return $this->renderMirrorView($viewName, [
            'windowItem' => $window,
            'windowFieldData' => is_array($window->field_data ?? null) ? $window->field_data : [],
        ]);
    }

    private function renderMirrorView(string $viewName, array $data = [])
    {
        abort_if(! view()->exists($viewName), 404);

        $html = view($viewName, $data)->render();
        if (! str_contains($html, '/webflow-overrides/classic-shared.css')) {
            $linkTag = '<link href="'.self::SHARED_CSS_HREF.'" rel="stylesheet" type="text/css"/>';
            $html = preg_replace('/<\/head>/i', $linkTag.'</head>', $html, 1) ?? ($html."\n".$linkTag);
        }

        return response($html);
    }
}
