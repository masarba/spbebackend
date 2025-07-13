<?php

// Audit.php (Model Audit)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    use HasFactory;

    protected $fillable = [
        'auditee_id',
        'group_id',
        'score',
        'status',
        'file',
        'audit_id',
        'question',
        'created_at',
        'updated_at'
    ];

    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function auditee()
    {
        return $this->belongsTo(User::class, 'auditee_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
