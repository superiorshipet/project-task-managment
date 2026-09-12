<?php

namespace App\Events;

use App\Models\WorkspaceNotification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkspaceNotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WorkspaceNotification $notification,
        public int $unreadCount,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('users.'.$this->notification->user_id);
    }

    public function broadcastAs(): string
    {
        return 'workspace.notification.created';
    }

    public function broadcastWith(): array
    {
        return [
            'unread_count' => $this->unreadCount,
            'notification' => [
                'id' => $this->notification->id,
                'title' => $this->notification->title,
                'body' => $this->notification->body,
                'type' => $this->notification->type,
                'read' => filled($this->notification->read_at),
                'created_at' => $this->notification->created_at?->diffForHumans(),
                'open_url' => route('notifications.open', $this->notification),
            ],
        ];
    }
}
