<?php

namespace Tests\Feature\Articles;

use App\Filament\Resources\Articles\Pages\ManageArticles;
use App\Models\User;
use App\Modules\Articles\Models\Article;
use App\Modules\Media\Enums\MediaType;
use App\Modules\Media\Models\MediaAsset;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArticleResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_editor_can_save_an_article_from_the_administration(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $media = MediaAsset::query()->create([
            'type' => MediaType::Image,
            'disk' => 'public',
            'path' => 'media/images/demo.png',
            'original_name' => 'demo.png',
            'display_name' => 'Image de démonstration',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size' => 1024,
            'width' => 640,
            'height' => 480,
            'checksum' => str_repeat('a', 64),
        ]);
        $article = Article::query()->create([
            'title' => 'Article initial',
            'slug' => 'article-initial',
            'excerpt' => 'Résumé initial.',
            'body_blocks' => [],
            'is_published' => false,
        ]);

        $this->actingAs($editor);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ManageArticles::class)
            ->callTableAction('edit', $article, [
                'title' => 'Article enregistré',
                'slug' => 'article-enregistre',
                'excerpt' => 'Résumé enregistré.',
                'image_media_id' => $media->id,
                'body_blocks' => [],
                'is_published' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'title' => 'Article enregistré',
            'slug' => 'article-enregistre',
            'image_media_id' => $media->id,
            'is_published' => true,
        ]);
    }
}
