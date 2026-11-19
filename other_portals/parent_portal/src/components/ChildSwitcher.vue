<script setup>
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import StudentAvatar from '@/components/StudentAvatar.vue'
import { children, loadChildren } from '@/children'
import { classLabel, firstName } from '@/utils/format'

/**
 * Who this page is for, at the top of it. With more than one child, tapping
 * another opens the same page (and day) for that child straight away.
 * `unsaved` asks before leaving changes behind.
 */
const props = defineProps({
  current: { type: Number, required: true },
  unsaved: { type: Boolean, default: false },
  label: { type: String, default: 'For' },
})

const route = useRoute()
const router = useRouter()
const list = computed(() => children.list)

onMounted(() => {
  if (children.status === 'idle') loadChildren()
})

function pick(child) {
  if (child.account_id === props.current) return
  if (props.unsaved && !window.confirm(`Leave without saving? The changes for this order are not saved.`)) return
  router.replace({ name: route.name, params: { ...route.params, id: child.account_id }, query: route.query })
}
</script>

<template>
  <nav v-if="list.length" class="pp-kidswitch" aria-label="Child">
    <span class="pp-kidswitch__label">{{ label }}</span>
    <div class="pp-kidswitch__row">
      <button
        v-for="child in list"
        :key="child.account_id"
        class="pp-kidswitch__opt"
        type="button"
        :aria-current="child.account_id === current ? 'true' : undefined"
        :disabled="list.length === 1"
        @click="pick(child)"
      >
        <span class="pp-kidswitch__face">
          <StudentAvatar :name="child.name" :image="child.image_url" />
          <i class="fa fa-check pp-kidswitch__tick" aria-hidden="true"></i>
        </span>
        <span class="pp-kidswitch__text">
          <b>{{ firstName(child.name) }}</b>
          <small v-if="child.class">{{ classLabel(child.class) }}</small>
        </span>
      </button>
    </div>
  </nav>
</template>
