<?php

namespace App\Models;

use App\Support\FastSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'cover_image',
        'status',
        'metadata',
        'search_text',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_favorites')->withTimestamps();
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        return FastSearch::apply($query, $keyword);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            if ($user->isProjectManager()) {
                $query->where('user_id', $user->id);

                return;
            }

            $query->whereHas('tasks', fn (Builder $tasks) => $tasks->where('assigned_to', $user->id));
        });
    }

    protected static function booted(): void
    {
        static::saving(function (Project $project): void {
            $project->search_text = collect([
                $project->title,
                $project->description,
                data_get($project->metadata, 'client'),
                data_get($project->metadata, 'tags', []),
            ])->flatten()->filter()->implode(' ');
        });
    }
}
