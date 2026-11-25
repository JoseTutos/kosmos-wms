<?php

return [

    // Los periodos diarios de los consecutivos (PLT/BUL-DDMMAA) se cortan a medianoche de esta zona.
    'zona_horaria' => env('KOSMOS_ZONA_HORARIA', 'America/Bogota'),

    // CUS-004: contraseña con la que nace un usuario y a la que vuelve al restablecerla.
    // Siempre exige cambiarla en el primer ingreso. Se cambia solo en el .env (DEFAULT_USER_PASSWORD).
    'password_por_defecto' => env('DEFAULT_USER_PASSWORD', 'Kosmos2024!'),

    /*
     | Parámetros de negocio editables (tabla configuraciones). Estos son los valores por defecto:
     | ConfiguracionSeeder los inserta si no existen y ConfiguracionService los usa si falta la fila.
     */
    'parametros' => [
        'max_intentos_login' => '5',     // CUS-004 (la BD limita intentos_fallidos a 0..5)
        'tolerancia_peso'    => '5',     // D5: ± % sobre el peso del manifiesto; fuera de rango exige observación
        'area_pallet_m2'     => '1.20',  // D9: pallet estándar 1,20 × 1,00 m
        'repetir_max'        => '50',    // D7a: máximo de bultos idénticos por "Repetir bulto ×N"
    ],

    'api' => [
        // Vida del token de la app. Un turno de bodega + margen; al vencer, la app vuelve al login.
        'token_horas' => (int) env('KOSMOS_TOKEN_HORAS', 12),

        // Peticiones por minuto por usuario autenticado (escaneo continuo).
        'peticiones_por_minuto' => (int) env('KOSMOS_API_RPM', 240),

        // Intentos de login por minuto por correo + IP (el bloqueo de cuenta a los 5 fallos es aparte).
        'login_por_minuto' => (int) env('KOSMOS_API_LOGIN_RPM', 10),
    ],

    'idempotencia' => [
        // Cuánto se guarda cada Idempotency-Key antes de purgarla (model:prune).
        'horas' => (int) env('KOSMOS_IDEMPOTENCIA_HORAS', 24),

        // Una clave "en proceso" más vieja que esto se considera abandonada (el proceso murió) y se reprocesa.
        'abandono_segundos' => (int) env('KOSMOS_IDEMPOTENCIA_ABANDONO', 60),

        
    ],

    'etiquetas' => [
        // Minutos después de crear un bulto o pallet en que se puede reimprimir sin motivo (la impresora pudo fallar).
        'reimpresion_libre_minutos' => (int) env('KOSMOS_REIMPRESION_LIBRE', 10),
    ],
];
