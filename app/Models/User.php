<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'google_id',
        'avatar',
        'password',
        'role',
        'is_active',
        'access_token',
        'refresh_token',
        'access_token_expiry',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'access_token',
        'refresh_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'access_token_expiry' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Check if user is an admin.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Get the license keys for the user.
     */
    public function licenseKeys()
    {
        return $this->hasMany(LicenseKey::class);
    }

    /**
     * Get valid license keys for the user.
     */
    public function validLicenseKeys()
    {
        return $this->licenseKeys()
            ->where('is_active', true)
            ->where(function($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Check if the user has an active license key.
     *
     * @return bool
     */
    public function hasValidLicense()
    {
        return $this->validLicenseKeys()->exists();
    }
    
    /**
     * Get jobs created by this user.
     */
    public function jobs()
    {
        return CopyJob::where('email', $this->email);
    }
    
    /**
     * Check if user is active.
     */
    public function isActive()
    {
        return $this->is_active;
    }
}