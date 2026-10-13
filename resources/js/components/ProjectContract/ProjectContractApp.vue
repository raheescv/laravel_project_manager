<template>
    <div class="pax">
        <component :is="screen" :record-id="recordId" :report="dataset.screen === 'report-paid' ? 'paid' : 'pending'" />
    </div>
</template>

<script setup>
import { computed, provide } from 'vue'
import { CONTEXT_KEY } from './common/useContractContext.js'
import ContractForm from './screens/ContractForm.vue'
import ContractList from './screens/ContractList.vue'
import ContractView from './screens/ContractView.vue'
import PaymentReports from './screens/PaymentReports.vue'
import VendorList from './screens/VendorList.vue'

const element = document.getElementById('project-contract-app')
const dataset = element?.dataset ?? {}
const parse = (value, fallback) => {
    try {
        return JSON.parse(value)
    } catch {
        return fallback
    }
}

const context = {
    permissions: parse(dataset.permissions, {}),
    urls: parse(dataset.urls, {}),
    userId: Number(dataset.userId),
}
provide(CONTEXT_KEY, context)

const recordId = dataset.recordId ? Number(dataset.recordId) : null
const screens = {
    list: ContractList,
    create: ContractForm,
    edit: ContractForm,
    view: ContractView,
    'report-pending': PaymentReports,
    'report-paid': PaymentReports,
    vendors: VendorList,
}
const screen = computed(() => screens[dataset.screen] ?? ContractList)
</script>
