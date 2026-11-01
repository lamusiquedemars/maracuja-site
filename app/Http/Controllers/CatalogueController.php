<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maracuja\SiteCore\Catalogue\Models\CatalogueCollection;
use Maracuja\SiteCore\Catalogue\Models\CatalogueItem;

class CatalogueController extends Controller
{
    public function index(Request $request)
    {
        $collection = CatalogueCollection::query()->where('is_published', true)->orderBy('id')->firstOrFail();
        $category = $request->string('categorie')->toString();
        $items = $collection->items()->public()->with('categories')
            ->when($category !== '', fn ($query) => $query->whereHas('categories', fn ($categories) => $categories->where('slug', $category)))
            ->orderBy('sort_order')->orderByDesc('published_at')->get();

        return view('site.catalogue.index', compact('collection', 'items', 'category'));
    }

    public function show(string $slug)
    {
        $collection = CatalogueCollection::query()->where('is_published', true)->orderBy('id')->firstOrFail();
        $item = $collection->items()->public()->with('categories')->where('slug', $slug)->firstOrFail();

        return view('site.catalogue.show', compact('collection', 'item'));
    }
}
