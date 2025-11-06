<?php

namespace Ellaisys\Cognito\Events\Auth;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Exception;

class PostAuthFailedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Auth Data
     */
    public $data;

    /**
     * Error Information
     */
    public $error;

    /**
     * IP Address
     */
    public $ipAddress;

    /**
     * Create a new event instance.
     */
    public function __construct(array $data, Exception $error, string $ipAddress)
    {
        $this->data = $data;
        $this->error = $error;
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
