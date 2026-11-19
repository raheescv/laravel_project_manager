package com.astraqatar.invo

import android.media.AudioManager
import android.media.ToneGenerator
import android.os.Handler
import android.os.Looper
import android.util.Log
import io.flutter.plugin.common.MethodCall
import io.flutter.plugin.common.MethodChannel

/**
 * Short feedback tones for the till — so far the "card refused" buzz.
 *
 * Android plays its own beep the moment an NFC card is read, before the app
 * knows whether the card is any good. A card that turns out unknown, blocked
 * or unreadable needs a sound of its own, distinct from that beep, so the
 * cashier hears the failure without looking at the screen. ToneGenerator needs
 * no sound files and no extra package. Dart side: `feedback_sound.dart`.
 */
class SoundPlugin : MethodChannel.MethodCallHandler {

    companion object {
        const val CHANNEL = "qloud/sound"
        private const val TAG = "QloudSound"
        private const val ERROR_MS = 400
    }

    private val main = Handler(Looper.getMainLooper())
    private var tones: ToneGenerator? = null

    override fun onMethodCall(call: MethodCall, result: MethodChannel.Result) {
        when (call.method) {
            "error" -> {
                play(ToneGenerator.TONE_SUP_ERROR, ERROR_MS)
                result.success(null)
            }
            else -> result.notImplemented()
        }
    }

    private fun play(tone: Int, durationMs: Int) {
        try {
            // Created on first use and kept: building one per tap can lag, and a
            // device may refuse a burst of them.
            val generator = tones ?: ToneGenerator(AudioManager.STREAM_NOTIFICATION, ToneGenerator.MAX_VOLUME).also { tones = it }
            generator.startTone(tone, durationMs)
        } catch (e: RuntimeException) {
            // No audio route (or the device refused a generator): a missing
            // buzz must never break the sale; the screen still says why.
            Log.w(TAG, "Could not play tone", e)
            release()
        }
    }

    private fun release() {
        main.post {
            tones?.release()
            tones = null
        }
    }

    fun dispose() = release()
}
