<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectWhiteboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectWhiteboardController extends Controller
{
    public function show(Request $request, Project $project): View|JsonResponse
    {
        $this->authorize('view', $project);

        $project->load(['owner:id,name,email,role', 'members:id,name,email,role'])
            ->loadCount([
                'tasks',
                'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'completed'),
            ]);

        $whiteboard = $project->whiteboard()
            ->with('updatedBy:id,name,email,role')
            ->first();

        if ($request->wantsJson()) {
            return response()->json($this->snapshot($whiteboard));
        }

        return view('projects.whiteboard', [
            'project' => $project,
            'whiteboard' => $whiteboard,
            'isFavorite' => $request->user()->favoriteProjects()->whereKey($project->id)->exists(),
            'activeTab' => 'whiteboard',
        ]);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $validated = $request->validate([
            'data' => ['required', 'array'],
            'data.items' => ['nullable', 'array', 'max:250'],
        ]);

        $data = [
            'items' => collect(data_get($validated, 'data.items', []))
                ->filter(fn ($item) => is_array($item))
                ->map(fn (array $item) => $this->normalizeItem($item))
                ->filter()
                ->values()
                ->all(),
        ];

        if (strlen(json_encode($data)) > 200000) {
            throw ValidationException::withMessages([
                'data' => 'The whiteboard is too large to save right now.',
            ]);
        }

        $whiteboard = ProjectWhiteboard::query()->updateOrCreate(
            ['project_id' => $project->id],
            [
                'data' => $data,
                'updated_by' => $request->user()->id,
            ],
        )->load('updatedBy:id,name,email,role');

        return response()->json($this->snapshot($whiteboard));
    }

    public function sync(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $whiteboard = $project->whiteboard()
            ->with('updatedBy:id,name,email,role')
            ->first();

        return response()->json($this->snapshot($whiteboard));
    }

    private function snapshot(?ProjectWhiteboard $whiteboard): array
    {
        $data = $whiteboard?->data ?? ['items' => []];

        return [
            'data' => $data,
            'revision' => md5(json_encode($data)),
            'updated_at' => $whiteboard?->updated_at?->toISOString(),
            'updated_by' => $whiteboard?->updatedBy?->only(['id', 'name', 'email', 'role']),
        ];
    }

    private function normalizeItem(array $item): ?array
    {
        $type = (string) ($item['type'] ?? '');

        if (! in_array($type, ['note', 'text', 'rect', 'line', 'path'], true)) {
            return null;
        }

        $base = [
            'id' => Str::limit((string) ($item['id'] ?? Str::uuid()), 64, ''),
            'type' => $type,
            'color' => $this->allowedColor((string) ($item['color'] ?? 'indigo')),
            'text' => Str::limit((string) ($item['text'] ?? ''), 220, ''),
        ];

        foreach (['x', 'y', 'width', 'height', 'x1', 'y1', 'x2', 'y2'] as $key) {
            if (isset($item[$key]) && is_numeric($item[$key])) {
                $base[$key] = max(-5000, min(5000, (float) $item[$key]));
            }
        }

        if ($type === 'path') {
            $base['points'] = collect($item['points'] ?? [])
                ->filter(fn ($point) => is_array($point) && isset($point['x'], $point['y']) && is_numeric($point['x']) && is_numeric($point['y']))
                ->take(800)
                ->map(fn ($point) => [
                    'x' => max(-5000, min(5000, (float) $point['x'])),
                    'y' => max(-5000, min(5000, (float) $point['y'])),
                ])
                ->values()
                ->all();
        }

        return $base;
    }

    private function allowedColor(string $color): string
    {
        return in_array($color, ['indigo', 'emerald', 'amber', 'rose', 'slate'], true) ? $color : 'indigo';
    }
}
