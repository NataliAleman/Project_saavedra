<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundicionStateLog extends Model
{
    use HasFactory;

    protected $table = 'fundicion_state_logs';

    protected $fillable = [
        'ot_id',
        'estado_anterior',
        'estado_nuevo',
        'user_id',
        'reason',
    ];

    public function fundicionHistory()
    {
        return $this->belongsTo(FundicionHistory::class, 'ot_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
