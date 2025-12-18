<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Domain extends Model
{
    protected $fillable = [
        'project_id',
        'url',
        'is_active',
        'ssl_status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the project that owns the domain.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
