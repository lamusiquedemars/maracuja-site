<?php

namespace App\Providers\Filament;

use App\Filament\Auth\CremonaSsoLogin;
use App\Filament\Widgets\AdminOverview;
use App\Filament\Widgets\AdminWelcome;
use App\Modules\SiteSettings\Models\SiteSetting;
use App\Support\Modules;
use Filament\Actions\Action;
use Filament\Auth\Pages\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Maracuja\SiteCore\Audience\Filament\Pages\AudienceHome;
use Maracuja\SiteCore\Catalogue\Filament\Pages\CatalogueHome;
use Maracuja\SiteCore\ProfessionalWorkspace\Filament\ProfessionalWorkspacePlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login(config('cremona-portal.enabled') ? CremonaSsoLogin::class : Login::class)
            ->userMenuItems(config('cremona-portal.enabled') ? [
                'logout' => Action::make('logout')
                    ->label('Se déconnecter de l’espace')
                    ->icon('heroicon-o-arrow-left-end-on-rectangle')
                    ->url(fn (): string => route('cremona-sso.logout'))
                    ->postToUrl(),
            ] : [])
            ->profile()
            ->brandName(fn (): string => Schema::hasTable('site_settings')
                ? SiteSetting::query()->value('site_name') ?: config('maracuja.product_name', 'Maracuja CMS')
                : config('maracuja.product_name', 'Maracuja CMS'))
            ->brandLogo(fn (): ?string => Schema::hasTable('site_settings')
                ? SiteSetting::query()->first()?->logoUrl()
                : null)
            ->brandLogoHeight('2.5rem')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->assets([
                Js::make('safari-livewire-upload-fix', resource_path('js/filament/safari-livewire-upload-fix.js')),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->plugin(new ProfessionalWorkspacePlugin)
            ->widgets([
                AccountWidget::class,
                AdminWelcome::class,
                AdminOverview::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        if (Modules::enabled('contact_form') && ! app(\App\Services\CremonaIncomingRequestSender::class)->enabled() && is_dir(base_path('vendor/maracuja-digital/site-core-contact/src/Filament/Resources'))) {
            $panel->discoverResources(
                in: base_path('vendor/maracuja-digital/site-core-contact/src/Filament/Resources'),
                for: 'Maracuja\SiteCore\Contact\Filament\Resources',
            );
        }

        if (Modules::enabled('catalogue') && is_dir(base_path('vendor/maracuja-digital/site-core-catalogue/src/Filament/Resources'))) {
            $panel->discoverResources(
                in: base_path('vendor/maracuja-digital/site-core-catalogue/src/Filament/Resources'),
                for: 'Maracuja\\SiteCore\\Catalogue\\Filament\\Resources',
            );
            $panel->pages([CatalogueHome::class]);
        }

        if (Modules::enabled('audience') && is_dir(base_path('vendor/maracuja-digital/site-core-audience/src/Filament/Resources'))) {
            $panel->discoverResources(
                in: base_path('vendor/maracuja-digital/site-core-audience/src/Filament/Resources'),
                for: 'Maracuja\SiteCore\Audience\Filament\Resources',
            );
            $panel->pages([
                AudienceHome::class,
            ]);
        }

        if (Modules::enabled('brevo') && is_dir(base_path('vendor/maracuja-digital/site-core-brevo/src/Filament/Resources'))) {
            $panel->discoverResources(
                in: base_path('vendor/maracuja-digital/site-core-brevo/src/Filament/Resources'),
                for: 'Maracuja\SiteCore\Brevo\Filament\Resources',
            );
        }

        if (Modules::enabled('events') && is_dir(base_path('vendor/maracuja-digital/site-core-events/src/Filament/Resources'))) {
            $panel->discoverResources(
                in: base_path('vendor/maracuja-digital/site-core-events/src/Filament/Resources'),
                for: 'Maracuja\\SiteCore\\Events\\Filament\\Resources',
            );
        }

        if (Modules::enabled('assistant') && is_dir(base_path('vendor/maracuja-digital/site-core-assistant/src/Filament/Resources'))) {
            $panel->discoverResources(
                in: base_path('vendor/maracuja-digital/site-core-assistant/src/Filament/Resources'),
                for: 'Maracuja\\SiteCore\\Assistant\\Filament\\Resources',
            );
        }

        if (is_dir(app_path('Modules/Media/Filament/Resources'))) {
            $panel->discoverResources(
                in: app_path('Modules/Media/Filament/Resources'),
                for: 'App\Modules\Media\Filament\Resources',
            );
        }

        return $panel;
    }
}
