<template>
    <div class="card shadow-sm border mb-4 border-primary rounded">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <CategoryFilter :value="filters.category_id" :categories="categories"
                        @update:value="updateFilter('category_id', $event)" />
                </div>
                <div class="col-12 col-md-3">
                    <BrandFilter :value="filters.brand_id" :brands="brands" @update:value="updateFilter('brand_id', $event)" />
                </div>
                <div class="col-12 col-md-2">
                    <RecordedQtyFilter :value="filters.recorded_qty_condition"
                        @update:value="updateFilter('recorded_qty_condition', $event)" />
                </div>
                <div class="col-12 col-md-2">
                    <StatusFilter :value="filters.status" :statuses="statuses"
                        @update:value="updateFilter('status', $event)" />
                </div>
                <div class="col-12 col-md-2">
                    <DifferenceFilter :value="filters.difference_condition"
                        @update:value="updateFilter('difference_condition', $event)" />
                </div>
                <div class="col-12 col-md-10">
                    <GeneralSearchFilter :value="filters.search" @update:value="updateFilter('search', $event)"
                        @search="handleSearch" />
                </div>
                <div class="col-12 col-md-2">
                    <ItemsPerPageFilter :value="filters.per_page" @update:value="updateFilter('per_page', $event)" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import CategoryFilter from './CategoryFilter.vue'
import BrandFilter from './BrandFilter.vue'
import RecordedQtyFilter from './RecordedQtyFilter.vue'
import StatusFilter from './StatusFilter.vue'
import DifferenceFilter from './DifferenceFilter.vue'
import GeneralSearchFilter from './GeneralSearchFilter.vue'
import ItemsPerPageFilter from './ItemsPerPageFilter.vue'

const props = defineProps({
    filters: {
        type: Object,
        required: true
    },
    categories: {
        type: Array,
        default: () => []
    },
    brands: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['filter-changed'])

const statuses = [
    { value: '', label: 'All' },
    { value: 'pending', label: 'Pending' },
    { value: 'completed', label: 'Completed' }
]

const updateFilter = (key, value) => {
    emit('filter-changed', { [key]: value })
}

const handleSearch = (query) => {
    emit('filter-changed', { search: query })
}
</script>

