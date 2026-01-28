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
                const query = this.lastQuery || '';
                const highlightText = (text) => {
                    if (!text) return '';
                    if (!query) return escape(text);
                    const re = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                    return escape(text).replace(re, '<span class="ts-highlight">$1</span>');
                };

                return `<div class="py-1">
                    <div class="fw-medium text-dark line-height-tight text-truncate">${highlightText(data.name)}</div>
                    <div class="text-muted small mt-0 opacity-75 d-flex align-items-center">
                        <span class="text-truncate">${highlightText(data.mobile || '')}</span>
                        ${data.email ? `<span class="mx-1">•</span><span class="text-truncate">${highlightText(data.email)}</span>` : ''}
                    </div>
                </div>`;
            },
            item: function(data, escape) {
                return `<div class="d-flex align-items-center gap-1">
                    <span class="fw-medium">${escape(data.name)}</span>
                    <span class="text-muted small opacity-50">(${escape(data.mobile || 'No mobile')})</span>
                </div>`;
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
            // Option doesn't exist, add it from initialData if available
            if (props.initialData && props.initialData.id === val) {
                tomSelect.addOption(props.initialData)
            }
        }
        tomSelect.setValue(val, true)
    }
})

// Watch initialData changes to update TomSelect accordingly
watch(() => props.initialData, (newData) => {
    if (tomSelect && newData && newData.id) {
        // Add or update the option
        tomSelect.addOption(newData)
        // Set value if it matches the modelValue
        if (newData.id === props.modelValue) {
            tomSelect.setValue(newData.id, true)
        }
    }
}, { deep: true })
</script>

