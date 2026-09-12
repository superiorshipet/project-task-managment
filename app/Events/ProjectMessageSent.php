<?php

namespace App\Events;

use App\Models\ProjectMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ProjectMessage $message) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('projects.'.$this->message->project_id);
    }

    public function broadcastAs(): string
    {
        return 'project.message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'body' => $this->message->body,
                'mentioned' => filled($this->message->mentioned_user_ids),
                'created_at' => $this->message->created_at?->diffForHumans(),
                'user' => [
                    'id' => $this->message->user?->id,
                    'name' => $this->message->user?->name,
                    'initials' => str($this->message->user?->name ?? 'NA')->substr(0, 2)->upper()->toString(),
                ],
            ],
        ];
    }
}
