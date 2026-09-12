<?php

namespace App\Models;

use App\Support\FastSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUSES = ['todo', 'in_progress', 'completed'];

    public const PRIORITIES = ['low', 'medium', 'high'];

    protected $fillable = [
        'project_id',
        'assigned_to',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'attachment',
        'progress',
        'metadata',
        'search_text',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'progress' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
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
                $query->orWhereHas('project', fn (Builder $projects) => $projects->where('projects.user_id', $user->id));

                return;
            }

            $query->where('assigned_to', $user->id)
                ->orWhereHas('project.members', fn (Builder $members) => $members->whereKey($user->id));
        });
    }

    protected static function booted(): void
    {
        static::saving(function (Task $task): void {
            $task->search_text = collect([
                $task->title,
                $task->description,
                $task->status,
                $task->priority,
                data_get($task->metadata, 'tags', []),
                data_get($task->metadata, 'labels', []),
            ])->flatten()->filter()->implode(' ');
        });
    }
}
