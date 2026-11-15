<?php

namespace Azuriom\Plugin\CasdoorAuth\Controllers;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Models\Role;
use Azuriom\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CasdoorController extends Controller
{
    /**
     * Redirect the user to the Casdoor OAuth2/OIDC authorization endpoint.
     */
    public function redirect(Request $request): RedirectResponse
    {
        $baseUrl = rtrim((string) setting('casdoor.url'), '/');
        $clientId = (string) setting('casdoor.client_id');

        if ($baseUrl === '' || $clientId === '') {
            return redirect()->route('login')
                ->with('error', 'El inicio de sesión con Casdoor no está configurado.');
        }

        $state = Str::random(40);
        $request->session()->put('casdoor_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => route('casdoor.callback'),
            'scope' => 'openid profile email',
            'state' => $state,
        ]);

        return redirect()->away("{$baseUrl}/login/oauth/authorize?{$query}");
    }

    /**
     * Handle the OAuth2/OIDC callback from Casdoor.
     */
    public function callback(Request $request): RedirectResponse
    {
        $expectedState = $request->session()->pull('casdoor_oauth_state');
        $receivedState = (string) $request->query('state', '');

        if (! $expectedState || ! hash_equals($expectedState, $receivedState)) {
            return redirect()->route('login')
                ->with('error', 'Estado de sesión OAuth inválido. Por favor, inténtalo de nuevo.');
        }

        if ($request->filled('error')) {
            return redirect()->route('login')
                ->with('error', 'Error de autenticación en Casdoor: '.$request->query('error_description', $request->query('error')));
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect()->route('login')
                ->with('error', 'No se recibió el código de autorización desde Casdoor.');
        }

        $baseUrl = rtrim((string) setting('casdoor.url'), '/');
        $clientId = (string) setting('casdoor.client_id');
        $clientSecret = (string) setting('casdoor.client_secret');

        // 1. Exchange authorization code for access_token
        $tokenResponse = Http::asForm()
            ->acceptJson()
            ->post("{$baseUrl}/api/login/oauth/access_token", [
                'grant_type' => 'authorization_code',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
                'redirect_uri' => route('casdoor.callback'),
            ]);

        if ($tokenResponse->failed()) {
            return redirect()->route('login')
                ->with('error', 'No se pudo obtener el token de acceso desde Casdoor.');
        }

        $accessToken = $tokenResponse->json('access_token');
        if (empty($accessToken)) {
            return redirect()->route('login')
                ->with('error', 'Respuesta de token inválida desde Casdoor.');
        }

        // 2. Fetch user profile from OIDC userinfo endpoint
        $userInfoResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->get("{$baseUrl}/api/userinfo");

        if ($userInfoResponse->failed()) {
            return redirect()->route('login')
                ->with('error', 'No se pudo obtener la información del usuario desde Casdoor.');
        }

        $casdoorUser = $userInfoResponse->json();
        $email = $casdoorUser['email'] ?? null;

        if (empty($email)) {
            return redirect()->route('login')
                ->with('error', 'Tu cuenta de Casdoor no proporciona una dirección de correo electrónico.');
        }

        $username = $casdoorUser['preferred_username']
            ?? $casdoorUser['name']
            ?? Str::before($email, '@');

        // 3. Find or create the user in Azuriom
        $user = User::where('email', $email)->first();

        if ($user === null) {
            $user = $this->createAzuriomUser($username, $email, $request->ip());
        }

        if ($user->isBanned()) {
            return redirect()->route('login')
                ->with('error', trans('auth.suspended'));
        }

        // 4. Synchronize Casdoor/LDAP groups or roles with Azuriom Role
        $this->syncUserRole($user, $casdoorUser);

        // 5. Authenticate the user
        Auth::login($user, true);

        return redirect()->intended(route('home'));
    }

    /**
     * Create a new Azuriom user with a unique name, verified email, and random password.
     */
    protected function createAzuriomUser(string $baseName, string $email, ?string $ip): User
    {
        $name = substr($baseName, 0, 25);
        $originalName = $name;
        $counter = 1;

        while (User::where('name', $name)->exists()) {
            $suffix = (string) $counter;
            $name = substr($originalName, 0, 25 - strlen($suffix)).$suffix;
            $counter++;
        }

        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make(Str::random(40));
        $user->role_id = Role::defaultRoleId();
        $user->game_id = Str::uuid()->toString();
        $user->last_login_ip = $ip;
        $user->last_login_at = now();
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    /**
     * Synchronize the user's Azuriom role based on Casdoor groups/roles and configured mappings.
     *
     * @param  array<string, mixed>  $casdoorUser
     */
    protected function syncUserRole(User $user, array $casdoorUser): void
    {
        $rawMappings = setting('casdoor.role_mappings', '[]');
        $mappings = is_string($rawMappings) ? (json_decode($rawMappings, true) ?? []) : [];

        if (empty($mappings)) {
            return;
        }

        $userGroups = $this->extractCasdoorGroups($casdoorUser);
        if (empty($userGroups)) {
            return;
        }

        $matchedRoleIds = [];

        foreach ($mappings as $casdoorGroup => $azuriomRoleId) {
            foreach ($userGroups as $userGroup) {
                // Match exact group name or stripped "org/group" suffix from Casdoor
                $shortGroup = Str::contains($userGroup, '/')
                    ? Str::afterLast($userGroup, '/')
                    : $userGroup;

                if ($userGroup === $casdoorGroup || $shortGroup === $casdoorGroup) {
                    $matchedRoleIds[] = (int) $azuriomRoleId;
                }
            }
        }

        if (empty($matchedRoleIds)) {
            return;
        }

        // If user matches multiple mapped groups, pick the Azuriom role with the highest power
        $targetRole = Role::whereIn('id', array_unique($matchedRoleIds))
            ->orderByDesc('power')
            ->first();

        if ($targetRole !== null && $user->role_id !== $targetRole->id) {
            $user->role_id = $targetRole->id;
            $user->save();
        }
    }

    /**
     * Extract group and role names from the Casdoor userinfo payload.
     *
     * @param  array<string, mixed>  $casdoorUser
     * @return array<int, string>
     */
    protected function extractCasdoorGroups(array $casdoorUser): array
    {
        $extracted = [];

        foreach (['groups', 'roles'] as $key) {
            if (empty($casdoorUser[$key]) || ! is_array($casdoorUser[$key])) {
                continue;
            }

            foreach ($casdoorUser[$key] as $item) {
                if (is_string($item) && $item !== '') {
                    $extracted[] = $item;
                } elseif (is_array($item) && ! empty($item['name']) && is_string($item['name'])) {
                    $extracted[] = $item['name'];
                }
            }
        }

        return array_values(array_unique($extracted));
    }
}
