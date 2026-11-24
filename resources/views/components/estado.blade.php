{{--
    Kosmos WMS · Componente Blade · Píldora de estado (activo / inactivo)

    Uso: <x-estado :activo="$cliente->estaActivo()" :texto="Etiquetas::activo($cliente->estado)" />
    Accesibilidad: el estado se lee por el texto, no solo por el color.
--}}
@props(['activo' => true, 'texto'])

<span @class([
    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold',
    'bg-green-100 text-green-800' => $activo,
    'bg-slate-200 text-slate-700' => ! $activo,
])>
    <span @class(['size-1.5 rounded-full', 'bg-green-600' => $activo, 'bg-slate-500' => ! $activo])></span>
    {{ $texto }}
</span>
