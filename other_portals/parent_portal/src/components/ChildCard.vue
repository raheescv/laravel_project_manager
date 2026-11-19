<script setup>
import { computed } from 'vue'

import SchoolMark from '@/components/SchoolMark.vue'
import StudentAvatar from '@/components/StudentAvatar.vue'
import { school } from '@/school'
import { amount, classLabel, money } from '@/utils/format'

/**
 * A child as their school ID card ("Split Portrait"): the photo down the left on
 * the school colour, then name, grade, student number and the balance. On the home
 * stack it is a link; as the child page's hero (`hero`) the balance is the headline.
 */
const props = defineProps({
  student: { type: Object, required: true },
  hero: { type: Boolean, default: false },
  to: { type: [Object, String], default: null },
})

const state = computed(() => (!props.student.has_card ? 'none' : props.student.card_blocked ? 'blocked' : 'active'))
const negative = computed(() => Number(props.student.balance) < 0)
const statusLabel = computed(() => ({ blocked: 'Card blocked', active: 'Card active' })[state.value] || '')
</script>

<template>
  <component
    :is="to ? 'RouterLink' : 'div'"
    :to="to || undefined"
    class="pp-child-card"
    :class="{ 'pp-child-card--blocked': state === 'blocked', 'pp-child-card--none': state === 'none', 'pp-child-card--hero': hero }"
  >
    <span class="pp-card__photo">
      <StudentAvatar :name="student.name" :image="student.image_url" />
      <SchoolMark />
      <span class="pp-card__kind">Student ID</span>
    </span>

    <span class="pp-card__info">
      <span class="pp-card__top">
        <span class="pp-card__label">Student</span>
        <span v-if="state !== 'none'" class="pp-status" :class="`pp-status--${state}`">
          <i v-if="state === 'blocked'" class="fa fa-ban"></i>{{ statusLabel }}
        </span>
      </span>
      <span class="pp-card__name">{{ student.name }}</span>
      <span v-if="student.class || student.admission_no" class="pp-card__class">
        {{ classLabel(student.class) }}<span v-if="student.admission_no" class="pp-card__no">ID {{ student.admission_no }}</span>
      </span>

      <span v-if="hero" class="pp-card__balance" :class="{ 'pp-card__balance--neg': negative }">
        <small>Card balance</small>
        <b class="pp-num"><span>{{ school.currency.code }}</span>{{ negative ? '−' : '' }}{{ amount(student.balance) }}</b>
      </span>
      <span v-else class="pp-card__bal" :class="{ 'pp-card__bal--neg': negative && state !== 'none' }">
        <small>{{ negative ? 'Overdraft' : 'Balance' }}</small><b>{{ money(student.balance) }}</b>
      </span>

      <span class="pp-card__bottom">
        <i v-if="state !== 'none'" class="pp-card__barcode" aria-hidden="true"></i>
        <span v-if="to" class="pp-card__open">Open<i class="fa fa-angle-right" aria-hidden="true"></i></span>
        <span v-else class="pp-card__type">{{ state === 'none' ? 'No card yet' : 'Student card' }}</span>
      </span>
    </span>
  </component>
</template>
