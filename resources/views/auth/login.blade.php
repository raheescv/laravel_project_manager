<x-guest-layout>
    <div>
        <div class="text-center mb-4">
            <div class="avatar-icon mb-4">
                <i class="demo-pli-gear text-primary"></i>
            </div>
            <h3 class="fw-bold text-dark mb-1">Welcome Back! 👋</h3>
            <p class="text-muted">Access your account</p>
        </div>
        <div class="card login-  border-0">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label d-flex justify-content-between">
                            <span class="fw-medium">Email Address</span>
                            <span class="text-primary fw-medium opacity-75">
                                <i class="demo-pli-information me-1 fs-sm"></i>
                                Required
                            </span>
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text text-muted border-end-0">
                                <i class="demo-pli-mail"></i>
                            </span>
                            <x-text-input id="email" class="form-control border-start-0 ps-0" type="email" name="email" :value="old('email', env('DEFAULT_USERNAME'))" placeholder="name@example.com" required autofocus
                                autocomplete="username" />
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2 small text-danger" />
                    </div>

                    <div class="mb-4">
                        <label class="form-label d-flex justify-content-between">
                            <span class="fw-medium">Password</span>
                            <span class="text-primary fw-medium opacity-75">
                                <i class="demo-pli-information me-1 fs-sm"></i>
                                Required
                            </span>
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text text-muted border-end-0">
                                <i class="demo-pli-lock-2"></i>
                            </span>
                            <x-text-input id="password" class="form-control border-start-0 ps-0" type="password" name="password" value="{{ env('DEFAULT_PASSWORD') }}" required
                                autocomplete="current-password" placeholder="Enter your password" />
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2 small text-danger" />
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                            <label class="form-check-label fw-medium" for="remember_me">
                                Remember me
                            </label>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button class="btn btn-primary btn-lg" type="submit">
                            <i class="demo-pli-unlock fs-5 me-2"></i>
                            <span class="fw-medium">Sign In to Continue</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="text-center mt-4">
            <p class="text-muted mb-0">
                <i class="demo-pli-security-check me-1"></i>
                Secured by {{ config('app.name', 'Laravel') }}
            </p>
        </div>
    </div>
</x-guest-layout>
