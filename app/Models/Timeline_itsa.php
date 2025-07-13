<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timeline_itsa extends Model
{
    protected $table = 'timeline_itsa';

    public function dokKoord()
    {
        return $this->belongsTo(Dok_koord::class, 'id_dok_koord');
    }
}
