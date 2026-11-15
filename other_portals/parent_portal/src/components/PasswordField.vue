<script setup>
import { ref } from 'vue'

const model = defineModel({ type: String, default: '' })
defineProps({
  label: { type: String, required: true },
  autocomplete: { type: String, default: 'current-password' },
  invalid: { type: Boolean, default: false },
})

const shown = ref(false)
</script>

<template>
  <div class="pp-field" :class="{ 'is-invalid': invalid }">
    <label class="pp-field__body">
      <span class="pp-field__label">{{ label }}</span>
      <input
        v-model="model"
        class="pp-input"
        :type="shown ? 'text' : 'password'"
        :autocomplete="autocomplete"
        autocapitalize="off"
        autocorrect="off"
        spellcheck="false"
        maxlength="100"
        :aria-invalid="invalid || undefined"
      />
    </label>
    <button class="pp-eye" type="button" :aria-label="shown ? 'Hide password' : 'Show password'" :aria-pressed="shown" @click="shown = !shown">
      <i class="fa" :class="shown ? 'fa-eye-slash' : 'fa-eye'"></i>
    </button>
  </div>
</template>
