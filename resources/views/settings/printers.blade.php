{{--
    Settings → Printers. Printers belong to the computer, so the choices here are
    saved in this browser by <x-qz-print /> (included on the settings page), not
    with the tenant's settings.
--}}
@php
    $qzPrinterRoles = [
        ['role' => 'label', 'icon' => 'fa-barcode', 'title' => 'Barcode label printer', 'hint' => 'Barcode stickers and jewellery tags'],
        ['role' => 'receipt', 'icon' => 'fa-file-text-o', 'title' => 'Sale invoice printer', 'hint' => 'Receipts after a sale, and reprints'],
    ];
@endphp

<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white py-2 d-flex align-items-center justify-content-between gap-2">
        <h5 class="mb-0 text-white"><i class="fa fa-print me-2"></i>Printers</h5>
        <span class="small text-white-50">Saved on this computer</span>
    </div>
    <div class="card-body p-3 p-lg-4">

        {{-- QZ Tray: status and downloads --}}
        <div class="border rounded-3 p-3 mb-4 bg-body-tertiary">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                    <i class="fa fa-plug fa-lg"></i>
                </div>
                <div class="flex-grow-1" style="min-width: 14rem;">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h6 class="mb-0">QZ Tray</h6>
                        <span class="badge text-bg-secondary" data-qz-connection>Not checked</span>
                    </div>
                    <p class="small text-body-secondary mb-0 mt-1">
                        A small free program that prints barcodes and invoices straight to the printer, without the print window.
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="https://qz.io/download/" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
                        <i class="fa fa-download me-1"></i> Download QZ Tray
                    </a>
                    <a href="{{ route('inventory::barcode::qz::certificate', ['download' => 1]) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa fa-certificate me-1"></i> Certificate
                    </a>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-qz-connection-refresh title="Check the connection again">
                        <i class="fa fa-refresh"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- One card per job, each with its own printer --}}
        <div class="row g-3">
            @foreach ($qzPrinterRoles as $printer)
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100 d-flex flex-column">
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                                <i class="fa {{ $printer['icon'] }} fa-lg"></i>
                            </div>
                            <div class="flex-grow-1" style="min-width: 0;">
                                <div class="fw-semibold">{{ $printer['title'] }}</div>
                                <div class="small text-body-secondary">{{ $printer['hint'] }}</div>
                            </div>
                            <div class="form-check form-switch mb-0 flex-shrink-0" title="Off: this computer uses the normal browser print window">
                                <input class="form-check-input" type="checkbox" role="switch" id="qz-enable-{{ $printer['role'] }}" data-qz-enable="{{ $printer['role'] }}" checked>
                                <label class="form-check-label small" for="qz-enable-{{ $printer['role'] }}">Direct print</label>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-auto">
                            <div class="form-control form-control-sm bg-body-tertiary text-truncate" data-{{ $printer['role'] }}-printer-name>Printer</div>
                            <button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0" data-{{ $printer['role'] }}-printer-choose>
                                <i class="fa fa-cog me-1"></i> Change
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="small text-body-secondary mt-2 mb-4">
            On the first print, barcodes go to a label printer (TSC, Zebra) and invoices to a receipt printer (Epson TM, thermal)
            by themselves, never the same one. Turn <em>Direct print</em> off to use the normal browser print window on this computer.
        </p>

        {{-- Setup guide --}}
        <div class="accordion" id="qz-setup-guide">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button py-2 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#qz-setup-steps"
                        aria-expanded="true" aria-controls="qz-setup-steps">
                        <i class="fa fa-book me-2 text-primary"></i> How to set it up
                    </button>
                </h2>
                <div id="qz-setup-steps" class="accordion-collapse collapse show" data-bs-parent="#qz-setup-guide">
                    <div class="accordion-body small">
                        <ol class="mb-0 ps-3">
                            <li class="mb-2">
                                <b>Install QZ Tray.</b> Click <em>Download QZ Tray</em>, install it on the computer the printers are connected to,
                                and open it. It runs in the menu bar on a Mac and in the system tray on Windows.
                            </li>
                            <li class="mb-2">
                                <b>Trust this site.</b> Click <em>Certificate</em>; it saves a file named <code>override.crt</code>.
                                Copy that file into the QZ Tray folder, then quit and reopen QZ Tray.
                                <ul class="mt-1 mb-0">
                                    <li>Mac: in Finder open Applications, right-click QZ Tray, choose <em>Show Package Contents</em>, then open Contents, then Resources.</li>
                                    <li>Windows: <code>C:\Program Files\QZ Tray\</code></li>
                                </ul>
                            </li>
                            <li class="mb-2"><b>Check the connection.</b> Click <i class="fa fa-refresh"></i>. The badge should turn green and say Connected.</li>
                            <li class="mb-2">
                                <b>Choose the printers.</b> Click <em>Change</em> on each card and pick the printer. A network printer can be added by
                                typing its IP address in that window.
                            </li>
                            <li class="mb-2">
                                <b>First print.</b> If QZ Tray asks whether to allow this site, tick <em>Remember this decision</em> and click <em>Allow</em>.
                            </li>
                            <li>
                                <b>New label roll.</b> After loading a new roll of stickers, click <em>Change</em> on the barcode label printer and press
                                <em>Calibrate</em>, so the printer learns the sticker size.
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
