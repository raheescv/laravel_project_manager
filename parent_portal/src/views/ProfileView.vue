<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import { fetchMe } from '@/api/parent'
import AppBar from '@/components/AppBar.vue'
import StudentAvatar from '@/components/StudentAvatar.vue'
import { children, loadChildren } from '@/children'
import { school } from '@/school'
import { session } from '@/session'
import { signOut } from '@/utils/account'
import { initials } from '@/utils/format'

/**
 * The signed-in parent: the details the school has for them, their children,
 * and their password. The school office owns the name, mobile and email (the
 * mobile is what links brothers and sisters to one login), so they are read-only.
 */
const router = useRouter()
const signingOut = ref(false)

const parent = computed(() => session.parent || {})

async function leave() {
  signingOut.value = true
  await signOut(router)
}

onMounted(() => {
  fetchMe()
    .then((me) => session.setParent(me))
    .catch(() => {})
  if (children.status === 'idle') loadChildren()
})
</script>

<template>
  <AppBar :back="{ name: 'home' }" back-label="Children" title="Profile" />

  <main class="pp-narrow">
    <section class="pp-profile">
      <span class="pp-avatar">{{ initials(parent.name) }}</span>
      <div>
        <h1>{{ parent.name }}</h1>
        <p>Parent{{ school.name ? ` at ${school.name}` : '' }}</p>
      </div>
    </section>

    <section class="pp-group">
      <h2 class="pp-group__head">Contact</h2>
      <dl class="pp-group__body">
        <div class="pp-row"><dt>Mobile</dt><dd class="pp-num">{{ parent.mobile || '—' }}</dd></div>
        <div class="pp-row">
          <dt>Email</dt>
          <dd :class="{ 'pp-text-muted': !parent.email }">{{ parent.email || 'Not added' }}</dd>
        </div>
      </dl>
      <p class="pp-group__foot">
        <span>
          You sign in with {{ parent.email ? 'either of these' : 'your mobile number' }}. To change them, please contact the school office<template
            v-if="school.contact.mobile"
            >&nbsp;on <a class="pp-num" :href="`tel:${school.contact.mobile}`">{{ school.contact.mobile }}</a></template
          >.
        </span>
      </p>
    </section>

    <section class="pp-group">
      <h2 class="pp-group__head">Sign-in</h2>
      <div class="pp-group__body">
        <RouterLink class="pp-row pp-row--icon" :to="{ name: 'change-password' }">
          <span class="pp-row__icon"><i class="fa fa-lock"></i></span>
          <span class="pp-row__main">
            <span class="pp-row__title">Change password</span>
            <span class="pp-row__sub">Other phones and computers are signed out</span>
          </span>
          <i class="fa fa-angle-right pp-chev"></i>
        </RouterLink>
        <button class="pp-row pp-row--icon" type="button" :disabled="signingOut" @click="leave">
          <span class="pp-row__icon pp-row__icon--neg"><i class="fa fa-sign-out"></i></span>
          <span class="pp-row__main">
            <span class="pp-row__title pp-text-neg">{{ signingOut ? 'Signing out…' : 'Sign out' }}</span>
            <span class="pp-row__sub">On this device</span>
          </span>
        </button>
      </div>
    </section>

    <section v-if="children.list.length" class="pp-group">
      <h2 class="pp-group__head">Your children</h2>
      <div class="pp-group__body">
        <RouterLink
          v-for="student in children.list"
          :key="student.account_id"
          class="pp-row pp-kid-link"
          :to="{ name: 'student', params: { id: student.account_id } }"
        >
          <StudentAvatar :name="student.name" :image="student.image_url" />
          <span class="pp-row__main">
            <span class="pp-row__title">{{ student.name }}</span>
            <span class="pp-row__sub">{{ [student.class, student.admission_no].filter(Boolean).join(' · ') }}</span>
          </span>
          <i class="fa fa-angle-right pp-chev"></i>
        </RouterLink>
      </div>
      <p class="pp-group__foot">The school office links children to your login.</p>
    </section>
  </main>
</template>
