<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'ticket';

    public function discussions()
    {
        return $this->hasMany(Diskusi::class, 'id_ticket');
    }
}
