<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     *
     * @param Message $message
     * @return void
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\PresenceChannel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel('chatroom.' . $this->message->chatroom_id);
    }

    /**
     * Get the data to broadcast with.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'message' => $this->message->content,
            'user' => $this->message->user->name,
            'created_at' => $this->message->created_at->toDateTimeString(),
        ];
    }

    /**
     * Specify the event name if you want to customize it.
     *
     * @return string
     */
    // public function broadcastAs()
    // {
    //     return 'message.sent';
    // }
}
