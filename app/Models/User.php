<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // available roles (value => label)
    public const ROLES = [
        'admin'     => 'Administrator',
        'manager'   => 'Manager',
        'warehouse' => 'Warehouse Staff',
        'staff'     => 'Staff',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // $user->hasRole('admin', 'manager')
    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    // shortcut for admin checks
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // readable role name
    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? ucfirst($this->role);
    }
}