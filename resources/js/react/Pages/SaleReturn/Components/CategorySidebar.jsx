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
        <aside className="categories-sidebar p-2 h-100" style={{ minHeight: '80vh' }}>
            <div className="card">
                <div className="card-body p-2">
                    <h6 className="fw-bold mb-2">Categories</h6>
                    <div className="list-group category-list" style={{ maxHeight: '70vh', overflow: 'auto' }}>
                        {categories.map((c) => (
                            <button
                                key={c.id}
                                type="button"
                                className={`list-group-item list-group-item-action ${isSelected(c.id) ? 'active' : ''}`}
                                onClick={() => {
                                    if (Array.isArray(selectedId)) {
                                        const idNum = Number(c.id);
                                        const exists = selectedId.includes(idNum);
                                        const next = exists ? selectedId.filter(i => i !== idNum) : [...selectedId, idNum];
                                        onSelect?.(next);
                                    } else {
                                        onSelect?.(Number(c.id));
                                    }
                                }}
                            >
                                {c.name}
                            </button>
                        ))}
                    </div>
                </div>
            </div>
        </aside>
    );
}
