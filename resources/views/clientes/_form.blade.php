{{--
    Kosmos WMS · Vista parcial · CUS-001 Campos del cliente. La usan create y edit.

    Cumple:
      - NIT (9 dígitos) y código corto (3 a 6 letras) solo se digitan al crear; en edición se
        muestran de solo lectura porque son inmutables (CUS-001). El servidor los ignora igual.
      - Largos máximos = columnas de la BD; las reglas reales están en los FormRequest.

    Variable: $cliente (Cliente|null).
--}}
@php $cliente = $cliente ?? null; @endphp
@php $editando = (bool) $cliente; @endphp

<div class="grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-4">
    <x-campo name="nit" label="NIT" :value="$cliente?->nit" required maxlength="11" inputmode="numeric" autocomplete="off"
             :readonly="$editando" placeholder="900123456"
             :ayuda="$editando ? 'No se puede cambiar.' : '9 dígitos, sin dígito de verificación.'" />

    <x-campo name="codigo_corto" label="Código corto" :value="$cliente?->codigo_corto" required maxlength="6" autocomplete="off"
             :readonly="$editando" placeholder="ACME" class="uppercase"
             :ayuda="$editando ? 'No se puede cambiar: es el prefijo de sus SKU.' : '3 a 6 letras. Será el prefijo de sus SKU (ACME-00001) y no se podrá cambiar.'" />
</div>

<x-campo name="razon_social" label="Razón social" :value="$cliente?->razon_social" required maxlength="200" autocomplete="organization" />

<div class="grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-4">
    <x-campo name="direccion" label="Dirección" :value="$cliente?->direccion" required maxlength="255" autocomplete="street-address" />
    <x-campo name="telefono" label="Teléfono" type="tel" :value="$cliente?->telefono" required maxlength="30" autocomplete="tel" />
</div>

<x-campo name="contacto_nombre" label="Nombre de contacto" :value="$cliente?->contacto_nombre" required maxlength="150" autocomplete="name" />
