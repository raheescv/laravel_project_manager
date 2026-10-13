<template>
    <div class="row g-3">
        <div class="col-12 col-md-6 col-xl-4">
            <div class="pvx-card">
                <div class="pvx-head blue"><i class="fa fa-briefcase"></i>Project &amp; Vendor<a v-if="canEdit" class="pvx-head-btn ms-auto no-print" :href="urlFor(context.urls, 'edit', contract.id)" title="Edit contract"><i class="fa fa-pencil"></i></a></div>
                <dl class="pvx-rows">
                    <div class="pvx-row"><dt>Reference No</dt><dd>{{ contract.reference_no }} <span class="pa-sub">· {{ contract.created_by }}, {{ date(contract.created_at) }}</span></dd></div>
                    <div class="pvx-row"><dt>Project</dt><dd>{{ contract.project_name }}</dd></div>
                    <div class="pvx-row"><dt>Vendor</dt><dd>
                        {{ contract.vendor?.name ?? '—' }}<span v-if="contract.vendor?.mobile" class="pa-sub"> · {{ contract.vendor.mobile }}</span>
                        <div v-if="contract.vendor?.alias_name" class="pa-sub" dir="rtl">{{ contract.vendor.alias_name }}</div>
                    </dd></div>
                    <div class="pvx-row"><dt>Branch</dt><dd>{{ contract.branch ?? '—' }}</dd></div>
                </dl>
                <div v-if="contract.approval" class="pvx-box">
                    <div class="pvx-box-title">
                        <i class="fa fa-gavel"></i> Approval
                        <span class="pa-pill ms-auto" :class="approvalTone">{{ approvalLabel }}</span>
                    </div>
                    <div class="pvx-levels">
                        <template v-for="(level, index) in contract.approval.levels" :key="level.level">
                            <i v-if="index" class="fa fa-angle-right text-muted-pa"></i>
                            <span class="lv" :class="{ done: level.status === 'approved', wait: level.status === 'pending', rejected: level.status === 'rejected' }" :title="level.comment ?? ''">
                                <i class="fa" :class="{ approved: 'fa-check', pending: 'fa-clock-o', rejected: 'fa-times' }[level.status]"></i>
                                L{{ level.level }} · {{ level.acted_by ?? level.role }}
                            </span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-4">
            <div class="pvx-card">
                <div class="pvx-head teal"><i class="fa fa-file-text-o"></i>Contract Details<a class="pvx-head-btn ms-auto no-print" :href="urlFor(context.urls, 'statement', contract.id)" target="_blank" rel="noopener" title="Print the contract statement"><i class="fa fa-print"></i> Print</a></div>
                <dl class="pvx-rows">
                    <div class="pvx-row"><dt>Period</dt><dd class="pa-num">{{ date(contract.start_date) }} → {{ date(contract.end_date) }}<div class="pvx-note">{{ months }} {{ months === 1 ? 'month' : 'months' }} · {{ remainingText }}</div></dd></div>
                    <div class="pvx-row"><dt>Frequency</dt><dd>{{ contract.payment_frequency_label }} <span class="pa-sub">· {{ contract.totals.terms_count }} of {{ contract.no_of_terms }} terms scheduled</span></dd></div>
                    <div class="pvx-row"><dt>Contract Amount</dt><dd class="pa-num"><span class="pvx-accent">{{ money(contract.contract_amount) }}</span> <span v-if="contract.original_amount !== null" class="pa-sub">· was {{ whole(contract.original_amount) }}</span></dd></div>
                    <div class="pvx-row"><dt>Security Held</dt><dd class="pa-num pvx-teal">{{ money(contract.totals.security_held) }}</dd></div>
                </dl>
                <div class="pvx-box">
                    <div class="pvx-fin">
                        <div><small>Contract</small><div class="v">{{ money(contract.contract_amount) }}</div></div>
                        <div><small>Paid</small><div class="v text-success-emphasis">{{ money(contract.totals.paid) }}</div></div>
                        <div><small>Balance</small><div class="v pvx-accent">{{ money(contract.totals.balance) }}</div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="pvx-card">
                <div class="pvx-head green"><i class="fa fa-money"></i>Payment Information<span v-if="contract.status === 'active' || contract.status === 'completed'" class="pvx-head-tag ms-auto">{{ contract.totals.terms_paid }} of {{ contract.totals.terms_count }} terms paid</span></div>
                <template v-if="['active', 'completed'].includes(contract.status)">
                    <dl class="pvx-rows">
                        <div class="pvx-row"><dt>Next Due</dt><dd class="pa-num">
                            <template v-if="contract.next_due">{{ date(contract.next_due.date) }} · {{ money(contract.next_due.amount) }} <span v-if="contract.next_due.overdue" class="pa-pill overdue ms-1">Overdue</span><div class="pa-sub">{{ contract.next_due.label }}</div></template>
                            <span v-else class="pa-sub">Nothing due</span>
                        </dd></div>
                        <div class="pvx-row"><dt>Last Payment</dt><dd class="pa-num">
                            <template v-if="contract.last_payment">{{ date(contract.last_payment.date) }} · {{ money(contract.last_payment.amount) }} <span class="pa-sub">· {{ contract.last_payment.method }}</span></template>
                            <span v-else class="pa-sub">No payments yet</span>
                        </dd></div>
                    </dl>
                    <div class="pvx-box">
                        <table class="pvx-mini">
                            <thead><tr><th>Type</th><th class="num">Paid</th><th class="num">Pending</th></tr></thead>
                            <tbody>
                                <tr><td>Scheduled terms</td><td class="num pvx-accent">{{ money(contract.totals.paid) }}</td><td class="num text-danger-emphasis">{{ money(contract.totals.scheduled_unpaid) }}</td></tr>
                                <tr><td>Not yet scheduled</td><td class="num">—</td><td class="num text-danger-emphasis">{{ money(Math.max(contract.totals.unscheduled, 0)) }}</td></tr>
                                <tr class="tot"><td>Total</td><td class="num pvx-accent">{{ money(contract.totals.paid) }}</td><td class="num text-danger-emphasis">{{ money(contract.totals.balance) }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </template>
                <div v-else class="text-center px-3 py-4">
                    <i class="fa fa-lock fa-lg text-muted-pa"></i>
                    <div class="fw-semibold mt-2">Payments unlock after approval</div>
                    <div class="pa-sub">Terms and payments open once every approval level agrees.</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { urlFor, useContext } from '../common/useContractContext.js'
import { contractMonths, date, daysFromToday, money, whole } from '../format.js'

const props = defineProps({ contract: { type: Object, required: true }, canEdit: { type: Boolean, default: false } })
const context = useContext()

const months = computed(() => contractMonths(props.contract.start_date, props.contract.end_date))
const remainingText = computed(() => {
    const days = daysFromToday(props.contract.end_date)
    if (days < 0) return `ended ${-days} days ago`
    const started = daysFromToday(props.contract.start_date)
    return started > 0 ? `starts in ${started} days` : `${days} days left`
})
const approvalLabel = computed(() => {
    const approval = props.contract.approval
    if (approval.levels.some((level) => level.status === 'rejected')) return 'Rejected'
    if (approval.current_level) return `${approval.current_level - 1} of ${approval.level_count}`
    return 'Approved'
})
const approvalTone = computed(() => ({ Rejected: 'overdue', Approved: 'paid' })[approvalLabel.value] ?? 'draft')

</script>
