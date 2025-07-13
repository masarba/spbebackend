<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KamusKerentanan extends Model
{
    protected $table = 'kamus_kerentanans';

    protected $fillable = ['title','severity','desc','impact','recommendation'];

    public function temuanItsa()
    {
        return $this->hasOne(Temuan_itsa::class, 'id_kamus_kerentanan');
    }
}