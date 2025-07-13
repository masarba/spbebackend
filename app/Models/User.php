<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use PragmaRX\Google2FA\Google2FA;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $table = 'users';

    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'status',
        'verifikasi',
        'google2fa_secret',
        'is_2fa_enabled',
        'auditor_id',
        'auditee_id',
        'is_new_user'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'id_user');
    }

    public function dataPics()
    {
        return $this->hasMany(Data_pic::class, 'id_user');
    }

    public function is2FAEnabled()
    {
        return (bool) $this->is_2fa_enabled;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function generate2FASecret()
    {
        $google2fa = new Google2FA();
        $this->google2fa_secret = $google2fa->generateSecretKey();
        $this->save();

        return $this->google2fa_secret;
    }

    public function auditRequestsAsAuditor()
    {
        return $this->hasMany(AuditRequest::class, 'auditor_id', 'id');
    }

    /**
     * Relasi ke audit request sebagai auditee.
     */
    public function auditRequestsAsAuditee()
    {
        return $this->hasMany(AuditRequest::class, 'auditee_id', 'id');
    }

    public function auditResults()
    {
        return $this->hasMany(AuditResult::class, 'auditee_id'); // Jika Anda ingin menghubungkan hasil audit dengan auditee
    }
}
