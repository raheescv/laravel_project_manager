import React, { useEffect, useState } from "react";
import axios from "axios";

export default function CategorySidebar({ selectedId, onSelect }) {
    const [categories, setCategories] = useState([]);

    useEffect(() => {
        let mounted = true;
        axios.get("/categories/categorie").then((res) => {
            if (!mounted) return;
            setCategories(res.data || []);
        }).catch(() => {
            setCategories([]);
        });

        return () => { mounted = false; };
    }, []);

    // selectedId can be a number or an array of numbers
    const isSelected = (id) => {
        if (Array.isArray(selectedId)) return selectedId.includes(Number(id));
        return Number(selectedId) === Number(id);
    };

    return (
        <aside className="categories-sidebar h-100" style={{ minHeight: '8vh', margin: 0, padding: 0 }}>
            <div className="card" style={{ margin: 0, boxShadow: 'none', border: 'none' }}>
                <div className="card-body p-1" style={{ padding: 0 }}>
                    <h6 className="fw-bold mb-1" style={{ marginBottom: 4 }}>Categories</h6>
                    <div className="list-group category-list" style={{ maxHeight: '70vh', overflow: 'auto', margin: 0 }}>
                        {categories.map((c) => (
                            <label key={c.id} className="list-group-item d-flex align-items-center" style={{ cursor: 'pointer', margin: 0, padding: '2px 0' }}>
                                <input
                                    type="checkbox"
                                    checked={isSelected(c.id)}
                                    onChange={() => {
                                        if (Array.isArray(selectedId)) {
                                            const idNum = Number(c.id);
                                            const exists = selectedId.includes(idNum);
                                            const next = exists ? selectedId.filter(i => i !== idNum) : [...selectedId, idNum];
                                            onSelect?.(next);
                                        } else {
                                            onSelect?.([Number(c.id)]);
                                        }
                                    }}
                                    style={{ marginRight: 8 }}
                                />
                                {c.name}
                            </label>
                        ))}
                    </div>
                </div>
            </div>
        </aside>
    );
}
