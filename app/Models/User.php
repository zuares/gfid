<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'employee_code',
        'role',
        'employee_id',
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function hasRole(string | array $roles): bool
    {
        $roles = (array) $roles;
        if ($this->isOwner()) {
            return true; // OWNER akses semua
        }

        return in_array($this->role, $roles, true);
    }
}
