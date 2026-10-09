<?php

namespace App\Http\Controllers;

use App\Support\QzTray;
use Illuminate\Http\Request;

/**
 * The two endpoints qz-tray.js calls before QZ Tray will print silently:
 * the certificate to present, and a signature for each request.
 */
class QzTrayController extends Controller
{
    public function certificate(Request $request)
    {
        QzTray::ensureGenerated();

        $response = response(QzTray::certificate())
            ->header('Content-Type', 'text/plain')
            ->header('Cache-Control', 'no-store');

        // Offered from the printer picker so a shop PC can save it as override.crt.
        if ($request->boolean('download')) {
            $response->header('Content-Disposition', 'attachment; filename="override.crt"');
        }

        return $response;
    }

    public function sign(Request $request)
    {
        $validated = $request->validate([
            'request' => ['required', 'string', 'max:4096'],
        ]);

        QzTray::ensureGenerated();

        return response(QzTray::sign($validated['request']))
            ->header('Content-Type', 'text/plain')
            ->header('Cache-Control', 'no-store');
    }
}
