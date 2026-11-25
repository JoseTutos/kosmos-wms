@extends('layouts.app')

@use('App\Enums\EstadoBulto')
@use('App\Support\Milesimas')
@use('Illuminate\Support\Js')

@section('titulo', 'Nuevo ajuste')

@section('migas')
    Control y legalización <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="{{ route('ajustes.index') }}" class="hover:underline">Ajustes</a> <span class="mx-1.5" aria-hidden="true">›</span>
    <span class="font-medium text-slate-800">Nuevo</span>
@endsection

@section('contenido')
    @php
        $prellenado = array_filter(['aforo' => $aforo?->id] + $previo, fn ($v) => filled($v));
    @endphp

    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Nuevo ajuste de inventario</h1>
        <p class="mt-1 text-sm text-slate-500">Busque o escanee el bulto, elija la línea y revise cómo quedarían las cantidades antes de confirmar.</p>
    </div>

    @if ($aforo)
        <div class="flex items-start gap-2.5 rounded-[14px] border border-marca-100 bg-marca-50 p-4 text-sm text-slate-700">
            <x-icono nombre="info" class="mt-px size-[18px] text-marca-600" />
            <span>Ajuste desde el aforo <a href="{{ route('aforos.show', $aforo) }}" class="font-mono font-semibold text-marca-600 hover:underline">AFO-{{ $aforo->id }}</a>: al guardarlo vuelve al aforo para cerrarlo.</span>
        </div>
    @endif

    {{-- ===================== 1. Bulto (lector USB en modo teclado) ===================== --}}
    <form method="GET" action="{{ route('ajustes.create') }}" class="flex flex-wrap items-end gap-3 rounded-[14px] border border-slate-200 bg-white p-5">
        <div class="flex min-w-72 flex-1 flex-col gap-1.5">
            <label for="bulto" class="text-sm font-medium">1. Bulto</label>
            <input id="bulto" name="bulto" value="{{ $codigo }}" @if (! $bulto) autofocus @endif autocomplete="off"
                   class="h-[46px] rounded-[10px] border border-slate-300 px-3 font-mono text-[15px] uppercase" placeholder="Escanee o escriba BUL-…">
            @if ($codigo !== '' && ! $bulto)
                <span class="text-sm text-red-700">No existe un bulto con el código {{ $codigo }}.</span>
            @endif
        </div>
        <button type="submit" class="h-[46px] rounded-[10px] bg-slate-800 px-5 text-sm font-medium text-white hover:bg-slate-700">Buscar</button>
    </form>

    {{-- ===================== 2. Línea ===================== --}}
    @if ($bulto)
        <div class="rounded-[14px] border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 class="font-mono text-[15px] font-semibold">{{ $bulto->codigo }}</h2>
                    <p class="text-xs text-slate-500">
                        BL {{ $bulto->bl->numero_bl }} · {{ $bulto->bl->cliente->codigo_corto }}
                        · Pallet {{ $bulto->pallet?->codigo ?? '—' }} · {{ $bulto->pallet?->bodega?->codigo ?? 'sin bodega' }}
                        · {{ $bulto->estado->value }}
                    </p>
                </div>
                <span class="text-sm font-medium">2. Elija la línea</span>
            </div>

            @if ($bulto->estado === EstadoBulto::EN_AFORO)
                <p class="border-b border-slate-200 bg-acento-50 px-5 py-3 text-sm text-acento-800">El bulto está en aforo: solo se admite un ajuste negativo de legalizadas (A2), el que destraba el cierre.</p>
            @elseif ($bulto->estado === EstadoBulto::ANULADO)
                <p class="border-b border-slate-200 bg-red-50 px-5 py-3 text-sm text-red-800">El bulto está anulado: no se ajusta.</p>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                            <th scope="col" class="px-4 py-3 font-semibold">Producto</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Recibida</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Legalizada</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">En cuarentena</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Reservada</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Despachada</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Disponible</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">Elegir</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bulto->detalles as $opcion)
                            @php
                                $elegida = $linea?->id === $opcion->id;
                            @endphp
                            <tr @class(['border-t border-slate-100', 'bg-marca-50' => $elegida])>
                                <td class="px-4 py-3" title="{{ $opcion->producto->descripcion }}">
                                    {{ $opcion->producto->sku }} <span class="text-xs text-slate-500">{{ $opcion->producto->unidad_medida->value }}</span>
                                    @if ($opcion->serializado) <span class="text-xs text-marca-600">· con seriales</span> @endif
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $opcion->cantidad_recibida }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $opcion->cantidad_legalizada }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $opcion->en_cuarentena }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $opcion->cantidad_reservada }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $opcion->cantidad_despachada }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $opcion->disponible }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($elegida)
                                        <span class="text-sm font-medium text-marca-900">Elegida</span>
                                    @else
                                        <a href="{{ route('ajustes.create', $prellenado + ['bulto' => $bulto->codigo, 'linea' => $opcion->id]) }}"
                                           class="inline-flex h-9 items-center rounded-[10px] border border-slate-300 px-3 text-sm font-medium hover:bg-slate-50">Elegir</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ===================== 3. Ajuste, vista previa (J9) y confirmación ===================== --}}
    @if ($linea)
        @php
            $config = [
                'tipo'        => old('tipo', $previo['tipo'] ?? ''),
                'estado'      => old('estado_afectado', $previo['estado'] ?? ''),
                'cantidad'    => old('cantidad', $previo['cantidad'] ?? ''),
                'motivo'      => old('motivo', ''),
                'movimiento'  => (string) old('movimiento_corregido_id', ''),
                'descripcion' => old('descripcion', $previo['descripcion'] ?? ''),
                'unidad'      => $linea->producto->unidad_medida->value,
                'enAforo'     => $bulto->estado === EstadoBulto::EN_AFORO,
                'anulado'     => $bulto->estado === EstadoBulto::ANULADO,
                'tipos'       => $tipos,
                'estados'     => $estados,
                'actual'      => [
                    'recibida'   => Milesimas::de($linea->cantidad_recibida),
                    'legalizada' => Milesimas::de($linea->cantidad_legalizada),
                    'reservada'  => Milesimas::de($linea->cantidad_reservada),
                    'despachada' => Milesimas::de($linea->cantidad_despachada),
                ],
            ];
        @endphp

        <script>
            /* CUS-019 · vista previa del ajuste (J9) con las mismas reglas que BultoDetalle::ajustar (J2, J3, J10). */
            function formularioAjuste(cfg) {
                return {
                    tipo: cfg.tipo,
                    estado: cfg.estado,
                    cantidad: cfg.cantidad,
                    motivo: cfg.motivo,
                    movimiento: cfg.movimiento,
                    descripcion: cfg.descripcion,
                    abierto: false,
                    enviando: false,

                    mil(t) {
                        const s = String(t ?? '').trim().replace(',', '.');
                        if (!/^\d+(\.\d{1,3})?$/.test(s)) return null;
                        const [e, d = ''] = s.split('.');
                        return parseInt(e, 10) * 1000 + parseInt((d + '000').slice(0, 3), 10);
                    },
                    txt(m) {
                        const signo = m < 0 ? '-' : '';
                        m = Math.abs(m);
                        return signo + Math.floor(m / 1000) + '.' + String(m % 1000).padStart(3, '0');
                    },
                    get q() { return this.mil(this.cantidad); },
                    get ahora() {
                        const a = cfg.actual;
                        return { recibida: a.recibida, legalizada: a.legalizada, cuarentena: a.recibida - a.legalizada, disponible: a.legalizada - a.reservada - a.despachada };
                    },
                    get quedaria() {
                        const a = cfg.actual;
                        const signo = this.tipo === 'POSITIVO' ? 1 : (this.tipo === 'NEGATIVO' ? -1 : 0);
                        const q = (this.q || 0) * signo;
                        const recibida = a.recibida + q;
                        const legalizada = this.estado === 'LEGALIZADA' ? a.legalizada + q : a.legalizada;
                        return { recibida, legalizada, cuarentena: recibida - legalizada, disponible: legalizada - a.reservada - a.despachada };
                    },
                    cambio(clave) { return this.quedaria[clave] !== this.ahora[clave]; },
                    get problemas() {
                        const p = [];
                        if (cfg.anulado) p.push('El bulto está anulado.');
                        if (!this.tipo || !this.estado) p.push('Elija el tipo y el estado afectado.');
                        if (!this.q) p.push('Escriba una cantidad mayor que cero, con máximo 3 decimales.');
                        else if (cfg.unidad === 'UND' && this.q % 1000 !== 0) p.push('El producto se maneja en unidades (UND): la cantidad debe ser entera.');
                        if (cfg.enAforo && this.tipo && this.estado && !(this.tipo === 'NEGATIVO' && this.estado === 'LEGALIZADA')) p.push('La línea está en aforo: solo admite un ajuste negativo de legalizadas (A2).');
                        if (this.tipo === 'NEGATIVO' && this.q && this.estado) {
                            const n = this.quedaria;
                            if (n.recibida < 0 || n.legalizada < 0 || n.cuarentena < 0) p.push('El ajuste dejaría cantidades negativas (EX-01).');
                            else if (this.estado === 'LEGALIZADA' && n.disponible < 0) p.push('El ajuste toca unidades reservadas o despachadas: modifique primero la orden de salida (EX-02).');
                        }
                        if (!this.motivo) p.push('Elija el motivo.');
                        if (this.motivo === 'CORRECCION_REGISTRO' && !this.movimiento) p.push('Indique el movimiento que corrige (RS-57).');
                        if (!this.descripcion.trim()) p.push('Describa el motivo del ajuste (EX-03).');
                        return p;
                    },
                    get resumen() {
                        return (this.tipo === 'NEGATIVO' ? '−' : '+') + this.txt(this.q || 0) + ' ' + cfg.unidad + ' · ' + (cfg.tipos[this.tipo] || '') + ' · ' + (cfg.estados[this.estado] || '');
                    },
                    abrir() { if (this.problemas.length === 0) this.abierto = true; },
                    enviar(evento) {
                        if (!this.abierto || this.enviando) { evento.preventDefault(); this.abrir(); return; }
                        this.enviando = true;
                    },
                };
            }
        </script>

        <form method="POST" action="{{ route('ajustes.store') }}" enctype="multipart/form-data"
              x-data="formularioAjuste({{ Js::from($config) }})" @submit="enviar($event)"
              class="grid gap-5 xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
            @csrf
            <input type="hidden" name="bulto_detalle_id" value="{{ $linea->id }}">
            <input type="hidden" name="confirmacion" :value="abierto ? 1 : 0">
            @if ($aforo)
                <input type="hidden" name="aforo_id" value="{{ $aforo->id }}">
            @endif

            {{-- Datos del ajuste --}}
            <div class="flex flex-col gap-4 rounded-[14px] border border-slate-200 bg-white p-5">
                <h2 class="text-[15px] font-semibold">3. Ajuste de {{ $linea->producto->sku }}</h2>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="flex flex-col gap-1.5">
                        <label for="tipo" class="text-sm font-medium">Tipo</label>
                        <select id="tipo" name="tipo" x-model="tipo" class="h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px]">
                            <option value="">Seleccione…</option>
                            @foreach ($tipos as $valor => $etiqueta)
                                <option value="{{ $valor }}">{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        @error('tipo') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="estado_afectado" class="text-sm font-medium">Estado afectado</label>
                        <select id="estado_afectado" name="estado_afectado" x-model="estado" class="h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px]">
                            <option value="">Seleccione…</option>
                            @foreach ($estados as $valor => $etiqueta)
                                <option value="{{ $valor }}">{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        @error('estado_afectado') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="cantidad" class="text-sm font-medium">Cantidad ({{ $linea->producto->unidad_medida->value }})</label>
                        <input id="cantidad" name="cantidad" x-model="cantidad" inputmode="decimal"
                               class="h-[46px] rounded-[10px] border border-slate-300 px-3 text-right text-[15px] tabular-nums" placeholder="0">
                        @error('cantidad') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="motivo" class="text-sm font-medium">Motivo</label>
                    <select id="motivo" name="motivo" x-model="motivo" class="h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px]">
                        <option value="">Seleccione…</option>
                        @foreach ($motivos as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    @error('motivo') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div x-show="motivo === 'CORRECCION_REGISTRO'" x-cloak class="flex flex-col gap-1.5">
                    <label for="movimiento_corregido_id" class="text-sm font-medium">Movimiento que corrige</label>
                    <select id="movimiento_corregido_id" name="movimiento_corregido_id" x-model="movimiento" class="h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 font-mono text-[13px]">
                        <option value="">Seleccione…</option>
                        @foreach ($movimientos as $mov)
                            <option value="{{ $mov->id }}">#{{ $mov->id }} · {{ $mov->created_at?->format('d/m/Y H:i') }} · {{ $mov->tipo->value }} · {{ $mov->cantidad }} {{ $mov->estado_legal?->value }} · {{ $mov->documento_ref ?? '—' }}</option>
                        @endforeach
                    </select>
                    <span class="text-xs text-slate-500">Últimos movimientos de esta línea en el kardex.</span>
                    @error('movimiento_corregido_id') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="descripcion" class="text-sm font-medium">Descripción</label>
                    <textarea id="descripcion" name="descripcion" x-model="descripcion" rows="3" maxlength="2000"
                              class="rounded-[10px] border border-slate-300 px-3 py-2 text-[15px]" placeholder="Qué pasó y quién responde (origen o custodia)."></textarea>
                    @error('descripcion') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="soporte" class="text-sm font-medium">Soporte <span class="font-normal text-slate-500">(opcional)</span></label>
                    <input id="soporte" name="soporte" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                           class="rounded-[10px] border border-slate-300 p-2 text-sm">
                    <span class="text-xs text-slate-500">Foto o PDF de hasta 10 MB.</span>
                    @error('soporte') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Vista previa (J9) --}}
            <div class="flex flex-col gap-4 rounded-[14px] border border-slate-200 bg-white p-5">
                <h2 class="text-[15px] font-semibold">Cómo quedaría la línea</h2>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                            <th scope="col" class="py-2 font-semibold">Cantidad</th>
                            <th scope="col" class="py-2 text-right font-semibold">Actual</th>
                            <th scope="col" class="py-2 text-right font-semibold">Quedaría</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (['recibida' => 'Recibida', 'legalizada' => 'Legalizada', 'cuarentena' => 'En cuarentena', 'disponible' => 'Disponible'] as $clave => $rotulo)
                            <tr class="border-t border-slate-100">
                                <td class="py-2">{{ $rotulo }}</td>
                                <td class="py-2 text-right tabular-nums" x-text="txt(ahora.{{ $clave }})"></td>
                                <td class="py-2 text-right font-semibold tabular-nums"
                                    :class="quedaria.{{ $clave }} < 0 ? 'text-red-700' : (cambio('{{ $clave }}') ? 'text-marca-900' : 'text-slate-400')"
                                    x-text="txt(quedaria.{{ $clave }})"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <ul x-show="problemas.length" class="flex flex-col gap-1 rounded-[10px] border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                    <template x-for="p in problemas" :key="p">
                        <li x-text="p"></li>
                    </template>
                </ul>

                <button type="button" @click="abrir()" :disabled="problemas.length > 0"
                        class="h-11 rounded-[10px] bg-marca-600 px-5 text-sm font-semibold text-white hover:bg-marca-700 disabled:opacity-50">Revisar y confirmar</button>
            </div>

            {{-- Confirmación "irreversible". Ventana propia porque envía este formulario con el archivo. --}}
            <div x-show="abierto" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-6" @keydown.escape.window="abierto = false">
                <div role="dialog" aria-modal="true" aria-labelledby="titulo-ajuste" class="flex w-full max-w-[480px] flex-col gap-5 rounded-2xl bg-white p-7 shadow-xl">
                    <div class="flex items-start gap-3.5">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                            <x-icono nombre="alerta" class="size-[22px]" />
                        </span>
                        <div>
                            <h2 id="titulo-ajuste" class="text-lg font-semibold tracking-tight">¿Registrar el ajuste?</h2>
                            <p class="mt-1 text-sm text-slate-500"><span class="font-mono">{{ $bulto->codigo }} · {{ $linea->producto->sku }}</span> · <span x-text="resumen"></span></p>
                        </div>
                    </div>
                    <ul class="flex list-disc flex-col gap-1.5 pl-5 text-sm text-slate-700">
                        <li><strong>Es irreversible:</strong> no se edita ni se borra; un error se corrige con otro ajuste.</li>
                        <li>Genera el movimiento AJUSTE en el kardex y actualiza las existencias.</li>
                        <li>Queda en la bitácora con su usuario y la descripción.</li>
                    </ul>
                    <div class="flex justify-end gap-2.5">
                        <button type="button" @click="abierto = false" class="h-11 rounded-[10px] border border-slate-300 px-5 text-sm font-medium hover:bg-slate-50">Cancelar</button>
                        <button type="submit" :disabled="enviando"
                                class="h-11 rounded-[10px] bg-red-700 px-5 text-sm font-semibold text-white hover:bg-red-800 disabled:opacity-50"
                                x-text="enviando ? 'Registrando…' : 'Sí, registrar ajuste'"></button>
                    </div>
                </div>
            </div>
        </form>
    @endif
@endsection
