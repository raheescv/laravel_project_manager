<template>
    <div class="d-flex flex-column gap-3">
        <!-- Grouped Items Cards -->
        <div>
            <div v-for="group in groupedItems" :key="group.categoryId" 
                class="card mb-3 shadow-sm border-0">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold text-dark mb-1">{{ group.categoryName }}</h6>
                            <p class="small text-muted mb-0">
                                <span class="fw-bold text-dark">{{ group.quantity }}</span> {{ group.quantity === 1 ? 'Item' : 'Items' }}
                            </p>
                        </div>
                        <div class="text-end">
                            <p class="h6 fw-bold text-primary mb-0">{{ formatCurrency(group.total) }}</p>
                            <p class="small text-muted mb-0">Total Amount</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="groupedItems.length === 0" class="text-center py-4 bg-light rounded border border-dashed text-muted">
                <p class="mb-0">No items added yet</p>
            </div>
        </div>

        <!-- Grand Total -->
        <div v-if="groupedItems.length > 0" class="alert alert-primary d-flex justify-content-between align-items-center mb-0 border-0 shadow-sm">
            <span class="fw-semibold">Net Total</span>
            <span class="fw-bold h4 mb-0">{{ formatCurrency(subTotal) }}</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    items: {
        type: Array,
        default: () => []
    }
})

const groupedItems = computed(() => {
    const groups = {}
    
    props.items.forEach(item => {
        const catId = item.tailoring_category_id
        if (!groups[catId]) {
            groups[catId] = {
                categoryId: catId,
                categoryName: item.category?.name || 'Unknown Category',
                quantity: 0,
                total: 0
            }
        }
        
        groups[catId].quantity += parseFloat(item.quantity || 0)
        groups[catId].total += parseFloat(item.total || 0)
    })
    
    return Object.values(groups)
})

const subTotal = computed(() => {
    return props.items.reduce((sum, item) => sum + (parseFloat(item.total) || 0), 0)
})

const formatCurrency = (value) => {
    return parseFloat(value || 0).toFixed(2)
}
</script>
