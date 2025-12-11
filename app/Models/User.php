<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;


class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // protected $fillable = [
    //     'name',
    //     'email',
    //     'password',
    //     'username'
    // ];

    protected $guarded = [];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'login_at' => 'datetime',
        'change_password_at' => 'datetime',
        'birthday' => 'date',
        'show_email' => 'boolean',
        'show_phone_number' => 'boolean',
        'show_biography' => 'boolean',
        'show_hobbies' => 'boolean',
        'show_occupation' => 'boolean',
        'show_birthday' => 'boolean',
        'show_gender' => 'boolean',
        'show_address' => 'boolean',
        'private_account' => 'boolean',
        'allow_search' => 'boolean',
        'is_online' => 'boolean',
        'last_active_at' => 'datetime',
        'show_online_status' => 'boolean',
    ];
    

     public function getJWTIdentifier()
    {
        return $this->getKey();
    }


    public function getJWTCustomClaims()
    {
        return [];
    }

    public function markAsOnline()
    {
        $this->update([
            'is_online' => true,
            'last_active_at' => now(),
        ]);

        // Broadcast online status
        broadcast(new UserOnlineStatus($this->id, true));
    }

    /**
     * Đánh dấu user là offline
     */
    public function markAsOffline()
    {
        $this->update([
            'is_online' => false,
            'last_active_at' => now(),
        ]);

        // Broadcast offline status
        broadcast(new UserOnlineStatus($this->id, false));
    }

    /**
     * Cập nhật last active time
     */
    public function updateLastActive()
    {
        $this->update([
            'last_active_at' => now(),
        ]);
    }

    /**
     * Kiểm tra user có online không
     */
    public function isOnline(): bool
    {
        // Check is_online flag hoặc last_active_at trong vòng 5 phút
        return $this->is_online || 
               ($this->last_active_at && $this->last_active_at->gt(now()->subMinutes(5)));
    }

}
