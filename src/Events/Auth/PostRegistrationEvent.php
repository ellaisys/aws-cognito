<?php

namespace Ellaisys\Cognito\Events\Auth;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostRegistrationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /*
     * Registration Request type
     */
    public string $type;

    /**
     * Auth Data
     */
    public $data;

    /*
     * Created user data
     */
    public $user;

    /**
     * IP Address
     */
    public $ipAddress;

    /**
     * Create a new event instance.
     */
    public function __construct(string $type, array $user, array $data, string $ipAddress)
    {
        $this->type = $type;
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
