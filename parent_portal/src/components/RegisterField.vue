<script setup>
/**
 * One boxed text field on the registration form. Anything else passed to it
 * (inputmode, maxlength, placeholder…) goes on the input.
 */
defineOptions({ inheritAttrs: false })

defineProps({
  id: { type: String, required: true },
  path: { type: String, required: true },
  label: { type: String, required: true },
  value: { type: String, default: '' },
  error: { type: String, default: '' },
  ok: { type: Boolean, default: false },
  prefix: { type: String, default: '' },
  count: { type: String, default: '' },
  help: { type: String, default: '' },
  wide: { type: Boolean, default: false },
})

defineEmits(['edit', 'leave'])
</script>

<template>
  <div class="srx-f" :class="{ 'srx-span': wide, 'is-bad': error, 'is-ok': ok }" :data-field="path">
    <label :for="id">{{ label }}</label>
    <div class="srx-ctl" :dir="prefix ? 'ltr' : undefined">
      <span v-if="prefix" class="srx-pre">{{ prefix }}</span>
      <input
        v-bind="$attrs"
        :id="id"
        :value="value"
        :data-path="path"
        :aria-invalid="error ? 'true' : 'false'"
        @input="$emit('edit', $event)"
        @blur="$emit('leave')"
      />
      <span class="srx-end">
        <span v-if="count" class="srx-count">{{ count }}</span>
        <i class="fa fa-check"></i>
      </span>
    </div>
    <p v-if="help" class="srx-help">{{ help }}</p>
    <p class="srx-err" role="alert">
      <template v-if="error"><i class="fa fa-exclamation-circle"></i><span>{{ error }}</span></template>
    </p>
  </div>
</template>
