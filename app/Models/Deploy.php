<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deploy extends Model
{
    protected $fillable = [
        'project_id',
        'status',
        'log_messages',
        'path',
        'duration_ms',
    ];

    protected $casts = [
        'log_messages' => 'array',
        'duration_ms' => 'integer',
    ];

    /**
     * Get the project that owns the deploy.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
