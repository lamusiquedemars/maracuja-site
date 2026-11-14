<?php

namespace App\Http\Controllers;

use App\Modules\SiteSettings\Models\SiteSetting;
use App\Support\Modules;
use App\Support\PublicCatalogue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CatalogueController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(Modules::enabled('catalogue'), 404);

        $collection = PublicCatalogue::published();
        $category = $request->string('categorie')->toString();
        $items = $collection?->items()->public()
            ->when($category !== '', fn ($query) => $query->whereHas('categories', fn ($categories) => $categories->where('slug', $category)))
            ->orderBy('sort_order')->orderByDesc('published_at')->get() ?? collect();

        return view('site.catalogue.index', [
            'settings' => SiteSetting::current(),
            'catalogueLabel' => PublicCatalogue::label($collection),
            'collection' => $collection,
            'items' => $items,
            'category' => $category,
        ]);
    }

    public function show(string $slug): View
    {
        abort_unless(Modules::enabled('catalogue'), 404);

        $collection = PublicCatalogue::published();
        abort_if($collection === null, 404);

        $item = $collection->items()->public()->where('slug', $slug)->firstOrFail();

        return view('site.catalogue.show', [
            'settings' => SiteSetting::current(),
            'catalogueLabel' => PublicCatalogue::label($collection),
            'item' => $item,
            'media' => $item->primaryMedia(),
        ]);
    }
}
