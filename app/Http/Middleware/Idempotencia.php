<?php

namespace App\Http\Middleware;

use App\Enums\CodigoError;
use App\Models\Idempotencia as RegistroIdempotencia;
use App\Support\ErrorApi;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * D13 – Sin duplicados. Va DESPUÉS de auth:sanctum (la clave es por usuario).
 *
 *  - POST: el encabezado Idempotency-Key (UUID) es obligatorio (428 si falta).
 *  - PUT / PATCH / DELETE: si llega la clave se usa; si no, pasa sin control.
 *  - Clave nueva           → se procesa y se guarda la respuesta (< 500).
 *  - Clave repetida        → se devuelve la respuesta guardada (+ Idempotency-Replayed: true).
 *  - Misma clave, otro body → 422 IDEMPOTENCIA_CLAVE_REUTILIZADA.
 *  - Aún en proceso        → 409 IDEMPOTENCIA_EN_PROCESO (la app reintenta en unos segundos).
 *  - Respuesta 5xx         → se borra el registro para que el reintento se procese de nuevo.
 *
 * Contrato para Flutter: un UUID nuevo por cada intención del operario; el MISMO UUID
 * solo en los reintentos automáticos de esa misma solicitud.
 */
class Idempotencia
{
    public const ENCABEZADO = 'Idempotency-Key';

    private const METODOS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! in_array($request->method(), self::METODOS, true)) {
            return $next($request);
        }

        $clave = trim((string) $request->header(self::ENCABEZADO));

        if ($clave === '') {
            return $request->isMethod('POST')
                ? ErrorApi::respuesta(CodigoError::IDEMPOTENCIA_CLAVE_REQUERIDA, estado: 428)
                : $next($request);
        }

        if (! Str::isUuid($clave)) {
            return ErrorApi::respuesta(CodigoError::IDEMPOTENCIA_CLAVE_INVALIDA, estado: 400);
        }

        $clave = Str::lower($clave);
        $huella = self::huella($request->method(), $request->path(), self::datosDe($request));

        $registro = $this->reservar((int) $usuario->getAuthIdentifier(), $clave, $request, $huella);

        if ($registro instanceof Response) {
            return $registro; // repetida, reutilizada o en proceso
        }

        $respuesta = $next($request);

        $this->guardar($registro, $respuesta);
        $respuesta->headers->set(self::ENCABEZADO, $clave);

        return $respuesta;
    }

    /** sha256 de método + ruta + datos canónicos (orden de claves irrelevante). */
    public static function huella(string $metodo, string $ruta, array $datos): string
    {
        return hash('sha256', strtoupper($metodo)."\n".$ruta."\n".json_encode(self::ordenar($datos), JSON_UNESCAPED_UNICODE));
    }

    /**
     * Intenta reservar la clave. Devuelve el registro propio (hay que procesar)
     * o la Response que corresponde a una clave ya existente.
     */
    private function reservar(int $usuarioId, string $clave, Request $request, string $huella): RegistroIdempotencia|Response
    {
        $ahora = now();

        // ON CONFLICT DO NOTHING: en PostgreSQL no aborta la transacción si la clave ya existe.
        $insertadas = RegistroIdempotencia::query()->insertOrIgnore([
            'usuario_id' => $usuarioId,
            'clave'      => $clave,
            'metodo'     => $request->method(),
            'ruta'       => Str::limit($request->path(), 250, ''),
            'huella'     => $huella,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);

        $registro = RegistroIdempotencia::where('usuario_id', $usuarioId)->where('clave', $clave)->first();

        if (! $registro) {
            // Se borró entre el INSERT y el SELECT (respuesta 5xx de otra petición): que reintente.
            return ErrorApi::respuesta(CodigoError::IDEMPOTENCIA_EN_PROCESO, estado: 409);
        }

        if ($insertadas === 1) {
            return $registro;
        }

        if (! hash_equals($registro->huella, $huella)) {
            return ErrorApi::respuesta(CodigoError::IDEMPOTENCIA_CLAVE_REUTILIZADA, estado: 422);
        }

        if ($registro->completada()) {
            return response($registro->cuerpo ?? '', $registro->estado_http)
                ->header('Content-Type', $registro->tipo_contenido ?? 'application/json')
                ->header(self::ENCABEZADO, $clave)
                ->header('Idempotency-Replayed', 'true');
        }

        // En proceso. Si lleva demasiado tiempo, el proceso original murió: se toma la clave.
        $limite = now()->subSeconds(config('kosmos.idempotencia.abandono_segundos', 60));

        if ($registro->updated_at->lt($limite)) {
            $tomada = RegistroIdempotencia::whereKey($registro->getKey())
                ->whereNull('estado_http')
                ->where('updated_at', $registro->updated_at)   // solo uno de los reintentos la toma
                ->update(['updated_at' => now()]);

            if ($tomada === 1) {
                return $registro->refresh();
            }
        }

        return ErrorApi::respuesta(CodigoError::IDEMPOTENCIA_EN_PROCESO, estado: 409);
    }

    private function guardar(RegistroIdempotencia $registro, Response $respuesta): void
    {
        $noGuardable = $respuesta->getStatusCode() >= 500
            || $respuesta instanceof StreamedResponse
            || $respuesta instanceof BinaryFileResponse;

        if ($noGuardable) {
            $registro->delete(); // el reintento vuelve a procesarse
            return;
        }

        $registro->forceFill([
            'estado_http'    => $respuesta->getStatusCode(),
            'cuerpo'         => (string) $respuesta->getContent(),
            'tipo_contenido' => Str::limit((string) $respuesta->headers->get('Content-Type'), 100, ''),
        ])->save();
    }

    /** Entrada + huella de cada archivo (un reintento con otro archivo no se confunde con el original). */
    private static function datosDe(Request $request): array
    {
        $archivos = $request->allFiles();

        if ($archivos === []) {
            return $request->all();
        }

        $huellas = array_map(
            fn ($f) => $f instanceof UploadedFile ? hash_file('sha256', $f->getRealPath()) : null,
            Arr::dot($archivos),
        );

        return $request->except(array_keys($archivos)) + ['__archivos' => $huellas];
    }

    private static function ordenar(array $datos): array
    {
        ksort($datos);

        foreach ($datos as $k => $v) {
            if (is_array($v)) {
                $datos[$k] = self::ordenar($v);
            }
        }

        return $datos;
    }
}
