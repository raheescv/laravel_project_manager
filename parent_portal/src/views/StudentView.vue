<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { blockCard, fetchBills, fetchStatement, fetchStudent } from '@/api/parent'
import AppBar from '@/components/AppBar.vue'
import BottomSheet from '@/components/BottomSheet.vue'
import ChildCard from '@/components/ChildCard.vue'
import LoadError from '@/components/LoadError.vue'
import StudentAvatar from '@/components/StudentAvatar.vue'
import { refreshChild } from '@/children'
import { toast } from '@/toast'
import { currentMonth, date, dateTile, dayMonth, firstName, isMonth, money, monthLabel, shiftMonth, weekdaysLabel } from '@/utils/format'
import { desktop } from '@/utils/viewport'

const route = useRoute()
const router = useRouter()
const id = computed(() => Number(route.params.id))

const student = ref(null)
const status = ref('loading')
const error = ref('')

// Tab and month live in the URL, so coming back from a bill lands on the same list.
const tab = computed(() => (route.query.tab === 'statement' ? 'statement' : 'bills'))
const month = computed(() => (isMonth(route.query.month) && route.query.month <= currentMonth() ? route.query.month : currentMonth()))
const isCurrentMonth = computed(() => month.value >= currentMonth())

function setQuery(patch) {
  const query = { ...route.query, ...patch }
  if (query.tab === 'bills') delete query.tab
  if (query.month === currentMonth()) delete query.month
  router.replace({ query })
}

const first = computed(() => firstName(student.value?.name))

const mealsToday = computed(() => {
  const today = student.value?.pre_orders?.today
  if (!today) return 'Nothing ordered for today'
  if (today.source === 'skipped') return 'No meal today'
  return today.collected ? "Today's meal collected" : 'Meal ordered for today'
})
const mealsSummary = computed(() => {
  const weekly = student.value?.pre_orders?.weekly
  return mealsToday.value + (weekly ? (weekly.status === 'paused' ? ' · weekly paused' : ' · weekly on') : '')
})
const cardState = computed(() => (!student.value?.has_card ? 'none' : student.value.card_blocked ? 'blocked' : 'active'))

/* Desktop: the card is in the wallet, so the page leads with the numbers. */
const details = computed(() => [student.value?.class, student.value?.admission_no].filter(Boolean).join(' · '))
const balanceNote = computed(() => {
  if (cardState.value === 'none') return 'No card yet'
  if (cardState.value === 'blocked') return 'Card blocked'
  return student.value.balance < 0 ? 'In overdraft. Please top up.' : 'Card active'
})
const weeklyMeals = computed(() => {
  const weekly = student.value?.pre_orders?.weekly
  if (!weekly) return 'No weekly order'
  return weekly.status === 'paused' ? 'Weekly order paused' : `Weekly · ${weekdaysLabel(weekly.weekdays)}`
})

async function loadStudent() {
  try {
    student.value = await fetchStudent(id.value)
    refreshChild(student.value)
    status.value = 'ready'
  } catch (e) {
    error.value = e.status === 404 ? "This child isn't linked to your login." : e.message
    status.value = 'error'
  }
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
    const result = await fetchBills(id.value, forMonth, page)
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
    const result = await fetchStatement(id.value, forMonth)
    if (forMonth === month.value) statement.value = result
  } catch (e) {
    statementError.value = e.message
  } finally {
    statementLoading.value = false
  }
}

const statementIcon = (row) =>
  ({
    student_topup: { icon: 'fa-plus', tone: 'pos' },
    student_topup_refund: { icon: 'fa-reply', tone: 'neg' },
    sale: { icon: 'fa-cutlery', tone: '' },
    sale_return: { icon: 'fa-undo', tone: 'warn' },
    saleReturn: { icon: 'fa-undo', tone: 'warn' },
  })[row.source] || { icon: 'fa-exchange', tone: 'muted' }

function loadTab() {
  if (tab.value === 'bills') loadBills()
  else loadStatement()
}

watch([tab, month], loadTab)

/* ---- Lost card ---- */
const sheetOpen = ref(false)
const reason = ref('')
const blocking = ref(false)
const blockError = ref('')

async function block() {
  blocking.value = true
  blockError.value = ''
  try {
    await blockCard(id.value, reason.value.trim() || null)
    sheetOpen.value = false
    reason.value = ''
    toast(`${first.value}'s card is blocked`)
    await loadStudent()
  } catch (e) {
    blockError.value = e.message
  } finally {
    blocking.value = false
  }
}

onMounted(() => {
  loadStudent()
  loadTab()
})
</script>

<template>
  <AppBar :back="{ name: 'home' }" back-label="Children" :title="first" :desktop-back="false" />

  <LoadError v-if="status === 'error'" title="We couldn't open this card" :message="error" @retry="loadStudent">
    <RouterLink class="pp-link" :to="{ name: 'home' }">Back to my children</RouterLink>
  </LoadError>

  <main v-else>
    <template v-if="desktop">
      <header class="pp-detail__head">
        <StudentAvatar v-if="student" :name="student.name" :image="student.image_url" />
        <span v-else class="pp-skel pp-avatar" aria-hidden="true"></span>
        <div class="pp-detail__id">
          <h1 v-if="student">{{ student.name }}</h1>
          <span v-else class="pp-skel pp-skel--title"></span>
          <p v-if="details">{{ details }}</p>
        </div>
        <template v-if="student">
          <RouterLink v-if="student.topup.enabled" class="pp-btn pp-btn--primary pp-btn--auto" :to="{ name: 'topup', params: { id } }">
            <i class="fa fa-plus-circle"></i>Top up card
          </RouterLink>
          <button v-else class="pp-btn pp-btn--primary pp-btn--auto" type="button" disabled><i class="fa fa-plus-circle"></i>Top up card</button>
        </template>
      </header>
      <p v-if="student && !student.topup.enabled" class="pp-cta__note pp-cta__note--start">
        Online top-up isn't available right now. Please contact the school office.
      </p>

      <div v-if="student" class="pp-stats">
        <div class="pp-stat" :class="{ 'pp-stat--neg': student.balance < 0 }">
          <small>Card balance</small><b>{{ money(student.balance) }}</b><span>{{ balanceNote }}</span>
        </div>
        <div v-if="cardState === 'active'" class="pp-stat">
          <small>Can spend</small><b>{{ money(student.available) }}</b>
          <span>{{ student.overdraft_limit > 0 ? `Includes ${money(student.overdraft_limit)} overdraft` : 'No overdraft' }}</span>
        </div>
        <div v-if="student.pre_orders?.enabled" class="pp-stat pp-stat--text">
          <small><i class="fa fa-cutlery"></i>Meals</small><b>{{ weeklyMeals }}</b><span>{{ mealsToday }}</span>
        </div>
      </div>
    </template>

    <template v-else>
      <section class="pp-balance-hero">
        <span v-if="!student" class="pp-skel pp-skel--card" aria-busy="true"></span>
        <ChildCard v-else :student="student" hero />

        <p v-if="student && cardState === 'active' && student.overdraft_limit > 0" class="pp-spend">
          <i class="fa fa-info-circle"></i>
          <span><b>Can spend {{ money(student.available) }}</b><small>Includes an overdraft of {{ money(student.overdraft_limit) }}</small></span>
        </p>
        <p v-else-if="student && student.balance < 0" class="pp-spend">
          <i class="fa fa-exclamation-triangle pp-text-neg"></i>
          <span><b>The card is in overdraft</b><small>Please top up so {{ first }} can keep paying.</small></span>
        </p>
        <div v-else class="pp-spend-gap"></div>
      </section>

      <div v-if="student" class="pp-cta">
        <RouterLink v-if="student.topup.enabled" class="pp-btn pp-btn--primary" :to="{ name: 'topup', params: { id } }">
          <i class="fa fa-plus-circle"></i>Top up card
        </RouterLink>
        <template v-else>
          <button class="pp-btn pp-btn--primary" type="button" disabled><i class="fa fa-plus-circle"></i>Top up card</button>
          <p class="pp-cta__note">Online top-up isn't available right now. Please contact the school office.</p>
        </template>
      </div>
    </template>

    <div class="pp-student-grid">
      <div class="pp-student-grid__aside">
        <section v-if="student" class="pp-group">
          <h2 class="pp-group__head">Card</h2>
          <div class="pp-group__body">
            <div v-if="cardState === 'none'" class="pp-row pp-row--icon">
              <span class="pp-row__icon pp-row__icon--muted"><i class="fa fa-credit-card"></i></span>
              <span class="pp-row__main"><span class="pp-row__title">No card yet</span><span class="pp-row__sub">Ask the school office about a card</span></span>
            </div>
            <div v-else-if="cardState === 'blocked'" class="pp-row pp-row--icon">
              <span class="pp-row__icon pp-row__icon--neg"><i class="fa fa-ban"></i></span>
              <span class="pp-row__main">
                <span class="pp-row__title pp-text-neg">Card blocked</span>
                <span class="pp-row__sub">{{ student.card_blocked_at ? `Since ${date(student.card_blocked_at)}` : 'The canteen will refuse it' }}</span>
              </span>
            </div>
            <template v-else>
              <div class="pp-row pp-row--icon">
                <span class="pp-row__icon pp-row__icon--pos"><i class="fa fa-credit-card"></i></span>
                <span class="pp-row__main"><span class="pp-row__title">Card active</span><span class="pp-row__sub">{{ first }} can pay at the canteen</span></span>
              </div>
              <button class="pp-row pp-row--icon" type="button" @click="sheetOpen = true">
                <span class="pp-row__icon pp-row__icon--neg"><i class="fa fa-ban"></i></span>
                <span class="pp-row__main"><span class="pp-row__title pp-text-neg">Lost card?</span><span class="pp-row__sub">Block it straight away</span></span>
                <i class="fa fa-angle-right pp-chev"></i>
              </button>
            </template>
          </div>
          <p v-if="cardState === 'blocked'" class="pp-group__foot">Only the school office can switch it back on or issue a new card. The balance stays with {{ first }}.</p>
        </section>

        <section v-if="student?.pre_orders?.enabled" class="pp-group">
          <h2 class="pp-group__head">Canteen</h2>
          <div class="pp-group__body">
            <RouterLink class="pp-row pp-row--icon" :to="{ name: 'pre-orders', params: { id } }">
              <span class="pp-row__icon pp-row__icon--accent"><i class="fa fa-cutlery"></i></span>
              <span class="pp-row__main">
                <span class="pp-row__title">Meals</span>
                <span class="pp-row__sub">{{ mealsSummary }}</span>
              </span>
              <i class="fa fa-angle-right pp-chev"></i>
            </RouterLink>
          </div>
        </section>
      </div>

      <section class="pp-activity">
        <div class="pp-activity__head">
          <div class="pp-seg" role="tablist" aria-label="Bills or statement">
            <button class="pp-seg__tab" type="button" role="tab" :aria-selected="tab === 'bills'" @click="setQuery({ tab: 'bills' })">Bills</button>
            <button class="pp-seg__tab" type="button" role="tab" :aria-selected="tab === 'statement'" @click="setQuery({ tab: 'statement' })">Statement</button>
          </div>
          <div class="pp-month">
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
        <div v-if="tab === 'bills'" class="pp-group" role="tabpanel" :class="{ 'is-loading': billsLoading && bills.length }">
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
                :to="{ name: 'bill', params: { id, saleId: bill.id } }"
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
        <div v-else class="pp-group" role="tabpanel" :class="{ 'is-loading': statementLoading && statement }">
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
                  <span class="pp-row__title">Brought forward</span>
                  <span class="pp-row__sub">From {{ monthLabel(shiftMonth(month, -1)).split(' ')[0] }}</span>
                </span>
                <span class="pp-row__end"><span class="pp-amt">{{ money(statement.opening) }}</span></span>
              </div>
              <div v-if="!statement.rows.length" class="pp-row pp-row--empty">No card activity in {{ monthLabel(month) }}</div>
              <div v-for="row in statement.rows" :key="row.id" class="pp-row pp-row--icon pp-stmt-row">
                <span class="pp-row__icon" :class="statementIcon(row).tone && `pp-row__icon--${statementIcon(row).tone}`"><i class="fa" :class="statementIcon(row).icon"></i></span>
                <span class="pp-row__main">
                  <span class="pp-row__title">{{ row.type }}</span>
                  <span class="pp-row__sub">{{ dayMonth(row.date) }}{{ row.description ? ` · ${row.description}` : '' }}</span>
                </span>
                <span class="pp-row__end">
                  <span v-if="row.credit > 0" class="pp-amt pp-amt--in">{{ money(row.credit, { sign: true }) }}</span>
                  <span v-else class="pp-amt">{{ money(-row.debit) }}</span>
                  <span class="pp-run">{{ money(row.balance) }}</span>
                </span>
              </div>
              <div class="pp-row pp-row--total">
                <span class="pp-row__main">
                  <span class="pp-row__title">Closing balance</span>
                  <span class="pp-row__sub">{{ isCurrentMonth ? `As of ${dayMonth(new Date())}` : `End of ${monthLabel(month).split(' ')[0]}` }}</span>
                </span>
                <span class="pp-row__end"><span class="pp-amt">{{ money(statement.closing) }}</span></span>
              </div>
            </template>
          </div>
        </div>
      </section>
    </div>
  </main>

  <BottomSheet :open="sheetOpen" label="Block card" @close="sheetOpen = false">
    <div class="pp-sheet__grabber"></div>
    <div class="pp-sheet__bar">
      <button class="pp-navbtn" type="button" @click="sheetOpen = false">Cancel</button>
      <div class="pp-sheet__title">Lost card</div>
    </div>
    <div class="pp-lede pp-lede--sheet">
      <div class="pp-hero-icon pp-hero-icon--neg"><i class="fa fa-ban"></i></div>
      <h2>Block {{ first }}'s card?</h2>
      <p>
        Blocking stops the card at the canteen straight away. Only the school office can switch it back on or issue a new card; the balance
        stays with your child.
      </p>
    </div>
    <div v-if="blockError" class="pp-alert" role="alert">
      <i class="fa fa-exclamation-circle"></i><span class="pp-alert__main">{{ blockError }}</span>
    </div>
    <form novalidate @submit.prevent="block">
      <div class="pp-group">
        <div class="pp-group__body">
          <div class="pp-field">
            <label class="pp-field__body">
              <span class="pp-field__label">Reason (optional)</span>
              <input v-model="reason" class="pp-input" type="text" maxlength="200" placeholder="For example, lost on the school bus" />
            </label>
          </div>
        </div>
      </div>
      <div class="pp-sheet__actions">
        <button class="pp-btn pp-btn--danger" :class="{ 'is-busy': blocking }" type="submit" :disabled="blocking">
          <span v-if="blocking" class="pp-spinner pp-spinner--sm" aria-hidden="true"></span><i v-else class="fa fa-ban"></i>
          {{ blocking ? 'Blocking…' : 'Block card' }}
        </button>
      </div>
    </form>
  </BottomSheet>
</template>
