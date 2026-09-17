<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { fetchMe, fetchStudents, logout } from '@/api/parent'
import AppBar from '@/components/AppBar.vue'
import BottomSheet from '@/components/BottomSheet.vue'
import ChildCard from '@/components/ChildCard.vue'
import LoadError from '@/components/LoadError.vue'
import { school } from '@/school'
import { session } from '@/session'
import { firstName, initials } from '@/utils/format'

const route = useRoute()
const router = useRouter()

const students = ref([])
const status = ref('loading') // loading · ready · error
const error = ref('')
const menuOpen = ref(false)
const signingOut = ref(false)

const parent = computed(() => session.parent || {})
const overdrawn = computed(() => students.value.filter((student) => Number(student.balance) < 0))
const unmatchedPayment = computed(() => route.query.payment === 'unmatched')

async function load() {
  status.value = students.value.length ? 'ready' : 'loading'
  try {
    students.value = await fetchStudents()
    status.value = 'ready'
  } catch (e) {
    error.value = e.message
    if (!students.value.length) status.value = 'error'
  }
}

async function signOut() {
  signingOut.value = true
  try {
    await logout()
  } catch {
    // Signed out on this device either way.
  }
  menuOpen.value = false
  session.end()
  router.replace({ name: 'login' })
}

onMounted(() => {
  load()
  // Keep the name and mobile in the menu current (the school may have edited them).
  fetchMe()
    .then((me) => session.setParent(me))
    .catch(() => {})
})
</script>

<template>
  <AppBar>
    <template #start>
      <span class="pp-appbar__school">
        <img v-if="school.logo" class="pp-appbar__logo" :src="school.logo" alt="" />
        <i v-else class="fa fa-graduation-cap pp-mark"></i>
        {{ school.name || 'Parent Portal' }}
      </span>
    </template>
    <template #end>
      <button class="pp-avatar-btn" type="button" aria-label="Account menu" @click="menuOpen = true">
        <span class="pp-avatar">{{ initials(parent.name) }}</span>
      </button>
    </template>
  </AppBar>

  <LoadError v-if="status === 'error'" title="We couldn't load your children" :message="error" @retry="load" />

  <main v-else>
    <div class="pp-largetitle">
      <h1>Hello, {{ firstName(parent.name) || 'there' }}</h1>
      <p v-if="status === 'loading'">Getting your children's cards…</p>
      <p v-else-if="students.length === 1">Your child's card. Tap it to open.</p>
      <p v-else-if="students.length">Your {{ students.length }} children. Tap a card to open it.</p>
    </div>

    <div v-if="unmatchedPayment" class="pp-alert" role="alert">
      <i class="fa fa-exclamation-circle"></i>
      <span class="pp-alert__main">
        <span class="pp-alert__title">We couldn't match that payment</span>If money was taken, please contact the school office.
      </span>
    </div>

    <div v-for="student in overdrawn" :key="`od-${student.account_id}`" class="pp-alert pp-alert--warn">
      <i class="fa fa-exclamation-triangle"></i>
      <span class="pp-alert__main"><span class="pp-alert__title">{{ student.name }}</span>The card is in overdraft. Please top up.</span>
      <RouterLink class="pp-alert__btn" :to="{ name: 'topup', params: { id: student.account_id } }">Top up</RouterLink>
    </div>

    <section v-if="status === 'loading'" class="pp-wallet-stack" aria-busy="true" aria-label="Loading">
      <span class="pp-skel pp-skel--card"></span>
      <span class="pp-skel pp-skel--card"></span>
    </section>

    <section v-else-if="students.length" class="pp-wallet-stack" aria-label="Your children">
      <ChildCard v-for="student in students" :key="student.account_id" :student="student" :to="{ name: 'student', params: { id: student.account_id } }" />
    </section>

    <div v-else class="pp-lede pp-lede--empty">
      <div class="pp-hero-icon"><i class="fa fa-users"></i></div>
      <h2>No children linked yet</h2>
      <p>The school hasn't linked a child to your login. Please contact the school office.</p>
    </div>

    <p v-if="students.length" class="pp-hint">Balances change each time your child pays at the canteen.</p>
  </main>

  <BottomSheet :open="menuOpen" variant="action" label="Account" @close="menuOpen = false">
    <div class="pp-action-group">
      <div class="pp-action-profile">
        <span class="pp-avatar">{{ initials(parent.name) }}</span>
        <b>{{ parent.name }}</b>
        <span v-if="parent.mobile">{{ parent.mobile }}</span>
        <span v-if="parent.email">{{ parent.email }}</span>
      </div>
      <button class="pp-action pp-action--danger" type="button" :disabled="signingOut" @click="signOut">
        <i class="fa fa-sign-out"></i>{{ signingOut ? 'Signing out…' : 'Sign out' }}
      </button>
    </div>
    <div class="pp-action-group">
      <button class="pp-action pp-action--cancel" type="button" @click="menuOpen = false">Cancel</button>
    </div>
  </BottomSheet>
</template>
