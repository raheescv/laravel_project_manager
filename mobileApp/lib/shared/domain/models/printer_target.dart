import 'package:flutter/foundation.dart';

/// How this till reaches its receipt printer.
///
/// Everything except [system] is a *direct* link: we hand the printer raw
/// ESC/POS bytes ourselves, so a receipt reaches paper with no sheet, no
/// dialog and no tap. [system] is the fallback the app has always had — the
/// platform print dialog, which on Android can never be silenced.
enum PrinterTransport {
  system('system', 'System dialog', 'Prints through the OS print sheet'),
  network('network', 'Wi-Fi / LAN', 'Network printer on port 9100'),
  bluetooth('bluetooth', 'Bluetooth', 'Paired thermal printer'),
  usb('usb', 'USB', 'Printer plugged into this device'),
  builtin('builtin', 'Built-in printer', 'The printer inside this POS terminal');

  const PrinterTransport(this.key, this.label, this.blurb);
  final String key;
  final String label;
  final String blurb;

  static PrinterTransport fromKey(String? k) =>
      PrinterTransport.values.firstWhere((t) => t.key == k, orElse: () => PrinterTransport.system);

  /// True when a job goes straight to paper — the only mode that makes
  /// auto-print genuinely zero-tap.
  bool get isDirect => this != PrinterTransport.system;
}

/// The printer this till is paired with.
///
/// [address] is transport-specific and opaque to the UI:
///   network   → `host:port` (port defaults to 9100)
///   bluetooth → MAC address
///   usb       → the Android `UsbDevice.deviceName` (e.g. `/dev/bus/usb/001/003`)
///   builtin   → '' (there is only one)
///   system    → the platform printer URL from `Printing.pickPrinter`
@immutable
class PrinterTarget {
  const PrinterTarget({required this.transport, required this.address, required this.name});

  /// Nothing paired — auto-print falls back to the OS dialog.
  static const PrinterTarget none =
      PrinterTarget(transport: PrinterTransport.system, address: '', name: '');

  final PrinterTransport transport;
  final String address;
  final String name;

  /// True once this till has something to print to without asking the cashier.
  /// The built-in printer needs no address; every other transport does.
  bool get isPaired =>
      transport == PrinterTransport.builtin || address.trim().isNotEmpty;

  /// True when printing bypasses the OS print sheet entirely.
  bool get isDirect => transport.isDirect && isPaired;

  /// `host` half of a network address.
  String get host => address.split(':').first;

  /// `port` half of a network address — 9100 is the RAW/JetDirect default
  /// every ESC/POS network printer listens on.
  int get port {
    final parts = address.split(':');
    if (parts.length < 2) return 9100;
    return int.tryParse(parts[1]) ?? 9100;
  }

  /// What the settings row shows.
  String get displayName => name.trim().isNotEmpty
      ? name.trim()
      : (address.trim().isNotEmpty ? address.trim() : transport.label);

  PrinterTarget copyWith({PrinterTransport? transport, String? address, String? name}) =>
      PrinterTarget(
        transport: transport ?? this.transport,
        address: address ?? this.address,
        name: name ?? this.name,
      );

  @override
  bool operator ==(Object other) =>
      other is PrinterTarget &&
      other.transport == transport &&
      other.address == address &&
      other.name == name;

  @override
  int get hashCode => Object.hash(transport, address, name);

  @override
  String toString() => 'PrinterTarget(${transport.key}, $address, $name)';
}

/// One printer offered by a transport's discovery scan.
@immutable
class DiscoveredPrinter {
  const DiscoveredPrinter({
    required this.transport,
    required this.address,
    required this.name,
    this.detail = '',
  });

  final PrinterTransport transport;
  final String address;
  final String name;

  /// Secondary line — the MAC, the IP, the USB vendor id.
  final String detail;

  PrinterTarget get target =>
      PrinterTarget(transport: transport, address: address, name: name);
}
