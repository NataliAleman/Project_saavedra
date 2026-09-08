<?php

namespace App\Enums;

enum FundicionEstadoFlujo: string
{
    case NUEVO = 'recibido';
    case TIENE_MODELO = 'tiene_modelo';
    case PRE_ORDEN = 'pre_orden';
    case CORREO_ENVIADO = 'correo_enviado';
    case REVISANDO = 'revisando';
    case APROBADO = 'aprobado';
    case RECHAZADO = 'rechazado';
    case MIXTO = 'mixto';
    case CASTING = 'casting';
    case CASTING_APROBADO = 'casting_aprobado';

    public function label(): string
    {
        return match($this) {
            self::NUEVO => 'Nuevo',
            self::TIENE_MODELO => 'Tengo Modelo',
            self::PRE_ORDEN => 'Pre-Orden',
            self::CORREO_ENVIADO => 'Correo Enviado',
            self::REVISANDO => 'En Revisión',
            self::APROBADO => 'Aprobado',
            self::RECHAZADO => 'Rechazado',
            self::MIXTO => 'Mixto',
            self::CASTING => 'Casting',
            self::CASTING_APROBADO => 'Enviado a Proveedor',
        };
    }
}
