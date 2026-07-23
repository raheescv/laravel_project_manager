<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Customer KYC Form</title>
    <style>
        @page {
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
        }

        .kyc {
            color: #2b3140;
            /* DejaVu Sans first: DomPDF embeds it and it carries the ✓ glyph */
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 8.5px;
            line-height: 1.4;
        }

        .kyc table {
            width: 100%;
            border-collapse: collapse;
        }

        .wrap {
            padding: 0 30px 26px;
        }

        /* ── Header band ────────────────────────────────────────── */
        .topbar {
            width: 100%;
            background: #14264a;
            padding: 0;
        }

        .topbar td {
            padding: 20px 30px 16px;
            vertical-align: middle;
        }

        .topbar .brand-logo {
            background: #ffffff;
            padding: 4px 6px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 7px;
        }

        .topbar .brand-logo img {
            max-height: 34px;
            max-width: 160px;
            display: block;
        }

        .topbar .brand {
            font-size: 16px;
            font-weight: bold;
            color: #ffffff;
        }

        .topbar .brand-sub {
            font-size: 7.5px;
            color: #aab4c8;
            margin-top: 3px;
        }

        .topbar .doc-badge {
            text-align: right;
        }

        .topbar .doc-badge .t1 {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 3px;
            color: #ffffff;
        }

        .topbar .doc-badge .t2 {
            font-size: 7px;
            letter-spacing: 1px;
            color: #c8a04a;
            margin-top: 3px;
            text-transform: uppercase;
        }

        .topbar .doc-badge .t3 {
            display: inline-block;
            margin-top: 7px;
            padding: 3px 12px;
            background: #c8a04a;
            color: #14264a;
            font-size: 9px;
            font-weight: bold;
            border-radius: 12px;
        }

        .goldrule {
            height: 4px;
            background: #c8a04a;
            font-size: 0;
            line-height: 0;
        }

        /* ── Client hero ────────────────────────────────────────── */
        .hero {
            margin: 18px 0 14px;
            border: 1px solid #e3e8f0;
            border-left: 4px solid #c8a04a;
            border-radius: 5px;
            background: #f7f9fc;
        }

        .hero td {
            padding: 12px 14px;
            vertical-align: middle;
        }

        .hero .name {
            font-size: 15px;
            font-weight: bold;
            color: #14264a;
        }

        .hero .name-sub {
            font-size: 7.5px;
            color: #6b7385;
            margin-top: 2px;
        }

        .chip {
            display: inline-block;
            border: 1px solid #d9e0ec;
            background: #ffffff;
            border-radius: 12px;
            padding: 3px 10px;
            margin-left: 5px;
            font-size: 7.5px;
            color: #3a4258;
        }

        .chip b {
            color: #14264a;
        }

        /* ── Sections ───────────────────────────────────────────── */
        .section-head {
            background: #14264a;
            color: #fff;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: .6px;
            text-transform: uppercase;
            padding: 7px 12px;
            border-left: 3px solid #c8a04a;
            border-radius: 3px 3px 0 0;
        }

        .section-head .num {
            color: #c8a04a;
            font-weight: bold;
            margin-right: 8px;
            padding-right: 8px;
            border-right: 1px solid #3a4a6b;
        }

        .data-table {
            table-layout: fixed;
            margin-bottom: 13px;
            border: 1px solid #e3e8f0;
            border-top: none;
            border-radius: 0 0 4px 4px;
        }

        .data-table td {
            border: 1px solid #eef1f6;
            padding: 6px 10px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table td.label {
            width: 18%;
            background: #eef2f8;
            color: #52607a;
            font-weight: bold;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .data-table td.value {
            width: 32%;
            color: #262c3a;
        }

        /* ── Client type chips ──────────────────────────────────── */
        .chip-row td {
            border: 1px solid #eef1f6;
            padding: 8px 11px;
        }

        .box {
            display: inline-block;
            width: 11px;
            height: 11px;
            border: 1.5px solid #14264a;
            text-align: center;
            line-height: 10px;
            font-size: 9px;
            color: #14264a;
            margin-right: 5px;
            border-radius: 2px;
        }

        .box.checked {
            background: #14264a;
            color: #fff;
            font-weight: bold;
        }

        /* ── Doc / checklist tables ─────────────────────────────── */
        .doc-table {
            border: 1px solid #e3e8f0;
            border-top: none;
            border-radius: 0 0 4px 4px;
            margin-bottom: 13px;
        }

        .doc-table th {
            background: #2b3958;
            color: #fff;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .3px;
            padding: 6px 10px;
            border: 1px solid #2b3958;
            text-align: left;
        }

        .doc-table td {
            border: 1px solid #eef1f6;
            padding: 6px 10px;
        }

        .doc-table tr:nth-child(even) td {
            background: #f7f9fc;
        }

        /* ── F · Document checklist (premium) ───────────────────── */
        .chk-summary {
            width: 100%;
            background: #f7f9fc;
            border: 1px solid #e3e8f0;
            border-top: none;
            border-collapse: collapse;
        }

        .chk-summary td {
            padding: 6px 12px;
            vertical-align: middle;
            border: none;
        }

        .chk-summary .lbl {
            font-size: 8px;
            color: #52607a;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .chk-summary .cnt {
            font-size: 8.5px;
            color: #14264a;
            font-weight: bold;
        }

        .chk-bar {
            width: 100%;
            border-collapse: collapse;
        }

        .chk-bar td {
            height: 8px;
            padding: 0;
            font-size: 1px;
            line-height: 1px;
        }

        .chk-table {
            border: 1px solid #e3e8f0;
            border-top: none;
            border-radius: 0 0 4px 4px;
            margin-bottom: 13px;
        }

        .chk-table th {
            background: #2b3958;
            color: #fff;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .3px;
            padding: 6px 10px;
            border: 1px solid #2b3958;
            text-align: left;
        }

        .chk-table td {
            border: 1px solid #eef1f6;
            padding: 7px 10px;
            vertical-align: middle;
        }

        .chk-table tr:nth-child(even) td {
            background: #f7f9fc;
        }

        .chk-idx {
            text-align: center;
            color: #8794ab;
            font-weight: bold;
        }

        .chk-name {
            color: #14264a;
            font-weight: bold;
        }

        .chk-pill {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 9px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .chk-pill.ok {
            background: #e6f4ea;
            color: #1e7e45;
            border: 1px solid #bfe3cb;
        }

        .chk-pill.pending {
            background: #fdf1dd;
            color: #9a6b16;
            border: 1px solid #ecd4a3;
        }

        .chk-ref {
            color: #52607a;
        }

        .chk-muted {
            color: #aab3c5;
        }

        /* ── Signature ──────────────────────────────────────────── */
        .declaration {
            background: #f7f9fc;
            border: 1px solid #e3e8f0;
            border-radius: 4px;
            padding: 9px 12px;
            font-style: italic;
            color: #52607a;
            margin-bottom: 12px;
        }

        .sign-table td {
            padding: 22px 10px 5px;
            vertical-align: bottom;
            font-size: 7.5px;
            color: #6b7385;
        }

        .sign-line {
            border-top: 1px solid #99a2b3;
            padding-top: 3px;
        }

        .foot-note {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px solid #e3e8f0;
            font-size: 6.8px;
            color: #99a2b3;
            text-align: center;
        }
    </style>
</head>

<body>
    @php
        $rentout = $rentout ?? null;
        $projectReference = '';
        if ($rentout) {
            $unitNumber = $rentout->property->number ?? null;
            $projectParts = array_filter([
                $rentout->group->name ?? null,
                $rentout->building->name ?? null,
                $unitNumber ? 'Unit ' . $unitNumber : null,
            ]);
            $projectReference = implode(' / ', $projectParts);
        }
        $clientFileNo = $rentout ? $rentout->agreement_no : $customer->id;
        $handlerName = $rentout ? $rentout->salesman->name ?? (auth()->user()->name ?? '') : auth()->user()->name ?? '';
        $isCompany = !empty($customer->company) || !empty($customer->cr_number);
        $documentTypes = $documentTypes ?? collect();
    @endphp
    <div class="kyc">
        {{-- Header band --}}
        <table class="topbar">
            <tr>
                <td style="width:15%;">
                    @if (!empty($companyLogo))
                        <div class="brand-logo"><img src="{{ $companyLogo }}" alt="Logo"></div>
                    @endif
                    <div class="brand">{{ $companyInfo['companyName'] }}</div>
                    <div class="brand-sub">
                        {{ $companyInfo['companyAddress'] }}@if ($companyInfo['companyPhone'])
                            &nbsp;•&nbsp; {{ $companyInfo['companyPhone'] }}
                        @endif
                        @if ($companyInfo['companyEmail'])
                            &nbsp;•&nbsp; {{ $companyInfo['companyEmail'] }}
                        @endif
                    </div>
                </td>
                <td class="doc-badge" style="width:85%;">
                    <div class="t1">{{ $customer->name }} KYC FORM</div>
                    <div class="t2">Know Your Customer &mdash; Client Record</div>
                </td>
            </tr>
        </table>
        <div class="goldrule">&nbsp;</div>

        <div class="wrap">
            {{-- A. File Information --}}
            <div class="section-head"><span class="num">A</span> File Information</div>
            <table class="data-table">
                <tr>
                    <td style="width:18%;" class="label">Date</td>
                    <td style="width:18%;" class="value">{{ date('d-m-Y') }}</td>
                    <td style="width:20%" class="label">Sales Consultant</td>
                    <td style="width:75%;" class="value" @unless ($rentout) colspan="3" @endunless>{{ $handlerName ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Client File No.</td>
                    <td class="value">{{ $clientFileNo }}</td>
                    @if ($rentout)
                        <td class="label">Project / Unit</td>
                        <td class="value">{{ $projectReference ?: '—' }}</td>
                    @endif
                </tr>
            </table>

            {{-- B. Client Type --}}
            <div class="section-head"><span class="num">B</span> Client Type</div>
            <table class="data-table">
                <tr class="chip-row">
                    <td style="width:50%;">
                        <span class="box">
                            @if (!$isCompany)
                                &#10003;
                            @endif
                        </span> Individual Client
                    </td>
                    <td style="width:50%;">
                        <span class="box">
                            @if ($isCompany)
                                &#10003;
                            @endif
                        </span> Corporate / Company
                    </td>
                </tr>
            </table>

            {{-- C. Client Details --}}
            <div class="section-head"><span class="num">C</span> Client Details</div>
            <table class="data-table">
                <tr>
                    <td class="label">Nationality</td>
                    <td class="value">{{ $customer->nationality ?: '—' }}</td>
                    <td class="label">Date of Birth</td>
                    <td class="value">{{ $customer->dob ? systemDate($customer->dob) : '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Passport No.</td>
                    <td class="value">{{ $customer->passport_no ?: '—' }}</td>
                    <td class="label">National / QID No.</td>
                    <td class="value">{{ $customer->id_no ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="label">ID Expiry Date</td>
                    <td class="value">{{ $customer->id_expiry_date ? systemDate($customer->id_expiry_date) : '—' }}</td>
                    <td class="label">Marital Status</td>
                    <td class="value">{{ $customer->marital_status ?: '—' }}</td>
                </tr>
            </table>

            {{-- D. Contact & Address --}}
            <div class="section-head"><span class="num">D</span> Contact &amp; Address Details</div>
            <table class="data-table">
                <tr>
                    <td class="label">Mobile No.</td>
                    <td class="value">{{ $customer->mobile ?: '—' }}</td>
                    <td class="label">WhatsApp No.</td>
                    <td class="value">{{ $customer->whatsapp_mobile ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Email Address</td>
                    <td class="value">{{ $customer->email ?: '—' }}</td>
                    <td class="label">Emergency Contact</td>
                    <td class="value">{{ $customer->emergency_contact_no ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="label">P.O. Box</td>
                    <td class="value">{{ $customer->po_box ?: '—' }}</td>
                    <td class="label">Contact Person</td>
                    <td class="value">
                        {{ $customer->contact_person ?: '—' }}@if ($customer->contact_person_mobile)
                            ({{ $customer->contact_person_mobile }})
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label">Residential Address</td>
                    <td class="value" colspan="3">{{ $customer->residential_address ?: '—' }}</td>
                </tr>
            </table>

            {{-- E. Employment / Business --}}
            <div class="section-head"><span class="num">E</span> Employment / Business Information</div>
            <table class="data-table">
                <tr>
                    <td class="label">Employer / Company</td>
                    <td class="value">{{ $customer->company ?: '—' }}</td>
                    <td class="label">Monthly Income</td>
                    <td class="value">{{ $customer->monthly_income ? currency($customer->monthly_income) : '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Job Title</td>
                    <td class="value">{{ $customer->job ?: '—' }}</td>
                    <td class="label">Position / Nature</td>
                    <td class="value">{{ $customer->position_nature_of_business ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Sponsor Name</td>
                    <td class="value">{{ $customer->sponsor_name ?: '—' }}</td>
                    <td class="label">Occupation</td>
                    <td class="value">{{ $customer->occupation ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Employer Address</td>
                    <td class="value" colspan="3">{{ $customer->employer_address ?: '—' }}</td>
                </tr>
            </table>

            @if ($rentout)
                <table class="chk-table">
                    <thead>
                        <tr>
                            <th style="width:6%; text-align:center;">#</th>
                            <th style="width:25%;">Required Document</th>
                            <th style="width:13%;">Status</th>
                            <th style="width:44%;">Reference / Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($documentTypes as $doc)
                            @php
                                $subs = $submittedDocuments[$doc->id] ?? collect();
                                $submitted = $subs->isNotEmpty();
                                $subLabel = $subs
                                    ->map(fn ($d) => $d->name . ($d->remarks ? ' — ' . $d->remarks : ''))
                                    ->implode('; ');
                            @endphp
                            <tr>
                                <td class="chk-idx">{{ $loop->iteration }}</td>
                                <td class="chk-name">{{ $doc->name }}</td>
                                <td>
                                    @if ($submitted)
                                        <span class="chk-pill ok">&#10003; Submitted</span>
                                    @else
                                        <span class="chk-pill pending">Pending</span>
                                    @endif
                                </td>
                                <td class="chk-ref">
                                    {{ $submitted ? $subLabel : '' }}<span class="chk-muted">{{ $submitted ? '' : '—' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="chk-muted" style="text-align:center; font-style:italic;">
                                    No mandatory document types configured for this booking.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
            @unless ($rentout)
                {{-- G. Company Documents (customer-level KYC only) --}}
                <div class="section-head"><span class="num">G</span> Company Documents</div>
                <table class="doc-table">
                    <thead>
                        <tr>
                            <th style="width:28%;">Document</th>
                            <th style="width:24%;">Number</th>
                            <th style="width:24%;">Issue Date</th>
                            <th style="width:24%;">Expiry Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Commercial Registration (CR)</td>
                            <td>{{ $customer->cr_number ?: '—' }}</td>
                            <td>{{ $customer->cr_issue_date ? systemDate($customer->cr_issue_date) : '—' }}</td>
                            <td>{{ $customer->cr_expiry_date ? systemDate($customer->cr_expiry_date) : '—' }}</td>
                        </tr>
                        <tr>
                            <td>Company Permit (CP)</td>
                            <td>{{ $customer->cp_number ?: '—' }}</td>
                            <td>{{ $customer->cp_issue_date ? systemDate($customer->cp_issue_date) : '—' }}</td>
                            <td>{{ $customer->cp_expiry_date ? systemDate($customer->cp_expiry_date) : '—' }}</td>
                        </tr>
                        <tr>
                            <td>Establishment ID (EID)</td>
                            <td>{{ $customer->eid_number ?: '—' }}</td>
                            <td>{{ $customer->eid_issue_date ? systemDate($customer->eid_issue_date) : '—' }}</td>
                            <td>{{ $customer->eid_expiry_date ? systemDate($customer->eid_expiry_date) : '—' }}</td>
                        </tr>
                        <tr>
                            <td>Tax Card</td>
                            <td>{{ $customer->tax_card_no ?: '—' }}</td>
                            <td>{{ $customer->tax_card_issue_date ? systemDate($customer->tax_card_issue_date) : '—' }}</td>
                            <td>—</td>
                        </tr>
                    </tbody>
                </table>
            @endunless

            {{-- Declaration --}}
            <div class="section-head"><span class="num">{{ $rentout ? 'G' : 'H' }}</span> Client Declaration &amp; Signature</div>
            <div class="declaration">
                I hereby declare that the information provided above is true and correct to the best of my knowledge and belief.
            </div>
            <table class="sign-table">
                <tr>
                    <td style="width:50%;">
                        <div class="sign-line">Client Signature &nbsp;&mdash;&nbsp; {{ $customer->name }}</div>
                    </td>
                    <td style="width:22%;">
                        <div class="sign-line">Date</div>
                    </td>
                    <td style="width:28%;">
                        <div class="sign-line">Handled By &nbsp;&mdash;&nbsp; {{ $handlerName ?: '—' }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>
