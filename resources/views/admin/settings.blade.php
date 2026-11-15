@extends('admin.layouts.admin')

@section('title', 'Configuración de Casdoor SSO')

@section('content')
    {{-- Caja informativa de Redirect URI --}}
    <div class="card shadow mb-4 border-left-info">
        <div class="card-header py-3">
            <h5 class="card-title mb-0">
                <i class="bi bi-info-circle me-1"></i> URL de Callback (Redirect URI)
            </h5>
        </div>
        <div class="card-body">
            <p class="mb-2 text-muted">
                Copia esta URL exacta y agrégala en la lista de <strong>Redirect URLs</strong> dentro de la configuración de tu aplicación en Casdoor:
            </p>
            <div class="input-group">
                <input type="text"
                       id="casdoor-callback-url"
                       class="form-control bg-light"
                       value="{{ route('casdoor.callback') }}"
                       readonly>
                <button type="button"
                        class="btn btn-outline-primary"
                        id="copy-callback-btn"
                        onclick="copyCasdoorCallback()">
                    <i class="bi bi-clipboard me-1" id="copy-callback-icon"></i>
                    <span id="copy-callback-text">Copiar URL</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Formulario principal de configuración --}}
    <form action="{{ route('casdoor-auth.admin.settings.save') }}" method="POST">
        @csrf

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h5 class="card-title mb-0">Credenciales de OpenID Connect (Casdoor)</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="url">URL base de Casdoor</label>
                    <input type="url"
                           class="form-control @error('url') is-invalid @enderror"
                           id="url"
                           name="url"
                           placeholder="https://tudominio.com"
                           value="{{ old('url', $casdoorUrl) }}"
                           required>
                    <small class="form-text text-muted">
                        URL base de tu instancia de Casdoor sin barra final (ej. <code>https://sso.tudominio.com</code>).
                    </small>
                    @error('url')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="client_id">Client ID</label>
                        <input type="text"
                               class="form-control @error('client_id') is-invalid @enderror"
                               id="client_id"
                               name="client_id"
                               value="{{ old('client_id', $clientId) }}"
                               required>
                        @error('client_id')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="client_secret">Client Secret</label>
                        <input type="password"
                               class="form-control @error('client_secret') is-invalid @enderror"
                               id="client_secret"
                               name="client_secret"
                               value="{{ old('client_secret', $clientSecret) }}"
                               required>
                        @error('client_secret')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabla dinámica de Mapeo de Grupos LDAP/Casdoor a Roles de Azuriom --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Mapeo de Grupos (Casdoor / LDAP) a Roles de Azuriom</h5>
                <button type="button" class="btn btn-sm btn-success" id="add-mapping-row">
                    <i class="bi bi-plus-lg me-1"></i> Añadir Mapeo
                </button>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Escribe el nombre exacto del grupo o rol recibido desde Casdoor/LDAP (por ejemplo, <code>org/Admin</code> o <code>Admin</code>) y selecciona el rol de Azuriom que se asignará automáticamente al iniciar sesión. Si un usuario pertenece a varios grupos mapeados, se le asignará el rol de mayor jerarquía (power).
                </p>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="mappings-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50%;">Grupo / Rol en Casdoor (LDAP)</th>
                                <th style="width: 40%;">Rol en Azuriom</th>
                                <th style="width: 10%;" class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="mappings-tbody">
                            @forelse($mappings as $groupName => $roleId)
                                <tr class="mapping-row">
                                    <td>
                                        <input type="text"
                                               name="groups[]"
                                               class="form-control"
                                               placeholder="Ej. Admins o organizacion/Admins"
                                               value="{{ $groupName }}"
                                               required>
                                    </td>
                                    <td>
                                        <select name="roles[]" class="form-select" required>
                                            <option value="" disabled>-- Selecciona un rol --</option>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}" @selected((int) $roleId === $role->id)>
                                                    {{ $role->name }} (Power: {{ $role->power }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger remove-mapping-row" title="Eliminar fila">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr class="mapping-row">
                                    <td>
                                        <input type="text"
                                               name="groups[]"
                                               class="form-control"
                                               placeholder="Ej. Admins o organizacion/Admins">
                                    </td>
                                    <td>
                                        <select name="roles[]" class="form-select">
                                            <option value="" selected>-- Selecciona un rol --</option>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}">
                                                    {{ $role->name }} (Power: {{ $role->power }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger remove-mapping-row" title="Eliminar fila">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i> {{ trans('messages.actions.save') }}
        </button>
    </form>

    {{-- Plantilla oculta para nuevas filas dinámicas --}}
    <template id="mapping-row-template">
        <tr class="mapping-row">
            <td>
                <input type="text"
                       name="groups[]"
                       class="form-control"
                       placeholder="Ej. Admins o organizacion/Admins"
                       required>
            </td>
            <td>
                <select name="roles[]" class="form-select" required>
                    <option value="" disabled selected>-- Selecciona un rol --</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">
                            {{ $role->name }} (Power: {{ $role->power }})
                        </option>
                    @endforeach
                </select>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger remove-mapping-row" title="Eliminar fila">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    </template>
@endsection

@push('scripts')
<script>
    function copyCasdoorCallback() {
        const input = document.getElementById('casdoor-callback-url');
        const textSpan = document.getElementById('copy-callback-text');
        const icon = document.getElementById('copy-callback-icon');

        input.select();
        input.setSelectionRange(0, 99999);

        navigator.clipboard.writeText(input.value).then(() => {
            textSpan.textContent = '¡Copiado!';
            icon.className = 'bi bi-check-lg me-1';
            setTimeout(() => {
                textSpan.textContent = 'Copiar URL';
                icon.className = 'bi bi-clipboard me-1';
            }, 2000);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const tbody = document.getElementById('mappings-tbody');
        const addBtn = document.getElementById('add-mapping-row');
        const template = document.getElementById('mapping-row-template');

        addBtn.addEventListener('click', function () {
            const clone = template.content.cloneNode(true);
            tbody.appendChild(clone);
        });

        tbody.addEventListener('click', function (event) {
            const removeBtn = event.target.closest('.remove-mapping-row');
            if (!removeBtn) {
                return;
            }

            const row = removeBtn.closest('.mapping-row');
            if (tbody.querySelectorAll('.mapping-row').length > 1) {
                row.remove();
            } else {
                row.querySelectorAll('input, select').forEach(el => el.value = '');
            }
        });
    });
</script>
@endpush
