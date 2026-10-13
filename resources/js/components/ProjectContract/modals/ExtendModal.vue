<template>
    <PcModal :open="open" title="Extend contract" subtitle="Changes apply once every approval level agrees" icon="fa-history" @close="emit('close')">
        <form id="pc-extend-form" class="row g-2" @submit.prevent="save">
            <div class="col-6">
                <label class="form-label" for="e-new">New end date</label>
                <input id="e-new" v-model="form.new_end_date" type="date" class="form-control" :min="contract.end_date" required />
                <div class="form-text">Currently {{ date(contract.end_date) }}</div>
            </div>
            <div class="col-6">
                <label class="form-label" for="e-amount">Additional amount</label>
                <div class="input-group"><span class="input-group-text">+</span><input id="e-amount" v-model="form.additional_amount" class="form-control text-end pa-num" inputmode="decimal" /></div>
                <div class="form-text pa-num">{{ whole(contract.contract_amount) }} → {{ whole(contract.contract_amount + parseAmount(form.additional_amount)) }}</div>
            </div>
            <div class="col-6">
                <label class="form-label" for="e-terms">Additional terms</label>
                <input id="e-terms" v-model.number="form.additional_terms" type="number" min="0" class="form-control" />
                <div class="form-text">{{ contract.no_of_terms }} → {{ contract.no_of_terms + (Number(form.additional_terms) || 0) }} planned</div>
            </div>
            <div class="col-6">
                <label class="form-label" for="e-file">Addendum</label>
                <input id="e-file" type="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" @change="file = $event.target.files[0] ?? null" />
            </div>
            <div class="col-12">
                <label class="form-label" for="e-reason">Reason *</label>
                <textarea id="e-reason" v-model="form.reason" class="form-control" rows="2" placeholder="What changed and who approved it" required></textarea>
            </div>
        </form>
        <template #footer>
            <button class="btn btn-quiet" type="button" @click="emit('close')">Cancel</button>
            <button class="btn btn-primary" type="submit" form="pc-extend-form" :disabled="saving"><i class="fa" :class="saving ? 'fa-spinner fa-spin' : 'fa-check'"></i> Extend contract</button>
        </template>
    </PcModal>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'
import { contractApi, formData } from '../api.js'
import PcModal from '../common/PcModal.vue'
import { date, parseAmount, whole } from '../format.js'

const props = defineProps({ open: Boolean, contract: { type: Object, required: true } })
const emit = defineEmits(['close', 'run'])

const form = reactive({ new_end_date: '', additional_amount: '', additional_terms: 0, reason: '' })
const file = ref(null)
const saving = ref(false)

watch(() => props.open, (isOpen) => {
    if (!isOpen) return
    saving.value = false
    file.value = null
    Object.assign(form, { new_end_date: props.contract.end_date, additional_amount: '', additional_terms: 0, reason: '' })
})

function save() {
    saving.value = true
    const body = formData({
        new_end_date: form.new_end_date,
        additional_amount: parseAmount(form.additional_amount),
        additional_terms: Number(form.additional_terms) || 0,
        reason: form.reason,
    }, { document: file.value })
    emit('run', contractApi.extend(props.contract.id, body), () => { saving.value = false })
}
</script>
