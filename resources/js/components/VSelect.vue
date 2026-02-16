<template>
    <div class="v-select-wrapper">
        <Multiselect :model-value="selectedOption" :options="options" label="label" track-by="value"
            :placeholder="placeholder" :searchable="true" :loading="loading" :internal-search="!remote"
            :allow-empty="true" use-teleport @update:model-value="onSelect" @search-change="onSearchChange">
            <template #option="{ option }">
                <div class="flex flex-col">
                    <span :class="option.value === modelValue ? 'font-black text-blue-600' : 'font-bold'">
                        {{ option.label }}
                    </span>
                    <span v-if="option.description" class="text-[10px] opacity-60 font-medium">
                        {{ option.description }}
                    </span>
                </div>
            </template>
            <template #singlelabel="{ option }">
                <span :class="option ? 'text-slate-700' : 'text-slate-400'">
                    {{ option ? option.label : placeholder }}
                </span>
            </template>
            <template #noResult>
                <span class="italic">No results found</span>
            </template>
        </Multiselect>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import Multiselect from 'vue-multiselect'
import 'vue-multiselect/dist/vue-multiselect.css'

const props = defineProps({
    modelValue: [String, Number, Boolean, null],
    options: {
        type: Array,
        required: true,
        // Expects [{ value, label, description }]
    },
    placeholder: {
        type: String,
        default: 'Select an option'
    },
    searchPlaceholder: {
        type: String,
        default: 'Type to search...'
    },
    loading: {
        type: Boolean,
        default: false
    },
    remote: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue', 'change', 'search'])

const selectedOption = computed(() => {
    if (props.modelValue === undefined || props.modelValue === null || props.modelValue === '') return null
    return props.options.find(opt => String(opt.value) === String(props.modelValue)) ?? null
})

function onSelect(option) {
    const value = option != null ? option.value : null
    emit('update:modelValue', value)
    emit('change', value)
}

function onSearchChange(term) {
    if (props.remote) {
        emit('search', term)
    }
}
</script>

<style scoped>
.v-select-wrapper :deep(.multiselect) {
    min-height: auto;
}

.v-select-wrapper :deep(.multiselect__tags) {
    min-height: auto;
    padding: 6px 28px 6px 12px;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    font-size: 0.75rem;
    font-weight: 700;
}

.v-select-wrapper :deep(.multiselect--active .multiselect__tags) {
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    background: #fff;
}

.v-select-wrapper :deep(.multiselect__placeholder) {
    color: #94a3b8;
    padding: 0;
    margin: 0;
}

.v-select-wrapper :deep(.multiselect__single) {
    padding: 0;
    margin: 0;
}

.v-select-wrapper :deep(.multiselect__input) {
    padding: 0;
    margin: 0;
    background: transparent;
    font-size: 0.7rem;
    font-weight: 600;
}

.v-select-wrapper :deep(.multiselect__content-wrapper) {
    border-radius: 1rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: 1px solid #f1f5f9;
}

.v-select-wrapper :deep(.multiselect__option--highlight) {
    background: #eff6ff;
    color: #2563eb;
}

.v-select-wrapper :deep(.multiselect__option--selected) {
    background: #eff6ff;
    color: #2563eb;
}
</style>
