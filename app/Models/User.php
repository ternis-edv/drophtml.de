<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'github_id', 'github_token', 'github_refresh_token', 'ternis_id', 'ternis_token', 'ternis_refresh_token', 'password_set_at', 'email_verified_at', 'storage_limit_bytes'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected $keyType = 'string';

    public $incrementing = false;

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
            'password_set_at' => 'datetime',
            'storage_limit_bytes' => 'integer',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function avatarUrl(): ?string
    {
        if ($this->validAvatarUrl($this->avatar_url)) {
            return $this->avatar_url;
        }

        // Existing GitHub accounts get an avatar without another OAuth sign-in.
        return $this->github_id && ctype_digit((string) $this->github_id)
            ? 'https://avatars.githubusercontent.com/u/'.$this->github_id.'?s=128&v=4'
            : null;
    }

    public function setProviderAvatar(string $provider, ?string $url): void
    {
        if (! in_array($provider, ['github', 'ternis'], true) || ! $this->validAvatarUrl($url)) {
            return;
        }
        if ($provider === 'github' && ! in_array(parse_url($url, PHP_URL_HOST), ['avatars.githubusercontent.com', 'github.com'], true)) {
            return;
        }
        $this->forceFill(['avatar_url' => $url, 'avatar_provider' => $provider])->save();
    }

    private function validAvatarUrl(?string $url): bool
    {
        if (! $url || strlen($url) > 2048 || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $parts = parse_url($url);

        return ($parts['scheme'] ?? null) === 'https' && ! isset($parts['user']) && ! isset($parts['pass']);
    }

    // Deliberately excluded from $fillable: users cannot self-assign paid access.
    public function hasPaidPlan(): bool
    {
        return $this->plan === 'paid';
    }

    public function sites()
    {
        return $this->hasMany(Site::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function mediaAssets()
    {
        return $this->hasMany(MediaAsset::class);
    }
}
