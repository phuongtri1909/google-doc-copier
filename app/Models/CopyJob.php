<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CopyJob extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'source_doc_id', 
        'destination_doc_id', 
        'folder_id',
        'email', 
        'access_token', 
        'refresh_token',
        'total_sentences', 
        'current_position', 
        'status',
        'interval_seconds',
        'error_message',
        'source_title',
        'destination_title'
    ];
    
    protected $casts = [
        'total_sentences' => 'integer',
        'current_position' => 'integer',
        'interval_seconds' => 'integer',
    ];

    /**
     * Get the user associated with this job based on email
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }
    
    /**
     * Check if the job is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }
    
    /**
     * Check if the job is processing
     */
    public function isProcessing()
    {
        return $this->status === 'processing';
    }
    
    /**
     * Check if the job is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }
    
    /**
     * Check if the job has failed
     */
    public function hasFailed()
    {
        return $this->status === 'failed';
    }
    
    /**
     * Get progress percentage of the job
     */
    public function getProgressPercentage()
    {
        if ($this->total_sentences > 0) {
            return round(($this->current_position / $this->total_sentences) * 100);
        }
        
        return 0;
    }
}