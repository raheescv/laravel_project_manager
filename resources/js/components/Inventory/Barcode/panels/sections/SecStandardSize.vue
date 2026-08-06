<template>
  <div>
    <div class="bcx-drawer__title">Label Size <span>{{ settings.width }} × {{ settings.height }} mm</span></div>

    <label class="bcx-field">
      <span>Width</span>
      <div class="bcx-field__unit">
        <input v-model.number="settings.width" type="number" min="10" step="0.5" @input="$emit('change')" />
        <em>mm</em>
      </div>
    </label>
    <label class="bcx-field">
      <span>Height</span>
      <div class="bcx-field__unit">
        <input v-model.number="settings.height" type="number" min="10" step="0.5" @input="$emit('change')" />
        <em>mm</em>
      </div>
    </label>

    <p class="bcx-note">This is the physical sticker size sent to the printer.</p>
  </div>
</template>

<script setup>
defineOptions({ inheritAttrs: false })

defineProps({
  settings: { type: Object, required: true },
})

defineEmits(['change'])
</script>
