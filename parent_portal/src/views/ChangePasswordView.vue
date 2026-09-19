<script setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import { changePassword, forgotPassword } from '@/api/parent'
import AppBar from '@/components/AppBar.vue'
import PasswordField from '@/components/PasswordField.vue'
import { session } from '@/session'
import { toast } from '@/toast'
import { goBackOr } from '@/utils/nav'

/**
 * A signed-in parent chooses a new password. The API wants the current one too;
 * this device stays signed in, every other one is signed out.
 */
const router = useRouter()

const form = reactive({ current: '', password: '', confirm: '' })
const submitted = ref(false)
const busy = ref(false)
const error = ref('')
const currentWrong = ref(false)

const reset = ref('idle') // idle · sending · sent
const resetError = ref('')

const longEnough = computed(() => form.password.length >= 8)
const matches = computed(() => form.password === form.confirm)
const changed = computed(() => !form.current || form.password !== form.current)
const hint = computed(() => {
  if (submitted.value && !longEnough.value) return { text: 'Use at least 8 characters.', tone: 'is-error', icon: 'fa-exclamation-circle' }
  if (submitted.value && !matches.value) return { text: "The two new passwords don't match.", tone: 'is-error', icon: 'fa-exclamation-circle' }
  if (submitted.value && !changed.value) return { text: 'Choose a password that is different from the current one.', tone: 'is-error', icon: 'fa-exclamation-circle' }
  if (longEnough.value) return { text: 'At least 8 characters', tone: 'is-ok', icon: 'fa-check-circle' }
  return { text: 'At least 8 characters', tone: '', icon: 'fa-circle-o' }
})

async function submit() {
  submitted.value = true
  error.value = ''
  currentWrong.value = !form.current
  if (!form.current) {
    error.value = 'Enter your current password.'
    return
  }
  if (!longEnough.value || !matches.value || !changed.value) return

  busy.value = true
  try {
    await changePassword({ current_password: form.current, password: form.password, password_confirmation: form.confirm })
    toast('Your password is changed')
    goBackOr(router, { name: 'profile' })
  } catch (e) {
    error.value = e.message
    currentWrong.value = Boolean(e.field?.('current_password'))
    if (currentWrong.value) form.current = ''
  } finally {
    busy.value = false
  }
}

/** Forgot the current one: send the same reset link the sign-in page offers. */
async function sendReset() {
  reset.value = 'sending'
  resetError.value = ''
  try {
    await forgotPassword(session.parent?.mobile || session.parent?.email || '')
    reset.value = 'sent'
  } catch (e) {
    resetError.value = e.message
    reset.value = 'idle'
  }
}
</script>

<template>
  <AppBar :back="{ name: 'profile' }" back-label="Profile" title="Password" />

  <main class="pp-narrow">
    <form novalidate @submit.prevent="submit">
      <div class="pp-lede">
        <div class="pp-hero-icon"><i class="fa fa-lock"></i></div>
        <h1>Change your password</h1>
        <p>You'll stay signed in here. Other phones and computers will be signed out.</p>
      </div>

      <div v-if="error" class="pp-alert" role="alert">
        <i class="fa fa-exclamation-circle"></i><span class="pp-alert__main">{{ error }}</span>
      </div>

      <div class="pp-group">
        <div class="pp-group__body">
          <PasswordField v-model="form.current" label="Current password" autocomplete="current-password" :invalid="currentWrong" />
        </div>
      </div>

      <div class="pp-group">
        <div class="pp-group__body">
          <PasswordField v-model="form.password" label="New password" autocomplete="new-password" :invalid="submitted && (!longEnough || !changed)" />
          <PasswordField v-model="form.confirm" label="Confirm new password" autocomplete="new-password" :invalid="submitted && longEnough && !matches" />
        </div>
        <p class="pp-group__foot" :class="hint.tone" aria-live="polite"><i class="fa" :class="hint.icon"></i>{{ hint.text }}</p>
      </div>

      <div class="pp-auth__actions">
        <button class="pp-btn pp-btn--primary" :class="{ 'is-busy': busy }" type="submit" :disabled="busy">
          <span v-if="busy" class="pp-spinner pp-spinner--sm" aria-hidden="true"></span>{{ busy ? 'Saving…' : 'Save new password' }}
        </button>
        <button v-if="reset !== 'sent'" class="pp-link" type="button" :disabled="reset === 'sending'" @click="sendReset">
          {{ reset === 'sending' ? 'Sending…' : 'Forgot it? Send me a reset link' }}
        </button>
      </div>

      <p v-if="reset === 'sent'" class="pp-hint" role="status">
        <i class="fa fa-paper-plane"></i> A link to set a new password is on its way to your email or WhatsApp. It can take a couple of minutes.
      </p>
      <p v-else-if="resetError" class="pp-hint pp-text-neg" role="alert">{{ resetError }}</p>
    </form>
  </main>
</template>
