import 'dart:async';
import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:permission_handler/permission_handler.dart';

import 'package:invo/shared/domain/models/printer_target.dart';

/// The direct (dialog-free) link to a thermal printer.
///
/// Network printing is pure Dart — a RAW/JetDirect socket, which works on
/// every platform. Bluetooth, USB and the built-in terminal printer are
/// Android-only and live behind the `qloud/printer` method channel implemented
/// in `PrinterPlugin.kt`; on any other platform they simply report themselves
/// as unavailable rather than throwing.
///
/// Nothing in this file throws at the caller: printing runs after a sale is
/// already saved, so a dead printer must never look like a failed sale.
class PrinterLink {
  const PrinterLink._();

  static const MethodChannel _channel = MethodChannel('qloud/printer');

  /// RAW / JetDirect. Every network-capable ESC/POS printer listens here.
  static const int defaultPort = 9100;

  /// Which transports this device can actually offer. [PrinterTransport.system]
  /// is always in the set — it is the fallback.
  static Future<Set<PrinterTransport>> capabilities() async {
    final out = <PrinterTransport>{PrinterTransport.system, PrinterTransport.network};
    if (!_nativeSupported) return out;
    try {
      final res = await _channel.invokeMapMethod<String, dynamic>('capabilities');
      if (res == null) return out;
      if (res['bluetooth'] == true) out.add(PrinterTransport.bluetooth);
      if (res['usb'] == true) out.add(PrinterTransport.usb);
      if (res['builtin'] == true) out.add(PrinterTransport.builtin);
    } on PlatformException catch (_) {
      // Older build without the plugin — network + dialog only.
    } on MissingPluginException catch (_) {
      // Same.
    }
    return out;
  }

  /// Only Android carries the native half. Everything else gets network-only,
  /// which is still a genuine silent print.
  static bool get _nativeSupported => !kIsWeb && Platform.isAndroid;

  // ---- discovery -------------------------------------------------------

  /// Lists the printers a [transport] can currently see. Never throws — an
  /// unreachable or unsupported transport returns an empty list.
  ///
  /// [onProgress] reports 0..1 for the LAN sweep, which is the slow one.
  static Future<List<DiscoveredPrinter>> discover(
    PrinterTransport transport, {
    void Function(double)? onProgress,
  }) async {
    switch (transport) {
      case PrinterTransport.network:
        return scanLan(onProgress: onProgress);
      case PrinterTransport.bluetooth:
        return _nativeList('btDevices', PrinterTransport.bluetooth);
      case PrinterTransport.usb:
        return _nativeList('usbDevices', PrinterTransport.usb);
      case PrinterTransport.builtin:
        final caps = await capabilities();
        if (!caps.contains(PrinterTransport.builtin)) return const [];
        return const [
          DiscoveredPrinter(
            transport: PrinterTransport.builtin,
            address: '',
            name: 'Built-in printer',
            detail: 'Integrated in this terminal',
          ),
        ];
      case PrinterTransport.system:
        return const [];
    }
  }

  static Future<List<DiscoveredPrinter>> _nativeList(
      String method, PrinterTransport transport) async {
    if (!_nativeSupported) return const [];
    try {
      final res = await _channel.invokeListMethod<dynamic>(method);
      if (res == null) return const [];
      return res.whereType<Map>().map((raw) {
        final m = raw.cast<Object?, Object?>();
        return DiscoveredPrinter(
          transport: transport,
          address: (m['address'] ?? '').toString(),
          name: (m['name'] ?? '').toString(),
          detail: (m['detail'] ?? '').toString(),
        );
      }).where((d) => d.address.isNotEmpty).toList();
    } catch (_) {
      return const [];
    }
  }

  /// Sweeps every IPv4 subnet this device sits on for something answering on
  /// [defaultPort]. Printers don't advertise themselves reliably (mDNS is off
  /// on most cheap ESC/POS boxes), so an honest port probe finds more of them
  /// than a discovery protocol does.
  static Future<List<DiscoveredPrinter>> scanLan({
    void Function(double)? onProgress,
    Duration timeout = const Duration(milliseconds: 400),
  }) async {
    final prefixes = await _subnets();
    if (prefixes.isEmpty) return const [];

    final found = <DiscoveredPrinter>[];
    final total = prefixes.length * 254;
    var done = 0;

    for (final prefix in prefixes) {
      // 32 at a time: enough to sweep a /24 in a couple of seconds without
      // exhausting the socket table on a mid-range Android till.
      for (var start = 1; start < 255; start += 32) {
        final end = (start + 32) > 255 ? 255 : start + 32;
        await Future.wait([
          for (var i = start; i < end; i++) _probeHost('$prefix.$i', timeout, found),
        ]);
        done += end - start;
        onProgress?.call(done / total);
      }
    }
    found.sort((a, b) => a.address.compareTo(b.address));
    return found;
  }

  static Future<void> _probeHost(
      String host, Duration timeout, List<DiscoveredPrinter> into) async {
    try {
      final socket = await Socket.connect(host, defaultPort, timeout: timeout);
      socket.destroy();
      into.add(DiscoveredPrinter(
        transport: PrinterTransport.network,
        address: '$host:$defaultPort',
        name: host,
        detail: 'Port $defaultPort open',
      ));
    } catch (_) {
      // Closed, filtered or nothing there — the normal case for 253 of 254.
    }
  }

  static Future<List<String>> _subnets() async {
    try {
      final ifaces = await NetworkInterface.list(
        type: InternetAddressType.IPv4,
        includeLoopback: false,
        includeLinkLocal: false,
      );
      final out = <String>{};
      for (final iface in ifaces) {
        for (final addr in iface.addresses) {
          final parts = addr.address.split('.');
          // /24 only. Anything else is a routed network a broadcast sweep has
          // no business walking.
          if (parts.length == 4) out.add('${parts[0]}.${parts[1]}.${parts[2]}');
        }
      }
      return out.toList();
    } catch (_) {
      return const [];
    }
  }

  // ---- permissions -----------------------------------------------------

  /// Bluetooth needs runtime consent from Android 12 on. Only CONNECT is asked
  /// for: the picker lists devices already paired in Android Settings, so
  /// nothing here scans — and SCAN is the permission tied to location.
  /// Returns false when the cashier declined, so the picker can say why the
  /// list is empty.
  static Future<bool> ensureBluetoothPermission() async {
    if (!_nativeSupported) return false;
    try {
      final status = await Permission.bluetoothConnect.request();
      return status.isGranted || status.isLimited;
    } catch (_) {
      return false;
    }
  }

  // ---- sending ---------------------------------------------------------

  /// Pushes [data] at [target]. Returns true only when the bytes were handed
  /// over; the caller falls back to the print dialog on false.
  static Future<bool> send(PrinterTarget target, Uint8List data) async {
    if (!target.isPaired) return false;
    switch (target.transport) {
      case PrinterTransport.network:
        return _sendNetwork(target.host, target.port, data);
      case PrinterTransport.bluetooth:
        return _sendNative('btPrint', {'address': target.address, 'data': data});
      case PrinterTransport.usb:
        return _sendNative('usbPrint', {'address': target.address, 'data': data});
      case PrinterTransport.builtin:
        return _sendNative('builtinPrint', {'data': data});
      case PrinterTransport.system:
        return false; // handled by the dialog path in receipt_printer.dart
    }
  }

  static Future<bool> _sendNetwork(String host, int port, Uint8List data) async {
    Socket? socket;
    try {
      socket = await Socket.connect(host, port,
          timeout: const Duration(seconds: 6));
      socket.add(data);
      await socket.flush();
      // Cheap print servers ack by closing; give the head a moment to swallow
      // the buffer before we tear the connection down, or the tail of a long
      // receipt is lost.
      await socket.close().timeout(const Duration(seconds: 6), onTimeout: () {});
      return true;
    } catch (_) {
      return false;
    } finally {
      socket?.destroy();
    }
  }

  static Future<bool> _sendNative(String method, Map<String, dynamic> args) async {
    if (!_nativeSupported) return false;
    try {
      return await _channel.invokeMethod<bool>(method, args) ?? false;
    } catch (_) {
      return false;
    }
  }
}
