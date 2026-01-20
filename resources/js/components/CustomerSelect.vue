<template>
    <select ref="selectRef" :placeholder="placeholder" autocomplete="off" class="w-full"></select>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
    modelValue: [String, Number],
    placeholder: {
        type: String,
        default: 'Search customer...'
    },
    valueField: {
        type: String,
        default: 'id'
    },
    labelField: {
        type: String,
        default: 'name'
    },
    initialData: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['update:modelValue', 'change', 'selected'])

const selectRef = ref(null)
let tomSelect = null

const initTomSelect = () => {
    if (typeof window.TomSelect === 'undefined') return

    tomSelect = new window.TomSelect(selectRef.value, {
        valueField: props.valueField,
        labelField: props.labelField,
        searchField: ['name', 'mobile', 'email'],
        load: function(query, callback) {
            axios.get('/account/list', {
                params: {
                    query: query,
                    model: 'customer'
                }
            })
            .then(response => {
                callback(response.data.items || []);
            })
            .catch(() => {
                callback();
            });
        },
        render: {
            option: function(data, escape) {
                return `<div>
                    <div class="font-medium">${escape(data.name)}</div>
                    <div class="text-gray-400 text-xs">${escape(data.mobile || '')} ${data.email ? '• ' + escape(data.email) : ''}</div>
                </div>`;
            },
            item: function(data, escape) {
                return `<div>${escape(data.name)} ${data.mobile ? '<span class="text-gray-500 ml-1">(' + escape(data.mobile) + ')</span>' : ''}</div>`;
            }
        },
        onChange: (value) => {
            emit('update:modelValue', value);
            const selected = tomSelect.options[value];
            emit('change', value, selected);
            if (selected) {
                emit('selected', selected);
            }
        }
    });

    // Handle initial value
    if (props.initialData) {
        tomSelect.addOption(props.initialData);
        tomSelect.setValue(props.modelValue, true);
    } else if (props.modelValue && props.valueField === 'name') {
        // Fallback for name-based selection without full data
        tomSelect.addOption({ name: props.modelValue });
        tomSelect.setValue(props.modelValue, true);
    }
}

onMounted(() => {
    if (typeof window.TomSelect !== 'undefined') {
        initTomSelect()
    } else {
        const check = setInterval(() => {
            if (typeof window.TomSelect !== 'undefined') {
                clearInterval(check)
                initTomSelect()
            }
        }, 100)
    }
})

onBeforeUnmount(() => {
    if (tomSelect) tomSelect.destroy()
})

watch(() => props.modelValue, (val) => {
    if (tomSelect && val !== tomSelect.getValue()) {
        if (val && !tomSelect.options[val]) {
            // Option doesn't exist, we might need to add it if we have context
            // For now just try to set it.
        }
        tomSelect.setValue(val, true)
    }
})
</script>

<style>
/* Base TomSelect overrides can go here or in a global file */
.ts-wrapper .ts-control {
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
}
</style>
