<?php

namespace App\Http\Controllers\Web;

use App\Enums\CodigoError;
use App\Exceptions\ReglaNegocioException;
use App\Http\Controllers\Controller;
use App\Services\Inbound\EtiquetaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Symfony\Component\HttpFoundation\Response;

/**
 * Paso 5 · Maestros: primera impresión de la etiqueta al crear una bodega o una ubicación (sin motivo).
 * Solo dentro de la ventana kosmos.etiquetas.reimpresion_libre_minutos; después es reimpresión (motivo obligatorio).
 * Bulto y pallet no pasan por aquí: su primera etiqueta la entrega la app al crearlos (ZPL).
 */
class EtiquetaInicialController extends Controller
{
    public function __construct(private readonly EtiquetaService $etiquetas) {}

    /** POST /etiquetas/imprimir { tipo: bodega|ubicacion, codigo } → PDF 4×2 in */
    public function __invoke(Request $request): Response
    {
        $datos = $request->validate([
            'tipo'   => ['required', Rule::in(['bodega', 'ubicacion'])],
            'codigo' => ['required', 'string', 'max:80'],
        ]);

        $modelo = $this->etiquetas->buscar($datos['tipo'], $datos['codigo']);
        $minutos = (int) config('kosmos.etiquetas.reimpresion_libre_minutos', 10);

        if (! $modelo->created_at?->gte(now()->subMinutes($minutos))) {
            throw new ReglaNegocioException(
                CodigoError::MOTIVO_REIMPRESION_REQUERIDO,
                "Pasaron más de {$minutos} minutos desde la creación: use Reimprimir etiqueta e indique el motivo.",
                campo: 'codigo',
            );
        }

        activity('etiquetas')->performedOn($modelo)->causedBy($request->user())
            ->withProperties(['tipo' => $datos['tipo'], 'codigo' => $datos['codigo'], 'canal' => 'web', 'ip' => $request->ip()])
            ->log('etiqueta impresa');

        $etiqueta = $this->etiquetas->datos($modelo);
        $svg = (new BarcodeGeneratorSVG)->getBarcode($etiqueta['barras'], BarcodeGeneratorSVG::TYPE_CODE_128, 2, 60);

        return Pdf::loadView('etiquetas.pdf', $etiqueta + ['barrasSvg' => base64_encode($svg)])
            ->setPaper([0, 0, 288, 144])
            ->stream('etiqueta-'.str_replace('/', '-', $etiqueta['barras']).'.pdf');
    }
}
