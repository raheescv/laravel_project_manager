package com.astrasalon.invo

import android.app.Activity
import android.content.ActivityNotFoundException
import android.content.ClipData
import android.content.ContentValues
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import android.os.Environment
import android.os.Handler
import android.os.Looper
import android.provider.MediaStore
import android.util.Log
import androidx.core.content.FileProvider
import io.flutter.plugin.common.MethodCall
import io.flutter.plugin.common.MethodChannel
import java.io.File
import java.util.concurrent.Executors

/**
 * Getting an exported PDF (the Reports screen's A4 report) out of the app.
 *
 * `printing` already covers the print dialog and the share sheet. What it can't
 * do is the two things a manager actually asks for: drop a copy in the public
 * Downloads folder, and hand the file straight to WhatsApp without a detour
 * through the chooser. Both live here. Dart side: `pdf_export.dart`.
 */
class FilesPlugin(private val activity: Activity) : MethodChannel.MethodCallHandler {

    companion object {
        const val CHANNEL = "qloud/files"
        private const val TAG = "QloudFiles"

        // WhatsApp first, then WhatsApp Business — a shop phone often carries
        // only the latter. Both are listed under <queries> in the manifest, or
        // Android 11+ reports them as not installed.
        private val WHATSAPP = listOf("com.whatsapp", "com.whatsapp.w4b")
    }

    // File writes stay off the platform thread; a long report runs to a few
    // hundred KB.
    private val io = Executors.newSingleThreadExecutor()
    private val main = Handler(Looper.getMainLooper())

    override fun onMethodCall(call: MethodCall, result: MethodChannel.Result) {
        if (call.method != "saveToDownloads" && call.method != "shareToWhatsApp") {
            result.notImplemented()
            return
        }
        val bytes = call.argument<ByteArray>("bytes")
        val name = call.argument<String>("name")?.let(::safeName)
        if (bytes == null || name.isNullOrEmpty()) {
            result.error("bad_args", "bytes and name are required", null)
            return
        }
        io.execute {
            if (call.method == "saveToDownloads") {
                val saved = saveToDownloads(bytes, name, call.argument<String>("mime") ?: "application/pdf")
                main.post { result.success(saved) }
            } else {
                val file = writeShareCopy(bytes, name)
                main.post { result.success(file != null && openWhatsApp(file, call.argument<String>("text"))) }
            }
        }
    }

    /**
     * Android 10+ writes through MediaStore, which needs no storage permission.
     * Older versions would need WRITE_EXTERNAL_STORAGE, which this app doesn't
     * hold, so they return null and Dart falls back to the share sheet.
     *
     * Returns the location the user will find it at, e.g. `Download/report.pdf`.
     */
    private fun saveToDownloads(bytes: ByteArray, name: String, mime: String): String? {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.Q) return null
        val resolver = activity.contentResolver
        val values = ContentValues().apply {
            put(MediaStore.MediaColumns.DISPLAY_NAME, name)
            put(MediaStore.MediaColumns.MIME_TYPE, mime)
            put(MediaStore.MediaColumns.RELATIVE_PATH, Environment.DIRECTORY_DOWNLOADS)
            put(MediaStore.MediaColumns.IS_PENDING, 1)
        }
        val uri = resolver.insert(MediaStore.Downloads.EXTERNAL_CONTENT_URI, values) ?: return null
        return try {
            resolver.openOutputStream(uri)?.use { it.write(bytes) }
                ?: throw IllegalStateException("no output stream for $uri")
            resolver.update(uri, ContentValues().apply { put(MediaStore.MediaColumns.IS_PENDING, 0) }, null, null)
            // MediaStore renames a clash to `name (1).pdf` — report what it kept.
            val kept = resolver.query(uri, arrayOf(MediaStore.MediaColumns.DISPLAY_NAME), null, null, null)
                ?.use { c -> if (c.moveToFirst()) c.getString(0) else null }
            "${Environment.DIRECTORY_DOWNLOADS}/${kept ?: name}"
        } catch (e: Exception) {
            Log.w(TAG, "saveToDownloads failed", e)
            resolver.delete(uri, null, null)
            null
        }
    }

    /** The copy WhatsApp reads, in the one folder the `.exports` provider exposes. */
    private fun writeShareCopy(bytes: ByteArray, name: String): File? = try {
        val dir = File(activity.cacheDir, "exports").apply { mkdirs() }
        File(dir, name).apply { writeBytes(bytes) }
    } catch (e: Exception) {
        Log.w(TAG, "writeShareCopy failed", e)
        null
    }

    /** False when neither WhatsApp is installed — Dart opens the share sheet then. */
    private fun openWhatsApp(file: File, text: String?): Boolean {
        val pkg = WHATSAPP.firstOrNull(::isInstalled) ?: return false
        return try {
            val uri = FileProvider.getUriForFile(activity, "${activity.packageName}.exports", file)
            val intent = Intent(Intent.ACTION_SEND).apply {
                type = "application/pdf"
                putExtra(Intent.EXTRA_STREAM, uri)
                if (!text.isNullOrBlank()) putExtra(Intent.EXTRA_TEXT, text)
                // The ClipData is what actually carries the read grant on newer
                // Android; EXTRA_STREAM alone is not always honoured.
                clipData = ClipData.newRawUri(file.name, uri)
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
                setPackage(pkg)
            }
            activity.startActivity(intent)
            true
        } catch (e: ActivityNotFoundException) {
            false
        } catch (e: IllegalArgumentException) {
            Log.w(TAG, "no FileProvider for ${file.path}", e)
            false
        }
    }

    private fun isInstalled(pkg: String): Boolean = try {
        val pm = activity.packageManager
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            pm.getPackageInfo(pkg, PackageManager.PackageInfoFlags.of(0))
        } else {
            @Suppress("DEPRECATION")
            pm.getPackageInfo(pkg, 0)
        }
        true
    } catch (e: PackageManager.NameNotFoundException) {
        false
    }

    /** One plain file name — no separators that could step out of the folder. */
    private fun safeName(name: String) = name.replace(Regex("[\\\\/:*?\"<>|]"), "_").trim()

    fun dispose() {
        io.shutdown()
    }
}
