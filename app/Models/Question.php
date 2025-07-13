<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'auditee_id', 
        'text', 
        'answer', 
        'category', 
        'audit_request_id', 
        'question',
        'is_draft',
        'draft_answer'
    ];

    public function audit()
    {
        return $this->belongsTo(Audit::class);
    }

    public function auditRequest()
    {
        return $this->belongsTo(AuditRequest::class, 'audit_request_id');
    }
}
