<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Deploy extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'git_config_id',
        'status',
        'source_type',
        'commit_hash',
        'log_messages',
        'path',
        'duration_ms',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'log_messages' => 'array',
            'duration_ms' => 'integer',
        ];
    }

    /**
     * Get the project that owns the deploy.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the git config that owns the deploy (nullable for ZIP deploys).
     */
    public function gitConfig(): BelongsTo
    {
        return $this->belongsTo(GitConfig::class);
    }

    /**
     * Check if this is a Git deploy.
     */
    public function isGitDeploy(): bool
    {
        return $this->source_type === 'git';
    }

    /**
     * Check if this is a ZIP deploy.
     */
    public function isZipDeploy(): bool
    {
        return $this->source_type === 'zip';
    }
}
