package com.astrasalon.invo

import io.flutter.embedding.android.FlutterFragmentActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel

// FlutterFragmentActivity is required by local_auth for biometric prompts.
class MainActivity : FlutterFragmentActivity() {

    private var printer: PrinterPlugin? = null
    private var files: FilesPlugin? = null

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        // Direct ESC/POS printing (Bluetooth / USB / built-in). Android's print
        // framework can't be silenced, so auto-print goes through here instead.
        val plugin = PrinterPlugin(applicationContext)
        printer = plugin
        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, PrinterPlugin.CHANNEL)
            .setMethodCallHandler(plugin)
        // Report PDFs: save to Downloads, send straight to WhatsApp. Needs the
        // activity (not the app context) to start WhatsApp's share screen.
        val exporter = FilesPlugin(this)
        files = exporter
        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, FilesPlugin.CHANNEL)
            .setMethodCallHandler(exporter)
    }

    override fun onDestroy() {
        printer?.dispose()
        printer = null
        files?.dispose()
        files = null
        super.onDestroy()
    }
}
