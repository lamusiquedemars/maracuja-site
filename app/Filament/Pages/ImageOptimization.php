<?php

namespace App\Filament\Pages;

use App\Support\Images\SiteImageOptimizer;
use App\Support\Modules;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ImageOptimization extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Outils';

    protected static ?string $navigationLabel = 'Optimisation images';

    protected static ?string $title = 'Optimisation des images';

    protected static ?string $slug = 'tools/images';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.image-optimization';

    /**
     * @var array{checked: int, resized: int, errors: int, remaining: int, stopped_early: bool, images: list<array<string, mixed>>}|null
     */
    public ?array $result = null;

    public bool $isOptimizing = false;

    public int $initialRemaining = 0;

    public int $remaining = 0;

    public int $totalChecked = 0;

    public int $totalResized = 0;

    public int $totalErrors = 0;

    /**
     * @var list<array<string, mixed>>
     */
    public array $optimizedImages = [];

    public function mount(): void
    {
        $this->configureLongRunningRequest();

        $this->remaining = app(SiteImageOptimizer::class)->countPending(public_path('assets/images'));
        $this->initialRemaining = $this->remaining;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        return Modules::developerToolEnabled('image_optimization') && (auth()->user()?->isAdministrator() ?? false) && parent::canAccess();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('optimize')
                ->label('Alléger les images')
                ->color('warning')
                ->icon(Heroicon::OutlinedBolt)
                ->requiresConfirmation()
                ->modalHeading('Alléger les images du site')
                ->modalDescription('Les JPEG et PNG trop larges seront redimensionnés automatiquement dans public/assets/images. Le traitement reste découpé côté serveur pour éviter les timeouts, mais il s’enchaîne sans clic supplémentaire.')
                ->disabled(fn (): bool => $this->isOptimizing || $this->remaining === 0)
                ->action(function (): void {
                    $this->startOptimization();
                }),
        ];
    }

    public function startOptimization(): void
    {
        $this->configureLongRunningRequest();

        $this->isOptimizing = true;
        $this->totalChecked = 0;
        $this->totalResized = 0;
        $this->totalErrors = 0;
        $this->optimizedImages = [];
        $this->remaining = app(SiteImageOptimizer::class)->countPending(public_path('assets/images'));
        $this->initialRemaining = $this->remaining;
        $this->result = null;

        if ($this->remaining === 0) {
            $this->finishOptimization();
        }
    }

    public function processNextBatch(): void
    {
        if (! $this->isOptimizing) {
            return;
        }

        $this->configureLongRunningRequest();

        $this->result = app(SiteImageOptimizer::class)->optimizeDirectory(
            public_path('assets/images'),
            recursive: true,
        );

        $this->totalChecked += $this->result['checked'];
        $this->totalResized += $this->result['resized'];
        $this->totalErrors += $this->result['errors'];
        $this->remaining = $this->result['remaining'];
        $this->optimizedImages = [
            ...$this->optimizedImages,
            ...$this->result['images'],
        ];

        if ($this->remaining === 0) {
            $this->finishOptimization();
        }
    }

    protected function finishOptimization(): void
    {
        $this->isOptimizing = false;

        Notification::make()
            ->title('Optimisation terminée')
            ->body("{$this->totalResized} image(s) redimensionnée(s), {$this->totalChecked} traitée(s), {$this->totalErrors} erreur(s).")
            ->success()
            ->send();
    }

    public function progressPercent(): int
    {
        if ($this->initialRemaining <= 0) {
            return $this->remaining === 0 ? 100 : 0;
        }

        $done = max(0, $this->initialRemaining - $this->remaining);

        return min(100, (int) round(($done / $this->initialRemaining) * 100));
    }

    protected function configureLongRunningRequest(): void
    {
        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / 1024 / 1024, 2, ',', ' ').' Mo';
        }

        return number_format($bytes / 1024, 0, ',', ' ').' Ko';
    }
}
