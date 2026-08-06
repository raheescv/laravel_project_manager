<template>
  <div v-if="field">
    <div class="bcx-drawer__title">Inspector <span>{{ fieldLabel }}</span></div>

    <label class="bcx-field">
      <span>Font size</span>
      <div class="bcx-field__unit">
        <input v-model.number="field.font_size" type="number" min="3" @input="$emit('change')" />
        <em>px</em>
      </div>
    </label>
    <label class="bcx-field">
      <span>Align</span>
      <select v-model="field.align" @change="$emit('change')">
        <option value="left">Left</option>
        <option value="center">Center</option>
        <option value="right">Right</option>
      </select>
    </label>
    <FontSelect
      v-model="field.font_family"
      :fonts="fonts"
      :inherited-key="templateFontKey"
      allow-inherit
      @change="$emit('change')"
    />
    <label class="bcx-field" v-if="'char_limit' in field">
      <span>Char limit</span>
      <input v-model.number="field.char_limit" type="number" min="1" @input="$emit('change')" />
    </label>
    <label class="bcx-field" v-if="'prefix' in field">
      <span>Prefix</span>
      <input v-model="field.prefix" type="text" placeholder="none" @input="$emit('change')" />
    </label>
    <label class="bcx-field">
      <span>Bold</span>
      <label class="bcx-switch" style="margin-inline-start:auto">
        <input type="checkbox" v-model="field.bold" @change="$emit('change')" />
        <span />
      </label>
    </label>

    <template v-if="selected === 'qty'">
      <div class="bcx-drawer__title">Quantity Source</div>
      <label class="bcx-field">
        <span>Source</span>
        <select v-model="field.source" @change="$emit('change')">
          <option v-for="(label, key) in qtySources" :key="key" :value="key">{{ label }}</option>
        </select>
      </label>
      <label class="bcx-field" v-if="field.source === 'custom'">
        <span>Fixed text</span>
        <input v-model="field.custom_text" type="text" @input="$emit('change')" />
      </label>
    </template>
  </div>

  <div v-else class="bcx-note">Pick a text wing field on the left to edit it.</div>
</template>

<script setup>
import { computed } from 'vue'
import FontSelect from '../FontSelect.vue'
import { JEWELLERY_FIELD_LABELS } from '../labels'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  settings: { type: Object, required: true },
  qtySources: { type: Object, default: () => ({}) },
  fonts: { type: Object, default: () => ({}) },
  selected: { type: String, default: '' },
})

defineEmits(['change'])

const field = computed(() => props.settings.fields?.[props.selected] || null)
const fieldLabel = computed(() => JEWELLERY_FIELD_LABELS[props.selected] || props.selected)
const templateFontKey = computed(() => props.settings.font?.family || '')
</script>
