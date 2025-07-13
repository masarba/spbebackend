<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    protected $table = 'notifikasi';

    public function dataPse()
    {
        return $this->belongsTo(Data_pse::class, 'id_data_pse');
    }
}
