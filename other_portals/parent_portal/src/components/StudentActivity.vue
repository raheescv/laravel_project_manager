<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { fetchBills, fetchStatement } from '@/api/parent'
import { amount, currentMonth, dateTile, dayMonth, isMonth, money, monthLabel, shiftMonth } from '@/utils/format'

/**
 * A child's bills or card statement, month by month. With `tabs` it switches
 * between the two in place (desktop child page); without, it shows only `view`
 * (the Bills and Statement pages). Tab and month live in the URL, so coming back
 * from a bill lands on the same list.
 */
const props = defineProps({
  studentId: { type: Number, required: true },
  view: { type: String, default: 'bills' },
  tabs: { type: Boolean, default: false },
})

const route = useRoute()
const router = useRouter()

const tab = computed(() => (props.tabs ? (route.query.tab === 'statement' ? 'statement' : 'bills') : props.view))
const month = computed(() => (isMonth(route.query.month) && route.query.month <= currentMonth() ? route.query.month : currentMonth()))
const isCurrentMonth = computed(() => month.value >= currentMonth())

function setQuery(patch) {
  const query = { ...route.query, ...patch }
  if (query.tab === 'bills') delete query.tab
  if (query.month === currentMonth()) delete query.month
  router.replace({ query })
}

/* ---- Bills ---- */
const bills = ref([])
const billsPage = ref(null)
const billsLoading = ref(false)
const billsError = ref('')

async function loadBills(page = 1) {
  billsLoading.value = true
  billsError.value = ''
  const forMonth = month.value
  try {
    const result = await fetchBills(props.studentId, forMonth, page)
    if (forMonth !== month.value) return
    bills.value = page === 1 ? result.data : [...bills.value, ...result.data]
    billsPage.value = result.pagination
  } catch (e) {
    billsError.value = e.message
  } finally {
    billsLoading.value = false
  }
}

const billsTotal = computed(() => bills.value.reduce((sum, bill) => sum + Number(bill.grand_total || 0), 0))

/* ---- Statement ---- */
const statement = ref(null)
const statementLoading = ref(false)
const statementError = ref('')

async function loadStatement() {
  statementLoading.value = true
  statementError.value = ''
  const forMonth = month.value
  try {
    const result = await fetchStatement(props.studentId, forMonth)
    if (forMonth === month.value) statement.value = result
  } catch (e) {
    statementError.value = e.message
  } finally {
    statementLoading.value = false
  }
}

/** The running balance under a row's amount — no currency code, the amount above carries it. */
const runningBalance = (value) => `${value < 0 ? '−' : ''}${amount(value)}`

// The API words every row; the portal only picks its face.
const statementIcon = (row) =>
  ({
    topup: { icon: 'fa-plus', tone: 'pos' },
    refund: { icon: 'fa-reply', tone: 'neg' },
    purchase: { icon: 'fa-cutlery', tone: 'accent' },
    return: { icon: 'fa-undo', tone: 'warn' },
  })[row.kind] || { icon: row.amount > 0 ? 'fa-plus' : 'fa-minus', tone: 'muted' }

function loadTab() {
  if (tab.value === 'bills') loadBills()
  else loadStatement()
}

watch([tab, month], loadTab)
onMounted(loadTab)
</script>

<template>
  <section class="pp-activity">
    <div class="pp-activity__head">
      <div v-if="tabs" class="pp-seg" data-tour="activity-tabs" role="tablist" aria-label="Bills or statement">
        <button class="pp-seg__tab" type="button" role="tab" :aria-selected="tab === 'bills'" @click="setQuery({ tab: 'bills' })">Bills</button>
        <button class="pp-seg__tab" type="button" role="tab" :aria-selected="tab === 'statement'" @click="setQuery({ tab: 'statement' })">Statement</button>
      </div>
      <div class="pp-month" data-tour="month">
        <button class="pp-month__btn" type="button" aria-label="Previous month" @click="setQuery({ month: shiftMonth(month, -1) })">
          <i class="fa fa-chevron-left"></i>
        </button>
        <span class="pp-month__label" aria-live="polite">{{ monthLabel(month) }}</span>
        <button class="pp-month__btn" type="button" aria-label="Next month" :disabled="isCurrentMonth" @click="setQuery({ month: shiftMonth(month, 1) })">
          <i class="fa fa-chevron-right"></i>
        </button>
      </div>
    </div>

    <!-- Bills -->
    <div v-if="tab === 'bills'" class="pp-group" data-tour="activity-list" role="tabpanel" :class="{ 'is-loading': billsLoading && bills.length }">
      <div class="pp-group__body">
        <template v-if="billsLoading && !billsPage">
          <div v-for="n in 3" :key="n" class="pp-row pp-bill-row" aria-hidden="true">
            <span class="pp-skel" style="width: 44px; height: 44px"></span>
            <span class="pp-row__main"><span class="pp-skel pp-skel--line" style="width: 40%"></span><span class="pp-skel pp-skel--line" style="width: 65%"></span></span>
          </div>
        </template>
        <button v-else-if="billsError && !bills.length" class="pp-row pp-row--empty" type="button" @click="loadBills()">
          {{ billsError }} Tap to try again.
        </button>
        <div v-else-if="!bills.length" class="pp-row pp-row--empty">No purchases in {{ monthLabel(month) }}</div>
        <template v-else>
          <RouterLink
            v-for="bill in bills"
            :key="bill.id"
            class="pp-row pp-bill-row"
            :to="{ name: 'bill', params: { id: studentId, saleId: bill.id } }"
          >
            <span class="pp-date"><small>{{ dateTile(bill.date).month }}</small><b>{{ dateTile(bill.date).day }}</b></span>
            <span class="pp-row__main">
              <span class="pp-row__title">{{ bill.items_count }} {{ bill.items_count === 1 ? 'item' : 'items' }}</span>
              <span class="pp-row__sub">#{{ bill.invoice_no }}{{ bill.branch ? ` · ${bill.branch}` : '' }}</span>
            </span>
            <span class="pp-row__end"><span class="pp-amt">{{ money(bill.grand_total) }}</span><i class="fa fa-angle-right pp-chev"></i></span>
          </RouterLink>
          <button
            v-if="billsPage?.has_more_pages"
            class="pp-row pp-row--more"
            type="button"
            :disabled="billsLoading"
            @click="loadBills(billsPage.current_page + 1)"
          >
            {{ billsLoading ? 'Loading…' : 'Show more bills' }}
          </button>
        </template>
      </div>
      <p v-if="bills.length && billsPage" class="pp-group__foot">
        {{ billsPage.total }} {{ billsPage.total === 1 ? 'bill' : 'bills' }}<template v-if="!billsPage.has_more_pages">
          · {{ money(billsTotal) }} spent in {{ monthLabel(month).split(' ')[0] }}</template
        >
      </p>
    </div>

    <!-- Statement -->
    <div v-else class="pp-group" data-tour="activity-list" role="tabpanel" :class="{ 'is-loading': statementLoading && statement }">
      <div class="pp-group__body">
        <template v-if="statementLoading && !statement">
          <div v-for="n in 3" :key="n" class="pp-row pp-row--icon" aria-hidden="true">
            <span class="pp-skel" style="width: 30px; height: 30px; border-radius: 8px"></span>
            <span class="pp-row__main"><span class="pp-skel pp-skel--line" style="width: 35%"></span><span class="pp-skel pp-skel--line" style="width: 60%"></span></span>
          </div>
        </template>
        <button v-else-if="statementError && !statement" class="pp-row pp-row--empty" type="button" @click="loadStatement">
          {{ statementError }} Tap to try again.
        </button>
        <template v-else-if="statement">
          <div class="pp-row pp-row--quiet">
            <span class="pp-row__main">
              <span class="pp-row__title">Starting balance</span>
              <span class="pp-row__sub">Brought forward from {{ monthLabel(shiftMonth(month, -1)).split(' ')[0] }}</span>
            </span>
            <span class="pp-row__end"><span class="pp-amt">{{ money(statement.opening) }}</span></span>
          </div>
          <div v-if="!statement.rows.length" class="pp-row pp-row--empty">Nothing moved on the card in {{ monthLabel(month) }}</div>
          <component
            :is="row.sale_id ? 'RouterLink' : 'div'"
            v-for="row in statement.rows"
            :key="row.id"
            class="pp-row pp-row--icon pp-stmt-row"
            :to="row.sale_id ? { name: 'bill', params: { id: studentId, saleId: row.sale_id } } : undefined"
          >
            <span class="pp-row__icon" :class="`pp-row__icon--${statementIcon(row).tone}`"><i class="fa" :class="statementIcon(row).icon"></i></span>
            <span class="pp-row__main">
              <span class="pp-row__title">{{ row.title }}</span>
              <span class="pp-row__sub">{{ dayMonth(row.date) }}{{ row.detail ? ` · ${row.detail}` : '' }}</span>
            </span>
            <span class="pp-row__end">
              <span class="pp-amt" :class="{ 'pp-amt--in': row.amount > 0 }">{{ money(row.amount, { sign: true }) }}</span>
              <span class="pp-run">Balance {{ runningBalance(row.balance) }}</span>
            </span>
            <i v-if="row.sale_id" class="fa fa-angle-right pp-chev"></i>
          </component>
          <div class="pp-row pp-row--total">
            <span class="pp-row__main">
              <span class="pp-row__title">{{ isCurrentMonth ? 'On the card now' : 'Closing balance' }}</span>
              <span class="pp-row__sub">{{ isCurrentMonth ? `As of ${dayMonth(new Date())}` : `End of ${monthLabel(month).split(' ')[0]}` }}</span>
            </span>
            <span class="pp-row__end"><span class="pp-amt">{{ money(statement.closing) }}</span></span>
          </div>
        </template>
      </div>
      <p v-if="statement?.rows.length" class="pp-group__foot">
        {{ money(statement.added) }} added · {{ money(statement.spent) }} spent in {{ monthLabel(month).split(' ')[0] }}
      </p>
    </div>
  </section>
</template>
