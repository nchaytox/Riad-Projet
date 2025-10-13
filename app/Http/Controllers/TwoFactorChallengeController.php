<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FALaravel\Support\Google2FA;
use Spatie\Prometheus\Facades\Prometheus;

class TwoFactorChallengeController extends Controller
{
    public function __construct(private readonly Google2FA $google2FA)
    {
        $this->middleware('guest');
    }

    public function create(Request $request)
    {
        if (! session()->has('two_factor:id')) {
            return redirect()->route('login.index');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request)
    {
        if (! session()->has('two_factor:id')) {
            return redirect()->route('login.index');
        }

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = User::find(session()->get('two_factor:id'));

        if (! $user) {
            session()->forget(['two_factor:id', 'two_factor:remember']);

            return redirect()->route('login.index')->with('failed', 'Unable to locate the user for two-factor authentication.');
        }

        $code = Str::upper(Str::replace(' ', '', $validated['code']));

        if ($user->twoFactorSecret() && $this->google2FA->verifyKey($user->twoFactorSecret(), $code)) {
            $this->successfullyAuthenticate($request, $user, false);

            return redirect()->intended('dashboard')->with('success', 'Two-factor authentication successful.');
        }

        $recoveryCodes = $user->recoveryCodes();
        if (in_array($code, $recoveryCodes, true)) {
            $user->replaceRecoveryCodes(array_values(array_diff($recoveryCodes, [$code])));

            $this->successfullyAuthenticate($request, $user, true);

            return redirect()->intended('dashboard')->with('success', 'Logged in using a recovery code. Please regenerate recovery codes if necessary.');
        }

        Prometheus::addCounter('hotel_login_attempts_total')
            ->helpText('Login attempts grouped by status')
            ->labels(['status'])
            ->inc(1, ['two_factor_failed']);

        throw ValidationException::withMessages([
            'code' => 'The provided authentication code is invalid.',
        ]);
    }

    protected function successfullyAuthenticate(Request $request, User $user, bool $usedRecoveryCode = false): void
    {
        $remember = (bool) session()->pull('two_factor:remember', false);

        session()->forget('two_factor:id');

        Auth::login($user, $remember);

        $request->session()->regenerate();

        activity()->causedBy($user)->log(
            $usedRecoveryCode
                ? 'Completed two-factor authentication with a recovery code.'
                : 'Completed two-factor authentication challenge.'
        );

        Prometheus::addCounter('hotel_login_attempts_total')
            ->helpText('Login attempts grouped by status')
            ->labels(['status'])
            ->inc(1, [$usedRecoveryCode ? 'two_factor_recovery' : 'two_factor_success']);
    }
}
