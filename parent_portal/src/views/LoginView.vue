<script setup>
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { login } from '@/api/parent'
import AuthFrame from '@/components/AuthFrame.vue'
import PasswordField from '@/components/PasswordField.vue'
import { safeRedirect } from '@/router'
import { school } from '@/school'
import { session } from '@/session'

const route = useRoute()
const router = useRouter()

const form = reactive({ login: '', password: '', remember: true })
const busy = ref(false)
const error = ref(session.expired ? 'You were signed out. Please sign in again.' : '')
const errors = ref({})

const schoolError = computed(() => (!school.loaded && school.error ? school.error : ''))

async function submit() {
  errors.value = {}
  if (!form.login.trim() || !form.password) {
    error.value = 'Enter your mobile number (or email) and password.'
    errors.value = { login: !form.login.trim(), password: !form.password }
    return
  }

  busy.value = true
  error.value = ''
  try {
    const data = await login({ login: form.login.trim(), password: form.password, remember: form.remember })
    session.start(data, form.remember)
    router.replace(safeRedirect(route.query.redirect) || { name: 'home' })
  } catch (e) {
    error.value = e.message
    errors.value = { login: Boolean(e.field?.('login')), password: Boolean(e.field?.('password')) }
    form.password = ''
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <AuthFrame>
    <main class="pp-auth">
      <div class="pp-auth__brand">
        <div v-if="school.logo" class="pp-logo pp-logo--image"><img :src="school.logo" :alt="`${school.name} logo`" /></div>
        <div v-else class="pp-logo" role="img" aria-hidden="true"><i class="fa fa-graduation-cap"></i></div>
        <h1 class="pp-auth__school">{{ school.name || 'Parent Portal' }}</h1>
        <span class="pp-auth__tag"><i class="fa fa-lock"></i>Parent Portal</span>
      </div>

      <div v-if="error || schoolError" class="pp-alert" role="alert">
        <i class="fa fa-exclamation-circle"></i>
        <span class="pp-alert__main">{{ error || schoolError }}</span>
      </div>

      <form novalidate @submit.prevent="submit">
        <div class="pp-group">
          <div class="pp-group__body">
            <div class="pp-field" :class="{ 'is-invalid': errors.login }">
              <label class="pp-field__body">
                <span class="pp-field__label">Mobile number or email</span>
                <input
                  v-model="form.login"
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
            <PasswordField v-model="form.password" label="Password" autocomplete="current-password" :invalid="Boolean(errors.password)" />
            <label class="pp-row">
              <span class="pp-row__main"><span class="pp-row__title">Keep me signed in</span></span>
              <span class="pp-switch"><input v-model="form.remember" type="checkbox" /><span class="pp-switch__track"></span></span>
            </label>
          </div>
          <p v-if="!form.remember" class="pp-group__foot">You'll be signed out when you close this page.</p>
        </div>

        <div class="pp-auth__actions">
          <button class="pp-btn pp-btn--primary" :class="{ 'is-busy': busy }" type="submit" :disabled="busy">
            <span v-if="busy" class="pp-spinner pp-spinner--sm" aria-hidden="true"></span>{{ busy ? 'Signing in…' : 'Sign in' }}
          </button>
          <RouterLink class="pp-link" :to="{ name: 'forgot' }">Forgot password?</RouterLink>
        </div>
      </form>

      <p class="pp-auth__foot">
        Trouble signing in? Please contact the school office<template v-if="school.contact.mobile">
          on <a class="pp-num" :href="`tel:${school.contact.mobile}`">{{ school.contact.mobile }}</a></template
        >.
      </p>
    </main>
  </AuthFrame>
</template>
