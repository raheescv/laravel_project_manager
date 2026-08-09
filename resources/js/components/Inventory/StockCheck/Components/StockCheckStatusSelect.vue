<template>
    <div class="sc-status-select" ref="rootEl">
        <button type="button" class="sc-status-trigger" :disabled="disabled" @click.stop="toggle"
            :title="disabled ? statusLabel(modelValue) : 'Change status'">
            <span :class="[statusBadgeClass(modelValue), sizeClass]">
                <i :class="statusIcon(modelValue)" class="me-1"></i>
                {{ statusLabel(modelValue) }}
            </span>
            <i v-if="!disabled" class="fa fa-caret-down ms-1 text-muted"></i>
        </button>

        <Teleport to="body">
            <div v-if="open" class="sc-status-menu shadow-sm" :style="menuStyle" @click.stop>
                <button v-for="option in STOCK_CHECK_STATUSES" :key="option.value" type="button"
                    class="sc-status-option" :class="{ 'is-active': option.value === modelValue }"
                    @click="choose(option.value)">
                    <span :class="option.badge" class="sc-status-dot">
                        <i :class="option.icon"></i>
                    </span>
                    <span class="sc-status-text">{{ option.label }}</span>
                    <i v-if="option.value === modelValue" class="fa fa-check text-success"></i>
                </button>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { STOCK_CHECK_STATUSES, statusBadgeClass, statusIcon, statusLabel } from '../statusOptions.js'

const props = defineProps({
    modelValue: {
        type: String,
        default: 'pending'
    },
    disabled: {
        type: Boolean,
        default: false
    },
    size: {
        type: String,
        default: 'sm' // sm | lg
    }
})

const emit = defineEmits(['select'])

const open = ref(false)
const rootEl = ref(null)
const menuStyle = ref({})

const sizeClass = props.size === 'lg' ? 'px-3 py-2 text-uppercase' : ''

// The list table lives inside `.table-responsive` (overflow: auto), which clips
// an absolutely positioned menu — so the menu is teleported to <body> and
// pinned to the trigger's viewport rect instead.
const position = () => {
    const rect = rootEl.value?.getBoundingClientRect()
    if (!rect) return
    const width = 190
    const left = Math.min(rect.left, window.innerWidth - width - 12)
    const openUpwards = rect.bottom + 150 > window.innerHeight
    menuStyle.value = {
        width: `${width}px`,
        left: `${Math.max(8, left)}px`,
        ...(openUpwards
            ? { bottom: `${window.innerHeight - rect.top + 6}px` }
            : { top: `${rect.bottom + 6}px` })
    }
}

const toggle = () => {
    if (props.disabled) return
    open.value = !open.value
    if (open.value) position()
}

const close = () => {
    open.value = false
}

const choose = (value) => {
    close()
    if (value === props.modelValue) return
    emit('select', value)
}

const onDocumentClick = (event) => {
    if (!open.value) return
    if (rootEl.value?.contains(event.target)) return
    close()
}

const onKeydown = (event) => {
    if (event.key === 'Escape') close()
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick)
    document.addEventListener('keydown', onKeydown)
    window.addEventListener('resize', close)
    window.addEventListener('scroll', close, true)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick)
    document.removeEventListener('keydown', onKeydown)
    window.removeEventListener('resize', close)
    window.removeEventListener('scroll', close, true)
})
</script>

<style scoped>
.sc-status-select {
    display: inline-flex;
    position: relative;
}

.sc-status-trigger {
    display: inline-flex;
    align-items: center;
    gap: 0.15rem;
    padding: 0;
    border: 0;
    background: transparent;
    line-height: 1;
}

.sc-status-trigger:not(:disabled) {
    cursor: pointer;
}

.sc-status-trigger:not(:disabled):hover .badge {
    filter: brightness(0.94);
}
</style>

<style>
/* Not scoped — the menu is teleported to <body>. */
.sc-status-menu {
    position: fixed;
    z-index: 1060;
    background: #fff;
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 10px;
    padding: 0.25rem;
}

.sc-status-menu .sc-status-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.4rem 0.5rem;
    border: 0;
    border-radius: 7px;
    background: transparent;
    cursor: pointer;
}

.sc-status-menu .sc-status-option:hover {
    background: rgba(13, 110, 253, 0.08);
}

.sc-status-menu .sc-status-option.is-active {
    background: rgba(13, 110, 253, 0.06);
}

.sc-status-menu .sc-status-dot {
    width: 22px;
    height: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
}

.sc-status-menu .sc-status-text {
    flex: 1 1 auto;
    text-align: left;
    font-size: 0.85rem;
    font-weight: 500;
    color: #1e293b;
}
</style>
