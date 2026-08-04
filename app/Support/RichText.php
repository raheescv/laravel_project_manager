<?php

namespace App\Support;

/**
 * Shared sanitiser / renderer for the small rich-text (HTML) fields edited with
 * the <x-rich-text-editor> component.
 *
 * Anything a user types in the editor is stored as HTML, so every value passes
 * through sanitize() on the way in and toHtml() on the way out. Only a short
 * allow-list of formatting tags and attributes survives — enough for headings,
 * lists, emphasis, alignment and RTL blocks (Arabic clauses), nothing that can
 * execute or fetch.
 *
 * Values saved before a field became rich text are plain text; toHtml() detects
 * that and renders them escaped with their line breaks kept, so nothing has to
 * be migrated.
 */
class RichText
{
    /** Tags kept as-is. Everything else is unwrapped (text kept) or dropped. */
    public const ALLOWED_TAGS = [
        'p', 'br', 'div', 'span', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'sub', 'sup',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'blockquote', 'hr', 'a',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
    ];

    /** Tags removed together with their contents — never unwrapped. */
    private const DROPPED_TAGS = [
        'script', 'style', 'iframe', 'object', 'embed', 'applet', 'form', 'input',
        'button', 'select', 'textarea', 'link', 'meta', 'base', 'head', 'title', 'svg',
    ];

    /** Attributes kept, per tag. '*' applies to every allowed tag. */
    private const ALLOWED_ATTRIBUTES = [
        '*' => ['dir', 'style'],
        'a' => ['href', 'target', 'rel'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
    ];

    /**
     * CSS declarations kept inside a surviving style attribute.
     *
     * Deliberately short: a contenteditable copies computed values into the markup
     * (Chrome writes `font-size: 0.84rem` spans of the editor's own body size), and
     * those then fight the print stylesheet. Only choices the author actually made
     * through the toolbar survive.
     */
    private const ALLOWED_STYLES = [
        'text-align', 'direction', 'font-weight', 'font-style',
        'text-decoration', 'text-decoration-line', 'margin-left', 'margin-right',
    ];

    /** Indent offsets are the only lengths worth keeping — anything exotic is dropped. */
    private const LENGTH_STYLES = ['margin-left', 'margin-right'];

    /** Elements that own a line of their own, and so can carry a direction. */
    private const BLOCK_TAGS = [
        'p', 'div', 'li', 'blockquote', 'td', 'th',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    ];

    private const SAFE_URL_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /** Strip everything outside the allow-list and return storable HTML. */
    public static function sanitize(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        // The charset hint stops DOMDocument reading UTF-8 (Arabic) as latin-1.
        $document->loadHTML(
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"><body>'.$html.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $document->getElementsByTagName('body')->item(0);
        if (! $body) {
            return '';
        }

        self::cleanChildren($body);
        self::tidy($body);

        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= $document->saveHTML($child);
        }

        return trim($out);
    }

    /** Render a stored value for output — HTML is sanitised, plain text is escaped. */
    public static function toHtml(?string $value): string
    {
        $value = (string) $value;
        if (trim($value) === '') {
            return '';
        }

        return self::looksLikeHtml($value)
            ? self::sanitize($value)
            : nl2br(e($value), false);
    }

    /** Flatten a rich-text value to a single-line plain string (previews, exports, SMS…). */
    public static function toPlainText(?string $value): string
    {
        $value = (string) $value;
        if (trim($value) === '') {
            return '';
        }

        $text = preg_replace('#<(br|/p|/li|/h[1-6]|/div|/tr)[^>]*>#i', "\n", $value);
        $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = preg_replace("/[ \t]+/", ' ', $text);
        $text = preg_replace("/\n\s*\n\s*/", "\n", (string) $text);

        return trim((string) $text);
    }

    /** True when the value carries no visible content (an "empty" editor still posts <p><br></p>). */
    public static function isBlank(?string $value): bool
    {
        if (trim((string) $value) === '') {
            return true;
        }

        if (preg_match('/<(img|hr|table|td)\b/i', (string) $value)) {
            return false;
        }

        return self::toPlainText($value) === '';
    }

    public static function looksLikeHtml(?string $value): bool
    {
        return (bool) preg_match('/<\/?[a-z][^>]*>/i', (string) $value);
    }

    // ─── Internals ───────────────────────────────────────────────────

    /**
     * Second pass over the surviving tree, undoing what pasting from Word or a
     * word-processor habit leaves behind:
     *   • runs of spaces / non-breaking spaces used to "centre" a line,
     *   • stacks of blank paragraphs used as vertical spacing,
     *   • Arabic lines with no direction, which otherwise print left-aligned with
     *     their full stop on the wrong side.
     */
    private static function tidy(\DOMNode $node): void
    {
        self::collapseWhitespace($node);
        self::dropStackedBlanks($node);
        self::applyDirection($node);
    }

    private static function collapseWhitespace(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMText) {
                // Two or more spaces never render as more than one anyway.
                $child->nodeValue = (string) preg_replace('/[ \t\x{00A0}]{2,}/u', ' ', $child->nodeValue);

                continue;
            }

            if ($child instanceof \DOMElement) {
                self::collapseWhitespace($child);
            }
        }
    }

    /** Keep a single blank line where the author left one, drop the pile-ups. */
    private static function dropStackedBlanks(\DOMNode $node): void
    {
        $previousWasBlank = false;

        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof \DOMElement) {
                continue;
            }

            if (! in_array(strtolower($child->tagName), self::BLOCK_TAGS, true)) {
                self::dropStackedBlanks($child);
                $previousWasBlank = false;

                continue;
            }

            $isBlank = self::isBlank($child->ownerDocument->saveHTML($child));

            if ($isBlank && $previousWasBlank) {
                $child->parentNode?->removeChild($child);

                continue;
            }

            if (! $isBlank) {
                self::dropStackedBlanks($child);
            }

            $previousWasBlank = $isBlank;
        }
    }

    private static function applyDirection(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof \DOMElement) {
                continue;
            }

            if (in_array(strtolower($child->tagName), self::BLOCK_TAGS, true)
                && $child->getAttribute('dir') === ''
                && self::isMostlyArabic($child->textContent)) {
                $child->setAttribute('dir', 'rtl');
            }

            self::applyDirection($child);
        }
    }

    /** True when a line reads right-to-left — more Arabic letters than Latin ones. */
    private static function isMostlyArabic(string $text): bool
    {
        $arabic = preg_match_all('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FEFF}]/u', $text);
        if (! $arabic) {
            return false;
        }

        return $arabic > preg_match_all('/[A-Za-z]/', $text);
    }

    private static function cleanChildren(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }

            if (! $child instanceof \DOMElement) {
                // Comments, processing instructions, CDATA — nothing to keep.
                $child->parentNode?->removeChild($child);

                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROPPED_TAGS, true)) {
                $child->parentNode?->removeChild($child);

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                // Unknown wrapper (font, section, o:p from Word…): keep the words, lose the tag.
                self::cleanChildren($child);
                self::unwrap($child);

                continue;
            }

            self::cleanAttributes($child, $tag);
            self::cleanChildren($child);
        }
    }

    private static function unwrap(\DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (! $parent) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    private static function cleanAttributes(\DOMElement $element, string $tag): void
    {
        $allowed = array_merge(self::ALLOWED_ATTRIBUTES['*'], self::ALLOWED_ATTRIBUTES[$tag] ?? []);

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->nodeName);

            if (! in_array($name, $allowed, true)) {
                $element->removeAttribute($attribute->nodeName);

                continue;
            }

            if ($name === 'style') {
                $style = self::cleanStyle($attribute->nodeValue);
                $style === '' ? $element->removeAttribute('style') : $element->setAttribute('style', $style);

                continue;
            }

            if ($name === 'dir' && ! in_array(strtolower((string) $attribute->nodeValue), ['ltr', 'rtl', 'auto'], true)) {
                $element->removeAttribute('dir');

                continue;
            }

            if ($name === 'href' && ! self::isSafeUrl((string) $attribute->nodeValue)) {
                $element->removeAttribute('href');
            }
        }

        // An external link should never hand the opener over to the target page.
        if ($tag === 'a' && $element->getAttribute('target') !== '') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function cleanStyle(?string $style): string
    {
        $kept = [];

        foreach (explode(';', (string) $style) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }

            [$property, $value] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);

            if (! in_array($property, self::ALLOWED_STYLES, true) || $value === '') {
                continue;
            }

            // url(), expression() and any scheme are the only ways a style can fetch or execute.
            if (preg_match('/url\(|expression|javascript:|@import/i', $value)) {
                continue;
            }

            if (in_array($property, self::LENGTH_STYLES, true) && ! preg_match('/^\d+(\.\d+)?(px|em|rem|%)$/', $value)) {
                continue;
            }

            $kept[] = $property.': '.$value;
        }

        return implode('; ', $kept);
    }

    private static function isSafeUrl(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || str_starts_with($url, '#') || str_starts_with($url, '/')) {
            return true;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return $scheme !== '' && in_array($scheme, self::SAFE_URL_SCHEMES, true);
    }
}
