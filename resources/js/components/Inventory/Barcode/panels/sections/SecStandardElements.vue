<template>
  <div>
    <div class="bcx-drawer__title">Elements <span>{{ visibleCount }} of {{ elementItems.length }}</span></div>

    <button
      v-for="item in elementItems"
      :key="item.key"
      type="button"
      class="bcx-row"
      :class="{ 'is-active': selected === item.key, 'is-off': !settings[item.key]?.visible }"
      @click="$emit('update:selected', item.key)"
    >
      <div class="bcx-row__label">{{ item.label }}</div>
      <div class="bcx-row__tools" @click.stop>
        <label class="bcx-switch">
          <input type="checkbox" v-model="settings[item.key].visible" @change="$emit('change')" />
          <span />
        </label>
      </div>
    </button>

    <p class="bcx-note">Pick an element to position it on the label with the inspector.</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { STANDARD_ELEMENTS } from '../labels'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  settings: { type: Object, required: true },
  selected: { type: String, default: '' },
})

defineEmits(['change', 'update:selected'])

const elementItems = STANDARD_ELEMENTS

const visibleCount = computed(() => elementItems.filter((i) => props.settings[i.key]?.visible).length)
</script>
