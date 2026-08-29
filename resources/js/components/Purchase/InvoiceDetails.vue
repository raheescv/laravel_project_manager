<template>
    <div class="pcx-f">
        <label for="invoice_no">Invoice No <i class="req">*</i></label>
        <input type="text" id="invoice_no" class="pcx-input" :value="invoiceNo"
            @input="handleInput('invoice_no', $event.target.value)" placeholder="Enter Invoice No" required />
    </div>
    <div class="pcx-f">
        <label for="date">Invoice Date</label>
        <input type="date" id="date" class="pcx-input" :value="date"
            @input="handleInput('date', $event.target.value)" />
    </div>
    <div class="pcx-f">
        <label for="delivery_date">Delivery Date</label>
        <input type="date" id="delivery_date" class="pcx-input" :value="deliveryDate"
            @input="handleInput('delivery_date', $event.target.value)" />
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
