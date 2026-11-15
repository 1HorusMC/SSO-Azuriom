<?php

namespace Azuriom\Plugin\CasdoorAuth\Providers;

use Azuriom\Extensions\Plugin\BasePluginServiceProvider;
use Azuriom\Models\Permission;

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
