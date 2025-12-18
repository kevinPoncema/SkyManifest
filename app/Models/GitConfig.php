<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GitConfig extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'repository_url',
        'branch',
        'base_directory',
    ];

    /**
     * Get the project that owns the git config.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the deploys for this git config.
     */
    public function deploys(): HasMany
    {
        return $this->hasMany(Deploy::class);
    }
}
