@extends('property.appointment.public-layout')

@section('content')
    <style>
        /* ╔══════════════════════════════════════════════════════════════════╗
           ║  Public appointment — "Estate"                                       ║
           ║                                                                  ║
           ║  Scoped to .apxp so it cannot reach any admin screen. This page   ║
           ║  is the one surface a customer ever sees, so it runs its own     ║
           ║  editorial system (warm ivory ground, ink hero, serif display,   ║
           ║  squared corners) rather than the .apx admin skin — only the     ║
           ║  ACCENT is shared, derived from the tenant theme's primary.      ║
           ╚══════════════════════════════════════════════════════════════════╝ */
        .apxp{
            --acc:var(--bs-primary);
            --acc-rgb:var(--bs-primary-rgb);
            --acc-deep:color-mix(in srgb, var(--bs-primary), #000 26%);
            --acc-lift:color-mix(in srgb, var(--bs-primary), #fff 44%);

            --sf:#fffdfa;
            --sf-2:#f6f2ec;
            --ink:#191512;
            --ink-2:#5b5248;
            --mut:#94897c;
            --ln:#e7dfd4;
            --serif:var(--pub-serif);

            font-size:13px; line-height:1.5; color:var(--ink);
        }
        [data-bs-theme="dark"] .apxp{
            --sf:#1b1815;
            --sf-2:#231f1b;
            --ink:#f2ece4;
            --ink-2:#c3b8ab;
            --mut:#8c8175;
            --ln:#332d27;
        }

        /* ── hero ──────────────────────────────────────────────────── */
        .apxp-hero{
            position:relative; overflow:hidden; color:#f6f1ea;
            padding:26px clamp(18px,4vw,40px) 104px;
            background:
                radial-gradient(90% 120% at 88% 0%, color-mix(in srgb, var(--acc) 42%, transparent), transparent 60%),
                linear-gradient(160deg, #171310 0%, #241d18 62%, #171310 100%);
        }
        .apxp-hero::after{
            content:"\f015"; font-family:FontAwesome; position:absolute;
            inset-inline-end:-16px; bottom:44px; font-size:200px; line-height:1;
            color:rgba(255,255,255,.05); pointer-events:none;
        }
        .apxp-inner{ max-width:1080px; margin:0 auto; position:relative; z-index:2; }

        .apxp-mast{ display:flex; align-items:center; gap:11px; margin-bottom:clamp(26px,4vw,42px); }
        .apxp-mast .bm{
            width:38px; height:38px; border-radius:11px; flex:none; overflow:hidden;
            display:inline-flex; align-items:center; justify-content:center;
            font-weight:800; font-size:16px; letter-spacing:-.02em;
            background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.2);
        }
        .apxp-mast .bm img{ width:100%; height:100%; object-fit:contain; background:#fff; }
        .apxp-mast .nm{ font-size:13.5px; font-weight:750; letter-spacing:-.015em; }
        .apxp-mast .ds{ font-size:10.5px; color:rgba(246,241,234,.6); margin-top:1px; }
        .apxp-mast .lock{
            margin-inline-start:auto; flex:none; font-size:10.5px; font-weight:700;
            padding:6px 12px; border-radius:999px;
            background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.18);
        }

        .apxp-eyebrow{
            font-size:10px; font-weight:800; letter-spacing:.24em; text-transform:uppercase;
            color:color-mix(in srgb, var(--acc), #fff 42%);
        }
        .apxp-h1{
            /* This Bootstrap build sets a hard --bs-heading-color on h1-h6, which
               would paint the headline dark ink on the dark hero. */
            color:inherit;
            font-family:var(--serif); font-weight:400; letter-spacing:-.02em; line-height:1.06;
            font-size:clamp(28px,4.6vw,46px); margin:12px 0 0; max-width:17ch;
        }
        .apxp-h1 em{ font-style:italic; color:color-mix(in srgb, var(--acc), #fff 46%); }
        .apxp-meta{
            margin-top:13px; font-size:11px; font-weight:800; letter-spacing:.16em;
            text-transform:uppercase; color:rgba(246,241,234,.55);
        }
        .apxp-sub{
            margin:14px 0 0; font-size:13px; line-height:1.6; max-width:48ch;
            color:rgba(246,241,234,.72);
        }
        .apxp-hero strong{ color:#fff; font-weight:750; }
        .apxp-facts{ display:flex; gap:clamp(18px,3vw,30px); flex-wrap:wrap; margin-top:24px; }
        .apxp-fact .k{
            font-size:9.5px; font-weight:800; letter-spacing:.14em; text-transform:uppercase;
            color:rgba(246,241,234,.5);
        }
        .apxp-fact .v{ font-size:13.5px; font-weight:700; margin-top:3px; letter-spacing:-.01em; max-width:30ch; }

        /* hero skeleton, shown for the blink before the payload lands */
        .apxp-sk{ height:13px; border-radius:2px; background:rgba(255,255,255,.09); animation:apxp-pulse 1.5s ease-in-out infinite; }
        .apxp-sk.lg{ height:38px; max-width:520px; margin-top:14px; }
        .apxp-sk.md{ max-width:300px; margin-top:16px; }
        .apxp-sk.sm{ max-width:170px; }
        @keyframes apxp-pulse{ 0%,100%{ opacity:.55 } 50%{ opacity:1 } }

        /* ── panel ─────────────────────────────────────────────────── */
        .apxp-body{
            max-width:1080px; margin:-78px auto 0; position:relative; z-index:3;
            padding:0 clamp(18px,4vw,40px);
        }
        .apxp-panel{
            background:var(--sf); border:1px solid var(--ln); border-radius:4px;
            box-shadow:0 30px 62px -34px rgba(25,21,18,.62);
        }
        .apxp-ptop{
            display:flex; align-items:flex-end; justify-content:space-between; gap:14px;
            flex-wrap:wrap; padding:22px clamp(18px,2.4vw,26px) 0;
        }
        .apxp-ptop h3{ margin:0; color:var(--ink); font-family:var(--serif); font-weight:400; font-size:21px; letter-spacing:-.01em; }
        .apxp-ptop .cnt{ font-size:11.5px; color:var(--mut); }
        .apxp-ptop .where{ font-size:11.5px; color:var(--mut); margin-top:5px; }
        .apxp-ptop .where i{ margin-inline-end:5px; }
        .apxp-rule{ height:1px; background:var(--ln); margin:16px clamp(18px,2.4vw,26px) 0; }

        .apxp-strip{ display:flex; gap:0; overflow-x:auto; padding:14px clamp(18px,2.4vw,26px) 0; scrollbar-width:thin; }
        .apxp-d{
            flex:none; min-width:80px; padding:10px 6px 12px; text-align:center; cursor:pointer;
            font-family:inherit; background:none; border:0; border-bottom:2px solid transparent; color:var(--ink-2);
        }
        .apxp-d .w{ font-size:10px; font-weight:800; letter-spacing:.12em; text-transform:uppercase; color:var(--mut); }
        .apxp-d .n{ font-family:var(--serif); font-size:23px; margin-top:2px; line-height:1.1; }
        .apxp-d .mo{ font-size:9px; font-weight:800; letter-spacing:.12em; text-transform:uppercase; color:var(--acc); margin-top:2px; }
        .apxp-d .mo.ghost{ visibility:hidden; }
        .apxp-d:hover{ color:var(--ink); }
        .apxp-d.sel{ color:var(--ink); border-bottom-color:var(--acc); }
        .apxp-d.sel .w{ color:var(--acc); }

        .apxp-rows{ padding:4px clamp(18px,2.4vw,26px) 18px; }
        .apxp-row{
            display:flex; align-items:center; gap:14px; width:100%; text-align:start;
            font-family:inherit; cursor:pointer; color:var(--ink);
            padding:14px 6px; background:none; border:0; border-bottom:1px solid var(--ln);
        }
        .apxp-row:last-child{ border-bottom:0; }
        .apxp-row .t{ font-family:var(--serif); font-size:20px; min-width:104px; letter-spacing:-.01em; }
        .apxp-row .m{ flex:1; min-width:0; font-size:11.5px; color:var(--mut); }
        .apxp-row .pick{
            flex:none; font-size:10.5px; font-weight:800; letter-spacing:.14em;
            text-transform:uppercase; color:var(--acc); opacity:0;
        }
        .apxp-row:hover{ background:var(--sf-2); }
        .apxp-row:hover .pick{ opacity:1; }
        .apxp-row.sel{ background:color-mix(in srgb, var(--acc), transparent 92%); }
        .apxp-row.sel .pick{ opacity:1; }
        .apxp-row:focus-visible{ outline:2px solid var(--acc); outline-offset:-2px; }

        .apxp-foot{
            display:flex; align-items:center; gap:16px; flex-wrap:wrap;
            padding:18px clamp(18px,2.4vw,26px); border-top:1px solid var(--ln); background:var(--sf-2);
        }
        .apxp-foot .lab .k{ font-size:9.5px; font-weight:800; letter-spacing:.14em; text-transform:uppercase; color:var(--mut); }
        .apxp-foot .lab .v{ font-family:var(--serif); font-size:18px; margin-top:2px; letter-spacing:-.01em; }
        .apxp-foot .lab .exp{ font-size:11px; color:var(--mut); margin-top:5px; }
        .apxp-foot .lab .v.none{ font-family:inherit; font-size:13px; color:var(--mut); }
        .apxp-cta{
            margin-inline-start:auto; font-family:inherit; cursor:pointer; border:0;
            padding:14px 26px; border-radius:2px; color:#fff;
            font-size:12px; font-weight:800; letter-spacing:.14em; text-transform:uppercase;
            background:var(--ink); transition:background .16s ease;
        }
        .apxp-cta:hover:not(:disabled){ background:var(--acc); }
        .apxp-cta:disabled{ opacity:.42; cursor:not-allowed; }
        [data-bs-theme="dark"] .apxp-cta{ background:var(--acc); color:#fff; }
        [data-bs-theme="dark"] .apxp-cta:hover:not(:disabled){ background:var(--acc-deep); }

        /* ── confirmed ─────────────────────────────────────────────── */
        .apxp-conf-h{
            display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;
            padding:24px clamp(18px,2.4vw,26px) 20px; border-bottom:1px solid var(--ln);
        }
        .apxp-conf-h .k{ font-size:9.5px; font-weight:800; letter-spacing:.14em; text-transform:uppercase; color:var(--mut); }
        .apxp-conf-h .when{ font-family:var(--serif); font-size:clamp(22px,3vw,30px); letter-spacing:-.015em; margin-top:6px; line-height:1.15; }
        .apxp-conf-h .at{ font-size:13px; color:var(--ink-2); margin-top:5px; }
        .apxp-seal{
            flex:none; display:inline-flex; align-items:center; gap:7px;
            font-size:10.5px; font-weight:800; letter-spacing:.12em; text-transform:uppercase;
            padding:8px 14px; border-radius:999px;
            color:var(--bs-success); background:color-mix(in srgb, var(--bs-success), transparent 90%);
            border:1px solid color-mix(in srgb, var(--bs-success), transparent 74%);
        }
        .apxp-kv{ display:flex; justify-content:space-between; gap:16px; padding:12px 0; border-bottom:1px dashed var(--ln); font-size:12.5px; }
        .apxp-kv:last-child{ border-bottom:0; }
        .apxp-kv .k{ color:var(--mut); flex:none; }
        .apxp-kv .v{ font-weight:700; text-align:end; }
        .apxp-pad{ padding:6px clamp(18px,2.4vw,26px) 18px; }
        .apxp-note{
            padding:16px clamp(18px,2.4vw,26px); border-top:1px solid var(--ln);
            background:var(--sf-2); font-size:11.5px; color:var(--ink-2); line-height:1.6;
        }

        /* ── states ────────────────────────────────────────────────── */
        .apxp-alert{ display:flex; gap:12px; align-items:flex-start; margin:18px clamp(18px,2.4vw,26px) 0; padding:13px 15px; border-radius:3px; }
        .apxp-alert i{ font-size:14px; margin-top:1px; flex:none; }
        .apxp-alert .t{ font-weight:750; font-size:12.5px; }
        .apxp-alert .s{ font-size:11.5px; color:var(--ink-2); margin-top:3px; line-height:1.55; }
        .apxp-alert.bad{ background:color-mix(in srgb, var(--bs-danger), transparent 92%); border:1px solid color-mix(in srgb, var(--bs-danger), transparent 80%); color:var(--bs-danger); }
        .apxp-alert.warn{ background:color-mix(in srgb, var(--bs-warning), transparent 90%); border:1px solid color-mix(in srgb, var(--bs-warning), transparent 78%); color:var(--bs-warning); }

        .apxp-empty{ padding:44px clamp(18px,2.4vw,26px) 46px; text-align:center; }
        .apxp-empty .art{
            width:62px; height:62px; border-radius:50%; margin:0 auto 16px;
            display:flex; align-items:center; justify-content:center; font-size:24px;
            color:var(--acc); background:color-mix(in srgb, var(--acc), transparent 92%);
            border:1px solid color-mix(in srgb, var(--acc), transparent 84%);
        }
        .apxp-empty h3{ margin:0 0 7px; color:var(--ink); font-family:var(--serif); font-weight:400; font-size:21px; }
        .apxp-empty p{ margin:0 auto; font-size:12.5px; color:var(--ink-2); max-width:44ch; line-height:1.6; }
        .apxp-empty .call{
            display:inline-flex; align-items:center; gap:8px; margin-top:18px; padding:12px 20px;
            border-radius:2px; text-decoration:none; color:#fff; background:var(--ink);
            font-size:12px; font-weight:800; letter-spacing:.12em; text-transform:uppercase;
        }
        [data-bs-theme="dark"] .apxp-empty .call{ background:var(--acc); }

        @media (max-width:575.98px){
            .apxp-hero{ padding-bottom:92px; }
            .apxp-body{ margin-top:-70px; }
            .apxp-row{ padding:13px 4px; gap:10px; }
            .apxp-row .t{ font-size:18px; min-width:88px; }
            .apxp-row .pick{ opacity:1; }
            .apxp-foot{ gap:12px; }
            .apxp-cta{ margin-inline-start:0; width:100%; }
        }
    </style>

    @php
        $companyName = tenant_cache('company_name', '') ?: config('app.name');
        $companyLogo = tenant_cache('logo', '');
        $companyDesc = tenant_cache('company_description', '');
        $companyInitial = \Illuminate\Support\Str::of($companyName)->substr(0, 1)->upper();
    @endphp

    {{-- Vue mounts here and replaces the markup inside. Every slot for the
         appointment window arrives in one payload, so choosing a day or a time is
         instant and only the actual appointment touches the network.

         The skeleton below is the same masthead the component renders, so the
         customer sees the branded hero on first paint instead of a blank page
         while the payload is in flight. --}}
    <div class="apxp"
        id="property-appointment"
        data-data-url="{{ route('property_appointment::public.data', $token) }}"
        data-book-url="{{ route('property_appointment::public.book', $token) }}"
        data-csrf="{{ csrf_token() }}"
        data-company-name="{{ $companyName }}"
        data-company-logo="{{ $companyLogo }}"
        data-company-tagline="{{ $companyDesc ?: 'Property appointments' }}">
        <header class="apxp-hero">
            <div class="apxp-inner">
                <div class="apxp-mast">
                    <span class="bm">
                        @if (filled($companyLogo))
                            <img src="{{ $companyLogo }}" alt="{{ $companyName }}">
                        @else
                            {{ $companyInitial }}
                        @endif
                    </span>
                    <div>
                        <div class="nm">{{ $companyName }}</div>
                        <div class="ds">{{ $companyDesc ?: 'Property appointments' }}</div>
                    </div>
                    <span class="lock"><i class="fa fa-lock"></i> Personal link</span>
                </div>
                <div class="apxp-sk sm"></div>
                <div class="apxp-sk lg"></div>
                <div class="apxp-sk md"></div>
            </div>
        </header>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/property-appointment.js')
@endpush
