<?php

namespace App\Models;

use App\Enums\StatutUser;
use App\Notifications\Auth\ResetPasswordNotification;
use App\Notifications\Auth\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Compagnie\Compagnie;
use Carbon\Carbon;
use App\Enums\UserRole;
use App\Models\Post\Like;
use App\Models\Post\Post;
use App\Models\Post\Comment;
use App\Models\Ticket\Ticket;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Jetstream\HasProfilePhoto;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'first_name',
        'last_name',
        'sexe',
        'numero_identifiant',
        'numero',
        'role',
        'compagnie_id',
        'loyalty_points',
        'loyalty_lifetime_points',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
        'is_verified',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'statut' => StatutUser::class,
        ];
    }

    /**
     * Le compte est considéré comme vérifié si l'email OU le téléphone l'est.
     */
    public function isVerified(): bool
    {
        return $this->email_verified_at !== null || $this->phone_verified_at !== null;
    }

    /**
     * Accesseur exposé dans l'API : { ..., "is_verified": true }
     */
    public function getIsVerifiedAttribute(): bool
    {
        return $this->isVerified();
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(callback: function (User $user) {
            $user->name = Str::upper($user->first_name) .' '. $user->last_name;
        });
    }


    function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    function pushTokens(): HasMany
    {
        return $this->hasMany(\App\Models\PushToken::class);
    }

    function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(\App\Models\LoyaltyTransaction::class)->latest();
    }

    /** Palier de fidélité (calculé sur les points cumulés). */
    public function getLoyaltyTierAttribute(): string
    {
        return \App\Enums\LoyaltyTier::pour((int) $this->loyalty_lifetime_points)->value;
    }

    /** Tokens Expo pour le canal de notification push. */
    public function routeNotificationForExpo(): array
    {
        return $this->pushTokens()->pluck('token')->all();
    }

    function comments():HasMany{
        return $this->hasMany(Comment::class);
    }

    function likes():HasMany{
        return $this->hasMany(Like::class);
    }


    function tickets():HasMany{
        return $this->hasMany(Ticket::class);
    }

    /** Restreint aux comptes rattachés à une compagnie donnée. */
    public function scopeOfCompagnie(Builder $query, int $compagnieId): Builder
    {
        return $query->where('compagnie_id', $compagnieId);
    }

    function compagnie(): BelongsTo
    {
        return $this->belongsTo(Compagnie::class);
    }

    function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    function hasAnyRole(array $roleNames): bool
    {
        return $this->roles()->whereIn('name', $roleNames)->exists();
    }

    function assignRole(string ...$roleNames): void
    {
        $roles = Role::whereIn('name', $roleNames)->get();
        $this->roles()->syncWithoutDetaching($roles);
    }

    function removeRole(string ...$roleNames): void
    {
        $roles = Role::whereIn('name', $roleNames)->get();
        $this->roles()->detach($roles);
    }

    function autrePersonnes():HasMany
    {
        return $this->hasMany(Authenticatable::class);

    }

    function ticketsAutrePersonne()
    {
        return $this->morphMany(Ticket::class,'autre_personne');
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }

}
