<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;

/**
 * Class User
 * @package App
 *
 * @property int id
 * @property string name
 * @property string email
 * @property Carbon email_verified_at
 * @property string password
 * @property string remember_token
 * @property string profile_picture_url
 * @property Carbon created_at
 * @property Carbon updated_at
 *
 * @property Collection|Chat[] chats
 * @property ChatUser chat_pivot
 */
class User extends Authenticatable
{
    use Notifiable;

    /** @var array */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /** @var array */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @var array */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Return all chats for this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|Chat
     */
    public function chats()
    {
        return $this->belongsToMany(Chat::class)
            ->orderByDesc('updated_at')
            ->using(ChatUser::class)
            ->as('member_pivot')
            ->withPivot('id', 'last_exposed_at')
            ->withTimestamps();
    }
}
