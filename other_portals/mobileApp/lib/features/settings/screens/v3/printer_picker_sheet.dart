import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:printing/printing.dart' as sys;

import 'package:invo/shared/domain/models/printer_target.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/printing/printer_link.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/receipt_printer.dart';

/// Choose the printer this till prints to. Returns the chosen [PrinterTarget],
/// [PrinterTarget.none] when the cashier picks the system print dialog, or null
/// when they backed out without changing anything.
///
/// One sheet per transport rather than four settings rows: the cashier holds a
/// printer, not a protocol, so the job is to show them every printer this
/// device can currently see and let them tap one.
Future<PrinterTarget?> showPrinterPickerSheet(
  BuildContext context, {
  required PrinterTarget current,
}) =>
    showModalBottomSheet<PrinterTarget>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _PrinterPickerSheet(current: current),
    );

class _PrinterPickerSheet extends StatefulWidget {
  const _PrinterPickerSheet({required this.current});
  final PrinterTarget current;

  @override
  State<_PrinterPickerSheet> createState() => _PrinterPickerSheetState();
}

class _PrinterPickerSheetState extends State<_PrinterPickerSheet> {
  final TextEditingController _ipCtl = TextEditingController();
  final FocusNode _ipFocus = FocusNode();

  Set<PrinterTransport> _caps = const {PrinterTransport.system};
  PrinterTransport _tab = PrinterTransport.network;
  List<DiscoveredPrinter> _found = const [];

  bool _loadingCaps = true;
  bool _scanning = false;
  double _progress = 0;

  /// Set when a scan finished and explained nothing — e.g. Bluetooth consent
  /// was declined. Shown instead of a bare "no printers" state.
  String? _note;

  /// True when this platform can also drive a printer picked from the OS print
  /// subsystem (iOS/macOS/Windows). False on Android.
  bool _systemPickable = false;

  @override
  void initState() {
    super.initState();
    _boot();
  }

  @override
  void dispose() {
    _ipCtl.dispose();
    _ipFocus.dispose();
    super.dispose();
  }

  Future<void> _boot() async {
    final caps = await PrinterLink.capabilities();
    if (!mounted) return;
    setState(() {
      _caps = caps;
      _loadingCaps = false;
      // Land on the transport already in use, otherwise on the first direct
      // one this device offers — the built-in printer needs no setup at all,
      // so it goes first.
      _tab = caps.contains(widget.current.transport) && widget.current.transport.isDirect
          ? widget.current.transport
          : _tabs.firstWhere((t) => t.isDirect, orElse: () => PrinterTransport.system);
    });
    // Bluetooth / USB / built-in listing is instant, so do it on arrival. The
    // LAN sweep is not, so that one waits for a tap.
    if (_tab != PrinterTransport.network) unawaited(_refresh());

    // Deliberately after the first frame, and never awaited before it: this
    // asks the OS print subsystem about itself, which only the "Print dialog"
    // tab cares about. Blocking the picker on it would leave a spinner where
    // the printer list should be if that subsystem is slow to answer — and on
    // a till with a Bluetooth printer the answer is irrelevant anyway.
    final pickable = await canDirectPrint();
    if (mounted && pickable != _systemPickable) {
      setState(() => _systemPickable = pickable);
    }
  }

  /// The transports offered as chips, in the order a till is most likely to
  /// use them.
  List<PrinterTransport> get _tabs => [
        for (final t in const [
          PrinterTransport.builtin,
          PrinterTransport.bluetooth,
          PrinterTransport.usb,
          PrinterTransport.network,
        ])
          if (_caps.contains(t)) t,
        PrinterTransport.system,
      ];

  Future<void> _select(PrinterTransport t) async {
    if (t == _tab) return;
    setState(() {
      _tab = t;
      _found = const [];
      _note = null;
      _progress = 0;
    });
    if (t != PrinterTransport.network && t != PrinterTransport.system) unawaited(_refresh());
  }

  Future<void> _refresh() async {
    if (_scanning) return;
    setState(() {
      _scanning = true;
      _note = null;
      _progress = 0;
      _found = const [];
    });

    if (_tab == PrinterTransport.bluetooth) {
      final granted = await PrinterLink.ensureBluetoothPermission();
      if (!mounted) return;
      if (!granted) {
        setState(() {
          _scanning = false;
          _note = 'Bluetooth permission was declined. Grant it in Android '
              'Settings → Apps → QLOUD POS → Permissions, then scan again.';
        });
        return;
      }
    }

    final found = await PrinterLink.discover(
      _tab,
      onProgress: (v) {
        if (mounted && _tab == PrinterTransport.network) setState(() => _progress = v);
      },
    );
    if (!mounted) return;
    setState(() {
      _found = found;
      _scanning = false;
      if (found.isEmpty) _note = _emptyNote(_tab);
    });
  }

  String _emptyNote(PrinterTransport t) {
    switch (t) {
      case PrinterTransport.bluetooth:
        return 'No paired printers. Pair the printer once in Android '
            'Settings → Bluetooth, then come back and scan.';
      case PrinterTransport.usb:
        return 'No USB printer detected. Check the cable and that the printer '
            'is powered on.';
      case PrinterTransport.network:
        return 'Nothing answered on port 9100. Check the printer is on the '
            'same Wi-Fi, or type its IP address below.';
      case PrinterTransport.builtin:
        return 'This device has no built-in printer service.';
      case PrinterTransport.system:
        return '';
    }
  }

  void _pick(PrinterTarget target) => Navigator.pop(context, target);

  /// Adds a network printer by hand — the reliable path when the till and the
  /// printer sit on different subnets, or the sweep is blocked by AP isolation.
  ///
  /// Pairs without probing the address first. A printer that happens to be
  /// switched off is not a wrong address, so the probe could never change the
  /// outcome — it would only cost the cashier a wait. Settings → Test print is
  /// the honest check, and it produces something they can hold.
  void _addManualIp() {
    final raw = _ipCtl.text.trim();
    if (raw.isEmpty) return;
    final host = raw.split(':').first.trim();
    if (host.isEmpty) return;
    final port = raw.contains(':') ? int.tryParse(raw.split(':')[1].trim()) : null;
    FocusScope.of(context).unfocus();
    _pick(PrinterTarget(
      transport: PrinterTransport.network,
      address: '$host:${port ?? PrinterLink.defaultPort}',
      name: host,
    ));
  }

  Future<void> _pickSystemPrinter() async {
    try {
      final printer = await sys.Printing.pickPrinter(context: context, title: 'Select printer');
      if (printer == null || !mounted) return;
      _pick(PrinterTarget(
        transport: PrinterTransport.system,
        address: printer.url,
        name: printer.name,
      ));
    } catch (_) {
      if (mounted) setState(() => _note = "Couldn't list printers on this device.");
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final media = MediaQuery.of(context);
    return Padding(
      padding: EdgeInsets.only(bottom: media.viewInsets.bottom),
      child: Container(
        constraints: BoxConstraints(maxHeight: media.size.height * 0.86),
        decoration: BoxDecoration(
          color: p.sheet,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(30)),
        ),
        padding: const EdgeInsets.fromLTRB(20, 14, 20, 20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 42,
                height: 5,
                decoration: BoxDecoration(
                    color: p.hairline, borderRadius: BorderRadius.circular(3)),
              ),
            ),
            const SizedBox(height: 16),
            Text('Choose printer', style: serif(size: 22, color: p.ink)),
            const SizedBox(height: 2),
            Text(
              'A direct link prints with no dialog and no taps.',
              style: ui(size: 12, weight: FontWeight.w600, color: p.textMuted),
            ),
            const SizedBox(height: 14),
            if (_loadingCaps)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 40),
                child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
              )
            else ...[
              _transportChips(context),
              const SizedBox(height: 14),
              Flexible(child: _body(context)),
            ],
          ],
        ),
      ),
    );
  }

  Widget _transportChips(BuildContext context) {
    final p = context.astra;
    return SizedBox(
      height: 36,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: _tabs.length,
        separatorBuilder: (_, __) => const SizedBox(width: 8),
        itemBuilder: (_, i) {
          final t = _tabs[i];
          final on = t == _tab;
          return GestureDetector(
            onTap: () => _select(t),
            behavior: HitTestBehavior.opaque,
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 160),
              padding: const EdgeInsets.symmetric(horizontal: 14),
              alignment: Alignment.center,
              decoration: BoxDecoration(
                gradient: on ? p.primaryGradient : null,
                color: on ? null : p.card,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(_iconFor(t),
                      size: 14, color: on ? Colors.white : p.textSecondary),
                  const SizedBox(width: 6),
                  Text(
                    t == PrinterTransport.system ? 'Print dialog' : t.label,
                    style: ui(
                        size: 12,
                        weight: FontWeight.w700,
                        color: on ? Colors.white : p.textSecondary),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  IconData _iconFor(PrinterTransport t) {
    switch (t) {
      case PrinterTransport.network:
        return Icons.wifi;
      case PrinterTransport.bluetooth:
        return Icons.bluetooth;
      case PrinterTransport.usb:
        return Icons.usb;
      case PrinterTransport.builtin:
        return Icons.point_of_sale_outlined;
      case PrinterTransport.system:
        return Icons.picture_as_pdf_outlined;
    }
  }

  Widget _body(BuildContext context) {
    if (_tab == PrinterTransport.system) return _systemBody(context);
    return ListView(
      shrinkWrap: true,
      padding: EdgeInsets.zero,
      children: [
        _actionBar(context),
        const SizedBox(height: 10),
        for (final d in _found) ...[
          _printerRow(context, d),
          const SizedBox(height: 8),
        ],
        if (_note != null && !_scanning) _noteBox(context, _note!),
        if (_tab == PrinterTransport.network) ...[
          const SizedBox(height: 6),
          _manualIpRow(context),
        ],
      ],
    );
  }

  Widget _actionBar(BuildContext context) {
    final p = context.astra;
    final label = _tab == PrinterTransport.network ? 'Scan network' : 'Refresh';
    return Row(
      children: [
        Expanded(
          child: Text(
            _scanning
                ? (_tab == PrinterTransport.network
                    ? 'Sweeping the network… ${(_progress * 100).round()}%'
                    : 'Looking…')
                : '${_found.length} found',
            style: ui(size: 11.5, weight: FontWeight.w700, color: p.textMuted),
          ),
        ),
        AstraButton(
          label: label,
          gold: false,
          expand: false,
          busy: _scanning,
          icon: Icons.refresh,
          onTap: _scanning ? null : _refresh,
        ),
      ],
    );
  }

  Widget _printerRow(BuildContext context, DiscoveredPrinter d) {
    final p = context.astra;
    final isCurrent = widget.current.transport == d.transport &&
        widget.current.address == d.address;
    return AstraCard(
      radius: 14,
      onTap: () => _pick(d.target),
      child: Row(
        children: [
          IconChip(icon: _iconFor(d.transport), size: 34, radius: 10, bg: p.tint),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(d.name.isEmpty ? d.address : d.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
                Text(d.detail.isEmpty ? d.address : d.detail,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          if (isCurrent)
            Icon(Icons.check_circle, size: 19, color: p.primary)
          else
            Icon(Icons.chevron_right, size: 19, color: p.textMuted),
        ],
      ),
    );
  }

  Widget _manualIpRow(BuildContext context) {
    final p = context.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('OR ENTER THE IP ADDRESS',
            style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted)),
        const SizedBox(height: 8),
        Row(
          children: [
            Expanded(
              child: KeyboardDoneField(
                focusNode: _ipFocus,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14),
                  decoration: BoxDecoration(
                    color: p.card,
                    borderRadius: BorderRadius.circular(13),
                    border: Border.all(color: p.primary.withValues(alpha: 0.22)),
                  ),
                  child: TextField(
                    controller: _ipCtl,
                    focusNode: _ipFocus,
                    keyboardType: TextInputType.url,
                    textInputAction: TextInputAction.done,
                    inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[0-9a-zA-Z.\-:]'))],
                    style: ui(size: 14, weight: FontWeight.w700, color: p.ink),
                    cursorColor: p.primary,
                    decoration: InputDecoration(
                      border: InputBorder.none,
                      hintText: '192.168.1.50',
                      hintStyle:
                          ui(size: 14, weight: FontWeight.w600, color: p.textMuted),
                    ),
                    onSubmitted: (_) => _addManualIp(),
                  ),
                ),
              ),
            ),
            const SizedBox(width: 10),
            AstraButton(
                label: 'Use', gold: false, expand: false, onTap: _addManualIp),
          ],
        ),
        const SizedBox(height: 6),
        Text('Port 9100 unless you add one, e.g. 192.168.1.50:9100',
            style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
      ],
    );
  }

  Widget _systemBody(BuildContext context) {
    final p = context.astra;
    return ListView(
      shrinkWrap: true,
      padding: EdgeInsets.zero,
      children: [
        _noteBox(
          context,
          _systemPickable
              ? 'The print dialog always needs a tap. Pick a system printer to '
                  'skip it, or use a direct link above for a truly hands-off till.'
              : 'Android always shows its own print sheet, so a receipt sent '
                  'this way needs one tap. Use a direct link above to remove it.',
        ),
        const SizedBox(height: 10),
        if (_systemPickable) ...[
          AstraCard(
            radius: 14,
            onTap: _pickSystemPrinter,
            child: Row(
              children: [
                IconChip(icon: Icons.print_outlined, size: 34, radius: 10, bg: p.tint),
                const SizedBox(width: 11),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Pick a system printer',
                          style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
                      Text('Drives it without the dialog on this platform',
                          style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                    ],
                  ),
                ),
                Icon(Icons.chevron_right, size: 19, color: p.textMuted),
              ],
            ),
          ),
          const SizedBox(height: 8),
        ],
        AstraCard(
          radius: 14,
          onTap: () => _pick(PrinterTarget.none),
          child: Row(
            children: [
              IconChip(icon: Icons.open_in_new, size: 34, radius: 10, bg: p.tint),
              const SizedBox(width: 11),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Always use the print dialog',
                        style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
                    Text('Un-pairs this till',
                        style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                  ],
                ),
              ),
              if (!widget.current.isPaired)
                Icon(Icons.check_circle, size: 19, color: p.primary)
              else
                Icon(Icons.chevron_right, size: 19, color: p.textMuted),
            ],
          ),
        ),
      ],
    );
  }

  Widget _noteBox(BuildContext context, String text) {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: p.tint,
        borderRadius: BorderRadius.circular(13),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.info_outline, size: 15, color: p.textSecondary),
          const SizedBox(width: 8),
          Expanded(
            child: Text(text,
                style: ui(size: 11.5, weight: FontWeight.w600, color: p.textSecondary)),
          ),
        ],
      ),
    );
  }
}
