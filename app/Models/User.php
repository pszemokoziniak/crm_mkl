<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'name',
        'email',
        'password',
        'password_changed_at',
        'owner',
        'active',
        'login_time',
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
        'owner' => 'integer',
        'email_verified_at' => 'datetime',
        'powiadomienia_kadrowe' => 'boolean',
    ];

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->withTrashed()->firstOrFail();
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
    public function contact()
    {
        return $this->hasOne(Contact::class);
    }

    public function getNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = Hash::needsRehash($password) ? Hash::make($password) : $password;
    }

    public function isDemoUser()
    {
        return $this->email === 'johndoe@example.com';
    }

    public function scopeOrderByName($query)
    {
        $query->orderBy('last_name')->orderBy('first_name');
    }

    public function scopeWhereRole($query, $role)
    {
        switch ($role) {
            case 'user': return $query->where('owner', false);
            case 'owner': return $query->where('owner', true);
        }
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        })->when($filters['role'] ?? null, function ($query, $role) {
            $query->whereRole($role);
        })->when($filters['trashed'] ?? null, function ($query, $trashed) {
            if ($trashed === 'with') {
                $query->withTrashed();
            } elseif ($trashed === 'only') {
                $query->onlyTrashed();
            }
        });
    }

    public function getPermissionsAttribute() {
        return [
            'admin' => $this->hasRole(Role::ADMIN),
            'biuro' => $this->hasRole(Role::BIURO),
            'kierownik' => $this->hasRole(Role::KIEROWNIK),
        ];
    }

    public function hasRole(Role $role): bool
    {
        return (int) $this->owner === $role->value;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    public function isBiuro(): bool
    {
        return $this->hasRole(Role::BIURO);
    }

    public function isKierownik(): bool
    {
        return $this->hasRole(Role::KIEROWNIK);
    }

    /** Admin lub biuro — pełny dostęp do wszystkich budów. */
    public function isOffice(): bool
    {
        return in_array((int) $this->owner, Role::officeValues(), true);
    }

    /**
     * Rekord pracownika (Contact) powiązany z użytkownikiem.
     * Powiązanie po user_id, a w razie braku — po imieniu i nazwisku.
     * Jedno źródło prawdy dla dashboardu, scope'ów i policy.
     */
    public function contactRecord(): ?Contact
    {
        return Contact::where('user_id', $this->id)
            ->orWhere(function ($query) {
                $query->where('first_name', $this->first_name)
                      ->where('last_name', $this->last_name);
            })
            ->first();
    }

    public function contactId(): ?int
    {
        return $this->contactRecord()?->id;
    }
}
