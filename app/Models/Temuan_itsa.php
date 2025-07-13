<?php

namespace App\Models;

#use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Temuan_itsa extends Model
{
    protected $table = 'temuan_itsa';

    public function dokAplikasi()
    {
        return $this->belongsTo(Dok_aplikasi::class, 'id_dok_aplikasi');
    }

    public function kamusKerentanan()
    {
        return $this->belongsTo(KamusKerentanan::class, 'id_kamus_kerentanan');
    }
}
