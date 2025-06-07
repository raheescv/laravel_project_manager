<section class="p-4 bg-light border rounded-3 shadow-sm">
    <header class="mb-4">
        <h2 class="h4 mb-1">
            <i class="fa fa-user me-2"></i>{{ __('Profile Information') }}
        </h2>
        <p class="text-muted small">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-4">
        @csrf
        @method('patch')

        <div class="mb-3">
            <div class="input-group has-validation">
                <span class="input-group-text"><i class="fa fa-user"></i></span>
                <div class="form-floating flex-grow-1">
                    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}"
                        placeholder="{{ __('Name') }}" required autofocus autocomplete="name">
                    <label for="name">{{ __('Name') }}</label>
                </div>
            </div>
            @error('name')
                <div class="invalid-feedback d-block mt-1">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="mb-3">
            <div class="input-group has-validation">
                <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                <div class="form-floating flex-grow-1">
                    <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}"
                        placeholder="{{ __('Email') }}" required autocomplete="username">
                    <label for="email">{{ __('Email') }}</label>
                </div>
            </div>
            @error('email')
                <div class="invalid-feedback d-block mt-1">
                    {{ $message }}
                </div>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                <div class="mt-3 p-3 bg-warning-soft border border-warning rounded-2">
                    <p class="small mb-2">
                        <i class="fa fa-exclamation-triangle me-1"></i>{{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="btn btn-link btn-sm p-0 text-decoration-underline ms-1">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="small text-success fw-medium">
                            <i class="fa fa-check-circle me-1"></i>{{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="d-flex align-items-center gap-3 mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save me-2"></i>{{ __('Save') }}
            </button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-success small mb-0">
                    <i class="fa fa-check me-1"></i>{{ __('Saved.') }}
                </p>
            @endif
        </div>
    </form>
</section>
