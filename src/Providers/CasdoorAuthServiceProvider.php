<?php

namespace Azuriom\Plugin\CasdoorAuth\Providers;

use Azuriom\Extensions\Plugin\BasePluginServiceProvider;
use Azuriom\Models\Permission;
use Illuminate\Support\Facades\Route;

class CasdoorAuthServiceProvider extends BasePluginServiceProvider
{
    /**
     * Register any plugin services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any plugin services.
     */
    public function boot(): void
    {
        $this->loadViews();
        $this->loadTranslations();

        if (! $this->app->routesAreCached()) {
            Route::middleware('web')
                ->group(plugin_path($this->plugin->id.'/routes/web.php'));

            Route::middleware(['web', 'admin-access'])
                ->prefix('admin/casdoor-auth')
                ->name('casdoor-auth.admin.')
                ->group(plugin_path($this->plugin->id.'/routes/admin.php'));
        }

        $this->registerAdminNavigation();

        Permission::registerPermissions([
            'casdoor-auth.admin' => 'casdoor-auth::admin.permission',
        ]);
    }

    /**
     * Return the admin navigation routes for this plugin.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function adminNavigation(): array
    {
        return [
            'casdoor-auth' => [
                'name' => 'Casdoor SSO',
                'icon' => 'bi bi-shield-lock',
                'route' => 'casdoor-auth.admin.settings',
                'permission' => 'casdoor-auth.admin',
            ],
        ];
    }
}
