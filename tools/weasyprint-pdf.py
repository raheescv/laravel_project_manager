"""
Render HTML (read from stdin) to a PDF with WeasyPrint.

    python3 tools/weasyprint-pdf.py <out.pdf>

Prints {"pages": N, "foot_gap": px} on stdout. pages saves the caller parsing
the PDF (WeasyPrint compresses its page tree, so /Count is not readable from the
bytes). foot_gap is the empty height, in CSS px, left under the element marked
data-pdf-foot on the page where it ends — the caller re-renders with that much
margin above it to pin it to the foot of the page. null when nothing is marked.

Only data: URIs are fetched. Every image, font and stylesheet a document needs
travels inside the HTML, the same rule Browsershot enforces with blockDomains —
tenant-entered rich text must never make the server request a URL.
"""
import json
import sys

from weasyprint import HTML

try:
    # Newer WeasyPrint (70 has it): fetchers are URLFetcher instances.
    from weasyprint import URLFetcher

    def data_uri_fetcher():
        return URLFetcher(allowed_protocols={'data'})
except ImportError:
    # Older WeasyPrint (66 has it): fetchers are plain functions.
    from weasyprint import default_url_fetcher

    def data_uri_fetcher():
        def fetch(url, *args, **kwargs):
            if not url.startswith('data:'):
                raise ValueError('external resources are blocked: ' + url[:80])

            return default_url_fetcher(url, *args, **kwargs)

        return fetch


def foot_gap(document):
    gap = None

    for page in document.pages:
        page_box = page._page_box
        for box in page_box.descendants():
            element = getattr(box, 'element', None)
            if element is not None and element.get('data-pdf-foot') is not None:
                page_bottom = page_box.content_box_y() + page_box.height
                gap = max(0.0, page_bottom - (box.position_y + box.margin_height()))
                break

    return gap


def main():
    if len(sys.argv) != 2:
        sys.stderr.write('usage: python3 tools/weasyprint-pdf.py <out.pdf>\n')
        return 2

    html = sys.stdin.buffer.read().decode('utf-8')
    document = HTML(string=html, url_fetcher=data_uri_fetcher()).render()
    document.write_pdf(sys.argv[1])
    sys.stdout.write(json.dumps({'pages': len(document.pages), 'foot_gap': foot_gap(document)}))

    return 0


if __name__ == '__main__':
    sys.exit(main())
