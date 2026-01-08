import React, { useEffect, useState } from "react";
import axios from "axios";

export default function CustomerDetailsModal({ open, onClose, customerId, initialDetails = null, onSaved = null }) {
    const [loading, setLoading] = useState(false);
    const [editing, setEditing] = useState(false);
    const [details, setDetails] = useState(initialDetails);
    const [form, setForm] = useState({ name: "", mobile: "", email: "" });
    const [errors, setErrors] = useState({});

    useEffect(() => {
        if (!open) return;
        let mounted = true;
        if (initialDetails) {
            setDetails(initialDetails);
            const c = initialDetails.customer || {};
            setForm({ name: c.name || "", mobile: c.mobile || "", email: c.email || "" });
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
                    setForm({ name: c.name || "", mobile: c.mobile || "", email: c.email || "" });
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
            email: form.email,
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
                        <h5 className="modal-title">Customer Details</h5>
                        <button type="button" className="btn-close" aria-label="Close" onClick={onClose}></button>
                    </div>
                    <div className="modal-body">
                        {loading && <div>Loading...</div>}

                        {!loading && details && (
                            <div>
                                <div className="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 className="mb-0">{details.customer.name}</h5>
                                        <small className="text-muted">{details.customer.mobile} {details.customer.email ? ` — ${details.customer.email}` : ''}</small>
                                        <div className="mt-2">
                                            <small className="text-muted">Orders: {details.total_sales} — Total: ₹{Number(details.total_amount || 0).toFixed(2)}</small>
                                        </div>
                                    </div>
                                    <div>
                                        <button className="btn btn-sm btn-outline-secondary me-2" onClick={() => setEditing(!editing)}>{editing ? 'Cancel' : 'Edit'}</button>
                                       
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
                                            <label className="form-label">Email</label>
                                            <input className="form-control form-control-sm" value={form.email} onChange={(e) => setForm(f => ({ ...f, email: e.target.value }))} />
                                        </div>
                                        <div className="d-flex justify-content-end gap-2 mt-2">
                                            <button className="btn btn-secondary btn-sm" onClick={() => setEditing(false)}>Cancel</button>
                                            <button className="btn btn-primary btn-sm" onClick={save} disabled={loading}>{loading ? 'Saving...' : 'Save'}</button>
                                        </div>
                                    </div>
                                )}

                                {!editing && (
                                    <div className="mt-3">
                                        <h6>Recent Sales</h6>
                                        <div className="list-group">
                                            {Array.isArray(details.recent_sales) && details.recent_sales.length > 0 ? (
                                                details.recent_sales.map(s => (
                                                    <div key={s.id} className="list-group-item d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <div className="fw-bold">{s.invoice_no}</div>
                                                            <div className="text-muted small">{s.date} — ₹{Number(s.total).toFixed(2)}</div>
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
