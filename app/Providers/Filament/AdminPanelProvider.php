<?php

namespace App\Providers\Filament;

use App\Filament\Resources\DbSetlistResource\Pages\CreateDbSetlist;
use App\Filament\Resources\DbSetlistResource\Pages\EditDbSetlist;
use App\Filament\Resources\SlSetlistResource\Pages\CreateSlSetlist;
use App\Filament\Resources\SlSetlistResource\Pages\EditSlSetlist;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // CloudflareがこのJSファイルを長時間（cache-control: max-age=14400）CDNキャッシュするため、
        // URLにクエリを付けずデプロイすると更新後も古い内容が配信され続ける。
        // ファイル更新時刻をクエリに付け、デプロイのたびにURL自体を変えてキャッシュを回避する。
        $draftAutosavePath = public_path('js/filament/draft-autosave.js');
        $draftAutosaveVersion = file_exists($draftAutosavePath) ? filemtime($draftAutosavePath) : time();

        FilamentAsset::register([
            Js::make('draft-autosave', asset('js/filament/draft-autosave.js') . '?v=' . $draftAutosaveVersion),
        ]);

        return $panel
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Yuki Official')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->renderHook(
                PanelsRenderHook::PAGE_START,
                fn (): string => view('filament.hooks.draft-autosave')->render(),
                scopes: [
                    CreateSlSetlist::class,
                    EditSlSetlist::class,
                    CreateDbSetlist::class,
                    EditDbSetlist::class,
                ],
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
