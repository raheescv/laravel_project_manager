<x-guest-layout>
    <div class="card shadow-lg">
        <div class="card-body p-4">
            <div class="text-center">
                <h1 class="h3">Account Login</h1>
                <p>Sign In to your account</p>
            </div>
            <form class="mt-4" method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <x-text-input id="email" class="form-control" type="email" name="email" :value="old('email', env('DEFAULT_USERNAME'))" placeholder="Username" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
                <div class="mb-3">
                    <x-text-input id="password" class="form-control" type="password" name="password" value="{{ env('DEFAULT_PASSWORD') }}" required autocomplete="current-password"
                        placeholder="Password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
                <div class="form-check">
                    <label for="remember_me" class="form-check-label">
                        <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                        <span class="form-check-label">{{ __('Remember me') }}</span>
                    </label>
                </div>
                <div class="d-grid mt-5">
                    <button class="btn btn-primary btn-lg" type="submit">Sign In</button>
                </div>
            </form>
            <div class="d-flex justify-content-between gap-md-5 mt-4">
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="btn-link text-decoration-none">Forgot password ?</a>
                @endif
                <a href="#" class="btn-link text-decoration-none">Create a new account</a>
            </div>
        </div>
    </div>
</x-guest-layout>
