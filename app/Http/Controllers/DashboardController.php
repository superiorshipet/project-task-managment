<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $tasks = Task::query()->visibleTo($user);
        $projects = Project::query()->visibleTo($user);

        return view('dashboard.index', [
            'totalProjects' => (clone $projects)->count(),
            'pendingTasks' => (clone $tasks)->where('status', 'pending')->count(),
            'completedTasks' => (clone $tasks)->where('status', 'completed')->count(),
            'teamMembers' => $this->teamMembersCount($user),
            'recentProjects' => Project::query()->visibleTo($user)->withCount('tasks')->latest()->limit(5)->get(),
            'upcomingTasks' => Task::query()->visibleTo($user)->with(['project', 'assignee'])->whereNotNull('due_date')->orderBy('due_date')->limit(6)->get(),
        ]);
    }

    private function teamMembersCount(User $user): int
    {
        if ($user->isAdmin()) {
            return User::count();
        }

        if ($user->isProjectManager()) {
            return User::query()
                ->whereHas('assignedTasks.project', fn ($query) => $query->where('user_id', $user->id))
                ->count();
        }

        return 1;
    }
}
