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
      axios.get(`/categories/categories/measurement/${categoryId}/subcategories`)

            .then(res => setSubCategories(res.data || []))
            .catch(() => setSubCategories([]))
            .finally(() => setLoading(false));
    }, [categoryId]);

    return (
        <div className="mb-2">
            <label className="form-label fw-bold">Model</label>
            <select
                className="form-control form-control-sm"
                value={selectedSubId || ""}
                onChange={(e) => onSelect(Number(e.target.value) || null)}
                disabled={!categoryId || loading}
            >
                <option value="">{loading ? "Loading..." : "-- Select Model --"}</option>
                {subCategories.map((s) => (
                    <option key={s.id} value={s.id}>{s.name}</option>
                ))}
            </select>
        </div>
    );
}
