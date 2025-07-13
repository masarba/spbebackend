<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'auditee_id',
        'auditor_id',
        'answers',
    ];

    // Relasi dengan model User (Auditee)
    public function auditee()
    {
        return $this->belongsTo(User::class, 'auditee_id');
    }

    // Relasi dengan model User (Auditor)
    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    // Relasi dengan model AdditionalQuestion
    public function additionalQuestions()
    {
        return $this->hasMany(AdditionalQuestion::class);
    }
}
