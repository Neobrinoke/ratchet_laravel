<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

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
 * @property Carbon created_at
 * @property Carbon updated_at
 *
 * @property string profile_picture
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

    /** @var array */
    protected $appends = [
        'profile_picture',
    ];

    /**
     * Return url of profile picture for this user.
     *
     * @return string
     */
    public function getProfilePictureAttribute(): string
    {
        return 'https://ptetutorials.com/images/user-profile.png';
    }
}
