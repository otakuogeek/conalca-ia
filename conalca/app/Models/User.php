<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'documento',
        'name',
        'email',
        'password',
        'active',
        'phone',
        'position',
        'department',
        'bio',
        'profile_photo',
        'language',
        'timezone',
        'dark_mode',
        'email_notifications',
        'push_notifications',
        'system_updates',
    ];

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
        'dark_mode' => 'boolean',
        'email_notifications' => 'boolean',
        'push_notifications' => 'boolean',
        'system_updates' => 'boolean',
    ];

    /**
     * Get all of the email_sended for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sent_emails(): HasMany
    {
        return $this->hasMany(Email::class, 'from_user');
    }

    /**
     * Get all of the received_emails for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function received_emails(): HasMany
    {
        return $this->hasMany(Email::class, 'to_user');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function columns()
    {
        return $this->hasMany(UserColumn::class);
    }

    public function userColumns()
    {
        return $this->hasMany(UserColumn::class)->orderBy('position');
    }

    // app/Models/User.php  (solamente los fragmentos nuevos)
    public function goalsAsCommercial()
    {
        return $this->hasMany(Goal::class, 'commercial_id');
    }

    public function goalsAsBoss()
    {
        return $this->hasMany(Goal::class, 'boss_id');
    }

    public function clientAssignments()
    {
        return $this->hasMany(ClientUserAssignment::class, 'user_id');
    }

    public function assignedClients()
    {
        return $this->belongsToMany(Client::class, 'client_user_assignments', 'user_id', 'client_id')
                    ->withPivot('assigned_by')
                    ->withTimestamps();
    }

    public function assignedClientsBy()
    {
        return $this->hasMany(ClientUserAssignment::class, 'assigned_by');
    }

}
