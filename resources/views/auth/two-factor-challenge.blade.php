@extends('template.auth')

@section('content')
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-card-body">
                <div class="auth-logo-section">
                    <div class="auth-logo">
                        <i class="fas fa-shield-alt text-primary"></i>
                    </div>
                    <h1 class="auth-title">Two-Factor Verification</h1>
                    <p class="auth-subtitle">Enter the 6-digit code from your authenticator app</p>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first('code') }}</div>
                @endif

                <form method="POST" action="{{ route('two-factor.challenge.store') }}" class="mt-4">
                    @csrf
                    <div class="form-floating mb-3">
                        <input type="text" name="code" id="code" class="form-control" placeholder="123456" required autofocus>
                        <label for="code"><i class="fas fa-key me-2"></i>Authentication Code</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-lock-open me-2"></i> Verify &amp; Continue
                    </button>
                </form>

                <div class="auth-help mt-4 text-center">
                    <p class="mb-1">Lost access to your authenticator app?</p>
                    <p class="text-muted mb-0">Use one of your recovery codes to sign in.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
