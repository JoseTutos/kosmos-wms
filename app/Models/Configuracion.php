<?php

namespace App\Models;

use App\Services\Nucleo\ConfiguracionService;
use Illuminate\Database\Eloquent\Model;

/** Parámetro de negocio editable (clave → valor). Léalo con ConfiguracionService (tiene caché). */
class Configuracion extends Model
{
    protected $table = 'configuraciones';

    protected $primaryKey = 'clave';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['clave', 'valor'];

    /** Compatibilidad (User, Bodega): delega en ConfiguracionService. */
    public static function obtener(string $clave, mixed $porDefecto = null): mixed
    {
        return app(ConfiguracionService::class)->obtener($clave, $porDefecto);
    }
}
