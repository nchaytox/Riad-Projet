<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FALaravel\Support\Google2FA;

class TwoFactorAuthenticationController extends Controller
{
    public function __construct(private readonly Google2FA $google2FA)
    {
        $this->middleware(['auth']);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $qrCode = null;

        if ($user->twoFactorSecret() && ! $user->hasEnabledTwoFactorAuthentication()) {
            $qrCode = $this->google2FA->getQRCodeInline(
                config('app.name', 'Riad Projet'),
                $user->email,
                $user->twoFactorSecret()
            );
        }

        return view('auth.two-factor-manage', [
            'user' => $user,
            'qrCode' => $qrCode,
            'recoveryCodes' => $user->recoveryCodes(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('two-factor.manage')->with('success', 'Two-factor authentication is already enabled.');
        }

        $secret = $this->google2FA->generateSecretKey();

        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        activity()->causedBy($user)->log('Generated a new two-factor authentication secret.');

        return redirect()->route('two-factor.manage')->with('status', 'two-factor-secret-generated');
    }

    public function confirm(Request $request)
    {
        $user = $request->user();

        if (! $user->twoFactorSecret()) {
            throw ValidationException::withMessages([
                'code' => 'Two-factor authentication has not been initiated yet.',
            ]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $code = Str::upper(Str::replace(' ', '', $validated['code']));

        $secret = $user->twoFactorSecret();

        $isValid = $this->google2FA->verifyKey($secret, $code);

        if (! $isValid) {
            throw ValidationException::withMessages([
                'code' => 'The provided authentication code is invalid.',
            ]);
        }

        $recoveryCodes = $user->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ])->save();

        activity()->causedBy($user)->log('Enabled two-factor authentication.');

        return redirect()->route('two-factor.manage')->with([
            'success' => 'Two-factor authentication is now enabled.',
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $user = $request->user();

        if (! $user->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('two-factor.manage')->with('failed', 'Enable two-factor authentication before regenerating recovery codes.');
        }

        $codes = $user->generateRecoveryCodes();
        $user->replaceRecoveryCodes($codes);

        activity()->causedBy($user)->log('Regenerated two-factor recovery codes.');

        return redirect()->route('two-factor.manage')->with([
            'success' => 'Recovery codes regenerated.',
            'recoveryCodes' => $codes,
        ]);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        $user->disableTwoFactorAuthentication();

        activity()->causedBy($user)->log('Disabled two-factor authentication.');

        return redirect()->route('two-factor.manage')->with('success', 'Two-factor authentication has been disabled.');
    }
}
