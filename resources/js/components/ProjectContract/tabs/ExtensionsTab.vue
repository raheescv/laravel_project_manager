<template>
    <div class="pa-card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="pa-sub">Every change to the period, amount or planned terms is kept here.</div>
            <button v-if="can.extend && contract.status === 'active'" class="btn btn-outline-primary btn-sm" :disabled="hasPending" :title="hasPending ? 'An extension is already waiting for approval' : ''" @click="emit('open', 'extend')"><i class="fa fa-history me-1"></i> Extend</button>
        </div>
        <div class="pa-timeline">
            <div v-for="(extension, index) in contract.extensions" :key="extension.id" class="pa-tl-item">
                <span class="pa-tl-dot" :class="{ accent: extension.status !== 'rejected' }"><i class="fa" :class="{ approved: 'fa-history', pending_approval: 'fa-clock-o', rejected: 'fa-times' }[extension.status]"></i></span>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="fw-semibold">Extension {{ contract.extensions.length - index }}</span>
                    <span class="pa-pill" :class="{ approved: 'paid', pending_approval: 'draft', rejected: 'overdue' }[extension.status]">{{ statusLabel(extension) }}</span>
                    <span class="pa-sub">{{ date(extension.created_at) }} · {{ extension.created_by }}</span>
                </div>
                <div class="mt-1" style="white-space: pre-line">{{ extension.reason }}</div>
                <div class="pa-diff">
                    <span v-if="extension.new_end_date !== extension.old_end_date">End date <s>{{ date(extension.old_end_date) }}</s><b>{{ date(extension.new_end_date) }}</b></span>
                    <span v-if="extension.new_amount !== extension.old_amount">Amount <s>{{ whole(extension.old_amount) }}</s><b>{{ whole(extension.new_amount) }}</b></span>
                    <span v-if="extension.new_no_of_terms !== extension.old_no_of_terms">Planned terms <s>{{ extension.old_no_of_terms }}</s><b>{{ extension.new_no_of_terms }}</b></span>
                </div>
                <a v-if="extension.document_url" :href="extension.document_url" target="_blank" rel="noopener" class="d-inline-block mt-2 pa-sub"><i class="fa fa-paperclip me-1"></i>Addendum</a>
                <div v-if="extension.status === 'pending_approval'" class="d-flex flex-wrap align-items-center gap-2 mt-2">
                    <span class="pa-sub"><i class="fa fa-info-circle me-1"></i>Changes take effect after every level approves.</span>
                    <template v-if="extension.approval?.can_act">
                        <button class="btn btn-quiet btn-sm ms-auto" @click="emit('decide', 'extension', extension.id, 'reject')">Reject</button>
                        <button class="btn btn-success btn-sm" @click="emit('decide', 'extension', extension.id, 'approve')">Approve</button>
                    </template>
                </div>
            </div>
            <div class="pa-tl-item">
                <span class="pa-tl-dot"><i class="fa fa-check"></i></span>
                <div class="d-flex flex-wrap align-items-center gap-2"><span class="fw-semibold">Contract created</span><span class="pa-sub">{{ date(contract.created_at) }} · {{ contract.created_by }}</span></div>
                <div class="pa-sub mt-1 pa-num">{{ date(contract.start_date) }} – {{ date(originalEnd) }} · QAR {{ whole(originalAmount) }} · {{ originalTerms }} planned terms</div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { date, whole } from '../format.js'

const props = defineProps({ contract: { type: Object, required: true }, can: { type: Object, required: true } })
const emit = defineEmits(['open', 'decide'])

const approved = computed(() => props.contract.extensions.filter((item) => item.status === 'approved'))
const first = computed(() => approved.value[approved.value.length - 1])
const originalEnd = computed(() => first.value?.old_end_date ?? props.contract.end_date)
const originalAmount = computed(() => first.value?.old_amount ?? props.contract.contract_amount)
const originalTerms = computed(() => first.value?.old_no_of_terms ?? props.contract.no_of_terms)
const hasPending = computed(() => props.contract.extensions.some((item) => item.status === 'pending_approval'))

function statusLabel(extension) {
    if (extension.status === 'pending_approval' && extension.approval) return `Pending approval · level ${extension.approval.current_level} of ${extension.approval.level_count}`
    return { approved: 'Approved', pending_approval: 'Pending approval', rejected: 'Rejected' }[extension.status]
}
</script>
