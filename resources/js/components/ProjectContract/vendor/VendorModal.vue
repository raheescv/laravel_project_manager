<template>
    <PcModal :open="open" :title="vendorId ? 'Edit vendor' : 'New vendor'" :subtitle="subtitle" icon="fa-truck" size="xl" @close="emit('close')">
        <div v-if="loading" class="pa-skeleton" style="height: 260px"></div>
        <VendorFields v-else :key="formKey" :model="model" :errors="errors" :framed="false" :collapsible="!expanded" id-prefix="vm-" />
        <template #footer>
            <button class="btn btn-quiet" type="button" @click="emit('close')">Cancel</button>
            <button class="btn btn-primary" type="button" :disabled="saving || loading" @click="save">
                <i class="fa" :class="saving ? 'fa-spinner fa-spin' : 'fa-check'"></i> {{ vendorId ? 'Save changes' : saveLabel }}
            </button>
        </template>
    </PcModal>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { contractApi, errorMessage, fieldErrors } from '../api.js'
import PcModal from '../common/PcModal.vue'
import VendorFields from './VendorFields.vue'
import { blankVendor, vendorPayload } from './vendorModel.js'

const props = defineProps({
    open: { type: Boolean, default: false },
    vendorId: { type: Number, default: null },
    saveLabel: { type: String, default: 'Save vendor' },
    subtitle: { type: String, default: '' },
})
const emit = defineEmits(['close', 'saved'])
const toast = useToast()

const model = reactive(blankVendor())
const errors = ref({})
const saving = ref(false)
const loading = ref(false)
const formKey = ref(0)

/** Personal details start open when an edited vendor already has some. */
const expanded = computed(() => !!props.vendorId && ['id_no', 'id_expiry_date', 'passport_no', 'occupation', 'nationality', 'company'].some((key) => model[key]))

watch(() => props.open, async (isOpen) => {
    if (!isOpen) return
    Object.assign(model, blankVendor())
    errors.value = {}
    saving.value = false
    formKey.value++
    if (!props.vendorId) return
    loading.value = true
    try {
        const response = await contractApi.vendor(props.vendorId)
        Object.entries(response.data).forEach(([key, value]) => {
            if (key in model) model[key] = value ?? ''
        })
        formKey.value++
    } catch (error) {
        toast.error(errorMessage(error))
        emit('close')
    } finally {
        loading.value = false
    }
})

async function save() {
    saving.value = true
    errors.value = {}
    try {
        const payload = vendorPayload(model)
        const response = props.vendorId ? await contractApi.updateVendor(props.vendorId, payload) : await contractApi.storeVendor(payload)
        toast.success(response.message)
        emit('saved', { id: props.vendorId ?? response.data.id, name: model.name, mobile: model.mobile })
    } catch (error) {
        errors.value = fieldErrors(error)
        toast.error(errorMessage(error))
    } finally {
        saving.value = false
    }
}
</script>
