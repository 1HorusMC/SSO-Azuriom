<?php

namespace Azuriom\Plugin\CasdoorAuth\Providers;

use Azuriom\Extensions\Plugin\BasePluginServiceProvider;
use Azuriom\Models\Permission;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

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

        View::composer(['auth.login', 'auth.register'], function ($view) {
            if (! setting('casdoor.url') || ! setting('casdoor.client_id')) {
                return;
            }

            $buttonHtml = view('casdoor-auth::partials.login-button')->render();
            $script = '<script>document.addEventListener("DOMContentLoaded",function(){'
                .'var form=document.querySelector("form[action*=\"/login\"], form[action*=\"/register\"]");'
                .'if(form&&!document.getElementById("casdoor-sso-btn")){'
                .'var div=document.createElement("div");div.id="casdoor-sso-btn";div.innerHTML='.json_encode($buttonHtml).';'
                .'form.insertAdjacentElement("afterend",div);'
                .'}});</script>';

            $view->getFactory()->startPush('scripts', $script);
        });

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
