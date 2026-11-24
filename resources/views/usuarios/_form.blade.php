{{--
    Kosmos WMS · Vista parcial · CUS-004 Campos del usuario (croquis 5). La usan create y edit.

    Cumple:
      - CUS-004 / RS-19: un solo rol, elegido entre los 6 del enum, cada uno con su
        descripción para evitar asignar el equivocado (RE-7: segregación de funciones).

    Restricciones:
      - Sin contraseña ni estado: la clave temporal la genera el sistema y el estado tiene
        sus acciones en el listado.
      - Un ADMIN_SISTEMA no puede cambiarse su propio rol: en su propia ficha los roles se
        muestran deshabilitados y se envía el rol actual en un campo oculto (el controlador
        también lo rechaza).
      - Las reglas reales las validan Store/UpdateUsuarioRequest.

    Variables: $roles (Rol[]) y, en edición, $usuario (User).
--}}
@use('App\Support\Etiquetas')

@php
    $usuario   = $usuario ?? null;
    $rolActual = old('rol', $usuario?->rol?->value);
    $esYo      = $usuario && auth()->user()->is($usuario);
@endphp

<div class="grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-4">
    <div class="flex flex-col gap-1.5">
        <label for="nombre" class="text-sm font-medium">Nombre completo</label>
        <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $usuario?->nombre) }}" required maxlength="150"
               autocomplete="off" @error('nombre') aria-invalid="true" @enderror
               class="h-[46px] rounded-[10px] border border-slate-300 px-3.5 text-[15px] outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
        @error('nombre') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div class="flex flex-col gap-1.5">
        <label for="email" class="text-sm font-medium">Correo</label>
        <input id="email" name="email" type="email" value="{{ old('email', $usuario?->email) }}" required maxlength="150"
               autocomplete="off" inputmode="email" @error('email') aria-invalid="true" @enderror
               class="h-[46px] rounded-[10px] border border-slate-300 px-3.5 text-[15px] outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
        <span class="text-xs text-slate-500">Será su usuario para ingresar.</span>
        @error('email') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
    </div>
</div>

<fieldset class="flex flex-col gap-2.5" @error('rol') aria-invalid="true" @enderror>
    <legend class="mb-1.5 text-sm font-medium">Rol <span class="font-normal text-slate-500">(uno solo)</span></legend>

    @if ($esYo)
        <input type="hidden" name="rol" value="{{ $usuario->rol->value }}">
        <p class="text-[13px] text-slate-500">No puede cambiar su propio rol.</p>
    @endif

    <div class="grid grid-cols-[repeat(auto-fit,minmax(300px,1fr))] gap-2.5">
        @foreach ($roles as $rol)
            <label class="flex cursor-pointer items-start gap-2.5 rounded-xl border border-slate-200 p-3 has-checked:border-marca-600 has-checked:bg-marca-50 has-checked:ring-1 has-checked:ring-marca-600 has-disabled:cursor-not-allowed has-disabled:opacity-60">
                <input type="radio" name="rol" value="{{ $rol->value }}" @checked($rolActual === $rol->value) @disabled($esYo)
                       required class="mt-0.5 accent-marca-600">
                <span>
                    <span class="block text-sm font-semibold">{{ Etiquetas::rol($rol) }}</span>
                    <span class="mt-0.5 block text-[13px] text-slate-500">{{ Etiquetas::descripcionRol($rol) }}</span>
                </span>
            </label>
        @endforeach
    </div>
    @error('rol') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
</fieldset>
