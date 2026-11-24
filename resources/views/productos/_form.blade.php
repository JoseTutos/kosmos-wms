{{--
    Kosmos WMS · Vista parcial · CUS-002 Campos del producto. La usan create y edit.

    Cumple:
      - Al crear: se elige un cliente ACTIVO (RS-06). El SKU no se digita: lo genera el sistema (RS-07).
      - Al editar: cliente y SKU se muestran de solo lectura (inmutables).
      - Tipo y unidad: solo los valores permitidos (CHECK de la BD). Decimales con coma o punto.
      - Stock mínimo: indicador visual en Inventario (RS-73), sin notificaciones.

    Variables: $tipos, $unidades, $clientes (activos) y, en edición, $producto.
--}}
@use('App\Support\Etiquetas')

@php $producto = $producto ?? null; @endphp

@if ($producto)
    <div class="grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-4">
        <x-campo name="sku_lectura" label="SKU" :value="$producto->sku" readonly ayuda="Lo generó el sistema. No se puede cambiar." class="font-mono" />
        <x-campo name="cliente_lectura" label="Cliente" :value="$producto->cliente->codigo_corto.' · '.$producto->cliente->razon_social" readonly ayuda="No se puede cambiar." />
    </div>
@else
    <div class="flex flex-col gap-1.5">
        <label for="cliente_id" class="text-sm font-medium">Cliente</label>
        <select id="cliente_id" name="cliente_id" required @error('cliente_id') aria-invalid="true" @enderror
                class="h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px] outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
            <option value="" disabled @selected(! old('cliente_id'))>Seleccione un cliente activo</option>
            @foreach ($clientes as $cl)
                <option value="{{ $cl->id }}" @selected((string) old('cliente_id') === (string) $cl->id)>{{ $cl->codigo_corto }} · {{ $cl->razon_social }}</option>
            @endforeach
        </select>
        <span class="text-xs text-slate-500">El SKU se genera con su código corto: ACME-00001, ACME-00002…</span>
        @error('cliente_id') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
    </div>
@endif

<x-campo name="descripcion" label="Descripción" :value="$producto?->descripcion" required maxlength="255" autocomplete="off"
         placeholder="Tornillo hexagonal M8 galvanizado" />

<div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-4">
    <div class="flex flex-col gap-1.5">
        <label for="tipo_almacenamiento" class="text-sm font-medium">Tipo de almacenamiento</label>
        <select id="tipo_almacenamiento" name="tipo_almacenamiento" required
                class="h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px] outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
            @foreach ($tipos as $tipo)
                <option value="{{ $tipo->value }}" @selected(old('tipo_almacenamiento', $producto?->tipo_almacenamiento?->value) === $tipo->value)>{{ Etiquetas::tipoAlmacenamiento($tipo) }}</option>
            @endforeach
        </select>
        @error('tipo_almacenamiento') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div class="flex flex-col gap-1.5">
        <label for="unidad_medida" class="text-sm font-medium">Unidad de medida</label>
        <select id="unidad_medida" name="unidad_medida" required
                class="h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px] outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
            @foreach ($unidades as $unidad)
                <option value="{{ $unidad->value }}" @selected(old('unidad_medida', $producto?->unidad_medida?->value) === $unidad->value)>{{ Etiquetas::unidadMedida($unidad) }}</option>
            @endforeach
        </select>
        @if ($producto)
            <span class="text-xs text-slate-500">No se puede cambiar si ya hay mercancía registrada.</span>
        @endif
        @error('unidad_medida') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <x-campo name="peso_unitario_kg" label="Peso unitario (kg)" :value="$producto?->peso_unitario_kg" required inputmode="decimal"
             autocomplete="off" placeholder="0,015" ayuda="Hasta 3 decimales." />

    <x-campo name="stock_minimo" label="Stock mínimo" :value="$producto?->stock_minimo" inputmode="decimal" autocomplete="off"
             placeholder="0" ayuda="Opcional. Solo es un aviso visual en el inventario." />
</div>
