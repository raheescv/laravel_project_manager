<script setup>
import { ref } from 'vue'

import { forgotPassword } from '@/api/parent'
import AppBar from '@/components/AppBar.vue'
import AuthFrame from '@/components/AuthFrame.vue'
import { school } from '@/school'

const login = ref('')
const busy = ref(false)
const sent = ref(false)
const error = ref('')

async function submit() {
  if (!login.value.trim()) {
    error.value = 'Enter the mobile number or email the school has for you.'
    return
  }
  busy.value = true
  error.value = ''
  try {
    await forgotPassword(login.value.trim())
    sent.value = true
  } catch (e) {
    // A school server not yet updated to accept an email here asks for `mobile` only.
    const onlyMobile = e.status === 422 && e.field?.('mobile') && !e.field?.('login')
    error.value = onlyMobile && login.value.includes('@') ? "The school's server doesn't accept an email here yet. Please use your mobile number." : e.message
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <AuthFrame bar>
    <AppBar :back="{ name: 'login' }" back-label="Sign in" />
    <main class="pp-auth pp-auth--inner">
      <form v-if="!sent" novalidate @submit.prevent="submit">
        <div class="pp-lede">
          <div class="pp-hero-icon"><i class="fa fa-key"></i></div>
          <h1>Forgot your password?</h1>
          <p>Enter the mobile number or email you gave the school. We'll send you a link to set a new password.</p>
        </div>
        <div class="pp-group">
          <div class="pp-group__body">
            <div class="pp-field" :class="{ 'is-invalid': error }">
              <label class="pp-field__body">
                <span class="pp-field__label">Mobile number or email</span>
                <input
                  v-model="login"
                  class="pp-input"
                  type="text"
                  inputmode="email"
                  autocomplete="username"
                  autocapitalize="off"
                  spellcheck="false"
                  maxlength="150"
                />
              </label>
            </div>
          </div>
          <p class="pp-group__foot" :class="{ 'is-error': error }" :role="error ? 'alert' : undefined">
            {{ error || 'Use the number or email the school has for you.' }}
          </p>
        </div>
        <div class="pp-auth__actions">
          <button class="pp-btn pp-btn--primary" :class="{ 'is-busy': busy }" type="submit" :disabled="busy">
            <span v-if="busy" class="pp-spinner pp-spinner--sm" aria-hidden="true"></span>{{ busy ? 'Sending…' : 'Send link' }}
          </button>
        </div>
      </form>

      <div v-else>
        <div class="pp-lede">
          <div class="pp-hero-icon pp-hero-icon--pos"><i class="fa fa-paper-plane"></i></div>
          <h1>Check your messages</h1>
          <p>If this mobile number or email is registered with the school, a link to set a new password is on its way by email or WhatsApp.</p>
        </div>
        <div class="pp-group">
          <div class="pp-group__body">
            <div class="pp-row pp-row--icon">
              <span class="pp-row__icon"><i class="fa fa-envelope"></i></span>
              <span class="pp-row__main"><span class="pp-row__title">Email</span><span class="pp-row__sub">Look in your inbox and spam folder</span></span>
            </div>
            <div class="pp-row pp-row--icon">
              <span class="pp-row__icon pp-row__icon--wa"><i class="fa fa-whatsapp"></i></span>
              <span class="pp-row__main"><span class="pp-row__title">WhatsApp</span><span class="pp-row__sub">A message from {{ school.name || 'the school' }}</span></span>
            </div>
          </div>
          <p class="pp-group__foot">It can take a couple of minutes to arrive.</p>
        </div>
        <div class="pp-auth__actions">
          <RouterLink class="pp-btn pp-btn--tinted" :to="{ name: 'login' }">Back to sign in</RouterLink>
          <button class="pp-link" type="button" @click="sent = false">Nothing came? Try again</button>
        </div>
      </div>
    </main>
  </AuthFrame>
</template>
