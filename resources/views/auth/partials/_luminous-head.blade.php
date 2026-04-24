{{-- ====================================================================
     Luminous Gateway — shared head markup for auth pages.
     Includes: early theme-scheme sync, CSS variable mapping to --bs-primary,
     the full visual stylesheet, and the ambient background layers.
     ==================================================================== --}}

{{-- Apply the user's saved color scheme (from global settings) as early as
     possible so CSS variables like --bs-primary resolve to the correct
     brand color before the page paints. --}}
<script>
    (function () {
        try {
            var stored = localStorage.getItem('pm_theme_settings');
            if (stored) {
                var s = JSON.parse(stored);
                if (s && s.color && s.color.scheme) {
                    document.documentElement.setAttribute('data-scheme', s.color.scheme);
                    document.documentElement.classList.add('scheme-' + s.color.scheme);
                }
            }
        } catch (e) {}

        // Mirror data-theme (dark/light) onto data-bs-theme so Bootstrap's
        // scheme-aware dark mode rules (e.g. [data-bs-theme=dark][data-scheme=ocean])
        // resolve correctly. Runs now and on future toggles.
        function syncBsTheme() {
            var t = document.documentElement.getAttribute('data-theme') || 'dark';
            document.documentElement.setAttribute('data-bs-theme', t);
            document.documentElement.classList.toggle('dark', t === 'dark');
        }
        syncBsTheme();
        new MutationObserver(syncBsTheme).observe(document.documentElement, {
            attributes: true, attributeFilter: ['data-theme']
        });
    })();
</script>

<style>
    /* ==========================================================
       Map auth page colors to the user's chosen theme scheme.
       The theme scheme sets `--bs-primary` and `--bs-primary-rgb`
       on <html data-scheme="..."> — we derive the whole palette
       from it so everything matches the user's branding.
       ========================================================== */
    :root {
        --login-primary: var(--bs-primary, #6366f1);
        --login-primary-rgb: var(--bs-primary-rgb, 99, 102, 241);
        --login-primary-light: color-mix(in srgb, var(--bs-primary, #6366f1), white 28%);
        --login-primary-lighter: color-mix(in srgb, var(--bs-primary, #6366f1), white 50%);
        --login-primary-softer: color-mix(in srgb, var(--bs-primary, #6366f1), white 70%);
        --login-primary-dark: color-mix(in srgb, var(--bs-primary, #6366f1), black 22%);
        --login-primary-deep: color-mix(in srgb, var(--bs-primary, #6366f1), black 40%);
    }

    /* Override guest-layout tokens so every element is primary-anchored */
    [data-theme="dark"] {
        --node-primary: var(--login-primary);
        --node-secondary: var(--login-primary-dark);
        --node-accent: var(--login-primary-light);
        --node-cyan: var(--login-primary-lighter);
        --node-pink: var(--login-primary-dark);
        --btn-shadow: rgba(var(--login-primary-rgb), 0.4);
        --btn-hover-shadow: rgba(var(--login-primary-rgb), 0.55);
        --glow-spread: rgba(var(--login-primary-rgb), 0.18);
        --input-focus-bg: rgba(var(--login-primary-rgb), 0.1);
        --input-focus-field-bg: rgba(var(--login-primary-rgb), 0.05);
        --input-focus-ring: rgba(var(--login-primary-rgb), 0.14);
        --input-focus-border: var(--login-primary);
        --welcome-gradient: linear-gradient(135deg, #fff 0%, var(--login-primary-light) 50%, var(--login-primary-lighter) 100%);
        --toggle-icon-color: var(--login-primary-light);
    }

    [data-theme="light"] {
        --node-primary: var(--login-primary);
        --node-secondary: var(--login-primary-dark);
        --node-accent: var(--login-primary-deep);
        --node-cyan: var(--login-primary-light);
        --node-pink: var(--login-primary-deep);
        --btn-shadow: rgba(var(--login-primary-rgb), 0.22);
        --btn-hover-shadow: rgba(var(--login-primary-rgb), 0.35);
        --glow-spread: rgba(var(--login-primary-rgb), 0.06);
        --input-focus-bg: rgba(var(--login-primary-rgb), 0.06);
        --input-focus-field-bg: #ffffff;
        --input-focus-ring: rgba(var(--login-primary-rgb), 0.14);
        --input-focus-border: var(--login-primary);
        --welcome-gradient: linear-gradient(135deg, #1e293b 0%, var(--login-primary) 60%, var(--login-primary-dark) 100%);
        --toggle-icon-color: var(--login-primary);
    }

    /* ==========================================================
       Global: Hide default particle canvas + orbs (we replace them)
       ========================================================== */
    body {
        overflow-y: auto !important;
        overflow-x: hidden !important;
    }

    #particleCanvas { display: none !important; }
    .ambient-orb { display: none !important; }
    .scanline-overlay { opacity: 0.5; }

    /* ==========================================================
       DAY MODE — warm, soft, eye-friendly background
       Layered radial pools tinted with the user's theme primary
       on a slightly warm off-white base. Fixed-attachment so the
       gradient stays stable when the card scrolls.
       ========================================================== */
    [data-theme="light"] body {
        background-color: #fafbff;
        background-image:
            radial-gradient(900px 700px at -10% -10%, color-mix(in srgb, var(--login-primary) 18%, white), transparent 60%),
            radial-gradient(800px 650px at 110% 0%, color-mix(in srgb, var(--login-primary-light) 22%, white), transparent 60%),
            radial-gradient(900px 800px at 50% 115%, color-mix(in srgb, var(--login-primary-softer, #fde68a) 30%, white), transparent 60%),
            linear-gradient(180deg, #fdfbff 0%, #f5f8ff 50%, #fef7f3 100%);
        background-attachment: fixed;
        background-repeat: no-repeat;
    }

    /* Soft corner vignette adds depth without being heavy */
    [data-theme="light"] body::before {
        content: "";
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 0;
        background:
            radial-gradient(60% 50% at 0% 0%, color-mix(in srgb, var(--login-primary) 10%, transparent), transparent 70%),
            radial-gradient(60% 50% at 100% 0%, color-mix(in srgb, #a78bfa 10%, transparent), transparent 70%),
            radial-gradient(70% 60% at 50% 100%, color-mix(in srgb, var(--login-primary-light) 14%, transparent), transparent 70%);
        mix-blend-mode: multiply;
        opacity: 0.9;
    }

    /* Subtle noise for texture (prevents banding in soft gradients) */
    [data-theme="light"] body::after {
        content: "";
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 0;
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='180' height='180' viewBox='0 0 180 180'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/><feColorMatrix values='0 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 0 0.06 0'/></filter><rect width='100%' height='100%' filter='url(%23n)'/></svg>");
        opacity: 0.6;
        mix-blend-mode: multiply;
    }

    #root.auth-page {
        padding: 2rem 1.5rem;
    }

    #root.auth-page .content__wrap {
        max-width: 480px !important;
        position: relative;
        z-index: 3;
    }

    /* Our gateway canvas layer */
    .gateway-canvas {
        position: fixed;
        inset: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
        pointer-events: none;
    }

    /* ==========================================================
       Aurora blobs (fluid color gradient background)
       ========================================================== */
    .aurora {
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 0;
        overflow: hidden;
        mix-blend-mode: screen;
    }

    [data-theme="light"] .aurora {
        /* "normal" blend keeps pastel hues clean instead of muddy */
        mix-blend-mode: normal;
        opacity: 0.85;
    }

    [data-theme="light"] .aurora-blob {
        filter: blur(110px);
    }

    .aurora-blob {
        position: absolute;
        border-radius: 50%;
        filter: blur(90px);
        will-change: transform;
    }

    .aurora-blob--1 {
        width: 55vw;
        height: 55vw;
        background: radial-gradient(circle, rgba(var(--login-primary-rgb), 0.55), transparent 65%);
        top: -18vw;
        left: -12vw;
        animation: auroraFlow1 22s ease-in-out infinite;
    }

    .aurora-blob--2 {
        width: 50vw;
        height: 50vw;
        background: radial-gradient(circle, color-mix(in srgb, var(--login-primary) 45%, transparent), transparent 65%);
        bottom: -18vw;
        right: -10vw;
        animation: auroraFlow2 26s ease-in-out infinite;
    }

    .aurora-blob--3 {
        width: 40vw;
        height: 40vw;
        background: radial-gradient(circle, color-mix(in srgb, var(--login-primary-lighter) 55%, transparent), transparent 65%);
        top: 40%;
        left: 50%;
        transform: translate(-50%, -50%);
        animation: auroraFlow3 20s ease-in-out infinite;
    }

    .aurora-blob--4 {
        width: 35vw;
        height: 35vw;
        background: radial-gradient(circle, color-mix(in srgb, var(--login-primary-light) 45%, transparent), transparent 65%);
        top: 10%;
        right: 10%;
        animation: auroraFlow4 28s ease-in-out infinite;
    }

    [data-theme="light"] .aurora-blob--1 { background: radial-gradient(circle, rgba(var(--login-primary-rgb), 0.38), transparent 62%); }
    [data-theme="light"] .aurora-blob--2 { background: radial-gradient(circle, color-mix(in srgb, var(--login-primary) 32%, transparent), transparent 62%); }
    [data-theme="light"] .aurora-blob--3 { background: radial-gradient(circle, color-mix(in srgb, var(--login-primary-light) 36%, transparent), transparent 62%); }
    [data-theme="light"] .aurora-blob--4 { background: radial-gradient(circle, color-mix(in srgb, var(--login-primary-softer, #fda4af) 42%, transparent), transparent 62%); }

    @keyframes auroraFlow1 {
        0%, 100% { transform: translate(0, 0) scale(1) rotate(0deg); }
        33% { transform: translate(15vw, 10vw) scale(1.15) rotate(60deg); }
        66% { transform: translate(-8vw, 18vw) scale(0.95) rotate(-40deg); }
    }
    @keyframes auroraFlow2 {
        0%, 100% { transform: translate(0, 0) scale(1) rotate(0deg); }
        33% { transform: translate(-12vw, -10vw) scale(1.1) rotate(-50deg); }
        66% { transform: translate(8vw, -15vw) scale(0.9) rotate(30deg); }
    }
    @keyframes auroraFlow3 {
        0%, 100% { transform: translate(-50%, -50%) scale(1); }
        50% { transform: translate(-55%, -45%) scale(1.2); }
    }
    @keyframes auroraFlow4 {
        0%, 100% { transform: translate(0, 0) scale(1); }
        50% { transform: translate(-15vw, 12vw) scale(1.1); }
    }

    /* ==========================================================
       Grid mask overlay (fades from center)
       ========================================================== */
    .grid-mask {
        position: fixed;
        inset: 0;
        z-index: 1;
        pointer-events: none;
        background-image:
            linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
        background-size: 60px 60px;
        mask-image: radial-gradient(ellipse at center, black 30%, transparent 75%);
        -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 75%);
    }

    [data-theme="light"] .grid-mask {
        background-image:
            linear-gradient(to right, rgba(var(--login-primary-rgb), 0.055) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(var(--login-primary-rgb), 0.055) 1px, transparent 1px);
        opacity: 0.75;
    }

    /* ==========================================================
       Floating geometric shapes
       ========================================================== */
    .float-shapes {
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 2;
    }

    .shape {
        position: absolute;
        opacity: 0.7;
        will-change: transform;
    }

    [data-theme="light"] .shape {
        opacity: 0.35;
    }

    .shape-diamond {
        top: 16%;
        left: 12%;
        width: 42px;
        height: 42px;
        background: linear-gradient(135deg, var(--login-primary), var(--login-primary-light));
        transform: rotate(45deg);
        border-radius: 10px;
        animation: shapeFloat 12s ease-in-out infinite;
        box-shadow: 0 10px 40px rgba(var(--login-primary-rgb), 0.5);
    }

    .shape-square {
        top: 22%;
        right: 10%;
        width: 54px;
        height: 54px;
        border: 2px solid rgba(var(--login-primary-rgb), 0.55);
        border-radius: 14px;
        animation: shapeFloat 15s ease-in-out infinite reverse;
        backdrop-filter: blur(2px);
    }

    [data-theme="light"] .shape-square {
        border-color: rgba(var(--login-primary-rgb), 0.35);
    }

    .shape-circle {
        bottom: 18%;
        left: 8%;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--login-primary), var(--login-primary-dark));
        animation: shapeFloat 14s ease-in-out infinite;
        animation-delay: -3s;
        box-shadow: 0 10px 30px rgba(var(--login-primary-rgb), 0.45);
    }

    .shape-triangle {
        bottom: 20%;
        right: 14%;
        width: 0;
        height: 0;
        border-left: 24px solid transparent;
        border-right: 24px solid transparent;
        border-bottom: 42px solid rgba(var(--login-primary-rgb), 0.55);
        animation: shapeFloat 18s ease-in-out infinite;
        animation-delay: -5s;
        filter: drop-shadow(0 10px 20px rgba(var(--login-primary-rgb), 0.45));
    }

    [data-theme="light"] .shape-triangle {
        border-bottom-color: rgba(var(--login-primary-rgb), 0.38);
    }

    .shape-hex {
        top: 55%;
        left: 4%;
        width: 48px;
        height: 48px;
        background: transparent;
        border: 2px solid rgba(var(--login-primary-rgb), 0.5);
        clip-path: polygon(50% 0, 100% 25%, 100% 75%, 50% 100%, 0 75%, 0 25%);
        animation: shapeFloat 16s ease-in-out infinite reverse;
        animation-delay: -7s;
    }

    @keyframes shapeFloat {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-30px) rotate(180deg); }
    }

    .shape-diamond { animation-name: shapeFloatDiamond; }
    @keyframes shapeFloatDiamond {
        0%, 100% { transform: translateY(0) rotate(45deg); }
        50% { transform: translateY(-30px) rotate(225deg); }
    }

    @media (max-width: 768px) {
        .shape { transform: scale(0.75); }
    }
    @media (max-width: 540px) {
        .shape { display: none; }
    }

    /* ==========================================================
       Cursor glow follower
       ========================================================== */
    .cursor-glow {
        position: fixed;
        top: 0;
        left: 0;
        width: 420px;
        height: 420px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(var(--login-primary-rgb), 0.22), transparent 60%);
        pointer-events: none;
        z-index: 2;
        transform: translate(-50%, -50%);
        mix-blend-mode: screen;
        transition: opacity 0.4s ease;
        opacity: 0;
    }

    [data-theme="light"] .cursor-glow {
        background: radial-gradient(circle, rgba(var(--login-primary-rgb), 0.14), transparent 60%);
        mix-blend-mode: multiply;
    }

    .cursor-glow.is-active { opacity: 1; }

    @media (hover: none) {
        .cursor-glow { display: none; }
    }

    /* ==========================================================
       Stage layout
       ========================================================== */
    .gateway-stage {
        position: relative;
        z-index: 3;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    /* ==========================================================
       Lock badge
       ========================================================== */
    .lock-badge {
        position: relative;
        width: 90px;
        height: 90px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .lock-badge-rings {
        position: absolute;
        inset: 0;
    }

    .lock-ring {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        border: 1px solid rgba(var(--login-primary-rgb), 0.3);
        animation: ringScale 3s cubic-bezier(0.16, 1, 0.3, 1) infinite;
    }

    .lock-ring--1 { animation-delay: 0s; }
    .lock-ring--2 { animation-delay: 1s; }
    .lock-ring--3 { animation-delay: 2s; }

    [data-theme="light"] .lock-ring {
        border-color: rgba(var(--login-primary-rgb), 0.35);
    }

    @keyframes ringScale {
        0% { transform: scale(0.6); opacity: 0.9; }
        100% { transform: scale(1.8); opacity: 0; }
    }

    .lock-badge-core {
        position: relative;
        width: 68px;
        height: 68px;
        border-radius: 22px;
        background: linear-gradient(135deg, var(--login-primary) 0%, var(--login-primary-light) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        box-shadow:
            0 12px 32px rgba(var(--login-primary-rgb), 0.5),
            inset 0 1px 0 rgba(255, 255, 255, 0.25);
        animation: lockFloat 5s ease-in-out infinite;
    }

    .lock-badge-core::after {
        content: '';
        position: absolute;
        inset: -3px;
        border-radius: 25px;
        background: conic-gradient(from 0deg,
            var(--login-primary-lighter),
            var(--login-primary),
            var(--login-primary-dark),
            var(--login-primary),
            var(--login-primary-lighter));
        z-index: -1;
        opacity: 0.8;
        filter: blur(10px);
        animation: auraRotate 6s linear infinite;
    }

    [data-theme="light"] .lock-badge-core {
        box-shadow:
            0 8px 24px rgba(var(--login-primary-rgb), 0.4),
            inset 0 1px 0 rgba(255, 255, 255, 0.3);
    }

    @keyframes lockFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-6px); }
    }

    @keyframes auraRotate {
        to { transform: rotate(360deg); }
    }

    /* Lock SVG draw-in animation */
    .lock-svg path, .lock-svg rect, .lock-svg circle {
        stroke-dasharray: 120;
        stroke-dashoffset: 120;
        animation: drawIn 1.2s cubic-bezier(0.16, 1, 0.3, 1) 0.3s forwards;
    }
    .lock-svg .lock-shackle { animation-delay: 0.3s; }
    .lock-svg .lock-body { animation-delay: 0.55s; }
    .lock-svg .lock-dot { animation-delay: 0.9s; stroke-dasharray: 20; stroke-dashoffset: 20; }
    .lock-svg .lock-dot-tail { animation-delay: 1s; stroke-dasharray: 10; stroke-dashoffset: 10; }

    @keyframes drawIn { to { stroke-dashoffset: 0; } }

    .lock-status {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: var(--bg-body);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
    }

    .lock-status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25), 0 0 10px rgba(16, 185, 129, 0.9);
        animation: pulseGreen 2s ease-in-out infinite;
    }

    @keyframes pulseGreen {
        0%, 100% { box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25), 0 0 10px rgba(16, 185, 129, 0.9); }
        50% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0.08), 0 0 16px rgba(16, 185, 129, 1); }
    }

    /* ==========================================================
       Title (gradient + shine sweep)
       ========================================================== */
    .gateway-title {
        position: relative;
        margin: 0;
        font-size: 2.2rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        line-height: 1.15;
        text-align: center;
        overflow: hidden;
    }

    .gateway-title-text {
        background: linear-gradient(120deg, var(--text-primary) 20%, var(--node-primary) 40%, var(--node-cyan) 60%, var(--text-primary) 80%);
        background-size: 200% 100%;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: titleGradient 6s linear infinite;
    }

    [data-theme="light"] .gateway-title-text {
        background: linear-gradient(120deg, #1e293b 20%, var(--node-primary) 40%, var(--node-cyan) 60%, #1e293b 80%);
        background-size: 200% 100%;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    @keyframes titleGradient { to { background-position: -200% 0; } }

    .gateway-subtitle {
        margin: 0.5rem 0 1.75rem;
        color: var(--text-secondary);
        font-size: 0.94rem;
        text-align: center;
    }

    .gateway-subtitle strong {
        color: var(--text-primary);
        font-weight: 600;
    }

    /* ==========================================================
       HOLO CARD — glassy with animated chromatic border
       ========================================================== */
    .holo-card {
        position: relative;
        width: 100%;
        border-radius: 24px;
        background: var(--glass-bg);
        backdrop-filter: blur(28px) saturate(180%);
        -webkit-backdrop-filter: blur(28px) saturate(180%);
        box-shadow:
            0 24px 64px rgba(0, 0, 0, 0.35),
            inset 0 1px 0 rgba(255, 255, 255, 0.06);
        overflow: hidden;
        transition: transform 0.15s cubic-bezier(0.16, 1, 0.3, 1),
                    box-shadow 0.35s ease,
                    background 0.4s ease;
        transform-style: preserve-3d;
        will-change: transform;
    }

    [data-theme="light"] .holo-card {
        /* Slightly more opaque glass so the card lifts cleanly off
           the richer pastel background without looking washed-out */
        background: rgba(255, 255, 255, 0.82);
        box-shadow:
            0 2px 8px rgba(15, 23, 42, 0.04),
            0 12px 32px rgba(var(--login-primary-rgb), 0.1),
            0 36px 80px rgba(var(--login-primary-rgb), 0.14),
            inset 0 1px 0 rgba(255, 255, 255, 0.95);
    }

    /* Primary-anchored rotating border */
    .holo-border {
        position: absolute;
        inset: 0;
        border-radius: inherit;
        padding: 1.5px;
        background: conic-gradient(
            from var(--border-angle, 0deg),
            rgba(var(--login-primary-rgb), 0.85),
            rgba(var(--login-primary-rgb), 0.25),
            rgba(var(--login-primary-rgb), 0.85),
            rgba(var(--login-primary-rgb), 0.35),
            rgba(var(--login-primary-rgb), 0.9),
            rgba(var(--login-primary-rgb), 0.25),
            rgba(var(--login-primary-rgb), 0.85)
        );
        -webkit-mask:
            linear-gradient(#000 0 0) content-box,
            linear-gradient(#000 0 0);
        mask:
            linear-gradient(#000 0 0) content-box,
            linear-gradient(#000 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        pointer-events: none;
        animation: borderSpin 6s linear infinite;
    }

    [data-theme="light"] .holo-border { opacity: 0.55; }

    @property --border-angle {
        syntax: '<angle>';
        inherits: false;
        initial-value: 0deg;
    }

    @keyframes borderSpin { to { --border-angle: 360deg; } }

    /* Holographic glare (follows cursor inside card) */
    .holo-glare {
        position: absolute;
        inset: 0;
        border-radius: inherit;
        pointer-events: none;
        background: radial-gradient(
            400px circle at var(--mx, 50%) var(--my, 50%),
            rgba(255, 255, 255, 0.12),
            transparent 50%
        );
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .holo-card:hover .holo-glare { opacity: 1; }

    [data-theme="light"] .holo-glare {
        background: radial-gradient(
            400px circle at var(--mx, 50%) var(--my, 50%),
            rgba(var(--login-primary-rgb), 0.1),
            transparent 50%
        );
    }

    .holo-body {
        position: relative;
        padding: 2rem 2rem 1.75rem;
        z-index: 1;
    }

    @media (min-width: 640px) {
        .holo-body { padding: 2.5rem 2.75rem 2rem; }
    }

    /* ==========================================================
       Luxury form fields
       ========================================================== */
    .lux-field { margin-bottom: 1.25rem; }

    .lux-label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 600;
        color: var(--text-secondary);
    }

    .lux-required {
        font-size: 0.65rem;
        font-weight: 700;
        color: var(--node-cyan);
        letter-spacing: 0.06em;
    }

    .lux-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
        background: var(--input-bg);
        border: 1px solid var(--input-border);
        border-radius: 14px;
        transition: border-color 0.3s ease, background 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
    }

    .lux-input-wrap:hover {
        border-color: rgba(var(--login-primary-rgb), 0.45);
    }

    .lux-input-wrap:focus-within {
        border-color: var(--input-focus-border);
        background: var(--input-focus-field-bg);
        box-shadow: 0 0 0 4px var(--input-focus-ring);
    }

    .lux-input-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 0.25rem 0 1rem;
        color: var(--text-muted);
        transition: color 0.3s ease, transform 0.3s ease;
    }

    .lux-input-wrap:focus-within .lux-input-icon {
        color: var(--node-primary);
        transform: scale(1.1);
    }

    .lux-input {
        flex: 1;
        min-width: 0;
        padding: 0.9rem 0.85rem;
        background: transparent;
        border: none;
        outline: none;
        color: var(--text-primary);
        font-size: 0.95rem;
        font-family: inherit;
    }

    .lux-input::placeholder { color: var(--text-muted); }

    .lux-input--has-toggle { padding-right: 3rem; }

    /* Webkit autofill override */
    .lux-input:-webkit-autofill,
    .lux-input:-webkit-autofill:hover,
    .lux-input:-webkit-autofill:focus,
    .lux-input:-webkit-autofill:active {
        -webkit-box-shadow: 0 0 0 30px var(--autofill-bg) inset !important;
        -webkit-text-fill-color: var(--text-primary) !important;
        caret-color: var(--text-primary) !important;
        transition: background-color 5000s ease-in-out 0s !important;
    }

    .lux-focus-line {
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 0;
        height: 2px;
        background: linear-gradient(90deg, var(--node-cyan), var(--node-primary), var(--node-pink));
        border-radius: 2px;
        transition: width 0.45s cubic-bezier(0.16, 1, 0.3, 1);
        pointer-events: none;
    }

    .lux-input-wrap:focus-within .lux-focus-line { width: 100%; }

    .lux-reveal {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        width: 36px;
        height: 36px;
        border: none;
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
        border-radius: 10px;
        cursor: pointer;
        transition: color 0.3s ease, background 0.3s ease;
    }

    .lux-reveal:hover {
        color: var(--node-primary);
        background: var(--input-focus-bg);
    }

    .lux-reveal .eye-closed { display: none; }
    .lux-reveal.active .eye-open { display: none; }
    .lux-reveal.active .eye-closed { display: block; }

    .lux-error {
        margin: 0.5rem 0 0;
        color: var(--error-color);
        font-size: 0.8rem;
    }

    /* ==========================================================
       Options row (checkbox + forgot link)
       ========================================================== */
    .lux-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin: 0.25rem 0 1.5rem;
    }

    .lux-check {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        cursor: pointer;
        user-select: none;
    }

    .lux-check input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .lux-check-box {
        width: 20px;
        height: 20px;
        border-radius: 6px;
        border: 1.5px solid var(--input-border);
        background: var(--input-bg);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: transparent;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .lux-check:hover .lux-check-box { border-color: var(--node-primary); }

    .lux-check input:checked + .lux-check-box {
        background: linear-gradient(135deg, var(--login-primary), var(--login-primary-dark));
        border-color: transparent;
        color: #fff;
        box-shadow: 0 4px 12px rgba(var(--login-primary-rgb), 0.4);
    }

    .lux-check-label {
        font-size: 0.85rem;
        color: var(--text-secondary);
    }

    .lux-link {
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--node-primary);
        text-decoration: none;
        position: relative;
        transition: color 0.25s ease;
    }

    .lux-link::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -2px;
        width: 100%;
        height: 1px;
        background: currentColor;
        transform: scaleX(0);
        transform-origin: right;
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .lux-link:hover::after {
        transform: scaleX(1);
        transform-origin: left;
    }

    /* ==========================================================
       Magnetic submit button
       ========================================================== */
    .lux-submit {
        position: relative;
        width: 100%;
        height: 54px;
        border: none;
        border-radius: 14px;
        background: transparent;
        color: #fff;
        font-size: 1rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        cursor: pointer;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1),
                    box-shadow 0.35s ease;
        box-shadow: 0 10px 30px rgba(var(--login-primary-rgb), 0.4);
        will-change: transform;
    }

    .lux-submit:hover {
        box-shadow: 0 18px 40px rgba(var(--login-primary-rgb), 0.55);
    }

    .lux-submit-bg {
        position: absolute;
        inset: 0;
        background: linear-gradient(110deg,
            var(--login-primary) 0%,
            var(--login-primary-dark) 45%,
            var(--login-primary) 70%,
            var(--login-primary-light) 100%);
        background-size: 200% 100%;
        transition: background-position 0.5s ease;
        z-index: 0;
    }

    .lux-submit:hover .lux-submit-bg { background-position: 100% 0; }

    .lux-submit::before {
        content: '';
        position: absolute;
        top: 0;
        left: -75%;
        width: 50%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.35), transparent);
        transform: skewX(-20deg);
        animation: shimmerSweep 3s ease-in-out infinite;
        z-index: 1;
        pointer-events: none;
    }

    @keyframes shimmerSweep {
        0%, 65% { left: -75%; }
        100% { left: 135%; }
    }

    .lux-submit-content {
        position: relative;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        transition: opacity 0.2s ease;
    }

    .lux-submit-arrow { transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1); }
    .lux-submit:hover .lux-submit-arrow { transform: translateX(4px); }

    .lux-submit-ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.35);
        transform: translate(-50%, -50%) scale(0);
        pointer-events: none;
        z-index: 1;
    }

    .lux-submit-ripple.is-active {
        animation: rippleBurst 0.7s ease-out forwards;
    }

    @keyframes rippleBurst {
        to {
            transform: translate(-50%, -50%) scale(8);
            opacity: 0;
        }
    }

    .lux-submit-spinner {
        position: absolute;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        border: 2.5px solid rgba(255, 255, 255, 0.3);
        border-top-color: #fff;
        opacity: 0;
        z-index: 2;
        animation: spin 0.8s linear infinite;
    }

    .lux-submit.is-loading .lux-submit-content { opacity: 0; }
    .lux-submit.is-loading .lux-submit-spinner { opacity: 1; }

    @keyframes spin { to { transform: rotate(360deg); } }

    /* ==========================================================
       Trust row
       ========================================================== */
    .lux-trust {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.65rem;
        margin-top: 1.5rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--glass-border);
        font-size: 0.72rem;
        color: var(--text-muted);
        letter-spacing: 0.03em;
    }

    .lux-trust-item {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-weight: 500;
    }

    .lux-trust-item svg { color: var(--node-primary); }

    .lux-trust-sep {
        width: 3px;
        height: 3px;
        border-radius: 50%;
        background: var(--text-muted);
        opacity: 0.5;
    }

    /* Helper links above/below card */
    .lux-aux-link {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.85rem;
        color: var(--text-secondary);
        text-decoration: none;
        transition: color 0.25s ease, transform 0.25s ease;
    }

    .lux-aux-link svg {
        transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .lux-aux-link:hover {
        color: var(--node-primary);
    }

    .lux-aux-link:hover svg {
        transform: translateX(-3px);
    }

    /* Forward-arrow variant (register / continue links) */
    .lux-aux-link--forward:hover svg {
        transform: translateX(3px);
    }

    /* Row that holds a helper label + link (e.g. "New here? Create an account") */
    .lux-aux-row {
        margin: 1rem 0 0;
        padding-top: 0.9rem;
        border-top: 1px dashed var(--glass-border);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        color: var(--text-muted);
    }

    .lux-aux-text {
        color: var(--text-muted);
    }

    /* Informational panel (used on verify-email + confirm-password) */
    .lux-notice {
        background: color-mix(in srgb, var(--node-primary) 8%, transparent);
        border: 1px solid color-mix(in srgb, var(--node-primary) 22%, transparent);
        color: var(--text-secondary);
        font-size: 0.88rem;
        line-height: 1.55;
        padding: 0.85rem 1rem;
        border-radius: 12px;
        margin: 0 0 1rem;
    }

    .lux-notice--success {
        background: color-mix(in srgb, #10b981 10%, transparent);
        border-color: color-mix(in srgb, #10b981 30%, transparent);
        color: color-mix(in srgb, #10b981, var(--text-primary) 55%);
    }

    .lux-notice strong {
        color: var(--text-primary);
    }

    /* Secondary action button (e.g. Log out on verify-email) */
    .lux-submit--ghost .lux-submit-bg {
        background: transparent;
        border: 1px solid var(--glass-border);
    }

    .lux-submit--ghost .lux-submit-content {
        color: var(--text-secondary);
    }

    .lux-submit--ghost:hover .lux-submit-bg {
        background: color-mix(in srgb, var(--node-primary) 10%, transparent);
        border-color: color-mix(in srgb, var(--node-primary) 35%, transparent);
    }

    /* Form actions row (two side-by-side forms/buttons) */
    .lux-actions {
        display: flex;
        gap: 0.75rem;
        margin-top: 0.5rem;
    }

    .lux-actions > form,
    .lux-actions > button,
    .lux-actions > a {
        flex: 1;
    }

    .lux-actions .lux-submit {
        margin-top: 0;
    }

    /* ==========================================================
       Footer
       ========================================================== */
    .gateway-footer {
        margin: 1.5rem 0 0;
        font-size: 0.78rem;
        color: var(--text-muted);
        text-align: center;
    }

    /* ==========================================================
       Session refresh banner (login)
       ========================================================== */
    .session-refresh-banner {
        position: fixed;
        bottom: 2rem;
        left: 50%;
        transform: translateX(-50%) translateY(20px);
        z-index: 200;
        opacity: 0;
        animation: bannerSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .session-refresh-inner {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.25rem;
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(245, 158, 11, 0.2);
        border-radius: 12px;
        font-size: 0.8rem;
        color: var(--text-secondary);
        box-shadow: 0 4px 20px var(--card-shadow);
        white-space: nowrap;
    }

    .session-refresh-inner svg { color: #f59e0b; flex-shrink: 0; }
    .session-refresh-inner strong { color: #f59e0b; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; }

    @keyframes bannerSlideUp {
        to { opacity: 1; transform: translateX(-50%) translateY(0); }
    }

    /* ==========================================================
       Responsive
       ========================================================== */
    @media (max-width: 640px) {
        .gateway-title { font-size: 1.7rem; }
        .gateway-subtitle { font-size: 0.88rem; }
        .lock-badge { width: 80px; height: 80px; margin-bottom: 1.25rem; }
        .lock-badge-core { width: 60px; height: 60px; border-radius: 18px; }
        .lock-badge-core svg { width: 30px; height: 30px; }
        .holo-body { padding: 1.5rem 1.25rem; }
        .lux-trust { font-size: 0.68rem; gap: 0.45rem; }
        .cursor-glow { display: none; }
    }

    @media (max-width: 420px) {
        .gateway-title { font-size: 1.45rem; }
        .gateway-subtitle { font-size: 0.82rem; margin-bottom: 1.25rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .aurora-blob, .shape, .lock-ring, .lock-badge-core,
        .lock-badge-core::after, .holo-border, .gateway-title-text,
        .lux-submit::before, .lock-status-dot {
            animation: none !important;
        }
    }
</style>

{{-- ====================================================================
     Background layers (canvas + aurora + grid + shapes + cursor glow)
     ==================================================================== --}}
<canvas id="gatewayCanvas" class="gateway-canvas"></canvas>

<div class="aurora" aria-hidden="true">
    <div class="aurora-blob aurora-blob--1"></div>
    <div class="aurora-blob aurora-blob--2"></div>
    <div class="aurora-blob aurora-blob--3"></div>
    <div class="aurora-blob aurora-blob--4"></div>
</div>

<div class="grid-mask" aria-hidden="true"></div>

<div class="float-shapes" aria-hidden="true">
    <div class="shape shape-diamond"></div>
    <div class="shape shape-square"></div>
    <div class="shape shape-circle"></div>
    <div class="shape shape-triangle"></div>
    <div class="shape shape-hex"></div>
</div>

<div class="cursor-glow" id="cursorGlow" aria-hidden="true"></div>
