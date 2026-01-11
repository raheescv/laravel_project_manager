import React, { useEffect, useState } from "react";
import axios from "axios";

export default function CustomerDetailsModal({ open, onClose, customerId, initialDetails = null, onSaved = null }) {
    const [loading, setLoading] = useState(false);
    const [editing, setEditing] = useState(false);
    const [details, setDetails] = useState(initialDetails);
    const [form, setForm] = useState({ name: "", mobile: "", email: "", whatsapp_mobile: "", company: "", nationality: "", id_no: "" });
    const [errors, setErrors] = useState({});

    useEffect(() => {
        if (!open) return;
        let mounted = true;
        if (initialDetails) {
            setDetails(initialDetails);
            const c = initialDetails.customer || {};
            setForm({
                name: c.name || "",
                mobile: c.mobile || "",
                email: c.email || "",
                whatsapp_mobile: c.whatsapp_mobile || "",
                company: c.company || "",
                nationality: c.nationality || "",
                id_no: c.id_no || "",
            });
            return () => { mounted = false };
        }

        if (!customerId) return;
        setLoading(true);
        axios.get(`/account/customer/${customerId}/details`)
            .then(res => {
                if (!mounted) return;
                if (res.data && res.data.success) {
                    setDetails(res.data);
                    const c = res.data.customer || {};
                    setForm({
                        name: c.name || "",
                        mobile: c.mobile || "",
                        email: c.email || "",
                        whatsapp_mobile: c.whatsapp_mobile || "",
                        company: c.company || "",
                        nationality: c.nationality || "",
                        id_no: c.id_no || "",
                    });
                } else {
                    setDetails(null);
                }
            })
            .catch(() => setDetails(null))
            .finally(() => setLoading(false));

        return () => { mounted = false };
    }, [open, customerId, initialDetails]);

    const save = async () => {
        setErrors({});
        const payload = {
            name: form.name,
            mobile: form.mobile,
            whatsapp_mobile: form.whatsapp_mobile,
            email: form.email,
            company: form.company,
            nationality: form.nationality,
            id_no: form.id_no,
            customer_type_id: details?.customer?.customer_type_id || null,
        };

        try {
            setLoading(true);
            let res;
            if (customerId) {
                res = await axios.put(`/customers/${customerId}`, payload);
            } else {
                res = await axios.post(`/customers`, payload);
            }

            if (res.data && res.data.success) {
                onSaved && onSaved(res.data.customer);
                setEditing(false);
                // refresh details
                setDetails(prev => ({ ...prev, customer: res.data.customer }));
            } else {
                // show generic error
                setErrors({ form: res.data?.message || 'Failed to save' });
            }
        } catch (e) {
            const resp = e.response?.data || {};
            setErrors({ form: resp.message || 'Failed to save' });
        } finally {
            setLoading(false);
        }
    };

    if (!open) return null;

    return (
        <div className="modal fade show d-block" tabIndex="-1" role="dialog">
            <div className="modal-dialog modal-lg" role="document">
                <div className="modal-content">
                    <div className="modal-header">
                        <div>
                            <h5 className="modal-title">Customer Details</h5>
                            <div className="text-muted small">View customer information and history</div>
                        </div>
                        <button type="button" className="btn-close" aria-label="Close" onClick={onClose}></button>
                    </div>
                    <div className="modal-body">
                        {loading && <div>Loading...</div>}

                        {!loading && details && (
                            <div>
                                <div className="card p-3 mb-3">
                                    <div className="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 className="mb-1">Basic Information</h6>
                                            <div className="fw-bold">{details.customer.name}</div>
                                            <div className="text-muted small">{details.customer.mobile}{details.customer.email ? ` — ${details.customer.email}` : ''}</div>
                                            <div className="mt-2 text-muted small">
                                                {details.customer.whatsapp_mobile && <div>WhatsApp: {details.customer.whatsapp_mobile}</div>}
                                                {details.customer.company && <div>Company: {details.customer.company}</div>}
                                                {details.customer.id_no && <div>ID: {details.customer.id_no}</div>}
                                            </div>
                                        </div>
                                        <div className="text-end">
                                            <button className="btn btn-sm btn-outline-secondary me-2" onClick={() => setEditing(!editing)}>{editing ? 'Cancel' : 'Edit'}</button>
                                        </div>
                                    </div>
                                </div>

                                {editing && (
                                    <div className="mt-3">
                                        {errors.form && <div className="alert alert-danger">{errors.form}</div>}
                                        <div className="mb-2">
                                            <label className="form-label">Name</label>
                                            <input className="form-control form-control-sm" value={form.name} onChange={(e) => setForm(f => ({ ...f, name: e.target.value }))} />
                                        </div>
                                        <div className="mb-2">
                                            <label className="form-label">Mobile</label>
                                            <input className="form-control form-control-sm" value={form.mobile} onChange={(e) => setForm(f => ({ ...f, mobile: e.target.value }))} />
                                        </div>
                                        <div className="mb-2">
                                            <label className="form-label">WhatsApp</label>
                                            <input className="form-control form-control-sm" value={form.whatsapp_mobile} onChange={(e) => setForm(f => ({ ...f, whatsapp_mobile: e.target.value }))} />
                                        </div>
                                        <div className="mb-2">
                                            <label className="form-label">Email</label>
                                            <input className="form-control form-control-sm" value={form.email} onChange={(e) => setForm(f => ({ ...f, email: e.target.value }))} />
                                        </div>
                                        <div className="mb-2">
                                            <label className="form-label">Company</label>
                                            <input className="form-control form-control-sm" value={form.company} onChange={(e) => setForm(f => ({ ...f, company: e.target.value }))} />
                                        </div>
                                        <div className="mb-2">
                                            <label className="form-label">Nationality</label>
                                            <input className="form-control form-control-sm" value={form.nationality} onChange={(e) => setForm(f => ({ ...f, nationality: e.target.value }))} />
                                        </div>
                                        <div className="mb-2">
                                            <label className="form-label">ID Number</label>
                                            <input className="form-control form-control-sm" value={form.id_no} onChange={(e) => setForm(f => ({ ...f, id_no: e.target.value }))} />
                                        </div>
                                        <div className="d-flex justify-content-end gap-2 mt-2">
                                            <button className="btn btn-secondary btn-sm" onClick={() => setEditing(false)}>Cancel</button>
                                            <button className="btn btn-primary btn-sm" onClick={save} disabled={loading}>{loading ? 'Saving...' : 'Save'}</button>
                                        </div>
                                    </div>
                                )}

                                {!editing && (
                                    <div className="mt-3">
                                        <h6 className="mb-2">Sales Summary</h6>
                                        <div className="d-flex gap-2 mb-3">
                                            <div className="p-2 bg-light rounded flex-fill text-center">
                                                <div className="h4 mb-0">{details.total_sales ?? 0}</div>
                                                <small className="text-muted">Total Sales</small>
                                            </div>
                                            <div className="p-2 bg-light rounded flex-fill text-center">
                                                <div className="h4 mb-0">{details.total_amount ? `₹${Number(details.total_amount).toFixed(2)}` : 'N/A'}</div>
                                                <small className="text-muted">Total Amount</small>
                                            </div>
                                            <div className="p-2 bg-light rounded flex-fill text-center">
                                                <div className="h6 mb-0">{details.last_purchase || 'N/A'}</div>
                                                <small className="text-muted">Last Purchase</small>
                                            </div>
                                        </div>

                                        <h6 className="mb-2">Recent Sales</h6>
                                        <div className="list-group">
                                            {Array.isArray(details.recent_sales) && details.recent_sales.length > 0 ? (
                                                details.recent_sales.map((s) => (
                                                    <div key={s.id} className="list-group-item d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <div className="fw-bold">Sale #{s.invoice_no}</div>
                                                            <div className="text-muted small">{s.date} • {s.items_count ?? s.items?.length ?? 0} items</div>
                                                        </div>
                                                        <div className="text-end">
                                                            <div className="fw-bold">₹{Number(s.total || 0).toFixed(2)}</div>
                                                            <div className="text-muted small">{s.status || ''}</div>
                                                        </div>
                                                    </div>
                                                ))
                                            ) : (
                                                <div className="text-muted">No recent sales found.</div>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        {!loading && !details && (
                            <div className="text-muted">Customer details not available.</div>
                        )}
                    </div>
                    <div className="modal-footer">
                        <button type="button" className="btn btn-secondary" onClick={onClose}>Close</button>
                    </div>
                </div>
            </div>
        </div>
    );
}
