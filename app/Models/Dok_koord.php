<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dok_koord extends Model
{
    protected $table = 'dok_koord';

    public function papsaItsa()
    {
        return $this->hasOne(Pasca_itsa::class, 'id_dok_koord');
    }

    public function timelineItsa()
    {
        return $this->hasMany(Timeline_itsa::class, 'id_dok_koord');
    }
}
