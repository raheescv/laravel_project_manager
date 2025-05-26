<!DOCTYPE html>
<html lang="en" data-bs-theme="light" data-scheme="navy">

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
            --primary-color: #5156be;
            --secondary-color: #0dcaf0;
            --primary-rgb: 81, 86, 190;
            --secondary-rgb: 13, 202, 240;
        }

        body {
            font-family: 'Poppins', sans-serif;
        }

        .auth-page {
            background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.15) 0%, rgba(var(--secondary-rgb), 0.15) 100%);
            min-height: 100vh;
            position: relative;
        }

        .auth-page::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 15% 50%, rgba(var(--primary-rgb), 0.1) 0%, transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(var(--secondary-rgb), 0.1) 0%, transparent 25%);
        }

        .avatar-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .avatar-icon::after {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            border-radius: 50%;
            background: linear-gradient(45deg, rgba(var(--primary-rgb), 0.3), rgba(var(--secondary-rgb), 0.3));
            z-index: -1;
            filter: blur(10px);
        }

        .avatar-icon i {
            font-size: 2.5rem;
            color: white;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-radius: 1rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .input-group {
            transition: all 0.2s ease;
        }

        .input-group:focus-within {
            transform: translateX(5px);
        }

        .input-group-text {
            background-color: transparent;
            border-color: #e5e9f2;
            color: var(--primary-color);
        }

        .form-control {
            border-color: #e5e9f2;
            background-color: rgba(255, 255, 255, 0.5);
            font-size: 0.95rem;
        }

        .form-control:focus {
            background-color: rgba(255, 255, 255, 0.9);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(var(--primary-rgb), 0.15);
        }

        .form-label {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .btn {
            border-radius: 0.75rem;
            padding: 0.8rem 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            box-shadow: 0 4px 15px rgba(var(--primary-rgb), 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(var(--primary-rgb), 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(var(--primary-rgb), 0.15);
        }

        .text-primary {
            color: var(--primary-color) !important;
        }

        @media (max-width: 576px) {
            .avatar-icon {
                width: 60px;
                height: 60px;
            }

            .avatar-icon i {
                font-size: 2rem;
            }

            .card-body {
                padding: 1.5rem !important;
            }
        }

        /* Animation for the login card */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .content__wrap {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>

<body>
    <div id="root" class="root auth-page">
        <section id="content" class="content">
            <div class="content__boxed w-100 min-vh-100 d-flex flex-column align-items-center justify-content-center">
                <div class="content__wrap">
                    {{ $slot }}
                </div>
            </div>
        </section>
    </div>
    <script src="{{ asset('assets/vendors/popperjs/popper.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/bootstrap/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/nifty.js') }}"></script>
</body>

</html>
