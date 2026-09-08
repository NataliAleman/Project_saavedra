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
}
