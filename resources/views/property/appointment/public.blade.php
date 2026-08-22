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

        /* ── picker: calendar + suggested times + typed window ─────── */
        .apxp-split{ display:grid; grid-template-columns:296px 1fr; }
        .apxp-cal{ padding:16px 20px 20px; border-inline-end:1px solid var(--ln); }
        .apxp-cal .h{ display:flex; align-items:center; justify-content:space-between; margin-bottom:11px; }
        .apxp-cal .h .m{ font-family:var(--serif); font-size:17px; }
        .apxp-cal .h button{
            width:27px; height:27px; border-radius:50%; cursor:pointer; color:var(--ink-2);
            border:1px solid var(--ln); background:var(--sf);
        }
        .apxp-cal .h button:disabled{ opacity:.32; cursor:not-allowed; }
        .apxp-dow{ display:grid; grid-template-columns:repeat(7,1fr); gap:2px; margin-bottom:4px; }
        .apxp-dow span{ text-align:center; font-size:9px; font-weight:800; letter-spacing:.1em; color:var(--mut); }
        .apxp-days{ display:grid; grid-template-columns:repeat(7,1fr); gap:2px; }
        .apxp-dy{
            aspect-ratio:1; border:0; background:none; cursor:pointer; border-radius:50%;
            font-family:var(--serif); font-size:14.5px; color:var(--ink);
            display:flex; flex-direction:column; align-items:center; justify-content:center; gap:1px;
        }
        .apxp-dy:hover:not(:disabled){ background:var(--sf-2); }
        .apxp-dy:disabled{ color:var(--mut); opacity:.34; cursor:not-allowed; }
        .apxp-dy.sel{ background:var(--ink); color:#fff; }
        [data-bs-theme="dark"] .apxp-dy.sel{ background:var(--acc); }
        .apxp-dy.today{ box-shadow:inset 0 0 0 1px var(--acc); }
        .apxp-dy .pip{ width:4px; height:4px; border-radius:50%; background:var(--acc); }
        .apxp-dy.sel .pip{ background:#fff; }
        .apxp-dy:disabled .pip{ visibility:hidden; }
        /* A company closure, struck through so a shut day is visibly different
           from a day that is merely fully booked. */
        .apxp-dy.hol{ text-decoration:line-through; opacity:.5; }
        .apxp-legend{ margin-top:13px; font-size:10.5px; color:var(--mut); line-height:1.75; }
        .apxp-legend i{ color:var(--acc); width:14px; }

        .apxp-pane{ padding:18px clamp(18px,2.4vw,24px) 22px; min-width:0; }
        .apxp-seldate{ font-family:var(--serif); font-size:clamp(21px,3vw,25px); letter-spacing:-.01em; }
        .apxp-selsub{ font-size:11.5px; color:var(--mut); margin-top:3px; }

        .apxp-sechead{ margin:18px 0 10px; }
        .apxp-sechead .h{ font-size:12.5px; font-weight:800; letter-spacing:-.01em; }
        .apxp-sechead .s{ font-size:11px; color:var(--mut); margin-top:2px; }

        .apxp-slots{ display:grid; grid-template-columns:repeat(auto-fill,minmax(104px,1fr)); gap:7px; }
        .apxp-slot{
            font-family:var(--serif); font-size:16px; line-height:1.15; color:var(--ink); cursor:pointer;
            background:var(--sf); border:1px solid var(--ln); border-radius:3px; padding:11px 6px; text-align:center;
        }
        .apxp-slot small{
            display:block; font-family:inherit; font-size:9.5px; font-weight:800; letter-spacing:.1em;
            text-transform:uppercase; color:var(--mut); margin-top:3px;
        }
        .apxp-slot:hover:not(:disabled){ border-color:var(--acc); color:var(--acc); }
        .apxp-slot.sel{ background:var(--ink); color:#fff; border-color:var(--ink); }
        .apxp-slot.sel small{ color:rgba(255,255,255,.62); }
        [data-bs-theme="dark"] .apxp-slot.sel{ background:var(--acc); border-color:var(--acc); }
        .apxp-slot:disabled{ opacity:.36; cursor:not-allowed; text-decoration:line-through; }
        .apxp-slot:focus-visible{ outline:2px solid var(--acc); outline-offset:-2px; }

        .apxp-or{ display:flex; align-items:center; gap:12px; margin:20px 0 15px; }
        .apxp-or::before, .apxp-or::after{ content:""; height:1px; background:var(--ln); flex:1; }
        .apxp-or span{ font-size:9.5px; font-weight:800; letter-spacing:.16em; text-transform:uppercase; color:var(--mut); }

        .apxp-when{ display:grid; grid-template-columns:1fr 1fr 1fr auto; gap:12px; align-items:end; }
        .apxp-fl{
            display:block; margin-bottom:6px; font-size:9.5px; font-weight:800;
            letter-spacing:.14em; text-transform:uppercase; color:var(--mut);
        }
        .apxp-fi{
            width:100%; font-family:var(--serif); font-size:21px; color:var(--ink);
            background:var(--sf); border:1px solid var(--ln); border-radius:3px; padding:10px 11px;
        }
        .apxp-fi.sm{ font-family:inherit; font-size:13px; font-weight:650; padding:11px; }
        .apxp-fi:focus{ outline:none; border-color:var(--acc); box-shadow:0 0 0 3px color-mix(in srgb, var(--acc), transparent 86%); }
        .apxp-dur{
            display:inline-flex; align-items:center; gap:7px; white-space:nowrap;
            font-size:11px; font-weight:800; letter-spacing:.1em; text-transform:uppercase;
            padding:11px 14px; border-radius:999px;
            background:color-mix(in srgb, var(--acc), transparent 90%); color:var(--acc);
        }
        .apxp-dur.bad{ background:color-mix(in srgb, var(--bs-danger), transparent 90%); color:var(--bs-danger); }

        .apxp-verdict{
            display:flex; gap:10px; align-items:flex-start; margin-top:14px; padding:11px 13px; border-radius:3px;
            font-size:11.5px; line-height:1.55; color:var(--ink-2);
            background:color-mix(in srgb, var(--acc), transparent 93%);
            border:1px solid color-mix(in srgb, var(--acc), transparent 82%);
        }
        .apxp-verdict i{ margin-top:2px; flex:none; color:var(--acc); }
        .apxp-verdict.ok{ background:color-mix(in srgb, var(--bs-success), transparent 92%); border-color:color-mix(in srgb, var(--bs-success), transparent 80%); }
        .apxp-verdict.ok i{ color:var(--bs-success); }
        .apxp-verdict.warn{ background:color-mix(in srgb, var(--bs-warning), transparent 90%); border-color:color-mix(in srgb, var(--bs-warning), transparent 78%); }
        .apxp-verdict.warn i{ color:var(--bs-warning); }
        .apxp-verdict.bad{ background:color-mix(in srgb, var(--bs-danger), transparent 92%); border-color:color-mix(in srgb, var(--bs-danger), transparent 80%); }
        .apxp-verdict.bad i{ color:var(--bs-danger); }

        .apxp-nowline{
            display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
            margin-top:14px; padding-top:13px; border-top:1px dashed var(--ln);
            font-size:11.5px; color:var(--mut);
        }
        .apxp-nowline b{ color:var(--ink); font-weight:750; }
        .apxp-mini{
            font-family:inherit; cursor:pointer; font-size:11px; font-weight:750; padding:7px 12px;
            border-radius:999px; background:var(--sf-2); border:1px solid var(--ln); color:var(--ink-2);
        }
        .apxp-mini:hover{ border-color:var(--acc); color:var(--acc); }

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

        /* ── the picker on smaller screens ─────────────────────────
           The calendar stops being a sidebar and becomes the first thing on the
           page; suggested times keep three to a row on a phone, and the typed
           window splits into date-then-times so both time fields stay side by
           side where thumbs expect them. */
        @media (max-width:900px){
            .apxp-split{ grid-template-columns:1fr; }
            .apxp-cal{ border-inline-end:0; border-bottom:1px solid var(--ln); }
        }
        @media (max-width:700px){
            .apxp-when{ grid-template-columns:1fr 1fr; }
            .apxp-when > .apxp-datefield{ grid-column:1 / -1; }
            .apxp-when > .apxp-durwrap{ grid-column:1 / -1; }
            .apxp-dur{ width:100%; justify-content:center; }
        }
        @media (max-width:575.98px){
            .apxp-hero{ padding-bottom:92px; }
            .apxp-body{ margin-top:-70px; }
            .apxp-cal{ padding:14px 14px 18px; }
            .apxp-pane{ padding:16px 14px 20px; }
            .apxp-slots{ grid-template-columns:repeat(3,1fr); gap:6px; }
            .apxp-slot{ font-size:14.5px; padding:10px 3px; }
            .apxp-slot small{ font-size:8.5px; letter-spacing:.06em; }
            .apxp-fi{ font-size:18px; }
            .apxp-foot{ gap:12px; }
            .apxp-cta{ margin-inline-start:0; width:100%; }
        }
        @media (max-width:359.98px){
            .apxp-slots{ grid-template-columns:repeat(2,1fr); }
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
