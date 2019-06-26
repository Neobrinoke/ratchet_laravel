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
    private const ACTION_RECEIVE_MESSAGE = 'receive_message';

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
        $msg = json_decode($msg);
        if (!$msg->action) {
            return;
        }

        /** @var Chat|null $chat */
        $chat = null;

        $webSocketUser = null;
        if ($msg->action !== self::ACTION_REGISTER_USER) {
            $webSocketUser = $this->users[$conn->resourceId];
            if (!$webSocketUser) {
                return;
            }
        }

        if (in_array($msg->action, [self::ACTION_REGISTER_CHAT, self::ACTION_SEND_CHAT_MESSAGE])) {
            $chat = Chat::query()->find($msg->chatId);
            if (!$chat) {
                return;
            }
        }

        switch ($msg->action) {
            case self::ACTION_REGISTER_USER:
                $this->registerUser($conn, $msg);
                break;
            case self::ACTION_REGISTER_CHAT:
                $this->registerChat($webSocketUser, $chat);
                break;
            case self::ACTION_CREATE_CHAT:
                $this->createChat($webSocketUser, $msg);
                break;
            case self::ACTION_SEND_CHAT_MESSAGE:
                $this->sendChatMessage($webSocketUser, $chat, $msg);
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

        foreach ($this->chats as $chatId => $chat) {
            if (in_array($webSocketUser, $chat['members'])) {
                unset($this->chats[$chatId]->members[$webSocketUser->id]);
            }
        }

        unset($this->users[$webSocketUser->resourceId]);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->command->error("An error has occurred [{$e->getMessage()}]");

        $conn->close();
    }

    private function registerUser(ConnectionInterface $conn, $msg)
    {
        $user = User::query()->find($msg->userId);
        if (!$user) {
            return;
        }

        $this->command->info("New user register [{$msg->userId}]");

        $this->users[$conn->resourceId] = new WebSocketUser($msg->userId, $conn->resourceId, $conn);
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

    private function createChat(WebSocketUser $webSocketUser, $msg)
    {
        /** @var Chat $chat */
        $chat = Chat::query()->create([
            'admin_id' => $webSocketUser->id,
            'name' => $msg->chatName,
        ]);

        $members = [];
        if (!empty($msg->chatMembers)) {
            $users = User::query()->findMany($msg->chatMembers);
            $members = $users->pluck('id');
        }

        $members[] = $webSocketUser->id;

        $chat->members()->sync($members);

        $chat->load('members');
        $chat->load('messages');

        foreach ($members as $member) {
            foreach ($this->users as $user) {
                if ($user->id === $member) {
                    $this->registerChat($user, $chat);

                    $user->conn->send(json_encode([
                        'action' => self::ACTION_CREATE_CHAT,
                        'data' => $chat,
                    ]));
                }
            }
        }
    }

    private function sendChatMessage(WebSocketUser $webSocketUser, Chat $chat, $msg)
    {
        $this->command->info("New message from [{$webSocketUser->id}] to chat [{$chat->id}] with content [{$msg->content}] at [{$msg->date}]!");

        /** @var Message $newMessage */
        $newMessage = $chat->messages()->create([
            'sender_id' => $webSocketUser->id,
            'content' => $msg->content,
            'sent_at' => Carbon::make($msg->date)->timezone(env('APP_TIMEZONE')),
        ]);

        $newMessage->load('sender');

        $chat->touch();

        $messageData = json_encode([
            'action' => self::ACTION_RECEIVE_MESSAGE,
            'data' => $newMessage,
        ]);

        /** @var WebSocketUser $member */
        foreach ($this->chats[$chat->id]->members as $member) {
            $member->conn->send($messageData);
        }
    }
}
