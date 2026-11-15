<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { session } from '@/session'
import { signOut } from '@/utils/account'
import { initials } from '@/utils/format'

/** The desktop account button: who is signed in, their profile and password, sign out. Phones use the action sheet on the home page. */
const route = useRoute()
const router = useRouter()
const root = ref(null)
const open = ref(false)
const busy = ref(false)

const parent = computed(() => session.parent || {})

function onPointer(event) {
  if (!root.value?.contains(event.target)) open.value = false
}
function onKey(event) {
  if (event.key === 'Escape') open.value = false
}
function listen(on) {
  const method = on ? 'addEventListener' : 'removeEventListener'
  document[method]('pointerdown', onPointer)
  document[method]('keydown', onKey)
}

watch(open, listen)
watch(() => route.fullPath, () => (open.value = false))
onBeforeUnmount(() => listen(false))

async function leave() {
  busy.value = true
  await signOut(router)
}
</script>

<template>
  <div ref="root" class="pp-menu-anchor">
    <button class="pp-avatar-btn" type="button" aria-label="Account menu" aria-haspopup="menu" :aria-expanded="open" @click="open = !open">
      <span class="pp-avatar">{{ initials(parent.name) }}</span>
    </button>
    <div v-if="open" class="pp-menu" role="menu">
      <div class="pp-menu__who">
        <span class="pp-avatar">{{ initials(parent.name) }}</span>
        <span class="pp-menu__id">
          <b>{{ parent.name }}</b>
          <small v-if="parent.mobile" class="pp-num">{{ parent.mobile }}</small>
          <small v-if="parent.email">{{ parent.email }}</small>
        </span>
      </div>
      <RouterLink class="pp-menu__item" :to="{ name: 'profile' }" role="menuitem"><i class="fa fa-user"></i>Profile</RouterLink>
      <RouterLink class="pp-menu__item" :to="{ name: 'change-password' }" role="menuitem"><i class="fa fa-lock"></i>Change password</RouterLink>
      <button class="pp-menu__item pp-menu__item--neg" type="button" role="menuitem" :disabled="busy" @click="leave">
        <i class="fa fa-sign-out"></i>{{ busy ? 'Signing out…' : 'Sign out' }}
      </button>
    </div>
  </div>
</template>
