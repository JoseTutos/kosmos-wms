{{--
    Kosmos WMS · Componente Blade · Campo de texto con etiqueta, ayuda y error

    Uso: <x-campo name="razon_social" label="Razón social" :value="$cliente?->razon_social" maxlength="200" required />
         <x-campo name="nit" label="NIT" :value="$cliente->nit" readonly ayuda="No se puede cambiar." />

    Accesibilidad: <label for>, aria-invalid y aria-describedby enlazan el error al campo.
    Seguridad: el valor se escapa con {{ }}; old() repinta lo digitado tras un error de validación.
    Un campo readonly sí se envía, pero los FormRequest de edición no lo aceptan (validated()).
--}}
@props(['name', 'label', 'type' => 'text', 'value' => null, 'ayuda' => null])

<div class="flex flex-col gap-1.5">
    <label for="{{ $name }}" class="text-sm font-medium">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
           @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
           {{ $attributes->class([
               'h-[46px] rounded-[10px] border border-slate-300 bg-white px-3.5 text-[15px] outline-none',
               'focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20',
               'read-only:bg-slate-50 read-only:text-slate-500 read-only:focus:ring-0',
           ]) }}>
    @if ($ayuda)
        <span class="text-xs text-slate-500">{{ $ayuda }}</span>
    @endif
    @error($name)
        <p id="{{ $name }}-error" class="text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
