import React, { useState, useEffect } from "react";
import { useForm } from "@inertiajs/react";

export default function MeasurementForm({ customers = [], categories = [], templates = [], editItem = null }) {
    const [selectedCustomer, setCustomer] = useState("");
    const [selectedCategory, setCategory] = useState("");
    const [localErrors, setLocalErrors] = useState({});

    const { data, setData, post, processing } = useForm({
        customer_id: "",
        category_id: "",
        values: {}
    });

    

    // ⭐ Prefill on edit
useEffect(() => {
    if (editItem) {
        // Prefill for edit
        setCustomer(editItem.customer_id);
        setCategory(editItem.category_id);
        setData({
            customer_id: editItem.customer_id,
            category_id: editItem.category_id,
            values: editItem.values ?? {},
        });
    }
}, [editItem]);


    const filtered = templates.filter(t => String(t.category_id) === String(selectedCategory));

    // ⭐ Validation
    const validateForm = () => {
        let temp = {};

        if (!data.customer_id) temp.customer_id = "Customer is required";
        if (!data.category_id) temp.category_id = "Category is required";

        filtered.forEach(t => {
            if (!data.values[t.id] || data.values[t.id].trim() === "") {
                temp[`values.${t.id}`] = `${t.name} is required`;
            }
        });

        setLocalErrors(temp);
        return Object.keys(temp).length === 0;
    };

const handleSubmit = (e) => {
    e.preventDefault();
    if (!validateForm()) return;

    post("/settings/category/measurements/save", {
        onSuccess: () => {
            // Reset form state after save
            setCustomer("");
            setCategory("");
            setData({
                customer_id: "",
                category_id: "",
                values: {},
            });
            setLocalErrors({});

            // Reset URL (important if you were editing before)
            router.replace(route("settings::category::measurements.data"));
        },
    });
};


    return (
        <div className="card p-3 mb-4">
            <div className="row mb-3">
                <div className="col-md-6">
                    <label>Customer</label>
                    <select
                        className="form-control"
                        value={selectedCustomer}
                        onChange={e => {
                            setCustomer(e.target.value);
                            setData("customer_id", e.target.value);
                        }}
                    >
                        <option value="">-- Select --</option>
                        {customers.map(c => (
                            <option key={c.id} value={c.id}>{c.name}</option>
                        ))}
                    </select>
                    {localErrors.customer_id && <p className="text-danger">{localErrors.customer_id}</p>}
                </div>

                <div className="col-md-6">
                    <label>Category</label>
                    <select
                        className="form-control"
                        value={selectedCategory}
                        onChange={e => {
                            setCategory(e.target.value);
                            setData("category_id", e.target.value);
                        }}
                    >
                        <option value="">-- Select --</option>
                        {categories.map(c => (
                            <option key={c.id} value={c.id}>{c.name}</option>
                        ))}
                    </select>
                    {localErrors.category_id && <p className="text-danger">{localErrors.category_id}</p>}
                </div>
            </div>

            {selectedCustomer && selectedCategory && (
                <form onSubmit={handleSubmit}>
                    {filtered.map(t => (
                        <div className="mb-2" key={t.id}>
                            <label>{t.name}</label>
                            <input
                                type="text"
                                className="form-control"
                                value={data.values[t.id] ?? ""}
                                onChange={e =>
                                    setData("values", {
                                        ...data.values,
                                        [t.id]: e.target.value
                                    })
                                }
                            />
                            {localErrors[`values.${t.id}`] && (
                                <p className="text-danger">{localErrors[`values.${t.id}`]}</p>
                            )}
                        </div>
                    ))}

                    <button className="btn btn-primary" disabled={processing}>
                        {editItem ? "Update" : "Save"}
                    </button>
                </form>
            )}
        </div>
    );
}
