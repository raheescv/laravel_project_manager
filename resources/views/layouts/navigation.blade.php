<nav id="mainnav-container" class="mainnav luminous-nav">
    <style>
        /* ======================================================================
           PREMIUM SIDEBAR — "Refined Indigo".
           Compact, low-padding, theme-aware navigation. Light-first with a
           [data-bs-theme="dark"] override (matches the app's premium .rvx system).
           Structure / collapse classes (.mainnav, .mininav-*, .has-sub) are
           preserved so the existing Nifty behavior keeps working.
           ====================================================================== */

        /* ---------- palette tokens (light-first, dark overrides below) ---------- */
        .luminous-nav {
            --ln-primary: #4f46e5;
            --ln-secondary: #7c3aed;

            --ln-bg: #ffffff;
            --ln-surface: #f7f8fc;
            --ln-border: #eceef5;
            --ln-border-strong: #e2e5ef;
            --ln-divider: #eef0f6;

            --ln-text: #334155;
            --ln-text-strong: #0f172a;
            --ln-text-muted: #64748b;
            --ln-text-dim: #94a3b8;

            --ln-chip-bg: #f1f3f9;
            --ln-chip-border: #eceef5;
            --ln-chip-color: #64748b;

            --ln-hover-bg: #f4f4fd;

            --ln-active-bg: linear-gradient(135deg, rgba(99, 102, 241, 0.10), rgba(124, 58, 237, 0.06));
            --ln-active-color: #4f46e5;
            --ln-accent-bar: linear-gradient(180deg, #6366f1, #7c3aed);

            --ln-sub-line: #ececf6;
            --ln-sub-dot: #cbd2e0;
            --ln-sub-dot-ring: #ffffff;

            --ln-scrollbar: rgba(79, 70, 229, 0.22);
            --ln-scrollbar-hover: rgba(79, 70, 229, 0.42);

            --ln-popover-bg: #ffffff;

            --ln-danger: #ef4444;
            --ln-danger-bg: rgba(239, 68, 68, 0.10);
            --ln-danger-border: rgba(239, 68, 68, 0.22);
        }

        /* Dark-theme overrides (app toggles [data-bs-theme="dark"]) */
        [data-bs-theme="dark"] .luminous-nav {
            --ln-primary: #818cf8;
            --ln-secondary: #a78bfa;

            --ln-bg: linear-gradient(180deg, #0d0d1f 0%, #0a0a18 100%);
            --ln-surface: rgba(255, 255, 255, 0.04);
            --ln-border: rgba(255, 255, 255, 0.07);
            --ln-border-strong: rgba(255, 255, 255, 0.12);
            --ln-divider: rgba(255, 255, 255, 0.06);

            --ln-text: #cbd5e1;
            --ln-text-strong: #e2e8f0;
            --ln-text-muted: #94a3b8;
            --ln-text-dim: #64748b;

            --ln-chip-bg: rgba(255, 255, 255, 0.05);
            --ln-chip-border: rgba(255, 255, 255, 0.08);
            --ln-chip-color: #94a3b8;

            --ln-hover-bg: rgba(129, 140, 248, 0.12);

            --ln-active-bg: rgba(129, 140, 248, 0.14);
            --ln-active-color: #c7d2fe;
            --ln-accent-bar: linear-gradient(180deg, #818cf8, #a78bfa);

            --ln-sub-line: rgba(255, 255, 255, 0.09);
            --ln-sub-dot: #475569;
            --ln-sub-dot-ring: #0c0c1a;

            --ln-scrollbar: rgba(129, 140, 248, 0.3);
            --ln-scrollbar-hover: rgba(129, 140, 248, 0.55);

            --ln-popover-bg: #14141f;

            --ln-danger: #f87171;
            --ln-danger-bg: rgba(248, 113, 113, 0.12);
            --ln-danger-border: rgba(248, 113, 113, 0.28);
        }

        /* ============================ BASE SHELL ============================= */
        .luminous-nav.mainnav {
            background: var(--ln-bg) !important;
            border-right: 1px solid var(--ln-border);
            position: relative;
            /* inherit the app body font (Poppins stack) — avoids a separate
               webfont swap/flash in the sidebar on each page load */
        }

        .luminous-nav .mainnav__inner {
            position: relative;
            z-index: 1;
            overflow: visible;
        }

        /* custom scrollbar */
        .luminous-nav .scrollable-content::-webkit-scrollbar { width: 5px; }
        .luminous-nav .scrollable-content::-webkit-scrollbar-track { background: transparent; }
        .luminous-nav .scrollable-content::-webkit-scrollbar-thumb {
            background: var(--ln-scrollbar);
            border-radius: 10px;
            transition: background 0.3s;
        }
        .luminous-nav .scrollable-content::-webkit-scrollbar-thumb:hover { background: var(--ln-scrollbar-hover); }

        /* ============================ BRAND CARD ============================ */
        .luminous-nav .mainnav__widget {
            margin: 0.5rem 0.55rem 0.5rem !important;
            padding: 0.45rem 0.5rem !important;
            background: var(--ln-surface);
            border: 1px solid var(--ln-border);
            border-radius: 12px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .luminous-nav .mainnav__widget::before { content: none; }
        .luminous-nav .mainnav__widget:hover {
            border-color: var(--ln-border-strong);
            box-shadow: 0 8px 20px -16px rgba(15, 23, 42, 0.4);
        }

        .luminous-nav .mainnav__brand {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            text-decoration: none;
            color: inherit;
            padding: 0;
        }
        .luminous-nav .mainnav__brand:hover { color: inherit; text-decoration: none; }

        .luminous-nav .mainnav__brand-circle {
            width: 36px;
            height: 36px;
            flex: 0 0 auto;
            border-radius: 10px;
            padding: 2px;
            background: linear-gradient(135deg, #38bdf8, #6366f1, #a855f7);
            box-shadow: 0 6px 14px -8px rgba(79, 70, 229, 0.8);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .luminous-nav .mainnav__brand-circle img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #ffffff;
            border-radius: 8px;
            padding: 4px;
            display: block;
        }
        .luminous-nav .mainnav__brand:hover .mainnav__brand-circle {
            transform: translateY(-1px);
            box-shadow: 0 9px 18px -10px rgba(79, 70, 229, 0.85);
        }

        .luminous-nav .mainnav__brand-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
            line-height: 1.1;
        }
        .luminous-nav .mainnav__brand-name {
            font-size: 0.84rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            color: var(--ln-text-strong);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .luminous-nav .mainnav__brand-tag {
            margin-top: 0.08rem;
            font-size: 0.58rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--ln-text-dim);
        }

        .luminous-nav .mininav-toggle { position: relative; }

        /* ============================ MENU ITEMS ============================= */
        .luminous-nav .mainnav__menu {
            padding: 0 0.45rem;
            gap: 0.05rem;
            display: flex;
            flex-direction: column;
        }
        .luminous-nav .mainnav__menu > .nav-item { position: relative; }

        .luminous-nav .mainnav__menu > .nav-item > .nav-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.32rem 0.45rem !important;
            margin: 0.06rem 0;
            border-radius: 9px;
            color: var(--ln-text) !important;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0;
            position: relative;
            min-width: 0;
            transition: background 0.18s, color 0.18s;
        }

        .luminous-nav .mainnav__menu > .nav-item > .nav-link > .nav-label {
            flex: 1 1 auto;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* left rail accent — active only, clean */
        .luminous-nav .mainnav__menu > .nav-item > .nav-link.active::before {
            content: '';
            position: absolute;
            left: -0.42rem;
            top: 24%;
            bottom: 24%;
            width: 3px;
            border-radius: 0 3px 3px 0;
            background: var(--ln-accent-bar);
        }

        .luminous-nav .mainnav__menu > .nav-item > .nav-link:hover {
            background: var(--ln-hover-bg);
            color: var(--ln-active-color) !important;
        }

        .luminous-nav .mainnav__menu > .nav-item > .nav-link.active {
            background: var(--ln-active-bg);
            color: var(--ln-active-color) !important;
        }

        /* ---- Nifty's native has-sub caret (border-arrow) — single clean chevron ---- */
        .luminous-nav .mainnav__menu .has-sub > .mininav-toggle:not(.has-badge)::after {
            margin-left: 0.35rem !important;
            margin-right: 0.1rem !important;
            width: 0.42em !important;
            height: 0.42em !important;
            flex-shrink: 0;
            border-color: var(--ln-text-dim) !important;
            border-width: 0.11em 0.11em 0 0 !important;
            opacity: 0.6;
            transition: transform 0.25s ease, border-color 0.2s ease, opacity 0.2s ease;
        }
        .luminous-nav .mainnav__menu .has-sub > .mininav-toggle:not(.has-badge):hover::after,
        .luminous-nav .mainnav__menu .has-sub > .mininav-toggle.active:not(.has-badge)::after {
            border-color: var(--ln-primary) !important;
            opacity: 1;
        }

        /* ============================ ICON CHIPS (uniform) ============================= */
        .luminous-nav .mainnav__menu > .nav-item > .nav-link > .fa,
        .luminous-nav .mainnav__menu > .nav-item > .nav-link > i.fa {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            background: var(--ln-chip-bg);
            border: 1px solid var(--ln-chip-border);
            border-radius: 7px;
            color: var(--ln-chip-color);
            font-size: 0.72rem !important;
            line-height: 1 !important;
            margin: 0 !important;
            transition: background 0.18s, border-color 0.18s, color 0.18s, box-shadow 0.18s;
        }

        .luminous-nav .mainnav__menu > .nav-item > .nav-link:hover > .fa,
        .luminous-nav .mainnav__menu > .nav-item > .nav-link:hover > i.fa {
            color: var(--ln-primary);
        }

        .luminous-nav .mainnav__menu > .nav-item > .nav-link.active > .fa,
        .luminous-nav .mainnav__menu > .nav-item > .nav-link.active > i.fa {
            background: linear-gradient(135deg, var(--ln-primary), var(--ln-secondary));
            color: #fff !important;
            border-color: transparent;
            box-shadow: 0 5px 12px -5px rgba(99, 102, 241, 0.6);
        }

        /* ============================ SUBMENU (tree) ============================= */
        .luminous-nav .mininav-content.nav {
            margin: 0.1rem 0 0.25rem 1.4rem !important;
            padding: 0.1rem 0 0.1rem 0.65rem !important;
            position: relative;
            border-left: 1.5px solid var(--ln-sub-line);
            list-style: none;
        }
        .luminous-nav .mininav-content,
        .luminous-nav .mininav-content .nav-item { list-style: none; }

        .luminous-nav .mininav-content .nav-item { position: relative; }

        .luminous-nav .mininav-content .nav-item > .nav-link {
            display: block;
            padding: 0.26rem 0.5rem !important;
            margin: 0.02rem 0;
            border-radius: 7px;
            color: var(--ln-text-muted) !important;
            font-size: 0.745rem;
            font-weight: 600;
            position: relative;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: background 0.18s, color 0.18s;
        }

        /* node dot sitting on the tree line */
        .luminous-nav .mininav-content .nav-item > .nav-link::before {
            content: '';
            position: absolute;
            left: -0.73rem;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--ln-sub-dot);
            border: 1.5px solid var(--ln-sub-dot-ring);
            transition: background 0.18s, box-shadow 0.18s, transform 0.18s;
        }

        .luminous-nav .mininav-content .nav-item > .nav-link:hover {
            background: var(--ln-hover-bg);
            color: var(--ln-active-color) !important;
        }
        .luminous-nav .mininav-content .nav-item > .nav-link:hover::before {
            background: var(--ln-primary);
        }

        .luminous-nav .mininav-content .nav-item > .nav-link.active {
            color: var(--ln-active-color) !important;
            font-weight: 800;
        }
        .luminous-nav .mininav-content .nav-item > .nav-link.active::before {
            background: var(--ln-primary);
            box-shadow: 0 0 0 3px var(--ln-active-bg);
        }

        /* hide legacy popper arrow inside inline sub-list (tree line replaces it) */
        .luminous-nav .mininav-content > .arrow[data-popper-arrow] { display: none; }

        /* ==========================================================================
           COLLAPSED RAIL (.mn--min) — submenu becomes a floating popover.
           Nifty promotes .mininav-content to position: fixed here, so it MUST be
           fully opaque (no page bleed-through).
           ========================================================================== */
        @media (min-width: 992px) {
            .mn--min .luminous-nav .mininav-content.nav,
            .mn--min .luminous-nav .mainnav__widget .mininav-content {
                background: var(--ln-popover-bg) !important;
                border: 1px solid var(--ln-border-strong) !important;
                border-radius: 12px !important;
                box-shadow: 0 18px 44px -14px rgba(15, 23, 42, 0.28),
                            0 6px 16px -8px rgba(79, 70, 229, 0.18) !important;
                padding: 0.35rem !important;
                margin: 0 0 0 0.25rem !important;
                overflow: visible !important;
                min-width: 12rem;
                max-width: 18rem;
            }

            [data-bs-theme="dark"] .mn--min .luminous-nav .mininav-content.nav,
            [data-bs-theme="dark"] .mn--min .luminous-nav .mainnav__widget .mininav-content {
                box-shadow: 0 22px 50px -12px rgba(0, 0, 0, 0.6),
                            0 8px 20px -8px rgba(99, 102, 241, 0.25) !important;
            }

            /* no tree line in popover mode */
            .mn--min .luminous-nav .mininav-content.nav { border-left: 0 !important; }

            /* color Nifty's CSS-triangle arrow to match the popover */
            .mn--min .luminous-nav .mininav-content .arrow[data-popper-arrow] {
                display: block !important;
                border-right-color: var(--ln-popover-bg) !important;
            }

            /* flyout items — clean, no node dot */
            .mn--min .luminous-nav .mininav-content .nav-item > .nav-link {
                display: block;
                padding: 0.4rem 0.7rem !important;
                margin: 0.06rem 0 !important;
                border-radius: 7px;
                font-size: 0.78rem !important;
                font-weight: 600;
                color: var(--ln-text) !important;
            }
            .mn--min .luminous-nav .mininav-content .nav-item > .nav-link::before { display: none !important; }
            .mn--min .luminous-nav .mininav-content .nav-item > .nav-link:hover {
                background: var(--ln-hover-bg) !important;
                color: var(--ln-active-color) !important;
            }
            .mn--min .luminous-nav .mininav-content .nav-item > .nav-link.active {
                background: linear-gradient(135deg, var(--ln-primary), var(--ln-secondary)) !important;
                color: #ffffff !important;
                font-weight: 700;
                box-shadow: 0 4px 14px -5px rgba(99, 102, 241, 0.5);
            }
        }

        /* ============================ BOTTOM / LOGOUT ============================= */
        .luminous-nav .mainnav__bottom-content {
            border-top: 1px solid var(--ln-divider) !important;
            padding: 0.5rem 0.45rem 0.6rem !important;
            position: relative;
        }

        .luminous-nav .mainnav__bottom-content .nav-link.mininav-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.32rem 0.45rem !important;
            border-radius: 9px;
            color: var(--ln-text) !important;
            font-size: 0.78rem;
            font-weight: 600;
            min-width: 0;
            transition: background 0.18s, color 0.18s;
        }
        .luminous-nav .mainnav__bottom-content .nav-link.mininav-toggle > .nav-label {
            flex: 1 1 auto;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .luminous-nav .mainnav__bottom-content .nav-link.mininav-toggle:hover {
            background: var(--ln-danger-bg);
            color: var(--ln-danger) !important;
        }
        .luminous-nav .mainnav__bottom-content .nav-link.mininav-toggle > .fa {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--ln-chip-bg);
            border: 1px solid var(--ln-chip-border);
            border-radius: 7px;
            color: var(--ln-danger);
            font-size: 0.72rem !important;
            line-height: 1 !important;
            margin: 0 !important;
            transition: background 0.18s, border-color 0.18s, color 0.18s;
        }
        .luminous-nav .mainnav__bottom-content .nav-link.mininav-toggle:hover > .fa {
            background: linear-gradient(135deg, var(--ln-danger), #ec4899);
            border-color: transparent;
            color: #fff !important;
        }
        .luminous-nav .mainnav__bottom-content .dropdown-item {
            color: var(--ln-danger) !important;
            font-weight: 600;
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            margin: 0.1rem 0.3rem;
            transition: background 0.18s;
        }
        .luminous-nav .mainnav__bottom-content .dropdown-item:hover { background: var(--ln-danger-bg); }

        /* ============================ COLLAPSED (mn--min) sizing ============================= */
        @media (min-width: 992px) {
            .mn--min .luminous-nav.mainnav {
                width: var(--nf-mainnav-min-width);
                max-width: var(--nf-mainnav-min-width);
            }
            .mn--min .luminous-nav .mainnav__top-content,
            .mn--min .luminous-nav .mainnav__bottom-content { padding-inline: 0 !important; }
            .mn--min .luminous-nav .mainnav__menu { width: 100%; padding-inline: 0.45rem !important; }
            .mn--min .luminous-nav .mainnav__menu > .nav-item,
            .mn--min .luminous-nav .mainnav__menu > .nav-item > .nav-link { width: 100%; }
            .mn--min .luminous-nav .mainnav__menu > .nav-item > .nav-link { min-height: 40px; }
            .mn--min.tm--expanded-hd .content__header { border-bottom-left-radius: 0 !important; }
            .mn--min.tm--expanded-hd .content__header::before { display: none !important; }
        }

        /* collapsed rail — the icon CHIP is the only visual; strip the pill/accent */
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__menu > .nav-item > .nav-link,
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__menu > .nav-item > .nav-link:hover,
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__menu > .nav-item > .nav-link.active {
            justify-content: center;
            padding: 0.3rem !important;
            background: transparent !important;
        }
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__menu > .nav-item > .nav-link::before { display: none !important; }
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__menu > .nav-item > .nav-link.active > .fa,
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__menu > .nav-item > .nav-link:hover > .fa,
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__menu > .nav-item > .nav-link:hover > i.fa {
            box-shadow: 0 4px 14px -5px rgba(99, 102, 241, 0.5);
        }
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__menu > .nav-item > .nav-link > .fa,
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__menu > .nav-item > .nav-link > i.fa { margin: 0 !important; }
        :where(.mn--min, .mn--min-min) .luminous-nav .nav-item.has-sub > .nav-link::after { display: none !important; }

        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__widget {
            padding: 0 !important;
            margin: 0.5rem 0.45rem 0.6rem !important;
            background: transparent;
            border: 0;
            box-shadow: none !important;
        }
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__brand { justify-content: center; padding: 0; width: 100%; }
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__brand-circle { width: 38px; height: 38px; }
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__brand-text { display: none !important; }

        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__bottom-content .nav-link.mininav-toggle,
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__bottom-content .nav-link.mininav-toggle:hover {
            justify-content: center;
            padding: 0.3rem !important;
            background: transparent !important;
        }
        :where(.mn--min, .mn--min-min) .luminous-nav .mainnav__bottom-content .nav-link.mininav-toggle > .fa { margin: 0 !important; }

        /* ============================ RESPONSIVE ============================= */
        @media (max-width: 991.98px) {
            .root.mn--max,
            .root.mn--min { --nf-mainnav-max-width: min(78vw, 17rem); }

            .luminous-nav.mainnav {
                width: var(--nf-mainnav-max-width);
                max-width: 100vw;
                box-shadow: 0 1.25rem 3rem rgba(15, 23, 42, 0.28);
            }
            .root.mn--max:not(.mn--show) .luminous-nav.mainnav,
            .root.mn--min:not(.mn--show) .luminous-nav.mainnav {
                transform: translateX(calc(-100% - 1px)) !important;
            }

            .luminous-nav .mainnav__inner {
                height: 100dvh;
                max-height: 100dvh;
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }
            .luminous-nav .mainnav__top-content {
                flex: 1 1 auto;
                min-height: 0;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 1rem !important;
            }
            .luminous-nav .mainnav__bottom-content {
                flex: 0 0 auto;
                padding-bottom: max(0.6rem, env(safe-area-inset-bottom)) !important;
            }

            .luminous-nav .mainnav__widget { margin: 0.6rem 0.6rem 0.55rem !important; padding: 0.55rem 0.6rem !important; }
            .luminous-nav .mainnav__brand-circle { width: 40px; height: 40px; }
            .luminous-nav .mainnav__brand-name { font-size: 0.92rem; }
            .luminous-nav .mainnav__menu { padding: 0 0.55rem; }

            .luminous-nav .mainnav__menu > .nav-item > .nav-link,
            .luminous-nav .mainnav__bottom-content .nav-link.mininav-toggle {
                min-height: 42px;
                padding: 0.5rem 0.6rem !important;
                font-size: 0.85rem;
            }
            .luminous-nav .mainnav__menu > .nav-item > .nav-link > .fa,
            .luminous-nav .mainnav__menu > .nav-item > .nav-link > i.fa,
            .luminous-nav .mainnav__bottom-content .nav-link.mininav-toggle > .fa {
                width: 28px;
                height: 28px;
                font-size: 0.8rem !important;
            }
            .luminous-nav .mininav-content .nav-item > .nav-link {
                min-height: 38px;
                display: flex;
                align-items: center;
                padding: 0.45rem 0.6rem !important;
                font-size: 0.81rem;
            }
        }

        @media (max-width: 575.98px) {
            .root.mn--max,
            .root.mn--min { --nf-mainnav-max-width: min(82vw, 16.5rem); }
            .luminous-nav .mainnav__menu > .nav-item > .nav-link { font-size: 0.83rem; }
            .luminous-nav .mainnav__brand-name { font-size: 0.9rem; }
            .luminous-nav .mininav-content .nav-item > .nav-link { font-size: 0.79rem; }
        }

        /* reduced motion */
        @media (prefers-reduced-motion: reduce) {
            .luminous-nav *,
            .luminous-nav *::before,
            .luminous-nav *::after { animation: none !important; transition: none !important; }
        }
    </style>

    <div class="mainnav__inner">
        <div class="pb-5 mainnav__top-content scrollable-content">
            <div id="_dm-mainnavProfile" class="mainnav__widget hv-outline-parent">
                <a href="{{ route('dashboard') }}" class="mainnav__brand mininav-toggle hv-oc"
                    aria-label="{{ config('app.name', 'Astra') }} — go to dashboard">
                    <span class="mainnav__brand-circle">
                        <img src="{{ cache('logo', asset('assets/img/logo.svg')) }}"
                            alt="{{ config('app.name', 'Astra') }} logo">
                    </span>
                    <span class="mainnav__brand-text">
                        <span class="mainnav__brand-name">{{ config('app.name', 'Astra') }}</span>
                        <span class="mainnav__brand-tag">Workspace</span>
                    </span>
                </a>
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
