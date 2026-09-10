<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clase extends Model
{
    use HasFactory;
    protected $fillable = [
        'id_ot',
        'nombre',
        'tamanio',
        'material',
        'proveedor',
        'composicion_quimica',
        'tipo_soldadura',
        'seccion',
        'piezas',
        'pedido',
        'finalizada',
        'fecha_entrega_fundicion',
        'entrega_tecamac',
        'fecha_real',
    ];
    public $timestamps = false;

    /**
     * Piezas asociadas a esta clase
     */
    public function piezas()
    {
        return $this->hasMany(\App\Models\Pieza::class, 'id_clase');
    }

    public function procesos()
    {
        return $this->hasOne(\App\Models\Procesos::class, 'id_clase');
    }

    public function fechasProcesos()
    {
        return $this->hasMany(\App\Models\Fecha_proceso::class, 'clase');
    }

    /**
     * Obtiene el tipo base de la clase normalizado (Molde, Bombillo, Fondo, etc.)
     */
    public function getBaseType(): string
    {
        return self::normalizeClassName($this->nombre);
    }

    /**
     * Normaliza un nombre de clase (e.g. "1 - MOLDES" -> "Molde", "3 - BOMBILLOS" -> "Bombillo")
     */
    public static function normalizeClassName(?string $nombre): string
    {
        $clLower = strtolower($nombre ?? '');
        $isExcluded = str_contains($clLower, 'base') || str_contains($clLower, 'tip') || str_contains($clLower, 'roll pin') || str_contains($clLower, 'porta') || str_contains($clLower, 'pastilla') || str_contains($clLower, 'canastilla');
        if ($isExcluded) return '';

        if (str_contains($clLower, 'bombillo')) return 'Bombillo';
        if (str_contains($clLower, 'molde')) return 'Molde';
        if (str_contains($clLower, 'obturador')) return 'Obturador';
        if (str_contains($clLower, 'fondo')) return 'Fondo';
        if (str_contains($clLower, 'corona')) return 'Corona';
        if (str_contains($clLower, 'plato')) return 'Plato';
        if (str_contains($clLower, 'embudo')) return 'Embudo';
        if (str_contains($clLower, 'cabeza de soplo')) return 'Cabeza de Soplo';
        if (str_contains($clLower, 'candado')) return 'Candado Obturador';

        return '';
    }
}

