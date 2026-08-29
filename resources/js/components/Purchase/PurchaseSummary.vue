<template>
    <div class="pcx-card">
        <div class="pcx-chd">
            <div class="pcx-ci"><i class="fa fa-calculator"></i></div>
            <h2 class="pcx-ct">Summary</h2>
        </div>

        <div class="pcx-sum">
            <div class="pcx-sr">
                <span class="k">Gross Total</span>
                <span class="v">{{ formatNumber(grossAmount) }}</span>
            </div>
            <div class="pcx-sr">
                <span class="k">Item Discount</span>
                <span class="v" :class="{ minus: itemDiscount > 0 }">{{ itemDiscount > 0 ? '-' : '' }}{{
                    formatNumber(itemDiscount) }}</span>
            </div>
            <div class="pcx-sr">
                <span class="k">Tax</span>
                <span class="v">{{ formatNumber(taxAmount) }}</span>
            </div>
            <div class="pcx-sr">
                <span class="k">Purchase Total</span>
                <span class="v">{{ formatNumber(total) }}</span>
            </div>
            <div class="pcx-sr">
                <span class="k">Other Discount</span>
                <input type="number" class="pcx-cell" :value="otherDiscount"
                    @input="handleInput('other_discount', $event.target.value)" step="any" />
            </div>
            <div class="pcx-sr">
                <span class="k">Freight Charges</span>
                <input type="number" class="pcx-cell" :value="freight"
                    @input="handleInput('freight', $event.target.value)" step="any" />
            </div>
        </div>

        <div class="pcx-grand">
            <div>
                <div class="k">Grand Total</div>
                <div class="v">{{ formatCurrency(grandTotal) }}</div>
            </div>
            <i class="fa fa-shopping-cart"></i>
        </div>
    </div>
</template>

<script setup>
import { useLivewire } from '@/composables/useLivewire'
import { formatCurrency, formatNumber } from '@/utils/number'

const props = defineProps({
    grossAmount: {
        type: Number,
        default: 0
    },
    total: {
        type: Number,
        default: 0
    },
    otherDiscount: {
        type: Number,
        default: 0
    },
    freight: {
        type: Number,
        default: 0
    },
    grandTotal: {
        type: Number,
        default: 0
    },
    itemDiscount: {
        type: Number,
        default: 0
    },
    taxAmount: {
        type: Number,
        default: 0
    }
})

const { set } = useLivewire()

const handleInput = (field, value) => {
    let numValue = parseFloat(value) || 0

    // Handle percentage for other_discount
    if (field === 'other_discount' && typeof value === 'string' && value.endsWith('%')) {
        const percentage = parseFloat(value.replace('%', '')) || 0
        numValue = Math.round((props.total / 100) * percentage * 100) / 100
        if (numValue > props.total) {
            numValue = percentage
        }
    }

    set(`purchases.${field}`, numValue)
}
</script>
