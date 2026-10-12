<script setup>
/**
 * Draws a widget payload: a list of display blocks built on the server by
 * App\Services\Dashboard\Blocks. One renderer per block type, so a new widget
 * needs no front-end work. `split` nests blocks in columns.
 */
import { inject } from 'vue'
import CalendarBlock from './CalendarBlock.vue'

defineProps({ blocks: { type: Array, required: true } })
const emit = defineEmits(['navigate'])

const fmt = inject('fmt')

/** Text in a tone; the accent uses its readable shade (lighter in dark mode). */
const ink = (tone) => (tone ? { color: tone === 'acc' ? 'var(--acc-ink)' : `var(--${tone})` } : null)
const clamp = (value) => Math.max(0, Math.min(100, Number(value) || 0))
/** A round axis ceiling in 4–6 even steps, as tight to the highest bar as possible (5,850 → 0…6,000 by 1,000). */
function axis(series) {
    const high = Math.max(0, ...series.flatMap((s) => s.values))
    if (!high) return { top: 4, ticks: [0, 1, 2, 3, 4] }
    const whole = series.every((s) => s.values.every((v) => Number.isInteger(v)))
    let best = null
    for (const count of [4, 5, 6]) {
        const raw = high / count
        const power = 10 ** Math.floor(Math.log10(raw))
        const steps = [1, 2, 2.5, 5, 10].map((n) => n * power).filter((step) => !whole || Number.isInteger(step))
        const step = steps.find((candidate) => candidate >= raw) ?? Math.ceil(raw)
        if (!best || step * count < best.top) best = { top: step * count, step, count }
    }
    return { top: best.top, ticks: Array.from({ length: best.count + 1 }, (_, i) => i * best.step) }
}
const tick = (value, format) => (Math.abs(value) >= 10000 ? fmt.compact(value, 'number') : fmt.number(value, Number.isInteger(value) ? 0 : 1)) + (format === 'percent' ? '%' : '')
/** Long or crowded labels are angled (all shown) instead of thinned out. */
const tilted = (labels) => labels.length <= 45 && (labels.length > 12 || Math.max(...labels.map((l) => String(l).length)) * labels.length > 70)
const every = (labels) => (tilted(labels) || labels.length <= 14 ? 1 : Math.ceil(labels.length / 8))
const tiltHeight = (labels) => Math.min(120, Math.max(...labels.map((l) => String(l).length)) * 4.6 + 14)

/** Donut arcs as stroke-dash segments on a 100-unit circumference. */
function arcs(slices) {
    const total = slices.reduce((sum, s) => sum + Number(s.value || 0), 0)
    let offset = 0
    return slices.map((slice) => {
        const share = total ? (Number(slice.value || 0) / total) * 100 : 0
        const gap = slices.length > 1 && share > 1 ? 0.8 : 0
        const arc = { ...slice, share, dash: `${Math.max(0, share - gap)} ${100 - share + gap}`, offset: -offset }
        offset += share
        return arc
    })
}

/** Filled-pie slices as SVG paths on a 100-unit box, with label and hover offsets at each slice's middle. */
function pieSlices(slices) {
    const positive = slices.filter((slice) => Number(slice.value) > 0)
    const total = positive.reduce((sum, slice) => sum + Number(slice.value), 0)
    const radius = 48
    const point = (angle, distance = radius) => [50 + distance * Math.cos(angle), 50 + distance * Math.sin(angle)]
    let angle = -Math.PI / 2
    return positive.map((slice) => {
        const share = (Number(slice.value) / total) * 100
        const sweep = (share / 100) * Math.PI * 2
        const [x0, y0] = point(angle)
        const [x1, y1] = point(angle + sweep)
        const middle = angle + sweep / 2
        const [lx, ly] = point(middle, radius * 0.62)
        angle += sweep
        return {
            ...slice, share, lx, ly,
            full: share > 99.99,
            d: `M50 50 L${x0} ${y0} A${radius} ${radius} 0 ${sweep > Math.PI ? 1 : 0} 1 ${x1} ${y1} Z`,
            lift: `translate(${(Math.cos(middle) * 3).toFixed(2)}px, ${(Math.sin(middle) * 3).toFixed(2)}px)`,
        }
    })
}

const shareOf = (slices, slice) => {
    const total = slices.reduce((sum, s) => sum + Math.max(0, Number(s.value) || 0), 0)
    return total ? (Math.max(0, Number(slice.value) || 0) / total) * 100 : 0
}

function cell(value, column) {
    if (value !== null && typeof value === 'object') return value
    if (typeof value === 'number' && column.format) return { value, format: column.format }
    return { plain: value ?? '—' }
}
</script>

<template>
    <template v-for="(b, index) in blocks" :key="index">
        <!-- KPI tiles -->
        <div v-if="b.type === 'kpis'" class="dbx-kpis" :data-n="b.items.length">
            <component :is="k.url ? 'a' : 'div'" v-for="k in b.items" :key="k.label" :href="k.url || undefined" class="dbx-kpi" :class="['t-' + k.tone, { 'is-link': k.url }]">
                <span class="dbx-kpi-ic"><i class="fa" :class="'fa-' + k.icon"></i></span>
                <div class="dbx-kpi-b">
                    <div class="dbx-kpi-l">{{ k.label }}</div>
                    <div class="dbx-kpi-v"><small v-if="fmt.parts(k.value, k.format, { big: true }).prefix">{{ fmt.parts(k.value, k.format, { big: true }).prefix }}</small>{{ fmt.parts(k.value, k.format, { big: true }).text }}</div>
                    <div v-if="k.delta || k.sub" class="dbx-kpi-s">
                        <span v-if="k.delta" class="dbx-delta" :class="(k.delta.invert ? k.delta.value <= 0 : k.delta.value >= 0) ? 'up' : 'down'" :data-tip="k.delta.hint">
                            <i class="fa" :class="k.delta.value >= 0 ? 'fa-caret-up' : 'fa-caret-down'"></i>{{ Math.abs(k.delta.value).toFixed(1) }}%
                        </span>
                        <span v-if="k.sub">{{ k.sub }}</span>
                    </div>
                </div>
            </component>
        </div>

        <!-- One big figure -->
        <div v-else-if="b.type === 'stat'" class="dbx-statb">
            <div class="dbx-lab">{{ b.label }}</div>
            <div class="dbx-big" :style="ink(b.tone)">
                <small v-if="fmt.parts(b.value, b.format, { big: true }).prefix">{{ fmt.parts(b.value, b.format, { big: true }).prefix }}</small>{{ fmt.parts(b.value, b.format, { big: true }).text }}<small v-if="fmt.parts(b.value, b.format).suffix" class="sfx">{{ fmt.parts(b.value, b.format).suffix }}</small>
            </div>
            <div v-if="b.note" class="dbx-lab">{{ b.note }}</div>
        </div>

        <!-- Highlight box -->
        <div v-else-if="b.type === 'highlight'" class="dbx-hl" :class="'t-' + b.tone">
            <b><small v-if="fmt.parts(b.value, b.format).prefix">{{ fmt.parts(b.value, b.format).prefix }}</small>{{ fmt.parts(b.value, b.format).text }}</b>
            <span>{{ b.label }}</span>
        </div>

        <!-- Titled summary cards (Collections: overdue · cheques · security) -->
        <div v-else-if="b.type === 'summary-cards'" class="dbx-sc">
            <div v-for="c in b.items" :key="c.title" class="dbx-sc-c">
                <div class="dbx-sc-h"><i class="fa" :class="['fa-' + c.icon, 't-' + c.tone]"></i>{{ c.title }}</div>
                <div class="dbx-sc-boxes">
                    <div v-for="box in c.boxes" :key="box.label" class="dbx-sc-box" :class="'t-' + box.tone">
                        <b :style="ink(box.tone)">{{ fmt.text(box.value, box.format) }}</b><span>{{ box.label }}</span>
                    </div>
                </div>
                <div v-if="c.stack" class="dbx-track stack lg"><i v-for="(part, i) in c.stack" :key="i" :style="{ width: clamp(part.percent) + '%', background: `var(--${part.tone})` }"></i></div>
                <span v-if="c.badge" class="dbx-sc-badge" :class="'t-' + c.badge.tone"><i class="fa fa-file-text-o"></i>{{ c.badge.text }}</span>
            </div>
        </div>

        <!-- Label / value rows -->
        <div v-else-if="b.type === 'facts'" class="dbx-facts">
            <div v-for="f in b.rows" :key="f.label"><span>{{ f.label }}</span><b :style="ink(f.tone)">{{ fmt.text(f.value, f.format) }}</b></div>
        </div>

        <!-- Bars, with a value axis -->
        <div v-else-if="b.type === 'bars'" class="dbx-chart">
            <div v-if="b.legend" class="dbx-leg">
                <span v-for="s in b.series" :key="s.name" :style="{ '--bc': `var(--${s.tone})` }"><i></i>{{ s.name }}</span>
            </div>
            <div v-if="!b.labels.length" class="dbx-none">Nothing to chart yet.</div>
            <div v-else class="dbx-plot">
                <div class="dbx-yax" :style="{ height: b.height + 'px' }">
                    <span v-for="(t, i) in axis(b.series).ticks" :key="i" :style="{ bottom: (i / (axis(b.series).ticks.length - 1)) * 100 + '%' }">{{ tick(t, b.format) }}</span>
                </div>
                <div class="dbx-plot-m">
                    <div class="dbx-cols" :style="{ '--h': b.height + 'px', '--n': axis(b.series).ticks.length - 1 }">
                        <div v-for="(label, i) in b.labels" :key="label + i" class="dbx-col">
                            <i v-for="s in b.series" :key="s.name" class="dbx-bv" :class="{ grad: b.gradient }"
                                :style="{ '--v': (s.values[i] / axis(b.series).top) * 100 + '%', '--bc': `var(--${s.tone})`, animationDelay: i * 18 + 'ms' }"
                                :data-tip="`${label} · ${s.name}: ${fmt.text(s.values[i], b.format)}`"></i>
                        </div>
                    </div>
                    <div class="dbx-xl" :class="{ tilt: tilted(b.labels) }" :style="tilted(b.labels) ? { height: tiltHeight(b.labels) + 'px' } : null">
                        <span v-for="(label, i) in b.labels" :key="'l' + i"><em :title="label">{{ i % every(b.labels) === 0 ? label : '' }}</em></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filled pie -->
        <div v-else-if="b.type === 'donut' && b.pie" class="dbx-pie-w">
            <svg class="dbx-pie" viewBox="0 0 100 100" role="img" :aria-label="b.slices.map((s) => `${s.name} ${fmt.text(s.value, b.format)}`).join(', ')">
                <circle v-if="!pieSlices(b.slices).length" cx="50" cy="50" r="48" style="fill: var(--line-soft)" />
                <g v-for="s in pieSlices(b.slices)" :key="s.name" class="dbx-pie-s" :style="{ '--lift': s.lift }" :data-tip="`${s.name}: ${fmt.text(s.value, b.format)} · ${s.share.toFixed(1)}%`">
                    <circle v-if="s.full" cx="50" cy="50" r="48" :style="{ fill: `var(--${s.tone})` }" />
                    <path v-else :d="s.d" :style="{ fill: `var(--${s.tone})` }" />
                    <text v-if="s.share >= 6" :x="s.lx" :y="s.ly" class="dbx-pie-t">{{ Math.round(s.share) }}%</text>
                </g>
            </svg>
            <div class="dbx-pleg">
                <div v-for="s in b.slices" :key="s.name" :style="{ '--bc': `var(--${s.tone})` }">
                    <i></i><span>{{ s.name }}</span>
                    <b>{{ fmt.text(s.value, b.format) }}</b><em>{{ shareOf(b.slices, s).toFixed(0) }}%</em>
                </div>
                <div v-if="!pieSlices(b.slices).length" class="dbx-none sm">Nothing recorded yet this month.</div>
            </div>
        </div>

        <!-- Donut: ring with the total inside, legend beside it (wraps into columns when there are many slices) -->
        <div v-else-if="b.type === 'donut'" class="dbx-donut-w" :class="{ many: b.slices.length > 6 }">
            <div class="dbx-donut">
                <svg viewBox="0 0 42 42" aria-hidden="true">
                    <circle cx="21" cy="21" r="15.915" style="stroke: var(--line-soft)" />
                    <circle v-for="a in arcs(b.slices)" :key="a.name" cx="21" cy="21" r="15.915" :style="{ stroke: `var(--${a.tone})` }"
                        :stroke-dasharray="a.dash" :stroke-dashoffset="a.offset" :data-tip="`${a.name}: ${fmt.text(a.value, b.format)} · ${a.share.toFixed(1)}%`" />
                </svg>
                <div v-if="b.center" class="dbx-donut-c"><b>{{ fmt.compact(b.center.value, 'number') }}</b><span>{{ b.center.label }}</span></div>
            </div>
            <div class="dbx-dleg">
                <div v-if="!b.slices.length" class="dbx-none">Nothing to chart yet.</div>
                <div v-for="a in arcs(b.slices)" :key="a.name" :style="{ '--bc': `var(--${a.tone})` }" :data-tip="`${a.name}: ${fmt.text(a.value, b.format)} · ${a.share.toFixed(1)}%`">
                    <i></i><span>{{ a.name }}</span><em>{{ fmt.compact(a.value, b.format) }}<small>{{ a.share < 1 && a.share > 0 ? '<1' : a.share.toFixed(0) }}%</small></em>
                </div>
            </div>
        </div>

        <!-- Label · value · bar rows -->
        <div v-else-if="b.type === 'meters'" class="dbx-meters">
            <div v-if="!b.rows.length" class="dbx-none">{{ b.empty }}</div>
            <div v-for="(m, i) in b.rows" :key="m.label + i" class="dbx-meter" :class="'t-' + (m.tone || 'acc')">
                <span>{{ m.label }}</span>
                <span>
                    {{ m.text ?? fmt.text(m.value, m.format) }}<em v-if="m.note">· {{ m.note }}</em>
                    <span v-if="m.badge" class="dbx-badge" :class="'t-' + m.badge.tone">{{ m.badge.text }}</span>
                </span>
                <div class="dbx-track" :class="{ stack: m.stack }">
                    <template v-if="m.stack"><i v-for="(part, p) in m.stack" :key="p" :style="{ width: clamp(part.percent) + '%', background: `var(--${part.tone})` }"></i></template>
                    <i v-else :style="{ width: clamp(m.percent) + '%' }"></i>
                </div>
            </div>
        </div>

        <!-- Icon list -->
        <div v-else-if="b.type === 'list'" class="dbx-list">
            <div v-if="!b.rows.length" class="dbx-none">{{ b.empty }}</div>
            <component :is="r.url ? 'a' : 'div'" v-for="(r, i) in b.rows" :key="i" :href="r.url || undefined" class="dbx-li" :class="{ 'is-link': r.url }">
                <span class="dbx-li-ic" :class="'t-' + r.tone"><i class="fa" :class="'fa-' + r.icon"></i></span>
                <div class="dbx-li-b"><b>{{ r.title }}</b><span>{{ r.sub }}</span></div>
                <span v-if="r.badge" class="dbx-badge" :class="'t-' + r.badge.tone">{{ r.badge.text }}</span>
            </component>
        </div>

        <!-- Table -->
        <div v-else-if="b.type === 'table'" class="dbx-tbl">
            <div v-if="!b.rows.length" class="dbx-none">{{ b.empty }}</div>
            <table v-else>
                <thead><tr><th v-for="c in b.columns" :key="c.label" :class="{ r: c.align === 'end' }">{{ c.label }}</th></tr></thead>
                <tbody>
                    <tr v-for="(row, r) in b.rows" :key="r">
                        <td v-for="(value, ci) in row" :key="ci" :class="{ r: b.columns[ci]?.align === 'end', ink: b.columns[ci]?.strong }">
                            <template v-for="c in [cell(value, b.columns[ci] || {})]" :key="'c'">
                                <span v-if="c.rank" class="rank">{{ c.rank }}</span>
                                <span v-else-if="c.badge" class="dbx-badge" :class="'t-' + c.badge.tone">{{ c.badge.text }}</span>
                                <span v-else-if="c.bar" class="dbx-tbar"><span class="dbx-track" :class="'t-' + c.bar.tone"><i :style="{ width: clamp(c.bar.percent) + '%' }"></i></span><b>{{ Math.round(c.bar.percent) }}%</b></span>
                                <a v-else-if="c.url" :href="c.url">{{ c.text }}</a>
                                <span v-else-if="c.format" :style="ink(c.tone)">{{ fmt.text(c.value, c.format) }}</span>
                                <template v-else>{{ c.plain ?? c.text }}</template>
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Dot breakdown -->
        <div v-else-if="b.type === 'dots'" class="dbx-dots">
            <div class="dbx-sub-h">{{ b.title }}</div>
            <div v-for="d in b.rows" :key="d.label" class="dbx-dot" :class="'t-' + d.tone">{{ d.label }}<b>{{ fmt.number(d.value) }}</b></div>
        </div>

        <!-- Alert strip -->
        <div v-else-if="b.type === 'alert'" class="dbx-alert" :class="'t-' + b.tone"><i class="fa fa-exclamation-circle"></i><span>{{ b.text }}</span></div>

        <!-- Progress -->
        <div v-else-if="b.type === 'progress'" class="dbx-prog">
            <div class="dbx-prog-h"><span>{{ b.label }}</span><b>{{ fmt.text(b.percent, 'percent') }}</b></div>
            <div class="dbx-track lg" :class="'t-' + b.tone"><i :style="{ width: clamp(b.percent) + '%' }"></i></div>
            <div v-if="b.note" class="dbx-lab">{{ b.note }}</div>
        </div>

        <!-- Side-by-side panels -->
        <div v-else-if="b.type === 'panels'" class="dbx-panels">
            <div v-for="p in b.items" :key="p.label" class="dbx-panel">
                <div class="dbx-lab">{{ p.label }}</div>
                <div class="dbx-big sm" :style="ink(p.tone)">
                    <small v-if="fmt.parts(p.value, p.format, { big: true }).prefix">{{ fmt.parts(p.value, p.format, { big: true }).prefix }}</small>{{ fmt.parts(p.value, p.format, { big: true }).text }}
                </div>
                <div v-if="p.note" class="dbx-lab">{{ p.note }}</div>
                <div v-if="p.stack" class="dbx-track stack" style="margin-top: 6px">
                    <i v-for="(part, i) in p.stack" :key="i" :style="{ width: clamp(part.percent) + '%', background: `var(--${part.tone})` }"></i>
                </div>
                <div v-if="p.badge" class="dbx-row"><span class="dbx-badge" :class="'t-' + p.badge.tone">{{ p.badge.text }}</span></div>
            </div>
        </div>

        <!-- Columns of blocks -->
        <div v-else-if="b.type === 'split'" class="dbx-split">
            <div v-for="(column, i) in b.columns" :key="i" class="dbx-split-c">
                <WidgetBlocks :blocks="column" @navigate="emit('navigate', $event)" />
            </div>
        </div>

        <CalendarBlock v-else-if="b.type === 'calendar'" :block="b" @navigate="emit('navigate', $event)" />
    </template>
</template>
