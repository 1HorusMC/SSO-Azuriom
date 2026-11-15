<?php

namespace Azuriom\Plugin\CasdoorAuth\Providers;

use Azuriom\Extensions\Plugin\BaseRouteServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    /**
     * Define the routes for the plugin.
     */
    public function loadRoutes(): void
    {
        Route::middleware('web')
            ->group(plugin_path($this->plugin->id.'/routes/web.php'));

        Route::middleware(['web', 'admin-access'])
            ->prefix('admin/casdoor-auth')
            ->name('casdoor-auth.admin.')
            ->group(plugin_path($this->plugin->id.'/routes/admin.php'));
    }
}
