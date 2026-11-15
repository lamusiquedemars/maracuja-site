<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Maracuja\SiteCore\Catalogue\Models\CatalogueCollection;
use Tests\TestCase;

class PublicCatalogueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['maracuja.modules.catalogue' => true]);
    }

    public function test_the_catalogue_is_in_the_menu_before_any_collection_is_published(): void
    {
        $draft = CatalogueCollection::query()->create([
            'name' => 'Collection privée',
            'plural_label' => 'Libellé en brouillon',
        ]);
        $draft->items()->create([
            'title' => 'Fiche dans un catalogue privé',
            'slug' => 'fiche-privee',
            'publication_status' => 'published',
        ]);

        $url = route('catalogue.index');

        $this->get('/')
            ->assertOk()
            ->assertSee('<a href="'.$url.'">Catalogue</a>', false)
            ->assertDontSee('Libellé en brouillon');

        $this->get($url)
            ->assertOk()
            ->assertSee('<h1>Catalogue</h1>', false)
            ->assertSee('Aucune fiche publiée pour le moment.')
            ->assertDontSee('Fiche dans un catalogue privé');

        $this->get(route('catalogue.show', 'fiche-privee'))->assertNotFound();
    }

    public function test_the_menu_and_heading_follow_the_public_label_at_the_same_url(): void
    {
        $catalogue = CatalogueCollection::query()->create([
            'name' => 'Nom interne du catalogue',
            'plural_label' => 'Réalisations',
            'is_published' => true,
        ]);

        $url = route('catalogue.index');

        $this->get('/')
            ->assertOk()
            ->assertSee('<a href="'.$url.'">Réalisations</a>', false);

        $this->get($url)
            ->assertOk()
            ->assertSee('<h1>Réalisations</h1>', false)
            ->assertSee('Aucune fiche publiée pour le moment.')
            ->assertDontSee('Nom interne du catalogue');

        $catalogue->update(['plural_label' => 'Nos projets']);

        $this->get($url)
            ->assertOk()
            ->assertSee('<a href="'.$url.'">Nos projets</a>', false)
            ->assertSee('<h1>Nos projets</h1>', false);
    }

    public function test_published_cards_filter_by_category_and_open_their_public_detail(): void
    {
        $catalogue = CatalogueCollection::query()->create([
            'name' => 'Catalogue de démonstration',
            'plural_label' => 'Réalisations',
            'is_published' => true,
        ]);
        $category = $catalogue->categories()->create(['name' => 'Lutherie', 'slug' => 'lutherie']);
        $project = $catalogue->items()->create([
            'title' => 'Un violon dans l’âme',
            'slug' => 'un-violon-dans-lame',
            'excerpt' => 'Site vitrine d’un atelier de lutherie.',
            'description' => 'Présentation du site de David.',
            'media' => [['url' => '/images/atelier.jpg', 'alt' => 'L’atelier de David']],
            'publication_status' => 'published',
        ]);
        $project->categories()->attach($category);
        $catalogue->items()->create([
            'title' => 'Prototype en brouillon',
            'slug' => 'prototype',
            'publication_status' => 'draft',
        ])->categories()->attach($category);
        $other = $catalogue->items()->create([
            'title' => 'Autre projet',
            'slug' => 'autre-projet',
            'price_amount' => 0,
            'publication_status' => 'published',
        ]);

        $detailUrl = route('catalogue.show', $project->slug);

        $this->get(route('catalogue.index'))
            ->assertOk()
            ->assertSee('Lutherie')
            ->assertSee('href="'.$detailUrl.'"', false)
            ->assertSee($project->title)
            ->assertSee($project->excerpt)
            ->assertSee($other->title)
            ->assertSee('0,00 €')
            ->assertDontSee('Prototype en brouillon')
            ->assertDontSee('Prix sur demande');

        $this->get(route('catalogue.index', ['categorie' => 'lutherie']))
            ->assertOk()
            ->assertSee($project->title)
            ->assertDontSee($other->title)
            ->assertDontSee('Prototype en brouillon');

        $this->get($detailUrl)
            ->assertOk()
            ->assertSee($project->description)
            ->assertSee('src="/images/atelier.jpg"', false)
            ->assertDontSee('Prix sur demande');

        $this->get(route('catalogue.show', 'prototype'))->assertNotFound();
    }

    public function test_the_disabled_catalogue_has_no_public_menu_link_or_access(): void
    {
        $indexUrl = route('catalogue.index');
        $detailUrl = route('catalogue.show', 'fiche');
        config(['maracuja.modules.catalogue' => false]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('href="'.$indexUrl.'"', false);

        $this->get($indexUrl)->assertNotFound();
        $this->get($detailUrl)->assertNotFound();
    }
}
