import React, { useEffect, useState } from "react";
import axios from "axios";

export default function MainCategorySidebar(props) {
    const [categories, setCategories] = useState([]);

    useEffect(() => {
        let mounted = true;
        axios.get("/categories/maincategorie").then((res) => {
            if (!mounted) return;
            setCategories(res.data || []);
        }).catch(() => {
            setCategories([]);
        });
        return () => { mounted = false; };
    }, []);

    const selectedId = Array.isArray(props.selectedId) ? props.selectedId[0] : props.selectedId;

    return (
        <aside className="categories-sidebar p-1 h-100" style={{ minHeight: '80vh', marginBottom: 0 }}>
            <div className="card mb-1" style={{ marginBottom: 0 }}>
                <div className="card-body p-2 pb-1" style={{ paddingBottom: 4 }}>
                    <h6 className="fw-bold mb-1">Main Categories</h6>
                    <div className="list-group category-list" style={{ maxHeight: '70vh', overflow: 'auto', marginBottom: 0 }}>
                        <button
                            type="button"
                            className={`list-group-item list-group-item-action${!selectedId ? ' active' : ''}`}
                            onClick={() => props.onSelect([])}
                            style={{ marginBottom: 2 }}
                        >
                            All Products
                        </button>
                        {categories.map((c) => (
                            <button
                                key={c.id}
                                type="button"
                                className={`list-group-item list-group-item-action${selectedId === c.id ? ' active' : ''}`}
                                onClick={() => props.onSelect([c.id])}
                                style={{ marginBottom: 2 }}
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
