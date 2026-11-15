<script setup>
import { computed, ref, watch } from 'vue'

import { t } from '@/i18n'

/**
 * A text field with a searchable suggestion list (ARIA combobox). Typing stays
 * free — the list only helps — so it still works when there is nothing to suggest.
 * options: [{ value, primary, secondary }]
 */
const props = defineProps({
  id: { type: String, required: true },
  label: { type: String, required: true },
  modelValue: { type: String, default: '' },
  options: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  placeholder: { type: String, default: '' },
  inputmode: { type: String, default: 'text' },
  maxlength: { type: Number, default: 10 },
  numeric: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'pick'])

const MAX_SHOWN = 80

const input = ref(null)
const open = ref(false)
const active = ref(-1)

const matches = computed(() => {
  const q = String(props.modelValue || '').trim().toLowerCase()
  if (!q) return props.options.slice(0, MAX_SHOWN)
  const byNumber = /^\d+$/.test(q)
  return props.options
    .filter((o) =>
      byNumber
        ? String(o.value).toLowerCase().startsWith(q)
        : String(o.value).toLowerCase().startsWith(q) || `${o.primary} ${o.secondary || ''}`.toLowerCase().includes(q),
    )
    .slice(0, MAX_SHOWN)
})

const showList = computed(() => open.value && !props.disabled && (props.loading || props.options.length > 0))

watch(matches, () => {
  active.value = -1
})

function onInput(e) {
  emit('update:modelValue', e.target.value)
  open.value = true
}

function pick(option) {
  emit('update:modelValue', String(option.value))
  emit('pick', option)
  open.value = false
}

function onKey(e) {
  if (e.key === 'Escape') {
    if (open.value) e.stopPropagation()
    open.value = false
    return
  }
  if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
    e.preventDefault()
    open.value = true
    const n = matches.value.length
    if (!n) return
    active.value = e.key === 'ArrowDown' ? (active.value + 1) % n : (active.value - 1 + n) % n
    document.getElementById(`${props.id}-opt-${active.value}`)?.scrollIntoView({ block: 'nearest' })
    return
  }
  if (e.key === 'Enter' && showList.value && active.value >= 0) {
    e.preventDefault()
    pick(matches.value[active.value])
  }
}

defineExpose({ focus: () => input.value?.focus() })
</script>

<template>
  <div class="combo" :class="{ 'is-disabled': disabled, 'combo--num': numeric }">
    <label class="fld__label" :for="id">{{ label }}</label>
    <div class="combo__box">
      <input
        :id="id"
        ref="input"
        :value="modelValue"
        type="text"
        role="combobox"
        autocomplete="off"
        :aria-expanded="showList"
        :aria-controls="`${id}-list`"
        :aria-activedescendant="active >= 0 ? `${id}-opt-${active}` : undefined"
        :inputmode="inputmode"
        :maxlength="maxlength"
        :placeholder="placeholder"
        :disabled="disabled"
        :class="{ 'is-num': numeric }"
        :dir="numeric ? 'ltr' : undefined"
        @input="onInput"
        @focus="open = true"
        @blur="open = false"
        @keydown="onKey"
      />
      <span v-if="loading" class="combo__spin" aria-hidden="true"></span>
      <svg v-else-if="options.length" class="combo__chev" viewBox="0 0 12 12" aria-hidden="true">
        <path d="M3 4.5 6 7.5 9 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
      </svg>
    </div>
    <ul v-show="showList" :id="`${id}-list`" class="combo__list" role="listbox" :aria-label="label">
      <li v-if="loading" class="combo__note">{{ t('addrLoading') }}</li>
      <li v-else-if="!matches.length" class="combo__note">{{ t('addrNoMatch') }}</li>
      <li
        v-for="(o, i) in matches"
        v-else
        :id="`${id}-opt-${i}`"
        :key="o.value"
        role="option"
        class="combo__opt"
        :class="{ 'is-active': i === active, 'is-on': String(o.value) === String(modelValue) }"
        :aria-selected="String(o.value) === String(modelValue)"
        @mousedown.prevent="pick(o)"
      >
        <span class="combo__val">{{ o.value }}</span>
        <span class="combo__text">
          <span class="combo__primary">{{ o.primary }}</span>
          <span v-if="o.secondary" class="combo__secondary">{{ o.secondary }}</span>
        </span>
      </li>
    </ul>
  </div>
</template>
