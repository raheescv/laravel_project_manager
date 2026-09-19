<script setup>
import { computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import ToastHost from '@/components/ToastHost.vue'
import WalletPane from '@/components/WalletPane.vue'
import { session } from '@/session'
import { desktop } from '@/utils/viewport'

const route = useRoute()
const router = useRouter()

// Signed-in pages on a computer: the wallet on the left, the page on the right.
const shell = computed(() => desktop.value && session.signedIn && !route.meta.guest && !route.meta.public)

// The API refused the token (expired, signed out elsewhere, parent disabled):
// back to sign in, remembering the page the parent was on.
watch(
  () => session.expired,
  (expired) => {
    if (!expired || route.meta.guest || route.meta.public) return
    router.replace({ name: 'login', query: route.fullPath !== '/' ? { redirect: route.fullPath } : {} })
  },
)
</script>

<template>
  <div v-if="shell" class="pp-web">
    <WalletPane />
    <RouterView v-slot="{ Component, route: current }">
      <Transition name="pp-fade" mode="out-in">
        <div :key="current.fullPath.split('?')[0]" class="pp-detail">
          <component :is="Component" />
        </div>
      </Transition>
    </RouterView>
  </div>
  <RouterView v-else v-slot="{ Component, route: current }">
    <Transition name="pp-fade" mode="out-in">
      <div :key="current.fullPath.split('?')[0]" class="pp-app" :class="{ 'pp-app--full': current.meta.guest || current.meta.public }">
        <component :is="Component" />
      </div>
    </Transition>
  </RouterView>
  <ToastHost />
</template>
