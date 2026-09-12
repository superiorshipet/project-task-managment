<?php

namespace App\Http\Controllers;

use App\Models\WorkspaceNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = WorkspaceNotification::query()
            ->visibleTo($request->user())
            ->with(['project:id,title', 'task:id,title,status'])
            ->when($request->filled('state'), function ($query) use ($request): void {
                $request->string('state')->toString() === 'unread'
                    ? $query->whereNull('read_at')
                    : $query->whereNotNull('read_at');
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => WorkspaceNotification::query()->visibleTo($request->user())->unread()->count(),
            'types' => WorkspaceNotification::query()->visibleTo($request->user())->distinct()->pluck('type'),
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $notifications = WorkspaceNotification::query()
            ->visibleTo($request->user())
            ->with(['project:id,title', 'task:id,title,status'])
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'unread_count' => WorkspaceNotification::query()->visibleTo($request->user())->unread()->count(),
            'notifications' => $notifications->map(fn (WorkspaceNotification $notification) => [
                'id' => $notification->id,
                'title' => $notification->title,
                'body' => $notification->body,
                'type' => $notification->type,
                'read' => filled($notification->read_at),
                'created_at' => $notification->created_at?->diffForHumans(),
                'open_url' => route('notifications.open', $notification),
            ])->values(),
        ]);
    }

    public function open(Request $request, WorkspaceNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return redirect($this->destinationUrl($notification));
    }

    public function markRead(Request $request, WorkspaceNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update(['read_at' => now()]);

        return back()->with('status', 'Notification marked as read.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        WorkspaceNotification::query()
            ->visibleTo($request->user())
            ->unread()
            ->update(['read_at' => now()]);

        return back()->with('status', 'All notifications marked as read.');
    }

    private function destinationUrl(WorkspaceNotification $notification): string
    {
        if ($notification->project) {
            return route('projects.show', [
                'project' => $notification->project,
                'tab' => $notification->type === 'project_mention' ? 'mentions' : 'board',
            ]);
        }

        return route('notifications.index');
    }
}
