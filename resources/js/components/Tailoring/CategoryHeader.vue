<template>
    <div class="category-header-container mb-4">
        <div class="card shadow-sm border-0 premium-card">
            <div class="card-body p-3">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="section-title-box">
                        <i class="fa fa-th-large me-2 text-primary"></i>
                        <span class="fw-bold text-gray-800">Item Types</span>
                    </div>
                    
                    <div class="category-chips-wrapper d-flex flex-wrap gap-2">
                        <div v-for="category in categories" :key="category.id"
                            class="category-chip"
                            :class="{ 'active': selectedCategories.includes(category.id) }"
                            @click="handleCategoryToggle(category.id)">
                            <div class="chip-content">
                                <span class="chip-name">{{ category.name }}</span>
                                <div class="chip-indicator">
                                    <i class="fa" :class="selectedCategories.includes(category.id) ? 'fa-check-circle' : 'fa-circle-thin'"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    },
    selectedCategories: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['update:selectedCategories', 'category-selected'])

const handleCategoryToggle = (categoryId) => {
    const updated = props.selectedCategories.includes(categoryId)
        ? props.selectedCategories.filter(id => id !== categoryId)
        : [...props.selectedCategories, categoryId]

    emit('update:selectedCategories', updated)
    emit('category-selected', updated)
}
</script>

<style scoped>
.premium-card {
    border-radius: 10px;
    background: #ffffff;
    border: 1px solid rgba(0,0,0,0.05) !important;
}

.section-title-box {
    padding-right: 12px;
    border-right: 2px solid #f1f5f9;
    height: 24px;
    display: flex;
    align-items: center;
}

.category-chips-wrapper {
    flex: 1;
}

.category-chip {
    padding: 4px 12px;
    border-radius: 8px;
    background-color: #f8fafc;
    border: 1.5px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
}

.category-chip:hover {
    border-color: #3b82f6;
    background-color: #eff6ff;
    transform: translateY(-1px);
}

.category-chip.active {
    background-color: #3b82f6;
    border-color: #3b82f6;
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
}

.chip-content {
    display: flex;
    align-items: center;
    gap: 8px;
}

.chip-name {
    font-weight: 600;
    font-size: 0.85rem;
}

.chip-indicator i {
    font-size: 0.9rem;
    opacity: 0.7;
}

.category-chip.active .chip-indicator i {
    opacity: 1;
}

.text-gray-800 { color: #1e293b; }

@media (max-width: 576px) {
    .section-title-box {
        border-right: none;
        width: 100%;
        margin-bottom: 5px;
    }
}
</style>
