<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Data_pic extends Model
{
    protected $table = 'data_pic';

    protected $fillable = [
        'id_data_pse', 
        'id_user', 
        'name', 
        'nik', 
        'nip', 
        'jabatan', 
        'phone', 
        'photo_ktp',
    ];

    // Relationship with DataPse model
    public function dataPse()
    {
        return $this->belongsTo(Data_pse::class, 'id_data_pse');
    }

    // Relationship with User model
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
