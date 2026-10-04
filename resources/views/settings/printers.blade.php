{{--
    Settings → Printers. Printers belong to the computer, so the choices here are
    saved in this browser by <x-qz-print /> (included on the settings page), not
    with the tenant's settings. The data-qz-* / data-{role}-printer-* hooks are
    driven by that component; keep them when restyling.
--}}
@php
    $qzPrinterRoles = [
        ['role' => 'label', 'icon' => 'fa-barcode', 'tone' => '#7c4fd6', 'title' => 'Barcode labels', 'hint' => 'Barcode stickers and jewellery tags', 'examples' => 'TSC, Zebra'],
        ['role' => 'receipt', 'icon' => 'fa-file-text-o', 'tone' => '#d4931c', 'title' => 'Sale invoices', 'hint' => 'Receipts after a sale, and reprints', 'examples' => 'Epson TM, thermal'],
    ];
@endphp

<div class="scx prx">
    @include('livewire.settings.partials.panel-styles')

    <style>
        /* .prx — Printers tab on top of .scx. Status colours follow the QZ badge's own class. */
        .prx-status {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .3rem .35rem .3rem .75rem;
            border: 1px solid var(--scx-line-soft);
            border-radius: 999px;
            background: var(--scx-surface);
        }

        .prx-status .badge {
            padding: .35em .7em;
            border-radius: 999px;
            font-weight: 600;
        }

        .prx-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--bs-tertiary-color);
        }

        .prx-status:has(.text-bg-success) .prx-status-dot {
            background: #2f9e62;
            box-shadow: 0 0 0 3px color-mix(in srgb, #2f9e62 25%, transparent);
        }

        .prx-status:has(.text-bg-danger) .prx-status-dot {
            background: #d94848;
            box-shadow: 0 0 0 3px color-mix(in srgb, #d94848 25%, transparent);
        }

        .prx-status .btn {
            width: 28px;
            height: 28px;
            padding: 0;
            border-radius: 50%;
        }

        /* Setup: three steps, each with its own action */
        .prx-steps {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
            margin: 0;
            padding: 0;
            list-style: none;
            counter-reset: prx-step;
        }

        .prx-step {
            counter-increment: prx-step;
            display: flex;
            flex-direction: column;
            gap: .35rem;
            padding: .9rem 1rem 1rem;
            border: 1px solid var(--scx-line-soft);
            border-radius: 14px;
        }

        .prx-step-num::before {
            content: "Step " counter(prx-step);
        }

        .prx-step-num {
            font-size: .66rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--scx-acc);
        }

        .prx-step h6 {
            margin: 0;
            font-size: .88rem;
            font-weight: 700;
            color: var(--bs-emphasis-color);
        }

        .prx-step p {
            margin: 0;
            color: var(--bs-secondary-color);
            font-size: .76rem;
            line-height: 1.45;
        }

        .prx-step .prx-step-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .4rem;
            margin-top: auto;
            padding-top: .5rem;
        }

        .prx-where summary {
            font-size: .74rem;
            color: var(--scx-acc);
            cursor: pointer;
        }

        .prx-where ul {
            margin: .35rem 0 0;
            padding-inline-start: 1rem;
            font-size: .72rem;
            color: var(--bs-secondary-color);
        }

        /* Printer cards */
        .prx-printers {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .85rem;
        }

        .prx-printer {
            display: flex;
            flex-direction: column;
            gap: .9rem;
            padding: 1rem;
            border: 1px solid var(--scx-line-soft);
            border-radius: 16px;
            transition: border-color .15s, background .15s, box-shadow .15s;
        }

        .prx-printer:has([data-qz-enable]:checked) {
            border-color: color-mix(in srgb, var(--scx-acc) 40%, var(--scx-line-soft));
            box-shadow: 0 0 0 3px var(--scx-acc-soft);
        }

        .prx-printer-top {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .prx-printer-top .scx-ic {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            font-size: 1.05rem;
        }

        /* Print window | Direct to printer — one checkbox, two visible sides */
        .prx-mode {
            position: relative;
            display: grid;
            grid-template-columns: 1fr 1fr;
            margin: 0;
            padding: 4px;
            border-radius: 12px;
            background: color-mix(in srgb, var(--bs-emphasis-color) 5%, var(--scx-surface));
            cursor: pointer;
            user-select: none;
        }

        .prx-mode input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .prx-mode:has(input:focus-visible) {
            outline: 2px solid var(--scx-acc);
            outline-offset: 2px;
        }

        .prx-mode span {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            padding: .45rem .5rem;
            border-radius: 9px;
            font-weight: 600;
            font-size: .78rem;
            color: var(--bs-secondary-color);
            transition: background .15s, color .15s, box-shadow .15s;
        }

        .prx-mode:not(:has(:checked)) .prx-mode-off,
        .prx-mode:has(:checked) .prx-mode-on {
            background: var(--scx-surface);
            color: var(--bs-emphasis-color);
            box-shadow: 0 1px 2px rgba(16, 24, 40, .1), 0 4px 10px -6px rgba(16, 24, 40, .3);
        }

        .prx-mode:has(:checked) .prx-mode-on {
            background: var(--scx-acc);
            color: #fff;
        }

        /* The printer slot: the whole row opens the picker */
        .prx-slot {
            display: flex;
            align-items: center;
            gap: .7rem;
            width: 100%;
            padding: .7rem .85rem;
            border: 1px dashed var(--scx-line);
            border-radius: 12px;
            background: transparent;
            color: var(--bs-emphasis-color);
            text-align: start;
            transition: border-color .15s, background .15s;
        }

        .prx-slot:hover {
            border-style: solid;
            border-color: var(--scx-acc);
            background: var(--scx-acc-soft);
        }

        .prx-slot-ic {
            display: grid;
            place-items: center;
            flex: none;
            width: 32px;
            height: 32px;
            border-radius: 9px;
            background: color-mix(in srgb, var(--bs-emphasis-color) 6%, transparent);
            color: var(--bs-secondary-color);
        }

        .prx-printer:has(:checked) .prx-slot-ic {
            background: var(--scx-acc-soft);
            color: var(--scx-acc);
        }

        .prx-slot-text {
            flex: 1;
            min-width: 0;
            line-height: 1.3;
        }

        .prx-slot-text small {
            display: block;
            font-size: .68rem;
            color: var(--bs-secondary-color);
        }

        .prx-slot-text span {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-weight: 600;
        }

        .prx-slot-go {
            font-size: .74rem;
            font-weight: 600;
            color: var(--scx-acc);
            white-space: nowrap;
        }

        .prx-printer:not(:has(:checked)) .prx-slot {
            opacity: .65;
        }

        .prx-tips {
            display: grid;
            gap: .5rem;
            margin: 0;
            padding: 0;
            list-style: none;
            font-size: .76rem;
            color: var(--bs-secondary-color);
        }

        .prx-tips li {
            display: flex;
            gap: .55rem;
        }

        .prx-tips i {
            margin-top: .2rem;
            color: var(--scx-acc);
        }

        @media (max-width: 991.98px) {
            .prx-steps {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767.98px) {
            .prx-printers {
                grid-template-columns: 1fr;
            }
        }
    </style>

    {{-- 1 · This computer: QZ Tray setup, each step with its own button --}}
    <section class="scx-section">
        <div class="scx-head flex-wrap">
            <span class="scx-ic"><i class="fa fa-desktop"></i></span>
            <div class="flex-grow-1" style="min-width: 12rem;">
                <h6>Set up this computer</h6>
                <p>QZ Tray lets this computer print straight to the printer, without the print window. Saved on this computer only.</p>
            </div>
            <div class="scx-head-end prx-status">
                <span class="prx-status-dot"></span>
                <span class="badge text-bg-secondary" data-qz-connection>Not checked</span>
                <button type="button" class="btn btn-light border" data-qz-connection-refresh title="Check the connection again" aria-label="Check the connection again">
                    <i class="fa fa-refresh"></i>
                </button>
            </div>
        </div>
        <div class="scx-body">
            <ol class="prx-steps">
                <li class="prx-step">
                    <span class="prx-step-num"></span>
                    <h6>Install QZ Tray</h6>
                    <p>Install it on the computer the printers are plugged into and open it. It sits in the menu bar (Mac) or system tray (Windows).</p>
                    <div class="prx-step-actions">
                        <a href="https://qz.io/download/" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
                            <i class="fa fa-download me-1"></i> Download QZ Tray
                        </a>
                    </div>
                </li>
                <li class="prx-step">
                    <span class="prx-step-num"></span>
                    <h6>Trust this site</h6>
                    <p>Download <code>override.crt</code>, copy it into the QZ Tray folder, then quit and reopen QZ Tray.</p>
                    <details class="prx-where">
                        <summary>Where is the QZ Tray folder?</summary>
                        <ul>
                            <li>Mac: Applications → right-click QZ Tray → <em>Show Package Contents</em> → Contents → Resources</li>
                            <li>Windows: <code>C:\Program Files\QZ Tray\</code></li>
                        </ul>
                    </details>
                    <div class="prx-step-actions">
                        <a href="{{ route('inventory::barcode::qz::certificate', ['download' => 1]) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fa fa-certificate me-1"></i> Download certificate
                        </a>
                    </div>
                </li>
                <li class="prx-step">
                    <span class="prx-step-num"></span>
                    <h6>Check the connection</h6>
                    <p>The status at the top turns green and says <em>Connected</em> when QZ Tray is ready.</p>
                    <div class="prx-step-actions">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-qz-connection-refresh>
                            <i class="fa fa-refresh me-1"></i> Check now
                        </button>
                    </div>
                </li>
            </ol>
        </div>
    </section>

    {{-- 2 · One card per job --}}
    <section class="scx-section">
        <div class="scx-head">
            <span class="scx-ic" style="--tone:#2f6fd6"><i class="fa fa-print"></i></span>
            <div>
                <h6>Printers</h6>
                <p>Choose how each job prints, then pick its printer.</p>
            </div>
        </div>
        <div class="scx-body">
            <div class="prx-printers">
                @foreach ($qzPrinterRoles as $printer)
                    <div class="prx-printer">
                        <div class="prx-printer-top">
                            <span class="scx-ic" style="--tone:{{ $printer['tone'] }}"><i class="fa {{ $printer['icon'] }}"></i></span>
                            <div class="flex-grow-1" style="min-width: 0;">
                                <div class="fw-semibold text-body-emphasis">{{ $printer['title'] }}</div>
                                <div class="small text-body-secondary">{{ $printer['hint'] }}</div>
                            </div>
                        </div>

                        <label class="prx-mode" for="qz-enable-{{ $printer['role'] }}">
                            <input type="checkbox" role="switch" id="qz-enable-{{ $printer['role'] }}" data-qz-enable="{{ $printer['role'] }}" aria-label="Direct print for {{ strtolower($printer['title']) }}">
                            <span class="prx-mode-off"><i class="fa fa-external-link"></i> Print window</span>
                            <span class="prx-mode-on"><i class="fa fa-bolt"></i> Direct to printer</span>
                        </label>

                        <button type="button" class="prx-slot" data-{{ $printer['role'] }}-printer-choose>
                            <span class="prx-slot-ic"><i class="fa fa-print"></i></span>
                            <span class="prx-slot-text">
                                <small>Printer · e.g. {{ $printer['examples'] }}</small>
                                <span data-{{ $printer['role'] }}-printer-name>Printer</span>
                            </span>
                            <span class="prx-slot-go">Change <i class="fa fa-angle-right ms-1"></i></span>
                        </button>
                    </div>
                @endforeach
            </div>

            <ul class="prx-tips mt-3">
                <li><i class="fa fa-magic"></i><span>Not picked yet? On the first print, labels go to a label printer and invoices to a receipt printer by themselves, never the same one.</span></li>
                <li><i class="fa fa-check-square-o"></i><span>If QZ Tray asks whether to allow this site, tick <em>Remember this decision</em> and click <em>Allow</em>.</span></li>
                <li><i class="fa fa-arrows-v"></i><span>New roll of stickers? Open the barcode label printer and press <em>Calibrate</em> so it learns the sticker size.</span></li>
                <li><i class="fa fa-globe"></i><span>Network printer? Type its IP address in the printer window.</span></li>
            </ul>
        </div>
    </section>
</div>
