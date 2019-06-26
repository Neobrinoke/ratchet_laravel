<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Message
 * @package App
 *
 * @property int id
 * @property int chat_id
 * @property int sender_id
 * @property string content
 * @property Carbon sent_at
 * @property Carbon created_at
 * @property Carbon updated_at
 *
 * @property Chat chat
 * @property User sender
 */
class Message extends Model
{
    /** @var array */
    protected $fillable = [
        'chat_id',
        'sender_id',
        'receiver_id',
        'content',
        'sent_at',
    ];

    /** @var array */
    protected $dates = [
        'sent_at',
    ];

    /** @var array */
    protected $with = [
        'sender',
    ];

    /**
     * Return chat for this message.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Chat
     */
    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Return sender user for this message.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|User
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id', 'id');
    }
}
