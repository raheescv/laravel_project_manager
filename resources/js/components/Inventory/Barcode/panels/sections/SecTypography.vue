<template>
  <div>
    <div class="bcx-drawer__title">Typography <span>{{ fontLabel }} · {{ font.weight }}</span></div>

    <FontSelect v-model="font.family" label="Template font" :fonts="fonts" @change="$emit('change')" />

    <label class="bcx-field">
      <span>Base weight</span>
      <select v-model.number="font.weight" @change="$emit('change')">
        <option v-for="(weightLabel, weight) in fontWeights" :key="weight" :value="Number(weight)">
          {{ weightLabel }}
        </option>
      </select>
    </label>

    <p class="bcx-note">Every element prints in this font unless you give it one of its own.</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import FontSelect from '../FontSelect.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  settings: { type: Object, required: true },
  fonts: { type: Object, default: () => ({}) },
  fontWeights: { type: Object, default: () => ({}) },
})

defineEmits(['change'])

// Templates saved before the font picker existed arrive without the block, and
// the server fills it in again on the next save.
const font = computed(() => {
  if (!props.settings.font) {
    props.settings.font = { family: Object.keys(props.fonts)[0] || '', weight: 400 }
  }

  return props.settings.font
})

const fontLabel = computed(() => props.fonts[font.value.family]?.label || 'default')
</script>
