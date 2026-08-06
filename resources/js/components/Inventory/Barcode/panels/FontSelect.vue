<template>
  <label class="bcx-field">
    <span>{{ label }}</span>
    <select
      :value="modelValue || ''"
      :style="{ fontFamily: activeStack }"
      :title="description"
      @change="onChange"
    >
      <option v-if="allowInherit" value="">Inherit ({{ inheritedLabel }})</option>
      <option v-for="font in fontList" :key="font.key" :value="font.key" :style="{ fontFamily: font.stack }">
        {{ font.label }}
      </option>
    </select>
  </label>
</template>

<script setup>
import { computed } from 'vue'

// Font picker shared by every panel: the same list, the same "inherit the
// template font" option, and each option drawn in the face it selects.
const props = defineProps({
  modelValue: { type: String, default: '' },
  fonts: { type: Object, default: () => ({}) },
  label: { type: String, default: 'Font' },
  allowInherit: { type: Boolean, default: false },
  inheritedKey: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue', 'change'])

const fontList = computed(() => Object.values(props.fonts))

const effectiveKey = computed(() => props.modelValue || props.inheritedKey)

const activeStack = computed(() => props.fonts[effectiveKey.value]?.stack || 'inherit')

// Inspector selects are narrow, so drop any "(recommended)" style aside from
// the inherited name rather than letting the option text get cut mid word.
const inheritedLabel = computed(() =>
  (props.fonts[props.inheritedKey]?.label || 'default').replace(/\s*\([^)]*\)\s*$/, '')
)

const description = computed(() => (props.modelValue ? props.fonts[props.modelValue]?.description : '') || '')

function onChange(event) {
  emit('update:modelValue', event.target.value)
  emit('change')
}
</script>
