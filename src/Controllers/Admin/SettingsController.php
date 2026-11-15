<?php

namespace Azuriom\Plugin\CasdoorAuth\Controllers\Admin;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Models\Role;
use Azuriom\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Display the CasdoorAuth settings page.
     */
    public function show(): View
    {
        $rawMappings = setting('casdoor.role_mappings', '[]');
        $mappings = is_string($rawMappings) ? (json_decode($rawMappings, true) ?? []) : [];

        return view('casdoor-auth::admin.settings', [
            'casdoorUrl' => setting('casdoor.url', ''),
            'clientId' => setting('casdoor.client_id', ''),
            'clientSecret' => setting('casdoor.client_secret', ''),
            'buttonText' => setting('casdoor.button_text', 'Iniciar sesión con Casdoor SSO'),
            'dividerText' => setting('casdoor.divider_text', 'O continuar con'),
            'buttonColor' => setting('casdoor.button_color', '#dc2626'),
            'buttonIcon' => setting('casdoor.button_icon', ''),
            'mappings' => $mappings,
            'roles' => Role::orderByDesc('power')->get(),
        ]);
    }

    /**
     * Save the CasdoorAuth settings.
     */
    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:255'],
            'client_id' => ['required', 'string', 'max:255'],
            'client_secret' => ['required', 'string', 'max:255'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'divider_text' => ['nullable', 'string', 'max:100'],
            'button_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'button_icon' => ['nullable', 'string', 'max:100'],
            'groups' => ['nullable', 'array'],
            'groups.*' => ['nullable', 'string', 'max:255'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['nullable', 'integer', 'exists:roles,id'],
        ]);

        $groups = $request->input('groups', []);
        $roleIds = $request->input('roles', []);
        $roleMappings = [];

        foreach ($groups as $index => $groupName) {
            $groupName = trim((string) $groupName);
            $roleId = $roleIds[$index] ?? null;

            if ($groupName !== '' && ! empty($roleId)) {
                $roleMappings[$groupName] = (int) $roleId;
            }
        }

        Setting::updateSettings([
            'casdoor.url' => rtrim($validated['url'], '/'),
            'casdoor.client_id' => trim($validated['client_id']),
            'casdoor.client_secret' => trim($validated['client_secret']),
            'casdoor.button_text' => trim((string) ($validated['button_text'] ?? 'Iniciar sesión con Casdoor SSO')),
            'casdoor.divider_text' => trim((string) ($validated['divider_text'] ?? 'O continuar con')),
            'casdoor.button_color' => $validated['button_color'] ?? '#dc2626',
            'casdoor.button_icon' => trim((string) ($validated['button_icon'] ?? '')),
            'casdoor.role_mappings' => json_encode($roleMappings, JSON_UNESCAPED_UNICODE),
        ]);

        return redirect()->route('casdoor-auth.admin.settings')
            ->with('success', trans('admin.settings.updated'));
    }
}
