<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class TwoFactorAuth extends Model
{
    use HasFactory;

    protected $table = 'two_factor_auth';

    protected $fillable = [
        'user_id',
        'enabled',
        'secret',
        'recovery_codes',
        'enabled_at',
        'backup_method',
        'backup_contact',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'recovery_codes' => 'array',
        'enabled_at' => 'datetime',
    ];

    protected $hidden = [
        'secret',
        'recovery_codes',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessors & Mutators
    public function getSecretAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setSecretAttribute($value)
    {
        $this->attributes['secret'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getRecoveryCodesAttribute($value)
    {
        if (!$value) return null;
        
        $codes = json_decode($value, true);
        return array_map(function($code) {
            return Crypt::decryptString($code);
        }, $codes);
    }

    public function setRecoveryCodesAttribute($value)
    {
        if (!$value) {
            $this->attributes['recovery_codes'] = null;
            return;
        }

        $encrypted = array_map(function($code) {
            return Crypt::encryptString($code);
        }, $value);

        $this->attributes['recovery_codes'] = json_encode($encrypted);
    }

    // Helper methods
    public function enable($secret, $recoveryCodes = null)
    {
        $this->update([
            'enabled' => true,
            'secret' => $secret,
            'recovery_codes' => $recoveryCodes ?? $this->generateRecoveryCodes(),
            'enabled_at' => now(),
        ]);
    }

    public function disable()
    {
        $this->update([
            'enabled' => false,
            'secret' => null,
            'recovery_codes' => null,
            'enabled_at' => null,
        ]);
    }

    public function generateRecoveryCodes($count = 8)
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
        }
        return $codes;
    }

    public function useRecoveryCode($code)
    {
        $codes = $this->recovery_codes ?? [];
        $index = array_search(strtoupper($code), array_map('strtoupper', $codes));
        
        if ($index !== false) {
            unset($codes[$index]);
            $this->recovery_codes = array_values($codes);
            $this->save();
            return true;
        }
        
        return false;
    }

    public function hasRecoveryCodes()
    {
        return !empty($this->recovery_codes);
    }

    public function getQrCodeUrl($appName = null)
    {
        if (!$this->secret) return null;
        
        $appName = $appName ?? config('app.name');
        $email = $this->user->email;
        
        return "otpauth://totp/{$appName}:{$email}?secret={$this->secret}&issuer={$appName}";
    }

    public function verifyCode($code)
    {
        if (!$this->enabled || !$this->secret) {
            return false;
        }

        // This would integrate with a TOTP library like Google2FA
        // For now, we'll return a placeholder
        return $this->verifyTotpCode($code);
    }

    private function verifyTotpCode($code)
    {
        // Placeholder for TOTP verification
        // In a real implementation, you would use a library like:
        // return app('pragmarx.google2fa')->verifyKey($this->secret, $code);
        return false;
    }
}