<template>
    <form class="lgx-form" :class="{ 'lgx-shake': shaking }" novalidate @submit.prevent="submit" @animationend="shaking = false">
        <transition name="lgx-fade">
            <div v-if="alert" class="lgx-alert" role="alert">
                <i class="fa" :class="lockedFor ? 'fa-clock-o' : 'fa-exclamation-circle'"></i>
                <span v-if="lockedFor">Too many attempts. Try again in <b>{{ lockedFor }}s</b>.</span>
                <span v-else>{{ alert }}</span>
            </div>
        </transition>

        <div class="lgx-field">
            <label class="lgx-label" for="login">Email or username</label>
            <div class="lgx-wrap">
                <i class="fa lgx-icon" :class="login.includes('@') ? 'fa-envelope-o' : 'fa-user'"></i>
                <input
                    id="login"
                    v-model.trim="login"
                    type="text"
                    class="lgx-input"
                    :class="{ 'lgx-bad': errors.login }"
                    placeholder="name@company.com or username"
                    autocomplete="username"
                    autocapitalize="none"
                    spellcheck="false"
                    autofocus
                    :disabled="busy"
                    @input="errors.login = null"
                />
            </div>
            <div v-if="errors.login" class="lgx-err"><i class="fa fa-info-circle"></i>{{ errors.login }}</div>
        </div>

        <div class="lgx-field">
            <label class="lgx-label" for="password">Password</label>
            <div class="lgx-wrap">
                <i class="fa fa-lock lgx-icon"></i>
                <input
                    id="password"
                    v-model="password"
                    :type="reveal ? 'text' : 'password'"
                    class="lgx-input lgx-input--toggle"
                    :class="{ 'lgx-bad': errors.password }"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    :disabled="busy"
                    @input="errors.password = null"
                    @keyup="capsLock = $event.getModifierState?.('CapsLock') ?? false"
                />
                <button type="button" class="lgx-eye" :aria-label="reveal ? 'Hide password' : 'Show password'" @click="reveal = !reveal">
                    <i class="fa" :class="reveal ? 'fa-eye-slash' : 'fa-eye'"></i>
                </button>
            </div>
            <div v-if="errors.password" class="lgx-err"><i class="fa fa-info-circle"></i>{{ errors.password }}</div>
            <div v-else-if="capsLock" class="lgx-caps"><i class="fa fa-warning"></i>Caps Lock is on</div>
        </div>

        <label class="lgx-check">
            <input v-model="remember" type="checkbox" />
            <span class="lgx-box"><i class="fa fa-check"></i></span>
            Keep me signed in
        </label>

        <button type="submit" class="lgx-submit" :class="{ 'lgx-ok': status === 'success' }" :disabled="busy || lockedFor > 0 || preview">
            <template v-if="status === 'loading'"><span class="lgx-spin"></span>Signing in…</template>
            <template v-else-if="status === 'success'"><i class="fa fa-check"></i>Welcome back — opening your workspace</template>
            <template v-else-if="preview">Preview — sign-in disabled</template>
            <template v-else>Sign in <i class="fa fa-arrow-right lgx-arrow"></i></template>
        </button>

        <div class="lgx-secure"><i class="fa fa-lock"></i> Secured connection</div>
    </form>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'

const props = defineProps({
    loginUrl: { type: String, required: true },
    prefill: { type: Object, default: () => ({}) },
    preview: { type: Boolean, default: false },
})

const login = ref(props.prefill.login || '')
const password = ref(props.prefill.password || '')
const remember = ref(false)
const reveal = ref(false)
const capsLock = ref(false)
const shaking = ref(false)
const status = ref('idle')
const alert = ref(null)
const lockedFor = ref(0)
const errors = reactive({ login: null, password: null })
const busy = computed(() => ['loading', 'success'].includes(status.value))

let lockTimer = null
let keepAlive = null

function validate() {
    errors.login = login.value ? null : 'Enter your email or username.'
    errors.password = password.value ? null : 'Enter your password.'
    return !errors.login && !errors.password
}

function fail(message) {
    status.value = 'idle'
    alert.value = message
    shaking.value = true
}

function startLockout(seconds) {
    clearInterval(lockTimer)
    lockedFor.value = seconds
    lockTimer = setInterval(() => {
        lockedFor.value -= 1
        if (lockedFor.value <= 0) {
            clearInterval(lockTimer)
            alert.value = null
        }
    }, 1000)
}

async function submit() {
    if (props.preview || busy.value) return
    if (!validate()) {
        shaking.value = true
        return
    }

    status.value = 'loading'
    alert.value = null

    let response
    try {
        response = await fetch(props.loginUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ login: login.value, password: password.value, remember: remember.value }),
        })
    } catch {
        return fail("Can't reach the server. Check your connection and try again.")
    }

    const body = await response.json().catch(() => ({}))

    if (response.ok) {
        status.value = 'success'
        setTimeout(() => window.location.assign(body.redirect || '/'), 450)
        return
    }

    if (response.status === 419) {
        fail('Your session expired. Refreshing the page…')
        setTimeout(() => window.location.reload(), 1200)
        return
    }

    if (response.status === 422) {
        const message = body.errors?.login?.[0] ?? body.errors?.password?.[0] ?? body.message
        const throttled = /(\d+)\s*seconds?/i.exec(message ?? '')
        if (throttled && /too many/i.test(message)) {
            startLockout(Number(throttled[1]))
        }
        return fail(message || 'These credentials do not match our records.')
    }

    fail('Something went wrong. Please try again.')
}

onMounted(() => {
    // Touching the session keeps this page's CSRF token valid while it sits open.
    keepAlive = setInterval(() => {
        fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' }).catch(() => {})
    }, 15 * 60 * 1000)
})

onBeforeUnmount(() => {
    clearInterval(lockTimer)
    clearInterval(keepAlive)
})
</script>
