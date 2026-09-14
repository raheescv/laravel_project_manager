{{--
    Silent printing through QZ Tray (https://qz.io), the desktop agent that prints
    without the browser's print dialog. Include once per page.

    Two printer roles, each remembered per browser because printers belong to the PC:
      label    barcode labels: <a href data-label-print>, Livewire 'label-print',
               window.LabelPrint.print(url). TSC printers get TSPL (?format=tspl),
               needed on macOS where PDF printing swaps the width and height of any
               label wider than tall (JDK-8372952); other printers get the PDF.
               Position, gap and flip are template settings; colour reversal is per printer.
      receipt  sale receipts: <a href data-receipt-print>, Livewire 'receipt-print',
               window.ReceiptPrint.print(url). The route answers ?format=escpos and
               the raster goes to the thermal printer raw.
    [data-label-printer-choose] / [data-receipt-printer-choose] open the settings, and
    [data-label-printer-name] / [data-receipt-printer-name] show the chosen printer.

    A printer is a local queue or a network address. A PC that never picked one, or
    picked "open in the browser", keeps the old behaviour. Server setup:
    php artisan qz:certificate; each PC then trusts that certificate once.
--}}
@once
    <style>
        #qz-printer-dialog::backdrop {
            background: rgba(0, 0, 0, .45);
        }
    </style>

    <dialog id="qz-printer-dialog" class="p-0 border-0 bg-transparent" style="width: min(440px, calc(100vw - 32px)); max-width: none;" aria-labelledby="qz-printer-title">
        <div class="card shadow mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0" id="qz-printer-title"><i class="fa fa-print me-2"></i><span data-qz-title>Printer</span></h6>
                <button type="button" class="btn-close" data-qz-close aria-label="Close"></button>
            </div>
            <div class="small px-3 py-2 border-bottom" data-qz-status></div>
            <div class="list-group list-group-flush" data-qz-list style="max-height: 36vh; overflow-y: auto;"></div>
            <form class="d-flex gap-2 px-3 py-2 border-top" data-qz-network>
                <input type="text" class="form-control form-control-sm" placeholder="Network printer IP, e.g. 192.168.1.50" data-qz-network-host aria-label="Network printer address">
                <button type="submit" class="btn btn-sm btn-outline-secondary flex-shrink-0"><i class="fa fa-plus me-1"></i>Add</button>
            </form>

            <div class="border-top px-3 py-3 small" data-qz-adjust hidden>
                <div class="fw-semibold text-truncate mb-2" data-qz-adjust-title></div>

                <div data-qz-panel="label">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="btn-group btn-group-sm" role="group" aria-label="How labels reach the printer">
                            <input type="radio" class="btn-check" name="qz-label-mode" id="qz-label-mode-tspl" value="tspl" data-qz-option="mode">
                            <label class="btn btn-outline-secondary" for="qz-label-mode-tspl">TSC direct</label>
                            <input type="radio" class="btn-check" name="qz-label-mode" id="qz-label-mode-pdf" value="pdf" data-qz-option="mode">
                            <label class="btn btn-outline-secondary" for="qz-label-mode-pdf">PDF</label>
                        </div>
                        <div class="form-check form-switch mb-0" data-qz-tspl-only>
                            <input class="form-check-input" type="checkbox" role="switch" id="qz-label-invert" data-qz-option="invert">
                            <label class="form-check-label" for="qz-label-invert">Reverse colours</label>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" data-qz-calibrate data-qz-tspl-only>
                            <i class="fa fa-arrows-v me-1"></i>Calibrate
                        </button>
                    </div>
                    <div class="text-body-secondary mt-2" data-qz-tspl-only>Position, gap and flip are saved with each label template: Print alignment in the template designer. Calibrate feeds a few labels so the printer learns their length and gap.</div>
                </div>

                <div data-qz-panel="receipt">
                    <div class="row g-2 align-items-center">
                        <div class="col-7">
                            <label class="form-label mb-1" for="qz-receipt-width">Paper</label>
                            <select class="form-select form-select-sm" id="qz-receipt-width" data-qz-option="width">
                                <option value="576">80 mm, 203 dpi (576 dots)</option>
                                <option value="512">80 mm, 180 dpi (512 dots, TM-T88)</option>
                                <option value="384">58 mm (384 dots)</option>
                            </select>
                        </div>
                        <div class="col-5 d-flex flex-column gap-1">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="qz-receipt-cut" data-qz-option="cut">
                                <label class="form-check-label" for="qz-receipt-cut">Cut paper</label>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="qz-receipt-drawer" data-qz-option="drawer">
                                <label class="form-check-label" for="qz-receipt-drawer">Open drawer</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex align-items-center justify-content-between gap-2">
                <a href="{{ route('inventory::barcode::qz::certificate', ['download' => 1]) }}" class="small">
                    <i class="fa fa-download me-1"></i>Download certificate
                </a>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-qz-refresh>
                    <i class="fa fa-refresh me-1"></i>Refresh
                </button>
            </div>
        </div>
    </dialog>

    @push('scripts')
        <script src="{{ https_asset('assets/vendors/qz-tray/qz-tray.js') }}"></script>
        <script>
            (() => {
                const OPTIONS_KEY = 'qz.printerOptions';
                const NETWORK_KEY = 'qz.networkPrinters';
                // Picked on a PC that should keep printing from the browser
                const OPEN_IN_BROWSER = '__open_pdf__';
                const NETWORK = /^net:\/\/(.+):(\d+)$/;
                const ROLES = {
                    label: {
                        title: 'Label printer',
                        printerKey: 'qz.labelPrinter',
                        enabledKey: 'qz.labelEnabled',
                        browserLabel: 'No printer, open the PDF instead',
                        // Picked by itself on a computer's first print when a printer's name fits
                        match: /tsc|zebra|godex|argox|label/i,
                        // TSC printers take TSPL straight; a network printer has no driver for a PDF
                        defaults: (printer) => ({ mode: /tsc/i.test(printer) || NETWORK.test(printer) ? 'tspl' : 'pdf', invert: false }),
                    },
                    receipt: {
                        title: 'Receipt printer',
                        printerKey: 'qz.receiptPrinter',
                        enabledKey: 'qz.receiptEnabled',
                        browserLabel: 'No printer, print from the browser',
                        // Epson TM-T20 / TM_T82, thermal and POS printers; not an Epson inkjet like L3110
                        match: /tm[-_ ]?[a-z]{0,2}\d|receipt|thermal|xprinter|rongta|bixolon|sunmi|(^|[^a-z])pos([^a-z]|$)/i,
                        defaults: () => ({ width: 576, cut: true, drawer: false }),
                    },
                };
                const urls = {
                    certificate: @json(route('inventory::barcode::qz::certificate')),
                    sign: @json(route('inventory::barcode::qz::sign')),
                };
                const dialog = document.getElementById('qz-printer-dialog');
                const list = dialog.querySelector('[data-qz-list]');
                const status = dialog.querySelector('[data-qz-status]');
                const adjust = dialog.querySelector('[data-qz-adjust]');

                const storage = {
                    read(key, fallback) {
                        try { return localStorage.getItem(key) ?? fallback; } catch (e) { return fallback; }
                    },
                    write(key, value) {
                        try { localStorage.setItem(key, value); } catch (e) {}
                    },
                    json(key, fallback) {
                        try { return JSON.parse(this.read(key, '')) || fallback; } catch (e) { return fallback; }
                    },
                    printer(role) {
                        return this.read(ROLES[role].printerKey, null);
                    },
                    setPrinter(role, value) {
                        this.write(ROLES[role].printerKey, value);
                    },
                    // On unless this computer switched it off in Settings, Printers
                    enabled(role) {
                        return this.read(ROLES[role].enabledKey, '1') !== '0';
                    },
                    setEnabled(role, on) {
                        this.write(ROLES[role].enabledKey, on ? '1' : '0');
                    },
                    options(role, printer) {
                        return { ...ROLES[role].defaults(printer || ''), ...(this.json(OPTIONS_KEY, {})[`${role}:${printer}`] || {}) };
                    },
                    saveOptions(role, printer, changes) {
                        const all = this.json(OPTIONS_KEY, {});
                        all[`${role}:${printer}`] = { ...this.options(role, printer), ...changes };
                        this.write(OPTIONS_KEY, JSON.stringify(all));
                    },
                };

                function notify(type, message) {
                    if (window.toastr) {
                        window.toastr[type](message);
                    } else if (type === 'error') {
                        window.alert(message);
                    }
                }

                function printerName(printer) {
                    const network = NETWORK.exec(printer || '');
                    return network ? `${network[1]}:${network[2]} (network)` : printer;
                }

                function printerConfig(printer, options) {
                    const network = NETWORK.exec(printer);
                    return qz.configs.create(network ? { host: network[1], port: Number(network[2]) } : printer, options);
                }

                // ── QZ Tray connection and request signing ──
                let securityReady = false;
                let connecting = null;

                function csrfHeaders() {
                    const token = document.querySelector('meta[name="csrf-token"]')?.content;
                    if (token) return { 'X-CSRF-TOKEN': token };
                    // Inertia pages carry no meta tag; Laravel takes its XSRF cookie too
                    const cookie = document.cookie.split('; ').find((row) => row.startsWith('XSRF-TOKEN='));
                    return cookie ? { 'X-XSRF-TOKEN': decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) } : {};
                }

                function prepareSecurity() {
                    if (securityReady) return;
                    securityReady = true;

                    qz.security.setCertificatePromise((resolve, reject) => {
                        fetch(urls.certificate, { cache: 'no-store', credentials: 'same-origin' })
                            .then((response) => response.ok ? response.text() : Promise.reject(new Error('No QZ Tray certificate on the server')))
                            .then(resolve, reject);
                    });
                    qz.security.setSignatureAlgorithm('SHA512');
                    qz.security.setSignaturePromise((toSign) => (resolve) => {
                        fetch(urls.sign, {
                            method: 'POST',
                            cache: 'no-store',
                            credentials: 'same-origin',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...csrfHeaders() },
                            body: JSON.stringify({ request: toSign }),
                        })
                            .then((response) => response.ok ? response.text() : '')
                            // An unsigned request still prints; QZ Tray just asks "Allow?" first
                            .then(resolve, () => resolve(''));
                    });
                }

                function connect() {
                    if (!window.qz) return Promise.reject(new Error('The QZ Tray library did not load'));
                    prepareSecurity();
                    if (qz.websocket.isActive()) return Promise.resolve();
                    connecting ??= qz.websocket.connect({ retries: 0 }).finally(() => { connecting = null; });
                    return connecting;
                }

                // ── Printer picker and per-printer settings ──
                let activeRole = 'label';
                let settle = null;
                let foundPrinters = [];

                function syncPrinterNames() {
                    Object.keys(ROLES).forEach((role) => {
                        const printer = storage.printer(role);
                        const label = !storage.enabled(role)
                            ? 'Off, browser print window'
                            : (!printer ? 'Printer' : (printer === OPEN_IN_BROWSER ? 'Browser' : printerName(printer)));
                        document.querySelectorAll(`[data-${role}-printer-name]`).forEach((el) => { el.textContent = label; });
                    });
                }

                function openDialog(role) {
                    activeRole = role;
                    dialog.querySelector('[data-qz-title]').textContent = ROLES[role].title;
                    if (!dialog.open) dialog.showModal();
                    renderPrinters();
                }

                // From a print: tapping a printer applies it, closes, and the print carries on
                function choosePrinter(role) {
                    settle?.(null);
                    return new Promise((resolve) => {
                        settle = resolve;
                        openDialog(role);
                    });
                }

                // From a settings button: stays open so the printer can be tuned
                function openSettings(role) {
                    settle?.(null);
                    settle = null;
                    openDialog(role);
                }

                function closePicker(value) {
                    const done = settle;
                    settle = null;
                    if (dialog.open) dialog.close();
                    done?.(value);
                }

                function applyPrinter(value) {
                    storage.setPrinter(activeRole, value);
                    syncPrinterNames();
                    if (settle) {
                        closePicker(value);
                    } else {
                        renderList();
                        renderAdjust();
                    }
                }

                function printerOption(value, label, icon) {
                    const active = storage.printer(activeRole) === value;
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'list-group-item list-group-item-action d-flex align-items-center gap-2' + (active ? ' active' : '');
                    button.innerHTML = `<i class="fa ${icon}"></i><span class="flex-grow-1 text-truncate"></span>` + (active ? '<i class="fa fa-check"></i>' : '');
                    button.querySelector('span').textContent = label;
                    button.addEventListener('click', () => applyPrinter(value));
                    return button;
                }

                function renderList() {
                    list.replaceChildren(
                        ...foundPrinters.map((name) => printerOption(name, name, 'fa-print')),
                        ...storage.json(NETWORK_KEY, []).map((value) => printerOption(value, printerName(value), 'fa-sitemap')),
                        printerOption(OPEN_IN_BROWSER, ROLES[activeRole].browserLabel, 'fa-external-link'),
                    );
                }

                function renderAdjust() {
                    const printer = storage.printer(activeRole);
                    adjust.hidden = !printer || printer === OPEN_IN_BROWSER;
                    if (adjust.hidden) return;

                    const options = storage.options(activeRole, printer);
                    adjust.querySelector('[data-qz-adjust-title]').textContent = printerName(printer);
                    adjust.querySelectorAll('[data-qz-panel]').forEach((panel) => { panel.hidden = panel.dataset.qzPanel !== activeRole; });

                    const panel = adjust.querySelector(`[data-qz-panel="${activeRole}"]`);
                    panel.querySelectorAll('[data-qz-option]').forEach((input) => {
                        const value = options[input.dataset.qzOption];
                        if (input.type === 'radio') input.checked = input.value === String(value);
                        else if (input.type === 'checkbox') input.checked = !!value;
                        else input.value = value ?? '';
                    });
                    panel.querySelectorAll('[data-qz-tspl-only]').forEach((el) => { el.hidden = options.mode !== 'tspl'; });
                }

                async function renderPrinters() {
                    list.replaceChildren();
                    status.className = 'small px-3 py-2 border-bottom text-body-secondary';
                    status.textContent = 'Looking for QZ Tray…';

                    try {
                        await connect();
                        foundPrinters = await qz.printers.find();
                        status.className = 'small px-3 py-2 border-bottom text-success';
                        status.textContent = `QZ Tray is connected. Tap the ${ROLES[activeRole].title.toLowerCase()}.`;
                    } catch (e) {
                        foundPrinters = [];
                        status.className = 'small px-3 py-2 border-bottom text-danger';
                        status.textContent = 'QZ Tray is not running on this computer. Start it, then Refresh.';
                    }

                    renderList();
                    renderAdjust();
                }

                // ── Printing ──
                const busy = {};

                function openInBrowser(url, fallback) {
                    if (fallback === 'redirect' || !window.open(url, '_blank')) {
                        window.location.href = url;
                    }
                }

                function toBase64(blob) {
                    return new Promise((resolve, reject) => {
                        const reader = new FileReader();
                        reader.onload = () => resolve(String(reader.result).split(',')[1] ?? '');
                        reader.onerror = () => reject(reader.error);
                        reader.readAsDataURL(blob);
                    });
                }

                async function fetchDocument(url, params, type) {
                    const target = new URL(url, window.location.origin);
                    Object.entries(params).forEach(([key, value]) => target.searchParams.set(key, value));

                    const response = await fetch(target, { cache: 'no-store', credentials: 'same-origin' });
                    const contentType = response.headers.get('Content-Type') || '';
                    if (response.ok && contentType.includes(type)) return response;

                    // Say why, so a server problem is told apart from a printer problem
                    let reason = `the server answered with error ${response.status}`;
                    if (contentType.includes('json')) {
                        reason = (await response.json().catch(() => ({}))).message || reason;
                    } else if (response.ok) {
                        reason = 'the server sent a web page instead of printer data, so it may need the latest update and a cache clear';
                    }
                    throw new Error(reason);
                }

                async function sendRaw(printer, response) {
                    const data = await toBase64(await response.blob());
                    // forceRaw hands the bytes to the printer untouched, skipping any driver
                    await qz.print(printerConfig(printer, { forceRaw: true }), [{ type: 'raw', format: 'command', flavor: 'base64', data }]);
                }

                async function sendLabel(printer, url, options) {
                    if (options.mode === 'tspl') {
                        // Position, gap and flip come from the template; colour polarity is the printer's
                        await sendRaw(printer, await fetchDocument(url, options.invert ? { format: 'tspl', invert: 1 } : { format: 'tspl' }, 'octet-stream'));
                        return;
                    }

                    const response = await fetchDocument(url, {}, 'pdf');
                    // Label size in mm, sent by the barcode PDF routes
                    const width = parseFloat(response.headers.get('X-Label-Width'));
                    const height = parseFloat(response.headers.get('X-Label-Height'));
                    const config = { units: 'mm', margins: 0 };
                    if (width > 0 && height > 0) config.size = { width, height };

                    const data = await toBase64(await response.blob());
                    await qz.print(printerConfig(printer, config), [{ type: 'pixel', format: 'pdf', flavor: 'base64', data }]);
                }

                async function sendReceipt(printer, url, options) {
                    const params = { format: 'escpos', width: options.width, cut: options.cut ? 1 : 0, drawer: options.drawer ? 1 : 0 };
                    await sendRaw(printer, await fetchDocument(url, params, 'octet-stream'));
                }

                // A computer's first print: take the printer whose name fits the job, never the one the
                // other role already uses, so barcodes and invoices start on different printers.
                async function suggestPrinter(role) {
                    let printers = [];
                    try {
                        printers = await qz.printers.find();
                    } catch (e) {
                        return null;
                    }

                    const other = storage.printer(role === 'label' ? 'receipt' : 'label');
                    const pick = printers.find((name) => ROLES[role].match.test(name) && name !== other);
                    if (!pick) return null;

                    storage.setPrinter(role, pick);
                    syncPrinterNames();
                    notify('info', `${ROLES[role].title}: ${pick}. Change it in Settings, Printers.`);
                    return pick;
                }

                // Settings, Printers tab: whether QZ Tray answers on this computer
                async function renderConnection() {
                    const badges = document.querySelectorAll('[data-qz-connection]');
                    const paint = (className, text) => badges.forEach((badge) => {
                        badge.className = `badge ${className}`;
                        badge.textContent = text;
                    });
                    if (!badges.length) return;

                    paint('text-bg-secondary', 'Checking…');
                    try {
                        await connect();
                        paint('text-bg-success', `Connected, QZ Tray ${await qz.api.getVersion()}`);
                    } catch (e) {
                        paint('text-bg-danger', 'Not running on this computer');
                    }
                }

                // Resolves 'printed' | 'opened' (shown in the browser instead) | 'cancelled' | 'busy'
                async function print(url, { role = 'label', fallback = 'tab' } = {}) {
                    // A double click must not print twice
                    if (busy[role]) return 'busy';
                    busy[role] = true;

                    try {
                        // Direct printing switched off on this computer: the browser print window, as before
                        if (!storage.enabled(role)) {
                            openInBrowser(url, fallback);
                            return 'opened';
                        }

                        let printer = storage.printer(role);

                        if (printer !== OPEN_IN_BROWSER) {
                            try {
                                await connect();
                            } catch (e) {
                                // Only warn a PC that was set up for silent printing
                                if (printer) notify('warning', 'QZ Tray is not running, so it opened in the browser instead.');
                                printer = OPEN_IN_BROWSER;
                            }
                        }

                        if (!printer) {
                            printer = (await suggestPrinter(role)) ?? (await choosePrinter(role));
                            if (!printer) return 'cancelled';
                        }

                        if (printer === OPEN_IN_BROWSER) {
                            openInBrowser(url, fallback);
                            return 'opened';
                        }

                        try {
                            const options = storage.options(role, printer);
                            await (role === 'receipt' ? sendReceipt(printer, url, options) : sendLabel(printer, url, options));
                            notify('success', `Sent to ${printerName(printer)}`);
                            return 'printed';
                        } catch (e) {
                            openInBrowser(url, fallback);
                            notify('error', `Could not print on ${printerName(printer)}: ${e?.message || e}. Opened it in the browser instead.`);
                            return 'opened';
                        }
                    } finally {
                        busy[role] = false;
                    }
                }

                // ── Wiring ──
                document.addEventListener('click', (event) => {
                    if (!(event.target instanceof Element)) return;

                    for (const role of Object.keys(ROLES)) {
                        const trigger = event.target.closest(`[data-${role}-print]`);
                        if (trigger) {
                            event.preventDefault();
                            print(trigger.getAttribute(`data-${role}-print`) || trigger.getAttribute('href'), { role });
                            return;
                        }

                        if (event.target.closest(`[data-${role}-printer-choose]`)) {
                            event.preventDefault();
                            openSettings(role);
                            return;
                        }
                    }
                });

                Object.keys(ROLES).forEach((role) => {
                    window.addEventListener(`${role}-print`, (event) => {
                        const url = event.detail?.url ?? event.detail?.[0]?.url;
                        if (url) print(url, { role });
                    });
                });

                adjust.addEventListener('change', (event) => {
                    const input = event.target.closest('[data-qz-option]');
                    const printer = storage.printer(activeRole);
                    if (!input || !printer || printer === OPEN_IN_BROWSER) return;

                    const key = input.dataset.qzOption;
                    const value = input.type === 'checkbox' ? input.checked : (key === 'width' ? Number(input.value) : input.value);
                    storage.saveOptions(activeRole, printer, { [key]: value });
                    renderAdjust();
                });

                adjust.querySelector('[data-qz-calibrate]').addEventListener('click', async () => {
                    const printer = storage.printer('label');
                    if (!printer || printer === OPEN_IN_BROWSER) return;
                    if (!window.confirm('The printer will feed a few labels to measure their length and gap. Continue?')) return;

                    try {
                        await connect();
                        await qz.print(printerConfig(printer, { forceRaw: true }), [{ type: 'raw', format: 'command', flavor: 'plain', data: 'GAPDETECT\r\n' }]);
                        notify('success', 'Calibrating: the printer is feeding a few labels.');
                    } catch (e) {
                        notify('error', `Calibration failed: ${e?.message || e}`);
                    }
                });

                dialog.querySelector('[data-qz-network]').addEventListener('submit', (event) => {
                    event.preventDefault();
                    const input = dialog.querySelector('[data-qz-network-host]');
                    const match = /^\s*([\w.-]+)(?::(\d{2,5}))?\s*$/.exec(input.value);
                    input.classList.toggle('is-invalid', !match);
                    if (!match) return;

                    // ESC/POS and TSPL network printers listen on 9100 unless told otherwise
                    const value = `net://${match[1]}:${match[2] || 9100}`;
                    storage.write(NETWORK_KEY, JSON.stringify([value, ...storage.json(NETWORK_KEY, []).filter((item) => item !== value)]));
                    input.value = '';
                    applyPrinter(value);
                });

                dialog.addEventListener('close', () => closePicker(null));
                dialog.addEventListener('click', (event) => {
                    if (event.target === dialog) closePicker(null);
                });
                dialog.querySelector('[data-qz-close]').addEventListener('click', () => closePicker(null));
                dialog.querySelector('[data-qz-refresh]').addEventListener('click', renderPrinters);

                // Only check QZ Tray once the Printers tab is open, so no other page wakes it
                document.addEventListener('shown.bs.tab', (event) => {
                    const pane = document.querySelector(event.target.getAttribute('data-bs-target') || '#none');
                    if (pane?.querySelector('[data-qz-connection]')) renderConnection();
                });
                document.querySelectorAll('[data-qz-connection-refresh]').forEach((button) => button.addEventListener('click', renderConnection));
                document.querySelectorAll('[data-qz-enable]').forEach((input) => {
                    const role = input.dataset.qzEnable;
                    input.checked = storage.enabled(role);
                    input.addEventListener('change', () => {
                        storage.setEnabled(role, input.checked);
                        syncPrinterNames();
                    });
                });
                if ([...document.querySelectorAll('[data-qz-connection]')].some((badge) => badge.offsetParent !== null)) renderConnection();

                syncPrinterNames();
                window.LabelPrint = {
                    print: (url, options = {}) => print(url, { ...options, role: 'label' }),
                    openSettings: () => openSettings('label'),
                };
                window.ReceiptPrint = {
                    print: (url, options = {}) => print(url, { ...options, role: 'receipt' }),
                    openSettings: () => openSettings('receipt'),
                };
            })();
        </script>
    @endpush
@endonce
