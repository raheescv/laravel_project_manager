<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import { fetchBill, fetchStudent } from '@/api/parent'
import AppBar from '@/components/AppBar.vue'
import LoadError from '@/components/LoadError.vue'
import { refreshChild } from '@/children'
import { school } from '@/school'
import { date, firstName, money, quantity, time } from '@/utils/format'
import { desktop } from '@/utils/viewport'

const route = useRoute()
const id = Number(route.params.id)

const bill = ref(null)
const student = ref(null)
const status = ref('loading')
const error = ref('')

const first = computed(() => firstName(student.value?.name))
const subtotal = computed(() => (bill.value?.items || []).reduce((sum, item) => sum + Number(item.total || 0), 0))
const showSubtotal = computed(() => bill.value && (bill.value.discount > 0 || bill.value.tax > 0))

async function load() {
  status.value = 'loading'
  try {
    ;[bill.value, student.value] = await Promise.all([fetchBill(id, route.params.saleId), fetchStudent(id)])
    refreshChild(student.value)
    status.value = 'ready'
  } catch (e) {
    error.value = e.status === 404 ? 'This bill could not be found.' : e.message
    status.value = 'error'
  }
}

const paidByNote = (payment) => (/wallet/i.test(payment.method || '') ? `${first.value}'s card` : 'At the canteen')

onMounted(load)
</script>

<template>
  <AppBar :back="{ name: 'student', params: { id } }" :back-label="first || 'Back'" title="Bill" />

  <LoadError v-if="status === 'error'" title="We couldn't open this bill" :message="error" @retry="load" />

  <main v-else class="pp-bill-layout">
    <div v-if="status === 'loading'" class="pp-pass" aria-busy="true">
      <div class="pp-pass__head" style="height: 150px"></div>
      <div style="padding: 18px">
        <span class="pp-skel pp-skel--line" style="width: 50%"></span>
        <span class="pp-skel pp-skel--line" style="width: 70%"></span>
        <span class="pp-skel pp-skel--line" style="width: 40%"></span>
      </div>
    </div>

    <article v-else class="pp-pass">
      <header class="pp-pass__head">
        <div class="pp-pass__brand">
          <span><i class="fa fa-graduation-cap pp-mark"></i>{{ school.name || 'School' }}</span><span>Canteen bill</span>
        </div>
        <h1 class="pp-pass__title">Bill #{{ bill.invoice_no }}</h1>
        <p class="pp-pass__sub">{{ student.name }}</p>
      </header>
      <dl class="pp-pass__fields">
        <div><dt>Date</dt><dd>{{ date(bill.date) }}</dd></div>
        <div><dt>Time</dt><dd>{{ time(bill.created_at) || '—' }}</dd></div>
        <div><dt>Canteen</dt><dd>{{ bill.branch || '—' }}</dd></div>
        <div><dt>Items</dt><dd class="pp-num">{{ bill.items.length }}</dd></div>
      </dl>
      <div class="pp-pass__total"><span>Total</span><b>{{ money(bill.grand_total) }}</b></div>
      <div class="pp-tear" aria-hidden="true"></div>
      <ul class="pp-lines">
        <li v-for="item in bill.items" :key="item.id" class="pp-line">
          <span>
            <span class="pp-line__name">{{ item.name || 'Item' }}</span>
            <span class="pp-line__qty">{{ quantity(item.quantity) }}{{ item.unit && !/^(nos?|pcs?|units?)$/i.test(item.unit) ? ` ${item.unit}` : '' }} × {{ money(item.unit_price) }}</span>
          </span>
          <span class="pp-line__total">{{ money(item.total) }}</span>
        </li>
      </ul>
      <dl class="pp-sum">
        <div v-if="showSubtotal" class="pp-sum__row"><dt>Subtotal</dt><dd>{{ money(subtotal) }}</dd></div>
        <div v-if="bill.discount > 0" class="pp-sum__row"><dt>Discount</dt><dd>{{ money(-bill.discount) }}</dd></div>
        <div v-if="bill.tax > 0" class="pp-sum__row"><dt>Tax</dt><dd>{{ money(bill.tax) }}</dd></div>
        <div class="pp-sum__row pp-sum__row--total"><dt>Total</dt><dd>{{ money(bill.grand_total) }}</dd></div>
      </dl>
      <div v-for="(payment, index) in bill.payments" :key="index" class="pp-paidby">
        <span class="pp-row__icon"><i class="fa fa-credit-card"></i></span>
        <span class="pp-row__main"><b>Paid by {{ payment.method || 'card' }}</b><span class="pp-row__sub">{{ paidByNote(payment) }}</span></span>
        <span class="pp-amt">{{ money(payment.amount) }}</span>
      </div>
    </article>
    <aside v-if="desktop" class="pp-panel">
      <div class="pp-panel__body">
        <h2 class="pp-panel__title">Something wrong with this bill?</h2>
        <p class="pp-panel__text">Please speak to the school office.</p>
      </div>
      <div v-if="school.contact.mobile || school.contact.email" class="pp-group__body">
        <a v-if="school.contact.mobile" class="pp-row pp-row--icon" :href="`tel:${school.contact.mobile}`">
          <span class="pp-row__icon pp-row__icon--accent"><i class="fa fa-phone"></i></span>
          <span class="pp-row__main"><span class="pp-row__title pp-num">{{ school.contact.mobile }}</span><span class="pp-row__sub">School office</span></span>
        </a>
        <a v-if="school.contact.email" class="pp-row pp-row--icon" :href="`mailto:${school.contact.email}`">
          <span class="pp-row__icon pp-row__icon--accent"><i class="fa fa-envelope"></i></span>
          <span class="pp-row__main"><span class="pp-row__title">{{ school.contact.email }}</span><span class="pp-row__sub">Email</span></span>
        </a>
      </div>
    </aside>
    <p v-else class="pp-hint">Something wrong with this bill? Please speak to the school office.</p>
  </main>
</template>
