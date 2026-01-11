import React, { useEffect, useState } from "react";
import axios from "axios";

export default function SubCategorySelect({ categoryId, selectedSubId, onSelect }) {
    const [subCategories, setSubCategories] = useState([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (!categoryId) {
            setSubCategories([]);
            return;
        }

        setLoading(true);
        // If categoryId is an array, request combined subcategories via `category_ids` query param
        const url = `/categories/categories/measurement/${Array.isArray(categoryId) ? (categoryId[0] || 0) : categoryId}/subcategories`;
        const params = Array.isArray(categoryId) ? { category_ids: categoryId.join(',') } : {};

        axios.get(url, { params })
            .then(res => setSubCategories(res.data || []))
            .catch(() => setSubCategories([]))
            .finally(() => setLoading(false));
    }, [categoryId]);

    // Normalize selectedSubId to array for multi-select support
    const selectedArray = Array.isArray(selectedSubId) ? selectedSubId.map(Number) : (selectedSubId ? [Number(selectedSubId)] : []);

    const handleToggle = (id) => {
        const next = selectedArray.includes(id) ? selectedArray.filter(i => i !== id) : [...selectedArray, id];
        onSelect?.(next);
    };

    return (
        <div className="mb-2">
            <label className="form-label fw-bold">Model</label>
            <div className="border rounded p-2" style={{ maxHeight: '160px', overflow: 'auto' }}>
                {/* Individual selection only (no Select All) */}
                {loading ? (
                    <div className="text-muted">Loading...</div>
                ) : (
                    subCategories.map((s) => (
                        <div key={s.id} className="form-check">
                            <input
                                className="form-check-input"
                                type="checkbox"
                                id={`sc-${s.id}`}
                                checked={selectedArray.includes(Number(s.id))}
                                onChange={() => handleToggle(Number(s.id))}
                            />
                            <label className="form-check-label" htmlFor={`sc-${s.id}`}>{s.name}</label>
                        </div>
                    ))
                )}
            </div>
            <small className="text-muted">Select one or more models</small>
        </div>
    );
}
