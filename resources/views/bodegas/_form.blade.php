{{--
    Kosmos WMS · Vista parcial · CUS-003 Campos de la bodega. La usan create y edit.

    Cumple:
      - Código único; al editar, solo lectura (va impreso en la etiqueta).
      - Tamaño en m² mayor que 0 (CHECK). Decimales con coma o punto.
      - Ubicaciones internas opcionales (RS-17): sin ellas, el pallet usa una referencia libre.
      - m2_ocupados no se pide: lo calcula el sistema.

    Variable: $bodega (Bodega|null).
--}}
@php $bodega = $bodega ?? null; @endphp

<div class="grid grid-cols-[repeat(auto-fit,minmax(220px,1fr))] gap-4">
    <x-campo name="codigo" label="Código" :value="$bodega?->codigo" required maxlength="20" autocomplete="off" class="uppercase"
             :readonly="(bool) $bodega" placeholder="BOD-A"
             :ayuda="$bodega ? 'No se puede cambiar: va impreso en la etiqueta.' : 'Letras, números o guion. Va impreso en la etiqueta.'" />
    <x-campo name="nombre" label="Nombre" :value="$bodega?->nombre" required maxlength="100" autocomplete="off" placeholder="Bodega principal" />
    <x-campo name="tamano_m2" label="Tamaño (m²)" :value="$bodega?->tamano_m2" required inputmode="decimal" autocomplete="off"
             placeholder="1200" ayuda="Base para calcular la ocupación." />
</div>

<label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 has-checked:border-marca-600 has-checked:bg-marca-50">
    <input type="hidden" name="maneja_ubicaciones" value="0">
    <input type="checkbox" name="maneja_ubicaciones" value="1" class="mt-0.5 size-4 accent-marca-600"
           @checked(old('maneja_ubicaciones', $bodega?->maneja_ubicaciones))>
    <span>
        <span class="block text-sm font-semibold">Maneja ubicaciones internas</span>
        <span class="mt-0.5 block text-[13px] text-slate-500">
            Active si la bodega tiene posiciones codificadas (A-01, A-02…). Si no, al ubicar un pallet se escribe una referencia libre, como "junto a la puerta norte".
        </span>
    </span>
</label>
@error('maneja_ubicaciones') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
