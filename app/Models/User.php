<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'api_key',
        'api_key_created_at',
        'api_key_last_used_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_key',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'api_key_created_at' => 'datetime',
        'api_key_last_used_at' => 'datetime',
    ];

    /**
     * Generate a new API key for the user.
     */
    public function generateApiKey(): string
    {
        $this->api_key = 'wh_' . Str::random(60);
        $this->api_key_created_at = now();
        $this->save();

        return $this->api_key;
    }

    /**
     * Regenerate the API key.
     */
    public function regenerateApiKey(): string
    {
        return $this->generateApiKey();
    }

    /**
     * Update the last used timestamp for the API key.
     */
    public function updateApiKeyLastUsed(): void
    {
        $this->api_key_last_used_at = now();
        $this->save();
    }

    /**
     * Check if the user has an API key.
     */
    public function hasApiKey(): bool
    {
        return !empty($this->api_key);
    }

    /**
     * Get the user by API key.
     */
    public static function findByApiKey(string $apiKey): ?self
    {
        return static::where('api_key', $apiKey)->first();
    }
}
