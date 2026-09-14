{{--
    Settings → Printers. Printers belong to the computer, so the choices here are
    saved in this browser by <x-qz-print /> (included on the settings page), not
    with the tenant's settings.
--}}
<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white py-2">
        <h5 class="mb-0 text-white">Printers</h5>
    </div>
    <div class="card-body p-3">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
            <div>
                <h6 class="mb-1">QZ Tray</h6>
                <p class="text-body-secondary small mb-0">
                    A small free program that lets this site print barcodes and invoices straight to the printer, without the print window.
                </p>
            </div>
            <span class="badge text-bg-secondary" data-qz-connection>Not checked</span>
        </div>
        <div class="d-flex flex-wrap gap-2 mb-4">
            <a href="https://qz.io/download/" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
                <i class="fa fa-download me-1"></i> Download QZ Tray
            </a>
            <a href="{{ route('inventory::barcode::qz::certificate', ['download' => 1]) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-certificate me-1"></i> Download certificate
            </a>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-qz-connection-refresh>
                <i class="fa fa-refresh me-1"></i> Check again
            </button>
        </div>

        <h6 class="mb-2">Printers on this computer</h6>
        <div class="list-group mb-2">
            <div class="list-group-item d-flex align-items-center gap-3">
                <i class="fa fa-barcode fa-lg text-primary"></i>
                <div class="flex-grow-1" style="min-width: 0;">
                    <div class="fw-semibold">Barcode label printer</div>
                    <div class="small text-body-secondary text-truncate" data-label-printer-name>Printer</div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" data-label-printer-choose>
                    <i class="fa fa-cog me-1"></i> Change
                </button>
            </div>
            <div class="list-group-item d-flex align-items-center gap-3">
                <i class="fa fa-file-text-o fa-lg text-primary"></i>
                <div class="flex-grow-1" style="min-width: 0;">
                    <div class="fw-semibold">Sale invoice printer</div>
                    <div class="small text-body-secondary text-truncate" data-receipt-printer-name>Printer</div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" data-receipt-printer-choose>
                    <i class="fa fa-cog me-1"></i> Change
                </button>
            </div>
        </div>
        <p class="small text-body-secondary mb-4">
            Each computer keeps its own printers. On the first print, barcodes go to a label printer (TSC, Zebra) and invoices to a
            receipt printer (Epson TM, thermal) automatically, never the same one. Change either one here at any time.
        </p>

        <h6 class="mb-2">How to set it up</h6>
        <ol class="small mb-0 ps-3">
            <li class="mb-2">
                <b>Install QZ Tray.</b> Click <em>Download QZ Tray</em>, install it on the computer the printers are connected to, and open it.
                It runs in the menu bar on a Mac and in the system tray on Windows.
            </li>
            <li class="mb-2">
                <b>Trust this site.</b> Click <em>Download certificate</em>; it saves a file named <code>override.crt</code>.
                Copy that file into the QZ Tray folder, then quit and reopen QZ Tray.
                <ul class="mt-1 mb-0">
                    <li>Mac: in Finder open Applications, right-click QZ Tray, choose <em>Show Package Contents</em>, then open Contents, then Resources.</li>
                    <li>Windows: <code>C:\Program Files\QZ Tray\</code></li>
                </ul>
            </li>
            <li class="mb-2"><b>Check the connection.</b> Click <em>Check again</em>. It should say Connected.</li>
            <li class="mb-2">
                <b>Choose the printers.</b> Click <em>Change</em> next to each printer and pick it from the list. A network printer can be added by
                typing its IP address in that window.
            </li>
            <li class="mb-2">
                <b>First print.</b> If QZ Tray asks whether to allow this site, tick <em>Remember this decision</em> and click <em>Allow</em>.
            </li>
            <li>
                <b>New label roll.</b> After loading a new roll of stickers, click <em>Change</em> next to the barcode label printer and press
                <em>Calibrate</em>, so the printer learns the sticker size.
            </li>
        </ol>
    </div>
</div>
