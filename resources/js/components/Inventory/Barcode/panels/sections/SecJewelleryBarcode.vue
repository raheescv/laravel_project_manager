<template>
  <div>
    <div class="bcx-drawer__title">Barcode Wing <span>{{ settings.barcode_wing }} · {{ settings.barcode.type }}</span></div>

    <label class="bcx-field">
      <span>Wing side</span>
      <select v-model="settings.barcode_wing" @change="$emit('change')">
        <option value="left">Left</option>
        <option value="right">Right</option>
      </select>
    </label>
    <label class="bcx-field">
      <span>Orientation</span>
      <select v-model="settings.barcode.orientation" @change="$emit('change')">
        <option value="horizontal">Horizontal</option>
        <option value="vertical">Vertical</option>
      </select>
    </label>
    <label class="bcx-field">
      <span>Symbology</span>
      <select v-model="settings.barcode.type" @change="$emit('change')">
        <option v-for="(label, key) in barcodeTypes" :key="key" :value="key">{{ label }}</option>
      </select>
    </label>
    <label class="bcx-field">
      <span>Scale</span>
      <input v-model.number="settings.barcode.scale" type="number" min="1" step="0.1" @input="$emit('change')" />
    </label>
    <label class="bcx-field">
      <span>Bar height</span>
      <div class="bcx-field__unit">
        <input v-model.number="settings.barcode.height" type="number" min="1" step="0.5" @input="$emit('change')" />
        <em>mm</em>
      </div>
    </label>
    <label class="bcx-field">
      <span>Number size</span>
      <div class="bcx-field__unit">
        <input v-model.number="settings.barcode.font_size" type="number" min="3" @input="$emit('change')" />
        <em>px</em>
      </div>
    </label>
    <FontSelect
      v-model="settings.barcode.font_family"
      label="Number font"
      :fonts="fonts"
      :inherited-key="templateFontKey"
      allow-inherit
      @change="$emit('change')"
    />
    <label class="bcx-field">
      <span>Show number</span>
      <label class="bcx-switch" style="margin-inline-start:auto">
        <input type="checkbox" v-model="settings.barcode.show_value" @change="$emit('change')" />
        <span />
      </label>
    </label>
    <label class="bcx-field">
      <span>Rotate text wing</span>
      <label class="bcx-switch" style="margin-inline-start:auto">
        <input type="checkbox" v-model="settings.rotate_text_wing" @change="$emit('change')" />
        <span />
      </label>
    </label>

    <p class="bcx-note">
      Rotating the text wing 180° is only needed if the details face reads upside down on your folded tags.
      Test print one before turning it on.
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import FontSelect from '../FontSelect.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  settings: { type: Object, required: true },
  barcodeTypes: { type: Object, default: () => ({}) },
  fonts: { type: Object, default: () => ({}) },
})

defineEmits(['change'])

const templateFontKey = computed(() => props.settings.font?.family || '')
</script>
