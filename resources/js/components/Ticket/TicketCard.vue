<template>
    <article class="tkx-tcard" :style="{ '--g': color }" :draggable="draggable" @dragstart="onDragStart" @dragend="$emit('dragend')" @click="$emit('open', ticket.id)">
        <div class="tkx-tcard-top">
            <span v-if="ticket.group" class="tkx-gtag"><span>{{ ticket.group }}</span></span>
            <span v-else class="tkx-nogroup">No group</span>
            <span class="tkx-num">#{{ ticket.id }}</span>
        </div>
        <div class="tkx-tcard-title">{{ ticket.title }}</div>
        <div v-if="ticket.excerpt" class="tkx-tcard-desc">{{ ticket.excerpt }}</div>
        <img v-if="ticket.cover" :src="ticket.cover" class="tkx-tcard-thumb" alt="" loading="lazy">
        <div class="tkx-tcard-foot">
            <div class="tkx-meta">
                <span :title="`${ticket.comments_count} comments`"><i class="fa fa-comments-o"></i>{{ ticket.comments_count }}</span>
                <span :title="`${ticket.attachments_count} attachments`"><i class="fa fa-paperclip"></i>{{ ticket.attachments_count }}</span>
                <span :title="formatDate(ticket.created_at, true)"><i class="fa fa-clock-o"></i>{{ relativeTime(ticket.created_at) }}</span>
            </div>
            <span class="tkx-av" :title="ticket.creator">{{ initials(ticket.creator) }}</span>
        </div>
    </article>
</template>

<script setup>
import { computed } from 'vue'
import { formatDate, groupColor, initials, relativeTime } from './ticketMeta.js'

const props = defineProps({
    ticket: { type: Object, required: true },
    draggable: { type: Boolean, default: false },
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
.tkx-tcard { background: var(--surf); border: 1px solid var(--line); border-inline-start: 3px solid var(--g); border-radius: 12px; padding: 11px 12px; cursor: pointer; transition: transform .15s, box-shadow .15s, border-color .15s; }
.tkx-tcard[draggable="true"] { cursor: grab; }
.tkx-tcard:hover { transform: translateY(-2px); box-shadow: 0 12px 24px -14px color-mix(in srgb, var(--acc) 55%, transparent); border-color: var(--acc-line); border-inline-start-color: var(--g); }
.tkx-tcard-top { display: flex; justify-content: space-between; align-items: center; gap: 8px; min-width: 0; }
.tkx-nogroup { font-size: 11px; color: var(--mute); font-style: italic; }
.tkx-num { font-size: 11px; color: var(--mute); font-weight: 500; flex: none; }
.tkx-tcard-title { font-weight: 600; color: var(--ink); line-height: 1.35; margin: 7px 0 3px; overflow-wrap: anywhere; }
.tkx-tcard-desc { color: var(--mute); font-size: 12px; line-height: 1.45; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.tkx-tcard-thumb { display: block; width: 100%; height: 92px; object-fit: cover; border-radius: 8px; margin-top: 9px; background: var(--surf-2); }
.tkx-tcard-foot { display: flex; align-items: center; justify-content: space-between; margin-top: 10px; gap: 8px; }
.tkx-meta { display: flex; align-items: center; gap: 11px; color: var(--mute); font-size: 11.5px; }
.tkx-meta i { margin-inline-end: 4px; }
</style>
