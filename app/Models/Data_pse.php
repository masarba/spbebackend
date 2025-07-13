<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Data_pse extends Model
{
    protected $table = 'data_pse';

    public function notifications()
    {
        return $this->hasMany(Notifikasi::class, 'id_data_pse');
    }

    public function dataPics()
    {
        return $this->hasMany(Data_pic::class, 'id_data_pse');
    }
}
