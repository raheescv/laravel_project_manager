<template>
    <div>
        <div class="pa-sec-sum">
            <div><div class="pa-eyebrow">Held</div><div class="v text-success-emphasis">{{ money(totals.held) }}</div><div class="pa-sub">{{ totals.heldCount }} {{ totals.heldCount === 1 ? 'security' : 'securities' }}</div></div>
            <div><div class="pa-eyebrow">Returned</div><div class="v">{{ money(totals.returned) }}</div><div class="pa-sub">approved returns</div></div>
            <div><div class="pa-eyebrow">Forfeited</div><div class="v">{{ money(totals.forfeited) }}</div><div class="pa-sub">kept or encashed</div></div>
            <div><div class="pa-eyebrow">Awaiting approval</div><div class="v">{{ money(totals.pending) }}</div><div class="pa-sub">returns and forfeits</div></div>
        </div>
        <div class="pa-card-body border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="pa-seg">
                <button v-for="option in filters" :key="option.key" type="button" :class="{ on: filter === option.key }" @click="filter = option.key">{{ option.label }}</button>
            </div>
            <button v-if="can.security && !['completed', 'cancelled'].includes(contract.status)" class="btn btn-outline-primary btn-sm" @click="emit('open', 'security')"><i class="fa fa-plus me-1"></i> Add security</button>
        </div>

        <div v-if="!visible.length" class="pa-empty"><i class="fa fa-shield"></i>{{ contract.securities.length ? 'Nothing matches this filter.' : 'No security lodged on this contract yet.' }}</div>
        <div v-for="security in visible" :key="security.id" class="pa-sec">
            <span class="si" :class="{ cash: security.form === 'cash', muted: security.held <= 0 }"><i class="fa" :class="formIcon[security.form]"></i></span>
            <div style="min-width: 0">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="fw-semibold">{{ security.purpose_label }}</span>
                    <span class="pa-pill" :class="stateTone[security.state]">{{ stateLabel[security.state] }}</span>
                    <span v-if="security.expires_soon" class="pa-pill overdue no-dot"><i class="fa fa-exclamation-triangle"></i> {{ expiryText(security) }}</span>
                </div>
                <div class="facts">
                    <span><i class="fa fa-file-text-o me-1"></i>{{ security.form_label }}{{ security.bank_name ? ` · ${security.bank_name}` : '' }}</span>
                    <span v-if="security.reference_no"><i class="fa fa-tag me-1"></i>{{ security.reference_no }}</span>
                    <span><i class="fa me-1" :class="security.direction === 'received' ? 'fa-arrow-down' : 'fa-arrow-up'"></i>{{ security.direction_label }}</span>
                    <span v-if="security.valid_until"><i class="fa fa-calendar me-1"></i>{{ date(security.valid_from) }} – {{ date(security.valid_until) }}</span>
                    <span v-if="security.cheque_date"><i class="fa fa-calendar me-1"></i>Cheque dated {{ date(security.cheque_date) }}</span>
                    <span v-if="security.form === 'cash'"><i class="fa fa-calendar me-1"></i>{{ date(security.received_date) }} · {{ security.method }}</span>
                    <a v-if="security.document_url" :href="security.document_url" target="_blank" rel="noopener"><i class="fa fa-paperclip me-1"></i>Scan</a>
                </div>
                <div v-for="settlement in security.settlements" :key="settlement.id" class="pa-callout info mt-2 py-1">
                    <i class="fa mt-1" :class="settlement.type === 'return' ? 'fa-undo' : 'fa-ban'"></i>
                    <span class="flex-grow-1">
                        {{ settlement.type_label }} {{ money(settlement.amount) }} on {{ date(settlement.date) }}
                        <span class="pa-pill ms-1" :class="{ approved: 'paid', pending_approval: 'draft', rejected: 'overdue' }[settlement.status]">{{ { approved: 'Approved', pending_approval: 'Pending approval', rejected: 'Rejected' }[settlement.status] }}</span>
                        <span v-if="settlement.reason" class="d-block">{{ settlement.reason }}</span>
                    </span>
                    <span v-if="settlement.approval?.can_act" class="d-flex gap-1 align-self-center">
                        <button class="btn btn-quiet btn-sm" @click="emit('decide', 'settlement', settlement.id, 'reject')">Reject</button>
                        <button class="btn btn-success btn-sm" @click="emit('decide', 'settlement', settlement.id, 'approve')">Approve</button>
                    </span>
                </div>
            </div>
            <div class="amt">{{ money(security.amount) }}<div v-if="security.held !== security.amount" class="pa-sub fw-normal">{{ money(security.held) }} held</div></div>
            <div class="acts">
                <span class="pa-sub me-auto"><i class="fa fa-book me-1"></i>
                    <button v-if="security.journal_id" class="paf-link" type="button" @click="openJournal(security.journal_id)">Lodging journal</button>
                    <template v-else>Tracked only · no journal until encashed</template>
                </span>
                <template v-if="can.security && security.settleable > 0 && contract.status !== 'cancelled'">
                    <button v-if="security.form === 'bank_guarantee'" class="btn btn-quiet btn-sm" @click="emit('open', 'security', { renew: security })"><i class="fa fa-refresh me-1"></i> Record renewal</button>
                    <button class="btn btn-quiet btn-sm" @click="emit('open', 'settle', { security, type: 'return' })">Return</button>
                    <button class="btn btn-quiet btn-sm text-danger" @click="emit('open', 'settle', { security, type: 'forfeit' })">{{ security.form === 'cash' ? 'Forfeit' : 'Encash' }}</button>
                </template>
                <button v-if="can.security && !security.settlements.length" class="btn-icon danger" title="Delete security" @click="remove(security)"><i class="fa fa-trash-o"></i></button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { contractApi } from '../api.js'
import { confirmAction } from '../common/useContractContext.js'
import { date, daysFromToday, money } from '../format.js'

const props = defineProps({ contract: { type: Object, required: true }, can: { type: Object, required: true } })
const emit = defineEmits(['open', 'decide', 'run'])
const toast = useToast()

const formIcon = { bank_guarantee: 'fa-shield', cash: 'fa-money', cheque: 'fa-credit-card' }
const stateLabel = { held: 'Held', partially_settled: 'Partly settled', returned: 'Returned', forfeited: 'Forfeited' }
const stateTone = { held: 'active', partially_settled: 'partial', returned: 'completed', forfeited: 'overdue' }
const filters = [
    { key: 'all', label: 'All' },
    { key: 'held', label: 'Held' },
    { key: 'closed', label: 'Returned / forfeited' },
]
const filter = ref('all')

const visible = computed(() => props.contract.securities.filter((security) => {
    if (filter.value === 'held') return security.held > 0
    if (filter.value === 'closed') return security.held <= 0
    return true
}))

const totals = computed(() => {
    const sum = (list, pick) => list.reduce((total, item) => total + pick(item), 0)
    const settlements = props.contract.securities.flatMap((security) => security.settlements)
    return {
        held: sum(props.contract.securities, (security) => security.held),
        heldCount: props.contract.securities.filter((security) => security.held > 0).length,
        returned: sum(settlements.filter((item) => item.type === 'return' && item.status === 'approved'), (item) => item.amount),
        forfeited: sum(settlements.filter((item) => item.type === 'forfeit' && item.status === 'approved'), (item) => item.amount),
        pending: sum(settlements.filter((item) => item.status === 'pending_approval'), (item) => item.amount),
    }
})

function expiryText(security) {
    const days = daysFromToday(security.valid_until)
    if (days < 0) return `Expired ${-days} days ago`
    return days === 0 ? 'Expires today' : `Expires in ${days} days`
}

function openJournal(journalId) {
    if (window.openJournalModal) window.openJournalModal(journalId)
    else toast.info(`Journal #${journalId}`)
}

async function remove(security) {
    const ok = await confirmAction({ title: 'Delete this security?', text: security.journal_id ? 'Its lodging journal is removed too.' : 'It was entered by mistake and nothing was posted.', confirm: 'Delete', danger: true })
    if (ok) emit('run', contractApi.deleteSecurity(props.contract.id, security.id))
}
</script>
