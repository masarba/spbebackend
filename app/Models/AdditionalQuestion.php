<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdditionalQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['audit_request_id', 'question', 'answer'];

    // Relasi ke AuditRequest
    public function auditRequest()
    {
        return $this->belongsTo(AuditRequest::class, 'audit_request_id');
    }
}

