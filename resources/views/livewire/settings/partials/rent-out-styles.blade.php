<style>
    .rtx {
        --rtx-acc: var(--bs-primary);
        --rtx-acc-soft: color-mix(in srgb, var(--rtx-acc) 11%, transparent);
        --rtx-acc-ink: color-mix(in srgb, var(--rtx-acc) 85%, var(--bs-emphasis-color));
        --rtx-grad: linear-gradient(135deg, color-mix(in srgb, var(--rtx-acc) 80%, #fff), var(--rtx-acc) 45%, color-mix(in srgb, var(--rtx-acc) 70%, #000));
        --rtx-glow: 0 10px 22px -12px color-mix(in srgb, var(--rtx-acc) 80%, transparent);
        --rtx-surface: var(--bs-component-bg, var(--bs-body-bg));
        --rtx-track: color-mix(in srgb, var(--rtx-acc) 5%, color-mix(in srgb, var(--bs-emphasis-color) 4%, var(--rtx-surface)));
        --rtx-line: var(--bs-border-color);
        --rtx-line-soft: color-mix(in srgb, var(--bs-border-color) 60%, transparent);
        --rtx-lift: 0 1px 2px rgba(16, 24, 40, .08), 0 6px 16px -8px rgba(16, 24, 40, .25);
    }

    [data-bs-theme="dark"] .rtx {
        --rtx-acc-ink: color-mix(in srgb, var(--rtx-acc) 50%, #fff);
        --rtx-acc-soft: color-mix(in srgb, var(--rtx-acc) 24%, transparent);
        --rtx-lift: 0 1px 2px rgba(0, 0, 0, .4), 0 6px 16px -8px rgba(0, 0, 0, .6);
    }

    /* ---- Section tabs: segmented track ---- */
    .rtx-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem 1rem;
        padding: 1rem;
        border-bottom: 1px solid var(--rtx-line-soft);
    }

    .rtx-rail {
        display: flex;
        flex-wrap: nowrap;
        gap: 4px;
        min-width: 0;
        max-width: 100%;
        padding: 5px;
        overflow-x: auto;
        scrollbar-width: none;
        border: 1px solid var(--rtx-line-soft);
        border-radius: 14px;
        background: var(--rtx-track);
    }

    .rtx-rail::-webkit-scrollbar {
        display: none;
    }

    .rtx-tab {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex: none;
        padding: 6px 12px 6px 6px;
        border: 0;
        border-radius: 10px;
        background: transparent;
        color: var(--bs-secondary-color);
        font-size: 13px;
        font-weight: 600;
        white-space: nowrap;
        transition: background .18s, color .18s, box-shadow .18s;
    }

    [dir="rtl"] .rtx-tab {
        padding: 6px 6px 6px 12px;
    }

    .rtx-tab:hover {
        color: var(--bs-emphasis-color);
        background: color-mix(in srgb, var(--rtx-surface) 60%, transparent);
    }

    .rtx-tab:focus-visible {
        outline: 2px solid var(--rtx-acc);
        outline-offset: 1px;
    }

    .rtx-tab.active {
        color: var(--bs-emphasis-color);
        background: var(--rtx-surface);
        box-shadow: var(--rtx-lift);
    }

    .rtx-tab-ic {
        display: inline-grid;
        place-items: center;
        width: 26px;
        height: 26px;
        border-radius: 8px;
        color: var(--bs-tertiary-color);
        transition: background .18s, color .18s;
    }

    .rtx-tab.active .rtx-tab-ic {
        color: #fff;
        background: var(--rtx-grad);
        box-shadow: var(--rtx-glow);
    }

    .rtx-dot {
        width: 7px;
        height: 7px;
        flex: none;
        border-radius: 50%;
        background: var(--rtx-tone);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--rtx-tone) 22%, transparent);
    }

    .rtx-dot.is-warning {
        --rtx-tone: var(--bs-warning);
    }

    .rtx-dot.is-danger {
        --rtx-tone: var(--bs-danger);
    }

    .rtx-progress {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 12px;
        line-height: 1.3;
        color: var(--bs-secondary-color);
    }

    .rtx-progress strong {
        display: block;
        color: var(--bs-emphasis-color);
    }

    .rtx-ring {
        display: grid;
        place-items: center;
        width: 38px;
        height: 38px;
        flex: none;
        border-radius: 50%;
        background: conic-gradient(var(--rtx-acc) calc(var(--p) * 1%), var(--rtx-line-soft) 0);
    }

    .rtx-ring b {
        display: grid;
        place-items: center;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: var(--rtx-surface);
        color: var(--bs-emphasis-color);
        font-size: 10px;
    }

    /* ---- Pane hero ---- */
    .rtx-hero {
        position: relative;
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding: 1.1rem 1.25rem;
        overflow: hidden;
        border: 1px solid color-mix(in srgb, var(--rtx-acc) 18%, var(--rtx-line));
        border-radius: 16px;
        background:
            radial-gradient(120% 140% at 100% 0%, color-mix(in srgb, var(--rtx-acc) 10%, transparent), transparent 55%),
            linear-gradient(135deg, color-mix(in srgb, var(--rtx-acc) 6%, var(--rtx-surface)), var(--rtx-surface) 60%);
    }

    .rtx-hero::before {
        content: "";
        position: absolute;
        inset-block: 14px;
        inset-inline-start: 0;
        width: 3px;
        border-radius: 0 3px 3px 0;
        background: var(--rtx-grad);
    }

    [dir="rtl"] .rtx-hero::before {
        border-radius: 3px 0 0 3px;
    }

    .rtx-hero-ic {
        display: inline-grid;
        place-items: center;
        width: 46px;
        height: 46px;
        flex: none;
        border-radius: 14px;
        color: #fff;
        font-size: 18px;
        background: var(--rtx-grad);
        box-shadow: var(--rtx-glow), inset 0 1px 0 rgba(255, 255, 255, .25);
    }

    .rtx-hero-title {
        margin: 0;
        color: var(--bs-emphasis-color);
        font-size: 16px;
        font-weight: 700;
        letter-spacing: -.01em;
    }

    .rtx-hero-sub {
        margin: 4px 0 0;
        max-width: 72ch;
        color: var(--bs-secondary-color);
        font-size: 13px;
        line-height: 1.55;
    }

    .rtx-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 2px 10px;
        border: 1px solid color-mix(in srgb, var(--rtx-tone) 28%, transparent);
        border-radius: 999px;
        background: color-mix(in srgb, var(--rtx-tone) 12%, transparent);
        color: color-mix(in srgb, var(--rtx-tone) 80%, var(--bs-emphasis-color));
        font-size: 11px;
        font-weight: 600;
        line-height: 1.6;
        white-space: nowrap;
    }

    .rtx-pill.is-success {
        --rtx-tone: var(--bs-success);
    }

    .rtx-pill.is-warning {
        --rtx-tone: var(--bs-warning);
    }

    .rtx-pill.is-danger {
        --rtx-tone: var(--bs-danger);
    }

    .rtx-pill.is-primary {
        --rtx-tone: var(--rtx-acc);
    }

    /* ---- Group card header ---- */
    .rtx-group-head {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .75rem 1rem;
        border-bottom: 1px solid var(--rtx-line-soft);
        border-top-left-radius: inherit;
        border-top-right-radius: inherit;
        background: linear-gradient(180deg, color-mix(in srgb, var(--rtx-acc) 5%, var(--rtx-surface)), var(--rtx-surface));
    }

    .rtx-group-ic {
        display: inline-grid;
        place-items: center;
        width: 34px;
        height: 34px;
        flex: none;
        border: 1px solid color-mix(in srgb, var(--rtx-acc) 20%, transparent);
        border-radius: 10px;
        background: var(--rtx-acc-soft);
        color: var(--rtx-acc-ink);
        font-size: 14px;
    }

    .rtx-group-title {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        color: var(--bs-emphasis-color);
        font-size: 14px;
        font-weight: 600;
    }

    .rtx-group-sub {
        margin-top: 2px;
        color: var(--bs-secondary-color);
        font-size: 12px;
        line-height: 1.5;
    }

    @media (max-width: 575.98px) {
        .rtx-progress {
            display: none;
        }

        .rtx-hero {
            padding: 1rem;
        }

        .rtx-hero-ic {
            width: 40px;
            height: 40px;
            font-size: 16px;
        }
    }
</style>
