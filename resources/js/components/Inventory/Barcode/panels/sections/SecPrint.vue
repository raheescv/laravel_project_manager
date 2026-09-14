<template>
  <div>
    <div class="bcx-drawer__title">Print Alignment <span>TSC printer</span></div>

    <label class="bcx-field">
      <span>Move right</span>
      <div class="bcx-field__unit">
        <input v-model.number="settings.print.offset_x" type="number" min="-10" max="10" step="0.1" @input="$emit('change')" />
        <em>mm</em>
      </div>
    </label>
    <label class="bcx-field">
      <span>Move down</span>
      <div class="bcx-field__unit">
        <input v-model.number="settings.print.offset_y" type="number" min="-20" max="20" step="0.1" @input="$emit('change')" />
        <em>mm</em>
      </div>
    </label>
    <label class="bcx-field">
      <span>Gap between labels</span>
      <div class="bcx-field__unit">
        <input v-model.number="settings.print.gap" type="number" min="0" max="20" step="0.5" placeholder="Printer" @input="$emit('change')" />
        <em>mm</em>
      </div>
    </label>
    <label class="bcx-field">
      <span>Flip 180°</span>
      <label class="bcx-switch" style="margin-inline-start:auto">
        <input type="checkbox" v-model="settings.print.flip" @change="$emit('change')" />
        <span />
      </label>
    </label>

    <p class="bcx-note">
      Used when labels print straight to a TSC printer. A minus value moves the print left or up. Move down shifts the
      whole print along the roll, so it can go up to 20 mm without cutting anything off: on butterfly tags the printer
      finds the neck, not the wing edge, so the print often needs a few mm. If the first label is right but each next
      one creeps up or down, measure the space between two labels on the roll and enter it as the gap. Then press Print
      to check with a few labels.
    </p>
  </div>
</template>

<script setup>
defineOptions({ inheritAttrs: false })

defineProps({
  settings: { type: Object, required: true },
})

defineEmits(['change'])
</script>
