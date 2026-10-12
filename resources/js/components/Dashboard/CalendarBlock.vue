<script setup>
/**
 * Package visit calendar (day / week / month / year). Navigation asks the
 * widget to reload for another date; the view switch lives in the widget
 * header like every other widget option.
 */
const props = defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['navigate'])

const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
const TONES = { visited: 'ok', rescheduled: 'warn', pending: 'info' }
const LABELS = { visited: 'Visited', rescheduled: 'Rescheduled', pending: 'Pending' }

const today = () => {
    const now = new Date()
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
}

const dayTip = (day) => (day.count ? `${day.count} visit${day.count > 1 ? 's' : ''} · ${LABELS[day.status] ?? ''}${day.rescheduled ? ' · rescheduled' : ''}` : null)
const tone = (status) => TONES[status] ?? 'slate'
</script>

<template>
    <div class="dbx-calw">
        <div class="dbx-cal-top">
            <b>{{ props.block.title }}</b>
            <div class="dbx-cal-nav">
                <button class="dbx-ib" type="button" aria-label="Previous" @click="emit('navigate', props.block.prev)"><i class="fa fa-angle-left"></i></button>
                <button class="dbx-btn sm" type="button" @click="emit('navigate', today())">Today</button>
                <button class="dbx-ib" type="button" aria-label="Next" @click="emit('navigate', props.block.next)"><i class="fa fa-angle-right"></i></button>
            </div>
        </div>

        <div v-if="block.view === 'month'" class="dbx-cal">
            <div v-for="w in WEEKDAYS" :key="w" class="wd">{{ w }}</div>
            <div v-for="n in block.blanks" :key="'b' + n" class="c e"></div>
            <div v-for="d in block.days" :key="d.date" class="c" :class="[{ has: d.count, today: d.today }, d.count ? 't-' + tone(d.status) : '']" :data-tip="dayTip(d)">
                <span class="n">{{ d.day }}</span>
                <span v-if="d.count > 1" class="k">×{{ d.count }}</span>
                <span v-if="d.rescheduled" class="k rs"><i class="fa fa-repeat"></i></span>
            </div>
        </div>

        <div v-else-if="block.view === 'year'" class="dbx-years">
            <div v-for="m in block.months" :key="m.name" class="m">
                <b>{{ m.name }}</b>
                <div class="dbx-cal">
                    <div v-for="n in m.blanks" :key="'b' + n" class="c e"></div>
                    <div v-for="d in m.days" :key="d.date" class="c" :class="[{ has: d.count, today: d.today }, d.count ? 't-' + tone(d.status) : '']" :data-tip="dayTip(d) ? `${d.date} · ${dayTip(d)}` : null">
                        <span class="n">{{ d.day }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="block.view === 'week'" class="dbx-week">
            <div v-for="d in block.days" :key="d.date" class="d" :class="{ today: d.today }">
                <b>{{ d.label }}</b>
                <span v-for="item in d.items" :key="item.id" class="dbx-pk" :class="'t-' + tone(item.status)" :data-tip="`${item.account} · ${LABELS[item.status] ?? item.status}`">
                    {{ item.name }}<i v-if="item.rescheduled" class="fa fa-repeat"></i>
                </span>
                <span v-if="!d.items.length" class="dbx-none sm">—</span>
            </div>
        </div>

        <div v-else class="dbx-list">
            <div v-if="!block.items.length" class="dbx-none">No package visits on this day.</div>
            <div v-for="item in block.items" :key="item.id" class="dbx-li">
                <span class="dbx-li-ic" :class="'t-' + tone(item.status)"><i class="fa fa-calendar"></i></span>
                <div class="dbx-li-b"><b>{{ item.name }}</b><span>{{ item.account }}{{ item.notes ? ' · ' + item.notes : '' }}</span></div>
                <span class="dbx-badge" :class="'t-' + tone(item.status)">{{ LABELS[item.status] ?? item.status }}<template v-if="item.rescheduled"> · moved</template></span>
            </div>
        </div>

        <div class="dbx-cal-leg">
            <span class="t-ok"><i></i>Visited</span><span class="t-warn"><i></i>Rescheduled</span><span class="t-info"><i></i>Pending</span><span class="t-acc"><i class="ring"></i>Today</span>
        </div>
    </div>
</template>
