<?php

namespace App\WebSocket\Controllers;

use App\Chat;
use App\Message;
use App\User;
use App\WebSocket\User as WebSocketUser;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use SplObjectStorage;

class Controller implements MessageComponentInterface
{
    private const ACTION_REGISTER_USER = 'register_user';
    private const ACTION_REGISTER_CHAT = 'register_chat';
    private const ACTION_CREATE_CHAT = 'create_chat';
    private const ACTION_SEND_CHAT_MESSAGE = 'send_chat_message';
    private const ACTION_RECEIVE_NOTIFICATION = 'receive_notification';
    private const ACTION_RECEIVE_MESSAGE = 'receive_message';
    private const ACTION_ADD_CHAT_MEMBERS = 'add_chat_members';

    protected $clients;

    private $command = null;
    private $chats = [];

    /** @var WebSocketUser[] */
    private $users = [];

    public function __construct(Command $command)
    {
        $this->clients = new SplObjectStorage;

        $this->command = $command;
        $this->command->info('Server start !');
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->command->info("New connection [{$conn->resourceId}]");

        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $conn, $msg)
    {
        $data = json_decode($msg);
        if (!$data->action) {
            return;
        }

        /** @var Chat|null $chat */
        $chat = null;
        $webSocketUser = null;

        if ($data->action !== self::ACTION_REGISTER_USER) {
            $webSocketUser = $this->users[$conn->resourceId];
            if (!$webSocketUser) {
                return;
            }
        }

        if (in_array($data->action, [self::ACTION_REGISTER_CHAT, self::ACTION_SEND_CHAT_MESSAGE, self::ACTION_ADD_CHAT_MEMBERS])) {
            $chat = Chat::query()->find($data->chat_id);
            if (!$chat) {
                return;
            }
        }

        switch ($data->action) {
            case self::ACTION_REGISTER_USER:
                $this->registerUser($conn, $data);
                break;
            case self::ACTION_REGISTER_CHAT:
                $this->registerChat($webSocketUser, $chat);
                break;
            case self::ACTION_CREATE_CHAT:
                $this->createChat($webSocketUser, $data);
                break;
            case self::ACTION_SEND_CHAT_MESSAGE:
                $this->sendChatMessage($webSocketUser, $chat, $data);
                break;
            case self::ACTION_ADD_CHAT_MEMBERS:
                $this->addChatMembers($webSocketUser, $chat, $data);
                break;
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);

        $this->command->info("Connection [{$conn->resourceId}] has disconnected");

        $webSocketUser = $this->users[$conn->resourceId];
        if (!$webSocketUser) {
            return;
        }

        // unset closed member in all chats.
        foreach ($this->chats as &$chat) {
            unset($chat->members[$webSocketUser->id]);
        }

        // unset closed user in users array.
        unset($this->users[$webSocketUser->resourceId]);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->command->error("An error has occurred [{$e->getMessage()}]");

        $conn->close();
    }

    private function registerUser(ConnectionInterface $conn, $data)
    {
        $user = User::query()->find($data->user_id);
        if (!$user) {
            return;
        }

        $this->command->info("New user register [{$data->user_id}]");

        $this->users[$conn->resourceId] = new WebSocketUser($data->user_id, $conn->resourceId, $conn);
    }

    private function registerChat(WebSocketUser $webSocketUser, Chat $chat)
    {
        if (!isset($this->chats[$chat->id])) {
            $this->chats[$chat->id] = (object)[
                'members' => [],
            ];
        }

        $this->chats[$chat->id]->members[$webSocketUser->id] = &$webSocketUser;
    }

    private function createChat(WebSocketUser $webSocketUser, $data)
    {
        $this->command->info("New creation chat from [{$webSocketUser->id}]");

        /** @var Chat $newChat */
        $newChat = Chat::query()->create([
            'admin_id' => $webSocketUser->id,
            'name' => $data->name,
        ]);

        $members = collect();
        if (!empty($data->members)) {
            $users = User::query()->findMany($data->members);
            $members = $users->pluck('id');
        }
        $members->push($webSocketUser->id);

        $newChat->members()->sync($members);
        $newChat->load('members');

        foreach ($members as $member) {
            foreach ($this->users as $user) {
                if ($user->id === $member) {
                    $this->registerChat($user, $newChat);

                    $user->conn->send(json_encode([
                        'action' => self::ACTION_CREATE_CHAT,
                        'chat' => $newChat,
                    ]));
                }
            }
        }
    }

    private function sendChatMessage(WebSocketUser $webSocketUser, Chat $chat, $data)
    {
        $this->command->info("New message from [{$webSocketUser->id}] to chat [{$chat->id}]");

        /** @var Message $newMessage */
        $newMessage = $chat->messages()->create([
            'sender_id' => $webSocketUser->id,
            'content' => $data->content,
            'sent_at' => Carbon::make($data->date)->timezone(env('APP_TIMEZONE')),
        ]);

        $newMessage->load('sender');

        $chat->touch();

        $messageData = json_encode([
            'action' => self::ACTION_RECEIVE_MESSAGE,
            'message' => $newMessage,
        ]);

        /** @var WebSocketUser $member */
        foreach ($this->chats[$chat->id]->members as $member) {
            $member->conn->send($messageData);
        }
    }

    private function addChatMembers(WebSocketUser $webSocketUser, Chat $chat, $data)
    {
        $this->command->info("New members [{$webSocketUser->id}] for chat [{$chat->id}]");

        $newMembers = collect();
        if (!empty($data->members)) {
            $users = User::query()->findMany($data->members);
            $newMembers = $users->pluck('id');
        }
        $chat->members()->attach($newMembers);

        $chat->load('members');

        foreach ($newMembers as $newMember) {
            foreach ($this->users as $user) {
                if ($user->id === $newMember) {
                    $this->registerChat($user, $chat);
                }
            }
        }

        /** @var WebSocketUser $member */
        foreach ($this->chats[$chat->id]->members as $member) {
            if ($newMembers->contains($member->id)) {
                $member->conn->send(json_encode([
                    'action' => self::ACTION_CREATE_CHAT,
                    'chat' => $chat,
                ]));
            } else {
                $member->conn->send(json_encode([
                    'action' => self::ACTION_RECEIVE_NOTIFICATION,
                    'notification' => [
                        'type' => 'add_members',
                        'chat_id' => $chat->id,
                        'members' => $chat->members,
                        'new_members' => $newMembers,
                        'created_at' => now(),
                    ],
                ]));
            }
        }
    }
}
