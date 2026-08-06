<template>
  <div>
    <div class="bcx-drawer__title">Tag Size <span>{{ totalWidth }} × {{ settings.height }} mm</span></div>

    <label class="bcx-field">
      <span>Wing width</span>
      <div class="bcx-field__unit">
        <input v-model.number="settings.wing_width" type="number" min="5" step="0.5" @input="$emit('change')" />
        <em>mm</em>
      </div>
    </label>
    <label class="bcx-field">
      <span>Neck width</span>
      <div class="bcx-field__unit">
        <input v-model.number="settings.neck_width" type="number" min="0" step="0.5" @input="$emit('change')" />
        <em>mm</em>
      </div>
    </label>
    <label class="bcx-field">
      <span>Tag height</span>
      <div class="bcx-field__unit">
        <input v-model.number="settings.height" type="number" min="3" step="0.5" @input="$emit('change')" />
        <em>mm</em>
      </div>
    </label>
    <label class="bcx-field">
      <span>Neck height</span>
      <div class="bcx-field__unit">
        <input v-model.number="settings.neck_height" type="number" min="1" step="0.5" @input="$emit('change')" />
        <em>mm</em>
      </div>
    </label>
    <label class="bcx-field">
      <span>Inner padding</span>
      <div class="bcx-field__unit">
        <input v-model.number="settings.inner_padding" type="number" min="0" step="0.1" @input="$emit('change')" />
        <em>mm</em>
      </div>
    </label>

    <p class="bcx-note">
      Total strip length is the two wings plus the neck. Measure one label on the roll and match it here.
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  settings: { type: Object, required: true },
})

defineEmits(['change'])

const totalWidth = computed(() => {
  const wing = Number(props.settings.wing_width || 0)
  const neck = Number(props.settings.neck_width || 0)

  return Math.round((wing * 2 + neck) * 100) / 100
})
</script>
