<?php

namespace App\Services\Inbound;

use App\Enums\CodigoError;
use App\Enums\MotivoReimpresion;
use App\Exceptions\ReglaNegocioException;
use App\Models\Bodega;
use App\Models\Bulto;
use App\Models\Pallet;
use App\Models\Ubicacion;
use App\Models\User;
use App\Support\Milesimas;
use Illuminate\Database\Eloquent\Model;

/**
 * CUS-007 Etiquetas (Code 128). Un solo armado de datos y dos salidas:
 *   - ZPL 4×2 in a 203 dpi (812×406 puntos) para la app, que lo envía a la impresora por TCP 9100 (E1, E3).
 *   - Datos para el PDF de la web (vista etiquetas.pdf).
 * Reimpresión (RS-35 a RS-37, E4, E5): motivo obligatorio y registro en la bitácora. E7: unos minutos después de crear
 * un bulto o pallet se puede reimprimir sin motivo (la impresora pudo fallar en el alta).
 *
 * Código de barras de una ubicación: "{BODEGA}/{UBICACION}" (el código de ubicación solo es único dentro de su bodega).
 */
final class EtiquetaService
{
    public const TIPOS = ['bulto', 'pallet', 'bodega', 'ubicacion'];

    /** E6: quién reimprime qué, por canal. */
    private const PERMISOS = [
        'api' => ['OPERARIO' => ['bulto', 'pallet']],
        'web' => ['SUPERVISOR' => self::TIPOS, 'ADMIN_MAESTROS' => ['bodega', 'ubicacion']],
    ];

    // ---------------- Salidas ----------------

    public function zpl(Model $modelo): string
    {
        $d = $this->datos($modelo);

        $zpl = "^XA\n^CI28\n^PW812\n^LL406\n^LH0,0\n";
        $zpl .= '^FO30,20^A0N,30,30^FD'.$this->texto($d['titulo'])."^FS\n";
        $zpl .= '^FO30,60^BY3,2,150^BCN,150,Y,N,N^FD'.$this->texto($d['barras'])."^FS\n";

        $y = 262;
        foreach (array_slice($d['lineas'], 0, 4) as $linea) {
            $zpl .= "^FO30,{$y}^A0N,26,26^FB752,1,0,L^FD".$this->texto($linea)."^FS\n";
            $y += 34;
        }

        return $zpl."^XZ\n";
    }

    /** @return array{tipo: string, titulo: string, barras: string, lineas: list<string>} */
    public function datos(Model $modelo): array
    {
        return match (true) {
            $modelo instanceof Bulto     => $this->datosBulto($modelo),
            $modelo instanceof Pallet    => $this->datosPallet($modelo),
            $modelo instanceof Bodega    => ['tipo' => 'bodega', 'titulo' => 'BODEGA', 'barras' => $modelo->codigo, 'lineas' => [$modelo->nombre]],
            $modelo instanceof Ubicacion => $this->datosUbicacion($modelo),
        };
    }

    // ---------------- Reimpresión ----------------

    public function buscar(string $tipo, string $codigo): Model
    {
        $codigo = trim($codigo);

        $modelo = match ($tipo) {
            'bulto'     => Bulto::where('codigo', $codigo)->first(),
            'pallet'    => Pallet::where('codigo', $codigo)->first(),
            'bodega'    => Bodega::where('codigo', $codigo)->first(),
            'ubicacion' => $this->buscarUbicacion($codigo),
        };

        if (! $modelo) {
            throw new ReglaNegocioException(CodigoError::CODIGO_NO_ENCONTRADO, "No existe {$tipo} con el código {$codigo}.", ['tipo' => $tipo, 'codigo' => $codigo], campo: 'codigo');
        }

        return $modelo;
    }

    /**
     * Valida permiso y motivo, y deja la reimpresión en la bitácora (E5). Devuelve el modelo para que el
     * controlador entregue ZPL (app) o PDF (web).
     *
     * @param 'api'|'web' $canal
     */
    public function reimprimir(string $tipo, string $codigo, ?MotivoReimpresion $motivo, ?string $detalle, User $usuario, string $canal): Model
    {
        if (! in_array($tipo, self::PERMISOS[$canal][$usuario->rol->value] ?? [], true)) {
            throw new ReglaNegocioException(CodigoError::ETIQUETA_TIPO_NO_PERMITIDO, "Su rol no reimprime etiquetas de {$tipo}.", ['tipo' => $tipo], campo: 'tipo');
        }

        $modelo = $this->buscar($tipo, $codigo);

        if (! $motivo && ! $this->enVentanaLibre($modelo)) {
            throw new ReglaNegocioException(CodigoError::MOTIVO_REIMPRESION_REQUERIDO, campo: 'motivo');
        }

        activity('etiquetas')->performedOn($modelo)->causedBy($usuario)
            ->withProperties([
                'tipo'    => $tipo,
                'codigo'  => $codigo,
                'motivo'  => $motivo?->value,
                'detalle' => $motivo === MotivoReimpresion::OTRO ? $detalle : null,
                'canal'   => $canal,
                'ip'      => request()->ip(),
            ])
            ->log($motivo ? 'etiqueta reimpresa' : 'etiqueta reimpresa (ventana inicial)');

        return $modelo;
    }

    /** E7: bulto o pallet recién creado. */
    private function enVentanaLibre(Model $modelo): bool
    {
        $minutos = (int) config('kosmos.etiquetas.reimpresion_libre_minutos', 10);

        return ($modelo instanceof Bulto || $modelo instanceof Pallet)
            && $modelo->created_at?->gte(now()->subMinutes($minutos));
    }

    // ---------------- Datos por tipo (E2) ----------------

    private function datosBulto(Bulto $bulto): array
    {
        $bulto->loadMissing(['bl.cliente:id,codigo_corto', 'detalles.producto:id,sku']);

        $productos = $bulto->detalles->map(fn ($d) => $d->producto->sku.' × '.$this->cantidad($d->cantidad_recibida));
        $resumen = $productos->take(2)->implode(' · ');

        if ($productos->count() > 2) {
            $resumen .= ' · +'.($productos->count() - 2).' más';
        }

        return [
            'tipo'   => 'bulto',
            'titulo' => 'BULTO',
            'barras' => $bulto->codigo,
            'lineas' => [
                "BL {$bulto->bl->numero_bl} · {$bulto->bl->cliente->codigo_corto}",
                $resumen,
                'Peso: '.$this->cantidad($bulto->peso_bruto_kg).' kg',
                $bulto->created_at?->timezone(config('kosmos.zona_horaria'))->format('d/m/Y H:i') ?? '',
            ],
        ];
    }

    private function datosPallet(Pallet $pallet): array
    {
        $pallet->loadMissing('bl.cliente:id,codigo_corto,razon_social');

        return [
            'tipo'   => 'pallet',
            'titulo' => 'PALLET',
            'barras' => $pallet->codigo,
            'lineas' => [
                "BL {$pallet->bl->numero_bl}",
                "{$pallet->bl->cliente->codigo_corto} · {$pallet->bl->cliente->razon_social}",
            ],
        ];
    }

    private function datosUbicacion(Ubicacion $ubicacion): array
    {
        $ubicacion->loadMissing('bodega:id,codigo,nombre');

        return [
            'tipo'   => 'ubicacion',
            'titulo' => 'UBICACIÓN',
            'barras' => "{$ubicacion->bodega->codigo}/{$ubicacion->codigo}",
            'lineas' => ["{$ubicacion->bodega->codigo} · {$ubicacion->bodega->nombre}", "Ubicación {$ubicacion->codigo}"],
        ];
    }

    /** "BOD-01/A-01-01" → ubicación A-01-01 de la bodega BOD-01. */
    private function buscarUbicacion(string $codigo): ?Ubicacion
    {
        if (! str_contains($codigo, '/')) {
            return null;
        }

        [$bodega, $ubicacion] = explode('/', $codigo, 2);

        return Ubicacion::query()
            ->where('codigo', $ubicacion)
            ->whereHas('bodega', fn ($q) => $q->where('codigo', $bodega))
            ->first();
    }

    /** "12.500" → "12,5"; "100.000" → "100". */
    private function cantidad(string|int|float|null $valor): string
    {
        $texto = rtrim(rtrim(Milesimas::texto(Milesimas::de($valor)), '0'), '.');

        return str_replace('.', ',', $texto);
    }

    /** ZPL: ^ y ~ son comandos; se quitan del texto. Con ^CI28 las tildes salen bien. */
    private function texto(string $valor): string
    {
        return mb_substr(str_replace(['^', '~'], ' ', $valor), 0, 70);
    }
}
