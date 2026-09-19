<script setup>
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'

import AccountMenu from '@/components/AccountMenu.vue'
import ChildCard from '@/components/ChildCard.vue'
import { children, loadChildren } from '@/children'
import { school } from '@/school'
import { session } from '@/session'
import { firstName } from '@/utils/format'

/**
 * The desktop layout's left side: the school, the account menu and the wallet of
 * children's cards. The open child's card moves to the top; the rest stack below.
 */
const route = useRoute()

const selectedId = computed(() => (route.params.id ? Number(route.params.id) : null))
const selected = computed(() => children.list.find((student) => student.account_id === selectedId.value) || null)
const cards = computed(() => (selected.value ? [selected.value, ...children.list.filter((student) => student !== selected.value)] : children.list))
const loading = computed(() => ['idle', 'loading'].includes(children.status))

const lede = computed(() => {
  const count = children.list.length
  if (loading.value) return "Getting your children's cards…"
  if (!count) return ''
  if (count === 1) return "Your child's card. Click it to open."
  return selected.value ? 'Click another card to switch.' : `Your ${count} children. Click a card to open it.`
})

onMounted(() => {
  if (children.status === 'idle') loadChildren()
})
</script>

<template>
  <aside class="pp-wallet">
    <div class="pp-wallet__top">
      <RouterLink class="pp-wallet__brand" :to="{ name: 'home' }">
        <span v-if="school.logo" class="pp-logo-tile pp-logo-tile--image"><img :src="school.logo" alt="" /></span>
        <span v-else class="pp-logo-tile"><i class="fa fa-graduation-cap"></i></span>
        <span class="pp-brandname"><b>{{ school.name || 'Parent Portal' }}</b><small v-if="school.name">Parent Portal</small></span>
      </RouterLink>
      <AccountMenu />
    </div>

    <div class="pp-wallet__title">
      <h1>Hello, {{ firstName(session.parent?.name) || 'there' }}</h1>
      <p v-if="lede">{{ lede }}</p>
    </div>

    <section v-if="loading && !children.list.length" class="pp-wallet-stack" aria-busy="true" aria-label="Loading">
      <span class="pp-skel pp-skel--card"></span>
      <span class="pp-skel pp-skel--card"></span>
    </section>
    <p v-else-if="children.status === 'error'" class="pp-wallet__error">
      We couldn't load your children.
      <button class="pp-link" type="button" @click="loadChildren">Try again</button>
    </p>
    <TransitionGroup v-else tag="section" name="pp-wallet" class="pp-wallet-stack" :class="{ 'has-selected': selected }" aria-label="Your children">
      <ChildCard
        v-for="student in cards"
        :key="student.account_id"
        :student="student"
        :to="{ name: 'student', params: { id: student.account_id } }"
        :class="{ 'is-selected': student === selected }"
      />
    </TransitionGroup>

    <p v-if="children.list.length" class="pp-wallet__note">Balances change each time your child pays at the canteen.</p>
  </aside>
</template>
