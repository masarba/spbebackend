<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dok_aplikasi extends Model
{
    protected $table = 'dok_aplikasi';

    public function temuanItsa()
    {
        return $this->hasOne(Temuan_itsa::class, 'id_dok_aplikasi');
    }
}
