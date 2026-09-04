<?php

declare(strict_types=1);

namespace Modules\System\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Tenant;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUlids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'terms_accepted_at',
        'privacy_policy_accepted_at',
        'acceptance_ip',
        'tenant_id',
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'terms_accepted_at' => 'datetime',
            'privacy_policy_accepted_at' => 'datetime',
        ];
    }

    /**
     * Verifica se o usuário já aceitou os termos e política vigentes.
     */
    public function hasAcceptedLegal(): bool
    {
        return $this->terms_accepted_at !== null && $this->privacy_policy_accepted_at !== null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function competences()
    {
        return $this->belongsToMany(InstrumentType::class, 'competences')
            ->withPivot('valid_until')
            ->withTimestamps();
    }

    public function hasValidCompetenceFor($instrumentTypeId): bool
    {
        $competence = $this->competences()->where('instrument_type_id', $instrumentTypeId)->first();

        if (! $competence) {
            return false;
        }

        if ($competence->pivot->valid_until) {
            return Carbon::parse($competence->pivot->valid_until)->isFuture();
        }

        return true;
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
