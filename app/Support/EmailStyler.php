<?php

namespace App\Support;

/**
 * Inlines the Editorial look onto tenant-authored email HTML.
 *
 * Email clients strip <style> blocks and never resolve custom properties, so
 * the styling a customer sees has to be inline on every tag. Tenants write
 * plain wording in the editor; this puts the typography on it at render time,
 * which means their template stays clean text and the design can be changed
 * later without rewriting anyone's content.
 *
 * Only tags that carry no style of their own are touched, so a tenant who
 * deliberately styles something keeps it.
 */
class EmailStyler
{
    private const PARAGRAPH = 'margin:0 0 16px;font-family:Georgia,\'Times New Roman\',serif;font-size:15px;line-height:1.8;color:#4a4238;';

    private const HEADING = 'margin:0 0 12px;font-family:Georgia,\'Times New Roman\',serif;font-weight:400;letter-spacing:-.02em;color:#1a1a1a;';

    private const LIST = 'margin:0 0 16px;padding-inline-start:20px;font-family:Georgia,\'Times New Roman\',serif;font-size:15px;line-height:1.8;color:#4a4238;';

    private const STRONG = 'color:#1a1a1a;font-weight:700;';

    public static function editorial(?string $html, string $accent = '#1D4ED8'): string
    {
        $html = (string) $html;

        $html = self::style($html, 'p', self::PARAGRAPH);
        $html = self::style($html, 'h1', self::HEADING.'font-size:26px;line-height:1.25;');
        $html = self::style($html, 'h2', self::HEADING.'font-size:21px;line-height:1.3;');
        $html = self::style($html, 'h3', self::HEADING.'font-size:18px;line-height:1.35;');
        $html = self::style($html, 'ul', self::LIST);
        $html = self::style($html, 'ol', self::LIST);
        $html = self::style($html, 'strong', self::STRONG);
        $html = self::style($html, 'b', self::STRONG);
        $html = self::style($html, 'a', "color:{$accent};text-decoration:underline;");

        return $html;
    }

    /** A complete, self-contained Editorial call-to-action button. */
    public static function button(string $url, string $label, string $accent = '#1D4ED8'): string
    {
        $url = e($url);
        $label = e($label);

        return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" '
            .'style="margin:26px auto 8px;"><tr><td align="center" '
            ."style=\"border:1px solid {$accent};border-radius:2px;\">"
            ."<a href=\"{$url}\" style=\"display:inline-block;padding:13px 34px;color:{$accent};"
            .'font-family:\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:11px;letter-spacing:.18em;'
            ."text-transform:uppercase;font-weight:700;text-decoration:none;\">{$label}</a>"
            .'</td></tr></table>';
    }

    /** Adds a style attribute to every occurrence of a tag that has none. */
    private static function style(string $html, string $tag, string $style): string
    {
        return preg_replace_callback(
            '/<'.$tag.'(\s[^>]*)?>/i',
            function (array $m) use ($tag, $style) {
                $attrs = $m[1] ?? '';
                if (stripos($attrs, 'style=') !== false) {
                    return $m[0];   // the tenant styled this deliberately
                }

                return '<'.$tag.$attrs.' style="'.$style.'">';
            },
            $html
        ) ?? $html;
    }
}
