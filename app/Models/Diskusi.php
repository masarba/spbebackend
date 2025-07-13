<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diskusi extends Model
{
    protected $table = 'diskusi';

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'id_ticket');
    }
}
