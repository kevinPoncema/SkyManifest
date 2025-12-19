<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
    ];

    /**
     * Get the user that owns the project.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the git configuration for the project.
     */
    public function gitConfig(): HasOne
    {
        return $this->hasOne(GitConfig::class);
    }

    /**
     * Get the deploys for the project.
     */
    public function deploys(): HasMany
    {
        return $this->hasMany(Deploy::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the latest deploy for the project.
     */
    public function latestDeploy(): HasOne
    {
        return $this->hasOne(Deploy::class)->latestOfMany();
    }

    /**
     * Get the latest successful deploy for the project.
     */
    public function latestSuccessfulDeploy(): HasOne
    {
        return $this->hasOne(Deploy::class)
                    ->where('status', 'success')
                    ->latestOfMany();
    }

    /**
     * Get the domains for the project.
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }
}
