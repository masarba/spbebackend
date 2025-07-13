<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditRequest extends Model
{
    // Kolom yang bisa diisi secara massal
    protected $fillable = [
        'auditor_id', 
        'auditee_id', 
        'status', 
        'nda_document', 
        'signed_nda', 
        'pdf_path', 
        'additional_questions', // Kolom untuk pertanyaan tambahan
        'answer',               // Kolom untuk jawaban auditee
    ];

    /**
     * Relasi ke model User untuk auditor.
     */
    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    /**
     * Relasi ke model User untuk auditee.
     */
    public function auditee()
    {
        return $this->belongsTo(User::class, 'auditee_id');
    }

    /**
     * Relasi ke tabel questions.
     */
    public function questions()
    {
        return $this->hasMany(Question::class, 'audit_id', 'id');
    }

    /**
     * Relasi untuk mendapatkan additional questions.
     */
    public function additionalQuestions()
    {
        return $this->hasMany(AdditionalQuestion::class, 'audit_id', 'id');
    }

    /**
     * Scope untuk mengambil data audit berdasarkan status tertentu.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Mengakses file signed NDA.
     */
    public function signedNDA()
    {
        return $this->attributes['signed_nda'];
    }

    /**
     * Scope untuk mengambil audit request berdasarkan auditee_id.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $auditeeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForAuditee($query, $auditeeId)
    {
        return $query->where('auditee_id', $auditeeId);
    }
}
