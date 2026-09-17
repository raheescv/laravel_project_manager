<script setup>
import { computed } from 'vue'

import SchoolMark from '@/components/SchoolMark.vue'
import StudentAvatar from '@/components/StudentAvatar.vue'
import { school } from '@/school'
import { amount, firstName, money } from '@/utils/format'

/**
 * A child as a physical student card. On the home stack it is a link showing who
 * and the balance; as the child page's hero (`hero`) the balance is the headline.
 */
const props = defineProps({
  student: { type: Object, required: true },
  hero: { type: Boolean, default: false },
  to: { type: [Object, String], default: null },
})

const state = computed(() => (!props.student.has_card ? 'none' : props.student.card_blocked ? 'blocked' : 'active'))
const negative = computed(() => Number(props.student.balance) < 0)
const statusLabel = computed(() => ({ none: 'No card yet', blocked: 'Card blocked', active: 'Card active' })[state.value])
</script>

<template>
  <component
    :is="to ? 'RouterLink' : 'div'"
    :to="to || undefined"
    class="pp-child-card"
    :class="{ 'pp-child-card--blocked': state === 'blocked', 'pp-child-card--none': state === 'none' }"
  >
    <span class="pp-card__top">
      <SchoolMark />
      <span class="pp-status" :class="`pp-status--${state}`">
        <i v-if="state === 'blocked'" class="fa fa-ban"></i>{{ statusLabel }}
      </span>
    </span>

    <template v-if="hero">
      <span class="pp-card__balance">
        <small>Card balance</small>
        <b class="pp-num"><span>{{ school.currency.code }}</span>{{ negative ? '−' : '' }}{{ amount(student.balance) }}</b>
      </span>
      <span class="pp-card__bottom">
        <span v-if="state !== 'none'" class="pp-card__chip"></span>
        <span class="pp-card__holder"><b>{{ student.name }}</b><small v-if="student.class">{{ student.class }}</small></span>
        <i v-if="state !== 'none'" class="fa fa-wifi pp-card__nfc"></i>
      </span>
    </template>

    <template v-else>
      <span class="pp-card__who">
        <StudentAvatar :name="student.name" :image="student.image_url" />
        <span class="pp-card__id">
          <span class="pp-card__name">{{ student.name }}</span>
          <span v-if="student.class || student.admission_no" class="pp-card__class">{{ student.class || student.admission_no }}</span>
        </span>
        <span class="pp-card__bal" :class="{ 'pp-card__bal--neg': negative && state !== 'none' }">
          <small>{{ negative ? 'Overdraft' : 'Balance' }}</small><b>{{ money(student.balance) }}</b>
        </span>
      </span>
      <span v-if="state === 'none'" class="pp-card__empty">
        <i class="fa fa-info-circle"></i>Ask the school office about a card for {{ firstName(student.name) }}.
      </span>
      <span v-else class="pp-card__bottom">
        <span class="pp-card__chip"></span><span class="pp-card__label">Student card</span><i class="fa fa-wifi pp-card__nfc"></i>
      </span>
    </template>
  </component>
</template>
