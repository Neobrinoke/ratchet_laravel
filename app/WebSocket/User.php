<?php

namespace App\WebSocket;

use Ratchet\ConnectionInterface;

class User
{
    /** @var int */
    public $id;
    /** @var int */
    public $resourceId;
    /** @var ConnectionInterface */
    public $conn;

    public function __construct(int $userId, int $resourceId, ConnectionInterface $conn)
    {
        $this->id = $userId;
        $this->resourceId = $resourceId;
        $this->conn = $conn;
    }
}
