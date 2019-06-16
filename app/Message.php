<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Message
 * @package App
 *
 * @property int id
 * @property int sender_id
 * @property int receiver_id
 * @property string content
 * @property Carbon sent_at
 * @property Carbon created_at
 * @property Carbon updated_at
 *
 * @property User sender
 * @property User receiver
 */
class Message extends Model
{
    /** @var array */
    protected $fillable = [
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
        'receiver',
    ];

    /**
     * Return sender user for this message.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|User
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id', 'id');
    }

    /**
     * Return receiver user for this message.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|User
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id', 'id');
    }
}
