package com.astrasalon.invo

import android.app.PendingIntent
import android.bluetooth.BluetoothAdapter
import android.bluetooth.BluetoothClass
import android.bluetooth.BluetoothDevice
import android.bluetooth.BluetoothManager
import android.bluetooth.BluetoothSocket
import android.content.BroadcastReceiver
import android.content.ComponentName
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.content.ServiceConnection
import android.content.pm.PackageManager
import android.hardware.usb.UsbConstants
import android.hardware.usb.UsbDevice
import android.hardware.usb.UsbEndpoint
import android.hardware.usb.UsbInterface
import android.hardware.usb.UsbManager
import android.os.Build
import android.os.Handler
import android.os.IBinder
import android.os.Looper
import android.util.Log
import io.flutter.plugin.common.MethodCall
import io.flutter.plugin.common.MethodChannel
import java.util.UUID
import java.util.concurrent.CountDownLatch
import java.util.concurrent.Executors
import java.util.concurrent.TimeUnit
import woyou.aidlservice.jiuiv5.ICallback
import woyou.aidlservice.jiuiv5.IWoyouService

/**
 * Direct (dialog-free) receipt printing.
 *
 * Android's print framework always puts its own sheet in front of a job, so a
 * till can never auto-print through it. This channel is the way around that:
 * Dart rasterises the receipt to ESC/POS (see `escpos.dart`) and hands the raw
 * bytes to whichever link the till is paired with — a Bluetooth SPP socket, a
 * USB bulk endpoint, or the SUNMI inner-printer service. Nothing here shows UI.
 *
 * Wi-Fi/LAN printing needs no native code and lives in Dart.
 *
 * Every method answers with a plain value or `false`; a printer that is off is
 * a normal outcome, not an exception, because printing happens *after* the sale
 * is already saved.
 */
class PrinterPlugin(private val context: Context) : MethodChannel.MethodCallHandler {

    companion object {
        const val CHANNEL = "qloud/printer"
        private const val TAG = "QloudPrinter"

        /** Serial Port Profile — what every ESC/POS Bluetooth printer exposes. */
        private val SPP_UUID: UUID = UUID.fromString("00001101-0000-1000-8000-00805F9B34FB")

        /** The SUNMI inner-printer service, bound over its published AIDL. */
        private const val SUNMI_PACKAGE = "woyou.aidlservice.jiuiv5"
        private const val SUNMI_ACTION = "woyou.aidlservice.jiuiv5.IWoyouService"

        private const val USB_PERMISSION_ACTION = "com.astrasalon.invo.USB_PERMISSION"

        /**
         * Bytes per write on a Bluetooth link. Cheap printers have a ~1KB input
         * buffer and silently drop the tail of a larger single write, which
         * shows up as a receipt that stops halfway down.
         */
        private const val BT_CHUNK = 512
        private const val BT_CHUNK_PAUSE_MS = 12L
        private const val USB_CHUNK = 8192
    }

    private val io = Executors.newSingleThreadExecutor()
    private val main = Handler(Looper.getMainLooper())

    // ---- built-in (SUNMI) service binding --------------------------------

    @Volatile
    private var sunmi: IWoyouService? = null
    private val bindLock = Any()
    private var bound = false
    private var bindLatch: CountDownLatch? = null

    private val sunmiConnection = object : ServiceConnection {
        override fun onServiceConnected(name: ComponentName?, service: IBinder?) {
            sunmi = IWoyouService.Stub.asInterface(service)
            synchronized(bindLock) { bindLatch?.countDown() }
        }

        override fun onServiceDisconnected(name: ComponentName?) {
            // The process hosting the printer service died. The binding itself
            // survives and Android re-connects it, so only the stale proxy goes.
            sunmi = null
        }
    }

    /** No-op result sink; we do not surface per-command printer feedback. */
    private val noopCallback = object : ICallback.Stub() {
        override fun onRunResult(isSuccess: Boolean) {}
        override fun onReturnString(result: String?) {}
        override fun onRaiseException(code: Int, msg: String?) {}
        override fun onPrintResult(code: Int, msg: String?) {}
    }

    fun dispose() {
        synchronized(bindLock) {
            if (bound) {
                try {
                    context.unbindService(sunmiConnection)
                } catch (_: Throwable) {
                }
                bound = false
                bindLatch = null
            }
        }
        sunmi = null
        io.shutdown()
    }

    // ---- channel ---------------------------------------------------------

    override fun onMethodCall(call: MethodCall, result: MethodChannel.Result) {
        when (call.method) {
            "capabilities" -> reply(result) {
                mapOf(
                    "bluetooth" to (bluetoothAdapter() != null),
                    "usb" to context.packageManager.hasSystemFeature(PackageManager.FEATURE_USB_HOST),
                    "builtin" to hasSunmiService(),
                )
            }
            "btDevices" -> reply(result) { bondedPrinters() }
            "btPrint" -> reply(result) {
                printBluetooth(call.argument<String>("address"), call.argument<ByteArray>("data"))
            }
            "usbDevices" -> reply(result) { usbDevices() }
            "usbPrint" -> reply(result) {
                printUsb(call.argument<String>("address"), call.argument<ByteArray>("data"))
            }
            "builtinPrint" -> reply(result) { printBuiltIn(call.argument<ByteArray>("data")) }
            else -> result.notImplemented()
        }
    }

    /** Runs [work] off the platform thread and answers on it. */
    private fun reply(result: MethodChannel.Result, work: () -> Any?) {
        io.execute {
            val value = try {
                work()
            } catch (t: Throwable) {
                Log.w(TAG, "printer call failed", t)
                null
            }
            main.post { result.success(value) }
        }
    }

    // ---- Bluetooth -------------------------------------------------------

    private fun bluetoothAdapter(): BluetoothAdapter? = try {
        (context.getSystemService(Context.BLUETOOTH_SERVICE) as? BluetoothManager)?.adapter
    } catch (_: Throwable) {
        null
    }

    /**
     * Already-paired devices. We deliberately do not scan: an ESC/POS printer
     * is paired once from Android Settings and then lives at the till, and a
     * discovery sweep would only offer headsets and phones alongside it.
     *
     * Devices that report the imaging class come first — cheap printers often
     * report nothing useful, so the list is not filtered, only ordered.
     */
    private fun bondedPrinters(): List<Map<String, String>> {
        val adapter = bluetoothAdapter() ?: return emptyList()
        if (!adapter.isEnabled) return emptyList()
        val bonded: Set<BluetoothDevice> = try {
            adapter.bondedDevices ?: emptySet()
        } catch (_: SecurityException) {
            // BLUETOOTH_CONNECT declined — Dart already asked, so just show none.
            return emptyList()
        }
        return bonded
            .sortedByDescending { looksLikePrinter(it) }
            .map {
                val name = try {
                    it.name ?: it.address
                } catch (_: SecurityException) {
                    it.address
                }
                mapOf(
                    "address" to it.address,
                    "name" to name,
                    "detail" to if (looksLikePrinter(it)) "Paired printer · ${it.address}"
                    else "Paired · ${it.address}",
                )
            }
    }

    private fun looksLikePrinter(device: BluetoothDevice): Boolean = try {
        device.bluetoothClass?.majorDeviceClass == BluetoothClass.Device.Major.IMAGING
    } catch (_: Throwable) {
        false
    }

    private fun printBluetooth(address: String?, data: ByteArray?): Boolean {
        if (address.isNullOrBlank() || data == null || data.isEmpty()) return false
        val adapter = bluetoothAdapter() ?: return false
        val device = try {
            adapter.getRemoteDevice(address)
        } catch (t: Throwable) {
            Log.w(TAG, "bad bluetooth address $address", t)
            return false
        }

        var socket: BluetoothSocket? = null
        return try {
            // Discovery and an RFCOMM connect fight over the same radio.
            try {
                if (adapter.isDiscovering) adapter.cancelDiscovery()
            } catch (_: SecurityException) {
            }

            socket = device.createRfcommSocketToServiceRecord(SPP_UUID)
            try {
                socket.connect()
            } catch (first: Throwable) {
                // A well-known firmware quirk: some printers publish no SPP
                // service record, so the UUID lookup connects to nothing.
                // Channel 1 is where they actually listen.
                try {
                    socket.close()
                } catch (_: Throwable) {
                }
                socket = fallbackRfcommSocket(device) ?: throw first
                socket.connect()
            }

            val out = socket.outputStream
            var offset = 0
            while (offset < data.size) {
                val len = minOf(BT_CHUNK, data.size - offset)
                out.write(data, offset, len)
                out.flush()
                offset += len
                if (offset < data.size) Thread.sleep(BT_CHUNK_PAUSE_MS)
            }
            // Closing the socket the instant the last byte is queued truncates
            // the receipt on printers that buffer; give the head time to drain.
            Thread.sleep(400)
            true
        } catch (t: Throwable) {
            Log.w(TAG, "bluetooth print failed", t)
            false
        } finally {
            try {
                socket?.close()
            } catch (_: Throwable) {
            }
        }
    }

    /** `createRfcommSocket(1)` is hidden API; reflection is the only route. */
    private fun fallbackRfcommSocket(device: BluetoothDevice): BluetoothSocket? = try {
        val method = device.javaClass.getMethod("createRfcommSocket", Int::class.javaPrimitiveType)
        method.invoke(device, 1) as? BluetoothSocket
    } catch (t: Throwable) {
        Log.w(TAG, "no fallback rfcomm socket", t)
        null
    }

    // ---- USB -------------------------------------------------------------

    private fun usbManager(): UsbManager? =
        context.getSystemService(Context.USB_SERVICE) as? UsbManager

    private fun usbDevices(): List<Map<String, String>> {
        val manager = usbManager() ?: return emptyList()
        return manager.deviceList.values
            .sortedByDescending { printerInterface(it) != null }
            .map { device ->
                val isPrinter = printerInterface(device) != null
                val label = device.productName ?: device.deviceName
                mapOf(
                    "address" to device.deviceName,
                    "name" to label,
                    "detail" to buildString {
                        append(if (isPrinter) "USB printer" else "USB device")
                        append(" · ")
                        append(String.format("%04X:%04X", device.vendorId, device.productId))
                        device.manufacturerName?.let { append(" · $it") }
                    },
                )
            }
    }

    /**
     * The interface to talk to: a USB printer-class (7) interface if the device
     * declares one, otherwise the first interface carrying a bulk OUT endpoint
     * — plenty of cheap receipt printers describe themselves as vendor-specific.
     */
    private fun printerInterface(device: UsbDevice): UsbInterface? {
        var fallback: UsbInterface? = null
        for (i in 0 until device.interfaceCount) {
            val iface = device.getInterface(i)
            if (bulkOut(iface) == null) continue
            if (iface.interfaceClass == UsbConstants.USB_CLASS_PRINTER) return iface
            if (fallback == null) fallback = iface
        }
        return fallback
    }

    private fun bulkOut(iface: UsbInterface): UsbEndpoint? {
        for (e in 0 until iface.endpointCount) {
            val ep = iface.getEndpoint(e)
            if (ep.type == UsbConstants.USB_ENDPOINT_XFER_BULK &&
                ep.direction == UsbConstants.USB_DIR_OUT
            ) {
                return ep
            }
        }
        return null
    }

    private fun printUsb(address: String?, data: ByteArray?): Boolean {
        if (address.isNullOrBlank() || data == null || data.isEmpty()) return false
        val manager = usbManager() ?: return false
        val device = manager.deviceList[address] ?: return false

        if (!manager.hasPermission(device)) {
            if (!requestUsbPermission(manager, device)) return false
        }

        val iface = printerInterface(device) ?: return false
        val endpoint = bulkOut(iface) ?: return false
        val connection = manager.openDevice(device) ?: return false
        return try {
            if (!connection.claimInterface(iface, true)) return false
            var offset = 0
            while (offset < data.size) {
                val len = minOf(USB_CHUNK, data.size - offset)
                val chunk = data.copyOfRange(offset, offset + len)
                val sent = connection.bulkTransfer(endpoint, chunk, chunk.size, 8000)
                if (sent < 0) return false
                offset += len
            }
            true
        } catch (t: Throwable) {
            Log.w(TAG, "usb print failed", t)
            false
        } finally {
            try {
                connection.releaseInterface(iface)
            } catch (_: Throwable) {
            }
            connection.close()
        }
    }

    /**
     * Android grants USB access per device, per app, with a system dialog. It
     * is asked once — after that the cashier is never prompted again — so it is
     * the one moment this class is allowed to put something on screen.
     */
    private fun requestUsbPermission(manager: UsbManager, device: UsbDevice): Boolean {
        val latch = CountDownLatch(1)
        var granted = false
        val receiver = object : BroadcastReceiver() {
            override fun onReceive(ctx: Context?, intent: Intent?) {
                if (intent?.action != USB_PERMISSION_ACTION) return
                granted = intent.getBooleanExtra(UsbManager.EXTRA_PERMISSION_GRANTED, false)
                latch.countDown()
            }
        }
        val filter = IntentFilter(USB_PERMISSION_ACTION)
        try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
                context.registerReceiver(receiver, filter, Context.RECEIVER_NOT_EXPORTED)
            } else {
                @Suppress("UnspecifiedRegisterReceiverFlag")
                context.registerReceiver(receiver, filter)
            }
            // FLAG_MUTABLE: the system fills the grant result into this intent.
            val flags = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
                PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_MUTABLE
            } else {
                PendingIntent.FLAG_UPDATE_CURRENT
            }
            val pending = PendingIntent.getBroadcast(
                context, 0, Intent(USB_PERMISSION_ACTION).setPackage(context.packageName), flags
            )
            manager.requestPermission(device, pending)
            latch.await(30, TimeUnit.SECONDS)
        } catch (t: Throwable) {
            Log.w(TAG, "usb permission request failed", t)
        } finally {
            try {
                context.unregisterReceiver(receiver)
            } catch (_: Throwable) {
            }
        }
        return granted && manager.hasPermission(device)
    }

    // ---- built-in printer ------------------------------------------------

    private fun hasSunmiService(): Boolean = try {
        val intent = Intent(SUNMI_ACTION).setPackage(SUNMI_PACKAGE)
        context.packageManager.queryIntentServices(intent, 0).isNotEmpty()
    } catch (_: Throwable) {
        false
    }

    private fun printBuiltIn(data: ByteArray?): Boolean {
        if (data == null || data.isEmpty()) return false
        val service = awaitSunmiService() ?: return false
        return try {
            service.sendRAWData(data, noopCallback)
            true
        } catch (t: Throwable) {
            Log.w(TAG, "built-in print failed", t)
            // A terminal that went to sleep can drop the binder; the binding
            // reconnects on its own, so just discard the dead proxy.
            sunmi = null
            false
        }
    }

    /**
     * Binds the inner-printer service on first use and keeps the connection for
     * the life of the app — binding costs about a second, which is a second the
     * cashier would otherwise wait on every single receipt.
     *
     * Always called from the io executor, never the platform thread.
     */
    private fun awaitSunmiService(): IWoyouService? {
        sunmi?.let { return it }
        val latch: CountDownLatch
        synchronized(bindLock) {
            sunmi?.let { return it }
            if (!bound) {
                if (!hasSunmiService()) return null
                val intent = Intent(SUNMI_ACTION).setPackage(SUNMI_PACKAGE)
                bindLatch = CountDownLatch(1)
                bound = try {
                    context.bindService(intent, sunmiConnection, Context.BIND_AUTO_CREATE)
                } catch (t: Throwable) {
                    Log.w(TAG, "cannot bind inner printer", t)
                    false
                }
                if (!bound) {
                    bindLatch = null
                    return null
                }
            }
            latch = bindLatch ?: return sunmi
        }
        latch.await(5, TimeUnit.SECONDS)
        return sunmi
    }
}
