/**
 * Print every positioned text fragment of a PDF as JSON.
 *
 *   node tools/pdf-words.mjs <file.pdf> [maxPages]
 *
 * `disableCombineTextItems` is the whole point: without it pdf.js glues a row's
 * cells into one string ("HL MAKEUP TRAY 39231090 300.00") and the column
 * positions — which is all an invoice table really is — are lost. With it every
 * cell keeps its own x, so App\Services\InvoiceScan\TableReader can rebuild the
 * grid. Coordinates come back with a TOP-LEFT origin, in points, to match the
 * pixel coordinates tesseract reports for scanned invoices.
 */
import { readFileSync } from 'node:fs'
import * as pdfjs from 'pdfjs-dist/legacy/build/pdf.mjs'

const [file, maxPagesArg] = process.argv.slice(2)

if (!file) {
    process.stderr.write('usage: node tools/pdf-words.mjs <file.pdf> [maxPages]\n')
    process.exit(2)
}

const maxPages = Math.max(1, parseInt(maxPagesArg || '20', 10) || 20)

try {
    const doc = await pdfjs.getDocument({
        data: new Uint8Array(readFileSync(file)),
        useSystemFonts: true,
        isEvalSupported: false,
        // A vendor invoice is data, not a program: never let the file run its
        // own JavaScript or reach the network while we read it.
        disableFontFace: true,
    }).promise

    const pages = []
    const count = Math.min(doc.numPages, maxPages)

    for (let number = 1; number <= count; number++) {
        const page = await doc.getPage(number)
        const viewport = page.getViewport({ scale: 1 })
        const content = await page.getTextContent({ disableCombineTextItems: true })

        const words = []
        for (const item of content.items) {
            const text = (item.str || '').trim()
            if (text === '') {
                continue
            }

            // Util.transform maps text space onto the viewport, so a rotated
            // page still lands in reading order instead of on its side.
            const [, , , , x, y] = pdfjs.Util.transform(viewport.transform, item.transform)
            const height = Math.abs(item.height) || Math.abs(item.transform[3]) || 8

            words.push({
                x: round(x),
                // y is the glyph baseline; lift it to the top of the line.
                y: round(y - height),
                w: round(Math.abs(item.width)),
                h: round(height),
                text,
            })
        }

        pages.push({
            number,
            width: round(viewport.width),
            height: round(viewport.height),
            words,
        })
    }

    process.stdout.write(JSON.stringify({ pages, pageCount: doc.numPages }))
} catch (error) {
    process.stderr.write(String(error && error.message ? error.message : error))
    process.exit(1)
}

function round(value) {
    return Math.round((Number(value) || 0) * 100) / 100
}
