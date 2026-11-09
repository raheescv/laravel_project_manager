<template>
    <Teleport to="body">
        <div class="ofx cpx-overlay" @keydown.esc="$emit('close')">
            <div class="cpx-scrim" @click="$emit('close')"></div>
            <aside class="cpx-sheet" role="dialog" aria-modal="true" aria-labelledby="cpx-sheet-title">
                <header class="cpx-sheet-head">
                    <h3 id="cpx-sheet-title">{{ isEditing ? `Edit ${coupon.code}` : 'New coupon' }}</h3>
                    <button type="button" class="ofx-btn ofx-btn-ghost ofx-icon" aria-label="Close" @click="$emit('close')"><i class="fa fa-times"></i></button>
                </header>

                <div class="cpx-sheet-body">
                    <form id="cpx-form" class="cpx-form" novalidate @submit.prevent="save" @input="clearError($event.target.name)">
                        <p class="ofx-eyebrow">The code</p>
                        <CouponRow label="Code" required :error="errors.code">
                            <div class="cpx-inline">
                                <input ref="codeInput" v-model="form.code" name="code" class="ofx-inp cpx-code" maxlength="24" autocomplete="off" spellcheck="false" />
                                <button type="button" class="ofx-btn ofx-btn-ghost ofx-icon" title="Generate a code" aria-label="Generate a code" @click="generateCode"><i class="fa fa-refresh"></i></button>
                            </div>
                        </CouponRow>
                        <CouponRow label="Title" :error="errors.title">
                            <input v-model="form.title" name="title" class="ofx-inp" maxlength="120" :placeholder="suggestedTitle" />
                        </CouponRow>
                        <CouponRow label="Arabic title" :error="errors.title_arabic">
                            <input v-model="form.title_arabic" name="title_arabic" class="ofx-inp" maxlength="120" dir="rtl" />
                        </CouponRow>

                        <p class="ofx-eyebrow">The discount</p>
                        <CouponRow label="Type">
                            <div class="ofx-seg cpx-seg">
                                <button v-for="option in discountTypes" :key="option.value" type="button" :class="{ on: form.discount_type === option.value }" @click="form.discount_type = option.value">{{ option.label }}</button>
                            </div>
                        </CouponRow>
                        <CouponRow label="Value" required :error="errors.discount_value">
                            <div class="ofx-inp-group">
                                <input v-model="form.discount_value" name="discount_value" class="ofx-inp" type="number" min="0" step="0.01" inputmode="decimal" />
                                <span>{{ isPercent ? '%' : consoleConfig.currency }}</span>
                            </div>
                        </CouponRow>
                        <CouponRow v-if="isPercent" label="Max. discount" :error="errors.max_discount">
                            <div class="ofx-inp-group">
                                <input v-model="form.max_discount" name="max_discount" class="ofx-inp" type="number" min="0" inputmode="decimal" placeholder="No limit" />
                                <span>{{ consoleConfig.currency }}</span>
                            </div>
                        </CouponRow>
                        <CouponRow label="Min. bag" :error="errors.min_bag_total">
                            <div class="ofx-inp-group">
                                <input v-model="form.min_bag_total" name="min_bag_total" class="ofx-inp" type="number" min="0" inputmode="decimal" placeholder="None" />
                                <span>{{ consoleConfig.currency }}</span>
                            </div>
                        </CouponRow>
                        <CouponRow label="Applies to" :error="form.applies_to === 'all' ? '' : errors.applies_to_ids">
                            <div class="ofx-seg cpx-seg">
                                <button v-for="option in scopes" :key="option.value" type="button" :class="{ on: form.applies_to === option.value }" @click="setScope(option.value)">{{ option.label }}</button>
                            </div>
                            <CouponScopePicker
                                v-if="form.applies_to !== 'all'"
                                :key="form.applies_to"
                                v-model="form.items"
                                class="cpx-mt"
                                :url="consoleConfig.lookups[form.applies_to]"
                                :placeholder="`Search ${scopeLabel}…`"
                            />
                        </CouponRow>
                        <CouponRow v-if="form.applies_to !== 'product'" label="Except products" :error="errors.excluded_product_ids">
                            <CouponScopePicker v-model="form.excluded_items" :url="consoleConfig.lookups.product" placeholder="Search products to leave out…" />
                        </CouponRow>

                        <p class="ofx-eyebrow">Limits</p>
                        <CouponRow label="Starts" required :error="errors.starts_on">
                            <input v-model="form.starts_on" name="starts_on" class="ofx-inp" type="date" />
                        </CouponRow>
                        <CouponRow label="Ends" :error="errors.ends_on">
                            <input v-model="form.ends_on" name="ends_on" class="ofx-inp" type="date" />
                        </CouponRow>
                        <CouponRow label="Total uses" :error="errors.usage_limit">
                            <input v-model="form.usage_limit" name="usage_limit" class="ofx-inp" type="number" min="1" inputmode="numeric" placeholder="Unlimited" />
                        </CouponRow>
                        <CouponRow label="Per customer" :error="errors.per_customer_limit">
                            <input v-model="form.per_customer_limit" name="per_customer_limit" class="ofx-inp" type="number" min="1" inputmode="numeric" placeholder="Unlimited" />
                        </CouponRow>

                        <p class="ofx-eyebrow">Visibility</p>
                        <CouponRow label="Search match">
                            <div class="cpx-hint">
                                <CouponSwitch v-model="form.is_searchable" label="Find this coupon by part of its code" />
                                <span>{{ form.is_searchable ? 'Found by part of the code, from 3 letters.' : 'Private. Found only when the full code is typed.' }}</span>
                            </div>
                        </CouponRow>
                        <CouponRow label="Active">
                            <div class="cpx-hint">
                                <CouponSwitch v-model="form.is_active" label="Coupon is active" />
                                <span>Turn off to pause it without deleting.</span>
                            </div>
                        </CouponRow>
                    </form>

                    <div class="cpx-side">
                        <p class="ofx-eyebrow">Search result preview</p>
                        <div class="cpx-preview">
                            <div class="cpx-preview-top">
                                <b class="cpx-code">{{ normalizedCode || 'YOURCODE' }}</b>
                                <span>{{ discountLabel }}</span>
                            </div>
                            <div class="cpx-preview-sub">
                                <span>{{ form.title.trim() || suggestedTitle }}</span>
                                <span v-if="Number(form.min_bag_total) > 0">Min. {{ money(form.min_bag_total) }}</span>
                            </div>
                        </div>
                        <p class="cpx-note">This is how the code appears in the bag once a customer searches for it.</p>
                    </div>
                </div>

                <footer class="cpx-sheet-foot">
                    <button type="button" class="ofx-btn ofx-btn-ghost" @click="$emit('close')">Cancel</button>
                    <button type="submit" form="cpx-form" class="ofx-btn ofx-btn-pri" :disabled="saving">
                        <i class="fa" :class="saving ? 'fa-spinner fa-spin' : 'fa-check'"></i> Save coupon
                    </button>
                </footer>
            </aside>
        </div>
    </Teleport>
</template>

<script setup>
import { computed, nextTick, onMounted, reactive, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { errorMessage } from '../Product/Offer/api.js'
import { consoleConfig, couponApi, money } from './api.js'
import CouponRow from './CouponRow.vue'
import CouponScopePicker from './CouponScopePicker.vue'
import CouponSwitch from './CouponSwitch.vue'

const props = defineProps({
    coupon: { type: Object, default: null },
})
const emit = defineEmits(['close', 'saved'])

const toast = useToast()

const discountTypes = [
    { value: 'percent', label: 'Percent' },
    { value: 'fixed', label: 'Fixed amount' },
]
const scopes = [
    { value: 'all', label: 'Everything' },
    { value: 'brand', label: 'Brands' },
    { value: 'category', label: 'Categories' },
    { value: 'product', label: 'Products' },
]

/** A row with an id is being edited; one without (a duplicate) is new. */
const isEditing = computed(() => Boolean(props.coupon?.id))
const source = props.coupon ?? {}
const form = reactive({
    code: source.code ?? '',
    title: source.title ?? '',
    title_arabic: source.title_arabic ?? '',
    discount_type: source.discount_type ?? 'percent',
    discount_value: source.discount_value ?? '',
    max_discount: source.max_discount ?? '',
    min_bag_total: source.min_bag_total || '',
    applies_to: source.applies_to ?? 'all',
    items: [...(source.items ?? [])],
    excluded_items: [...(source.excluded_items ?? [])],
    starts_on: source.starts_on ?? new Date().toLocaleDateString('en-CA'),
    ends_on: source.ends_on ?? '',
    usage_limit: source.usage_limit ?? '',
    per_customer_limit: source.per_customer_limit ?? 1,
    is_searchable: source.is_searchable ?? true,
    is_active: source.is_active ?? true,
})
const errors = ref({})
const saving = ref(false)
const codeInput = ref(null)

const isPercent = computed(() => form.discount_type === 'percent')
const normalizedCode = computed(() => form.code.replace(/\s+/g, '').toUpperCase())
const scopeLabel = computed(() => scopes.find((option) => option.value === form.applies_to).label.toLowerCase())
const discountLabel = computed(() => {
    const value = Number(form.discount_value) || 0
    return isPercent.value ? `${value}% off` : `${money(value)} off`
})
const suggestedTitle = computed(() => (Number(form.discount_value) ? discountLabel.value : 'e.g. 15% off for runners'))

function clearError(field) {
    if (field && errors.value[field]) {
        errors.value = { ...errors.value, [field]: '' }
    }
}

function setScope(scope) {
    if (scope === form.applies_to) return
    form.applies_to = scope
    form.items = []
    clearError('applies_to_ids')
}

function generateCode() {
    const letters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'
    form.code = Array.from({ length: 8 }, () => letters[Math.floor(Math.random() * letters.length)]).join('')
    clearError('code')
}

const blankToNull = (value) => (value === '' || value === null ? null : value)

function payload() {
    return {
        ...form,
        code: normalizedCode.value,
        title: form.title.trim() || suggestedTitle.value,
        title_arabic: blankToNull(form.title_arabic),
        max_discount: isPercent.value ? blankToNull(form.max_discount) : null,
        min_bag_total: blankToNull(form.min_bag_total),
        applies_to_ids: form.items.map((item) => item.id),
        excluded_product_ids: form.excluded_items.map((item) => item.id),
        ends_on: blankToNull(form.ends_on),
        usage_limit: blankToNull(form.usage_limit),
        per_customer_limit: blankToNull(form.per_customer_limit),
    }
}

async function save() {
    if (saving.value) return
    saving.value = true
    errors.value = {}
    try {
        const response = isEditing.value ? await couponApi.update(props.coupon.id, payload()) : await couponApi.store(payload())
        toast.success(response.message)
        emit('saved')
    } catch (error) {
        const fieldErrors = error?.response?.data?.errors
        if (fieldErrors) {
            errors.value = Object.fromEntries(Object.entries(fieldErrors).map(([field, messages]) => [field, messages[0]]))
        } else {
            toast.error(errorMessage(error))
        }
    } finally {
        saving.value = false
    }
}

onMounted(() => nextTick(() => codeInput.value?.focus()))
</script>
