<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'status',
        'agent_id',
        'agent_level_id',
        'referral_code',
        'rfid_uid',
        'player_code',
        'pin',
        'gcash_account_number',
        'maya_account_number',
        'maya_account_name',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'pin',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pin' => 'hashed',
        ];
    }

    /**
     * True once this (superadmin) user has set their approval PIN — see
     * App\Services\AdminPinService.
     */
    public function hasPin(): bool
    {
        return ! is_null($this->pin);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * The saved destination account for a given GCash/Maya channel (see
     * App\Services\Paybucks\PaybucksChannel) — used to pre-fill a deposit
     * and, for a withdrawal, as the ONLY account CashTransactionService
     * will pay out to (see hasSavedPaymentMethod()).
     */
    public function savedAccountNumber(string $channel): ?string
    {
        return match ($channel) {
            'gcash' => $this->gcash_account_number,
            'maya' => $this->maya_account_number,
            default => null,
        };
    }

    public function savedAccountName(string $channel): ?string
    {
        return $channel === 'maya' ? $this->maya_account_name : null;
    }

    /**
     * Whether every field that channel's withdrawal needs (see
     * PaybucksChannel::withdrawalRequiresAccountName()) has been saved —
     * a withdrawal is refused until this is true, so a player can't pay
     * out to an account no one confirmed is actually theirs.
     */
    public function hasSavedPaymentMethod(string $channel): bool
    {
        if (! filled($this->savedAccountNumber($channel))) {
            return false;
        }

        return ! \App\Services\Paybucks\PaybucksChannel::withdrawalRequiresAccountName($channel)
            || filled($this->savedAccountName($channel));
    }

    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class);
    }

    /**
     * The agent this user was recruited under (a player's sponsoring agent,
     * or an agent's upline agent).
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Everyone recruited directly under this user (their downline agents and/or
     * players, one level deep).
     */
    public function downline(): HasMany
    {
        return $this->hasMany(User::class, 'agent_id');
    }

    public function agentLevel(): BelongsTo
    {
        return $this->belongsTo(AgentLevel::class);
    }

    public function commissionRates(): HasMany
    {
        return $this->hasMany(AgentCommissionRate::class, 'agent_id');
    }

    /**
     * POS shifts this user has worked as a teller.
     */
    public function tellerShifts(): HasMany
    {
        return $this->hasMany(TellerShift::class, 'teller_id');
    }

    public function displayName(): string
    {
        return $this->username ?? $this->name;
    }

    /**
     * Stable, opaque per-player identifier used in the player's "profile
     * QR" — generated lazily on first use rather than at registration, so
     * existing accounts don't need a backfill.
     */
    public function profileCode(): string
    {
        if ($this->player_code) {
            return $this->player_code;
        }

        do {
            $code = Str::upper(Str::random(10));
        } while (self::where('player_code', $code)->exists());

        $this->update(['player_code' => $code]);

        return $code;
    }

    /**
     * The named route this user should land on after login (or when hitting
     * a role-agnostic entry point like "/"). Centralized here so login,
     * navigation, and the root route can never disagree.
     */
    public function homeRouteName(): string
    {
        return match (true) {
            $this->hasRole('superadmin|webmaster') => 'superadmin.dashboard',
            $this->hasRole('declarator') => 'declarator.events.index',
            $this->hasRole('agent') => 'agent.dashboard',
            $this->hasRole('teller') => 'teller.dashboard',
            default => 'play.index',
        };
    }
}
