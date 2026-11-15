{{-- Fragmento para incluir en resources/views/auth/login.blade.php de tu tema o mediante @include('casdoor-auth::partials.login-button') --}}
@if(setting('casdoor.url') && setting('casdoor.client_id'))
    <div class="d-grid gap-2 my-3">
        <div class="d-flex align-items-center my-2">
            <hr class="flex-grow-1">
            <span class="mx-2 text-muted small text-uppercase">O continuar con</span>
            <hr class="flex-grow-1">
        </div>

        <a href="{{ route('casdoor.redirect') }}" class="btn btn-outline-primary d-flex align-items-center justify-content-center gap-2 py-2">
            <i class="bi bi-shield-lock-fill"></i>
            <span>Iniciar sesión con Casdoor SSO</span>
        </a>
    </div>
@endif
