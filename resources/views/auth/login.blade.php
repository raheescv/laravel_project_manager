<x-guest-layout>
    <div>
        <div class="text-center mb-4">
            <div class="avatar-icon mb-4">
                <i class="demo-pli-gear"></i>
            </div>
            <h2 class="fw-bold mb-1 welcome-title"
                style="background: linear-gradient(90deg, var(--futuristic-primary), var(--futuristic-secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Welcome Back! 👋
            </h2>
            <p class="text-secondary">Access your account</p>
        </div>
        <div class="card login-card border-0">
            <div class="card-body p-4 p-lg-5">
                <form method="POST" action="{{ route('login') }}" class="responsive-form">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label d-flex justify-content-between">
                            <span class="fw-medium">Email Address</span>
                            <span class="fw-medium" style="color: var(--futuristic-secondary)">
                                <i class="demo-pli-information me-1 fs-sm"></i>
                                Required
                            </span>
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text border-end-0">
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
                            <span class="fw-medium" style="color: var(--futuristic-secondary)">
                                <i class="demo-pli-information me-1 fs-sm"></i>
                                Required
                            </span>
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text border-end-0">
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
            <p class="mb-0" style="color: var(--futuristic-secondary)">
                <i class="demo-pli-security-check me-1"></i>
                Secured by {{ config('app.name', 'Laravel') }}
            </p>
        </div>
    </div>
</x-guest-layout>
