import React, { useEffect, useState } from "react";
import axios from "axios";

export default function AddCustomerModal({ open, onClose, customerId, onSaved }) {
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState({});
    const [countries, setCountries] = useState([]);

    const [form, setForm] = useState({
        account_type: "customer",
        name: "",
        mobile: "",
       customer_type_id: "",   // ✅ ADD THIS
        whatsapp_mobile: "",
        email: "",
        company: "",
        dob: "",
        id_no: "",
        nationality: "",
    });

    useEffect(() => {
        if (!open) return;

        axios.get("/customers/meta").then(res => {
            setCountries(res.data.countries || []);
        });

        if (customerId) {
            setLoading(true);
            axios.get(`/customers/${customerId}`)
                .then(res => setForm(res.data))
                .finally(() => setLoading(false));
        }
    }, [open, customerId]);

    const handleChange = (e) => {
        setForm({ ...form, [e.target.name]: e.target.value });
        setErrors({ ...errors, [e.target.name]: null });
    };

    const validate = () => {
        const e = {};

        if (!form.name.trim()) {
            e.name = "Name is required";
        }

        if (!form.mobile.trim()) {
            e.mobile = "Mobile number is required";
        } else if (!/^[0-9]{8,15}$/.test(form.mobile)) {
            e.mobile = "Invalid mobile number";
        }

        return e;
    };

    const save = async (addNew = false) => {
    const validationErrors = validate();
    if (Object.keys(validationErrors).length) {
        setErrors(validationErrors);
        return;
    }

    setLoading(true);

    try {
        const res = await axios.post("/customers", {
            ...form,
            account_type: "asset",
            model: "customer",
        });

        if (!res.data.success) {
            setErrors({ form: res.data.message });
            return;
        }

        onSaved?.(res.data.data);

        if (addNew) {
            setForm({ ...form, name: "", mobile: "" });
        } else {
            onClose();
        }
    } catch {
        setErrors({ form: "Server error" });
    } finally {
        setLoading(false);
    }
};


    if (!open) return null;

    return (
        <div className="modal fade show d-block">
            <div className="modal-dialog modal-lg">
                <div className="modal-content">

                    {/* HEADER */}
                    <div className="modal-header bg-primary text-white">
                        <h5 className="modal-title">
                            <i className="fa fa-user-plus me-2" />
                            Add Customer
                        </h5>
                        <button className="btn-close btn-close-white" onClick={onClose} />
                    </div>

                    {/* BODY */}
                    <div className="modal-body p-4">

                        <div className="row g-3">
                            <div className="col-md-8">
                                <label className="form-label">Full Name *</label>
                                <input
                                    name="name"
                                    className={`form-control ${errors.name ? "is-invalid" : ""}`}
                                    value={form.name}
                                    onChange={handleChange}
                                />
                                {errors.name && <div className="invalid-feedback">{errors.name}</div>}
                            </div>

                            <div className="col-md-4">
                                <label className="form-label">Mobile *</label>
                                <input
                                    name="mobile"
                                    className={`form-control ${errors.mobile ? "is-invalid" : ""}`}
                                    value={form.mobile}
                                    onChange={handleChange}
                                />
                                {errors.mobile && <div className="invalid-feedback">{errors.mobile}</div>}
                            </div>

                            <div className="col-md-6">
                                <label className="form-label">WhatsApp</label>
                                <input name="whatsapp_mobile" className="form-control" value={form.whatsapp_mobile} onChange={handleChange} />
                            </div>

                            <div className="col-md-6">
                                <label className="form-label">Email</label>
                                <input name="email" className="form-control" value={form.email} onChange={handleChange} />
                            </div>

                            <div className="col-md-6">
                                <label className="form-label">Company</label>
                                <input name="company" className="form-control" value={form.company} onChange={handleChange} />
                            </div>

                            <div className="col-md-3">
                                <label className="form-label">DOB</label>
                                <input type="date" name="dob" className="form-control" value={form.dob} onChange={handleChange} />
                            </div>

                            <div className="col-md-3">
                                <label className="form-label">Nationality</label>
                                <select name="nationality" className="form-select" value={form.nationality} onChange={handleChange}>
                                    <option value="">Select</option>
                                    {countries.map(c => (
                                        <option key={c.code} value={c.code}>{c.name}</option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {errors.form && <div className="alert alert-danger mt-3">{errors.form}</div>}
                    </div>

                    {/* FOOTER */}
                    <div className="modal-footer bg-light">
                        <button className="btn btn-secondary" onClick={onClose}>Cancel</button>
                        <button className="btn btn-success" onClick={() => save(true)} disabled={loading}>
                            Save & Add New
                        </button>
                        <button className="btn btn-primary" onClick={() => save(false)} disabled={loading}>
                            Save Customer
                        </button>
                    </div>

                </div>
            </div>
        </div>
    );
}
