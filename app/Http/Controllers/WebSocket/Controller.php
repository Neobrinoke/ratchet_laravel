<?php

namespace App\Http\Controllers\WebSocket;

use Illuminate\Console\Command;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use SplObjectStorage;


class Controller implements MessageComponentInterface
{
    protected $clients;
//    private $subscriptions;
//    private $users;
//    private $userresources;
    private $command;

    private $channels;
    private $users;

    public function __construct(Command $command)
    {
        $this->clients = new SplObjectStorage;
//        $this->subscriptions = [];
//        $this->users = [];
//        $this->userresources = [];
        $this->command = $command;

        $this->channels = collect();
        $this->users = collect();

        $this->command->info("Server start !");
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->command->info("New connection [{$conn->resourceId}]");

        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $conn, $msg)
    {
        $message = json_decode($msg);
        if (isset($message->action)) {
            switch ($message->action) {
                case 'register':
                    $this->register($conn, $message);
                    break;

                case 'message':
                    $this->message($conn, $message);
                    break;
            }
        }

//        $data = json_decode($msg);
//        if (isset($data->command)) {
//            switch ($data->command) {
//                case 'subscribe':
//                    $this->subscriptions[$conn->resourceId] = $data->channel;
//                    break;

//                case 'groupchat':
//                    if (isset($this->subscriptions[$conn->resourceId])) {
//                        $target = $this->subscriptions[$conn->resourceId];
//                        foreach ($this->subscriptions as $id => $channel) {
//                            if ($channel == $target && $id != $conn->resourceId) {
//                                $this->users[$id]->send($data->message);
//                            }
//                        }
//                    }
//                    break;

//                case 'message':
//                    if (isset($this->userresources[$data->to])) {
//                        foreach ($this->userresources[$data->to] as $key => $resourceId) {
//                            if (isset($this->users[$resourceId])) {
//                                $this->users[$resourceId]->send($msg);
//                            }
//                        }
//                        $conn->send(json_encode($this->userresources[$data->to]));
//                    }
//
//                    if (isset($this->userresources[$data->from])) {
//                        foreach ($this->userresources[$data->from] as $key => $resourceId) {
//                            if (isset($this->users[$resourceId]) && $conn->resourceId != $resourceId) {
//                                $this->users[$resourceId]->send($msg);
//                            }
//                        }
//                    }
//                    break;

//                case 'register':
//                    if (isset($data->userId)) {
//                        if (isset($this->userresources[$data->userId])) {
//                            if (!in_array($conn->resourceId, $this->userresources[$data->userId])) {
//                                $this->userresources[$data->userId][] = $conn->resourceId;
//                            }
//                        } else {
//                            $this->userresources[$data->userId] = [];
//                            $this->userresources[$data->userId][] = $conn->resourceId;
//                        }
//                    }
//
//                    $conn->send(json_encode($this->users));
//                    $conn->send(json_encode($this->userresources));
//                    break;

//                default:
//                    $example = [
//                        'methods' => [
//                            "subscribe" => '{command: "subscribe", channel: "global"}',
//                            "groupchat" => '{command: "groupchat", message: "hello glob", channel: "global"}',
//                            "message" => '{command: "message", to: "1", message: "it needs xss protection"}',
//                            "register" => '{command: "register", userId: 9}',
//                        ],
//                    ];
//                    $conn->send(json_encode($example));
//                    break;
//            }
//        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);

        $this->command->info("Connection [{$conn->resourceId}] has disconnected");

        unset($this->users[$conn->resourceId]);
//        unset($this->subscriptions[$conn->resourceId]);
//
//        foreach ($this->userresources as &$userId) {
//            foreach ($userId as $key => $resourceId) {
//                if ($resourceId == $conn->resourceId) {
//                    unset($userId[$key]);
//                }
//            }
//        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->command->error("An error has occurred [{$e->getMessage()}]");

        $conn->close();
    }

    private function register(ConnectionInterface $conn, $message)
    {
        if (isset($message->userId)) {
            $this->command->info("New user register [{$message->userId}]");

            $this->users->put($conn->resourceId, (object) [
                'userId' => $message->userId,
                'resourceId' => $conn->resourceId,
                'connection' => $conn,
            ]);
        }
    }

    private function message(ConnectionInterface $conn, $message)
    {
        $from = $this->users->get($conn->resourceId);
        $to = $this->users->first(function ($user) use ($message) {
            return $user->userId === $message->to;
        });

        if ($from && $to) {
            $this->command->info("New message from [{$from->userId}] to [{$to->userId}] with content [{$message->content}] at [{$message->date}]!");

            $to->connection->send(json_encode([
                'action' => 'message',
                'from' => $from->userId,
                'content' => $message->content,
                'date' => $message->date,
                'is_mine' => false,
            ]));
        }
    }
}
