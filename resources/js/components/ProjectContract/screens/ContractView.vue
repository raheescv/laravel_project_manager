<template>
    <div>
        <div v-if="!contract" class="d-grid gap-3">
            <div class="pa-skeleton" style="height: 56px"></div>
            <div class="row g-3"><div v-for="n in 3" :key="n" class="col-12 col-md-6 col-xl-4"><div class="pa-skeleton" style="height: 230px"></div></div></div>
            <div class="pa-skeleton" style="height: 260px"></div>
        </div>

        <template v-else>
            <div class="pa-pagehead">
                <div class="me-auto" style="min-width: 0">
                    <div class="pa-crumbs"><a :href="urlFor(context.urls, 'list')">Project Contracts</a> <i class="fa fa-angle-right mx-1"></i> {{ contract.reference_no }}</div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h1 class="pa-title">{{ contract.project_name }}</h1>
                        <span class="pa-pill" :class="statusTone[contract.status]">{{ contract.status_label }}<template v-if="contract.approval && contract.status === 'pending_approval'"> · level {{ contract.approval.current_level }} of {{ contract.approval.level_count }}</template></span>
                        <span v-if="contract.extensions.some((item) => item.status === 'approved')" class="pa-pill info no-dot"><i class="fa fa-history"></i> Extended</span>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 no-print">
                    <template v-if="isActive">
                        <button v-if="can.extend" class="btn btn-quiet" @click="openModal('extend')"><i class="fa fa-history me-1"></i> Extend</button>
                        <button v-if="can.term" class="btn btn-quiet" @click="openModal('term')"><i class="fa fa-plus me-1"></i> Add term</button>
                        <button v-if="can.payment" class="btn btn-primary" :disabled="!unpaidTerms.length" @click="openModal('pay', { termIds: unpaidTerms.slice(0, 1).map((term) => term.id) })"><i class="fa fa-money me-1"></i> Record payment</button>
                    </template>
                    <template v-if="contract.approval?.can_act">
                        <button class="btn btn-quiet" @click="openDecision('contract', contract.id, 'reject')"><i class="fa fa-times me-1"></i> Reject</button>
                        <button class="btn btn-success" @click="openDecision('contract', contract.id, 'approve')"><i class="fa fa-check me-1"></i> Approve</button>
                    </template>
                    <button v-if="editableStatus && can.edit" class="btn btn-primary" :disabled="busy" @click="submitForApproval"><i class="fa fa-paper-plane me-1"></i> Submit for approval</button>
                    <div class="dropdown">
                        <button class="btn btn-quiet px-2" type="button" aria-label="More actions" @click="menu = !menu"><i class="fa fa-ellipsis-v"></i></button>
                        <ul v-if="menu" class="dropdown-menu dropdown-menu-end show shadow-sm" style="right: 0; left: auto" @click="menu = false">
                            <li v-if="can.edit && !['completed', 'cancelled'].includes(contract.status)"><a class="dropdown-item" :href="urlFor(context.urls, 'edit', contract.id)"><i class="fa fa-pencil fa-fw me-1"></i> Edit contract</a></li>
                            <li><button class="dropdown-item" type="button" @click="printPage"><i class="fa fa-print fa-fw me-1"></i> Print statement</button></li>
                            <li v-if="isActive && can.edit"><button class="dropdown-item" type="button" @click="changeStatus('completed')"><i class="fa fa-check-circle fa-fw me-1"></i> Mark completed</button></li>
                            <li v-if="can.edit && !['completed', 'cancelled'].includes(contract.status)"><button class="dropdown-item text-danger" type="button" @click="changeStatus('cancelled')"><i class="fa fa-ban fa-fw me-1"></i> Cancel contract</button></li>
                            <li v-if="can.delete && editableStatus"><hr class="dropdown-divider" /></li>
                            <li v-if="can.delete && editableStatus"><button class="dropdown-item text-danger" type="button" @click="destroy"><i class="fa fa-trash-o fa-fw me-1"></i> Delete draft</button></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-3">
                <div v-if="contract.approval?.can_act" class="pa-banner">
                    <span class="bi"><i class="fa fa-gavel"></i></span>
                    <div class="me-auto" style="min-width: 0">
                        <div class="fw-semibold">Waiting for your approval</div>
                        <div class="pa-sub">Level {{ contract.approval.current_level }} of {{ contract.approval.level_count }} · {{ currentRole }}. Terms and payments unlock once every level approves.</div>
                    </div>
                    <button class="btn btn-quiet" @click="openDecision('contract', contract.id, 'reject')">Reject</button>
                    <button class="btn btn-success" @click="openDecision('contract', contract.id, 'approve')"><i class="fa fa-check me-1"></i> Approve</button>
                </div>
                <div v-else-if="contract.status === 'pending_approval' && contract.approval" class="pa-callout info">
                    <i class="fa fa-clock-o mt-1"></i>
                    <span>Waiting for level {{ contract.approval.current_level }} of {{ contract.approval.level_count }} · <b>{{ currentRole }}</b>. {{ contract.approval.refusal ?? '' }} Anyone with the project contract “approve” permission can approve it here.</span>
                </div>
                <div v-else-if="contract.status === 'rejected'" class="pa-callout bad"><i class="fa fa-times-circle mt-1"></i><span>Rejected<template v-if="rejection"> by {{ rejection.acted_by }}: “{{ rejection.comment }}”</template>. Edit the contract and submit it again.</span></div>
                <div v-else-if="contract.status === 'draft'" class="pa-callout info"><i class="fa fa-info-circle mt-1"></i><span>Draft. Submit it for approval to start adding terms and payments.</span></div>

                <ContractCards :contract="contract" :can-edit="can.edit && !['completed', 'cancelled'].includes(contract.status)" />

                <div class="pvx-notes">
                    <div v-if="contract.remark" class="pvx-remarks">
                        <span class="lbl"><i class="fa fa-comment-o"></i> Remarks</span>
                        <p>{{ contract.remark }}</p>
                        <a v-if="can.edit" class="btn-icon no-print" :href="urlFor(context.urls, 'edit', contract.id)" title="Edit remarks"><i class="fa fa-pencil"></i></a>
                    </div>
                    <div v-for="security in expiringSecurities" :key="security.id" class="pvx-warn">
                        <i class="fa fa-exclamation-triangle"></i>
                        <span>{{ security.purpose_label }} {{ security.reference_no ? `(${security.reference_no})` : '' }} expires {{ date(security.valid_until) }}{{ security.valid_until < contract.end_date ? ', before the contract ends' : '' }}. Ask the vendor to renew it.</span>
                        <button v-if="can.security" class="pvx-warn-btn no-print" @click="openModal('security', { renew: security })">Record renewal</button>
                    </div>
                </div>

                <div class="pa-card" style="overflow: hidden">
                    <div class="pvx-manage-head"><i class="fa fa-folder-open"></i><h5>Management Sections</h5></div>
                    <div class="pa-tabs" role="tablist">
                        <button v-for="tab in tabs" :key="tab.key" type="button" role="tab" :class="{ on: activeTab === tab.key }" :aria-selected="activeTab === tab.key" @click="activeTab = tab.key">
                            {{ tab.label }}<span class="count">{{ tab.count }}</span>
                        </button>
                    </div>
                    <TermsTab v-if="activeTab === 'terms'" :contract="contract" :can="can" @open="openModal" @run="run" />
                    <TransactionsTab v-else-if="activeTab === 'transactions'" :contract="contract" :can="can" @run="run" />
                    <SecurityTab v-else-if="activeTab === 'security'" :contract="contract" :can="can" @open="openModal" @decide="openDecision" @run="run" />
                    <ExtensionsTab v-else-if="activeTab === 'extensions'" :contract="contract" :can="can" @open="openModal" @decide="openDecision" />
                    <DocumentsTab v-else-if="activeTab === 'documents'" :contract="contract" :can="can" @run="run" />
                    <NotesTab v-else :contract="contract" :can="can" @run="run" />
                </div>
            </div>

            <TermModal :open="modal.name === 'term'" :contract="contract" :term="modal.payload?.term" @close="closeModal" @run="runAndClose" />
            <PayModal :open="modal.name === 'pay'" :contract="contract" :term-ids="modal.payload?.termIds ?? []" @close="closeModal" @run="runAndClose" />
            <ExtendModal :open="modal.name === 'extend'" :contract="contract" @close="closeModal" @run="runAndClose" />
            <SecurityModal :open="modal.name === 'security'" :contract="contract" :renew="modal.payload?.renew" @close="closeModal" @run="runAndClose" />
            <SettleModal :open="modal.name === 'settle'" :contract="contract" :security="modal.payload?.security" :type="modal.payload?.type" @close="closeModal" @run="runAndClose" />
            <DecisionModal :open="modal.name === 'decision'" :contract="contract" :subject="modal.payload" @close="closeModal" @run="runAndClose" />
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { contractApi, errorMessage } from '../api.js'
import { confirmAction, loadOptions, urlFor, useContext } from '../common/useContractContext.js'
import { date, statusTone } from '../format.js'
import DecisionModal from '../modals/DecisionModal.vue'
import ExtendModal from '../modals/ExtendModal.vue'
import PayModal from '../modals/PayModal.vue'
import SecurityModal from '../modals/SecurityModal.vue'
import SettleModal from '../modals/SettleModal.vue'
import TermModal from '../modals/TermModal.vue'
import DocumentsTab from '../tabs/DocumentsTab.vue'
import ExtensionsTab from '../tabs/ExtensionsTab.vue'
import NotesTab from '../tabs/NotesTab.vue'
import SecurityTab from '../tabs/SecurityTab.vue'
import TermsTab from '../tabs/TermsTab.vue'
import TransactionsTab from '../tabs/TransactionsTab.vue'
import ContractCards from '../view/ContractCards.vue'

const props = defineProps({ recordId: { type: Number, required: true } })
const context = useContext()
const can = context.permissions
const toast = useToast()

const contract = ref(null)
const busy = ref(false)
const menu = ref(false)
const activeTab = ref(new URLSearchParams(window.location.search).get('tab') ?? 'terms')
const modal = reactive({ name: null, payload: null })

const isActive = computed(() => contract.value?.status === 'active')
const editableStatus = computed(() => ['draft', 'rejected'].includes(contract.value?.status))
const unpaidTerms = computed(() => (contract.value?.terms ?? []).filter((term) => term.balance > 0))
const expiringSecurities = computed(() => (contract.value?.securities ?? []).filter((security) => security.expires_soon && security.held > 0))
const currentRole = computed(() => contract.value?.approval?.levels.find((level) => level.level === contract.value.approval.current_level)?.role ?? '')
const rejection = computed(() => contract.value?.approval?.levels.find((level) => level.status === 'rejected'))

const tabs = computed(() => [
    { key: 'terms', label: 'Payment terms', count: contract.value.terms.length },
    { key: 'transactions', label: 'Transactions', count: contract.value.payments.length },
    { key: 'security', label: 'Security', count: contract.value.securities.length },
    { key: 'extensions', label: 'Extensions', count: contract.value.extensions.length },
    { key: 'documents', label: 'Documents', count: contract.value.documents.length },
    { key: 'notes', label: 'Notes', count: contract.value.notes.length },
])

async function load() {
    try {
        const response = await contractApi.show(props.recordId)
        contract.value = response.data
    } catch (error) {
        toast.error(errorMessage(error))
    }
}

/** Run a write; the server answers with the fresh contract, which redraws the page. */
async function run(promise) {
    try {
        const response = await promise
        if (response.data && response.data.id) contract.value = response.data
        toast.success(response.message)
        ;(response.data?.warnings ?? []).forEach((warning) => toast.warning(warning, { timeout: 7000 }))
        return true
    } catch (error) {
        toast.error(errorMessage(error))
        return false
    }
}

async function runAndClose(promise, done) {
    const ok = await run(promise)
    if (ok) closeModal()
    done?.(ok)
}

function openModal(name, payload = null) {
    modal.name = name
    modal.payload = payload
}

function closeModal() {
    modal.name = null
    modal.payload = null
}

function openDecision(subject, id, decision, summary = null) {
    openModal('decision', { subject, id, decision, summary })
}

async function submitForApproval() {
    busy.value = true
    await run(contractApi.submit(contract.value.id))
    busy.value = false
}

async function changeStatus(status) {
    const completing = status === 'completed'
    const ok = await confirmAction({
        title: completing ? 'Mark this contract completed?' : 'Cancel this contract?',
        text: completing ? 'No more terms or payments can be added afterwards.' : 'A cancelled contract cannot be reopened.',
        confirm: completing ? 'Mark completed' : 'Cancel contract',
        danger: !completing,
    })
    if (ok) await run(contractApi.status(contract.value.id, status))
}

async function destroy() {
    const ok = await confirmAction({ title: 'Delete this draft?', text: 'The contract and its approval history are removed.', confirm: 'Delete', danger: true })
    if (!ok) return
    try {
        await contractApi.destroy(contract.value.id)
        window.location.href = urlFor(context.urls, 'list')
    } catch (error) {
        toast.error(errorMessage(error))
    }
}

function printPage() {
    window.open(urlFor(context.urls, 'statement', contract.value.id), '_blank', 'noopener')
}

onMounted(() => {
    loadOptions()
    load()
    document.addEventListener('click', (event) => {
        if (!event.target.closest('.dropdown')) menu.value = false
    })
})
</script>
