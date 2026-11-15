<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { checkPasswordLink, setPassword } from '@/api/parent'
import AuthFrame from '@/components/AuthFrame.vue'
import LoadError from '@/components/LoadError.vue'
import PasswordField from '@/components/PasswordField.vue'
import { school } from '@/school'
import { session } from '@/session'
import { toast } from '@/toast'

const route = useRoute()
const router = useRouter()
const token = String(route.params.token || '')

const status = ref('loading') // loading · form · expired · error
const link = ref(null)
const loadError = ref('')
const form = reactive({ password: '', confirm: '' })
const busy = ref(false)
const submitted = ref(false)
const error = ref('')

const longEnough = computed(() => form.password.length >= 8)
const matches = computed(() => form.password === form.confirm)
const hint = computed(() => {
  if (submitted.value && !longEnough.value) return { text: 'Use at least 8 characters.', tone: 'is-error', icon: 'fa-exclamation-circle' }
  if (submitted.value && !matches.value) return { text: "The two passwords don't match.", tone: 'is-error', icon: 'fa-exclamation-circle' }
  if (longEnough.value) return { text: 'At least 8 characters', tone: 'is-ok', icon: 'fa-check-circle' }
  return { text: 'At least 8 characters', tone: '', icon: 'fa-circle-o' }
})

async function check() {
  status.value = 'loading'
  try {
    link.value = await checkPasswordLink(token)
    status.value = link.value.valid ? 'form' : 'expired'
  } catch (e) {
    loadError.value = e.message
    status.value = 'error'
  }
}

async function submit() {
  submitted.value = true
  error.value = ''
  if (!longEnough.value || !matches.value) return

  busy.value = true
  try {
    const data = await setPassword({ token, password: form.password, password_confirmation: form.confirm })
    session.start(data, true)
    toast('Your password is set. Welcome!')
    router.replace({ name: 'home' })
  } catch (e) {
    if (e.status === 422 && /expired|already used/i.test(e.message)) status.value = 'expired'
    else error.value = e.message
  } finally {
    busy.value = false
  }
}

onMounted(check)
</script>

<template>
  <AuthFrame>
    <LoadError v-if="status === 'error'" title="We couldn't open this link" :message="loadError" @retry="check" />

    <main v-else class="pp-auth">
      <div v-if="status === 'loading'" class="pp-lede" aria-busy="true">
        <div class="pp-hero-icon"><span class="pp-spinner pp-spinner--sm"></span></div>
        <h1>Checking your link…</h1>
      </div>

      <form v-else-if="status === 'form'" novalidate @submit.prevent="submit">
        <div class="pp-lede">
          <div class="pp-hero-icon"><i class="fa fa-lock"></i></div>
          <h1>Set a new password</h1>
          <p v-if="link?.name">For {{ link.name }}</p>
        </div>
        <div v-if="error" class="pp-alert" role="alert">
          <i class="fa fa-exclamation-circle"></i><span class="pp-alert__main">{{ error }}</span>
        </div>
        <div class="pp-group pp-group--form">
          <div class="pp-group__body">
            <PasswordField v-model="form.password" label="New password" autocomplete="new-password" :invalid="submitted && !longEnough" />
            <PasswordField v-model="form.confirm" label="Confirm new password" autocomplete="new-password" :invalid="submitted && longEnough && !matches" />
          </div>
          <p class="pp-group__foot" :class="hint.tone" aria-live="polite"><i class="fa" :class="hint.icon"></i>{{ hint.text }}</p>
        </div>
        <div class="pp-auth__actions">
          <button class="pp-btn pp-btn--primary" :class="{ 'is-busy': busy }" type="submit" :disabled="busy">
            <span v-if="busy" class="pp-spinner pp-spinner--sm" aria-hidden="true"></span>{{ busy ? 'Saving…' : 'Save and sign in' }}
          </button>
        </div>
      </form>

      <div v-else>
        <div class="pp-lede">
          <div class="pp-hero-icon pp-hero-icon--warn"><i class="fa fa-clock-o"></i></div>
          <h1>This link has expired</h1>
          <p>
            For your safety, a link to set a password works once and for {{ link?.valid_days || 7 }} days. We can send you a new one
            straight away.
          </p>
        </div>
        <div class="pp-auth__actions">
          <RouterLink class="pp-btn pp-btn--primary" :to="{ name: 'forgot' }">Send me a new link</RouterLink>
          <RouterLink class="pp-link" :to="session.signedIn ? { name: 'home' } : { name: 'login' }">
            {{ session.signedIn ? 'Go to my children' : 'Back to sign in' }}
          </RouterLink>
        </div>
      </div>

      <p class="pp-auth__foot">{{ school.name || 'School' }} · Parent Portal</p>
    </main>
  </AuthFrame>
</template>
