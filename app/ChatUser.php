<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Class ChatUser
 * @package App
 *
 * @property int id
 * @property int chat_id
 * @property int user_id
 * @property Carbon last_exposed_at
 * @property Carbon created_at
 * @property Carbon updated_at
 *
 * @property bool is_admin
 *
 * @property Chat chat
 * @property User user
 */
class ChatUser extends Pivot
{
    /** @var array */
    protected $fillable = [
        'chat_id',
        'user_id',
        'last_exposed_at',
    ];

    /** @var array */
    protected $dates = [
        'last_exposed_at',
    ];

    /**
     * Return chat for this pivot.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Chat
     */
    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Return user for this pivot.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine if user is admin of chat for this pivot.
     *
     * @return bool
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->chat->admin_id === $this->user_id;
    }
}
