<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\Prometheus\Facades\Prometheus;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            $this->recordLoginMetric('failed');

            return redirect()
                ->route('login.index')
                ->withInput($request->only('email'))
                ->with('failed', 'Incorrect email or password.');
        }

        $user = Auth::user();

        if ($user && ! $user->hasVerifiedEmail()) {
            Auth::logout();
            $user->sendEmailVerificationNotification();

            $this->recordLoginMetric('unverified');

            return redirect()
                ->route('login.index')
                ->with('failed', 'Please verify your email address. A new verification link has been sent.');
        }

        if ($user && $user->shouldEnforceTwoFactorAuthentication() && $user->hasEnabledTwoFactorAuthentication()) {
            Auth::logout();

            session()->put('two_factor:id', $user->id);
            session()->put('two_factor:remember', $remember);

            $this->recordLoginMetric('two_factor_challenge');

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();

        if ($user && $user->shouldPromptForTwoFactorSetup()) {
            session()->flash('warning', 'Two-factor authentication is required for your role. Please enable it to keep your account secure.');
        }

        if ($user) {
            activity()->causedBy($user)->log('User logged into the portal');
        }

        $this->recordLoginMetric('success');

        return redirect()->intended('dashboard')->with('success', 'Welcome '.$user->name);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $name = $user?->name ?? 'Guest';

        if ($user) {
            activity()->causedBy($user)->log('User logged out of the portal');
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login.index')->with('success', 'Logout success, goodbye '.$name);
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                if (config('session.driver') === 'database') {
                    DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
                }

                $user->notify(new PasswordChangedNotification);

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login.index')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }

    protected function recordLoginMetric(string $status): void
    {
        Prometheus::addCounter('hotel_login_attempts_total')
            ->helpText('Login attempts grouped by status')
            ->labels(['status'])
            ->inc(1, [$status]);
    }
}
