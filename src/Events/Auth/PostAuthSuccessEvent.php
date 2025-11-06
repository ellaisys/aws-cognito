<?php

namespace Ellaisys\Cognito\Events\Auth;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostAuthSuccessEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * User Data
     */
    public $user;

    /**
     * Additional Data
     */
    public $data;

    /**
     * IP Address
     */
    public $ipAddress;

    /**
     * Create a new event instance.
     */
    public function __construct(array $user, array $data, string $ipAddress)
    {
        $this->user = $user;
        $this->data = $data;
        $this->ipAddress = $ipAddress;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [];
    }
}
