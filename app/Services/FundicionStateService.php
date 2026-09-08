<?php

namespace App\Services;

use App\Models\FundicionHistory;
use App\Models\PreOrdenFundicion;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio para determinar el estado visual y de flujo (FSM) de una Orden de Trabajo
 * en el módulo de Fundición.
 */
class FundicionStateService
{
    /**
     * Resuelve el estado de una OT, devolviendo la configuración para la UI.
     * 
     * @param FundicionHistory $reg El registro de la OT.
     * @param FundicionHistory $targetReg El registro destino (útil para reprocesos).
     * @param array $aprobados Lista de clases aprobadas.
     * @param bool $isReproceso Indica si es una OT de reproceso (termina en _R\d+).
     * @return array Array asociativo con 'fsmState', 'icon', 'label', 'tooltip', 'borderColor', 'bgColor', 'textColor'
     */
    public static function resolverEstadoOT(FundicionHistory $reg, FundicionHistory $targetReg, array $aprobados, bool $isReproceso): array
    {
        $libStatus = $targetReg->calidad_revision_status ?? null;
        $fsmState = FundicionStateConstants::FSM_RECIBIDO;

        // FIX: Evitar que OTs de reproceso hereden el estado final "Casting Aprobado" de la OT original
        if ($isReproceso) {
            // Un reproceso no puede mostrar casting_aprobado de la OT original.
            // Solo es válido si hay un casting propio para esta OT de reproceso.
            $castingPdfPropio = PreOrdenFundicion::where('ot', $reg->ot)
                ->where('pdf_filename', 'LIKE', '%Casting%')
                ->exists();
            if ($libStatus === FundicionStateConstants::CASTING_APROBADO && !$castingPdfPropio) {
                $libStatus = null; // Reseteamos para que caiga en la lógica natural del reproceso
            }
        }

        $userPerfil = Auth::check() ? Auth::user()->perfil : null;

        $stateKey = self::determinarStateKey($reg, $targetReg, $aprobados, $isReproceso, $libStatus, $userPerfil);

        return self::getUIConfig($stateKey);
    }

    /**
     * Determina la clave de estado lógico (State Key) evaluando las condiciones del registro.
     */
    private static function determinarStateKey(FundicionHistory $reg, FundicionHistory $targetReg, array $aprobados, bool $isReproceso, ?string $libStatus, ?int $userPerfil): string
    {
        // 1. Usar el nuevo modelo FSM estricto si está definido
        if ($targetReg->estado_flujo) {
            $key = match($targetReg->estado_flujo) {
                \App\Enums\FundicionEstadoFlujo::CASTING_APROBADO => 'CASTING_APROBADO',
                \App\Enums\FundicionEstadoFlujo::CASTING => 'CASTING',
                \App\Enums\FundicionEstadoFlujo::APROBADO => 'CALIDAD_APROBADO',
                \App\Enums\FundicionEstadoFlujo::RECHAZADO => 'CALIDAD_RECHAZADO',
                \App\Enums\FundicionEstadoFlujo::MIXTO => 'CALIDAD_MIXTO',
                \App\Enums\FundicionEstadoFlujo::REVISANDO => 'CALIDAD_REVISANDO',
                \App\Enums\FundicionEstadoFlujo::CORREO_ENVIADO => in_array($userPerfil, [1, 3, 4]) ? 'RECIBIDO_CALIDAD' : (!$targetReg->isAlmacenFullyProcessed() ? 'PROCESO_PARCIAL_CORREO' : 'CORREO_ENVIADO'),
                \App\Enums\FundicionEstadoFlujo::PRE_ORDEN => !$targetReg->isAlmacenFullyProcessed() ? 'PROCESO_PARCIAL_PRE_ORDEN' : 'PRE_ORDEN',
                \App\Enums\FundicionEstadoFlujo::TIENE_MODELO => !$targetReg->isAlmacenFullyProcessed() ? 'PROCESO_PARCIAL_MODELO' : 'TIENE_MODELO',
                \App\Enums\FundicionEstadoFlujo::NUEVO => 'NUEVO',
                default => 'NUEVO',
            };

            // Override visual para rechazos procesados
            if ($reg->rechazos_procesados && !in_array($key, ['CASTING_APROBADO', 'CASTING'])) {
                return count($aprobados) > 0 ? 'RECHAZOS_PROCESADOS_APROBADO' : 'RECHAZOS_PROCESADOS_RECHAZADO';
            }

            return $key;
        }

        // 2. Fallback a lógica legacy (basada en booleanos) para registros antiguos sin migrar
        if ($libStatus === FundicionStateConstants::CASTING_APROBADO) {
            return 'CASTING_APROBADO';
        }
        
        if ($targetReg->casting_pdf_generated) {
            return 'CASTING';
        }
        
        if (in_array($libStatus, [FundicionStateConstants::CALIDAD_APROBADO, FundicionStateConstants::CALIDAD_PARCIAL])) {
            return 'CALIDAD_APROBADO';
        }
        
        if ($libStatus === FundicionStateConstants::CALIDAD_RECHAZADO) {
            return 'CALIDAD_RECHAZADO';
        }
        
        if ($libStatus === FundicionStateConstants::CALIDAD_MIXTO) {
            return 'CALIDAD_MIXTO';
        }
        
        if (in_array($libStatus, ['pendiente', 'aprobado', 'rechazado', 'mixto'])) {
            return 'CALIDAD_REVISANDO';
        }
        
        if ($targetReg->pre_orden_email_sent) {
            if (in_array($userPerfil, [1, 3, 4])) {
                return 'RECIBIDO_CALIDAD';
            }
            return !$targetReg->isAlmacenFullyProcessed() ? 'PROCESO_PARCIAL_CORREO' : 'CORREO_ENVIADO';
        }
        
        if ($targetReg->pre_orden_sent) {
            return !$targetReg->isAlmacenFullyProcessed() ? 'PROCESO_PARCIAL_PRE_ORDEN' : 'PRE_ORDEN';
        }
        
        if ($targetReg->tiene_modelo) {
            return !$targetReg->isAlmacenFullyProcessed() ? 'PROCESO_PARCIAL_MODELO' : 'TIENE_MODELO';
        }
        
        if ($reg->rechazos_procesados) {
            return count($aprobados) > 0 ? 'RECHAZOS_PROCESADOS_APROBADO' : 'RECHAZOS_PROCESADOS_RECHAZADO';
        }
        
        if ($isReproceso && in_array($libStatus, [null, 'pendiente']) && !$targetReg->tiene_modelo && !$targetReg->pre_orden_sent && !$targetReg->pre_orden_email_sent) {
            return 'REPROCESO_RECHAZADO';
        }
        
        return 'NUEVO';
    }

    /**
     * Mapea la clave de estado a su configuración visual (UI) correspondiente.
     */
    private static function getUIConfig(string $stateKey): array
    {
        return match ($stateKey) {
            'CASTING_APROBADO' => [
                'fsmState' => FundicionStateConstants::FSM_CASTING_APROBADO,
                'icon' => 'Proveedor.png',
                'label' => 'Enviado a Proveedor',
                'tooltip' => 'Pre-orden de casting enviada al proveedor, proceso finalizado',
                'borderColor' => '#9333ea',
                'bgColor' => '#f3e8ff',
                'textColor' => '#9333ea',
            ],
            'CASTING' => [
                'fsmState' => FundicionStateConstants::FSM_CASTING,
                'icon' => 'pdf-view.png',
                'label' => 'Casting',
                'tooltip' => 'Pre-orden de casting generada, esperando envío',
                'borderColor' => '#059669',
                'bgColor' => '#f0fdf4',
                'textColor' => '#15803d',
            ],
            'CALIDAD_APROBADO' => [
                'fsmState' => FundicionStateConstants::FSM_APROBADO,
                'icon' => 'Quality.png',
                'label' => 'Aprobado',
                'tooltip' => 'Modelo aprobado y liberado por Calidad',
                'borderColor' => '#10b981',
                'bgColor' => '#ecfdf5',
                'textColor' => '#047857',
            ],
            'CALIDAD_RECHAZADO' => [
                'fsmState' => FundicionStateConstants::FSM_RECHAZADO,
                'icon' => 'Quality.png',
                'label' => 'Rechazado',
                'tooltip' => 'Modelo rechazado por Calidad debido a desviaciones',
                'borderColor' => '#ef4444',
                'bgColor' => '#fef2f2',
                'textColor' => '#b91c1c',
            ],
            'CALIDAD_MIXTO' => [
                'fsmState' => FundicionStateConstants::FSM_MIXTO,
                'icon' => 'Quality.png',
                'label' => 'Mixto',
                'tooltip' => 'Liberación mixta por Calidad (clases aprobadas y rechazadas)',
                'borderColor' => '#eab308',
                'bgColor' => '#fef9c3',
                'textColor' => '#854d0e',
            ],
            'CALIDAD_REVISANDO' => [
                'fsmState' => FundicionStateConstants::FSM_REVISANDO,
                'icon' => 'Revisando.png',
                'label' => 'En Revisión',
                'tooltip' => 'Calidad está realizando la revisión del modelo',
                'borderColor' => '#f59e0b',
                'bgColor' => '#fffbeb',
                'textColor' => '#b45309',
            ],
            'RECIBIDO_CALIDAD' => [
                'fsmState' => FundicionStateConstants::FSM_RECIBIDO,
                'icon' => 'Recibido.png',
                'label' => 'Nuevo',
                'tooltip' => 'Pre-orden de fabricación de modelo recibida, esperando revisión de Calidad',
                'borderColor' => '#cbd5e1',
                'bgColor' => '#f1f5f9',
                'textColor' => '#64748b',
            ],
            'CORREO_ENVIADO' => [
                'fsmState' => FundicionStateConstants::FSM_CORREO_ENVIADO,
                'icon' => 'enviando.png',
                'label' => 'Correo Enviado',
                'tooltip' => 'Pre-orden enviada por correo electrónico, esperando revisión de Calidad',
                'borderColor' => '#818cf8',
                'bgColor' => '#e0e7ff',
                'textColor' => '#4f46e5',
            ],
            'PRE_ORDEN' => [
                'fsmState' => FundicionStateConstants::FSM_PRE_ORDEN,
                'icon' => 'pdf-view.png',
                'label' => 'Pre-Orden',
                'tooltip' => 'Pre-orden de modelo generada y guardada, pendiente de enviar',
                'borderColor' => '#60a5fa',
                'bgColor' => '#eff6ff',
                'textColor' => '#2563eb',
            ],
            'TIENE_MODELO' => [
                'fsmState' => FundicionStateConstants::FSM_TIENE_MODELO,
                'icon' => 'Espera.png',
                'label' => 'Tengo Modelo',
                'tooltip' => 'Modelo físico disponible en Almacén, en espera de revisión por Calidad',
                'borderColor' => '#0ea5e9',
                'bgColor' => '#f0f9ff',
                'textColor' => '#0369a1',
            ],
            'PROCESO_PARCIAL_CORREO' => [
                'fsmState' => FundicionStateConstants::FSM_REVISANDO,
                'icon' => 'Revisando.png',
                'label' => 'Proceso Parcial',
                'tooltip' => 'Pre-orden parcial enviada, esperando clases restantes o revisión',
                'borderColor' => '#f59e0b',
                'bgColor' => '#fffbeb',
                'textColor' => '#b45309',
            ],
            'PROCESO_PARCIAL_PRE_ORDEN' => [
                'fsmState' => FundicionStateConstants::FSM_REVISANDO,
                'icon' => 'Revisando.png',
                'label' => 'Proceso Parcial',
                'tooltip' => 'Pre-orden parcial generada, esperando procesar el resto de las clases',
                'borderColor' => '#f59e0b',
                'bgColor' => '#fffbeb',
                'textColor' => '#b45309',
            ],
            'PROCESO_PARCIAL_MODELO' => [
                'fsmState' => FundicionStateConstants::FSM_REVISANDO,
                'icon' => 'Revisando.png',
                'label' => 'Proceso Parcial',
                'tooltip' => 'Clases parciales indicadas con modelo físico, esperando las demás',
                'borderColor' => '#f59e0b',
                'bgColor' => '#fffbeb',
                'textColor' => '#b45309',
            ],
            'RECHAZOS_PROCESADOS_APROBADO' => [
                'fsmState' => FundicionStateConstants::FSM_APROBADO,
                'icon' => 'Quality.png',
                'label' => 'Aprobado',
                'tooltip' => 'Clases aprobadas se conservan en este registro',
                'borderColor' => '#10b981',
                'bgColor' => '#ecfdf5',
                'textColor' => '#047857',
            ],
            'RECHAZOS_PROCESADOS_RECHAZADO', 'REPROCESO_RECHAZADO' => [
                'fsmState' => FundicionStateConstants::FSM_RECHAZADO,
                'icon' => 'Rechazado.png',
                'label' => 'Rechazado',
                'tooltip' => 'Retornado hacia un nuevo ciclo de modelo (Reproceso)',
                'borderColor' => '#dc2626',
                'bgColor' => '#fef2f2',
                'textColor' => '#b91c1c',
            ],
            'NUEVO' => [
                'fsmState' => FundicionStateConstants::FSM_RECIBIDO,
                'icon' => 'Recibido.png',
                'label' => 'Nuevo',
                'tooltip' => 'Alerta inicial recibida, pendiente de procesar modelo por Almacén',
                'borderColor' => '#cbd5e1',
                'bgColor' => '#f1f5f9',
                'textColor' => '#64748b',
            ],
        };
    }
}
