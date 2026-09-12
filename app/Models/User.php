<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_PROJECT_MANAGER = 'project_manager';

    public const ROLE_USER = 'user';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_PROJECT_MANAGER,
        self::ROLE_USER,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function favoriteProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_favorites')->withTimestamps();
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(WorkspaceNotification::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isProjectManager(): bool
    {
        return $this->role === self::ROLE_PROJECT_MANAGER;
    }

    public function isUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    public function canManageProjects(): bool
    {
        return $this->isAdmin() || $this->isProjectManager();
    }

    public function canManageProject(Project $project): bool
    {
        return $this->isAdmin()
            || ($this->isProjectManager() && $project->user_id === $this->id);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
