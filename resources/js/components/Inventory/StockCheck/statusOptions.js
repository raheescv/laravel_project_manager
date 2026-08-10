// The stock check's own (header) statuses — mirrors `stockCheckStatuses()` in
// app/Helpers/helper.php, which is also what the DB enum was built from.
export const STOCK_CHECK_STATUSES = [
    { value: 'pending', label: 'Pending', badge: 'badge bg-warning text-dark', icon: 'fa fa-clock-o' },
    { value: 'completed', label: 'Completed', badge: 'badge bg-success', icon: 'fa fa-check' },
    { value: 'cancelled', label: 'Cancelled', badge: 'badge bg-danger', icon: 'fa fa-ban' },
]

export const statusOption = (value) => STOCK_CHECK_STATUSES.find(s => s.value === value) || null

export const statusLabel = (value) => {
    const option = statusOption(value)
    if (option) return option.label
    if (!value) return '-'
    return value.charAt(0).toUpperCase() + value.slice(1)
}

export const statusBadgeClass = (value) => statusOption(value)?.badge || 'badge bg-secondary'

export const statusIcon = (value) => statusOption(value)?.icon || 'fa fa-info-circle'
