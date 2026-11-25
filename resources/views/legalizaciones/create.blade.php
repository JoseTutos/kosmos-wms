@extends('layouts.app')

@use('Illuminate\Support\Js')

@section('titulo', 'Nueva legalización')

@section('migas')
    Control y legalización <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="{{ route('legalizaciones.index') }}" class="hover:underline">Legalizaciones</a> <span class="mx-1.5" aria-hidden="true">›</span>
    <span class="font-medium text-slate-800">Nueva</span>
@endsection

@section('contenido')
    @php
        $erroresAutorizacion = $errors->hasAny(['tipo_documento', 'numero_autorizacion', 'fecha_autorizacion', 'soporte']);
        $errorCarrito = $errors->first('items') ?: $errors->first('items.*');
        $config = $bl ? [
            'blId'        => $bl->id,
            'bultos'      => $datos['bultos'],
            'legalizados' => $datos['legalizados'],
            'pasoInicial' => $erroresAutorizacion ? 3 : 2,
            'urlNumero'   => route('legalizaciones.autorizacion'),
            'tipos'       => $tipos,
            'anterior'    => [
                'tipo'   => old('tipo_documento', ''),
                'numero' => old('numero_autorizacion', ''),
                'fecha'  => old('fecha_autorizacion', ''),
            ],
        ] : null;
        $pasos = ['BL', 'Carrito', 'Autorización', 'Confirmar'];
    @endphp

    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Nueva legalización</h1>
        <p class="mt-1 text-sm text-slate-500">Pase mercancía de cuarentena a legalizada con el documento de la autoridad. Solo lo legalizado puede salir de la bodega.</p>
    </div>

    {{-- ===================== Paso 1: el BL (GET, como en aforos) ===================== --}}
    <form method="GET" action="{{ route('legalizaciones.create') }}" class="flex flex-wrap items-end gap-3 rounded-[14px] border border-slate-200 bg-white p-5">
        <div class="flex min-w-72 flex-1 flex-col gap-1.5">
            <label for="bl" class="text-sm font-medium">1. BL</label>
            <select id="bl" name="bl" required class="h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px]">
                <option value="">Seleccione un BL con mercancía en cuarentena…</option>
                @foreach ($bls as $opcion)
                    <option value="{{ $opcion->id }}" @selected($bl?->id === $opcion->id)>{{ $opcion->numero_bl }} · {{ $opcion->cliente->codigo_corto }} · {{ $opcion->fecha_ingreso->format('d/m/Y') }}</option>
                @endforeach
            </select>
            @if ($bls->isEmpty())
                <span class="text-xs text-slate-500">No hay BL recibidos con unidades en cuarentena.</span>
            @endif
            @if ($blNoValido)
                <span class="text-xs text-red-700">El BL elegido no está RECIBIDO o DESPACHADO_PARCIAL, o ya no tiene unidades en cuarentena.</span>
            @endif
        </div>
        <button type="submit" class="h-[46px] rounded-[10px] bg-slate-800 px-5 text-sm font-medium text-white hover:bg-slate-700">Cargar bultos</button>
    </form>

    @if ($bl)
        <script>
            /* CUS-010 · asistente de legalización. Cantidades en milésimas (enteros), igual que App\Support\Milesimas. */
            function asistenteLegalizacion(cfg) {
                return {
                    paso: cfg.pasoInicial,
                    pestana: 'A',
                    bultos: cfg.bultos,
                    legalizados: cfg.legalizados,
                    tipos: cfg.tipos,
                    items: [],
                    lineaId: '',
                    cantidad: '',
                    elegidos: [],
                    escaneo: '',
                    aviso: null,
                    auth: { tipo: cfg.anterior.tipo, numero: cfg.anterior.numero, fecha: cfg.anterior.fecha },
                    soporte: '',
                    usos: [],
                    enviando: false,
                    mapaLineas: {},
                    mapaBultos: {},
                    mapaSeriales: {},
                    audio: null,

                    init() {
                        for (const b of this.bultos) {
                            this.mapaBultos[b.codigo] = b;
                            for (const l of b.lineas) {
                                l.bulto = b;
                                this.mapaLineas[l.id] = l;
                                for (const s of l.seriales) this.mapaSeriales[s.codigo] = { ...s, linea: l };
                            }
                        }

                        const guardado = this.leer();
                        if (guardado) {
                            this.items = this.depurar(guardado.items || []);
                            for (const k of ['tipo', 'numero', 'fecha']) {
                                if (!this.auth[k] && guardado.auth && guardado.auth[k]) this.auth[k] = guardado.auth[k];
                            }
                        }

                        this.$watch('items', () => this.guardar());
                        this.$watch('auth', () => this.guardar());
                        this.$watch('lineaId', () => this.alCambiarLinea());
                        if (this.auth.numero) this.revisarNumero();
                        this.$nextTick(() => this.enfocar());
                    },

                    // ---------- almacenamiento local (L10, S2) ----------
                    clave() { return 'kosmos.legalizacion.bl.' + cfg.blId; },
                    leer() { try { return JSON.parse(localStorage.getItem(this.clave()) || 'null'); } catch (e) { return null; } },
                    guardar() { try { localStorage.setItem(this.clave(), JSON.stringify({ items: this.items, auth: this.auth })); } catch (e) {} },
                    vaciar() { this.items = []; this.aviso = null; try { localStorage.removeItem(this.clave()); } catch (e) {} },
                    /** Quita del carrito guardado lo que ya no está en cuarentena (otro usuario lo legalizó). */
                    depurar(items) {
                        const bultoIds = new Set(this.bultos.map(b => b.id));
                        return items.filter(i => {
                            if (i.modo === 'A') return bultoIds.has(i.bulto_id);
                            const l = this.mapaLineas[i.linea_id];
                            if (!l) return false;
                            if (i.modo === 'C') {
                                const ids = new Set(l.seriales.map(s => s.id));
                                i.seriales = (i.seriales || []).filter(id => ids.has(id));
                                return i.seriales.length > 0;
                            }
                            return true;
                        });
                    },

                    // ---------- cantidades ----------
                    mil(t) {
                        const s = String(t ?? '').trim().replace(',', '.');
                        if (!/^\d+(\.\d{1,3})?$/.test(s)) return null;
                        const [e, d = ''] = s.split('.');
                        return parseInt(e, 10) * 1000 + parseInt((d + '000').slice(0, 3), 10);
                    },
                    txt(m) { return Math.floor(m / 1000) + '.' + String(m % 1000).padStart(3, '0'); },
                    /** Milésimas de la línea que ya están en el carrito (A = toda su cuarentena). */
                    usado(l) {
                        let m = 0;
                        for (const i of this.items) {
                            if (i.modo === 'A' && i.bulto_id === l.bulto_id) m += this.mil(l.en_cuarentena);
                            if (i.modo === 'B' && i.linea_id === l.id) m += this.mil(i.cantidad);
                            if (i.modo === 'C' && i.linea_id === l.id) m += i.seriales.length * 1000;
                        }
                        return m;
                    },
                    libre(l) { return this.mil(l.en_cuarentena) - this.usado(l); },
                    bultoEnCarrito(b) { return this.items.some(i => i.modo === 'A' && i.bulto_id === b.id); },
                    serialEnCarrito(id) { return this.items.some(i => i.modo === 'C' && i.seriales.includes(id)); },

                    // ---------- avisos y beep ----------
                    error(texto) { this.aviso = { tipo: 'error', texto }; this.beep(true); },
                    ok(texto) { this.aviso = { tipo: 'ok', texto }; this.beep(false); },
                    beep(esError) {
                        try {
                            this.audio = this.audio || new (window.AudioContext || window.webkitAudioContext)();
                            const osc = this.audio.createOscillator();
                            const vol = this.audio.createGain();
                            osc.type = esError ? 'square' : 'sine';
                            osc.frequency.value = esError ? 220 : 880;
                            vol.gain.value = 0.08;
                            osc.connect(vol).connect(this.audio.destination);
                            osc.start();
                            osc.stop(this.audio.currentTime + (esError ? 0.35 : 0.12));
                        } catch (e) {}
                    },
                    enfocar() { if (this.paso === 2 && this.$refs.escaner) this.$refs.escaner.focus(); },

                    // ---------- Modo A: bulto completo (L4) ----------
                    agregarBulto(b) {
                        if (b.bloqueo) return this.error(b.codigo + ': ' + b.motivo);
                        if (this.bultoEnCarrito(b)) return this.error(b.codigo + ' ya está en el carrito.');
                        if (b.lineas.some(l => this.usado(l) > 0)) return this.error(b.codigo + ': ya hay unidades de este bulto en el carrito. Quítelas o siga con los modos B y C.');
                        this.items.push({ modo: 'A', bulto_id: b.id });
                        this.ok(b.codigo + ' agregado completo.');
                    },

                    // ---------- Modos B y C: por línea ----------
                    get linea() { return this.mapaLineas[this.lineaId] || null; },
                    get lineasDisponibles() { return Object.values(this.mapaLineas).filter(l => !l.bulto.bloqueo); },
                    alCambiarLinea() {
                        this.elegidos = [];
                        const l = this.linea;
                        if (!l) return;
                        if (l.serializado && this.pestana === 'B') this.pestana = 'C';
                        if (!l.serializado && this.pestana === 'C') {
                            this.pestana = 'B';
                            this.error('La línea no tiene seriales: legalícela por cantidad (Modo B).'); // FA-02
                        }
                    },
                    agregarCantidad() {
                        const l = this.linea;
                        if (!l) return this.error('Elija la línea.');
                        if (l.bulto.bloqueo) return this.error(l.bulto.codigo + ': ' + l.bulto.motivo);
                        if (l.serializado) return this.error('La línea tiene seriales: legalice el bulto completo o escanee los seriales (Modo C).');
                        if (this.bultoEnCarrito(l.bulto)) return this.error(l.bulto.codigo + ' ya está completo en el carrito.');
                        const m = this.mil(this.cantidad);
                        if (!m) return this.error('Escriba una cantidad mayor que cero, con máximo 3 decimales.');
                        if (l.unidad === 'UND' && m % 1000 !== 0) return this.error('El producto se maneja en unidades (UND): la cantidad debe ser entera.');
                        if (m > this.libre(l)) return this.error('La cantidad supera lo que está en cuarentena (libre: ' + this.txt(this.libre(l)) + ' ' + l.unidad + ').'); // FA-04
                        this.items.push({ modo: 'B', linea_id: l.id, cantidad: this.txt(m) });
                        this.cantidad = '';
                        this.ok(this.txt(m) + ' ' + l.unidad + ' de ' + l.sku + ' agregadas.');
                    },
                    agregarSeriales(ids, l) {
                        l = l || this.linea;
                        if (!l) return this.error('Elija la línea.');
                        if (!l.serializado) return this.error('La línea no tiene seriales: legalícela por cantidad (Modo B).'); // FA-02
                        if (l.bulto.bloqueo) return this.error(l.bulto.codigo + ': ' + l.bulto.motivo);
                        if (this.bultoEnCarrito(l.bulto)) return this.error(l.bulto.codigo + ' ya está completo en el carrito.');
                        const nuevos = ids.filter(id => !this.serialEnCarrito(id));
                        if (nuevos.length === 0) return this.error('Esos seriales ya están en el carrito.');
                        const item = this.items.find(i => i.modo === 'C' && i.linea_id === l.id);
                        if (item) item.seriales.push(...nuevos); else this.items.push({ modo: 'C', linea_id: l.id, seriales: nuevos });
                        this.elegidos = [];
                        this.ok(nuevos.length + ' serial(es) de ' + l.sku + ' agregado(s).');
                    },

                    // ---------- lector USB (modo teclado: código + Enter) ----------
                    escanear() {
                        const codigo = this.escaneo.trim().toUpperCase();
                        this.escaneo = '';
                        if (!codigo) return;
                        const b = this.mapaBultos[codigo];
                        if (b) return this.agregarBulto(b);
                        const s = this.mapaSeriales[codigo];
                        if (s) {
                            if (this.serialEnCarrito(s.id)) return this.error('El serial ' + codigo + ' ya está en el carrito.');
                            return this.agregarSeriales([s.id], s.linea);
                        }
                        if (this.legalizados.bultos.includes(codigo)) return this.error('El bulto ' + codigo + ' ya está legalizado.');   // FA-01
                        if (this.legalizados.seriales.includes(codigo)) return this.error('El serial ' + codigo + ' ya está legalizado.'); // FA-01
                        this.error('El código ' + codigo + ' no está en cuarentena en este BL.');
                    },

                    // ---------- carrito ----------
                    quitar(indice) { this.items.splice(indice, 1); this.aviso = null; },
                    titulo(i) {
                        if (i.modo === 'A') { const b = this.bultos.find(x => x.id === i.bulto_id); return b ? b.codigo + ' · bulto completo' : '—'; }
                        const l = this.mapaLineas[i.linea_id];
                        return l ? l.bulto.codigo + ' · ' + l.sku : '—';
                    },
                    detalle(i) {
                        if (i.modo === 'A') {
                            const b = this.bultos.find(x => x.id === i.bulto_id);
                            return b ? b.lineas.map(l => l.sku + ' ' + l.en_cuarentena + ' ' + l.unidad + (l.serializado ? ' (' + l.seriales.length + ' seriales)' : '')).join(' · ') : '';
                        }
                        const l = this.mapaLineas[i.linea_id];
                        if (!l) return '';
                        return i.modo === 'B' ? i.cantidad + ' ' + l.unidad : i.seriales.length + ' serial(es)';
                    },
                    /** Totales por unidad de medida para la confirmación. */
                    get totales() {
                        const t = {};
                        for (const l of Object.values(this.mapaLineas)) {
                            const m = this.usado(l);
                            if (m > 0) t[l.unidad] = (t[l.unidad] || 0) + m;
                        }
                        return Object.entries(t).map(([u, m]) => this.txt(m) + ' ' + u).join(' · ');
                    },
                    get lineasEnCarrito() { return Object.values(this.mapaLineas).filter(l => this.usado(l) > 0).length; },
                    get carrito() {
                        return JSON.stringify(this.items.map(i => i.modo === 'A'
                            ? { modo: 'A', bulto_id: i.bulto_id }
                            : (i.modo === 'B' ? { modo: 'B', linea_id: i.linea_id, cantidad: i.cantidad } : { modo: 'C', linea_id: i.linea_id, seriales: i.seriales })));
                    },

                    // ---------- autorización (L11–L14) ----------
                    revisarNumero() {
                        const numero = (this.auth.numero || '').trim();
                        if (!numero) { this.usos = []; return; }
                        fetch(cfg.urlNumero + '?numero=' + encodeURIComponent(numero), { headers: { Accept: 'application/json' } })
                            .then(r => r.ok ? r.json() : { usos: [] })
                            .then(d => { this.usos = d.usos || []; })
                            .catch(() => {});
                    },
                    get autorizacionCompleta() { return !!(this.auth.tipo && this.auth.numero.trim() && this.auth.fecha && this.soporte); },

                    // ---------- navegación ----------
                    ir(n) {
                        if (n >= 3 && this.items.length === 0) return this.error('Agregue al menos un bulto, una cantidad o un serial.');
                        if (n === 4 && !this.autorizacionCompleta) return this.error('Complete el tipo, el número, la fecha y el soporte.');
                        this.aviso = null;
                        this.paso = n;
                        this.$nextTick(() => this.enfocar());
                    },
                    enviar(evento) {
                        if (this.enviando || this.items.length === 0 || !this.autorizacionCompleta) { evento.preventDefault(); return; }
                        this.enviando = true;
                    },
                };
            }
        </script>

        <form method="POST" action="{{ route('legalizaciones.store') }}" enctype="multipart/form-data"
              x-data="asistenteLegalizacion({{ Js::from($config) }})" @submit="enviar($event)" class="flex flex-col gap-5">
            @csrf
            <input type="hidden" name="bl_id" value="{{ $bl->id }}">
            <input type="hidden" name="carrito" :value="carrito">

            {{-- Indicador de pasos --}}
            <ol class="flex flex-wrap gap-2 text-sm" aria-label="Pasos de la legalización">
                @foreach ($pasos as $i => $nombre)
                    <li class="flex items-center gap-2 rounded-full border px-3 py-1.5"
                        :class="paso === {{ $i + 1 }} ? 'border-marca-600 bg-marca-50 font-semibold text-marca-900' : (paso > {{ $i + 1 }} ? 'border-green-200 bg-green-50 text-green-800' : 'border-slate-200 bg-white text-slate-500')">
                        <span class="flex size-5 items-center justify-center rounded-full bg-white text-xs font-semibold">{{ $i + 1 }}</span>
                        {{ $nombre }}
                    </li>
                @endforeach
            </ol>

            {{-- Aviso del asistente (FA-01, FA-02, FA-04) --}}
            <div x-show="aviso" x-cloak role="status"
                 :class="aviso && aviso.tipo === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800'"
                 class="flex items-start gap-2.5 rounded-xl border px-4 py-3 text-sm">
                <span class="flex-1" x-text="aviso ? aviso.texto : ''"></span>
                <button type="button" @click="aviso = null" aria-label="Cerrar aviso"><x-icono nombre="cerrar" class="size-4" /></button>
            </div>

            @if ($errorCarrito)
                <p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errorCarrito }}</p>
            @endif

            {{-- ===================== Paso 2: carrito ===================== --}}
            <section x-show="paso === 2" class="flex flex-col gap-5">
                <div class="rounded-[14px] border border-slate-200 bg-white p-5">
                    <label for="escaner" class="text-sm font-medium">Lector de códigos</label>
                    <p class="text-xs text-slate-500">Escanee un bulto (BUL-…) para agregarlo completo, o un serial para agregarlo por unidad. También puede escribir el código y pulsar Enter.</p>
                    <input id="escaner" x-ref="escaner" x-model="escaneo" @keydown.enter.prevent="escanear()" autocomplete="off" autofocus
                           class="mt-2 h-[46px] w-full max-w-md rounded-[10px] border border-slate-300 px-3 font-mono text-[15px] uppercase" placeholder="BUL-251126-0001 o ACME-00001-0001">
                </div>

                <div class="grid gap-5 xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
                    {{-- Selección por pestañas --}}
                    <div class="rounded-[14px] border border-slate-200 bg-white">
                        <div class="flex gap-1 border-b border-slate-200 px-3 pt-3" role="tablist">
                            <button type="button" role="tab" :aria-selected="pestana === 'A'" @click="pestana = 'A'"
                                    :class="pestana === 'A' ? 'border-marca-600 text-marca-900' : 'border-transparent text-slate-500'"
                                    class="border-b-2 px-3 pb-2.5 text-sm font-medium">A · Bulto completo</button>
                            <button type="button" role="tab" :aria-selected="pestana === 'B'" @click="pestana = 'B'"
                                    :disabled="linea && linea.serializado" :title="linea && linea.serializado ? 'La línea tiene seriales: use A o C' : ''"
                                    :class="pestana === 'B' ? 'border-marca-600 text-marca-900' : 'border-transparent text-slate-500'"
                                    class="border-b-2 px-3 pb-2.5 text-sm font-medium disabled:opacity-40">B · Cantidad</button>
                            <button type="button" role="tab" :aria-selected="pestana === 'C'" @click="pestana = 'C'"
                                    :disabled="linea && !linea.serializado" :title="linea && !linea.serializado ? 'La línea no tiene seriales: use B' : ''"
                                    :class="pestana === 'C' ? 'border-marca-600 text-marca-900' : 'border-transparent text-slate-500'"
                                    class="border-b-2 px-3 pb-2.5 text-sm font-medium disabled:opacity-40">C · Serial</button>
                        </div>

                        {{-- A --}}
                        <div x-show="pestana === 'A'" class="overflow-x-auto">
                            <table class="w-full min-w-[640px] text-sm">
                                <thead>
                                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                                        <th scope="col" class="px-4 py-3 font-semibold">Bulto</th>
                                        <th scope="col" class="px-4 py-3 font-semibold">Pallet · Lugar</th>
                                        <th scope="col" class="px-4 py-3 font-semibold">En cuarentena</th>
                                        <th scope="col" class="px-4 py-3"><span class="sr-only">Agregar</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="b in bultos" :key="b.id">
                                        <tr class="border-t border-slate-100 align-top" :class="b.bloqueo ? 'bg-slate-50 text-slate-500' : ''">
                                            <td class="px-4 py-3 font-mono text-[13px] font-semibold" x-text="b.codigo"></td>
                                            <td class="px-4 py-3 font-mono text-[13px]"><span x-text="b.pallet || '—'"></span> · <span x-text="b.lugar || 'sin ubicar'"></span></td>
                                            <td class="px-4 py-3">
                                                <template x-for="l in b.lineas" :key="l.id">
                                                    <div class="tabular-nums">
                                                        <span x-text="l.sku"></span> · <span x-text="l.en_cuarentena"></span> <span class="text-xs text-slate-500" x-text="l.unidad"></span>
                                                        <span x-show="l.serializado" class="text-xs text-marca-600" x-text="'(' + l.seriales.length + ' seriales)'"></span>
                                                    </div>
                                                </template>
                                                <span x-show="b.bloqueo" class="text-xs text-acento-800" x-text="b.motivo"></span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <button type="button" @click="agregarBulto(b)" :disabled="!!b.bloqueo || bultoEnCarrito(b)"
                                                        class="h-9 rounded-[10px] border border-slate-300 px-3 text-sm font-medium hover:bg-slate-50 disabled:opacity-40"
                                                        x-text="bultoEnCarrito(b) ? 'En el carrito' : 'Agregar'"></button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <p x-show="bultos.length === 0" class="p-5 text-sm text-slate-500">Este BL no tiene bultos con unidades en cuarentena.</p>
                        </div>

                        {{-- B y C: primero la línea --}}
                        <div x-show="pestana !== 'A'" class="flex flex-col gap-4 p-5">
                            <div class="flex flex-col gap-1.5">
                                <label for="linea" class="text-sm font-medium">Línea (bulto · producto)</label>
                                <select id="linea" x-model.number="lineaId" class="h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px]">
                                    <option value="">Seleccione…</option>
                                    <template x-for="l in lineasDisponibles" :key="l.id">
                                        <option :value="l.id" x-text="l.bulto.codigo + ' · ' + l.sku + ' · ' + l.en_cuarentena + ' ' + l.unidad + (l.serializado ? ' · con seriales' : '')"></option>
                                    </template>
                                </select>
                                <p x-show="linea" class="text-xs text-slate-500">
                                    Recibida <span x-text="linea && linea.recibida"></span> · legalizada <span x-text="linea && linea.legalizada"></span>
                                    · libre para agregar <strong x-text="linea ? txt(libre(linea)) : ''"></strong> <span x-text="linea && linea.unidad"></span>
                                </p>
                            </div>

                            <div x-show="pestana === 'B'" class="flex flex-wrap items-end gap-3">
                                <div class="flex flex-col gap-1.5">
                                    <label for="cantidad" class="text-sm font-medium">Cantidad</label>
                                    <input id="cantidad" x-model="cantidad" inputmode="decimal" @keydown.enter.prevent="agregarCantidad()"
                                           class="h-[46px] w-44 rounded-[10px] border border-slate-300 px-3 text-right text-[15px] tabular-nums" placeholder="0">
                                </div>
                                <button type="button" @click="agregarCantidad()" class="h-[46px] rounded-[10px] bg-slate-800 px-5 text-sm font-medium text-white hover:bg-slate-700">Agregar cantidad</button>
                                <p class="w-full text-xs text-slate-500">UND solo admite enteros; KG y LT, hasta 3 decimales.</p>
                            </div>

                            <div x-show="pestana === 'C'" class="flex flex-col gap-3">
                                <template x-if="linea && linea.serializado">
                                    <div class="flex flex-col gap-3">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <button type="button" class="text-sm text-marca-600 hover:underline"
                                                    @click="elegidos = linea.seriales.filter(s => !serialEnCarrito(s.id)).map(s => s.id)">Elegir todos</button>
                                            <span class="text-xs text-slate-500"><span x-text="elegidos.length"></span> elegidos</span>
                                        </div>
                                        <div class="grid max-h-72 grid-cols-[repeat(auto-fill,minmax(190px,1fr))] gap-1.5 overflow-y-auto rounded-[10px] border border-slate-200 p-3">
                                            <template x-for="s in linea.seriales" :key="s.id">
                                                <label class="flex items-center gap-2 font-mono text-[13px]" :class="serialEnCarrito(s.id) ? 'text-slate-400' : ''">
                                                    <input type="checkbox" :value="s.id" x-model.number="elegidos" :disabled="serialEnCarrito(s.id)" class="rounded border-slate-300">
                                                    <span x-text="s.codigo"></span>
                                                </label>
                                            </template>
                                        </div>
                                        <button type="button" @click="agregarSeriales(elegidos)" :disabled="elegidos.length === 0"
                                                class="h-[46px] self-start rounded-[10px] bg-slate-800 px-5 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-40">Agregar seriales</button>
                                    </div>
                                </template>
                                <p x-show="!linea || !linea.serializado" class="text-sm text-slate-500">Elija una línea con seriales (generados en el aforo). Una línea sin seriales se legaliza por cantidad (B).</p>
                            </div>
                        </div>
                    </div>

                    {{-- Carrito --}}
                    <div class="flex flex-col rounded-[14px] border border-slate-200 bg-white">
                        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                            <div>
                                <h2 class="text-[15px] font-semibold">Carrito</h2>
                                <p class="text-xs text-slate-500"><span x-text="items.length"></span> ítems · <span x-text="lineasEnCarrito"></span> líneas · se guarda en este navegador</p>
                            </div>
                            <button type="button" x-show="items.length" @click="vaciar()" class="text-sm text-red-700 hover:underline">Vaciar</button>
                        </div>
                        <ul class="flex-1 divide-y divide-slate-100">
                            <template x-for="(item, indice) in items" :key="indice">
                                <li class="flex items-start gap-3 px-5 py-3 text-sm">
                                    <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-marca-50 text-xs font-semibold text-marca-900" x-text="item.modo"></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-mono text-[13px] font-semibold" x-text="titulo(item)"></p>
                                        <p class="text-xs text-slate-500" x-text="detalle(item)"></p>
                                    </div>
                                    <button type="button" @click="quitar(indice)" class="text-slate-400 hover:text-red-700" aria-label="Quitar del carrito"><x-icono nombre="cerrar" class="size-4" /></button>
                                </li>
                            </template>
                        </ul>
                        <p x-show="items.length === 0" class="px-5 py-8 text-center text-sm text-slate-500">El carrito está vacío.</p>
                        <div class="flex justify-end border-t border-slate-200 px-5 py-4">
                            <button type="button" @click="ir(3)" :disabled="items.length === 0"
                                    class="h-11 rounded-[10px] bg-marca-600 px-5 text-sm font-semibold text-white hover:bg-marca-700 disabled:opacity-50">Siguiente: autorización</button>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ===================== Paso 3: autorización ===================== --}}
            <section x-show="paso === 3" x-cloak class="flex max-w-[720px] flex-col gap-4 rounded-[14px] border border-slate-200 bg-white p-5">
                <div class="flex flex-col gap-1.5">
                    <label for="tipo_documento" class="text-sm font-medium">Tipo de documento</label>
                    <select id="tipo_documento" name="tipo_documento" x-model="auth.tipo" class="h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px]">
                        <option value="">Seleccione…</option>
                        @foreach ($tipos as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    @error('tipo_documento') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-1.5">
                        <label for="numero_autorizacion" class="text-sm font-medium">Número de autorización</label>
                        <input id="numero_autorizacion" name="numero_autorizacion" x-model="auth.numero" @change="revisarNumero()" maxlength="60"
                               class="h-[46px] rounded-[10px] border border-slate-300 px-3 font-mono text-[15px]">
                        @error('numero_autorizacion') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="fecha_autorizacion" class="text-sm font-medium">Fecha de autorización</label>
                        <input id="fecha_autorizacion" name="fecha_autorizacion" type="date" x-model="auth.fecha"
                               class="h-[46px] rounded-[10px] border border-slate-300 px-3 text-[15px]">
                        @error('fecha_autorizacion') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- L12: aviso, no bloquea --}}
                <div x-show="usos.length" x-cloak class="flex items-start gap-2.5 rounded-[10px] border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-900">
                    <x-icono nombre="alerta" class="mt-0.5 size-[18px]" />
                    <span>
                        Este número ya se usó en
                        <template x-for="(u, i) in usos" :key="u.consecutivo">
                            <span><a :href="u.url" target="_blank" class="font-mono font-semibold hover:underline" x-text="u.consecutivo"></a> (BL <span x-text="u.bl"></span>)<span x-text="i < usos.length - 1 ? ', ' : '.'"></span></span>
                        </template>
                        Puede continuar si es correcto.
                    </span>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="soporte" class="text-sm font-medium">Soporte</label>
                    <input id="soporte" name="soporte" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                           @change="soporte = $event.target.files.length ? $event.target.files[0].name : ''"
                           class="rounded-[10px] border border-slate-300 p-2 text-sm">
                    <span class="text-xs text-slate-500">PDF, JPG o PNG de hasta 10 MB. El archivo no se guarda en el navegador: si recarga la página, vuelva a elegirlo.</span>
                    @error('soporte') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-between gap-2.5 border-t border-slate-200 pt-4">
                    <button type="button" @click="ir(2)" class="h-11 rounded-[10px] border border-slate-300 px-5 text-sm font-medium hover:bg-slate-50">Volver al carrito</button>
                    <button type="button" @click="ir(4)" :disabled="!autorizacionCompleta"
                            class="h-11 rounded-[10px] bg-marca-600 px-5 text-sm font-semibold text-white hover:bg-marca-700 disabled:opacity-50">Siguiente: confirmar</button>
                </div>
            </section>

            {{-- ===================== Paso 4: confirmar ===================== --}}
            <section x-show="paso === 4" x-cloak class="flex max-w-[720px] flex-col gap-4 rounded-[14px] border border-slate-200 bg-white p-5">
                <h2 class="text-[15px] font-semibold">Confirme la legalización</h2>
                <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[180px_1fr]">
                    <dt class="text-slate-500">BL</dt>
                    <dd class="font-mono">{{ $bl->numero_bl }} · {{ $bl->cliente->codigo_corto }}</dd>
                    <dt class="text-slate-500">Líneas</dt>
                    <dd><span x-text="lineasEnCarrito"></span> (<span x-text="items.length"></span> ítems del carrito)</dd>
                    <dt class="text-slate-500">Unidades</dt>
                    <dd class="font-semibold tabular-nums" x-text="totales"></dd>
                    <dt class="text-slate-500">Documento</dt>
                    <dd><span x-text="tipos[auth.tipo] || ''"></span> · <span class="font-mono" x-text="auth.numero"></span></dd>
                    <dt class="text-slate-500">Fecha</dt>
                    <dd x-text="auth.fecha"></dd>
                    <dt class="text-slate-500">Soporte</dt>
                    <dd x-text="soporte"></dd>
                </dl>
                <div class="flex items-start gap-2.5 rounded-[10px] border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-900">
                    <x-icono nombre="alerta" class="mt-0.5 size-[18px]" />
                    <span>La legalización <strong>no se edita ni se borra</strong>. Si hay un error, se corrige con un ajuste de inventario.</span>
                </div>
                <div class="flex justify-between gap-2.5 border-t border-slate-200 pt-4">
                    <button type="button" @click="ir(3)" class="h-11 rounded-[10px] border border-slate-300 px-5 text-sm font-medium hover:bg-slate-50">Volver</button>
                    <button type="submit" :disabled="enviando"
                            class="inline-flex h-11 items-center gap-2 rounded-[10px] bg-marca-600 px-5 text-sm font-semibold text-white hover:bg-marca-700 disabled:opacity-50">
                        <x-icono nombre="check" class="size-[18px]" /> <span x-text="enviando ? 'Registrando…' : 'Registrar legalización'"></span>
                    </button>
                </div>
            </section>
        </form>
    @endif
@endsection
