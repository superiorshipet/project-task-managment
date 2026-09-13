<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectFileController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('view', $project);

        $validated = $request->validate([
            'files' => ['required', 'array', 'max:10'],
            'files.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip', 'max:10240'],
        ]);

        foreach ($validated['files'] as $file) {
            $path = $file->store("projects/{$project->id}/files", config('filesystems.default'));

            $project->files()->create([
                'user_id' => $request->user()->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize() ?: 0,
            ]);
        }

        return redirect()
            ->route('projects.show', ['project' => $project, 'tab' => 'files'])
            ->with('status', 'Files uploaded successfully.');
    }

    public function destroy(Request $request, Project $project, ProjectFile $file): RedirectResponse
    {
        $this->authorize('view', $project);
        abort_unless($file->project_id === $project->id, 404);
        abort_unless($request->user()->canManageProject($project) || $request->user()->is($file->user), 403);

        Storage::disk(config('filesystems.default'))->delete($file->path);
        $file->delete();

        return redirect()
            ->route('projects.show', ['project' => $project, 'tab' => 'files'])
            ->with('status', 'File deleted successfully.');
    }
}
