<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class Chat
 * @package App
 *
 * @property int id
 * @property int admin_id
 * @property string name
 * @property Carbon created_at
 * @property Carbon updated_at
 *
 * @property User admin
 * @property Collection|User[] members
 * @property Collection|Message[] messages
 * @property ChatUser member_pivot
 */
class Chat extends Model
{
    /** @var array */
    protected $fillable = [
        'admin_id',
        'name',
    ];

    /** @var array */
    protected $with = [
        'members',
        'messages',
    ];

    /**
     * Return admin for this chat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo|User
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'id', 'admin_id');
    }

    /**
     * Return all members for this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|User
     */
    public function members()
    {
        return $this->belongsToMany(User::class)
            ->using(ChatUser::class)
            ->as('chat_pivot')
            ->withPivot('id', 'last_exposed_at')
            ->withTimestamps();
    }

    /**
     * Return all message for this chat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|Message
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
