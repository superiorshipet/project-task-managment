<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectFavoriteController extends Controller
{
    public function toggle(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('view', $project);

        $request->user()->favoriteProjects()->toggle($project->id);

        return back()->with('status', 'Favorite projects updated.');
    }
}
