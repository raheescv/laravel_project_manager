<template>
    <PcModal :open="open && !!subject" :title="title" :subtitle="subtitle" icon="fa-gavel" @close="emit('close')">
        <form v-if="subject" id="pc-decision-form" class="d-grid gap-3" @submit.prevent="save">
            <div class="p-3 rounded-3" style="background: var(--pa-head); border: 1px solid var(--pa-line)">
                <dl class="mb-0 small d-grid gap-1" style="grid-template-columns: auto 1fr">
                    <template v-for="row in details" :key="row[0]">
                        <dt class="text-muted-pa fw-normal">{{ row[0] }}</dt><dd class="mb-0 text-end fw-semibold">{{ row[1] }}</dd>
                    </template>
                </dl>
            </div>
            <div class="pa-choice">
                <input id="d-approve" v-model="decision" type="radio" name="decision" value="approve" />
                <label for="d-approve"><i class="fa fa-check"></i> Approve</label>
                <input id="d-reject" v-model="decision" type="radio" name="decision" value="reject" class="danger" />
                <label for="d-reject"><i class="fa fa-times"></i> Reject</label>
            </div>
            <div>
                <label class="form-label" for="d-comment">Comment<span v-if="decision === 'reject'"> *</span></label>
                <textarea id="d-comment" v-model="comment" class="form-control" rows="3" :placeholder="decision === 'reject' ? 'Tell the submitter what to change' : 'Optional for approval'" :required="decision === 'reject'"></textarea>
                <div class="form-text">{{ help }}</div>
            </div>
        </form>
        <template #footer>
            <button class="btn btn-quiet" type="button" @click="emit('close')">Cancel</button>
            <button class="btn" :class="decision === 'reject' ? 'btn-danger' : 'btn-success'" type="submit" form="pc-decision-form" :disabled="saving">
                <i class="fa" :class="saving ? 'fa-spinner fa-spin' : decision === 'reject' ? 'fa-times' : 'fa-check'"></i> {{ decision === 'reject' ? 'Reject' : 'Approve' }}
            </button>
        </template>
    </PcModal>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { contractApi } from '../api.js'
import PcModal from '../common/PcModal.vue'
import { date, money } from '../format.js'

const props = defineProps({ open: Boolean, contract: { type: Object, required: true }, subject: { type: Object, default: null } })
const emit = defineEmits(['close', 'run'])

const decision = ref('approve')
const comment = ref('')
const saving = ref(false)

watch(() => props.open, (isOpen) => {
    if (!isOpen || !props.subject) return
    saving.value = false
    decision.value = props.subject.decision ?? 'approve'
    comment.value = ''
})

const item = computed(() => {
    if (!props.subject) return null
    if (props.subject.subject === 'extension') return props.contract.extensions.find((extension) => extension.id === props.subject.id)
    if (props.subject.subject === 'settlement') {
        for (const security of props.contract.securities) {
            const settlement = security.settlements.find((entry) => entry.id === props.subject.id)
            if (settlement) return { ...settlement, security }
        }
    }
    return props.contract
})

const approval = computed(() => item.value?.approval ?? null)
const level = computed(() => approval.value?.levels.find((entry) => entry.level === approval.value.current_level))
const isLast = computed(() => approval.value && approval.value.current_level === approval.value.level_count)
const nouns = { contract: 'contract', extension: 'extension', settlement: 'security settlement' }
const title = computed(() => `Review ${nouns[props.subject?.subject] ?? 'item'}`)
const subtitle = computed(() => (level.value ? `Level ${level.value.level} · ${level.value.role} · ${props.contract.project_name}` : props.contract.project_name))

const details = computed(() => {
    const value = item.value
    if (!value) return []
    if (props.subject.subject === 'extension') {
        return [
            ['End date', `${date(value.old_end_date)} → ${date(value.new_end_date)}`],
            ['Amount', `${money(value.old_amount)} → ${money(value.new_amount)}`],
            ['Planned terms', `${value.old_no_of_terms} → ${value.new_no_of_terms}`],
            ['Reason', value.reason],
        ]
    }
    if (props.subject.subject === 'settlement') {
        return [
            ['Security', `${value.security.purpose_label} · ${value.security.form_label}`],
            ['Action', `${value.type_label} on ${date(value.date)}`],
            ['Amount', `QAR ${money(value.amount)}`],
            ['Reason', value.reason || '—'],
        ]
    }
    return [
        ['Vendor', props.contract.vendor?.name ?? '—'],
        ['Period', `${date(props.contract.start_date)} – ${date(props.contract.end_date)}`],
        ['Amount', `QAR ${money(props.contract.contract_amount)}`],
        ['Terms', `${props.contract.no_of_terms} · ${props.contract.payment_frequency_label}`],
        ...(approval.value?.levels.filter((entry) => entry.status === 'approved').map((entry) => [`Level ${entry.level}`, `Approved by ${entry.acted_by}`]) ?? []),
    ]
})

const help = computed(() => {
    if (decision.value === 'reject') return props.subject?.subject === 'contract' ? 'The contract goes back to the submitter with your reason.' : 'The request is closed with your reason; nothing changes.'
    return isLast.value ? 'This is the last level. Approving puts it into effect.' : 'The next level is asked after you.'
})

function save() {
    saving.value = true
    emit('run', contractApi.decide({ subject: props.subject.subject, subject_id: props.subject.id, decision: decision.value, comment: comment.value || null }), () => { saving.value = false })
}
</script>
