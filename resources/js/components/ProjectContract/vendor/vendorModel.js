/** Every vendor field the form edits, blank. */
export function blankVendor() {
    return {
        name: '', alias_name: '', vendor_type: '', dob: '', credit_period_days: '', cr_number: '', tax_no: '',
        mobile: '', whatsapp_mobile: '', emergency_contact_no: '', email: '', po_box: '', contact_person: '', place: '',
        id_no: '', id_expiry_date: '', passport_no: '', occupation: '', nationality: '', company: '', marital_status: '', residential_address: '',
        position_nature_of_business: '', monthly_income: '', employer_address: '',
    }
}

/** Drop empty strings so the server sees missing rather than blank values. */
export function vendorPayload(model) {
    return Object.fromEntries(Object.entries(model).filter(([key, value]) => key !== 'id' && value !== '' && value !== null && value !== undefined))
}
