{{--
    Kosmos WMS · Componente Blade anónimo · Íconos de trazo (SVG en línea)

    Uso: <x-icono nombre="candado" class="size-5" />
    Rendimiento: SVG en línea, sin fuentes de íconos ni peticiones extra.
    Accesibilidad: decorativo (aria-hidden). Un botón con solo ícono debe llevar aria-label.
--}}
@props(['nombre'])

<svg {{ $attributes->merge(['class' => 'size-5 shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($nombre)
        @case('inicio')       <path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/> @break
        @case('usuarios')     <circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18.5 20a6.5 6.5 0 0 0-3-5.5"/> @break
        @case('clientes')     <path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M16 9h2a2 2 0 0 1 2 2v10"/><path d="M8 7h4M8 11h4M8 15h4M3 21h18"/> @break
        @case('bl')           <path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/> @break
        @case('aforo')        <rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4h6v3H9z"/><path d="M9 14l2 2 4-4"/> @break
        @case('etiqueta')     <path d="M3 12V4h8l9 9-8 8-9-9z"/><circle cx="7.5" cy="7.5" r="1.5"/> @break
        @case('legalizacion') <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/><path d="M9 15l2 2 4-4"/> @break
        @case('cuarentena')   <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/> @break
        @case('ajuste')       <path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="18" cy="18" r="2"/> @break
        @case('salida')       <path d="M3 6h11v10H3z"/><path d="M14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/> @break
        @case('inventario')   <path d="M12 3l9 5-9 5-9-5 9-5z"/><path d="M3 13l9 5 9-5"/> @break
        @case('kardex')       <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/> @break
        @case('despachos')    <rect x="3" y="4" width="18" height="4" rx="1"/><path d="M5 8v11h14V8"/><path d="M10 12h4"/> @break
        @case('bitacora')     <path d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7z"/><path d="M9 12l2 2 4-4"/> @break
        @case('mas')          <path d="M12 5v14M5 12h14"/> @break
        @case('abajo')        <path d="M6 9l6 6 6-6"/> @break
        @case('arriba')       <path d="M18 15l-6-6-6 6"/> @break
        @case('ojo')          <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/> @break
        @case('ojo-off')      <path d="M3 3l18 18"/><path d="M10.6 5.1A10 10 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4.2M6.6 6.6A17 17 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/> @break
        @case('candado')      <rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/> @break
        @case('llave')        <circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M17 6l3 3"/> @break
        @case('prohibido')    <circle cx="12" cy="12" r="9"/><path d="M5.7 5.7l12.6 12.6"/> @break
        @case('papelera')     <path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/> @break
        @case('check')        <path d="M5 12l5 5L20 7"/> @break
        @case('circulo')      <circle cx="12" cy="12" r="8"/> @break
        @case('copiar')       <rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/> @break
        @case('alerta')       <path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/> @break
        @case('error')        <circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/> @break
        @case('info')         <circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/> @break
        @case('menu')         <path d="M4 6h16M4 12h16M4 18h16"/> @break
        @case('cerrar')       <path d="M6 6l12 12M18 6L6 18"/> @break
        @case('salir')        <path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 17l-5-5 5-5M5 12h11"/> @break
        @case('editar')       <path d="M4 20h4L19 9l-4-4L4 16v4z"/> @break
        @default              <circle cx="12" cy="12" r="2"/>
    @endswitch
</svg>
