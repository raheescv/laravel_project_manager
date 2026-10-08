<template>
    <article class="tkx-tcard" :class="{ 'is-compact': compact }" :style="{ '--g': color }" :draggable="draggable" :title="compact ? ticket.title : null"
        @dragstart="onDragStart" @dragend="$emit('dragend')" @click="$emit('open', ticket.id)">
        <template v-if="compact">
            <span class="tkx-row-bar" aria-hidden="true"></span>
            <div class="tkx-row-main">
                <div class="tkx-tcard-title">{{ ticket.title }}</div>
                <div class="tkx-row-sub">
                    <span class="tkx-num">#{{ ticket.id }}</span>
                    <span v-if="ticket.comments_count" :title="`${ticket.comments_count} comments`"><i class="fa fa-comments-o"></i>{{ ticket.comments_count }}</span>
                    <span v-if="ticket.attachments_count" :title="`${ticket.attachments_count} attachments`"><i class="fa fa-paperclip"></i>{{ ticket.attachments_count }}</span>
                    <span class="tkx-row-time">{{ relativeTime(ticket.created_at) }}</span>
                </div>
            </div>
            <span class="tkx-av sm" :title="ticket.creator">{{ initials(ticket.creator) }}</span>
        </template>
        <template v-else>
            <span v-if="ticket.group" class="tkx-gtag"><span>{{ ticket.group }}</span></span>
            <div class="tkx-tcard-title">{{ ticket.title }}</div>
            <div v-if="ticket.excerpt" class="tkx-tcard-desc">{{ ticket.excerpt }}</div>
            <img v-if="ticket.cover" :src="ticket.cover" class="tkx-tcard-thumb" alt="" loading="lazy" decoding="async">
            <div class="tkx-tcard-foot">
                <div class="tkx-meta">
                    <span class="tkx-num">#{{ ticket.id }}</span>
                    <span :title="formatDate(ticket.created_at, true)"><i class="fa fa-clock-o"></i>{{ relativeTime(ticket.created_at) }}</span>
                    <span v-if="ticket.comments_count" :title="`${ticket.comments_count} comments`"><i class="fa fa-comments-o"></i>{{ ticket.comments_count }}</span>
                    <span v-if="ticket.attachments_count" :title="`${ticket.attachments_count} attachments`"><i class="fa fa-paperclip"></i>{{ ticket.attachments_count }}</span>
                </div>
                <span class="tkx-av" :title="ticket.creator">{{ initials(ticket.creator) }}</span>
            </div>
        </template>
    </article>
</template>

<script setup>
import { computed } from 'vue'
import { formatDate, groupColor, initials, relativeTime } from './ticketMeta.js'

const props = defineProps({
    ticket: { type: Object, required: true },
    draggable: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
})
const emit = defineEmits(['open', 'dragstart', 'dragend'])

const color = computed(() => groupColor(props.ticket.group))

function onDragStart(event) {
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', String(props.ticket.id))
    emit('dragstart', props.ticket)
}
</script>

<style>
.tkx-tcard { content-visibility: auto; contain-intrinsic-size: auto 110px; background: var(--surf); border: 1px solid var(--line); border-inline-start: 3px solid var(--g); border-radius: 12px; padding: 11px 12px; box-shadow: 0 1px 2px rgba(15, 21, 34, .05); cursor: pointer; transition: transform .15s, box-shadow .15s, border-color .15s; }
.tkx-tcard[draggable="true"] { cursor: grab; }
.tkx-tcard:hover { transform: translateY(-2px); box-shadow: 0 12px 24px -14px color-mix(in srgb, var(--acc) 55%, transparent); border-color: var(--acc-line); border-inline-start-color: var(--g); }
.tkx-num { font-size: 11px; color: var(--mute); font-weight: 500; flex: none; }
.tkx-tcard > .tkx-gtag { margin-bottom: 7px; }
.tkx-tcard-title { font-weight: 600; color: var(--ink); line-height: 1.35; margin: 0 0 3px; overflow-wrap: anywhere; }
.tkx-tcard-desc { color: var(--mute); font-size: 12px; line-height: 1.45; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.tkx-tcard-thumb { display: block; width: 100%; height: 84px; object-fit: cover; border-radius: 8px; margin-top: 9px; background: var(--surf-2); }
.tkx-tcard-foot { display: flex; align-items: center; justify-content: space-between; margin-top: 10px; gap: 8px; }
.tkx-meta { display: flex; align-items: center; gap: 10px; min-width: 0; color: var(--mute); font-size: 11.5px; }
.tkx-meta i { margin-inline-end: 4px; }
/* ---------- title-only rows: the column becomes one sheet of hairline-divided rows ---------- */
.tkx-tcard.is-compact { contain-intrinsic-size: auto 46px; position: relative; display: flex; align-items: center; gap: 10px; padding: 8px 10px 8px 14px; border: 0; border-radius: 9px; background: transparent; }
.tkx-tcard.is-compact + .tkx-tcard.is-compact::before { content: ""; position: absolute; inset: 0 10px auto 14px; height: 1px; background: var(--line); }
.tkx-tcard.is-compact:hover { transform: none; box-shadow: none; background: color-mix(in srgb, var(--g) 9%, transparent); }
.tkx-tcard.is-compact:hover::before, .tkx-tcard.is-compact:hover + .tkx-tcard.is-compact::before { opacity: 0; }
.tkx-row-bar { position: absolute; inset-inline-start: 5px; top: 9px; bottom: 9px; width: 3px; border-radius: 3px; background: var(--g); transition: top .15s, bottom .15s; }
.tkx-tcard.is-compact:hover .tkx-row-bar { top: 5px; bottom: 5px; }
.tkx-row-main { flex: 1; min-width: 0; }
.tkx-tcard.is-compact .tkx-tcard-title { margin: 0; font-weight: 500; font-size: 13px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; transition: color .15s; }
.tkx-tcard.is-compact:hover .tkx-tcard-title { color: var(--acc-ink); }
.tkx-row-sub { display: flex; align-items: center; gap: 9px; margin-top: 2px; font-size: 10.5px; color: var(--mute); white-space: nowrap; }
.tkx-row-sub i { margin-inline-end: 3px; }
.tkx-row-sub .tkx-num { font-size: 10.5px; font-variant-numeric: tabular-nums; }
.tkx-row-time { margin-inline-start: auto; }
.tkx-av.sm { width: 22px; height: 22px; font-size: 9px; background: color-mix(in srgb, var(--g) 22%, var(--surf)); color: color-mix(in srgb, var(--g), var(--ink) 35%); box-shadow: 0 0 0 2px var(--surf); }
</style>
