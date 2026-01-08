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
                                className={`list-group-item list-group-item-action ${selectedId == c.id ? 'active' : ''}`}
                                onClick={() => onSelect?.(Number(c.id))}
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
