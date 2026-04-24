<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1">
    <meta name="description" content="The login page allows a user to gain access to an application by entering their username and password or by authenticating using a social media login.">
    <title>{{ config('app.name', 'Astra') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/nifty.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/demo-purpose/demo-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/premium/icon-sets/line-icons/premium-line-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/demo-purpose/demo-settings.min.css') }}">
    <style>
        /* ===== DARK MODE (Default) ===== */
        [data-theme="dark"] {
            --node-primary: #6366f1;
            --node-secondary: #8b5cf6;
            --node-accent: #a78bfa;
            --node-cyan: #06b6d4;
            --node-pink: #ec4899;
            --bg-body: #0a0a1a;
            --bg-body-rgb: 10, 10, 26;
            --glass-bg: rgba(15, 15, 35, 0.65);
            --glass-border: rgba(255, 255, 255, 0.08);
            --glass-highlight: rgba(255, 255, 255, 0.04);
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --input-bg: rgba(255, 255, 255, 0.06);
            --input-border: rgba(255, 255, 255, 0.1);
            --input-focus-border: var(--node-primary);
            --input-focus-bg: rgba(99, 102, 241, 0.08);
            --input-focus-field-bg: rgba(99, 102, 241, 0.04);
            --input-focus-ring: rgba(99, 102, 241, 0.1);
            --glow-spread: rgba(99, 102, 241, 0.15);
            --card-shadow: rgba(0, 0, 0, 0.3);
            --card-hover-shadow: rgba(0, 0, 0, 0.4);
            --btn-shadow: rgba(99, 102, 241, 0.3);
            --btn-hover-shadow: rgba(99, 102, 241, 0.45);
            --scanline-color: rgba(0, 0, 0, 0.03);
            --orb-opacity: 0.3;
            --particle-line-color: 99, 102, 241;
            --particle-bright-center: rgba(255, 255, 255, 0.7);
            --welcome-gradient: linear-gradient(135deg, #fff 0%, var(--node-accent) 50%, var(--node-cyan) 100%);
            --toggle-bg: rgba(255, 255, 255, 0.08);
            --toggle-hover-bg: rgba(255, 255, 255, 0.14);
            --toggle-icon-color: #fbbf24;
            --divider-color: rgba(255, 255, 255, 0.08);
            --error-color: #f87171;
            --autofill-bg: rgba(15, 15, 35, 0.9);
        }

        /* ===== LIGHT MODE ===== */
        [data-theme="light"] {
            --node-primary: #4f46e5;
            --node-secondary: #6d28d9;
            --node-accent: #7c3aed;
            --node-cyan: #0e7490;
            --node-pink: #be185d;
            --bg-body: #f8f9fc;
            --bg-body-rgb: 248, 249, 252;
            --glass-bg: rgba(255, 255, 255, 0.88);
            --glass-border: rgba(0, 0, 0, 0.06);
            --glass-highlight: rgba(255, 255, 255, 0.9);
            --text-primary: #1e293b;
            --text-secondary: #4b5563;
            --text-muted: #9ca3af;
            --input-bg: #ffffff;
            --input-border: rgba(0, 0, 0, 0.1);
            --input-focus-border: var(--node-primary);
            --input-focus-bg: rgba(79, 70, 229, 0.04);
            --input-focus-field-bg: #ffffff;
            --input-focus-ring: rgba(79, 70, 229, 0.12);
            --glow-spread: rgba(79, 70, 229, 0.04);
            --card-shadow: rgba(0, 0, 0, 0.04);
            --card-hover-shadow: rgba(0, 0, 0, 0.08);
            --btn-shadow: rgba(79, 70, 229, 0.18);
            --btn-hover-shadow: rgba(79, 70, 229, 0.3);
            --scanline-color: transparent;
            --orb-opacity: 0.06;
            --particle-line-color: 180, 190, 210;
            --particle-bright-center: rgba(255, 255, 255, 1);
            --welcome-gradient: linear-gradient(135deg, #1e293b 0%, var(--node-primary) 60%, var(--node-cyan) 100%);
            --toggle-bg: rgba(0, 0, 0, 0.04);
            --toggle-hover-bg: rgba(0, 0, 0, 0.07);
            --toggle-icon-color: #4f46e5;
            --divider-color: rgba(0, 0, 0, 0.06);
            --error-color: #dc2626;
            --autofill-bg: #ffffff;
        }

        /* Light mode specific overrides for smoother feel */
        [data-theme="light"] .login-card {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04),
                        0 8px 24px rgba(0, 0, 0, 0.06) !important;
        }

        [data-theme="light"] .login-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06),
                        0 16px 40px rgba(0, 0, 0, 0.08) !important;
        }

        [data-theme="light"] .login-card::before {
            opacity: 0.7;
        }

        [data-theme="light"] .avatar-icon {
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.2);
        }

        [data-theme="light"] .input-group-text {
            background: #f9fafb !important;
        }

        [data-theme="light"] .form-control {
            background: #ffffff !important;
        }

        [data-theme="light"] .form-control::placeholder {
            color: #b0b8c4 !important;
        }

        [data-theme="light"] .password-toggle-btn {
            background: #ffffff;
            border-color: rgba(0, 0, 0, 0.1);
        }

        [data-theme="light"] .ambient-orb {
            filter: blur(120px);
        }

        [data-theme="light"] .form-check-input {
            background-color: #ffffff !important;
            border-color: rgba(0, 0, 0, 0.15) !important;
        }

        [data-theme="light"] .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #6d28d9) !important;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.18) !important;
        }

        [data-theme="light"] .btn-primary:hover {
            box-shadow: 0 4px 20px rgba(79, 70, 229, 0.3) !important;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-body);
            color: var(--text-primary);
            overflow: hidden;
            transition: background 0.5s ease, color 0.4s ease;
        }

        /* ===== Particle Canvas ===== */
        #particleCanvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        /* ===== Ambient Glow Orbs ===== */
        .ambient-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: var(--orb-opacity);
            z-index: 0;
            pointer-events: none;
            transition: opacity 0.5s ease;
        }

        .ambient-orb--1 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, var(--node-primary), transparent 70%);
            top: -10%;
            left: -5%;
            animation: orbFloat1 20s ease-in-out infinite;
        }

        .ambient-orb--2 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, var(--node-pink), transparent 70%);
            bottom: -10%;
            right: -5%;
            animation: orbFloat2 25s ease-in-out infinite;
        }

        .ambient-orb--3 {
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, var(--node-cyan), transparent 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: orbFloat3 18s ease-in-out infinite;
        }

        @keyframes orbFloat1 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(60px, 40px) scale(1.1); }
            66% { transform: translate(-30px, 70px) scale(0.95); }
        }

        @keyframes orbFloat2 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(-50px, -30px) scale(1.05); }
            66% { transform: translate(40px, -60px) scale(0.9); }
        }

        @keyframes orbFloat3 {
            0%, 100% { transform: translate(-50%, -50%) scale(1); }
            50% { transform: translate(-50%, -55%) scale(1.15); }
        }

        /* ===== Theme Toggle ===== */
        .theme-toggle {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 100;
            width: 48px;
            height: 48px;
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            background: var(--toggle-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .theme-toggle:hover {
            background: var(--toggle-hover-bg);
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        .theme-toggle:active {
            transform: translateY(0) scale(0.95);
        }

        .theme-toggle .icon-sun,
        .theme-toggle .icon-moon {
            position: absolute;
            font-size: 22px;
            transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            color: var(--toggle-icon-color);
        }

        /* Dark mode: show sun icon (to switch to light) */
        [data-theme="dark"] .theme-toggle .icon-sun {
            opacity: 1;
            transform: rotate(0deg) scale(1);
        }
        [data-theme="dark"] .theme-toggle .icon-moon {
            opacity: 0;
            transform: rotate(-90deg) scale(0.5);
        }

        /* Light mode: show moon icon (to switch to dark) */
        [data-theme="light"] .theme-toggle .icon-sun {
            opacity: 0;
            transform: rotate(90deg) scale(0.5);
        }
        [data-theme="light"] .theme-toggle .icon-moon {
            opacity: 1;
            transform: rotate(0deg) scale(1);
        }

        /* ===== Auth Page Container ===== */
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .content__wrap {
            width: 100%;
            max-width: 560px;
            position: relative;
            z-index: 2;
        }

        /* ===== Glassmorphism Login Card ===== */
        .login-card {
            background: var(--glass-bg) !important;
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid var(--glass-border) !important;
            border-radius: 24px !important;
            overflow: hidden;
            position: relative;
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1),
                        box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1),
                        background 0.5s ease,
                        border-color 0.5s ease;
            box-shadow: 0 8px 32px var(--card-shadow),
                        0 0 0 1px var(--glass-highlight) inset !important;
        }

        .login-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 60px var(--card-hover-shadow),
                        0 0 80px var(--glow-spread),
                        0 0 0 1px var(--glass-highlight) inset !important;
        }

        /* Card top accent line */
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--node-cyan), var(--node-primary), var(--node-secondary), var(--node-pink));
            background-size: 200% 100%;
            animation: gradientShift 4s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* ===== Avatar / Logo Icon ===== */
        .avatar-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto;
            background: linear-gradient(135deg, var(--node-primary), var(--node-secondary));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.35);
            animation: iconFloat 6s ease-in-out infinite;
        }

        .avatar-icon::before {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 22px;
            background: linear-gradient(135deg, var(--node-cyan), var(--node-primary), var(--node-pink));
            z-index: -1;
            opacity: 0.5;
            filter: blur(8px);
            animation: iconGlow 3s ease-in-out infinite alternate;
        }

        @keyframes iconFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }

        @keyframes iconGlow {
            0% { opacity: 0.3; transform: scale(1); }
            100% { opacity: 0.6; transform: scale(1.05); }
        }

        .avatar-icon i {
            font-size: 30px;
            color: white;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }

        /* ===== Welcome Title ===== */
        .welcome-title {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.2;
            background: var(--welcome-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            transition: background 0.5s ease;
        }

        .welcome-subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 400;
            letter-spacing: 0.02em;
            transition: color 0.4s ease;
        }

        /* ===== Form Elements ===== */
        .form-label {
            color: var(--text-secondary);
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.5rem;
            transition: color 0.4s ease;
        }

        .form-label .badge-required {
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--node-cyan);
            letter-spacing: 0.05em;
        }

        .input-group {
            position: relative;
        }

        .input-group-text {
            background: var(--input-bg) !important;
            border: 1px solid var(--input-border) !important;
            border-right: none !important;
            color: var(--text-muted) !important;
            border-radius: 14px 0 0 14px !important;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control {
            background: var(--input-bg) !important;
            border: 1px solid var(--input-border) !important;
            border-left: none !important;
            color: var(--text-primary) !important;
            border-radius: 0 14px 14px 0 !important;
            padding: 0.75rem 1rem 0.75rem 0;
            font-size: 0.95rem;
            font-weight: 400;
            transition: all 0.3s ease;
        }

        .form-control::placeholder {
            color: var(--text-muted) !important;
        }

        /* ===== Autofill Styling Fix ===== */
        .form-control:-webkit-autofill,
        .form-control:-webkit-autofill:hover,
        .form-control:-webkit-autofill:focus,
        .form-control:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px var(--autofill-bg, rgba(15, 15, 35, 0.9)) inset !important;
            -webkit-text-fill-color: var(--text-primary) !important;
            caret-color: var(--text-primary) !important;
            transition: background-color 5000s ease-in-out 0s !important;
        }

        .input-group-text:has(+ .form-control:-webkit-autofill) {
            border-color: var(--input-focus-border) !important;
            color: var(--node-primary) !important;
            background: var(--input-focus-bg) !important;
        }

        .input-group:focus-within .input-group-text {
            border-color: var(--input-focus-border) !important;
            color: var(--node-primary) !important;
            background: var(--input-focus-bg) !important;
        }

        .input-group:focus-within .form-control {
            border-color: var(--input-focus-border) !important;
            box-shadow: 0 0 0 3px var(--input-focus-ring) !important;
            background: var(--input-focus-field-bg) !important;
        }

        /* ===== Checkbox ===== */
        .form-check-input {
            background-color: var(--input-bg) !important;
            border-color: var(--input-border) !important;
            width: 1.1rem;
            height: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-check-input:checked {
            background-color: var(--node-primary) !important;
            border-color: var(--node-primary) !important;
        }

        .form-check-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
            cursor: pointer;
            transition: color 0.4s ease;
        }

        /* ===== Password Toggle ===== */
        .password-toggle-btn {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-left: none;
            border-radius: 0 14px 14px 0 !important;
            padding: 0 1rem;
            color: var(--text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .password-toggle-btn:hover {
            color: var(--node-primary);
        }

        .password-toggle-btn .eye-icon {
            transition: all 0.3s ease;
        }

        .password-toggle-btn .eye-closed {
            display: none;
        }

        .password-toggle-btn.active .eye-open {
            display: none;
        }

        .password-toggle-btn.active .eye-closed {
            display: block;
        }

        /* When password toggle exists, remove right border-radius from form-control */
        .input-group .form-control:has(+ .password-toggle-btn),
        .input-group .password-toggle-btn ~ .form-control {
            border-radius: 0 !important;
        }

        /* Fix: password field should not have right radius when toggle follows */
        .input-group > .form-control:not(:last-child) {
            border-radius: 0 !important;
            border-right: none !important;
        }

        .input-group:focus-within .password-toggle-btn {
            border-color: var(--input-focus-border);
            background: var(--input-focus-bg);
            color: var(--node-primary);
        }

        /* ===== Primary Button ===== */
        .btn-primary {
            background: linear-gradient(135deg, var(--node-primary), var(--node-secondary)) !important;
            border: none !important;
            border-radius: 14px !important;
            padding: 0.85rem 1.5rem !important;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.02em;
            color: #fff !important;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1) !important;
            box-shadow: 0 4px 16px var(--btn-shadow) !important;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--node-secondary), var(--node-pink));
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 30px var(--btn-hover-shadow) !important;
            color: #fff !important;
        }

        .btn-primary:hover::before {
            opacity: 1;
        }

        .btn-primary span,
        .btn-primary i {
            position: relative;
            z-index: 1;
        }

        .btn-primary:active {
            transform: translateY(0) !important;
        }

        /* ===== Divider ===== */
        .auth-divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.25rem 0;
        }

        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--divider-color), transparent);
        }

        .auth-divider span {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 500;
            transition: color 0.4s ease;
        }

        /* ===== Footer ===== */
        .auth-footer {
            color: var(--text-muted);
            font-size: 0.8rem;
            letter-spacing: 0.02em;
            transition: color 0.4s ease;
        }

        .auth-footer i {
            color: var(--node-primary);
        }

        /* ===== Error Messages ===== */
        .text-danger {
            color: var(--error-color) !important;
            font-size: 0.8rem;
        }

        /* ===== Entrance Animations ===== */
        .fade-up {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .fade-up-delay-1 { animation-delay: 0.1s; }
        .fade-up-delay-2 { animation-delay: 0.2s; }
        .fade-up-delay-3 { animation-delay: 0.35s; }

        @keyframes fadeUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== Responsive ===== */

        /* Extra large desktops */
        @media (min-width: 1400px) {
            .content__wrap {
                max-width: 620px;
            }

            .login-card .card-body {
                padding: 3rem 3.5rem !important;
            }

            .welcome-title {
                font-size: 2.1rem;
            }

            .avatar-icon {
                width: 80px;
                height: 80px;
                border-radius: 22px;
            }

            .avatar-icon i {
                font-size: 34px;
            }

            .form-control {
                font-size: 1rem;
                padding: 0.9rem 1.15rem 0.9rem 0;
            }

            .input-group-text {
                padding: 0.9rem 1.15rem;
            }

            .btn-primary {
                padding: 1rem 1.75rem !important;
                font-size: 1.05rem;
            }
        }

        /* Large desktops */
        @media (min-width: 1200px) and (max-width: 1399px) {
            .content__wrap {
                max-width: 580px;
            }

            .login-card .card-body {
                padding: 2.5rem 3rem !important;
            }

            .welcome-title {
                font-size: 1.95rem;
            }
        }

        /* Regular desktops / laptops */
        @media (min-width: 992px) and (max-width: 1199px) {
            .content__wrap {
                max-width: 540px;
            }

            .login-card .card-body {
                padding: 2.25rem 2.5rem !important;
            }
        }

        /* Tablets */
        @media (min-width: 768px) and (max-width: 991px) {
            .content__wrap {
                max-width: 480px;
            }

            .login-card .card-body {
                padding: 2rem 2.25rem !important;
            }

            .welcome-title {
                font-size: 1.6rem;
            }
        }

        /* Small tablets / large phones */
        @media (min-width: 576px) and (max-width: 767px) {
            .content__wrap {
                max-width: 440px;
            }

            .login-card .card-body {
                padding: 1.75rem 2rem !important;
            }

            .welcome-title {
                font-size: 1.5rem;
            }

            .avatar-icon {
                width: 64px;
                height: 64px;
                border-radius: 18px;
            }

            .avatar-icon i {
                font-size: 28px;
            }
        }

        /* Mobile phones */
        @media (max-width: 575px) {
            .auth-page {
                padding: 1rem;
            }

            .content__wrap {
                max-width: 100%;
            }

            .login-card {
                border-radius: 20px !important;
            }

            .login-card .card-body {
                padding: 1.5rem 1.25rem !important;
            }

            .welcome-title {
                font-size: 1.35rem;
            }

            .welcome-subtitle {
                font-size: 0.82rem;
            }

            .avatar-icon {
                width: 56px;
                height: 56px;
                border-radius: 16px;
            }

            .avatar-icon i {
                font-size: 24px;
            }

            .form-label {
                font-size: 0.72rem;
            }

            .form-control {
                font-size: 0.88rem;
                padding: 0.6rem 0.75rem 0.6rem 0;
            }

            .input-group-text {
                padding: 0.6rem 0.75rem;
            }

            .btn-primary {
                padding: 0.75rem 1.25rem !important;
                font-size: 0.9rem;
                border-radius: 12px !important;
            }

            .password-toggle-btn {
                padding: 0 0.75rem;
            }

            .theme-toggle {
                top: 1rem;
                right: 1rem;
                width: 40px;
                height: 40px;
                border-radius: 12px;
            }

            .theme-toggle .icon-sun,
            .theme-toggle .icon-moon {
                font-size: 18px;
            }

            .auth-footer {
                font-size: 0.72rem;
            }
        }

        /* Very small phones */
        @media (max-width: 380px) {
            .auth-page {
                padding: 0.75rem;
            }

            .login-card .card-body {
                padding: 1.25rem 1rem !important;
            }

            .welcome-title {
                font-size: 1.2rem;
            }

            .avatar-icon {
                width: 48px;
                height: 48px;
                border-radius: 14px;
            }

            .avatar-icon i {
                font-size: 22px;
            }

            .form-label {
                font-size: 0.68rem;
            }

            .form-control {
                font-size: 0.84rem;
            }
        }

        /* ===== Error Page Shared Styles ===== */
        .error-icon-wrap {
            width: 88px;
            height: 88px;
            margin: 0 auto;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-icon-inner {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            position: relative;
            z-index: 2;
            animation: iconFloat 6s ease-in-out infinite;
        }

        .error-icon-ring {
            position: absolute;
            inset: -6px;
            border-radius: 24px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            animation: ringPulse 3s ease-in-out infinite;
        }

        .error-icon-ring--2 {
            inset: -14px;
            border-radius: 28px;
            border-color: rgba(255, 255, 255, 0.04);
            animation-delay: 1.5s;
        }

        @keyframes ringPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.5; }
        }

        .error-code-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.15em;
            margin-bottom: 0.75rem;
        }

        .error-code-number {
            font-size: 4.5rem;
            font-weight: 900;
            letter-spacing: -0.04em;
            line-height: 1;
            color: var(--text-primary);
            opacity: 0.18;
            transition: color 0.4s ease, opacity 0.4s ease;
        }

        .error-code-number--accent {
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            color: transparent !important;
            opacity: 1;
        }

        .error-title {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            background: var(--welcome-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .error-subtitle {
            color: var(--text-secondary);
            font-size: 0.88rem;
            font-weight: 400;
            max-width: 380px;
            margin: 0 auto;
            line-height: 1.6;
            transition: color 0.4s ease;
        }

        .error-info-box {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            border: 1px solid;
            border-radius: 14px;
            transition: all 0.4s ease;
        }

        .error-info-icon {
            flex-shrink: 0;
            margin-top: 1px;
        }

        .error-info-text {
            color: var(--text-secondary);
            font-size: 0.85rem;
            line-height: 1.5;
            margin: 0;
            transition: color 0.4s ease;
        }

        .btn-outline-glass {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 14px !important;
            padding: 0.85rem 1.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.02em;
            color: var(--text-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-outline-glass:hover {
            background: var(--input-focus-bg);
            border-color: var(--input-focus-border);
            color: var(--node-primary);
            transform: translateY(-1px);
        }

        .error-countdown-text {
            color: var(--text-muted);
            font-size: 0.8rem;
            margin: 0;
            transition: color 0.4s ease;
        }

        .error-countdown-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.75rem;
            font-family: 'JetBrains Mono', monospace;
        }

        [data-theme="light"] .error-code-number {
            color: #64748b;
            opacity: 0.3;
        }

        /* ===== Error Details Box ===== */
        .error-details-box {
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            overflow: hidden;
            background: var(--glass-bg);
        }

        .error-details-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1rem;
            background: rgba(255, 255, 255, 0.03);
            border-bottom: 1px solid var(--glass-border);
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
        }

        [data-theme="light"] .error-details-header {
            background: rgba(0, 0, 0, 0.02);
        }

        .error-details-body {
            padding: 0.25rem 0;
        }

        .error-detail-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 1rem;
            gap: 1rem;
        }

        .error-detail-row + .error-detail-row {
            border-top: 1px solid var(--glass-border);
        }

        .error-detail-label {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            flex-shrink: 0;
        }

        .error-detail-value {
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--text-primary);
            text-align: right;
            word-break: break-all;
        }

        .error-detail-value--highlight {
            color: var(--node-primary);
            font-weight: 600;
        }

        .error-detail-value--mono {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.78rem;
            color: var(--text-secondary);
        }

        /* ===== Scanline overlay ===== */
        .scanline-overlay {
            position: fixed;
            inset: 0;
            background: repeating-linear-gradient(
                0deg,
                transparent,
                transparent 2px,
                var(--scanline-color) 2px,
                var(--scanline-color) 4px
            );
            pointer-events: none;
            z-index: 1;
        }
    </style>
</head>

<body>
    <canvas id="particleCanvas"></canvas>
    <div class="ambient-orb ambient-orb--1"></div>
    <div class="ambient-orb ambient-orb--2"></div>
    <div class="ambient-orb ambient-orb--3"></div>
    <div class="scanline-overlay"></div>

    <!-- Theme Toggle Button -->
    <button class="theme-toggle" id="themeToggle" type="button" aria-label="Toggle dark/light mode">
        <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="5"></circle>
            <line x1="12" y1="1" x2="12" y2="3"></line>
            <line x1="12" y1="21" x2="12" y2="23"></line>
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
            <line x1="1" y1="12" x2="3" y2="12"></line>
            <line x1="21" y1="12" x2="23" y2="12"></line>
            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
        </svg>
        <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
        </svg>
    </button>

    {{-- ======================================================
         Luminous Gateway — global authentication environment.
         Shared background layers + styles are applied here so
         EVERY auth page (login, register, forgot, reset, etc.)
         inherits the same theme-aware visual experience.
         ====================================================== --}}
    @include('auth.partials._luminous-head')

    <div id="root" class="root auth-page">
        <div class="content__wrap">
            {{ $slot }}
        </div>
    </div>

    @include('auth.partials._luminous-scripts')

    <script src="{{ asset('assets/vendors/popperjs/popper.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/bootstrap/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/nifty.js') }}"></script>

    <script>
        (function () {
            /* ===== Theme Toggle Logic ===== */
            const html = document.documentElement;
            const toggleBtn = document.getElementById('themeToggle');
            const savedTheme = localStorage.getItem('login-theme') || 'dark';
            html.setAttribute('data-theme', savedTheme);

            toggleBtn.addEventListener('click', function () {
                const current = html.getAttribute('data-theme');
                const next = current === 'dark' ? 'light' : 'dark';
                html.setAttribute('data-theme', next);
                localStorage.setItem('login-theme', next);
                updateParticleTheme(next);
            });

            /* ===== Particle Network ===== */
            const canvas = document.getElementById('particleCanvas');
            const ctx = canvas.getContext('2d');
            let width, height, particles, mouse, animId;

            const THEMES = {
                dark: {
                    colors: [
                        'rgba(99, 102, 241, ',
                        'rgba(139, 92, 246, ',
                        'rgba(6, 182, 212, ',
                        'rgba(236, 72, 153, ',
                        'rgba(167, 139, 250, ',
                    ],
                    lineColor: '99, 102, 241',
                    mouseLineEnd: '6, 182, 212',
                    mouseGlow: 'rgba(99, 102, 241, 0.08)',
                    brightCenter: 0.7,
                    lineAlphaMultiplier: 0.25,
                    mouseAlphaMultiplier: 0.4,
                },
                light: {
                    colors: [
                        'rgba(180, 190, 210, ',
                        'rgba(160, 170, 200, ',
                        'rgba(140, 160, 190, ',
                        'rgba(170, 180, 205, ',
                        'rgba(150, 165, 195, ',
                    ],
                    lineColor: '180, 190, 210',
                    mouseLineEnd: '140, 160, 190',
                    mouseGlow: 'rgba(79, 70, 229, 0.03)',
                    brightCenter: 1,
                    lineAlphaMultiplier: 0.1,
                    mouseAlphaMultiplier: 0.15,
                },
            };

            let currentThemeConfig = THEMES[savedTheme];

            function updateParticleTheme(theme) {
                currentThemeConfig = THEMES[theme];
                // Reassign particle colors
                for (let p of particles) {
                    const colorIdx = Math.floor(Math.random() * currentThemeConfig.colors.length);
                    p.color = currentThemeConfig.colors[colorIdx];
                }
            }

            const CONFIG = {
                particleCount: 120,
                connectionDistance: 150,
                mouseRadius: 200,
                baseSpeed: 0.3,
                minSize: 1.5,
                maxSize: 3.5,
            };

            mouse = { x: -1000, y: -1000 };

            function resize() {
                width = canvas.width = window.innerWidth;
                height = canvas.height = window.innerHeight;
            }

            function createParticle() {
                const colorIdx = Math.floor(Math.random() * currentThemeConfig.colors.length);
                return {
                    x: Math.random() * width,
                    y: Math.random() * height,
                    vx: (Math.random() - 0.5) * CONFIG.baseSpeed * 2,
                    vy: (Math.random() - 0.5) * CONFIG.baseSpeed * 2,
                    size: CONFIG.minSize + Math.random() * (CONFIG.maxSize - CONFIG.minSize),
                    color: currentThemeConfig.colors[colorIdx],
                    alpha: 0.4 + Math.random() * 0.5,
                    pulseSpeed: 0.005 + Math.random() * 0.01,
                    pulseOffset: Math.random() * Math.PI * 2,
                };
            }

            function init() {
                resize();
                particles = [];
                for (let i = 0; i < CONFIG.particleCount; i++) {
                    particles.push(createParticle());
                }
            }

            function drawParticle(p, time) {
                const pulse = Math.sin(time * p.pulseSpeed + p.pulseOffset) * 0.3 + 0.7;
                const a = p.alpha * pulse;
                const s = p.size * (0.8 + pulse * 0.4);

                // Outer glow
                ctx.beginPath();
                ctx.arc(p.x, p.y, s * 3, 0, Math.PI * 2);
                ctx.fillStyle = p.color + (a * 0.1).toFixed(3) + ')';
                ctx.fill();

                // Core
                ctx.beginPath();
                ctx.arc(p.x, p.y, s, 0, Math.PI * 2);
                ctx.fillStyle = p.color + a.toFixed(3) + ')';
                ctx.fill();

                // Bright center
                ctx.beginPath();
                ctx.arc(p.x, p.y, s * 0.4, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(255, 255, 255, ' + (a * currentThemeConfig.brightCenter).toFixed(3) + ')';
                ctx.fill();
            }

            function drawConnections() {
                const lc = currentThemeConfig.lineColor;
                const am = currentThemeConfig.lineAlphaMultiplier;
                for (let i = 0; i < particles.length; i++) {
                    for (let j = i + 1; j < particles.length; j++) {
                        const dx = particles[i].x - particles[j].x;
                        const dy = particles[i].y - particles[j].y;
                        const dist = Math.sqrt(dx * dx + dy * dy);

                        if (dist < CONFIG.connectionDistance) {
                            const alpha = (1 - dist / CONFIG.connectionDistance) * am;
                            ctx.beginPath();
                            ctx.moveTo(particles[i].x, particles[i].y);
                            ctx.lineTo(particles[j].x, particles[j].y);
                            ctx.strokeStyle = 'rgba(' + lc + ', ' + alpha.toFixed(3) + ')';
                            ctx.lineWidth = 0.6;
                            ctx.stroke();
                        }
                    }
                }
            }

            function drawMouseConnections() {
                if (mouse.x < 0) return;
                const mam = currentThemeConfig.mouseAlphaMultiplier;
                const mle = currentThemeConfig.mouseLineEnd;
                for (let i = 0; i < particles.length; i++) {
                    const dx = particles[i].x - mouse.x;
                    const dy = particles[i].y - mouse.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);

                    if (dist < CONFIG.mouseRadius) {
                        const alpha = (1 - dist / CONFIG.mouseRadius) * mam;
                        ctx.beginPath();
                        ctx.moveTo(particles[i].x, particles[i].y);
                        ctx.lineTo(mouse.x, mouse.y);

                        const grad = ctx.createLinearGradient(
                            particles[i].x, particles[i].y, mouse.x, mouse.y
                        );
                        grad.addColorStop(0, particles[i].color + alpha.toFixed(3) + ')');
                        grad.addColorStop(1, 'rgba(' + mle + ', ' + (alpha * 0.5).toFixed(3) + ')');
                        ctx.strokeStyle = grad;
                        ctx.lineWidth = 0.8;
                        ctx.stroke();
                    }
                }

                // Mouse glow
                const g = ctx.createRadialGradient(mouse.x, mouse.y, 0, mouse.x, mouse.y, 60);
                g.addColorStop(0, currentThemeConfig.mouseGlow);
                g.addColorStop(1, 'rgba(99, 102, 241, 0)');
                ctx.beginPath();
                ctx.arc(mouse.x, mouse.y, 60, 0, Math.PI * 2);
                ctx.fillStyle = g;
                ctx.fill();
            }

            function update() {
                for (let p of particles) {
                    p.x += p.vx;
                    p.y += p.vy;

                    // Mouse repulsion
                    if (mouse.x > 0) {
                        const dx = p.x - mouse.x;
                        const dy = p.y - mouse.y;
                        const dist = Math.sqrt(dx * dx + dy * dy);
                        if (dist < CONFIG.mouseRadius * 0.5 && dist > 0) {
                            const force = (1 - dist / (CONFIG.mouseRadius * 0.5)) * 0.02;
                            p.vx += (dx / dist) * force;
                            p.vy += (dy / dist) * force;
                        }
                    }

                    // Speed damping
                    const speed = Math.sqrt(p.vx * p.vx + p.vy * p.vy);
                    if (speed > CONFIG.baseSpeed * 3) {
                        p.vx *= 0.98;
                        p.vy *= 0.98;
                    }

                    // Wrap around edges
                    if (p.x < -50) p.x = width + 50;
                    if (p.x > width + 50) p.x = -50;
                    if (p.y < -50) p.y = height + 50;
                    if (p.y > height + 50) p.y = -50;
                }
            }

            function animate(time) {
                ctx.clearRect(0, 0, width, height);
                update();
                drawConnections();
                drawMouseConnections();
                for (let p of particles) {
                    drawParticle(p, time);
                }
                animId = requestAnimationFrame(animate);
            }

            window.addEventListener('resize', resize);

            window.addEventListener('mousemove', (e) => {
                mouse.x = e.clientX;
                mouse.y = e.clientY;
            });

            window.addEventListener('mouseleave', () => {
                mouse.x = -1000;
                mouse.y = -1000;
            });

            window.addEventListener('touchmove', (e) => {
                if (e.touches.length > 0) {
                    mouse.x = e.touches[0].clientX;
                    mouse.y = e.touches[0].clientY;
                }
            }, { passive: true });

            window.addEventListener('touchend', () => {
                mouse.x = -1000;
                mouse.y = -1000;
            });

            init();
            animate(0);
        })();
    </script>
</body>

</html>
