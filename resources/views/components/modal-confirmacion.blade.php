{{--
    Kosmos WMS · Componente Blade · Ventana de confirmación reutilizable (diseño aprobado, croquis 6)

    Se incluye UNA vez en layouts/app. Cualquier botón la abre con un evento de Alpine:

        <button type="button" @click="$dispatch('confirmar', {{ Js::from([
            'url' => route('clientes.inactivar', $cliente), 'metodo' => 'PATCH',
            'titulo' => '¿Inactivar ACME?', 'detalle' => 'NIT 900123456',
            'puntos' => ['...', '...'], 'boton' => 'Inactivar cliente', 'peligro' => true,
        ]) }})">Inactivar</button>

    Seguridad:
      - El formulario es POST con @csrf y _method (PATCH/DELETE); nada por GET.
      - Los textos llegan con Js::from() (escapados) y se pintan con x-text (texto plano),
        nunca x-html: un nombre de cliente no puede inyectar código.
    Accesibilidad: role="dialog", aria-modal, foco inicial en "Cancelar", cierre con Esc.
    Rendimiento: una sola ventana por página, no una por fila.
--}}
<div x-data="{
        abierto: false,
        a: {},
        abrir(datos) { this.a = datos; this.abierto = true; this.$nextTick(() => this.$refs.cancelar.focus()); }
     }"
     @confirmar.window="abrir($event.detail)"
     @keydown.escape.window="abierto = false"
     x-show="abierto" x-cloak x-transition.opacity
     class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-6">

    <div role="dialog" aria-modal="true" aria-labelledby="confirmar-titulo"
         class="flex w-full max-w-[480px] flex-col gap-5 rounded-2xl bg-white p-7 shadow-xl">
        <div class="flex items-start gap-3.5">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-full"
                  :class="a.peligro ? 'bg-red-100 text-red-700' : 'bg-marca-100 text-marca-600'">
                <x-icono nombre="alerta" class="size-[22px]" />
            </span>
            <div>
                <h2 id="confirmar-titulo" class="text-lg font-semibold tracking-tight" x-text="a.titulo"></h2>
                <p class="mt-1 text-sm text-slate-500" x-text="a.detalle"></p>
            </div>
        </div>

        <ul class="list-disc space-y-1 pl-5 text-sm leading-relaxed text-slate-700">
            <template x-for="punto in (a.puntos || [])" :key="punto">
                <li x-text="punto"></li>
            </template>
        </ul>

        <form method="POST" :action="a.url" class="flex flex-wrap justify-end gap-2.5">
            @csrf
            <input type="hidden" name="_method" :value="a.metodo || 'POST'">
            <button type="button" x-ref="cancelar" @click="abierto = false"
                    class="h-11 rounded-[10px] border border-slate-300 px-[18px] text-sm font-medium hover:bg-slate-50">Cancelar</button>
            <button type="submit" x-text="a.boton"
                    :class="a.peligro ? 'bg-red-700 hover:bg-red-800' : 'bg-marca-600 hover:bg-marca-700'"
                    class="h-11 rounded-[10px] px-[18px] text-sm font-semibold text-white"></button>
        </form>
    </div>
</div>
