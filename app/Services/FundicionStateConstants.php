<?php

namespace App\Services;

/**
 * Constantes de estado para el flujo de Fundición.
 *
 * Este archivo centraliza todos los valores posibles de `calidad_revision_status`
 * en la tabla `fundicion_history`, junto con los estados FSM que se muestran en
 * la interfaz de Almacén y Calidad.
 *
 * NUNCA usar magic strings directamente en controladores o vistas.
 * Usar siempre las constantes definidas aquí.
 */
class FundicionStateConstants
{
    // =========================================================================
    // VALORES DE calidad_revision_status EN FUNDICION_HISTORY
    // Escritos por: CalidadFundicionController::submitLiberacion()
    //              CalidadFundicionController::sendReleaseAlert()
    //              AlmacenFundicionController::sendCastingEmail()
    // =========================================================================

    /** Calidad aún no ha dado ninguna decisión / en proceso de revisión. */
    const CALIDAD_PENDIENTE = 'pendiente';

    /** Todas las clases requeridas fueron APROBADAS por Calidad. */
    const CALIDAD_APROBADO = 'calidad_aprobado';

    /** Todas las clases requeridas fueron RECHAZADAS por Calidad. */
    const CALIDAD_RECHAZADO = 'calidad_rechazado';

    /** Algunas clases aprobadas y otras rechazadas (resultado mixto). */
    const CALIDAD_MIXTO = 'calidad_mixto';

    /**
     * Calidad aprobó al menos una clase pero aún faltan clases por evaluar.
     * Estado transitorio mientras se procesan todas las clases.
     */
    const CALIDAD_PARCIAL = 'calidad_parcial';

    /**
     * Almacén generó y envió la pre-orden de Casting al proveedor.
     * Este es el estado FINAL del flujo de la OT base.
     * Escritor: AlmacenFundicionController::sendCastingEmail() (línea ~3636)
     */
    const CASTING_APROBADO = 'casting_aprobado';

    // =========================================================================
    // ESTADOS FSM PARA LA INTERFAZ (calculados, NO almacenados en BD)
    // Estos son los valores del atributo data-estado-real en el <tr>
    // =========================================================================

    /** OT recién recibida, Almacén aún no ha tomado ninguna acción. */
    const FSM_RECIBIDO = 'recibido';

    /** Almacén indicó que tiene el modelo físico disponible. */
    const FSM_TIENE_MODELO = 'tiene_modelo';

    /** Almacén generó la pre-orden de fabricación de modelo (PDF guardado). */
    const FSM_PRE_ORDEN = 'pre_orden';

    /** Almacén envió la pre-orden de modelo por correo a Calidad. */
    const FSM_CORREO_ENVIADO = 'correo_enviado';

    /** Calidad está revisando el modelo (tiene registros de liberación pendientes). */
    const FSM_REVISANDO = 'revisando';

    /** Calidad aprobó el/los modelo(s). */
    const FSM_APROBADO = 'aprobado';

    /** Calidad rechazó el/los modelo(s). */
    const FSM_RECHAZADO = 'rechazado';

    /** Resultado mixto: algunas clases aprobadas, otras rechazadas. */
    const FSM_MIXTO = 'mixto';

    /** Almacén generó la pre-orden de Casting y está lista para enviar. */
    const FSM_CASTING = 'casting';

    /** Almacén envió la pre-orden de Casting al proveedor. Estado final. */
    const FSM_CASTING_APROBADO = 'casting_aprobado';

    // =========================================================================
    // GRUPOS DE ESTADOS PARA COMPARACIONES
    // =========================================================================

    /**
     * Estados que indican que Calidad ya emitió una alerta (aprobación o rechazo).
     * Sirven para determinar si los documentos de Calidad son visibles para Almacén.
     */
    const ESTADOS_CALIDAD_ALERTADA = [
        self::CALIDAD_APROBADO,
        self::CALIDAD_RECHAZADO,
        self::CALIDAD_MIXTO,
        self::CALIDAD_PARCIAL,
        self::CASTING_APROBADO,
    ];

    /**
     * Estados que indican aprobación parcial o total por Calidad
     * (hay clases que pueden pasar a Casting).
     */
    const ESTADOS_CON_APROBADOS = [
        self::CALIDAD_APROBADO,
        self::CALIDAD_PARCIAL,
        self::CALIDAD_MIXTO,
    ];
}
