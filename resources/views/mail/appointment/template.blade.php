<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $companyName }}</title>
</head>
{{--
    "Editorial" email wrapper.

    Email clients do not reliably support external stylesheets, custom
    properties, flexbox or dark mode — Gmail and Outlook rewrite colours
    unpredictably — so this is deliberately light-only, table-based and fully
    inline-styled. It is the one view in the app that does not follow the .apx
    system, because it cannot.

    The tenant writes only the wording; this wrapper supplies the branding,
    logo, rules and typography, so a tenant can never break the layout.

    The body is tenant-authored HTML already sanitised by App\Support\RichText
    in EmailTemplateRenderer, so it is echoed unescaped on purpose.
--}}
<body style="margin:0;padding:0;background:#f4f1ec;-webkit-font-smoothing:antialiased;">

    {{-- Preheader: the grey line mail clients show next to the subject. --}}
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;height:0;width:0;">
        {{ $preheader ?? '' }}
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="background:#f4f1ec;padding:32px 12px;">
        <tr>
            <td align="center">

                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
                    style="max-width:600px;width:100%;background:#fffdf9;border:1px solid #ece5da;">

                    {{-- ── masthead ──────────────────────────────────── --}}
                    <tr>
                        <td align="center" style="padding:36px 40px 0;">
                            @if (filled($companyLogo))
                                <img src="{{ $companyLogo }}" alt="{{ $companyName }}" width="52"
                                    style="display:block;width:52px;height:52px;border:0;outline:none;
                                           border-radius:50%;object-fit:contain;background:#ffffff;">
                            @else
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td width="52" height="52" align="center" valign="middle"
                                            style="width:52px;height:52px;border-radius:50%;background:{{ $accent }};
                                                   color:#ffffff;font-family:Georgia,'Times New Roman',serif;
                                                   font-size:22px;font-weight:700;">
                                            {{ \Illuminate\Support\Str::of($companyName)->substr(0, 1)->upper() }}
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td align="center"
                            style="padding:16px 40px 0;font-family:'Segoe UI',Helvetica,Arial,sans-serif;
                                   font-size:10px;letter-spacing:.28em;text-transform:uppercase;
                                   color:#a89880;font-weight:700;">
                            {{ $companyName }}
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:18px 40px 0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr><td width="44" height="2" style="width:44px;height:2px;background:{{ $accent }};font-size:0;line-height:0;">&nbsp;</td></tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ── the tenant's wording ──────────────────────── --}}
                    <tr>
                        <td style="padding:26px 40px 34px;font-family:Georgia,'Times New Roman',serif;
                                   font-size:15px;line-height:1.8;color:#4a4238;">
                            {!! $bodyHtml !!}
                        </td>
                    </tr>

                    {{-- ── footer ────────────────────────────────────── --}}
                    <tr>
                        <td align="center"
                            style="padding:22px 40px 32px;border-top:1px solid #ece5da;
                                   font-family:'Segoe UI',Helvetica,Arial,sans-serif;
                                   font-size:11px;line-height:1.9;color:#a89880;">
                            <span style="color:#8c7f6d;font-weight:600;">{{ $companyName }}</span>
                            @if (filled($companyPhone))
                                &nbsp;&middot;&nbsp;<a href="tel:{{ $companyPhone }}" style="color:#a89880;text-decoration:none;">{{ $companyPhone }}</a>
                            @endif
                            @if (filled($companyEmail))
                                &nbsp;&middot;&nbsp;<a href="mailto:{{ $companyEmail }}" style="color:#a89880;text-decoration:none;">{{ $companyEmail }}</a>
                            @endif
                            <br>
                            You received this because you enquired about a property with us.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>
</body>
</html>
