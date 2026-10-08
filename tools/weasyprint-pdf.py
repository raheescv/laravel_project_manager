"""
Render HTML (read from stdin) to a PDF with WeasyPrint.

    python3 tools/weasyprint-pdf.py <out.pdf>

Prints {"pages": N, "foot_shift": px} on stdout: the page count (WeasyPrint
compresses its page tree, so /Count is not readable from the PDF bytes) and how
far the data-pdf-foot element was moved down (0 when it was not).

An element marked data-pdf-foot is pinned to the foot of the page it ends on:
the document is laid out once and the empty height left under that element is
measured. When nothing follows it on that page, the laid-out block is simply
moved down by that much. Otherwise the document is laid out again with that
much padding above it (one parse and one image cache shared), and if that pass
gains a page (rounding) the first is written instead.

Only data: URIs are fetched. Every image, font and stylesheet a document needs
travels inside the HTML, the same rule Browsershot enforces with blockDomains —
tenant-entered rich text must never make the server request a URL.
"""
import json
import sys

from weasyprint import CSS, HTML

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


def find_foot(document):
    """(foot box, gap, followed) for the data-pdf-foot element on the page it ends on.

    gap is the empty height, in CSS px, left at the foot of that page; followed
    is whether anything is laid out after the element there. None when nothing
    is marked.
    """
    found = None

    for page in document.pages:
        page_box = page._page_box
        boxes = list(page_box.descendants())
        for index, box in enumerate(boxes):
            element = getattr(box, 'element', None)
            if element is None or element.get('data-pdf-foot') is None:
                continue

            # Boxes after it in document order that are not inside it: ancestors
            # come before it, so what remains is what follows it on the page.
            inside = {id(child) for child in box.descendants()}
            following = [other for other in boxes[index + 1:] if id(other) not in inside
                         and (other.margin_height() > 0 or other.margin_width() > 0)]
            bottom = max(other.position_y + other.margin_height() for other in [box, *following])
            page_bottom = page_box.content_box_y() + page_box.height
            found = (box, max(0.0, page_bottom - bottom), bool(following))
            break

    return found


def main():
    if len(sys.argv) != 2:
        sys.stderr.write('usage: python3 tools/weasyprint-pdf.py <out.pdf>\n')
        return 2

    fetcher = data_uri_fetcher()
    html = HTML(string=sys.stdin.buffer.read().decode('utf-8'), url_fetcher=fetcher)
    cache = {}

    document = html.render(cache=cache)
    found = find_foot(document)
    shift = 0.0

    if found is not None and found[1] >= 1:
        foot, gap, followed = found
        if not followed:
            # Nothing follows it on that page, so sliding the laid-out block down
            # is the same as laying the page out again with it padded — and free.
            foot.translate(dy=gap - 1)
            shift = gap - 1
        else:
            padding = CSS(string='[data-pdf-foot] { padding-top: %.2fpx; }' % (gap - 1), url_fetcher=fetcher)
            pinned = html.render(cache=cache, stylesheets=[padding])
            if len(pinned.pages) == len(document.pages):
                document = pinned
                shift = gap - 1

    document.write_pdf(sys.argv[1])
    sys.stdout.write(json.dumps({'pages': len(document.pages), 'foot_shift': round(shift, 2)}))

    return 0


if __name__ == '__main__':
    sys.exit(main())
