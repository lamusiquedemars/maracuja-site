<?php

namespace App\Support;

use Maracuja\SiteCore\Catalogue\Models\CatalogueCollection;

final class PublicCatalogue
{
    public static function published(): ?CatalogueCollection
    {
        return CatalogueCollection::query()
            ->where('is_published', true)
            ->orderBy('id')
            ->first();
    }

    public static function label(?CatalogueCollection $collection): string
    {
        $label = trim($collection?->plural_label ?? '');

        return $label !== '' ? $label : 'Catalogue';
    }
}
