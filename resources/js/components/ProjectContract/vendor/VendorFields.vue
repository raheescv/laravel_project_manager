<template>
    <div class="paf">
        <template v-for="section in visibleSections" :key="section.key">
            <component :is="framed ? 'section' : 'div'" :class="framed ? 'paf-card mb-2' : 'mb-3'">
                <div v-if="framed" class="paf-head"><i class="fa" :class="section.icon"></i><h5>{{ section.title }}</h5><span v-if="section.hint" class="pa-sub ms-auto d-none d-md-inline">{{ section.hint }}</span></div>
                <div v-else class="paf-sub"><i class="fa" :class="section.icon"></i> {{ section.title }}</div>
                <div :class="framed ? 'paf-body' : ''">
                    <div class="row g-2">
                        <div v-for="field in section.fields" :key="field.key" :class="field.wide ? 'col-12' : 'col-12 col-sm-6 col-xl-4'" @input="clearError(field.key)" @change="clearError(field.key)">
                            <label class="form-label" :for="`${idPrefix}${field.key}`">{{ field.label }}<span v-if="field.required"> *</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text"><i class="fa" :class="field.icon"></i></span>
                                <select v-if="field.options" :id="`${idPrefix}${field.key}`" v-model="model[field.key]" class="form-select" :class="{ 'is-invalid': errors[field.key] }">
                                    <option value="">{{ field.placeholder }}</option>
                                    <option v-for="option in field.options" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                                <textarea v-else-if="field.textarea" :id="`${idPrefix}${field.key}`" v-model="model[field.key]" rows="1" class="form-control" :class="{ 'is-invalid': errors[field.key] }" :placeholder="field.placeholder" @input="grow"></textarea>
                                <input v-else :id="`${idPrefix}${field.key}`" v-model="model[field.key]" :type="field.type ?? 'text'" class="form-control" :class="{ 'is-invalid': errors[field.key] }" :placeholder="field.placeholder" :dir="field.rtl ? 'rtl' : null" :inputmode="field.inputmode" />
                                <div v-if="errors[field.key]" class="invalid-feedback">{{ errors[field.key] }}</div>
                            </div>
                            <div v-if="field.help" class="form-text">{{ field.help }}</div>
                        </div>
                    </div>
                </div>
            </component>
            <button v-if="collapsible && section.key === 'contact' && !expanded" type="button" class="pvm-more mb-2" @click="expanded = true">
                <i class="fa fa-angle-down"></i> More details <span class="pa-sub">Personal and business details, optional</span>
            </button>
        </template>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useOptions } from '../common/useContractContext.js'

const props = defineProps({
    model: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
    framed: { type: Boolean, default: true },
    collapsible: { type: Boolean, default: false },
    idPrefix: { type: String, default: 'v-' },
})

const options = useOptions()
const expanded = ref(!props.collapsible)

const nationalities = ['Qatar', 'Saudi Arabia', 'United Arab Emirates', 'Kuwait', 'Bahrain', 'Oman', 'Egypt', 'Jordan', 'Lebanon', 'Syria', 'Sudan', 'India', 'Pakistan', 'Bangladesh', 'Sri Lanka', 'Nepal', 'Philippines', 'Indonesia', 'United Kingdom', 'United States', 'Other']
    .map((name) => ({ value: name, label: name }))
const maritalStatuses = ['Single', 'Married', 'Divorced', 'Widowed'].map((name) => ({ value: name, label: name }))

const sections = computed(() => [
    {
        key: 'basic', title: 'Basic Information', icon: 'fa-info-circle text-primary',
        fields: [
            { key: 'name', label: 'Name', icon: 'fa-user text-secondary', required: true, placeholder: 'Vendor or company name' },
            { key: 'alias_name', label: 'Name (Arabic)', icon: 'fa-language text-secondary', placeholder: 'الاسم بالعربية', rtl: true },
            { key: 'vendor_type', label: 'Vendor Type', icon: 'fa-tag text-primary', options: options.vendor_types, placeholder: 'Select type' },
            { key: 'dob', label: 'Date Of Birth', icon: 'fa-birthday-cake text-secondary', type: 'date', help: 'Individuals only' },
            { key: 'credit_period_days', label: 'Credit Period (days)', icon: 'fa-clock-o text-secondary', type: 'number', placeholder: 'e.g. 30', inputmode: 'numeric' },
            { key: 'cr_number', label: 'CR No.', icon: 'fa-file-text-o text-secondary', placeholder: 'Commercial registration number' },
            { key: 'tax_no', label: 'Tax No.', icon: 'fa-file-text-o text-secondary', placeholder: 'Tax registration number' },
        ],
    },
    {
        key: 'contact', title: 'Contact Information', icon: 'fa-phone-square text-primary',
        fields: [
            { key: 'mobile', label: 'Mobile', icon: 'fa-mobile text-secondary', required: true, type: 'tel', placeholder: 'e.g. 0097455512345' },
            { key: 'whatsapp_mobile', label: 'WhatsApp', icon: 'fa-whatsapp text-success', type: 'tel', placeholder: 'Same as mobile if blank' },
            { key: 'emergency_contact_no', label: 'Emergency Contact No', icon: 'fa-phone text-secondary', type: 'tel', placeholder: 'Enter emergency contact number' },
            { key: 'email', label: 'Email', icon: 'fa-envelope-o text-secondary', type: 'email', placeholder: 'name@company.com' },
            { key: 'po_box', label: 'P.O. Box', icon: 'fa-inbox text-secondary', placeholder: 'Enter P.O. Box number' },
            { key: 'contact_person', label: 'Contact Person', icon: 'fa-user-plus text-secondary', placeholder: 'Who to call at the vendor' },
            { key: 'place', label: 'Place', icon: 'fa-map-marker text-secondary', placeholder: 'Area or city' },
        ],
    },
    {
        key: 'personal', title: 'Personal Details', icon: 'fa-user text-secondary', hint: 'For individual vendors and the signing person',
        fields: [
            { key: 'id_no', label: 'ID Number', icon: 'fa-credit-card text-secondary', placeholder: 'Enter ID number' },
            { key: 'id_expiry_date', label: 'ID Expiry Date', icon: 'fa-calendar text-danger', type: 'date' },
            { key: 'passport_no', label: 'Passport No', icon: 'fa-book text-secondary', placeholder: 'Enter passport number' },
            { key: 'occupation', label: 'Occupation', icon: 'fa-briefcase text-secondary', placeholder: 'Enter occupation' },
            { key: 'nationality', label: 'Nationality', icon: 'fa-flag text-secondary', options: nationalities, placeholder: 'Select nationality' },
            { key: 'company', label: 'Company', icon: 'fa-building-o text-secondary', placeholder: 'Enter company name' },
            { key: 'marital_status', label: 'Marital Status', icon: 'fa-users text-secondary', options: maritalStatuses, placeholder: 'Select marital status' },
            { key: 'residential_address', label: 'Residential Address', icon: 'fa-home text-secondary', textarea: true, wide: true, placeholder: 'Enter residential address' },
        ],
    },
    {
        key: 'business', title: 'Employment / Business Details', icon: 'fa-suitcase text-info',
        fields: [
            { key: 'position_nature_of_business', label: 'Position / Nature Of Business', icon: 'fa-suitcase text-secondary', placeholder: 'e.g. Steel fabrication contractor' },
            { key: 'monthly_income', label: 'Monthly Income', icon: 'fa-money text-secondary', type: 'number', placeholder: '0.00', inputmode: 'decimal' },
            { key: 'employer_address', label: 'Employer / Business Address', icon: 'fa-map-marker text-danger', textarea: true, wide: true, placeholder: 'Enter employer or business address' },
        ],
    },
])

const visibleSections = computed(() => (expanded.value ? sections.value : sections.value.filter((section) => ['basic', 'contact'].includes(section.key))))

function clearError(key) {
    if (props.errors[key]) delete props.errors[key]
}

function grow(event) {
    const element = event.target
    element.style.height = 'auto'
    element.style.height = `${element.scrollHeight + 2}px`
}
</script>
