@if(setting('casdoor.url') && setting('casdoor.client_id'))
    @php
        $btnColor = setting('casdoor.button_color', '#dc2626') ?: '#dc2626';
        $btnText = setting('casdoor.button_text', 'Iniciar sesión con Casdoor SSO') ?: 'Iniciar sesión con Casdoor SSO';
        $dividerText = setting('casdoor.divider_text', 'O continuar con');
        $btnIcon = trim((string) setting('casdoor.button_icon', ''));
    @endphp

    <style>
        #casdoor-sso-login-btn {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.6rem !important;
            width: 100% !important;
            padding: 0.7rem 1.2rem !important;
            background: {{ $btnColor }} !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            font-size: 0.95rem !important;
            text-decoration: none !important;
            border: none !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 2px 8px {{ $btnColor }}40 !important;
            transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease !important;
        }
        #casdoor-sso-login-btn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 16px {{ $btnColor }}70 !important;
            filter: brightness(1.08) !important;
            color: #ffffff !important;
        }
    </style>

    <div class="d-grid gap-2 my-3">
        @if(! empty($dividerText))
            <div class="d-flex align-items-center my-2">
                <hr class="flex-grow-1 opacity-25">
                <span class="mx-3 text-muted small text-uppercase fw-semibold" style="letter-spacing: 0.05em;">
                    {{ $dividerText }}
                </span>
                <hr class="flex-grow-1 opacity-25">
            </div>
        @endif

        <a id="casdoor-sso-login-btn" href="{{ route('casdoor.redirect') }}">
            @if($btnIcon !== '')
                <i class="{{ $btnIcon }}" aria-hidden="true"></i>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M5.338 1.59a61 61 0 0 0-2.837.856.48.48 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.7 10.7 0 0 0 2.287 2.233c.346.244.652.42.893.533q.18.085.293.118a1 1 0 0 0 .101.025 1 1 0 0 0 .1-.025q.114-.034.294-.118c.24-.113.547-.29.893-.533a10.7 10.7 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.8 11.8 0 0 1-2.517 2.453 7 7 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7 7 0 0 1-1.048-.625 11.8 11.8 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 63 63 0 0 1 5.072.56"/>
                    <path d="M9.5 6.5a1.5 1.5 0 0 1-1 1.415l.385 1.99a.5.5 0 0 1-.491.595h-.788a.5.5 0 0 1-.49-.595l.384-1.99a1.5 1.5 0 1 1 2-1.415"/>
                </svg>
            @endif
            <span>{{ $btnText }}</span>
        </a>
    </div>
@endif
