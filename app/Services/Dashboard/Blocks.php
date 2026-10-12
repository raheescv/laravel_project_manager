<?php

namespace App\Services\Dashboard;

/**
 * Builders for the display blocks a widget payload is made of. The Vue
 * dashboard has one renderer per block type, so a widget is described here as
 * data and never needs its own front-end component.
 *
 * Tones are the dashboard palette: acc, ok, warn, bad, info, slate, c2, c3, c4.
 * Formats are money, number, percent and days; the browser formats the value.
 */
final class Blocks
{
    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    public static function kpis(array $items): array
    {
        return ['type' => 'kpis', 'items' => array_values(array_filter($items))];
    }

    /**
     * @param  array{value: float, invert?: bool, hint?: string}|null  $delta
     * @return array<string, mixed>
     */
    public static function kpi(string $label, float|int $value, string $icon, string $tone = 'acc', string $format = 'number', ?string $sub = null, ?array $delta = null, ?string $url = null): array
    {
        return compact('label', 'value', 'icon', 'tone', 'format', 'sub', 'delta', 'url');
    }

    /**
     * @return array<string, mixed>
     */
    public static function stat(string $label, float|int $value, string $format = 'money', ?string $note = null, ?string $tone = null): array
    {
        return ['type' => 'stat'] + compact('label', 'value', 'format', 'note', 'tone');
    }

    /**
     * One figure in a coloured, gradient-filled box (centred value, label below).
     *
     * @return array<string, mixed>
     */
    public static function highlight(string $label, float|int $value, string $format = 'money', string $tone = 'c2'): array
    {
        return ['type' => 'highlight'] + compact('label', 'value', 'format', 'tone');
    }

    /**
     * Plain label / value rows; a row's `tone` colours its value.
     *
     * @param  list<array{label: string, value: float|int, format: string, tone?: string}>  $rows
     * @return array<string, mixed>
     */
    public static function facts(array $rows): array
    {
        return ['type' => 'facts', 'rows' => $rows];
    }

    /**
     * @param  list<string>  $labels
     * @param  list<array{name: string, tone: string, values: list<float|int>}>  $series
     * @return array<string, mixed>
     */
    public static function bars(array $labels, array $series, string $format = 'money', int $height = 170, bool $gradient = false, bool $legend = false): array
    {
        return ['type' => 'bars'] + compact('labels', 'series', 'format', 'height', 'gradient', 'legend');
    }

    /**
     * @param  list<float|int>  $values
     * @return array{name: string, tone: string, values: list<float>}
     */
    public static function series(string $name, string $tone, array $values): array
    {
        return ['name' => $name, 'tone' => $tone, 'values' => array_map(fn ($value): float => (float) $value, array_values($values))];
    }

    /**
     * @param  list<array{name: string, value: float|int, tone: string}>  $slices
     * @param  array{value: float|int, format: string, label: string}|null  $center
     * @return array<string, mixed>
     */
    public static function donut(array $slices, string $format, ?array $center = null, bool $pie = false): array
    {
        return ['type' => 'donut'] + compact('slices', 'format', 'center', 'pie');
    }

    /**
     * A filled pie: percentages on the slices, the legend underneath.
     *
     * @param  list<array{name: string, value: float|int, tone: string}>  $slices
     * @return array<string, mixed>
     */
    public static function pie(array $slices, string $format): array
    {
        return self::donut($slices, $format, pie: true);
    }

    /**
     * Rows of label, value and a bar. A row may carry `stack` (several coloured
     * segments) instead of a single `percent`.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public static function meters(array $rows, string $empty = 'Nothing to show yet.'): array
    {
        return ['type' => 'meters'] + compact('rows', 'empty');
    }

    /**
     * @param  list<array{icon: string, tone: string, title: string, sub: string, badge?: array{text: string, tone: string}|null, url?: string|null}>  $rows
     * @return array<string, mixed>
     */
    public static function list(array $rows, string $empty = 'Nothing to show yet.'): array
    {
        return ['type' => 'list'] + compact('rows', 'empty');
    }

    /**
     * Columns are `{label, align?, format?, strong?}`. A cell is a scalar, or an
     * array with one of value+format, badge, bar, rank, or text+tone.
     *
     * @param  list<array<string, mixed>>  $columns
     * @param  list<list<mixed>>  $rows
     * @return array<string, mixed>
     */
    public static function table(array $columns, array $rows, string $empty = 'Nothing to show yet.'): array
    {
        return ['type' => 'table'] + compact('columns', 'rows', 'empty');
    }

    /**
     * @param  list<array{label: string, value: float|int, tone: string}>  $rows
     * @return array<string, mixed>
     */
    public static function dots(string $title, array $rows): array
    {
        return ['type' => 'dots'] + compact('title', 'rows');
    }

    /**
     * @return array<string, mixed>
     */
    public static function alert(string $text, string $tone = 'bad'): array
    {
        return ['type' => 'alert'] + compact('text', 'tone');
    }

    /**
     * @return array<string, mixed>
     */
    public static function progress(string $label, float $percent, string $tone = 'ok', ?string $note = null): array
    {
        return ['type' => 'progress'] + compact('label', 'percent', 'tone', 'note');
    }

    /**
     * Small side-by-side panels: `{label, value, format, tone?, badge?, note?, stack?}`.
     *
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    public static function panels(array $items): array
    {
        return ['type' => 'panels', 'items' => $items];
    }

    /**
     * Columns of blocks side by side; they stack when the widget is narrow.
     *
     * @param  list<array<string, mixed>>  ...$columns
     * @return array<string, mixed>
     */
    public static function split(array ...$columns): array
    {
        return ['type' => 'split', 'columns' => $columns];
    }

    /**
     * @return array{badge: array{text: string, tone: string}}
     */
    public static function badge(string $text, string $tone = 'slate'): array
    {
        return ['badge' => ['text' => $text, 'tone' => $tone]];
    }

    /**
     * @return array{value: float, format: string}
     */
    public static function money(float|int|string|null $value): array
    {
        return ['value' => (float) $value, 'format' => 'money'];
    }
}
