<?php

return [
    /*
     * The python that has WeasyPrint installed (`pip install weasyprint`, plus the
     * Pango system library). It runs tools/weasyprint-pdf.py.
     *
     * Documents that support WeasyPrint use it only when this is set, and render
     * through Browsershot (Chrome) otherwise — so a server without WeasyPrint keeps
     * printing exactly as before.
     *
     * WEASYPRINT_PYTHON_BINARY=/usr/bin/python3
     */
    'python_binary' => env('WEASYPRINT_PYTHON_BINARY'),

    /** Seconds one render may take before it is killed. */
    'timeout' => (int) env('WEASYPRINT_TIMEOUT', 60),
];
