<!DOCTYPE html>
<html lang="en" data-bs-theme="light" data-scheme="teal">

<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1">
    <meta name="description" content="The login page allows a user to gain access to an application by entering their username and password or by authenticating using a social media login.">
    <title>{{ config('app.name', 'Astra') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700&family=Ubuntu:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/nifty.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/demo-purpose/demo-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/premium/icon-sets/line-icons/premium-line-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/demo-purpose/demo-settings.min.css') }}">
    <style>
        :root {
            --futuristic-primary: #3470e4;
            --futuristic-secondary: #2c56b3;
            --futuristic-accent: #5a8bdb;
            --futuristic-light-1: #f8f9fc;
            --futuristic-light-2: #eef1f8;
            --futuristic-grid-color: rgba(44, 86, 179, 0.08);
            --futuristic-glow-color: rgba(52, 112, 228, 0.2);
        }

        /* Base styling for the auth page */
        .auth-page {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--futuristic-light-1) 0%, var(--futuristic-light-2) 100%);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 0;
        }

        /* Grid Background */
        .auth-page::before {
            content: '';
            position: absolute;
            top: -100%;
            left: -100%;
            width: 300%;
            height: 300%;
            background-image:
                linear-gradient(var(--futuristic-grid-color) 1px, transparent 1px),
                linear-gradient(90deg, var(--futuristic-grid-color) 1px, transparent 1px);
            background-size: 30px 30px;
            transform: perspective(500px) rotateX(60deg);
            animation: gridMove 60s linear infinite;
            opacity: 0.7;
            z-index: -2;
        }

        /* Circle Elements */
        .auth-page::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 30% 20%, var(--futuristic-glow-color) 0%, transparent 25%),
                radial-gradient(circle at 70% 60%, rgba(44, 86, 179, 0.15) 0%, transparent 30%),
                radial-gradient(circle at 90% 10%, rgba(90, 139, 219, 0.1) 0%, transparent 20%);
            z-index: -1;
        }

        /* Floating Elements */
        .floating-element {
            position: absolute;
            border-radius: 50%;
            opacity: 0.6;
            filter: blur(10px);
            z-index: -1;
        }

        .floating-element:nth-child(1) {
            width: 100px;
            height: 100px;
            background-color: var(--futuristic-primary);
            top: 15%;
            left: 10%;
            opacity: 0.4;
            animation: float 15s ease-in-out infinite;
        }

        .floating-element:nth-child(2) {
            width: 150px;
            height: 150px;
            background-color: var(--futuristic-secondary);
            top: 70%;
            right: 10%;
            opacity: 0.3;
            animation: float 18s ease-in-out infinite reverse;
        }

        .floating-element:nth-child(3) {
            width: 80px;
            height: 80px;
            background-color: var(--futuristic-accent);
            bottom: 10%;
            left: 20%;
            opacity: 0.35;
            animation: float 12s ease-in-out infinite 1s;
        }

        /* Login Card */
        .login-card {
            background-color: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            border-radius: 8px !important;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08),
                0 1px 6px rgba(0, 0, 0, 0.03),
                0 0 0 1px rgba(255, 255, 255, 0.1) inset !important;
            overflow: hidden;
            position: relative;
            max-width: 550px;
            width: 100%;
            margin: 0 auto;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--futuristic-primary);
        }

        .login-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12),
                0 4px 12px rgba(0, 0, 0, 0.06),
                0 0 0 1px rgba(255, 255, 255, 0.2) inset !important;
        }

        /* Form Elements */
        .form-control {
            border-color: rgba(44, 86, 179, 0.15);
            background-color: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--futuristic-primary);
            box-shadow: 0 0 0 0.2rem var(--futuristic-glow-color);
        }

        .btn-primary {
            background: var(--futuristic-primary);
            border: none;
            box-shadow: 0 3px 6px rgba(52, 112, 228, 0.2);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: var(--futuristic-secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(44, 86, 179, 0.3);
        }

        .input-group-text {
            border-color: rgba(44, 86, 179, 0.15);
            background-color: rgba(255, 255, 255, 0.9);
            color: var(--futuristic-secondary);
        }

        /* Responsive Form */
        .responsive-form {
            width: 100%;
        }

        /* Animations */
        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        @keyframes gridMove {
            0% {
                transform: perspective(500px) rotateX(60deg) translateY(0);
            }

            100% {
                transform: perspective(500px) rotateX(60deg) translateY(30px);
            }
        }

        /* Avatar Icon */
        .avatar-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto;
            background: var(--futuristic-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 12px rgba(52, 112, 228, 0.2);
            position: relative;
            overflow: hidden;
        }

        .avatar-icon i {
            font-size: 32px;
            color: white;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
            animation: pulse 3s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        /* Content Wrapper */
        .content__wrap {
            width: 100%;
            padding: 0 15px;
            max-width: 1200px;
        }

        /* Welcome Title */
        .welcome-title {
            font-size: 2rem;
            line-height: 1.2;
        }

        /* Responsive Adjustments */
        @media (min-width: 1200px) {
            .content__wrap {
                width: 80%;
                max-width: 1140px;
            }

            .login-card {
                max-width: 550px;
            }
        }

        @media (min-width: 992px) and (max-width: 1199px) {
            .content__wrap {
                width: 85%;
                max-width: 960px;
            }

            .login-card {
                max-width: 500px;
            }
        }

        @media (min-width: 768px) and (max-width: 991px) {
            .content__wrap {
                width: 90%;
                max-width: 720px;
            }

            .login-card {
                max-width: 450px;
            }

            .card-body {
                padding: 2rem !important;
            }

            .avatar-icon {
                width: 60px;
                height: 60px;
            }

            .avatar-icon i {
                font-size: 28px;
            }
        }

        @media (max-width: 767px) {
            .content__wrap {
                width: 100%;
                padding: 0 20px;
            }

            .login-card {
                max-width: 100%;
            }

            .card-body {
                padding: 1.5rem !important;
            }

            h2.fw-bold {
                font-size: 1.5rem;
            }

            .avatar-icon {
                width: 50px;
                height: 50px;
            }

            .avatar-icon i {
                font-size: 24px;
            }

            .input-group-lg>.form-control {
                padding: 0.5rem 0.75rem;
                font-size: 1rem;
            }

            .btn-lg {
                padding: 0.5rem 1rem;
                font-size: 1rem;
            }
        }

        /* Extra Small Devices */
        @media (max-width: 575px) {
            .card-body {
                padding: 1.25rem !important;
            }
        }
    </style>
</head>

<body>
    <div id="root" class="root auth-page">
        <!-- Floating elements for dynamic background -->
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="content__boxed w-100 min-vh-100 d-flex flex-column align-items-center justify-content-center">
            <div class="content__wrap px-3">
                {{ $slot }}
            </div>
        </div>
    </div>
    <script src="{{ asset('assets/vendors/popperjs/popper.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/bootstrap/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/nifty.js') }}"></script>
</body>

</html>
