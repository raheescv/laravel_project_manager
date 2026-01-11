<template>
    <thead>
        <tr>
            <th @click="handleSort('stock_check_items.id')" class="pointer d-none d-xl-table-cell">
                ID
                <i v-if="sortField === 'stock_check_items.id'"
                    :class="sortDirection === 'asc' ? 'fa fa-sort-up' : 'fa fa-sort-down'"></i>
            </th>
            <th @click="handleSort('stock_check_items.updated_at')" class="pointer d-none d-lg-table-cell">
                Updated At
                <i v-if="sortField === 'stock_check_items.updated_at'"
                    :class="sortDirection === 'asc' ? 'fa fa-sort-up' : 'fa fa-sort-down'"></i>
            </th>
            <th @click="handleSort('brands.name')" class="pointer d-none d-xl-table-cell">
                Brand
                <i v-if="sortField === 'brands.name'"
                    :class="sortDirection === 'asc' ? 'fa fa-sort-up' : 'fa fa-sort-down'"></i>
            </th>
            <th @click="handleSort('categories.name')" class="pointer d-none d-xl-table-cell">
                Category
                <i v-if="sortField === 'categories.name'"
                    :class="sortDirection === 'asc' ? 'fa fa-sort-up' : 'fa fa-sort-down'"></i>
            </th>
            <th @click="handleSort('products.name')" class="pointer">
                Product
                <i v-if="sortField === 'products.name'"
                    :class="sortDirection === 'asc' ? 'fa fa-sort-up' : 'fa fa-sort-down'"></i>
            </th>
            <th @click="handleSort('inventories.barcode')" class="pointer text-end d-none d-lg-table-cell">
                Barcode
                <i v-if="sortField === 'inventories.barcode'"
                    :class="sortDirection === 'asc' ? 'fa fa-sort-up' : 'fa fa-sort-down'"></i>
            </th>
            <th @click="handleSort('stock_check_items.physical_quantity')" class="pointer text-end">
                Physical Qty
                <i v-if="sortField === 'stock_check_items.physical_quantity'"
                    :class="sortDirection === 'asc' ? 'fa fa-sort-up' : 'fa fa-sort-down'"></i>
            </th>
            <th @click="handleSort('stock_check_items.recorded_quantity')"
                class="pointer text-end d-none d-md-table-cell">
                Recorded Qty
                <i v-if="sortField === 'stock_check_items.recorded_quantity'"
                    :class="sortDirection === 'asc' ? 'fa fa-sort-up' : 'fa fa-sort-down'"></i>
            </th>
            <th @click="handleSort('difference')" class="pointer text-end">
                Difference
                <i v-if="sortField === 'difference'"
                    :class="sortDirection === 'asc' ? 'fa fa-sort-up' : 'fa fa-sort-down'"></i>
            </th>
            <th @click="handleSort('stock_check_items.status')" class="pointer">
                Status
                <i v-if="sortField === 'stock_check_items.status'"
                    :class="sortDirection === 'asc' ? 'fa fa-sort-up' : 'fa fa-sort-down'"></i>
            </th>
        </tr>
    </thead>
</template>

<script setup>
defineProps({
    sortField: {
        type: String,
        default: 'stock_check_items.updated_at'
    },
    sortDirection: {
        type: String,
        default: 'desc'
    }
})

const emit = defineEmits(['sort'])

const handleSort = (field) => {
    emit('sort', field)
}
</script>

<style scoped>
.pointer {
    cursor: pointer;
    user-select: none;
}

.pointer:hover {
    background-color: #f0f0f0;
}
</style>
