<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import { fetchStudent } from '@/api/parent'
import AppBar from '@/components/AppBar.vue'
import ChildSwitcher from '@/components/ChildSwitcher.vue'
import LoadError from '@/components/LoadError.vue'
import StudentActivity from '@/components/StudentActivity.vue'
import { refreshChild } from '@/children'
import { firstName } from '@/utils/format'

/** A child's Bills (`student-bills`) or card Statement (`student-statement`) on a page of its own. */
const route = useRoute()
const id = Number(route.params.id)
const view = route.name === 'student-statement' ? 'statement' : 'bills'
const title = view === 'statement' ? 'Statement' : 'Bills'

const student = ref(null)
const error = ref('')
const first = computed(() => firstName(student.value?.name))

async function load() {
  error.value = ''
  try {
    student.value = await fetchStudent(id)
    refreshChild(student.value)
  } catch (e) {
    error.value = e.status === 404 ? "This child isn't linked to your login." : e.message
  }
}

onMounted(load)
</script>

<template>
  <AppBar :back="{ name: 'student', params: { id } }" :back-label="first || 'Back'" :title="title" />

  <LoadError v-if="error" :title="`We couldn't open the ${title.toLowerCase()}`" :message="error" @retry="load">
    <RouterLink class="pp-link" :to="{ name: 'home' }">Back to my children</RouterLink>
  </LoadError>

  <main v-else>
    <ChildSwitcher :current="id" />
    <div class="pp-largetitle">
      <h1>{{ title }}</h1>
      <p>{{ view === 'statement' ? `Everything that moved on ${first || 'the'}${first ? "'s" : ''} card, month by month.` : `What ${first || 'your child'} bought at the canteen.` }}</p>
    </div>
    <StudentActivity :student-id="id" :view="view" />
  </main>
</template>
