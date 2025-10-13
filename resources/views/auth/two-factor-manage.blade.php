@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Two-Factor Authentication</span>
                    @if($user->hasEnabledTwoFactorAuthentication())
                        <span class="badge bg-success">Enabled</span>
                    @else
                        <span class="badge bg-warning text-dark">Pending</span>
                    @endif
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if(session('failed'))
                        <div class="alert alert-danger">{{ session('failed') }}</div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning">{{ session('warning') }}</div>
                    @endif

                    @if(session('status') === 'two-factor-secret-generated')
                        <div class="alert alert-info">Scan the QR code below using your authenticator app, then enter the 6-digit verification code to finish setup.</div>
                    @endif

                    <p class="mb-3">
                        Two-factor authentication adds an extra layer of security to your account. Employees and administrators are required to keep it enabled.
                    </p>

                    @if(!$user->twoFactorSecret())
                        <form method="POST" action="{{ route('two-factor.store') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">Generate Setup Code</button>
                        </form>
                    @elseif(!$user->hasEnabledTwoFactorAuthentication())
                        <div class="mb-3">
                            <p class="fw-semibold">Scan this QR code with your authenticator app:</p>
                            <div class="text-center">
                                {!! $qrCode !!}
                            </div>
                        </div>
                        <form method="POST" action="{{ route('two-factor.confirm') }}" class="row g-3">
                            @csrf
                            <div class="col-12">
                                <label for="code" class="form-label">Verification Code</label>
                                <input id="code" type="text" name="code" class="form-control @error('code') is-invalid @enderror" placeholder="123 456" required autofocus>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success">Confirm &amp; Enable</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('two-factor.store') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary">Regenerate Secret</button>
                        </form>
                    @else
                        <div class="mb-4">
                            <h6 class="fw-semibold">Recovery Codes</h6>
                            <p class="text-muted">Store these codes in a safe place. Each code can be used once if you lose access to your authenticator app.</p>
                            <div class="bg-light rounded p-3">
                                @php($codes = session('recoveryCodes', $recoveryCodes))
                                <div class="row">
                                    @foreach($codes as $code)
                                        <div class="col-md-6 mb-2">
                                            <code>{{ $code }}</code>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('two-factor.recovery') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary">Regenerate Recovery Codes</button>
                            </form>
                            <form method="POST" action="{{ route('two-factor.disable') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Disable two-factor authentication?')">Disable Two-Factor</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
