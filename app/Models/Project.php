<?php

namespace App\Models;

use App\Support\FastSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

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

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')->withTimestamps();
    }

    public function whiteboard(): HasOne
    {
        return $this->hasOne(ProjectWhiteboard::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ProjectMessage::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function assignableUsers(): Collection
    {
        $this->loadMissing(['owner:id,name,email,role', 'members:id,name,email,role']);

        return collect([$this->owner])
            ->merge($this->members)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();
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
            $query->where('projects.user_id', $user->id);

            if ($user->isProjectManager()) {
                return;
            }

            $query->orWhereHas('tasks', fn (Builder $tasks) => $tasks
                ->where('assigned_to', $user->id)
                ->orWhereHas('assignees', fn (Builder $assignees) => $assignees->whereKey($user->id)))
                ->orWhereHas('members', fn (Builder $members) => $members->whereKey($user->id));
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
