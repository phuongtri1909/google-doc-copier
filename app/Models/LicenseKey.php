<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LicenseKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'user_id',
        'created_by',
        'is_active',
        'activated_at',
        'expires_at',
        'max_documents',
        'documents_used',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'max_documents' => 'integer',
        'documents_used' => 'integer',
    ];

    /**
     * Get the user that owns the license key.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who created the license key.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Determine if the license is valid.
     */
    public function isValid()
    {
        return $this->is_active && 
               ($this->expires_at === null || $this->expires_at > now()) &&
               ($this->max_documents === null || $this->documents_used < $this->max_documents);
    }

    /**
     * Increment the documents used count.
     */
    public function incrementDocumentsUsed()
    {
        $this->documents_used++;
        $this->save();
    }

    /**
     * Generate a new license key.
     */
    public static function generateKey()
    {
        return strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));
    }
}