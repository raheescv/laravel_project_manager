<template>
    <Teleport to="body">
        <div v-if="open" class="pax pcm-layer" @keydown.esc="close">
            <div class="pcm-backdrop" @click="close"></div>
            <div class="pcm-dialog" :class="`pcm-${size}`" role="dialog" aria-modal="true" :aria-labelledby="titleId">
                <div class="pcm-content">
                    <div class="pcm-header">
                        <div v-if="icon" class="pa-modal-icon" :class="tone"><i class="fa" :class="icon"></i></div>
                        <div class="me-auto" style="min-width: 0">
                            <h5 :id="titleId" class="pcm-title">{{ title }}</h5>
                            <div v-if="subtitle" class="pa-sub">{{ subtitle }}</div>
                        </div>
                        <button type="button" class="btn-close" aria-label="Close" @click="close"></button>
                    </div>
                    <div class="pcm-body">
                        <slot />
                    </div>
                    <div v-if="$slots.footer" class="pcm-footer">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { onBeforeUnmount, watch } from 'vue'

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    icon: { type: String, default: '' },
    tone: { type: String, default: '' },
    size: { type: String, default: 'md' },
})

const emit = defineEmits(['close'])
const titleId = `pcm-${Math.random().toString(36).slice(2, 9)}`

function close() {
    emit('close')
}

function onKey(event) {
    if (event.key === 'Escape' && props.open) close()
}

watch(
    () => props.open,
    (isOpen) => {
        document.body.classList.toggle('overflow-hidden', isOpen)
        if (isOpen) document.addEventListener('keydown', onKey)
        else document.removeEventListener('keydown', onKey)
    },
)

onBeforeUnmount(() => {
    document.body.classList.remove('overflow-hidden')
    document.removeEventListener('keydown', onKey)
})
</script>
