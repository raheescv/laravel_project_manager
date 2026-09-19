<?php

return [
    /*
     * Binaries used to read an uploaded invoice.
     *
     * node runs tools/pdf-words.mjs (pdfjs-dist) for PDFs that carry a text
     * layer — which is every invoice a vendor emails out of their own software.
     * tesseract is only reached for photographed or scanned invoices; leave it
     * unset and that upload is refused with a message instead of guessing.
     *
     * Left empty both are auto-detected with `which` at runtime, the same way
     * config/browsershot.php does it.
     *
     * INVOICE_SCAN_NODE_BINARY=
     * INVOICE_SCAN_TESSERACT_BINARY=
     */
    'node_binary' => env('INVOICE_SCAN_NODE_BINARY', env('BROWSERSHOT_NODE_BINARY')),
    'tesseract_binary' => env('INVOICE_SCAN_TESSERACT_BINARY'),

    /** Seconds one extraction may take before it is killed. */
    'timeout' => (int) env('INVOICE_SCAN_TIMEOUT', 120),

    /** Pages read out of one PDF. */
    'max_pages' => (int) env('INVOICE_SCAN_MAX_PAGES', 20),

    /** Hard ceiling on item rows pulled out of one invoice. */
    'max_rows' => (int) env('INVOICE_SCAN_MAX_ROWS', 500),

    'ocr' => [
        /*
         * Whole-page OCR mangles money: on a clean 290dpi render of a real
         * vendor invoice tesseract read 190.68 as 150.68, 800.00 as 600.00 and
         * 915.25 as 91825. Re-reading each numeric cell on its own, upscaled,
         * with the alphabet restricted to digits took the same columns to
         * 14/14 exact. It costs one tesseract call per numeric cell, which is
         * why it can be turned off for very large scans.
         */
        'refine_numeric' => (bool) env('INVOICE_SCAN_OCR_REFINE', true),

        /** Cells are blown up by this factor before the second pass. */
        'refine_scale' => (int) env('INVOICE_SCAN_OCR_REFINE_SCALE', 3),

        /** Page segmentation mode for the first, whole-image pass. */
        'psm' => env('INVOICE_SCAN_OCR_PSM', '6'),

        'language' => env('INVOICE_SCAN_OCR_LANGUAGE', 'eng'),
    ],
];
