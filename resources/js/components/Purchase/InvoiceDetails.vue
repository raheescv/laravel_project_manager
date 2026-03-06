<template>
    <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-gradient bg-primary text-white py-2">
            <h5 class="card-title mb-0 text-white">
                <i class="fa fa-file-text me-2"></i>Invoice Details
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-12">
                    <label for="invoice_no" class="form-label">
                        Invoice No <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="invoice_no" class="form-control" :value="invoiceNo"
                        @input="handleInput('invoice_no', $event.target.value)" placeholder="Enter Invoice No"
                        required />
                </div>
                <div class="col-md-6">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" id="date" class="form-control" :value="date"
                        @input="handleInput('date', $event.target.value)" />
                </div>
                <div class="col-md-6">
                    <label for="delivery_date" class="form-label">Delivery Date</label>
                    <input type="date" id="delivery_date" class="form-control" :value="deliveryDate"
                        @input="handleInput('delivery_date', $event.target.value)" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useLivewire } from '@/composables/useLivewire'

const props = defineProps({
    invoiceNo: {
        type: String,
        default: ''
    },
    date: {
        type: String,
        default: ''
    },
    deliveryDate: {
        type: String,
        default: ''
    }
})

const { set } = useLivewire()

const handleInput = (field, value) => {
    set(`purchases.${field}`, value)
}
</script>
