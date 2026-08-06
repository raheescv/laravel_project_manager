<template>
  <div v-if="element">
    <div class="bcx-drawer__title">Inspector <span>{{ element.label }}</span></div>

    <label class="bcx-field">
      <span>Top</span>
      <input v-model.number="box.top" type="number" @input="$emit('change')" />
    </label>
    <label class="bcx-field">
      <span>Left</span>
      <input v-model.number="box.left" type="number" @input="$emit('change')" />
    </label>
    <label class="bcx-field">
      <span>Width</span>
      <input v-model.number="box.width" type="number" min="20" @input="$emit('change')" />
    </label>
    <label class="bcx-field">
      <span>Height</span>
      <input v-model.number="box.height" type="number" min="12" @input="$emit('change')" />
    </label>

    <template v-if="element.key !== 'logo'">
      <div class="bcx-drawer__title">Type</div>
      <label class="bcx-field">
        <span>Font size</span>
        <div class="bcx-field__unit">
          <input v-model.number="settings[element.key].font_size" type="number" min="6" @input="$emit('change')" />
          <em>px</em>
        </div>
      </label>
      <label class="bcx-field">
        <span>Align</span>
        <select v-model="settings[element.key].align" @change="$emit('change')">
          <option value="left">Left</option>
          <option value="center">Center</option>
          <option value="right">Right</option>
        </select>
      </label>
      <FontSelect
        v-model="settings[element.key].font_family"
        :fonts="fonts"
        :inherited-key="templateFontKey"
        allow-inherit
        @change="$emit('change')"
      />
      <label class="bcx-field">
        <span>Weight</span>
        <select v-model.number="settings[element.key].font_weight" @change="$emit('change')">
          <option v-for="(weightLabel, weight) in fontWeights" :key="weight" :value="Number(weight)">
            {{ weightLabel }}
          </option>
        </select>
      </label>
    </template>

    <label class="bcx-field" v-if="['product_name', 'product_name_arabic', 'company_name'].includes(element.key)">
      <span>Char limit</span>
      <input v-model.number="settings[element.key].char_limit" type="number" min="5" @input="$emit('change')" />
    </label>

    <template v-if="element.key === 'barcode'">
      <div class="bcx-drawer__title">Barcode</div>
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
        <span>Show number</span>
        <label class="bcx-switch" style="margin-inline-start:auto">
          <input type="checkbox" v-model="settings.barcode.show_value" @change="$emit('change')" />
          <span />
        </label>
      </label>
    </template>
  </div>

  <div v-else class="bcx-note">Pick an element on the left to edit it.</div>
</template>

<script setup>
import { computed } from 'vue'
import FontSelect from '../FontSelect.vue'
import { STANDARD_ELEMENTS } from '../labels'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  settings: { type: Object, required: true },
  barcodeTypes: { type: Object, default: () => ({}) },
  fonts: { type: Object, default: () => ({}) },
  fontWeights: { type: Object, default: () => ({}) },
  selected: { type: String, default: '' },
})

defineEmits(['change'])

const element = computed(() => STANDARD_ELEMENTS.find((item) => item.key === props.selected) || null)

const templateFontKey = computed(() => props.settings.font?.family || '')

const box = computed(() => {
  const key = props.selected
  if (!key) {
    return { top: 0, left: 0, width: 0, height: 0 }
  }

  if (!props.settings.elements) {
    props.settings.elements = {}
  }

  if (!props.settings.elements[key]) {
    props.settings.elements[key] = { top: 0, left: 0, width: 120, height: 32 }
  }

  return props.settings.elements[key]
})
</script>
