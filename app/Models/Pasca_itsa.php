<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pasca_itsa extends Model
{
    protected $table = 'pasca_itsa';

    public function dokKoord()
    {
        return $this->belongsTo(Dok_koord::class, 'id_dok_koord');
    }
}
