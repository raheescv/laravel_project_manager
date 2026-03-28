<x-guest-layout>
    <div class="fade-up">
        <div class="text-center mb-4">
            <div class="avatar-icon mb-3 fade-up fade-up-delay-1">
                <i class="demo-pli-gear"></i>
            </div>
            <h2 class="welcome-title mb-2 fade-up fade-up-delay-2">Welcome Back</h2>
            <p class="welcome-subtitle fade-up fade-up-delay-2">Sign in to your workspace</p>
        </div>

        <div class="card login-card border-0 fade-up fade-up-delay-3">
            <div class="card-body p-4 p-lg-5">
                <x-auth-session-status class="mb-3" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="responsive-form">
                    @csrf

                    {{-- Email Field --}}
                    <div class="mb-4">
                        <label class="form-label d-flex justify-content-between align-items-center">
                            <span>Email Address</span>
                            <span class="badge-required">Required</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="demo-pli-mail"></i>
                            </span>
                            <x-text-input
                                id="email"
                                class="form-control"
                                type="email"
                                name="email"
                                :value="old('email', env('DEFAULT_USERNAME'))"
                                placeholder="name@example.com"
                                required
                                autofocus
                                autocomplete="username"
                            />
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    {{-- Password Field --}}
                    <div class="mb-4">
                        <label class="form-label d-flex justify-content-between align-items-center">
                            <span>Password</span>
                            <span class="badge-required">Required</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="demo-pli-lock-2"></i>
                            </span>
                            <x-text-input
                                id="password"
                                class="form-control"
                                type="password"
                                name="password"
                                value="{{ env('DEFAULT_PASSWORD') }}"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password"
                            />
                            <button class="password-toggle-btn" type="button" id="togglePassword" aria-label="Toggle password visibility">
                                <svg class="eye-icon eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="eye-icon eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    {{-- Remember Me --}}
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                            <label class="form-check-label" for="remember_me">Remember me</label>
                        </div>
                    </div>

                    <div class="auth-divider">
                        <span>secure login</span>
                    </div>

                    {{-- Submit Button --}}
                    <div class="d-grid">
                        <button class="btn btn-primary btn-lg" type="submit">
                            <i class="demo-pli-unlock fs-5 me-2"></i>
                            <span>Sign In</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="text-center mt-4 auth-footer fade-up fade-up-delay-3">
            <p class="mb-0">
                <i class="demo-pli-security-check me-1"></i>
                Secured by {{ config('app.name', 'Laravel') }}
            </p>
        </div>
    </div>
    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const input = document.getElementById('password');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            this.classList.toggle('active', isPassword);
        });
    </script>
</x-guest-layout>
