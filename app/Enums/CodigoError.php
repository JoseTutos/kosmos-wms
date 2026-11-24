<?php

namespace App\Enums;

/**
 * Catálogo de códigos de error que la API devuelve en el campo "codigo".
 * La app Flutter decide qué hacer (beep rojo, volver al login, reintentar) por el código,
 * nunca por el texto del mensaje.
 *
 * Contrato de error de /api/v1:
 *   { "mensaje": string, "codigo": CodigoError, "errores"?: {campo: [msg]}, "datos"?: {} }
 */
enum CodigoError: string
{
    // ---------- Generales de la API ----------
    case VALIDACION          = 'VALIDACION';
    case NO_AUTENTICADO      = 'NO_AUTENTICADO';
    case SIN_PERMISO         = 'SIN_PERMISO';
    case NO_ENCONTRADO       = 'NO_ENCONTRADO';
    case METODO_NO_PERMITIDO = 'METODO_NO_PERMITIDO';
    case DEMASIADOS_INTENTOS = 'DEMASIADOS_INTENTOS';
    case ERROR_HTTP          = 'ERROR_HTTP';
    case ERROR_INTERNO       = 'ERROR_INTERNO';
    case REGLA_NEGOCIO       = 'REGLA_NEGOCIO';     // reglas lanzadas solo con texto (Maestros)

    // ---------- Autenticación y cuenta (CUS-004) ----------
    case CREDENCIALES_INVALIDAS = 'CREDENCIALES_INVALIDAS';
    case USUARIO_BLOQUEADO      = 'USUARIO_BLOQUEADO';
    case USUARIO_INACTIVO       = 'USUARIO_INACTIVO';
    case ROL_SIN_ACCESO         = 'ROL_SIN_ACCESO';
    case CUENTA_NO_ACTIVA       = 'CUENTA_NO_ACTIVA';   // sesión abierta de una cuenta que dejó de estar ACTIVA
    case PASSWORD_TEMPORAL      = 'PASSWORD_TEMPORAL';  // debe cambiar la clave temporal antes de operar

    // ---------- Idempotencia (D13) ----------
    case IDEMPOTENCIA_CLAVE_REQUERIDA   = 'IDEMPOTENCIA_CLAVE_REQUERIDA';
    case IDEMPOTENCIA_CLAVE_INVALIDA    = 'IDEMPOTENCIA_CLAVE_INVALIDA';
    case IDEMPOTENCIA_CLAVE_REUTILIZADA = 'IDEMPOTENCIA_CLAVE_REUTILIZADA';
    case IDEMPOTENCIA_EN_PROCESO        = 'IDEMPOTENCIA_EN_PROCESO';

    // ---------- Inbound (CUS-005 a CUS-009) ----------
    case BL_NO_EN_RECEPCION  = 'BL_NO_EN_RECEPCION';
    case PRODUCTO_NO_EN_BL   = 'PRODUCTO_NO_EN_BL';
    case BULTO_OTRO_BL       = 'BULTO_OTRO_BL';
    case PALLET_CERRADO      = 'PALLET_CERRADO';
    case ESCANEO_NO_COINCIDE = 'ESCANEO_NO_COINCIDE';
    case BODEGA_INACTIVA     = 'BODEGA_INACTIVA';
    case CLIENTE_INACTIVO      = 'CLIENTE_INACTIVO';
    case PRODUCTO_INACTIVO     = 'PRODUCTO_INACTIVO';
    case PRODUCTO_OTRO_CLIENTE = 'PRODUCTO_OTRO_CLIENTE';
    case BL_CON_RECEPCION      = 'BL_CON_RECEPCION';
    case BULTOS_SIN_PALLET            = 'BULTOS_SIN_PALLET';
    case CODIGO_NO_ENCONTRADO         = 'CODIGO_NO_ENCONTRADO';
    case MOTIVO_REIMPRESION_REQUERIDO = 'MOTIVO_REIMPRESION_REQUERIDO';
    case ETIQUETA_TIPO_NO_PERMITIDO   = 'ETIQUETA_TIPO_NO_PERMITIDO';
    case BL_ESTADO_INVALIDO         = 'BL_ESTADO_INVALIDO';
    case OBSERVACION_REQUERIDA      = 'OBSERVACION_REQUERIDA';
    case BULTO_ESTADO_INVALIDO      = 'BULTO_ESTADO_INVALIDO';
    case BULTO_ANULADO              = 'BULTO_ANULADO';
    case BULTO_EN_OTRO_PALLET       = 'BULTO_EN_OTRO_PALLET';
    case BULTO_NO_EN_PALLET         = 'BULTO_NO_EN_PALLET';
    case PRODUCTO_REPETIDO_EN_BULTO = 'PRODUCTO_REPETIDO_EN_BULTO';
    case PALLET_ESTADO_INVALIDO     = 'PALLET_ESTADO_INVALIDO';
    case PALLET_VACIO               = 'PALLET_VACIO';
    case UBICACION_REQUERIDA        = 'UBICACION_REQUERIDA';
    case UBICACION_NO_PERMITIDA     = 'UBICACION_NO_PERMITIDA';
    case UBICACION_OTRA_BODEGA      = 'UBICACION_OTRA_BODEGA';
    case UBICACION_INACTIVA         = 'UBICACION_INACTIVA';
    case TRASLADO_MISMA_BODEGA      = 'TRASLADO_MISMA_BODEGA';
    case AFORO_NO_EN_PROCESO        = 'AFORO_NO_EN_PROCESO';
    case AFORO_INCOMPLETO           = 'AFORO_INCOMPLETO';
    case CONTEO_MENOR_A_LEGALIZADO  = 'CONTEO_MENOR_A_LEGALIZADO';
    case SERIAL_ESTADO_INVALIDO     = 'SERIAL_ESTADO_INVALIDO';

    // ---------- Núcleo de Inventario ----------
    case CANTIDAD_INVALIDA       = 'CANTIDAD_INVALIDA';
    case CANTIDAD_INSUFICIENTE   = 'CANTIDAD_INSUFICIENTE';
    case EXISTENCIA_INSUFICIENTE = 'EXISTENCIA_INSUFICIENTE';
    case CONSECUTIVO_AGOTADO     = 'CONSECUTIVO_AGOTADO';

    /** Mensaje por defecto. Quien lanza el error puede enviar uno más específico. */
    public function mensaje(): string
    {
        return match ($this) {
            self::VALIDACION          => 'Los datos enviados no son válidos.',
            self::NO_AUTENTICADO      => 'Su sesión no es válida o expiró. Inicie sesión de nuevo.',
            self::SIN_PERMISO         => 'Su rol no tiene permiso para esta acción.',
            self::NO_ENCONTRADO       => 'El recurso solicitado no existe.',
            self::METODO_NO_PERMITIDO => 'Operación no permitida sobre este recurso.',
            self::DEMASIADOS_INTENTOS => 'Demasiadas solicitudes. Espere un momento e intente de nuevo.',
            self::ERROR_HTTP          => 'No se pudo completar la solicitud.',
            self::ERROR_INTERNO       => 'Error interno del servidor. Intente de nuevo o contacte al administrador.',
            self::REGLA_NEGOCIO       => 'La operación no cumple una regla del negocio.',

            self::CREDENCIALES_INVALIDAS => 'Credenciales inválidas.',
            self::USUARIO_BLOQUEADO      => 'Usuario bloqueado por intentos fallidos. Contacte al Administrador del Sistema.',
            self::USUARIO_INACTIVO       => 'Usuario inactivo. Contacte al Administrador del Sistema.',
            self::ROL_SIN_ACCESO         => 'Su rol no tiene acceso a este canal.',
            self::CUENTA_NO_ACTIVA       => 'Su cuenta no está activa. Contacte al Administrador del Sistema.',
            self::PASSWORD_TEMPORAL      => 'Debe cambiar su contraseña temporal.',

            self::IDEMPOTENCIA_CLAVE_REQUERIDA   => 'Falta el encabezado Idempotency-Key.',
            self::IDEMPOTENCIA_CLAVE_INVALIDA    => 'El encabezado Idempotency-Key debe ser un UUID.',
            self::IDEMPOTENCIA_CLAVE_REUTILIZADA => 'La Idempotency-Key ya se usó con otra solicitud. Genere una nueva.',
            self::IDEMPOTENCIA_EN_PROCESO        => 'La solicitud anterior con esta clave aún se está procesando. Reintente en unos segundos.',

            self::BL_NO_EN_RECEPCION  => 'El BL no está en recepción.',
            self::PRODUCTO_NO_EN_BL   => 'El producto no está declarado en el manifiesto del BL.',
            self::BULTO_OTRO_BL       => 'El bulto pertenece a otro BL.',
            self::PALLET_CERRADO      => 'El pallet está cerrado.',
            self::ESCANEO_NO_COINCIDE => 'El código escaneado no coincide con el destino elegido.',
            self::BODEGA_INACTIVA     => 'La bodega está inactiva.',
            self::CLIENTE_INACTIVO      => 'El cliente está inactivo.',
            self::PRODUCTO_INACTIVO     => 'El producto está inactivo.',
            self::PRODUCTO_OTRO_CLIENTE => 'El producto no pertenece al cliente del BL.',
            self::BL_CON_RECEPCION      => 'El BL ya tiene bultos o pallets: no se puede eliminar.',
            self::BULTOS_SIN_PALLET            => 'Hay bultos sin pallet. Ármelos antes de finalizar.',
            self::CODIGO_NO_ENCONTRADO         => 'El código escaneado no existe.',
            self::MOTIVO_REIMPRESION_REQUERIDO => 'Indique el motivo de la reimpresión.',
            self::ETIQUETA_TIPO_NO_PERMITIDO   => 'Su rol no reimprime este tipo de etiqueta.',
            self::BL_ESTADO_INVALIDO         => 'El BL no permite esta operación en su estado actual.',
            self::OBSERVACION_REQUERIDA      => 'Hay diferencias con el manifiesto: registre una observación (RS-33).',
            self::BULTO_ESTADO_INVALIDO      => 'El bulto no permite esta operación en su estado actual.',
            self::BULTO_ANULADO              => 'El bulto está anulado.',
            self::BULTO_EN_OTRO_PALLET       => 'El bulto ya está en otro pallet. Retírelo primero.',
            self::BULTO_NO_EN_PALLET         => 'El bulto no está en este pallet.',
            self::PRODUCTO_REPETIDO_EN_BULTO => 'El producto ya tiene una línea en este bulto.',
            self::PALLET_ESTADO_INVALIDO     => 'El pallet no permite esta operación en su estado actual.',
            self::PALLET_VACIO               => 'El pallet no tiene bultos.',
            self::UBICACION_REQUERIDA        => 'La bodega maneja ubicaciones: escanee la ubicación de destino.',
            self::UBICACION_NO_PERMITIDA     => 'La bodega no maneja ubicaciones internas.',
            self::UBICACION_OTRA_BODEGA      => 'La ubicación pertenece a otra bodega.',
            self::UBICACION_INACTIVA         => 'La ubicación está inactiva.',
            self::TRASLADO_MISMA_BODEGA      => 'El origen y el destino del traslado son la misma bodega.',
            self::AFORO_NO_EN_PROCESO        => 'El aforo no está en proceso.',
            self::AFORO_INCOMPLETO           => 'Faltan líneas por contar en el aforo.',
            self::CONTEO_MENOR_A_LEGALIZADO  => 'El conteo es menor que la cantidad ya legalizada: registre un ajuste (CUS-019).',
            self::SERIAL_ESTADO_INVALIDO     => 'El serial no permite esta operación en su estado actual.',

            self::CANTIDAD_INVALIDA       => 'La cantidad debe ser mayor que cero.',
            self::CANTIDAD_INSUFICIENTE   => 'La cantidad supera lo disponible en la línea.',
            self::EXISTENCIA_INSUFICIENTE => 'No hay existencia suficiente para este movimiento.',
            self::CONSECUTIVO_AGOTADO     => 'Se agotó el consecutivo del periodo.',
        };
    }
}
