<template>
    <div class="card shadow-sm border-0">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-4">
                <div class="d-flex gap-5">
                    <div>
                        <div class="small fw-bold text-muted text-uppercase mb-1">Order Number</div>
                        <div class="h5 fw-bold text-primary mb-0">{{ order.order_no }}</div>
                    </div>
                    <div>
                        <div class="small fw-bold text-muted text-uppercase mb-1">Customer</div>
                        <div class="h5 fw-bold text-dark mb-0">{{ order.customer_name }}</div>
                    </div>
                </div>
                <div class="d-flex gap-3">
                    <div style="min-width: 200px;">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Rack</label>
                        <SearchableSelect :modelValue="order.rack_id" @update:modelValue="emit('update-rack', $event)"
                            :options="racks" placeholder="Select rack..." class="form-select-sm" />
                    </div>
                    <div style="min-width: 200px;">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Cutter</label>
                        <SearchableSelect :modelValue="order.cutter_id"
                            @update:modelValue="emit('update-cutter', $event)" :options="cutters"
                            placeholder="Select cutter..." class="form-select-sm" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import SearchableSelect from '@/components/SearchableSelect.vue'

const props = defineProps({
    order: Object,
    racks: Object,
    cutters: Object,
})

const emit = defineEmits(['update-rack', 'update-cutter'])
</script>
