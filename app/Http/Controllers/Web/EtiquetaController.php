<?php

namespace App\Http\Controllers\Web;

use App\Enums\MotivoReimpresion;
use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ReimprimirEtiquetaRequest;
use App\Services\Inbound\EtiquetaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Symfony\Component\HttpFoundation\Response;

/** CUS-007 reimpresión web en PDF (E6): SUPERVISOR todas; ADMIN_MAESTROS bodega y ubicación. */
class EtiquetaController extends Controller
{
    public function __construct(private readonly EtiquetaService $etiquetas) {}

    public function index(Request $request): View
    {
        $tipos = $request->user()->tieneRol(Rol::SUPERVISOR)
            ? EtiquetaService::TIPOS
            : ['bodega', 'ubicacion'];

        return view('etiquetas.index', [
            'tipos'   => $tipos,
            'motivos' => MotivoReimpresion::cases(),
        ]);
    }

    /** POST /etiquetas/reimprimir → PDF 4×2 in (se abre en otra pestaña). */
    public function reimprimir(ReimprimirEtiquetaRequest $request): Response
    {
        $modelo = $this->etiquetas->reimprimir(
            $request->validated('tipo'),
            $request->validated('codigo'),
            $request->motivo(),
            $request->validated('detalle'),
            $request->user(),
            'web',
        );

        $datos = $this->etiquetas->datos($modelo);
        $svg = (new BarcodeGeneratorSVG)->getBarcode($datos['barras'], BarcodeGeneratorSVG::TYPE_CODE_128, 2, 60);

        return Pdf::loadView('etiquetas.pdf', $datos + ['barrasSvg' => base64_encode($svg)])
            ->setPaper([0, 0, 288, 144])                                       // 4 × 2 in en puntos
            ->stream('etiqueta-'.str_replace('/', '-', $datos['barras']).'.pdf');
    }
}
