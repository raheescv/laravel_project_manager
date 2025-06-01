<section class="p-4 bg-light border rounded-3 shadow-sm">
    <header class="mb-4">
        <h2 class="h4 mb-1">
            <i class="fa fa-key me-2"></i>{{ __('Update Password') }}
        </h2>
        <p class="text-muted small">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-4">
        @csrf
        @method('put')

        <div class="mb-3">
            <div class="input-group has-validation">
                <span class="input-group-text"><i class="fa fa-lock"></i></span>
                <div class="form-floating flex-grow-1">
                    <input id="update_password_current_password" name="current_password" type="password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                        placeholder="{{ __('Current Password') }}" autocomplete="current-password">
                    <label for="update_password_current_password">{{ __('Current Password') }}</label>
                </div>
            </div>
            @error('current_password', 'updatePassword')
                <div class="invalid-feedback d-block">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="mb-3">
            <div class="input-group has-validation">
                <span class="input-group-text"><i class="fa fa-lock"></i></span>
                <div class="form-floating flex-grow-1">
                    <input id="update_password_password" name="password" type="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                        placeholder="{{ __('New Password') }}" autocomplete="new-password">
                    <label for="update_password_password">{{ __('New Password') }}</label>
                </div>
            </div>
            @error('password', 'updatePassword')
                <div class="invalid-feedback d-block">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="mb-3">
            <div class="input-group has-validation">
                <span class="input-group-text"><i class="fa fa-lock"></i></span>
                <div class="form-floating flex-grow-1">
                    <input id="update_password_password_confirmation" name="password_confirmation" type="password"
                        class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror" placeholder="{{ __('Confirm Password') }}" autocomplete="new-password">
                    <label for="update_password_password_confirmation">{{ __('Confirm Password') }}</label>
                </div>
            </div>
            @error('password_confirmation', 'updatePassword')
                <div class="invalid-feedback d-block">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="d-flex align-items-center gap-3 mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save me-2"></i>{{ __('Save') }}
            </button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-success small mb-0">
                    <i class="fa fa-check me-1"></i>{{ __('Saved.') }}
                </p>
            @endif
        </div>
    </form>
</section>
