<template>
  <div>
    <div class="bcx-drawer__title">Text Wing <span>{{ visibleCount }} of {{ orderedFields.length }}</span></div>

    <div
      v-for="(field, index) in orderedFields"
      :key="field.key"
      class="bcx-row"
      :class="{ 'is-active': selected === field.key, 'is-off': !settings.fields[field.key].visible }"
      @click="$emit('update:selected', field.key)"
    >
      <i class="fa fa-bars" style="opacity:.45"></i>
      <div class="bcx-row__label">{{ field.label }}</div>
      <div class="bcx-row__tools" @click.stop>
        <button type="button" class="bcx-ord" :disabled="index === 0" title="Move up" @click="move(index, -1)">
          <i class="fa fa-angle-up"></i>
        </button>
        <button
          type="button"
          class="bcx-ord"
          :disabled="index === orderedFields.length - 1"
          title="Move down"
          @click="move(index, 1)"
        >
          <i class="fa fa-angle-down"></i>
        </button>
        <label class="bcx-switch">
          <input type="checkbox" v-model="settings.fields[field.key].visible" @change="$emit('change')" />
          <span />
        </label>
      </div>
    </div>

    <p class="bcx-note">
      Visible fields stack top to bottom on the text wing. The barcode wing never carries text.
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { JEWELLERY_FIELD_LABELS } from '../labels'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  settings: { type: Object, required: true },
  selected: { type: String, default: '' },
})

const emit = defineEmits(['change', 'update:selected'])

const orderedFields = computed(() =>
  Object.entries(props.settings.fields || {})
    .map(([key, field]) => ({ key, label: JEWELLERY_FIELD_LABELS[key] || key, order: Number(field.order ?? 99) }))
    .sort((a, b) => a.order - b.order)
)

const visibleCount = computed(() => orderedFields.value.filter((f) => props.settings.fields[f.key]?.visible).length)

// Reorder by rebuilding the list, then renumber so the order stays a clean
// 1..n even after fields have been moved around repeatedly.
function move(index, direction) {
  const target = index + direction
  if (target < 0 || target >= orderedFields.value.length) return

  const reordered = [...orderedFields.value]
  const [moved] = reordered.splice(index, 1)
  reordered.splice(target, 0, moved)
  reordered.forEach((field, position) => {
    props.settings.fields[field.key].order = position + 1
  })

  emit('change')
}
</script>
