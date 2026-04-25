<template>
    <div class="card">
        <div class="card-header bg-primary ">
            <h5 class="mb-0 text-white">
                <i class="fa fa-save me-2"></i>
                Save Stock Adjustment
            </h5>
        </div>
        <div class="card-body">
            <form @submit.prevent="handleSubmit">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Remarks</label>
                        <textarea class="form-control" rows="2" v-model="remarks"
                            placeholder="Enter remarks (optional)..." />
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-1">Total Items</h6>
                                <h4 class="mb-0 text-primary">{{ totalItems }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-1">Total Quantity</h6>
                                <h4 class="mb-0 text-success">{{ totalQuantity.toFixed(2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end align-items-center">
                    <button type="submit" class="btn btn-success btn-lg text-white"
                        :disabled="loading || totalItems === 0">
                        <i class="fa fa-save me-2"></i>
                        {{ loading ? 'Saving...' : 'Save Stock Adjustment' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
    items: {
        type: Array,
        default: () => []
    },
    branchId: {
        type: [String, Number],
        default: null
    },
    onSuccess: {
        type: Function,
        default: null
    },
    onError: {
        type: Function,
        default: null
    }
})

const emit = defineEmits(['success', 'error', 'loading'])

const loading = ref(false)
const remarks = ref('')

// Computed properties for summary
const totalItems = computed(() => {
    return props.items?.length || 0
})

const totalQuantity = computed(() => {
    return props.items?.reduce((sum, item) => {
        return sum + (parseFloat(item.adjusted_quantity) || 0)
    }, 0) || 0
})

const totalValue = computed(() => {
    return props.items?.reduce((sum, item) => {
        const qty = parseFloat(item.adjusted_quantity) || 0
        const cost = parseFloat(item.cost || item.product_cost) || 0
        return sum + (qty * cost)
    }, 0) || 0
})

// Handle form submission
const handleSubmit = async () => {
    if (!props.items || props.items.length === 0) {
        const errorMsg = 'Please select at least one product'
        if (props.onError) {
            props.onError(errorMsg)
        }
        emit('error', errorMsg)
        return
    }

    loading.value = true
    emit('loading', true)

    try {
        // Prepare data for stock adjustment
        const stockAdjustmentData = props.items.map(item => ({
            inventory_id: item.inventory_id,
            quantity: parseFloat(item.adjusted_quantity) || 0,
            remarks: remarks.value || 'Stock Adjustment Entry',
        }))

        const response = await axios.post('/inventory/stock-adjustment/save', {
            items: stockAdjustmentData,
            remarks: remarks.value,
        })

        if (response.data.success) {
            const successMsg = response.data.message || 'Stock adjustment saved successfully'
            if (props.onSuccess) {
                props.onSuccess(successMsg)
            }
            emit('success', successMsg)

            // Optionally reload the page
            setTimeout(() => {
                window.location.reload()
            }, 1500)
        } else {
            throw new Error(response.data.message || 'Failed to save stock adjustment')
        }
    } catch (error) {
        console.error('Error saving stock adjustment:', error)
        const errorMsg = error.response?.data?.message || error.message || 'Failed to save stock adjustment'
        if (props.onError) {
            props.onError(errorMsg)
        }
        emit('error', errorMsg)
    } finally {
        loading.value = false
        emit('loading', false)
    }
}
</script>

<style scoped>
/* Add any component-specific styles here if needed */
</style>
