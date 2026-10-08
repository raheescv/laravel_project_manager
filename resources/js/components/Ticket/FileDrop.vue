<template>
    <div>
        <label class="tkx-drop" :class="{ over, compact }" @dragover.prevent="over = true" @dragleave.prevent="over = false" @drop.prevent="onDrop">
            <input type="file" multiple :accept="accept" hidden @change="onPick">
            <i class="fa fa-cloud-upload"></i>
            <span>Drop files or <b>browse</b></span>
            <small v-if="!compact">Images, video, PDF, Word, Excel · up to 50 MB each · 10 at a time</small>
        </label>
        <ul v-if="!compact && modelValue.length" class="tkx-pending">
            <li v-for="(file, i) in modelValue" :key="`${file.name}-${i}`">
                <i class="fa fa-file-o"></i>
                <span class="n">{{ file.name }}</span>
                <span class="s">{{ formatSize(file.size) }}</span>
                <button type="button" title="Remove" @click="remove(i)"><i class="fa fa-times"></i></button>
            </li>
        </ul>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { formatSize } from './ticketMeta.js'

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    compact: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])

const accept = '.jpg,.jpeg,.png,.gif,.webp,.mp4,.mov,.avi,.wmv,.mkv,.pdf,.doc,.docx,.xls,.xlsx'
const over = ref(false)

function add(list) {
    over.value = false
    const files = Array.from(list || [])
    if (files.length) emit('update:modelValue', [...props.modelValue, ...files].slice(0, 10))
}

function onDrop(event) {
    add(event.dataTransfer.files)
}

function onPick(event) {
    add(event.target.files)
    event.target.value = ''
}

function remove(index) {
    emit('update:modelValue', props.modelValue.filter((_, i) => i !== index))
}
</script>

<style>
.tkx-drop { display: flex; flex-direction: column; align-items: center; gap: 3px; border: 1.5px dashed var(--acc-line); border-radius: 11px; padding: 16px; text-align: center; color: var(--mute); background: var(--acc-soft); cursor: pointer; margin: 0; transition: background .15s, border-color .15s; }
.tkx-drop i { font-size: 20px; color: var(--acc-ink); }
.tkx-drop b { color: var(--acc-ink); }
.tkx-drop small { font-size: 11px; }
.tkx-drop.over { border-color: var(--acc); background: color-mix(in srgb, var(--acc) 16%, transparent); }
.tkx-drop.compact { flex-direction: row; justify-content: center; gap: 8px; padding: 10px; }
.tkx-drop.compact i { font-size: 15px; }
.tkx-pending { list-style: none; margin: 8px 0 0; padding: 0; display: grid; gap: 6px; }
.tkx-pending li { display: flex; align-items: center; gap: 9px; border: 1px solid var(--line); border-radius: 9px; padding: 6px 10px; }
.tkx-pending .n { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--ink); }
.tkx-pending .s { color: var(--mute); font-size: 11.5px; }
.tkx-pending button { border: 0; background: transparent; color: var(--mute); cursor: pointer; }
.tkx-pending button:hover { color: var(--bs-danger); }
</style>
