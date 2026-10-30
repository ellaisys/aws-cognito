<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Events\Auth;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Log;

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

        // Log the event data for debugging purposes
        Log::debug('PostAuthSuccessEvent fired', [
            'user' => $this->user,
            'data' => $this->data,
            'ip_address' => $this->ipAddress,
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     * @codeCoverageIgnore
     */
    public function broadcastOn(): array
    {
        return [];
    }
}
