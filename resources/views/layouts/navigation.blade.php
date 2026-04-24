<nav id="mainnav-container" class="mainnav luminous-nav">
    <style>
        /* ======================================================================
           LUMINOUS SIDEBAR — node-graph inspired, theme-aware navigation.
           Shares the palette of the auth "Luminous Gateway" experience.
           Structure / collapse classes (.mainnav, .mininav-*, .has-sub) are
           preserved so the existing Nifty behavior keeps working.
           ====================================================================== */

        /* ---------- palette tokens (dark-first, light overrides below) ---------- */
        .luminous-nav {
            --ln-primary: #6366f1;
            --ln-secondary: #8b5cf6;
            --ln-accent: #a78bfa;
            --ln-cyan: #06b6d4;
            --ln-pink: #ec4899;
            --ln-danger: #f87171;

            --ln-bg: linear-gradient(180deg, #0b0b1f 0%, #0a0a1a 60%, #09091a 100%);
            --ln-surface: rgba(18, 18, 40, 0.72);
            --ln-surface-2: rgba(30, 30, 60, 0.55);
            --ln-border: rgba(255, 255, 255, 0.08);
            --ln-border-strong: rgba(255, 255, 255, 0.14);
            --ln-divider: rgba(255, 255, 255, 0.06);

            --ln-text: #e2e8f0;
            --ln-text-muted: #94a3b8;
            --ln-text-dim: #64748b;

            --ln-chip-bg: rgba(255, 255, 255, 0.05);
            --ln-chip-border: rgba(255, 255, 255, 0.08);

            --ln-hover-bg: rgba(99, 102, 241, 0.10);
            --ln-hover-border: rgba(99, 102, 241, 0.28);

            --ln-active-bg: linear-gradient(135deg, rgba(99, 102, 241, 0.22), rgba(139, 92, 246, 0.18));
            --ln-active-border: rgba(139, 92, 246, 0.45);
            --ln-active-glow: 0 6px 24px -8px rgba(99, 102, 241, 0.55), 0 0 0 1px rgba(139, 92, 246, 0.25) inset;

            --ln-sub-bg: rgba(10, 10, 26, 0.55);
            --ln-sub-connector: rgba(139, 92, 246, 0.35);

            --ln-scrollbar: rgba(139, 92, 246, 0.35);
            --ln-scrollbar-hover: rgba(139, 92, 246, 0.6);
        }

        /* Light-theme overrides (the app uses [data-bs-theme]) */
        [data-bs-theme="light"] .luminous-nav {
            --ln-bg: linear-gradient(180deg, #fbfcff 0%, #f6f8fd 60%, #f2f4fb 100%);
            --ln-surface: rgba(255, 255, 255, 0.9);
            --ln-surface-2: rgba(248, 250, 255, 0.85);
            --ln-border: rgba(15, 23, 42, 0.08);
            --ln-border-strong: rgba(15, 23, 42, 0.14);
            --ln-divider: rgba(15, 23, 42, 0.08);

            --ln-text: #1e293b;
            --ln-text-muted: #475569;
            --ln-text-dim: #94a3b8;

            --ln-chip-bg: #ffffff;
            --ln-chip-border: rgba(15, 23, 42, 0.08);

            --ln-hover-bg: rgba(79, 70, 229, 0.07);
            --ln-hover-border: rgba(79, 70, 229, 0.22);

            --ln-active-bg: linear-gradient(135deg, rgba(79, 70, 229, 0.10), rgba(139, 92, 246, 0.08));
            --ln-active-border: rgba(79, 70, 229, 0.35);
            --ln-active-glow: 0 8px 22px -10px rgba(79, 70, 229, 0.35), 0 0 0 1px rgba(79, 70, 229, 0.18) inset;

            --ln-sub-bg: rgba(246, 248, 253, 0.8);
            --ln-sub-connector: rgba(79, 70, 229, 0.25);

            --ln-scrollbar: rgba(79, 70, 229, 0.25);
            --ln-scrollbar-hover: rgba(79, 70, 229, 0.45);
        }

        /* ============================ BASE SHELL ============================= */
        .luminous-nav.mainnav {
            background: var(--ln-bg) !important;
            border-right: 1px solid var(--ln-border);
            position: relative;
            isolation: isolate;
            font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* animated gradient accent strip along the top edge */
        .luminous-nav::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg,
                var(--ln-cyan),
                var(--ln-primary),
                var(--ln-secondary),
                var(--ln-pink),
                var(--ln-cyan));
            background-size: 300% 100%;
            animation: ln-gradient-slide 8s ease infinite;
            z-index: 2;
            pointer-events: none;
        }

        /* subtle ambient glow in the background (node orb feel) */
        .luminous-nav::after {
            content: '';
            position: absolute;
            top: 10%;
            left: -40%;
            width: 180%;
            height: 40%;
            background: radial-gradient(ellipse at center,
                rgba(99, 102, 241, 0.14),
                transparent 60%);
            filter: blur(40px);
            z-index: 0;
            pointer-events: none;
        }

        [data-bs-theme="light"] .luminous-nav::after {
            background: radial-gradient(ellipse at center,
                rgba(79, 70, 229, 0.05),
                transparent 60%);
        }

        @keyframes ln-gradient-slide {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .luminous-nav .mainnav__inner {
            position: relative;
            z-index: 1;
        }

        /* custom scrollbar */
        .luminous-nav .scrollable-content::-webkit-scrollbar { width: 6px; }
        .luminous-nav .scrollable-content::-webkit-scrollbar-track { background: transparent; }
        .luminous-nav .scrollable-content::-webkit-scrollbar-thumb {
            background: var(--ln-scrollbar);
            border-radius: 10px;
            transition: background 0.3s;
        }
        .luminous-nav .scrollable-content::-webkit-scrollbar-thumb:hover { background: var(--ln-scrollbar-hover); }

        /* ============================ PROFILE CARD ============================ */
        .luminous-nav .mainnav__widget {
            margin: 0.5rem 0.55rem 0.65rem !important;
            padding: 0.6rem 0.55rem 0.5rem !important;
            background: var(--ln-surface);
            border: 1px solid var(--ln-border);
            border-radius: 12px;
            backdrop-filter: blur(16px) saturate(160%);
            -webkit-backdrop-filter: blur(16px) saturate(160%);
            position: relative;
            overflow: hidden;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .luminous-nav .mainnav__widget::before {
            content: '';
            position: absolute;
            inset: 0;
            padding: 1px;
            border-radius: 12px;
            background: linear-gradient(135deg,
                rgba(99, 102, 241, 0.25),
                transparent 40%,
                rgba(236, 72, 153, 0.18));
            -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
            -webkit-mask-composite: xor;
                    mask-composite: exclude;
            pointer-events: none;
            opacity: 0.7;
        }

        .luminous-nav .mainnav__widget:hover {
            border-color: var(--ln-border-strong);
            box-shadow: 0 10px 30px -12px rgba(99, 102, 241, 0.35);
        }

        .luminous-nav .mainnav__avatar {
            width: 38px !important;
            height: 38px !important;
            border-radius: 50% !important;
            padding: 2px;
            background: linear-gradient(135deg, var(--ln-cyan), var(--ln-primary), var(--ln-pink));
            box-shadow: 0 5px 14px -6px rgba(99, 102, 241, 0.55);
            position: relative;
            animation: ln-avatar-float 6s ease-in-out infinite;
        }

        .luminous-nav .mininav-toggle {
            position: relative;
        }

        /* online node indicator on avatar */
        .luminous-nav .mainnav__widget .mininav-toggle::after {
            content: '';
            position: absolute;
            right: calc(50% - 18px);
            top: calc(50% - 11px);
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            border: 2px solid var(--ln-surface);
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6);
            animation: ln-pulse-dot 2.2s ease-out infinite;
        }

        @keyframes ln-pulse-dot {
            0%   { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.55); }
            70%  { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        @keyframes ln-avatar-float {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-3px); }
        }

        .luminous-nav .mainnav-widget-toggle {
            color: var(--ln-text) !important;
            background: transparent !important;
            border-radius: 12px;
            transition: background 0.2s;
        }

        .luminous-nav .mainnav-widget-toggle:hover {
            background: var(--ln-hover-bg) !important;
        }

        .luminous-nav .mainnav-widget-toggle h5 {
            color: var(--ln-text);
            font-weight: 600;
            letter-spacing: -0.01em;
            font-size: 0.8rem;
            margin-top: 0.3rem;
        }

        .luminous-nav .mainnav-widget-toggle small {
            color: var(--ln-text-muted) !important;
            font-size: 0.62rem;
            font-weight: 500;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .luminous-nav .mainnav-widget-toggle p small {
            text-transform: none;
            letter-spacing: 0.01em;
            color: var(--ln-text-dim) !important;
        }

        /* inline profile/settings sublinks */
        .luminous-nav #usernav .nav-link {
            padding: 0.55rem 0.75rem !important;
            margin-top: 0.35rem;
            border-radius: 10px;
            color: var(--ln-text-muted) !important;
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.55rem;
            transition: background 0.2s, color 0.2s, transform 0.2s;
        }

        .luminous-nav #usernav .nav-link:hover {
            background: var(--ln-hover-bg);
            color: var(--ln-text) !important;
            transform: translateX(2px);
        }

        .luminous-nav #usernav .nav-link i { margin: 0 !important; }

        /* ============================ MENU ITEMS ============================= */
        .luminous-nav .mainnav__menu {
            padding: 0 0.4rem;
            gap: 0.05rem;
            display: flex;
            flex-direction: column;
        }

        .luminous-nav .mainnav__menu > .nav-item {
            position: relative;
        }

        .luminous-nav .mainnav__menu > .nav-item > .nav-link {
            display: flex;
            align-items: center;
            gap: 0.1rem;
            padding: 0.22rem 0.4rem !important;
            border-radius: 8px;
            color: var(--ln-text) !important;
            font-size: 0.7rem;
            font-weight: 500;
            letter-spacing: -0.005em;
            position: relative;
            border: 1px solid transparent;
            background: transparent;
            min-width: 0;
            transition: background 0.25s, border-color 0.25s, color 0.25s, transform 0.2s;
        }

        /* nav label takes remaining space and truncates long text with ellipsis,
           so the caret at the end (Nifty's native ::after) always stays visible */
        .luminous-nav .mainnav__menu > .nav-item > .nav-link > .nav-label {
            flex: 1 1 auto;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* left accent bar (hidden, revealed on hover/active — node-connector feel) */
        .luminous-nav .mainnav__menu > .nav-item > .nav-link::before {
            content: '';
            position: absolute;
            left: -2px;
            top: 50%;
            transform: translateY(-50%) scaleY(0);
            height: 60%;
            width: 3px;
            border-radius: 3px;
            background: linear-gradient(180deg, var(--ln-cyan), var(--ln-primary), var(--ln-pink));
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            transform-origin: center;
        }

        .luminous-nav .mainnav__menu > .nav-item > .nav-link:hover {
            background: var(--ln-hover-bg);
            border-color: var(--ln-hover-border);
            color: var(--ln-text) !important;
        }

        .luminous-nav .mainnav__menu > .nav-item > .nav-link:hover::before {
            transform: translateY(-50%) scaleY(0.6);
        }

        .luminous-nav .mainnav__menu > .nav-item > .nav-link.active {
            background: var(--ln-active-bg);
            border-color: var(--ln-active-border);
            color: var(--ln-text) !important;
            box-shadow: var(--ln-active-glow);
        }

        .luminous-nav .mainnav__menu > .nav-item > .nav-link.active::before {
            transform: translateY(-50%) scaleY(1);
            box-shadow: 0 0 10px var(--ln-primary);
        }

        /* ---- restyle Nifty's native has-sub caret (border-based arrow) ----
           Nifty draws a small border-corner arrow on .has-sub > .mininav-toggle:after.
           We override its position/size/color so there's only ONE clean chevron. */
        .luminous-nav .mainnav__menu .has-sub > .mininav-toggle:not(.has-badge)::after {
            margin-left: 0.4rem !important;
            margin-right: 0.15rem !important;
            width: 0.45em !important;
            height: 0.45em !important;
            flex-shrink: 0;
            border-color: var(--ln-text-dim) !important;
            border-width: 0.12em 0.12em 0 0 !important;
            opacity: 0.65;
            transition: transform 0.25s ease, border-color 0.2s ease, opacity 0.2s ease;
        }

        .luminous-nav .mainnav__menu .has-sub > .mininav-toggle:not(.has-badge):hover::after {
            border-color: var(--ln-primary) !important;
            opacity: 1;
        }

        .luminous-nav .mainnav__menu .has-sub > .mininav-toggle.active:not(.has-badge)::after {
            border-color: var(--ln-primary) !important;
            opacity: 1;
        }

        /* ============================ ICON CHIPS (compact) ============================= */
        .luminous-nav .mainnav__menu > .nav-item > .nav-link > .fa,
        .luminous-nav .mainnav__menu > .nav-item > .nav-link > i.fa {
            width: 22px;
            height: 22px;
            flex-shrink: 0;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            background: var(--ln-chip-bg);
            border: 1px solid var(--ln-chip-border);
            border-radius: 6px;
            font-size: 0.68rem !important;
            line-height: 1 !important;
            margin-right: 0.45rem !important;
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1),
                        background 0.3s, border-color 0.3s, box-shadow 0.3s;
            position: relative;
        }

        .luminous-nav .mainnav__menu > .nav-item > .nav-link:hover > .fa,
        .luminous-nav .mainnav__menu > .nav-item > .nav-link:hover > i.fa {
            transform: scale(1.06);
            border-color: var(--ln-hover-border);
            box-shadow: 0 3px 10px -4px currentColor;
        }

        .luminous-nav .mainnav__menu > .nav-item > .nav-link.active > .fa,
        .luminous-nav .mainnav__menu > .nav-item > .nav-link.active > i.fa {
            background: linear-gradient(135deg, var(--ln-primary), var(--ln-secondary));
            color: #fff !important;
            border-color: transparent;
            box-shadow: 0 4px 14px -4px rgba(99, 102, 241, 0.55);
            animation: ln-icon-glow 2.6s ease-in-out infinite;
        }

        @keyframes ln-icon-glow {
            0%, 100% { box-shadow: 0 4px 14px -4px rgba(99, 102, 241, 0.45); }
            50%      { box-shadow: 0 6px 20px -4px rgba(139, 92, 246, 0.65); }
        }

        /* keep brand icon colors from original palette visible as a subtle tint */
        .luminous-nav .nav-link .fa-dashboard,
        .luminous-nav .nav-link .fa-tachometer   { color: #60a5fa; }
        .luminous-nav .nav-link .fa-cubes        { color: #fb923c; }
        .luminous-nav .nav-link .fa-calendar     { color: #34d399; }
        .luminous-nav .nav-link .fa-shopping-cart{ color: #f472b6; }
        .luminous-nav .nav-link .fa-rotate-left  { color: #c084fc; }
        .luminous-nav .nav-link .fa-cart-plus    { color: #22d3ee; }
        .luminous-nav .nav-link .fa-reply        { color: #fb7185; }
        .luminous-nav .nav-link .fa-bank,
        .luminous-nav .nav-link .fa-university   { color: #a3e635; }
        .luminous-nav .nav-link .fa-users        { color: #fbbf24; }
        .luminous-nav .nav-link .fa-user         { color: #94a3b8; }
        .luminous-nav .nav-link .fa-cog          { color: #a78bfa; }
        .luminous-nav .nav-link .fa-clipboard    { color: #818cf8; }
        .luminous-nav .nav-link .fa-building,
        .luminous-nav .nav-link .fa-building-o   { color: #2dd4bf; }
        .luminous-nav .nav-link .fa-sign-out     { color: #f87171; }
        .luminous-nav .nav-link .fa-chart-line,
        .luminous-nav .nav-link .fa-line-chart   { color: #22d3ee; }
        .luminous-nav .nav-link .fa-gift         { color: #fb7185; }
        .luminous-nav .nav-link .fa-cut,
        .luminous-nav .nav-link .fa-scissors     { color: #c084fc; }
        .luminous-nav .nav-link .fa-exchange     { color: #818cf8; }
        .luminous-nav .nav-link .fa-home         { color: #93c5fd; }
        .luminous-nav .nav-link .fa-hand-o-right { color: #fb7185; }
        .luminous-nav .nav-link .fa-truck        { color: #fdba74; }
        .luminous-nav .nav-link .fa-ticket       { color: #f472b6; }
        .luminous-nav .nav-link .fa-wrench       { color: #94a3b8; }
        .luminous-nav .nav-link .fa-sitemap      { color: #22d3ee; }
        .luminous-nav .nav-link .fa-sun-o        { color: #fbbf24; }
        .luminous-nav .nav-link .fa-filter       { color: #a78bfa; }
        .luminous-nav .nav-link .fa-dollar       { color: #34d399; }
        .luminous-nav .nav-link .fa-share-square-o { color: #f472b6; }

        /* light theme — slightly deeper icon colors */
        [data-bs-theme="light"] .luminous-nav .nav-link .fa-dashboard,
        [data-bs-theme="light"] .luminous-nav .nav-link .fa-tachometer    { color: #2563eb; }
        [data-bs-theme="light"] .luminous-nav .nav-link .fa-cubes         { color: #ea580c; }
        [data-bs-theme="light"] .luminous-nav .nav-link .fa-calendar      { color: #059669; }
        [data-bs-theme="light"] .luminous-nav .nav-link .fa-shopping-cart { color: #db2777; }
        [data-bs-theme="light"] .luminous-nav .nav-link .fa-cart-plus     { color: #0891b2; }
        [data-bs-theme="light"] .luminous-nav .nav-link .fa-bank,
        [data-bs-theme="light"] .luminous-nav .nav-link .fa-university    { color: #65a30d; }
        [data-bs-theme="light"] .luminous-nav .nav-link .fa-users         { color: #d97706; }
        [data-bs-theme="light"] .luminous-nav .nav-link .fa-user          { color: #475569; }
        [data-bs-theme="light"] .luminous-nav .nav-link .fa-cog           { color: #7c3aed; }
        [data-bs-theme="light"] .luminous-nav .nav-link .fa-building,
        [data-bs-theme="light"] .luminous-nav .nav-link .fa-building-o    { color: #0d9488; }
        [data-bs-theme="light"] .luminous-nav .nav-link .fa-sign-out      { color: #dc2626; }

        /* ============================ SUBMENU (tree) ============================= */
        .luminous-nav .mininav-content.nav {
            margin: 0.2rem 0 0.3rem 0.35rem !important;
            padding: 0.2rem 0 0.2rem 1rem !important;
            position: relative;
            background: var(--ln-sub-bg);
            border-radius: 8px;
            border: 1px solid var(--ln-border);
            overflow: hidden;
        }

        /* vertical tree line (node connector) */
        .luminous-nav .mininav-content.nav::before {
            content: '';
            position: absolute;
            left: 0.65rem;
            top: 0.3rem;
            bottom: 0.3rem;
            width: 1px;
            background: linear-gradient(180deg, var(--ln-sub-connector), transparent);
        }

        .luminous-nav .mininav-content .nav-item {
            position: relative;
        }

        /* node dot for each sub-item */
        .luminous-nav .mininav-content .nav-item > .nav-link::before {
            content: '';
            position: absolute;
            left: -0.42rem;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: var(--ln-text-dim);
            box-shadow: 0 0 0 2px var(--ln-sub-bg);
            transition: background 0.25s, box-shadow 0.25s, transform 0.25s;
        }

        .luminous-nav .mininav-content .nav-item > .nav-link {
            display: block;
            padding: 0.28rem 0.5rem !important;
            margin: 0.04rem 0.25rem;
            border-radius: 5px;
            color: var(--ln-text-muted) !important;
            font-size: 0.72rem;
            font-weight: 500;
            position: relative;
            transition: background 0.2s, color 0.2s, transform 0.2s;
        }

        .luminous-nav .mininav-content .nav-item > .nav-link:hover {
            background: var(--ln-hover-bg);
            color: var(--ln-text) !important;
            transform: translateX(2px);
        }

        .luminous-nav .mininav-content .nav-item > .nav-link:hover::before {
            background: var(--ln-primary);
            transform: translateY(-50%) scale(1.35);
            box-shadow: 0 0 0 2px var(--ln-sub-bg), 0 0 10px var(--ln-primary);
        }

        .luminous-nav .mininav-content .nav-item > .nav-link.active {
            background: var(--ln-active-bg);
            color: var(--ln-text) !important;
            font-weight: 600;
        }

        .luminous-nav .mininav-content .nav-item > .nav-link.active::before {
            background: linear-gradient(135deg, var(--ln-primary), var(--ln-pink));
            transform: translateY(-50%) scale(1.55);
            box-shadow: 0 0 0 2px var(--ln-sub-bg), 0 0 14px var(--ln-primary);
        }

        /* reset list bullets everywhere inside the sub-list */
        .luminous-nav .mininav-content,
        .luminous-nav .mininav-content .nav-item {
            list-style: none;
        }

        /* hide legacy popper arrow inside sub-list (we use the tree line instead) */
        .luminous-nav .mininav-content > .arrow[data-popper-arrow] { display: none; }

        /* ==========================================================================
           COLLAPSED RAIL (.mn--min) — submenu becomes a floating popover.
           Nifty promotes .mininav-content to position: fixed in this mode, so it
           MUST be fully opaque (no glass bleed of page content).
           ========================================================================== */
        @media (min-width: 992px) {
            .mn--min .luminous-nav .mininav-content.nav,
            .mn--min .luminous-nav .mainnav__widget .mininav-content {
                /* solid opaque surface — defeats the page bleed-through */
                background-color: #111127 !important;
                background-image:
                    linear-gradient(135deg,
                        rgba(99, 102, 241, 0.08),
                        rgba(139, 92, 246, 0.04) 40%,
                        rgba(236, 72, 153, 0.06)) !important;
                border: 1px solid var(--ln-border-strong) !important;
                border-radius: 12px !important;
                box-shadow:
                    0 20px 50px -12px rgba(0, 0, 0, 0.55),
                    0 8px 20px -6px rgba(99, 102, 241, 0.25),
                    0 0 0 1px rgba(255, 255, 255, 0.03) inset !important;
                padding: 0.35rem !important;
                margin: 0 0 0 0.25rem !important;
                overflow: visible !important;
                min-width: 12rem;
                max-width: 18rem;
            }

            /* light-theme popover — opaque white surface */
            [data-bs-theme="light"] .mn--min .luminous-nav .mininav-content.nav,
            [data-bs-theme="light"] .mn--min .luminous-nav .mainnav__widget .mininav-content {
                background-color: #ffffff !important;
                background-image:
                    linear-gradient(135deg,
                        rgba(79, 70, 229, 0.03),
                        rgba(139, 92, 246, 0.015) 40%,
                        rgba(236, 72, 153, 0.025)) !important;
                border-color: rgba(15, 23, 42, 0.08) !important;
                box-shadow:
                    0 18px 44px -12px rgba(15, 23, 42, 0.22),
                    0 6px 18px -6px rgba(79, 70, 229, 0.18),
                    0 0 0 1px rgba(15, 23, 42, 0.03) inset !important;
            }

            /* kill the decorative tree-line in popover mode — it only belongs in the
               expanded sidebar's inline submenus, not in the floating popover */
            .mn--min .luminous-nav .mininav-content.nav::before {
                display: none !important;
            }

            /* re-style Nifty's CSS-triangle arrow so it points at the item with
               our popover color, instead of the default theme color */
            .mn--min .luminous-nav .mininav-content .arrow[data-popper-arrow] {
                display: block !important;
                border-right-color: #111127 !important;
            }

            [data-bs-theme="light"] .mn--min .luminous-nav .mininav-content .arrow[data-popper-arrow] {
                border-right-color: #ffffff !important;
            }

            /* flyout items — clean, consistent typography, no node-dot decoration */
            .mn--min .luminous-nav .mininav-content .nav-item > .nav-link {
                display: block;
                padding: 0.4rem 0.75rem !important;
                margin: 0.08rem 0 !important;
                border-radius: 6px;
                font-size: 0.78rem !important;
                font-weight: 500;
                color: var(--ln-text) !important;
                transform: none !important;
            }

            .mn--min .luminous-nav .mininav-content .nav-item > .nav-link::before {
                display: none !important;
            }

            .mn--min .luminous-nav .mininav-content .nav-item > .nav-link:hover {
                background: var(--ln-hover-bg) !important;
                color: var(--ln-text) !important;
                transform: translateX(2px) !important;
            }

            .mn--min .luminous-nav .mininav-content .nav-item > .nav-link.active {
                background: linear-gradient(135deg, var(--ln-primary), var(--ln-secondary)) !important;
                color: #ffffff !important;
                font-weight: 600;
                box-shadow: 0 4px 14px -4px rgba(99, 102, 241, 0.5);
            }

            /* popover mini profile card — same opaque treatment for its inner buttons */
            .mn--min .luminous-nav .mainnav__widget .mininav-content .mainnav-widget-toggle {
                color: var(--ln-text) !important;
            }

            .mn--min .luminous-nav .mainnav__widget .mininav-content #usernav .nav-link {
                color: var(--ln-text) !important;
            }
        }

        /* prevent the ambient background glow from leaking outside the sidebar
           in collapsed mode (can otherwise bleed over the content area) */
        .luminous-nav.mainnav { overflow: hidden; }
        .luminous-nav .mainnav__inner { overflow: visible; }

        /* ============================ BOTTOM / LOGOUT ============================= */
        .luminous-nav .mainnav__bottom-content {
            border-top: 1px solid var(--ln-divider) !important;
            padding: 0.6rem 0.4rem 0.75rem !important;
            position: relative;
        }

        .luminous-nav .mainnav__bottom-content .nav-link.mininav-toggle {
            display: flex;
            align-items: center;
            padding: 0.3rem 0.5rem !important;
            border-radius: 8px;
            color: var(--ln-text) !important;
            font-size: 0.76rem;
            font-weight: 500;
            border: 1px solid transparent;
            min-width: 0;
            transition: background 0.2s, border-color 0.2s, color 0.2s;
        }

        .luminous-nav .mainnav__bottom-content .nav-link.mininav-toggle > .nav-label {
            flex: 1 1 auto;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .luminous-nav .mainnav__bottom-content .nav-link.mininav-toggle:hover {
            background: rgba(248, 113, 113, 0.08);
            border-color: rgba(248, 113, 113, 0.25);
            color: var(--ln-danger) !important;
        }

        .luminous-nav .mainnav__bottom-content .nav-link.mininav-toggle > .fa {
            width: 22px;
            height: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--ln-chip-bg);
            border: 1px solid var(--ln-chip-border);
            border-radius: 6px;
            font-size: 0.68rem !important;
            line-height: 1 !important;
            margin-right: 0.45rem !important;
            transition: background 0.25s, border-color 0.25s, color 0.25s;
        }

        .luminous-nav .mainnav__bottom-content .nav-link.mininav-toggle:hover > .fa {
            background: linear-gradient(135deg, rgba(248, 113, 113, 0.2), rgba(236, 72, 153, 0.15));
            border-color: rgba(248, 113, 113, 0.4);
            color: #fff !important;
        }

        .luminous-nav .mainnav__bottom-content .dropdown-item {
            color: var(--ln-danger) !important;
            font-weight: 500;
            font-size: 0.85rem;
            padding: 0.45rem 0.9rem;
            border-radius: 8px;
            margin: 0.1rem 0.35rem;
            transition: background 0.2s;
        }

        .luminous-nav .mainnav__bottom-content .dropdown-item:hover {
            background: rgba(248, 113, 113, 0.12);
        }

        /* ============================ COLLAPSED (mn--min) ============================= */
        /* when the sidebar collapses to the icon-rail, the icon CHIP is the only
           visual element — strip the nav-link's own pill/border/accent so we don't
           get a doubled background around the chip. */
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__menu > .nav-item > .nav-link,
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__menu > .nav-item > .nav-link:hover,
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__menu > .nav-item > .nav-link.active {
            justify-content: center;
            padding: 0.3rem !important;
            background: transparent !important;
            border-color: transparent !important;
            box-shadow: none !important;
        }

        /* kill the left gradient accent bar in collapsed mode */
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__menu > .nav-item > .nav-link::before {
            display: none !important;
        }

        /* remove the drop shadow that was emanating from the chip in expanded mode */
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__menu > .nav-item > .nav-link:hover > .fa,
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__menu > .nav-item > .nav-link:hover > i.fa {
            transform: scale(1.08);
            box-shadow: 0 4px 14px -4px rgba(99, 102, 241, 0.5);
        }

        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__menu > .nav-item > .nav-link > .fa,
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__menu > .nav-item > .nav-link > i.fa {
            margin-right: 0 !important;
        }

        :where(.mn--min, .mn--min-min) .luminous-nav .nav-item.has-sub > .nav-link::after {
            display: none !important;
        }

        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__widget {
            padding: 0.5rem 0.25rem !important;
            margin: 0.5rem 0.25rem !important;
        }

        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__widget .mininav-toggle::after {
            right: calc(50% - 10px);
            top: calc(50% - 15px);
        }

        /* same cleanup for the bottom logout rail item */
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__bottom-content .nav-link.mininav-toggle,
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__bottom-content .nav-link.mininav-toggle:hover {
            justify-content: center;
            padding: 0.3rem !important;
            background: transparent !important;
            border-color: transparent !important;
        }

        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__bottom-content .nav-link.mininav-toggle > .fa {
            margin-right: 0 !important;
        }

        /* ============================ RESPONSIVE ============================= */
        @media (max-width: 991.98px) {
            .luminous-nav .mainnav__widget { margin: 0.75rem 0.75rem 0.85rem !important; }
            .luminous-nav .mainnav__menu   { padding: 0 0.45rem; }
        }

        @media (max-width: 575px) {
            .luminous-nav .mainnav__menu > .nav-item > .nav-link {
                padding: 0.28rem 0.45rem !important;
                font-size: 0.74rem;
            }
            .luminous-nav .mainnav__menu > .nav-item > .nav-link > .fa {
                width: 20px;
                height: 20px;
                margin-right: 0.4rem !important;
                font-size: 0.64rem !important;
            }
            .luminous-nav .mainnav__avatar {
                width: 36px !important;
                height: 36px !important;
            }
            .luminous-nav .mininav-content .nav-item > .nav-link {
                font-size: 0.7rem;
            }
        }

        /* reduced motion — disable animations for users that prefer it */
        @media (prefers-reduced-motion: reduce) {
            .luminous-nav *,
            .luminous-nav *::before,
            .luminous-nav *::after {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>

    <div class="mainnav__inner">
        <div class="pb-5 mainnav__top-content scrollable-content">
            <div id="_dm-mainnavProfile" class="mainnav__widget hv-outline-parent">
                <div class="py-2 text-center mininav-toggle">
                    <img class="mainnav__avatar img-md rounded-circle hv-oc" src="{{ secure_asset('assets/img/profile-photos/1.png') }}"
                        alt="Profile Picture">
                </div>
                <div class="mininav-content collapse d-mn-max">
                    <span data-popper-arrow class="arrow"></span>
                    <div class="d-grid">
                        <button class="p-2 border-0 mainnav-widget-toggle d-block btn" data-bs-toggle="collapse" data-bs-target="#usernav"
                            aria-expanded="false" aria-controls="usernav">
                            <span class="dropdown-toggle d-flex justify-content-center align-items-center">
                                <h5 class="mb-0 me-3">{{ auth()->user()->name }}</h5>
                            </span>
                            <small class="text-body-secondary">{{ getUserRoles(auth()->user()) }}</small>
                            <p class="mb-0 mt-1"><small class="text-body-secondary">{{ auth()->user()->branch?->name }}</small></p>
                        </button>
                        <div id="usernav" class="nav flex-column collapse">
                            <a href="{{ route('profile.edit') }}" class="nav-link">
                                <i class="fa fa-user fs-6"></i>
                                <span>Profile</span>
                            </a>
                            <a href="{{ route('settings::index') }}" class="nav-link">
                                <i class="fa fa-cog fs-6"></i>
                                <span>Settings</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Dynamic navigation: ordered and filtered by user preferences --}}
            @php
                $navItems = \App\Services\NavigationService::getNavigationItems();
            @endphp
            <ul class="mainnav__menu nav flex-column">
                @foreach ($navItems as $navItem)
                    @if ($navItem['visible'] ?? true)
                        @include('layouts.nav.sections.' . $navItem['id'])
                    @endif
                @endforeach
            </ul>
        </div>
        <div class="mainnav__bottom-content border-top">
            <ul id="mainnav" class="mainnav__menu nav flex-column">
                <li class="nav-item has-sub">
                    <a href="#" class="nav-link mininav-toggle collapsed" aria-expanded="false">
                        <i class="fa fa-sign-out fs-5"></i>
                        <span class="nav-label mininav-content ms-1 collapse show">Logout</span>
                    </a>
                    <ul class="mininav-content nav flex-column collapse">
                        <li data-popper-arrow class="arrow"></li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
