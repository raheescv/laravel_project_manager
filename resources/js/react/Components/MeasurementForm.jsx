import React, { useState } from "react";
import { useForm } from "@inertiajs/react";

export default function MeasurementForm({ customers, categories, templates }) {
    const [selectedCustomer, setCustomer] = useState("");
    const [selectedCategory, setCategory] = useState("");

    const { data, setData, post } = useForm({
        customer_id: "",
        category_id: "",
        template_ids: [],
        values: {}
    });

    const filtered = templates.filter(t => t.category_id == selectedCategory);

    const handleSubmit = e => {
        e.preventDefault();
        post("/settings/category/measurements/save");
    };

    return (
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
                    <option value="">-- Select Customer --</option>
                    {customers.map(c => (
                        <option key={c.id} value={c.id}>{c.name}</option>
                    ))}
                </select>
            </div>

            <div className="col-md-6">
                <label>Category</label>
                <select
                    className="form-control"
                    value={selectedCategory}
                    onChange={e => {
                        setCategory(e.target.value);
                        setData("category_id", e.target.value);
                        setData("template_ids", filtered.map(t => t.id));
                    }}
                >
                    <option value="">-- Select Category --</option>
                    {categories.map(c => (
                        <option key={c.id} value={c.id}>{c.name}</option>
                    ))}
                </select>
            </div>

            {selectedCustomer && selectedCategory && (
                <form className="col-md-12 mt-3" onSubmit={handleSubmit}>
                    {filtered.map(t => (
                        <div className="mb-2" key={t.id}>
                            <label>{t.name}</label>
                            <input
                                type="text"
                                className="form-control"
                                placeholder={`Enter ${t.name}`}
                                onChange={e =>
                                    setData("values", {
                                        ...data.values,
                                        [t.id]: e.target.value
                                    })
                                }
                            />
                        </div>
                    ))}

                    <button type="submit" className="btn btn-primary mb-3">
                        Save
                    </button>
                </form>
            )}
        </div>
    );
}
